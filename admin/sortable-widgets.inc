<?php

/*
 * Theme the output for editing widgets on a page
 */
function ww_theme_sortable_widgets($v)
{
	return $v;
}

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

    // build select box
    $corrals_options = "<option value='disabled'>Disabled</option>";
    foreach($all_corrals as $id => $corral){
      ($id == $corral_slug) ? $selected = "selected='selected'" : $selected = '';
      $corrals_options.= "<option name='".$id."' value='".$id."' ".$selected.">".$corral['name']."</option>";
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
	foreach($all_corrals as $corral_id => $corral)
	{
		// open the list
		$themed.= "<h4 class='ww-sortable-widgets-corral-title'>".$corral['name']." <sup>id: ".$corral_id."</sup></h4>
								<ul name='".$corral_id."' id='ww-corral-".$corral_id."-items' class='inner ww-sortable' width='100%'>";
		$style = '';
		if (isset($corrals_array['active']) && isset($corrals_array['active'][$corral_id]) && is_array($corrals_array['active'][$corral_id])) {
			// loop through corral array and add items to list
			foreach($corrals_array['active'][$corral_id] as $item){
				$themed.= $item;
			}
			//hide the 'no widgets' list item when widgets are present
			$style = "style='display: none;'";
		}
		
		// close the list
		$themed.= "	<li class='ww-no-widgets' ".$style.">No widgets in this corral.</li>
							 </ul>";
	}

  // disabled list
  $themed.= "<h4 class='ww-sortable-widgets-corral-title'>Disabled</h4>
							<ul name='disabled' id='ww-disabled-items' class='inner ww-sortable' width='100%'>";

  // loop through and add disabled widgets to list
  $style = '';
  if (isset($corrals_array['disabled']) && is_array($corrals_array['disabled'])){
    foreach ($corrals_array['disabled'] as $disabled){
      $themed.= $disabled;
    }
    $style = "style='display: none;'";
  }
	
  // close disabled list
  $themed.= "	<li class='ww-no-widgets' ".$style.">No disabled widgets</li>
						 </ul>";

  return "<div id='ww-sortable-corrals'>$themed</div>";
	
}
