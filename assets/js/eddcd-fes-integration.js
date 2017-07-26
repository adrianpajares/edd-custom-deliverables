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
			});
		},

		move : function() {

			$(".eddcd_repeatable_table .eddcd-repeatables-wrap").sortable({
				handle: '.eddcd-draghandle-anchor', items: '.eddcd_repeatable_row', opacity: 0.6, cursor: 'move', axis: 'y', update: function() {
					var count  = 0;
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
					count = row.parent().find( '.eddcd_repeatable_row' ).length,
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

				if ( count > 1 ) {
					$( 'input, select', row ).val( '' );
					row.fadeOut( 'fast' ).remove();
					firstFocusable.focus();
					$( '#edd-custom-deliverables-files-' + download_id + '-' + price_id + ' .eddcd_repeatable_upload_wrapper[data-key="' + row.data('key') + '"]' ).remove();
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


			});
		},

		files: function (e) {

			$( document.body ).on('click', '.eddcd_upload_file_button', function(e) {

				var self = $(this),
					downloadable_frame;

				if (downloadable_frame) {
					downloadable_frame.open();
					return;
				}

				downloadable_frame = wp.media({
					title: fes_form.file_title,
					button: {
						text: fes_form.file_button
					},
					multiple: false
				});

				downloadable_frame.on('open',function() {

					// turn on file filter
					var fid   = self.closest('.eddcd-repeatable-row-standard-fields').find('input.eddcd_repeatable_attachment_id_field').attr("data-formid");
					var fname = self.closest('.eddcd-repeatable-row-standard-fields').find('input.eddcd_repeatable_attachment_id_field').attr("data-fieldname");

					$.post(fes_form.ajaxurl,{ action:'fes_turn_on_file_filter', formid: fid, name: fname }, function (res) { });
				});

				downloadable_frame.on('close',function() {
					// turn on file filter
					var fid   = self.closest('.eddcd-repeatable-row-standard-fields').find('input.eddcd_repeatable_attachment_id_field').attr("data-formid");
					var fname = self.closest('.eddcd-repeatable-row-standard-fields').find('input.eddcd_repeatable_attachment_id_field').attr("data-fieldname");
					$.post(fes_form.ajaxurl,{ action:'fes_turn_off_file_filter', formid: fid, name: fname }, function (res) { });
				});

				downloadable_frame.on('select', function () {

					var selection = downloadable_frame.state().get('selection');

					selection.map(function (attachment) {
						attachment = attachment.toJSON();

						self.closest('.eddcd-repeatable-row-standard-fields').find('input.eddcd_repeatable_attachment_id_field').val(attachment.id);
						self.closest('.eddcd-repeatable-row-standard-fields').find('input.eddcd_repeatable_name_field').val(attachment.title);
						self.closest('.eddcd-repeatable-row-standard-fields').find('input.eddcd_repeatable_upload_field').val(attachment.url);
					});
				});

				downloadable_frame.open();

				// We also want to remove the option to notify the customer now that the files have been modified. They must save the payment first.
				$( '.edd-custom-deliverables-send-email-wrapper' ).html( edd_custom_deliverables_fes_vars.save_payment_text );

			});
		},

	};

	EDD_Custom_Deliverables_Configuration.init();

	/**
	 * Send notification email to customer via ajax upon click
	 */
	$( document ).on( 'click', '#edd-custom-deliverables-email-customer', function( event ) {

		$( '.edd-custom-deliverables-send-email-wrapper .spinner' ).css( 'visibility', 'visible' );
		$( '#edd-custom-deliverables-email-customer' ).css( 'display', 'none' );

		var body;

		// Get the body contents
		if ( $( "#wp-edd-custom-deliverables-email-body-wrap" ).hasClass( "tmce-active" ) ) {
	        body = tinyMCE.activeEditor.getContent();
	    } else {
	        body = jQuery('#edd-custom-deliverables-email-body').val();
	    }

		// Send the email via ajax
	 	$.ajax( {
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
	 			if ( ! response.success ) {
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
	 	}).fail( function ( response ) {
	 		if ( window.console && window.console.log ) {
	 			console.log( response );
	 		}
	 	});
	});

});
