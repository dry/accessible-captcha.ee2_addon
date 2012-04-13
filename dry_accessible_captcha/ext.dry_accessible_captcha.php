<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * An ExpressionEngine Extension that changes the default graphic
 * captcha into a question & answer based one.
 *
 * @package		Accessible Captcha
 * @author		Greg Salt <drylouvre> <greg@purple-dogfish.co.uk>
 * @copyright	Copyright (c) 2009 - 2012 Purple Dogfish Ltd
 * @license		http://www.purple-dogfish.co.uk/licence/free
 * @link		http://www.purple-dogfish.co.uk/free-stuff/accessible-captcha-2.x
 * @since		Version 2.1.
 * 
 */

/**
 * Changelog
 * Version 2.3 20120413
 * Enabled use of dollar signs in captcha questions
 * Cleaned up code to remove legacy PHP4 stuff
 *
 * Version 2.2 20110628
 * Fixed bug generated in EE 2.2.0
 * Fixed rendering of add pair button
 * Enabled 'settings saved' flash message
 *
 * Version 2.1 20100724
 * Replaced 'pur' prefix with 'dry'
 * Implemented dynamic question and answer pairs
 * Fully MSM compatible i.e. use different Q&A pairs in sites
 * 
 * Version 2.0 20091204
 * --------------------
 * Initial public release
 */
class Dry_accessible_captcha_ext {

	public $name = 'Accessible Captcha';
	public $version = '2.3';
	public $description = 'Convert the default graphic captcha into an accessible (and more secure) version using questions and answers';
	public $settings_exist = 'y';
	public $docs_url = 'http://www.purple-dogfish.co.uk/free-stuff/accessible-captcha-2.x';

	public $settings = array();
	
