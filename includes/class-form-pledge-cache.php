<?php
/**
 * "Submit Pledge Form" Cache Class.
 *
 * Handles "Submit Pledge Form" Cache functionality.
 *
 * @package SOF_Pledgeball
 * @since 1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * "Submit Pledge Form" Cache Class.
 *
 * A class that encapsulates "Submit Pledge Form" Cache functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball_Form_Pledge_Cache {

	/**
	 * Plugin object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $plugin The Plugin object.
	 */
	public $plugin;

	/**
	 * Form object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $form The Form object.
	 */
	public $form;

	/**
	 * Meta key name.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $meta_key The meta key name.
	 */
	private $meta_key = '_sof_pledge_submit_cache';

	/**
	 * Queued flag.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $is_queued True if the submission has been queued, false otherwise.
	 */
	private $is_queued = false;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param object $form The form object.
	 */
	public function __construct( $form ) {

		// Store reference to Plugin object.
		$this->plugin = $form->plugin;
		$this->form = $form;

		// Init when this form class is loaded.
		add_action( 'sof_pledgeball/form/init', [ $this, 'initialise' ] );

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
		 * Broadcast that this object is now initialised.
		 *
		 * @since 1.0
		 */
		do_action( 'sof_pledgeball/form/pledge_cache/init' );

	}

	/**
	 * Registers hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

		// Intercept submissions.
		add_action( 'sof_pledgeball/form/pledge_submit/submission', [ $this, 'add_to_queue' ], 10, 2 );
		add_filter( 'sof_pledgeball/form/pledge_submit/response', [ $this, 'response_queued' ], 10, 2 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Sends a set of queued submissions to Pledgeball.
	 *
	 * @since 1.0
	 *
	 * @return array $submissions The stored queue of submissions.
	 */
	public function queue_run() {

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets all queued submissions.
	 *
	 * @since 1.0
	 *
	 * @return array $submissions The stored queue of submissions.
	 */
	public function get_queue() {

		// Init return.
		$submissions = [];

		// Get Events with meta.
		$query = [
			'post_type' => 'event',
			'post_status' => 'publish',
			'no_found_rows' => true,
			'posts_per_page' => -1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key' => $this->meta_key,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			//'meta_value' => $filename,
			'meta_compare' => 'EXISTS',
		];

		// Get all non-unique items from post meta.
		foreach ( $events as $event ) {
			$data = $this->get_queue_by_event_id( $event->ID );
			if ( ! empty( $data ) ) {
				$submissions[] = $data;
			}
		}

		// --<
		return $submissions;

	}

	/**
	 * Gets the queued submissions for a given Event Organiser Event ID.
	 *
	 * @since 1.0
	 *
	 * @param int $event_id The numeric ID of the Event Organiser Event.
	 * @return array $submissions The stored queue of submissions.
	 */
	public function get_queue_by_event_id( $event_id ) {

		// Init return.
		$submissions = [];

		// Get the non-unique items from post meta.
		$data = get_post_meta( $event_id, $this->meta_key, false );
		if ( ! empty( $data ) ) {
			$submissions = $data;
		}

		// --<
		return $submissions;

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
	public function add_to_queue( $submission, $response ) {

		// If we get a response, no need to queue.
		if ( false !== $response ) {
			return;
		}

		///*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'submission' => $submission,
			'response' => $response,
			//'backtrace' => $trace,
		] );
		//*/

		// Add a non-unique item to post meta.
		add_post_meta( $submission['eo_event_id'], $this->meta_key, $submission, false );

		// Set flag.
		$this->is_queued = true;

	}

	/**
	 * Acts on a response when a submission has been queued.
	 *
	 * @since 1.0
	 *
	 * @param array $response The response from the server.
	 * @param array $submission The submitted data.
	 * @return array $response The modified response.
	 */
	public function response_queued( $response, $submission ) {

		// If not queued, no need to filter.
		if ( false === $this->is_queued ) {
			return;
		}

		///*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'response' => $response,
			'submission' => $submission,
			//'backtrace' => $trace,
		] );
		//*/

		// Set impossible value.
		$response = true;

		// Reset flag.
		$this->is_queued = false;

		// --<
		return $response;

	}

}
