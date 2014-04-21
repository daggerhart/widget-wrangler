<?php

/*
 *
 */
class WW_Widget_PostType {
  // public meta fields
  var $meta_box_fields = array(
        "ww-adv-enabled",
        "ww-parse",
        "ww-wpautop",
        "ww-adv-template",
        "ww-hide-title",
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
  var $capability_type;
  var $widget_type;
  var $post_id;
  var $widget_meta = array();
  
  //
  function __construct(){
    global $widget_wrangler;
    $this->ww = $widget_wrangler;
    
    add_action( 'init', array( $this, 'wp_init' ) );
  }
  
  /*
   * hook_init
   */
  function wp_init(){
    $settings = $this->ww->settings;
    $capability_type = ($settings['capabilities'] == "advanced" && isset($settings['advanced_capability'])) ? $settings['advanced_capability'] : "post";
    
    $supports = array(
      'title' => 'title',
      'excerpt' => 'excerpt',
      'editor' => 'editor',
      'custom-fields' => 'custom-fields',
    );
      
    // custom post type labels
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
      'not_found' =>  __('No widgets found'),
      'not_found_in_trash' => __('No widgets found in Trash'), 
      'parent_item_colon' => '',
    );

    
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
      // Allow for insert post hook
      add_action("wp_insert_post", array(&$this, "wp_insert_post"), 10, 2);
      
			// this widget has been saved before, we know $pid exists
			$this->post_id = $pid;
      $this->widget_meta = get_post_meta($pid);
      $widget_type = get_post_meta($pid, 'ww-widget-type', true);
			$this->widget_type = ($widget_type) ? $widget_type : "standard";

			// Clones do not need an editor
			if($this->widget_type == 'clone'){
				unset($supports['editor']);
			}
    }
    
    // Register the post_type
    register_post_type('widget', array(
      'labels' => $labels,
      'public' => true,
			//'publicly_queryable' => true,?
      'exclude_from_search' => (isset($settings['exclude_from_search']) && $settings['exclude_from_search'] == 0) ? false : true, 
      'show_in_menu' => true,
      'show_ui' => true, // UI in admin panel
      '_builtin' => false, // It's a custom post type, not built in
      '_edit_link' => 'post.php?post=%d',
      'capability_type' => $capability_type,
      'hierarchical' => false,
      'rewrite' => array("slug" => 'widget'), // Permalinks
      'query_var' => 'widget', // This goes to the WP_Query schema
      'supports' => $supports,
      'menu_icon' => WW_PLUGIN_URL.'/admin/images/lasso-menu.png'
    ));

    add_filter("manage_edit-widget_columns", array(&$this, "edit_columns"));
    add_action("manage_posts_custom_column", array(&$this, "custom_columns"));

