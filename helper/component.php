<?php
/**
* @package Joomla.Plugin
* @subpackage System.joomlaoverride
*
* @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
* @license GNU General Public License version 2 or later; see LICENSE.txt
*/
 
// no direct access
defined('_JEXEC') or die;

jimport('joomla.filesystem.path');
jimport('joomla.filesystem.folder');

/**
 * Component Helper Manager
 *
 * @author juliopontes <juliopfneto@gmail.com>
 */
class JoomlaOverrideHelperComponent
{
	/**
	 * Exception data to prevent override classes
	 * 
	 * @var array
	 */
	static private $_exception = array();

	/**
	 * Preload component files to override
	 *
	 * We read original source file and if are enabled to extend default classes we need to change defined vars because they dont exists at this step.
	 *
	 * @param string $option
	 * @param JRegistry $params
	 */
	static public function preload($option, $params)
	{
		if (count(JoomlaOverrideHelperCodepool::addCodePath()) == 0) return;

		//get files that can be overridden
		$componentOverrideFiles = self::loadComponentFiles($option);
		self::registerPaths($option);

		//loading override files
		if( !empty($componentOverrideFiles) )
		{
			//constants to replace JPATH_COMPONENT, JPATH_COMPONENT_SITE and JPATH_COMPONENT_ADMINISTRATOR
			define('JPATH_SOURCE_COMPONENT',JPATH_BASE.'/components/'.$option);
			define('JPATH_SOURCE_COMPONENT_SITE',JPATH_SITE.'/components/'.$option);
			define('JPATH_SOURCE_COMPONENT_ADMINISTRATOR',JPATH_ADMINISTRATOR.'/components/'.$option);
			
			foreach($componentOverrideFiles as $componentFile)
			{
				if($filePath = JPath::find(JoomlaOverrideHelperCodepool::addCodePath(),$componentFile))
				{
					//include the original code and replace class name add a Default on
					if ($params->get('extendDefault',0))
					{
						if (!class_exists(JoomlaOverrideHelperOverride::getClassName(JPATH_BASE.'/components/'.$componentFile)))
						{
							JoomlaOverrideHelperOverride::load(JoomlaOverrideHelperOverride::fixDefines(JoomlaOverrideHelperOverride::createDefaultClass(JPATH_BASE.'/components/'.$componentFile)));
						}
						
						require_once $filePath;
					}
					else
					{
						require_once $filePath;
					}
				}
			}
		}
	}

