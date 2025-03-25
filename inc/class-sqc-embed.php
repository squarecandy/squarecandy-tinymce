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
 */

class SQC_Embed_Manager {

	private $add_editor_button             = true;
	private $visual_editor_paste_intercept = true;

	private $embed_items = array(
		'SQC_Youtube_Embed',
		'SQC_Vimeo_Embed',
		'SQC_Facebook_Embed',
		'SQC_Instagram_Embed',
		'SQC_Bandcamp_Embed',
		'SQC_GoogleMaps_Embed',
		'SQC_GoogleForms_Embed',
		'SQC_MailchimpArchive_Embed',
		'SQC_Termageddon_Embed',
		'SQC_Livecontrol_Embed',
		'SQC_Streamspot_Embed',
	);

	private $javascript_variables = array(
		'pasteIntercept' => array(),
		'mceButton'      => array(),
	);

	private $custom_js = '';

	public function __construct() {

		// allow filtering of which are loaded = so you can turn them off, or add your own class and enqueue it here
		$this->embed_items = apply_filters( 'sqc_embed_items', $this->embed_items );

		//loop through and instantiate a list of classes
		foreach ( $this->embed_items as $embed_item ) {
			$this->register_embed_class_item( $embed_item );
		}

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

	private function register_embed_class_item( $item ) {

		if ( is_string( $item ) && class_exists( $item ) ) { // also check that it extends SQC class?
			$embed_class = new $item();
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
			sqcdy_log( 'Abort adding shortcode button' );
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


/**
 * Adds missing wordpress.com bandcamp shortcode
 *
 * Outputs an iframe like:
 *    <iframe style="border: 0; width: %s; height: %s;"
 *       src="https://bandcamp.com/EmbeddedPlayer/album={album}/size={size}/bgcol={bgcol}/linkcol={linkcol}/tracklist={tracklist}/transparent=true/artwork={artwork}"
 *       title="{title}" seamless></iframe>';
 */

class SQC_Bandcamp_Embed extends SQC_Embed {

	public $name        = 'bandcamp';
	public $js_name     = 'sqcBandcamp';
	public $embed_regex = '';

	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = false;

	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-bandcamp',
		'title'     => 'Bandcamp',
		'notes'     => 'You can paste the "Wordpress" version of the Bandcamp Embed code here or directly into the content editor.',
		'noCode'    => '1',
	);

	/**
	 * Function to create an iframe
	 */
	public function create_iframe( $attr ) {

		$attr = $this->process_shortcode_attr(
			$attr,
			array(
				'album'     => null,
				'width'     => 350,
				'height'    => 470,
				'title'     => null,
				'size'      => 'large',
				'bgcol'     => 'ffffff',
				'url'       => null,
				'linkcol'   => '0687f5',
				'tracklist' => 'false',
				'title'     => null,
				'artwork'   => null,
			)
		);

		if ( ! $attr['album'] ) {
			return false;
		}

		$attr['width']  = $this->maybe_add_px( $attr['width'] );
		$attr['height'] = $this->maybe_add_px( $attr['height'] );

		$iframe = '<iframe style="border: 0; width: %s; height: %s;" src="https://bandcamp.com/EmbeddedPlayer/album=%s/size=%s/bgcol=%s/linkcol=%s/tracklist=%s/transparent=true/artwork=%s" title="%s" seamless></iframe>';

		return sprintf(
			$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
			$attr['width'],
			$attr['height'],
			$attr['album'],
			$attr['size'],
			$attr['bgcol'],
			$attr['linkcol'],
			$attr['tracklist'],
			$attr['artwork'],
			$attr['title'],
		);
	}
}


/**
 * Add paste intercept for Youtube video iframes
 * Pasted iframes are replaced with the video url, which is auto-embedded by WP
 */

class SQC_Youtube_Embed extends SQC_Embed {

	public $name            = 'sqc-youtube';
	public $js_name         = 'sqcYoutube';
	public $add_shortcode   = false;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkText'    => 'youtu',
		'message'      => 'We have detected that you are trying to paste a YouTube iframe embed into the HTML view. For better results, we are replacing this with the appropriate URL format. To avoid this message in the future, please paste the YouTube URL into the Visual tab instead of the iframe embed code.',
		'replaceRegex' => 'embed\/([^?]+)',
		'replacePre'   => 'https://www.youtube.com/watch?v=',
		'replacePost'  => '',
	);

	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-youtube',
		'title'     => 'Youtube',
		'notes'     => 'You can paste the Youtube url here or directly into the content editor.',
		'noCode'    => '1',
	);
}


/**
 * Add paste intercept for Vimeo video iframes
 * Pasted iframes are replaced with the video url, which is auto-embedded by WP
 */

class SQC_Vimeo_Embed extends SQC_Embed {

