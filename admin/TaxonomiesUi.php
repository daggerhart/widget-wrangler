<?php

namespace WidgetWrangler;

/**
 * Class TaxonomiesUi
 * @package WidgetWrangler
 */
class TaxonomiesUi {

	public $settings = array();

	function __construct( $settings ){
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @param $settings
	 *
	 * @return \WidgetWrangler\TaxonomiesUi
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
	  wp_enqueue_script('ww-admin');

	  SortableWidgetsUi::js();
  }

  //
  function menu(){
    // need a hook for saving taxonomy defaults
    add_submenu_page(null, 'title', 'title', 'manage_options', 'ww_save_taxonomy_form', array( $this, 'route' ) );
  }
  
  //
  function route(){
    if (isset($_GET['page']) && $_GET['page'] == 'ww_save_taxonomy_form' && isset($_POST['taxonomy'])){
      $this->_save_taxonomy_form();
      wp_redirect($_SERVER['HTTP_REFERER']);
    }
  }


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

	/**
     * Widget form on taxonomy (term list) screen
     *
	 * @param $taxonomy
	 */
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
			if ($tax_data = Extras::get($where))
			{
				if ( isset( $tax_data->data['override_default'] ) ){
					$override_default_checked = 'checked="checked"';
				}

				if (isset($tax_data->data['preset_id']) && $tax_data->data['preset_id'] != 0) {
					$preset = Presets::get($tax_data->data['preset_id']);
					$widgets = $preset->widgets;
				}
				else {
					$widgets = $tax_data->widgets;
				}
			}
			else {
				$preset = Presets::getCore('default');
				$widgets = $preset->widgets;
			}

			$page_widgets = $widgets;

			if (isset($preset)){
				Presets::$current_preset_id = $preset->id;
			}

			ob_start();
			SortableWidgetsUi::metaBox( $page_widgets );
			$sortable_widgets = ob_get_clean();

			$form = new Form(array(
				'action' => 'edit.php?post_type=widget&page=ww_save_taxonomy_form&noheader=true',
				'style' => 'box',
				'fields' => array(
					'opening' => array(
						'type' => 'markup',
						'title' => __('Widget Wrangler'),
						'description' => __('Here you can override the default widgets for all terms in this taxonomy.', 'widgetwrangler'),
					),
					'taxonomy' => array(
						'type' => 'hidden',
						'value' => $taxonomy,
					),
					'override_default' => array(
						'type' => 'checkbox',
						'title' => __('Set as taxonomy default widgets'),
						'help' => __('Enable these widgets as the default widgets for terms in this taxonomy.'),
					),
					'save_widgets' => array(
						'type' => 'submit',
						'value' => __('Save Widgets', 'widgetwrangler'),
						'class' => 'button button-large button-primary',
					),
					'widgets' => array(
						'type' => 'markup',
						'value' => $sortable_widgets,
						'title' => __('Widgets'),
					),
				)
			));

			print $form->render();
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
      if ($term_data = Extras::get($where)){
        if (isset($term_data->data['preset_id']) && $term_data->data['preset_id'] != 0) {
          $preset = Presets::get($term_data->data['preset_id']);
          $widgets = $preset->widgets;
        }
        else {
          $widgets = $term_data->widgets;
        }
      }
      else {
        $preset = Presets::getCore('default');
        $widgets = $preset->widgets;
      }

      if (isset($preset)){
        Presets::$current_preset_id = $preset->id;
      }

      ?>
      <tr class="form-field">
        <th scope="row" valign="top"><label><?php _e('Widget Wrangler', 'widgetwrangler'); ?></label><br/><em><small>(<?php _e('for this term only', 'widgetwrangler'); ?>)</small></em></th>
        <td>
          <div class="postbox">
            <div>
              <?php SortableWidgetsUi::metaBox( $widgets ); ?>
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
    $widgets = Utils::serializeWidgets($_POST['ww-data']['widgets']);

    // let presets addon do it's stuff
    $widgets = apply_filters('widget_wrangler_save_widgets_alter', $widgets);

    // get the new preset id, if set
    $new_preset_id = (Presets::$new_preset_id !== FALSE) ? (int) Presets::$new_preset_id : 0;

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
    if (!Extras::get($where)){
      $values['data'] = serialize($values['data']);
      Extras::insert($values);
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
    Extras::update($values, $where);
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
          if ($preset_id && $preset = Presets::get($preset_id)){
            Presets::$current_preset_id = $preset_id;
            $widgets = $preset->widgets;
          }
          // else, attempt to load tag widgets
          else {
            $where = array(
              'type' => 'taxonomy',
              'variety' => 'term',
              'extra_key' => $tag_id,
            );

            if ($term_data = Extras::get($where)){
              $widgets = $term_data->widgets;
            }
            else {
              $widgets = Presets::getCore('default')->widgets;
            }
          }
          ob_start();
            SortableWidgetsUi::metaBox( $widgets );
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
          if ($preset_id && $preset = Presets::get($preset_id)){
            Presets::$current_preset_id = $preset_id;
            $widgets = $preset->widgets;
          }
          // else, attempt to load tag widgets
          else {
            $where = array(
              'type' => 'taxonomy',
              'variety' => 'taxonomy',
              'extra_key' => $taxonomy,
            );

            if ($term_data = Extras::get($where)){
              $widgets = $term_data->widgets;
            }
            else {
              $widgets = Presets::getCore('default')->widgets;
            }
          }
          ob_start();
            SortableWidgetsUi::metaBox( $widgets );
          $output = ob_get_clean();
  
          print $output;          
        }
        exit;
      }
    }
  }
    
}
