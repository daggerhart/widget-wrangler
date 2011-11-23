<?php
/*
 * Handle sorting of sidebars
 */
function ww_sidebar_sort($posted = array())
{
  $all_sidebars = ww_get_all_sidebars();
  $new_order_array = array();
  $new_order_string = '';
  
  if (is_array($posted['weight']))
  {
    $i = 1;
    $total = count($posted['weight']);
    while($i <= $total)
    {
      $new_order_array[$posted['weight'][$i]] = $all_sidebars[$posted['weight'][$i]];
      $i++;
    }
    $new_order_string = serialize($new_order_array);
    
    update_option('ww_sidebars',$new_order_string);
  }
}
/*
 * Add a new sidebar
 */
function ww_sidebar_insert($posted = array())
{
  // just in case
  $new_sidebar = strip_tags($posted['ww-new-sidebar']);
  // clean name
  $slug_name = ww_make_slug($new_sidebar);
  
  if ($sidebars_string = get_option('ww_sidebars'))
  {
    $sidebars_array = unserialize($sidebars_string);
  }
  // add new sidebar
  $sidebars_array[$slug_name] = $new_sidebar;
  // encode
  $new_option = serialize($sidebars_array);
  // save
  update_option('ww_sidebars',$new_option);
}
/*
 * Delete a sidebar
 */
function ww_sidebar_delete($posted = array())
{
  $old_slug = $posted['ww-delete-slug'];
  
  if ($sidebars_string = get_option('ww_sidebars'))
  {
    $sidebars_array = unserialize($sidebars_string);
    unset($sidebars_array[$old_slug]);
    $new_option = serialize($sidebars_array);
  }
  else
  {
    $new_option = '';
  }
  update_option('ww_sidebars', $new_option);
}
/*
 * Update/Edit a sidebar
 */
function ww_sidebar_update($posted = array())
{
  $update_sidebar = strip_tags($posted['ww-update-sidebar']);
  $update_slug = ww_make_slug($posted['ww-update-slug']);
  $old_slug = $posted['ww-update-old-slug'];
  
  if ($sidebars_string = get_option('ww_sidebars'))
  {
    $sidebars_array = unserialize($sidebars_string);
    // delete old one
    unset($sidebars_array[$old_slug]);
    // add new one
    $sidebars_array[$update_slug] = $update_sidebar;
    // serialize
    $new_option = serialize($sidebars_array);
  }
  else
  {
    $new_option = '';
  }
  update_option('ww_sidebars', $new_option);
}
