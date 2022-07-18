<?php
/**
 * CiviCRM Activity Custom Fields Class.
 *
 * Handles CiviCRM Activity Custom Fields-related functionality.
 *
 * @package SOF_Pledgeball
 * @since 1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Activity Custom Fields Class.
 *
 * A class that encapsulates CiviCRM Activity Custom Fields-related functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball_CiviCRM_Activity_Fields {

	/**
	 * Plugin object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $plugin The Plugin object.
	 */
	public $plugin;

	/**
	 * CiviCRM object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param object $civicrm The CiviCRM object.
	 */
	public function __construct( $civicrm ) {

		// Store references.
		$this->civicrm = $civicrm;
		$this->plugin = $civicrm->plugin;

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

		// Hook into Pledgeball Form submissions.
		//add_action( 'pledgeball_client/form/pledge_submit/submission', [ $this, 'pledge_submitted' ], 10, 2 );

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

		///*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'submission' => $submission,
			'response' => $response,
			//'backtrace' => $trace,
		], true ) );
		//*/

	}

	// -------------------------------------------------------------------------

	/**
	 * Creates a set of CiviCRM Custom Fields to attach to the Activity Type.
	 *
	 * @since 1.0
	 *
	 * @return array|bool $activity_data The array Activity_Fields data from the CiviCRM API, or false on failure.
	 */
	public function create( $activity ) {

	/**
	 * Creates a Custom Field's Option Value.
	 *
	 * The mappings are:
	 *
	 * * Pledge "Name" -> Option Value "Label"
	 * * Term "Name" -> Option Value "Value"
	 * * Term "Description" -> Option Value "Description"
	 *
	 * The Op
	 *
	 * @since 1.0
	 *
	 * @param WP_Term $new_term The new Term in the synced Taxonomy.
	 * @param WP_Term $old_term The Term in the synced Taxonomy as it was before the update.
	 * @return int|bool $option_value_id The CiviCRM Option Value ID, or false on failure.
	 */
	public function option_value_update( $new_term, $old_term = null ) {

		// Sanity check.
		if ( ! ( $new_term instanceof WP_Term ) ) {
			return false;
		}

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Get the full Custom Field from the synced Custom Field ID.
		$custom_field = $this->custom_field_get_by_id( $this->custom_field_id );
		if ( empty( $custom_field ) ) {
			return false;
		}

		// Do not sync if it has no Option Group ID.
		if ( empty( $custom_field['option_group_id'] ) ) {
			return false;
		}

		// Define params for the Option Value.
		$params = [
			'version' => 3,
			'option_group_id' => $custom_field['option_group_id'],
			'label' => $new_term->name,
			'value' => $new_term->name,
		];

		// If there is a description, apply content filters and add to params.
		if ( ! empty( $new_term->description ) ) {
			$params['description'] = $new_term->description;
		}

		// Try and get the synced Option Value ID.
		$option_value_id = $this->option_value_id_get_by_term( $new_term );

		// Trigger update if we find a synced Option Value ID.
		if ( $option_value_id !== false ) {
			$params['id'] = $option_value_id;
		}

		// Unhook CiviCRM.
		$this->hooks_civicrm_remove();

		// Create (or update) the Option Value.
		$result = civicrm_api( 'OptionValue', 'create', $params );

		// Rehook CiviCRM.
		$this->hooks_civicrm_add();

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			$this->sync->log_error( [
				'method' => __METHOD__,
				'message' => $result['error_message'],
				'params' => $params,
				'backtrace' => $trace,
			] );
			return false;
		}

		// Success, grab Option Value ID.
		if ( isset( $result['id'] ) && is_numeric( $result['id'] ) && $result['id'] > 0 ) {
			$option_value_id = intval( $result['id'] );
		}

		// --<
		return $option_value_id;

	}

	/**
	 * Updates a CiviCRM Activity_Fields with a given set of data.
	 *
	 * @since 1.0
	 *
	 * @param array $activity The CiviCRM Activity_Fields data.
	 * @return array|bool $activity_data The array Activity_Fields data from the CiviCRM API, or false on failure.
	 */
	public function update( $activity ) {

	}

}
