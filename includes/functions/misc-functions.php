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
 * Filter the download_files array so that it shows the custom deliverables instead of/alongside the files attached to the product.
 *
 * @since 1.0.0
 * @param $download_files The array of files the customer gets to download
 * @param $download_id The ID of the product for which we are fetching files
 * @param $price_id The Price ID of the product for which we are fetching files
 *
 * @return $download_files
 */
function edd_custom_deliverables_apply_custom_deliverables( $download_files, $download_id, $price_id = 0 ){

	global $edd_receipt_args, $edd_custom_deliverable_ajax_email_payment_id;

	// If a global payment id has been set, we're probably looking at the purchase confirmation screen
	if ( isset( $_GET['payment_key'] ) ) {

		// Get the payment using the payment key
		$payment_id = edd_get_purchase_id_by_key( urldecode( $_GET['payment_key'] ) );
		$payment = new EDD_Payment( $payment_id );

	} elseif ( isset( $edd_receipt_args['id'] ) ){

		// Get the payment using the global payment id
		$payment = new EDD_Payment( $edd_receipt_args['id'] );

	} elseif ( isset( $_GET['eddfile'] ) && isset( $_GET['token'] )  ) {

		// If we are doing a tokenized EDD download, get the payment information from the URL via edd_process_signed_download_url()
		if( ! isset( $_GET['download_id'] ) && isset( $_GET['download'] ) ) {
			$_GET['download_id'] = $_GET['download'];
		}

		$args = apply_filters( 'edd_process_download_args', array(
			'download' => ( isset( $_GET['download_id'] ) )  ? (int) $_GET['download_id']                       : '',
			'email'    => ( isset( $_GET['email'] ) )        ? rawurldecode( $_GET['email'] )                   : '',
			'expire'   => ( isset( $_GET['expire'] ) )       ? rawurldecode( $_GET['expire'] )                  : '',
			'file_key' => ( isset( $_GET['file'] ) )         ? (int) $_GET['file']                              : '',
			'price_id' => ( isset( $_GET['price_id'] ) )     ? (int) $_GET['price_id']                          : false,
			'key'      => ( isset( $_GET['download_key'] ) ) ? $_GET['download_key']                            : '',
			'eddfile'  => ( isset( $_GET['eddfile'] ) )      ? $_GET['eddfile']                                 : '',
			'ttl'      => ( isset( $_GET['ttl'] ) )          ? $_GET['ttl']                                     : '',
			'token'    => ( isset( $_GET['token'] ) )        ? $_GET['token']                                   : ''
		) );

		// Validate a signed URL that edd_process_signed_download_urlcontains a token
		$args = edd_process_signed_download_url( $args );

		if ( $args['payment'] ) {

			$payment = new EDD_Payment( $args['payment'] );

		}
	} elseif ( isset( $_GET['order_id'] ) ){

		// If we are looking at the order_id screen in FES, don't filter the results.
		return $download_files;

	} elseif ( $edd_custom_deliverable_ajax_email_payment_id ){

		// If we are sending an email via ajax, get the payment ID from our global variable
		$payment = new EDD_Payment( $edd_custom_deliverable_ajax_email_payment_id );

	}else {
		return $download_files;
	}

	// If we still don't have a payment here, don't make any changes
	if ( ! isset( $payment ) || empty( $payment ) ){
		return $download_files;
	}

	$return_files = edd_custom_deliverables_get_download_files( $payment, $download_id, $price_id, $download_files );

	return $return_files;
}
add_filter( 'edd_download_files', 'edd_custom_deliverables_apply_custom_deliverables', 10, 3 );

/**
 * For the EDD download URL that is created for a file, here we will modify the file key if the download is a custom deliverable.
 * This is so that the download log logs the file id with the prefix eddcd_ instead of just the file key.
 * For example, say a customer downloads the second file, normally edd will log that as the second file attached to the download itself.
 * We need it to log the second file attached to the custom deliverables in the payment instead.
 *
 * @since    1.0.0
 * @param    array $args
 * @return   array $args
 */
function edd_custom_deliverables_modify_file_key_for_downloads( $args, $payment_id, $params ){

	// If the file key does not contain eddcd_, it is not a file from EDD Custom Deliverables so make no changes.
	if ( strpos( $params['file'], 'eddcd_' ) === false ){
		return $args;
	}

	// Break it apart so we can sanitize it as an int
	$parts = explode( 'eddcd_', $params['file'] );

	// Sanitize and store it
	$sanitized_file_key = 'eddcd_' . (int) $parts[1];

	// The edd_get_download_file_url function sanitizes the file key to be an int but we need it to be a string so it has the prefix.
	$args['eddfile'] = rawurlencode( sprintf( '%d:%d:%s:%d', $payment_id, $params['download_id'], $sanitized_file_key, $params['price_id'] ) );

	return $args;

}
add_filter( 'edd_get_download_file_url_args', 'edd_custom_deliverables_modify_file_key_for_downloads', 10, 3 );

/**
 * After a Custom Deliverable file has been downloaded, modify the log meta so that the file key is prefixed with eddcd_
 *
 * @since    1.0.0
 * @return   array $download_args The args being set in the edd_process_download function.
 */
