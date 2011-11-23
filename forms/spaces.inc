<?php
/*
 * Variables for 'This' space
 * $space_id
 * $space_data
 * $space_widgets
 * $space_type
 *
 * Other
 * $all_sidebars
 * $all_widgets
 * $all_spaces
 */
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
          <?php
            // show all spaces
            if(is_array($all_spaces)){
              foreach($all_spaces as $space)
              {
                $this_data = unserialize($space->data);
                $classes = ($space_id == $space->id) ? 'active' : '';
                ?>
                <div class="space-list-item <?php print $classes; ?>">
                  <a href="edit.php?post_type=widget&page=ww-spaces&space_id=<?php print $space->id; ?>"><?php print $this_data['name']; ?></a>
                </div>
                <?php
              }
            }
          ?>
        </div>
        
        <div id="spaces-add-new">
          <form action='edit.php?post_type=widget&page=ww-spaces&action=create&noheader=true' method='post' name='widget-wrangler-form'>
            <select name="type">
              <option value="category">Category</option>
            </select>
            <br />
            <input type="submit" name="action-create" class="button" value="Create New" />
          </form>
        </div>
      </div>
    
      <div id="space-details">
        <form action='edit.php?post_type=widget&page=ww-spaces&action=update&noheader=true' method='post' name='widget-wrangler-form'>
          <div id="space-data">
            <input class='button' name='action-save' type='submit' value='Save' />
            
            <?php if($space_type != 'default'){ ?>
              <input class='button' name='action-delete' type='submit' value='Delete' />
            <?php } ?>
            
            <input value='true' type='hidden' name='widget-wrangler-edit' />
            <input type='hidden' name='ww_noncename' id='ww_noncename' value='<?php print wp_create_nonce( plugin_basename(__FILE__) ); ?>' />
            <br />
            Name:
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
                    <input type="text" name="data[name]" value="<?php print $space_data['name']; ?>" />
                  <?php
                }
              ?>
            <input type="hidden" name="space-id" value="<?php print $space_id; ?>" />
            <input type="hidden" name="space-type" value="<?php print $space_type; ?>" />
          </div>
          
          <?php
            // categories
            if($space_type == 'category')
            {
              // categories
              $category_ids = get_all_category_ids();
              ?>
                <div id="space-categories">
                <p>Select which categories to use these widgets on.</p>
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
              <?php

              // tags
              /*
              $tags = get_tags(array('hide_empty' => false));
              ?>
                <div id="space-tags">
                <p>Select which tags to use these widgets on.</p>
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
              <?php
              */
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