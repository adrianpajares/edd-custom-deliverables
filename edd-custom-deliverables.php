<?php
/**
* Plugin Name:     Easy Digital Downloads - Custom Deliverables
* Plugin URI:      http://easydigitaldownloads.com/downloads/custom-deliverables/
* Description:     This extension makes it possible to deliver custom files to clients at a later time after they purchase. Perfect for freelancers or freelancing marketplaces like fiverr.com
* Version:         1.0.1
* Author:          Phil Johnston
* Author URI:      https://mintplugins.com
* Text Domain:     edd_custom_deliverables
*
* @package         EDD\EddCustomDeliverables
* @author          Phil Johnston
* @copyright       Copyright (c) Easy Digital Downloads
*/


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version
define( 'EDD_CUSTOM_DELIVERABLES_VER', '1.0.0' );

// Plugin path
define( 'EDD_CUSTOM_DELIVERABLES_DIR', plugin_dir_path( __FILE__ ) );

// Plugin URL
define( 'EDD_CUSTOM_DELIVERABLES_URL', plugin_dir_url( __FILE__ ) );

// Plugin Root File
define( 'EDD_CUSTOM_DELIVERABLES_FILE', __FILE__ );

if( !class_exists( 'EDD_Custom_Deliverables' ) ) {

  /**
  * Main EDD_Custom_Deliverables class
  *
  * @since       1.0.0
  */
  class EDD_Custom_Deliverables {

	/**
	* @var         EDD_Custom_Deliverables $instance The one true EDD_Custom_Deliverables
	* @since       1.0.0
	*/
	private static $instance;

	public static $edd_custom_deliverables_metabox;

	public static $edd_fes;

	public static $edd_amazons3;

	/**
	* Get active instance
	*
	* @access      public
	* @since       1.0.0
	* @return      object self::$instance The one true EDD_Custom_Deliverables
	*/
	public static function instance() {
	  if( !self::$instance ) {
		self::$instance = new EDD_Custom_Deliverables();
		self::$instance->includes();
		self::$instance->load_textdomain();
		self::$instance->hooks();
		self::$edd_custom_deliverables_metabox = new EDD_Custom_Deliverables_MetaBox();

		// Set up integrated plugins
		self::$edd_fes = new EDD_Custom_Deliverables_Fes();
		self::$edd_amazons3 = new EDD_Custom_Deliverables_AmazonS3();

	  }

	  return self::$instance;
	}

	/**
	* Include necessary files
	*
	* @access      private
	* @since       1.0.0
	* @return      void
	*/
	private function includes() {

	  // Include scripts
	  require_once EDD_CUSTOM_DELIVERABLES_DIR . 'includes/functions/enqueue-scripts.php';

	  // Include misc functions
	  require_once EDD_CUSTOM_DELIVERABLES_DIR . 'includes/functions/misc-functions.php';

	  // Include helper functions
	  require_once EDD_CUSTOM_DELIVERABLES_DIR . 'includes/functions/helper-functions.php';

	  // Include receipt functions
	  require_once EDD_CUSTOM_DELIVERABLES_DIR . 'includes/functions/receipts.php';

	  // Include ajax callbacks
	  require_once EDD_CUSTOM_DELIVERABLES_DIR . 'includes/functions/ajax-callbacks.php';

	  // Include Post Meta options
	  require_once EDD_CUSTOM_DELIVERABLES_DIR . 'includes/admin/payment-meta/custom-deliverables-metabox.php';

	  // Integration with FES
	  require_once EDD_CUSTOM_DELIVERABLES_DIR . 'includes/integrations/plugin-fes.php';

	  // Integration with AmazonS3
	  require_once EDD_CUSTOM_DELIVERABLES_DIR . 'includes/integrations/plugin-amazons3.php';

	}

	/**
	* Run action and filter hooks
	*
	* @access      private
	* @since       1.0.0
	* @return      void
	*
	*/
	private function hooks() {
	  // Handle licensing
	  if( class_exists( 'EDD_License' ) ) {
		$license = new EDD_License( __FILE__, 'Custom Deliverables', EDD_CUSTOM_DELIVERABLES_VER, 'MintPlugins' );
	  }
	}

	/**
	* Internationalization
	*
	* @access      public
	* @since       1.0.0
	* @return      void
	*/
	public function load_textdomain() {
	  // Set filter for language directory
	  $lang_dir = EDD_CUSTOM_DELIVERABLES_DIR . '/languages/';
	  $lang_dir = apply_filters( 'edd_custom_deliverables_languages_directory', $lang_dir );

	  // Traditional WordPress plugin locale filter
	  $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-custom-deliverables' );
	  $mofile = sprintf( '%1$s-%2$s.mo', 'edd-custom-deliverables', $locale );

	  // Setup paths to current locale file
	  $mofile_local   = $lang_dir . $mofile;
	  $mofile_global  = WP_LANG_DIR . '/edd-custom-deliverables/' . $mofile;

	  if( file_exists( $mofile_global ) ) {
		// Look in global /wp-content/languages/edd_custom_deliverables/ folder
		load_textdomain( 'edd-custom-deliverables', $mofile_global );
	  } elseif( file_exists( $mofile_local ) ) {
		// Look in local /wp-content/plugins/edd_custom_deliverables/languages/ folder
		load_textdomain( 'edd-custom-deliverables', $mofile_local );
	  } else {
		// Load the default language files
		load_plugin_textdomain( 'edd-custom-deliverables', false, $lang_dir );
	  }
	}
  }
} // End if class_exists check

