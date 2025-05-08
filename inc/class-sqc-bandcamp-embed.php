<?php
/**
 * Adds missing wordpress.com bandcamp shortcode
 *
 * Outputs an iframe like:
 *    <iframe style="border: 0; width: %s; height: %s;"
 *       src="https://bandcamp.com/EmbeddedPlayer/album={album}/size={size}/bgcol={bgcol}/linkcol={linkcol}/tracklist={tracklist}/transparent=true/artwork={artwork}"
 *       title="{title}" seamless></iframe>';
 */

class SQC_Bandcamp_Embed extends SQC_Embed {

	public $name        = 'bandcamp';
	public $js_name     = 'sqcBandcamp';
	public $embed_regex = '';

	public $add_shortcode   = true;
	public $add_to_button   = true;
	public $auto_embed      = false;
	public $paste_intercept = false;

	public $shortcode_button_settings = array(
		'shortcode' => 'sqc-bandcamp',
		'title'     => 'Bandcamp',
		'notes'     => 'You can paste the "Wordpress" version of the Bandcamp Embed code here or directly into the content editor.',
		'noCode'    => '1',
	);

	/**
	 * Function to create an iframe
	 */
	public function create_iframe( $attr ) {

		$attr = $this->process_shortcode_attr(
			$attr,
			array(
				'album'     => null,
				'width'     => 350,
				'height'    => 470,
				'title'     => null,
				'size'      => 'large',
				'bgcol'     => 'ffffff',
				'url'       => null,
				'linkcol'   => '0687f5',
				'tracklist' => 'false',
				'title'     => null,
				'artwork'   => null,
			)
		);

		if ( ! $attr['album'] ) {
			return false;
		}

		$attr['width']  = $this->maybe_add_px( $attr['width'] );
		$attr['height'] = $this->maybe_add_px( $attr['height'] );

		$iframe = '<iframe style="border: 0; width: %s; height: %s;" src="https://bandcamp.com/EmbeddedPlayer/album=%s/size=%s/bgcol=%s/linkcol=%s/tracklist=%s/transparent=true/artwork=%s" title="%s" seamless></iframe>';

		return sprintf(
			$this->iframe_wrapper['open'] . $iframe . $this->iframe_wrapper['close'],
			$attr['width'],
			$attr['height'],
			$attr['album'],
			$attr['size'],
			$attr['bgcol'],
			$attr['linkcol'],
			$attr['tracklist'],
			$attr['artwork'],
			$attr['title'],
		);
	}
}
