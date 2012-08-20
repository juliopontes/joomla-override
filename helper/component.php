<?php
/**
 * Component Helper Manager
 * 
 * @author juliopontes <juliopfneto@gmail.com>
 */
class MVCOverrideHelperComponent
{
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
		if (count(MVCOverrideHelperOverride::addCodePath()) == 0) return;
		
		//get files that can be overrided
		$componentOverrideFiles = self::loadComponentFiles($option);
		self::registerPaths($option);
		
		//loading override files
		if( !empty($componentOverrideFiles) )
		{
			foreach($componentOverrideFiles as $componentFile)
			{
				if($filePath = JPath::find(MVCOverrideHelperOverride::addCodePath(),$componentFile))
				{
					//include the original code and replace class name add a Default on 
					if ($params->get('extendDefault',0))
					{
						$bufferFile = JFile::read(JPATH_BASE.'/components/'.$componentFile);
						//detect if source file use some constants
						preg_match_all('/JPATH_COMPONENT(_SITE|_ADMINISTRATOR)|JPATH_COMPONENT/i', $bufferFile, $definesSource);
						
						$bufferOverrideFile = JFile::read($filePath);
						//detect if override file use some constants
						preg_match_all('/JPATH_COMPONENT(_SITE|_ADMINISTRATOR)|JPATH_COMPONENT/i', $bufferOverrideFile, $definesSourceOverride);
						
						// Append "Default" to the class name (ex. ClassNameDefault). We insert the new class name into the original regex match to get
						$rx = '/class *[a-z0-0]* *(extends|{)/i';
						
						preg_match($rx, $bufferFile, $classes);
						
						$parts = explode(' ',$classes[0]);
						
						$originalClass = $parts[1];
						$replaceClass = $originalClass.'Default';
						
						if (count($definesSourceOverride[0]))
						{
							JError::raiseError('Plugin MVC Override','Your override file use constants, please replace code constants<br />JPATH_COMPONENT -> JPATH_SOURCE_COMPONENT,<br />JPATH_COMPONENT_SITE -> JPATH_SOURCE_COMPONENT_SITE and<br />JPATH_COMPONENT_ADMINISTRATOR -> JPATH_SOURCE_COMPONENT_ADMINISTRATOR');
						}
						else
						{
							//replace original class name by default
							$bufferContent = str_replace($originalClass,$replaceClass,$bufferFile);
							
							//replace JPATH_COMPONENT constants if found, because we are loading before define these constants
							if (count($definesSource[0]))
							{
								$bufferContent = preg_replace(array('/JPATH_COMPONENT/','/JPATH_COMPONENT_SITE/','/JPATH_COMPONENT_ADMINISTRATOR/'),array('JPATH_SOURCE_COMPONENT','JPATH_SOURCE_COMPONENT_SITE','JPATH_SOURCE_COMPONENT_ADMINISTRATOR'),$bufferContent);
							}
							
							// Change private methods to protected methods
							if ($params->get('changePrivate',0))
							{
								$bufferContent = preg_replace('/private *function/i', 'protected function', $bufferContent);
							}
							
							// Finally we can load the base class
							eval('?>'.$bufferContent.PHP_EOL.'?>');
							
							require_once $filePath;
						}
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
			$controllers = JFolder::files($JPATH_COMPONENT.'/controllers', '.php', false, true);
			$files = array_merge($files, $controllers);
		}
		
		//check if models folder exists
		if (JFolder::exists($JPATH_COMPONENT.'/models'))
		{
			$exclude = array('.svn', 'CVS', '.DS_Store', '__MACOSX');
			if ($option == 'com_menus' && JFactory::getApplication()->isAdmin())
			{
				$exclude[] = 'menutypes.php';
			}
			if ($option == 'com_modules' && JFactory::getApplication()->isAdmin())
			{
				$exclude[] = 'module.php';
			}
			$models = JFolder::files($JPATH_COMPONENT.'/models', '.php', true, true, $exclude);
			
			$files = array_merge($files, $models);
		}
		
		//check if views folder exists
		if (JFolder::exists($JPATH_COMPONENT.'/views'))
		{
			//reading view folders
			$views = JFolder::folders($JPATH_COMPONENT.'/views');
			foreach ($views as $view)
			{
				//get view formats files
				$viewsFiles = JFolder::files($JPATH_COMPONENT.'/views/'.$view, '.php', false, true);
				$files = array_merge($files, $viewsFiles);
			}
		}
		
		if ($option == 'com_menus' && JFactory::getApplication()->isAdmin())
		{
			//override MenusModelMenutypes class
			$modelContent = JFile::read($JPATH_COMPONENT.'/models/menutypes.php');
			$modelContent = str_replace('MenusModelMenutypes', 'MenusModelMenutypesDefault', $modelContent);
			// Finally we can load the base class
			eval('?>'.$modelContent.PHP_EOL.'?>');
			
			require_once dirname(dirname(__FILE__)).'/core/model/menutypes.php';
		}
		if ($option == 'com_modules' && JFactory::getApplication()->isAdmin())
		{
			//override MenusModelMenutypes class
			$modelContent = JFile::read($JPATH_COMPONENT.'/models/module.php');
			$modelContent = str_replace('ModulesModelModule', 'ModulesModelModuleDefault', $modelContent);
			// Finally we can load the base class
			eval('?>'.$modelContent.PHP_EOL.'?>');
			
			require_once dirname(dirname(__FILE__)).'/core/model/module.php';
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
		foreach (MVCOverrideHelperOverride::addCodePath() as $codePool)
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
			JModelForm::addComponentFormPath($codePool.'/'.$option.'/models/forms');
			JModelForm::addComponentFieldPath($codePool.'/'.$option.'/models/fields');
		}
	}
	
	/**
	 * Here we initialize a file to people can add new Buttons and Submenus
	 * 
	 * @param string $option
	 * @param string $extension
	 */
	static public function includeSubmenu($option, $extension)
	{
		if (!JFactory::getApplication()->isAdmin()) return false;
		
		if ($file = JPath::find(MVCOverrideHelperOverride::addCodePath(), $option.'/initialize.php'))
		{
			require_once $file;
		}
		if ($option == 'com_categories' && !empty($extension))
		{
			if ($file = JPath::find(MVCOverrideHelperOverride::addCodePath(), $extension.'/initialize.php'))
			{
				require_once $file;
			}
		}
	}
}