<?php
/*
 * Build the form 
 */
function ww_sidebars_form()
{
  $sidebars = unserialize(get_option('ww_sidebars'));
  $sorting_items = '';
  $output.= " <div class='wrap'>
                <h2>Widget Sidebars</h2>
                <p>A sidebar is a container for groups of widgets.  Like the pen I keep ma' cattle in.</p>
                <table id='ww-sidebar-page'>
                <td>
                  <h2>Edit existing Sidebars</h2>
                  <div class='description'>
                    Warning! If you change a sidebar's 'slug', widgets currently assigned to that sidebar will need to be reassigned.
                    </div>
                  <ul id='ww-sidebars-list'>";
  if (is_array($sidebars))
  {
    // loop through each sidebar and build form
    $i = 1;
    foreach($sidebars as $slug => $sidebar)
    {
      $output.= "<li class='ww-sidebar-item'>
                  <div class='widget'>
                    <div class='widget-top'>
                      <div class='widget-title-action'>
                        <div class='widget-action'></div>
                      </div>
                      <h4>".$sidebar." (".$slug.")</h4>
                    </div>
                    <div class='widget-inside'>
                      <form action='edit.php?post_type=widget&page=ww-sidebars&ww-sidebar-action=update&noheader=true' method='post'>
                        <label>Name: </label><input class='ww-text' name='ww-update-sidebar' type='text' value='".$sidebar."' /><br />
                        <label>Slug: </label><input class='ww-text' name='ww-update-slug' type='text' value='".$slug."' />
                        <input name='ww-update-old-slug' type='hidden' value='".$slug."' />
                        <input class='ww-sidebar-update-submit' name='ww-update-submit' type='submit' value='Update' />
                      </form>
                      <hr />
                      <form class='ww-delete-sidebar' action='edit.php?post_type=widget&page=ww-sidebars&ww-sidebar-action=delete&noheader=true' method='post'>
                        <input name='ww-delete-slug' type='hidden' value='".$slug."' />
                        <input class='ww-sidebar-delete-submit' name='ww-delete-submit' type='submit' value='Delete' />
                      </form>
                    </div>
                  </div>
                 </li>";
                 
      $sorting_items.= "<li class='ww-sidebar-sort-item'>
                          <strong>".$sidebar." (".$slug.")</strong>
                          <input type='hidden' class='ww-sidebar-weight' name='weight[".$i."]' value='".$slug."' />
                        </li>";
      $i++;
    }
  }
  else
  {
    $output.= "<li>No Sidebars defined</li>";
  }
  
  $output.=  "</ul>
              </td>
              <td>
                <h2>Create New Sidebar</h2>
                <form action='edit.php?post_type=widget&page=ww-sidebars&ww-sidebar-action=insert&noheader=true' method='post'>
                  Sidebar Name: <br />
                  <input name='ww-new-sidebar' type='text' value='' />
                  <input class='button' type='submit' value='Create Sidebar' />
                </form>
                <h2>Sort your Sidebars</h2>
                <form action='edit.php?post_type=widget&page=ww-sidebars&ww-sidebar-action=sort&noheader=true' method='post'>
                  <ul id='ww-sidebars-sort'>
                  ".$sorting_items."
                  </ul>
                  <input class='ww-sidebar-sort-submit button' type='submit' name='ww-sidebars-save' value='Save Order' />
                </form>
              </td>
              </table>
            </div>";
  
  print $output;
}
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
