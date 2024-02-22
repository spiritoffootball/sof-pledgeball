<?php
/**
 * CiviCRM Activity Class.
 *
 * Handles CiviCRM Activity-related functionality.
 *
 * @package SOF_Pledgeball
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Activity Class.
 *
 * A class that encapsulates CiviCRM Activity-related functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball_CiviCRM_Activity {

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
		do_action( 'sof_pledgeball/civicrm/activity/init' );

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
	 * Creates a CiviCRM Activity for a given set of data.
	 *
	 * @since 1.0
	 *
	 * @param array $activity The CiviCRM Activity data.
	 * @return array|bool $activity_data The array Activity data from the CiviCRM API, or false on failure.
	 */
	public function create( $activity ) {

		// Init as failure.
		$activity_data = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $activity_data;
		}

		// Build params to create Activity.
		$params = [
			'version' => 3,
		] + $activity;

		/*
		 * Minimum array to create an Activity:
		 *
		 * $params = [
		 *   'version' => 3,
		 *   'activity_type_id' => 56,
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
		$result = civicrm_api( 'Activity', 'create', $params );

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
			return $activity_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $activity_data;
		}

		// The result set should contain only one item.
		$activity_data = array_pop( $result['values'] );

		// --<
		return $activity_data;

	}

	/**
	 * Updates a CiviCRM Activity with a given set of data.
	 *
	 * @since 1.0
	 *
	 * @param array $activity The CiviCRM Activity data.
	 * @return array|bool $activity_data The array Activity data from the CiviCRM API, or false on failure.
	 */
	public function update( $activity ) {

		// Log and bail if there's no Activity ID.
		if ( empty( $activity['id'] ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => __( 'A numeric ID must be present to update an Activity.', 'sof-pledgeball' ),
				'activity'  => $activity,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Pass through.
		return $this->create( $activity );

	}

}
