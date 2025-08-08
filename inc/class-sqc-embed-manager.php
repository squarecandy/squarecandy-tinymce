<?php
/**
 * Manager class for creating new shortcodes/embeds
 *    new shortcodes etc are created by defining a child class of SQC_Embed with desired properties/methods
 *
 * Shortcodes:
 *    added via WP add_shortcode
 *    Code to create an iframe from an array of attributes defined in {ChildClass}->create_iframe()
 * Auto embed:
 *    added via wp_embed_register_handler, uses {ChildClass}->create_iframe()
 * Shortcode button on visual editor toolbar:
 *    Added via $this->toolbar_button_init / mce_external_plugins/mce_buttons filters & shortcode-mce-button.js
 * Paste intercept
 *    in text editor:
 *       added via squarecandy-tinymce.js
 *    in visual editor:
 *       added via squarecandy_tinymce_before_paste_preprocess filter (tiny_mce_before_init), uses js functions defined in squarecandy-tinymce.js
 * javascript:
 *    custom variables and extra functions are injected via
 *
 * To add custom embeds in theme:
 *    add (but don't require/include) a new class file that extends SQC_Embed, e.g. mytheme/inc/class-my-custom-embed.php:
 *    add_filter( 'sqc_embed_items', 'custom_embed_items' );
 *      function custom_embed_items( $items ) {
 *          $items[ 'My_Custom_Embed' ] = THEME_PATH . '/inc/class-my-custom-embed.php';
 *          return $items;
 *      }
 */

class SQC_Embed_Manager {

	private $add_editor_button             = true;
	private $visual_editor_paste_intercept = true;

	private $embed_items = array(
		'SQC_Youtube_Embed'          => 'class-sqc-youtube-embed.php',
		'SQC_Vimeo_Embed'            => 'class-sqc-vimeo-embed.php',
		'SQC_Facebook_Embed'         => 'class-sqc-facebook-embed.php',
		'SQC_Instagram_Embed'        => 'class-sqc-instagram-embed.php',
		'SQC_Bandcamp_Embed'         => 'class-sqc-bandcamp-embed.php',
		'SQC_GoogleMaps_Embed'       => 'class-sqc-googlemaps-embed.php',
		'SQC_GoogleForms_Embed'      => 'class-sqc-googleforms-embed.php',
		'SQC_MailchimpArchive_Embed' => 'class-sqc-mailchimparchive-embed.php',
		'SQC_Termageddon_Embed'      => 'class-sqc-termageddon-embed.php',
	);

	private $javascript_variables = array(
		'pasteIntercept' => array(),
		'mceButton'      => array(),
	);

