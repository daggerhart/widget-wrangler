<?php

/*
 * Widget Wrangler custom_post_type class for widget post type
 *
 *  all of the admin only methods have been moved to the Widget_Wranger_Admin class.
 */
class Widget_Wrangler {
  var $data = array();
  var $type;
  var $post_id;
	
  /*
   * Constructor, build the new post type
   */
  function Widget_Wrangler()
  {
		// Editing specific widget
		if (isset($_GET['post'])){
			$pid = $_GET['post'];
		}
		else if (isset($_POST['post_ID'])){
			$pid = $_POST['post_ID'];
		}
		// get the type
		if (isset($pid)){
			$post_type = get_post_type($pid);
		}
    
		// load some extra data if we are on a WW widget post_type
		if (isset($post_type) && $post_type == 'widget')
		{
			// this widget has been saved before, we now $pid exists
			$this->post_id = $pid;
			
			// get widget data
			if ($widget = ww_get_widget_data($this->post_id)) {
				$this->type = $widget->type;
				$this->data = $widget->data;
			}
			// handle editing page for all widgets
			$supports = array(
				'title',
				'excerpt'
			);
			
			// Clones do not need an editor
			if($this->type == 'standard'){
				$supports[] = 'editor';
			}
    }
    // this is a new widget
    else
    {
      $supports = array(
        'title',
        'excerpt',
        'editor',
        /*,'custom-fields'*/
      );
    }
    
		$settings = ww_get_settings();
    // allow for custom capability type
    $capability_type = ($settings['capabilities'] == "advanced" && isset($settings['advanced'])) ? $settings['advanced'] : "post";
    $labels = array(
      'name' => _x('Widget Wrangler', 'post type general name'),
      'all_items' => __('All Widgets'),
      'singular_name' => _x('Widget', 'post type singular name'),
      'add_new' => _x('Add New Widget', 'widget'),
      'add_new_item' => __('Add New Widget'),
      'edit_item' => __('Edit Widget'),
      'new_item' => __('New Widget'),
      'view_item' => __('View Widget'),
      'search_items' => __('Search Widgets'),
      'menu_icon' => WW_PLUGIN_DIR.'/icon-wrangler.png',
      'not_found' =>  __('No widgets found'),
      'not_found_in_trash' => __('No widgets found in Trash'), 
      'parent_item_colon' => '',
    );
    // Register custom post types
    register_post_type('widget', array(
      'labels' =>$labels,
      'public' => true,
      'exclude_from_search' => true, // @TODO: setting ?
      'show_in_menu' => true,
      'show_ui' => true, // UI in admin panel
      '_builtin' => false, // It's a custom post type, not built in
      '_edit_link' => 'post.php?post=%d',
      'capability_type' => $capability_type,
      'hierarchical' => false,
      'rewrite' => array("slug" => "widget"), // Permalinks
      'query_var' => "widget", // This goes to the WP_Query schema
      'supports' => $supports,
      'menu_icon' => WW_PLUGIN_URL.'/images/wrangler_icon.png'
    ));
  }
}
// end widget class
 