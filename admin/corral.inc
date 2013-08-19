<?php
/*
 * Add a new corral
 * 
 * @param array $posted  -
 * 		$_POST data from submission
 */
function ww_corral_insert()
{
  // just in case
  $new_corral_name = strip_tags($_POST['ww-new-corral']);
	$new_corral_slug = ww_make_slug($new_corral);
  $corrals = ww_get_all_corrals();

	// no corrals, make this #1
	if(!count($corrals)){
		$corrals[1] = $new_corral;
	}
	else {
		// add new corral safely, start at 2 and get highest number
		$next = 0;
		foreach($corrals as $i => $corral){
			$next = ($i > $next) ? $i : $next;
		}
		$corral_id = ($next+1);
		$corrals[$corral_id] = array(
			'id' => $corral_id,
			'slug' => $new_corral_slug,
			'name' => $new_corral_name,
		);
  }
	
  $corrals_str = serialize($corrals);
  // save
  update_option('ww_corrals', $corrals_str);
}
/*
 * Delete a corral
 * 
 * @param array $_POST  -
 * 		$_POST data from submission
 */
function ww_corral_delete()
{
  if (isset($_POST['ww-delete-slug'])) {
		$old_id = $_POST['ww-delete-slug'];
		
		if ($corrals_string = get_option('ww_corrals'))
		{
			$corrals_array = unserialize($corrals_string);
			unset($corrals_array[$old_id]);
			$new_option = serialize($corrals_array);
			update_option('ww_corrals', $new_option);
		}
	}
}
/*
 * Update/Edit a corral
 * 
 * @param array $_POST $_POST data from submission
 */
function ww_corral_update()
{
	$id = array_pop(array_keys($_POST['action']['update']));
	if (isset($_POST['ww-update-corral-name'][$id])) {
		$update_name = strip_tags($_POST['ww-update-corral-name'][$id]);
	
		// make sure corral exists
		if ($corrals = ww_get_all_corrals()){
			if (isset($corrals[$id])) {
				$corrals[$id] = $update_name;
				// serialize
				$corrals_str = serialize($corrals);
				update_option('ww_corrals', $corrals_str);
			}
		}
	}
}

/*
 * Handle sorting of corrals
 *
 * @param array $_POST-
 * 		$_POST data from submission
 */
function ww_corral_sort()
{
	$all_corrals = ww_get_all_corrals();
  $new_order_array = array();
  $new_order_string = '';
  
  if (is_array($_POST['weight']))
  {
    $i = 1;
    $total = count($_POST['weight']);
    while($i <= $total)
    {
			if (isset($_POST['weight'][$i]) && isset($all_corrals[$_POST['weight'][$i]])){
				$new_order_array[$_POST['weight'][$i]] = $all_corrals[$_POST['weight'][$i]];
				$i++;
			}
    }
    $new_order_string = serialize($new_order_array);
    
    update_option('ww_corrals',$new_order_string);
  }
}

/*
 * Available variables
 *
 * $corrals = all corrals
 */
function ww_corral_form(){
	$corrals = ww_get_all_corrals();
	ob_start();
	?>
		<p>
			A <strong>Corral</strong> is an arbitrary group of widgets. Wordpress and Widget Wrangler 1.x call them "sidebars", but they are ultimately not limited by that terminology.
		</p>
		<form action='edit.php?post_type=widget&page=ww-corrals&noheader=true' method='post' name='widget-wrangler-form'>		
			<div id="ww-admin-tabs">	
				<ul id="corrals-tabs" class="ww-admin-tab-list">
					<li class="ww-admin-tab-list-title">Corrals</li>
					<li><a href="#ww-corrals-edit"><span>All Corrals</span></a></li>
					<li><a href="#ww-corrals-add"><span>Add New Corral</span></a></li>
					<li><a href="#ww-corrals-sort"><span>Sort Corrals</span></a></li>
				</ul>
				
				<div id="ww-corrals-edit" class="ww-admin-tab">
					<h2>All Corrals</h2>
					<div class='description'>
						<!-- Warning! If you change a corral's 'slug', widgets currently assigned to that corral will need to be reassigned. -->
					</div>
					<ul id='ww-corrals-list'>
						<?php
							if (count($corrals)){
								// loop through each corral and build form
								foreach($corrals as $id => $corral)
								{ ?>
									<li class='ww-corral-item'>
										<div class='widget'>
											<div class='widget-top'>
												<div class='widget-title-action'>
													<div class='widget-action'></div>
												</div>
												<h4><?php print $corral['name']; ?></h4>
											</div>
											<div class='widget-inside'>
												<div>
													<p>
														<label><strong>Corral id: </strong></label><?php print $id; ?>
													</p>
													<p>
														<label><strong>Name: </strong></label><input class='ww-text' name='ww-update-corral-name[<?php print $id; ?>]' type='text' value='<?php print $corral['name']; ?>' />
													</p>
												</div>
												<hr class="ww-hr" />					
												<div class='ww-delete-corral'>
													<input class='ww-corral-delete-submit' name='action[delete][<?php print $id; ?>]' type='submit' value='Delete' />
												</div>
												<div class='ww-update-corral'>
													<input class='button button-primary button-large' name='action[update][<?php print $id; ?>]' type='submit' value='Update' />
												</div>
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
				</div>
				
				<div id="ww-corrals-add" class="ww-admin-tab">
					<h2>Add New Corral</h2>
					<div class="description">
						
					</div>
					<p>
						Corral Name: <input name='ww-new-corral' type='text' value='' />
					</p>
					<hr class="ww-hr" />					
					<input name="action[insert]" class='button button-primary button-large' type='submit' value='Create Corral' />
				</div>
				
				<div id="ww-corrals-sort" class="ww-admin-tab">
					<h2>Sort Corrals</h2>
					<ul id='ww-corrals-sort-list'>
					<?php
						//loop
						$weight = 1;
						foreach($corrals as $id => $corral)
						{ ?>
							<li class='ww-corral-sort-item'>
								<strong><?php print $corral['name']; ?></strong>
								<input type='hidden' class='ww-corral-weight' name='weight[<?php print $weight; ?>]' value='<?php print $id; ?>' />
							</li>
							<?php
							$weight++;
						}
					?>
					</ul>
					<hr class="ww-hr" />					
					<input class='button button-primary button-large' type='submit' name='action[sort]' value='Save Order' />
				</div>
			</div>
		</form>
	<?php
	return ob_get_clean();
}