	public $name            = 'sqc-vimeo';
	public $js_name         = 'sqcVimeo';
	public $add_shortcode   = false;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkText'    => 'vimeo',
		'message'      => 'We have detected that you are trying to paste a Vimeo iframe embed into the HTML view. For better results, we are replacing this with the appropriate URL format. To avoid this message in the future, please paste the Vimeo URL into the Visual tab instead of the iframe embed code.',
		'replaceRegex' => 'video\/([^?]+)',
		'replacePre'   => 'https://vimeo.com/',
		'replacePost'  => '',
	);

	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-vimeo',
		'title'     => 'Vimeo',
		'notes'     => 'You can paste the Vimeo url here or directly into the content editor.',
		'noCode'    => '1',
	);
}


/**
 * Manage embeds/shortcode for Instagram videos
 *
 * Shortcode/Autoembed:
 *    Takes urls like:
 *       https://www.instagram.com/reel/{VIDEO_ID}/?utm_source=ig_web_copy_link
 *       https://www.instagram.com/reel/{VIDEO_ID}/?igsh=ZGUzMzM3NWJiOQ==
 *
 * Outputs an iframe like:
 *    <iframe id="instagram-embed-1" class="instagram-media instagram-media-rendered"
 *       style="background: white; max-width: XXXpx; width: calc(100%% - 2px); border-radius: 3px; border: 1px solid #dbdbdb; box-shadow: none;
 *       display: block; margin: 0px 0px 12px; min-width: 326px; padding: 0px; position: static !important;"
 *      src="{VIDEO_ID}/embed/?cr=1&amp;v=14&amp;wp=XXX" height="YYY" frameborder="0" scrolling="no" allowfullscreen="allowfullscreen" data-instgrm-payload-id="instagram-media-payload-0">
 *    </iframe>
 */

class SQC_Instagram_Embed extends SQC_Embed {

	public $name            = 'sqc-instagram';
	public $js_name         = 'sqcInstagram';
	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings  = array(
		'checkTag'     => 'script',
		'checkText'    => 'instagram',
		'message'      => 'We have detected that you are trying to paste an Instagram iframe embed into the HTML view. For better results, we are replacing this with the appropriate URL format. To avoid this message in the future, please paste the Instagram URL into the Visual tab instead of the iframe embed code.',
		'replaceRegex' => '(https://www\.instagram\.com/reel/[a-zA-Z0-9]+)',
		'replacePre'   => '[sqc-instagram ',
		'replacePost'  => ']',
	);
	public $shortcode_button_settings = array(
		'shortcode'    => 'sqc-instagram',
		'title'        => 'Instagram Video',
		'notes'        => 'You can embed Instagram videos by pasting the link here.', // @TODO trim params from instagram urls here
		'functionName' => 'sqcInstagramShortcodeButton',
		'custom_js'    => 'sqcInstagramShortcodeButton = function( input = "" ) {
			console.log( "sqcInstagramShortcodeButton input", input );
			const regex = /(https:\/\/www\.instagram\.com\/reel\/[\S].*)(\/.*)/gm;
			const result = input.replace( regex, `$1` );
			return "[sqc-instagram " + result + "]";
		}',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {
		//@TODO instagram links with / on ends aren't working here, need to fix that
		// these are narrower than others - maybe 540 is too narrow

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
		$regex = '#(https://www\.instagram\.com/reel/[a-zA-Z0-9]+)#';
		preg_match( $regex, $attr['url'], $matches );
		$attr['url'] = isset( $matches[1] ) ? $matches[1] : false;

		if ( ! $attr['url'] ) :
			return false;
		endif;

		$raw_width  = $attr['width'];
		$raw_height = $attr['height'];

