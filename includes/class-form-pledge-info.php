<?php
/**
 * Pledge Info Class.
 *
 * Handles "Pledge Info" Metabox functionality.
 *
 * @package SOF_Pledgeball
 * @since 1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * "Pledge Info" Metabox Class.
 *
 * A class that encapsulates "Pledge Info" Metabox functionality.
 *
 * @since 1.1
 */
class SOF_Pledgeball_Form_Pledge_Info {

	/**
	 * Plugin object.
	 *
	 * @since 1.1
	 * @access public
	 * @var object $plugin The Plugin object.
	 */
	public $plugin;

	/**
	 * Form object.
	 *
	 * @since 1.1
	 * @access public
	 * @var object $form The Form object.
	 */
	public $form;

	/**
	 * Backup meta key name.
	 *
	 * @since 1.1
	 * @access private
	 * @var string $meta_key The backup meta key name.
	 */
	private $backup_key = '_sof_pledge_backup_cache';

	/**
	 * Constructor.
	 *
	 * @since 1.1
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
	 * @since 1.1
	 */
	public function initialise() {

		// Bootstrap class.
		$this->register_hooks();

		/**
		 * Broadcast that this object is now initialised.
		 *
		 * @since 1.1
		 */
		do_action( 'sof_pledgeball/form/pledge_info/init' );

	}

	/**
	 * Registers hooks.
	 *
	 * @since 1.1
	 */
	public function register_hooks() {

		// Register dashboard hooks.
		$this->register_dashboard_hooks();

	}

