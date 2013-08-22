WidgetWrangler.ajax = {
	
	init: function() {
		jQuery('select#ww_post_preset').live('change' , WidgetWrangler.ajax.replace_edit_page_widgets);
	},
	
	replace_edit_page_widgets: function() {
	  // show throbber
		//jQuery('#query-preview-controls').removeClass('query-preview-inactive').addClass('query-preview-active');
		
		// prepare post data
		var post_data_form = {
			'action': 'ww_form_ajax',
			'op': 'replace_edit_page_widgets',
			'preset_id': jQuery('select#ww_post_preset').val(),
			'post_id': jQuery('input#ww_ajax_post_id').val(),
		};
		
		// make ajax call
		jQuery.ajax({
			url: WidgetWrangler.data.ajaxURL,
			type: 'POST',
			async: false,
			data: post_data_form,
			//dataType: 'json',
			success: function(data){
				jQuery('#widget-wrangler-form').parent().html(data);
				// disable the wrangler for presets, enable for no preset
				WidgetWrangler.is_disabled = (post_data_form.preset_id) ? true : false;
				// re init
				WidgetWrangler.sortable.init();
			},
			error: function(s,m) {
				
			}
		});
		
		// handle enabled vs disabled 

		
		// hide throbber
		//jQuery('#query-preview-controls').removeClass('query-preview-active').addClass('query-preview-inactive');
	}
};

jQuery(document).ready(function(){
	WidgetWrangler.ajax.init();
});