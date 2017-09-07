<?php

/**
 * For themes that implement this directly
 *
 * @param $slug
 * @param array $args
 */
function ww_dynamic_sidebar($slug, $args = array('before_widget' => '', 'before_title' => '', 'after_title' => '', 'after_widget' => '')){
  global $widget_wrangler;
  $widget_wrangler->display->dynamic_corral($slug, $args);
}

/**
 * For old clone widgets that use advanced parsing area with php to execute the widget
 *
 * @param $wp_widget_class
 * @param array $instance
 */
function ww_the_widget($wp_widget_class, $instance = array()){
  global $widget_wrangler;
  print $widget_wrangler->display->_the_widget($wp_widget_class, $instance);
}

/*
 * Check if corral contains active widgets
 *
 * @TODO: Not exactly a backwards compatibility function. Temporarily placed here.
 */
function ww_is_active_corral($corral_slug) {
  $page_widgets = \WidgetWrangler\Utils::pageWidgets();
  return ( isset($page_widgets[$corral_slug]) && !empty($page_widgets[$corral_slug]) );
}

/*
 * uncomment if you need this one too
 *
function ww_theme_single_widget($widget){
  global $widget_wrangler;
  return $widget_wrangler->display->theme_single_widget($widget);
}
*/