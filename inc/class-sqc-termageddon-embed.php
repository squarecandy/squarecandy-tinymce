<?php
/**
 * Manage embeds/shortcode for Termageddon Privacy Policy
 *
 * Outputs like:
 * OLD:
 *    <div id="policy" data-policy-key="XXXYYY123" data-extra="email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion">
 *       Please wait while the policy is loaded. If it does not load, please
 *       <a href="https://app.termageddon.com/api/policy/XXXYYY123?email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion"
 *       target="_blank" rel="nofollow noopener">click here</a>.</div>
 *       <script src="https://app.termageddon.com/js/termageddon.js"></script>
 * NEW:
 *    <div id="XXXYYY123" class="policy_embed_div" width="640" height="480">
 *       Please wait while the policy is loaded. If it does not load, please <a rel="nofollow" href="https://policies.termageddon.com/api/policy/XXXYYY123" target="_blank">
 *       click here</a> to view the policy.</div>
 *       <script src="https://policies.termageddon.com/api/embed/XXXYYY123.js"></script>
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
		'checkText'    => 'termageddon.com/api',
		'message'      => 'We have detected that you are trying to paste a Termageddon embed into the HTML view. For better results, we are replacing this with the appropriate shortcode format.',
		'replaceRegex' => '\.termageddon\.com\/api\/policy\/([a-zA-Z0-9]*)',
		'replacePre'   => '[sqc-termageddon ',
		'replacePost'  => ']',
		'custom_js'    => 'function sqcTermageddonProcess( output, pastedData ) {
			const isV1 = typeof pastedData !== "undefined" && pastedData.includes( "data-policy-key" );
			if ( isV1 ) {
				output = "src=" + output + " version=v1";
			}
			return output;
		}',
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
				'src'     => null,
				'version' => null,
			),
			'src' // accepts single att
		);

		if ( ! $attr['src'] ) :
			return false;
		endif;

		$v1_script = '<div id="policy" data-policy-key="%1$s" data-extra="email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion">Please wait while the policy is loaded. If it does not load, please <a href="https://app.termageddon.com/api/policy/%1$s?email-links=true&amp;h-align=left&amp;no-title=true&amp;table-style=accordion" target="_blank" rel="nofollow noopener">click here</a>.</div>
            <script src="https://app.termageddon.com/js/termageddon.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
		$v2_script = '<div id="%1$s" class="policy_embed_div" width="640" height="480"> Please wait while the policy is loaded. If it does not load, please <a rel="nofollow" href="https://policies.termageddon.com/api/policy/%1$s" target="_blank">click here</a> to view the policy.</div>
             <script src="https://policies.termageddon.com/api/embed/%1$s.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
		$script    = 'v1' === $attr['version'] ? $v1_script : $v2_script;

		return sprintf(
			'<p><div class="wp-block-embed__wrapper">' . $script . '</p></div>',
			$attr['src'],
		);
	}
}
