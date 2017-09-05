<?php

/**
 * Class WW_Widget_PostType
 */
class WW_Widget_PostType {
	// public meta fields
	var $meta_box_fields = array(
		"ww-adv-enabled",
		"ww-parse",
		"ww-wpautop",
		"ww-adv-template",
		"ww-hide-title",
		"ww-hide-from-wrangler",
		"ww-custom-template-suggestion",
		'ww-override-output-html',
		'ww-html-wrapper-element',
		'ww-html-wrapper-id',
		'ww-html-wrapper-classes',
		'ww-html-title-element',
		'ww-html-title-classes',
		'ww-html-content-element',
		'ww-html-content-classes',
		'ww-preview-corral-slug',
		'ww-display-logic-enabled',
		'ww-display-logic',
	);
	var $widget_type;
	var $post_id;
	var $widget_meta = array();

	function __construct($settings) {
	    $this->settings = $settings;
	}

	/**
	 * @param $settings
	 *
	 * @return \WW_Widget_PostType
	 */
	public static function register( $settings ) {
        $plugin = new self($settings);

		add_action('wp_insert_post', array( $plugin, 'wp_insert_post' ), 10, 2);
		add_action('wp_ajax_widget_wrangler_preview', array( $plugin, 'preview_ajax' ) );
		add_action('admin_enqueue_scripts', array( $plugin, 'enqueue') );

		return $plugin;
	}

	/**
	 * Enqueue scripts
	 */
	function enqueue() {
		add_filter('manage_edit-widget_columns', array($this, 'table_headers'));
		add_action('manage_posts_custom_column', array($this, 'table_data'));

		$screen = get_current_screen();

	    if ($screen->id == 'widget') {
		    $this->add_meta_boxes();
		    wp_enqueue_style('ww-admin');
		    wp_enqueue_script('ww-widget-edit');
		    wp_enqueue_script('ww-box-toggle');
        }
	}

	/**
	 * Add metaboxes to the post if it is a widget
	 */
	function add_meta_boxes() {
	    $post = get_post();

		// load some extra data if we are on a WW widget post_type
		if (get_post_type($post) == 'widget')
		{
			$this->post_id = $post->ID;
			$this->widget_meta = get_post_meta($post->ID);

			$widget_type = get_post_meta($post->ID, 'ww-widget-type', true);
			$this->widget_type = ($widget_type) ? $widget_type : 'standard';

			if ($this->widget_type == 'clone') {
				remove_post_type_support( $post->post_type, 'editor' );
				add_meta_box('ww-clone-instance', __('Widget Form', 'widgetwrangler'), array($this, 'meta_box_clone_instance'), 'widget', 'normal', 'high');
			}
			else {
				add_meta_box('ww-options', __('Options', 'widgetwrangler'), array($this, 'meta_box_options'), 'widget', 'normal', 'high');
				add_meta_box('ww-parse', __('Advanced Parsing', 'widgetwrangler'), array($this, 'meta_box_parse'), 'widget', 'normal', 'high');
			}

			if ($this->settings['override_elements_enabled']) {
			    add_meta_box('ww-overrides', __('Override HTML Output', 'widgetwrangler'), array($this, 'meta_box_parse'), 'widget', 'normal', 'high');
			}

			add_meta_box('ww-display-logic', __('Display Logic', 'widgetwrangler'), array($this, 'meta_box_display_logic'), 'widget', 'normal', 'high');
			add_meta_box('ww-templates', __('Templates', 'widgetwrangler'), array($this, 'meta_box_templates'), 'widget', 'normal', 'high');
			add_meta_box('ww-widget-details', __('Details', 'widgetwrangler'), array($this, 'meta_box_details'), 'widget', 'side', 'default');
			add_meta_box('ww-widget-preview', __('Widget Preview', 'widgetwrangler'), array($this, 'meta_box_widget_preview'), 'widget', 'side', 'default');
		}
	}

