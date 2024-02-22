<?php
/**
 * Organisation Class.
 *
 * Handles organisation-related functionality.
 *
 * This class interacts with Pledgeball Organisations.
 *
 * @package SOF_Pledgeball
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Organisation Class.
 *
 * A class that encapsulates Pledgeball Organisation-related functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball_Organisation {

	/**
	 * Plugin object.
	 *
	 * @since 1.0
	 * @access public
	 * @var SOF_Pledgeball
	 */
	public $plugin;

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

		// Bootstrap class.
		$this->include_files();
		$this->setup_objects();
		$this->register_hooks();

		/**
		 * Broadcast that this class is active.
		 *
		 * @since 1.0
		 */
		do_action( 'sof_pledgeball/organisation/init' );

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
	 * Counts the number of Partners.
	 *
	 * @since 1.0
	 *
	 * @return int $found_posts The number of Partners found.
	 */
	public function partners_count() {

		// Define query args.
		$partners_args = [
			'post_type'      => 'partner',
			'post_status'    => 'publish',
			'order'          => 'ASC',
			'orderby'        => 'title',
			'posts_per_page' => -1,
		];

		// The query.
		$partners = new WP_Query( $partners_args );

		// --<
		return $partners->found_posts;

	}

}
