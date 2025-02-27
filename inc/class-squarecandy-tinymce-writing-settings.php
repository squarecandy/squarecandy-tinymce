<?php
class SquareCandy_TinyMCE_Writing_Settings {
	public function __construct() {
		add_filter( 'admin_init', array( &$this, 'register_fields' ) );
	}
	public function register_fields() {
		register_setting( 'writing', 'sqcdy_theme_color1', 'esc_attr' );
		register_setting( 'writing', 'sqcdy_theme_color2', 'esc_attr' );
		register_setting( 'writing', 'sqcdy_theme_color3', 'esc_attr' );
		register_setting( 'writing', 'sqcdy_theme_color4', 'esc_attr' );
		register_setting( 'writing', 'sqcdy_allow_color_picker', 'esc_attr' );
		register_setting( 'writing', 'sqcdy_theme_colwidth', 'esc_attr' );
		register_setting( 'writing', 'sqcdy_theme_css', 'esc_attr' );
		register_setting( 'writing', 'sqcdy_include_theme_style_css', 'esc_attr' );
		register_setting( 'writing', 'sqcdy_remove_theme_editor_css', 'esc_attr' );
		register_setting( 'writing', 'sqcdy_remove_frontend_style_css', 'esc_attr' );

		add_settings_section( 'squarecandy_tinymce', 'TinyMCE Reboot', 'squarecandy_tinymce_section_callback', 'writing' );
		function squarecandy_tinymce_section_callback() {
			// blank on purpose
		}

		add_settings_field( 'sqcdy_theme_color1', '<label for="sqcdy_theme_color1">' . __( 'Theme Color 1', 'sqcdy_theme_color1' ) . '</label>', array( &$this, 'fields1_html' ), 'writing', 'squarecandy_tinymce' );
		add_settings_field( 'sqcdy_theme_color2', '<label for="sqcdy_theme_color2">' . __( 'Theme Color 2', 'sqcdy_theme_color2' ) . '</label>', array( &$this, 'fields2_html' ), 'writing', 'squarecandy_tinymce' );
		add_settings_field( 'sqcdy_theme_color3', '<label for="sqcdy_theme_color3">' . __( 'Theme Color 3', 'sqcdy_theme_color3' ) . '</label>', array( &$this, 'fields3_html' ), 'writing', 'squarecandy_tinymce' );
		add_settings_field( 'sqcdy_theme_color4', '<label for="sqcdy_theme_color4">' . __( 'Theme Color 4', 'sqcdy_theme_color4' ) . '</label>', array( &$this, 'fields4_html' ), 'writing', 'squarecandy_tinymce' );
		add_settings_field( 'sqcdy_allow_color_picker', '<label for="sqcdy_allow_color_picker">' . __( 'Allow Color Picker Use?', 'sqcdy_allow_color_picker' ) . '</label>', array( &$this, 'allow_color_picker_html' ), 'writing', 'squarecandy_tinymce' );
		add_settings_field( 'sqcdy_theme_colwidth', '<label for="sqcdy_theme_colwidth">' . __( 'Typical Theme Content Column Max Width', 'sqcdy_theme_colwidth' ) . '</label>', array( &$this, 'fields_colwidth_html' ), 'writing', 'squarecandy_tinymce' );
		add_settings_field( 'sqcdy_theme_css', '<label for="sqcdy_theme_css">' . __( 'Additional CSS files for TinyMCE (absolute urls, one per line)', 'sqcdy_theme_css' ) . '</label>', array( &$this, 'fields_css_html' ), 'writing', 'squarecandy_tinymce' );
		add_settings_field( 'sqcdy_include_theme_style_css', '<label for="sqcdy_include_theme_style_css">' . __( 'Include Active Theme style.css file in TinyMCE?', 'sqcdy_include_theme_style_css' ) . '</label>', array( &$this, 'include_theme_style_css_html' ), 'writing', 'squarecandy_tinymce' );
		add_settings_field( 'sqcdy_remove_theme_editor_css', '<label for="sqcdy_remove_theme_editor_css">' . __( 'Remove Active Theme editor.css or editor-style.css files in TinyMCE?', 'sqcdy_remove_theme_editor_css' ) . '</label>', array( &$this, 'remove_theme_editor_css_html' ), 'writing', 'squarecandy_tinymce' );
		add_settings_field( 'sqcdy_remove_frontend_style_css', '<label for="sqcdy_remove_frontend_style_css">' . __( 'Remove the basic button, small text and quote styles provided by this plugin. (frontend-style.css)', 'sqcdy_remove_theme_editor_css' ) . '</label>', array( &$this, 'remove_frontend_style_css_html' ), 'writing', 'squarecandy_tinymce' );
	}
	public function fields1_html() {
		$value = get_option( 'sqcdy_theme_color1', '' );
		echo '<input type="text" id="sqcdy_theme_color1" name="sqcdy_theme_color1" value="' . $value . '" />';
	}
	public function fields2_html() {
		$value = get_option( 'sqcdy_theme_color2', '' );
		echo '<input type="text" id="sqcdy_theme_color2" name="sqcdy_theme_color2" value="' . $value . '" />';
	}
	public function fields3_html() {
		$value = get_option( 'sqcdy_theme_color3', '' );
		echo '<input type="text" id="sqcdy_theme_color3" name="sqcdy_theme_color3" value="' . $value . '" />';
	}
	public function fields4_html() {
		$value = get_option( 'sqcdy_theme_color4', '' );
		echo '<input type="text" id="sqcdy_theme_color4" name="sqcdy_theme_color4" value="' . $value . '" />';
	}
	public function allow_color_picker_html() {
		$value = get_option( 'sqcdy_allow_color_picker', 'on' );
		echo '<input type="checkbox" id="sqcdy_allow_color_picker" name="sqcdy_allow_color_picker"';
		if ( 'on' === $value ) {
			echo ' checked="checked"';
		}
		echo '>';
	}
	public function fields_colwidth_html() {
		$value = get_option( 'sqcdy_theme_colwidth', '' );
		echo '<input type="text" id="sqcdy_theme_colwidth" name="sqcdy_theme_colwidth" value="' . $value . '" />';
	}
	public function fields_css_html() {
		$value = get_option( 'sqcdy_theme_css', '' );
		echo '<textarea id="sqcdy_theme_css" name="sqcdy_theme_css" rows="4" cols="100">' . $value . '</textarea>';
	}
	public function include_theme_style_css_html() {
		$value = get_option( 'sqcdy_include_theme_style_css', 'on' );
		echo '<input type="checkbox" id="sqcdy_include_theme_style_css" name="sqcdy_include_theme_style_css"';
		if ( 'on' === $value ) {
			echo ' checked="checked"';
		}
		echo '>';
	}
	public function remove_theme_editor_css_html() {
		$value = get_option( 'sqcdy_remove_theme_editor_css', 'on' );
		echo '<input type="checkbox" id="sqcdy_remove_theme_editor_css" name="sqcdy_remove_theme_editor_css"';
		if ( 'on' === $value ) {
			echo ' checked="checked"';
		}
		echo '>';
	}
	public function remove_frontend_style_css_html() {
		$value = get_option( 'sqcdy_remove_frontend_style_css', '' );
		echo '<input type="checkbox" id="sqcdy_remove_frontend_style_css" name="sqcdy_remove_frontend_style_css"';
		if ( 'on' === $value ) {
			echo ' checked="checked"';
		}
		echo '>';
	}

}
