<?php

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
			//sqcdy_log( $embed_class, 'embed_class' );
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
			if ( ! empty( $embed_class->paste_intercept_settings['custom_js'] ) ) {
				$this->custom_js .= $embed_class->paste_intercept_settings['custom_js'] . "\n";
			}
			$this->javascript_variables['pasteIntercept'][ $embed_class->js_name ] = $embed_class->paste_intercept_settings;
		}

		if ( $embed_class->add_to_button && $embed_class->shortcode_button_settings ) {
			$shortcode_button_settings = $embed_class->shortcode_button_settings;

			$this->javascript_variables['mceButton'][ $embed_class->js_name ] = $shortcode_button_settings;
		}
	}

	private function register_editor_button() {
		// init process for registering our button
		 add_action( 'admin_init', array( $this, 'toolbar_button_init' ) );
	}

	public function toolbar_button_init() {

		  //Abort early if the user will never see TinyMCE
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) && get_user_option( 'rich_editing' ) == 'true' ) {
			 return;
		}

		  //Add a callback to regiser our tinymce plugin
		  add_filter( 'mce_external_plugins', array( $this, 'register_tinymce_plugin' ) );

		  // Add a callback to add our button to the TinyMCE toolbar
		  add_filter( 'mce_buttons', array( $this, 'add_tinymce_button' ) );
	}

	//This callback registers our tinymce plug-in
	public function register_tinymce_plugin( $plugin_array ) {
		$plugin_array['sqc_embed_button'] = SQUARECANDY_TINYMCE_DIR_URL . '/js/shortcode-mce-button.js';
		return $plugin_array;
	}

	//This callback adds our button to the toolbar
	public function add_tinymce_button( $buttons ) {
		//Add the button ID to the $button array
		//sqcdy_log( $buttons, '$buttons' );
		$buttons[] = 'sqc_embed_button';
		return $buttons;
	}
}

class SQC_Embed {

	public $name        = ''; // shortcode (if used)
	public $js_name     = ''; // camelCase version of class name (used as identifier for localized variables/functions in js)
	public $embed_regex = ''; // for autoembed, this is the regex to isolate the url used to populate the iframe

	public $add_shortcode   = true; // add a shortcode
	public $add_to_button   = true; // add to the shortcode button in the visual editor
	public $auto_embed      = false; // add auto embed
	public $paste_intercept = false; // intercept pasted code block & replace

	public $paste_intercept_settings  = array();

	public $shortcode_button_settings = array();

	public $iframe_wrapper = array( 'open' => '', 'close' => '' );

	const DEFAULT_PASTE_INTERCEPT_SETTINGS  = array(
		'checkTag'     => 'iframe', // optional - tag to check for surrounding the text (set falsy to bypass)
		'checkText'    => '', // required - pattern to check for
		'message'      => '', // required - message to be displayed when pattern is matched
		'replaceRegex' => '', // optional - tregex to locate url/identifier (without delimiters)
		'replacePre'   => '', // optional - text of outout that goes before the identifier
		'replacePost'  => '', // optional - text of outout that goes after the identifier
		'custom_js'    => '', // optional - javascript defining custom function to use instead of default regex/replace. function name must be like {$class->js_name}Process
	);

	const DEFAULT_SHORTCODE_BUTTON_SETTINGS = array(
		'shortcode' => '', // required - used as identifier & shortcode
		'title'     => '', // required - label fro the radio button for this option
		'notes'     => '', // required - notes displayed when this option is selected
		'notesMore' => '', // optional - more notes displayed in a closed by default accordion section
		'noCode'    => '', // optional - if truthy, link pasted into the input is passed directly to content (e.g. youtube/vimeo)
		'customJS'  => '', // optional - function name for custom function to process input
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
			$this->iframe_wrapper['open'] = '<p><figure class="wp-block-embed-' . $this->name . ' wp-block-embed is-type-audio is-provider-' . $this->name . ' js">' . '<div class="wp-block-embed__wrapper">';
			$this->iframe_wrapper['close'] = '</div>' . '</figure></p>';
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

		if ( filter_var( $url, FILTER_VALIDATE_URL ) === FALSE ) {
		    return false;
		}

		if ( $force_noslash ) {
		    $url = rtrim( $url, '/\\' );
		}

		return $url;
		
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
		'noCode'  => '1',
	);

