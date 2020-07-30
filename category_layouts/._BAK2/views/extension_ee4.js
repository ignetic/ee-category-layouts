
// Category Layouts - extension_ee4.js

if (/\/cp\/categories\/(edit|create)\//.test(window.location.href))
{

	var parts = window.location.href.replace(/\/$/, '').split('/');
	
	/*if (parts.length >= 8) {
		group_id = parts[7];
	}*/
	
	var layouts = {};
	var json_layouts = '<?= $layouts_js ?>';
	var columns = '<?= $columns ?>';
	var group_id = '<?= $group_id ?>';

	
	var IS_JSON = true;
	try {
		//var layouts = $.parseJSON(json_layouts);
		var layouts = JSON.parse(json_layouts);;
	} catch(err) {
		IS_JSON = false;
	}
	
	if (IS_JSON === true && !$.isEmptyObject(layouts)) {
		
		$('.form-standard > form').addClass('category-layouts-form');
		$('.form-standard > form h2').after('<div class="category-layouts cols-'+columns+'"></div>');
		
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
							else if (fieldType == "select") {
								$thisField = $('.field-control div[data-input-value='+fieldId+']').closest('fieldset');
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
	$('form.category-layouts-form textarea[name=cat_description]').css('height', '100px').addClass('cat-editor').closest('.fieldset');
	
	// Category Lists
	$(window).load(function() {
		
		$('form .category-layouts .cat-field-category_select').each(function(i) {
			var $catInput = $(this).find('input, select');
			var catVal = $catInput.val();

			var options = '';
			$('form.category-layouts-form .fields-select li').each(function(i) {	
				var pad = ''
				var indents = $(this).parents('li').length;
				for (var i=0; i < indents; i++) {
					pad += "&ndash;&ndash;";
				}
				pad += "&nbsp;";
				var value = $('> label input', this).val();
				if (value == '0') {
					value = '';
				}
				var name = $('> label', this).text();
				options += '<option value="'+value+'">'+pad+name+'</option>'
			});


			var $catSelect = $('<select>')
				.attr('name', $catInput.attr('name'))
				.attr('id', $catInput.attr('id'))
				.html(options)
				.val(catVal);

			$(this).find('.field-control').html($catSelect);
			
		});
	});		

}
