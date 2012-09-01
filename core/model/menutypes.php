<?php
// No direct access
defined('_JEXEC') or die;

/**
 * Menu Item Types Model for Menus.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_menus
 * @version		1.6
 */
class MenusModelMenutypes extends MenusModelMenutypesDefault
{
	/**
	 * Sync with codepools
	 * 
	 * @param string $component
	 */
	protected function getTypeOptionsByComponent($component)
	{
		// Initialise variables.
		$options = array();

		$paths = array(
			JPATH_SITE.'/components/'.$component,
		);
		foreach (JoomlaOverrideHelperCodepool::addCodePath() as $codePool)
		{
			$paths[] = JPath::clean($codePool.'/'.$component);
		}
		$file = 'metadata.xml';
		$mainXML = JPath::find($paths, $file);

		if (is_file($mainXML)) {
			$options = $this->getTypeOptionsFromXML($mainXML, $component);
		}

		if (empty($options)) {
			$options = $this->getTypeOptionsFromMVC($component);
		}

		return $options;
	}

	/**
	 * Sync with codepools
	 * 
	 * @param string $component
	 */
	protected function getTypeOptionsFromMVC($component)
	{
		// Initialise variables.
		$options = array();
		$views = array();
		// Get the views for this component.
		$paths = array();
		$basePath = JPATH_SITE.'/components/'.$component.'/views';
		$paths[] = $basePath;
		if (!JFolder::exists($basePath))
		{
			return $options;
		}
		
		if (JFolder::exists($basePath))
		{
			$views = JFolder::folders($basePath);
		}
		
		$siteCodePath = JPATH_SITE.'/code/'.$component.'/views';
		if (JFolder::exists($siteCodePath))
		{
			$paths[] = $siteCodePath;
			$views = array_merge($views, JFolder::folders($siteCodePath));
		}
		
		if (is_null($views))
		{
			return false;
		}

		foreach ($views as $view)
		{
			// Ignore private views.
			if (strpos($view, '_') !== 0) {
				// Determine if a metadata file exists for the view.
				$file = JPath::find($paths,$view.'/metadata.xml');
				if (is_file($file)) {
					// Attempt to load the xml file.
					if ($xml = simplexml_load_file($file)) {
						// Look for the first view node off of the root node.
						if ($menu = $xml->xpath('view[1]')) {
							$menu = $menu[0];

							// If the view is hidden from the menu, discard it and move on to the next view.
							if (!empty($menu['hidden']) && $menu['hidden'] == 'true') {
								unset($xml);
								continue;
							}

							// Do we have an options node or should we process layouts?
							// Look for the first options node off of the menu node.
							if ($optionsNode = $menu->xpath('options[1]')) {
								$optionsNode = $optionsNode[0];

								// Make sure the options node has children.
								if ($children = $optionsNode->children()) {
									// Process each child as an option.
									foreach ($children as $child)
									{
										if ($child->getName() == 'option') {
											// Create the menu option for the component.
											$o = new JObject;
											$o->title		= (string) $child['name'];
											$o->description	= (string) $child['msg'];
											$o->request		= array('option' => $component, 'view' => $view, (string) $optionsNode['var'] => (string) $child['value']);

											$options[] = $o;
										}
										elseif ($child->getName() == 'default') {
											// Create the menu option for the component.
											$o = new JObject;
											$o->title		= (string) $child['name'];
											$o->description	= (string) $child['msg'];
											$o->request		= array('option' => $component, 'view' => $view);

											$options[] = $o;
										}
									}
								}
							}
							else {
								$options = array_merge($options, (array) $this->getTypeOptionsFromLayouts($component, $view));
							}
						}
						unset($xml);
					}

				}
				else {
					$options = array_merge($options, (array) $this->getTypeOptionsFromLayouts($component, $view));
				}
			}
		}

		return $options;
	}

