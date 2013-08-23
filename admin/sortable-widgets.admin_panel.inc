<?php
/*
  Single posts/pages can control their own widgets. This file handles the administration interface and functions.
  
	form
	save
	update
	delete
	serialize

*/

/*
 * admin_panel form for single page/post
 */
function ww_widget_post_form($post, $ajax = NULL){
	// get post_id
  if (isset($ajax['post_id']) && !isset($ajax['preset_id'])){
		$post_id = $ajax['post_id'];
	}
	else if (isset($post->ID)) {
		$post_id = $post->ID;
	}
	else if (isset($_GET['post'])){
		$post_id = $_GET['post'];
	}
	
	// add our js
	ww_sortable_widgets_js();

	// get some data to work with
	$all_widgets = ww_get_all_widgets(); 
	$all_presets = ww_get_all_presets();
	$corrals = ww_get_all_corrals();
	
	$active_widgets = array();
	$post_preset = NULL;
	$post_preset_message = "";
		
  if (isset($post_id) && is_numeric($post_id)){
    // get post meta for this post
    // array of chosen widgets
		if ($post_widgets = ww_get_post_widgets($post_id))
		{
			if (isset($post_widgets->preset_id) && $post_widgets->preset_id != 0 && !isset($ajax['post_id'])){
				$post_preset = ww_get_preset($post_widgets->preset_id);
				$active_widgets = $post_preset->widgets;
			}
      else {
				$active_widgets = $post_widgets->widgets;
			}
    }
		// ajax new post_id not in ww records
		else if (isset($ajax['post_id']) && !isset($ajax['preset_id'])) {
			$preset_id = 0;
			$active_widgets = ww_get_preset(1)->widgets;
		}
    // new page/post, pull default widgets 
    else if (is_null($ajax)) {
      $post_preset = ww_get_preset(1);
			$active_widgets = $post_preset->widgets;
    }
  }
	// ajax
	else if (isset($ajax['preset_id']) && $ajax['preset_id'] > 0){
			$post_preset = ww_get_preset($ajax['preset_id']);
			$active_widgets = $post_preset->widgets;
			$post_id = $ajax['post_id'];
	}
	// nothing to do
  else {
    print "There was a problem loading widgets for this page.";
		return;
  }
	
	// preset message
	if (isset($post_preset)){
		$post_preset_message = "This page is currently using the <a href='edit.php?post_type=widget&page=ww-presets&preset_id=".$post_preset->id."'>".$post_preset->data['name']."</a>  Widgets.";
	}
	else {
		$post_preset_message = "Presets are Disabled. This page is wrangling widgets on its own.";
	}
	
	// build top and bottom of output array
	?><div id='widget-wrangler-form' class='new-admin-panel'>
			<div class='outer'>
				<input value='true' type='hidden' name='widget-wrangler-edit' />
				<input type='hidden' name='ww_noncename' id='ww_noncename' value='<?php print wp_create_nonce( plugin_basename(__FILE__) ); ?>' />
				<input value="<?php print $post_id; ?>" type="hidden" id="ww_ajax_post_id" />				
				<?php
					// if this post isn't wrangling widgets, disable the sorting
					if (isset($post_preset) && $post_preset->id != 0)
					{ ?>
						<style type="text/css">
							.ww-sortable .ww-corral-sort-item,
							.ww-sortable .ww-item {
								cursor: default;
							}
						</style>
						<?php
					}
				?>
				<div id='ww-post-preset'>
					<span id="ww-post-preset-message"><?php print $post_preset_message; ?></span>
					<div class='ww-presets'>
						<span>Widget Preset:</span>
						
						<select id='ww_post_preset' name='ww-post-preset-id-new'>
							<option value='0'>- No Preset -</option>
							<?php
								// create options
								foreach($all_presets as $preset)
								{
									$selected = (!is_null($post_preset) && $preset->id == $post_preset->id) ? "selected='selected'" : '';
									?>
									<option value='<?php print $preset->id; ?>' <?php print $selected;?>><?php print $preset->data['name']; ?></option>
									<?php
								}
							?>
						</select>
						<span class="ajax-working">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
						
						<div class='description'>
							<span>Select the Widget Preset you would like to control widgets on this page.  When using a preset, you will not be able to rearrange the widgets below.  To allow this page to control it's own widgets, select '- No Preset -'.</span>							
						</div>
					</div>
				</div>
				
				<div id='ww-post-edit-message'>* Widget changes will not be updated until you save this post.</div>
				
				<?php print theme('ww_sortable_widgets', array('active_widgets' => $active_widgets)); ?>
			</div><!-- .outer -->		
		 </div>
	<?php
}

/*
 * Hook into saving a page
 * Save the post meta for this post
 */
