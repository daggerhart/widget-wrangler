<?php
/*
 * Handle sorting of corrals
 *
 * @param array $posted $_POST data from submission
 */
function ww_corral_sort($posted = array())
{
  $all_corrals = ww_get_all_corrals();
  $new_order_array = array();
  $new_order_string = '';
  
  if (is_array($posted['weight']))
  {
    $i = 1;
    $total = count($posted['weight']);
    while($i <= $total)
    {
      $new_order_array[$posted['weight'][$i]] = $all_corrals[$posted['weight'][$i]];
      $i++;
    }
    $new_order_string = serialize($new_order_array);
    
    update_option('ww_corrals',$new_order_string);
  }
}
/*
 * Add a new corral
 * 
 * @param array $posted $_POST data from submission
 */
function ww_corral_insert($posted = array())
{
  // just in case
  $new_corral = strip_tags($posted['ww-new-corral']);
  // clean name
  $slug_name = ww_make_slug($new_corral);
  
  if ($corrals_string = get_option('ww_corrals'))
  {
    $corrals_array = unserialize($corrals_string);
  }
  // add new corral
  $corrals_array[$slug_name] = $new_corral;
  // encode
  $new_option = serialize($corrals_array);
  // save
  update_option('ww_corrals',$new_option);
}
/*
 * Delete a corral
 * 
 * @param array $posted $_POST data from submission
 */
function ww_corral_delete($posted = array())
{
  $old_slug = $posted['ww-delete-slug'];
  
  if ($corrals_string = get_option('ww_corrals'))
  {
    $corrals_array = unserialize($corrals_string);
    unset($corrals_array[$old_slug]);
    $new_option = serialize($corrals_array);
  }
  else
  {
    $new_option = '';
  }
  update_option('ww_corrals', $new_option);
}
/*
 * Update/Edit a corral
 * 
 * @param array $posted $_POST data from submission
 */
function ww_corral_update($posted = array())
{
  $update_corral = strip_tags($posted['ww-update-corral']);
  $update_slug = ww_make_slug($posted['ww-update-slug']);
  $old_slug = $posted['ww-update-old-slug'];
  
  if ($corrals_string = get_option('ww_corrals'))
  {
    $corrals_array = unserialize($corrals_string);
    // delete old one
    unset($corrals_array[$old_slug]);
    // add new one
    $corrals_array[$update_slug] = $update_corral;
    // serialize
    $new_option = serialize($corrals_array);
  }
  else
  {
    $new_option = '';
  }
  update_option('ww_corrals', $new_option);
}

/*
 * Available variables
 *
 * $corrals = all corrals
 */
function ww_corral_form(){
	$corrals = ww_get_all_corrals();
	?>
	<div class='wrap'>
		<h2>Widget Corrals</h2>
		<p>A corral is a way to group widgets.  Like where I keep ma' cattle!  Wordpress and Widget Wrangler 1.x call them "sidebars", but I find that to be confusin'.
		</p>
		<table id='ww-corral-page'>
		<td>
			<h2>Edit existing Corrals</h2>
			<div class='description'>
				<!-- Warning! If you change a corral's 'slug', widgets currently assigned to that corral will need to be reassigned. -->
			</div>
			<ul id='ww-corrals-list'>
				<?php
					if (is_array($corrals))
					{
						// loop through each corral and build form
						foreach($corrals as $slug => $corral)
						{ ?>
								<li class='ww-corral-item'>
									<div class='widget'>
										<div class='widget-top'>
											<div class='widget-title-action'>
												<div class='widget-action'></div>
											</div>
											<h4><?php print $corral; //." (".$slug.")"; ?></h4>
										</div>
										<div class='widget-inside'>
											<form action='edit.php?post_type=widget&page=ww-corrals&ww-corral-action=update&noheader=true' method='post'>
												<label>Name: </label><input class='ww-text' name='ww-update-corral' type='text' value='<?php print $corral; ?>' /><br />
												<!-- <label>Slug: </label><input class='ww-text' name='ww-update-slug' type='text' value='<?php print $slug; ?>' />
												<input name='ww-update-old-slug' type='hidden' value='<?php print $slug; ?>' /> -->
												<input class='ww-corral-update-submit' name='ww-update-submit' type='submit' value='Update' />
											</form>
											<hr />
											<form class='ww-delete-corral' action='edit.php?post_type=widget&page=ww-corrals&ww-corral-action=delete&noheader=true' method='post'>
												<input name='ww-delete-slug' type='hidden' value='<?php print $slug; ?>' />
												<input class='ww-corral-delete-submit' name='ww-delete-submit' type='submit' value='Delete' />
											</form>
										</div>
									</div>
								</li>
							<?php
						}
					}                            
					else
					{ ?>
						<li>No Corrals defined</li>
						<?php
					}
				?>
			</ul>
		</td>
		<td>
			<h2>Create New Corral</h2>
			<form action='edit.php?post_type=widget&page=ww-corrals&ww-corral-action=insert&noheader=true' method='post'>
				Corral Name: <br />
				<input name='ww-new-corral' type='text' value='' />
				<input class='button' type='submit' value='Create Corral' />
			</form>
			<h2>Sort your Corrals</h2>
			<form action='edit.php?post_type=widget&page=ww-corrals&ww-corral-action=sort&noheader=true' method='post'>
				<ul id='ww-corrals-sort'>
				<?php
					//loop
					if(is_array($corrals))
					{
						$i = 1;
						foreach($corrals as $slug => $corral)
						{ ?>
							<li class='ww-corral-sort-item'>
								<strong><?php print $corral; //." (".$slug.")"; ?></strong>
								<input type='hidden' class='ww-corral-weight' name='weight[<?php print $i; ?>]' value='<?php print $slug; ?>' />
							</li>
							<?php
							$i++;
						}
					}
				?>
				</ul>
				<input class='ww-corral-sort-submit button' type='submit' name='ww-corrals-save' value='Save Order' />
			</form>
		</td>
		</table>
	</div>
	<?php
}