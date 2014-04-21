<?php

/*
 *

filters
  - widget-wrangler-display-widget-output-alter
  - widget-wrangler-display-corral-output-alter
  
 */
class Widget_Wrangler_Display {
  //
  function __construct(){
    include_once WW_PLUGIN_DIR.'/common/backwards-compat-functions.inc';
    $this->add_hooks();
  }
  
  //
  function add_hooks(){
    
    // shortcodes
    add_shortcode( 'ww_widget', array( $this, 'single_widget_shortcode' ) );
    add_shortcode( 'ww_corral', array( $this, 'single_corral_shortcode' ) );
    
    // template wrangler hook
    add_filter( 'tw_templates', array( $this, '_tw_templates' ) );
    add_filter( 'tw_pre_process_template', array( $this, '_tw_pre_process_template' ) );
  }
  
  //
  // Template wrangler hook
  //
  function _tw_templates($templates)
  {
    // template applied by files
    $templates['ww_widget'] = array(
      // string of single template suggestion
      // or array of multiple template suggestions
      'files' => array(
          // use argument keys as replacable patterns with the argument's value
          // order is important as the order here is the order inwhich templates are searched for
          // 2.x
          'widget--corral_[corral_slug]--[widget_id].php',
          'widget--corral_[corral_slug]--[post_name].php',
          'widget--[widget_id].php', 
          'widget--[post_name].php', 
          'widget--corral_[corral_slug]--type_[widget_type].php',
          'widget--type_[widget_type].php',
          'widget--corral_[corral_slug].php',
          'widget.php'
      ),
  
      // location of the default template if nothing is found
      'default_path' => WW_PLUGIN_DIR.'/templates',
  
      // optional arguments to be passed to the themeing function
      'arguments' => array(
          // must be key => value pairs
          'widget' => NULL,
          'widget_id' => 0,
          'corral_slug' => '',
          'widget_type' => '',
          'post_name' => '',
      ),
    );
  /*	
    $templates['ww_corral'] = array(
      'files' => array(
        'corral-[corral_id].php',
        'corral.php'
      ),
      'default_path' => dirname(__FILE__).'/templates',
      'arguments' => array(
          'corral_id' => 0,
      ),
    );
  */
  
    if ($this->ww->settings['legacy_template_suggestions']){
      // remove the default widget.php
      array_pop($templates['ww_widget']['files']);
      // add the legacy template suggestion patterns
      $templates['ww_widget']['files'][] = 'widget-[widget_type]-[widget_id].php';
      $templates['ww_widget']['files'][] = 'widget-[widget_id].php';
      $templates['ww_widget']['files'][] = 'widget-[post_name].php';
      $templates['ww_widget']['files'][] = 'widget-[widget_type].php';
      $templates['ww_widget']['files'][] = 'widget.php';
    }
    
    return $templates;
  }
  
  // allow for custom template names
  function _tw_pre_process_template($tw_template){
    if ($tw_template['name'] == "ww_widget" && !empty($tw_template['arguments']['widget']->custom_template_suggestion)){
      array_unshift($tw_template['files'], 'widget-[custom_template_suggestion].php');
      $tw_template['arguments']['custom_template_suggestion'] = $tw_template['arguments']['widget']->custom_template_suggestion;
    }
    return $tw_template;
  }
  
  /*
   * Output a corral
   */
  function dynamic_corral($corral_slug = 'default', $wp_widget_args = array('before_widget' => '', 'before_title' => '', 'after_title' => '', 'after_widget' => ''))
  {
    $corral_html = '';
    // only if page_widgets were found
    if (!is_null($corral_slug) &&
        $this->ww->page_widgets &&
        isset($this->ww->page_widgets[$corral_slug]))
    {
      // ensure widgets are sorted correctly
      usort($this->ww->page_widgets[$corral_slug], array( $this->ww, '_sort_by_weight') );
      
      $i = 0;
      $total = count($this->ww->page_widgets[$corral_slug]);
      while($i < $total) {
        if($widget = $this->ww->get_single_widget($this->ww->page_widgets[$corral_slug][$i]['id'], 'publish'))
        {
          // we know the widget is in this corral
          $widget->in_corral = TRUE;
          $widget->corral_slug = $corral_slug;
          
          // include theme compatibility data
          $widget->current_weight = $this->ww->page_widgets[$corral_slug][$i]['weight'];
          
          // Theme compatiblity
          if (!$widget->override_output_html &&
              $widget->theme_compat)
          {
            $widget->wp_widget_args = $wp_widget_args;
            $widget = $this->_replace_wp_widget_args($widget);
          }
          
          $widget_html = $this->theme_single_widget($widget);
          $widget_html = apply_filters('widget-wrangler-display-widget-output-alter', $widget_html, $widget, $corral_slug);
          $corral_html.= $widget_html;
          
        }
        $i++;
      }
      
      $corral_html = apply_filters('widget-wrangler-display-corral-output-alter', $corral_html, $corral_slug);
    }
    
    print $corral_html;
  }
  
