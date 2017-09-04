<?php
/*
 *
filters
 - widget_wrangler_preset_ajax_op
 
 */
// hook this addon in
add_filter( 'Widget_Wrangler_Admin_Addons', 'ww_presets_admin_addon', 10, 2 );

//
function ww_presets_admin_addon($addons, $settings){
  $addons['Presets_Admin'] = WW_Presets_Admin::register($settings);
  return $addons;
}

/**
 * Class WW_Presets_Admin
 */
class WW_Presets_Admin extends WidgetWranglerAdminPage {
  public $page_hook;
  public $current_preset_id = 0;
  public $new_preset_id = FALSE;

	/**
	 * @see WidgetWranglerAdminPage::title()
	 */
	function title() {
		return __('Pre-configured sets of Widgets assigned to Corrals');
	}

	/**
	 * @see WidgetWranglerAdminPage::menuTitle()
	 */
	function menuTitle() {
		return __('Presets');
	}

	/**
	 * @see WidgetWranglerAdminPage::slug()
	 */
	function slug() {
		return 'presets';
	}

	/**
	 * @see WidgetWranglerAdminPage::actions()
	 */
	function actions() {
		return array(
			'create' => array( $this, 'actionCreate' ),
            'update' => array( $this, 'actionUpdate' ),
            'delete' => array( $this, 'actionDelete' ),
		);
	}

	/**
	 * @param $settings
	 *
	 * @return \WidgetWranglerAdminPage
	 */
	public static function register( $settings ) {
		$plugin  = parent::register( $settings );

		add_action('admin_init', array( $plugin, 'init') );

		return $plugin;
	}

	/**
	 * Implements action 'admin_init'
	 */
	function init(){
		add_action( 'widget_wrangler_form_top', array( $this, 'ww_form_top' ));
		add_action( 'wp_ajax_ww_form_ajax', array( $this, 'ww_form_ajax' ) );
		add_action( 'widget_wrangler_save_widgets_alter', array( $this, 'ww_save_widgets_alter' ) );

		if ( isset($_GET['post_type']) && 'widget' == $_GET['post_type'] &&
		     isset($_GET['page']) && $_GET['page'] == 'presets' )
		{
			WW_Admin_Sortable::init();
		}
	}


	/**
	 * Delete a Widget Preset
	 */
	function actionDelete(){
		// can't delete defaults
		if (isset($_POST['preset-id']) && isset($_POST['preset-variety']) && $_POST['preset-variety'] != 'core'){
			WidgetWranglerExtras::delete(array(
				'type' => 'preset',
				'variety' => $_POST['preset-variety'],
				'id' => $_POST['preset-id'],
			));

			return $this->result(
			  __('Preset deleted.'),
				get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=presets'
            );
		}

		return $this->error();
	}

	/**
	 * Update a Widget Preset
	 */
	function actionUpdate(){
		if (isset($_POST['preset-id']) && isset($_POST['preset-variety']) && isset($_POST['data']) && isset($_POST['ww-data']['widgets']))
		{
			$submitted_widget_data = $_POST['ww-data']['widgets'];
			$active_widgets = WidgetWranglerUtils::serializeWidgets($submitted_widget_data);
//	    $varieties = WW_Presets::varieties();

			$preset_id = $_POST['preset-id'];
//      $preset_variety = $_POST['preset-variety'];
//	    $this_preset_variety = $varieties[$preset_variety];
			$preset_data = $_POST['data'];
			$preset_widgets = (!empty($active_widgets)) ? $active_widgets : serialize(array());

			// update the ww_presets db
			$data = array(
				'data' => serialize($preset_data),
				'widgets' => $preset_widgets,
			);

			$where = array(
				'id' => $preset_id,
				'type' => 'preset',
			);

			// save the widgets
			WidgetWranglerExtras::update($data, $where);

			return $this->result(__('Preset updated.'));
		}

		return $this->error();
	}

	/**
	 * Create a new Widget Preset
	 */
	function actionCreate() {
		if (isset($_POST['create']['variety'])) {
			switch($_POST['create']['variety']) {
				case 'standard':
					$data = array(
						'type' => 'preset',
						'variety' => WidgetWranglerUtils::makeSlug( $_POST['create']['variety'] ),
						'data' => serialize(array('name' => ucfirst($_POST['create']['variety']))),
						'widgets' => serialize(array()),
					);

					$new_preset_id = WidgetWranglerExtras::insert($data);

					return $this->result(
                        __('New Preset Created.'),
						get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=presets&preset_id='.$new_preset_id
                    );
					break;
			}
		}

		return $this->error();
	}
  
