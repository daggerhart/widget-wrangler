<?php

/*
 * Widget Wrangler custom_post_type class for widget post type
 */
class Widget_Wrangler_Admin extends Widget_Wrangler {

  /*
   * Constructor, build the new post type
   */
  function Widget_Wrangler_Admin(){
		parent::__construct();
    add_filter("manage_edit-widget_columns", array(&$this, "edit_columns"));
    add_action("manage_posts_custom_column", array(&$this, "custom_columns"));
   
    // Admin interface init
    add_action("admin_init", array(&$this, "admin_init"));
    //add_action("template_redirect", array(&$this, 'template_redirect'));
    
    // Insert post hook
    add_action("wp_insert_post", array(&$this, "wp_insert_post"), 10, 2);
		
		// delet_post
		add_action('delete_post', array(&$this, "delete_post"), 10, 2);
  }
  
	/*
   * Custom columns for the main Widgets management page
   */  
  function edit_columns($columns){
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
    $widget = ww_get_single_widget($post->ID);
    
    switch ($column){
      case "ww_type":
        print isset($widget->widget_type) ? $widget->widget_type : "";
        break;
      case "ww_description":
        the_excerpt();
        break;
      case "ww_rewrite_output":
        print isset($widget->widget_data['rewrite-output']) ? $widget->widget_data['rewrite-output']: "off";
        break;
			case "ww_shortcode":
				print "<code>[ww_widget  id=".$widget->ID."]</code>";
				break;
    }
  }

  /*
   * Add meta box to widget posts
   */
  // Custom meta boxes for the edit widget screen
  function admin_init() 
  {
		// add meta boxes
    if (isset($_GET['post'])){
      $post_id = $_GET['post'];
    }
    else if (isset($_POST['post_ID'])){
      $post_id = $_POST['post_ID'];
    }
		
		if ((isset($post_id) && get_post_type($post_id) == "widget") || (isset($_GET['post_type']) && $_GET['post_type'] == "widget"))
		{
			add_action( 'admin_enqueue_scripts', 'ww_post_type_widget_js');
	
			// get widget data, default to empty object
			$widget = isset($post_id) ? ww_get_single_widget($post_id) : new stdClass();
			
			if(isset($widget->widget_type) && $widget->widget_type == "clone") {
				// Clone Instance
				add_meta_box("ww-clone-instance", "Clone Instance", array(&$this, "meta_clone_instance"), "widget", "normal", "high");  
				// templating help
				add_meta_box("ww-parse", "Advanced", array(&$this, "meta_advanced_help"), "widget", "normal", "high");
			}
			else if (!isset($widget->widget_type) || $widget->widget_type == 'standard') {
				// Rewrite output box
				add_meta_box("ww-parse", "Advanced", array(&$this, "meta_parse"), "widget", "normal", "high");
			}
			
			// Widget type
			add_meta_box("ww-type", "Widget Details", array(&$this, "meta_widget_details"), "widget", "side", "high");
	
			// Widget Preview
			add_meta_box("ww-widget-preview", "Widget Preview", array(&$this, "meta_widget_preview"), "widget", "side", "default");
			
			// Widget Display Logic
			add_meta_box("ww-display-logic", "Display Logic", array(&$this, "meta_display_logic"), "widget", "normal", "default");
		}
  }
	
  /*
   * When a post is inserted or updated
   */ 
  function wp_insert_post($post_id, $post = null)
  {
		// don't track revisions independently
		if ( wp_is_post_revision( $post_id ) ) {
			return $post_id;
		}
		
		// only on our post_type
    if ($post->post_type == "widget")
    {
      global $wpdb;
			$table = $wpdb->prefix."ww_widget_data";
      $type = (isset($_POST['ww-type'])) ? $_POST['ww-type'] : "standard";
			
      // problems with php and html adding slashes
      if(isset($_POST['ww-data']['standard']['custom-output'])){
        $_POST['ww-data']['standard']['custom-output'] = trim(stripcslashes($_POST['ww-data']['standard']['custom-output']));
      }
      if(isset($_POST['ww-data']['logic']['php'])){
        $_POST['ww-data']['logic']['php'] = trim(stripcslashes($_POST['ww-data']['logic']['php']));
      }
      
      // clones
      if($type == 'clone' && $_POST['ww-data']['clone']['clone-class']){
        $instance = ww_make_clone_instance($_POST);
        $_POST['ww-data']['clone']['clone-instance'] = serialize($instance);
      }
      
      // prep the data and logic for save
      $widget_data = array();
      if (isset($_POST['ww-data'])) {
				$widget_data = $_POST['ww-data'][$type];
        $widget_data['logic'] = $_POST['ww-data']['logic'];
      }
      // enabled/disabled
			$widget_data['logic']['enabled'] = (isset($widget_data['logic']['enabled'])) ? 1 : 0;
      // preview corral id
			$widget_data['preview-corral-id'] = (isset($_POST['ww-data']['preview-corral-id'])) ? $_POST['ww-data']['preview-corral-id'] : 0;
			
			// unset the php value if empty
      if(isset($widget_data['logic']) && isset($widget_data['logic']['php']) && $widget_data['logic']['php'] == ''){
        unset($widget_data['logic']['php']);
      }
      $data = serialize($widget_data);

      // see if this post has data
      $exists = $wpdb->get_results("SELECT type FROM ".$table." WHERE post_id = ".$post_id);
      
      // update if exists
      if(count($exists) > 0){
        $wpdb->update($table, array('type' => $type, 'data' => $data), array('post_id' => $post_id));
      }
      // insert if not
      else {
        $wpdb->insert($table, array('post_id' => $post_id, 'type' => $type, 'data' => $data));
      }
    }
  }

