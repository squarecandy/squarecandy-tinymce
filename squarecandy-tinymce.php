<?php
/*
Plugin Name: Square Candy TinyMCE Reboot
Plugin URI: https://github.com/squarecandy/squarecandy-tinymce
GitHub Plugin URI: https://github.com/squarecandy/squarecandy-tinymce
Description: An opinionated reconfiguration of the default WordPress TinyMCE settings.
Version: 1.2.3
Author: Peter Wise
Author URI: http://squarecandydesign.com
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Text Domain: squarecandy-tinymce
*/

define( 'SQUARECANDY_TINYMCE_DIR_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Add options to the Writing options page
 * @link https://trepmal.com/2011/03/07/add-field-to-general-settings-page/
 * thanks to trepmal
 */
require SQUARECANDY_TINYMCE_DIR_PATH . 'class-squarecandy-tinymce-writing-settings.php';

$new_writing_settings = new SquareCandy_TinyMCE_Writing_Settings();

function squarecandy_tinymce_enqueue_scripts() {
	if ( get_option( 'sqcdy_allow_color_picker' ) ) :
		// add colorpicker js to the admin
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'squarecandy-tinymce', plugins_url( 'colorpick.js', __FILE__ ), array( 'wp-color-picker' ), '1.2.4', true );
	endif;
}
add_action( 'admin_enqueue_scripts', 'squarecandy_tinymce_enqueue_scripts' );

// TinyMCE Customization
function make_mce_awesome( $in ) {
	$customcolors       = array();
	$custom_colors      = '"000000","Black","222222","Almost Black","404040","Dark Gray","636363","Gray","939393","Light Gray","AAAAAA","Lightest Gray"';
	$sqcdy_theme_color1 = get_option( 'sqcdy_theme_color1' );
	$sqcdy_theme_color1 = preg_replace( '/[^A-Fa-f0-9]/', '', $sqcdy_theme_color1 );
	$sqcdy_theme_color2 = get_option( 'sqcdy_theme_color2' );
	$sqcdy_theme_color2 = preg_replace( '/[^A-Fa-f0-9]/', '', $sqcdy_theme_color2 );
	$sqcdy_theme_color3 = get_option( 'sqcdy_theme_color3' );
	$sqcdy_theme_color3 = preg_replace( '/[^A-Fa-f0-9]/', '', $sqcdy_theme_color3 );
	$sqcdy_theme_color4 = get_option( 'sqcdy_theme_color4' );
	$sqcdy_theme_color4 = preg_replace( '/[^A-Fa-f0-9]/', '', $sqcdy_theme_color4 );
	if ( ! empty( $sqcdy_theme_color1 ) ) {
		$custom_colors .= ',"' . $sqcdy_theme_color1 . '","Branding Color 1"';
	}
	if ( ! empty( $sqcdy_theme_color2 ) ) {
		$custom_colors .= ',"' . $sqcdy_theme_color2 . '","Branding Color 2"';
	}
	if ( ! empty( $sqcdy_theme_color3 ) ) {
		$custom_colors .= ',"' . $sqcdy_theme_color3 . '","Branding Color 3"';
	}
	if ( ! empty( $sqcdy_theme_color4 ) ) {
		$custom_colors .= ',"' . $sqcdy_theme_color4 . '","Branding Color 4"';
	}
	$in['textcolor_map'] = '[' . $custom_colors . ']';

	$in['block_formats'] = 'paragraph=p;big heading (h2)=h2;medium heading (h3)=h3;small heading (h4)=h4;generic box (div)=div';

	$colorpicker    = get_option( 'sqcdy_allow_color_picker' ) ? 'forecolor,' : '';
	$in['toolbar1'] = 'formatselect,styleselect,bold,italic,alignleft,aligncenter,alignright,bullist,numlist,blockquote,hr,' . $colorpicker . 'link,unlink,pastetext,undo,redo,removeformat';
	$in['toolbar2'] = '';
	$in['toolbar3'] = '';
	$in['toolbar4'] = '';

	return $in;
}
add_filter( 'tiny_mce_before_init', 'make_mce_awesome' );


// make the same changes to ACF editors too
function squarecandy_tinymce_toolbars( $toolbars ) {

	$colorpicker = get_option( 'sqcdy_allow_color_picker' ) ? 'forecolor' : '';

	$toolbars['Full']    = array();
	$toolbars['Full'][1] = array( 'formatselect', 'styleselect', 'bold', 'italic', 'alignleft', 'aligncenter', 'alignright', 'bullist', 'numlist', 'blockquote', 'hr', $colorpicker, 'link', 'unlink', 'pastetext', 'undo', 'redo', 'removeformat' );
	$toolbars['Full'][2] = array();

	$toolbars['Basic']    = array();
	$toolbars['Basic'][1] = array( 'bold', 'italic', 'alignleft', 'aligncenter', 'alignright', $colorpicker, 'link', 'unlink', 'removeformat' );

	return $toolbars;
}
add_filter( 'acf/fields/wysiwyg/toolbars', 'squarecandy_tinymce_toolbars' );


