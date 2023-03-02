
// Category Layouts - menus_ee4.js

if (/\/cp\/categories\/group/.test(window.location.href) 
	|| /\/cp\/categories\/(edit|create)\//.test(window.location.href)
	|| /\/cp\/addons\/settings\/category_layouts/.test(window.location.href)
)
{
	if ($('.box.sidebar .layout-set').length == 0) {
		$('.box.sidebar .folder-list').find('li[data-content_id], div[data-content_id]').each(function (){
			var group_id = $(this).data('content_id');
			var attrs = {title: 'Layouts', href: '<?= $layouts_url ?>&group_id='+group_id};
			if (group_id) {
				var $layout = $(this).find('.toolbar .edit').clone(true);
				if ($layout.is('a')) {
					$layout.attr('class', 'layout-set button button--default').attr(attrs);
				} else {
					$layout.attr('class', 'layout-set').find('a').attr(attrs);
				}
				$(this).find('.toolbar .edit').after($layout);
			}
		});
		$('head').append('<style>.sidebar .folder-list .act .layout-set a:before {content:"\\f247"}}</style>');
	}
}

