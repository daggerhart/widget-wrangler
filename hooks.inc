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

