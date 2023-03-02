<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Category Layouts Module Install/Update File
 *
 * @category	Module
 * @author		Simon Andersohn
 * @link		https://github.com/ignetic
 */

require_once PATH_THIRD.'category_layouts/config.php';

class Category_layouts_upd {

	public $version = CATEGORY_LAYOUTS_VERSION;
	private $module_name = CATEGORY_LAYOUTS_CLASS_NAME;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->module_name = strtolower(str_replace(array('_ext', '_mcp', '_upd'), "", __CLASS__));
	}

	/**
	 * Installation Method
	 *
	 * @return 	boolean 	TRUE
	 */
	public function install()
	{
		$mod_data = array(
			'module_name'			=> ucfirst($this->module_name),
			'module_version'		=> $this->version,
			'has_cp_backend'		=> 'y',
			'has_publish_fields'	=> 'n'
		);

		ee()->db->insert('modules', $mod_data);
     
     	// Sets up the table fields structure
		$this->add_layouts_table();

		return TRUE;
	}

	/**
	 * Uninstall
	 *
	 * @return 	boolean 	TRUE
	 */
	public function uninstall()
	{
		$mod_id = ee()->db->select('module_id')
			->get_where('modules', array(
				'module_name'	=> ucfirst($this->module_name)
			))->row('module_id');

		if (ee()->db->table_exists('module_member_groups')) {
			ee()->db->where('module_id', $mod_id)
				->delete('module_member_groups');
		}	
		
		if (ee()->db->table_exists('module_member_roles')) {
			ee()->db->where('module_id', $mod_id)
				->delete('module_member_roles');
		}

		ee()->db->where('module_name', ucfirst($this->module_name))
					 ->delete('modules');
			 
		ee()->load->dbforge();
		ee()->dbforge->drop_table($this->module_name);

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @return 	boolean 	TRUE
	 */
	public function update($current = '')
	{
		if ($current == $this->version)
		{
			return FALSE;
		}
		
		if (version_compare($current, '1.4.2', '<'))
		{
			// Create separate table
			if ($this->add_layouts_table())
			{
				// Move old data
				$query = ee()->db->select('settings')
					->where('module_name', ucfirst($this->module_name))
					->limit(1)
					->get('modules');
					
				$module_settings = unserialize($query->row('settings'));
				
				if (isset($module_settings['layouts']))
				{
					$layouts = @json_decode($module_settings['layouts']);
					if (!empty($layouts))
					{
						foreach ($layouts as $group_id => $layout)
						{
							$data = array(
								'site_id'			=> ee()->config->item('site_id'),
								'group_id'			=> $group_id,
								'layout'			=> (isset($layout->layout) ? json_encode($layout->layout) : '')
							);

							ee()->db->insert($this->module_name, $data);
						}
					}
				}
			}							

			// Remove settings from old table
			ee()->db->update('modules', array('settings' => NULL), array('module_name' => ucfirst($this->module_name)));
							
			// Update version
			ee()->db->update('modules', array('module_version' => $this->version), array('module_name' => ucfirst($this->module_name)));

		}
		
		return TRUE;
		
	}
	
	
	private function add_layouts_table()
	{
		if ( ! ee()->db->table_exists($this->module_name))
		{
			ee()->load->dbforge();
			
			// Sets up the table fields structure
			$table_fields = array(
				'id' => array(
					'type' => 'int',
					'constraint' => 10,
					'unsigned' => true,
					'auto_increment' => true
				),
				'site_id' => array(
					'type' => 'int',
					'constraint' => 4,
					'unsigned' => true,
					'auto_increment' => false
				),
				'group_id' => array(
					'type' => 'int',
					'constraint' => 4,
					'unsigned' => true,
					'auto_increment' => false
				),
				'layout' => array(
					'type' => 'text',
					'null' => TRUE
				),
				'settings' => array(
					'type' => 'text',
					'null' => TRUE
				)
			);
			
			// Set primary key and create table
			ee()->dbforge->add_field($table_fields);
			ee()->dbforge->add_key('id', true);
			ee()->dbforge->create_table($this->module_name);
			
			return TRUE;
		}
		return FALSE;
	}
	
}

/* End of file upd.category_layouts.php */