// remove some stylesheets
function squarecandy_tinymce_remove_mce_css( $stylesheets ) {
	$stylesheets = explode( ',', $stylesheets );

	$remove = get_option( 'sqcdy_remove_theme_editor_css', 'on' );

	foreach ( $stylesheets as $key => $sheet ) {

		// remove default WordPress TinyMCE css
		if ( preg_match( '/wp\-includes/', $sheet ) ) {
			unset( $stylesheets[ $key ] );
		}
		// remove editor styles from Theme
		if ( 'on' === $remove && preg_match( '/editor/', $sheet ) && ! preg_match( '/squarecandy\-tinymce/', $sheet ) ) {
			unset( $stylesheets[ $key ] );
		}
	}

	$stylesheets = implode( ',', $stylesheets );
	return $stylesheets;
}
add_filter( 'mce_css', 'squarecandy_tinymce_remove_mce_css' );


// Add styles to the TinyMCE editor window to make it look more like your site's front end
function squarecandy_tinymce_add_editor_styles() {

	$sqcdy_include_theme_style_css = get_option( 'sqcdy_include_theme_style_css' );
	if ( 'on' === $sqcdy_include_theme_style_css ) {
		add_editor_style( get_stylesheet_directory_uri() . '/style.css' );
	}

	$sqcdy_theme_colwidth = get_option( 'sqcdy_theme_colwidth' );
	if ( ! empty( $sqcdy_theme_colwidth ) ) {
		add_editor_style( plugins_url( 'dynamic.css.php', __FILE__ ) );
	}

	$sqcdy_theme_css = get_option( 'sqcdy_theme_css' );
	if ( ! empty( $sqcdy_theme_css ) ) {
		$all_css = explode( "\n", $sqcdy_theme_css );
		foreach ( $all_css as $css ) {
			$css = preg_replace( '/\s/', '', $css );
			add_editor_style( $css );
		}
	}

	if ( file_exists( get_stylesheet_directory() . '/squarecandy-tinymce-editor-style.css' ) ) {
		add_editor_style( get_stylesheet_directory_uri() . '/squarecandy-tinymce-editor-style.css' );
	} elseif ( file_exists( get_stylesheet_directory() . '/dist/css/squarecandy-tinymce-editor-style.min.css' ) ) {
		add_editor_style( get_stylesheet_directory_uri() . '/dist/css/squarecandy-tinymce-editor-style.min.css' );
	} else {
		add_editor_style( plugins_url( 'squarecandy-tinymce-editor-style.css', __FILE__ ) );
	}

	if ( file_exists( get_stylesheet_directory() . '/frontend-style.css' ) ) {
		add_editor_style( get_stylesheet_directory_uri() . '/frontend-style.css' );
	} else {
		add_editor_style( plugins_url( 'frontend-style.css', __FILE__ ) );
	}

}
add_action( 'admin_init', 'squarecandy_tinymce_add_editor_styles' );


// add the frontend-style.css to the front end display as well
function squarecandy_tinymce_frontendstyle() {
	// check if an admin has disabled frontend-style.css
	if ( get_option( 'sqcdy_remove_frontend_style_css', false ) ) {
		return;
	}

	if ( file_exists( get_stylesheet_directory() . '/frontend-style.css' ) ) {
		// check if an override exists
		wp_enqueue_style( 'onebeat-style', get_stylesheet_directory_uri() . '/frontend-style.css', array(), '1.2.4' );
	} else {
		// load the default copy
		wp_enqueue_style( 'onebeat-style', plugins_url( 'frontend-style.css', __FILE__ ), array(), '1.2.4' );
	}
}
add_action( 'wp_enqueue_scripts', 'squarecandy_tinymce_frontendstyle' );


// Callback function to insert 'styleselect' into the $buttons array
function squarecandy_tinymce_mce_buttons( $buttons ) {
	$buttons[] = 'styleselect';
	return $buttons;
}
add_filter( 'mce_buttons', 'squarecandy_tinymce_mce_buttons' );


// Callback function to filter the MCE settings
function squarecandy_tinymce_mce_before_init( $init_array ) {
	// Define the style_formats array
	$style_formats = array(
		array(
			'title'   => 'button',
			'block'   => 'p',
			'classes' => 'button-container',
			'wrapper' => false,
		),
		array(
			'title'   => 'smaller text',
			'block'   => 'span',
			'classes' => 'small',
			'wrapper' => false,
		),
		array(
			'title'   => 'bigger text',
			'block'   => 'span',
			'classes' => 'big',
			'wrapper' => false,
		),
		array(
			'title'   => 'quote author',
			'block'   => 'div',
			'classes' => 'quote-author',
			'wrapper' => false,
		),
	);
	// Insert the array, JSON ENCODED, into 'style_formats'
	$init_array['style_formats'] = wp_json_encode( $style_formats );

	// clean code on paste (works with Word, Google Docs, copy-paste from other web pages)
	$init_array['paste_preprocess'] = "function(plugin, args){
		// Strip all HTML tags except those we have whitelisted
		var whitelist = 'p,b,strong,i,em,h2,h3,h4,h5,h6,ul,li,ol,a,href';
		var stripped = jQuery('<div>' + args.content + '</div>');
		var els = stripped.find('*').not(whitelist);
		for (var i = els.length - 1; i >= 0; i--) {
			var e = els[i];
			jQuery(e).replaceWith(e.innerHTML);
		}
		// Strip all class and id attributes
		stripped.find('*').removeAttr('id').removeAttr('class').removeAttr('style');
		// Return the clean HTML
		args.content = stripped.html();
	}";

	return $init_array;
}
add_filter( 'tiny_mce_before_init', 'squarecandy_tinymce_mce_before_init' );