	/**
	 * Function to create an iframe
	 */
	public function create_iframe( $attr ) {

		$attr = shortcode_atts(
			array(
				'width'     => 350,
				'height'    => 470,
				'album'     => null,
				'title'     => null,
				'size'      => 'large',
				'bgcol'     => 'ffffff',
				'url'       => null,
				'linkcol'   => '0687f5',
				'tracklist' => 'false',
				'title'     => null,
				'artwork'   => null,
			),
			$attr
		);

		extract( $attr );

		if ( $album == null ) {
			return false;
		}

		//add px to width/height (but not if width is e.g. 100%)
		if ( preg_match( '#^[0-9]+$#', $width ) ) {
				$width = $width . 'px';
		}
		if ( preg_match( '#^[0-9]+$#', $height ) ) {
				$height = $height . 'px';
		}

		$iframe = '<iframe style="border: 0; width: %s; height: %s;" src="https://bandcamp.com/EmbeddedPlayer/album=%s/size=%s/bgcol=%s/linkcol=%s/tracklist=%s/transparent=true/artwork=%s" title="%s" seamless></iframe>';

		return sprintf(
			$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
			$width,
			$height,
			$album,
			$size,
			$bgcol,
			$linkcol,
			$tracklist,
			$artwork,
			$title
		);
	}
}

/**
 * Add paste intercept for Youtube video iframes
 * Pasted iframes are replaced with the video url, which is auto-embedded by WP
 */ 

class SQC_Youtube_Embed extends SQC_Embed {

	public $name    = 'sqc-youtube';
	public $js_name = 'sqcYoutube';

	//public $regex;
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
		'noCode'  => '1',
	);
}

/**
 * Add paste intercept for Vimeo video iframes
 * Pasted iframes are replaced with the video url, which is auto-embedded by WP
 */ 

class SQC_Vimeo_Embed extends SQC_Embed {

	public $name    = 'sqc-vimeo';
	public $js_name = 'sqcVimeo';

	//public $regex;
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
		'noCode'  => '1',
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

	public $name    = 'sqc-instagram';
	public $js_name = 'sqcInstagram';

