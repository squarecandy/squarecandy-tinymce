jQuery(document).ready(function($) {

	tinymce.create('tinymce.plugins.sqc_embed_plugin', {

		init : function(editor, url) {

			console.log( 'editor init', editor );

			// set a unique id
			const dialogID = editor.id + '-sqc-shortcode-dialog';

			// get our list of shortcodes from js localization
			const buttonSettings = typeof sqcEmbed !== 'undefined' ? sqcEmbed.mceButton : [];
			let radioButtons = '';
			let dialogNotes = '';
			for ( const prop in buttonSettings ) {
				const item = buttonSettings[prop];
				console.log( prop, item );
				if ( typeof item.shortcode !== 'undefined' && typeof item.title !== 'undefined' ) {
					let buttonData = item.customJS ? ' data-custom="' + item.customJS + '" ' : '';
					buttonData += item.noCode ? ' data-nocode="1" ' : '';
					radioButtons +=  '<div class="sqc-btn sqc-btn-' + item.shortcode + '" data-slug="' + prop + '"><input type="radio" name="sqc-insert-type" value="' + item.shortcode + '" autocomplete="off"' + buttonData + '/><label for="' + item.shortcode + '">' + item.title + '</label></div>';
					const buttonNotes = item.notes !== 'undefined' ? item.notes : '';
					const buttonNotesMore = item.notesMore ? '<div class="show-more button-link">more</div><div class="more">' + item.notesMore + '</div>' : '';
					dialogNotes += '<div class="sqc-btn-notes notes-' + item.shortcode + '">' + buttonNotes + buttonNotesMore + '</div>'
				}

			}
			console.log( radioButtons );

			// create the dialog element, and add it to the page
			const dialogHtml = '<dialog class="sqc-shortcode-dialog" id="' + dialogID + '">' + 
				'<form><div><input type="text" name="sqc-insert" placeholder="Enter Link or Embed code"></div>' + 
				'<p><label>Choose embed type:</label></p><div class="btn-group">' + radioButtons + '</div>' +
				'<div class="note-container">' + dialogNotes + '</div>' +
				'<div class="btn-group btn-group-submit"><button class="button" value="cancel" formmethod="dialog">Cancel</button><button class="confirmBtn button button-primary" value="default">Confirm</button></div>' + 
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
				const $dialogInput = $( sqcDialog ).find("[name=sqc-insert]");
				const insertVal = $dialogInput.val();
				const $selectedRadio = $( sqcDialog ).find('input[name="sqc-insert-type"]:checked');
				const radioVal = $selectedRadio.val();
				console.log( insertVal, radioVal, $selectedRadio );
				if( sqcDialog.returnValue !== "cancel" && insertVal && radioVal ) {
					const customFunction = $selectedRadio.data('custom') ? window[ $selectedRadio.data('custom') ] : false;
					console.log( customFunction, typeof customFunction );
					if ( $selectedRadio.data('nocode') ) {
						console.log('no code');
						tinymce.execCommand('mceInsertContent', false, insertVal );
					} else if ( customFunction && typeof customFunction == 'function' ) {
						console.log('do custom function');
						const newText = customFunction( insertVal );
						tinymce.execCommand('mceInsertContent', false, newText );
					} else {
						tinymce.execCommand('mceInsertContent', false, '[' + radioVal + ' ' + insertVal + ' ]');
					}
					tinymce.execCommand('InsertLineBreak');
					tinymce.execCommand('InsertLineBreak');			
				}
				
				// clear out the input values
				$dialogInput.val('');
				$( sqcDialog ).find('input[name="sqc-insert-type"]').prop( 'checked', false );
				$( sqcDialog ).find( '.sqc-btn-notes' ).removeClass( 'show' );
				$( sqcDialog ).find( '.more' ).hide();
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

			$( '#' + dialogID + ' input[type="radio"]').on( 'change', function(){
				shortcodeName = this.value;
				$allNotes  = $(this).parents('dialog').find( '.sqc-btn-notes' );
				$targetDiv = $(this).parents('dialog').find( '.notes-' + shortcodeName );
				console.log( 'change button', this.checked, shortcodeName, $targetDiv, this );
				$allNotes.removeClass( 'show' );
				$allNotes.find( '.more' ).slideUp();
				if ( this.checked ) {
					$targetDiv.addClass( 'show' );
				}
			});

			$( '#' + dialogID + ' .show-more').on( 'click', function(){
				$thisMore  = $(this).parents('.sqc-btn-notes' ).find('.more');
				if ( $thisMore.is(':visible')) {
					$thisMore.slideUp();
				} else {
					$thisMore.slideDown();
				}
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


