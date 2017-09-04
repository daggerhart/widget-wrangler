<?php
// hook this addon in
add_filter( 'Widget_Wrangler_Admin_Addons', 'ww_corrals_admin_addon', 10, 2   );

//
function ww_corrals_admin_addon($addons, $settings){
  $addons['Corrals'] = WW_Corrals_Admin::register($settings);
  return $addons;
}

/*
 *
 */
class WW_Corrals_Admin  {
  public $page_hook;

	public $settings = array();

	function __construct($settings){
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @param $settings
	 *
	 * @return \WW_Corrals_Admin
	 */
	public static function register( $settings ) {
		$plugin = new self($settings);

		add_action( 'admin_menu', array( $plugin, 'wp_admin_menu' ) );

		return $plugin;
	}


	function wp_admin_menu(){
    $page_title = 'Corrals';

    $this->page_hook = add_submenu_page(Widget_Wrangler_Admin::$page_slug, $page_title, $page_title, Widget_Wrangler_Admin::$capability, 'corrals', array( $this, '_menu_router' ));
    add_action( "admin_print_scripts-".$this->page_hook, array( $this, '_corrals_form_js' ) );
    add_action( "admin_head", 'WidgetWranglerAdminUi::css' );
  }

  /*
   * Handles settings pages
   *   - all settings pages submit to this execute_action
   */
  function _menu_router(){
    if (isset($_GET['ww_action'])){
      switch($_GET['ww_action']){
        case 'insert':
          if ( !empty( $_POST['ww-new-corral'] ) ) {
              WidgetWranglerCorrals::add( $_POST['ww-new-corral'] );
          }
          break;

        case 'delete':
            if ( !empty( $_POST['ww-delete-slug'] ) ) {
                WidgetWranglerCorrals::remove( $_POST['ww-delete-slug'] );
            }
          break;

        case 'update':
	        if ( isset( $_POST['ww-update-old-slug'], $_POST['ww-update-corral'], $_POST['ww-update-slug'] ) ) {
		        WidgetWranglerCorrals::update( $_POST['ww-update-old-slug'], $_POST['ww-update-slug'], $_POST['ww-update-corral'] );
	        }
          break;

        case 'sort':
            if ( is_array( $_POST['weight'] ) ) {
	            WidgetWranglerCorrals::reorder($_POST['weight']);
            }
          break;
      }
      
      wp_redirect($_SERVER['HTTP_REFERER']);
      exit;
    }
    else {
      $this->_corrals_form();
    }
  }
  
  /*
   * Build the form 
   */
  function _corrals_form()
  {
    $corrals = WidgetWranglerCorrals::all();
    $sorting_items = '';
    ?>
    <div class='wrap'>
      <h2><?php _e("Widget Corrals", 'widgetwrangler'); ?></h2>
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
    </div>
    <?php
  }

  //
  // Javascript for drag and drop sidebar sorting
  //
  function _corrals_form_js(){
    wp_enqueue_script('ww-corrals-js',
                      plugins_url('js/corrals.js', __FILE__ ),
                      array('jquery-ui-core', 'jquery-ui-sortable'),
                      false);
  }
    
}