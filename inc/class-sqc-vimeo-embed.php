<?php
/**
 * Add paste intercept for Vimeo video iframes
 * Pasted iframes are replaced with the video url, which is auto-embedded by WP
 * regular vimeo src urls like "https://player.vimeo.com/video/851112587?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479"
 * also showcase urls like "https://vimeo.com/showcase/8015409/embed"
 * intercept oembed of urls with timecode like "https://vimeo.com/459832142?fl=pl&fe=cm#t=28m7s"
 */

class SQC_Vimeo_Embed extends SQC_Embed {

	public $name            = 'sqc-vimeo';
	public $js_name         = 'sqcVimeo';
	public $add_shortcode   = false;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $extra_scripts   = true;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkText'    => 'vimeo',
		'message'      => 'We have detected that you are trying to paste a Vimeo iframe embed into the HTML view. For better results, we are replacing this with the appropriate URL format. To avoid this message in the future, please paste the Vimeo URL into the Visual tab instead of the iframe embed code.',
		'replaceRegex' => '', // leave empty so customjs function is used
		'replacePre'   => '',
		'replacePost'  => '',
		'custom_js'    => "sqcVimeoProcess = function( output, pastedData ) {
			const re = new RegExp( 'video\/([^?]+)' );
			const singleVideoMatches = pastedData.match( re );
			maybeDebug('sqcVimeoProcess output', output);
			maybeDebug('sqcVimeoProcess pastedData', pastedData);
			maybeDebug('sqcVimeoProcess matches', singleVideoMatches);
			if ( singleVideoMatches ) {
				output = 'https://vimeo.com/' + singleVideoMatches[1];
			} else if ( pastedData.includes( 'showcase' ) ) {
				const showcaseRegex = /https:\/\/vimeo\.com\/showcase\/\d*\/embed/gm;
				const showcaseMatches = pastedData.match( showcaseRegex );
				maybeDebug('showcaseMatches', showcaseMatches );
				if( showcaseMatches ) {
					output = '[sqc-vimeo showcase=true src=\"' + showcaseMatches[0] + '\" ]';
				}
			}
			return output;
		}",
	);

	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-vimeo',
		'title'     => 'Vimeo',
		'notes'     => 'You can paste the Vimeo url here or directly into the content editor.',
		'noCode'    => '1',
	);

	public function create_iframe( $attr ) {
		sqcdy_log( $attr, 'vimeo iframe attr' );
		if ( isset( $attr['showcase'] ) || isset( $attr['timecode'] ) ) :
			$attr = $this->process_shortcode_attr(
				$attr,
				array(
					'src'    => null,
					'width'  => '100%',
					'height' => 450,
				)
			);

			if ( ! $attr['src'] ) :
				return false;
			endif;

			$iframe = '<iframe src="%s" width="%s" height="%s" frameborder="0" allowfullscreen="allowfullscreen"></iframe>';

			return sprintf(
				$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
				$attr['src'],
				$attr['width'],
				$attr['height']
			);
		endif;
	}

	public function extra_scripts() {
		// native WP oembed strips timecode, so add filter to shortcircuit vimeo urls with timecode
		// NB may need to flush oembed cache to get this working: $wp embed cache clear {post_id}
		add_filter(
			'pre_oembed_result',
			function( $html, $url, $args ) {
				if ( str_contains( $url, 'vimeo' ) && str_contains( $url, '#t' ) ) {
					preg_match( '/vimeo\.com\/(\d+).*?(#t=.*s)/', $url, $matches );
					if ( length( $matches ) ) {
						$args['src']      = 'https://player.vimeo.com/video/' . $matches[1] . $matches[2];
						$args['timecode'] = true;
						$html             = $this->create_iframe( $args );
						sqcdy_log( $html, 'pre_oembed_result html' );
					}
				}
				return $html;
			},
			10,
			3
		);
	}
}
