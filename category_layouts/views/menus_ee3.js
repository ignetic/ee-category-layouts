
// Category Layouts - menus_ee3.js

if (/\/cp\/channels\/cat[^\/]/.test(window.location.href)
	|| /\/cp\/addons\/settings\/category_layouts/.test(window.location.href)
)
{
	$("a[href*=\"cp/channels/cat/edit\"]").each(function (){
		var group_id = $(this).closest('tr').find('td:first').text();
		if (group_id) {
			$(this).closest('tr').find('.toolbar-wrap .toolbar').each(function() {
				$(this).append('<li><a href="<?= $layouts_url ?>&group_id='+group_id+'">layout</a></li>')
			});
		}
	});
}

