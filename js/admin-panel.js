

WidgetWrangler.toggle_no_widgets = function(){
	var lists = jQuery("ul.ww-sortable");
	jQuery.each(lists, function(){
		var num_items = jQuery(this).children('.ww-item');
		if(num_items.length > 0)
		{
			jQuery(this).children('.ww-no-widgets').hide();
		}
		else
		{
			jQuery(this).children('.ww-no-widgets').show();
		}
		//console.log(num_items.length);
	});
}

WidgetWrangler.update_widget_weights = function(){
	var lists = jQuery("ul.ww-sortable");
	jQuery.each(lists, function(){
		var this_list = jQuery(this).children(".ww-item");
			jQuery.each(this_list, function(i){
				jQuery(this).children(".ww-widget-weight").val(i+1);// stop working in 3.2:  .attr("disabled","");
			});
	});
}

WidgetWrangler.refresh_all = function() {
	// Auto change sort order when drag and drop
	var sortable_lists = jQuery("ul.ww-sortable");
	jQuery("ul.ww-sortable").sortable({
		connectWith: '.ww-sortable',
		cancel: 'select,input',
		update: function(event,ui){
			var active_widgets = jQuery(this).children(".ww-item");
			var corral_name = jQuery(this).attr("name");
			//console.log(active_widgets);
			 jQuery.each(active_widgets, function(i){
					jQuery(this).children("select").val(corral_name);
					//console.log((i+1)+" - "+jQuery(this).attr("id"));
			});
			WidgetWrangler.toggle_no_widgets();
			WidgetWrangler.update_widget_weights();
		}
	}).disableSelection();
	
	
	var selects = jQuery("#ww-sortable-corrals .nojs select");
	jQuery.each(selects, function(){
		jQuery(this).parent('.ww-item').removeClass('nojs');
		jQuery(this).change(function(){
			var select_val = jQuery(this).val();
			var select_name = jQuery(this).attr("name");
			
			//console.log(select_val);
			//console.log(select_name);
			if ( select_val != 'disabled')
			{
				//console.log(jQuery(this).val());
				//console.log(jQuery(this));
				jQuery(this).parent('.ww-item').clone()
					.addClass('nojs').prependTo("#ww-corral-"+select_val+"-items").removeClass("disabled");
				jQuery(this).parent('.ww-item').remove();
				jQuery("#ww-corral-"+select_val+"-items select[name='"+select_name+"']").val(select_val);
				
				var this_list = jQuery("#ww-corral-"+select_val+"-items").children(".ww-item");
				//console.log(this_list);
			}
			else
			{
				jQuery(this).siblings('.ww-widget-weight').val('').parent('.ww-item').clone().addClass('nojs disabled').appendTo("#ww-disabled-items").children(".ww-widget-weight").attr("disabled","disabled");
				jQuery(this).parent('.ww-item').remove();
				jQuery("#ww-disabled-items select[name='"+select_name+"']").val(select_val);
			}
			WidgetWrangler.update_widget_weights();
			WidgetWrangler.toggle_no_widgets();
			WidgetWrangler.refresh_all();
		});
	});
}

WidgetWrangler.disable = function() {
	console.log('dis');
	var selects = jQuery("#ww-sortable-corrals select, #ww-sortable-corrals input[type='text']");
	jQuery.each(selects, function(i, element){
		jQuery(element).attr('disabled','disabled');
	});
}

jQuery(document).ready(function(){
	// handle disabling from outside of object definition
	if (typeof WidgetWrangler_disable === 'undefined') {
    WidgetWrangler_disable = false;
  }
	
  if (!WidgetWrangler_disable) {
		WidgetWrangler.refresh_all();
	}
	else {
		WidgetWrangler.disable();
	}
	
	// fix some form input issues
	// http://stackoverflow.com/questions/13898027/jquery-ui-sortable-cant-type-in-input-fields-with-cancel-method
	jQuery("#ww-sortable-corrals select, #ww-sortable-corrals input[type='text']").live('click', function(e) {
		jQuery(this).trigger({
			type: 'mousedown',
			which: 3
		});
	});
	
	jQuery("#ww-sortable-corrals select, #ww-sortable-corrals input[type='text']").live('mousedown', function(e) {
		if(e.which == 3){
			jQuery(this).focus();   
		}
	});
	// */

});
