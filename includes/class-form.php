<?php
/**
 * Form Class.
 *
 * This class loads the default SOF Pledgeball form classes.
 *
 * @package SOF_Pledgeball
 * @since 1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Form Class.
 *
 * A class that encapsulates form-related functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball_Form {

	/**
	 * Plugin object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $plugin The Plugin object.
	 */
	public $plugin;

	/**
	 * "Submit Pledge" Form object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $pledge_submit The "Submit Pledge" Form object.
	 */
	public $submit;

	/**
	 * "Submit Pledge Form" Cache object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $cache The "Submit Pledge Form" Cache object.
	 */
	public $cache;

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
		do_action( 'sof_pledgeball/form/init' );

	}

	/**
	 * Includes files.
	 *
	 * @since 1.0
	 */
	public function include_files() {

		// Include class files.
		include SOF_PLEDGEBALL_PATH . 'includes/class-form-pledge.php';
		include SOF_PLEDGEBALL_PATH . 'includes/class-form-pledge-cache.php';

	}

	/**
	 * Instantiates objects.
	 *
	 * @since 1.0
	 */
	public function setup_objects() {

		// Init objects.
		$this->submit = new SOF_Pledgeball_Form_Pledge_Submit( $this );
		$this->cache = new SOF_Pledgeball_Form_Pledge_Cache( $this );

	}

	/**
	 * Registers hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

	}

}
