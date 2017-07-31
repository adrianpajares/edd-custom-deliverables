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
class EDD_Custom_Deliverables_Fes {

	/**
	 * Get things started
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct() {

		if ( ! class_exists( 'EDD_Front_End_Submissions' ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ) );
		add_action( 'fes_below_vendor_receipt', array( $this, 'add_custom_deliverables_fields_to_order_edit_screen' ) );
		add_action( 'edd_add_email_tags', array( $this, 'add_email_tag' ), 100 );
		add_action( 'wp_ajax_edd_custom_deliverables_send_fes_email_ajax', array( $this, 'send_fes_email_ajax' ) );
		add_filter( 'edd_registered_settings', array( $this, 'email_settings' ), 99 );

	}

	public function frontend_enqueue_scripts(){

		// If we aren't on the "Edit Order" screen, don't enqueue these
		if ( ! isset( $_GET['task'] ) || 'edit-order' !== $_GET['task'] ){
			return;
		}

		// Use minified libraries if SCRIPT_DEBUG is turned off
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'edd_custom_deliverables_fes_js', EDD_CUSTOM_DELIVERABLES_URL . 'assets/js/eddcd-fes-integration' . $suffix . '.js', array( 'jquery' ) );

		wp_localize_script( 'edd_custom_deliverables_fes_js', 'edd_custom_deliverables_fes_vars',
			array(
				'save_payment_text' => '<h3>' . __( 'Notify Customer', 'edd-custom-deliverables' ) . '</h3><p>' . __( 'Since you have just modified the files, save the payment before notifying the customer. After saving, a notification tool will appear here.', 'edd-custom-deliverables' ) . '</p>',
			)
		);

		wp_enqueue_style( 'edd_custom_deliverables_fes_css', EDD_CUSTOM_DELIVERABLES_URL . 'assets/css/eddcd-fes-integration' . $suffix . '.css' );

	}

	public function add_custom_deliverables_fields_to_order_edit_screen(){

		// If we are looking at the order_id screen in FES, get out o here.
		if( ! isset( $_GET['order_id'] ) ){
			return;
		}

		// Get the product ID from the $_GET['order_id']
		$payment_id = absint( $_GET['order_id'] );
		$payment = new EDD_Payment( $payment_id );
		$available_files = $payment->get_meta( '_eddcd_custom_deliverables_available_files', true );
		$custom_deliverables = $payment->get_meta( '_eddcd_custom_deliverables_custom_files', true );

		// Check if Custom Deliverables form data was just updated. If so, run the save function
		if ( isset( $_POST['eddcd_fes_save_field'] ) ){
			if ( wp_verify_nonce( $_POST['eddcd_fes_save_field'], 'eddcd_fes_save' ) ) {

				// Check if this user has permissions to update payments/orders
				if( current_user_can( 'edit_shop_payments' ) || ( function_exists( 'EDD_FES' ) && EDD_FES()->vendors->vendor_is_vendor() ) ) {

					$custom_deliverables = $this->save_edited_fes_order( $payment, $custom_deliverables );
				}
			}
		}

		// Set up the Vendor Object
		$user_id = get_current_user_id();
		$vendor  = new FES_Vendor( $user_id, true );

		//Output the title for the Custom Deliverables section
		?><h3><strong><?php echo apply_filters( 'edd_custom_deliverables_fes_payment_receipt_title', __( 'Custom Deliverables', 'edd-custom-deliverables' ) ); ?></strong></h3><?php

		// If there are cart details and purchased items
		if ( ! empty( $payment->cart_details ) ) {

			?><form id="edd-custom-deliverables-fes-order-form" method="POST" class="fes-fields"><?php

			// Loop through those purchased items
			foreach( $payment->cart_details as $cart_key => $cart_item ){

				// Get the download if of this purchased product
				$download_id = $cart_item['id'];

				$post = get_post( $download_id );

				// If this product is not one from this Vendor, skip it. We won't allow vendors to upload custom deliverables for products they didn't create.
				if ( $user_id != $post->post_author ){
					continue;
				}

				// Get the purchased price ID
				$price_id = isset( $cart_item['item_number']['options']['price_id'] ) ? $cart_item['item_number']['options']['price_id'] : 0;

				?><div id="edd-custom-deliverables-files-<?php echo $download_id; ?>-<?php echo $price_id; ?>" class="eddcd_repeatable_table"><?php

					?><span class="label"><?php echo __( 'Customized files for', 'edd-custom-deliverables' ) . ' ' . $cart_item['name']; ?></span><?php

					?><div class="eddcd-file-fields eddcd-repeatables-wrap"><?php

						// Get the custom files attached to this payment for this product
						$custom_download_files = isset( $custom_deliverables[$download_id][$price_id] ) ? $custom_deliverables[$download_id][$price_id] : array();

						if ( ! empty( $custom_download_files ) && is_array( $custom_download_files ) ){
							// Loop through the default files
							foreach( $custom_download_files as $key => $value ){

								// Remove our prefix of eddcd_ just for these fields while showing them
								$key = str_replace( 'eddcd_', '', $key );

								$index          = isset( $value['index'] )         ? $value['index']         : $key;
								$name           = isset( $value['name'] )          ? $value['name']          : '';
								$file           = isset( $value['file'] )          ? $value['file']          : '';
								$condition      = isset( $value['condition'] )     ? $value['condition']     : false;
								$attachment_id  = isset( $value['attachment_id'] ) ? absint( $value['attachment_id'] ) : false;
								$thumbnail_size = isset( $value['thumbnail_size'] ) ? $value['thumbnail_size'] : '';

								$args = apply_filters( 'edd_file_row_args', compact( 'name', 'file', 'condition', 'attachment_id', 'thumbnail_size' ), $value );
								?>

								<div class="eddcd_repeatable_upload_wrapper eddcd_repeatable_row" data-key="<?php echo esc_attr( $key ); ?>">
									<?php $this->edd_render_file_row( $key, $args, $download_id, $price_id, $index ); ?>
								</div>

								<?php
							}
						}else{
							?>
							<div class="eddcd_repeatable_upload_wrapper eddcd_repeatable_row">
								<?php $this->edd_render_file_row( 1, array(), $download_id, $price_id, 0 ); ?>
							</div>
							<?php
						}
						?>

						<div class="eddcd-add-repeatable-row">
							<div class="submit" style="float: none; clear:both; background: #fff;">
								<button class="button-secondary eddcd_add_repeatable" download-id="<?php echo $download_id; ?>" price-id="<?php echo $price_id; ?>"><?php _e( 'Add New File', 'edd-custom-deliverables' ); ?></button>
							</div>
						</div>
					</div>
				</div>
				<?php
			}

			if( current_user_can( 'edit_shop_payments' ) || ( function_exists( 'EDD_FES' ) && EDD_FES()->vendors->vendor_is_vendor() ) ) {

				wp_nonce_field( 'eddcd_fes_save', 'eddcd_fes_save_field' );

				?><input type="submit" id="eddcd_fes_submit" value="<?php echo __( 'Save Custom Deliverables', 'edd-custom-deliverables' ); ?>">

			<?php }

			?></form>

			<div class="edd-custom-deliverables-send-email-wrapper edd-admin-box-inside">
				<h3><strong><?php echo __( 'Notify Customer', 'edd-customized-deliverables' ); ?></strong></h3>
				<p>
					<span><?php echo __( 'If you\'d like to send an email to the customer to let them know their customized files are ready to download, you can do so below. ', 'edd-custom-deliverables' ); ?></span>
				</p>
				<p>
					<?php $notify_button_text = __( 'Notify Customer', 'edd-custom-deliverables' ); ?><button class="button" id="edd-custom-deliverables-email-customer" data-payment="<?php echo $payment_id; ?>"><?php echo $notify_button_text; ?></button>
					<span class="spinner"></span>
				</p>
				<?php wp_nonce_field( 'edd-custom-deliverables-send-email', 'edd-custom-deliverables-send-email', false, true ); ?>
				<input type="hidden" id="edd-custom-deliverables-payment-id" name="edd-custom-deliverables-payment-id" value="<?php echo $payment_id; ?>">
				</p>
				<div class="clear"></div>
			</div>

			<?php
		}

	}

	/**
	 * Individual file row.
	 *
	 * Used to output a table row for each file associated with a download.
	 *
	 * @since 1.0.0
	 * @param string $key Array key
	 * @param array $args Array of all the arguments passed to the function
	 * @param int $post_id Download (Post) ID
	 * @return void
	 */
	function edd_render_file_row( $key = '', $args = array(), $post_id, $price_id, $index ) {
		$defaults = array(
			'name'           => null,
			'file'           => null,
			'condition'      => null,
			'attachment_id'  => null,
			'thumbnail_size' => null,
		);

		$args = wp_parse_args( $args, $defaults );

		$fes_helpers = new FES_Helpers();
		$submission_form_id = $fes_helpers->get_form_id_by_name( 'submission' );

	?>
		<div class="eddcd-repeatable-row-header eddcd-draghandle-anchor">
			<span class="eddcd-repeatable-row-title" title="<?php _e( 'Click and drag to re-order files', 'edd-custom-deliverables' ); ?>">
				<?php printf( __( '%1$s file: %2$s', 'edd-custom-deliverables' ), edd_get_label_singular(), '<span class="eddcd_file_id">' . $key . '</span>' ); ?>
				<input type="hidden" name="eddcd_custom_deliverables_custom_files[<?php echo $post_id; ?>][<?php echo $price_id; ?>][<?php echo $key; ?>][index]" class="eddcd_repeatable_index" value="<?php echo $index; ?>" />
			</span>
			<span class="eddcd-repeatable-row-actions">
				<a class="eddcd-remove-row edd-delete" data-type="file" download-id="<?php echo $post_id; ?>" price-id="<?php echo $price_id; ?>"><?php printf( __( 'Remove', 'edd-custom-deliverables' ), $key ); ?><span class="screen-reader-text"><?php printf( __( 'Remove file %s', 'edd-custom-deliverables' ), $key ); ?></span>
				</a>
			</span>
		</div>

		<div class="eddcd-repeatable-row-standard-fields">

			<div class="eddcd-file-name">
				<span class="eddcd-repeatable-row-setting-label"><?php _e( 'File Name', 'edd-custom-deliverables' ); ?></span>
				<input type="hidden" data-formid="<?php echo $submission_form_id ?>" data-fieldname="<?php echo 'custom_deliverables'; ?>" name="eddcd_custom_deliverables_custom_files[<?php echo $post_id; ?>][<?php echo $price_id; ?>][<?php echo absint( $key ); ?>][attachment_id]" class="eddcd_repeatable_attachment_id_field" value="<?php echo esc_attr( absint( $args['attachment_id'] ) ); ?>"/>
				<input type="hidden" name="eddcd_custom_deliverables_custom_files[<?php echo $post_id; ?>][<?php echo $price_id; ?>][<?php echo absint( $key ); ?>][thumbnail_size]" class="eddcd_repeatable_thumbnail_size_field" value="<?php echo esc_attr( $args['thumbnail_size'] ); ?>"/>
				<?php echo EDD()->html->text( array(
					'name'        => 'eddcd_custom_deliverables_custom_files[' . $post_id . '][' . $price_id . '][' . $key . '][name]',
					'value'       => $args['name'],
					'placeholder' => __( 'File Name', 'edd-custom-deliverables' ),
					'class'       => 'eddcd_repeatable_name_field large-text'
				) ); ?>
			</div>

			<div class="eddcd-file-url">
				<span class="eddcd-repeatable-row-setting-label"><?php _e( 'File URL', 'edd-custom-deliverables' ); ?></span>
				<div class="eddcd_repeatable_upload_field_container">
					<?php echo EDD()->html->text( array(
						'name'        => 'eddcd_custom_deliverables_custom_files[' . $post_id . '][' . $price_id . '][' . $key . '][file]',
						'value'       => $args['file'],
						'placeholder' => __( 'Upload or enter the file URL', 'edd-custom-deliverables' ),
						'class'       => 'eddcd_repeatable_upload_field eddcd_upload_field eddcd_large-text'
					) ); ?>

					<span class="eddcd_upload_file">
						<a href="#" data-uploader-title="<?php _e( 'Insert File', 'edd-custom-deliverables' ); ?>" data-uploader-button-text="<?php _e( 'Insert', 'edd-custom-deliverables' ); ?>" class="eddcd_upload_file_button edd-submit button" onclick="return false;"><?php _e( 'Upload', 'edd-custom-deliverables' ); ?></a>
					</span>
				</div>
			</div>

			<?php do_action( 'edd_custom_deliverables_download_file_table_row', $post_id, $price_id, $key, $args ); ?>

		</div>

	<?php
	}

