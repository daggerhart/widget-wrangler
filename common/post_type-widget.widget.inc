<?php
/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action( 'widgets_init', 'ww_widget_widgets' );
/**
 * Register our widget.
 * 'Widget_Wrangler_Widget_Widget' is the widget class used below.
 *
 * @since 0.1
 */
function ww_widget_widgets() {
  register_widget( 'WidgetWrangler_Widget_Widget' );
}
/**
 * Widget Wrangler Widget Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 *
 * @since 0.1
 */
class WidgetWrangler_Widget_Widget extends WP_Widget {
  /**
   * Widget setup.
   */
  function WidgetWrangler_Widget_Widget()
  {
    // Widget settings. 
    $widget_ops = array( 'classname' => 'widget-wrangler-widget', 'description' => __('A single Widget Wrangler Widget', 'widget') );
    
    // Widget control settings. 
    $control_ops = array( 'id_base' => 'widget-wrangler-widget' );
    
    // Create the widget. 
    $this->WP_Widget( 'widget-wrangler-widget', __('Widget Wrangler - Widget', 'widgetwranglerwidget'), $widget_ops, $control_ops );
  }
  /**
   * How to display the widget on the screen.
   */
  function widget( $args, $instance )
  {
    extract( $args );
		print ww_theme_single_widget(ww_get_single_widget($widget_post_id));
  }
	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance )
 {
    $instance = $old_instance; 
    $instance['title'] = $new_instance['title'];
    $instance['widget_post_id'] = $new_instance['widget_post_id'];
    return $instance;
	}
  /**
   * Displays the widget settings controls on the widget panel.
   * Make use of the get_field_id() and get_field_name() function
   * when creating your form elements. This handles the confusing stuff.
   */
  function form( $instance )
  {
		// get all widgets
		$widgets = ww_get_all_widgets();
		if (isset($_GET['post']) && is_numeric($_GET['post']) && isset($widgets[$_GET['post']])){
			//$this_widget = $widgets[$_GET['post']];
			// don't want this widget to be selectable in the dropdown
			unset($widgets[$_GET['post']]);
		}
		
    // Set up some default widget settings. 
    $defaults = array( 'title' => __('Widget Wrangler - Widget', 'widgetwranglerwidget'), 'widget_post_id' => '' );
    $instance = wp_parse_args( (array) $instance, $defaults );
		$selected_widget_title = (isset($widgets[$instance['widget_post_id']])) ? $widgets[$instance['widget_post_id']]->post_title: ""; 
		
    ?>
    <?php // Widget Title: Hidden Input ?>
    <input type="hidden" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $selected_widget_title; ?>" style="width:100%;" />
    
    <?php // Widget: Select Box ?>
    <p>
     <label for="<?php echo $this->get_field_id( 'widget_post_id' ); ?>"><?php _e('Widget:', 'widget'); ?></label> 
     <select id="<?php echo $this->get_field_id( 'widget_post_id' ); ?>" name="<?php echo $this->get_field_name( 'widget_post_id' ); ?>" class="widefat" style="width:100%;">
      <?php
				foreach($widgets as $id => $widget){
					?>
					<option <?php if (isset($instance['widget_post_id']) && $instance['widget_post_id'] == $id){ print 'selected="selected"'; }?> value="<?php print $id; ?>"><?php print $widget->post_title; ?></option>
					<?php
				}
      ?>
     </select>
    </p>
    <?php
  }
}
