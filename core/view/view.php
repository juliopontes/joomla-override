<?php
// no direct access
defined('_JEXEC') or die;

/**
 * View class
 *
 * @package     Joomla.Legacy
 * @subpackage  Module
 * @since       11.1
 */
abstract class JView extends LIB_JViewDefault
{
	/**
	 * Register new paths to helpers and templates
	 * 
	 * @var array
	 */
	static private $_codePaths = array('helper' => array(), 'template' => array());

	/**
	 * Load a template file -- first look in the templates folder for an override
	 *
	 * @param   string  $tpl  The name of the template source file; automatically searches the template paths and compiles as needed.
	 *
	 * @return  string  The output of the the template script.
	 *
	 * @since   11.1
	 */
	public function loadTemplate($tpl = null)
	{
		if (!empty(self::$_codePaths['template']))
		{
			foreach (self::$_codePaths['template'] as $codePool)
			{
				$this->addTemplatePath($codePool.'/views/'.$this->getName().'/tmpl/');
			}
		}
		
		return parent::loadTemplate($tpl);
	}
	
	/**
	 * Load a helper file
	 *
	 * @param   string  $hlp  The name of the helper source file automatically searches the helper paths and compiles as needed.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function loadHelper($hlp = null)
	{
		if (!empty(self::$_codePaths['helper']))
		{
			foreach (self::$_codePaths['helper'] as $codePool)
			{
				$this->addTemplatePath($codePool.'/helpers/');
			}
		}
		
		return parent::loadHelper($hlp);
	}

	/**
	 * Add new helper path
	 * 
	 * @param string $path
	 */
	static public function addViewHelperPath($path = null)
	{
		if (is_null($path))
		{
			return self::$_codePaths['helper'];
		}
		
		array_push(self::$_codePaths['helper'], $path);
		
		return self::$_codePaths['helper'];
	}

	/**
	 * Add new template path
	 * 
	 * @param string $path
	 */
	static public function addViewTemplatePath($path = null)
	{
		if (is_null($path))
		{
			return self::$_codePaths['template'];
		}
		
		array_push(self::$_codePaths['template'], $path);
		
		return self::$_codePaths['template'];
	}
}