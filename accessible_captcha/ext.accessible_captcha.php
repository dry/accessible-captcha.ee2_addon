<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * An ExpressionEngine Extension that changes the default graphic
 * captcha into a question & answer based one.
 *
 * @package		Accessible Captcha
 * @author		Greg Salt <greg@purple-dogfish.co.uk>
 * @copyright	Copyright (c) 2009 - 2010 Purple Dogfish Ltd
 * @license		http://creativecommons.org/licenses/by-sa/3.0/
 * @link		http://www.purple-dogfish.co.uk
 * @since		Version 2.0
 * 
 */

/**
 * Changelog
 * Version 2.1 2010
 * Removed 'pur' prefix from files
 * 
 * Version 2.0 20091204
 * --------------------
 * Initial public release
 */
class Accessible_captcha_ext {

	var $name            = 'Accessible Captcha';
	var $version         = '2.1';
	var $description     = 'Convert the existing graphic captcha into an accessible version using questions and answers';
	var $settings_exist  = 'y';
	var $docs_url        = 'http://www.purple-dogfish.co.uk/free-stuff/accessible-captcha';

	var $settings        = array();
	
	/**
	 * Constructor
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 - 2010 Purple Dogfish Ltd
	 * @access		Public
	 */
	function Accessible_captcha_ext($settings = '')
	{
		$this->EE =& get_instance();
		
    	$this->settings = $settings;
	}
	
	/**
	 * Activate Extension
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 - 2010 Purple Dogfish Ltd
	 * @access		Public
	 */
	function activate_extension()
	{
		$data = array();
		
		//$data['extension_id']	= '';
		$data['class']			= __CLASS__;
        $data['method']			= "create_captcha";
        $data['hook']     	    = "create_captcha_start";
        $data['settings']	    = "";
		$data['priority']	    = 10;
		$data['version']		= $this->version;
		$data['enabled']		= "y";
		
    	$this->EE->db->insert('extensions', $data);

		//$data['extension_id']	= '';
		$data['class']			= __CLASS__;
        $data['method']			= "lang_override";
        $data['hook']     	    = "sessions_end";
        $data['settings']	    = "";
		$data['priority']	    = 10;
		$data['version']		= $this->version;
		$data['enabled']		= "y";
		
    	$this->EE->db->insert('extensions', $data);
	}

	/**
	 * Update Extension
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 - 2010 Purple Dogfish Ltd
	 * @access		Public
	 */
	function update_extension($current = '')
	{
		$status = TRUE;
		
		if ($this->version != $current)
		{
			$data = array();
			$data['version'] = $this->version;
			$this->EE->db->update('extensions', $data, 'version = '.$current);
			
			if($this->EE->db->affected_rows() != 1)
			{
				$status = FALSE;
			}
		}
		
		return $status;
	}

