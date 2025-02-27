jQuery(document).ready(function($) {

	tinymce.create('tinymce.plugins.sqc_embed_plugin', {

		init : function(editor, url) {

			console.log( 'editor init', editor );

			// set a unique id
			const dialogID = editor.id + '-sqc-shortcode-dialog';

			// get our list of shortcodes from js localization
			const buttonSettings = typeof sqcEmbed !== 'undefined' ? sqcEmbed.mceButton : [];
			let radioButtons = '';
			for ( const prop in buttonSettings ) {
				const item = buttonSettings[prop];
				console.log( prop, item );
				if ( typeof item.shortcode !== 'undefined' && typeof item.title !== 'undefined' ) {
					radioButtons +=  '<div><input type="radio" name="sqc-insert-type" value="' + item.shortcode + '"/><label for="' + item.shortcode + '">' + item.title + '</label></div>';
				}
			}
			console.log( radioButtons );

			// create the dialog element, and add it to the page
			const dialogHtml = '<dialog class="sqc-shortcode-dialog" id="' + dialogID + '">' + 
				'<form><div><input type="text" name="sqc-insert" placeholder="Enter Link or Embed code"></div>' + 
				'<div><label>Choose embed type:</label></div><div class="btn-group">' + radioButtons + '</div>' +
				'<div class="btn-group"><button value="cancel" formmethod="dialog">Cancel</button><button class="confirmBtn" value="default">Confirm</button></div>' + 
				'</form></dialog>';
			$('#ed_toolbar').before( $( dialogHtml ) );

			console.log( dialogHtml );

			// add event listeners to catch the dialog close
			const sqcDialog = $("#" + dialogID)[0];			
			const confirmBtn = $( sqcDialog ).find(".confirmBtn")[0];

			console.log( sqcDialog, confirmBtn );

			// "Cancel" button closes the dialog without submitting because of [formmethod="dialog"], triggering a close event.
			// "Confirm" also ends up here bc we're adding the close action to the confirm onclick
			sqcDialog.addEventListener("close", (e) => {
				console.log( dialogID, sqcDialog.returnValue, sqcDialog );
				const insertVal = $( sqcDialog ).find("[name=sqc-insert]").val();
				const radioVal = $( sqcDialog ).find('input[name="sqc-insert-type"]:checked').val();
				console.log( insertVal, radioVal );
				if( sqcDialog.returnValue !== "cancel" && insertVal && radioVal ) {
					tinymce.execCommand('mceInsertContent', false, '[' + radioVal + ' ' + insertVal + ' ]');
				}	
			});

			// Prevent the "confirm" button from the default behavior of submitting the form, and close the dialog with the `close()` method, which triggers the "close" event.
			confirmBtn.addEventListener("click", (event) => {
				event.preventDefault(); // We don't want to submit this fake form
				sqcDialog.close('confirm'); // so we can tell it apart from cancel
			});

			// Register command for when button is clicked
			editor.addCommand('sqc_embed_insert_shortcode', function() {
				// opens the <dialog> modally
				sqcDialog.showModal();
			});

			// Register buttons - trigger above command when clicked
			editor.addButton('sqc_embed_button', {title : 'Insert shortcode', cmd : 'sqc_embed_insert_shortcode', icon: 'dashicon', classes: 'sqc-shortcode' });

		},   
	});

	// Register our TinyMCE plugin
	// first parameter is the button ID1
	// second parameter must match the first parameter of the tinymce.create() function above
	tinymce.PluginManager.add('sqc_embed_button', tinymce.plugins.sqc_embed_plugin);
});


