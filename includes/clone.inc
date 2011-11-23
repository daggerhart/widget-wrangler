<?php
/*
 * Inserts a cloned WP widget as a WW widget
 */
function ww_clone_insert($posted)
{
  global $wpdb, $wp_widget_factory, $user_ID;

  $clone_class = $_POST['ww-data']['clone']['clone-class'];
  
  // prep new widget info for saving
  $new_widget = array();
  $new_widget['post_author'] = $user_ID; // for now
  $new_widget['post_title'] = ($instance['title']) ? $instance['title'] : "Clone of ".$clone_class;
  $new_widget['post_excerpt'] = 'Cloned from '.$clone_class;
  $new_widget['post_date'] = date('Y-m-d H:i:s');
  $new_widget['post_category'] = array(0);
  $new_widget['comment_status'] = 'closed';
  $new_widget['ping_status'] = 'closed';
  $new_widget['post_status'] = 'draft';
  $new_widget['post_type'] = 'widget';
  // Herb contributed fix for problem cloning
  $new_widget['post_content'] = '';
  $new_widget['to_ping'] = '';
  $new_widget['pinged'] = '';
  $new_widget['post_content_filtered'] = '';
  
  // widget data
  $_POST['ww-type'] = 'clone';
  
  // insert new widget into db
  $new_post_id = wp_insert_post($new_widget);
  
  return $new_post_id;
}