	private $custom_js = '';

	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'init' ) );
	}

	public function init() {

		$is_admin = is_admin();

		// don't load if ajax or cron
		if ( ( $is_admin && wp_doing_ajax() ) || wp_doing_cron() ) {
			return;
		}

		require_once SQUARECANDY_TINYMCE_DIR_PATH . 'inc/class-sqc-embed.php';

		foreach ( $this->embed_items as $embed_item => $file_name ) {
			$this->embed_items[ $embed_item ] = SQUARECANDY_TINYMCE_DIR_PATH . 'inc/' . $file_name;
		}

		// allow filtering of which are loaded = so you can turn them off, or add your own class and enqueue it here
		$this->embed_items = apply_filters( 'sqc_embed_items', $this->embed_items );

		//loop through and instantiate a list of classes
		foreach ( $this->embed_items as $embed_item => $file_path ) {
			$this->register_embed_class_item( $embed_item, $file_path );
		}

		// don't load admin stuff on front end
		if ( $is_admin ) :
			// add the shortcode button to the editor(s)
			// allow filtering to turn this off
			$this->add_editor_button = apply_filters( 'sqc_embed_editor_button', $this->add_editor_button );
			if ( $this->add_editor_button ) {
				$this->register_editor_button();
			}

			if ( $this->javascript_variables['pasteIntercept'] || $this->javascript_variables['mceButton'] ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'localize_scripts' ), 20 );
			}

			if ( $this->javascript_variables['pasteIntercept'] && $this->visual_editor_paste_intercept ) {
				add_action( 'squarecandy_tinymce_before_paste_preprocess', array( $this, 'tinymce_before_paste_preprocess' ), 20 );
			}
		endif;
	}

	// add our intercepts to the visual editor paste preprocess
	public function tinymce_before_paste_preprocess( $code ) {

		$visual_paste_intercept = "
			if ( typeof replacePastedText == 'function' && typeof displayInterceptMessage == 'function' ) {
				const output = replacePastedText( args.content );
				if ( output != args.content ) {
					args.content = output.text;
					const messageTarget = jQuery( args.target.container ).find('.mce-toolbar-grp');
					console.log( 'PASTE ARGS', args, messageTarget );
					displayInterceptMessage( messageTarget, output.message );
				}
				console.log( 'newContent', args.content );
			}
		";

		return $code . $visual_paste_intercept;
	}

	public function localize_scripts() {

		//using localize bc I'm too lazy to process my own array of values
		wp_localize_script( 'squarecandy-tinymce', 'sqcEmbed', $this->javascript_variables );

		if ( $this->custom_js ) {
			//using inline to add custom functions
			wp_add_inline_script( 'squarecandy-tinymce', $this->custom_js, 'before' );
		}
	}

	private function register_embed_class_item( $item, $file_path ) {

		if ( is_string( $item ) ) {

			// try to load the file
			if ( ! class_exists( $item ) && file_exists( $file_path ) ) {
				require_once $file_path;
			}

			if ( class_exists( $item ) ) {
				$embed_class = new $item();
			} else {
				error_log( 'SQC_Embed_Manager: error loading ' . $item . ' class.' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				return;
			}
		} elseif ( is_array( $item ) ) {
			$embed_class = new SQC_Embed( $item ); // possible way of adding new embed without creating a class, not implemented yet
		}

		$this->register_embed_class( $embed_class );
	}

	private function register_embed_class( $embed_class ) {

		if ( ! is_subclass_of( $embed_class, 'SQC_Embed' ) ) {
			return;
		}

		if ( $embed_class->paste_intercept && $embed_class->paste_intercept_settings ) {
			$paste_intercept_settings = $embed_class->paste_intercept_settings;
			// if we're defining custom functions, grab that separately
			if ( ! empty( $paste_intercept_settings['custom_js'] ) ) {
				$this->custom_js .= $paste_intercept_settings['custom_js'] . "\n";
				unset( $paste_intercept_settings['custom_js'] ); // remove from what will be localized
			}
			$this->javascript_variables['pasteIntercept'][ $embed_class->js_name ] = $paste_intercept_settings;
		}

		if ( $embed_class->add_to_button && $embed_class->shortcode_button_settings ) {
			$shortcode_button_settings = $embed_class->shortcode_button_settings;
			// if we're defining custom functions, grab that separately
			if ( ! empty( $shortcode_button_settings['custom_js'] ) ) {
				$this->custom_js .= $shortcode_button_settings['custom_js'] . "\n";
				unset( $shortcode_button_settings['custom_js'] ); // remove from what will be localized
			}
			$this->javascript_variables['mceButton'][ $embed_class->js_name ] = $shortcode_button_settings;
		}
	}

	private function register_editor_button() {
		// init process for registering our button
		add_action( 'admin_init', array( $this, 'toolbar_button_init' ) );
	}

	public function toolbar_button_init() {
		//Abort early if the user will never see TinyMCE
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) && get_user_option( 'rich_editing' ) === 'true' ) {
			return;
		}

		//Add a callback to regiser our tinymce plugin
		add_filter( 'mce_external_plugins', array( $this, 'register_tinymce_plugin' ) );

		// Add a callback to add our button to the TinyMCE toolbar
		add_filter( 'mce_buttons', array( $this, 'add_tinymce_button' ) );
	}

	//This callback registers our tinymce plug-in
	public function register_tinymce_plugin( $plugin_array ) {
		$plugin_array['sqc_embed_button'] = SQUARECANDY_TINYMCE_DIR_URL . 'js/shortcode-mce-button.js';
		return $plugin_array;
	}

	//This callback adds our button to the toolbar
	public function add_tinymce_button( $buttons ) {
		//Add the button ID to the $button array
		$buttons[] = 'sqc_embed_button';
		return $buttons;
	}
}
