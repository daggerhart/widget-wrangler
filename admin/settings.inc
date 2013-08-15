<?php
/*
 * Get the Widget Wrangler Settings
 *
 * @return settings array
 */
function ww_get_settings()
{
  if ($settings = get_option("ww_settings")){
    $settings = unserialize($settings);
  }
  else{
    ww_settings_set_default();
    $settings = ww_get_settings();
  }

  return $settings;
}
/*
 * Reset all pages to use the default widget settings
 */
function ww_settings_reset_widgets()
{
  global $wpdb;
  $query = "TRUNCATE TABLE `".$wpdb->prefix."ww_post_widgets`";
  $wpdb->query($query);
}

/*
 * Save the Widget Wrangler Settings page
 */
function ww_settings_save($post)
{
  $settings = serialize($post['settings']);
  
  // save to wordpress options
  update_option("ww_settings", $settings);
}

/*
 * Default settings
 */
function ww_settings_set_default()
{
  $settings["capabilities"] = "simple";
  $settings["post_types"]['page'] = "page";
  $settings["post_types"]['post'] = "post";
  update_option("ww_settings", serialize($settings));
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
	if($settings['capabilities'] == 'simple') { $simple_checked =  "checked"; }
	if($settings['capabilities'] == 'advanced') { $adv_checked = "checked"; } 
	
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
  ?>
    <div class='wrap'>
      <h2>Widget Wrangler Settings</h2>
      <form action="edit.php?post_type=widget&page=ww-settings&ww-settings-action=save&noheader=true" method="post">
        <h3 class="ww-settings-title">Capabilities </h3>
        <div class="ww-settings-field">
          <p> 
            <label>
              <input name="settings[capabilities]" type="radio" value="simple" <?php print $simple_checked; ?> />
              <strong>Simple</strong>:  Widgets can be Created and Edited by anyone who can edit Posts.  Anyone who can edit a Page can change the Widgets displayed on that Page.
            </label>
          </p>
          <hr />
          <p>
            <label>
              <input name="settings[capabilities]" type="radio" value="advanced" <?php print $adv_checked; ?> />
              <strong>Advanced</strong>:  Change the capability_type for this post_type.
            </label>
            This is primarily for incorporating third party permission systems. <br />
            A simple use of this setting would be to change the Capability Type to 'page'.  This would make it so that only users who can create and edit pages may create and edit widgets.
            <br />
          </p>
          <label><input name="settings[advanced]" type="text" size="20" value="<?php print $settings['advanced']; ?>"/> Capability Type</label> 
          <br />
        </div>
        <h3 class="ww-settings-title">Post Types</h3>
        <div class="ww-settings-field">
          <p>
            Select each post type you would like to use Widget Wrangler on.
          </p>
            <div class="space-checkboxes">
              <?php
                // loop through post types
                foreach ($post_types as $post_type )
                {
                  $post_type_checked = (isset($settings['post_types'][$post_type])) ? 'checked="checked"' : '';
                  ?>
                  <label class="ww-space-checkbox"><input type="checkbox" name="settings[post_types][<?php print $post_type; ?>]" value="<?php print $post_type; ?>" <?php print $post_type_checked; ?> /><?php print ucfirst($post_type); ?> </label>
                  <?php
                }
              ?>
            </div>
        </div>
        <input class="button" type="submit" value="Save Settings" />
      </form>
      
      <form action="edit.php?post_type=widget&page=ww-settings&ww-settings-action=reset&noheader=true" method="post">
        <h3 class="ww-settings-title">Mass Reset</h3>
        <div class="ww-settings-field">
          <p>
            <span style="color: red;">WARNING!</span>  If you click this button, all pages will lose their widget sidebar and order settings and will fall back on the default settings.</p>
            <input class="button" type="submit" value="Reset All Widgets to Default" onclick="return confirm('Are you Really sure you want to Reset widget settings on all pages?');" />
          </p>
        </div>
      </form>
    </div>
	<?php
}