  //
  // Ajax form templates
  //
  function ww_form_ajax() {
    if (isset($_POST['op']) && $_POST['op'] == 'replace_edit_page_widgets'){
      // legit post ids only
      if (isset($_POST['context_id']) && is_numeric($_POST['context_id']) && $_POST['context_id'] > 0)
      {
        $post_id = $_POST['context_id'];
        $preset_id = 0;
        
        if (isset($_POST['preset_id']) && is_numeric($_POST['preset_id'])){
          $preset_id = $_POST['preset_id'];
        }
        
        // if we changed to a preset, load those widgets
        if ($preset_id && $preset = WW_Presets::get($preset_id)){
          WW_Presets::$current_preset_id = $preset_id;
          $widgets = $preset->widgets;
        }
        // else, attempt to load page widgets
        else {
          $page_widgets = get_post_meta($post_id, 'ww_post_widgets', TRUE);
          $widgets = ($page_widgets != '') ? maybe_unserialize($page_widgets) : array();
        }
        
        ob_start();
          WW_Admin_Sortable::metaBox( $widgets );
        $output = ob_get_clean();

        print $output;
      }
      exit;
    }
  }

	//
	function ww_save_widgets_alter($widgets){
		// '0' handles itself
		$new_preset_id = (isset($_POST['ww-post-preset-id-new'])) ? (int)$_POST['ww-post-preset-id-new'] : 1;
		$new_preset_widgets = NULL;

		// attempt to load that new preset
		if ($new_preset_id && $new_preset = WW_Presets::get($new_preset_id)){
			$new_preset_widgets = serialize($new_preset->widgets);
		}

		// if the widgets to be saved are not the same as the selected preset's widgets
		//   then the user loaded a preset, and changed some widgets
		if ($widgets != $new_preset_widgets){
			// force the 'zero' preset because these widgets are custom
			$new_preset_id = 0;
		}

		// if new_preset_id is not zero, then a preset was selected and we don't want to save widgets
		if ($new_preset_id !== 0){
			$widgets = false;
		}
		// set the new preset id for other plugins or addons to use
		WW_Presets::$new_preset_id = $new_preset_id;

		return $widgets;
	}

	// action for widget_wrangler_form_top
  function ww_form_top()
  {
    $preset_ajax_op = 'replace_edit_page_widgets';
    // allow other addons to manage their own ajax
    $preset_ajax_op = apply_filters('widget_wrangler_preset_ajax_op', $preset_ajax_op);
    
    $all_presets = WW_Presets::all();
    $current_preset_id = WW_Presets::$current_preset_id;
    $current_preset = NULL;
    $current_preset_message = "No preset selected. This page is wrangling widgets on its own.";
    
    // we have a preset to load
    if ($current_preset_id && $current_preset = WW_Presets::get($current_preset_id)){
      $current_preset_message = "This page is currently using the <a href='".Widget_Wrangler_Admin::$page_slug."&page=presets&preset_id=".$current_preset->id."'>".$current_preset->data['name']."</a>  Widgets.";
    }

    ?>
    <div id='ww-post-preset'>
      <input type="hidden" name="widget_wrangler_preset_ajax_op" id="widget_wrangler_preset_ajax_op" value="<?php print $preset_ajax_op; ?>" />
      <span id="ww-post-preset-message"><?php print $current_preset_message; ?></span>
      <div class='ww-presets'>
        <span><?php _e("Widget Preset", 'widgetwrangler'); ?>:</span>
        
        <select id='ww_post_preset' name='ww-post-preset-id-new'>
          <option value='0'><?php _e("- No Preset -", 'widgetwrangler'); ?></option>
          <?php
            // create options
            foreach($all_presets as $preset)
            {
              $selected = ($preset->id == $current_preset_id) ? "selected='selected'" : '';
              ?>
              <option value='<?php print $preset->id; ?>' <?php print $selected;?>><?php print $preset->data['name']; ?></option>
              <?php
            }
          ?>
        </select>
        <span class="ajax-working spinner">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        
        <p class='description'>
          <span><?php _e("Select the Widget Preset you would like to control widgets on this page. To allow this page to control its own widgets, select '- No Preset -'. If you select a preset and then rearrange widgets, this page will convert itself to '- No Preset -' on save.", 'widgetwrangler'); ?></span>
        </p>
      </div>
    </div>        
      
    <?php
  }
  
