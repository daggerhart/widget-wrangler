jQuery(document).ready(function(){          
	jQuery('.ww-corral-item .widget-title-action').click(
		function(){
			jQuery(this).parent().siblings('.widget-inside').slideToggle();
	});
	
	jQuery('.ww-delete-corral').submit(
		function(){
			var slug = jQuery(this).children('input[name=ww-delete-slug]').val();
			
			var ask = confirm('Whoa-there partner! Are you sure you want to delete this corral?');
			if (ask) {
				return true;
			}
			else
			{
				return false;
			}
		});
	
	jQuery('#ww-corrals-sort').sortable({
		update: function(event,ui){
			var all_corrals = jQuery('#ww-corrals-sort').children('.ww-corral-sort-item');
			
			jQuery.each(all_corrals, function(i){
					var weight_input = jQuery(this).children('.ww-corral-weight');
					var count = i+1;
					jQuery(weight_input).attr("name", "weight["+count+"]");
			});
		}
		
	}).disableSelection();
});