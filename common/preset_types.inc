<?php

/*
 * All Widget Preset types provided by default
 *
 * @return array All widget preset types
 */
function ww_get_all_preset_types(){
  $preset_types = apply_filters('ww_preset_types', array());

  foreach($preset_types as $type => $preset_type){
    // set filter's type as a value if not provided by filter
    if(!isset($preset_type['type'])){
      $preset_types[$type]['type'] = $type;
    }
    // maintain the hook's key
    $preset_types[$type]['hook_key'] = $type;
  }

  return $preset_types;
}


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
 * Execute standard preset type
 *
 * @return array of widgets
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
 * Execute term preset type
 *
 * @return array of widgets
 */
function ww_preset_term_execute($op, &$widgets_array){
	
	switch($op){
    // tags and categories are controlled the same way
    case 'tag':
    case 'category':
      global $wpdb,$wp_query;
      // get first category
      if($op == 'category' && isset($wp_query->query_vars['category_name'])) {
        $term = get_category_by_slug($wp_query->query_vars['category_name']);  
      }
      else if ($op == 'tag'){
				if (isset($wp_query->query_vars['tag_id'])){
					$term = get_tag($wp_query->query_vars['tag_id']);
				}
      }
      //print_r($term);
			if (isset($term)){
				// get the preset
				$presets_table = $wpdb->prefix.'ww_presets';
				$relation_table = $wpdb->prefix.'ww_preset_term_relationships';
				
				// look for presets for this category
				$sql = "SELECT r.preset_id, s.widgets
								FROM ".$relation_table." as r
								LEFT JOIN ".$presets_table." as s ON r.preset_id = s.id
								WHERE r.term_id = %d
								LIMIT 1";
				$row = $wpdb->get_row($wpdb->prepare($sql,$term->term_id));
				
				// make the array
				if(isset($row->preset_id)){
					$widgets_array = unserialize($row->widgets);
				}
			}
      break;
  }
}

/*
 * Execute default preset type
 *
 * @return array of widgets
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
 * Form to display on the admin presets page
 *		- use data[my_key] for form item names
 * print out preset form
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
