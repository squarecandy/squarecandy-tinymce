=== Square Candy TinyMCE Reboot ===
Contributors: squarecandy
Donate link: http://squarecandy.github.io/squarecandy-tinymce/
Tags: tinymce, editor
Requires at least: 4.0.1
Tested up to: 4.4
Stable tag: 1.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt

An opinionated reconfiguration of the default WordPress TinyMCE settings.

== Description ==

= Overview =

WordPress is awesome, but some of the TinyMCE rich text editing options that come out of the box are not ideal for less web-savvy editors and clients.  This plugin provides some modifications to the standard editor buttons and features that will make it easier for clients to have as all the formatting options that are good for them and remove options that can break a custom site design.

= Features =

* Removes the toggle advanced button.  This is confusing to many users who can't find the features they are looking for. Just provides 1 row of editor buttons.
* Removes features such as strike through, underline (should be reserved for links online, not emphasis), full justification (generally looks bad online, but clients like to use it), and several other less used and confusing features.
* Simplifies the dropdown list of heading and block types available.
* Adds several formats that can be added to a paragraph
    * Button - add this format to a paragraph to make the links into "call to action" buttons
    * Small - a format for create a paragraph of smaller text, such as a "terms & conditions" section.
    * Big - enlarge the text of a certain paragraph.  This helps client not mis-use heading elements for entire paragraphs when they want slightly larger text for emphasis.
* Limits color palette in the text colorpicker to grayscale only by default.
* Allows for adding up to 4 custom colors that compliment the site's design and branding that the user may use. (Under Settings > Writing)
* Adds the current theme's style.css to the editor to make what you see in TinyMCE match the live site better
* Allows for adding additional css files to the editor styles.  These could be external resources such as google font stylesheets, additional theme css you want to load in the editor, or a custom stylesheet. Just add the URL you want to add in the Additional CSS field under Settings > Writing. For multiple files separate the files by comma with no spaces.

= Scope =

As the description states, this plugin makes **opinionated** changes to the default setup. I don't plan to grow this into a bulkier plugin that allows you to modify and customize all aspects of TinyMCE.  Try [TinyMCE Advanced](https://wordpress.org/plugins/tinymce-advanced/) or other existing plugins for that purpose.

== Installation ==

1. Upload the squarecandy-tinymce directory to the `/wp-content/plugins` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Use the Settings->Writing screen to configure custom colors and other settings if you would like to.
1. Check that the plugin is working by editing an existing page or post.
1. If you want to make changes to `editor-style.css` or `frontend-style.css` just copy them into the root of your active theme directory and they will override the plugin's versions. Don't edit the css in the plugin folder because it may get overwritten on updates. Note that `editor-style.css` applies to the TinyMCE editor on the backend only and `frontend-style.css` applies to both the editor and the front end of WordPress.


== Screenshots ==

1. Example of the modified editor

== Changelog ==

= 1.0 =

* The very first version

= 1.1 =

* Adds column width option
* Adds checkbox to optionally add the theme style.css
* Adds a check to optionally remove editor-style.css or editor.css from the existing theme

= 1.2 = 

* bugfixes
* adds dotted line to define right side of editor column width
* use tabs and other corrections to conform to WordPress coding standards
