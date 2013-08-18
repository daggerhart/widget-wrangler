=== Widget Wrangler ===
Contributors: daggerhart
Donate link: http://www.daggerhart.com/wordpress/widget-wrangler#donate
Tags: widget, admin, widgets, administration, sidebar, manage
Requires at least: 3
Tested up to: 3.2.1
Stable tag: trunk

A plugin for managing the display of widgets on a page by page basis. Using widgets as a post type.

== Description ==

Widget Wrangler is a plugin for Wordpress that gives administrators a clean interface for managing widgets on a page by page basis.

Widget Wrangler provides widgets as a post type, gives you the ability to copy existing wordpress widgets into the wrangler system, and provides shortcode support for individual widgets.

Create and manage multiple "corrals" (groups of widgets, like sidebars) per page.
It's similar to Drupal Blocks, but on every page, with the ability to set the default location of the widgets in specific sidebars.

Screencasts

* [Getting Started Screencast](http://screencast.com/t/YjUwNDM3Zjk)
* [Basic Examples w/ Advanced Parsing Screencast](http://screencast.com/t/NjI2NDYzY)
* [Templating & PHP](http://screencast.com/t/YmI2Mjg1NT)


== Installation ==

1. Upload `widget-wrangler` to the `/wp-content/plugins/` directory
1. Activate the plugin through the `Plugins` menu in WordPress
1. Add a new corral under the Widget Wrangler Corrals menu item
1. Begin adding widgets under the Widget Wrangler Add New menu
1. Set default widgets on the Widget Wrangler Set Defaults page
1. (Corral Display Option A) Add the function 'ww_dynamic_corral(corral_id);' to your page templates. Normally, you will want to replace the existing 'dynamic_corral()' function with this one.
1. (Corral Display Option B) If you don't want to edit your template files, you can add a Widget Wrangler Corral to your existing corrals by using the "Widget Wrangler - Corral" widget in the standard Wordpress Widget area.

== Frequently Asked Questions ==

= How do I display a single widget within a Page's content? =

Widget Wrangler comes with built in shortcode support for each widget.  To show a single widget in a content area, use the shortcode pattern of [ww_widget id={post_id}].
ie. [ww_widget id=240] where 240 is the post_id of the widget.

= How do I find out a widget's post ID? =

A widget's post ID is displayed in the 'Options' panel when editing that widget.

= How do I display my widgets? =

There are two ways to accomplish this.   The easiest way is to go to the standard Widget area under the Appearance admin menu item and drag the "Widget Wrangler - Corral" widget into the wordpress sidebar where it should appear, then select which "Widget Wrangler - Corral" should be shown from the widget's options.
The other way requires you to edit your template files and find any instance of the dynamic_sidebar() function.  Replace these functions with ww_dynamic_corral(corral_id).

= How can I control the widget's template (HTML)? =

In the widget-wrangler directory there is a file named 'templates/widget.php'.  Copy this file to your theme's root directory.  You can edit the HTML in the file to have widgets appear as you want.
To template specific widgets, save a copy of as widget-[widget ID].php in your theme directory (eg. widget-121.php, where 121 is the widget's ID). Other templates are also available and are suggested on a widget's edit page in the Advanced Help area.

= Can I use existing wordpress widgets? = 

Mostly. Widgets designed for Wordpress 3+ are able to be used with the 'Copy Widget' option in the Widget Wrangler menu.  Widgets programmed for older versions of wordpress may not work.

= What does it mean to Copy a widget? =

When you Copy a wordpress widget, it creates a new widget post in the Widget Wrangler system with the settings for the original wordpress widget.


== Screenshots ==

1. Widget Page Manager
1. Individual Widget
1. Cloned Wordpress Widget
1. WW Sidebar Widget

== Changelog ==
= 2.0 =

* Major changes to the function of the plugin.  Saving data to custom tables, many new features.

= 1.4.2 =

* Bug fix: Posts page widgets not saving correctly

= 1.4.1 =

* Bug fix: Forgot to add new images to svn

= 1.4 =

* Feature: Preview Widget on the widget's edit page
* Feature: Add WW sidebars using Wordpress's standard widget system
* Bug fix: Issue with cloning specific wordpress widgets
* Bug fix: Images not showing up in non-standard install directories

= 1.3.2 =

* Bug fix: upgrading to 1.3.1 had no post type settings. Trouble fixing.

= 1.3.1 =

* Bug fix: upgrading to 1.3 had no post type settings. Grrr, sry.

= 1.3 =

* Feature: Now use Widget Wrangler on any post type and the blog page (Posts page).
* Feature: Use template with advanced parsed widget.
* Feature: Set widgets for the home/frontpage when using Wordpress's 'Reading Setting' for 'Front page displays' as 'Your latest posts'. Settings >> Reading >> Front page displays.
* Programming: Refactored function names to standardize concepts and descriptors

= 1.2.1 =

* Feature: Additional template options per widget (see FAQ: How can I control a widget's template)
* Fixed Cloned widgets now get templating
* Fixed minor widget admin panel display bug
* Fixed form content in advanced parsing area
* Tested successfully with cloning Buddypress widgets

= 1.2 =

* Official Release: Incremented version due to additional features
* Feature: Reset a page's widgets to default (checkbox on page)
* Feature: Reset all pages to use default widgets (settings page)
* Feature: Disable all widgets on a page (drag to disabled list)

= 1.1rc8 =

* Fix for javascript with wordpress 3.2 update.  Added option to reset all widgets on all pages.

= 1.1rc7 =

* Fix for javacsript not loading correctly.   Changed method to use script wp_enqueue_script.

= 1.1rc6 =

* Fix for disappearing menu items with wordpress 3.1 update.  

= 1.1rc5 =

* IE 8 Bug fixes.
* Clone Widget widget name now filling into advanced parse correctly.

= 1.1rc4 =

* Bug fix from rc3 changes.   Capability for access to submenus corrected.

= 1.1rc3 =

* Added 'Auto Paragraph' checkbox for each widget
* Added a basic level of capability control.  Now possible to change capability type for use with other plugins.

= 1.1rc2 =

* Found another important bug related to recent changes. 

= 1.1rc1 =

* Fixed bug where disabled widgets disappear
* Fixed the need to save multiple times when enabling widgets
* Fixed disappearance of widgets assigned to deleted sidebars

= 1.1beta =
Initial Release

== Upgrade Notice ==

2.0 Significant changes and improvements.