	/**
	 * Custom columns for the main Widgets management page
	 */
	function table_headers($columns) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __('Widget Title', 'widgetwrangler'),
			'ww_type' => __('Type', 'widgetwrangler'),
			'ww_description' => __('Description', 'widgetwrangler'),
			'ww_rewrite_output' => __('Rewrite Output', 'widgetwrangler'),
			'ww_shortcode' => __('Shortcode', 'widgetwrangler'),
		);

		return $columns;
	}

	/**
	 * Handler for custom columns
	 *
	 * @param $column
	 */
	function table_data($column) {
		global $post;

		switch ($column) {
			case 'ww_type':
				$widget_type = get_post_meta($post->ID, 'ww-widget-type', true);
				print (!empty($widget_type) ? $widget_type : 'standard');
				break;

			case 'ww_description':
				the_excerpt();
				break;

			case 'ww_rewrite_output':
				$rewrite = get_post_meta($post->ID, 'ww-adv-enabled', true);
				print (!empty($rewrite) ? $rewrite : '&nbsp;');
				break;

			case 'ww_shortcode':
				print "[ww_widget  id={$post->ID}]<hr />[ww_widget slug=\"{$post->post_name}\"]";
				break;
		}
	}

	/**
	 * When a post is inserted or updated
	 * - should only be called when saving a widget post type
	 *
	 * @param $post_id
	 * @param null $post
	 */
	function wp_insert_post($post_id, $post = null) {
	    // widgets only
		if (get_post_type($post_id) != 'widget') { return; }

		// data is required
		if (empty($_POST['ww-data'])) { return; }

		//Check if this call results from another event, like the "Quick Edit" option
		// http://wordpress.org/support/topic/quickedit-deletes-code-in-advanced-parsing
		if (isset($_REQUEST['_inline_edit'])) { return; }

		// @todo - ww-wpautop defaults to checked

		// Loop through the public meta fields ($this->meta_box_fields) for $_POST data
		foreach ($this->meta_box_fields as $key){
			$value = @$_POST['ww-data'][$key];

			// If value is a string it should be unique
			if (!is_array($value))
			{
				// custom template suggestion filtering
				if ($key == "ww-custom-template-suggestion"){
					$value = sanitize_key($value);
				}

				// Update meta
				update_post_meta($post_id, $key, trim($value));
			}
			else {
				// If passed along as an array, we should remove all previous data
				delete_post_meta($post_id, $key);

				// Loop through the array adding new values to the post meta as different entries with the same name
				foreach ($value as $entry){
					add_post_meta($post_id, $key, trim($entry));
				}
			}
		}

		// update clone instance
		if ( !empty( $_POST['ww-data']['clone'] ) ) {
			$instance = $this->make_clone_instance($_POST);
			$old_instance = get_post_meta($post_id, 'ww-clone-instance', true);

			// let the widget update itself
			$classname = $_POST['ww-data']['clone']['clone-class'];
			$classname = implode("\\", array_filter( explode( "\\", $classname )));

			if ( class_exists( $classname ) ) {
				$wp_widget = new $classname;
				$instance = $wp_widget->update($instance, $old_instance);
			}

			$instance['ID'] = $post_id;
			if (isset($_POST['ww-data']['clone']['hide_title'])){
				$instance['hide_title'] = $_POST['ww-data']['clone']['hide_title'];
			}
			else if (isset($instance['hide_title'])) {
				unset($instance['hide_title']);
			}

			delete_post_meta($post_id, 'ww-clone-instance');
			add_post_meta($post_id, 'ww-clone-instance', $instance);
		}
	}

	/**
	 * Read the clone instance data from WW post type widget form
	 *
	 * @param $posted
	 *
	 * @return array|bool
	 */
	function make_clone_instance($posted) {
		global $wp_widget_factory;

		if (isset($posted['ww-data']['clone'])) {
			$clone_class = $posted['ww-data']['clone']['clone-class'];
			$clone_class = implode("\\", array_filter( explode( "\\", $clone_class )));
			$option_name = "widget-".$wp_widget_factory->widgets[$clone_class]->control_options['id_base'];
			$instance = array();

			// loop through instance values and create an instance array
			foreach($posted[$option_name] as $i => $settings){
				foreach($settings as $key => $value){
					$instance[$key] = $value;
				}
			}

			return $instance;
		}
		return false;
	}

	/**
     * Get the values of all meta data in a simple array
     *
	 * @return array
	 */
	function get_meta_values() {
		$values = array();
		foreach ($this->meta_box_fields as $i => $key){
			$values[$key] = (isset($this->widget_meta[$key])) ? $this->widget_meta[$key][0] : NULL;
		}

		return $values;
	}

	/**
	 * Details metabox
	 */
	function meta_box_details() {
		?>
        <ul>
            <li><?php _e('Post ID'); ?> <code><?php print $this->post_id;?></code></li>
            <li><?php _e('Shortcode by ID'); ?><br>- <code>[ww_widget id=<?php print $this->post_id; ?>]</code></li>
            <li><?php _e('Shortcode by slug'); ?><br>- <code>[ww_widget slug="<?php print get_post()->post_name; ?>"]</code></li>
        </ul>
        <?php
    }

	/**
	 * Standard options
	 */
	function meta_box_options() {
		$values = $this->get_meta_values();

		$form = new WidgetWranglerForm( array(
			'form_field_prefix' => 'ww-data',
		) );

		print $form->render_field(array(
			'type'  => 'checkbox',
			'name'  => 'ww-wpautop',
			'title' => __( 'Automatically add paragraphs to the widget content.' ),
			'value' => $values['ww-wpautop'],
		));
		print $form->render_field(array(
			'type'  => 'checkbox',
			'name'  => 'ww-hide-title',
			'title' => __( 'Hide this widget title on output.' ),
			'value' => $values['ww-hide-title'],
		));
		print $form->render_field(array(
			'type'  => 'checkbox',
			'name'  => 'ww-hide-from-wrangler',
			'title' => __( 'Hide this widget from the drag and drop Wrangler.' ),
			'help' => __('This is useful for widgets that are only meant to be used as shortcodes.'),
			'value' => $values['ww-hide-from-wrangler'],
		));
	}

	/**
	 * Advanced parsing area
	 */
	function meta_box_parse() {
		$values = $this->get_meta_values();

		$form = new WidgetWranglerForm( array(
			'form_field_prefix' => 'ww-data',
		) );

		print $form->render_field( array(
			'type'  => 'checkbox',
			'name'  => 'ww-adv-enabled',
			'title' => __( 'Enable advanced parsing' ),
			'value' => $values['ww-adv-enabled'],
		) );
		print $form->render_field( array(
			'type'  => 'checkbox',
			'name'  => 'ww-adv-template',
			'title' => __( 'Template the advanced parsing area' ),
			'value' => $values['ww-adv-template'],
		) );
		print $form->render_field( array(
			'type'  => 'textarea',
			'name'  => 'ww-parse',
			'title' => __( '' ),
			'value' => $values['ww-parse'],
			'class' => array('code'),
			'attributes' => array(
				'rows' => substr_count($values['ww-parse'], "\n") + 3,
			)
		) );
		?>

        <hr>
        <a class="toggle-next-content"><?php _e("Help & Hints"); ?></a>
        <div class="togglable-content">
            <h4><?php _e("In the Advanced Parsing area you can", 'widgetwrangler'); ?>:</h4>
            <ul class="list">
                <li><?php print sprintf(__("Use of PHP tags ( %s and %s are required )", 'widgetwrangler'), '<code>&lt;?php</code>', '<code>?&gt;</code>'); ?></li>
                <li><?php print sprintf(__("Use %s or %s to insert the widget's title", 'widgetwrangler'), '<code>{{title}}</code>', '<code>$widget->post_title</code>'); ?></li>
                <li><?php print sprintf(__("Use %s or %s to insert the widget's content", 'widgetwrangler'), '<code>{{content}}</code>', '<code>$widget->post_content</code>'); ?></li>
                <li><?php print sprintf(__("Access the %s object for more widget data (see provided template for examples)", 'widgetwrangler'), '<code>$widget</code>'); ?></li>
                <li><?php print sprintf(__("Access the %s object for data concerning the page being displayed (see provided template for examples)", 'widgetwrangler'), '<code>$post</code>'); ?></li>
            </ul>
            <h4><?php _e("Templating Advanced Parsed Widgets", 'widgetwrangler'); ?></h4>
            <ul class="list">
                <li><?php _e("To template an advanced parsed widget you must return an associative array with a title and content string.", 'widgetwrangler'); ?></li>
                <li><?php _e("Example", 'widgetwrangler'); ?>: <code>&lt;?php return array("title" => "The Widget's Title", "content" => "The Widget's Content"); ?&gt;</code></li>
            </ul>
        </div>
		<?php
	}

	/**
	 * Display Logic
	 */
	function meta_box_display_logic()
	{
		$values = $this->get_meta_values();
		$form = new WidgetWranglerForm( array(
			'form_field_prefix' => 'ww-data',
		) );

		print $form->render_field( array(
			'type'  => 'checkbox',
			'name'  => 'ww-display-logic-enabled',
			'title' => __( 'Enable display logic' ),
			'value' => $values['ww-display-logic-enabled'],
		) );
		print $form->render_field( array(
			'type'  => 'textarea',
			'name'  => 'ww-display-logic',
			'description' => __( 'Site-wide raw PHP logic for displaying this widget' ),
			'value' => $values['ww-display-logic'],
			'class' => array('code'),
			'attributes' => array(
				'rows' => substr_count($values['ww-display-logic'], "\n") + 3,
			)
		) );

		?>
        <hr>
        <a class="toggle-next-content"><?php _e("Help & Hints"); ?></a>
        <div class="togglable-content">
            <h4><?php _e("Display Logic", 'widgetwrangler'); ?></h4>
            <ul class="list">
                <li><?php print sprintf(__("Do NOT use %s and %s tags.  This field is for raw PHP.", 'widgetwrangler'), '<code>&lt;?php</code>', '<code>?&gt;</code>'); ?></li>
                <li><?php _e("Evaluate boolean true or false with php. Defaults to TRUE.", 'widgetwrangler'); ?></li>
                <li><?php print sprintf(__("For simple logic, execute your conditions directly.  (For example, use Wordpress Conditional Tags such as %s or %s).", 'widgetwrangler'), '<code>is_search()</code>', '<code>is_404()</code>'); ?></li>
                <li><?php _e("For complex logic, return TRUE or FALSE as needed.", 'widgetwrangler'); ?></li>
            </ul>
        </div>
		<?php
	}

	/**
	 * Clone Instance
	 */
	function meta_box_clone_instance() {
		$wp_widget_classname = get_post_meta($this->post_id,'ww-clone-classname', true);
		$wp_widget_instance = get_post_meta($this->post_id, 'ww-clone-instance', true);
		$ww_hide_from_wrangler = get_post_meta( $this->post_id, 'ww-hide-from-wrangler', true);

		if ($wp_widget_classname) {
			// create instance form
			ob_start();
			$wp_widget = new $wp_widget_classname;
			$wp_widget->form($wp_widget_instance);
			$instance_form = ob_get_clean();

			$form = new WidgetWranglerForm( array(
				'form_field_prefix' => 'ww-data',
			) );

			print $form->render_field(array(
				'type'  => 'checkbox',
				'name'  => 'hide_title',
				'name_prefix' => '[clone]',
				'title' => __( 'Hide the widget title on display' ),
				'value' => $wp_widget_instance['hide_title'],
			));
			print $form->render_field(array(
				'type'  => 'checkbox',
				'name'  => 'ww-hide-from-wrangler',
				'title' => __( 'Hide the widget from the drag and drop Wrangler.' ),
				'help' => __('This is useful for widgets that are only meant to be used as shortcodes.'),
				'value' => $ww_hide_from_wrangler,
			));
			print $form->render_field(array(
				'type'  => 'hidden',
				'name'  => 'clone-class',
				'name_prefix' => '[clone]',
				'value' => $wp_widget_classname,
			));
			print $form->render_field(array(
				'type'  => 'hidden',
				'name'  => 'clone-instance',
				'name_prefix' => '[clone]',
				'value' => 'Placeholder',
			));

			print $instance_form;

			?>
            <hr>
            <a class="toggle-next-content"><?php _e("Instance details"); ?></a>
            <div class="togglable-content">
                <h4>WP_Widget $instance</h4>
                <pre class="code"><?php print htmlentities(print_r($wp_widget_instance,1)); ?></pre>
            </div>
			<?php
		}
	}

	/**
	 * Overrides meta box.
	 */
	function meta_box_overrides() {
		?>
        <p class="description"><?php _e("Alter the html output of a templated widget.  Doesn't apply to advanced parsing unless templating is selected.", 'widgetwrangler'); ?></p>
		<?php
		$values = $this->get_meta_values();
		$form = new WidgetWranglerForm( array(
			'form_field_prefix' => 'ww-data',
		) );

		$options = $this->settings['override_elements'];

		print $form->render_field(array(
			'type'  => 'checkbox',
			'name'  => 'ww-override-output-html',
			'title' => __( 'Override the HTML output of this widget with the values below.  This will override theme compatibility.' ),
			'value' => $values['ww-override-output-html'],
		));
		print $form->render_field(array(
			'type'  => 'select',
			'name'  => 'ww-wrapper-element',
			'title' => __( 'Wrapper Element' ),
			'value' => $values['ww-wrapper-element'],
			'options' => $this->ensure_in_array($options, $values['ww-wrapper-element']),
		));
		print $form->render_field(array(
			'type'  => 'text',
			'name'  => 'ww-html-wrapper-id',
			'title' => __( 'Wrapper ID' ),
			'value' => $values['ww-html-wrapper-id'],
		));
		print $form->render_field(array(
			'type'  => 'text',
			'name'  => 'ww-html-wrapper-classes',
			'title' => __( 'Wrapper Classes' ),
			'help' => __('Separate multiple classes with spaces.'),
			'value' => $values['ww-html-wrapper-classes'],
		));
		print $form->render_field(array(
			'type'  => 'select',
			'name'  => 'ww-html-title-element',
			'title' => __( 'Title Element' ),
			'value' => $values['ww-html-title-element'],
			'options' => $this->ensure_in_array($options, $values['ww-html-title-element']),
		));
		print $form->render_field(array(
			'type'  => 'text',
			'name'  => 'ww-html-title-classes',
			'title' => __( 'Title Classes' ),
			'help' => __('Separate multiple classes with spaces.'),
			'value' => $values['ww-html-title-classes'],
		));
		print $form->render_field(array(
			'type'  => 'select',
			'name'  => 'ww-html-content-element',
			'title' => __( 'Content Element' ),
			'value' => $values['ww-html-content-element'],
			'options' => $this->ensure_in_array($options, $values['ww-html-content-element']),
		));
		print $form->render_field(array(
			'type'  => 'text',
			'name'  => 'ww-html-content-classes',
			'title' => __( 'Content Classes' ),
			'help' => __('Separate multiple classes with spaces.'),
			'value' => $values['ww-html-content-classes'],
		));
	}

	/**
     * Make sure a value is in an array
     *
	 * @param $array
	 * @param $value
	 *
	 * @return array
	 */
	function ensure_in_array($array, $value) {
		$value = trim($value);

		if (!in_array($value, $array) && !empty($value)) {
			$array[] = $value;
		}

		return $array;
	}

	/**
	 * Templates meta box.
	 */
	function meta_box_templates() {
		$settings = new WidgetWranglerSettings();
		$display = new Widget_Wrangler_Display($settings->values);

		if ($widget = WidgetWranglerWidgets::get($this->post_id)){

			$preview_corral_slug = get_post_meta($this->post_id, 'ww-preview-corral-slug', TRUE);
			if ($preview_corral_slug){
				$display->doing_corral = TRUE;
				$display->doing_corral_slug = $preview_corral_slug;
			}

			$args = array(
				'widget' => $widget, // needed in final template
				'widget_id' => $widget->ID,
				'post_name' => $widget->post_name,
				'widget_type' => $widget->widget_type,
				//'corral_id' => (isset($widget->widget_data) && isset($widget->widget_data['preview-corral-id'])) ? $widget->widget_data['preview-corral-id'] : 0,
				'tw_action'  => 'find_only',
			);

			if ($preview_corral_slug){
				$args['corral_slug'] = $preview_corral_slug;
			}

			$a = theme('ww_widget', $args);

			$suggestions = "";
			if (isset($a['suggestions'])){
				foreach($a['suggestions'] as $i => $suggestion) {
					// we can't detect corral here
					//$suggestion = str_replace("corral_0", "corral_[corral_id]", $suggestion);
					$class = (isset($a['found_suggestion']) && $suggestion == $a['found_suggestion']) ? "item found" : "item";
					$suggestions.= "<li class='$class'>".$suggestion."</li>";
				}
			}
		}

		$result_suggestion = '{your_custom_suggestion}';
        if (!empty($widget->custom_template_suggestion)) {
	        $result_suggestion = $widget->custom_template_suggestion;
        }

		$form = new WidgetWranglerForm( array(
			'form_field_prefix' => 'ww-data',
		) );
		print $form->render_field(array(
			'type'  => 'text',
			'name'  => 'ww-custom-template-suggestion',
			'title' => __( 'Custom template suggestion' ),
			'description' => __('Define a custom template name for this widget.'),
			'help' => sprintf( __('The resulting filename will be: %s'), "<code>widget-{$result_suggestion}.php</code>"),
			'value' => $widget->custom_template_suggestion,
		));

		?>
        <hr>
        <a class="toggle-next-content"><?php _e("Help & Suggestions"); ?></a>
        <div class="togglable-content">
            <ul class="list">
                <li><?php _e("Lowercase alphanumeric characters, dashes and underscores are allowed.", 'widgetwrangler'); ?></li>
                <li><?php _e("Uppercase characters will be converted to lowercase.", 'widgetwrangler'); ?></li>
                <li><?php _e("If defined, the custom suggestion will take precedence.", 'widgetwrangler'); ?></li>
            </ul>

            <h4><?php _e("Template Suggestions", 'widgetwrangler'); ?></h4>
            <p class="description"><?php _e("Corral specific templates will not be detected here unless you set the 'Preview Corral Context' in the preview pane.", 'widgetwrangler'); ?></p>
            <ul class="template-suggestions"><?php print $suggestions; ?></ul>
            <?php
            if (isset($a['found_path']) && $a['found_path'])
            { ?>
                <h4><?php _e("Found template location", 'widgetwrangler'); ?></h4>
                <div class='code'><?php print str_replace(ABSPATH, "/", $a['found_path']); ?></div>
                <?php
            }
            ?>
        </div>
        <?php
	}

	/**
	 * Widget preview box
	 */
	function meta_box_widget_preview(){
		if ($this->post_id) {
			$settings = $this->settings;
			$preview_corral_slug = get_post_meta($this->post_id, 'ww-preview-corral-slug', TRUE);

			$display = new Widget_Wrangler_Display($settings);
			$display->doing_corral = TRUE;
			$display->doing_corral_slug = $preview_corral_slug;

			$corrals = WidgetWranglerCorrals::all();

			$form = new WidgetWranglerForm( array(
				'form_field_prefix' => 'ww-data',
			) );

			print $form->render_field(array(
				'type'  => 'select',
				'name'  => 'ww-preview-corral-slug',
				'title' => __( 'Preview Corral Context' ),
				'description' => __('This setting only affects the preview on this page, and helps provide accurate template suggestions.'),
				'help' => __('This preview does not include your theme stylesheet, nor corral or sidebar styling.'),
				'value' => $preview_corral_slug,
				'options' => !empty($corrals) ? $corrals : array('' => '- No Corrals -'),
			));
			?>
            <a id="preview-widget" class="button" data-postid="<?php print $this->post_id; ?>">Preview</a>
            <div id="preview-target"><!-- placeholder --></div>
			<?php
		}
	}

	/**
	 * Widget preview meta box ajax action
	 */
	function preview_ajax() {
		// queries in widgets can cause problems, even when executed correctly

		$settings = $this->settings;
		$display = new Widget_Wrangler_Display($settings);
		$widget = WidgetWranglerWidgets::get( intval( $_POST['widget_post_id'] ) );

		if (!$widget) {
			die(__('Widget not found'));
		}

		// buffer all of this in case of php errors
		ob_start();
		$values = $_POST['form_state'];

		$widget->in_preview = TRUE;
		$preview_corral_slug = $values['ww-preview-corral-slug'];

		// set some data so the preview is as accurate as possible
		if ($preview_corral_slug){
			global $wp_registered_sidebars;
			$corral_sidebar_map = $display->corrals_to_wpsidebars_map();

			// get the sidebar widget args if context is set
			if (isset($corral_sidebar_map[$preview_corral_slug]) && isset($wp_registered_sidebars[$corral_sidebar_map[$preview_corral_slug]])){
				$this_sidebar = $wp_registered_sidebars[$corral_sidebar_map[$preview_corral_slug]];

				// get the id of the corral selected if it exists within the sidebar
				$sidebars_widgets = wp_get_sidebars_widgets();
				if (isset($sidebars_widgets[$this_sidebar['id']])){
					$corral_widgets = $sidebars_widgets[$this_sidebar['id']];
				}
				$widget->wp_widget_args = $this_sidebar;
				$widget->wp_widget_args['before_widget'] = sprintf($this_sidebar['before_widget'], $corral_widgets[0], 'widget-wrangler-widget-classname');
				// replace id and classes as done during output
				$widget = $display->_replace_wp_widget_args($widget);
			}

			$display->doing_corral = TRUE;
			$display->doing_corral_slug = $preview_corral_slug;
		}
		print $display->theme_single_widget($widget);

		$preview = ob_get_clean();
		$preview_balance = balanceTags($preview, true);

		?>
        <hr />
		<?php print $preview_balance; ?>

		<?php if ($preview != $preview_balance) { ?>
            <div class="ww-danger">
                <p>
					<?php _e("Your widget may contain some broken or malformed html.", 'widgetwrangler'); ?>
					<?php _e("Wordpress balanced the tags in this preview in an attempt to prevent the page from breaking, but it will not do so on normal widget display.", 'widgetwrangler'); ?>
                </p>
            </div>
		<?php } ?>
        <hr />
        <a class="toggle-next-content"><?php _e("View Output HTML"); ?></a>
        <pre class="togglable-content code"><?php print htmlentities($this->clean_html($preview)); ?></pre>
		<?php
		exit;
	}

	/**
	 * Tidy up html as output
	 *
	 * @param $html
	 *
	 * @return string
	 */
	function clean_html($html) {
		$html = str_replace('>', ">\n", $html);
		$html_array = explode("\n", $html);
		$clean_array = array();

		foreach($html_array as $row) {
			$row = trim($row);

			if (!empty($row) ) {
				$clean_array[] = $row;
			}
		}

		$total = count($clean_array);
		$half = floor($total / 2 );

		for( $i=0; $i < $total; $i++ ) {
			if ($i < $half ) {
				$clean_array[$i] = str_repeat('  ', $i) .$clean_array[$i];
			}
			else {
				$clean_array[$i] = str_repeat('  ', $total - $i - 1) .$clean_array[$i];
			}
		}

		return implode("\n", $clean_array);
	}
}