    // Admin interface init
    add_action("admin_init", array(&$this, "wp_admin_init"));
  }
  
  function _editing_widget_js(){
    
  }
  
  /*
   * Add meta box to widget posts
   */
  function wp_admin_init()
  {
    add_action('admin_enqueue_scripts', array( $this->ww->admin, '_editing_widget_js' ));
    
    // Clone Instance
    if ($this->widget_type == "clone") {
      add_meta_box("ww-clone-instance", "Widget Form", array(&$this, "meta_box_clone_instance"), "widget", "normal", "high");
    }
    else {
      // Custom meta boxes for the edit widget screen
      add_meta_box("ww-parse", "Options", array(&$this, "meta_box_parse"), "widget", "normal", "high");
    }
    add_meta_box("ww-adv-help", "Advanced Help", array(&$this, "meta_box_advanced_help"), "widget", "normal", "high");
    add_meta_box("ww-widget-preview", "Widget Preview", array(&$this, "meta_box_widget_preview"), "widget", "side", "default");
  }
  
  /*
   * Custom columns for the main Widgets management page
   */
  function edit_columns($columns)
  {
    $columns = array(
      "cb" => "<input type=\"checkbox\" />",
      "title" => "Widget Title",
      "ww_type" => "Type",
      "ww_description" => "Description",
      "ww_rewrite_output" => "Rewrite Output",
			"ww_shortcode" => "Shortcode",
    );

    return $columns;
  }
  /*
   * Handler for custom columns
   */
  function custom_columns($column)
  {
    global $post;
    
    switch ($column){
      case "ww_type":
        $widget_type = get_post_meta($post->ID, 'ww-widget-type', true);
        print ($widget_type) ? $widget_type : "standard";
        break;
      case "ww_description":
        the_excerpt();
        break;
      case "ww_rewrite_output":
        $rewrite = get_post_meta($post->ID, 'ww-adv-enabled', true);
        print ($rewrite) ? $rewrite : "&nbsp;";
        break;
			case "ww_shortcode":
				print "[ww_widget  id=".$post->ID."]<hr />[ww_widget slug=\"".$post->post_name."\"]";
				break;
    }
  }

  /*
   * When a post is inserted or updated
   * - should only be called when saving a widget post type
   */
  function wp_insert_post($post_id, $post = null)
  {
    //Check if this call results from another event, like the "Quick Edit" option
    // http://wordpress.org/support/topic/quickedit-deletes-code-in-advanced-parsing
		if (isset($_REQUEST['_inline_edit'])) { return; }
    
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
    if ($this->widget_type == "clone"){
      $instance = $this->make_clone_instance($_POST);
      $old_instance = get_post_meta($post_id, 'ww-clone-instance', true);
      
      // let the widget update itself
      $classname = $_POST['ww-data']['clone']['clone-class'];
      if (class_exists($classname)) {
        $wp_widget = new $classname;
        $instance = $wp_widget->update($instance, $old_instance);
      }

      $instance['ID'] = $post_id;
      if (isset($_POST['ww-data']['clone']['hide_title'])){
        $instance['hide_title'] = $_POST['ww-data']['clone']['hide_title'];
      } else if (isset($instance['hide_title'])) {
        unset($instance['hide_title']);
      }
      
      delete_post_meta($post_id, 'ww-clone-instance');
      add_post_meta($post_id, 'ww-clone-instance', $instance);
    }
  }

  /*
   * Read the clone instance data from WW post type widget form
   */
  function make_clone_instance($posted){
    global $wp_widget_factory;
    if (isset($posted['ww-data']['clone'])) {
      $clone_class = $posted['ww-data']['clone']['clone-class'];
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
	
  /*
	 * Clone Instance
	 */ 
  function meta_box_clone_instance()
  {
    // get widget factory and post data
    global $wp_widget_factory;

    $wp_widget_classname = get_post_meta($this->post_id,'ww-clone-classname', true);
    $wp_widget_instance = get_post_meta($this->post_id, 'ww-clone-instance', true);
    
    if($wp_widget_classname)
    {
      // create instance form
      ob_start();
        $wp_widget = new $wp_widget_classname;
        $wp_widget->form($wp_widget_instance);
      $instance_form = ob_get_clean();
        
      $hide_title_checked = (isset($wp_widget_instance['hide_title'])) ? 'checked="checked"' : '';
      ?>
        <label>
          <input type="checkbox" name="ww-data[clone][hide_title]" <?php print $hide_title_checked; ?> /> - Hide the Widget's title on display
        </label>
        <div class="ww-clone-form">
          <?php print $instance_form; ?>
        </div>
				<input type="hidden" name="ww-data[clone][clone-class]" value="<?php print $wp_widget_classname; ?>" />
        <input type="hidden" name="ww-data[clone][clone-instance]" value="Placeholder" />
      <?php
    }
  }
  
  /*
   * Widgetv preview box
   */ 
  function meta_box_widget_preview(){
    if ($this->post_id)
    {
      // buffer all of this in case of php errors
      ob_start();
        $widget = $this->ww->get_single_widget($this->post_id);
        $widget->in_preview = TRUE;
        $preview_corral_slug = get_post_meta($this->post_id, 'ww-preview-corral-slug', TRUE);
        
        // set some data so the preview is as accurate as possible
        if ($preview_corral_slug){
          global $wp_registered_sidebars;
          $corral_sidebar_map = $this->ww->display->corrals_to_wpsidebars_map();
          
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
            $widget = $this->ww->display->_replace_wp_widget_args($widget);
          }
          
          $widget->in_corral = TRUE;
          $widget->corral_slug = $preview_corral_slug;
        }
        print $this->ww->display->theme_single_widget($widget);
      $preview = ob_get_clean();
      
			$preview_balance = balanceTags($preview, true);
      ?>
        <div id="ww-preview">
          <label><strong>Preview Corral Context:</strong></label>
          <p><em>This setting only affects the preview on this page, and helps provide accurate template suggestions.</em></p>
          <select id="ww-preview-corral" name="ww-data[ww-preview-corral-slug]" class="widefat" style="width:100%;">
            <option value='0'>- No Corral -</option>
              <?php
                foreach($this->ww->corrals as $corral_slug => $corral)
                {
                  $selected = (isset($widget->corral_slug) && $corral_slug == $widget->corral_slug) ? 'selected="selected"': "";
                  ?>
                  <option <?php print $selected; ?> value="<?php print $corral_slug; ?>"><?php print $corral; ?></option>
                  <?php
                }
              ?>
          </select>
          <p><em>This preview does not include your theme's CSS stylesheet, nor corral or sidebar styling.</em></p>
        </div>
        <hr />
        <?php	print $preview_balance; ?>
          
				<?php if ($preview != $preview_balance) { ?>
					<div style="border-top: 1px solid #bbb; margin-top: 12px; padding-top: 8px;">
						<span style="color: red; font-style: italic;">Your widget may contain some broken or malformed html.</span> Wordpress balanced the tags in this preview in an attempt to prevent the page from breaking, but it will not do so on normal widget display.
					</div>
				<?php } ?>
        <hr />
        <div id="ww-preview-html-toggle">View Output HTML</div><pre id="ww-preview-html-content"><?php
          print htmlentities($preview); ?></pre>
      <?php
    }
  }

  // helper function for override html element select 
  function _make_override_element_select($fields, $key)
  { ?>
      <select name="ww-data[<?php print $key; ?>]" class="ww-element-select">
        <option value="">- Use Default -</option>
        <option value="_none_">- None -</option>
        <?php
          $trimmed_value = trim($fields[$key]['value']);
          
          // make sure that if an element is later changed, we can still retain the current value
          if (!empty($trimmed_value) &&
              !in_array($fields[$key]['value'], $this->ww->settings['override_elements']))
          {
            $this->ww->settings['override_elements'][] = $fields[$key]['value'];
          }
          
          foreach ($this->ww->settings['override_elements'] as $element){
            $selected = ($fields[$key]['value'] == $element) ? 'selected="selected"': '';
            ?>
            <option value="<?php print $element; ?>" <?php print $selected; ?>><?php print strtoupper($element); ?></option>
            <?php
          }
        ?>
      </select>  
    <?php
  }
  
  // Admin post meta contents
  function meta_box_parse()
  {
    $fields = array();
    foreach ($this->meta_box_fields as $i => $key){
      // get values
      $fields[$key]['value'] = (isset($this->widget_meta[$key])) ? $this->widget_meta[$key][0] : NULL;
      // for checkboxes
      $fields[$key]['checked'] = ($fields[$key]['value']) ? 'checked="checked"' : '';
    }
    
    // ww-wpautop defaults to checked
    if (!isset($_GET['action']) && (isset($_GET['post_type']) && $_GET['post_type'] == 'widget')){
      $fields['ww-wpautop']['checked'] = 'checked="checked"';
    }
    
    ?><div id="ww-template">
        <div class="ww-widget-postid">Post ID<br/><span><?php print $this->post_id;?></span></div>
        <p>
          <label><input type="checkbox" name="ww-data[ww-wpautop]" <?php print $fields['ww-wpautop']['checked']; ?> /> Automatically add Paragraphs to this Widget's Content</label>
        </p>
        <p>
          <label><input type="checkbox" name="ww-data[ww-hide-title]" <?php print $fields['ww-hide-title']['checked']; ?> /> Hide this widget's title on output.</label>
        </p>
        <hr />
        <div>
          <h4>Advanced Parsing</h4>
          <div id="ww-advanced-field">
            <p>
              <label><input id="ww-adv-parse-toggle" type="checkbox" name="ww-data[ww-adv-enabled]" <?php print $fields['ww-adv-enabled']['checked']; ?> /> Enable Advanced Parsing</label>
            </p>
            <div id="ww-adv-parse-content">
              <p id="ww-advanced-template">
                <label><input id="ww-adv-template-toggle" type="checkbox" name="ww-data[ww-adv-template]" <?php print $fields['ww-adv-template']['checked']; ?> /> Template the Advanced Parsing Area</label> <em>(Do not use with Cloned Widgets.  Details below)</em>
              </p>
              <div>
                <textarea name="ww-data[ww-parse]" cols="40" rows="16" style="width: 100%;"><?php print htmlentities($fields['ww-parse']['value']); ?></textarea>
              </div>
            </div>
          </div>
        </div>
        
        <div>
          <hr />
          <h4>Display Logic</h4>
            <p>
              <label><input id="ww-display-logic-toggle" type="checkbox" name="ww-data[ww-display-logic-enabled]" <?php print $fields['ww-display-logic-enabled']['checked']; ?> /> Enable Display Logic</label>
            </p>
            <div id="ww-display-logic-content">
              <p class="description">Site-wide raw PHP logic for displaying this widget</p>
              <div>
                <textarea name="ww-data[ww-display-logic]" cols="40" rows="5" style="width: 100%;"><?php print htmlentities($fields['ww-display-logic']['value']); ?></textarea>
              </div>
            </div>
        </div>
        
        <?php if ($this->ww->_check_license()) { ?>
          <hr />
          <h4>Override HTML Output</h4>
          <p class="description">Alter the html output of a templated widget.  Doesn't apply to advanced parsing unless templating is selected.</p>
          <p>
            <label><input type="checkbox" id="ww-override-html-toggle" name="ww-data[ww-override-output-html]" value="1" <?php print $fields['ww-override-output-html']['checked']; ?> /> Override the HTML output of this widget with the values below.  This will take precendence over theme compatibility.</label>
          </p>
          <div id="ww-override-html-content" class="ww-override-html">
            <div>
              <label>Wrapper Element</label>
              <p><?php $this->_make_override_element_select($fields, 'ww-html-wrapper-element'); ?></p>
            </div>
            <div>
              <label>Wrapper ID</label>
              <p><input type="text" size="30" name="ww-data[ww-html-wrapper-id]" value="<?php print $fields['ww-html-wrapper-id']['value']; ?>"/></p>
            </div>
            <div>
              <label>Wrapper Classes</label>
              <p class="description">Separate multiple classes with spaces.</p>
              <p><input type="text" size="30" name="ww-data[ww-html-wrapper-classes]" value="<?php print $fields['ww-html-wrapper-classes']['value']; ?>"/></p>
            </div>
            <div>
              <label>Title Element</label>
              <p><?php $this->_make_override_element_select($fields, 'ww-html-title-element'); ?></p>
            </div>
            <div>
              <label>Title Classes</label>
              <p class="description">Separate multiple classes with spaces.</p>
              <p><input type="text" size="30" name="ww-data[ww-html-title-classes]" value="<?php print $fields['ww-html-title-classes']['value']; ?>"/></p>
            </div>
            <div>
              <label>Content Element</label>
              <p><?php $this->_make_override_element_select($fields, 'ww-html-content-element'); ?></p>
            </div>
            <div>
              <label>Content Classes</label>
              <p class="description">Separate multiple classes with spaces.</p>
              <p><input type="text" size="30" name="ww-data[ww-html-content-classes]" value="<?php print $fields['ww-html-content-classes']['value']; ?>"/></p>
            </div>
          </div>
        <?php } ?>
      </div>
    <?php
  }
	
	/*
	 * Advanced Help
	 */
	function meta_box_advanced_help()
	{
		if ($widget = $this->ww->get_single_widget($this->post_id)){
      
			$preview_corral_slug = get_post_meta($this->post_id, 'ww-preview-corral-slug', TRUE);
      if ($preview_corral_slug){
        $widget->in_corral = TRUE;
        $widget->corral_slug = $preview_corral_slug;
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
          $class = (isset($a['found_suggestion']) && $suggestion == $a['found_suggestion']) ? "ww-template-suggestion found" : "ww-template-suggestion";
          $suggestions.= "<li class='$class'>".$suggestion."</li>";
        }
      }
    }
			?>
      <div class="adv-parse-description">
        <div id="ww-advanced-help">
          <div class="ww-advanced-help-description adv-parse-description">
            <?php
              //  only show adv parsing help on standard
              if ($this->widget_type != "clone"){ ?>
                <h4>In the Advanced Parsing area you can:</h4>
                <ul>
                  <li>Use PHP tags ( &lt;?php and ?&gt; are required )</li>
                  <li>Use {{title}} or $widget->post_title to insert the widget's title</li>
                  <li>Use {{content}} or $widget->post_content to insert the widget's content</li>
                  <li>Access the $widget object for more widget data (see provided template for examples)</li>
                  <li>Access the $post object for data concerning the page being displayed (see provided template for examples)</li>
                </ul>
                <h4>Templating Advanced Parsed Widgets</h4>
                <ul>
                  <li>To template an advanced parsed widget you must return an associative array with a title and content string.</li>
                  <li>Example: <code>&lt;?php return array("title" => "The Widget's Title", "content" => "The Widget's Content"); ?&gt;</code></li>
                </ul>
                <?php
              }
            ?>
              <h4>Display Logic</h4>
              <ul>
                <li>Do NOT use &lt;?php and ?&gt; tags.  This field is for raw PHP.</li>
                <li>Evaluate boolean true or false with php. (Defaults to <em>true</em>).</li>
                <li>For simple logic, execute your conditions directly.  (For example, use Wordpress Conditional Tags such as <code>is_search()</code> or <code>is_404()</code>).</li>
                <li>For complex logic, <em>return</em> TRUE or FALSE as needed.</li>
              </ul>
            <?php
              if (isset($suggestions)) { ?>
              
                <?php if ($this->ww->_check_license()){ ?>
                  <h4>Custom template suggestion</h4>
                  <ul class="ww-custom-template-suggestion">
                    <li>Define a custom template name for this widget. </li>
                    <li><label>widget-<input type="text" maxlength="64" size="32" name="ww-data[ww-custom-template-suggestion]" value="<?php print $widget->custom_template_suggestion; ?>" />.php</label></li>
                    <li>Lowercase alphanumeric characters, dashes and underscores are allowed.</li>
                    <li>Uppercase characters will be converted to lowercase.</li>
                    <li>If defined, the custom suggestion will take precedence.</li>
                  </ul>
                <?php } ?>
                
                <h4>Template Suggestions</h4>
                <p class="description">Corral specific templates will not be detected here unless you set the "Preview Corral Context" in the preview pane.</p>
                <ul><?php print $suggestions; ?></ul>
              <?php
                if (isset($a['found_path']) && $a['found_path'])
                { ?>
                    <h4>Found template location</h4>
                    <div class='ww-found-template-location'><?php print str_replace(ABSPATH, "/", $a['found_path']); ?></div>
                  <?php
                }
                
                // provide some clone instance debugging
                if ($this->widget_type == "clone")
                {
                  $clone_instance = unserialize($this->widget_meta['ww-clone-instance'][0]);
                  ?>
                  <div>
                    <h4>WP_Widget $instance</h4>
                    <div><pre style="font-size: 0.9em;"><?php print htmlentities(print_r($clone_instance,1)); ?></pre></div>
                  </div>
                  <?php
                }
              }
            ?>
          </div>
        </div>
      </div>
		<?php
	}    
}