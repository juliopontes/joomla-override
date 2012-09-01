<?php
// no direct access
defined('_JEXEC') or die;

/**
 * Controller class
 *
 * @package     Joomla.Legacy
 * @subpackage  Module
 * @since       11.1
 */
abstract class JControllerLegacy extends LIB_JControllerLegacyDefault
{
	/**
	 * Method to get a singleton controller instance.
	 * Sync with codepools
	 *
	 * @param   string  $prefix  The prefix for the controller.
	 * @param   array   $config  An array of optional constructor options.
	 *
	 * @return  JController
	 *
	 * @since   11.1
	 * @throws  Exception if the controller cannot be loaded.
	 */
	public static function getInstance($prefix, $config = array())
	{
		if (is_object(self::$instance))
		{
			return self::$instance;
		}

		// Get the environment configuration.
		$basePath = array_key_exists('base_path', $config) ? $config['base_path'] : JPATH_COMPONENT;
		$format = JRequest::getWord('format');
		$command = JRequest::getVar('task', 'display');

		// Check for array format.
		$filter = JFilterInput::getInstance();

		if (is_array($command))
		{
			$command = $filter->clean(array_pop(array_keys($command)), 'cmd');
		}
		else
		{
			$command = $filter->clean($command, 'cmd');
		}
		
		$includePaths = array();

		$option = JFactory::getApplication()->input->get('option');

		// Check for a controller.task command.
		if (strpos($command, '.') !== false)
		{
			// Explode the controller.task command.
			list ($type, $task) = explode('.', $command);

			// Define the controller filename and path.
			$file = self::createFileName('controller', array('name' => $type, 'format' => $format));
			
			
			//template name
			$template = JFactory::getApplication()->getTemplate();
			
			$includePaths[] = $basePath . '/controllers';
			foreach (JoomlaOverrideHelperCodepool::addCodePath() as $codepool)
			{
				$includePaths[] = $codepool.'/'.$option.'/controllers';
			}
			$path = JPath::find($includePaths, $file);

			// Reset the task without the controller context.
			JRequest::setVar('task', $task);
		}
		else
		{
			// Base controller.
			$type = null;
			$task = $command;
			
			$includePaths[] = $basePath;
			foreach (JoomlaOverrideHelperCodepool::addCodePath() as $codepool)
			{
				$includePaths[] = $codepool.'/'.$option;
			}
			// Define the controller filename and path.
			$file		 = self::createFileName('controller', array('name' => 'controller', 'format' => $format));
			
			$path		 = JPath::find($includePaths,$file);
			$backupfile  = self::createFileName('controller', array('name' => 'controller'));
			$backuppath  = JPath::find($includePaths,$backupfile);
		}

		// Get the controller class name.
		$class = ucfirst($prefix) . 'Controller' . ucfirst($type);

		// Include the class if not present.
		if (!class_exists($class))
		{
			// If the controller file path exists, include it.
			if (file_exists($path))
			{
				require_once $path;
			}
			elseif (isset($backuppath) && file_exists($backuppath))
			{
				require_once $backuppath;
			}
			else
			{
				throw new InvalidArgumentException(JText::sprintf('JLIB_APPLICATION_ERROR_INVALID_CONTROLLER', $type, $format));
			}
		}

		// Instantiate the class.
		if (class_exists($class))
		{
			self::$instance = new $class($config);
		}
		else
		{
			throw new InvalidArgumentException(JText::sprintf('JLIB_APPLICATION_ERROR_INVALID_CONTROLLER_CLASS', $class));
		}

		return self::$instance;
	}

	/**
	 * Sync with codepools
	 * 
	 * Method to load and return a view object. This method first looks in the
	 * current template directory for a match and, failing that, uses a default
	 * set path to load the view class file.
	 *
	 * Note the "name, prefix, type" order of parameters, which differs from the
	 * "name, type, prefix" order used in related public methods.
	 *
	 * @param   string  $name    The name of the view.
	 * @param   string  $prefix  Optional prefix for the view class name.
	 * @param   string  $type    The type of view.
	 * @param   array   $config  Configuration array for the view. Optional.
	 *
	 * @return  mixed  View object on success; null or error result on failure.
	 *
	 * @since   11.1
	 * @note    Replaces _createView.
	 */
	protected function createView($name, $prefix = '', $type = '', $config = array())
	{
		$option = JFactory::getApplication()->input->get('option');
		foreach (JoomlaOverrideHelperCodepool::addCodePath() as $codepool)
		{
			$this->addViewPath($codepool.'/'.$option.'/views');
		}
		
		return parent::createView($name, $prefix, $type, $config);
	}
}