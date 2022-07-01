<?php
/**
 * Mapping Class.
 *
 * Handles mapping-related functionality.
 *
 * This class interacts with Pledgeball Mappings.
 *
 * @package SOF_Pledgeball
 * @since 1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Mapping Class.
 *
 * A class that encapsulates Pledgeball Mapping-related functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball_Mapping {

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
		do_action( 'sof_pledgeball/mapping/init' );

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
	 * Prepares the Pledgeball Events for a given Event Organiser Event.
	 *
	 * @since 1.0
	 *
	 * @param object $eo_event The Event Organiser Event object.
	 * @param array $dates The array of dates for the Event Organiser Event.
	 * @param object $venue The Event Organiser Venue object.
	 * @param object $term The Event Organiser Term object.
	 * @return array $pledgeball_events The array of data for the Pledgeball Events.
	 */
	public function pledgeball_event_prepare( $eo_event, $dates, $venue, $term ) {

		// Init return.
		$pledgeball_events = [];

		// Sanity check.
		if ( empty( $dates ) ) {
			return $pledgeball_events;
		}

		// Init basic Pledgeball Event data.
		$pledgeball_event = [
			'title' => $eo_event->post_title,
			'description' => get_the_excerpt( $eo_event->ID ),
			'fixturesource' => 'sof',
			'fixturesourceid' => $eo_event->ID,
			'eventgroup' => SOF_PLEDGEBALL_EVENT_GROUP_ID,
		];

		// Maybe add the Location data.
		$location = $this->pledgeball_location_prepare( $venue );
		if ( ! empty( $location ) ) {
			$pledgeball_event += $location;
		}

		// We need an email from the CiviCRM Contact that is the "Organiser".
		if ( empty( $pledgeball_event['email'] ) ) {
			$organiser_id = get_field( 'ball_host' );
			if ( ! empty( $organiser_id ) ) {
				$organiser = $this->plugin->civicrm->contact_get_by_id( $organiser_id );
				if ( ! empty( $organiser['email'] ) ) {
					$pledgeball_event['email'] = $organiser['email'];
				}
			}
		}

		// We need an "eventtype" from the Event Category.
		if ( ! empty( $term ) && ( $term instanceof WP_Term ) ) {
			// TODO: Check this.
			$pledgeball_event['eventtype'] = 'other';
			$pledgeball_event['othertype'] = $term->name;
		}

		// Loop through dates and format a Pledgeball Event per date.
		foreach ( $dates as $date ) {

			// Overwrite Occurrence ID.
			$pledgeball_event['occurrence_id'] = $date['occurrence_id'];

			// Overwrite dates.
			// TODO: Use GMT for storage.
			$start_date = new DateTime( $date['start'] );
			$pledgeball_event['eventdate'] = $start_date->format( 'Y-m-d\TH:i:s' );
			$end_date = new DateTime( $date['end'] );
			$pledgeball_event['enddate'] = $end_date->format( 'Y-m-d\TH:i:s' );

			// Add completed Event to return array.
			$pledgeball_events[] = $pledgeball_event;

		}

		// --<
		return $pledgeball_events;

	}

	/**
	 * Prepares the Pledgeball Location data for a given EO Venue.
	 *
	 * @since 1.0
	 *
	 * @param object $venue The Venue object.
	 * @return array $location The array of Pledgeball Location data.
	 */
	public function pledgeball_location_prepare( $venue ) {

		// Init return.
		$location = [];

		// Bail if Venue is empty.
		if ( empty( $venue ) ) {
			return $location;
		}

		// Convert whatever we can.
		$location['location'] = isset( $venue->name ) ? $venue->name : '';

		// Try and get Country code.
		$country_code = '';
		$country_name = isset( $venue->country ) ? $venue->country : '';
		if ( ! empty( $country_name ) ) {
			$country = $this->plugin->civicrm->country_get_by_name( $country_name );
			if ( ! empty( $country ) ) {
				$country_code = isset( $country['iso_code'] ) ? $country['iso_code'] : '';
			}
		}

		// Make sure 3-letter ISO Country code is empty.
		$location['countrycode'] = '';

		// Apply 2-letter ISO Country code.
		$location['countrycode2'] = $country_code;

		// Apply latitude and longitude.
		$location['latitude'] = isset( $venue->lat ) ? $venue->lat : '';
		$location['longitude'] = isset( $venue->lng ) ? $venue->lng : '';

		// --<
		return $location;

	}

}
