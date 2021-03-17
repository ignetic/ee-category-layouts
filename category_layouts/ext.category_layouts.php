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
	private $EE3 = FALSE;
	
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
		
		if (defined('APP_VER') && version_compare(APP_VER, '4.0.0', '<'))
		{
			$this->EE3 = TRUE;
		}
		
		if ($this->EE3)
		{
			$this->allowed_segments = array('edit-cat','create-cat');
		}
		
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
		
		// Update version
		ee()->db->update('extensions', array('version' => $this->version), array('class' => __CLASS__));
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

		return $this->settings;
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

			if ($segments = $this->get_segments(ee()->uri->segments))
			{
				$_SESSION['category_layouts']['group_id'] = $segments['group_id'];
				$_SESSION['category_layouts']['uri'] = $segments['uri'];
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
						.file-chosen #cat_image, .fields-upload-chosen-file #cat_image { max-width: {$image_max_width}; }
					";
				}
			}
		}
		
		$data .= ee()->load->view('layout.css', array(), TRUE);
		
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

		if ($segments = $this->get_segments($uri))
		{
			$js_menus = 'menus_ee4.js';
			if (version_compare(APP_VER, '4', '<'))
			{
				$js_menus = 'menus_ee3.js';
			}
			
			$data .= ee()->load->view($js_menus, $js_vars, TRUE);
		
			if ( ! $segments['in_editor'])
			{
				return $data;
			}
		}
	
		if ( ! is_numeric($group_id))
		{
			return $data;
		}


		// Get layouts
		$settings = array();
		$layouts = array();
		$layouts_js = '';
		$layout_style = '';
		$columns = 2;

		if (ee()->db->table_exists($this->module_name))
		{
			$results = ee()->db
				->where('group_id', $group_id)
				->where('site_id', ee()->config->item('site_id'))
				->get($this->module_name);

			if ($results->num_rows() > 0)
			{
				$settings = ( ! empty($results->row('settings')) ? unserialize($results->row('settings')) : array());
				$layouts = ( ! empty($results->row('layout')) ? json_decode($results->row('layout')) : array());
				$layouts_js = $results->row('layout');
			}
		}

		$layouts_js = str_replace("'", "\'", $layouts_js);
		

		// Editors
		$wygwamInstalled = ee()->addons_model->module_installed('wygwam');
		$rteInstalled = ee()->addons_model->module_installed('rte');

		$rte_fields = array();
		$wygwam_fields = array();

		if (isset($settings['cat_editor']))
		{
			$cat_editor = explode(':', $settings['cat_editor']);
			$editor = $cat_editor[0];
			$config_id = isset($cat_editor[1]) ? $cat_editor[1] : FALSE;
			
			// RTE
			if ($editor == 'rte' && $rteInstalled)
			{
				$rte_fields['cat_description'] = $config_id;
			}	
			
			// Wygwam
			if ($editor == 'wygwam' && $wygwamInstalled)
			{
				$wygwam_fields['cat_description'] = $config_id;
			}
			
		}

		// Get editor configs
		if (is_array($layouts))
		{
			foreach ($layouts as $irow => $row)
			{
				foreach ($row as $icol => $col)
				{
					if ( ! empty($col)) 
					{
						foreach ($col as $key => $field)
						{
							if (isset($field->element))
							{
								$edit_field = explode(':', $field->element);
								$editor = $edit_field[0];
								$config_id = (isset($edit_field[1]) ? $edit_field[1] : FALSE);

								// RTE
								if (($editor == 'rte' || $editor == 'editor') && $rteInstalled)
								{
									$rte_fields['field_id_'.$field->id] = $config_id;
								}	
								
								// Wygwam
								if ($editor == 'wygwam' && $wygwamInstalled)
								{
									$wygwam_fields['field_id_'.$field->id] = $config_id;
								}
								
							}
						}
					}
				}
			}
		}

		
		// Load JS
		$js_vars['layouts_js'] = $layouts_js;
		$js_vars['columns'] = intval($columns);
		$js_vars['layouts_url'] = $layouts_url;
		$js_vars['group_id'] = $group_id;
		$js_vars['layout_style'] = (isset($settings['layout_style']) ? $settings['layout_style'] : '');
		
		if (version_compare(APP_VER, '4', '<'))
		{
			$js_view = 'layout_ee3.js';
		}
		else
		{
			$js_view = 'layout_ee4.js';
		}

