(function($) {
	//******* Load plugin specific language pack
	//tinymce.PluginManager.requireLangPack('example');
	$(document).ready(function(){
		tinymce.create('tinymce.plugins.WW', {
			/**
			 * Initializes the plugin, this will be executed after the plugin has been created.
			 * This call is done before the editor instance has finished it's initialization so use the onInit event
			 * of the editor instance to intercept that event.
			 *
			 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
			 * @param {string} url Absolute URL to where the plugin is located.
			 */
			init : function(ed, url) {},
	
			/**
			 * Creates control instances based in the incomming name. This method is normally not
			 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
			 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
			 * method can be used to create those.
			 *
			 * @param {String} n Name of the control to create.
			 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
			 * @return {tinymce.ui.Control} New control instance or null if no control was created.
			 */
			createControl : function(n, cm) {
				if(n=='WW'){
					var mlb = cm.createListBox('WWList', {
						title : 'WidgetWrangler',
						
						onselect : function(v) { //Option value as parameter
							if (v != ""){
								tinyMCE.activeEditor.selection.setContent('[ww_widget slug="' + v + '"]');
							}
						}
					});

					// Add some values to the list box

					for (var i=0; i < WidgetWrangler.data.allWidgets.length; i++){
						WW_widget = WidgetWrangler.data.allWidgets[i];
						WW_widget.post_title = (WW_widget.post_title) ? WW_widget.post_title : "(no title) ID# "+WW_widget.ID;
						//console.log(WW_widget);
						mlb.add(WW_widget.post_title, WW_widget.post_name);
					}
					
					// Return the new listbox instance
					return mlb;
        }
							 
				return null;
			},
	
			/**
			 * Returns information about the plugin as a name/value array.
			 * The current keys are longname, author, authorurl, infourl and version.
			 *
			 * @return {Object} Name/value array containing information about the plugin.
			 */
			getInfo : function() {
				return {
					longname : 'WidgetWrangler Shortcodes',
					author : 'daggerhart',
					authorurl : 'http://websmiths.co',
					infourl : 'http://marquex.es/387/adding-a-select-box-to-wordpress-tinymce-editor',
					version : "0.1"
				};
			}
		});
	
		// Register plugin
		tinymce.PluginManager.add('WW', tinymce.plugins.WW);
	});
})(jQuery);