  /*
   * Replace the classes and IDs of a widget displayed within a sidebar
   */
  function _replace_wp_widget_args($widget){
    // wrapper_classes
    $replace = !empty($widget->html['wrapper_classes']) ? $widget->html['wrapper_classes']: 'ww_widget-'.$widget->post_name.' ww_widget-'.$widget->ID;
    $widget->wp_widget_args['before_widget'] = str_replace('widget-wrangler-widget-classname', $replace, $widget->wp_widget_args['before_widget']);
    return $widget;
  }
  
  /*
   * Apply templating and parsing to a single widget
   * @return themed widget for output or templating
   */
  function theme_single_widget($widget){
    if (empty($widget)) { return ''; }
    
    if ($widget->display_logic_enabled){
      //$show = TRUE;
      ob_start();
        // expecting direct conditional statemnt
        if (stripos($widget->display_logic, 'return') === FALSE){
          $show = (eval('return '.$widget->display_logic.';'));
        }
        else {
          $show = eval($widget->display_logic);
        }
      // 
      $ob = ob_get_clean();
      if (!$show && !$widget->in_preview){
        return '';
      }
    }
    
    $default_html = array(
      'wrapper_element' => 'div',
      'wrapper_id'      => 'widget-'.$widget->ID,
      'wrapper_classes' => 'widget',
      'title_element'   => 'h3',
      'title_classes'   => '',
      'content_element' => 'div',
      'content_classes' => 'content',
    );
    
    //
    foreach ($default_html as $k => $v){
      // allow nulls to bypass so we can unset element tags
      if (empty($widget->html[$k]) && !is_null($widget->html[$k])){
        $widget->html[$k] = $default_html[$k];
      }
      
      // allow for _none_
      if ($widget->html[$k] == '_none_'){
        $widget->html[$k] = NULL;
      }
    }
    
    // clone
    if ($widget->widget_type == "clone"){
      $widget->clone_instance['post_name'] = $widget->post_name;
      $widget->clone_instance['ww_widget'] = $widget;
      $themed = $this->_the_widget($widget->clone_classname, $widget->clone_instance);
    }
    // standard
    else{
      // maybe they don't want auto p ?
      if ($widget->wpautop == "on"){
        $widget->post_content = wpautop($widget->post_content);
      }
    
      // apply shortcode
      $widget->post_content = do_shortcode($widget->post_content);
    
      // see if this should use advanced parsing
      if($widget->adv_enabled){
        $themed = $this->_adv_parse_widget($widget);
      } else {
        $themed = $this->template_widget($widget);
      }
    }
    
    return $themed;
  }
  
  /*
   * Look for possible custom templates, then default to widget.php
   * @return templated widget
   */
  function template_widget($widget)
  {
    $output = '';
    
    $args = array(
      'widget' => $widget, // needed in final template
      'widget_id' => $widget->ID,
      'widget_type' => $widget->widget_type,
      'post_name' => $widget->post_name,
    );
    
    if ($widget->in_corral && isset($widget->corral_slug)){
      $args['corral_slug'] = $widget->corral_slug;
    }
    
    $args += $widget->html;
    
    // preview bypasses the theme compatibility assignment
    if (!isset($widget->theme_compat)){
      $widget->theme_compat = 0;
    }
    
    // theme compatibility
    // remove post title from templating
    // and include it manually later
    if (!$widget->override_output_html &&
        ($widget->theme_compat || $widget->hide_title))
    {
      $widget->hidden_title = $widget->post_title;
      $widget->post_title = NULL;
    }
    
    // template-wrangler.inc
    $output = theme('ww_widget', $args);
    
    // handle final theme compat issues if the widget is in a corral,
    // and not overriding it's html output
    if (!$widget->override_output_html &&
        $widget->in_corral &&
        $widget->theme_compat)
    {
      $theme_compat =  $widget->wp_widget_args['before_widget'];
     
      // title can also be NULL with clones
      if ($widget->hidden_title && !$widget->hide_title) {
        $theme_compat.= $widget->wp_widget_args['before_title'] .
                          $widget->hidden_title .
                        $widget->wp_widget_args['after_title'];
      }
     
      $theme_compat.= $output . $widget->wp_widget_args['after_widget'];
      $output = $theme_compat;
      
      // give the post title back now that we're done
      $widget->post_title = $widget->hidden_title;
    }
    
    return $output;  
  }
  
