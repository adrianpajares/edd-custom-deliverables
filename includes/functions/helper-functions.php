<?php
/**
 * Helper Functions
 *
 * @package     EDD\EDDCustomDeliverables\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * When the customer is viewing the custom deliverables, this is the function which makes sure they see the correct files.
 *
 * @since 1.0.0
 * @param $payment
 * @param $download_id
 * @param $price_id
 * @param $default_download_files
 *
 * @return void
 */
function edd_custom_deliverables_get_download_files( $payment, $download_id, $price_id = 0, $default_download_files ){

	// Get which files we should show, just custom files? Just default files? Both?
	$available_files = edd_custom_deliverables_get_available_files_meta( $payment );

	// Get our array of customized deliverable files for this payment
	$custom_deliverables = edd_custom_deliverables_get_custom_files_meta( $payment );

	// If this is a single price product, use 0 as the key
	if ( empty( $price_id ) ){
		$price_id = 0;
	}

	// Figure out which files we should show based on the available_files saved setting
	if ( 'custom_only' == $available_files ) {
		$return_files = $custom_deliverables[$download_id][$price_id];
	} elseif ( 'default_only' == $available_files ) {
		$return_files = $default_download_files;
	} else {

		if ( ! empty( $custom_deliverables ) ) {

			foreach( $custom_deliverables[$download_id][$price_id] as $file_key => $custom_file_data ){
				$default_download_files[$file_key] = $custom_file_data;
			}

		}

		$return_files = $default_download_files;
	}

	// If no custom deliverables are currently set up, return an empty array
	if ( ! isset( $return_files ) ){
		return array();
	}

	return $return_files;
}

/**
 * This function retrieves the custom deliverables from the payment and runs them through a filter.
 * Anywhere you retrieve custom deliverables payment meta, you should do it using this function.
 *
 * @since 1.0.0
 * @param $payment
 *
 * @return void
 */
function edd_custom_deliverables_get_custom_files_meta( $payment ){

	// Get the custom files meta from the database
	$custom_deliverables = $payment->get_meta( '_eddcd_custom_deliverables_custom_files', true );

	// Filter those
	$custom_deliverables = apply_filters( 'eddcd_custom_deliverables_custom_files', $custom_deliverables, $payment );

	return $custom_deliverables;

}

/**
 * This function retrieves the available files setting from the payment and runs them through a filter.
 * Anywhere you retrieve available files payment meta, you should do it using this function.
 *
 * @since 1.0.0
 * @param $payment
 *
 * @return void
 */
function edd_custom_deliverables_get_available_files_meta( $payment ){

	// Get the available files meta from the database
	$available_files = $payment->get_meta( '_eddcd_custom_deliverables_available_files', true );

	// Filter it
	$available_files = apply_filters( 'eddcd_custom_deliverables_available_files', $available_files, $payment );

	return $available_files;

}

/**
 * This function retrieves the fulfilles jobs setting from the payment and runs them through a filter.
 * Anywhere you retrieve fulfilled jobs payment meta, you should do it using this function.
 *
 * @since 1.0.0
 * @param $payment
 *
 * @return void
 */
function edd_custom_deliverables_get_fulfilled_jobs_meta( $payment ){
	
	// Get the custom files meta from the database
	$fulfilled_jobs = $payment->get_meta( '_eddcd_custom_deliverables_fulfilled_jobs', true );

	// Filter those
	$fulfilled_jobs = apply_filters( 'eddcd_custom_deliverables_fulfilled_jobs', $fulfilled_jobs, $payment );

	return $fulfilled_jobs;

}
