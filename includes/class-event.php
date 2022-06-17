<?php
/**
 * Event Class.
 *
 * Handles event-related functionality.
 *
 * This class interacts with Pledgeball Events.
 *
 * @package SOF_Pledgeball
 * @since 1.0
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
	 * @var object $plugin The Plugin object.
	 */
	public $plugin;

	/**
	 * Pledgeball Event correspondences meta key.
	 *
	 * @since 1.0
	 * @access public
	 * @var string $pledgeball_meta_key The Pledgeball Event correspondences meta key.
	 */
	public $pledgeball_meta_key = '_sof_pledgeball_meta';

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
		if ( in_array( 'event', $post_types ) ) {
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

			// Grab the Occurrence ID and remove it from the event data.
			$occurrence_id = $pledgeball_event['occurrence_id'];
			unset( $pledgeball_event['occurrence_id'] );

			// Add the ID if this Event already exists.
			if ( ! empty( $meta[ $occurrence_id ] ) ) {
				$pledgeball_event['id'] = $meta[ $occurrence_id ];
			}

			// Send the Event to Pledgeball.
			$pledgeball_event_id = $this->plugin->pledgeball->remote->event_save( $pledgeball_event );

			// Add the ID if this Event correspondence doesn't already exist.
			if ( empty( $meta[ $occurrence_id ] ) ) {
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
			$date = [];
			$date['occurrence_id'] = $occurrence_id;
			$date['start'] = eo_get_the_start( 'Y-m-d H:i:s', $event_id, $occurrence_id );
			$date['end'] = eo_get_the_end( 'Y-m-d H:i:s', $event_id, $occurrence_id );
			$date['human'] = eo_get_the_start( 'g:ia, M jS, Y', $event_id, $occurrence_id );

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
	 * @param array $pledgeball_meta The array of Pledgeball Event correspondences.
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

}