  /*
   * Handle the advanced parsing for a widget, including adv templating
   * 
   * @return advanced parsed widget
   */
  function _adv_parse_widget($widget){
    // make $post and $page available
    global $post;
    $page = $post;
    
    // find and replace title and content tokens
    // this should happen after eval() to prevent code-like content from attempting to execute
    // use str_replace to avoid $<digits> issue with preg_replace
    // replace \$ with $ for backwards compat w/ users who have added their own backslashes
    $search = array('{{title}}','{{content}}', '\$');
    $replace = array($widget->post_title, $widget->post_content, '$');
    
    // handle advanced templating
    if($widget->adv_template)
    {
      $returned_array = eval('?>'.$widget->parse);
      
      if (is_array($returned_array)){
        // only change values if passed into returned array
        if (isset($returned_array['title'])) {
          // tokens
          $returned_array['title'] = str_replace($search, $replace, $returned_array['title']);
          $widget->post_title = $returned_array['title'];
        }
        if (isset($returned_array['content'])) {
          // tokens
          $returned_array['content'] = str_replace($search, $replace, $returned_array['content']);
          $widget->post_content = $returned_array['content'];
        }
        $output = $this->template_widget($widget);
      }
      else {
        $output = "<!-- Error:  This widget did not return an array. -->";
      }
    }
    else{
      // execute adv parsing area - no advanced templating
      ob_start();
        // add some context to all parsed areas
        eval('$instance["ww_widget"] = $widget; ?>'.$widget->parse);
      $output = ob_get_clean();
      
      // theme compatibility
      // adv parse w/o templating doesn't have separate title
      if (!$widget->override_output_html &&
          $widget->theme_compat)
      {
        $output = $widget->wp_widget_args['before_widget'].$output.$widget->wp_widget_args['after_widget'];
      }
      // tokens
      $output = str_replace($search, $replace, $output);
      
      // fix for recent post widget not resetting the query
      $post = $page;
    }
        
    return $output;
  }
  
  /**
   * Handles output for cloned widgets
   * 
   * Taken from wp-includes/widgets.php, adjusted for my needs
   *
   * @param string $wp_widget_class the widget's PHP class name (see default-widgets.php)
   * @param array $instance the widget's instance settings
   * @return void
   **/
  function _the_widget($wp_widget_class, $instance = array())
  {
    // load widget from widget factory ?
    global $wp_widget_factory;
    $wp_widget = $wp_widget_factory->widgets[$wp_widget_class];
  
    // get as much ww widget data as possible 
    $ww_widget = (isset($instance['ww_widget'])) ? $instance['ww_widget'] : $this->ww->get_single_widget($instance['ID']);
    
    if ( !is_a($wp_widget, 'WP_Widget') )
      return;
  
    // args for spliting title from content
    $args = array('before_widget'=>'','after_widget'=>'','before_title'=>'','after_title'=>'[eXpl0de]');
  
    // output to variable for replacements
    ob_start();
       $wp_widget->widget($args, $instance);
    $temp = ob_get_clean();
  
    // get title and content separate
    $array = explode("[eXpl0de]", $temp);
  
    // prep object for template
    if (count($array) > 1) {
      // we have a title
      $ww_widget->post_title    = ($array[0]) ? $array[0]: $instance['title'];
      $ww_widget->post_content  = $array[1];
    }
    else {
      // no title
      $ww_widget->post_content = $array[0];
    }
  
    if (isset($instance['hide_title']) && $instance['hide_title']){
      $ww_widget->post_title = NULL;
    }
  
    $themed_widget = $this->template_widget($ww_widget);
    if (!isset($ww_widget->widget_type) || $ww_widget->widget_type == "standard"){
      print $themed_widget;
    }
    // template with WW template
    return $themed_widget;
  }
  
  /*
   * Single widget shortcode
   */
  function single_widget_shortcode($atts){
    $args = shortcode_atts(array('id' => NULL, 'slug' => NULL), $atts);
    
    // allow slug
    if ($args['slug']){
      global $wpdb;
      if ($id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->prefix."posts WHERE post_name = '%s' AND post_status = 'publish' LIMIT 1", $args['slug']))){
        $args['id'] = $id;
      }
    }
    
    if ($widget = $this->ww->get_single_widget($args['id'], 'publish')){
      return $this->ww->display->theme_single_widget($widget);
    }
    return '';
  }
  
  /*
   * Single corral shortcode
   */
  function single_corral_shortcode($atts){
    $args = shortcode_atts(array('slug' => NULL), $atts);
    
    // allow slug
    if ($args['slug']){
      ob_start();
        $this->ww->display->dynamic_corral($args['slug']);
      return ob_get_clean();
    }
  }

  /*
   * Create a reusable map of corrals to sidebars
   */
  function corrals_to_wpsidebars_map(){
    global $wp_registered_sidebars;
    $wpsidebars_widgets = get_option( 'sidebars_widgets' );
    $widget_ww_sidebar = get_option('widget_widget-wrangler-sidebar');
    
    $corrals = array();
    
    foreach ($wp_registered_sidebars as $wpsidebar_id => $wpsidebar_details){
      $wpwidgets_in_sidebar = $wpsidebars_widgets[$wpsidebar_id];
      foreach ($wpwidgets_in_sidebar as $i => $wpwidget_id){
        $widget_key = str_replace('widget-wrangler-sidebar-', '', $wpwidget_id);
        if (isset($widget_ww_sidebar[$widget_key])){
          $widget_instance = $widget_ww_sidebar[$widget_key];
          $corrals[$widget_instance['sidebar']] = $wpsidebar_id;
        }
      }
    }
    
    return $corrals;  
  }  
}
