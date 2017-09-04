<?php
// hook this addon in
add_filter( 'Widget_Wrangler_Admin_Addons', 'ww_clone_admin_addon', 10, 2 );

//
function ww_clone_admin_addon($addons, $settings){
  $addons['Clone'] = WW_Clone_Admin::register($settings);
  return $addons;
}

/**
 * Class WW_Clone_Admin
 */
class WW_Clone_Admin extends WidgetWranglerAdminPage {

	/**
	 * @see WidgetWranglerAdminPage::title()
	 */
    function title() {
        return __('Copy WordPress Widgets to Widget Wrangler');
    }

	/**
	 * @see WidgetWranglerAdminPage::menuTitle()
	 */
    function menuTitle() {
	    return __('Copy WP Widgets');
    }

	/**
	 * @see WidgetWranglerAdminPage::slug()
	 */
	function slug() {
        return 'clone';
    }

	/**
	 * @see WidgetWranglerAdminPage::description()
	 */
    function description() {
        return array(
            __("Here you can copy (instantiate) an existing WordPress widget into Widget Wrangler.", 'widgetwrangler'),
            __("Click on the title of the widget you would like to copy, fill in the widget's form according to your needs and click 'Create'. This will create an instance of the chosen widget in Widget Wrangler.", 'widgetwrangler')
        );
    }

	/**
	 * @see WidgetWranglerAdminPage::actions()
	 */
    function actions() {
        return array(
            'insert' => array( $this, 'actionInsert' ),
        );
    }

	/**
	 * @see WidgetWranglerAdminPage::enqueue()
	 */
	function enqueue() {
		if ( $this->onPage() ){
			wp_enqueue_style('ww-admin');
			wp_enqueue_script('ww-box-toggle');
		}
	}

	/**
	 * Display widgets available for cloning.
	 *
	 * @see \WidgetWranglerAdminPage::page()
	 */
	function page() {
		global $wp_widget_factory;
		$total_widgets = count($wp_widget_factory->widgets);
		$half = round($total_widgets/2);
		$i = 0;
		?>
        <ul class='ww-column'>
		<?php
		foreach ($wp_widget_factory->widgets as $classname => $widget)
		{
			// break into 2 columns
			if ($i == $half)
			{
				?>
                </ul><ul class='ww-column'>
				<?php
			}

			$posted_array_key = "widget-".$widget->id_base;

			ob_start();
			$wp_widget = new $classname;
			$wp_widget->form(array());
			$new_class_form = ob_get_clean();
			?>
            <li class="ww-box ww-box-toggle">
                <h3><?php print $widget->name; ?></h3>
                <div class='ww-box-toggle-content'>
                    <form action='edit.php?post_type=widget&page=clone&ww_action=insert&noheader=true' method='post'>
                        <input type='hidden' name='ww-classname' value='<?php print $classname; ?>' />
                        <input type='hidden' name='ww-keyname' value='<?php print $posted_array_key; ?>' />
                        <?php print $new_class_form; ?>
                        <input class='button button-primary button-large' type='submit' value='Create' />
                    </form>
                </div>
            </li>
			<?php
			$i++;
		}
		?>
        </ul>
		<?php
    }

	/**
	 * Inserts a cloned WP widget as a WW widget
     *
     * @return array
	 */
	function actionInsert()
	{
		global $wpdb,$wp_widget_factory;
		$posted = $_POST;

		//Start our outputs
		$this_class_name = '';
		$instance = array();

		if( isset( $posted[ $posted['ww-keyname'] ] ) ) {
			// Sanitize namespace characters that are improperly encoded from input
			$this_class_name = implode("\\", array_filter( explode( "\\", $posted['ww-classname'] )));
			foreach($posted[$posted['ww-keyname']] as $i => $settings){
				foreach($settings as $key => $value){
					$instance[$key] = $value;
				}
			}
		}

		$user = wp_get_current_user();

		$wp_widget = new $this_class_name;
		$wp_widget_name = $wp_widget_factory->widgets[$this_class_name]->name;
		$instance = $wp_widget->update($instance, array());

		// prep new widget info for saving
		$new_widget = array();
		$new_widget['post_author']    = $user->ID;
		$new_widget['post_title']     = ($instance['title']) ? $instance['title'] : "Clone of ".$this_class_name;
		$new_widget['post_excerpt']   = __('Cloned from', 'widgetwrangler') .' '. $this_class_name;
		$new_widget['comment_status'] = 'closed';
		$new_widget['ping_status']    = 'closed';
		$new_widget['post_status']    = 'draft';
		$new_widget['post_type']      = 'widget';
		// Herb contributed fix for problem cloning
		$new_widget['post_content']   = '';
		$new_widget['to_ping']        = '';
		$new_widget['pinged']         = '';
		$new_widget['post_content_filtered'] = '';

		// insert new widget into db
		// insert new widget into db
		$new_post_id = wp_insert_post($new_widget);
		$instance['ID'] = $new_post_id;
		$instance['hide_title'] = '';

		// post as meta values
		add_post_meta($new_post_id,'ww-widget-type', 'clone');
		add_post_meta($new_post_id,'ww-clone-classname', \wp_slash($this_class_name));
		add_post_meta($new_post_id,'ww-clone-instance', $instance);

		return $this->result(
			__(sprintf('New copy of %s created', $wp_widget_name)),
			get_bloginfo('wpurl')."/wp-admin/post.php?post={$new_post_id}&action=edit"
		);
	}

}
