<?php
defined('_JEXEC') or die;

/**
 * Module model.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_modules
 * @since       1.6
 */
class ModulesModelModule extends ModulesModelModuleDefault
{
	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItem($pk = null)
	{
		// Initialise variables.
		$pk	= (!empty($pk)) ? (int) $pk : (int) $this->getState('module.id');
		$db	= $this->getDbo();

		if (!isset($this->_cache[$pk]))
		{
			$false	= false;

			// Get a row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			$return = $table->load($pk);

			// Check for a table object error.
			if ($return === false && $error = $table->getError())
			{
				$this->setError($error);
				return $false;
			}

			// Check if we are creating a new extension.
			if (empty($pk))
			{
				if ($extensionId = (int) $this->getState('extension.id'))
				{
					$query	= $db->getQuery(true);
					$query->select('element, client_id');
					$query->from('#__extensions');
					$query->where('extension_id = ' . $extensionId);
					$query->where('type = ' . $db->quote('module'));
					$db->setQuery($query);

					try
					{
						$extension = $db->loadObject();
					}
					catch (RuntimeException $e)
					{
						$this->setError($e->getMessage);
						return false;
					}

					if (empty($extension))
					{
						$this->setError('COM_MODULES_ERROR_CANNOT_FIND_MODULE');
						return false;
					}

					// Extension found, prime some module values.
					$table->module    = $extension->element;
					$table->client_id = $extension->client_id;
				}
				else
				{
					$app = JFactory::getApplication();
					$app->redirect(JRoute::_('index.php?option=com_modules&view=modules', false));
					return false;
				}
			}

			// Convert to the JObject before adding other data.
			$properties = $table->getProperties(1);
			$this->_cache[$pk] = JArrayHelper::toObject($properties, 'JObject');

			// Convert the params field to an array.
			$registry = new JRegistry;
			$registry->loadString($table->params);
			$this->_cache[$pk]->params = $registry->toArray();

			// Determine the page assignment mode.
			$db->setQuery(
				'SELECT menuid' .
				' FROM #__modules_menu' .
				' WHERE moduleid = ' . $pk
			);
			$assigned = $db->loadColumn();

			if (empty($pk))
			{
				// If this is a new module, assign to all pages.
				$assignment = 0;
			}
			elseif (empty($assigned))
			{
				// For an existing module it is assigned to none.
				$assignment = '-';
			}
			else
			{
				if ($assigned[0] > 0)
				{
					$assignment = +1;
				}
				elseif ($assigned[0] < 0)
				{
					$assignment = -1;
				}
				else
				{
					$assignment = 0;
				}
			}

			$this->_cache[$pk]->assigned = $assigned;
			$this->_cache[$pk]->assignment = $assignment;

			// Get the module XML.
			$client	= JApplicationHelper::getClientInfo($table->client_id);
			$query = $db->getQuery(true);
			$query->select('params')->from('#__extensions')->where('type="plugin" AND element = "joomlaoverride"');
			$db->setQuery($query);
			$pluginParams = new JRegistry($db->loadResult());
			
			$basePath = JPath::clean(JPATH_SITE.DIRECTORY_SEPARATOR.$pluginParams->get('global_path'));
			if ($client->id == JFactory::getApplication()->getClientId())
			{
				$basePath .= '/administrator';
			}
			$paths = array($basePath, $client->path. '/modules/');
			$path = JPath::find($paths, $table->module . '/' . $table->module . '.xml');

			if (file_exists($path))
			{
				$this->_cache[$pk]->xml = simplexml_load_file($path);
			}
			else
			{
				$this->_cache[$pk]->xml = null;
			}
		}

		return $this->_cache[$pk];
	}

	/**
	 * Method to preprocess the form
	 *
	 * @param   JForm   $form   A form object.
	 * @param   mixed   $data   The data expected for the form.
	 * @param   string  $group  The name of the plugin group to import (defaults to "content").
	 *
	 * @return  void
	 *
	 * @since   1.6
	 * @throws  Exception if there is an error loading the form.
	 */
	protected function preprocessForm(JForm $form, $data, $group = 'content')
	{
		jimport('joomla.filesystem.path');

		// Initialise variables.
		$lang     = JFactory::getLanguage();
		$clientId = $this->getState('item.client_id');
		$module   = $this->getState('item.module');

		$client   = JApplicationHelper::getClientInfo($clientId);
		
		$db = JFactory::getDbo();
		
		$query = $db->getQuery(true);
		$query->select('params')->from('#__extensions')->where('type="plugin" AND element = "joomlaoverride"');
		$db->setQuery($query);
		$pluginParams = new JRegistry($db->loadResult());
		
		$basePath = JPath::clean(JPATH_SITE.DIRECTORY_SEPARATOR.$pluginParams->get('global_path'));
		if ($client->id == JFactory::getApplication()->getClientId())
		{
			$basePath .= '/administrator';
		}
		
		$paths = array($basePath, $client->path. '/modules/');
		$formFile = JPath::find($paths, $module . '/' . $module . '.xml');

		// Load the core and/or local language file(s).
		$lang->load($module, $client->path, null, false, false)
			||	$lang->load($module, $client->path . '/modules/' . $module, null, false, false)
			||	$lang->load($module, $client->path, $lang->getDefault(), false, false)
			||	$lang->load($module, $client->path . '/modules/' . $module, $lang->getDefault(), false, false);

		if (file_exists($formFile))
		{
			// Get the module form.
			if (!$form->loadFile($formFile, false, '//config'))
			{
				throw new Exception(JText::_('JERROR_LOADFILE_FAILED'));
			}

			// Attempt to load the xml file.
			if (!$xml = simplexml_load_file($formFile))
			{
				throw new Exception(JText::_('JERROR_LOADFILE_FAILED'));
			}

			// Get the help data from the XML file if present.
			$help = $xml->xpath('/extension/help');
			if (!empty($help))
			{
				$helpKey = trim((string) $help[0]['key']);
				$helpURL = trim((string) $help[0]['url']);

				$this->helpKey = $helpKey ? $helpKey : $this->helpKey;
				$this->helpURL = $helpURL ? $helpURL : $this->helpURL;
			}

		}

		// Load the default advanced params
		JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_modules/models/forms');
		$form->loadFile('advanced', false);

		// Trigger the default form events.
		parent::preprocessForm($form, $data, $group);
	}
}