		$attr['width']  = $this->maybe_add_px( $attr['width'] );
		$attr['height'] = $this->maybe_add_px( $attr['height'] );

		//@TODO set id to something unique!!
		$iframe = '<iframe id="instagram-embed-1" class="instagram-media instagram-media-rendered" style="background: white; max-width: %s; width: calc(100%% - 2px); border-radius: 3px; border: 1px solid #dbdbdb; box-shadow: none; display: block; margin: 0px 0px 12px; min-width: 326px; padding: 0px; position: static !important;" src="%s/embed/?cr=1&amp;v=14&amp;wp=%s" height="%s" frameborder="0" scrolling="no" allowfullscreen="allowfullscreen" data-instgrm-payload-id="instagram-media-payload-0"></iframe>';

		return sprintf(
			$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
			$attr['width'],
			$attr['url'],
			$raw_width,
			$raw_height
		);
	}
}


/**
 * Manage embeds/shortcode for Facebook videos
 *
 * Shortcode/Autoembed:
 *    Takes urls like:
 *       https://www.facebook.com/myPageName/videos/longvideoID/
 *       https://www.facebook.com/watch/?v=longvideoID
 *       https://fb.watch/shortvideoID/
 *    But not like:
 *       https://www.facebook.com/share/v/shortvideoID/
 *
 * Outputs an iframe like:
 *    <iframe src="https://www.facebook.com/plugins/video.php?href={VIDEO_URL}&show_text=0&width=XXX"
 *       width="XXX" height="YYY" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true"
 *       allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowFullScreen="true">
 *    </iframe>
 */

class SQC_Facebook_Embed extends SQC_Embed {

	public $name            = 'sqc-facebook-video';
	public $js_name         = 'sqcFacebookVideo';
	public $embed_regex     = '#https://www\.facebook\.com/(?:watch.*|.*/videos.*)$#i';
	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = true;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkText'    => 'facebook.com/plugins/video.php',
		'message'      => 'We have detected that you are trying to paste a Facebook video iframe embed into the HTML view. For better results, we are replacing this with the appropriate shortcode. To avoid this message in the future, please add the Facebook URL using the shortcode button on the Visual tab instead of the iframe embed code.',
		'replaceRegex' => 'video\.php\?.*href=([^&?"]*)', //@TODO make sure trailing slash is trimmed here
		'replacePre'   => '[sqc-facebook-video ',
		'replacePost'  => ']',
		'custom_js'    => "sqcFacebookVideoProcess = function( pastedData ) { 
				const decoded = decodeURIComponent( pastedData );
				const hasParams = decoded.indexOf( '?' );
				console.log('sqcFacebookVideo', 'decoded', decoded, 'hasParams', hasParams );
				if ( hasParams > -1 ) {
					return decoded.substring( 0, hasParams - 1 );
				} else {
					return decoded;
				}
			}",
	);

	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-facebook-video',
		'title'     => 'Facebook Video',
		'notes'     => 'You can embed Facebook videos by pasting the link here, or just by pasting it into the content editor. NB links with /share/ in them won\'t work',
		'notesMore' => 'If you only have the /share/ url, paste that into a browser, wait for the page to load, and then copy the new url that shows in your browser bar.',
	);

	/**
	 * Function to create an iframe
	 */
	public function create_iframe( $attr ) {

		$attr = $this->process_shortcode_attr(
			$attr,
			array(
				'url'    => null,
				'width'  => 350,
				'height' => 470,
			),
			'url' // accepts single att
		);

		$attr['url'] = $this->validate_url( $attr['url'] );

		if ( ! $attr['url'] ) :
			return false;
		endif;

		$attr['url'] = rawurlencode( $attr['url'] );

		$attr['width']  = $this->maybe_add_px( $attr['width'] );
		$attr['height'] = $this->maybe_add_px( $attr['height'] );

		//@TODO Maybe don't need the px adding

		$iframe = '<iframe src="https://www.facebook.com/plugins/video.php?href=%1$s&show_text=0&width=%2$s" width="%2$s" height="%3$s" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowFullScreen="true"></iframe>';

		return sprintf(
			'<p><figure class="wp-block-embed-facebook wp-block-embed is-type-audio is-provider-facebook wp-embed-aspect-16-9 wp-has-aspect-ratio js fitvids"><div class="wp-block-embed__wrapper">' . $iframe . '</div></figure></p>',
			$attr['url'],
			$attr['width'],
			$attr['height']
		);
	}
}


