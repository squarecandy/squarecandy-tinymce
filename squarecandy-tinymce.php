<?php
/*
Plugin Name: Square Candy TinyMCE Reboot
Plugin URI:  http://squarecandy.net
Description: An opinionated reconfiguration of the default wordpress TinyMCE settings.
Version:	 1.2
Author:	  Peter Wise
Author URI:  http://squarecandy.net
License:	 GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Text Domain: squarecandy-tinymce
*/

// Add options to the Writing options page
// thanks to trepmal - https://trepmal.com/2011/03/07/add-field-to-general-settings-page/
$new_writing_setting = new squarecandy_tinymce_writing_setting();

class squarecandy_tinymce_writing_setting {
	public function squarecandy_tinymce_writing_setting() {
		add_filter('admin_init', array(&$this, 'register_fields'));
	}
	public function register_fields() {
		register_setting('writing', 'sqcdy_theme_color1', 'esc_attr');
		register_setting('writing', 'sqcdy_theme_color2', 'esc_attr');
		register_setting('writing', 'sqcdy_theme_color3', 'esc_attr');
		register_setting('writing', 'sqcdy_theme_color4', 'esc_attr');
		register_setting('writing', 'sqcdy_theme_colwidth', 'esc_attr');
		register_setting('writing', 'sqcdy_theme_css', 'esc_attr');
		register_setting('writing', 'sqcdy_include_theme_style_css', 'esc_attr');
		register_setting('writing', 'sqcdy_remove_theme_editor_css', 'esc_attr');

		add_settings_section('squarecandy_tinymce', 'TinyMCE Reboot', 'squarecandy_tinymce_section_callback', 'writing');
		function squarecandy_tinymce_section_callback() {
			// blank on purpose
		}

		add_settings_field('sqcdy_theme_color1', '<label for="sqcdy_theme_color1">'.__('Theme Color 1', 'sqcdy_theme_color1').'</label>', array(&$this, 'fields1_html'), 'writing', 'squarecandy_tinymce');
		add_settings_field('sqcdy_theme_color2', '<label for="sqcdy_theme_color2">'.__('Theme Color 2', 'sqcdy_theme_color2').'</label>', array(&$this, 'fields2_html'), 'writing', 'squarecandy_tinymce');
		add_settings_field('sqcdy_theme_color3', '<label for="sqcdy_theme_color3">'.__('Theme Color 3', 'sqcdy_theme_color3').'</label>', array(&$this, 'fields3_html'), 'writing', 'squarecandy_tinymce');
		add_settings_field('sqcdy_theme_color4', '<label for="sqcdy_theme_color4">'.__('Theme Color 4', 'sqcdy_theme_color4').'</label>', array(&$this, 'fields4_html'), 'writing', 'squarecandy_tinymce');
		add_settings_field('sqcdy_theme_colwidth', '<label for="sqcdy_theme_colwidth">'.__('Typical Theme Content Column Max Width', 'sqcdy_theme_colwidth').'</label>', array(&$this, 'fields_colwidth_html'), 'writing', 'squarecandy_tinymce');
		add_settings_field('sqcdy_theme_css', '<label for="sqcdy_theme_css">'.__('Additional CSS files for TinyMCE (absolute urls, one per line)', 'sqcdy_theme_css').'</label>', array(&$this, 'fields_css_html'), 'writing', 'squarecandy_tinymce');
		add_settings_field('sqcdy_include_theme_style_css', '<label for="sqcdy_include_theme_style_css">'.__('Include Active Theme style.css file in TinyMCE?', 'sqcdy_include_theme_style_css').'</label>', array(&$this, 'include_theme_style_css_html'), 'writing', 'squarecandy_tinymce');
		add_settings_field('sqcdy_remove_theme_editor_css', '<label for="sqcdy_remove_theme_editor_css">'.__('Remove Active Theme editor.css or editor-style.css files in TinyMCE?', 'sqcdy_remove_theme_editor_css').'</label>', array(&$this, 'remove_theme_editor_css_html'), 'writing', 'squarecandy_tinymce');

	}
	public function fields1_html() {
		$value = get_option('sqcdy_theme_color1', '');
		echo '<input type="text" id="sqcdy_theme_color1" name="sqcdy_theme_color1" value="'.$value.'" />';
	}
	public function fields2_html() {
		$value = get_option('sqcdy_theme_color2', '');
		echo '<input type="text" id="sqcdy_theme_color2" name="sqcdy_theme_color2" value="'.$value.'" />';
	}
	public function fields3_html() {
		$value = get_option('sqcdy_theme_color3', '');
		echo '<input type="text" id="sqcdy_theme_color3" name="sqcdy_theme_color3" value="'.$value.'" />';
	}
	public function fields4_html() {
		$value = get_option('sqcdy_theme_color4', '');
		echo '<input type="text" id="sqcdy_theme_color4" name="sqcdy_theme_color4" value="'.$value.'" />';
	}
	public function fields_colwidth_html() {
		$value = get_option('sqcdy_theme_colwidth', '');
		echo '<input type="text" id="sqcdy_theme_colwidth" name="sqcdy_theme_colwidth" value="'.$value.'" />';
	}
	public function fields_css_html() {
		$value = get_option('sqcdy_theme_css', '');
		echo '<textarea id="sqcdy_theme_css" name="sqcdy_theme_css" rows="4" cols="100">'.$value.'</textarea>';
	}
	public function include_theme_style_css_html() {
		$value = get_option('sqcdy_include_theme_style_css', 'on');
		echo '<input type="checkbox" id="sqcdy_include_theme_style_css" name="sqcdy_include_theme_style_css"';
		if ($value=='on') echo ' checked="checked"';
		echo '>';
	}
	public function remove_theme_editor_css_html() {
		$value = get_option('sqcdy_remove_theme_editor_css', 'on');
		echo '<input type="checkbox" id="sqcdy_remove_theme_editor_css" name="sqcdy_remove_theme_editor_css"';
		if ($value=='on') echo ' checked="checked"';
		echo '>';
	}

}


