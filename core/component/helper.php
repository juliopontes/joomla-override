<?php
/**
 * Component helper class
 *
 * @package     Joomla.Libraries
 * @subpackage  Component
 * @since       1.5
 */
class JComponentHelper extends LIB_JComponentHelperDefault
{
	/**
	 * Execute the component.
	 *
	 * @param   string  $path  The component path.
	 *
	 * @return  string  The component output
	 *
	 * @since   1.7
	 */
	protected static function executeComponent($path)
	{
		$app = JFactory::getApplication();
		$componentFile = $app->scope . DIRECTORY_SEPARATOR . basename(str_replace('com_','',$app->scope)) . '.php';
		$file = JPath::find(JoomlaOverrideHelperCodepool::addCodePath(),$componentFile);
		if ($file) {
			$path = $file;
		}
		
		ob_start();
		require_once $path;
		$contents = ob_get_clean();

		return $contents;
	}
}
