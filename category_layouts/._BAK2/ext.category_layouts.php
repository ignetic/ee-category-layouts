<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Category Layouts Extension
 *
 * @category	Module
 * @author		Simon Andersohn
 * @link		https://github.com/ignetic
 */

require_once PATH_THIRD.'category_layouts/config.php';

class Category_layouts_ext {

	public $name           = CATEGORY_LAYOUTS_NAME;
	public $description    = CATEGORY_LAYOUTS_DESCRIPTION;
	public $version        = CATEGORY_LAYOUTS_VERSION;
	public $settings_exist = 'n';
	public $settings       = array();
	public $docs_url       = '';

	private $module_name = CATEGORY_LAYOUTS_CLASS_NAME;
	
	/**
	 * Extension hooks
	 *
	 * @var array
	 */
	private $hooks = array(
		'cp_css_end',
		'cp_js_end',
		'core_boot',
	);
	
	private $allowed_segments = array('group','groups','edit','create');

	/**
	 * Constructor
	 *
	 * @param	array
	 * @return  void
	 */
	function __construct($settings = '')
	{
		$this->settings = $settings;
	}

	/**
	 * Activate extension
	 *
	 * @return  void
	 */
	function activate_extension()
	{
		if (version_compare(APP_VER, '3.0', '<'))
		{
			return; // Need v3 or above
		}

		foreach ($this->hooks AS $hook)
		{
			ee()->db->insert('extensions', array(
				'class'    => __CLASS__,
				'method'   => $hook,
				'hook'     => $hook,
				'settings' => '',
				'priority' => 10,
				'version'  => $this->version,
				'enabled'  => 'y'
			));
		}
	}

	/**
	 * Update the extension
	 *
	 * @param	string
	 * @return	bool
	 */
	function update_extension($current = '')
	{
		if ($current == $this->version)
		{
			return FALSE;
		}
		
		if (version_compare($current, '1.4.2', '<'))
		{
			ee()->db->insert('extensions', array(
				'class'    => __CLASS__,
				'method'   => 'core_boot',
				'hook'     => 'core_boot',
				'settings' => '',
				'priority' => 10,
				'version'  => $this->version,
				'enabled'  => 'y'
			));
		}
	}

	/**
	 * Disable the extension
	 *
	 * @return	bool
	 */
	function disable_extension()
	{
		// Remove references from extensions
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');

		return TRUE;
	}

	/**
	 * Abstract settings form
	 *
	 * @return	array
	 */
	function settings()
	{

		return $settings;
	}


	// --------------------------------------------------------------------
	// HOOKS
	// --------------------------------------------------------------------

	/**
	 * Hook: core_boot
	 *
	 * @return	void
	 */
	public function core_boot()
	{
		if (REQ === 'CP')
		{
			// Get group id from uri to use later in cp_js_end
			if( ! isset($_SESSION)) 
			{ 
				session_start(); 
			}

			if (ee()->uri->segment(1) == 'cp' && ee()->uri->segment(2) == 'categories' && in_array(ee()->uri->segment(3), $this->allowed_segments) && ee()->uri->segment(4))
			{
				$_SESSION['category_layouts']['group_id'] = ee()->uri->segment(4);
				$_SESSION['category_layouts']['uri'] = ee()->uri->uri_string;
			}
		}

		return;
	}