	/**
	 * Save the post meta payment when saving/updating an FES order
	 *
	 * @since 1.0.0
	 * @param $payment_id
	 *
	 * @return void
	 */
	public function save_edited_fes_order( $payment, $custom_deliverables ) {

		// Check if Custom Deliverables form data was just updated. If so, run the save function
		if ( ! wp_verify_nonce( $_POST['eddcd_fes_save_field'], 'eddcd_fes_save' ) ) {
			return $custom_deliverables;
		}

		if ( ! EDD_FES()->vendors->vendor_is_vendor() ) {
			return $custom_deliverables;
		}

		if ( ! isset( $_POST['eddcd_custom_deliverables_custom_files'] ) ){
			return;
		}

		if ( empty( $custom_deliverables ) ){
			$custom_deliverables = array();
		}

		// Get the files being saved as custom deliverables for this payment
		$products = $_POST['eddcd_custom_deliverables_custom_files'];

		$sanitized_values = array();

		$user_id = get_current_user_id();

		// Loop through each product whose files are being saved
		foreach ( $products as $product_id => $custom_download_files ) {

			$post = get_post( $product_id );

			// If this product is not one from this Vendor, skip it. We won't allow vendors to upload custom deliverables for products they didn't create.
			if ( $user_id != $post->post_author ){
				continue;
			}

			// Loop through each file within this product being saved
			foreach( $custom_download_files as $price_id => $files ){

				// Loop through each peice of file data so we can sanitize it
				foreach ( $files as $file_key => $file_data ){

					//Append eddcd_ to the file key if needed
					$file_key = strpos( $file_key, 'eddcd_' ) !== false ? $file_key : 'eddcd_' . $file_key;

					// Loop through each peice of file data so we can sanitize it
					foreach ( $file_data as $meta_key => $meta_value ){

						// If there is no file entered, skip saving this file as it's likely blank.
						if ( empty( $file_data['file'] ) ){
							unset( $sanitized_values[$product_id][$price_id][$file_key] );
						}

						switch( $meta_key ){
							case 'file':

								$all_access_categories = array();

								$sanitized_values[$product_id][$price_id][$file_key]['file'] = sanitize_text_field( $meta_value );

								break;
							case 'index':
								if ( is_numeric( $meta_value ) ){
									$sanitized_values[$product_id][$price_id][$file_key]['index'] = $meta_value;
								}

								break;
							case 'attachment_id':

								if ( ! is_numeric( $meta_value ) ){
									$sanitized_values[$product_id][$price_id][$file_key]['attachment_id'] = false;
								}else{
									$sanitized_values[$product_id][$price_id][$file_key]['attachment_id'] = $meta_value;
								}

								break;
							case 'thumbnail_size':
									$sanitized_values[$product_id][$price_id][$file_key]['thumbnail_size'] = sanitize_text_field( $meta_value );

								break;
							case 'name':
								$sanitized_values[$product_id][$price_id][$file_key]['name'] = sanitize_text_field( $meta_value );

								break;
						}
					}
				}
			}
		}

		// Now that we have sanitized all of the custom deliverables for this Vendor,
		// add in any other customer deliverables for products that belong to other vendors as well.
		if ( ! empty( $payment->cart_details ) ) {

			// Loop through those purchased items
			foreach( $payment->cart_details as $cart_key => $cart_item ){

				// Get the download if of this purchased product
				$product_id = $cart_item['id'];

				// Loop through each sanitized value
				foreach( $sanitized_values as $sanitized_product_id => $sanitized_custom_deliverables_data ){

					// If the product id matches the one we sanitized from this vendor, replace the old value with the new one. Otherwise we don't touch them.
					// This is how we prevent Vendors from uploading customized deliverables for products they didn't create.
					if( $product_id == $sanitized_product_id ){
						$custom_deliverables[$product_id] = $sanitized_custom_deliverables_data;
					}
				}

			}
		}else{
			$custom_deliverables = $sanitized_values;
		}

		$sanitized_values = apply_filters( 'edd_custom_deliverables_pre_files_save', $custom_deliverables, $payment->ID );

		edd_update_payment_meta( $payment->ID, '_eddcd_custom_deliverables_custom_files', $sanitized_values );

		return $sanitized_values;
	}

