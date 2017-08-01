<?php
/**
 * Ajax callback functions
 *
 * @package     EDD\EDDCustomDeliverables\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Mark an order as "fulfilled" via ajax
 *
 * @since 1.0
 * @return void
 */
function edd_custom_deliverables_mark_as_fulfilled(){

	if ( ! isset( $_POST['payment_id'] ) || ! isset( $_POST['download_id'] ) || ! isset( $_POST['price_id'] ) || ! isset( $_POST['nonce'] ) ){

		print_r( $_POST );

		echo json_encode( array(
			'success' => false,
			'failure_code' => 'data_missing',
			'failure_message' => __( 'There was data missing so the job could not be marked as fulfilled', 'edd-custom-deliverables' )
		) );

		die();
	}

	$nonce = $_POST['nonce'];

	if ( ! wp_verify_nonce( $nonce, 'edd-custom-deliverables-mark-as-fulfilled' ) ){
		echo json_encode( array(
			'success' => false,
			'failure_code' => 'security_failure',
			'failure_message' => __( 'There was a problem with the security check.', 'edd-custom-deliverables' )
		) );

		die();
	}

	// Get the Payment ID
	$payment_id = intval( $_POST['payment_id'] );
	$payment = new EDD_Payment( $payment_id );

	// Get the download and price ids
	$download_id = intval( $_POST['download_id'] );
	$price_id = intval( $_POST['price_id'] );

	$user = wp_get_current_user();

	// Get the existing data for fulfilled jobs for this payment
	$fulfilled_jobs = edd_get_payment_meta( $payment_id, '_eddcd_custom_deliverables_fulfilled_jobs', true );

	// Make sure its an array if this is a brand new save
	if ( empty( $fulfilled_jobs ) || ! is_array( $fulfilled_jobs ) ){
		$fulfilled_jobs = array();
	}

	// Mark this job as complete by saving the timestamp
	$fulfilled_jobs[$download_id][$price_id] = time();

	// Update the fulfilled jobs meta
	edd_update_payment_meta( $payment_id, '_eddcd_custom_deliverables_fulfilled_jobs', $fulfilled_jobs );

	edd_custom_deliverables_check_for_full_fulfillment( $payment, $fulfilled_jobs );

	ob_start();

	echo '<div class="eddcd_fulfilled_message_box">';
		echo __( 'Fullfilled on', 'edd_custom_deliverables' ) . ' ' . date( 'F d, Y, h:ia', time() ) . ' ' . __( 'by', 'edd-custom-deliverables' ) . ' ' . $user->display_name;
		echo ' <a class="eddcd-mark-not-fulfilled" download-id="' . $download_id . '" price-id="' . $price_id . '">' . __( '(Mark as not fulfilled)', 'edd-custom-deliverables' ) . '</a>';
	echo '</div>';
	wp_nonce_field( 'edd-custom-deliverables-mark-as-not-fulfilled', 'edd-custom-deliverables-mark-as-not-fulfilled', false, true );

	$output = ob_get_clean();

	// Return the timestamp
	echo json_encode( array(
		'success' => true,
		'success_message' => $output
	) );

	die();

}
add_action( 'wp_ajax_edd_custom_deliverables_mark_as_fulfilled', 'edd_custom_deliverables_mark_as_fulfilled' );

/**
 * Mark an order as "fulfilled" via ajax
 *
 * @since 1.0
 * @return void
 */
function edd_custom_deliverables_mark_as_not_fulfilled(){

	if ( ! isset( $_POST['payment_id'] ) || ! isset( $_POST['download_id'] ) || ! isset( $_POST['price_id'] ) || ! isset( $_POST['nonce'] ) ){

		print_r( $_POST );

		echo json_encode( array(
			'success' => false,
			'failure_code' => 'data_missing',
			'failure_message' => __( 'There was data missing so the email could not be sent', 'edd-custom-deliverables' )
		) );

		die();
	}

	$nonce = $_POST['nonce'];

	if ( ! wp_verify_nonce( $nonce, 'edd-custom-deliverables-mark-as-not-fulfilled' ) ){
		echo json_encode( array(
			'success' => false,
			'failure_code' => 'security_failure',
			'failure_message' => __( 'There was a problem with the security check.', 'edd-custom-deliverables' )
		) );

		die();
	}

	// Get the Payment ID
	$payment_id = intval( $_POST['payment_id'] );
	$payment = new EDD_Payment( $payment_id );

	// Get the download and price ids
	$download_id = intval( $_POST['download_id'] );
	$price_id = intval( $_POST['price_id'] );

	$user = wp_get_current_user();

	// Get the existing data for fulfilled jobs for this payment
	$fulfilled_jobs = edd_get_payment_meta( $payment_id, '_eddcd_custom_deliverables_fulfilled_jobs', true );

	// Make sure its an array if this is a brand new save
	if ( empty( $fulfilled_jobs ) || ! is_array( $fulfilled_jobs ) ){
		$fulfilled_jobs = array();
	}

	// Mark this job as not complete by removing the variable key for it
	unset( $fulfilled_jobs[$download_id][$price_id] );

	// Update the fulfilled jobs meta
	edd_update_payment_meta( $payment_id, '_eddcd_custom_deliverables_fulfilled_jobs', $fulfilled_jobs );

	edd_custom_deliverables_check_for_full_fulfillment( $payment, $fulfilled_jobs );

	ob_start();

	?>
	<button class="button-secondary eddcd-fulfill-order-btn" download-id="<?php echo $download_id; ?>" price-id="<?php echo $price_id; ?>"><?php echo __( 'Mark job as fulfilled', 'edd-custom-deliverables' ); ?></button>
	<?php

	wp_nonce_field( 'edd-custom-deliverables-mark-as-fulfilled', 'edd-custom-deliverables-mark-as-fulfilled', false, true );

	$output = ob_get_clean();

	// Return the timestamp
	echo json_encode( array(
		'success' => true,
		'success_message' => $output
	) );

	die();

}
add_action( 'wp_ajax_edd_custom_deliverables_mark_as_not_fulfilled', 'edd_custom_deliverables_mark_as_not_fulfilled' );


