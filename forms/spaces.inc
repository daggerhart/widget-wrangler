<?php
/*
 * Variables for 'This' space
 * -----
 * $space_id
 * $space_data
 * $space_widgets
 * $space_type
 *
 * Other variables
 * -----
 * $all_sidebars
 * $all_widgets
 * $all_spaces
 */

// prepare space data  
if(!$_GET['space_id'] || !is_numeric($_GET['space_id'])){
  $space_id = 1;
} else {
  $space_id = $_GET['space_id'];
}
$this_space     = ww_get_space($space_id);
$space_type     = $this_space->type;
$space_data     = unserialize($this_space->data);
$space_widgets  = unserialize($this_space->widgets);
$all_spaces     = ww_get_all_spaces();

$sortable_widgets = array();
$all_widgets = ww_get_all_widgets();
$all_sidebars = ww_get_all_sidebars();  

// add additional sidebars to output
if (is_array($all_widgets) && count($all_sidebars) > 0){
  $sortable_widgets = ww_create_sortable_widgets($all_widgets, $space_widgets, $all_sidebars);
}

// sort each sidebar
if(is_array($sortable_widgets['active'])){
  foreach($sortable_widgets['active'] as $sidebar => $unsorted_widgets){
    ksort($sortable_widgets['active'][$sidebar]);  
  }
}
?>
  <div class="wrap">
    <h2>Edit Widget Space <em><?php print $space_data['name']; ?></em></h2>
    <div id="spaces-wrap">
      
      <div id="spaces-details">
        <?php print $type; ?>
        <br />
      
      </div>
      
      <div id="spaces-sidebar">
        <div id="spaces-list">
          <h4>Widget Spaces</h4>
          <?php
            // show all spaces
            if(is_array($all_spaces)){
              foreach($all_spaces as $space)
              {
                $this_data = unserialize($space->data);
                $classes = ($space_id == $space->id) ? 'active' : '';
                ?>
                <div class="space-list-item <?php print $classes; ?>">
                  <a href="edit.php?post_type=widget&page=ww-spaces&space_id=<?php print $space->id; ?>"><?php print $this_data['name']; ?><?php print ($space->type != 'default') ? ' ('.$space->type.')' : ''; ?></a>
                </div>
                <?php
              }
            }
          ?>
        </div>
        
        <div id="spaces-add-new">
          <form action='edit.php?post_type=widget&page=ww-spaces&action=create&noheader=true' method='post' name='widget-wrangler-form'>
            <select name="type">
              <option value="term">Term (Cats & Tags)</option>
            </select>
            <br />
            <input type="submit" name="action-create" class="button" value="Create New Space" />
          </form>
        </div>
      </div>
    
      <div id="space-details">
        <form action='edit.php?post_type=widget&page=ww-spaces&action=update&noheader=true' method='post' name='widget-wrangler-form'>
          <div id="space-data">
            <div id="space-action">
              <input class='button' name='action-save' type='submit' value='Save' />
              
              <?php if($space_type != 'default'){ ?>
                <input class='button' name='action-delete' type='submit' value='Delete' />
              <?php } ?>
              
              <input value='true' type='hidden' name='widget-wrangler-edit' />
              <input type='hidden' name='ww_noncename' id='ww_noncename' value='<?php print wp_create_nonce( plugin_basename(__FILE__) ); ?>' />
            </div>
            <div id="space-name">
              <div class="detail">
                <label>Name:</label>
                  <?php
                    // can't change the names of defaults
                    if($space_type == 'default')
                    {
                      print $space_data['name'];
                      ?>
                        <input type="hidden" name="data[name]" value="<?php print $space_data['name']; ?>" />
                      <?php
                    }
                    else
                    { ?>
                        <input size="40" type="text" name="data[name]" value="<?php print $space_data['name']; ?>" />
                      <?php
                    }
                  ?>
              </div>
              <div class="detail">
                <label>Type:</label> <?php print $space_type; ?>
              </div>
              <input type="hidden" name="space-id" value="<?php print $space_id; ?>" />
              <input type="hidden" name="space-type" value="<?php print $space_type; ?>" />          
            </div>
          </div>
          <?php
            // categories
            if($space_type == 'term')
            { ?>
              <div id="space-terms">
                <div class="description">
                  <em>If you select a category or tag for multiple widget spaces, results will conflict.</em>
                </div>
                <?php
                  // categories
                  $category_ids = get_all_category_ids();
                  ?>
                    <div id="space-categories">
                      <p><strong>Select which category pages to display these widgets on.</strong></p>
                      <div class="space-checkboxes">
                        <?php
                          // List all categories as checkboxes
                          foreach($category_ids as $cat_id)
                          {
                            $cat_name = get_cat_name($cat_id);
                            $cat_checked = (isset($space_data['categories'][$cat_id])) ? 'checked="checked"' : '';
                            ?>
                            <label class="ww-space-checkbox"><input type="checkbox" name="data[categories][<?php print $cat_id; ?>]" value="<?php print $cat_name; ?>" <?php print $cat_checked; ?> /><?php print $cat_name; ?></label> 
                            <?php
                          }
                        ?>                
                      </div>
                    </div>
                  <?php
  
                  // tags
                  $tags = get_tags(array('hide_empty' => false));
                  ?>
                    <div id="space-tags">
                      <p><strong>Select which tag pages to display these widgets on.</strong></p>
                      <div class="space-checkboxes">
                        <?php
                          // List all categories as checkboxes
                          foreach($tags as $tag)
                          {
                            $tag_checked = (isset($space_data['categories'][$tag->term_id])) ? 'checked="checked"' : '';
                            ?>
                            <label class="ww-space-checkbox"><input type="checkbox" name="data[categories][<?php print $tag->term_id; ?>]" value="<?php print $tag->name; ?>" <?php print $tag_checked; ?> /><?php print $tag->name; ?></label> 
                            <?php
                          }
                        ?>                
                      </div>
                    </div>
                </div>
              <?php
            }
          ?>
          
          <div id="space-widgets">
            
            <?php
              // themes draggable widgets
              ww_theme_sortable_widgets($sortable_widgets);
            ?>
            
          </div>
        </form>
      </div>
      
    </div>
  </div>