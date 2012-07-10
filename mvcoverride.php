<?php
// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class PlgSystemMVCOverride extends JPlugin
{
	/**
	 * Override MVC
	 */
	public function onAfterRoute()
	{
		$option = JRequest::getCMD('option');
		
		if( empty($option) && JFactory::getApplication()->isSite() ) {
			$menuDefault = JFactory::getApplication()->getMenu()->getDefault();
			if ($menuDefault == 0) return;
			
			$componentID = $menuDefault->componentid;
			$db = JFactory::getDBO();
			$db->setQuery('SELECT * FROM #__extensions WHERE id ='.$db->quote($componentID));
			$component = $db->loadObject();
			$option = $component->element;
		}
		
		//get files that can be overrided
		$componentOverrideFiles = $this->loadComponentFiles($option);
		//application name
		$applicationName = JFactory::getApplication()->getName();
		//template name
		$template = JFactory::getApplication()->getTemplate();
		
		//code paths
		$includePath = array();
		//template code path
		$includePath[] = JPATH_THEMES.'/'.$template.'/code';
		//base extensions path
		$includePath[] = JPATH_BASE.'/code';
		
		//constants to replace JPATH_COMPONENT, JPATH_COMPONENT_SITE and JPATH_COMPONENT_ADMINISTRATOR
		define('JPATH_SOURCE_COMPONENT',JPATH_BASE.'/components/'.$option);
		define('JPATH_SOURCE_COMPONENT_SITE',JPATH_SITE.'/components/'.$option);
		define('JPATH_SOURCE_COMPONENT_ADMINISTRATOR',JPATH_ADMINISTRATOR.'/components/'.$option);
		
		//loading override files
		if( !empty($componentOverrideFiles) ){
			foreach($componentOverrideFiles as $componentFile)
			{
				if($filePath = JPath::find($includePath,$componentFile))
				{
					//include the original code and replace class name add a Default on 
					if ($this->params->get('extendDefault',0))
					{
						$bufferFile = JFile::read(JPATH_BASE.'/components/'.$componentFile);
						//detect if override file use some constants
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
							$bufferContent = str_replace($originalClass,$replaceClass,$bufferFile);
							
							//replace JPATH_COMPONENT constants if found, because we are loading before define these constants
							if (count($definesSource[0]))
							{
								$bufferContent = preg_replace(array('/JPATH_COMPONENT/','/JPATH_COMPONENT_SITE/','/JPATH_COMPONENT_ADMINISTRATOR/'),array('JPATH_SOURCE_COMPONENT','JPATH_SOURCE_COMPONENT_SITE','JPATH_SOURCE_COMPONENT_ADMINISTRATOR'),$bufferContent);
							}
							
							// Change private methods to protected methods
							if ($this->params->get('changePrivate',0))
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
			$models = JFolder::files($JPATH_COMPONENT.'/models', '.php', false, true);
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
}