	/*
	 * clean up widget data on delete
	 */
  function delete_post($post_id)
  {
    if (is_numeric($post_id)){
			global $wpdb;
			$table = $wpdb->prefix."ww_widget_data";
			
			// see if this is a widget
			$widget_data = $wpdb->get_row($wpdb->prepare("SELECT post_id,type FROM ".$table." WHERE post_id = %d", $post_id));
			
			if (isset($widget_data)){
				$where = array(
					'post_id' => $widget_data->post_id,
					'type' => $widget_data->widget_type,
				);
				
				$wpdb->delete($table, $where);
			}
		}
	}
	
  /*
	 * Clone Instance
	 */ 
  function meta_clone_instance()
  {
    // get widget factory and post data
    global $wp_widget_factory;

    $data = $this->widget_data;
   
    if(isset($data['clone-class']))
    {
      $instance = isset($data['clone-instance']) ? $data['clone-instance'] : array();

      // create instance form
      ob_start();
        eval('$w = new '.$data['clone-class'].'(); $w->form($instance);');
        $instance_form = ob_get_clean();
        
      $hide_title_checked = (isset($data['hide_title'])) ? 'checked="checked"' : '';
      ?>
        <label>
          <input type="checkbox" name="ww-data[clone][hide_title]" <?php print $hide_title_checked; ?> /> - Hide the Widget's title on display
        </label>
        <div class="ww-clone-form">
          <?php print $instance_form; ?>
        </div>
        <input type="hidden" name="ww-data[clone][clone-instance]" value="Placeholder" />
      <?php
    }
  }
	
  /*
	 * Widget Type
	 */ 
  function meta_widget_details()
  {
		global $wp_widget_factory;
		$data = $this->widget_data;
    $widget_types = ww_all_widget_types();
    
    // use standard as a sdefault for new widgets
    if(!isset($this->widget_type)){
      $this->widget_type = 'standard';
    } 
    // output field
    ?>
      <div>
        <?php if($this->post_id) { ?>
					<div class="ww-widget-postid">
						Post ID<br/>
						<span><?php print $this->post_id;?></span>
					</div>
				<?php } ?>
				
        <p><strong>Widget Type:</strong> <?php print $widget_types[$this->widget_type]; ?></p>
				
				<?php
					// Clone details
					if(isset($data['clone-class']))
					{
						$clone_class = $data['clone-class'];
						?>
							<p>
								<strong>WP Widget:</strong> <?php print $wp_widget_factory->widgets[$clone_class]->name; ?>
								<input type="hidden" name="ww-data[clone][clone-class]" value="<?php print $clone_class; ?>" />
							</p>
						<?php
					}
					// select box if not set widget is clone but no clone class set
					else if ($this->widget_type == "clone")
					{ ?>
							<select name="ww-data[clone][clone-class]">
								<?php
									foreach ($wp_widget_factory->widgets as $class_name => $widget)
									{
										$this_widget_selected = ($clone_class == $class_name) ? 'selected="selected"':'';
										?>
										<option value="<?php print $class_name; ?>" <?php print $this_widget_selected; ?>><?php print $widget->name; ?></option>
										<?php
									}
								?>
							</select>
						<?php
					}

          // widget shortcode
          if($this->post_id)
          { ?>
            <strong>Widget Shortcode:</strong>
            <p>
              <code>[ww_widget  id=<?php print $this->post_id; ?>]</code>
            </p>
            <?php
          }
        ?>
        <input type="hidden" name="ww-type" value="<?php print $this->widget_type; ?>" />
        
        <div class="ww-clear-gone"><!-- ie hack -->&nbps;</div>
      </div>
    <?php
  }

    
  /*
	 * Preview box
	 */ 
  function meta_widget_preview()
  {
    if ($this->post_id)
    {
      // query widgets break edit page
      if(isset($this->widget_data['clone-class']) && $this->widget_data['clone-class'] == "Query_Wrangler_Widget") {
        print "Query Widgets cannot be previewed";
      }
      else {
        $widget = ww_get_single_widget($this->post_id);
				$all_corrals = ww_get_all_corrals();
				$preview = ww_theme_single_widget($widget);
				$preview_balance = balanceTags($preview, true);
        ?>
					<div id="ww-preview">
						<label><strong>Preview Corral Context:</strong></label> 
						<select id="ww-preview-corral" name="ww-data[preview-corral-id]" class="widefat" style="width:100%;">
              <option value='0'>- No Corral -</option>
							<?php
								foreach($all_corrals as $id => $corral)
								{
									$selected = (isset($widget->widget_data['preview-corral-id']) && $id == $widget->widget_data['preview-corral-id']) ? 'selected="selected"': "";
									?>
									<option <?php print $selected; ?> value="<?php print $id; ?>"><?php print $corral['name']; ?></option>
									<?php
								}
							?>
						</select>
						<p><em>The above setting only affects the preview on this page, it does not set the corral for this widget in any way.</em>
						<p><em>This preview does not include your theme's CSS stylesheet.</em></p>
						
						<label><strong>Preview</strong></label> 
            <div id="ww-preview-widget"><?php print $preview_balance; ?></div>
						
						<?php if (trim($preview) != trim($preview_balance)) { ?>
							<div class="preview-unbalanced">
								<span>Your widget may contain some broken or malformed html.</span> Wordpress balanced the tags in this preview in an attempt to prevent the page from breaking, but it will not do so on normal widget display.
							</div>
						<?php } ?>
						
          </div>
        <?php
      }
    }
  }
  
