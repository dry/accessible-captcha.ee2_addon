<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Pur Accessible Captcha
 *
 * An ExpressionEngine Extension that changes the default graphic
 * captcha into a question & answer based one.
 *
 * @package		Pur_accessible_captcha
 * @author		Greg Salt <greg@purple-dogfish.co.uk>
 * @copyright	Copyright (c) 2009 Purple Dogfish Ltd
 * @license		http://creativecommons.org/licenses/by-sa/3.0/
 * @link		http://www.purple-dogfish.co.uk
 * @since		Version 2.0
 * 
 */

/**
 * Changelog
 * 
 * Version 2.0 20091204
 * --------------------
 * Initial public release
 */
class Pur_accessible_captcha_ext {

	var $name            = 'Accessible Captcha';
	var $version         = '2.0';
	var $description     = 'Convert the existing graphic captcha into an accessible version using questions and answers';
	var $settings_exist  = 'y';
	var $docs_url        = 'http://www.purple-dogfish.co.uk/free-stuff/accessible-captcha';

	var $settings        = array();
	
	/**
	 * Constructor
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 Purple Dogfish Ltd
	 * @access		Public
	 */
	function Pur_accessible_captcha_ext($settings = '')
	{
		$this->EE =& get_instance();
		
    	$this->settings = $settings;
	}
	/* End of Pur_accessible_captcha_ext */
	
	/**
	 * Activate Extension
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 Purple Dogfish Ltd
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
	/* End of activate_extension */

	/**
	 * Update Extension
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 Purple Dogfish Ltd
	 * @access		Public
	 */
	function update_extension($current = '')
	{
    	return TRUE;
	}
	/* End of update_extension */

	/**
	 * Disable Extensions
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 Purple Dogfish Ltd
	 * @access		Public
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->delete('extensions');
	}
	/* End of disable_extension */
	
	/**
	 * Settings
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 Purple Dogfish Ltd
	 * @access		Public
	 */
	function settings()
	{
		$this->EE->lang->loadfile('pur_accessible_captcha');
		
		$settings = array();

		$settings['hints'] = array('r', array('yes' => 'yes', 'no' => 'no'), 'no');
		$settings['hints_wrap'] = array('r', array('yes' => "yes", 'no' => "no"), 'no');
    	$settings['question1'] = array('t', array('rows' => 2), $this->EE->lang->line('warning_question'));
    	$settings['answer1'] = array('t', array('rows' => 1), $this->EE->lang->line('warning_answer'));
    	$settings['question2'] = array('t', array('rows' => 2), '');
    	$settings['answer2'] = array('t', array('rows' => 1), '');
    	$settings['question3'] = array('t', array('rows' => 2), '');
    	$settings['answer3'] = array('t', array('rows' => 1), '');
		$settings['question4'] = array('t', array('rows' => 2), '');
		$settings['answer4'] = array('t', array('rows' => 1), '');
		$settings['question5'] = array('t', array('rows' => 2), '');
		$settings['answer5'] = array('t', array('rows' => 1), '');
		$settings['question6'] = array('t', array('rows' => 2), '');
		$settings['answer6'] = array('t', array('rows' => 1), '');
		$settings['question7'] = array('t', array('rows' => 2), '');
		$settings['answer7'] = array('t', array('rows' => 1), '');
		$settings['question8'] = array('t', array('rows' => 2), '');
		$settings['answer8'] = array('t', array('rows' => 1), '');

    	return $settings;
	}
	/* End of settings */

	/**
	 * Create Captcha
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 Purple Dogfish Ltd
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
	/* End of create_captcha */
	
	/**
	 * Lang Override
	 *
	 * @author		Greg Salt <greg@purple-dogfish.co.uk>
	 * @copyright	Copyright (c) 2009 Purple Dogfish Ltd
	 * @access		Public
	 */
	function lang_override()
	{
		// Only override the language keys if the extension has been setup correctly
		if (count($this->settings) < 2)
		{
			return;
		}
		
		$this->EE->lang->loadfile('pur_accessible_captcha');
		$captcha_required = $this->EE->lang->line('captcha_required');
		$captcha_incorrect = $this->EE->lang->line('captcha_incorrect');
		$this->EE->lang->loadfile('core');
		
		// Override the lang.core.php keys for captchas
		$this->EE->lang->language['captcha_required'] = $captcha_required;
		$this->EE->lang->language['captcha_incorrect'] = $captcha_incorrect;
	}
	/* End of lang_override */
}
/* End of file pur_accessible_captcha.php */
/* Location: ./system/expressionengine/third_party/pur_accessible_captcha/pur_accessible_captcha.php */