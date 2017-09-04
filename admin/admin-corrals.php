<?php
// hook this addon in
add_filter( 'Widget_Wrangler_Admin_Addons', 'ww_corrals_admin_addon', 10, 2   );

//
function ww_corrals_admin_addon($addons, $settings){
  $addons['Corrals'] = WW_Corrals_Admin::register($settings);
  return $addons;
}

/**
 * Class WW_Corrals_Admin
 */
class WW_Corrals_Admin extends WidgetWranglerAdminPage {

	/**
	 * @see WidgetWranglerAdminPage::title()
	 */
    function title() {
        return __('Widget Corrals');
    }

	/**
	 * @see WidgetWranglerAdminPage::menuTitle()
	 */
    function menuTitle() {
	    return __('Corrals');
    }

	/**
	 * @see WidgetWranglerAdminPage::slug()
	 */
    function slug() {
        return 'corrals';
    }

	/**
	 * @see WidgetWranglerAdminPage::actions()
	 */
	function actions() {
        return array(
            'insert' => array( $this, 'actionInsert' ),
            'delete' => array( $this, 'actionDelete' ),
            'update' => array( $this, 'actionUpdate' ),
            'sort' => array( $this, 'actionReorder' ),
        );
    }

	/**
	 * @see WidgetWranglerAdminPage::enqueue()
	 */
	function enqueue() {
		if ( $this->onPage() ){
		    wp_enqueue_style('ww-admin');
			wp_enqueue_script('ww-corrals');
		}
	}

	/**
     * Create a new corral.
     *
	 * @return array
	 */
	function actionInsert() {
		if ( !empty( $_POST['ww-new-corral'] ) ) {
			WidgetWranglerCorrals::add( $_POST['ww-new-corral'] );

			return $this->result(__(sprintf('New corral %s created.', $_POST['ww-new-corral'])));
		}

		return $this->error();
	}

	/**
	 * Delete an existing corral.
	 *
	 * @return array
	 */
	function actionDelete() {
		if ( !empty( $_POST['ww-delete-slug'] ) ) {
			WidgetWranglerCorrals::remove( $_POST['ww-delete-slug'] );

			return $this->result(__(sprintf('Corral %s deleted.', $_POST['ww-delete-slug'])));
		}

		return $this->error();
	}

	/**
	 * Update an existing corral.
	 *
	 * @return array
	 */
	function actionUpdate() {
		if ( isset( $_POST['ww-update-old-slug'], $_POST['ww-update-corral'], $_POST['ww-update-slug'] ) ) {
			WidgetWranglerCorrals::update( $_POST['ww-update-old-slug'], $_POST['ww-update-slug'], $_POST['ww-update-corral'] );

			return $this->result(__(sprintf('New corral %s created.', $_POST['ww-new-corral'])));
		}

		return $this->error();
	}

	/**
	 * Reorder the corrals.
	 *
	 * @return array
	 */
	function actionReorder() {
		if ( isset( $_POST['weight'] ) && is_array( $_POST['weight'] ) ) {
			WidgetWranglerCorrals::reorder($_POST['weight']);

			return $this->result(__('Corrals have been reordered.'));
		}

		return $this->error();
	}
  
