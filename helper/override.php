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

jimport('joomla.filesystem.file');


abstract class JoomlaOverrideHelperOverride
{
	/**
	 * Default suffix of class overrides
	 * 
	 * @var string
	 */
	const SUFIX = 'Default';

	/**
	 * Default pre-suffix of class overrides
	 * 
	 * @var string
	 */
	const PREFIX = '';

	static public function getClassName($componentFile,$prefix=null,$sufix=null)
	{
		$bufferFile = JFile::read($componentFile);
		
		//set default values if null
		if (is_null($sufix))
		{
			$sufix = self::SUFIX;
		}
		if (is_null($prefix))
		{
			$prefix = self::PREFIX;
		}
		
		$originalClass = self::getOriginalClass($bufferFile);
		
		return $prefix.$originalClass.$sufix;
	}
	
	static public function getOriginalClass($bufferContent)
	{
		$originalClass = null;
		$tokens = token_get_all($bufferContent);
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
		
		return $originalClass;
	}

	/**
	 * Read source file and replace class name by adding suffix/prefix
	 * 
	 * @param string $componentFile
	 * @param string $sufix
	 * @param string $prefix
	 */
	static public function createDefaultClass($componentFile,$prefix=null,$sufix=null)
	{	
		$bufferFile = JFile::read($componentFile);
		
		$originalClass = self::getOriginalClass($bufferFile);
		$replaceClass = self::getClassName($componentFile, $prefix, $sufix);
		//replace original class name by default
		$bufferContent = str_replace($originalClass,$replaceClass,$bufferFile);
		
		return $bufferContent;
	}

	/**
	 * Will search for defined JPATH_COMPONENT, JPATH_SITE, JPATH_ADMINISTRATOR and replace by right values
	 * 
	 * @param string $bufferFile
	 */
	static public function fixDefines($bufferContent)
	{
		//detect if source file use some constants
		preg_match_all('/JPATH_COMPONENT(_SITE|_ADMINISTRATOR)|JPATH_COMPONENT/i', $bufferContent, $definesSource);
		//replace JPATH_COMPONENT constants if found, because we are loading before define these constants
		if (count($definesSource[0]))
		{
			$bufferContent = preg_replace(array('/JPATH_COMPONENT/','/JPATH_COMPONENT_SITE/','/JPATH_COMPONENT_ADMINISTRATOR/'),array('JPATH_SOURCE_COMPONENT','JPATH_SOURCE_COMPONENT_SITE','JPATH_SOURCE_COMPONENT_ADMINISTRATOR'),$bufferContent);
		}
		
		//fix requires
		
		return $bufferContent;
	}
	
	static public function load($bufferContent)
	{
		if (!empty($bufferContent))
		{
			// Finally we can load the base class
			eval('?>'.$bufferContent.PHP_EOL.'?>');
		}
	}
}