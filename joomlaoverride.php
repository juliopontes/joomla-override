<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaoverride
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
// no direct access
defined('_JEXEC') or die;

ini_set('display_errors', 'true');

jimport('joomla.plugin.plugin');

require_once 'helper/override.php';
require_once 'core/loader.php';
require_once 'helper/codepool.php';
require_once 'helper/component.php';

/**
 * Joomla! Override Plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaoverride
 * @since       1.5
 */
class PlgSystemJoomlaOverride extends JPlugin
{
	/**
	 * onAfterInitialise function.
	 * 
	 * @access public
	 * @return void
	 */
	public function onAfterInitialise()
	{	
		JoomlaOverrideHelperCodepool::initialize();
		//template name
		$template = JFactory::getApplication()->getTemplate();
		
		//code pools
		$includePath = array();
		//global extensions path
		$customPath = JPath::clean($this->params->get('global_path','templates/system/code'));
		$basePath = JPATH_SITE.DIRECTORY_SEPARATOR.$customPath;
		//add administrator scope
		if (JFactory::getApplication()->isAdmin())
		{
			$basePath .= '/administrator';
		}
		
		$includePath[] = $basePath;
		//template code path
		$includePath[] = JPATH_THEMES.'/'.$template.'/code';
		
		JoomlaOverrideHelperCodepool::addCodePath($includePath);
	}
	
	/**
	 * onAfterRoute function.
	 * 
	 * @access public
	 * @return void
	 */
	public function onAfterRoute()
	{
		$this->option = JFactory::getApplication()->input->get('option');
		$this->extension = JFactory::getApplication()->input->get('extension');
		
		if( empty($this->option) && JFactory::getApplication()->isSite() ) {
			$menuDefault = JFactory::getApplication()->getMenu()->getDefault();
			if ($menuDefault == 0) return;
			
			$componentID = $menuDefault->componentid;
			$component = JTable::getInstance('extension');
			$component->load($componentID);
			$this->option = $component->element;
		}
		
		JoomlaOverrideHelperComponent::preload($this->option, $this->params);
		JoomlaOverrideHelperComponent::includeInitialize($this->option,$this->extension);
	}
}