function typeInTextarea(newText, el = document.activeElement) {
	const [start, end] = [el.selectionStart, el.selectionEnd];
	el.setRangeText(newText, start, end, 'select');
}

// detect iframes in content and replace with embed links/shortcodes where appropriate
function replacePastedText( pastedData, checkIndex = false ) {

	// read info on what to look for from localized data:
	const globalChecks = typeof sqcEmbed !== 'undefined' ? sqcEmbed.pasteIntercept : [];
	// if we're passing in a particular item to search for via checkIndex, replace the full array with an arry with just that item's info
	const interceptChecks = checkIndex ? { [checkIndex]: globalChecks[checkIndex] } : globalChecks;

	console.log( 'pasteIntercept', pastedData );
	console.log( 'globalChecks', globalChecks, 'checkIndex', checkIndex );
	console.log( 'interceptChecks', interceptChecks );

	for ( const prop in interceptChecks ) {

		const check = interceptChecks[prop];
		const findTag = check.checkTag;
		const hasTag = findTag ? pastedData.includes( '<' + findTag ) || pastedData.includes( '&lt;' + findTag ) : true; //'&lt;iframe' when in visual editor context / pasted
	
		const matchesCheck = pastedData.includes( check.checkText );

		console.log( 'check', check );
		console.log( check.checkText, hasTag, matchesCheck );

		if ( hasTag && matchesCheck ) {
			console.log( 'found!' );

			// check if there/s a coustom function to handle this type:
			const customFunction = window[prop + 'Process'];
			console.log( 'customFunction ' + prop + 'Process', customFunction, typeof customFunction, typeof customFunction === 'function' );
			let output = '';

			if ( check.replaceRegex ) {
				// use regex to get the needed part of the pasted text
				// and set up the string for the embed link

				const re = new RegExp( check.replaceRegex );
				const matches = pastedData.match( re );

				console.log( 'matches', check.replaceRegex, re, matches );

				// get the youtube video id from the iframe
				if ( matches ) {
					output = matches[1];
				}
			} else {
				output = pastedData;
			}
			console.log( 'output', output );
			if ( typeof customFunction === 'function' ) {
				output = customFunction( output );
			}

			output = "\n" + check.replacePre + output + check.replacePost + "\n\n"; // line breaks not working?	

			if ( output ) {
				return { text: output, message: check.message };
			}
		}
	}

	// fallback - just return pasteddate
	return pastedData;
}

function displayInterceptMessage( element, message ) {

	messageDiv = '<div class="squarecandy-tinymce-alert message error">' +	message + '</div>';
	jQuery(element).append( messageDiv );

	// clear the alert after 20 seconds
	setTimeout(function(){
		jQuery( '.squarecandy-tinymce-alert' ).fadeOut();
	}, 20 * 1000);

}


jQuery( document ).ready(
	function($){

		// anytime the user pastes into the HTML view check if the value has a youtube iframe.
		$( document ).on( 'paste', 'textarea.wp-editor-area', function(e){
			console.log('pasted');
			// access the clipboard using the api
			const pastedData = e.originalEvent.clipboardData.getData('text');
			const output = replacePastedText( pastedData );
			if ( output != pastedData ) {
				e.preventDefault();

				const messageTarget = $(this).parent().find('.quicktags-toolbar');

				displayInterceptMessage( messageTarget, output.message );

				// replace the clipboard data with the youtube URL
				typeInTextarea( output.text );
			}
		} );

		$( document ).on( 'click', '.squarecandy-tinymce-alert', function(){
			$(this).fadeOut();
		} );
	}
);
