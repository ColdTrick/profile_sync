define(function(require){
	
	var $ = require('jquery');
	var spinner = require('elgg/spinner');
	
	$(document).on('click', 'li.elgg-menu-item-profile-sync-run', function(){
		spinner.start();
	});
});
