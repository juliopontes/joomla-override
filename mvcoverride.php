<?php
// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

require_once 'helper/override.php';
require_once 'core/loader.php';
require_once 'helper/codepool.php';
require_once 'helper/component.php';

/**
 * PlgSystemOverride class.
 * 
 * @extends JPlugin
 */
class PlgSystemOverride extends JPlugin
{
	/**
	 * onAfterInitialise function.
	 * 
	 * @access public
	 * @return void
	 */
	public function onAfterInitialise()
	{	
		MVCOverrideHelperCodepool::initialize();
		//template name
		$template = JFactory::getApplication()->getTemplate();
		
		//code pools
		$includePath = array();
		//global extensions path
		$basePath = JPATH_SITE.'/override';
		//add administrator scope
		if (JFactory::getApplication()->isAdmin())
		{
			$basePath .= '/administrator';
		}
		
		$includePath[] = $basePath;
		//template code path
		$includePath[] = JPATH_THEMES.'/'.$template.'/code';
		
		MVCOverrideHelperCodepool::addCodePath($includePath);
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
			$db = JFactory::getDBO();
			$db->setQuery('SELECT * FROM #__extensions WHERE id ='.$db->quote($componentID));
			$component = $db->loadObject();
			$this->option = $component->element;
		}
		
		MVCOverrideHelperComponent::preload($this->option, $this->params);
	}
	
	/**
	 * onAfterDispatch function.
	 * 
	 * @access public
	 * @return void
	 */
	public function onAfterDispatch()
	{
		MVCOverrideHelperComponent::includeSubmenu($this->option,$this->extension);
	}
}