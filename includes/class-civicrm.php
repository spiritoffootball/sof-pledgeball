<?php
/**
 * CiviCRM Class.
 *
 * Handles CiviCRM-related functionality.
 *
 * @package SOF_Pledgeball
 * @since 1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Class.
 *
 * A class that encapsulates CiviCRM-related functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball_CiviCRM {

	/**
	 * Plugin object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $plugin The Plugin object.
	 */
	public $plugin;

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
		 * Broadcast that this class is active.
		 *
		 * @since 1.0
		 */
		do_action( 'sof_pledgeball/civicrm/init' );

	}

	/**
	 * Includes files.
	 *
	 * @since 1.0
	 */
	public function include_files() {

	}

	/**
	 * Instantiates objects.
	 *
	 * @since 1.0
	 */
	public function setup_objects() {

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
	 * Check if CiviCRM is initialised.
	 *
	 * @since 1.0
	 *
	 * @return bool True if CiviCRM initialised, false otherwise.
	 */
	public function is_initialised() {

		// Init only when CiviCRM is fully installed.
		if ( ! defined( 'CIVICRM_INSTALLED' ) ) {
			return false;
		}
		if ( ! CIVICRM_INSTALLED ) {
			return false;
		}

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			return false;
		}

		// Try and initialise CiviCRM.
		return civi_wp()->initialize();

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the CiviCRM Contact data for a given ID.
	 *
	 * @since 1.0
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array|bool $contact_data An array of Contact data, or false on failure.
	 */
	public function contact_get_by_id( $contact_id ) {

		// Init return.
		$contact_data = false;

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $contact_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $contact_data;
		}

		// Define params to get queried Contact.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $contact_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $contact_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_data;
		}

		// The result set should contain only one item.
		$contact_data = array_pop( $result['values'] );

		// --<
		return $contact_data;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get a Country by its numeric ID.
	 *
	 * @since 1.0
	 *
	 * @param integer $country_id The numeric ID of the Country.
	 * @return array $country The array of Country data.
	 */
	public function country_get_by_id( $country_id ) {

		// Init return.
		$country = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $country;
		}

		// Params to get the Country.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $country_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Country', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $country;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $country;
		}

		// The result set should contain only one item.
		$country = array_pop( $result['values'] );

		// --<
		return $country;

	}

	/**
	 * Get a Country by its "name".
	 *
	 * @since 1.0
	 *
	 * @param string $country_name The "name" of the Country.
	 * @return array $country The array of Country data, empty on failure.
	 */
	public function country_get_by_name( $country_name ) {

		// Init return.
		$country = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $country;
		}

		// Params to get the Country.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'name' => $country_name,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Country', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $country;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $country;
		}

		// The result set should contain only one item.
		$country = array_pop( $result['values'] );

		// --<
		return $country;

	}

	/**
	 * Get a Country by its "short name".
	 *
	 * @since 1.0
	 *
	 * @param string $country_short The "short name" of the Country.
	 * @return array $country The array of Country data, empty on failure.
	 */
	public function country_get_by_short( $country_short ) {

		// Init return.
		$country = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $country;
		}

		// Params to get the Country.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'iso_code' => $country_short,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Country', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $country;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $country;
		}

		// The result set should contain only one item.
		$country = array_pop( $result['values'] );

		// --<
		return $country;

	}

}
