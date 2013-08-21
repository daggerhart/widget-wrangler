<?php

/*
 * Default Preset types 
 */
function ww_default_preset_types($preset_types){
  $preset_types['default'] = array(
    'title' => 'Default',
		'description' => 'Default provided presets',
    //'form_callback' => 'ww_preset_default_form',
		'execution_callback' => 'ww_preset_default_execute',
		'execution_ops' => array('home', 'front_page', 'search', '404'),
  );
  $preset_types['standard'] = array(
    'title' => 'Standard',
		'description' => '',
    //'form_callback' => 'ww_preset_standard_form',
		'execution_callback' => 'ww_preset_standard_execute',
		'execution_ops' => array('single', 'page'),
  );
  $preset_types['term'] = array(
    'title' => 'Term (Cats &amp; Tags)',
		'description' => 'If you select a category or tag for multiple widget presets, results will conflict.',
    'form_callback' => 'ww_preset_term_form',
		'execution_callback' => 'ww_preset_term_execute',
		'execution_ops' => array('category', 'tag'),
  );
	return $preset_types;
}
// add default preset_types to the filter
add_filter('ww_preset_types', 'ww_default_preset_types');

/*
 *
 */
function ww_preset_standard_execute($op, &$widgets_array){
	global $post;
	$settings = ww_get_settings();
	
  switch ($op) {
    case 'single':
    case 'page':
			if (isset($settings['post_types'][$post->post_type])){
				// see if we have widgets for this page
				if ($post_widgets = ww_get_post_widgets($post->ID)){
					// individual widgets or a preset ?
					if (isset($post_widgets->preset_id) && $post_widgets->preset_id != 0){
						// preset
						$widgets_array = ww_get_preset($post_widgets->preset_id)->widgets;
					}
				}
				else {
					// widget wrangler enabled for type, serve default widgets
					$widgets_array = ww_get_preset(1)->widgets;
				}
			}
      break;
  }	
}

/*
 *
 */
function ww_preset_term_execute($op, &$widgets_array){
	switch($op){
    // tags and categories are controlled the same way
    case 'tag':
    case 'category':
      global $wpdb;
      // get first category
      if($op == 'category') {
        $category = array_pop(get_the_category());  
      }
      else if ($op == 'tag'){
        $category = array_pop(get_the_tags());
      }
      
      // get the preset
      $presets_table = $wpdb->prefix.'ww_presets';
      $relation_table = $wpdb->prefix.'ww_preset_term_relationships';
      
      // look for presets for this category
      $sql = "SELECT r.preset_id, s.widgets
              FROM ".$relation_table." as r
              LEFT JOIN ".$presets_table." as s ON r.preset_id = s.id
              WHERE r.term_id = %d
              LIMIT 1";
      $row = $wpdb->get_row($wpdb->prepare($sql,$category->term_id));
      
      // make the array
      if(isset($row->preset_id)){
        $widgets_array = $row->widgets;
      }
      break;
  }
}

/*
 *
 */
function ww_preset_default_execute($op, &$widgets_array){
	switch($op){
		case 'home':
    case 'front_page':
      // see if this is the Posts (blog) page
      if(get_option('show_on_front') == 'posts'){
        // posts page is preset 2
        $widgets_array = ww_get_preset(2)->widgets;
      }
      break;

    case 'search':
      // search page is default preset 3
      $widgets_array = ww_get_preset(3)->widgets;
      break;
    
    case '404':
      // 404 page is default preset 4
      $widgets_array = ww_get_preset(4)->widgets;
      break;
	}
}

/*
 * 
 */
function ww_preset_term_form($preset)
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
								$cat_checked = (isset($preset->data['categories'][$cat_id])) ? 'checked="checked"' : '';
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
								$tag_checked = (isset($preset->data['categories'][$tag->term_id])) ? 'checked="checked"' : '';
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
		$sortable_widgets = theme('ww_sortable_widgets', array('active_widgets' => $preset_widgets));
	}
	
	// sort each corral
	if(isset($sortable_widgets['active']) && is_array($sortable_widgets['active'])){
		foreach($sortable_widgets['active'] as $corral => $unsorted_widgets){
			ksort($sortable_widgets['active'][$corral]);  
		}
	}
	
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
							print ww_theme_sortable_widgets($sortable_widgets);
						?>
					</div>
			</div>
		</div>
		<div class="ww-clear-gone">&nbsp;</div>
	</form>
	<?php
	return ob_get_clean();
}
