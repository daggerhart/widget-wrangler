<?php

/*
 * For themes that implement this directly
 */
function ww_dynamic_sidebar($slug, $args = array('before_widget' => '', 'before_title' => '', 'after_title' => '', 'after_widget' => '')){
  global $widget_wrangler;
  $widget_wrangler->display->dynamic_corral($slug, $args);
}

/*
 * For old clone widgest that use advanced parsing area with php to execute the widget
 */
function ww_the_widget($wp_widget_class, $instance = array()){
  global $widget_wrangler;
  $widget_wrangler->display->_the_widget($wp_widget_class, $instance);
}

/*
 * uncomment if you need this one too
 *
function ww_theme_single_widget($widget){
  global $widget_wrangler;
  return $widget_wrangler->display->theme_single_widget($widget);
}
*/