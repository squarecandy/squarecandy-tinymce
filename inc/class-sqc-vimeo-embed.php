<?php
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