	/**
	 * Hook: cp_css_end
	 *
	 * @param	string $data
	 * @return	string
	 */
	public function cp_css_end()
	{
		$data = '';
		
		if (ee()->extensions->last_call !== FALSE)
		{
			$data = ee()->extensions->last_call;
		}
		
		if( ! isset($_SESSION)) 
		{ 
			session_start(); 
		}
		
		$group_id = FALSE;
		$uri = FALSE;

		// Get group id from session then unset it so that it does't apply to other pages
		if (isset($_SESSION['category_layouts']))
		{
			$group_id = (isset($_SESSION['category_layouts']['group_id']) ? $_SESSION['category_layouts']['group_id'] : FALSE);
			$uri = (isset($_SESSION['category_layouts']['uri']) ? $_SESSION['category_layouts']['uri'] : FALSE);
		}

		$settings = array();
		
		if (ee()->db->table_exists($this->module_name) && $group_id)
		{
			$results = ee()->db
				->select('settings')
				->where('group_id', $group_id)
				->where('site_id', ee()->config->item('site_id'))
				->get($this->module_name);

			if ($results->num_rows() > 0)
			{
				$settings = (!empty($results->row('settings')) ? unserialize($results->row('settings')) : array());
				
				if (isset($settings['image_max_width']))
				{
					$image_max_width = $settings['image_max_width'];
					if (is_numeric($settings['image_max_width']))
					{
						$image_max_width .= 'px';
					}
					$data .= "
						.file-chosen #cat_image { max-width: {$image_max_width}; }
					";
				}
			}
		}
		
		$data .= ee()->load->view('extension.css', array(), TRUE);
		
		return $data;
	}

	
	
