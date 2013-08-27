<?php

/*
 * Put all widgets into a list for output
 */
function theme_ww_sortable_widgets($active_widgets)
{
	$all_widgets = ww_get_all_widgets();
	$all_corrals = ww_get_all_corrals();
  
	$i = 0;
  foreach($all_widgets as $widget)
  {
    $temp = array();
    $keys = ww_array_searchRecursive($widget->ID, $active_widgets);
    // fix widgets with no title
    if ($widget->post_title == ""){
      $widget->post_title = "(no title) - Widget ID: ".$widget->ID;
    }

    // look for appropriate corral, default to disabled
    if ($keys[0] == '' || (!array_key_exists($keys[0], $all_corrals))){
      $keys[0] = "disabled";
    }

    // setup initial info
    $corral_slug = $keys[0];

    // get weight
    if (isset($keys[1]) && isset($active_widgets[$corral_slug][$keys[1]]['weight'])) {
      $weight = $active_widgets[$corral_slug][$keys[1]]['weight'];
    } else {
      $weight = count($temp); // 'next' row
    }
    $temp[$weight] = ww_single_sortable_widget($widget, $corral_slug, $weight);

    // place into output array
    if ($corral_slug == 'disabled'){
      $corrals_array['disabled'][] = $temp[$weight];
    }
    else{
      $corrals_array['active'][$corral_slug][$weight] = $temp[$weight];
    }

    $i++;
  }
	
	// sort the corrals' widgets
	if (isset($corrals_array['active'])){
		foreach($corrals_array['active'] as $corral => $all_widgets){
			if ($corrals_array['active'][$corral]){
				ksort($corrals_array['active'][$corral]);
			}
		}
	}
	
	// start output
	$themed = "";
	
  // loop through corrals and add active widgets to list
	foreach($all_corrals as $corral_id => $corral) {
		$themed.= ww_single_sortable_corral($corrals_array, $corral);
	}
	
	
  // disabled list
  $themed.= "<h4 class='ww-sortable-widgets-corral-title'>Disabled</h4>
							<ul name='disabled' id='ww-disabled-items' class='inner ww-sortable' width='100%'>";

  // loop through and add disabled widgets to list
  $disabled_style = '';
  if (isset($corrals_array['disabled']) &&
			is_array($corrals_array['disabled']))
	{
    $themed.= implode("", $corrals_array['disabled']);
    $disabled_style = "style='display: none;'";
  }
	
  // close disabled list
  $themed.= "	<li class='ww-no-widgets' ".$disabled_style.">No disabled widgets</li>
						 </ul>";
		
  return "<div id='ww-sortable-corrals'>$themed</div>";
}

/*
 * Single wortable widget form
 */
function ww_single_sortable_widget($widget, $corral_slug, $weight)
{	
	$all_corrals = ww_get_all_corrals();
	ob_start();
	?>
		<li class='ww-item <?php print $corral_slug; ?> nojs' width='100%'>
			<input class='ww-widget-weight' name='ww-widgets[<?php print $widget->post_name; ?>][weight]' type='text' size='2' value='<?php print $weight; ?>' />
			<select name='ww-widgets[<?php print $widget->post_name; ?>][corral]'>
				<option value='disabled'>Disabled</option>
				<?php
					foreach($all_corrals as $id => $corral)
					{
						$selected = ($id == $corral_slug) ? "selected='selected'" : '';
						?>
							<option name='<?php print $id; ?>' value='<?php print $id; ?>' <?php print $selected; ?>><?php print $corral['name']; ?></option>
						<?php
					}
				?>
			</select>
			<input class='ww-widget-name' name='ww-widgets[<?php print $widget->post_name; ?>][name]' type='hidden' value='<?php print $widget->post_name; ?>' />
			<input class='ww-widget-id' name='ww-widgets[<?php print $widget->post_name; ?>][id]' type='hidden' value='<?php print $widget->ID; ?>' />
			<?php print $widget->post_title; ?>
		</li>
	<?php
	return ob_get_clean();
}

/*
 * Single sortable corral form
 */
function ww_single_sortable_corral($corrals_array, $corral)
{	
	$has_widgets = false;
	
	if (isset($corrals_array['active']) &&
			isset($corrals_array['active'][$corral['id']]) &&
			is_array($corrals_array['active'][$corral['id']]))
	{
		$has_widgets = true;
	}

	ob_start();
	?>
		<h4 class='ww-sortable-widgets-corral-title'><?php print $corral['name']; ?> <sup>id: <?php print $corral['id']; ?></sup></h4>
			<ul name='<?php print $corral['id']; ?>' id='ww-corral-<?php print $corral['id']; ?>-items' class='inner ww-sortable' width='100%'>
				<?php
					if ($has_widgets){
						print implode(" ", $corrals_array['active'][$corral['id']]);
					}
				?>
				<li class='ww-no-widgets' <?php print ($has_widgets) ? "style='display: none;'" : ""; ?>>No widgets in this corral.</li>
			</ul>
	<?php
	return ob_get_clean();
}

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
