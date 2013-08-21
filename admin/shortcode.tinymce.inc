<?php

class WidgetWrangler_ShortcodeTinymce{
	function addWidgetListbox(){
		// Don't bother doing this stuff if the current user lacks permissions
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
			return;
	 
	   // Add only in Rich Editor mode
	    if ( get_user_option('rich_editing') == 'true') {
	      add_filter('mce_external_plugins', array($this, 'registerWidgetPlugin'));
	      //you can use the filters mce_buttons_2, mce_buttons_3 and mce_buttons_4 
	      //to add your button to other toolbars of your tinymce
	      add_filter('mce_buttons', array($this, 'registerWidgetListbox'));
	    }
	}
	
	function registerWidgetListbox($buttons){
		array_push($buttons, "separator", "WW");
		return $buttons;
	}
	
	function registerWidgetPlugin($plugin_array){
		$plugin_array['WW'] = WW_PLUGIN_URL.'/admin/js/shortcode.tinymce.js';
		return $plugin_array;
	}
}
