/* eslint-disable no-console, no-unused-vars, eqeqeq */
/* global tinymce, sqcEmbed */
( function( $ ) {
	tinymce.create( 'tinymce.plugins.sqc_embed_plugin', {
		init( editor, url ) {
			// set a unique id
			const dialogID = editor.id + '-sqc-shortcode-dialog';

			// get our list of shortcodes from js localization
			const buttonSettings = typeof sqcEmbed !== 'undefined' ? sqcEmbed.mceButton : [];
			let radioButtons = '';
			let dialogNotes = '';
			const inputRequired = ' required ';
			for ( const prop in buttonSettings ) {
				const item = buttonSettings[ prop ];

				if ( typeof item.shortcode !== 'undefined' && typeof item.title !== 'undefined' ) {
					// set up data on the item
					let buttonData = ' data-slug="' + prop + '"';
					buttonData += item.functionName ? ' data-custom="' + item.functionName + '"' : '';
					buttonData += item.noCode ? ' data-nocode="1"' : '';
					buttonData += item.noInput ? ' data-noinput="1"' : '';
					// add the item to the set of radio buttons
					radioButtons +=
						'<div class="sqc-btn sqc-btn-' +
						item.shortcode +
						'">' +
						'<label>' +
						'<input type="radio" name="sqc-insert-type" value="' +
						item.shortcode +
						'" autocomplete="off" required ' +
						buttonData +
						'/>' +
						item.title +
						'</label>' +
						'</div>';
					// set up notes for the item
					const buttonNotes = item.notes !== 'undefined' ? item.notes : '';
					const buttonNotesMore = item.notesMore
						? '<div class="show-more button-link">more</div><div class="more">' + item.notesMore + '</div>'
						: '';
					// add that to the set of notes
					dialogNotes += '<div class="sqc-btn-notes notes-' + item.shortcode + '">' + buttonNotes + buttonNotesMore + '</div>';
				}
			}

			// create the dialog element, and add it to the page
			const dialogHtml =
				'<dialog class="sqc-shortcode-dialog" id="' +
				dialogID +
				'">' +
				'<form method=dialog><div><input type="text" name="sqc-insert" placeholder="Enter Link or Embed code" required ></div>' +
				'<p><label>Choose embed type:</label></p><div class="btn-group">' +
				radioButtons +
				'</div>' +
				'<div class="note-container">' +
				dialogNotes +
				'</div>' +
				'<div class="btn-group btn-group-submit">' +
				'<button class="button" value="cancel" formmethod="dialog" formnovalidate>Cancel</button>' +
				'<button class="confirmBtn button button-primary" value="default">Confirm</button></div>' +
				'</form></dialog>';

			const editorContainerClass = 'wp-' + editor.id + '-wrap';
			const $editorContainer = $( '#' + editorContainerClass );
			//console.log( 'editorContainerClass', editorContainerClass, $editorContainer );
			$editorContainer.before( $( dialogHtml ) );

			// add event listeners to catch the dialog close
			const sqcDialog = $( '#' + dialogID )[ 0 ];
			const confirmBtn = $( sqcDialog ).find( '.confirmBtn' )[ 0 ];

			// "Cancel" button closes the dialog without submitting because of [formmethod="dialog"], triggering a close event.
			// "Confirm" also ends up here bc we're adding the close action to the confirm onclick
			sqcDialog.addEventListener( 'close', ( e ) => {
				const $dialogInput = $( sqcDialog ).find( '[name=sqc-insert]' );
				const insertVal = $dialogInput.val();
				const $selectedRadio = $( sqcDialog ).find( 'input[name="sqc-insert-type"]:checked' );
				const radioVal = $selectedRadio.val();
				const validValue = insertVal || $selectedRadio.data( 'noinput' );

				// if the confirm button was clicked & we have the values we need
				if ( sqcDialog.returnValue !== 'cancel' && validValue && radioVal ) {
					const customFunction = $selectedRadio.data( 'custom' ) ? window[ $selectedRadio.data( 'custom' ) ] : false;
					if ( $selectedRadio.data( 'nocode' ) ) {
						// nocode items pass through the pasted text
						tinymce.execCommand( 'mceInsertContent', false, insertVal );
					} else if ( customFunction && typeof customFunction === 'function' ) {
						if ( 'replacePastedText' == $selectedRadio.data( 'custom' ) ) {
							// use replacePastedText function
							const newOutput = customFunction( insertVal, $selectedRadio.data( 'slug' ) );
							tinymce.execCommand( 'mceInsertContent', false, newOutput.text );
						} else {
							// do some custom function
							const newText = customFunction( insertVal );
							tinymce.execCommand( 'mceInsertContent', false, newText );
						}
					} else {
						// otherwise wrap the value in the shortcode
						const addSpace = $selectedRadio.data( 'noinput' ) ? '' : ' '; //noinput won't need a space after the shortcode
						tinymce.execCommand( 'mceInsertContent', false, '[' + radioVal + addSpace + insertVal + ']' );
					}
					// @TODO these don't always work?
					tinymce.execCommand( 'InsertLineBreak' );
					tinymce.execCommand( 'InsertLineBreak' );
				}

				// reset the input values
				$dialogInput.val( '' );
				$( sqcDialog )
					.find( 'input[name="sqc-insert-type"]' )
					.prop( 'checked', false );
				$( sqcDialog )
					.find( '.sqc-btn-notes' )
					.removeClass( 'show' );
				$( sqcDialog )
					.find( '.more' )
					.hide();
				$dialogInput.prop( 'required', true );
			} );

			// Prevent the "confirm" button from the default behavior of submitting the form,
			// and close the dialog with the `close()` method, which triggers the "close" event.
			confirmBtn.addEventListener( 'click', ( event ) => {
				event.preventDefault(); // We don't want to submit this fake form
				const $dialogForm = $( sqcDialog ).find( 'form' );
				const isValid = $dialogForm[ 0 ].reportValidity();
				if ( isValid ) {
					sqcDialog.close( 'confirm' ); // so we can tell it apart from cancel
				}
			} );

			// Register command for when button is clicked
			editor.addCommand( 'sqc_embed_insert_shortcode', function() {
				// opens the <dialog> modally
				sqcDialog.showModal();
			} );

			$( '#' + dialogID + ' input[type="radio"]' ).on( 'change', function() {
				const shortcodeName = this.value;
				const $allNotes = $( this )
					.parents( 'dialog' )
					.find( '.sqc-btn-notes' );
				const $targetDiv = $( this )
					.parents( 'dialog' )
					.find( '.notes-' + shortcodeName );
				const $dialogInput = $( this )
					.parents( 'dialog' )
					.find( 'input[name="sqc-insert"]' );

				// unshow all notes & more notes
				$allNotes.removeClass( 'show' );
				$allNotes.find( '.more' ).slideUp();
				// show this note
				if ( this.checked ) {
					$targetDiv.addClass( 'show' );
				}

				if ( this.dataset.noinput ) {
					$dialogInput.prop( 'required', false );
				} else {
					$dialogInput.prop( 'required', true );
				}
			} );

			$( '#' + dialogID + ' .show-more' ).on( 'click', function() {
				const $thisMore = $( this )
					.parents( '.sqc-btn-notes' )
					.find( '.more' );
				if ( $thisMore.is( ':visible' ) ) {
					$thisMore.slideUp();
				} else {
					$thisMore.slideDown();
				}
			} );

			//TODO button not always getting added when multiple editors - does it need a unique id etc?
			// Register buttons - trigger above command when clicked
			editor.addButton( 'sqc_embed_button', {
				title: 'Insert shortcode',
				cmd: 'sqc_embed_insert_shortcode',
				icon: 'dashicon',
				classes: 'sqc-shortcode',
			} );
		},
	} );

	// Register our TinyMCE plugin
	// first parameter is the button ID1
	// second parameter must match the first parameter of the tinymce.create() function above
	tinymce.PluginManager.add( 'sqc_embed_button', tinymce.plugins.sqc_embed_plugin );
} )( jQuery );
