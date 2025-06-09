<?php
/**
 * Manage embeds/shortcode for Mailchimp list archive
 *
 * Outputs style/script tags like:
 *    <style type="text/css"><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span><br />
 *    <span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span>
 *    <!-- .display_archive { font-family: Source Sans Pro,sans-serif; font-size: 20px; font-size: 1.25rem; line-height: 1.4; font-weight: 400; letter-spacing: 0; }
 *    .campaign {line-height: 125%%; margin: 5px;} //--><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark"
 *    class="mce_SELRES_end">﻿</span><br /></style>'
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
		'replacePost'  => ']',
	);

	public $shortcode_button_settings = array(
		'shortcode'    => 'mailchimp-archive',
		'title'        => 'Mailchimp Archive',
		'notes'        => 'You can embed Mailchimp Archives by pasting the embed code here.',
		'notesMore'    => '<a href="https://mailchimp.com/help/add-an-email-campaign-archive-to-your-website/" target="_blank">More information on how to get your Mailchimp archive embed.</a>',
		'functionName' => 'replacePastedText',
	);

	/**
	 * Function to create an iframe
	 * Override this in child classes
	 */
	public function create_iframe( $attr ) {

		$attr = $this->process_shortcode_attr(
			$attr,
			array(
				'src' => null,
			),
			'src' // accepts single att
		);

		if ( ! $attr['src'] ) :
			return false;
		endif;

		$style = '<style type="text/css"><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span><br /><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span><!-- .display_archive { font-family: Source Sans Pro,sans-serif; font-size: 20px; font-size: 1.25rem; line-height: 1.4; font-weight: 400; letter-spacing: 0; } .campaign {line-height: 125%%; margin: 5px;} //--><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_end">﻿</span><br /></style>';

		$script = '<script language="javascript" src="%s" type="text/javascript"><span style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" data-mce-type="bookmark" class="mce_SELRES_start">﻿</span></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript

		return sprintf(
			$this->iframe_wrapper['open'] . $style . $script . $this->iframe_wrapper['close'],
			$attr['src'],
		);
	}
}
