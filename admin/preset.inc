<?php

/*
 * Get all Corral Presets
 *
 * @return array All widget presets found in the DB
 */
function ww_get_all_presets(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_presets";
  
  $sql = "SELECT id,type,data,widgets FROM ".$table." ORDER BY id ASC";
  $results = $wpdb->get_results($sql);
  
  //$presets = array();
  
  return $results;
}
/*
 * Get a single Corral Preset
 *
 * @param int $preset_id The id for the Corral Preset
 * @return array The Corral Preset details
 */
function ww_get_preset($preset_id){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_presets";
  
  $sql = "SELECT id,type,data,widgets FROM ".$table." WHERE id = ".$preset_id." LIMIT 1";
  $results = $wpdb->get_row($sql);
  if (isset($results->data) && is_string($results->data)){
    $results->data = unserialize($results->data);
  }
  return $results;
}
/*
 * All Corral Preset types
 *
 * @return array All widget preset types
 */
function ww_preset_types(){
  return array(
    'custom',
    'term',
  );
}
/*
 * Delete a Corral Preset
 */
function ww_delete_preset(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_presets";
  
  $preset_id = $_POST['preset-id'];
  $preset_type = $_POST['preset-type'];
  
  $sql = "DELETE FROM ".$table." WHERE id = ".$preset_id." AND type = '".$preset_type."' LIMIT 1";
  $wpdb->query($sql);
}
/*
 * Update a Corral Preset
 *
 * @return int Corral Preset id
 */
function ww_update_preset(){
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_presets";
  
  $preset_id = $_POST['preset-id'];
  $preset_type = $_POST['preset-type'];
  $preset_data = serialize($_POST['data']);
  $preset_widgets = ww_serialize_widgets($_POST);
  
  // update the ww_widget_presets db
  $data = array(
    'data' => $preset_data,
    'widgets' => $preset_widgets,
  );
  $where = array(
    'id' => $preset_id,
    'type' => $preset_type,
  );
  
  $wpdb->update($table, $data, $where);
  
  // update the category reference table
  if($preset_type == 'term' && is_array($_POST['data']['categories']))
  {
    // new table
    $table = $wpdb->prefix."ww_preset_term_relationships";
    
    // get category info
    $preset_categories = array_keys($_POST['data']['categories']);
    
    // delete all existing relationships
    $sql = "DELETE FROM ".$table." WHERE preset_id = ".$preset_id;
    $wpdb->query($sql);
          
    // loop through and insert all relationships
    foreach($_POST['data']['categories'] as $term_id => $term_name)
    {
      // insert all relationships
      $data = array(
        'preset_id' => $preset_id,
        'term_id' => $term_id,
      );
      $wpdb->insert($table, $data);
    }
  }
  
  return $preset_id;
}
/*
 * Create a new Corral Preset
 *
 * @return int New Corral Preset id
 */
function ww_create_preset()
{
  //print_r($_POST);exit();
  
  global $wpdb;
  $table = $wpdb->prefix."ww_widget_presets";
      
  switch($_POST['type'])
  {
    case 'term':
    case 'custom':
      $data = array(
        'type' => $_POST['type'],
        'data' => serialize(array('name' => ucfirst($_POST['type']))),
        'widgets' => serialize(array()),
      );
      
      $wpdb->insert($table, $data);
      
      return $wpdb->insert_id;
      break;
  }  
}


/*
 * Variables for 'This' preset
 * -----
 * $preset_id
 * $preset_data
 * $preset_widgets
 * $preset_type
 *
 * Other variables
 * -----
 * $all_corrals
 * $all_widgets
 * $all_presets
 */