	/**
	 * Disable Extensions
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 - 2010 Purple Dogfish Ltd
	 * @access		Public
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->delete('extensions');
	}
	
	/**
	 * Settings
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 - 2010 Purple Dogfish Ltd
	 * @access		Public
	 */
	function settings_form($current)
	{
		$this->EE->load->library('twig');
		$this->EE->lang->loadfile('accessible_captcha');
		$this->EE->cp->load_package_js('accessible_captcha');
		$this->EE->cp->load_package_css('accessible_captcha');
		
		$this->EE->javascript->output(array('
			AC = {};
			AC.lang_warning = "'.$this->EE->lang->line('warning_no_questions').'"
			AC.lang_warning_question = "'.$this->EE->lang->line('warning_question').'"
			AC.lang_warning_answer = "'.$this->EE->lang->line('warning_answer').'"
		'));
		$this->EE->javascript->compile();
		
		$data = array();
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			$data['XID'] = XID_SECURE_HASH;
		}
		$data['lang'] = $this->EE->lang->language;
		$data['BASE'] = str_replace('amp;', '&', BASE);
		
		$site_id = $this->EE->config->item('site_id');
		$settings = $current[$site_id];
		$data['switched_on'] = $settings['switched_on'];
		$data['hints'] = $settings['hints'];
		$data['hints_wrap'] = $settings['hints_wrap'];
		$data['pairs'] = $settings['pairs'];
		
    	return $this->EE->twig->render('settings.html', $data, TRUE);
	}
	
	/**
	 * Save Settings
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 - 2010 Purple Dogfish Ltd
	 * @access		Public
	 * @return		void
	 */
	function save_settings()
	{
		if (empty($_POST))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}
		
		unset($_POST['submit']);
		$this->EE->lang->loadfile('accessible_captcha');

		$switched_on = $this->EE->input->post('switched_on');
		$hints = $this->EE->input->post('hints');
		$hints_wrap = $this->EE->input->post('hints_wrap');
		
		$this->_valid_or_redirect($switched_on, array('yes', 'no'));
		$this->_valid_or_redirect($hints, array('yes', 'no'));
		$this->_valid_or_redirect($hints_wrap, array('yes', 'no'));
		
		$questions = $this->EE->input->post('questions');
		$answers = $this->EE->input->post('answers');
		$pairs = array();
		foreach($questions AS $index => $question)
		{
			if (isset($answers[$index]))
			{
				$pairs[] = array(
						'question' => $question,
						'answer' => $answers[$index]
					);
			}
		}
		
		$site_id = $this->EE->config->item('site_id');
		
		$data = array();
		$data[$site_id]['switched_on'] = $switched_on;
		$data[$site_id]['hints'] = $hints;
		$data[$site_id]['hints_wrap'] = $hints_wrap;
		$data[$site_id]['pairs'] = $pairs;
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('settings' => serialize($data)));
		
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('preferences_updated'));
		$this->EE->functions->redirect(
			BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=accessible_captcha'
		);
	}

	/**
	 * Create Captcha
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 - 2010 Purple Dogfish Ltd
	 * @access		Public
	 */
	function create_captcha($old_word = '')
	{
		// Only continue if the extension has been setup correctly
		if (count($this->settings) < 2)
		{
			return;
		}
		
		$this->EE->extensions->end_script = TRUE;
		
		$settings = $this->settings;
		
		// Get rid of the Hints and Hints wrap settings
		array_shift($settings);
		array_shift($settings);
		
		ksort($settings);
		
		$answers_array = array_slice($settings, 0, 8);
		$questions_array = array_slice($settings, 8, 8);
		
		$set = array_combine($questions_array, $answers_array);

		$question_count = count($set);
		
		if ($question_count < 8)
		{
			array_pop($set);
		}
		
		$answer = '';

		$seed = array_rand($set);
		
		$question = $seed;
		$answer = $set[$seed];

		$this->EE->db->query("INSERT INTO exp_captcha (date, ip_address, word) VALUES (UNIX_TIMESTAMP(), '".$this->EE->input->ip_address()."', '".$this->EE->db->escape_str($answer)."')");
	
		$this->cached_captcha = $answer;
	
		if($this->settings['hints_wrap'] == 'yes')
		{
			$lw = '(';
			$rw = ')';
		}
	
		$this->EE->lang->loadfile('pur_accessible_captcha');
		
		if($this->settings['hints'] == 'yes')
		{
			$question .= ' <span class="captcha-hints">' . $lw . strlen($answer) . ' ' . $this->EE->lang->line('characters_required') . $rw . '</span>';
		}
		
		return $question;
	}
	
	/**
	 * Lang Override
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 - 2010 Purple Dogfish Ltd
	 * @access		Public
	 */
	function lang_override()
	{
		// Only override the language keys if the extension has been setup correctly
		if (count($this->settings) < 2)
		{
			return;
		}
		
		$this->EE->lang->loadfile('accessible_captcha');
		$captcha_required = $this->EE->lang->line('captcha_required');
		$captcha_incorrect = $this->EE->lang->line('captcha_incorrect');
		$this->EE->lang->loadfile('core');
		
		// Override the lang.core.php keys for captchas
		$this->EE->lang->language['captcha_required'] = $captcha_required;
		$this->EE->lang->language['captcha_incorrect'] = $captcha_incorrect;
	}
	
	function _valid_or_redirect()
	{
		$var = func_get_arg(0);
		$tests = func_get_arg(1);
		if (! in_array($var, $tests))
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('save_failure'));
			$this->EE->functions->redirect(
				BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=accessible_captcha'
			);
		}
	}
}
/* End of file accessible_captcha.php */
/* Location: ./system/expressionengine/third_party/accessible_captcha/accessible_captcha.php */