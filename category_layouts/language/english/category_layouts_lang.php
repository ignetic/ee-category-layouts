<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'category_layouts/config.php';

$lang = array(
	'category_layouts_module_name' => CATEGORY_LAYOUTS_NAME,
	'category_layouts_module_description' => CATEGORY_LAYOUTS_DESCRIPTION,
	'category_layouts_module_info' => 'You can access the category layouts from the main categories menu where you will see a "Layouts" button to access it.',
	
	'category_layouts' => 'Category Layouts',
	'category_groups' => 'Category Groups',
	'category_layouts_description' => 'Drag and drop to create layout for the category view.',
	'layout_settings' => 'Layout Settings',
	'category_management' => 'Category Management',

	'module_home' => 'Main Menu',
	'category_description_editor' => 'Main Category Description Editor',
	'category_description_editor_desc' => 'Wysiwyg editor has limited capability due to EE validation restrictions.',
	'category_image_max_width' => 'Category Image Max Width',
	'category_image_max_width_desc' => 'Constrain the maximum size of the image displayed.',
	'layout_style' => 'Layout Style',
	'layout_style_desc' => 'Spaced style displays gaps between field rows',
	
	'drop_here_to_add' => 'Drop here to add to layout',
	'drop_here_to_remove' => 'Drop here to remove',
	
	'no_category_message' => 'No Custom Category Fields Found',
	'category_group_not_found' => 'Category group not found',
	
	'success_layout_updated' => 'Layout updated',
	'success_layout_updated_desc' => 'Category layout has been updated successfully',
	'fail_layout_updated' => 'Layout not updated',
	'fail_layout_updated_desc' => 'Category has not been updated: category group not found',
	'fail_get_settings' => 'Failed to get settings',
	'fail_get_settings_desc' => 'Category Layouts table cannot be found. Try reinstalling.',

);

