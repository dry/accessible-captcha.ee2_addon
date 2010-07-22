<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Twig {
	
	/*
	 * Private variable to contain CI instance
	 */
	private $EE;
	
	/*
	 * Variable to contain Twig instance
	 */
	public $twig;
	
	/*
	 * An array or string of locations where templates may be located
	 */
	private $_template_dirs;
	
	/*
	 * Location of the template compile cache directory
	 */
	private $_cache_dir = NULL;
	
	/*
	 * Debug errors shown on template
	 */
	private $_debug = TRUE;
	
	public function __construct($string = FALSE)
	{
		$this->EE =& get_instance();
		
		$this->_template_dirs = $this->EE->load->_ci_view_path;

		require_once $this->EE->load->_ci_library_paths[0].'libraries/Twig/Autoloader'.EXT;
		Twig_Autoloader::register();
		
		if ($string)
		{
			$loader = new Twig_Loader_String();
		}
		else
		{
			$loader = new Twig_Loader_Filesystem($this->_template_dirs);
		}
		
		$this->twig = new Twig_Environment($loader, array(
					'cache' => $this->_cache_dir,
					'debug' => $this->_debug
				)
			);
		$escaper = new Twig_Extension_Escaper(true);
		$this->twig->addExtension($escaper);

		require_once $this->EE->load->_ci_library_paths[0].'libraries/Twig_filters/Twig_filters'.EXT;
		$this->twig->addFilter('date', new Twig_Filter_Function('date_filter'));
	}
	
	public function render($template, $data, $return = FALSE)
	{
		$output = $this->twig->loadTemplate($template);
		log_message('debug', sprintf('Twig rendering template %s', $template));

		if ($return)
		{
			return $output->render($data);
		}
		else
		{
			$output->display($data);
		}
	}
	
	public function set_cache_dir($dir = '')
	{
		if ($dir)
		{
			$this->_cache_dir = $dir;
		}
	}
	
	public function get_cache_dir()
	{
		return $this->_cache_dir;
	}
}

/* End of file Twig.php */
/* Location: ./system/libraries/Twig.php */