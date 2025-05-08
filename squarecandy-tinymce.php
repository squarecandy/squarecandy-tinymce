<?php
/*
Plugin Name: Square Candy TinyMCE Reboot
Plugin URI: https://github.com/squarecandy/squarecandy-tinymce
GitHub Plugin URI: https://github.com/squarecandy/squarecandy-tinymce
Description: An opinionated reconfiguration of the default WordPress TinyMCE settings.
Version: 2.1.0-dev.5
Author: Peter Wise
Author URI: http://squarecandydesign.com
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Text Domain: squarecandy-tinymce
*/

define( 'SQUARECANDY_TINYMCE_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'SQUARECANDY_TINYMCE_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'SQUARECANDY_TINYMCE_VERSION', 'version-2.1.0-dev.5' );
/**
 * Add options to the Writing options page
 * @link https://trepmal.com/2011/03/07/add-field-to-general-settings-page/
 * thanks to trepmal
 */
require SQUARECANDY_TINYMCE_DIR_PATH . 'inc/class-squarecandy-tinymce-writing-settings.php';

$new_writing_settings = new SquareCandy_TinyMCE_Writing_Settings();

// set up embed manager
require SQUARECANDY_TINYMCE_DIR_PATH . 'inc/class-sqc-embed-manager.php';
$sqc_embeds = new SQC_Embed_Manager();

function squarecandy_tinymce_enqueue_scripts() {
	if ( get_option( 'sqcdy_allow_color_picker' ) ) :
		// add colorpicker js to the admin
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'squarecandy-tinymce', SQUARECANDY_TINYMCE_DIR_URL . 'js/colorpick.js', array( 'wp-color-picker' ), SQUARECANDY_TINYMCE_VERSION, true );
	endif;
	wp_enqueue_script( 'squarecandy-tinymce', SQUARECANDY_TINYMCE_DIR_URL . 'js/squarecandy-tinymce.js', array(), SQUARECANDY_TINYMCE_VERSION, true );

	wp_enqueue_style( 'squarecandy-tinymce-admin-css', SQUARECANDY_TINYMCE_DIR_URL . 'css/admin.css', array(), SQUARECANDY_TINYMCE_VERSION );
}
add_action( 'admin_enqueue_scripts', 'squarecandy_tinymce_enqueue_scripts' );

