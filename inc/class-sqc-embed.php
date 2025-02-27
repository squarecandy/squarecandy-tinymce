<?php
error_log( 'SQC_Embed' );

class SQC_Embed_Manager {

	private $add_editor_button             = true;
	private $visual_editor_paste_intercept = true;

	private $embed_classes = array(
		'SQC_Youtube_Embed',
		'SQC_Facebook_Embed',
		'SQC_Instagram_Embed',
		'SQC_GoogleMaps_Embed',
		'SQC_Bandcamp_Embed',
	);

	private $javascript_variables = array(
		'pasteIntercept' => array(),
		'mceButton'      => array(),
	);

	private $custom_js = '';

	public function __construct() {

		// allow filtering of which are loaded = so you can turn them off, or add your own class and enqueue it here
		$this->embed_classes = apply_filters( 'sqc_embed_classes', $this->embed_classes );

		//loop trhough and instantiate a list of classes
		foreach ( $this->embed_classes as $embed_class_name ) {
			sqcdy_log( 'register_embed_class ' . $embed_class_name );
			$this->register_embed_class_name( $embed_class_name );
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

	// add out intercepts to the visual editor paste preprocess
	public function tinymce_before_paste_preprocess( $code ) {

		$visual_paste_intercept = "
			console.log( 'paste_preprocess' );
			console.log( 'pasteintercept', typeof pasteIntercept );
			console.log( 'pre content', args.content );
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

	private function register_embed_class_name( $class_name ) {
		$embed_class = new $class_name();
		sqcdy_log( $embed_class, 'embed_class' );

		$this->register_embed_class( $embed_class );
	}

	private function register_embed_class( $embed_class ) {

		if ( $embed_class->paste_intercept && $embed_class->paste_intercept_settings ) {
			if ( ! empty( $embed_class->paste_intercept_settings['custom_js'] ) ) {
				$this->custom_js .= $embed_class->paste_intercept_settings['custom_js'];
			}
			$this->javascript_variables['pasteIntercept'][ $embed_class->js_name ] = $embed_class->paste_intercept_settings;
		}

		if ( $embed_class->add_to_button && $embed_class->shortcode_button_settings ) {
			$this->javascript_variables['mceButton'][ $embed_class->js_name ] = $embed_class->shortcode_button_settings;
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

	//This callback registers our plug-in
	public function register_tinymce_plugin( $plugin_array ) {
		$plugin_array['sqc_embed_button'] = SQUARECANDY_TINYMCE_DIR_URL . '/js/shortcode-mce-button.js';
		return $plugin_array;
	}

	//This callback adds our button to the toolbar
	public function add_tinymce_button( $buttons ) {
		//Add the button ID to the $button array
		sqcdy_log( $buttons, '$buttons' );
		$buttons[] = 'sqc_embed_button';
		return $buttons;
	}
}

class SQC_Embed {

	public $name        = '';
	public $js_name     = '';
	public $embed_regex = '';

	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = false;

	public $paste_intercept_settings  = array();
	public $shortcode_button_settings = array();

	public function __construct( $attr = array() ) {
		sqcdy_log( '__construct ' . $this->name );
		/*foreach ( $attr as $key => $value ) { // do this properly
			$this->$key = $value;
		}*/
		//add filter so atts can be changed per site
		if ( $this->add_shortcode ) {
			$this->register_shortcode();
		}
		if ( $this->auto_embed ) {
			$this->add_auto_embed();
		}
	}

	/**
	 * Function to create an iframe
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
		sqcdy_log( 'register_shortcode ' . $this->name );
		add_shortcode(
			$this->name,
			array( $this, 'process_shortcode' )
		);
	}

	/**
	 * Add custom auto embed function
	 * So when you enter a matching url in the visual editor, it will be turned into an iframe
	 * (?or could we store it as our shortcode?)
	 */
	public function add_auto_embed() {
		sqcdy_log( 'add_auto_embed' );
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
	function embed_handler( $matches, $attr, $url, $rawattr ) {
		sqcdy_log( $matches, 'wp_embed_handler_facebook' . $url );
		sqcdy_log( $attr, 'attr' );
		sqcdy_log( $rawattr, 'rawattr' );
		$attr['url'] = $url;
		return $this->create_iframe( $attr );
	}
}

class SQC_Bandcamp_Embed extends SQC_Embed {

	public $name        = 'bandcamp';
	public $js_name     = 'sqcBandcamp';
	public $embed_regex = '';

	public $add_shortcode   = true;
	public $add_to_button   = false;
	public $auto_embed      = false;
	public $paste_intercept = false;

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {
		sqcdy_log( $attr, 'create_iframe bandcamp' );

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

		if ( preg_match( '#^[0-9]+$#', $width ) ) {
				$width = $width . 'px';
		}
		if ( preg_match( '#^[0-9]+$#', $height ) ) {
				$height = $height . 'px';
		}

			$iframe = '<iframe style="border: 0; width: %s; height: %s;" src="https://bandcamp.com/EmbeddedPlayer/album=%s/size=%s/bgcol=%s/linkcol=%s/tracklist=%s/transparent=true/artwork=%s" title="%s" seamless></iframe>';

		return sprintf(
			'<p><figure class="wp-block-embed-bandcamp wp-block-embed is-type-audio is-provider-bandcamp js">' . '<div class="wp-block-embed__wrapper">' . $iframe . '</div>' . '</figure></p>',
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

class SQC_Youtube_Embed extends SQC_Embed {

	public $name    = 'sqc-youtube';
	public $js_name = 'sqcYoutube';

	//public $regex;
	public $add_shortcode   = false;
	public $add_to_button   = false;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkText'    => 'youtu',
		'message'      => 'We have detected that you are trying to paste a YouTube iframe embed into the HTML view. For better results, we are replacing this with the appropriate URL format. To avoid this message in the future, please paste the YouTube URL into the Visual tab instead of the iframe embed code.',
		'replaceRegex' => 'embed\/([^?]+)',
		'replacePre'   => 'https://www.youtube.com/watch?v=',
		'replacePost'  => '',
	);
}

class SQC_Instagram_Embed extends SQC_Embed {

	public $name    = 'sqc-instagram';
	public $js_name = 'sqcInstagram';

	//public $regex;
	public $add_shortcode   = true;
	public $add_to_button   = false;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkText'    => 'vimeo',
		'message'      => 'We have detected that you are trying to paste a Vimeo iframe embed into the HTML view. For better results, we are replacing this with the appropriate URL format. To avoid this message in the future, please paste the Vimeo URL into the Visual tab instead of the iframe embed code.',
		'replaceRegex' => 'video\/([^?]+)',
		'replacePre'   => 'https://vimeo.com/',
		'replacePost'  => '',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {
		sqcdy_log( $attr, 'instagram' );
		sqcdy_log( array_keys( $attr ), 'count ' . count( $attr ) );
		sqcdy_log( count( $attr ) === 1 );
		sqcdy_log( array_keys( $attr )[0] === 0 );
		if ( count( $attr ) === 1 && array_keys( $attr )[0] === 0 ) :
			$attr = array( 'url' => $attr[0] );
		endif;
		sqcdy_log( $attr, 'instagram 2' );

		$attr = shortcode_atts(
			array(
				'width'  => 540,
				'height' => 881,
				'url'    => null,
			),
			$attr
		);

		extract( $attr );

		if ( $url == null ) :
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

		$iframe = '<iframe id="instagram-embed-1" class="instagram-media instagram-media-rendered" style="background: white; max-width: %s; width: calc(100%% - 2px); border-radius: 3px; border: 1px solid #dbdbdb; box-shadow: none; display: block; margin: 0px 0px 12px; min-width: 326px; padding: 0px; position: static !important;" src="%s/embed/?cr=1&amp;v=14&amp;wp=%s" height="%s" frameborder="0" scrolling="no" allowfullscreen="allowfullscreen" data-instgrm-payload-id="instagram-media-payload-0"></iframe>';

		return sprintf(
			'<p><figure class="wp-block-embed-instagram wp-block-embed is-type-audio is-provider-instagram js">' . '<div class="wp-block-embed__wrapper">' . $iframe . '</div>' . '</figure></p>',
			$width,
			$url,
			$raw_width,
			$raw_height
		);
	}
}

class SQC_GoogleMaps_Embed extends SQC_Embed {

	public $name    = 'sqc-gmaps';
	public $js_name = 'sqcGoogleMaps';

	//public $regex;
	public $add_shortcode   = true;
	public $add_to_button   = false;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkText'    => 'www.google.com/maps/embed',
		'message'      => 'We have detected that you are trying to paste a Google Maps iframe embed into the HTML view. For better results, we are replacing this with the appropriate shortcode format.',
		'replaceRegex' => 'video\/([^?]+)',
		'replacePre'   => 'https://vimeo.com/',
		'replacePost'  => '',
		'custom_js'    => "sqcGoogleMapsProcess = function( pastedData ) { newText = pastedData.replace( '<iframe', '[sqc-gmaps ' ); newText = newText.replace( '></iframe>', ' ]' ); return newText; };",
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {
		sqcdy_log( $attr, 'gmaps' );
		sqcdy_log( array_keys( $attr ), 'count ' . count( $attr ) );
		sqcdy_log( count( $attr ) === 1 );
		sqcdy_log( array_keys( $attr )[0] === 0 );
		if ( count( $attr ) === 1 && array_keys( $attr )[0] === 0 ) :
			$attr = array( 'src' => $attr[0] );
		endif;
		sqcdy_log( $attr, 'gmaps 2' );

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
			'<p><figure class="wp-block-embed-googlemaps wp-block-embed is-type-audio is-provider-googlemaps js">' . '<div class="wp-block-embed__wrapper">' . $iframe . '</div>' . '</figure></p>',
			$src,
			$width,
			$height
		);
	}
}

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
		'replaceRegex' => 'video\.php\?href=([^?]+)\?',
		'replacePre'   => '[sqc-facebook-video ',
		'replacePost'  => ']',
	);
	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-facebook-video',
		'title'     => 'Facebook Video',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {
		if ( count( $attr ) === 1 && array_keys( $attr )[0] === 0 ) :
			$attr = array( 'url' => $attr[0] );
		endif;
		sqcdy_log( $attr, 'facebook 2' );

		$attr = shortcode_atts(
			array(
				'width'  => 350,
				'height' => 470,
				'url'    => null,
			),
			$attr
		);

		extract( $attr );

		if ( $url == null ) :
			return false;
		endif;

		$url = urlencode( $url );

		if ( preg_match( '#^[0-9]+$#', $width ) ) {
				$width = $width . 'px';
		}
		if ( preg_match( '#^[0-9]+$#', $height ) ) {
				$height = $height . 'px';
		}

		$iframe = '<iframe src="https://www.facebook.com/plugins/video.php?href=%s&show_text=0&width=%s" width="%s" height="%s" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowFullScreen="true"></iframe>';

		return sprintf(
			'<p><figure class="wp-block-embed-facebook wp-block-embed is-type-audio is-provider-facebook wp-embed-aspect-16-9 wp-has-aspect-ratio js fitvids">' . '<div class="wp-block-embed__wrapper">' . $iframe . '</div>' . '</figure></p>',
			$url,
			$width,
			$width,
			$height
		);
	}
}

$sqc_embeds = new SQC_Embed_Manager();