function edd_custom_deliverables_add_download_id_to_file_log( $requested_file, $download, $email, $payment ){

	global $edd_logs;

	// If this was not a log for a custom deliverable file, make no changes and leave.
	if( ! isset( $_GET['file'] ) || empty( $_GET['file'] ) || strpos( $_GET['file'], 'eddcd_' ) === false ){
		return false;
	}

	// Break it apart so we can sanitize it as an int
	$parts = explode( 'eddcd_', $_GET['file'] );

	// Sanitize and store it
	$sanitized_file_key = 'eddcd_' . (int) $parts[1];

	// Get the ID of our newly added download log
	$log_id = get_posts("post_type=edd_log&numberposts=1&fields=ids");

	// Update the log meta file ID to be prefixed with eddcd_. This gets us around the (int) santization in edd_record_download_in_log.
	update_post_meta( $log_id[0], '_edd_log_file_id', $sanitized_file_key );

}
add_action( 'edd_process_download_headers', 'edd_custom_deliverables_add_download_id_to_file_log', 10, 4 );

/**
 * When we are viewing file download logs, we need to add a workaround so that our custom files are included in the file download list.
 *
 * @since    1.0.0
 * @return   array $download_args The args being set in the edd_process_download function.
 */
function edd_custom_deliverables_logs_get_custom_deliverables( $download_files, $log, $log_meta ){

	// If the file download key attached to this log does not contain the eddcd_ prefix, no nothing and return the download files as-is.
	if ( strpos( $log_meta['_edd_log_file_id'][0], 'eddcd_' ) === false ){
		return $download_files;
	}

	// Get the payment id and setup an EDD Payment object
	$payment_id  = isset( $log_meta['_edd_log_payment_id'] ) ? $log_meta['_edd_log_payment_id'][0] : false;
	$download_id = $log->post_parent;
	$price_id =  $log_meta['_edd_log_price_id'][0];

	$payment = new EDD_Payment( $payment_id );

	$default_download_files = edd_get_download_files( $download_id, $price_id );

	// Get the download files including custom deliverables from the payment meta
	$custom_deliverables_download_files = edd_custom_deliverables_get_download_files( $payment, $download_id, $price_id, $default_download_files );

	return $custom_deliverables_download_files;

}
add_filter( 'edd_log_file_download_download_files', 'edd_custom_deliverables_logs_get_custom_deliverables', 10, 3 );

/**
 * When we are viewing file download logs, we need to add a workaround so that the file key could be prefixed with eddcd_ when required.
 *
 * @since    1.0.0
 * @return   array $download_args The args being set in the edd_process_download function.
 */
function edd_custom_deliverables_log_file_download_file_id( $file_id, $log ){
	$meta = get_post_custom( $log->ID );

	if ( strpos( $meta['_edd_log_file_id'][0], 'eddcd_' ) === false ) {
		return $file_id;
	}

	return $meta['_edd_log_file_id'][0];
}
add_filter( 'edd_log_file_download_file_id', 'edd_custom_deliverables_log_file_download_file_id', 10, 2 );

/**
 * Change Downloads Upload Directory on the Payment History page
 *
 * Hooks the edd_set_upload_dir filter when appropriate. This function works by
 * hooking on the WordPress Media Uploader and moving the uploading files that
 * are used for EDD to an edd directory under wp-content/uploads/ therefore,
 * the new directory is wp-content/uploads/edd/{year}/{month}. This directory is
 * provides protection to anything uploaded to it.
 *
 * @since 1.0
 * @return void
 */
function eddcd_change_downloads_upload_dir() {

	// If the upload filter has been enabled
	if ( isset( $_SESSION['eddcd_upload_filter_enabled'] ) && $_SESSION['eddcd_upload_filter_enabled'] ){

			edd_create_protection_files( true );
			add_filter( 'upload_dir', 'edd_set_upload_dir' );
	}

}
add_action( 'admin_init', 'eddcd_change_downloads_upload_dir', 999 );

/**
 * Add a fulfilled status column to Payment History
 *
 * @since 1.0
 *
 * @return array
 */
function edd_custom_deliverables_add_fulfilled_column( $columns ) {
	// Force the fulfilled column to be placed just before Status
	unset( $columns['status'] );
	$columns['eddcd_fulfilled'] = __( 'Fulfilled?', 'edd-custom-deliverables' );
	$columns['status']  = __( 'Status', 'edd-custom-deliverables' );
	return $columns;
}
add_filter( 'edd_payments_table_columns', 'edd_custom_deliverables_add_fulfilled_column' );

/**
 * Make the Fulfilled? column sortable
 *
 * @since 1.0
 *
 * @access public
 * @return array
 */
function edd_custom_deliverables_add_sortable_column( $columns ) {
	$columns['eddcd_fulfilled'] = array( 'eddcd_fulfilled', false );
	return $columns;
}
add_filter( 'edd_payments_table_sortable_columns', 'edd_custom_deliverables_add_sortable_column' );

/**
 * The value for the "Fulfilled" column for each payment.
 *
 * @since 1.0
 *
 * @param string $value
 * @param int    $payment_id
 * @param string $column_name
 * @return void
 */
function edd_custom_deliverables_fulfilled_column_value( $value = '', $payment_id = 0, $column_name = '' ){
	if( $column_name == 'eddcd_fulfilled' ) {
		$fulfillment_status = get_post_meta( $payment_id, '_eddcd_fulfillment_status', true );
		if( ! $fulfillment_status ) {
			$value = __( 'No', 'edd-custom-deliverables' );
		} elseif( $fulfillment_status ) {
			$value = __( 'Yes', 'edd-custom-deliverables' );
		} else {
			$value = __( 'N/A', 'edd-custom-deliverables' );
		}
	}
	return $value;
}
add_filter( 'edd_payments_table_column', 'edd_custom_deliverables_fulfilled_column_value', 10, 3 );
