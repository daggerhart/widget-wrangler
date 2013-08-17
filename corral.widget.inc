<?php
/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action( 'widgets_init', 'ww_corral_widgets' );
/**
 * Register our widget.
 * 'Widget_Wrangler_Corral_Widget' is the widget class used below.
 *
 * @since 0.1
 */
function ww_corral_widgets() {
  register_widget( 'Widget_Wrangler_Corral_Widget' );
}
/**
 * Widget Wrangler Corral Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 *
 * @since 0.1
 */
class Widget_Wrangler_Corral_Widget extends WP_Widget {
  /**
   * Widget setup.
   */
  function Widget_Wrangler_Corral_Widget()
  {
    // Widget settings. 
    $widget_ops = array( 'classname' => 'widget-wrangler-corral', 'description' => __('A single Widget Wrangler Corral', 'corral') );
    
    // Widget control settings. 
    $control_ops = array( 'id_base' => 'widget-wrangler-corral' );
    
    // Create the widget. 
    $this->WP_Widget( 'widget-wrangler-corral', __('Widget Wrangler - Corral', 'widgetwranglercorral'), $widget_ops, $control_ops );
  }
  /**
   * How to display the widget on the screen.
   */
  function widget( $args, $instance )
  {
    extract( $args );
    ww_dynamic_corral($instance['corral']);
  }
	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance )
 {
    $instance = $old_instance; 
    $instance['title'] = $new_instance['title'];
    $instance['corral'] = $new_instance['corral'];
    return $instance;
	}
  /**
   * Displays the widget settings controls on the widget panel.
   * Make use of the get_field_id() and get_field_name() function
   * when creating your form elements. This handles the confusing stuff.
   */
  function form( $instance )
  {
		$exists = 	isset($corrals[$instance['corral']]) ? true : false;
		//$wp_widget_title = ($exists) ? __('Widget Wrangler - Corral', 'widgetwranglercorral') : __('WW Corral - '.$title, 'widgetwranglercorral');
		$input_title =  ($exists) ? $corrals[$instance['corral']] : "!Error!";
    // Set up some default widget settings. 
    $defaults = array( 'title' => __('Widget Wrangler - Corral', 'widgetwranglercorral'), 'corral' => '' );
    $instance = wp_parse_args( (array) $instance, $defaults );
    $corrals = ww_get_all_corrals();
    ?>
    <?php // Widget Title: Hidden Input ?>
    <input type="hidden" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $input_title; ?>" style="width:100%;" />
    
    <?php // Corral: Select Box ?>
    <p>
			<?php
				if (!$exists)
				{ ?>
					<p class="ww-error"><strong>!Error!</strong>: The previously selected corral does not exist. Please choose another and save.</p>
					<?php
				}
			?>
			<label for="<?php echo $this->get_field_id( 'corral' ); ?>"><strong><?php _e('Corral:', 'corral'); ?></strong></label> 
			<select id="<?php echo $this->get_field_id( 'corral' ); ?>" name="<?php echo $this->get_field_name( 'corral' ); ?>" class="widefat" style="width:100%;">
			 <?php
				 foreach($corrals as $slug => $name){
					 ?>
					 <option <?php if ($instance['corral'] == $slug){ print 'selected="selected"'; }?> value="<?php print $slug; ?>"><?php print $name; ?></option>
					 <?php
				 }
			 ?>
			</select>
    </p>
    <?php
  }
}