	/**
	 * Registers dashboard hooks.
	 *
	 * @since 1.1
	 */
	public function register_dashboard_hooks() {

		// Bail if the current WordPress User is not an editor.
		if ( ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		// Add our meta boxes.
		add_action( 'wp_dashboard_setup', [ $this, 'meta_box_add' ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Registers the "Pledges by Event" meta box.
	 *
	 * @since 1.1
	 */
	public function meta_box_add() {

		// Bail if the current WordPress User is not an editor.
		if ( ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		// Bail if there is no Pledge data.
		$data = $this->get_data();
		if ( empty( $data ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'data' => $data,
			//'backtrace' => $trace,
		), true ) );
		*/

		// Create "Pledges by Event" metabox.
		add_meta_box(
			'sof_pledgeball_info_metabox',
			__( 'Pledges by Event', 'sof-pledgeball' ),
			[ $this, 'meta_box_render' ], // Callback.
			'dashboard', // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'high', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

	}

	/**
	 * Renders the "Pledges by Event" meta box.
	 *
	 * @since 1.1
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

		// Get info.
		$event_count = count( $metabox['args'] );
		$pledge_count = 0;
		foreach ( $metabox['args'] as $event_id => $stats ) {
			$pledge_count = $pledge_count + $stats['count'];
		}

		// Build info.
		$info = sprintf(
			/* translators: 1: The number of pledges, 2: The number of events. */
			__( 'There are %1$s Pledges in %2$s Events.', 'sof-pledgeball' ),
			'<span class="sof-pledge-count">' . $pledge_count . '</span>',
			'<span class="sof-event-count">' . $event_count . '</span>'
		);

		// Build data.
		$data = [];
		foreach ( $metabox['args'] as $event_id => $stats ) {
			if ( empty( $stats['count'] ) ) {

				$data[] = sprintf(
					/* translators: %s: The title of the event */
					__( '%s No pledges recorded', 'sof-pledgeball' ),
					'<span style="display: inline-block; min-width: 200px; margin-right: 5px; font-weight: bold;">' . $stats['title'] . '</span>',
					$stats['kgCO2']
				);

			} else {

				$data[] = sprintf(
					/* translators: 1: The title of the event, 2: The amount of savings pledged, 2: The number of pledges. */
					__( '%1$s %2$s kgCO<sub>2</sub>e pledged (Pledges: %3$s)', 'sof-pledgeball' ),
					'<span style="display: inline-block; min-width: 200px; margin-right: 5px; font-weight: bold;">' . $stats['title'] . '</span>',
					$stats['kgCO2'],
					$stats['count']
				);

			}
		}

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

		// Include template file.
		include SOF_PLEDGEBALL_PATH . 'assets/templates/metaboxes/pledge-info.php';

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the data for the backed-up submissions.
	 *
	 * @since 1.1
	 *
	 * @return array $submissions The info array for the backed-up submissions.
	 */
	public function get_data() {

		// Init return.
		$info = [];

		// Get Events with meta.
		$query = [
			'post_type' => 'event',
			'post_status' => 'publish',
			'no_found_rows' => true,
			'posts_per_page' => -1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			//'meta_key' => $this->backup_key,
			//'meta_compare' => 'EXISTS',
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
				$event_id = get_the_ID();

				// Init Event Pledge total.
				$event_kgCO2_total = 0;

				// Try and get the Pledges for this Event.
				$data = $this->get_pledges_by_event_id( $event_id );

				// Show when there are no Pledges.
				if ( empty( $data ) ) {
					$info[ get_the_ID() ] = [
						'title' => get_the_title(),
						'kgCO2' => 0,
						'count' => 0,
					];
					continue;
				}

				/*
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				$this->plugin->log_error( [
					'method' => __METHOD__,
					'data' => $data,
					//'backtrace' => $trace,
				] );
				*/

				// Try and get ISO Country Code for this Event.
				$country_code = $this->get_country_code_by_event_id( $event_id );

				// Get the Pledge definitions for this Event.
				$pledge_definitions = $this->form->pledge_definitions_get( $event_id, $country_code );
				if ( empty( $pledge_definitions ) ) {
					continue;
				}

				/*
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				$this->plugin->log_error( [
					'method' => __METHOD__,
					'pledge_definitions' => $pledge_definitions,
					//'backtrace' => $trace,
				] );
				*/

				// Calculate total pledged.
				foreach ( $data as $pledge ) {

					/*
					$e = new \Exception();
					$trace = $e->getTraceAsString();
					$this->plugin->log_error( [
						'method' => __METHOD__,
						'pledge' => $pledge,
						//'backtrace' => $trace,
					] );
					*/

					// Init Pledge total.
					$pledge_kgCO2_total = 0;

					// Sanity check.
					if ( empty( $pledge['pledges'] ) ) {
						continue;
					}

					foreach ( $pledge['pledges'] as $choice ) {

						// Get the KgC02 value for each choice.
						$kgCO2 = 0;
						$pledge_number = (int) $choice['pledgenumber'];
						if ( ! empty( $pledge_definitions[ $pledge_number ]->KgCO2e ) ) {
							if ( $pledge_definitions[ $pledge_number ]->KgCO2e != '-1' ) {
								$kgCO2 = (float) $pledge_definitions[ $pledge_number ]->KgCO2e;
							}
						}

						/*
						$e = new \Exception();
						$trace = $e->getTraceAsString();
						$this->plugin->log_error( [
							'method' => __METHOD__,
							'choice' => $choice,
							'kgCO2' => $kgCO2,
							//'backtrace' => $trace,
						] );
						*/

						$event_kgCO2_total += $kgCO2;

					}

				}

				/*
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				$this->plugin->log_error( [
					'method' => __METHOD__,
					'event_kgCO2_total' => $event_kgCO2_total,
					//'backtrace' => $trace,
				] );
				*/

				// Add data to return array.
				$info[ get_the_ID() ] = [
					'title' => get_the_title(),
					'kgCO2' => $event_kgCO2_total,
					'count' => count( $data ),
				];

			}
		}


		// Reset query.
		wp_reset_postdata();

		// Also requires manual emptying of post.
		global $post;
		$GLOBALS['post'] = null;
		$$post = null;

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'info' => $info,
			//'backtrace' => $trace,
		] );
		*/

		// --<
		return $info;

	}

	/**
	 * Gets the backed-up submissions for a given Event Organiser Event ID.
	 *
	 * @since 1.1
	 *
	 * @param int $event_id The numeric ID of the Event Organiser Event.
	 * @return array $submissions The stored queue of submissions.
	 */
	public function get_pledges_by_event_id( $event_id ) {

		// Init return.
		$submissions = [];

		// Get the non-unique items from post meta.
		$data = get_post_meta( $event_id, $this->backup_key, false );
		if ( ! empty( $data ) ) {
			$submissions = $data;
		}

		// --<
		return $submissions;

	}

	/**
	 * Gets the ISO Country Code for a given Event Organiser Event ID.
	 *
	 * @since 1.1
	 *
	 * @param int $event_id The numeric ID of the Event Organiser Event.
	 * @return str $country_code The ISO Country Code.
	 */
	public function get_country_code_by_event_id( $event_id ) {

		// Init return.
		$country_code = '';

		// Get the non-unique items from post meta.
		$pledge_form_use_country = get_field( 'pledge_form_use_country', $event_id );
		if ( ! empty( $pledge_form_use_country ) ) {
			$country_code = $this->plugin->event->countrycode_get_by_event_id( $event_id );
		}

		// --<
		return $country_code;

	}

}
