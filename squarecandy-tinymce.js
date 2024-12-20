function typeInTextarea(newText, el = document.activeElement) {
	const [start, end] = [el.selectionStart, el.selectionEnd];
	el.setRangeText(newText, start, end, 'select');
}

jQuery( document ).ready(
	function($){

		// anytime the user pastes into the HTML view check if the value has a youtube iframe.
		$( document ).on( 'paste', 'textarea.wp-editor-area', function(e){
			// access the clipboard using the api
			var pastedData = e.originalEvent.clipboardData.getData('text');

			// YouTube
			// if the paste value has '<iframe' and 'youtu' in it, then we need to intercept it.
			if ( pastedData.indexOf( '<iframe' ) > -1 && pastedData.indexOf( 'youtu' ) > -1 ) {

				$(this).before('<div style="position:absolute; top:31px" class="squarecandy-tinymce-alert message error">' +
					'We have detected that you are trying to paste a YouTube iframe embed into the HTML view. ' +
					'For better results, we are replacing this with the appropriate URL format. ' +
					'To avoid this message in the future, please paste the YouTube URL into the Visual tab ' +
					'instead of the iframe embed code.' +
					'</div>');

				// clear the alert after 20 seconds
				setTimeout(function(){
					$( '.squarecandy-tinymce-alert' ).fadeOut();
				}, 20 * 1000);

				// get the youtube video id from the iframe
				var videoId = pastedData.match( /embed\/([^?]+)/ )[1];
				// create the youtube URL
				var youtubeUrl = "\n\nhttps://www.youtube.com/watch?v=" + videoId + "\n\n";

				e.preventDefault();

				// replace the clipboard data with the youtube URL
				typeInTextarea( youtubeUrl );
			}

			// Vimeo
			// if the paste value has '<iframe' and 'vimeo' in it, then we need to intercept it.
			if ( pastedData.indexOf( '<iframe' ) > -1 && pastedData.indexOf( 'vimeo' ) > -1 ) {

				$(this).before('<div style="position:absolute; top:31px" class="squarecandy-tinymce-alert message error">' +
					'We have detected that you are trying to paste a Vimeo iframe embed into the HTML view. ' +
					'For better results, we are replacing this with the appropriate URL format. ' +
					'To avoid this message in the future, please paste the Vimeo URL into the Visual tab ' +
					'instead of the iframe embed code.' +
					'</div>');

				// clear the alert after 20 seconds
				setTimeout(function(){
					$( '.squarecandy-tinymce-alert' ).fadeOut();
				}, 20 * 1000);

				// get the vimeo video id from the iframe format "https://player.vimeo.com/video/271777551?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479"
				var videoId = pastedData.match( /video\/([^?]+)/ )[1];
				// create the vimeo URL
				var vimeoUrl = "\n\nhttps://vimeo.com/" + videoId + "\n\n";

				e.preventDefault();

				// replace the clipboard data with the youtube URL
				typeInTextarea( vimeoUrl );
			}

		} );

		$( document ).on( 'click', '.squarecandy-tinymce-alert', function(){
			$(this).fadeOut();
		} );
	}
);
