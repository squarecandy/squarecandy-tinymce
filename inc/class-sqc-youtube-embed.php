<?php
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
