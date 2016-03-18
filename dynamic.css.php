<?php
	// serve the file as CSS
	header('Content-type: text/css');

	// Load WordPress Bootstrap */
	require_once( $_SERVER[DOCUMENT_ROOT] . '/wp-load.php' );

	/** Allow for cross-domain requests (from the frontend). */
	send_origin_headers();

	$sqcdy_theme_colwidth = get_option('sqcdy_theme_colwidth');
?>
<?php if (!empty($sqcdy_theme_colwidth)) : ?>
	body.mce-content-body {
		max-width: <?php echo $sqcdy_theme_colwidth; ?>px;
		border-right: 1px dashed #ccc;
	}
<?php endif; ?>