  /*
	 * Options / Advanced
	 */ 
  function meta_parse()
  {
    $data = $this->widget_data;
    
    // default to checked upon creation
    $rewrite_output_checked = (isset($data['rewrite-output'])) ? 'checked="checked"' : '';
    $adv_template_checked   = (isset($data['adv-template'])) ? 'checked="checked"' : '';
    $filter_content_checked = (isset($data['filter-content']) || (!isset($_GET['action']) && isset($_GET['post_type']) && $_GET['post_type'] == 'widget')) ? 'checked="checked"' : '';
    $data_custom_output     = (isset($data['custom-output'])) ? htmlentities($data['custom-output']) : "";
    ?>
      <div id="ww-template">
        <div>
          <div class="ww-options-group">
						<label class="ww-checkbox"><input type="checkbox" name="ww-data[standard][filter-content]" <?php print $filter_content_checked; ?> /> - Apply Content filters to this Widget's Content.</label>
						
						<div class="ww-options-group-title">
							<label class="ww-checkbox"><input id="ww-adv-parse-toggle" type="checkbox" name="ww-data[standard][rewrite-output]" <?php print $rewrite_output_checked; ?> /> - Rewrite the output of this widget</label>
						</div>
						
						<div class="ww-options-group-content ww-options-hidden">
							<h4>Rewrite Output</h4>
							<label class="ww-checkbox ww-adv-template"><input id="ww-adv-template-toggle" type="checkbox" name="ww-data[standard][adv-template]" <?php print $adv_template_checked; ?> /> - Template the rewritten output.</label>
							<textarea name="ww-data[standard][custom-output]" cols="40" rows="16" style="width: 100%;"><?php print $data_custom_output; ?></textarea>
						</div>
          </div> <!-- ww-options-group -->
        </div>
				<?php
					$this->meta_advanced_help();
				?>
      </div>
    <?php
  }
	
	/*
	 * Advanced Help
	 */
	function meta_advanced_help()
	{
		if ($widget = ww_get_single_widget($this->post_id)){
			$args = array(
				'widget' => $widget,
				'widget_id' => $widget->ID,
				'widget_type' => $widget->widget_type,
				'corral_id' => (isset($widget->widget_data) && isset($widget->widget_data['preview-corral-id'])) ? $widget->widget_data['preview-corral-id'] : 0,
				'tw_action'  => 'find_only',
			);
			$a = theme('ww_widget', $args);
			
			$suggestions = "";
			if (isset($a['suggestions'])){
				foreach($a['suggestions'] as $i => $suggestion) {
					// we can't detect corral here
					$suggestion = str_replace("corral_0", "corral_[corral_id]", $suggestion);
					$class = ($suggestion == $a['found_suggestion']) ? "ww-template-suggestion found" : "ww-template-suggestion";
					$suggestions.= "<li class='$class'>".$suggestion."</li>";
				}
			}
		}
			?>
			<div id="ww-advanced-help">
				<div class="ww-advanced-help-title"><h4>Advanced Help</h4></div>
				<div class="ww-advanced-help-description">
					<?php
						//  only show adv parsing help on standard
						if (isset($this->widget_type) && $this->widget_type == "standard")
					  { ?>
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
								<li>Example: &lt;?php return array("title" => "The Widget's Title", "content" => "The Widget's Content"); ?&gt;</li>
							</ul>
							<?php
						}
						
						if (isset($suggestions)) { ?>
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
						}
					?>
				</div>
			</div>
		<?php
	}
	