/**
 * Manage embeds/shortcode for Google Maps
 *
 * We need to start with the full embed code provide by Google Maps. To store in content without an iframe, the shortcode takes the properties of the original iframe.
 *
 * Outputs an iframe like:
 *    '<iframe src="{SRC}" width="XXX" height="YYY" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
 */

//@TODO take more params out of the shortcode?

class SQC_GoogleMaps_Embed extends SQC_Embed {

	public $name            = 'sqc-gmaps';
	public $js_name         = 'sqcGoogleMaps';
	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkText'    => 'www.google.com/maps/embed',
		'message'      => 'We have detected that you are trying to paste a Google Maps iframe embed into the HTML view. For better results, we are replacing this with the appropriate shortcode format.',
		'replaceRegex' => '', // not needed, custom process
		'replacePre'   => '',
		'replacePost'  => '',
		'custom_js'    => "sqcGoogleMapsProcess = function( pastedData ) { 
			const iframeOpen = /(?:&lt;|<)iframe/; 
			const iframeClose = /(?:&gt;&lt;|><)\/iframe(?:&gt;|>)/; 
			newText = pastedData.replace( iframeOpen, '[sqc-gmaps ' ); 
			console.log( 'newText', newText );
			newText = newText.replace( iframeClose, ']' ); 
			return newText; 
		};",
	);

	public $shortcode_button_settings = array(
		'shortcode'    => 'sqc-gmaps',
		'title'        => 'Google Maps',
		'notes'        => 'You can embed Google Maps by pasting the embed code here, or by pasting it directly into the content editor.',
		'functionName' => 'sqcGoogleMapsProcess',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {

		$attr = $this->process_shortcode_attr(
			$attr,
			array(
				'src'    => null,
				'width'  => 540,
				'height' => 881,
			),
			'src' // accepts single att
		);

		if ( ! $src ) :
			return false;
		endif;

		$iframe = '<iframe src="%s" width="%s" height="%s" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';

		return sprintf(
			$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
			$attr['src'],
			$attr['width'],
			$attr['height']
		);
	}
}


/**
 * Manage embeds/shortcode for Google Forms
 *
 * We need to start with the full embed code provide by Google Forms. To store in content without an iframe, the shortcode takes the properties of the original iframe.
 *
 * Outputs an iframe like:
 *    <iframe src="https://docs.google.com/forms/d/e/1FAIpQLSeVW-_CT2cAoLYTStvT7TEwNO84kjLjPSV72mUfPsy_AHCHzQ/viewform?embedded=true"
 *       width="640" height="494" frameborder="0" marginheight="0" marginwidth="0">Loading…</iframe>
 */

class SQC_GoogleForms_Embed extends SQC_Embed {

	public $name    = 'sqc-gforms';
	public $js_name = 'sqcGoogleForms';

	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkText'    => 'docs.google.com/forms',
		'message'      => 'We have detected that you are trying to paste a Google Forms iframe embed into the HTML view. For better results, we are replacing this with the appropriate shortcode format.',
		'replaceRegex' => '',
		'replacePre'   => '',
		'replacePost'  => '',
		'custom_js'    => "sqcGoogleFormsProcess = function( pastedData ) { 
			const iframeOpen = /(?:&lt;|<)iframe/; 
			const iframeClose = /(?:&gt;|>).*(?:&lt;|<)\/iframe(?:&gt;|>)/; 
			newText = pastedData.replace( iframeOpen, '[sqc-gforms ' ); 
			newText = newText.replace( iframeClose, ']' ); 
			return newText; 
		};", //@TODO grab individual params, or at least trim src & get rid of non-embed customizable options
	);

	public $shortcode_button_settings = array(
		'shortcode'    => 'sqc-gforms',
		'title'        => 'Google Forms',
		'notes'        => 'You can embed Google Forms by pasting the embed code here, or by pasting it directly into the content editor.',
		'functionName' => 'sqcGoogleFormsProcess',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {

		$attr = $this->process_shortcode_attr(
			$attr,
			array(
				'src'    => null,
				'width'  => 640,
				'height' => 494,
			),
			'src' // accepts single att
		);

		if ( ! $attr['src'] ) :
			return false;
		endif;

		$iframe = '<iframe src="%s" width="%s" height="%s" frameborder="0" marginheight="0" marginwidth="0">Loading…</iframe>';
		return sprintf(
			$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
			$attr['src'],
			$attr['width'],
			$attr['height']
		);
	}
}


