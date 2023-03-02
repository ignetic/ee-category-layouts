<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Category Layouts Module Control Panel File
 *
 * @category	Module
 * @author		Simon Andersohn
 * @link		https://github.com/ignetic
 */

require_once PATH_THIRD.'category_layouts/config.php';

class Category_layouts_mcp {
	
	public $version = CATEGORY_LAYOUTS_VERSION;
	private $module_name = CATEGORY_LAYOUTS_CLASS_NAME;

	private $settings = array();
	private $site_id = 1;
	private $_form_url;
	private $_base_url;
	private $EE3 = FALSE;

	private $default_settings = array(
		'site_id' => 1,
		'group_id' => 0,
		'layout' => '',
		'settings' => ''
	);
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->module_name = strtolower(str_replace(array('_ext', '_mcp', '_upd'), "", __CLASS__));
		
		if(!defined('URL_THIRD_THEMES')) {
			define('URL_THIRD_THEMES',	ee()->config->slash_item('theme_folder_url').'third_party/');
		}
		
		$this->site_id = ee()->config->item('site_id');

		$this->_form_url = ee('CP/URL', 'addons/settings/'.$this->module_name); 
		$this->_base_url = ee('CP/URL', 'addons/settings/'.$this->module_name); 

		if (defined('APP_VER') && version_compare(APP_VER, '4.0.0', '<'))
		{
			$this->EE3 = TRUE;
		}

	}

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
		
		$group_id = ee()->input->get('group_id');
		
		$vars['base_url'] = $this->_base_url;
		$vars['form_url'] = $this->_form_url.AMP.'method=save_settings'.AMP.'group_id='.$group_id;
		
		if ($this->EE3)
		{
			$vars['cat_managment_url'] = ee('CP/URL', 'channels/cat');
		}
		else
		{
			$vars['cat_managment_url'] = ee('CP/URL', 'categories');
		}
		
		// Menus js
		$js_vars = array();
		$js_menus = 'menus_ee4.js';
		if ($this->EE3)
		{
			$js_menus = 'menus_ee3.js';
		}
		$js_vars['layouts_url'] = $this->_base_url; 
		$js = ee()->load->view($js_menus, $js_vars, TRUE);
		
		// Default page
		if ( ! $group_id)
		{
			ee()->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
			$this->generateSidebar();
			return ee()->load->view('index', $vars, TRUE);
		}
		
		
		// Category page
		$this->get_gettings($group_id);
		
		$vars['group_name'] = lang('category_group_not_found');

		// Get cat group name
		$q = ee()->db->select('group_name')
				->where('group_id', $group_id)
				->get('category_groups');
		$results = $q->result_array();
		foreach ($results as $result) {
			$vars['group_name'] = $result['group_name'];
		}
				
		$included_items = array();
		$included = array();
		$excluded = array();
		$num_cols = 2;


		// Get Editors List
		$wygwamInstalled = ee()->addons_model->module_installed('wygwam');
		$rteInstalled = ee()->addons_model->module_installed('rte');
		$editor_select = array('textarea' => 'Textarea');

		if ($rteInstalled && ee()->db->table_exists('rte_toolsets'))
		{
			if (defined('APP_VER') && version_compare(APP_VER, '6.0.0', '>=')) 
			{
				$rte_configs = ee('Model')->get('rte:Toolset')->all()->toArray();
			}
			else
			{
				//ee()->load->model('rte_toolset_model');
				//$rte_configs = ee()->rte_toolset_model->get_toolset_list();
				$rte_configs = ee()->db->select('toolset_id, name')
					->get('rte_toolsets')->result_array();
			}
			
			foreach ($rte_configs as $row)
			{
				$editor_select['Rich Text Editor']['rte:'.$row['toolset_id']] = 'RTE: '.(isset($row['toolset_name']) ? $row['toolset_name'] : $row['name']);
			}
		}
		if ($wygwamInstalled && ee()->db->table_exists('wygwam_configs'))
		{
			$wygwam_configs = ee()->db->select('config_id, config_name')
				->get('wygwam_configs');
			
			foreach ($wygwam_configs->result_array() as $row)
			{
				$editor_select['Wygwam']['wygwam:'.$row['config_id']] = 'Wygwam: '.$row['config_name'];
			}
		}


		$cat_fields = array();

		$q = ee()->db->select('field_id, field_name, field_label, field_type')
				->where('group_id', $group_id)
				->order_by('field_order')
				->get('category_fields');

		$results = $q->result_array();

		foreach ($results as $result) {
			$cat_fields[$result['field_id']] = $result;
			if ($cat_fields[$result['field_id']]['field_type'] == 'textarea')
			{
				$cat_fields[$result['field_id']]['editor_select'] = $editor_select;
			}
		}

		// Sort layouts into selected/included items
		if (is_array($this->settings['layout']))
		{
			foreach ($this->settings['layout'] as $irow => $row)
			{
				foreach ($row as $icol => $col)
				{
					if (empty($col)) 
					{
						$included[$irow][$icol] = array();
					}
					else
					{
						foreach ($col as $field_id => $field)
						{
							if (isset($cat_fields[$field->id]))
							{
								if (isset($field->element))
								{
									$cat_fields[$field->id]['element'] = $field->element;
								}
								$included_items[$field->id] = $cat_fields[$field->id];
								$included[$irow][$icol][$field->id] = $cat_fields[$field->id];
							}
							else
							{

								$included[$irow][$icol] = array();
							}	
						}
					}
				}
			}
		}


		// Vars
		$cat_group_link = 'categories/group/';
		if ($this->EE3)
		{
			$cat_group_link = 'channels/cat/cat-list/';
		}
		
		$vars['cat_group_link'] = ee('CP/URL')->make($cat_group_link . $group_id);
		$vars['editor_select'] = $editor_select;
		$vars['cat_editor'] = (isset($this->settings['settings']['cat_editor']) ? $this->settings['settings']['cat_editor'] : '');
		$vars['image_max_width'] = (isset($this->settings['settings']['image_max_width']) ? $this->settings['settings']['image_max_width'] : '');
		$vars['layout_style'] = (isset($this->settings['settings']['layout_style']) ? $this->settings['settings']['layout_style'] : '');
		
		$vars['included'] = $included;
		$vars['excluded'] = array_diff_key($cat_fields, $included_items);
		
		$vars['num_cols'] = $num_cols;
		$vars['group_id'] = $group_id;
		$vars['layout'] = $this->settings['layout'];

		$css_vars = array();
		$css = ee()->load->view('settings.css', $css_vars, TRUE);
		ee()->cp->add_to_head('<style>'.$css.'</style>');

		$js .= ee()->load->view('settings.js', $js_vars, TRUE);
		
		ee()->cp->add_to_foot('<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>');
		ee()->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
		
		
		ee()->cp->add_js_script(array('ui' => array( 'core', 'widget', 'mouse', 'sortable')));
		
		$this->generateSidebar($group_id);

		return ee()->load->view('settings', $vars, TRUE);
		 
	}
	
	/**
	 * Remove Function
	 *
	 * @return 	void
	 */
	public function remove()
	{
		$group_id = ee()->input->post('content_id');
		
		if ($group_id)
		{
			ee()->db->delete($this->module_name, array('group_id' => $group_id));
		}
		
		ee()->functions->redirect($this->_base_url.AMP.'group_id='.$group_id);
	}
	
	/**
	 * Remove Function
	 *
	 * @param 	group_id int
	 * @return 	settings array
	 */
	function get_gettings($group_id=FALSE)
	{
		if ( ! ee()->db->table_exists($this->module_name))
		{
			ee('CP/Alert')->makeBanner($this->module_name)
				->asIssue()
				->withTitle(lang('fail_get_settings'))
				->addToBody(lang('fail_get_settings_desc'))
				->defer();
				
			ee()->functions->redirect($this->_base_url);

			return FALSE;
		}
		
		if ($group_id)
		{
			ee()->db->where('group_id', $group_id);
		}
		$result = ee()->db->get($this->module_name);
		
		$module_settings = array();
		
		if ($result->num_rows() > 0)
		{
			$module_settings['site_id'] = $result->row('site_id');
			$module_settings['group_id'] = $result->row('group_id');
			$module_settings['layout'] = json_decode($result->row('layout'));
			$module_settings['settings'] = unserialize($result->row('settings'));
		}

		$this->settings = array_merge($this->default_settings, $module_settings);

		return $this->settings;
	}
	
		
	/**
	 * Save Settings
	 *
	 * This function provides a little extra processing and validation
	 * than the generic settings form.
	 *
	 * @return void
	 */
    function save_settings($data=array(), $redirect=TRUE)
    {
		if (!empty($data))
		{
			$settings = $data;
		}
		elseif (!empty($_POST))
		{
			unset($_POST['submit']);
			$settings = $_POST;
		}
		else
		{
			show_error(lang('unauthorized_access'));
			return;
		}

		$group_id = ee()->input->post('group_id');
		$layout = ee()->input->post('layout');
		$settings = ee()->input->post('settings');

		$cat_groups = ee()->db
			->where('group_id', $group_id)
			->get('category_groups');

		if ($cat_groups->num_rows() == 0)
		{
			ee('CP/Alert')->makeStandard($this->module_name)
				->asWarning()
				->withTitle(lang('fail_layout_updated'))
				->addToBody(lang('fail_layout_updated_desc'))
				->defer();
				
			if ($redirect)
			{
				ee()->functions->redirect($this->_base_url);
			}
		}

		$data = array(
			'site_id'			=> $this->site_id,
			'group_id'			=> $group_id,
			'layout'			=> $layout,
			'settings'			=> serialize($settings)
		);

		$results = ee()->db
			->where('group_id', $group_id)
			->get($this->module_name);

		if ($results->num_rows() > 0)
		{
			ee()->db->update($this->module_name, $data, array('group_id' => $group_id, 'site_id' => $this->site_id));
		}
		else
		{
			ee()->db->insert($this->module_name, $data);
		}

		ee('CP/Alert')->makeStandard($this->module_name)
			->asSuccess()
			->withTitle(lang('success_layout_updated'))
			->addToBody(lang('success_layout_updated_desc'))
			->defer();

		if ($redirect)
		{
			ee()->functions->redirect($this->_base_url.AMP.'group_id='.$group_id);
		}
		
    }

	/**
	 * generateSidebar
	 *
	 * Create CP sidebar
	 *
	 * @return void
	 */
	protected function generateSidebar($active = NULL)
	{
		$edit_link = 'categories/groups/edit/';
		if ($this->EE3)
		{
			$edit_link = 'channels/cat/field/';
		}
		
		ee()->javascript->set_global(
			'sets.importUrl',
			ee('CP/URL', 'channels/sets')->compile()
		);
		ee()->cp->add_js_script(array(
			'file' => array('cp/channel/menu'),
		));

		$sidebar = ee('CP/Sidebar')->make();
		$header = $sidebar->addHeader(lang('category_groups'));

		$list = $header->addFolderList('categories')
			->withNoResultsText(sprintf(lang('no_found'), lang('category_groups')));

		if ($this->hasPermission('can_delete_categories'))
		{
			$list->withRemoveUrl(ee('CP/URL')->make('addons/settings/'.$this->module_name.'/remove'))
				->withRemovalKey('content_id');
		}

		$groups = ee('Model')->get('CategoryGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_name')
			->all();

		foreach ($groups as $group)
		{
			$group_name = htmlentities($group->group_name, ENT_QUOTES, 'UTF-8');

			$item = $list->addItem(
				$group_name,
				ee('CP/URL')->make('addons/settings/'.$this->module_name, array('group_id' => $group->getId()))
			);

			if ( ! $this->hasPermission('can_edit_categories'))
			{
				$item->cannotEdit();
			}
			else
			{
				$item->withEditUrl(
					ee('CP/URL')->make($edit_link . $group->getId())
				);
			}

			if ( ! $this->hasPermission('can_delete_categories'))
			{
				$item->cannotRemove();
			}
			else
			{
				$item->withRemoveConfirmation(
					lang('category_layouts') . ': <b>' . $group_name . '</b>'
				)->identifiedBy($group->getId());
			}

			if ($active == $group->getId())
			{
				$item->isActive();
			}
			else
			{
				$item->isInactive();
			}
		}
	}
	
	private function hasPermission() 
	{
		$which = func_get_args();
		if (defined('APP_VER') && version_compare(APP_VER, '6.0.0', '>='))
		{
			return ee('Permission')->hasAll($which);
		}
		else
		{
			return ee()->cp->allowed_group($which);
		}
	}
}

