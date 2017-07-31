<?php
/**
* Helper Functions
*
* @package     EDD\EDDAllAccess\Functions
* @since       1.0.0
*/

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ){
	exit;
}

/**
 * Add Custom Deliverables settings tab to EDD email settings page
 *
 * @since 1.0
 * @param $sections
 * @return void
 */
function edd_custom_deliverables_settings_sections_emails( $sections ){
	$sections['edd-custom-deliverables-emails'] = __( 'Custom Deliverables', 'edd-custom-deliverables' );
	return $sections;
}
add_filter( 'edd_settings_sections_emails', 'edd_custom_deliverables_settings_sections_emails' );

/**
 * Display the email settings for Custom Deliverables
 *
 * @since 1.0
 * @param $settings
 *
 * @return array
 */
function edd_custom_deliverables_email_settings( $settings ) {
	$custom_deliverables_email_settings = array(
		array(
			'id'   => 'edd_custom_deliverables_emails_header',
			'name' => '<strong>' . __( 'Custom Deliverables Emails', 'edd-custom-deliverables' ) . '</strong>',
			'desc' => '',
			'type' => 'header',
			'size' => 'regular',
		),
		array(
			'id'          => 'custom_deliverables_subject',
			'name'        => __( 'Email Subject Line', 'edd-custom-deliverables' ),
			'desc'        => __( 'The subject line used when sending a notification to customers that their customized files are ready to download.','edd-custom-deliverables' ),
			'type'        => 'text',
			'allow_blank' => false,
			'std'         => __( 'Your files are ready!', 'edd-custom-deliverables' ),
		),
		array(
			'id'          => 'custom_deliverables_email',
			'name'        => __( 'Email', 'edd-custom-deliverables' ),
			'desc'        => __( 'Enter the text that is used when sending a notification to customers that their files are ready. HTML is accepted. Available template tags:','edd-custom-deliverables' ) . '<br/>' . edd_get_emails_tags_list(),
			'type'        => 'rich_editor',
			'allow_blank' => false,
			'std'         => edd_custom_deliverables_default_email_message(),
		)
	);

	if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
		$custom_deliverables_email_settings = array( 'edd-custom-deliverables-emails' => $custom_deliverables_email_settings );
	}

	return array_merge( $settings, $custom_deliverables_email_settings );
}
add_filter( 'edd_settings_emails', 'edd_custom_deliverables_email_settings' );

/**
 * Set up the default message for the global dcustom deliverables email.
 *
 * @since 1.0
 * @param $settings
 *
 * @return string
 */
function edd_custom_deliverables_default_email_message(){

	return __( 'Dear {name},

Your files are ready to download for your order {payment_id}.
You can download them here: {download_list}', 'edd_custom_deliverables_body' );

}
