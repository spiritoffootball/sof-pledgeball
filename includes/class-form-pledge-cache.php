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
	 * Backup meta key name.
	 *
	 * @since 1.1
	 * @access private
	 * @var string $meta_key The backup meta key name.
	 */
	private $backup_key = '_sof_pledge_backup_cache';

	/**
	 * Queued flag.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $is_queued True if the submission has been queued, false otherwise.
	 */
	private $is_queued = false;

	/**
	 * POST Nonce action.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $nonce_action The Nonce action.
	 */
	private $nonce_action = 'sof_pledgeball_queue_runner_action';

	/**
	 * POST Nonce name.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $nonce_name The Nonce name.
	 */
	private $nonce_name = 'sof_pledgeball_queue_runner_nonce';

	/**
	 * AJAX Nonce name.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $nonce_ajax The Nonce name.
	 */
	private $nonce_ajax = 'sof_pledgeball_queue_runner_ajax';

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
		add_action( 'sof_pledgeball/form/pledge_submit/submission', [ $this, 'add_backup' ], 10, 2 );
		add_action( 'sof_pledgeball/form/pledge_submit/submission', [ $this, 'add_to_queue' ], 10, 2 );
		add_filter( 'sof_pledgeball/form/pledge_submit/response', [ $this, 'response_queued' ], 10, 2 );

		// Register dashboard hooks.
		$this->register_dashboard_hooks();

	}

	/**
	 * Registers dashboard hooks.
	 *
	 * @since 1.0
	 */
	public function register_dashboard_hooks() {

		// Bail if the current WordPress User is not an editor.
		if ( ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		// Add our meta boxes.
		add_action( 'wp_dashboard_setup', [ $this, 'meta_box_add' ] );

		// Add resources prior to page load.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_js' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_css' ] );

		// Register AJAX handler.
		add_action( 'wp_ajax_sof_pledgeball_queue_runner', [ $this, 'queue_run' ] );

		// Register our form submit hander.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//add_action( 'admin_init', [ $this, 'form_submitted' ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Enqueues our Javascript on the dashboard.
	 *
	 * @since 1.0
	 *
	 * @param str $hook The filename of the displayed screen.
	 */
	public function enqueue_js( $hook ) {

		// Bail if not the dashboard.
		if ( 'index.php' !== $hook ) {
			return;
		}

		// Bail if there is no queue.
		$queue = $this->get_queue();
		if ( empty( $queue ) ) {
			return;
		}

		// Enqueue Javascript.
		wp_enqueue_script(
			'sof-pledgeball-queue-script',
			SOF_PLEDGEBALL_URL . 'assets/js/pledge-queue-runner.js',
			[ 'jquery' ],
			SOF_PLEDGEBALL_VERSION,
			true
		);

		// Init settings and localisation array.
		$vars = [
			'settings' => [
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			],
			'localisation' => [
				'send' => __( 'Send', 'sof-pledgeball' ),
				'sending' => __( 'Sending...', 'sof-pledgeball' ),
				'sent' => __( 'Pledges Sent', 'sof-pledgeball' ),
			],
		];

		// Localise the WordPress way.
		wp_localize_script(
			'sof-pledgeball-queue-script',
			'Pledgeball_Queue_Runner_Vars',
			$vars
		);

	}

	/**
	 * Enqueues the stylesheet on the dashboard.
	 *
	 * @since 1.0
	 *
	 * @param str $hook The filename of the displayed screen.
	 */
	public function enqueue_css( $hook ) {

		// Bail if not the dashboard.
		if ( 'index.php' !== $hook ) {
			return;
		}

		// Bail if there is no queue.
		$queue = $this->get_queue();
		if ( empty( $queue ) ) {
			return;
		}

		// Enqueue CSS.
		wp_enqueue_style(
			'sof-pledgeball-queue-styles',
			SOF_PLEDGEBALL_URL . 'assets/css/pledge-queue-runner.css',
			null,
			SOF_PLEDGEBALL_VERSION,
			'all'
		);

	}

	// -------------------------------------------------------------------------

	/**
	 * Registers the "Send Queued Pledges to Pledgeball" meta box.
	 *
	 * @since 1.0
	 */
	public function meta_box_add() {

		// Bail if the current WordPress User is not an editor.
		if ( ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		// Bail if there is no queue.
		$queue = $this->get_queue();
		if ( empty( $queue ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'queue' => $queue,
			//'backtrace' => $trace,
		), true ) );
		*/

		// Create "Send Queued Pledges to Pledgeball" metabox.
		add_meta_box(
			'sof_pledgeball_queue_metabox',
			__( 'Send Queued Pledges to Pledgeball', 'sof-pledgeball' ),
			[ $this, 'meta_box_render' ], // Callback.
			'dashboard', // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'high', // Vertical placement: options are 'core', 'high', 'low'.
			$queue
		);

	}

	/**
	 * Renders the "Send Queued Pledges to Pledgeball" meta box.
	 *
	 * @since 1.0
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_render( $unused, $metabox ) {

		// Bail if the current WordPress User is not an editor.
		if ( ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'unused' => $unused,
			'metabox-args' => $metabox['args'],
			//'backtrace' => $trace,
		), true ) );
		*/

		// Handle errors.
		$error = '';
		$error_css = ' display: none;';
		$error_flag = filter_input( INPUT_GET, 'queue-runner-error' );
		if ( ! empty( $error_flag ) ) {
			$error_css = '';
			switch ( $error_flag ) {
				case 'ajax':
					$error = __( 'An AJAX process is running. Could not submit Pledges to Pledgeball', 'sof-pledgeball' );
					break;
				case 'no-auth':
					$error = __( 'Authentication failed. Could not submit Pledges to Pledgeball.', 'sof-pledgeball' );
					break;
			}
		}

		// Get info.
		$event_count = count( $metabox['args'] );
		$pledge_count = 0;
		foreach ( $metabox['args'] as $event_id => $items ) {
			$pledge_count = $pledge_count + count( $items );
		}

		// Build info.
		$info = sprintf(
			/* translators: 1: The number of pledges, 2: The number of events. */
			__( 'There are %1$s unsent Pledges in %2$s Events.', 'sof-pledgeball' ),
			'<span class="sof-pledge-count">' . $pledge_count . '</span>',
			'<span class="sof-event-count">' . $event_count . '</span>'
		);

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'event_count' => $event_count,
			'pledge_count' => $pledge_count,
			//'backtrace' => $trace,
		), true ) );
		*/

		// Set submit button options.
		$options = [
			'data-security' => esc_attr( wp_create_nonce( $this->nonce_ajax ) ),
		];

		// Include template file.
		include SOF_PLEDGEBALL_PATH . 'assets/templates/metaboxes/pledge-queue-runner.php';

	}

	// -------------------------------------------------------------------------

	/**
	 * Runs the queue of submissions.
	 *
	 * @since 1.0
	 */
	public function queue_run() {

		// Default response.
		$data = [
			'notice' => __( 'Could not send Pledges to Pledgeball. Please try again.', 'sof-pledgeball' ),
			'saved' => false,
		];

		// Skip if not AJAX submission.
		if ( ! wp_doing_ajax() ) {
			wp_send_json( $data );
		}

		// Since this is an AJAX request, check security.
		$result = check_ajax_referer( $this->nonce_ajax, false, false );
		if ( $result === false ) {
			$data['notice'] = __( 'Authentication failed. Could not send Pledges to Pledgeball.', 'sof-pledgeball' );
			wp_send_json( $data );
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'POST' => $_POST,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get current queue.
		$queue = $this->get_queue();

		// Get info.
		$event_count = count( $queue );
		$pledge_count = 0;
		foreach ( $queue as $event_id => $items ) {
			$pledge_count = $pledge_count + count( $items );
		}

		// When there are remaining Pledges.
		if ( $pledge_count > 0 ) {

			// Get a submission from the queue.
			$event_data = array_pop( $queue );
			$submission = array_pop( $event_data );

			// Submit the queued Pledge.
			if ( false === SOF_PLEDGEBALL_SKIP_SUBMIT ) {
				$response = $this->plugin->pledgeball->remote->pledge_create( $submission );
			} else {
				$response = false;
			}

			// Delete the queued Pledge on success - or when testing.
			if ( ! empty( $response ) || true === SOF_PLEDGEBALL_SKIP_SUBMIT ) {
				$this->delete_from_queue( $submission );
				$pledge_count--;
				if ( 0 === count( $event_data ) ) {
					$event_count--;
				}
			}

		}

		// Assign success message.
		$message = __( 'All done!', 'sof-pledgeball' );

		// Data response.
		$data = [
			'message' => $message,
			'saved' => true,
			'pledge_count' => $pledge_count,
			'event_count' => $event_count,
		];

		// Return the data.
		wp_send_json( $data );

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
			'meta_compare' => 'EXISTS',
		];

		// The query.
		$events = new WP_Query( $query );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'query' => $query,
			'events' => $events,
			//'backtrace' => $trace,
		] );
		*/

		// Get all non-unique items from post meta.
		if ( $events->have_posts() ) {
			while ( $events->have_posts() ) {
				$events->the_post();
				$data = $this->get_queue_by_event_id( get_the_ID() );
				if ( ! empty( $data ) ) {
					$submissions[ get_the_ID() ] = $data;
				}
			}
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'queue' => $submissions,
			//'backtrace' => $trace,
		] );
		*/

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
	 * Backs up the pledge when a Pledge submission has been completed.
	 *
	 * @since 1.1
	 *
	 * @param array $submission The submitted data.
	 * @param array $response The response from the server.
	 */
	public function add_backup( $submission, $response ) {

		// Bail if we have no response - unless we are testing.
		if ( empty( $response ) ) {
			if ( false === SOF_PLEDGEBALL_SKIP_SUBMIT ) {
				return;
			}
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'submission' => $submission,
			'response' => $response,
			//'backtrace' => $trace,
		] );
		*/

		// Add a non-unique item to post meta.
		add_post_meta( $submission['eo_event_id'], $this->backup_key, $submission, false );

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

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'submission' => $submission,
			'response' => $response,
			//'backtrace' => $trace,
		] );
		*/

		// Add a non-unique item to post meta.
		add_post_meta( $submission['eo_event_id'], $this->meta_key, $submission, false );

		// Set flag.
		$this->is_queued = true;

	}

	/**
	 * Deletes a submission has been completed.
	 *
	 * @since 1.0
	 *
	 * @param array $submission The submitted data.
	 */
	public function delete_from_queue( $submission ) {

		// Be extra safe.
		if ( empty( $submission ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'submission' => $submission,
			//'backtrace' => $trace,
		] );
		*/

		// Remove non-unique item from post meta.
		delete_post_meta( $submission['eo_event_id'], $this->meta_key, $submission );

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

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'response' => $response,
			'submission' => $submission,
			//'backtrace' => $trace,
		] );
		*/

		// Set impossible value.
		$response = true;

		// Reset flag.
		$this->is_queued = false;

		// --<
		return $response;

	}

	// -------------------------------------------------------------------------

	/**
	 * Perform actions when the form has been submitted.
	 *
	 * @since 1.0
	 */
	public function form_submitted() {

		// Maybe send queue.
		$submitted = filter_input( INPUT_POST, 'sof-pledgeball-queue-runner-submit' );
		if ( ! empty( $submitted ) ) {
			$this->form_nonce_check();
			$this->form_queue_run_chunked();
			$this->form_redirect();
		}

	}

	/**
	 * Runs the Pledge Queue.
	 *
	 * @since 1.0
	 */
	public function form_queue_run_chunked() {

		// Skip if AJAX submission.
		if ( wp_doing_ajax() ) {
			$this->form_redirect( [ 'queue-runner-error' => 'ajax' ] );
		}

		// Skip if no form nonce.
		if ( ! isset( $_POST[ $this->nonce_name ] ) ) {
			return;
		}

		// Skip if nonce verification fails.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! wp_verify_nonce( wp_unslash( $_POST[ $this->nonce_name ] ), $this->nonce_action ) ) {
			$this->form_redirect( [ 'queue-runner-error' => 'no-auth' ] );
		}

		///*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'POST' => $_POST,
			//'backtrace' => $trace,
		], true ) );
		//*/

	}

	/**
	 * Check the nonce.
	 *
	 * @since 1.0
	 */
	private function form_nonce_check() {

		// Do we trust the source of the data?
		check_admin_referer( $this->nonce_action, $this->nonce_name );

	}

	/**
	 * Redirect to the Dashboard page with an extra param.
	 *
	 * @since 1.0
	 *
	 * @param array $args The query arguments.
	 */
	private function form_redirect( $args = [] ) {

		// Maybe use default array of arguments.
		if ( empty( $args ) ) {
			$args = [
				'queue-sent' => 'true',
			];
		}

		// Redirect to our admin page.
		wp_safe_redirect( add_query_arg( $args, admin_url( 'index.php' ) ) );
		exit;

	}

}
