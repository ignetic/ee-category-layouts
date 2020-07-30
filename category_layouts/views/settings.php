<?php

function display_field_type($field)
{
	$field_display = '';
	$field_data = 'data-field-id="'.$field['field_id'].'" data-field-type="'.$field['field_type'].'" disabled';
	$selected = (isset($field['element']) ? $field['element'] : '');
	$editor_select = (isset($field['editor_select']) ? $field['editor_select'] : array());

	switch ($field['field_type']) {
		case 'text':
			$field_display = form_dropdown('cl_text', array('text' => 'Text', 'email' => 'Email', 'number' => 'Number', 'category_select' => 'Category Select'), $selected); //form_input('cl_text', 'Text Input', 'disabled');
			break;
		case 'textarea':
			$field_display = form_dropdown('cl_textarea', $editor_select, $selected); //form_textarea('cl_textarea', 'Textarea', 'disabled');
			break;
		case 'select':
			$field_display = form_dropdown('cl_select', array('select' => 'Select Dropdown', 'category_select' => 'Category Select'), $selected); //form_dropdown('cl_select', array('Select'), 'disabled');
			break;
		case 'cat_image':
			$field_display = '<div class="cl_image"><div class="image"></div></div>';
			break;
	}
	
	return '
		<li class="ui-state-default" class="type-'.$field['field_type'].'" '.$field_data.'">
			<h4><span class="handle"></span><span class="label" title="'.$field['field_label'].'">'.$field['field_label'].'</span></h4>
			'.$field_display.'
		</li>';
}

?>
<div class="box">
	<div class="tbl-ctrls">

	<h1><?= $group_name ?> &nbsp; <a href="<?= $cat_group_link ?>" class="icon icon-settings icon-tool--list"></a></h1>

	<p><?= lang('category_layouts_description') ?></p>

	<div id="sortables">

		<div class="drop-source">
			<p class="drop-description"><?= lang('drop_here_to_remove') ?></p>
			<ul class="sortable droptrue">
			<?php
				if (count($excluded) > 0)
				{
					foreach ($excluded as $field)
					{
						echo display_field_type($field);
					}
				}
			?>
			</ul>
		</div>

		<div class="drop-container cols-<?= $num_cols ?>">
			<div class="drop-target">
			<?php
				if (empty($included) && empty($excluded)) 
				{
					echo '<p class="drop-description">'.lang('no_category_message').'</p>';
				}
				else
				{
					echo '<p class="drop-description">'. lang('drop_here_to_add') .'</p>';
				
					if (empty($included) && !empty($excluded)) 
					{
						echo '<div class="row droptrue cols-'.$num_cols.'"><a href="#" class="button remove_row" title="Remove Row">-</a>';
						for ($i=0; $i < $num_cols; $i++)
						{
							echo '<ul class="col sortable droptrue"></ul>';
						}
						echo '</div>';
					}
				
					if (count($included) > 0)
					{
						foreach ($included as $row)
						{
							
							echo '<div class="row droptrue cols-'.count($row).'"><a href="#" class="button remove_row">-</a>';
							if (is_array($row))
							{
								foreach ($row as $col)
								{
									echo '<ul class="col sortable droptrue">';
							
									foreach ($col as $field)
									{
										echo display_field_type($field);
									}
										
									echo '</ul>';
								}
							}
							echo '</div>';
								
						}
					}
				}
			?>
			</div>

			<?php if (!empty($included) || !empty($excluded)): ?>
			<div class="buttons">
				<a href="#" class="button add_row">Add Row</a>
			</div>
			<?php endif; ?>

		</div>

		<br style="clear:both">
	</div>


	<?=form_open($form_url, array('id' => 'update_form'));?>
		<div class="form-standard">
			<h2><?= lang('layout_settings') ?></h2>
			<fieldset>
				<div class="field-instruct">
					<label><?= lang('category_description_editor') ?>: </label>
					<small><i><?= lang('category_description_editor_desc') ?></i></small>
				</div>
				<div class="field-control">
					<?= form_dropdown('settings[cat_editor]', $editor_select, $cat_editor); ?>
				</div>
			</fieldset>
			<fieldset>
				<div class="field-instruct">
					<label><?= lang('category_image_max_width') ?>: </label>
					<small><i><?= lang('category_image_max_width_desc') ?></i></small>
				</div>
				<div class="field-control">
					<?= form_input('settings[image_max_width]', $image_max_width, 'style="width:140px"'); ?>
				</div>
			</fieldset>
			<fieldset>
				<div class="field-instruct">
					<label><?= lang('layout_style') ?>: </label>
					<small><i><?= lang('layout_style_desc') ?></i></small>
				</div>
				<div class="field-control">
					<?= form_dropdown('settings[layout_style]', array('' => 'Default', 'spaced' => 'Spaced'), $layout_style); ?>
				</div>
			</fieldset>
		</div>

		<input type="hidden" name="group_id" value="<?= $group_id ?>">
		<input type="hidden" name="layout" value="<?= htmlentities(json_encode($layout)); ?>">

		<fieldset class="form-ctrls ">
			<input type="submit" name="submit" value="Update Layout" class="btn update submit button" data-submit-text="Update Layout" data-work-text="Updating Layout...">
		</fieldset>
	<?=form_close()?>

	</div>
</div>
