<?php
/**
 * Scripts
 *
 * @package     EDD\PluginName\Scripts
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Load admin scripts
 *
 * @since       1.0.0
 * @global      array $edd_settings_page The slug for the EDD settings page
 * @global      string $post_type The type of post that we are editing
 * @return      void
 */
function edd_custom_deliverables_admin_post_meta_scripts( $hook ) {

	global $pagenow;

	// Check if the page variable exists in the url
	$page_url_variable = isset( $_GET['page'] ) ? sanitize_title( $_GET['page'] ) : NULL;

	// If we are on the payment history page
	if ( 'edd-payment-history' !== $page_url_variable  ){
		return false;
	}

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	wp_enqueue_script( 'edd_custom_deliverables_admin_js', EDD_CUSTOM_DELIVERABLES_URL . 'assets/js/eddcd-admin-scripts' . $suffix . '.js', array( 'jquery' ) );

	/*
	wp_localize_script( 'edd_custom_deliverables_admin_js', 'edd_custom_deliverables_vars',
		array(
			'first_variation_string' => __( 'st Price Variation from each product', 'edd-all-access' ),
		)
	);
	*/

	wp_enqueue_style( 'edd_custom_deliverables_admin_css', EDD_CUSTOM_DELIVERABLES_URL . 'assets/css/eddcd-admin' . $suffix . '.css' );

}
add_action( 'admin_enqueue_scripts', 'edd_custom_deliverables_admin_post_meta_scripts', 100 );
