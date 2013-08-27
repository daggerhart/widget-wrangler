<?php

/*
 * Delete a Widget Preset
 */
function ww_delete_preset(){
  global $wpdb;
  $table = $wpdb->prefix."ww_presets";
  
	// can't delete defaults
	if(isset($_POST['preset-id']) && isset($_POST['preset-type']) && $_POST['preset-type'] != 'default'){
		$preset_id = $_POST['preset-id'];
		$preset_type = $_POST['preset-type'];
	
		$sql = "DELETE FROM ".$table." WHERE id = %d AND type = '%s' LIMIT 1";
		$wpdb->query($wpdb->prepare($sql,$preset_id,$preset_type));
	}
}
/*
 * Update a Widget Preset
 *
 * @return int Widget Preset id
 */
function ww_update_preset(){
  global $wpdb;
  $table = $wpdb->prefix."ww_presets";

  if (isset($_POST['preset-id']) && isset($_POST['preset-type']) && isset($_POST['data']) && isset($_POST['ww-widgets'])) 
	{
		$preset_id = $_POST['preset-id'];
		$preset_type = $_POST['preset-type'];
		$preset_data = serialize($_POST['data']);
		$preset_widgets = ww_serialize_widgets($_POST['ww-widgets']);
		
		// update the ww_presets db
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
		if($preset_type == 'term' && isset($_POST['data']['categories']) && is_array($_POST['data']['categories']))
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
}
/*
 * Create a new Widget Preset
 *
 * @return int New Widget Preset id
 */
function ww_create_preset()
{
  global $wpdb;
  $table = $wpdb->prefix."ww_presets";
  if (isset($_POST['create']['type'])){
		switch($_POST['create']['type'])
		{
			case 'term':
			case 'standard':
				$data = array(
					'type' => $_POST['create']['type'],
					'data' => serialize(array('name' => ucfirst($_POST['create']['type']))),
					'widgets' => serialize(array()),
				);
				
				$wpdb->insert($table, $data);
				
				return $wpdb->insert_id;
				break;
		}
	}
}


/*
 * Admin Manage presets form
 */
function ww_preset_form() {
	// prepare preset data  
	if(!isset($_GET['preset_id']) || !is_numeric($_GET['preset_id']) || $_GET['preset_id'] < 1){
		$preset_id = 1;
	} else {
		$preset_id = $_GET['preset_id'];
	}
	$all_preset_types     = ww_get_all_preset_types();
	$this_preset     = ww_get_preset($preset_id);
	$preset_type     = $all_preset_types[$this_preset->type];
	$preset_data     = $this_preset->data;
	$preset_widgets  = $this_preset->widgets;
	$all_presets     = ww_get_all_presets();
	
	$sortable_widgets = array();
	$all_widgets = ww_get_all_widgets();
	$all_corrals = ww_get_all_corrals();  
	
	// add additional corrals to output
	if (is_array($all_widgets) && count($all_corrals) > 0 && count($all_widgets) > 0){
		//$sortable_widgets = theme('ww_sortable_widgets', array('active_widgets' => $preset_widgets));
	}
	
	/*/ sort each corral
	if(isset($sortable_widgets['active']) && is_array($sortable_widgets['active'])){
		foreach($sortable_widgets['active'] as $corral => $unsorted_widgets){
			ksort($sortable_widgets['active'][$corral]);  
		}
	}
	// */
	ob_start();
	?>
	<form action='edit.php?post_type=widget&page=ww-presets&action=update&noheader=true' method='post' name='widget-wrangler-form'>				
		<div id="ww-page-top">
			<input class='button button-primary button-large' name='action[update]' type='submit' value='Save Preset' />
			
			<?php if($preset_type['type'] != 'default'){ ?>
				<input class='button' name='action[delete]' type='submit' value='Delete' />
			<?php } ?>
			
			<input value='true' type='hidden' name='widget-wrangler-edit' />
			<input type='hidden' name='ww_noncename' id='ww_noncename' value='<?php print wp_create_nonce( plugin_basename(__FILE__) ); ?>' />
		</div>
	
		<div id="presets-wrap">	
			<div id="presets-links">
				<ul class="ww-admin-tab-list">
					<li class="ww-admin-tab-list-title">Presets</li>
					<?php
						// show all presets
						if(is_array($all_presets)){
							foreach($all_presets as $preset)
							{
								$classes = ($preset_id == $preset->id) ? 'active' : '';
								?>
								<li class="<?php print $classes; ?>">
									<a href="edit.php?post_type=widget&page=ww-presets&preset_id=<?php print $preset->id; ?>"><?php print $preset->data['name']; ?><?php print ($preset->type != 'default') ? ' ('.$preset->type.')' : ''; ?></a>
								</li>
								<?php
							}
						}
					?>
				</ul>
				
				<div id="presets-add-new">
					<select name="create[type]">
						<?php
							foreach($all_preset_types as $key => $pstype)
							{ ?>
								<option value="<?php print $key; ?>"><?php print $pstype['title']; ?></option>
								<?php
							}
						?>
					</select>
					<br />
					<input type="submit" name="action[create]" class="button" value="Create New Preset" />
				</div>
			</div>
		
			<div  class="ww-admin-tab">					
					<div id="preset-data">
						<div id="preset-name">
							<div class="detail">
								<label>Name:</label>
									<?php
										// can't change the names of defaults
										if($preset_type['type'] == 'default')
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
								<label>Type:</label> <?php print $preset_type['type']; ?>
							</div>
							<input type="hidden" name="preset-id" value="<?php print $preset_id; ?>" />
							<input type="hidden" name="preset-type" value="<?php print $preset_type['type']; ?>" />          
						</div>
					</div>
					<?php
						if (isset($preset_type['form_callback']) && function_exists($preset_type['form_callback'])){
							$preset_type['form_callback']($this_preset);
						}
					?>
					
					<div id="preset-widgets">
						<?php
							// themes draggable widgets
							print theme_ww_sortable_widgets($preset_widgets);
						?>
					</div>
			</div>
		</div>
		<div class="ww-clear-gone">&nbsp;</div>
	</form>
	<?php
	return ob_get_clean();
}