function squarecandy_tinymce_enqueue_scripts() {
	// add colorpicker js to the admin
	wp_enqueue_style('wp-color-picker');
	wp_enqueue_script('squarecandy-tinymce', plugins_url('colorpick.js', __FILE__), array('wp-color-picker'), false, true);
}
add_action('admin_enqueue_scripts', 'squarecandy_tinymce_enqueue_scripts');


// TinyMCE Customization
function make_mce_awesome($in) {
	$customcolors = array();
	$custom_colors = '"000000","Black","222222","Almost Black","404040","Dark Gray","636363","Gray","939393","Light Gray","AAAAAA","Lightest Gray"';
	$sqcdy_theme_color1 = get_option('sqcdy_theme_color1');
	$sqcdy_theme_color1 = preg_replace('/[^A-Fa-f0-9]/', '', $sqcdy_theme_color1);
	$sqcdy_theme_color2 = get_option('sqcdy_theme_color2');
	$sqcdy_theme_color2 = preg_replace('/[^A-Fa-f0-9]/', '', $sqcdy_theme_color2);
	$sqcdy_theme_color3 = get_option('sqcdy_theme_color3');
	$sqcdy_theme_color3 = preg_replace('/[^A-Fa-f0-9]/', '', $sqcdy_theme_color3);
	$sqcdy_theme_color4 = get_option('sqcdy_theme_color4');
	$sqcdy_theme_color4 = preg_replace('/[^A-Fa-f0-9]/', '', $sqcdy_theme_color4);
	if (!empty($sqcdy_theme_color1)) {
		$custom_colors .= ',"'.$sqcdy_theme_color1.'","Branding Color 1"';
	}
	if (!empty($sqcdy_theme_color2)) {
		$custom_colors .= ',"'.$sqcdy_theme_color2.'","Branding Color 2"';
	}
	if (!empty($sqcdy_theme_color3)) {
		$custom_colors .= ',"'.$sqcdy_theme_color3.'","Branding Color 3"';
	}
	if (!empty($sqcdy_theme_color4)) {
		$custom_colors .= ',"'.$sqcdy_theme_color4.'","Branding Color 4"';
	}
	$in['textcolor_map'] = '['.$custom_colors.']';

	$in['block_formats'] = 'paragraph=p;big heading (h2)=h2;medium heading (h3)=h3;small heading (h4)=h4;generic box (div)=div';
	$in['toolbar1'] = 'formatselect,styleselect,bold,italic,alignleft,aligncenter,alignright,bullist,numlist,blockquote,hr,forecolor,forecolorpicker,link,unlink,pastetext,undo,redo';
	$in['toolbar2'] = '';
	$in['toolbar3'] = '';
	$in['toolbar4'] = '';

	return $in;
}
add_filter('tiny_mce_before_init', 'make_mce_awesome');


