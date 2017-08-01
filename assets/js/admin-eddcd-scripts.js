jQuery(document).ready(function ($) {

	/**
	 * Download Configuration Metabox
	 */
	var EDD_Custom_Deliverables_Configuration = {
		init : function() {
			this.add();
			this.move();
			this.remove();
			this.files();
		},
		clone_repeatable : function(row) {

			// Retrieve the highest current key
			var key = highest = 1;
			row.parent().find( '.eddcd_repeatable_row' ).each(function() {
				var current = $(this).data( 'key' );
				if( parseInt( current ) > highest ) {
					highest = current;
				}
			});
			key = highest += 1;

			clone = row.clone();

			/** manually update any select box values */
			clone.find( 'select' ).each(function() {
				$( this ).val( row.find( 'select[name="' + $( this ).attr( 'name' ) + '"]' ).val() );
			});

			clone.removeClass( 'edd_add_blank' );

			clone.attr( 'data-key', key );
			clone.find( 'input, select, textarea' ).val( '' ).each(function() {
				var name = $( this ).attr( 'name' );
				var id   = $( this ).attr( 'id' );

				if( name ) {

					name = name.replace( /\[(.*?)\]\[(.*?)\]\[(.*?)\]/, function (match, product_id, price_id, file_id ) {
					    return '[' + product_id + '][' + price_id + '][' + parseInt( key ) + ']';
					});

					$( this ).attr( 'name', name );

				}

				$( this ).attr( 'data-key', key );

				if( typeof id != 'undefined' ) {

					id = id.replace( /(\d+)/, parseInt( key ) );
					$( this ).attr( 'id', id );

				}

			});

			clone.find( 'span.eddcd_price_id' ).each(function() {
				$( this ).text( parseInt( key ) );
			});

			clone.find( 'span.eddcd_file_id' ).each(function() {
				$( this ).text( parseInt( key ) );
			});

			clone.find( '.eddcd_repeatable_default_input' ).each( function() {
				$( this ).val( parseInt( key ) ).removeAttr('checked');
			});

			clone.find( '.eddcd_repeatable_condition_field' ).each ( function() {
				$( this ).find( 'option:eq(0)' ).prop( 'selected', 'selected' );
			});

			// Remove Chosen elements
			clone.find( '.search-choice' ).remove();
			clone.find( '.chosen-container' ).remove();

			return clone;
		},

		add : function() {
			$( document.body ).on( 'click', '.submit .eddcd_add_repeatable', function(e) {

				e.preventDefault();
				var button = $( this ),
				    download_id = $( this ).attr( 'download-id' );
				    price_id = $( this ).attr( 'price-id' );
				row = button.parent().parent().prev( '.eddcd_repeatable_row' ),
				clone = EDD_Custom_Deliverables_Configuration.clone_repeatable( row );

				clone.insertAfter( row ).find('input, textarea, select').filter(':visible').eq(0).focus();

				// Setup chosen fields again if they exist
				clone.find('.edd-select-chosen').chosen({
					inherit_select_classes: true,
					placeholder_text_single: edd_vars.one_option,
					placeholder_text_multiple: edd_vars.one_or_more_option,
				});
				clone.find( '.edd-select-chosen' ).css( 'width', '100%' );
				clone.find( '.edd-select-chosen .chosen-search input' ).attr( 'placeholder', edd_vars.search_placeholder );
			});
		},

		move : function() {

			$(".eddcd_repeatable_table .eddcd-repeatables-wrap").sortable({
				handle: '.eddcd-draghandle-anchor', items: '.eddcd_repeatable_row', opacity: 0.6, cursor: 'move', axis: 'y', update: function() {
					var count  = 0;
					console.log( 'heyo' );
					$(this).find( '.eddcd_repeatable_row' ).each(function() {
						$(this).find( 'input.eddcd_repeatable_index' ).each(function() {
							$( this ).val( count );
						});
						count++;
					});
				}
			});

		},

		remove : function() {
			$( document.body ).on( 'click', '.eddcd-remove-row', function(e) {
				e.preventDefault();

				var row   = $(this).parents( '.eddcd_repeatable_row' ),
					count = row.parent().find( '.eddcd_repeatable_row' ).length - 1,
					type  = $(this).data('type'),
					repeatable = 'div.eddcd_repeatable_' + type + 's',
					focusElement,
					focusable,
					firstFocusable;

				// Set focus on next element if removing the first row. Otherwise set focus on previous element.
				if ( $(this).is( '.ui-sortable .eddcd_repeatable_row:first-child .edd-remove-row' ) ) {
					focusElement  = row.next( '.eddcd_repeatable_row' );
				} else {
					focusElement  = row.prev( '.eddcd_repeatable_row' );
				}

				focusable  = focusElement.find( 'select, input, textarea, button' ).filter( ':visible' );
				firstFocusable = focusable.eq(0);

				var download_id = $( this ).attr( 'download-id' );
				var price_id = $( this ).attr( 'price-id' );

				$( '#edd-custom-deliverables-files-' + download_id + '-' + price_id + ' .eddcd_repeatable_upload_wrapper[data-key="' + row.data('key') + '"]' ).remove();


				if ( count > 1 ) {
					$( 'input, select', row ).val( '' );
					row.fadeOut( 'fast' ).remove();
					firstFocusable.focus();
				} else {
					switch( type ) {
						case 'price' :
							alert( edd_vars.one_price_min );
							break;
						case 'file' :
							$( 'input, select', row ).val( '' );
							break;
						default:
							alert( edd_vars.one_field_min );
							break;
					}
				}

				/* re-index after deleting */
				$(repeatable).each( function( rowIndex ) {
					$(this).find( 'input, select' ).each(function() {
						var name = $( this ).attr( 'name' );
						//name = name.replace( /\[(\d+)\]/, '[' + rowIndex+ ']');

						name = name.replace( /\[(.*?)\]\[(.*?)\]\[(.*?)\]/, function (match, product_id, price_id, file_id ) {
							console.log( match );
							return '[' + product_id + '][' + price_id + '][' + rowIndex + ']';
						});

						$( this ).attr( 'name', name ).attr( 'id', name );

					});
				});
			});
		},

		files : function() {
			var file_frame;
			window.formfield = '';

			$( document.body ).on('click', '.eddcd_upload_file_button', function(e) {

				e.preventDefault();

				var button = $(this);

				window.formfield = $(this).closest('.eddcd_repeatable_upload_wrapper');

				// Turn on the file upload filter for edd_cd
				$.post(ajaxurl,{ action:'edd_cd_turn_on_file_filter' }, function (res) { });

				// If the media frame already exists, reopen it.
				if ( file_frame ) {
					//file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
					file_frame.open();
					return;
				}

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media( {
					frame: 'post',
					state: 'insert',
					title: button.data( 'uploader-title' ),
					button: {
						text: button.data( 'uploader-button-text' )
					},
					multiple: $( this ).data( 'multiple' ) == '0' ? false : true  // Set to true to allow multiple files to be selected
				} );

				file_frame.on( 'menu:render:default', function( view ) {
					// Store our views in an object.
					var views = {};

					// Unset default menu items
					view.unset( 'library-separator' );
					view.unset( 'gallery' );
					view.unset( 'featured-image' );
					view.unset( 'embed' );

					// Initialize the views in our view object.
					view.set( views );
				} );

				// When an image is selected, run a callback.
				file_frame.on( 'insert', function() {

					var selection = file_frame.state().get('selection');
					selection.each( function( attachment, index ) {
						attachment = attachment.toJSON();

						var selectedSize = 'image' === attachment.type ? $('.attachment-display-settings .size option:selected').val() : false;
						var selectedURL  = attachment.url;
						var selectedName = attachment.title.length > 0 ? attachment.title : attachment.filename;

						if ( selectedSize && typeof attachment.sizes[selectedSize] != "undefined" ) {
							selectedURL = attachment.sizes[selectedSize].url;
						}

						if ( 'image' === attachment.type ) {
							if ( selectedSize && typeof attachment.sizes[selectedSize] != "undefined" ) {
								selectedName = selectedName + '-' + attachment.sizes[selectedSize].width + 'x' + attachment.sizes[selectedSize].height;
							} else {
								selectedName = selectedName + '-' + attachment.width + 'x' + attachment.height;
							}
						}

						if ( 0 === index ) {
							// place first attachment in field
							window.formfield.find( '.eddcd_repeatable_attachment_id_field' ).val( attachment.id );
							window.formfield.find( '.eddcd_repeatable_thumbnail_size_field').val( selectedSize );
							window.formfield.find( '.eddcd_repeatable_upload_field' ).val( selectedURL );
							window.formfield.find( '.eddcd_repeatable_name_field' ).val( selectedName );
						} else {
							// Create a new row for all additional attachments
							var row = window.formfield,
								clone = EDD_Custom_Deliverables_Configuration.clone_repeatable( row );

							clone.find( '.eddcd_repeatable_attachment_id_field' ).val( attachment.id );
							clone.find( 'edd_repeatable_thumbnail_size_field' ).val( selectedSize );
							clone.find( '.eddcd_repeatable_upload_field' ).val( selectedURL );
							clone.find( '.eddcd_repeatable_name_field' ).val( selectedName );
							clone.insertAfter( row );
						}
					});

					// Turn off the file upload filter for edd_cd
					$.post(ajaxurl,{ action:'edd_cd_turn_off_file_filter' }, function (res) { });
				});

				// Finally, open the modal
				file_frame.open();

				// We also want to remove the option to notify the customer now that the files have been modified. They must save the payment first.
				$( '.edd-custom-deliverables-send-email-wrapper' ).html( edd_custom_deliverables_vars.save_payment_text );
			});


			var file_frame;
			window.formfield = '';

		}

	};

	EDD_Custom_Deliverables_Configuration.init();

	/**
	 * Send notification email to customer via ajax upon click
	 */
	$( document ).on( 'click', '#edd-custom-deliverables-email-customer', function( event ){

		event.preventDefault();

		$( '.edd-custom-deliverables-send-email-wrapper .spinner' ).css( 'visibility', 'visible' );
		$( '#edd-custom-deliverables-email-customer' ).css( 'display', 'none' );

		var body;

		// Get the body contents
		if ( $( "#wp-edd-custom-deliverables-email-body-wrap" ).hasClass( "tmce-active" )){
	        body = tinyMCE.activeEditor.getContent();
	    }else{
	        body = jQuery('#edd-custom-deliverables-email-body').val();
	    }

		// Send the email via ajax
	 	$.ajax({
	 		type: 'POST',
	 		url: ajaxurl,
	 		data: {
	 			nonce: $( '#edd-custom-deliverables-send-email' ).val(),
	 			subject: $( '#edd-custom-deliverables-subject' ).val(),
	 			body: body,
				payment_id: $( '#edd-custom-deliverables-payment-id' ).val(),
	 			action: 'edd_custom_deliverables_send_email_ajax',
	 		},
	 		dataType: "json",
	 		success: function( response ) {

				// If the email was not sent
	 			if ( ! response.success ){
	 				console.log( response );
	 			}else{

					// Hide the spinner again
					$( '.edd-custom-deliverables-send-email-wrapper .spinner' ).css( 'visibility', 'hidden' );

					// Show the successfully sent message
					$( '#edd-custom-deliverables-email-customer' ).after( '<span id="edd-custom-deliverables-email-successful-message">' + response.success_message + '</span>' );

					// Give the customer 2 seconds to read the "Email successful" message
					setTimeout( function() {
						// Refresh the page
						location.reload();
					}, 2000 );
	 			}

	 		}
	 	}).fail(function (response) {
	 		if ( window.console && window.console.log ) {
	 			console.log( response );
	 		}
	 	});
	});

	/**
	 * Mark job as fulfilled upon click
	 */
	$( document ).on( 'click', '.eddcd-fulfill-order-btn', function( event ){

		event.preventDefault();

		fulfilled_box = $( this ).parent();

		// Hide the Fulfill button
		fulfilled_box.children().hide();

		// Show the spinner
		fulfilled_box.next().css( 'visibility', 'visible' ).css( 'display', 'inline-block' );

		download_id = $(this).attr( 'download-id' );
		price_id = $(this).attr( 'price-id' );

		// Mark the job as fulfilled via ajax
		$.ajax({
	 		type: 'POST',
	 		url: ajaxurl,
	 		data: {
	 			nonce: $( '#edd-custom-deliverables-mark-as-fulfilled' ).val(),
	 			download_id: download_id,
	 			price_id: price_id,
				payment_id: $( '#edd-custom-deliverables-payment-id' ).val(),
	 			action: 'edd_custom_deliverables_mark_as_fulfilled',
	 		},
	 		dataType: "json",
	 		success: function( response ) {

				// If the job was not able to be marked as fulfilled
	 			if ( ! response.success ){
	 				console.log( response );
	 			}else{

					// Hide the spinner again
					fulfilled_box.next().css( 'visibility', 'hidden' ).css( 'display', 'none' );

					fulfilled_box.html( response.success_message );

	 			}

	 		}
	 	}).fail(function (response) {
	 		if ( window.console && window.console.log ) {
	 			console.log( response );
	 		}
	 	});
	});

	/**
	 * Mark job as not fulfilled upon click
	 */
	$( document ).on( 'click', '.eddcd-mark-not-fulfilled', function( event ){

		event.preventDefault();

		fulfilled_box = $( this ).parent().parent();

		// Hide the previous Fullfilled By message
		fulfilled_box.children().hide();

		// Show the spinner
		fulfilled_box.next().css( 'visibility', 'visible' ).css( 'display', 'inline-block' );

		download_id = $(this).attr( 'download-id' );
		price_id = $(this).attr( 'price-id' );

		// Mark the job as unfulfilled via ajax
		$.ajax({
	 		type: 'POST',
	 		url: ajaxurl,
	 		data: {
	 			nonce: $( '#edd-custom-deliverables-mark-as-not-fulfilled' ).val(),
	 			download_id: download_id,
	 			price_id: price_id,
				payment_id: $( '#edd-custom-deliverables-payment-id' ).val(),
	 			action: 'edd_custom_deliverables_mark_as_not_fulfilled',
	 		},
	 		dataType: "json",
	 		success: function( response ) {

				// If the job was not able to be marked as fulfilled
	 			if ( ! response.success ){
	 				console.log( response );
	 			}else{

					// Hide the spinner again
					fulfilled_box.next().css( 'visibility', 'hidden' ).css( 'display', 'none' );

					fulfilled_box.html( response.success_message );

	 			}

	 		}
	 	}).fail(function (response) {
	 		if ( window.console && window.console.log ) {
	 			console.log( response );
	 		}
	 	});
	});

});
