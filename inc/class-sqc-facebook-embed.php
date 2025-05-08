<?php
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
