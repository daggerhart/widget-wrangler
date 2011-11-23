<?php
/*
 * Available variables
 *
 * $sidebars = all sidebars
 */

$sidebars = unserialize(get_option('ww_sidebars'));
?>
<div class='wrap'>
  <h2>Widget Sidebars</h2>
  <p>A sidebar is a container for groups of widgets.  Like the pen I keep ma' cattle in.</p>
  <table id='ww-sidebar-page'>
  <td>
    <h2>Edit existing Sidebars</h2>
    <div class='description'>
      Warning! If you change a sidebar's 'slug', widgets currently assigned to that sidebar will need to be reassigned.
    </div>
    <ul id='ww-sidebars-list'>
      <?php
        if (is_array($sidebars))
        {
          // loop through each sidebar and build form
          foreach($sidebars as $slug => $sidebar)
          { ?>
              <li class='ww-sidebar-item'>
                <div class='widget'>
                  <div class='widget-top'>
                    <div class='widget-title-action'>
                      <div class='widget-action'></div>
                    </div>
                    <h4><?php print $sidebar." (".$slug.")"; ?></h4>
                  </div>
                  <div class='widget-inside'>
                    <form action='edit.php?post_type=widget&page=ww-sidebars&ww-sidebar-action=update&noheader=true' method='post'>
                      <label>Name: </label><input class='ww-text' name='ww-update-sidebar' type='text' value='<?php print $sidebar; ?>' /><br />
                      <label>Slug: </label><input class='ww-text' name='ww-update-slug' type='text' value='<?php print $slug; ?>' />
                      <input name='ww-update-old-slug' type='hidden' value='<?php print $slug; ?>' />
                      <input class='ww-sidebar-update-submit' name='ww-update-submit' type='submit' value='Update' />
                    </form>
                    <hr />
                    <form class='ww-delete-sidebar' action='edit.php?post_type=widget&page=ww-sidebars&ww-sidebar-action=delete&noheader=true' method='post'>
                      <input name='ww-delete-slug' type='hidden' value='<?php print $slug; ?>' />
                      <input class='ww-sidebar-delete-submit' name='ww-delete-submit' type='submit' value='Delete' />
                    </form>
                  </div>
                </div>
              </li>
            <?php
          }
        }                            
        else
        { ?>
          <li>No Sidebars defined</li>
          <?php
        }
      ?>
    </ul>
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
      <?php
        //loop
        if(is_array($sidebars))
        {
          $i = 1;
          foreach($sidebars as $slug => $sidebar)
          { ?>
            <li class='ww-sidebar-sort-item'>
              <strong><?php print $sidebar." (".$slug.")"; ?></strong>
              <input type='hidden' class='ww-sidebar-weight' name='weight[<?php print $i; ?>]' value='<?php print $slug; ?>' />
            </li>
            <?php
            $i++;
          }
        }
      ?>
      </ul>
      <input class='ww-sidebar-sort-submit button' type='submit' name='ww-sidebars-save' value='Save Order' />
    </form>
  </td>
  </table>
</div>