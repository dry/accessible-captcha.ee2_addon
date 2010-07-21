<?php if ( ! defined('BASEPATH')) die('No direct script access allowed');

class Project_Twig_Extension extends Twig_Extension {

	public function getFilters()
	{
		return array(
			'date' => new Twig_Filter_Method($this, 'date_filter')
		);
	}

	public function getName()
	{
		return 'project';
	}
}

function date_filter($date, $format)
{
	return date($format, $date);
}