/**
* The main function responsible for returning the one true EDD_Custom_Deliverables
* instance to functions everywhere
*
* @since       1.0.0
* @return      \EDD_Custom_Deliverables The one true EDD_Custom_Deliverables
*
* @todo        Inclusion of the activation code below isn't mandatory, but
*              can prevent any number of errors, including fatal errors, in
*              situations where your extension is activated but EDD is not
*              present.
*/
function edd_custom_deliverables() {

	if ( version_compare( phpversion(), '5.3', '<' ) ){
		echo __( 'You need to be running version 5.3 of PHP or later to use EDD Custom Deliverables. Contact your webhost to have them upgrade your PHP version' );
	}
	if ( ! defined( 'EDD_VERSION' ) || version_compare( EDD_VERSION, '2.7' ) <= 0 ){
		add_action( 'admin_notices', 'edd_custom_deliverables_edd_too_old_notice' );
	}
	if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		if( ! class_exists( 'EDD_Extension_Activation' ) ) {
		  require_once 'includes/updates/class.extension-activation.php';
		}

		$activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();
	} else {
		return EDD_Custom_Deliverables::instance();
	}
}
add_action( 'plugins_loaded', 'edd_custom_deliverables' );

/**
* Admin notice used if EDD is not updated to 2.6 or later.
*
* @since       1.0.0
*/
function edd_custom_deliverables_edd_too_old_notice(){
  ?>
  <div class="notice notice-error">
	<p><?php echo __( 'EDD Custom Deliverables: Your version of Easy Digital Downloads must be updated to version 2.7 or later to use the Custom Deliverables extension', 'edd-custom-deliverables' ); ?></p>
  </div>
  <?php
}

function check_form_id(){

	$form_id = EDD()->session->get( 'FES_FILE_UPLOAD_FORMID' );

	// If a form ID exists, log that form ID. This will happen if the file upload filter is successfully enabled.
	if ( ! empty( $form_id ) ){
		error_log( EDD()->session->get( 'FES_FILE_UPLOAD_FORMID' ) );
	}else{
		// This will happen if the file upload filter is disabled or incorrectly enabled without setting the Form ID.
		error_log( 'Form ID was empty' );
	}
}
add_action( 'init', 'check_form_id' );