	/**
	 * Load component files that can be overrided
	 *
	 * Controllers, Models and Views
	 * We have an exeption for MenuModelMenutypes because we've add a new feature to read our codepools and override views and create new views.
	 *
	 * @access private
	 * @param mixed $option
	 * @return void
	 */
	private function loadComponentFiles($option)
	{
		$JPATH_COMPONENT = JPATH_BASE.'/components/'.$option;
		$files = array();

		//check if default controller exists
		if (JFile::exists($JPATH_COMPONENT.'/controller.php'))
		{
			$files[] = $JPATH_COMPONENT.'/controller.php';
		}

		//check if controllers folder exists
		if (JFolder::exists($JPATH_COMPONENT.'/controllers'))
		{
			$exclude = array('.svn', 'CVS', '.DS_Store', '__MACOSX');
			//check if has controllers exceptions
			if (self::hasException($option,'controllers'))
			{
				//add source controllers files to exception list
				foreach (self::$_exception[$option][JFactory::getApplication()->getName()]['controllers'] as $controllerData)
				{
					$exclude[] = JFile::stripext($controllerData['source']);
				}
			}
			$controllers = JFolder::files($JPATH_COMPONENT.'/controllers', '.php', false, true, $exclude);
			$files = array_merge($files, $controllers);
		}

		//check if models folder exists
		if (JFolder::exists($JPATH_COMPONENT.'/models'))
		{
			$exclude = array('.svn', 'CVS', '.DS_Store', '__MACOSX');
			//check if have some models exception
			if (self::hasException($option,'models'))
			{
				//add source model files to exception list
				foreach (self::$_exception[$option][JFactory::getApplication()->getName()]['models'] as $modelData)
				{
					$exclude[] = JFile::stripext($modelData['source']);
				}
			}
			$models = JFolder::files($JPATH_COMPONENT.'/models', '.php', false, true, $exclude);

			$files = array_merge($files, $models);
		}

		//check if views folder exists
		if (JFolder::exists($JPATH_COMPONENT.'/views'))
		{
			//reading view folders
			$views = JFolder::folders($JPATH_COMPONENT.'/views');
			foreach ($views as $view)
			{
				$exclude = array('.svn', 'CVS', '.DS_Store', '__MACOSX');
				//check if have some views exception
				if (self::hasException($option,'views'))
				{
					//add source views files to exception list
					foreach (self::$_exception[$option][JFactory::getApplication()->getName()]['views'] as $viewData)
					{
						$exclude[] = JFile::stripext($viewData['source']);
					}
				}
				//get view formats files
				$viewsFiles = JFolder::files($JPATH_COMPONENT.'/views/'.$view, '.php', false, true, $exclude);
				$files = array_merge($files, $viewsFiles);
			}
		}

		//now we check all types of exception and load custom classes
		if (self::hasException($option))
		{
			foreach (self::$_exception[$option][JFactory::getApplication()->getName()] as $type => $exceptionDatas)
			{
				foreach ($exceptionDatas as $exceptionData)
				{
					$modelContent = JFile::read($JPATH_COMPONENT.$exceptionData['source']);
					$modelContent = str_replace($exceptionData['class'], $exceptionData['class'].'Default', $modelContent);
					// Finally we can load the base class
					eval('?>'.$modelContent.PHP_EOL.'?>');
					require_once dirname(__DIR__).'/core/'.$exceptionData['destiny'];
				}
			}
		}

		$return = array();
		//cleaning files
		foreach ($files as $file)
		{
			$file = JPath::clean($file);
			$file = substr($file, strlen(JPATH_BASE.'/components/'));
			$return[] = $file;
		}

		return $return;
	}

	/**
	 * Register override paths based on codepools
	 *
	 * @param string $option
	 */
	static private function registerPaths($option)
	{
		foreach (JoomlaOverrideHelperCodepool::addCodePath() as $codePool)
		{
			if (JVERSION > 2.5)
			{
				JModelLegacy::addIncludePath($codePool.'/'.$option.'/models');
				JViewLegacy::addViewHelperPath($codePool.'/'.$option);
				JViewLegacy::addViewTemplatePath($codePool.'/'.$option);
			}
			else
			{
				JModel::addIncludePath($codePool.'/'.$option.'/models');
				JView::addViewHelperPath($codePool.'/'.$option);
				JView::addViewTemplatePath($codePool.'/'.$option);
			}
			JTable::addIncludePath($codePool.'/'.$option.'/tables');
			JModelForm::addComponentFormPath($codePool.'/'.$option.'/models/forms');
			JModelForm::addComponentFieldPath($codePool.'/'.$option.'/models/fields');
		}
	}

	/**
	 * Check if exists and exception for specific component/application or if has a specific exceptions by types(models, controllers, views)
	 * 
	 * @param string $option
	 * @param string $key
	 */
	static private function hasException($option,$key=null)
	{
		if ( (is_null($key) && !isset(self::$_exception[$option][JFactory::getApplication()->getName()]) ) || (!empty($key) && !isset(self::$_exception[$option][JFactory::getApplication()->getName()][$key]))  )
		{
			return false;
		}

		return true;
	}

	/**
	 * String register and exception data to override specific files
	 * 
	 * @param string $option
	 * @param string $application
	 * @param array $data
	 */
	static public function addExceptionOverride($option, $application, array $data)
	{
		self::$_exception[$option][$application] = $data;
	}

	/**
	 * Here we initialize a file to people can add new Buttons and Submenus
	 *
	 * @param string $option
	 * @param string $extension
	 */
	static public function includeInitialize($option, $extension)
	{
		if (!JFactory::getApplication()->isAdmin()) return false;

		if ($file = JPath::find(JoomlaOverrideHelperCodepool::addCodePath(), $option.'/initialize.php'))
		{
			require_once $file;
		}
		if ($option == 'com_categories' && !empty($extension))
		{
			if ($file = JPath::find(JoomlaOverrideHelperCodepool::addCodePath(), $extension.'/initialize.php'))
			{
				require_once $file;
			}
		}
	}
}