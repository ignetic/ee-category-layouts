
// Category Layouts - extension_ee3.js

if (/\/cp\/channels\/cat\/(edit-cat|create-cat)\//.test(window.location.href))
{	

	var parts = window.location.href.replace(/\/$/, '').split('/');
	
	var group_id = parts.pop();
	if (parts.length == 9) {
		group_id = parts.pop();
	}

	var layouts = {};
	var json_layouts = '<?= $layouts_js ?>';
	var columns = '<?= $columns ?>';
	
	var IS_JSON = true;
	try {
		//var layouts = $.parseJSON(json_layouts);
		var layouts = JSON.parse(json_layouts);;
	} catch(err) {
		IS_JSON = false;
	}
	
	if (IS_JSON === true && !$.isEmptyObject(layouts)) {
		
		$('form.settings').closest('.box').addClass('category-layouts-form');
		$('form.settings h2').after('<div class="category-layouts cols-'+columns+'"></div>');

				$.each( layouts, function( irow, row ) {

					var $thisRow = $('<div class="cat-row"></div>').appendTo(".category-layouts");
					
					$.each( row, function( icol, col ) {
						
						var col_item = '';
						
						if (col.length > 0) {
							col_item = '<div class="cat-col-item"></div>';
						}

						var $thisCol = $('<div class="cat-col cols-'+row.length+'">'+col_item+'</div>').appendTo($thisRow);
						
						$.each( col, function( icol, field ) {
							
							var fieldId = "field_id_"+field.id;
							var fieldType = field.type;
							var fieldStyle = '';
							var $thisField = null;

							if (fieldType == "text") {
								$thisField = $("input[name="+fieldId+"]").closest('fieldset');
							}
							else {
								$thisField = $(fieldType+"[name="+fieldId+"]").closest('fieldset');
							}
							
							if ($thisField) {
								var $movedField = $thisField.detach().appendTo($thisCol.find(".cat-col-item"));
								if (field.element) {
									$movedField.addClass('cat-field-'+field.element);
									if (fieldType == "text") {
										$movedField.find('input[type=text]').attr('type', field.element);
									}
								}
							}
							
						});
					});
				});
	}

	
	// Rich text editor
	$('form textarea[name=cat_description]').css('height', '100px').addClass('cat-editor').closest('.col').removeClass('w-8').addClass('w-16');
	
	
	// Category Lists
	$('form .category-layouts .cat-field-category_select').each(function(i) {
		var $catInput = $(this).find('input, select');
		var catVal = $catInput.val();

		var $catSelect = $('<select>')
			.attr('name', $catInput.attr('name'))
			.attr('id', $catInput.attr('id'))
			.html($('select[name=parent_id]').html())
			.val(catVal);

		$catInput.after($catSelect).remove();
		
		$(this).html($(this).html().replace('[cat_list]', ''));
	});

}

