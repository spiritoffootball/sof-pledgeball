<?php
/**
 * Pledgeball Data Shortcode Class.
 *
 * Provides a Shortcode for rendering Pledgeball Data.
 *
 * @package SOF_Pledgeball
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Pledgeball Data Shortcode Class.
 *
 * A class that encapsulates a Shortcode for rendering Pledgeball Data.
 *
 * @since 1.0
 */
class SOF_Pledgeball_Shortcode_Data {

	/**
	 * Plugin object.
	 *
	 * @since 1.0
	 * @access public
	 * @var SOF_Pledgeball
	 */
	public $plugin;

	/**
	 * Shortcode object.
	 *
	 * @since 1.0
	 * @access public
	 * @var SOF_Pledgeball_Shortcode
	 */
	public $shortcode;

	/**
	 * Shortcode name.
	 *
	 * @since 1.0
	 * @access public
	 * @var string
	 */
	public $tag = 'sof_pledgeball_data';

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param object $parent The Shortcode object.
	 */
	public function __construct( $parent ) {

		// Store reference to Plugin object.
		$this->shortcode = $parent;
		$this->plugin    = $parent->plugin;

		// Init when the Shortcode class is loaded.
		add_action( 'sof_pledgeball/shortcode/init', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 1.0
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

		// Register Shortcode.
		add_action( 'init', [ $this, 'shortcode_register' ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Register our Shortcode.
	 *
	 * @since 1.0
	 */
	public function shortcode_register() {

		// Register our Shortcode and its callback.
		add_shortcode( $this->tag, [ $this, 'shortcode_render' ] );

	}

	/**
	 * Render the Shortcode.
	 *
	 * @since 1.0
	 *
	 * @param array  $attr The saved Shortcode attributes.
	 * @param string $content The enclosed content of the Shortcode.
	 * @param string $tag The Shortcode which invoked the callback.
	 * @return string $content The HTML-formatted Shortcode content.
	 */
	public function shortcode_render( $attr, $content = '', $tag = '' ) {

		// Return something else for feeds.
		if ( is_feed() ) {
			return '<p>' . __( 'Visit the website to see the summary of Pledgeball data.', 'sof-pledgeball' ) . '</p>';
		}

		// Get the Pledgeball Event data.
		$event_data = $this->plugin->event->pledgeball_data_get_all();
		if ( empty( $event_data ) ) {
			return '';
		}

		// Populate templates variables.
		$events = count( $event_data );

		$partners = $this->plugin->organisation->partners_count();

		$pledges = 0;
		foreach ( $event_data as $event ) {
			if ( ! empty( $event->NumberSubmissions ) ) {
				$pledges = $pledges + (int) $event->NumberSubmissions;
			}
		}

		$co2_saved = 0;
		foreach ( $event_data as $event ) {
			if ( ! empty( $event->TotalCO2 ) ) {
				$co2_saved = $co2_saved + (float) $event->TotalCO2;
			}
		}
		$co2_saved = number_format( round( $co2_saved ), 0, '.', __( ',', 'sof-pledgeball' ) );

		// Use the template.
		ob_start();
		include SOF_PLEDGEBALL_PATH . 'assets/templates/display/pledge-data-display.php';
		$content = ob_get_contents();
		ob_end_clean();

		// --<
		return $content;

	}

}
