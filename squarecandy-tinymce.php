<?php
/*
Plugin Name: Square Candy TinyMCE Reboot
Plugin URI: https://github.com/squarecandy/squarecandy-tinymce
GitHub Plugin URI: https://github.com/squarecandy/squarecandy-tinymce
Description: An opinionated reconfiguration of the default WordPress TinyMCE settings.
Version: 1.3.0
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
		wp_enqueue_script( 'squarecandy-tinymce', plugins_url( 'colorpick.js', __FILE__ ), array( 'wp-color-picker' ), '1.3.0', true );
	endif;
}
add_action( 'admin_enqueue_scripts', 'squarecandy_tinymce_enqueue_scripts' );

// TinyMCE Customization
function make_mce_awesome( $in ) {
	$custom_colors      = array(
		'"000000","Black"',
		'"222222","Almost Black"',
		'"404040","Dark Gray"',
		'"636363","Gray"',
		'"939393","Light Gray"',
		'"AAAAAA","Lightest Gray"',
	);
	$sqcdy_theme_color1 = get_option( 'sqcdy_theme_color1' );
	$sqcdy_theme_color1 = preg_replace( '/[^A-Fa-f0-9]/', '', $sqcdy_theme_color1 );
	$sqcdy_theme_color2 = get_option( 'sqcdy_theme_color2' );
	$sqcdy_theme_color2 = preg_replace( '/[^A-Fa-f0-9]/', '', $sqcdy_theme_color2 );
	$sqcdy_theme_color3 = get_option( 'sqcdy_theme_color3' );
	$sqcdy_theme_color3 = preg_replace( '/[^A-Fa-f0-9]/', '', $sqcdy_theme_color3 );
	$sqcdy_theme_color4 = get_option( 'sqcdy_theme_color4' );
	$sqcdy_theme_color4 = preg_replace( '/[^A-Fa-f0-9]/', '', $sqcdy_theme_color4 );
	if ( ! empty( $sqcdy_theme_color1 ) ) {
		$custom_colors[] = '"' . $sqcdy_theme_color1 . '","Branding Color 1"';
	}
	if ( ! empty( $sqcdy_theme_color2 ) ) {
		$custom_colors[] = '"' . $sqcdy_theme_color2 . '","Branding Color 2"';
	}
	if ( ! empty( $sqcdy_theme_color3 ) ) {
		$custom_colors[] = '"' . $sqcdy_theme_color3 . '","Branding Color 3"';
	}
	if ( ! empty( $sqcdy_theme_color4 ) ) {
		$custom_colors[] = '"' . $sqcdy_theme_color4 . '","Branding Color 4"';
	}
	$custom_colors       = apply_filters( 'squarecandy_tinymce_filter_colors', $custom_colors );
	$custom_colors       = implode( ',', $custom_colors );
	$in['textcolor_map'] = '[' . $custom_colors . ']';

	$block_formats       = array(
		'paragraph=p',
		'big heading (h2)=h2',
		'medium heading (h3)=h3',
		'small heading (h4)=h4',
		'generic box (div)=div',
	);
	$block_formats       = apply_filters( 'squarecandy_tinymce_filter_block_formats', $block_formats );
	$block_formats       = implode( '; ', $block_formats );
	$in['block_formats'] = $block_formats;

	$colorpicker = get_option( 'sqcdy_allow_color_picker' ) ? 'forecolor' : false;
	$toolbar1    = array(
		'formatselect',
		'styleselect',
		'bold',
		'italic',
		'alignleft',
		'aligncenter',
		'alignright',
		'bullist',
		'numlist',
		'blockquote',
		'hr',
	);
	if ( $colorpicker ) {
		$toolbar1[] = $colorpicker;
	}
	$toolbar1[] = 'link';
	$toolbar1[] = 'unlink';
	$toolbar1[] = 'pastetext';
	$toolbar1[] = 'undo';
	$toolbar1[] = 'redo';
	$toolbar1[] = 'removeformat';

	$toolbar1       = apply_filters( 'squarecandy_tinymce_filter_toolbar', $toolbar1 );
	$toolbar1       = implode( ',', $toolbar1 );
	$in['toolbar1'] = $toolbar1;
	$in['toolbar2'] = '';
	$in['toolbar3'] = '';
	$in['toolbar4'] = '';

	return $in;
}
add_filter( 'tiny_mce_before_init', 'make_mce_awesome', 99 );


// make the same changes to ACF editors too
function squarecandy_tinymce_toolbars( $toolbars ) {

	$colorpicker = get_option( 'sqcdy_allow_color_picker' ) ? 'forecolor' : '';

	$toolbars['Full']    = array();
	$toolbar1            = array( 'formatselect', 'styleselect', 'bold', 'italic', 'alignleft', 'aligncenter', 'alignright', 'bullist', 'numlist', 'blockquote', 'hr', $colorpicker, 'link', 'unlink', 'pastetext', 'undo', 'redo', 'removeformat' );
	$toolbar1            = apply_filters( 'squarecandy_tinymce_filter_toolbar', $toolbar1 );
	$toolbars['Full'][1] = $toolbar1;
	$toolbars['Full'][2] = array();

	$toolbars['Basic']    = array();
	$toolbar_basic        = array( 'bold', 'italic', 'alignleft', 'aligncenter', 'alignright', $colorpicker, 'link', 'unlink', 'removeformat' );
	$toolbar_basic        = apply_filters( 'squarecandy_tinymce_filter_toolbar_basic', $toolbar_basic );
	$toolbars['Basic'][1] = $toolbar_basic;

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
		wp_enqueue_style( 'squarecandy-tinymce-style', get_stylesheet_directory_uri() . '/frontend-style.css', array(), '1.3.0' );
	} else {
		// load the default copy
		wp_enqueue_style( 'squarecandy-tinymce-style', plugins_url( 'frontend-style.css', __FILE__ ), array(), '1.3.0' );
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
		'button'       => array(
			'title'   => 'button',
			'block'   => 'p',
			'classes' => 'button-container',
			'wrapper' => false,
		),
		'smaller_text' => array(
			'title'   => 'smaller text',
			'block'   => 'span',
			'classes' => 'small',
			'wrapper' => false,
		),
		'bigger_text'  => array(
			'title'   => 'bigger text',
			'block'   => 'span',
			'classes' => 'big',
			'wrapper' => false,
		),
		'quote_author' => array(
			'title'   => 'quote author',
			'block'   => 'div',
			'classes' => 'quote-author',
			'wrapper' => false,
		),
	);
	$style_formats = apply_filters( 'squarecandy_tinymce_filter_style_formats', $style_formats );
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

