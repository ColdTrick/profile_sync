define(function(require){
	
	var $ = require('jquery');
	var lightbox = require('elgg/lightbox');
	
	var check_unique_checkboxes = function() {
		
		if (!$(this).is(':checked')) {
			// unchecked, do nothing
			return;
		}
		
		$('.profile-sync-edit-sync-unique-checkbox:checked').not(this).prop('checked', false).change();
	};
	
	var toggle_field_config = function() {
		
		if ($(this).is(':checked')) {
			$('.profile-sync-edit-sync-fields').hide();
		} else {
			$('.profile-sync-edit-sync-fields').show();
		}
		
		lightbox.resize();
	};
	
	var add_field_config = function() {
		var $clone = $('#profile-sync-field-config-template').clone();
		$clone.removeAttr('id').removeClass('hidden');
		$clone.insertBefore('#profile-sync-field-config-template');
		
		lightbox.resize();
	};
	
	$(document).on('change', '.profile-sync-edit-sync-unique-checkbox', check_unique_checkboxes);
	$(document).on('change', '#profile-sync-edit-sync-ban-user', toggle_field_config);
	$(document).on('change', '#profile-sync-edit-sync-unban-user', toggle_field_config);
	$(document).on('click', '#profile-sync-edit-sync-add-field', add_field_config);
});
