<?php

/*
 * Display widgets available for cloning.
 */
function ww_clone_form()
{
  global $wp_widget_factory;
  $total_widgets = count($wp_widget_factory->widgets);
  $half = round($total_widgets/2);
  $i = 0;
  
  $output = "<div class='wrap'>
              <h2>Clone a Wordpress Widget</h2>
              <p>Here you can clone an existing Wordpress widget into the Widget Wrangler system.</p>";
              
  $output.= "<ul class='ww-clone-widgets'>";
  foreach ($wp_widget_factory->widgets as $class_name => $widget)
  {
    if($class_name != "Widget_Wrangler_Sidebar_Widget")
    {
      $posted_array_key = "widget-".$widget->id_base;
      if ($i == $half){
        $output.= "</ul><ul class='ww-clone-widgets'>";
      }
      
      ob_start();
      eval('$w = new '.$class_name.'(); $w->form(array());');
      $instance_form = ob_get_clean();
      
      $output.= "<li>
                  <div class='widget'>
                    <div class='widget-top'>
                      <div class='widget-title-action'>
                        <div class='widget-action'></div>
                      </div>
                      <h4>".$widget->name."</h4>
                    </div>
                    <div class='widget-inside'>            
                      <form action='edit.php?post_type=widget&page=ww-clone&ww-clone-action=insert&noheader=true' method='post'>
                        <input type='hidden' name='ww-data[clone][clone-class]' value='$class_name' />
                        ".$instance_form."
                        <input class='ww-clone-submit button' type='submit' value='Create' />
                      </form>
                    </div>
                  </div>
                </li>";
      
      $i++;
    }
  }
  $output.= " </ul></div>";
  print $output;
}
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