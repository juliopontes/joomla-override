<?php
/**
 * Component Helper Manager
 *
 * @author juliopontes <juliopfneto@gmail.com>
 */
class MVCOverrideHelperComponent
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
						$originalClass = null;
						$tokens = token_get_all($bufferFile);
					    foreach( $tokens as $key => $token)
					    {
					        if(is_array($token))
					        {
					        	// Find the class declaration
					        	if (token_name($token[0]) == 'T_CLASS')
			        			{
			        				// Class name should be in the key+2 position
									$originalClass = $tokens[$key+2][1];
									break;
			        			}
					        }
					    }
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