<?php
/**
 * Ajax callback functions
 *
 * @package     EDD\EDDCustomDeliverables\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

function edd_custom_deliverables_send_email_ajax(){

	global $edd_custom_deliverable_ajax_email_payment_id;

	if ( ! isset( $_POST['payment_id'] ) || ! isset( $_POST['nonce'] ) || ! isset( $_POST['subject'] ) || ! isset( $_POST['body'] ) ){
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

	// Get the details for the email from the $_POST
	$payment_id = intval( $_POST['payment_id'] );
	$subject    = sanitize_text_field( $_POST['subject'] );
	$heading    = $subject;

	// Globalize the payment_id so we can use it in other functions that we'll run during this ajax function
	$edd_custom_deliverable_ajax_email_payment_id = $payment_id;

	// Set up the message for the email
	$body = stripslashes( edd_sanitize_text_field( $_POST['body'] ) );
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
add_action( 'wp_ajax_no_priv_edd_custom_deliverables_send_email_ajax', 'edd_custom_deliverables_send_email_ajax' );
