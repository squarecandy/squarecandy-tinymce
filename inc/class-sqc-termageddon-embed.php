<?php
/**
 * Manage embeds/shortcode for Termageddon Privacy Policy
 *
 * Outputs like:
 *    <div id="policy" data-policy-key="U21SQ2FrRXhXbU13VTNOS0szYzlQUT09" data-extra="email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion">
 *       Please wait while the policy is loaded. If it does not load, please
 *       <a href="https://app.termageddon.com/api/policy/U21SQ2FrRXhXbU13VTNOS0szYzlQUT09?email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion"
 *       target="_blank" rel="nofollow noopener">click here</a>.</div>
 *       <script src="https://app.termageddon.com/js/termageddon.js"></script>
 */

class SQC_Termageddon_Embed extends SQC_Embed {

	public $name    = 'sqc-termageddon';
	public $js_name = 'sqcTermageddon';

	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = true;

	public $paste_intercept_settings = array(
		'checkTag'     => 'script',
		'checkText'    => 'app.termageddon.com/api',
		'message'      => 'We have detected that you are trying to paste a Termageddon embed into the HTML view. For better results, we are replacing this with the appropriate shortcode format.',
		'replaceRegex' => 'data-policy-key=(?:"|&quot;)([a-zA-Z0-9]*)',
		'replacePre'   => '[sqc-termageddon ',
		'replacePost'  => ']',
	);

	public $shortcode_button_settings = array(
		'shortcode'    => 'sqc-termageddon',
		'title'        => 'Termageddon',
		'notes'        => 'You can embed your Termageddon Privacy Policy by pasting the embed code here.',
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

		$script = '<div id="policy" data-policy-key="%1$s" data-extra="email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion">Please wait while the policy is loaded. If it does not load, please <a href="https://app.termageddon.com/api/policy/%1$s?email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion" target="_blank" rel="nofollow noopener">click here</a>.</div>
            <script src="https://app.termageddon.com/js/termageddon.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript

		return sprintf(
			'<p><div class="wp-block-embed__wrapper">' . $script . '</p></div>',
			$attr['src'],
		);
	}
}
