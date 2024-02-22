<?php
/**
 * Form Class.
 *
 * This class loads the default SOF Pledgeball form classes.
 *
 * @package SOF_Pledgeball
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
	 * @var SOF_Pledgeball
	 */
	public $plugin;

	/**
	 * "Submit Pledge" Form object.
	 *
	 * @since 1.0
	 * @access public
	 * @var SOF_Pledgeball_Form_Pledge_Submit
	 */
	public $submit;

	/**
	 * "Submit Pledge Form" Cache object.
	 *
	 * @since 1.0
	 * @access public
	 * @var SOF_Pledgeball_Form_Pledge_Cache
	 */
	public $cache;

	/**
	 * "Pledge Data" Metabox object.
	 *
	 * @since 1.1
	 * @access public
	 * @var SOF_Pledgeball_Form_Pledge_Info
	 */
	public $info;

	/**
	 * Transient key.
	 *
	 * @since 1.1
	 * @access public
	 * @var string
	 */
	public $transient_key = 'sof_pledgeball_definitions';

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param SOF_Pledgeball $plugin The plugin object.
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
		include SOF_PLEDGEBALL_PATH . 'includes/class-form-pledge-info.php';

	}

	/**
	 * Instantiates objects.
	 *
	 * @since 1.0
	 */
	public function setup_objects() {

		// Init objects.
		$this->submit = new SOF_Pledgeball_Form_Pledge_Submit( $this );
		$this->cache  = new SOF_Pledgeball_Form_Pledge_Cache( $this );
		$this->info   = new SOF_Pledgeball_Form_Pledge_Info( $this );

	}

	/**
	 * Registers hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the Pledge definitions.
	 *
	 * @since 1.1
	 *
	 * @param int $event_id The numeric ID of the Event.
	 * @param str $country The ISO Country Code for the Event.
	 * @return array $pledges The array of Pledge definitions.
	 */
	public function pledge_definitions_get( $event_id, $country ) {

		// Build transient key.
		$transient_key = $this->transient_key;
		if ( ! empty( $country ) ) {
			$transient_key .= '_' . $country;
		}

		// First check our transient for the data.
		$pledges = get_site_transient( $transient_key );

		// Query again if it's not found.
		if ( false === $pledges ) {

			// Define params to get the Spirit of Football Pledges.
			$args = [ 'eventgroup' => SOF_PLEDGEBALL_EVENT_GROUP_ID ];
			if ( ! empty( $country ) ) {
				$args['countrycode2'] = $country;
			}

			// Get all relevant Pledge definitions.
			$pledges = $this->plugin->pledgeball->remote->definitions_get_all( $args );

			// How did we do?
			if ( ! empty( $pledges ) ) {
				// Store for a day given how infrequently Pledge definitions are modified.
				set_site_transient( $transient_key, $pledges, DAY_IN_SECONDS );
			} else {
				// We got an error and want to try again.
				delete_site_transient( $transient_key );
			}

		}

		// --<
		return $pledges;

	}

}
