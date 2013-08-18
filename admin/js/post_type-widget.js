/*
 * Simple show/hide toggle for field options
 */
function ww_field_options_toggle(element) {
  if (jQuery(element).is(':checked')){
    jQuery(element).parent().parent().siblings('.ww-options-group-content').show();
  }
  else {
    jQuery(element).parent().parent().siblings('.ww-options-group-content').hide();
  }
}

jQuery(document).ready(function(){
  
	/*
	 * option groups
	 */
  jQuery('.ww-options-group-title input[type=checkbox]').click(function(){
    ww_field_options_toggle(jQuery(this));
  });
  
  // toggle selected fields
  jQuery('.ww-options-hidden').each(function(index,element){
    if(jQuery(element).parent().find('.ww-options-group-title input[type=checkbox]').is(':checked')){
      jQuery(element).removeClass('ww-options-hidden');
    }
  });
  
  // ui tabs for display logic
  jQuery('#logic-tabs').tabs();

	/*
	 * enabled advanced
	 */
  // open and close widget menu
  jQuery('.widget-action').click(function(){
    jQuery(this).parent('div').parent('div').next('.widget-inside').slideToggle();
  });
	
  // handle advanced parsing template description/option
  if(jQuery('#ww-adv-parse-toggle').is(':checked')){
    jQuery('#ww-advanced-template').show();
  } else {
    jQuery('#ww-advanced-template').hide();
  }
  jQuery('#ww-adv-parse-toggle').click(function(){
    jQuery('#ww-advanced-template').toggle();
  });
	
	/*
	 * advanced help
	 */
	jQuery('.ww-advanced-help-description').hide();
	jQuery('.ww-advanced-help-title').click(function(){
		jQuery(this).siblings('.ww-advanced-help-description').slideToggle();
	});
});