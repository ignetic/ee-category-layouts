
// Category Layouts - menus_ee4.js

if (/\/cp\/categories\/group/.test(window.location.href) 
	|| /\/cp\/categories\/(edit|create)\//.test(window.location.href)
	|| /\/cp\/addons\/settings\/category_layouts/.test(window.location.href)
)
{
	if ($('.box.sidebar .layout-set').length == 0) {
		$('.box.sidebar .folder-list li[data-content_id]').each(function (){
			var group_id = $(this).data('content_id');
			if (group_id) {
				var $layout = $(this).find('.toolbar li.edit').clone(true);
				$layout.attr('class', 'layout-set').find('a').attr({title: 'Layouts', href: '<?= $layouts_url ?>&group_id='+group_id});
				$(this).find('.toolbar li.edit').after($layout);
			}
		});
		$('body').append('<style>.sidebar .folder-list li.act li.layout-set a:before {content:"\\f247"}}</style>');
	}
}