function ww_preset_form() {
	// prepare preset data  
	if(!isset($_GET['preset_id']) || !is_numeric($_GET['preset_id']) || $_GET['preset_id'] < 1){
		$preset_id = 1;
	} else {
		$preset_id = $_GET['preset_id'];
	}
	$this_preset     = ww_get_preset($preset_id);
	$preset_type     = $this_preset->type;
	$preset_data     = $this_preset->data;
	$preset_widgets  = unserialize($this_preset->widgets);
	$all_presets     = ww_get_all_presets();
	
	$sortable_widgets = array();
	$all_widgets = ww_get_all_widgets();
	$all_corrals = ww_get_all_corrals();  
	
	// add additional corrals to output
	if (is_array($all_widgets) && count($all_corrals) > 0 && count($all_widgets) > 0){
		$sortable_widgets = ww_create_sortable_widgets($all_widgets, $preset_widgets, $all_corrals);
	}
	
	// sort each corral
	if(isset($sortable_widgets['active']) && is_array($sortable_widgets['active'])){
		foreach($sortable_widgets['active'] as $corral => $unsorted_widgets){
			ksort($sortable_widgets['active'][$corral]);  
		}
	}
	?>
		<div class="wrap">
			<h2>Edit Widget Preset <em><?php print $preset_data['name']; ?></em></h2>
			<div id="presets-wrap">
				
				<div id="presets-details">
					<?php //print $preset_type; // ???  ?>
					<br />
				
				</div>
				
				<div id="presets-corral">
					<div id="presets-list">
						<h4>Corral Presets</h4>
						<?php
							// show all presets
							if(is_array($all_presets)){
								foreach($all_presets as $preset)
								{
									$this_data = unserialize($preset->data);
									$classes = ($preset_id == $preset->id) ? 'active' : '';
									?>
									<div class="preset-list-item <?php print $classes; ?>">
										<a href="edit.php?post_type=widget&page=ww-presets&preset_id=<?php print $preset->id; ?>"><?php print $this_data['name']; ?><?php print ($preset->type != 'default') ? ' ('.$preset->type.')' : ''; ?></a>
									</div>
									<?php
								}
							}
						?>
					</div>
					
					<div id="presets-add-new">
						<form action='edit.php?post_type=widget&page=ww-presets&action=create&noheader=true' method='post' name='widget-wrangler-form'>
							<select name="type">
								<option value="custom">Custom</option>
								<option value="term">Term (Cats & Tags)</option>
							</select>
							<br />
							<input type="submit" name="action-create" class="button" value="Create New Preset" />
						</form>
					</div>
				</div>
			
				<div id="preset-details">
					<form action='edit.php?post_type=widget&page=ww-presets&action=update&noheader=true' method='post' name='widget-wrangler-form'>
						<div id="preset-data">
							<div id="preset-action">
								<input class='button' name='action-save' type='submit' value='Save' />
								
								<?php if($preset_type != 'default'){ ?>
									<input class='button' name='action-delete' type='submit' value='Delete' />
								<?php } ?>
								
								<input value='true' type='hidden' name='widget-wrangler-edit' />
								<input type='hidden' name='ww_noncename' id='ww_noncename' value='<?php print wp_create_nonce( plugin_basename(__FILE__) ); ?>' />
							</div>
							<div id="preset-name">
								<div class="detail">
									<label>Name:</label>
										<?php
											// can't change the names of defaults
											if($preset_type == 'default')
											{
												print $preset_data['name'];
												?>
													<input type="hidden" name="data[name]" value="<?php print $preset_data['name']; ?>" />
												<?php
											}
											else
											{ ?>
													<input size="40" type="text" name="data[name]" value="<?php print $preset_data['name']; ?>" />
												<?php
											}
										?>
								</div>
								<div class="detail">
									<label>Type:</label> <?php print $preset_type; ?>
								</div>
								<input type="hidden" name="preset-id" value="<?php print $preset_id; ?>" />
								<input type="hidden" name="preset-type" value="<?php print $preset_type; ?>" />          
							</div>
						</div>
						<?php
							// categories
							if($preset_type == 'term')
							{ ?>
								<div id="preset-terms">
									<div class="description">
										<em>If you select a category or tag for multiple widget presets, results will conflict.</em>
									</div>
									<?php
										// categories
										$category_ids = get_all_category_ids();
										?>
											<div id="preset-categories">
												<p><strong>Select which category pages to display these widgets on.</strong></p>
												<div class="preset-checkboxes">
													<?php
														// List all categories as checkboxes
														foreach($category_ids as $cat_id)
														{
															$cat_name = get_cat_name($cat_id);
															$cat_checked = (isset($preset_data['categories'][$cat_id])) ? 'checked="checked"' : '';
															?>
															<label class="ww-preset-checkbox"><input type="checkbox" name="data[categories][<?php print $cat_id; ?>]" value="<?php print $cat_name; ?>" <?php print $cat_checked; ?> /><?php print $cat_name; ?></label> 
															<?php
														}
													?>                
												</div>
											</div>
										<?php
		
										// tags
										$tags = get_tags(array('hide_empty' => false));
										?>
											<div id="preset-tags">
												<p><strong>Select which tag pages to display these widgets on.</strong></p>
												<div class="preset-checkboxes">
													<?php
														// List all categories as checkboxes
														foreach($tags as $tag)
														{
															$tag_checked = (isset($preset_data['categories'][$tag->term_id])) ? 'checked="checked"' : '';
															?>
															<label class="ww-preset-checkbox"><input type="checkbox" name="data[categories][<?php print $tag->term_id; ?>]" value="<?php print $tag->name; ?>" <?php print $tag_checked; ?> /><?php print $tag->name; ?></label> 
															<?php
														}
													?>                
												</div>
											</div>
									</div>
								<?php
							}
						?>
						
						<div id="preset-widgets">
							
							<?php
								// themes draggable widgets
								ww_theme_sortable_widgets($sortable_widgets);
							?>
							
						</div>
					</form>
				</div>
				
			</div>
		</div>
	<?php
}