	/**
	 * Hook: cp_js_end
	 *
	 * @param	string $data
	 * @return	string
	 */
	public function cp_js_end()
	{
		$data = '';

		if( ! isset($_SESSION)) 
		{ 
			session_start(); 
		}

		if (ee()->extensions->last_call !== FALSE)
		{
			$data = ee()->extensions->last_call;
		}

		$group_id = FALSE;
		$uri = FALSE;

		// Get group id from session then unset it so that it does't apply to other pages
		if (isset($_SESSION['category_layouts']))
		{
			$group_id = (isset($_SESSION['category_layouts']['group_id']) ? $_SESSION['category_layouts']['group_id'] : FALSE);
			$uri = (isset($_SESSION['category_layouts']['uri']) ? $_SESSION['category_layouts']['uri'] : FALSE);
			unset($_SESSION['category_layouts']);
		}

		// Detect if we are on the right page
		$uri_segments = array_pad(explode('/', $uri), 5, 0);
		$layouts_url = ee('CP/URL', 'addons/settings/'.$this->module_name); 
		
		$js_vars['layouts_url'] = $layouts_url;

		if ($uri_segments[0] == 'cp' && $uri_segments[1] == 'categories' && (in_array($uri_segments[2], $this->allowed_segments)) )
		{
			$js_menus = 'menus_ee4.js';
			if (version_compare(APP_VER, '4', '<'))
			{
				$js_menus = 'menus_ee3.js';
			}
			
			$data .= ee()->load->view($js_menus, $js_vars, TRUE);
			
			if ($uri_segments[2] !== 'edit' && $uri_segments[2] !== 'create')
			{
				return $data;
			}
		}
	
		if ( ! is_numeric($group_id))
		{
			return $data;
		}


		$settings = array();
		$layouts_js = '';
		$columns = 2;

		if (ee()->db->table_exists($this->module_name))
		{
			$results = ee()->db
				->where('group_id', $group_id)
				->where('site_id', ee()->config->item('site_id'))
				->get($this->module_name);

			if ($results->num_rows() > 0)
			{
				$settings = (!empty($results->row('settings')) ? unserialize($results->row('settings')) : '');
				$layouts_js = $results->row('layout');
			}
		}

		$layouts_js = str_replace("'", "\'", $layouts_js);
		
		
		
		// JS
		$js_vars['layouts_js'] = $layouts_js;
		$js_vars['columns'] = intval($columns);
		$js_vars['layouts_url'] = $layouts_url;
		$js_vars['group_id'] = $group_id;
		
		if (version_compare(APP_VER, '4', '<'))
		{
			$js_view = 'extension_ee3.js';
		}
		else
		{
			$js_view = 'extension_ee4.js';
		}

$data .= "
/**********************************
/* Category Layouts
/**********************************/
";

		$data .= ee()->load->view($js_view, $js_vars, TRUE);


		// Rich text editor
		ee()->load->add_package_path(SYSPATH.'ee/EllisLab/Addons/rte/');
		ee()->load->library('rte_lib');
		$data .= ee()->rte_lib->build_js(0, '.cat-field-editor textarea', NULL, FALSE);

		if (isset($settings['cat_editor']))
		{
			$cat_editor = explode(':', $settings['cat_editor']);
			$editor = $cat_editor[0];
			$config_id = isset($cat_editor[1]) ? $cat_editor[1] : FALSE;

			// RTE
			if ($editor == 'rte' && ee()->addons_model->module_installed('wygwam'))
			{
				$data .= ee()->rte_lib->build_js($config_id, 'textarea.cat-editor', NULL, FALSE);
			}
			// Wygwam
			elseif ($editor == 'wygwam' && ee()->addons_model->module_installed('rte'))
			{
				$config = FALSE;
				
				if (ee()->db->table_exists('wygwam_configs') && is_numeric($config_id))
				{
					
					ee()->load->helper('wygwam_helper', 'wygwam_helper');
					$wygwam_helper = new Wygwam_helper();
					
					// Check if we have the right class
					if ( ! method_exists($wygwam_helper, 'baseConfig'))
					{
						return $data;
					}
					
					$base_config = $wygwam_helper->baseConfig();
					
					$wygwam_configs = ee()->db->select('settings')
						->where('config_id', $config_id)
						->limit(1)
						->get('wygwam_configs');
				
					$js_config = '';
				
					if ($wygwam_configs->num_rows() > 0)
					{
						$config = @unserialize(@base64_decode($wygwam_configs->row('settings')));

						$config = array_merge($base_config, $config);

						unset($config['contentsCss']);
						unset($config['restrict_html']);
						unset($config['upload_dir']);

						$config['allowedContent'] = true;
						$config['language'] = 'en';


						if (is_array($config['toolbar']))
						{
							$config['toolbar'] = $wygwam_helper->createToolbar($config['toolbar']);
						}

						$config_booleans = $wygwam_helper->configBooleans();

						$config_literals = $wygwam_helper->configLiterals();

						foreach ($config as $setting => $value)
						{
							if (! in_array($setting, $config_literals))
							{
								if (in_array($setting, $config_booleans))
								{
									$value = ($value == 'y' ? TRUE : FALSE);
								}

								$value = json_encode($value);

								// Firefox gets an "Unterminated string literal" error if this line gets too long,
								// so let's put each new value on its own line
								if ($setting == 'link_types')
								{
									$value = str_replace('","', "\",\n\t\t\t\"", $value);
								}
							}

							$js_config .= ($js_config ? ','.NL : '')
								 . "\t\t".'"'.$setting.'": '.$value;
						}
						
						$js_config = str_replace(array(chr(10), chr(11), chr(12), chr(13)), ' ', $js_config);

					}
				}

				$wygwam_base = URL_THIRD_THEMES.'wygwam/';
				$ckeditor = $wygwam_base.'lib/ckeditor/ckeditor.js';
				$wygwam = $wygwam_base.'scripts/wygwam.js';
				$wygwamThemeUrl = $wygwam_helper->themeUrl();

				$wygwam_js = '$("textarea.cat-editor").attr("id", "cat_description");';

				$wygwam_js .= '
					var j = document.createElement("script");
					j.setAttribute("src","' . $ckeditor . '");
					document.getElementsByTagName("body")[0].appendChild(j);';	

				$wygwam_js .= '
					$(window).load(function(){ 

						var j = document.createElement("script");
						j.setAttribute("src","' . $wygwam . '");
						document.getElementsByTagName("body")[0].appendChild(j);
					
						var waitCKEDITOR = setInterval(function() {
							if (window.Wygwam && window.CKEDITOR) {
								clearInterval(waitCKEDITOR);
								Wygwam.themeUrl = "' . $wygwamThemeUrl . '";
								Wygwam.configs["catConfig"] = {'.NL.$js_config.NL."\t".'};
								new Wygwam("cat_description", "catConfig"); 
							}
						}, 50);
					});

				';
				
				$data .= 'if (/\/cp\/categories\/(edit|create)\//.test(window.location.href)) {'.$wygwam_js.'}';
				
			}

		}


$data .= "
/* END - Category Layouts
/**********************************/
";

		return $data;
	}

}

/* End of file ext.category_layouts.php */