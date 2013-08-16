<?php

/*
 * Theme the output for editing widgets on a page
 */
function ww_theme_sortable_widgets($panel_array)
{
	// start output
	$output = "";
  $corrals = ww_get_all_corrals();
	
  // loop through corrals and add active widgets to list
  if (is_array($corrals)){
    foreach($corrals as $slug => $corral)
		{
      // open the list
      $output.= "<h4>".$corral."</h4>
									<ul name='".$slug."' id='ww-corral-".$slug."-items' class='inner ww-sortable' width='100%'>";

      if (isset($panel_array['active']) && isset($panel_array['active'][$slug]) && is_array($panel_array['active'][$slug])) {
        // loop through corral array and add items to list
        foreach($panel_array['active'][$slug] as $item){
          $output.= $item;
        }
				//hide the 'no widgets' list item when widgets are present
        $style = "style='display: none;'";
      }
      else {
        $style = '';
      }
      // close the list
      $output.= "	<li class='ww-no-widgets' ".$style.">No Widgets in this corral.</li>
								 </ul>";
    }
  }

  // disabled list
  $output.= "<h4>Disabled</h4>
							<ul name='disabled' id='ww-disabled-items' class='inner ww-sortable' width='100%'>";

  // loop through and add disabled widgets to list
  if (isset($panel_array['disabled']) && is_array($panel_array['disabled'])){
    foreach ($panel_array['disabled'] as $disabled){
      $output.= $disabled;
    }
    $style = "style='display: none;'";
  }
  else{
    $style = '';
  }
  // close disabled list
  $output.= "	<li class='ww-no-widgets' ".$style.">No disabled Widgets</li>
						 </ul>";

  return "<div id='ww-sortable-corrals'>$output</div>";
}

/*
 * Put all widgets into a list for output
 */
function ww_create_sortable_widgets($widgets, $ref_array, $corrals)
{
  $i = 0;
  foreach($widgets as $widget)
  {
    $temp = array();
    $keys = ww_array_searchRecursive($widget->ID, $ref_array);
    // fix widgets with no title
    if ($widget->post_title == ""){
      $widget->post_title = "(no title) - Widget ID: ".$widget->ID;
    }

    // look for appropriate corral, default to disabled
    if ($keys[0] == '' || (!array_key_exists($keys[0], $corrals))){
      $keys[0] = "disabled";
    }

    // setup initial info
    $corral_slug = $keys[0];

    // get weight
    if (isset($keys[1]) && isset($ref_array[$corral_slug][$keys[1]]['weight'])) {
      $weight = $ref_array[$corral_slug][$keys[1]]['weight'];
    } else {
      $weight = count($temp); // 'next' row
    }

    // build select box
    $corrals_options = "<option value='disabled'>Disabled</option>";
    foreach($corrals as $slug => $corral){
      ($slug == $corral_slug) ? $selected = "selected='selected'" : $selected = '';
      $corrals_options.= "<option name='".$slug."' value='".$slug."' ".$selected.">".$corral."</option>";
    }

    // add item to our temp array
    $temp[$weight] = "<li class='ww-item ".$corral_slug." nojs' width='100%'>
                        <input class='ww-widget-weight' name='ww-widgets[".$widget->post_name."][weight]' type='text' size='2' value='$weight' />
                        <select name='ww-widgets[".$widget->post_name."][corral]'>
                        ".$corrals_options."
                        </select>
                        <input class='ww-widget-name' name='ww-widgets[".$widget->post_name."][name]' type='hidden' value='".$widget->post_name."' />
                        <input class='ww-widget-id' name='ww-widgets[".$widget->post_name."][id]' type='hidden' value='".$widget->ID."' />
                        ".$widget->post_title."
                      </li>";

    // place into output array
    if ($corral_slug == 'disabled'){
      $output['disabled'][] = $temp[$weight];
    }
    else{
      $output['active'][$corral_slug][$weight] = $temp[$weight];
    }

    $i++;
  }
	
	// sort the corrals' widgets
	if (isset($output['active'])){
		foreach($output['active'] as $corral => $widgets){
			if ($output['active'][$corral]){
				ksort($output['active'][$corral]);
			}
		}
	}
  return $output;
}
