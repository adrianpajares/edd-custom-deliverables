<?php

/**
 * Class EDD_Custom_Deliverables_MetaBox
 * @since 1.0.0
 * Hooks, filters, and methods for the data required for Custom Deliverables
 */
class EDD_Custom_Deliverables_MetaBox {

	/**
	 * Load up all the hooks
	 *
	 * @since 1.0.0
	 *
	 * @return false;
	 */
	public function __construct() {
		add_action( 'edd_view_order_details_billing_after', array( $this, 'custom_files_in_payment' ) );
		add_action( 'edd_updated_edited_purchase',          array( $this, 'save_edited_payment' ), 10, 1 );
	}

	/**
	 * Show the Custom Deliverables metabox on the view order details
	 *
	 * @since 1.0.0
	 * @param $payment_id
	 *
	 * @return void
	 */
	public function custom_files_in_payment( $payment_id ) {

		$payment = new EDD_Payment( $payment_id );
		$available_files = $payment->get_meta( '_eddcd_custom_deliverables_available_files', true );
		$custom_deliverables = $payment->get_meta( '_eddcd_custom_deliverables_custom_files', true );

		?>
		<div id="edd-custom-deliverables" class="postbox">
			<h3 class="hndle"><span><?php _e( 'Custom Deliverables', 'edd-custom-deliverables' ); ?></span></h3>
			<div class="inside">
				<div class="edd-admin-box">
					<div class="edd-custom-deliverables-files-area-wrapper edd-admin-box-inside">
						<div id="eddcd_custom_deliverables_file_chooser_wrapper">
							<p><?php echo __( 'Here, you can provide custom files to this customer.', 'edd-custom-deliverables' ); ?>
							</p>
							<p>
								<span class="label"><?php echo __( 'Which group(s) of files should the customer have access to?', 'edd-custom-deliverables' ); ?></span><br />
								<select id="eddcd_custom_deliverables_available_files" name="eddcd_custom_deliverables_available_files">
									<option <?php selected( $available_files, 'custom_and_default' ); ?> value="custom_and_default"><?php echo __( 'Both custom files (below) and default product files', 'edd-custom-deliverables' ); ?></option>
									<option <?php selected( $available_files, 'custom_only' ); ?> value="custom_only"><?php echo __( 'Only custom files', 'edd-custom-deliverables' ); ?></option>
									<option <?php selected( $available_files, 'default_only' ); ?> value="default_only"><?php echo __( 'Only default files', 'edd-custom-deliverables' ); ?></option>
								</select>
							</p>
						</div>
						<div id="eddcd_custom_deliverables_custom_files_wrapper">
							<div id="eddcd_file_fields" class="eddcd_meta_table_wrap">
								<div class="widefat eddcd_repeatable_table">

									<div class="eddcd-custom-deliverables-products eddcd-repeatables-wrap">
										<?php
											// If there are cart details and purchased items
											if ( ! empty( $payment->cart_details ) ) {

												// Loop through those purchased items
												foreach( $payment->cart_details as $cart_key => $cart_item ){

													// Get the download if of this purchased product
													$download_id = $cart_item['id'];

													// Get the purchased price ID
													$price_id = isset( $cart_item['item_number']['options']['price_id'] ) ? $cart_item['item_number']['options']['price_id'] : 0;

													?><div id="edd-custom-deliverables-files-<?php echo $download_id; ?>-<?php echo $price_id; ?>"><?php

														?><span class="label"><?php echo __( 'Customized files for', 'edd-custom-deliverables' ) . ' ' . $cart_item['name']; ?></span><?php

														?><div class="eddcd-file-fields eddcd-repeatables-wrap"><?php

															// Get the custom files attached to this payment for this product
															$custom_download_files = isset( $custom_deliverables[$download_id][$price_id] ) ? $custom_deliverables[$download_id][$price_id] : array();

															if ( ! empty( $custom_download_files ) ){
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
											}
										?>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="edd-custom-deliverables-send-email-wrapper edd-admin-box-inside">
						<h3><?php echo __( 'Notify Customer', 'edd-customized-deliverables' ); ?></h3>
						<p>
							<?php echo sprintf( __( 'If you\'d like to send an email to the customer to let them know their files are ready to download, you can do so below. Note that you can edit that email in the %s area.', 'edd-custom-deliverables' ), '<a href="' . admin_url( 'edit.php?post_type=download&page=edd-settings&tab=emails&section=edd-custom-deliverables-emails' ) . '">' . __( 'Email Settings', 'edd-custom-deliverables' ) . '</a>' ); ?>
						</p>
						<p>
							<?php $notify_button_text = __( 'Notify Customer', 'edd-custom-deliverables' ); ?><span class="button-secondary" id="edd-custom-deliverables-email-customer" data-payment="<?php echo $payment_id; ?>"><?php echo $notify_button_text; ?></span>
							<span class="spinner"></span>
						</p>
						<?php wp_nonce_field( 'edd-custom-deliverables-send-email', 'edd-custom-deliverables-send-email', false, true ); ?>
						<input type="hidden" id="edd-custom-deliverables-payment-id" name="edd-custom-deliverables-payment-id" value="<?php echo $payment_id; ?>">
						</p>
						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>
		<?php
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

		$prices = edd_get_variable_prices( $post_id );

		$variable_pricing = edd_has_variable_prices( $post_id );
		$variable_display = $variable_pricing ? '' : ' style="display:none;"';
		$variable_class   = $variable_pricing ? ' has-variable-pricing' : '';
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

		<div class="eddcd-repeatable-row-standard-fields<?php echo $variable_class; ?>">

			<div class="eddcd-file-name">
				<span class="eddcd-repeatable-row-setting-label"><?php _e( 'File Name', 'edd-custom-deliverables' ); ?></span>
				<input type="hidden" name="eddcd_custom_deliverables_custom_files[<?php echo $post_id; ?>][<?php echo $price_id; ?>][<?php echo absint( $key ); ?>][attachment_id]" class="eddcd_repeatable_attachment_id_field" value="<?php echo esc_attr( absint( $args['attachment_id'] ) ); ?>"/>
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
						'class'       => 'eddcd_repeatable_upload_field eddcd_upload_field large-text'
					) ); ?>

					<span class="eddcd_upload_file">
						<a href="#" data-uploader-title="<?php _e( 'Insert File', 'edd-custom-deliverables' ); ?>" data-uploader-button-text="<?php _e( 'Insert', 'edd-custom-deliverables' ); ?>" class="eddcd_upload_file_button" onclick="return false;"><?php _e( 'Upload a File', 'edd-custom-deliverables' ); ?></a>
					</span>
				</div>
			</div>

			<?php do_action( 'edd_custom_deliverables_download_file_table_row', $post_id, $price_id, $key, $args ); ?>

		</div>
	<?php
	}

	/**
	 * Save the post meta for the order details when modifying the attached files
	 *
	 * @since 1.0.0
	 * @param $payment_id
	 *
	 * @return void
	 */
	public function save_edited_payment( $payment_id ) {

		// Get the setting for which files should be shown to the customer
		$available_files = isset( $_POST['eddcd_custom_deliverables_available_files'] ) ? $_POST['eddcd_custom_deliverables_available_files'] : NULL;

		if ( empty( $available_files ) ){
			return;
		}

		// Sanitize and save the setting
		$available_files = sanitize_text_field( $available_files );

		// Save the setting
		edd_update_payment_meta( $payment_id, '_eddcd_custom_deliverables_available_files', $available_files );

		// Get the files being saved as custom deliverables for this payment
		$products = $_POST['eddcd_custom_deliverables_custom_files'];

		$sanitized_values = array();

		// Loop through each product whose files are being saved
		foreach ( $products as $product_id => $custom_download_files ) {

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
							continue;
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

		edd_update_payment_meta( $payment_id, '_eddcd_custom_deliverables_custom_files', $sanitized_values );

	}
}