/**
 * Manage embeds/shortcode for Mailchimp list archive
 *
 * Outputs style/script tags like:
 *    <style type="text/css"><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span><br />
 *    <span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span>
 *    <!-- .display_archive { font-family: Source Sans Pro,sans-serif; font-size: 20px; font-size: 1.25rem; line-height: 1.4; font-weight: 400; letter-spacing: 0; }
 *    .campaign {line-height: 125%%; margin: 5px;} //--><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark"
 *    class="mce_SELRES_end">﻿</span><br /></style>'
 *    <script language="javascript" src="//firstchurchcambridge.us5.list-manage.com/generate-js/?u=e8d9144b526b20dffd6009d45&fid=20494&show=52" type="text/javascript"><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span></script>
 */

class SQC_MailchimpArchive_Embed extends SQC_Embed {

	public $name    = 'mailchimp-archive';
	public $js_name = 'sqcMailchimpArchive';

	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkTag'     => 'script',
		'checkText'    => 'list-manage.com/generate-js',
		'message'      => 'We have detected that you are trying to paste a Mailchimp Archive embed into the HTML view. For better results, we are replacing this with the appropriate shortcode format.',
		'replaceRegex' => 'src=(?:"|&quot;)(.*?)(?:"|&quot;)',
		'replacePre'   => '[mailchimp-archive ',
		'replacePost'  => ']',
	);

	public $shortcode_button_settings = array(
		'shortcode'    => 'mailchimp-archive',
		'title'        => 'Mailchimp Archive',
		'notes'        => 'You can embed Mailchimp Archives by pasting the embed code here.',
		'notesMore'    => '<a href="https://mailchimp.com/help/add-an-email-campaign-archive-to-your-website/" target="_blank">More information on how to get your Mailchimp archive embed.</a>',
		'functionName' => 'replacePastedText',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {

		$attr = $this->process_shortcode_attr(
			$attr,
			array(
				'src' => null,
			),
			'src' // accepts single att
		);

		if ( ! $attr['src'] ) :
			return false;
		endif;

		$style = '<style type="text/css"><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span><br /><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span><!-- .display_archive { font-family: Source Sans Pro,sans-serif; font-size: 20px; font-size: 1.25rem; line-height: 1.4; font-weight: 400; letter-spacing: 0; } .campaign {line-height: 125%%; margin: 5px;} //--><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_end">﻿</span><br /></style>';

		$script = '<script language="javascript" src="%s" type="text/javascript"><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript

		return sprintf(
			$this->iframe_wrapper['open'] . $style . $script . $this->iframe_wrapper['close'],
			$attr['src'],
		);
	}
}

/**
 * Manage embeds/shortcode for Termageddon Privacy Policy
 *
 * Outputs like:
 *    <div id="policy" data-policy-key="U21SQ2FrRXhXbU13VTNOS0szYzlQUT09" data-extra="email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion">
 *       Please wait while the policy is loaded. If it does not load, please
 *       <a href="https://app.termageddon.com/api/policy/U21SQ2FrRXhXbU13VTNOS0szYzlQUT09?email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion"
 *       target="_blank" rel="nofollow noopener">click here</a>.</div>
 *       <script src="https://app.termageddon.com/js/termageddon.js"></script>
 */

class SQC_Termageddon_Embed extends SQC_Embed {