  /*
	 * Display logic
	 */ 
  function meta_display_logic()
  {
    $data = $this->widget_data;
		$display_logic_enabled = (isset($data['logic']['enabled']) && $data['logic']['enabled'] == 1) ? 'checked="checked"' : '';
    ?>
    <p><em>The Widget's display logic lets you choose which Wordpress pages the widget is allowed to be displayed on.</em></p>
		<p>
			<label class="ww-checkbox"><input type="checkbox" name="ww-data[logic][enabled]" <?php print $display_logic_enabled; ?> /> - <strong>Enable</strong></label>
			<em>If display logic is not enabled, the widget will display in all cases.</em>
		</p>
    <div id="logic-tabs">
      <ul id="logic-tabs-list"  class="ww-admin-tab-list">
        <li><a href="#tab-post-types"><span>Post Types</span></a></li>
        <!--<li><a href="#tab-conditional-tags"><span>Conditional Tags</span></a></li>-->
        <li><a href="#tab-php"><span>Custom PHP</span></a></li>
      </ul>
      <?php
        /*
         * Post types
         */ 
        // Get all extra post types
        $post_types = get_post_types(array('public' => true, '_builtin' => false),'names','and');
        $post_types['post'] = 'post';
        $post_types['page'] = 'page';
        unset($post_types['widget']);
        ksort($post_types);    
      ?>
			<div id="tab-post-types" class="ww-admin-tab">
				<div class="description">
					<strong>Select which post types this widget should display on.</strong>
				</div>
				<div class="ww-checkboxes">
					<?php  
						// loop through post types
						foreach ($post_types as $post_type )
						{
							$post_type_checked = (isset($data['logic']['post_types'][$post_type])) ? 'checked="checked"' : '';
							?>
							<label class="ww-checkbox"><input type="checkbox" name="ww-data[logic][post_types][<?php print $post_type; ?>]" value="<?php print $post_type; ?>" <?php print $post_type_checked; ?> /> - <?php print ucfirst($post_type); ?> </label>
							<?php
						}
					?>
				</div>
			</div>
			<?php
        /*
         * Conditional tags
         */
				/*/ on hold
        $conditionals = array('category','tag','home','front_page','single','author','search','404');
      ?>
			<div id="tab-conditional-tags" class="ww-admin-tab">
				<div class="description">
					<strong>Select which conditions should make this widget display.</strong>
				</div>
				<div class="ww-checkboxes">
					<?php
						// loop through post types
						foreach ($conditionals as $conditional )
						{
							$conditional_checked = (isset($data['logic']['conditionals'][$conditional])) ? 'checked="checked"' : '';
							?>
							<label class="ww-checkbox"><input type="checkbox" name="ww-data[logic][conditionals][<?php print $conditional; ?>]" value="<?php print $conditional; ?>" <?php print $conditional_checked; ?> /> - <?php print ucfirst($conditional); ?> </label>
							<?php
						}
					?>
				</div>
			</div>
			<?php //*/ ?>
        
      <?php
        /*
         * Custom php
         */
        $data_logic_php = (isset($data['logic']['php'])) ? htmlentities($data['logic']['php']) : "";
      ?>
			<div id="tab-php" >
				<div class="ww-admin-tab description">
					Use custom php to determine if the widget should be displayed.
					<ul>
						<li>If there is code present, it must return true to display the widget.</li>
						<li>Do not use php tags. (eg. &lt;?php , ?&gt;)</li>
						<li>Example:  <code>return is_front_page();</code> would only display this widget on the front page.</li>
					</ul>
				</div>
				<div class="logic-php-textarea">
					<strong>PHP code:</strong><br/>
					<textarea name="ww-data[logic][php]" cols="40" rows="16" style="width: 100%;"><?php print $data_logic_php; ?></textarea>
				</div>
			</div>
      <div class="ww-clear-gone"><!-- ie hack -->&nbps;</div>
    </div>
    <?php
	}
}
// end widget class
 