  /**
   * Build the various forms
   *
   * @see \WidgetWranglerAdminPage::page()
   */
  function page()
  {
	  $corrals = WidgetWranglerCorrals::all();
	  $sorting_items = '';
    ?>
      <p>
        <?php _e("A Corral is an arbitrary group of widgets. WordPress and previous Widget Wrangler versions call them 'sidebars', but they are ultimately not limited by that terminology.", 'widgetwrangler'); ?>
      </p>
      <p>
        <?php _e("In general, you probably want at least one corral per theme sidebar.", 'widgetwrangler'); ?>
      </p>
    <div id='ww-corral-page'>
      <div class="ww-setting-column">
        
        <div class="postbox">
          <h2 class="ww-setting-title"><?php _e("Edit existing Corrals", 'widgetwrangler'); ?></h2>
          <div class="ww-setting-content">
          <div class='description' style='color:red;'>
            <?php _e("Warning! If you change a corral's slug, widgets currently assigned to that corral will need to be reassigned.", 'widgetwrangler'); ?>
          </div>
          <ul id='ww-corrals-list'>
          <?php
            //  no corrals
            if (!is_array($corrals))
            { ?>
              <li><?php _e("No Corrals defined", 'widgetwrangler'); ?></li>
              <?php
            }
            // corrals
            else {
              // loop through each sidebar and build edit form
              $i = 1;
              foreach($corrals as $slug => $corral)
              { ?><li class='ww-corral-item ww-widgets-holder-wrap'>
                    <div class='widget widgets-holder-wrap closed'>
                      <div class='widget-top'>
                        <div class='widget-title-action sidebar-name'>
                          <div class='widget-action sidebar-name-arrow handlediv'></div>
                        </div>
                        <h4><?php print $corral; ?> (<?php print $slug; ?>)</h4>
                      </div>
                      <div class='widget-inside'>
                        <form action='edit.php?post_type=widget&page=corrals&ww_action=update&noheader=true' method='post'>
                          <p class="ww-top-right-save">
                            <input class='ww-corral-update-submit button button-primary button-large' name='ww-update-submit' type='submit' value='<?php _e("Update", 'widgetwrangler'); ?>' />
                          </p>
                          <p>
                            <label><?php _e("Name", 'widgetwrangler'); ?>: </label>
                            <input class='ww-text' name='ww-update-corral' type='text' value='<?php print $corral; ?>' />
                          </p>
                          <p>
                            <label><?php _e("Slug", 'widgetwrangler'); ?>: </label>
                            <input class='ww-text' name='ww-update-slug' type='text' value='<?php print $slug; ?>' />
                          </p>
                          <input name='ww-update-old-slug' type='hidden' value='<?php print $slug; ?>' />
                        </form>
                        <hr />
                        <form class='ww-delete-corral' action='edit.php?post_type=widget&page=corrals&ww_action=delete&noheader=true' method='post'>
                          <input name='ww-delete-slug' type='hidden' value='<?php print $slug; ?>' />
                          <p>
                            <input class='ww-setting-button-bad button button-small ww-delete-submit' name='ww-delete-submit' type='submit' value='<?php _e("Delete", 'widgetwrangler'); ?>' onclick="return confirm('<?php _e('Are you sure you want to delete this corral?', 'widgetwrangler'); ?>');" />
                          </p>
                        </form>
                        <div class="ww-clear-gone">&nbsp;</div>
                      </div>
                    </div>
                  </li>
                  <?php
                // sortable list
                $sorting_items.= "<li class='ww-corral-sort-item'>
                                    <strong>".$corral." (".$slug.")</strong>
                                    <input type='hidden' class='ww-corral-weight' name='weight[".$i."]' value='".$slug."' />
                                  </li>";
                $i++;
              }
              
            }
          ?>
          </ul>
          </div>
        </div>
      </div>
      <div class="ww-setting-column">
        <div class="postbox">
          <h2 class="ww-setting-title"><?php _e("Create New Corral", 'widgetwrangler'); ?></h2>
          <div class="ww-setting-content">
            <form action='edit.php?post_type=widget&page=corrals&ww_action=insert&noheader=true' method='post'>
              <p>
                <?php _e("Corral Name", 'widgetwrangler'); ?>: <input name='ww-new-corral' type='text' value='' />
              </p>
              <input class='button button-primary button-large' type='submit' value='<?php _e("Create Corral", 'widgetwrangler'); ?>' />
            </form>
          </div>
        </div>
        
        <div class="postbox">
          <h2 class="ww-setting-title"><?php _e("Sort Corrals", 'widgetwrangler'); ?></h2>
          <div class="ww-setting-content">
            <form action='edit.php?post_type=widget&page=corrals&ww_action=sort&noheader=true' method='post'>
              <ul id='ww-corrals-sort-list'>
                <?php print $sorting_items; ?>
              </ul>
              <input class='ww-sidebar-sort-submit button button-primary button-large' type='submit' name='ww-sidebars-save' value='<?php _e("Save Order", 'widgetwrangler'); ?>' />
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php
  }
    
}