// TinyMCE Customization
function make_mce_awesome( $in ) {

	// bail if we're not in the admin (don't load these settings on the front end)
	if ( ! is_admin() ) {
		return $in;
	}

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
	$toolbar1[] = 'sqc_embed_button';

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
	$stylesheet_directory_uri = get_stylesheet_directory_uri();
	$stylesheet_directory     = get_stylesheet_directory();
	$template_directory       = get_template_directory();

	$sqcdy_include_theme_style_css = get_option( 'sqcdy_include_theme_style_css' );
	if ( 'on' === $sqcdy_include_theme_style_css ) {
		add_editor_style( $stylesheet_directory_uri . '/style.css' );
	}

	$sqcdy_theme_colwidth = get_option( 'sqcdy_theme_colwidth' );
	if ( ! empty( $sqcdy_theme_colwidth ) ) {
		add_editor_style( SQUARECANDY_TINYMCE_DIR_URL . 'inc/dynamic.css.php' );
	}

	$sqcdy_theme_css = get_option( 'sqcdy_theme_css' );
	if ( ! empty( $sqcdy_theme_css ) ) {
		$all_css = explode( "\n", $sqcdy_theme_css );
		foreach ( $all_css as $css ) {
			$css = preg_replace( '/\s/', '', $css );
			add_editor_style( $css );
		}
	}

	$file_name_base = 'squarecandy-tinymce-editor-style';
	if ( function_exists( 'sqcdy_is_views2' ) && sqcdy_is_views2() ) {
		$file_name_base = 'squarecandy-tinymce-editor-style-views2';
	}

	// if both child and parent override files exist (meaning a child theme is active), load both
	if (
		file_exists( $stylesheet_directory . '/' . $file_name_base . '.css' ) &&
		file_exists( $template_directory . '/' . $file_name_base . '.css' ) &&
		$stylesheet_directory !== $template_directory
	) {
		add_editor_style( get_template_directory_uri() . '/' . $file_name_base . '.css' );
	} elseif (
		file_exists( $stylesheet_directory . '/dist/css/' . $file_name_base . '.min.css' ) &&
		file_exists( $template_directory . '/dist/css/' . $file_name_base . '.min.css' ) &&
		$stylesheet_directory !== $template_directory
	) {
		add_editor_style( get_template_directory_uri() . '/dist/css/' . $file_name_base . '.min.css' );
	}

	// add override stylesheets from theme or child theme directory locations
	if ( file_exists( $stylesheet_directory . '/' . $file_name_base . '.css' ) ) {
		add_editor_style( $stylesheet_directory_uri . '/' . $file_name_base . '.css' );
	} elseif ( file_exists( $stylesheet_directory . '/dist/css/' . $file_name_base . '.min.css' ) ) {
		add_editor_style( $stylesheet_directory_uri . '/dist/css/' . $file_name_base . '.min.css' );
	} else {
		add_editor_style( SQUARECANDY_TINYMCE_DIR_URL . 'css/' . $file_name_base . '.css' );
	}

	// fontend-style overrides
	if ( ! get_option( 'sqcdy_remove_frontend_style_css', false ) ) :
		if ( file_exists( $stylesheet_directory . '/frontend-style.css' ) ) {
			add_editor_style( $stylesheet_directory_uri . '/frontend-style.css' );
		} else {
			add_editor_style( SQUARECANDY_TINYMCE_DIR_URL . 'css/frontend-style.css' );
		}
	endif;

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
		wp_enqueue_style( 'squarecandy-tinymce-style', get_stylesheet_directory_uri() . '/frontend-style.css', array(), SQUARECANDY_TINYMCE_VERSION );
	} else {
		// load the default copy
		wp_enqueue_style( 'squarecandy-tinymce-style', SQUARECANDY_TINYMCE_DIR_URL . 'frontend-style.css', array(), SQUARECANDY_TINYMCE_VERSION );
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

	if ( is_admin() ) {
		$allowlist = 'p,b,strong,i,em,h2,h3,h4,h5,h6,ul,li,ol,a,href,br,hr,blockquote';
	} else {
		// more limited allow list for the front end
		$allowlist = 'p,b,strong,i,em,a,href,br';
	}

	$added_paste_preprocess = apply_filters( 'squarecandy_tinymce_before_paste_preprocess', '' );

	$strip_paste_preprocess = "
		// Strip all HTML tags except those we have allow-listed
		var allowlist = '" . $allowlist . "';
		var stripped = jQuery('<div>' + args.content + '</div>');
		var els = stripped.find('*').not(allowlist);
		for (var i = els.length - 1; i >= 0; i--) {
			var e = els[i];
			jQuery(e).replaceWith(e.innerHTML);
		}
		// Strip all class and id attributes
		stripped.find('*').removeAttr('id').removeAttr('class').removeAttr('style');
		// Return the clean HTML
		args.content = stripped.html();
		";

	// clean code on paste (works with Word, Google Docs, copy-paste from other web pages)
	$init_array['paste_preprocess'] = 'function(plugin, args){' .
		$added_paste_preprocess .
		$strip_paste_preprocess .
		'}';

	return $init_array;
}
add_filter( 'tiny_mce_before_init', 'squarecandy_tinymce_mce_before_init' );


function squarecandy_tiny_mce_init() {
	?>
	<script type="text/javascript">
		jQuery(function () {
			jQuery('input#wp-link-target').prop('checked',false);
			jQuery( document ).on('click', 'div[aria-label*="edit link"]', function(){
				jQuery('input#wp-link-target').prop('checked',false);
			});
		});
	</script>
	<?php
}
add_action( 'before_wp_tiny_mce', 'squarecandy_tiny_mce_init' );


// Force the front end editors to be much simpler
add_filter( 'wp_editor_settings', 'squarecandy_frontend_wp_editor_settings', 9999, 2 );
function squarecandy_frontend_wp_editor_settings( $settings, $editor_id ) {
	// bail if we're not on the front end
	if ( is_admin() ) {
		return $settings;
	}

	// simplify the toolbar and plugins
	$settings['tinymce']['plugins']  = 'link,paste,tabfocus,wptextpattern';
	$settings['tinymce']['toolbar1'] = 'bold,italic,link,unlink,removeformat';
	$settings['tinymce']['toolbar2'] = '';

	// turn off the access to the HTML view
	$settings['quicktags'] = false;

	return $settings;
}