$data .= "
/**********************************
/* Category Layouts
/**********************************/
";

		$data .= ee()->load->view($js_view, $js_vars, TRUE);


		// -------------------------------------------
		// Rich text editor
		// -------------------------------------------
		$delayed_rte = '';
		if (!empty($rte_fields))
		{
			ee()->load->add_package_path(SYSPATH.'ee/EllisLab/Addons/rte/');
			ee()->load->library('rte_lib');

			foreach($rte_fields as $field_id => $config_id)
			{
				// Dynamically loading wygwam scripts descrupts the RTE display, so let's delay it...
				if ( ! empty($wygwam_fields))
				{
					$delayed_rte .= ee()->rte_lib->build_js($config_id, 'textarea[name='.$field_id.']', NULL, FALSE);
				}
				else
				{
					$date .= ee()->rte_lib->build_js($config_id, 'textarea[name='.$field_id.']', NULL, FALSE);
				}
			}
		}


		// -------------------------------------------
		// Wygwam editor
		// -------------------------------------------
		if ( ! empty($wygwam_fields))
		{
			ee()->load->helper('wygwam_helper', 'wygwam_helper');
			$wygwamHelper = new Wygwam_helper();
			
			$wygwamThemeUrl = $wygwamHelper->themeUrl();
			$ckeditor = $wygwamThemeUrl.'lib/ckeditor/ckeditor.js';
			$wygwam = $wygwamThemeUrl.'scripts/wygwam.js';
			
			$handle = '';
			$configs = array();
			$wygwam_init_js = '';
			$wygwam_js = '';
			
			foreach ($wygwam_fields as $field_id => $config_id)
			{
				$allowed_content = '';
				// Cat Description field is limited, so let's restrict it
				if ($field_id == 'cat_description')
				{
					$allowed_content = 'img[!src,alt,width,height]{float};a[!href];h1 h2 div p b i ul ol';
				}
				$handle = $wygwamHelper->getConfigJsById($config_id, $allowed_content);
				$configs[$field_id] = $handle;
			}
			
			$wygwam_configs_js = $wygwamHelper->getJs();

			if (!empty($configs))
			{
				$wygwam_js .= '
					var j = document.createElement("script");
					j.setAttribute("src","' . $ckeditor . '");
					document.getElementsByTagName("body")[0].appendChild(j);';	
				
				foreach($configs as $field_id => $config_name)
				{
					// CKEditor script identifies fields by id
					$wygwam_js .= NL.'$("textarea[name='.$field_id.']").attr("id", "'.$field_id.'");'.NL;
					$wygwam_init_js .= NL.'new Wygwam("'.$field_id.'", "'.$config_name.'");'.NL;

				}
				
				$wygwam_js .= '
					/************************
					* Category Layouts Addon
					*************************/
					$(window).load(function() { 

						var j = document.createElement("script");
						j.setAttribute("src","' . $wygwam . '");
						document.getElementsByTagName("body")[0].appendChild(j);
					
						// Cat Description has XSS filtering so need to remove tab spacing
						var waitCKEDITOR = setInterval(function() {
							if (window.Wygwam && window.CKEDITOR) {
								clearInterval(waitCKEDITOR);

								// Restrict tab spacing - mainly for category description field
								CKEDITOR.on("instanceReady", function(ev) {
									ev.editor.indentationChars = "  ";
									var blockTags = ["div","h1","h2","h3","h4","h5","h6","p","pre","li","blockquote","ul","ol","table","thead","tbody","tfoot","td","th"];
									for (var i = 0; i < blockTags.length; i++) {
										ev.editor.dataProcessor.writer.setRules( blockTags[i], { indent : false, breakBeforeOpen : true, breakAfterOpen : false, breakBeforeClose : false, breakAfterClose : true });
									}
								});

								Wygwam.themeUrl = "' . $wygwamThemeUrl . '";
								'.$wygwam_configs_js.'
								'.$wygwam_init_js.'
								
								// Delayed RTE
								'.$delayed_rte.'
							}
						}, 50);
					});
				';
			}
			
			$data .= $wygwam_js;
		}
		
		
		// Image size (EE5)
		if (isset($settings['image_max_width']))
		{
			$image_max_width = (int) $settings['image_max_width'];
			if ($image_max_width > 73)
			{
				$data .= '
					var $catImage = $(".fields-upload-chosen-file img#cat_image");
					if ($catImage.length) {
						var largerImage = $(".fields-upload-chosen-file img#cat_image").attr("src").replace("/_thumbs/", "/");
						$(".fields-upload-chosen-file img#cat_image").attr("src", largerImage);
					}
				';
			}
		}
		


$data .= "
/* END - Category Layouts
/**********************************/
";

		return $data;
	}
	
	
	private function get_segments($uri)
	{
		$return = FALSE;
		
		if (is_array($uri))
		{
			$uri_segments = $uri;
		}
		else
		{
			$uri_segments = explode('/', $uri);
			
			//$uri_segments = array_combine(range(1, count($uri)), array_values($uri));
		}

		// start array at 1
		$uri_segments = array_pad($uri_segments, 5, FALSE);
		$uri_segments = array_combine(range(1, count($uri_segments)), array_values($uri_segments));

		if ($this->EE3)
		{

//			if ($uri_segments[1] == 'cp' && $uri_segments[2] == 'channels' && $uri_segments[3] == 'cat' && (in_array($uri_segments[4], $this->allowed_segments)) )
			if ($uri_segments[1] == 'cp' && $uri_segments[2] == 'channels' && $uri_segments[3] == 'cat')
			{
				$return['uri'] = implode('/', $uri_segments);
				$return['group_id'] = $uri_segments[5];
				$return['segments'] = $uri_segments;
				$return['in_editor'] = FALSE;
				if ($uri_segments[4] == 'edit-cat' || $uri_segments == 'create-cat')
				{
					$return['in_editor'] = TRUE;
				}
			}
		}
		else
		{
			if ($uri_segments[1] == 'cp' && $uri_segments[2] == 'categories' && (in_array($uri_segments[3], $this->allowed_segments)) )
			{
				$return['uri'] = implode('/', $uri_segments);
				$return['group_id'] = $uri_segments[4];
				$return['segments'] = $uri_segments;
				$return['in_editor'] = FALSE;
				if ($uri_segments[3] == 'edit' || $uri_segments[3] == 'create')
				{
					$return['in_editor'] = TRUE;
				}
			}
		}

		return $return;
	}

}

/* End of file ext.category_layouts.php */