	//public $regex;
	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkTag'     => 'script',
		'checkText'    => 'instagram',
		'message'      => 'We have detected that you are trying to paste an Instagram iframe embed into the HTML view. For better results, we are replacing this with the appropriate URL format. To avoid this message in the future, please paste the Instagram URL into the Visual tab instead of the iframe embed code.',
		'replaceRegex' => '(https://www\.instagram\.com/reel/[a-zA-Z0-9]+)',
		'replacePre'   => '',
		'replacePost'  => '',
	);
	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-instagram',
		'title'     => 'Instagram Video',
		'notes'     => 'You can embed Instagram videos by pasting the link here.', // @TODO trim params from instagram urls here
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {
		//@TODO instagram links with / on ends aren't working here, need to fix that
		// these are narrower than others - maybe 540 is too narrow

		if ( count( $attr ) === 1 && array_keys( $attr )[0] === 0 ) :
			$attr = array( 'url' => $attr[0] );
		endif;
		//sqcdy_log( $attr, 'instagram 2' );

		$attr = shortcode_atts(
			array(
				'width'  => 540,
				'height' => 881,
				'url'    => null,
			),
			$attr
		);

		extract( $attr );

		$url = $this->validate_url( $url );

		// grab just the part we need of the url
		$regex = '#(https://www\.instagram\.com/reel/[a-zA-Z0-9]+)#';
		preg_match( $regex, $url, $matches );
		$url = isset( $matches[1] ) ? $matches[1] : false;

		if ( ! $url ) :
			return false;
		endif;

		//$url = urlencode( $url ); // also need to trim it make sure ends with slash or not

		$raw_width  = $width;
		$raw_height = $height;

		if ( preg_match( '#^[0-9]+$#', $width ) ) {
				$width = $width . 'px';
		}
		if ( preg_match( '#^[0-9]+$#', $height ) ) {
				$height = $height . 'px';
		}
		//@TODO set id to something unique!!
		$iframe = '<iframe id="instagram-embed-1" class="instagram-media instagram-media-rendered" style="background: white; max-width: %s; width: calc(100%% - 2px); border-radius: 3px; border: 1px solid #dbdbdb; box-shadow: none; display: block; margin: 0px 0px 12px; min-width: 326px; padding: 0px; position: static !important;" src="%s/embed/?cr=1&amp;v=14&amp;wp=%s" height="%s" frameborder="0" scrolling="no" allowfullscreen="allowfullscreen" data-instgrm-payload-id="instagram-media-payload-0"></iframe>';

		return sprintf(
			$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
			$width,
			$url,
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

	public $name        = 'sqc-facebook-video';
	public $js_name     = 'sqcFacebookVideo';
	public $embed_regex = '#https://www\.facebook\.com/watch/\?v=([\d]+)/?#i';
	//public $regex;
	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = true;
	public $paste_intercept = true;

	public $paste_intercept_settings  = array(
		'checkText'    => 'facebook.com/plugins/video.php',
		'message'      => 'We have detected that you are trying to paste a Facebook video iframe embed into the HTML view. For better results, we are replacing this with the appropriate shortcode. To avoid this message in the future, please add the Facebook URL using the shortcode button on the Visual tab instead of the iframe embed code.',
		'replaceRegex' => 'video\.php\?.+href=([^&?"]*)', //@TODO make sure trailing slash is trimmed here
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
		if ( count( $attr ) === 1 && array_keys( $attr )[0] === 0 ) :
			$attr = array( 'url' => $attr[0] );
		endif;
		//sqcdy_log( $attr, 'facebook 2' );

		$attr = shortcode_atts(
			array(
				'width'  => 350,
				'height' => 470,
				'url'    => null,
			),
			$attr
		);

		extract( $attr );

		$url = $this->validate_url( $url );

		if ( ! $url ) :
			return false;
		endif;

		$url = urlencode( $url );

		if ( preg_match( '#^[0-9]+$#', $width ) ) {
				$width = $width . 'px';
		}
		if ( preg_match( '#^[0-9]+$#', $height ) ) {
				$height = $height . 'px';
		}

		//@TODO Maybe don't need the px adding

		$iframe = '<iframe src="https://www.facebook.com/plugins/video.php?href=%1$s&show_text=0&width=%2$s" width="%2$s" height="%3$s" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowFullScreen="true"></iframe>';

		return sprintf(
			'<p><figure class="wp-block-embed-facebook wp-block-embed is-type-audio is-provider-facebook wp-embed-aspect-16-9 wp-has-aspect-ratio js fitvids">' . '<div class="wp-block-embed__wrapper">' . $iframe . '</div>' . '</figure></p>',
			$url,
			$width,
			$height
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

	public $name    = 'sqc-gmaps';
	public $js_name = 'sqcGoogleMaps';

	//public $regex;
	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	//public $customJavascript = 

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
			newText = newText.replace( iframeClose, ' ]' ); 
			return newText; 
		};", // needs some adjustment for visual editor
	);

	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-gmaps',
		'title'     => 'Google Maps',
		'notes'     => 'You can embed Google Maps by pasting the embed code here, or by pasting it directly into the content editor.',
		'customJS'  => 'sqcGoogleMapsProcess',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {
		//sqcdy_log( $attr, 'gmaps' );
		//sqcdy_log( array_keys( $attr ), 'count ' . count( $attr ) );
		//sqcdy_log( count( $attr ) === 1 );
		//sqcdy_log( array_keys( $attr )[0] === 0 );
		if ( count( $attr ) === 1 && array_keys( $attr )[0] === 0 ) :
			$attr = array( 'src' => $attr[0] );
		endif;
		//sqcdy_log( $attr, 'gmaps 2' );

		$attr = shortcode_atts(
			array(
				'width'  => 540,
				'height' => 881,
				'src'    => null,
			),
			$attr
		);

		extract( $attr );

		if ( $src == null ) :
			return false;
		endif;

		//$url = urlencode( $url ); // also need to trim it make sure ends with slash or not

		$iframe = '<iframe src="%s" width="%s" height="%s" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';

		return sprintf(
			$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
			$src,
			$width,
			$height
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
			newText = newText.replace( iframeClose, ' ]' ); 
			return newText; 
		};", //@TODO grab individual params, or at least trim src & get rid of non-embed customizable options
	);

	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-gforms',
		'title'     => 'Google Forms',
		'notes'     => 'You can embed Google Forms by pasting the embed code here, or by pasting it directly into the content editor.',
		'customJS'  => 'sqcGoogleFormsProcess',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {
		//sqcdy_log( $attr, 'gmaps' );
		//sqcdy_log( array_keys( $attr ), 'count ' . count( $attr ) );
		//sqcdy_log( count( $attr ) === 1 );
		//sqcdy_log( array_keys( $attr )[0] === 0 );
		if ( count( $attr ) === 1 && array_keys( $attr )[0] === 0 ) :
			$attr = array( 'src' => $attr[0] );
		endif;
		//sqcdy_log( $attr, 'gmaps 2' );

		$attr = shortcode_atts(
			array(
				'width'  => 640,
				'height' => 494,
				'src'    => null,
			),
			$attr
		);

		extract( $attr );

		if ( $src == null ) :
			return false;
		endif;

		//$url = urlencode( $url ); // also need to trim it make sure ends with slash or not

		$iframe = '<iframe src="%s" width="%s" height="%s" frameborder="0" marginheight="0" marginwidth="0">Loading…</iframe>';
		return sprintf(
			$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
			$src,
			$width,
			$height
		);
	}
}

/**
 * Manage embeds/shortcode for Mailchimp list archive
 * 
 * Outputs script tag like:
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
		'replacePost'  => ' ]',
	);

	public $shortcode_button_settings = array(
		'shortcode' => 'mailchimp-archive',
		'title'     => 'Mailchimp Archive',
		'notes'     => 'You can embed Mailchimp Archives by pasting the embed code here.',
		'customJS'  => 'sqcMailchimpArchiveProcess',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {

		if ( count( $attr ) === 1 && array_keys( $attr )[0] === 0 ) :
			$attr = array( 'src' => $attr[0] );
		endif;
		//sqcdy_log( $attr, 'gmaps 2' );

		$attr = shortcode_atts(
			array(
				'src'    => null,
			),
			$attr
		);

		extract( $attr );

		if ( $src == null ) :
			return false;
		endif;

		$style = '<style type="text/css"><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span><br /><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span><!-- .display_archive { font-family: Source Sans Pro,sans-serif; font-size: 20px; font-size: 1.25rem; line-height: 1.4; font-weight: 400; letter-spacing: 0; } .campaign {line-height: 125%%; margin: 5px;} //--><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_end">﻿</span><br /></style>';

		$script = '<script language="javascript" src="%s" type="text/javascript"><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span></script>';

		return sprintf(
			$this->iframe_wrapper['open'] . $style . $script . $this->iframe_wrapper['close'],
			$src,
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
		'replacePost'  => ' ]',
	);

	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-termageddon',
		'title'     => 'Termageddon',
		'notes'     => 'You can embed your Termageddon Privacy Policy by pasting the embed code here.',
		//'customJS'  => 'sqcTermageddonProcess',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {

		if ( count( $attr ) === 1 && array_keys( $attr )[0] === 0 ) :
			$attr = array( 'src' => $attr[0] );
		endif;
		//sqcdy_log( $attr, 'gmaps 2' );

		$attr = shortcode_atts(
			array(
				'src'    => null,
			),
			$attr
		);

		extract( $attr );

		if ( $src == null ) :
			return false;
		endif;

		$script = '<div id="policy" data-policy-key="%1$s" data-extra="email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion">Please wait while the policy is loaded. If it does not load, please <a href="https://app.termageddon.com/api/policy/%1$s?email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion" target="_blank" rel="nofollow noopener">click here</a>.</div>
            <script src="https://app.termageddon.com/js/termageddon.js"></script>';

		return sprintf(
			'<p><div class="wp-block-embed__wrapper">' . $script . '</p></div>',
			$src,
		);
	}
}

$sqc_embeds = new SQC_Embed_Manager();