  /*
   * Admin Manage presets form
   */
  function page() {
    // prepare preset data  
    $preset_id = 1;
    
    if(isset($_GET['preset_id'])) {
      if (is_numeric($_GET['preset_id']) && $_GET['preset_id'] > 1){
        $preset_id = $_GET['preset_id'];
      }
    }
    
    $all_presets = WW_Presets::all();
    $this_preset = WW_Presets::get($preset_id);
    $varieties  = WW_Presets::varieties();
    $preset_variety  = $varieties[$this_preset->variety];


	  // themes draggable widgets
	  $sortable = new WW_Admin_Sortable();

	  // need to remove the normal preset form_top. can't select preset for preset
	  remove_action( 'widget_wrangler_form_top', array( $this, 'ww_form_top' ));

	  ob_start();
    ?>
    <div class='wrap'>
        <div class="ww-admin-top">
          <h2><?php _e("Edit Preset", 'widgetwrangler'); ?> <em><?php print $this_preset->data['name']; ?></em></h2>
        </div>
  
        <div id="presets-wrap">	
          <div class="ww-admin-tab-links">
            <ul class="ww-admin-tab-list">
              <li class="ww-admin-tab-list-title"><h2 class="ww-setting-title">Presets</h2></li>
              <?php
                // show all presets, core first, then others
                if(is_array($all_presets)){
                  foreach($all_presets as $preset)
                  {
                    $classes = ($preset_id == $preset->id) ? 'active' : '';
                    ?>
                    <li class="<?php print $classes; ?>">
                      <a href="edit.php?post_type=widget&page=presets&preset_id=<?php print $preset->id; ?>"><?php print $preset->data['name']; ?><?php print ($preset->variety == 'core') ? ' <small>('.$preset->variety.')</small>' : ''; ?></a>
                    </li>
                    <?php
                  }
                }
              ?>
            </ul>

            <div id="presets-add-new">
              <h2 class="ww-setting-title">Create New</h2>
              <div class="ww-setting-content">
              <form action='edit.php?post_type=widget&page=presets&ww_action=create&noheader=true' method='post' name='widget-wrangler-form'>
                <p>
                  <select name="create[variety]">
                  <?php
                    foreach($varieties as $key => $pstype)
                    {
                      if ($key != 'core')
                      { ?>
                        <option value="<?php print $key; ?>"><?php print $pstype['title']; ?></option>
                        <?php
                      }
                    }
                  ?>
                  </select>
                </p>
                <p>
                  <input type="submit" value="<?php _e("Create New Preset", 'widgetwrangler'); ?>" class="button button-primary button-large" />
                </p>
              </form>
              </div>
            </div>
          </div>
        
          <div class="ww-admin-tab postbox">

              <?php
              // can't change the names of core presets
              if($preset_variety['slug'] == 'core')
              { ?>
                <h2 class="ww-setting-title"><?php print $this_preset->data['name']; ?></h2>
                <input type="hidden" name="data[name]" value="<?php print $this_preset->data['name']; ?>" />
                <?php
              }
              else
              { ?>			
                <div id="preset-data">
                  <div id="preset-name">
                    <div class="detail">
                      <label><?php _e("Name:", 'widgetwrangler'); ?></label>
                      <input size="40" type="text" name="data[name]" value="<?php print $this_preset->data['name']; ?>" />
                    </div>
                    <div class="detail">
                      <label><?php _e("Type:", 'widgetwrangler'); ?></label> <?php print $preset_variety['slug']; ?>
                    </div>         
                  </div>
                </div>

                  <form action='edit.php?post_type=widget&page=presets&ww_action=delete&noheader=true' method='post' name='widget-wrangler-form'>
                      <input class='button' type='submit' value='<?php _e("Delete", 'widgetwrangler'); ?>' />
                      <input type="hidden" name="preset-id" value="<?php print $preset_id; ?>" />
                      <input type="hidden" name="preset-variety" value="<?php print $preset_variety['slug']; ?>" />
                  </form>
                <?php
              }
            ?>
          <form action='edit.php?post_type=widget&page=presets&ww_action=update&noheader=true' method='post' name='widget-wrangler-form'>

              <input type='submit' value='Save Preset' class='button button-primary button-large' />
              <input type='hidden' name='widget-wrangler-edit' value='true' />
              <input type='hidden' name='ww_noncename' id='ww_noncename' value='<?php print wp_create_nonce( plugin_basename(__FILE__) ); ?>' />
              <input type="hidden" name="preset-id" value="<?php print $preset_id; ?>" />
              <input type="hidden" name="preset-variety" value="<?php print $preset_variety['slug']; ?>" />
            
            <div class="ww-admin-tab-inner">

			<div id="widget_wrangler_form_top">
				<?php
				// TODO, dry this action up
				do_action('widget_wrangler_form_top');
				?>
			</div>
			<div id='ww-post-edit-message'>* <?php _e("Widget changes will not be updated until you save.", 'widgetwrangler'); ?>"</div>

              <div id="preset-widgets">
                  <?php print $sortable->theme_sortable_corrals( $this_preset->widgets ); ?>
              </div>
            </div>
          </form>
          </div>
        </div>
        <div class="ww-clear-gone">&nbsp;</div>
    </div>
    <?php
  }

}