# squarecandy-tinymce

An opinionated reconfigureation of the default wordpress TinyMCE settings.

[squarecandy.github.io/squarecandy-tinymce](http://squarecandy.github.io/squarecandy-tinymce/)

## Overview

WordPress is awesome, but some of the TinyMCE rich text editing options that come out of the box are not ideal for less web-savvy editors and clients.  This plugin provides some modifications to the standard editor buttons and features that will make it easier for clients to have as all the formatting options that are good for them and remove options that can break a custom site design.

## Features

* Removes the toggle advanced button. This is confusing to many users who can't find the features they are looking for. Just provides 1 row of editor buttons.
* Removes features such as strike through, underline (should be reserved for links online, not emphasis), full justification (generally looks bad online, but clients like to use it), and several other less used and confusing features.
* Simplifies the dropdown list of heading and block types available.
* Adds several formats that can be added to a paragraph
    * Button - add this format to a paragraph to make the links into "call to action" buttons
    * Small - a format for create a paragraph of smaller text, such as a "terms & conditions" section.
    * Big - enlarge the text of a certain paragraph.  This helps client not mis-use heading elements for entire paragraphs when they want slightly larger text for emphasis.
* Limits color palette in the text colorpicker to grayscale only by default.
* Allows for adding up to 4 custom colors that compliment the site's design and branding that the user may use. (Under Settings > Writing)
* Adds the current theme's style.css to the editor to make what you see in TinyMCE match the live site better
* Allows for adding additional css files to the editor styles.  These could be external resources such as google font stylesheets, additional theme css you want to load in the editor, or a custom stylesheet. Just add the URL you want to add in the Additional CSS field under Settings > Writing. For multiple files, add one per line.
* Cleans up pasted content copied from other websites, Word, Google Docs, etc.

## Scope

As the description states, this plugin makes **opinionated** changes to the default setup. I don't plan to grow this into a bulkier plugin that allows you to modify and customize all aspects of TinyMCE.  Try [TinyMCE Advanced](https://wordpress.org/plugins/tinymce-advanced/) or other existing plugins for that purpose.

## What About Gutenberg?

Gutenberg will be great for some things, but we also believe that using custom fields (via ACF) and a basic WYSIWYG body editing area has a place in WordPress development.

Continued use of this plugin should be used in conjunction with the [Classic Editor](https://wordpress.org/plugins/classic-editor/) plugin in most circumstances.

## History

### v2.0.0 - 2025-01-23

* Force Open in New Window to always start unchecked
* Replace YouTube and Vimeo iframes with link on paste
* Only allow very restricted TinyMCE on front end: bold, italics, links. (For WPForms or other front end uses)
* Square Candy Views 2: use different css override file name
* Add version constant & add variables for reused values

### v1.3.1 - 2020-10-06

* fix: don't strip br, hr, blockquote on paste
* fix: css file overrides, allow in both child and parent theme.
* fix: don't load frontend-style.css in editor if disabled in settings
* fix: `$_SERVER` vars must be quoted in php 7.4+

### v1.3.0 - 2019-09-11

* add phpcs rules and do plugin code cleanup
* New feature: Clean HTML pasted into the visual editor

### v1.2.3

* add option to turn off colorpicker

### v1.2.2

* add option to turn off frontend-style.css

### v1.2.1

* Add compatibility with GitHub Updater

### v1.2.0

* Add clear formatting button
* bugfixes
* adds dotted line to define right side of editor column width
* use tabs and other corrections to conform to WordPress coding standards

### v1.1.x

* Adds column width option
* Adds checkbox to optionally add the theme style.css
* Adds a check to optionally remove editor-style.css or editor.css from the existing theme

### v1.0.0

* The very first version!