/**
 * Send the custom deliverables email via ajax
 *
 * @since 1.0
 * @return void
 */
function edd_custom_deliverables_send_email_ajax(){

	global $edd_custom_deliverable_ajax_email_payment_id;

	if ( ! isset( $_POST['payment_id'] ) || ! isset( $_POST['nonce'] ) ){
		echo json_encode( array(
			'success' => false,
			'failure_code' => 'data_missing',
			'failure_message' => __( 'There was data missing so the email could not be sent', 'edd-custom-deliverables' )
		) );

		die();
	}

	$nonce = $_POST['nonce'];

	if ( ! wp_verify_nonce( $nonce, 'edd-custom-deliverables-send-email' ) ){
		echo json_encode( array(
			'success' => false,
			'failure_code' => 'security_failure',
			'failure_message' => __( 'There was a problem with the security check.', 'edd-custom-deliverables' )
		) );

		die();
	}

	// Get the Payment ID
	$payment_id = intval( $_POST['payment_id'] );

	// Set up the subject and header
	$default_message = edd_custom_deliverables_default_email_message();

	$subject      = edd_get_option( 'custom_deliverables_email_subject', __( 'Your files are ready!', 'edd-custom-deliverables' ) );
	$heading      = $subject;
	$message      = edd_get_option( 'custom_deliverables_email_body', '' );

	if ( empty( $message ) ){
		$message = $default_message;
	}

	// Globalize the payment_id so we can use it in other functions that we'll run during this ajax function
	$edd_custom_deliverable_ajax_email_payment_id = $payment_id;

	// Set up the message for the email
	$body = stripslashes( edd_sanitize_text_field( $message ) );
	$body = EDD()->email_tags->do_tags( $body, $payment_id );

	// Set up data for email
	$from_name  = edd_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_email = edd_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$to_email   = edd_get_payment_user_email( $payment_id );

	// Build the email header
	$headers  = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers .= "Reply-To: ". $from_email . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=utf-8\r\n";

	$emails = EDD()->emails;

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );
	$emails->__set( 'headers', $headers );

	$attachments = array();

	$result = $emails->send( $to_email, $subject, $body, $attachments );

	// If the send was not successful
	if ( ! $result ){

		echo json_encode( array(
			'success' => false,
			'failure_code' => 'email_not_sent',
			'success_message' => __( 'The email was not able to be sent.', 'edd-custom-deliverables' ),
		) );

	}else{

		echo json_encode( array(
			'success' => true,
			'success_code' => 'email_successfully_sent',
			'success_message' => __( 'Email successfully sent.', 'edd-custom-deliverables' ),
		) );

		// Add a note to the payment indiciating that the email was sent
		edd_insert_payment_note( $payment_id, __( 'Customer was sent email to notify them of custom deliverables being available.', 'edd-custom-deliverables' ) );
	}

	die();

}
add_action( 'wp_ajax_edd_custom_deliverables_send_email_ajax', 'edd_custom_deliverables_send_email_ajax' );

/**
 * Turn on the file upload filter which tells files to upload to the edd directory
 *
 * @since 1.0
 * @return void
 */
function edd_cd_turn_on_file_filter(){
	$_SESSION['eddcd_upload_filter_enabled'] = true;
}
add_action( 'wp_ajax_edd_cd_turn_on_file_filter', 'edd_cd_turn_on_file_filter' );

/**
 * Turn off the file upload filter which tells files to upload to the edd directory
 *
 * @since 1.0
 * @return void
 */
function edd_cd_turn_off_file_filter(){
	$_SESSION['eddcd_upload_filter_enabled'] = false;
}
add_action( 'wp_ajax_edd_cd_turn_off_file_filter', 'edd_cd_turn_off_file_filter' );

/**
 * Check if an entire payment has been fulfilled and save it accordingly (true or false)
 *
 * @since 1.0
 * @param object $payment
 * @return void
 */
function edd_custom_deliverables_check_for_full_fulfillment( $payment, $fulfilled_jobs ){

	// Set default to true
	$all_jobs_fulfilled = true;

	// Check if all jobs have been fulfilled - Loop through the purchased items
	foreach( $payment->cart_details as $cart_key => $cart_item ){

		// Get the download if of this purchased product
		$purchased_download_id = $cart_item['id'];

		// Get the purchased price ID
		$purchased_price_id = isset( $cart_item['item_number']['options']['price_id'] ) ? $cart_item['item_number']['options']['price_id'] : 0;

		// Check if this product has not been fulfilled
		if ( ! isset( $fulfilled_jobs[$purchased_download_id][$purchased_price_id] ) ){
			$all_jobs_fulfilled = false;
		}

	}

	// If all jobs have been fulfilled, set the status of the fulfillment to true
	if ( $all_jobs_fulfilled ){
		edd_update_payment_meta( $payment->ID, '_eddcd_fulfillment_status', true );
	}else{
		edd_update_payment_meta( $payment->ID, '_eddcd_fulfillment_status', false );
	}
}
