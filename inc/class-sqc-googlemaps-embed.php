<?php
/**
 * Manage embeds/shortcode for Google Maps
 *
 * We need to start with the full embed code provide by Google Maps. To store in content without an iframe, the shortcode takes the properties of the original iframe.
 *
 * Outputs an iframe like:
 *    '<iframe src="{SRC}" width="XXX" height="YYY" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
 *
 * The $paste_intercept_settings sqcGoogleMapsProcess() function will strip height & width parameters out of the initially pasted code, but they can be manually re-added if we need to override the defaults
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
			let newText = pastedData;
			const srcRegex = /src=(?:\"|&quot;).+?(?:\"|&quot;)/g;			
			const widthRegex = /width=(?:\"|&quot;)\d*(?:\"|&quot;)/g;			
			const heightRegex = /height=(?:\"|&quot;)\d*(?:\"|&quot;)/g;

			const srcMatches = [...newText.matchAll(srcRegex)];
			const widthMatches = [...newText.matchAll(widthRegex)];
			const heightMatches = [...newText.matchAll(heightRegex)];
			maybeDebug( 'srcMatches', srcMatches );
			maybeDebug( 'widthMatches', widthMatches );
			maybeDebug( 'heightMatches', heightMatches );

			if( srcMatches.length ) {
				newText = '[sqc-gmaps ' + srcMatches[0];
			}	
			if( widthMatches.length ) {
				newText += ' ' + widthMatches[0];
			}
			if( heightMatches.length ) {
				newText += ' ' + heightMatches[0];
			}
			
			newText += ' ]';	
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
				'width'  => '100%',
				'height' => 450,
			),
			'src' // accepts single att
		);

		if ( ! $attr['src'] ) :
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
