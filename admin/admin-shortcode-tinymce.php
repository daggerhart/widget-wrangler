<?php

// hook this addon in
add_filter( 'Widget_Wrangler_Admin_Addons', 'ww_shortcode_tinymce_admin_addon' );

//
function ww_shortcode_tinymce_admin_addon($addons){
  $addons['Shortcode_Tinymce'] = new WW_Shortcode_Tinymce_Admin();
  return $addons;
}


class WW_Shortcode_Tinymce_Admin  {
  
  function wp_admin_init(){
    // shortcode inserter
    if (!empty($this->ww->settings['shortcode_tinymce']) &&
        $this->ww->admin->_is_editing_enabled_post_type() &&
        $this->ww->_check_license())
    {
      add_action('admin_head', array($this, 'wp_admin_head'));   
    }   
  }
  
	function wp_admin_head(){
		// Don't bother doing this stuff if the current user lacks permissions
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
			return;
	 
	   // Add only in Rich Editor mode
	    if ( get_user_option('rich_editing') == 'true') {
        // TODO:  disabled because of tinymce update to 4.x.  Rewrite button
        
	      //add_filter('mce_external_plugins', array($this, 'mce_external_plugins_widgets'));
	      //you can use the filters mce_buttons_2, mce_buttons_3 and mce_buttons_4 
	      //to add your button to other toolbars of your tinymce
	      //add_filter('mce_buttons', array($this, 'mce_buttons_widgets_listbox'));
	    }
	}
	
	function mce_buttons_widgets_listbox($buttons){
		array_push($buttons, "separator", "WW");
		return $buttons;
	}
	
	function mce_external_plugins_widgets($plugin_array){
		$plugin_array['WW'] = WW_PLUGIN_URL.'/admin/js/shortcode-tinymce.js';
		return $plugin_array;
	}
}
