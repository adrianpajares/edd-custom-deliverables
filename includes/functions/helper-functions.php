<?php
/**
 * Helper Functions
 *
 * @package     EDD\EDDCustomDeliverables\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

function edd_custom_deliverables_get_download_files( $payment, $download_id, $price_id = 0, $default_download_files ){

	// Get which files we should show, just custom files? Just default files? Both?
	$available_files = $payment->get_meta( '_eddcd_custom_deliverables_available_files', true );

	// Get our array of customized deliverable files for this payment
	$custom_deliverables = $payment->get_meta( '_eddcd_custom_deliverables_custom_files', true );

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
