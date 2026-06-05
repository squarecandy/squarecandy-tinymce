<?php
/**
 * Add paste intercept for Youtube video iframes
 * Pasted iframes are replaced with the video url, which is auto-embedded by WP
 * e.g. <iframe title="YouTube video player" src="https://www.youtube.com/embed/0GkvsB5jO0Y?si=ruvE80B-7pQs-Fgl" width="560" height="315" frameborder="0" allowfullscreen="allowfullscreen"></iframe>
 * src can also be like: "https://www.youtube.com/embed/y6KkvC268xI", "https://www.youtube.com/embed/m9bXEgq5Ia8?si=RqImxnI166oWhW-z&amp;start=1"
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
		'replaceRegex' => 'src=(?:\"|&quot;)(.*?)(?:\"|&quot;)', // pass bare url to custom function
		'replacePre'   => 'https://www.youtube.com/watch?v=',
		'replacePost'  => '',
		'custom_js'    => "sqcYoutubeProcess = function( output, pastedData ) {
			const re = new RegExp( 'embed\/([^\?]+)' ); // isolate just video id
			const videoIdMatches = output.match( re );
			const cleanUrl = output.replaceAll( '&amp;', '&' );
			const parsedUrl = URL.parse( cleanUrl );
			const parsedParams = parsedUrl ? parsedUrl.searchParams : null;
			maybeDebug(' output', output);
			maybeDebug(' matches', videoIdMatches);
			maybeDebug(' parsedParams', parsedParams);
			if ( videoIdMatches ) {
				output = videoIdMatches[1];
			}
			if ( videoIdMatches && typeof parsedParams == 'object' && parsedParams.size ) {
				for ( const [key, value] of parsedParams ) {
					if ( key == 't' || key == 'start' ) { // pass start param to url
						maybeDebug( key, value );
						output = output + '&t=' + value;
					}
				}
			}
			return output;
		}",
	);

	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-youtube',
		'title'     => 'Youtube',
		'notes'     => 'You can paste the Youtube url here or directly into the content editor.',
		'noCode'    => '1',
	);
}
