<?php
/**
 * Plugin Name: SOF Pledgeball
 * Plugin URI: https://github.com/spiritoffootball/sof-pledgeball
 * GitHub Plugin URI: https://github.com/spiritoffootball/sof-pledgeball
 * Description: Interacts with Pledgeball Client plugin.
 * Author: Christian Wach
 * Version: 1.0a
 * Author URI: https://theball.tv
 * Requires at least: 5.7
 * Requires PHP: 7.1
 * Text Domain: sof-pledgeball
 * Domain Path: /languages
 *
 * @package SOF_Pledgeball
 * @since 1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



// Set plugin version here.
define( 'SOF_PLEDGEBALL_VERSION', '1.0a' );

// Store reference to this file.
if ( ! defined( 'SOF_PLEDGEBALL_FILE' ) ) {
	define( 'SOF_PLEDGEBALL_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'SOF_PLEDGEBALL_URL' ) ) {
	define( 'SOF_PLEDGEBALL_URL', plugin_dir_url( SOF_PLEDGEBALL_FILE ) );
}

// Store path to this plugin's directory.
if ( ! defined( 'SOF_PLEDGEBALL_PATH' ) ) {
	define( 'SOF_PLEDGEBALL_PATH', plugin_dir_path( SOF_PLEDGEBALL_FILE ) );
}



/**
 * Pledgeball Client Class.
 *
 * A class that encapsulates this plugin's functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball {

	/**
	 * Pledgeball Client plugin reference.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $pledgeball The Pledgeball Client plugin reference.
	 */
	public $pledgeball;

	/**
	 * CiviCRM object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * Mapping object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $mapping The Mapping object.
	 */
	public $mapping;

	/**
	 * Event object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $event The Event object.
	 */
	public $event;

	/**
	 * Organisation object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $organisation The Organisation object.
	 */
	public $organisation;

	/**
	 * Form object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $form The Form object.
	 */
	public $form;

	/**
	 * Initialises this object.
	 *
	 * @since 1.0
	 */
	public function __construct() {

		// Initialise this plugin.
		$this->initialise();

	}

	/**
	 * Initialises this plugin.
	 *
	 * @since 1.0
	 */
	public function initialise() {

		// Skip when Pledgeball Client plugin not present.
		if ( ! defined( 'PLEDGEBALL_CLIENT_VERSION' ) ) {
			return;
		}

		// Only do this once.
		static $done;
		if ( isset( $done ) && $done === true ) {
			return;
		}

		// Bootstrap plugin.
		$this->translation();
		$this->include_files();
		$this->setup_objects();

		/**
		 * Broadcast that this plugin is active.
		 *
		 * @since 1.0
		 */
		do_action( 'sof_pledgeball/init' );

		// We're done.
		$done = true;

	}

	/**
	 * Enables translation.
	 *
	 * @since 1.0
	 */
	public function translation() {

		// Load translations.
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found
		load_plugin_textdomain(
			'sof-pledgeball', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( SOF_PLEDGEBALL_FILE ) ) . '/languages/' // Relative path to files.
		);

	}

	/**
	 * Includes files.
	 *
	 * @since 1.0
	 */
	public function include_files() {

		// Load our class files.
		include SOF_PLEDGEBALL_PATH . 'includes/class-mapping.php';
		include SOF_PLEDGEBALL_PATH . 'includes/class-civicrm.php';
		include SOF_PLEDGEBALL_PATH . 'includes/class-event.php';
		include SOF_PLEDGEBALL_PATH . 'includes/class-organisation.php';
		include SOF_PLEDGEBALL_PATH . 'includes/class-form.php';

	}

	/**
	 * Sets up this plugin's objects.
	 *
	 * @since 1.0
	 */
	public function setup_objects() {

		// Store reference to Pledgeball Client plugin.
		$this->pledgeball = pledgeball_client();

		// Initialise objects.
		$this->mapping = new SOF_Pledgeball_Mapping( $this );
		$this->civicrm = new SOF_Pledgeball_CiviCRM( $this );
		$this->event = new SOF_Pledgeball_Event( $this );
		$this->organisation = new SOF_Pledgeball_Organisation( $this );
		$this->form = new SOF_Pledgeball_Form( $this );

	}

}



/**
 * Loads plugin if not yet loaded and return reference.
 *
 * @since 1.0
 *
 * @return SOF_Pledgeball $plugin The plugin reference.
 */
function sof_pledgeball() {

	// Instantiate plugin if not yet instantiated.
	static $plugin;
	if ( ! isset( $plugin ) ) {
		$plugin = new SOF_Pledgeball();
	}

	// --<
	return $plugin;

}

// Load late when plugins have loaded.
add_action( 'plugins_loaded', 'sof_pledgeball', 100 );

/**
 * Performs plugin activation tasks.
 *
 * @since 1.0
 */
function sof_pledgeball_activate() {

	/**
	 * Broadcast that this plugin has been activated.
	 *
	 * @since 1.0
	 */
	do_action( 'sof_pledgeball/activated' );

}

// Activation.
register_activation_hook( __FILE__, 'sof_pledgeball_activate' );

/**
 * Performs plugin deactivation tasks.
 *
 * @since 1.0
 */
function sof_pledgeball_deactivated() {

	/**
	 * Broadcast that this plugin has been deactivated.
	 *
	 * @since 1.0
	 */
	do_action( 'sof_pledgeball/deactivated' );

}

// Deactivation.
register_deactivation_hook( __FILE__, 'sof_pledgeball_deactivated' );

/*
 * Uninstall uses the 'uninstall.php' method.
 *
 * @see https://developer.wordpress.org/reference/functions/register_uninstall_hook/
 */