	/**
	 * Constructor
	 *
	 * @access public
	 *
	 * @return \Dry_accessible_captcha_ext
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		
    	$this->settings = $settings;
	}
	
	/**
	 * Activate Extension
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function activate_extension()
	{
		$data = array();

		$data['class']			= __CLASS__;
        $data['method']			= "create_captcha";
        $data['hook']     	    = "create_captcha_start";
        $data['settings']	    = "";
		$data['priority']	    = 10;
		$data['version']		= $this->version;
		$data['enabled']		= "y";
		
    	$this->EE->db->insert('extensions', $data);

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
	 * @access public
	 *
	 * @return bool TRUE on update, FALSE otherwise
	 */
	public function update_extension($current = '')
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
	 * @access public
	 *
	 * @return void
	 */
	public function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->delete('extensions');
	}
	
	/**
	 * Settings
	 *
	 * @access public
	 *
	 * @return string Rendered template
	 */
	public function settings_form($current)
	{
		$this->EE->load->library('twig');
		$this->EE->lang->loadfile('dry_accessible_captcha');
		$this->EE->cp->load_package_js('dry_accessible_captcha');
		$this->EE->cp->load_package_css('dry_accessible_captcha');
		
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
		
		$settings_template = array(
			'switched_on' => 'no',
			'hints' => 'no',
			'hints_wrap' => 'no',
			'pairs' => array($site_id => array(
					'question' => $this->EE->lang->line('warning_question'),
					'answer' => $this->EE->lang->line('warning_answer')
				)
			)
		);
		
		$settings = (isset($current[$site_id])) ? $current[$site_id] : $settings_template;

		$data['switched_on'] = $settings['switched_on'];
		$data['hints'] = $settings['hints'];
		$data['hints_wrap'] = $settings['hints_wrap'];
		$data['pairs'] = $settings['pairs'];
		$data['flash_message'] = $this->EE->session->flashdata('message_success');
		
    	return $this->EE->twig->render('settings.html', $data, TRUE);
	}
	
	/**
	 * Save Settings
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function save_settings()
	{
		if (empty($_POST))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}
		
		unset($_POST['submit']);
		$this->EE->lang->loadfile('dry_accessible_captcha');
		$this->EE->load->helper('array');

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
			if ($question !== '')
			{
				$pairs[] = array(
						'question' => $question,
						'answer' => $answers[$index]
					);
			}
		}
		
		if (count($pairs) == 0)
		{
			$pairs[0] = array('question' => '', 'answer' => '');
		}

		$site_id = $this->EE->config->item('site_id');

		$this->EE->db->where('class', __CLASS__);
		$query = $this->EE->db->get('extensions', 1, 0);

		$save_data = array();
		
		if ($query->num_rows() == 1)
		{
			$data = $query->row();
			$save_data = unserialize($data->settings);
		}

		$save_data[$site_id]['switched_on'] = $switched_on;
		$save_data[$site_id]['hints'] = $hints;
		$save_data[$site_id]['hints_wrap'] = $hints_wrap;
		$save_data[$site_id]['pairs'] = $pairs;
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('settings' => serialize($save_data)));

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('preferences_updated'));
		$this->EE->functions->redirect(
			BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=dry_accessible_captcha'
		);
	}

	/**
	 * Create Captcha Hook
	 *
	 * @access public
	 *
	 * @param string Unused by this extension
	 *
	 * @return string Captcha question
	 */
	public function create_captcha($old_word = '')
	{
		unset($old_word);
		$site_id = $this->EE->config->item('site_id');
		
		if ( ! isset($this->settings[$site_id]))
		{
			$this->EE->extensions->end_script = FALSE;
			return;
		}
		
		$settings = $this->settings[$site_id];
		
		if ($settings['switched_on'] == 'no')
		{
			$this->EE->extensions->end_script = FALSE;
			return;
		}
		
		$this->EE->extensions->end_script = TRUE;
		
		$left_wrap = '';
		$right_wrap = '';
		$set = $settings['pairs'];
		$question_pair = array_rand($set);
		$question = $set[$question_pair]['question'];
		
		$data['date'] = time();
		$data['ip_address'] = $this->EE->input->ip_address();
		$data['word'] = $set[$question_pair]['answer'];
		$this->EE->db->insert('captcha', $data);
	
		if($settings['hints_wrap'] == 'yes')
		{
			$left_wrap = '(';
			$right_wrap = ')';
		}
	
		$this->EE->lang->loadfile('dry_accessible_captcha');
		
		if($settings['hints'] == 'yes')
		{
			$line = (strlen($data['word']) == 1) ? 'character_required' : 'characters_required';
			$question .= ' <span class="captcha-hints">'.$left_wrap.strlen($data['word']).' '.$this->EE->lang->line($line).$right_wrap.'</span>';
		}
		
		// Escape dollar signs
		return str_replace('$', '&#36;', $question);
	}
	
	/**
	 * Lang Override
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function lang_override()
	{
		$site_id = $this->EE->config->item('site_id');
		
		if ( ! isset($this->settings[$site_id]))
		{
			$this->EE->extensions->end_script = FALSE;
			return;
		}
		
		$this->EE->lang->loadfile('dry_accessible_captcha');
		$captcha_required = $this->EE->lang->line('captcha_required');
		$captcha_incorrect = $this->EE->lang->line('captcha_incorrect');
		$this->EE->lang->loadfile('core');
		
		// Override the lang.core.php keys for captchas
		$this->EE->lang->language['captcha_required'] = $captcha_required;
		$this->EE->lang->language['captcha_incorrect'] = $captcha_incorrect;
	}
	
	/**
	 * Validate function arguments or redirect
	 *
	 * @access private
	 *
	 * @return void
	 */
	public function _valid_or_redirect()
	{
		$var = func_get_arg(0);
		$tests = func_get_arg(1);
		if (! in_array($var, $tests))
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('save_failure'));
			$this->EE->functions->redirect(
				BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=dry_accessible_captcha'
			);
		}
	}
}
/* End of file ext.dry_accessible_captcha.php */
/* Location: ./third_party/dry_accessible_captcha/ext.dry_accessible_captcha.php */
