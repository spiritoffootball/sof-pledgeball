<?php
/**
 * Event Class.
 *
 * Handles event-related functionality.
 *
 * This class interacts with Pledgeball Events.
 *
 * @package SOF_Pledgeball
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Event Class.
 *
 * A class that encapsulates Pledgeball Event-related functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball_Event {

	/**
	 * Plugin object.
	 *
	 * @since 1.0
	 * @access public
	 * @var SOF_Pledgeball
	 */
	public $plugin;

	/**
	 * Pledgeball Event correspondences meta key.
	 *
	 * @since 1.0
	 * @access public
	 * @var string
	 */
	public $pledgeball_meta_key = '_sof_pledgeball_meta';

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

		// Bail on localhost.
		if ( defined( 'SOF_PLEDGEBALL_HOST' ) && 'localhost' === SOF_PLEDGEBALL_HOST ) {
			return;
		}

		// Bootstrap class.
		$this->include_files();
		$this->setup_objects();
		$this->register_hooks();

		/**
		 * Broadcast that this class is active.
		 *
		 * @since 1.0
		 */
		do_action( 'sof_pledgeball/event/init' );

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

		// Add Event callbacks.
		add_action( 'eventorganiser_save_event', [ $this, 'event_saved' ], 20 );

		// We don't want Co-Authors on Event Organiser Events.
		add_filter( 'coauthors_supported_post_types', [ $this, 'coauthors_exclude' ], 20 );

		// Clear the "All Event data" transient when a Pledge is made.
		add_action( 'sof_pledgeball/form/pledge_submit/submission', [ $this, 'pledgeball_data_all_delete' ], 20, 2 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Filter the Co-Authors Post Types.
	 *
	 * @since 1.0
	 *
	 * @param array $post_types The array of supported Post Types.
	 * @return array $post_types The modified array of supported Post Types.
	 */
	public function coauthors_exclude( $post_types ) {

		// Remove "event" from the array of Post Types.
		if ( in_array( 'event', $post_types, true ) ) {
			$post_types = array_diff( $post_types, [ 'event' ] );
		}

		// --<
		return $post_types;

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept "Save Event".
	 *
	 * @since 1.0
	 *
	 * @param int $event_id The numeric ID of the Event Organiser Event.
	 */
	public function event_saved( $event_id ) {

		// Only allow people who can publish Posts.
		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}

		// Get Event data.
		$event = get_post( $event_id );

		// Get all dates for this Event.
		$dates = $this->dates_get_by_event_id( $event_id );

		// Get Venue for this Event.
		$venue_id = eo_get_venue( $event_id );

		// Get the full Venue data.
		$venue = false;
		if ( ! empty( $venue_id ) ) {
			$venue = $this->venue_get_by_id( $venue_id );
		}

		// Get the Event Category.
		$term = $this->term_get_by_event_id( $event_id );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'event' => $event,
			'dates' => $dates,
			'venue' => $venue,
			'term' => $term,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Convert to Pledgeball format.
		$pledgeball_events = $this->plugin->mapping->pledgeball_event_prepare( $event, $dates, $venue, $term );
		if ( empty( $pledgeball_events ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'pledgeball_events' => $pledgeball_events,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Try and get existing meta.
		$meta = $this->pledgeball_meta_get( $event_id );

		// Handle all Pledgeball Events.
		foreach ( $pledgeball_events as $pledgeball_event ) {

			// Skip sending data if mandatory Fields are missing.
			if ( empty( $pledgeball_event['email'] ) ) {
				continue;
			}
			if ( empty( $pledgeball_event['title'] ) ) {
				continue;
			}
			if ( empty( $pledgeball_event['eventtype'] ) ) {
				continue;
			}

			// Grab the Occurrence ID and remove it from the event data.
			$occurrence_id = $pledgeball_event['occurrence_id'];
			unset( $pledgeball_event['occurrence_id'] );

			// Add the ID if this Event already exists.
			if ( ! empty( $meta[ $occurrence_id ] ) ) {
				$pledgeball_event['id'] = $meta[ $occurrence_id ];
			}

			// Send the Event to Pledgeball.
			$pledgeball_event_id = $this->plugin->pledgeball->remote->event_save( $pledgeball_event );

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'pledgeball_event_id' => $pledgeball_event_id,
				//'backtrace' => $trace,
			], true ) );
			*/

			// Overwrite the ID because the Pledgeball data may have changed.
			if ( ! empty( $pledgeball_event_id ) ) {
				$meta[ $occurrence_id ] = $pledgeball_event_id;
			}

		}

		// Save the ID in the Event Organiser Event meta.
		$this->pledgeball_meta_set( $event_id, $meta );

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the Event Organiser Category for a given Event ID.
	 *
	 * The Category Term corresponds to the CiviCRM Event Type.
	 *
	 * @since 1.0
	 *
	 * @param int $event_id The numeric ID of the Event Organiser Event.
	 * @return object|bool $term The Category for the Event.
	 */
	public function term_get_by_event_id( $event_id ) {

		// Get the Terms for this Post - there should only be one.
		$terms = get_the_terms( $event_id, 'event-category' );

		// Error check.
		if ( is_wp_error( $terms ) ) {
			return false;
		}

		// Bail if we didn't get one.
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return false;
		}

		// Get first Term object (keyed by Term ID).
		$term = array_shift( $terms );

		// --<
		return $term;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets all Event Organiser dates for a given Event ID.
	 *
	 * @since 1.0
	 *
	 * @param int $event_id The numeric ID of the Event Organiser Event.
	 * @return array $dates All dates for the Event.
	 */
	public function dates_get_by_event_id( $event_id ) {

		// Init dates.
		$dates = [];

		// Get Occurrences.
		$occurrences = eo_get_the_occurrences_of( $event_id );
		if ( empty( $occurrences ) ) {
			return $dates;
		}

		// Loop through them.
		foreach ( $occurrences as $occurrence_id => $occurrence ) {

			// Build an array, formatted for CiviCRM.
			$date                  = [];
			$date['occurrence_id'] = $occurrence_id;
			$date['start']         = eo_get_the_start( 'Y-m-d H:i:s', $event_id, $occurrence_id );
			$date['end']           = eo_get_the_end( 'Y-m-d H:i:s', $event_id, $occurrence_id );
			$date['human']         = eo_get_the_start( 'g:ia, M jS, Y', $event_id, $occurrence_id );

			// Add to our return array.
			$dates[] = $date;

		}

		// --<
		return $dates;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the full Venue data for a given ID.
	 *
	 * @since 1.0
	 *
	 * @param int $venue_id The numeric ID of the Venue.
	 * @return object $venue The numeric ID of the Venue.
	 */
	public function venue_get_by_id( $venue_id ) {

		// Get Venue data as Term object.
		$venue = eo_get_venue_by( 'id', $venue_id );
		if ( empty( $venue ) ) {
			return false;
		}

		/*
		 * Manually add Venue metadata because since Event Organiser 3.0 it is
		 * no longer added by default to the Venue object.
		 */
		$address         = eo_get_venue_address( $venue_id );
		$venue->address  = isset( $address['address'] ) ? $address['address'] : '';
		$venue->postcode = isset( $address['postcode'] ) ? $address['postcode'] : '';
		$venue->city     = isset( $address['city'] ) ? $address['city'] : '';
		$venue->country  = isset( $address['country'] ) ? $address['country'] : '';
		$venue->state    = isset( $address['state'] ) ? $address['state'] : '';

		// Add geolocation data.
		$venue->lat = number_format( floatval( eo_get_venue_lat( $venue_id ) ), 6 );
		$venue->lng = number_format( floatval( eo_get_venue_lng( $venue_id ) ), 6 );

		// --<
		return $venue;

	}

	/**
	 * Gets the 2-letter ISO Country Code for a given Event ID.
	 *
	 * @since 1.0
	 *
	 * @param int $event_id The numeric ID of the Event.
	 * @return str $country_code The Country Code of the Event, empty on failure.
	 */
	public function countrycode_get_by_event_id( $event_id ) {

		// Init return.
		$country_code = '';

		// Get Venue for this Event.
		$venue_id = eo_get_venue( $event_id );
		if ( empty( $venue_id ) ) {
			return $country_code;
		}

		// Get the full Venue data.
		$venue = $this->venue_get_by_id( $venue_id );

		// Extract the name of the Country.
		$country_name = isset( $venue->country ) ? $venue->country : '';
		if ( empty( $country_name ) ) {
			return $country_code;
		}

		// Get the Country data.
		$country = $this->plugin->civicrm->country_get_by_name( $country_name );
		if ( empty( $country ) ) {
			return $country_code;
		}

		// Extract the ISO Country Code.
		$country_code = isset( $country['iso_code'] ) ? $country['iso_code'] : '';

		// --<
		return $country_code;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets a Pledgeball Event ID for a given Event Organiser Event.
	 *
	 * The correspondence data is stored as an array keyed by Occurrence ID. It
	 * is done this way because a single Event Organiser Event can have multiple
	 * Occurrences - though in practice there should only ever be a one-to-one
	 * mapping because the Events are created through an ACFE Form.
	 *
	 * @since 1.0
	 *
	 * @param integer $event_id The numeric ID of the Event Organiser Event.
	 * @return array $pledgeball_meta The array of Pledgeball Event correspondences.
	 */
	public function pledgeball_meta_get( $event_id ) {

		// Get the meta value.
		$pledgeball_meta = get_post_meta( $event_id, $this->pledgeball_meta_key, true );

		// If it's empty, cast as empty array.
		if ( empty( $pledgeball_meta ) ) {
			$pledgeball_meta = [];
		}

		// --<
		return $pledgeball_meta;

	}

	/**
	 * Updates a Pledgeball Event ID for a given Event Organiser Event.
	 *
	 * @since 1.0
	 *
	 * @param integer $event_id The numeric ID of the Event Organiser Event.
	 * @param array   $pledgeball_meta The array of Pledgeball Event correspondences.
	 */
	public function pledgeball_meta_set( $event_id, $pledgeball_meta ) {

		// Update Event meta.
		update_post_meta( $event_id, $this->pledgeball_meta_key, $pledgeball_meta );

	}

	/**
	 * Deletes the Pledgeball Event ID for a given Event Organiser Event.
	 *
	 * @since 1.0
	 *
	 * @param integer $event_id The numeric ID of the Event Organiser Event.
	 */
	public function pledgeball_meta_delete( $event_id ) {

		// Delete the meta value.
		delete_post_meta( $event_id, $this->pledgeball_meta_key );

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets all Pledgeball Event data.
	 *
	 * @since 1.0
	 *
	 * @param bool $force Force data to fetched from remote API.
	 * @return array $pledgeball_data The array of Pledgeball Event data.
	 */
	public function pledgeball_data_get_all( $force = false ) {

		// Init return.
		$pledgeball_data = [];

		// Use a transient key.
		$transient_key = 'sof_pledgeball_events';

		// Maybe check our transient for the data.
		if ( false === $force ) {
			// Return the data if there is some.
			$pledgeball_data = get_site_transient( $transient_key );
			if ( ! empty( $pledgeball_data ) ) {
				return $pledgeball_data;
			}
		}

		// Get all Pledgeball Event data.
		$pledgeball_data = $this->plugin->pledgeball->remote->events_get_all();

		// Store for a day given that Pledgeball recalculate daily.
		if ( ! empty( $pledgeball_data ) ) {
			set_site_transient( $transient_key, $pledgeball_data, DAY_IN_SECONDS );
		}

		// --<
		return $pledgeball_data;

	}

	/**
	 * Deletes the transient for all Pledgeball Event data.
	 *
	 * Note that the transient is cleared when a Pledge is made.
	 *
	 * @since 1.0
	 *
	 * @param array $submission The submitted data.
	 * @param array $response The response from the server.
	 */
	public function pledgeball_data_all_delete( $submission, $response ) {

		// Bail if we have no submission.
		if ( empty( $submission ) ) {
			return;
		}

		// Delete the transient.
		delete_site_transient( 'sof_pledgeball_events' );

	}

	/**
	 * Gets the Pledgeball Event data for a given Event Organiser Event.
	 *
	 * @since 1.0
	 *
	 * @param integer $event_id The numeric ID of the Event Organiser Event.
	 * @param bool    $force Force data to fetched from remote API.
	 * @return array $pledgeball_meta The array of Pledgeball Event correspondences.
	 */
	public function pledgeball_data_get( $event_id, $force = false ) {

		// Init return.
		$pledgeball_data = [];

		// Bail if we don't have an Event ID.
		if ( empty( $event_id ) ) {
			return $pledgeball_data;
		}

		// Use a transient key.
		$transient_key = 'sof_pledgeball_event_' . $event_id;

		// Maybe check our transient for the data.
		if ( false === $force ) {
			// Return the data if there is some.
			$pledgeball_data = get_site_transient( $transient_key );
			if ( ! empty( $pledgeball_data ) ) {
				return $pledgeball_data;
			}
		}

		// Get the Pledgeball data for this Event.
		$pledgeball_data = $this->plugin->pledgeball->remote->event_get_by_id( $event_id );

		// Store for a day given that Pledgeball recalculate daily.
		if ( ! empty( $pledgeball_data ) ) {
			set_site_transient( $transient_key, $pledgeball_data, DAY_IN_SECONDS );
		}

		// --<
		return $pledgeball_data;

	}

}
