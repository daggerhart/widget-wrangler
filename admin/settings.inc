<?php

/*
 * Reset all pages to use the default widget settings
 */
function ww_settings_reset_widgets()
{
  global $wpdb;
	$table = $wpdb->prefix."ww_post_widgets";
  $wpdb->query("TRUNCATE TABLE ".$table);
}

/*
 * Save the Widget Wrangler Settings page
 */
function ww_settings_save($post)
{
	// handle exclude from search
	$_POST['settings']['exclude_from_search'] = (isset($_POST['settings']['exclude_from_search']) && $_POST['settings']['exclude_from_search'] == "on") ? 1 : 0;
  $_POST['settings']['disable_autosave'] = (isset($_POST['settings']['disable_autosave']) && $_POST['settings']['disable_autosave'] == "on") ? 1 : 0;
  $settings = serialize($_POST['settings']);
  
  // save to wordpress options
  update_option("ww_settings", $settings);
}

/*
 * Available variables
 * $settings - WW settings option
 * 
 * TODO:
 *   Access Control
 */
function ww_settings_form() {
	$settings = ww_get_settings();
	
	// handle checkboxes
	$simple_checked = (isset($settings['capabilities']) && $settings['capabilities'] == 'simple') ? "checked" : ""; 
	$adv_checked    = (isset($settings['capabilities']) && $settings['capabilities'] == 'advanced') ? "checked" : ""; 
	$exclude_from_search_checked = (isset($settings['exclude_from_search']) && $settings['exclude_from_search'] == 1) ? "checked='checked'" : "";
	$disable_autosave_checked = (isset($settings['disable_autosave']) && $settings['disable_autosave'] == 1) ? "checked='checked'" : "";
	
	// Get all extra post types
	$args = array('public'   => true, '_builtin' => false); 
	$post_types = get_post_types($args,'names','and');
	// Add standard types
	$post_types['post'] = 'post';
	$post_types['page'] = 'page';
	unset($post_types['widget']);
	ksort($post_types);
	
	/*  Access control TODO
	global $wp_roles;
	if($settings['capabilities'] == 'roles') {
		$role_checked =  "checked";
		foreach($settings['roles'] as $role => $value)
		{
			if ($value == "on")
			{
				// make a variable as the role name and st it to a value
				$var = $role."_checked";
				$$var = "checked";
			}
		}
	}
	*/
	ob_start();
  ?>
		<form action="edit.php?post_type=widget&page=ww-settings&noheader=true" method="post">
			<div id="ww-page-top">
				<input class="button button-primary button-large" type="submit" value="Save Settings" name="action[save]" />	
			</div>
			
			<div id="ww-admin-tabs">
				<ul id="ww-settings-list" class="ww-admin-tab-list">
					<li class="ww-admin-tab-list-title">Settings</li>
					<li><a href="#ww-settings-general"><span>General</span></a></li>
					<li><a href="#ww-settings-advanced"><span>Advanced</span></a></li>
					<li><a href="#ww-settings-tools"><span>Tools</span></a></li>
				</ul>	
			
				<div id="ww-settings-general" class="ww-admin-tab">
					<h4 class="ww-settings-title">Exclude from search</h4>
					<p class="ww-checkboxes">
						<label class="ww-checkbox">
							<input name="settings[exclude_from_search]" type="checkbox" <?php print $exclude_from_search_checked; ?> /> -  If checked, widgets will excluded from search results.
						</label>
						<div class="ww-clear-gone">&nbsp;</div>
					</p>
				
					<h4 class="ww-settings-title">Disable Autosave</h4>
					<p class="ww-checkboxes">
						<label class="ww-checkbox">
							<input name="settings[disable_autosave]" type="checkbox" <?php print $disable_autosave_checked; ?> /> -  If checked, widget posts will not be autosaved during editing. 
						</label>
						<div class="ww-clear-gone">&nbsp;</div>
					</p>
				
					<h4 class="ww-settings-title">Post types</h4>
					<p>
						Select each post type you would like to be able to control their own widgets with Widget Wrangler.
					</p>
					<div class="ww-checkboxes">
						<?php
							// loop through post types
							foreach ($post_types as $post_type )
							{
								$post_type_checked = (isset($settings['post_types'][$post_type])) ? 'checked="checked"' : '';
								?>
								<label class="ww-checkbox"><input type="checkbox" name="settings[post_types][<?php print $post_type; ?>]" value="<?php print $post_type; ?>" <?php print $post_type_checked; ?> /> - <?php print ucfirst($post_type); ?> </label>
								<?php
							}
						?>
						<div class="ww-clear-gone">&nbsp;</div>
					</div>					
				</div>
				
				<div id="ww-settings-advanced" class="ww-admin-tab">
					<h4 class="ww-settings-title">Capabilities </h4>
					<div>
						<p> 
							<label>
								<input name="settings[capabilities]" type="radio" value="simple" <?php print $simple_checked; ?> />
								<strong>Simple</strong>:  Widgets can be Created and Edited by anyone who can edit Posts.  Anyone who can edit a Page can change the Widgets displayed on that Page.
							</label>
						</p>
						<p>
							<label>
								<input name="settings[capabilities]" type="radio" value="advanced" <?php print $adv_checked; ?> />
								<strong>Advanced</strong>:  Change the capability_type for this post_type.
							</label>
							This is primarily for incorporating third party permission systems. <br />
							A simple use of this setting would be to change the Capability Type to 'page'.  This would make it so that only users who can create and edit pages may create and edit widgets.
						</p>
						<p>If unsure what this does, select "Simple" and leave the below field blank.</p>
						<label><input name="settings[advanced]" type="text" size="20" value="<?php print (isset($settings['advanced'])) ? $settings['advanced'] : ""; ?>"/> Capability Type</label> 
						<br />
					</div>
				</div>
				
				<div id="ww-settings-tools" class="ww-admin-tab">
					<h4 class="ww-settings-title">Mass Reset</h4>
					<div>
						<p>
							<span style="color: red;">WARNING!</span>  If you click this button, all pages will lose their individual corral asignments and will fall back on the default settings.</p>
							<input class="button" type="submit" name="action[reset]" value="Reset All Widgets to Default" onclick="return confirm('Are you Really sure you want to Reset widget settings on all pages?');" />
						</p>
					</div>
					<?php
						global $wpdb;
						$total_widgets = $wpdb->get_var("SELECT count(post_id) FROM ".$wpdb->prefix."ww_widget_data");
						$are_clones = $wpdb->get_var("SELECT count(post_id) FROM ".$wpdb->prefix."ww_widget_data WHERE `type` = 'clone'");
						$are_standard = $total_widgets - $are_clones;
						$all_corrals = ww_get_all_corrals();
						$total_pages =  $wpdb->get_var("SELECT count(post_id) FROM ".$wpdb->prefix."ww_post_widgets");
						$pages_with_presets = $wpdb->get_var("SELECT count(post_id) FROM ".$wpdb->prefix."ww_post_widgets WHERE `preset_id` != 0");
						$pages_no_preset = $total_pages - $pages_with_presets;
					?>
					<h4 class="ww-settings-title">Details</h4>
					<div>
						<div>Total Widgets: <?php print $total_widgets;?></div>
						<div>Clones: <?php print $are_clones;?></div>
						<div>Standard: <?php print $are_standard;?></div>
						<div>Corrals: <?php print count($all_corrals);?></div>
						<div>Total Posts wrangled: <?php print $total_pages; ?></div>
						<div>Posts with presets: <?php print $pages_with_presets; ?></div>
						<div>Posts wrangling own widgets: <?php print $pages_no_preset; ?></div>
					</div>
					<pre>Settings <?php print_r($settings); ?></pre>
				</div>
			</div>
			<div class="ww-clear-gone">&nbsp;</div>
		</form>
	<?php
	return ob_get_clean();
}