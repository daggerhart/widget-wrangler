jQuery(document).ready(function(){          

	/*
	 * Corrals
	 */
	jQuery('.ww-delete-corral').submit(function(){
			var ask = confirm('Whoa-there partner! Are you sure you want to delete this corral?');			
			return (ask) ? true : false;
	});
	
	jQuery('#ww-corrals-sort-list').sortable({
		update: function(event,ui){
			var all_corrals = jQuery('#ww-corrals-sort-list').children('.ww-corral-sort-item');
			
			jQuery.each(all_corrals, function(i){
					var weight_input = jQuery(this).children('.ww-corral-weight');
					var count = i+1;
					jQuery(weight_input).attr("name", "weight["+count+"]");
			});
		}
	}).disableSelection();

	/*
	 * Presets
	 */    
	jQuery('#preset-action input[name=action-delete]').click(function(){
			var ask = confirm('Whoa-there partner! Are you sure you want to delete this Preset? Doing so will set all pages using this preset back to the Default preset.');
			return (ask) ? true : false;
	});

	/*
	 * Settings
	 */
	if (jQuery('#ww-admin-tabs').length && jQuery.ui) {
		jQuery('#ww-admin-tabs').tabs();
	}
});