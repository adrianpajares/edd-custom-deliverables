<?php
/**
 * Integration functions to make Custom Deliverables compatible with EDD Frontend Submissions
 *
 * @package     EDD\EDDCustomDeliverables\Functions
 * @since       1.0.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Integrates EDD Custom Deliverables with the EDD Frontend Submissions extension
 *
 * @since 1.0.0
 */
class EDD_Custom_Deliverables_AmazonS3 {

	/**
	 * Get things started
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct() {

		if ( ! class_exists( 'EDD_Amazon_S3' ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'media_upload_tabs', array( $this, 'edd_custom_deliverables_add_s3_media_tabs' ), 11 );

		//Fes Integration with AmazonS3 and Custom Deliverables
		add_filter( 'fes_validate_multiple_pricing_field_files', array( $this, 'valid_url' ), 10, 2 );
		add_filter( 'edd_custom_deliverables_pre_files_save', array( $this, 'send_fes_files_to_s3' ), 10, 2 );
	}

	public function admin_enqueue_scripts(){

		// Use minified libraries if SCRIPT_DEBUG is turned off
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'admin_edd_custom_deliverables_amazons3_js', EDD_CUSTOM_DELIVERABLES_URL . 'assets/js/admin-eddcd-amazons3-integration' . $suffix . '.js', array( 'jquery' ) );

	}

	/**
	 * Adds Amazon S3 tabs to media uploader if on payment details pages
	 *
	 * @param $tabs
	 * @since 1.0.0
	 * @return mixed
	 */
	function edd_custom_deliverables_add_s3_media_tabs( $tabs ) {

		// If we are not on the payment details page, get out of here and return tabs untouched.
		if ( ! isset( $_GET['page'] ) || 'edd-payment-history' !== $_GET['page'] ){
			return $tabs;
		}

		// Add the AmazonS3 tabs.
		$tabs['s3']         = __( 'Upload to Amazon S3', 'edd_s3' );
		$tabs['s3_library'] = __( 'Amazon S3 Library', 'edd_s3' );

		return $tabs;
	}

	/**
	 * Tells FES/CFM that Amazon S3 URLs are valid
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @return bool
	 */
	public function valid_url( $valid, $value = '' ) {

		if( ! $valid && is_string( $value ) ) {
			$ext   = edd_get_file_extension( $value );
			$valid = ! empty( $ext );
		}

		return $valid;
	}

	/**
	 * Uploads Custom Deliverable files to Amazon S3 during FES form submissions
	 *
	 * Only runs if Frontend Submissions is active
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @return array
	 */
	public function send_fes_files_to_s3( $products_and_files = array(), $payment ) {

		if( ! function_exists( 'fes_get_attachment_id_from_url' ) ) {
			return $products_and_files;
		}

		if( ! empty( $products_and_files ) && is_array( $products_and_files ) ) {

			// Loop through the array continaing the custom deliverables for this payment.
			foreach ( $products_and_files as $product_id => $prices ){
				foreach( $prices as $price_id => $files ){
					foreach( $files as $key => $file ) {

						$attachment_id = fes_get_attachment_id_from_url( $file['file'], get_current_user_id() );

						// If this custom deliverable does not have a WordPress attachment ID, skip it. 
						if( ! $attachment_id ) {
							continue;
						}

						$user   = get_userdata( get_current_user_id() );
						$folder = trailingslashit( $user->user_login );
						$args   = array(
							'file' => get_attached_file( $attachment_id, false ),
							'name' => $folder . basename( $file['file'] ),
							'type' => get_post_mime_type( $attachment_id )
						);

						$GLOBALS['edd_s3']->upload_file( $args );

						$products_and_files[$product_id][$price_id][$key]['file'] = edd_get_option( 'edd_amazon_s3_bucket' ) . '/' . $folder . basename( $file['file'] );

						wp_delete_attachment( $attachment_id, true );

					}
				}
			}
		}

		return $products_and_files;

	}
}
