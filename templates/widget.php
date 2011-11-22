<?php
/*
 * Some useful variables:
 * The $post object contains the information for the Page, Post, or other post type the widget is being displayed on
 * 
 * $post->post_title
 * $post->post_content
 * $post->post_name
 * $post->ID
 *
 * $widget->post_title
 * $widget->post_content
 * $widget->post_name
 * $widget->ID
 *
 */
?>
<div id="widget-<?php print $widget->ID; ?>" class="widget">
  <?php if($widget->post_title) { ?>
    <h3><?php print $widget->post_title;?></h3>
  <?php } ?>
  <?php if($widget->post_content) { ?>  
    <div class="content">
      <?php print $widget->post_content; ?>
    </div>
  <?php } ?>
</div>