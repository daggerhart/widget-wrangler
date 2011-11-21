<?php
/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action( 'widgets_init', 'ww_sidebar_widgets' );
/**
 * Register our widget.
 * 'Widget_Wrangler_Sidebar_Widget' is the widget class used below.
 *
 * @since 0.1
 */
function ww_sidebar_widgets() {
  register_widget( 'Widget_Wrangler_Sidebar_Widget' );
}
/**
 * Widget Wrangler Sidebar Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 *
 * @since 0.1
 */
class Widget_Wrangler_Sidebar_Widget extends WP_Widget {
  /**
   * Widget setup.
   */
  function Widget_Wrangler_Sidebar_Widget()
  {
    // Widget settings. 
    $widget_ops = array( 'classname' => 'widget-wrangler-sidebar', 'description' => __('A single Widget Wrangler Sidebar', 'sidebar') );
    
    // Widget control settings. 
    $control_ops = array( 'id_base' => 'widget-wrangler-sidebar' );
    
    // Create the widget. 
    $this->WP_Widget( 'widget-wrangler-sidebar', __('WW Sidebar', 'widgetwranglersidebar'), $widget_ops, $control_ops );
  }
  /**
   * How to display the widget on the screen.
   */
  function widget( $args, $instance )
  {
    extract( $args );
    ww_dynamic_sidebar($instance['sidebar']);
  }
	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance )
 {
    $instance = $old_instance; 
    $instance['title'] = $new_instance['title'];
    $instance['sidebar'] = $new_instance['sidebar'];
    return $instance;
	}
  /**
   * Displays the widget settings controls on the widget panel.
   * Make use of the get_field_id() and get_field_name() function
   * when creating your form elements. This handles the confusing stuff.
   */
  function form( $instance )
  {
    // Set up some default widget settings. 
    $defaults = array( 'title' => __('WW Sidebar', 'widgetwranglersidebar'), 'sidebar' => '' );
    $instance = wp_parse_args( (array) $instance, $defaults );
    $sidebars = ww_get_all_sidebars();
    ?>
    <?php // Widget Title: Hidden Input ?>
    <input type="hidden" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $sidebars[$instance['sidebar']]; ?>" style="width:100%;" />
    
    <?php // Sidebar: Select Box ?>
    <p>
     <label for="<?php echo $this->get_field_id( 'sidebar' ); ?>"><?php _e('Sidebar:', 'sidebar'); ?></label> 
     <select id="<?php echo $this->get_field_id( 'sidebar' ); ?>" name="<?php echo $this->get_field_name( 'sidebar' ); ?>" class="widefat" style="width:100%;">
      <?php
        foreach($sidebars as $slug => $name){
          ?>
          <option <?php if ($instance['sidebar'] == $slug){ print 'selected="selected"'; }?> value="<?php print $slug; ?>"><?php print $name; ?></option>
          <?php
        }
      ?>
     </select>
    </p>
    <?php
  }
}
