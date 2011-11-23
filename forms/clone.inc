<?php
/*
 * Display widgets available for cloning.
 */
  global $wp_widget_factory;
  $total_widgets = count($wp_widget_factory->widgets);
  $half = round($total_widgets/2);
?>
  <div class='wrap'>
    <h2>Clone a Wordpress Widget</h2>
    <p>Here you can clone an existing Wordpress widget into the Widget Wrangler system.</p>
    
    <ul class='ww-clone-widgets'>
      <?php
        $i = 0;
        foreach ($wp_widget_factory->widgets as $class_name => $widget)
        {
          // skip potential recursion
          if($class_name != "Widget_Wrangler_Sidebar_Widget")
          {
            $posted_array_key = "widget-".$widget->id_base;
            
            // split them in half
            if ($i == $half)
            { ?>
                </ul><ul class='ww-clone-widgets'>
              <?php
            }
            
            ?>
            <li>
              <div class='widget'>
                <div class='widget-top'>
                  <div class='widget-title-action'>
                    <div class='widget-action'></div>
                  </div>
                  <h4><?php print $widget->name; ?></h4>
                </div>
                <div class='widget-inside'>            
                  <form action='edit.php?post_type=widget&page=ww-clone&ww-clone-action=insert&noheader=true' method='post'>
                    <input type='hidden' name='ww-data[clone][clone-class]' value='<?php print $class_name; ?>' />
                    <?php 
                      // new widget instance form
                      $w = new $class_name();
                      $w->form(array());
                    ?>
                    <input class='ww-clone-submit button' type='submit' value='Create' />
                  </form>
                </div>
              </div>
            </li>
            <?php
            $i++;
          }
        }
      ?>
    </ul>
  </div>