function ww_save_post($post_id)
{
	//print "<pre>".print_r($_POST,1)."</pre>";exit();
  $settings = ww_get_settings();
	
	// make sure ww form was involved
	if (!isset($_POST['widget-wrangler-edit'])){
		return $post_id;
	}

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times
  if (isset($_POST['ww_noncename']) && !wp_verify_nonce( $_POST['ww_noncename'], plugin_basename(WW_PLUGIN_DIR.'/admin/sortable-widgets.admin_panel.inc') )) {
    //print 'bad nonce';exit();
		return $post_id;
  }
	
	// make sure we're posted good info
	if (empty($_POST) || !isset($_POST['post_type']) || !isset($_POST['ww-widgets'])){
		// nothing good posted, so we're not saving
		return $post_id;
	}

	// don't track revisions independently
	if ( wp_is_post_revision( $post_id ) ) {
		return $post_id;
	}

  // verify if this is an auto save routine. If it is, our form has not been submitted, so we dont want
  // to do anything
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
    return $post_id;
  }

  // Check permissions to save widgets
  if (!current_user_can('edit_page', $post_id)){
		return $post_id;
  }
	
	// default values set to null for future use
	$current_preset_id = NULL;
	$new_post_widgets = NULL;
	
	// preset id submitted by user
	$preset_id_new = (isset($_POST['ww-post-preset-id-new'])) ? (int)$_POST['ww-post-preset-id-new'] : 0;
	
	// OK, we're authenticated: we need to find and save the data
	if ($current_page_widgets = ww_get_post_widgets($post_id)){
		// no longer null because we found widgets in ww_post_widgets for this post_id
		$current_preset_id = $current_page_widgets->preset_id;
	}
	
		
	// we only save widgets when the preset has not been changed.
	// this prevents overwriting a post's widgets everytime a user changes the preset
	if ($preset_id_new == $current_preset_id || $preset_id_new == 0){
		$new_post_widgets = ww_serialize_widgets($_POST['ww-widgets']);
	}
	// first time saving widgets, leave widgets blank if preset is chosen
	else if (is_null($current_preset_id) && $preset_id_new > 0){
		$new_post_widgets = array();
	}
	// else, $new_post_widgets remains null
	
	//save what we have
	ww_update_post_widgets( $post_id, $preset_id_new, $new_post_widgets);
}

/*
 * Serialize widgets from a saved form
 *
 * @param array $posted $_POST array for a saved widget
 * @return string serialized array of the widgets within the $_POST array
 */
function ww_serialize_widgets($posted_widgets)
{
  $active_widgets = array();
  $all_widgets = ww_get_all_widgets();

  // loop through post data and build widgets array for saving
  $i = 1;
  foreach($all_widgets as $key => $widget)
  {
		if (isset($posted_widgets[$widget->post_name])){
			$name = $posted_widgets[$widget->post_name]['name'];
			$weight = $posted_widgets[$widget->post_name]['weight'];
			$corral = $posted_widgets[$widget->post_name]['corral'];
	
			// if something was submitted without a weight, make it neutral
			if ($weight < 1){
				$weight = $i;
			}
			// add widgets to save array
			if (($corral && $name) &&
					($corral != 'disabled'))
			{
				$active_widgets[$corral][] = array(
							'id' => $widget->ID,
							'name' => $widget->post_title,
							'weight' => $weight,
							);
			}
		}
    $i++;
  }
  // serialize the results
  return serialize($active_widgets);
}

/*
 * Save a post's widgets
 *
 * @param int $post_id -
 * 		The WP post ID for the page or post to assign widgets
 * 		
 * @param int $preset_id -
 * 		The page/post's widget preset_id
 * 		
 * @param string $post_widgets -
 * 		Serialized array of widgets on this post
 */
function ww_update_post_widgets($post_id, $preset_id = 0, $post_widgets = NULL){
  global $wpdb;
  $table = $wpdb->prefix."ww_post_widgets";
  $set = array(
		'preset_id' => $preset_id,
	);
	
	// we don't always save widgets
	if (!is_null($post_widgets)){
		$set['widgets'] = $post_widgets;
	}
  
	$where = array(
    'post_id' => $post_id,
  );

  // If can't update, insert
  // $wpdb->update responds false on errors
  if(!$wpdb->update($table, $set, $where))
  {
    // send along the post_id
    $set['post_id'] = $post_id;
		if (!isset($set['widgets'])){
			$set['widgets'] = serialize(array());
		}
    $wpdb->insert($table, $set);
  }
}

/*
 * Delete a post's widgets
 *
 * @param int $post_id The WP post ID for the page or post to delete widgets from
 */
function ww_delete_post_widgets($post_id){
  if(is_numeric($post_id)) {
    global $wpdb;
    $table = $wpdb->prefix."ww_post_widgets";
    $wpdb->query($wpdb->prepare("DELETE FROM ".$table." WHERE post_id = %d", $post_id));
  }
}
