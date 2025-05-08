<?php
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
