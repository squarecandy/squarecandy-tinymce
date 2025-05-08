<?php
/**
 * Parent class for creating new shortcodes/embeds
 */

class SQC_Embed {

	public $name        = ''; // shortcode if used
	public $js_name     = ''; // camelCase version of class name (used as identifier for localized variables/functions in js)
	public $embed_regex = ''; // for autoembed, this is the regex to isolate the url used to populate the iframe

	public $add_shortcode   = true; // add a shortcode
	public $add_to_button   = true; // add to the shortcode button in the visual editor
	public $auto_embed      = false; // add auto embed
	public $paste_intercept = false; // intercept pasted code block & replace

	public $paste_intercept_settings = array();

	public $shortcode_button_settings = array();

	public $iframe_wrapper = array(
		'open'  => '',
		'close' => '',
	);

	const DEFAULT_PASTE_INTERCEPT_SETTINGS = array(
		'checkTag'     => 'iframe', // optional - tag to check for surrounding the text (set falsy to bypass)
		'checkText'    => '', // required - pattern to check for (plain string, not regex)
		'message'      => '', // required - message to be displayed when pattern is matched
		'replaceRegex' => '', // optional - regex to locate url/identifier (without delimiters) (leave empty if custom_js)
		'replacePre'   => '', // optional - text of outout that goes before the identifier, e.g. '[ myshortcode'
		'replacePost'  => '', // optional - text of outout that goes after the identifier, e.g. ']'
		'custom_js'    => '', // optional - javascript defining custom function to use instead of default regex/replace.
		//function name must be like {$class->js_name}Process for js to automaticaly locate it
	);

	const DEFAULT_SHORTCODE_BUTTON_SETTINGS = array(
		'shortcode'    => '', // required - used as identifier & shortcode
		'title'        => '', // required - label for the radio button for this option
		'notes'        => '', // required - notes displayed when this option is selected
		'notesMore'    => '', // optional - more notes displayed in a closed by default accordion section
		'noCode'       => '', // optional - if truthy, link pasted into the input is passed directly to content (e.g. youtube/vimeo)
		'noInput'      => '', // optional - if truthy, allow shortcode button input to be empty
		'functionName' => '', // optional - function name for custom function to process input. put 'replacePastedText' to use paste intercept regex, or name of function in custom_js in paste intercept settings, or name of function defined in shortcode_button_settings['custom_js']
		'custom_js'    => '', // optional - javascript defining custom function.
		// avoid function name like {$class->js_name}Process as this will affect paste intercep.
	);

	public function __construct( $attr = array() ) {

		// allow attr to be filtered
		$attr = apply_filters( 'sqc_embed_properties', $attr, get_class( $this ) );

		// allow properties to be set via array (needs more work on setting methods to make class fully instantiable this way)
		foreach ( $attr as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

		// set (default) wrappers for iframes ( should this be set in embed class instead? )
		if ( ! $this->iframe_wrapper['open'] || ! $this->iframe_wrapper['close'] ) {
			//@TODO confirm this - figure doesn't work for Termageddon, sets lh 0 for contents
			$this->iframe_wrapper['open']  = '<p><figure class="wp-block-embed-' . $this->name . ' wp-block-embed is-type-audio is-provider-' . $this->name . ' js"><div class="wp-block-embed__wrapper">';
			$this->iframe_wrapper['close'] = '</div></figure></p>';
		}

		// parse js args

		$this->paste_intercept_settings = wp_parse_args( $this->paste_intercept_settings, self::DEFAULT_PASTE_INTERCEPT_SETTINGS );

		$this->shortcode_button_settings = wp_parse_args( $this->shortcode_button_settings, self::DEFAULT_SHORTCODE_BUTTON_SETTINGS );

		if ( $this->add_shortcode ) {
			$this->register_shortcode();
		}

		if ( $this->auto_embed ) {
			$this->add_auto_embed();
		}
	}

	/**
	 * Function to create an iframe/script tag etc
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {
		// phpcs:disable Squiz.PHP.CommentedOutCode.Found
		//example code:
		/*
		$attr = $this->process_shortcode_attr(
			$attr,
			array(
				'url'    => null,
				'width'  => 540,
				'height' => 881,
			),
			'url' // accepts single att
		);

		$attr['url'] = $this->validate_url( $attr['url'] );

		// grab just the part we need of the url
		$regex = '#pattern#';
		preg_match( $regex, $attr['url'], $matches );
		$attr['url'] = isset( $matches[1] ) ? $matches[1] : false;

		if ( ! $attr['url'] ) :
			return false;
		endif;

		$iframe = '<iframe src="%s"></iframe>';

		return sprintf(
			$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
			$attr['url'],
		);
		*/
		// phpcs:enable
	}

	/**
	 * Function to convert the shortcode into an iframe
	 */
	public function process_shortcode( $attr ) {
		return $this->create_iframe( $attr );
	}


	/**
	 * Add a custom shortcode
	 */
	public function register_shortcode() {
		add_shortcode(
			$this->name,
			array( $this, 'process_shortcode' )
		);
	}

	/**
	 * Add custom auto embed function
	 * So when you enter a matching url in the visual editor, it will be turned into an iframe
	 */
	public function add_auto_embed() {
		add_action(
			'init',
			function() {
				wp_embed_register_handler(
					$this->name,
					$this->embed_regex,
					array( $this, 'embed_handler' )
				);
			}
		);
	}

	/**
	 * Function to convert embedded url into an iframe
	 */
	public function embed_handler( $matches, $attr, $url, $rawattr ) {
		$attr['url'] = $url;
		return $this->create_iframe( $attr );
	}

	/**
	 * Function to validate and prepare urls
	 * @param string $url input url
	 * @param string|bool $check_domain placeholder for now - domain to check against
	 * @param bool $force_noslash whether to trim slash off the end of the url
	 */
	public function validate_url( $url, $check_domain = false, $force_noslash = true ) {

		if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
			return false;
		}

		if ( $force_noslash ) {
			$url = rtrim( $url, '/\\' );
		}

		return $url;

	}

	/**
	 * @param $attr array
	 * @param $accept_single_attr bool|string
	 * @param $defaults
	 */
	public function process_shortcode_attr( $attr, $defaults = array(), $accept_single_attr = false ) {

		if ( $accept_single_attr ) :
			if ( 1 === count( $attr ) && 0 === array_keys( $attr )[0] ) :
				$attr = array( $accept_single_attr => $attr[0] );
			endif;
		endif;

		$attr = shortcode_atts( $defaults, $attr );
		return $attr;
	}

	//add px to width/height (but not if width is e.g. 100%)
	public function maybe_add_px( $value ) {
		if ( preg_match( '#^[0-9]+$#', $value ) ) {
			$value = $value . 'px';
		}
		return $value;
	}

}
