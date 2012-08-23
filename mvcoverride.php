<?php
// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

require_once 'helper/override.php';
require_once 'helper/component.php';

/**
 * PlgSystemMVCOverride class.
 * 
 * @extends JPlugin
 */
class PlgSystemMVCOverride extends JPlugin
{
	/**
	 * onAfterInitialise function.
	 * 
	 * @access public
	 * @return void
	 */
	public function onAfterInitialise()
	{	
		MVCOverrideHelperOverride::initialize();
		//template name
		$template = JFactory::getApplication()->getTemplate();
		
		//code pools
		$includePath = array();
		//global extensions path
		$includePath[] = JPATH_BASE.'/code';
		//template code path
		$includePath[] = JPATH_THEMES.'/'.$template.'/code';
		
		MVCOverrideHelperOverride::addCodePath($includePath);
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