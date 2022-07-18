<?php
/**
 * Shortcode Class.
 *
 * This class loads the default SOF Pledgeball shortcode classes.
 *
 * @package SOF_Pledgeball
 * @since 1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Shortcode Class.
 *
 * A class that encapsulates shortcode-related functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball_Shortcode {

	/**
	 * Plugin object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $plugin The Plugin object.
	 */
	public $plugin;

	/**
	 * "Pledgeball Data" Shortcode object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $data_display The "Pledgeball Data" Shortcode object.
	 */
	public $data_display;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param object $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to Plugin object.
		$this->plugin = $plugin;

		// Init when this plugin is loaded.
		add_action( 'sof_pledgeball/init', [ $this, 'initialise' ] );

	}

	/**
	 * Initialises this object.
	 *
	 * @since 1.0
	 */
	public function initialise() {

		// Bootstrap class.
		$this->include_files();
		$this->setup_objects();
		$this->register_hooks();

		/**
		 * Broadcast that this object is now initialised.
		 *
		 * @since 1.0
		 */
		do_action( 'sof_pledgeball/shortcode/init' );

	}

	/**
	 * Includes files.
	 *
	 * @since 1.0
	 */
	public function include_files() {

		// Include class files.
		include SOF_PLEDGEBALL_PATH . 'includes/class-shortcode-data.php';

	}

	/**
	 * Instantiates objects.
	 *
	 * @since 1.0
	 */
	public function setup_objects() {

		// Init objects.
		$this->data_display = new SOF_Pledgeball_Shortcode_Data( $this );

	}

	/**
	 * Registers hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

	}

}
