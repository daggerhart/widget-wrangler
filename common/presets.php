<?php

/*
 * Presets are a mechanism for using pre-configured groups of widgets throughout
 *  a WordPress site.
 *
 * Preset varieties are different types of presets.
 *
 * New WordPress filters
 *  - widget_wrangler_preset_varieties
 */
class WW_Presets {

	/**
	 * Preset found for this page context
	 *
	 * @var int
	 */
	public static $current_preset_id = 0;

	/**
	 * Id for new Preset being created through admin UI
	 *
	 * @var int
	 */
	public static $new_preset_id = 0;

	/**
	 * Register hooks
	 *
	 * @return \WW_Presets
	 */
	public static function register() {
		$plugin = new self();
		
		// wp hooks
		add_action( 'admin_init', array( $plugin, 'wp_admin_init' ) );

		// ww hooks

		// early, so it will prevent the singular filter
		// presets override the ww_posts_widgets
		add_filter( 'widget_wrangler_find_all_page_widgets', array( $plugin, 'ww_find_standard_preset_widgets' ), 0 );
		// last, to ensure that nothing else was found
		add_filter( 'widget_wrangler_find_all_page_widgets', array( $plugin, 'ww_find_core_preset_widgets' ), 999 );

		return $plugin;
	}

  
  /*
   * WordPress hook admin_init
   *  Ensure default presets are installed
   */
  function wp_admin_init(){
    // make sure our core presets are installed
    if (!self::getCore('default')){
      WidgetWranglerExtras::ensureTable();
      $this->_install_core_presets();
    }
  }

	/**
	 * Get all Presets
	 *
	 * @return array All widget presets found in the DB
	 */
	public static function all(){
		$where = array(
			'type' => 'preset',
		);
		return WidgetWranglerExtras::get($where, 'all');
	}

	/**
	 * Get a single Preset
	 *
	 * @param int $preset_id the id for the Preset
	 *
	 * @return array the Preset details
	 */
	public static function get($preset_id) {
		$where = array(
			'id' => $preset_id,
			'type' => 'preset',
		);

		return WidgetWranglerExtras::get($where);
	}

	/**
	 * Get a preset of the 'core' variety using its key
	 *
	 * @param string - key for the core preset
	 *
	 * @return mixed
	 */
	public static function getCore($preset_key){
		$where = array(
			'type' => 'preset',
			'variety' => 'core',
			'extra_key' => $preset_key,
		);

		return WidgetWranglerExtras::get($where);
	}

	/**
	 * All Preset types provided by default
	 *
	 * @param array - existing preset varieties
	 *
	 * @return array
	 */
	public static function varieties() {
		// default varieties
		$varieties = array(
			'core' => array(
				'title' => 'Core',
				'description' => 'Widget Wrangler provided presets',
			),
			'standard' => array(
				'title' => 'Standard',
				'description' => 'Custom arbitrary widget groupings',
			)
		);

		$varieties = apply_filters('widget_wrangler_preset_varieties', $varieties );

		// Process them a little for extra data
		foreach($varieties as $slug => $preset_variety){
			// set filter's type as a value if not provided by filter
			if(!isset($preset_variety['slug'])){
				$varieties[$slug]['slug'] = $slug;
			}
			// maintain the hook's key
			$varieties[$slug]['hook_key'] = $slug;
		}

		return $varieties;
	}
  
  /*
   * Handle determining the widgets for a single post using a preset
   *
   * @param (mixed) - array if widgets already found, null if not
   *
   * @return (mixed) - array if widgets found, null if not
   */
  function ww_find_standard_preset_widgets($widgets){
    if (is_null($widgets) && (is_singular() || is_admin())) {
      global $post;
      if (isset($post) && $post_preset_id = get_post_meta($post->ID, 'ww_post_preset_id', TRUE)){
        if ($post_preset = self::get($post_preset_id)){
          self::$current_preset_id = $post_preset_id;
          $widgets = $post_preset->widgets;
        }
      }
    }
    
    return $widgets;
  }
  
  /*
   * Handle determining the widgets for non-post routes using a preset
   *
   * @param (mixed) - array if widgets already found, null if not
   *
   * @return (mixed) - array if widgets found, null if not
   */
  function ww_find_core_preset_widgets($widgets){
    // only take over with core widgets if no other widgets have been found
    if (is_null($widgets)){
      $found_widgets = FALSE;
      
      if(is_home() && $preset = self::getCore('postspage')){
        $found_widgets = $preset->widgets;
      }
      
      else if ($preset = self::getCore('default')) {
        $found_widgets = $preset->widgets;
      }
      
      if ($found_widgets){
        $widgets = $found_widgets;
        self::$current_preset_id = $preset->id;
      }
    }
    return $widgets;
  }

  /*
   * Look for and insert legacy default and postpage widgets as presets
   */
  function _install_core_presets(){
    $data = array(
      'type' => 'preset',
      'variety' => 'core',
      'extra_key' => '',
      'data' => '',
      'widgets' => serialize(array()),
    );
    
    $where = array(
      'type' => 'preset',
      'variety' => 'core',
      'extra_key' => 'default',
    );
    
    // default widgets
    if (!$row = WidgetWranglerExtras::get($where)) {
      $data['extra_key'] = $where['extra_key'];
      $data['data'] = serialize(array('name' => 'Default'));
      
      $existing_widgets = WidgetWranglerUtils::unserializeWidgets(get_option('ww_default_widgets', array()));
      $data['widgets'] = serialize($existing_widgets);
      
      WidgetWranglerExtras::insert($data);
    }
  
    // postspage widgets
    $where['extra_key'] = 'postspage';
    if (!$row = WidgetWranglerExtras::get($where)) {
      $data['extra_key'] = $where['extra_key'];
      $data['data'] = serialize(array('name' => 'Posts Page'));

      $existing_widgets = WidgetWranglerUtils::unserializeWidgets(get_option('ww_postspage_widgets', array()));
      $data['widgets'] = serialize($existing_widgets);
      
      WidgetWranglerExtras::insert($data);
    }
  }
}