	/**
	 * Register the {custom_download_list} email tag
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_email_tag() {

		edd_add_email_tag( 'custom_download_list', __( 'Show custom files added for the customer.', 'edd-custom-deliverables' ), array( $this, 'custom_download_list_tag' ) );
	}

	/**
	 * Output a UL of the custom files provided by the Vendor
	 *
	 * @since 1.0.0
	 * @param int $payment_id
	 *
	 * @return string
	 */
	public function custom_download_list_tag( $payment_id ) {

		// Start a buffer so we don't output any errors into the email.
		ob_start();
		$output = '';

		$payment = new EDD_Payment( $payment_id );

		$payment_data  = $payment->get_meta();
		$download_list = '<ul>';
		$cart_items    = $payment->cart_details;
		$email         = $payment->email;
		$user_id       = get_current_user_id();

		if ( $cart_items ) {
			$show_names = apply_filters( 'edd_email_show_names', true );
			$show_links = apply_filters( 'edd_email_show_links', true );

			foreach ( $cart_items as $item ) {

				// Get the download if of this purchased product
				$download_id = $item['id'];

				$post = get_post( $download_id );

				// If this product is not one from this Vendor, skip it. We won't include email custom deliverables for products this vendor didn't create.
				if ( $user_id != $post->post_author ){
					continue;
				}

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

				$files = edd_get_download_files( $item['id'], $price_id );

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

						$files = edd_get_download_files( $bundle_item );

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

		ob_end_clean();

		return $download_list;

	}

	/**
	 * Send the default email for FES and Custom Deliverables letting the customer know their files are ready.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function send_fes_email_ajax(){

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
		$default_message = $this->default_email_message();

		$subject      = edd_get_option( 'custom_deliverables_fes_subject', __( 'Your files are ready!', 'edd-custom-deliverables' ) );
		$heading      = $subject;
		$message      = edd_get_option( 'custom_deliverables_fes_email', '' );

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

	/**
	 * Set up the default message for the global FES custom deliverables email.
	 *
	 * @since 1.0
	 * @param $settings
	 *
	 * @return string
	 */
	public function default_email_message(){

		return __( 'Dear {name},

	Your files are ready to download for your order {payment_id}.
	You can download them here: {custom_download_list}', 'edd_custom_deliverables' );

	}

	/**
	 * Display the FES email settings for Custom Deliverables under the FES > Emails tab.
	 *
	 * @since 1.0
	 * @param $settings
	 *
	 * @return array
	 */
	function email_settings( $settings ) {

		if ( ! isset( $settings['fes'] ) ){
			return $settings;
		}

		$settings['fes']['emails']['edd_custom_deliverables_fes_emails_header'] = array(
			'id'   => 'edd_custom_deliverables_emails_header',
			'name' => '<strong>' . __( 'Custom Deliverables Emails', 'edd-custom-deliverables' ) . '</strong>',
			'desc' => '',
			'type' => 'header',
			'size' => 'regular',
		);
		$settings['fes']['emails']['custom_deliverables_fes_subject'] = array(
				'id'          => 'custom_deliverables_fes_subject',
				'name'        => __( 'Email Subject Line', 'edd-custom-deliverables' ),
				'desc'        => __( 'The subject line used when sending a notification to customers that their customized files are ready to download.','edd-custom-deliverables' ),
				'type'        => 'text',
				'allow_blank' => false,
				'std'         => __( 'Your files are ready!', 'edd-custom-deliverables' ),
		);
		$settings['fes']['emails']['custom_deliverables_fes_email'] = array(
				'id'          => 'custom_deliverables_fes_email',
				'name'        => __( 'Email', 'edd-custom-deliverables' ),
				'desc'        => __( 'Enter the text that is used when sending a notification to customers that their files are ready. HTML is accepted. Available template tags:','edd-custom-deliverables' ) . '<br/>' . edd_get_emails_tags_list(),
				'type'        => 'rich_editor',
				'allow_blank' => false,
				'std'         => $this->default_email_message(),
		);

		return $settings;
	}

}
