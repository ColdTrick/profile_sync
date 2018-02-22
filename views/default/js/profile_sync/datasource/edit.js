define(function(require) {
	
	var $ = require('jquery');
	var lightbox = require('elgg/lightbox');
	
	var switch_datasource = function() {
		
		var $form = $(this).closest('form');
		// hide all datasource sections
		$form.find('.profile-sync-datasource-type').hide();
		
		var datasource_type = $(this).val();
		if (datasource_type !== '') {
			// show selected datasource section
			$form.find('.profile-sync-datasource-type-' + datasource_type).show();
		}
		
		// fix required fields
		$form.find('[required]').prop('disabled', true);
		$form.find('[required]:visible').prop('disabled', false);
		
		lightbox.resize();
	};
	
	$(document).on('change', '#profile-sync-edit-datasource-type', switch_datasource);
});