// make the same changes to ACF editors too
function squarecandy_tinymce_toolbars($toolbars) {
	$toolbars['Full'] = array();
	$toolbars['Full'][1] = array('formatselect', 'styleselect', 'bold', 'italic', 'alignleft', 'aligncenter', 'alignright', 'bullist', 'numlist', 'blockquote', 'hr', 'forecolor', 'forecolorpicker', 'link', 'unlink', 'pastetext', 'undo', 'redo');
	$toolbars['Full'][2] = array();

	$toolbars['Basic'] = array();
	$toolbars['Basic'][1] = array('bold', 'italic', 'alignleft', 'aligncenter', 'alignright', 'forecolor', 'forecolorpicker', 'link', 'unlink');

	return $toolbars;
}
add_filter('acf/fields/wysiwyg/toolbars', 'squarecandy_tinymce_toolbars');


// remove some stylesheets
function squarecandy_tinymce_remove_mce_css($stylesheets) {
	$stylesheets = explode(',',$stylesheets);

	$remove = get_option('sqcdy_remove_theme_editor_css', 'on');

	foreach ($stylesheets as $key => $sheet) {

		// remove default wordpress TinyMCE css
		if (preg_match('/wp\-includes/',$sheet)) {
			unset($stylesheets[$key]);
		}
		// remove editor styles from Theme
		if ( $remove=="on" && preg_match('/editor/',$sheet) && !preg_match('/squarecandy\-tinymce/',$sheet) ) {
			unset($stylesheets[$key]);
		}

	}

	$stylesheets = implode(',',$stylesheets);
	return $stylesheets;
}
add_filter("mce_css", "squarecandy_tinymce_remove_mce_css");


// Add styles to the TinyMCE editor window to make it look more like your site's front end
function squarecandy_tinymce_add_editor_styles() {

	$sqcdy_include_theme_style_css = get_option('sqcdy_include_theme_style_css');
	if ($sqcdy_include_theme_style_css == 'on') {
		add_editor_style(get_stylesheet_directory_uri().'/style.css');
	}

	$sqcdy_theme_colwidth = get_option('sqcdy_theme_colwidth');
	if (!empty($sqcdy_theme_colwidth)) {
		add_editor_style(plugins_url('dynamic.css.php', __FILE__));
	}

	$sqcdy_theme_css = get_option('sqcdy_theme_css');
	if (!empty($sqcdy_theme_css)) {
		$all_css = explode("\n", $sqcdy_theme_css);
		foreach ($all_css as $css) {
			$css = preg_replace('/\s/', '', $css);
			add_editor_style($css);
		}
	}

	if (file_exists(get_stylesheet_directory().'/squarecandy-tinymce-editor-style.css')) {
		add_editor_style(get_stylesheet_directory_uri().'/squarecandy-tinymce-editor-style.css');
	} else {
		add_editor_style(plugins_url('squarecandy-tinymce-editor-style.css', __FILE__));
	}

	if (file_exists(get_stylesheet_directory().'/frontend-style.css')) {
		add_editor_style(get_stylesheet_directory_uri().'/frontend-style.css');
	} else {
		add_editor_style(plugins_url('frontend-style.css', __FILE__));
	}

}
add_action('admin_init', 'squarecandy_tinymce_add_editor_styles');


// add the frontend-style.css to the front end display as well
function squarecandy_tinymce_frontendstyle() {
	if (file_exists(get_stylesheet_directory().'/frontend-style.css')) {
		wp_enqueue_style('onebeat-style', get_stylesheet_directory_uri().'/frontend-style.css');
	} else {
		wp_enqueue_style('onebeat-style', plugins_url('frontend-style.css', __FILE__));
	}
}
add_action('wp_enqueue_scripts', 'squarecandy_tinymce_frontendstyle');


// Callback function to insert 'styleselect' into the $buttons array
function squarecandy_tinymce_mce_buttons($buttons) {
	$buttons[] = 'styleselect';
	return $buttons;
}
add_filter('mce_buttons', 'squarecandy_tinymce_mce_buttons');


// Callback function to filter the MCE settings
function squarecandy_tinymce_mce_before_init($init_array) {
	// Define the style_formats array
	$style_formats = array(
		array(
			'title' => 'button',
			'block' => 'p',
			'classes' => 'button-container',
			'wrapper' => false,
		),
		array(
			'title' => 'smaller text',
			'block' => 'span',
			'classes' => 'small',
			'wrapper' => false,
		),
		array(
			'title' => 'bigger text',
			'block' => 'span',
			'classes' => 'big',
			'wrapper' => false,
		),
	);
	// Insert the array, JSON ENCODED, into 'style_formats'
	$init_array['style_formats'] = json_encode($style_formats);
	return $init_array;
}
add_filter('tiny_mce_before_init', 'squarecandy_tinymce_mce_before_init');
