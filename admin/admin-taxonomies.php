<?php
// hook this addon in
add_filter( 'Widget_Wrangler_Admin_Addons', 'ww_taxonomies_admin_addon', 10, 2 );

//
function ww_taxonomies_admin_addon($addons, $settings){
  $addons['Taxonomies'] = WW_Taxonomies_Admin::register($settings);
  return $addons;
}

/*
 *
 */
class WW_Taxonomies_Admin {

	public $settings = array();

	function __construct( $settings ){
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @param $settings
	 *
	 * @return \WW_Taxonomies_Admin
	 */
	public static function register( $settings ) {
		$plugin = new self($settings);

		add_action( 'admin_init', array( $plugin, 'init' ) );
		add_action( 'admin_menu', array( $plugin, 'menu' ) );

		return $plugin;
	}

  //
  function init(){
    // saving widgets 
    add_action( 'edited_term', array( $this, '_taxonomy_term_form_save' ) );
    
    // alter the form and add some ajax functionality
    add_filter( 'widget_wrangler_preset_ajax_op', array( $this, '_preset_ajax_op') );
    add_action( 'wp_ajax_ww_form_ajax', array( $this, 'ww_form_ajax' ) );
    add_action( 'widget_wrangler_form_meta' , array( $this, 'ww_form_meta' ) );
    
    if (isset($_GET['taxonomy'])){
      $taxonomy = $_GET['taxonomy'];
      
      // see if it's enabled for ww
      if (isset($this->settings['taxonomies']) && isset($this->settings['taxonomies'][$taxonomy]))
      {
        add_action('admin_enqueue_scripts', array( $this, 'enqueue' ));
        // editing a term
        if (isset($_GET['action']) && isset($_GET['tag_ID'])){
          // add our sortable widgets to term edit form
          add_action ( $taxonomy . '_edit_form_fields', array( $this, '_taxonomy_term_form'), 10, 2);
        }
        // taxonomy list table page
        else {
          add_action( "after-{$taxonomy}-table", array( $this, '_taxonomy_form' ) );
        }
      }
    }
  }

  function enqueue() {
	  wp_enqueue_style('ww-admin');
	  WW_Admin_Sortable::js();
  }

  //
  function menu(){
    // need a hook for saving taxonomy defaults
    add_submenu_page(null, 'title', 'title', Widget_Wrangler_Admin::$capability, 'ww_save_taxonomy_form', array( $this, 'route' ) );
  }
  
  //
  function route(){
    if (isset($_GET['page']) && $_GET['page'] == 'ww_save_taxonomy_form' && isset($_POST['taxonomy'])){
      $this->_save_taxonomy_form();
      wp_redirect($_SERVER['HTTP_REFERER']);
    }
  }
  
  //
  function ww_form_meta()
  {
    if (isset($_GET['tag_ID']))
    { ?>
      <input value="<?php print $_GET['tag_ID']; ?>" type="hidden" id="ww_ajax_context_id" />
      <?php
    }
    else if (isset($_GET['taxonomy']))
    { ?>
      <input value="<?php print $_GET['taxonomy']; ?>" type="hidden" id="ww_ajax_context_id" />
      <?php
    }
  }
  
  //
  // widget form on taxonomy (term list) screen
  // 
  function _taxonomy_form( $taxonomy ) {
    
    if (isset($this->settings['taxonomies'][$taxonomy]) &&
        $taxonomies = get_taxonomies(array('name' => $taxonomy), 'objects'))
    {
      $tax = array_pop($taxonomies);
      $override_default_checked = "";
      
      $where = array(
        'type' => 'taxonomy',
        'variety' => 'taxonomy',
        'extra_key' => $taxonomy,
      );
      
       // allow for presets
      if ($tax_data = WidgetWranglerExtras::get($where))
      {
        if ( isset( $tax_data->data['override_default'] ) ){
          $override_default_checked = 'checked="checked"';
        }
        
        if (isset($tax_data->data['preset_id']) && $tax_data->data['preset_id'] != 0) {
          $preset = WW_Presets::get($tax_data->data['preset_id']);
          $widgets = $preset->widgets;
        }
        else {
          $widgets = $tax_data->widgets;
        }
      }
      else {
        $preset = WW_Presets::getCore('default');
        $widgets = $preset->widgets;
      }
      
      $page_widgets = $widgets;

      if (isset($preset)){
        WW_Presets::$current_preset_id = $preset->id;
      }
      
      $form = array(
        'title' => __('Widget Wrangler', 'widgetwrangler'),
        'description' => __('Here you can override the default widgets for all terms in this taxonomy.', 'widgetwrangler'),
        'attributes' => array(
          'action' => Widget_Wrangler_Admin::$page_slug . '&page=ww_save_taxonomy_form&noheader=true',
          ),
        'submit_button' => array(
          'attributes' => array(
            'value' => __('Save Widgets', 'widgetwrangler'),
            ),
          ),
        );

      ob_start();
        ?>
        <input type="hidden" name="taxonomy" value="<?php print $taxonomy; ?>" />
        <div class="ww-box">
            <h3><?php _e('Widgets'); ?></h3>
          <div>
            <p>
              <label><input type="checkbox" name="override_default" <?php print $override_default_checked; ?> /> - <?php _e('Enable these widgets as the default widgets for terms in this taxonomy.', 'widgetwrangler'); ?></label>
            </p>
            <?php WW_Admin_Sortable::metaBox( $page_widgets ); ?>
          </div>
        </div>
        <?php
      $form['content'] = ob_get_clean();
      
      print WidgetWranglerAdminUi::form($form);
    }
  }

  //
  // save taxonomy widget data
  //
  function _save_taxonomy_form(){
    if (isset($_POST['taxonomy'])) {
      $additional_data = array();
      if (isset($_POST['override_default'])){
        $additional_data['override_default'] = 1;
      }
      $this->_update_posted_taxonomy_widgets('taxonomy', $_POST['taxonomy'], $additional_data);
    }
  }
  
  //
  // widget form on taxonomy_term edit screen
  // 
  function _taxonomy_term_form( $tag, $taxonomy ) {
    $settings = $this->settings;
    
    if (isset($settings['taxonomies'][$tag->taxonomy])){
      $where = array(
        'type' => 'taxonomy',
        'variety' => 'term',
        'extra_key' => $tag->term_id,
      );
      // allow for presets
      if ($term_data = WidgetWranglerExtras::get($where)){
        if (isset($term_data->data['preset_id']) && $term_data->data['preset_id'] != 0) {
          $preset = WW_Presets::get($term_data->data['preset_id']);
          $widgets = $preset->widgets;
        }
        else {
          $widgets = $term_data->widgets;
        }
      }
      else {
        $preset = WW_Presets::getCore('default');
        $widgets = $preset->widgets;
      }

      if (isset($preset)){
        WW_Presets::$current_preset_id = $preset->id;
      }

      ?>
      <tr class="form-field">
        <th scope="row" valign="top"><label><?php _e('Widget Wrangler', 'widgetwrangler'); ?></label><br/><em><small>(<?php _e('for this term only', 'widgetwrangler'); ?>)</small></em></th>
        <td>
          <div class="postbox">
            <div>
              <?php WW_Admin_Sortable::metaBox( $widgets ); ?>
            </div>
          </div>
        </td>
      </tr>
      <?php
    }
  }

  //
  // save taxonomy_term widget data
  //
  function _taxonomy_term_form_save( $term_id ) {
    if (isset($_POST['taxonomy']) &&
        isset($_POST['tag_ID']) &&
        is_numeric($_POST['tag_ID']) &&
        isset($_POST['ww-data']['widgets']))
    {
      $this->_update_posted_taxonomy_widgets('term', $_POST['tag_ID']);
    }
  }

  //
  //
  //
  function _update_posted_taxonomy_widgets($variety, $extra_key, $additional_data = array()){
    //
    $widgets = WidgetWranglerUtils::serializeWidgets($_POST['ww-data']['widgets']);

    // let presets addon do it's stuff
    $widgets = apply_filters('widget_wrangler_save_widgets_alter', $widgets);

    // get the new preset id, if set
    $new_preset_id = (WW_Presets::$new_preset_id !== FALSE) ? (int) WW_Presets::$new_preset_id : 0;

    $where = array(
      'type' => 'taxonomy',
      'variety' => $variety,
      'extra_key' => $extra_key,
    );

    $values = array(
      'type' => 'taxonomy',
      'variety' => $variety,
      'extra_key' => $extra_key,
      'data' => array('preset_id' => $new_preset_id),
      'widgets' => $widgets,
    );

    if (!empty($additional_data)){
      $values['data'] += $additional_data;
    }

    // doesn't exist, create it before update
    if (!WidgetWranglerExtras::get($where)){
      $values['data'] = serialize($values['data']);
      WidgetWranglerExtras::insert($values);
    }

    if ($widgets) {
      // no preset, save widgets
      $values['widgets'] = $widgets;

      // force the 'zero' preset because these widgets are custom
      $values['data']['preset_id'] = 0;
    }

    if ($new_preset_id) {
      // don't save widgets because they are preset widgets
      unset($values['widgets']);
    }

    $values['data'] = serialize($values['data']);
    WidgetWranglerExtras::update($values, $where);
  }

  // override the preset ajax op
  function _preset_ajax_op($op){
    if (isset($_GET['tag_ID']) || (isset($_POST['op']) && $_POST['op'] == 'replace_edit_taxonomy_term_widgets')) {
      $op = 'replace_edit_taxonomy_term_widgets';
    }
    else if (isset($_GET['taxonomy']) || (isset($_POST['op']) && $_POST['op'] == 'replace_edit_taxonomy_widgets')) {
      $op = 'replace_edit_taxonomy_widgets';
    }

    return $op;
  }

  //
  function ww_form_ajax(){
    if (isset($_POST['op'])) {
      if ($_POST['op'] == 'replace_edit_taxonomy_term_widgets'){
        // legit post ids only
        if (isset($_POST['context_id']) && is_numeric($_POST['context_id']) && $_POST['context_id'] > 0)
        {
          $tag_id = $_POST['context_id'];
          $preset_id = 0;

          if (isset($_POST['preset_id']) && is_numeric($_POST['preset_id'])){
            $preset_id = $_POST['preset_id'];
          }

          // if we changed to a preset, load those widgets
          if ($preset_id && $preset = WW_Presets::get($preset_id)){
            WW_Presets::$current_preset_id = $preset_id;
            $widgets = $preset->widgets;
          }
          // else, attempt to load tag widgets
          else {
            $where = array(
              'type' => 'taxonomy',
              'variety' => 'term',
              'extra_key' => $tag_id,
            );

            if ($term_data = WidgetWranglerExtras::get($where)){
              $widgets = $term_data->widgets;
            }
            else {
              $widgets = WW_Presets::getCore('default')->widgets;
            }
          }
          ob_start();
            WW_Admin_Sortable::metaBox( $widgets );
          $output = ob_get_clean();

          print $output;
        }
        exit;
      }
      else if ($_POST['op'] == 'replace_edit_taxonomy_widgets'){
        if (isset($_POST['context_id'])) {
          $taxonomy = $_POST['context_id'];
          $preset_id = 0;

          if (isset($_POST['preset_id']) && is_numeric($_POST['preset_id'])){
            $preset_id = $_POST['preset_id'];
          }

          // if we changed to a preset, load those widgets
          if ($preset_id && $preset = WW_Presets::get($preset_id)){
            WW_Presets::$current_preset_id = $preset_id;
            $widgets = $preset->widgets;
          }
          // else, attempt to load tag widgets
          else {
            $where = array(
              'type' => 'taxonomy',
              'variety' => 'taxonomy',
              'extra_key' => $taxonomy,
            );

            if ($term_data = WidgetWranglerExtras::get($where)){
              $widgets = $term_data->widgets;
            }
            else {
              $widgets = WW_Presets::getCore('default')->widgets;
            }
          }
          ob_start();
            WW_Admin_Sortable::metaBox( $widgets );
          $output = ob_get_clean();
  
          print $output;          
        }
        exit;
      }
    }
  }
    
}