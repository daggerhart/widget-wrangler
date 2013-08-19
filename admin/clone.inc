<?php
/*
 * Inserts a cloned WP widget as a WW widget
 */
function ww_clone_insert($posted)
{
  global $wpdb, $wp_widget_factory, $user_ID;

  $clone_class = $_POST['ww-data']['clone']['clone-class'];
	$instance = ww_make_clone_instance($_POST);
  
	// prep new widget info for saving
  $new_widget = array();
  $new_widget['post_author'] = $user_ID; // for now
  $new_widget['post_title'] = (isset($instance['title'])) ? $instance['title'] : "Copy of ".$clone_class;
  $new_widget['post_excerpt'] = 'Copied from '.$clone_class;
  $new_widget['post_date'] = date('Y-m-d H:i:s');
  $new_widget['post_category'] = array(0);
  $new_widget['comment_status'] = 'closed';
  $new_widget['ping_status'] = 'closed';
  $new_widget['post_status'] = 'publish';
  $new_widget['post_type'] = 'widget';
  // Herb contributed fix for problem cloning
  $new_widget['post_content'] = '';
  $new_widget['to_ping'] = '';
  $new_widget['pinged'] = '';
  $new_widget['post_content_filtered'] = '';
  
  // widget data
  $_POST['ww-type'] = 'clone';

	//print '<pre>';print_r($posted);print '</pre>';exit;
	
  // insert new widget into db
  $new_post_id = wp_insert_post($new_widget);
  
  return $new_post_id;
}

/*
 * Read the clone instance data from WW Copy widget form
 */
function ww_make_clone_instance($posted){
	global $wp_widget_factory;
	$clone_class = $posted['ww-data']['clone']['clone-class'];
	$option_name = str_replace("_", "-", $wp_widget_factory->widgets[$clone_class]->option_name);
	$instance = array();
	
	// loop through instance values and create an instance array
	foreach($posted[$option_name] as $i => $settings){
		foreach($settings as $key => $value){
			$instance[$key] = $value;
		}
	}
	return $instance;
}

/*
 * Display widgets available for cloning.
 */
function ww_clone_form() {
	global $wp_widget_factory;
	$total_widgets = count($wp_widget_factory->widgets);
	$half = round($total_widgets/2);
	
	ob_start();
	?>
		<p>Here you can copy an existing Wordpress widget into the Widget Wrangler system.</p>
		
		<ul class='ww-clone-widgets'>
			<?php
				$i = 0;
				foreach ($wp_widget_factory->widgets as $class_name => $widget)
				{
					// skip potential recursion
					if($class_name != "Widget_Wrangler_Corral_Widget")
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
	<?php
	return ob_get_clean();
}