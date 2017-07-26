<?php
/**
 * Misc Functions
 *
 * @package     EDD\EDDCustomDeliverables\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Here we will modify the callback functions that EDD email template tags use so that we can add the custom deliverable files.
 *
 * @since  1.0.0
 * @param  array $email_tags The array of callback functions that will be called by the email tags
 * @return array $email_tags The modified array so that we can define our own call back functions
 */
function edd_custom_deliverables_modify_email_callbacks( $email_tags ){

	// Loop counter
	$loop_counter = 0;

	// Loop through each email tag
	foreach( $email_tags as $email_tag ){

		// If we are looking at the download_list email tag
		if ( 'download_list' == $email_tag['tag'] ){

			// Modify its callback function
			$email_tags[$loop_counter]['function'] = 'text/html' == EDD()->emails->get_content_type() ? 'edd_custom_deliverables_email_tag_download_list' : 'edd_custom_deliverables_email_tag_download_list';

		}elseif( 'file_urls' == $email_tag['tag']  ){

			// Modify its callback function
			$email_tags[$loop_counter]['function'] = 'edd_custom_deliverables_email_tag_file_urls';

		}

		$loop_counter = $loop_counter + 1;
	}

	return $email_tags;
}
add_filter( 'edd_email_tags', 'edd_custom_deliverables_modify_email_callbacks', 10, 1 );

/**
 * This is our custom callback function for the EDD email template tag: download_list
 * We'll output the correct list of download links (based on the custom deliverables settings for this payment)
 *
 * @param int $payment_id
 *
 * @return string download_list
 */
function edd_custom_deliverables_email_tag_download_list( $payment_id ) {

	$payment = new EDD_Payment( $payment_id );

	$payment_data  = $payment->get_meta();
	$download_list = '<ul>';
	$cart_items    = $payment->cart_details;
	$email         = $payment->email;

	if ( $cart_items ) {
		$show_names = apply_filters( 'edd_email_show_names', true );
		$show_links = apply_filters( 'edd_email_show_links', true );

		foreach ( $cart_items as $item ) {

			if ( edd_use_skus() ) {
				$sku = edd_get_download_sku( $item['id'] );
			}

			if ( edd_item_quantities_enabled() ) {
				$quantity = $item['quantity'];
			}

			$price_id = edd_get_cart_item_price_id( $item );
			if ( $show_names ) {

				$title = '<strong>' . get_the_title( $item['id'] ) . '</strong>';

				if ( ! empty( $quantity ) && $quantity > 1 ) {
					$title .= "&nbsp;&ndash;&nbsp;" . __( 'Quantity', 'easy-digital-downloads' ) . ': ' . $quantity;
				}

				if ( ! empty( $sku ) ) {
					$title .= "&nbsp;&ndash;&nbsp;" . __( 'SKU', 'easy-digital-downloads' ) . ': ' . $sku;
				}

				if( ! empty( $price_id ) && 0 !== $price_id ){
					$title .= "&nbsp;&ndash;&nbsp;" . edd_get_price_option_name( $item['id'], $price_id, $payment_id );
				}

				$download_list .= '<li>' . apply_filters( 'edd_email_receipt_download_title', $title, $item, $price_id, $payment_id ) . '<br/>';
			}

			$default_download_files = edd_get_download_files(  $item['id'], $price_id );
			$files = edd_custom_deliverables_get_download_files( $payment, $item['id'], $price_id, $default_download_files );

			if ( ! empty( $files ) ) {

				foreach ( $files as $filekey => $file ) {

					if ( $show_links ) {
						$download_list .= '<div>';
							$file_url = edd_get_download_file_url( $payment_data['key'], $email, $filekey, $item['id'], $price_id );
							$download_list .= '<a href="' . esc_url_raw( $file_url ) . '">' . edd_get_file_name( $file ) . '</a>';
							$download_list .= '</div>';
					} else {
						$download_list .= '<div>';
							$download_list .= edd_get_file_name( $file );
						$download_list .= '</div>';
					}

				}

			} elseif ( edd_is_bundled_product( $item['id'] ) ) {

				$bundled_products = apply_filters( 'edd_email_tag_bundled_products', edd_get_bundled_products( $item['id'] ), $item, $payment_id, 'download_list' );

				foreach ( $bundled_products as $bundle_item ) {

					$download_list .= '<div class="edd_bundled_product"><strong>' . get_the_title( $bundle_item ) . '</strong></div>';

					$files = edd_custom_deliverables_get_download_files( $payment, $bundle_item, $price_id, $default_download_files );

					foreach ( $files as $filekey => $file ) {
						if ( $show_links ) {
							$download_list .= '<div>';
							$file_url = edd_get_download_file_url( $payment_data['key'], $email, $filekey, $bundle_item, $price_id );
							$download_list .= '<a href="' . esc_url( $file_url ) . '">' . edd_get_file_name( $file ) . '</a>';
							$download_list .= '</div>';
						} else {
							$download_list .= '<div>';
							$download_list .= edd_get_file_name( $file );
							$download_list .= '</div>';
						}
					}
				}
			}else{

				$no_downloads_message = apply_filters( 'edd_receipt_no_files_found_text', __( 'No downloadable files found.', 'easy-digital-downloads' ), $item['id'] );
				$no_downloads_message = apply_filters( 'edd_email_receipt_no_downloads_message', $no_downloads_message, $item['id'], $price_id, $payment_id );

				if ( ! empty( $no_downloads_message ) ){
					$download_list .= '<div>';
						$download_list .= $no_downloads_message;
					$download_list .= '</div>';
				}
			}


			if ( '' != edd_get_product_notes( $item['id'] ) ) {
				$download_list .= ' &mdash; <small>' . edd_get_product_notes( $item['id'] ) . '</small>';
			}


			if ( $show_names ) {
				$download_list .= '</li>';
			}
		}
	}
	$download_list .= '</ul>';

	return $download_list;
}

/**
 * This is our custom callback function for the EDD email template tag: file_urls
 * We'll output the correct list of file url download links (based on the custom deliverables settings for this payment)
 *
 * @param int $payment_id
 *
 * @return string $file_urls
 */
function edd_custom_deliverables_email_tag_file_urls( $payment_id ) {

	$payment = new EDD_Payment( $payment_id );

	$payment_data = $payment->get_meta();
	$file_urls    = '';
	$cart_items   = $payment->cart_details;
	$email        = $payment->email;

	foreach ( $cart_items as $item ) {

		$price_id = edd_get_cart_item_price_id( $item );
		$default_download_files = edd_get_download_files(  $item['id'], $price_id );
		$files    = edd_custom_deliverables_get_download_files( $payment, $item['id'], $price_id, $default_download_files );

		if ( $files ) {
			foreach ( $files as $filekey => $file ) {
				$file_url = edd_get_download_file_url( $payment_data['key'], $email, $filekey, $item['id'], $price_id );

				$file_urls .= esc_html( $file_url ) . '<br/>';
			}
		}
		elseif ( edd_is_bundled_product( $item['id'] ) ) {

			$bundled_products = apply_filters( 'edd_email_tag_bundled_products', edd_get_bundled_products( $item['id'] ), $item, $payment_id, 'file_urls' );

			foreach ( $bundled_products as $bundle_item ) {

				$files = edd_custom_deliverables_get_download_files( $payment, $bundle_item, $price_id, $default_download_files );
				foreach ( $files as $filekey => $file ) {
					$file_url = edd_get_download_file_url( $payment_data['key'], $email, $filekey, $bundle_item, $price_id );
					$file_urls .= esc_html( $file_url ) . '<br/>';
				}

			}
		}

	}

	return $file_urls;
}