	public $name    = 'sqc-termageddon';
	public $js_name = 'sqcTermageddon';

	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkTag'     => 'script',
		'checkText'    => 'app.termageddon.com/api',
		'message'      => 'We have detected that you are trying to paste a Termageddon embed into the HTML view. For better results, we are replacing this with the appropriate shortcode format.',
		'replaceRegex' => 'data-policy-key=(?:"|&quot;)([a-zA-Z0-9]*)',
		'replacePre'   => '[sqc-termageddon ',
		'replacePost'  => ']',
	);

	public $shortcode_button_settings = array(
		'shortcode'    => 'sqc-termageddon',
		'title'        => 'Termageddon',
		'notes'        => 'You can embed your Termageddon Privacy Policy by pasting the embed code here.',
		'functionName' => 'replacePastedText',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {

		$attr = $this->process_shortcode_attr(
			$attr,
			array(
				'src' => null,
			),
			'src' // accepts single att
		);

		if ( ! $attr['src'] ) :
			return false;
		endif;

		$script = '<div id="policy" data-policy-key="%1$s" data-extra="email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion">Please wait while the policy is loaded. If it does not load, please <a href="https://app.termageddon.com/api/policy/%1$s?email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion" target="_blank" rel="nofollow noopener">click here</a>.</div>
            <script src="https://app.termageddon.com/js/termageddon.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript

		return sprintf(
			'<p><div class="wp-block-embed__wrapper">' . $script . '</p></div>',
			$attr['src'],
		);
	}
}

/**
 * Manage embeds/shortcode for TILB livecontrol.tv
 *
 * Outputs like:
 *    <script type="text/javascript" src="https://tilb.livecontrol.tv/scripts/v1/embed.js">
 *    </script>
 *    <script>
 *      LiveControl.Webplayer.embed({
 *        source: "https://tilb.livecontrol.tv/embed?page=profile"
 *      });
 *    </script>
 */

class SQC_Livecontrol_Embed extends SQC_Embed {

	public $name    = 'tilb-livecontrol';
	public $js_name = 'sqcLivecontrol';

	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkTag'     => 'script',
		'checkText'    => 'tilb.livecontrol.tv',
		'message'      => 'We have detected that you are trying to paste a livecontrol.tv embed into the HTML view. For better results, we are replacing this with the appropriate shortcode format.',
		'replaceRegex' => 'xxx', // we want this to fail
		'replacePre'   => '[tilb-livecontrol]',
		'replacePost'  => '',
	);

	public $shortcode_button_settings = array(
		'shortcode' => 'tilb-livecontrol',
		'title'     => 'livecontrol.tv',
		'notes'     => 'You can embed livecontrol.tv by clicking Confirm.',
		'noInput'   => '1',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {

		$script = '<script type="text/javascript" src="https://tilb.livecontrol.tv/scripts/v1/embed.js"></script><script>LiveControl.Webplayer.embed({source: "https://tilb.livecontrol.tv/embed?page=profile"});</script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript

		return sprintf( $this->iframe_wrapper['open'] . $script . $this->iframe_wrapper['close'] );
	}
}

/**
 * Manage embeds/shortcode for Streamspot
 *
 * Outputs iframe like:
 *    <iframe src="https://player2.streamspot.com/?playerId=783b36c8" width="900" height="506" frameborder="0" allowfullscreen="allowfullscreen"><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start"></span></iframe>
 */

class SQC_Streamspot_Embed extends SQC_Embed {

	public $name    = 'rockspring-streamspot';
	public $js_name = 'sqcStreamspot';

	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkText'    => 'player2.streamspot.com',
		'message'      => 'We have detected that you are trying to paste a Streamspot embed into the HTML view. For better results, we are replacing this with the appropriate shortcode format.',
		'replaceRegex' => 'src=(?:"|&quot;)(.*?)(?:"|&quot;)',
		'replacePre'   => '[rockspring-streamspot ',
		'replacePost'  => ']',
	);

	public $shortcode_button_settings = array(
		'shortcode'    => 'rockspring-streamspot',
		'title'        => 'Streamspot',
		'notes'        => 'You can embed Streamspot by pasting the embed code here.',
		'functionName' => 'replacePastedText',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {

		$attr = $this->process_shortcode_attr(
			$attr,
			array(
				'src' => null,
			),
			'src' // accepts single att
		);

		if ( ! $attr['src'] ) :
			return false;
		endif;

		$iframe = '<iframe src="%s" width="900" height="506" frameborder="0" allowfullscreen="allowfullscreen"><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start"></span></iframe>';

		return sprintf(
			$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
			$attr['src'],
		);
	}
}

$sqc_embeds = new SQC_Embed_Manager();
