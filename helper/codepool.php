<?php
/**
 * Registry codepools and intialize basic override for core classes
 * 
 * @author juliopontes <juliopfneto@gmail.com>
 */
class MVCOverrideHelperCodepool
{
	/**
	 * Register global paths to override code
	 * 
	 * @var array
	 */
	private static $_paths = array();

	/**
	 * Initialize override of some core classes
	 * 
	 */
	static public function initialize()
	{
		$plugin_path = dirname(dirname(__FILE__));
		
		//exception for implement new features
		$exceptionDatas = array(
			array(
				'option' => 'com_menus',
				'application' => 'administrator',
				'data' => array(
					'models' => array(
						array(
							'class' => 'MenusModelMenutypes',
							'source' => '/models/menutypes.php',
							'destiny' => '/model/menutypes.php',
						)
					)
				)
			),
			array(
				'option' => 'com_modules',
				'application' => 'administrator',
				'data' => array(
					'models' => array(
						array(
							'class' => 'ModulesModelModule',
							'source' => '/models/module.php',
							'destiny' => '/model/module.php',
						)
					)
				)
			)
		);
		
		if (JVERSION > 2.5)
		{
			$overrideClasses = array(
				array(
					'source_file' => JPATH_LIBRARIES.'/legacy/module/helper.php',
					'class_name' => 'JModuleHelper',
					'jimport' => '',
					'override_file' => $plugin_path.'/core/module/helper.php'
				),
				array(
					'source_file' => JPATH_LIBRARIES.'/legacy/model/form.php',
					'class_name' => 'JModelForm',
					'jimport' => '',
					'override_file' => $plugin_path.'/core/model/modelform.php'
				),
				array(
					'source_file' => JPATH_LIBRARIES.'/legacy/controller/legacy.php',
					'class_name' => 'JControllerLegacy',
					'jimport' => '',
					'override_file' => $plugin_path.'/core/controller/legacy.php'
				),
				array(
					'source_file' => JPATH_LIBRARIES.'/legacy/view/legacy.php',
					'class_name' => 'JViewLegacy',
					'jimport' => '',
					'override_file' => $plugin_path.'/core/view/legacy.php'
				)
			);
		}
		else {
			$overrideClasses = array(
				array(
					'source_file' => JPATH_LIBRARIES.'/joomla/application/module/helper.php',
					'class_name' => 'JModuleHelper',
					'jimport' => 'joomla.application.module.helper',
					'override_file' => $plugin_path.'/core/module/helper.php'
				),
				array(
					'source_file' => JPATH_LIBRARIES.'/joomla/application/component/modelform.php',
					'class_name' => 'JModelForm',
					'jimport' => 'joomla.application.component.modelform',
					'override_file' => $plugin_path.'/core/model/modelform.php'
				),
				array(
					'source_file' => JPATH_LIBRARIES.'/joomla/application/component/controller.php',
					'class_name' => 'JController',
					'jimport' => 'joomla.application.component.controller',
					'override_file' => $plugin_path.'/core/controller/controller.php'
				),
				array(
					'source_file' => JPATH_LIBRARIES.'/joomla/application/component/view.php',
					'class_name' => 'JView',
					'jimport' => 'joomla.application.component.view',
					'override_file' => $plugin_path.'/core/view/view.php'
				)
			);
		}
		
		foreach ($overrideClasses as $overrideClass)
		{
			self::overrideClass($overrideClass['source_file'], $overrideClass['class_name'], $overrideClass['jimport'], $overrideClass['override_file']);
		}
		
		foreach ($exceptionDatas as $exceptionData)
		{
			MVCOverrideHelperComponent::addExceptionOverride($exceptionData['option'], $exceptionData['application'], $exceptionData['data']);
		}
	}

	/**
	 * Override a core classes and just overload methods that need
	 * 
	 * @param string $sourcePath
	 * @param string $class
	 * @param string $jimport
	 * @param string $replacePath
	 */
	static private function overrideClass($sourcePath, $class, $jimport, $replacePath)
	{
		//override library class
		MVCOverrideHelperOverride::load(MVCOverrideHelperOverride::createDefaultClass($sourcePath,'LIB_'));
		
		if (!empty($jimport)) jimport($jimport);
		JLoader::register($class, $replacePath, true);
	}

	/**
	 * Add a code pool to override
	 * 
	 * @param string $path
	 */
	static public function addCodePath($path = null)
	{
		if (is_null($path))
		{
			return self::$_paths;
		}
		
		settype($path, 'array');
		
		foreach ($path as $codePool)
		{
			$codePool = JPath::clean($codePool);
			JModuleHelper::addIncludePath($codePool);
			
			array_push(self::$_paths, $codePool);
		}
		
		return self::$_paths;
	}
}