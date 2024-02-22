<?php
/**
 * CiviCRM Contact Class.
 *
 * Handles CiviCRM Contact-related functionality.
 *
 * @package SOF_Pledgeball
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Contact Class.
 *
 * A class that encapsulates CiviCRM Contact-related functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball_CiviCRM_Contact {

	/**
	 * Plugin object.
	 *
	 * @since 1.0
	 * @access public
	 * @var SOF_Pledgeball
	 */
	public $plugin;

	/**
	 * CiviCRM object.
	 *
	 * @since 1.0
	 * @access public
	 * @var SOF_Pledgeball_CiviCRM
	 */
	public $civicrm;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param SOF_Pledgeball_CiviCRM $civicrm The CiviCRM object.
	 */
	public function __construct( $civicrm ) {

		// Store references.
		$this->civicrm = $civicrm;
		$this->plugin  = $civicrm->plugin;

		// Init when the CiviCRM class is loaded.
		add_action( 'sof_pledgeball/civicrm/init', [ $this, 'initialise' ] );

	}

	/**
	 * Initialises this object.
	 *
	 * @since 1.0
	 */
	public function initialise() {

		// Bootstrap class.
		$this->register_hooks();

		/**
		 * Broadcast that this class is active.
		 *
		 * @since 1.0
		 */
		do_action( 'sof_pledgeball/civicrm/contact/init' );

	}

	/**
	 * Registers hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

		/*
		// Hook into Pledgeball Form submissions.
		add_action( 'pledgeball_client/form/pledge_submit/submission', [ $this, 'pledge_submitted' ], 10, 2 );
		*/

	}

	// -------------------------------------------------------------------------

	/**
	 * Acts when a submission has been completed.
	 *
	 * @since 1.0
	 *
	 * @param array $submission The submitted data.
	 * @param array $response The response from the server.
	 */
	public function pledge_submitted( $submission, $response ) {

		/*
		$e     = new \Exception();
		$trace = $e->getTraceAsString();
		$log   = [
			'method'     => __METHOD__,
			'submission' => $submission,
			'response'   => $response,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

	}

	// -------------------------------------------------------------------------

	/**
	 * Creates a CiviCRM Contact for a given set of data.
	 *
	 * @since 1.0
	 *
	 * @param array $contact The CiviCRM Contact data.
	 * @return array|bool $contact_data The array Contact data from the CiviCRM API, or false on failure.
	 */
	public function create( $contact ) {

		// Init as failure.
		$contact_data = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_data;
		}

		// Build params to create Contact.
		$params = [
			'version' => 3,
		] + $contact;

		/*
		 * Minimum array to create an Contact:
		 *
		 * $params = [
		 *   'version' => 3,
		 *   'contact_type_id' => 56,
		 *   'source_contact_id' => "user_contact_id",
		 * ];
		 *
		 * Updates are triggered by:
		 *
		 * $params['id'] = 654;
		 *
		 * Custom Fields are addressed by ID:
		 *
		 * $params['custom_9'] = "Blah";
		 * $params['custom_7'] = 1;
		 * $params['custom_8'] = 0;
		 *
		 * CiviCRM kindly ignores any Custom Fields which are passed to it that
		 * aren't attached to the Entity.
		 */

		// Call the API.
		$result = civicrm_api( 'Contact', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
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

	/**
	 * Updates a CiviCRM Contact with a given set of data.
	 *
	 * @since 1.0
	 *
	 * @param array $contact The CiviCRM Contact data.
	 * @return array|bool $contact_data The array Contact data from the CiviCRM API, or false on failure.
	 */
	public function update( $contact ) {

		// Log and bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => __( 'A numeric ID must be present to update an Contact.', 'sof-pledgeball' ),
				'contact'   => $contact,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Pass through.
		return $this->create( $contact );

	}

}
