<?php
spl_autoload_register(function($class_name){
	$original_class = $class_name;
	$sufix = JoomlaOverrideHelperOverride::SUFIX;
	$prefix = JoomlaOverrideHelperOverride::PREFIX;
	
	$class_prefix = substr($class_name,0,strlen($prefix));
	$class_sufix = substr($class_name,strlen($sufix) * -1);
	
	if (
		(!empty($prefix) && !empty($class_prefix) && ($class_prefix != $prefix))
		||
		(!empty($sufix) && !empty($class_sufix) && ($class_sufix != $sufix))
	)
	{
		return false;
	}
	
	//check for com_class_namedefault
	if (!empty($prefix))
	{
		$class_name = str_replace($prefix,'',$class_name);
	}
	if (!empty($sufix))
	{
		$class_name = substr($class_name,0,strlen($sufix) * -1);
	}
	
	
	$class_name = strtolower($class_name);
	if (strpos($class_name,'controller') !== false)
	{
		$format = JFactory::getApplication()->input->get('format');
		$parts = split('controller',$class_name);
		
		$file = '/components/com_'.strtolower($parts[0]);
		if (count($parts) > 1)
		{
			$file .= '/controllers/'.strtolower(end($parts));
			if (!empty($format))
			{
				$file .= '.'.$format;
			}
			$file .= '.php';
		}
		else {
			$file .= '/controllers.php';
		}
		
		if (is_file(JPATH_BASE.$file))
		{
			if (!class_exists($original_class))
			{
				JoomlaOverrideHelperOverride::load(JoomlaOverrideHelperOverride::fixDefines(JoomlaOverrideHelperOverride::createDefaultClass(JPATH_BASE.$file)));
			}
		}
	}
	if (strpos($class_name,'model') !== false)
	{
		$parts = split('model',$class_name);
		
		$file = '/components/com_'.strtolower($parts[0]).'/models/'.strtolower(end($parts)).'.php';
		
		if (is_file(JPATH_BASE.$file))
		{
			if (!class_exists($original_class))
			{
				JoomlaOverrideHelperOverride::load(JoomlaOverrideHelperOverride::fixDefines(JoomlaOverrideHelperOverride::createDefaultClass(JPATH_BASE.$file)));
			}
		}
	}
	if (strpos($class_name,'view') !== false)
	{
		$format = JFactory::getApplication()->input->get('format','html');
		$parts = split('view',$class_name);
		
		$file = '/components/com_'.strtolower($parts[0]).'/views/'.strtolower(end($parts)).'/view.'.$format.'.php';
		
		if (is_file(JPATH_BASE.$file))
		{
			
			if (!class_exists($original_class))
			{
				JoomlaOverrideHelperOverride::load(JoomlaOverrideHelperOverride::fixDefines(JoomlaOverrideHelperOverride::createDefaultClass(JPATH_BASE.$file)));
			}
		}
	}
	if (strpos($class_name,'table') !== false)
	{
		$parts = split('table',$class_name);
		
		$file = '/components/com_'.strtolower($parts[0]).'/tables/'.strtolower(end($parts)).'.php';
		
		if (is_file(JPATH_BASE.$file))
		{
			
			if (!class_exists($original_class))
			{
				JoomlaOverrideHelperOverride::load(JoomlaOverrideHelperOverride::fixDefines(JoomlaOverrideHelperOverride::createDefaultClass(JPATH_BASE.$file)));
			}
		}
	}
});