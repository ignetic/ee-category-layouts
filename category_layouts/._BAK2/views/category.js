$( function() {
	
	$('#group_select_form select[name=group_id]').on('change', function() {
		$(this).closest('form').submit();
	});	
	
	
	$('#sortables select').on('change', function() {
		getLayouts();
	});
	

	function getLayouts() {

		var groupID = $('form#update_form input[name=group_id]').val();
		var layouts = $('form#update_form input[name=layout]').val();

		var catFields = {};
		if (layouts) {
			catFields = JSON.parse(layouts);
		}
		
		catLayouts = [];

		$('.drop-target .row').each(function(irow) {
			
			catLayouts[irow] = [];
			
			$cols = $(this).find('.col');
			
			$cols.each(function(icol) {
				
				var itemArray = [];
				
				$(this).find('li').each(function() {
					fieldId = $(this).data('field-id');
					fieldType = $(this).data('field-type');
					fieldElement = $(this).find('select').val();
					
					var data = {};
					
					data.id = fieldId;
					data.type = fieldType;
					if (fieldElement) {
						data.element = fieldElement;
					}
			
					itemArray.push(data);
					
				});
				
				catLayouts[irow][icol] = itemArray;
				
			});
			
			
		});

		$('form#update_form input[name=layout]').val(JSON.stringify(catLayouts));
		
		return catLayouts;
		
	}
  
	function createSortables() {

		$containment = $('#mainContent, body>.wrap');
	
		// Main Items 
		var $sortables = $('#sortables ul.sortable').sortable({
			connectWith: 'ul.sortable',
			items: '> li',
			cursor: 'move',
			cursorAt: { left: 8 },
			tolerance: 'pointer',
			containment: $containment,
			update: function( event, ui ) {
				getLayouts();
			}
		});
		
		
		// Row Items 
		var $sortables = $('#sortables .drop-target').sortable({
			connectWith: '.row',
			cursor: 'move',
			items: '.row',
			axis: 'y',
		})
		
/*
		// Children List Order
		var $sortables = $('#sortables ul.sortable ul.children.sort').sortable({
			connectWith: 'ul.children',
			items: '> li',
			cursor: 'ns-resize',
			axis: 'y',
			update: function( event, ui ) {
				
				// update sorting
				var catSort = [];
				$(this).find('li').each(function(index) {
					var catId = $(this).data('child-id');
					if (catId) {
						catSort[index] = catId;
					}
				});
				
				// Update Order in Database
				if (catSort.length > 0) { 
					
					var updateURL = $('form#update_form input[name=base_url]').val()+'&method=update_order';
				
					$.ajax({
						type: 'POST',
						dataType: 'json',
						url: updateURL,
						data: 'cat_order=' + JSON.stringify(catSort)
					});
				}
			}
		});
*/
	}


	$('#sortables').on('click', '.remove_row', function(e) {
		e.preventDefault();
		
		// Remove 
		$(this).closest('.row').find('.col > li').each(function() {
			$(this).detach().appendTo('.drop-source .sortable');
		});
		
		$(this).closest('.row').remove();
	
		getLayouts();
		createSortables();
	});

	$('#sortables').on('click', '.add_row', function(e) {
		e.preventDefault();
		
		var numCols = parseInt(prompt("Number of columns:", "2"));
		if (numCols > 5) {
			numCols = 5;
		}
		
		if (numCols) {
			var col = '<ul class="col sortable droptrue"></ul>';
			var row = '';

			for (var i = 0; i < numCols; i++) {
				row += col;
			}

			$('#sortables .drop-target').append('<div class="row droptrue cols-'+numCols+'"><a href="#" class="button remove_row">-</a>'+row+'</div>');

			getLayouts();
			createSortables();
		}
	});
	 
	 
	createSortables();

	
});