	/**
	 * Sync with codepools
	 * 
	 * @param string $component
	 * @param string $view
	 */
	protected function getTypeOptionsFromLayouts($component, $view)
	{
		// Initialise variables.
		$options = array();
		$layouts = array();
		$layoutNames = array();
		$templateLayouts = array();
		$lang = JFactory::getLanguage();

		// Get the views for this component.
		$paths = array(
			JPATH_SITE.'/components/'.$component.'/views/'.$view.'/tmpl',
			JPATH_SITE.'/code/'.$component.'/views/'.$view.'/tmpl',
		);
		
		
		if (JFolder::exists($paths[0]))
			$layouts = JFolder::files($paths[0], '.xml$', false, true);
		
		if (JFolder::exists($paths[1]))
		{
			$layouts = array_merge($layouts, JFolder::files($paths[1], '.xml$', false, true));
		}
		
		if (is_null($layouts) || empty($layouts))
		{
			return $options;
		}

		// build list of standard layout names
		foreach ($layouts as $layout)
		{
			// Ignore private layouts.
			if (strpos(JFile::getName($layout), '_') === false) {
				$file = $layout;
				// Get the layout name.
				$layoutNames[] = JFile::stripext(JFile::getName($layout));
			}
		}

		// get the template layouts
		// TODO: This should only search one template -- the current template for this item (default of specified)
		$folders = JFolder::folders(JPATH_SITE . '/templates', '', false, true);
		// Array to hold association between template file names and templates
		$templateName = array();
		foreach($folders as $folder)
		{
			if (JFolder::exists($folder . '/html/' . $component . '/' . $view)) {
				$template = JFile::getName($folder);
					$lang->load('tpl_'.$template.'.sys', JPATH_SITE, null, false, false)
				||	$lang->load('tpl_'.$template.'.sys', JPATH_SITE.'/templates/'.$template, null, false, false)
				||	$lang->load('tpl_'.$template.'.sys', JPATH_SITE, $lang->getDefault(), false, false)
				||	$lang->load('tpl_'.$template.'.sys', JPATH_SITE.'/templates/'.$template, $lang->getDefault(), false, false);

				$templateLayouts = JFolder::files($folder . '/html/' . $component . '/' . $view, '.xml$', false, true);

				foreach ($templateLayouts as $layout)
				{
					$file = $layout;
					// Get the layout name.
					$templateLayoutName = JFile::stripext(JFile::getName($layout));

					// add to the list only if it is not a standard layout
					if (array_search($templateLayoutName, $layoutNames) === false) {
						$layouts[] = $layout;
						// Set template name array so we can get the right template for the layout
						$templateName[$layout] = JFile::getName($folder);
					}
				}
			}
		}

		// Process the found layouts.
		foreach ($layouts as $layout)
		{
			// Ignore private layouts.
			if (strpos(JFile::getName($layout), '_') === false) {
				$file = $layout;
				// Get the layout name.
				$layout = JFile::stripext(JFile::getName($layout));

				// Create the menu option for the layout.
				$o = new JObject;
				$o->title		= ucfirst($layout);
				$o->description	= '';
				$o->request		= array('option' => $component, 'view' => $view);

				// Only add the layout request argument if not the default layout.
				if ($layout != 'default') {
					// If the template is set, add in format template:layout so we save the template name
					$o->request['layout'] = (isset($templateName[$file])) ? $templateName[$file] . ':' . $layout : $layout;
				}

				// Load layout metadata if it exists.
				if (is_file($file)) {
					// Attempt to load the xml file.
					if ($xml = simplexml_load_file($file)) {
						// Look for the first view node off of the root node.
						if ($menu = $xml->xpath('layout[1]')) {
							$menu = $menu[0];

							// If the view is hidden from the menu, discard it and move on to the next view.
							if (!empty($menu['hidden']) && $menu['hidden'] == 'true') {
								unset($xml);
								unset($o);
								continue;
							}

							// Populate the title and description if they exist.
							if (!empty($menu['title'])) {
								$o->title = trim((string) $menu['title']);
							}

							if (!empty($menu->message[0])) {
								$o->description = trim((string) $menu->message[0]);
							}
						}
					}
				}

				// Add the layout to the options array.
				$options[] = $o;
			}
		}

		return $options;
	}
}