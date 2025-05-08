<?php
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
