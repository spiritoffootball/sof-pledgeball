<?php
/**
 * "Submit Pledge" Form Class.
 *
 * Handles "Submit Pledge" Form functionality.
 *
 * @package SOF_Pledgeball
 * @since 1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * "Submit Pledge" Form Class.
 *
 * A class that encapsulates "Submit Pledge" Form functionality.
 *
 * @since 1.0
 */
class SOF_Pledgeball_Form_Pledge_Submit {

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
	 * POST Nonce action.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $nonce_action The Nonce action.
	 */
	private $nonce_action = 'sof_pledge_submit_action';

	/**
	 * POST Nonce name.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $nonce_name The Nonce name.
	 */
	private $nonce_name = 'sof_pledge_submit_nonce';

	/**
	 * AJAX Nonce name.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $nonce_ajax The Nonce name.
	 */
	private $nonce_ajax = 'sof_pledge_submit_ajax';

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
		do_action( 'sof_pledgeball/form/pledge_submit/init' );

	}

	/**
	 * Registers hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

		// Register Shortcodes.
		add_action( 'init', [ $this, 'shortcode_register' ] );

		// Register Form handlers.
		add_action( 'wp_ajax_sof_pledgeball_pledge_submit', [ $this, 'form_submitted_ajax' ] );
		add_action( 'wp_ajax_nopriv_sof_pledgeball_pledge_submit', [ $this, 'form_submitted_ajax' ] );
		add_action( 'init', [ $this, 'form_submitted_post' ], 1000 );

	}

	/**
	 * Register our Shortcode.
	 *
	 * @since 1.0
	 */
	public function shortcode_register() {

		// Register Shortcode.
		add_shortcode( 'sof_pledgeball_pledge_form', [ $this, 'form_render' ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Adds the "Submit Pledge" Form to a Page via a Shortcode.
	 *
	 * @since 1.0
	 *
	 * @param array $attr The saved Shortcode attributes.
	 * @param str $content The enclosed content of the Shortcode.
	 * @return str $markup The HTML markup for the Shortcode.
	 */
	public function form_render( $attr, $content = null ) {

		// Init return.
		$markup = '';

		// Check attributes for the WordPress Event.
		$event_id = isset( $attr['event'] ) ? (int) $attr['event'] : 0;
		$event_country = isset( $attr['country'] ) ? $attr['country'] : '';

		// Bail if we didn't get an Event Organiser Event ID.
		if ( empty( $event_id ) ) {
			$markup .= '<p>' . __( 'Event not recognized.', 'sof-pledgeball' ) . '</p>' . "\n";
			return $markup;
		}

		// Try and get the Pledgeball Event ID.
		$pledgeball_event_ids = $this->plugin->event->pledgeball_meta_get( $event_id );

		// Bail if we didn't get any Pledgeball Event IDs.
		if ( empty( $pledgeball_event_ids ) ) {
			$markup .= '<p>' . __( 'Sorry, something went wrong. Event not recognized.', 'sof-pledgeball' ) . '</p>' . "\n";
			return $markup;
		}

		// Use the first one because we can't do repeating Events yet.
		$pledgeball_event_id = array_pop( $pledgeball_event_ids );

		// Build transient key.
		$transient_key = 'sof_pledgeball_definitions';
		if ( ! empty( $event_country ) ) {
			$transient_key .= '_' . $event_country;
		}

		// First check our transient for the data.
		$pledges = get_site_transient( $transient_key );

		// Query again if it's not found.
		if ( $pledges === false ) {

			// Define params to get the Spirit of Football Pledges.
			$args = [ 'eventgroup' => SOF_PLEDGEBALL_EVENT_GROUP_ID ];
			if ( ! empty( $event_country ) ) {
				$args['countrycode2'] = $event_country;
			}

			// Get all relevant Pledge definitions.
			$pledges = $this->plugin->pledgeball->remote->definitions_get_all( $args );

			// How did we do?
			if ( ! empty( $pledges ) ) {
				// Store for a day given how infrequently Pledge definitions are modified.
				set_site_transient( $transient_key, $pledges, DAY_IN_SECONDS );
			} else {
				// We got an error and want to try again.
				delete_site_transient( $transient_key );
			}

		}

		// Bail if we didn't get any results.
		if ( empty( $pledges ) ) {
			$markup .= '<p>' . __( 'Sorry, something went wrong. Please reload and try again.', 'sof-pledgeball' ) . '</p>' . "\n";
			return $markup;
		}

		// Let's build an array keyed by Category.
		$build = [];
		foreach ( $pledges as $pledge ) {

			$input = '<input type="checkbox" class="pledge_checkbox" name="pledgeball_ids[]" id="pledgeball_id_' . esc_attr( $pledge->Number ) . '" value="' . esc_attr( $pledge->Number ) . '">';
			$label = '<label for="pledgeball_id_' . esc_attr( $pledge->Number ) . '">' . esc_html( $pledge->Description ) . '</label>';

			$saving = '';
			if ( ! empty( $pledge->KgCO2e ) && $pledge->KgCO2e != '-1' ) {
				$saving = ' <span>' . sprintf(
					/* translators: %s The number of kilogrammes. */
					__( 'Saves %s kg of CO<sub>2</sub>e per year.', 'sof-pledgeball' ),
					'<span class="pledge_kgco2e">' . esc_html( $pledge->KgCO2e ) . '</span>'
				) . '</span>';
			}
			if ( ! empty( $pledge->KgCO2e ) && $pledge->KgCO2e == '-1' ) {
				$saving = ' <span>' . __( 'Saves CO<sub>2</sub>e but hard to quantify.', 'sof-pledgeball' ) . '</span>';
			}

			$context = '';
			if ( ! empty( $pledge->UsefulURL ) ) {
				$context_array = explode( ' ', $pledge->UsefulURL );
				$context_count = count( $context_array );
				if ( 1 === $context_count ) {
					$context .= ' <span>(<a href="' . esc_url( $pledge->UsefulURL ) . '" target="_blank">' . __( 'More information', 'sof-pledgeball' ) . '</a>)</span>';
				} else {
					$context .= ' <span>(' . __( 'More information', 'sof-pledgeball' );
					$counter = 1;
					foreach ( $context_array as $context_url ) {
						if ( $counter === $context_count ) {
							$context .= __( ' and', 'sof-pledgeball' );
						}
						$context .= ' <a href="' . esc_url( $context_url ) . '" target="_blank">' . __( 'here', 'sof-pledgeball' ) . '</a>';
						if ( $counter < $context_count - 1 ) {
							$context .= ',';
						}
						if ( $counter === $context_count ) {
							$context .= ')</span>';
						}
						$counter++;
					}
				}
			}

			$divider = '';
			if ( ! empty( $saving ) || ! empty( $context ) ) {
				$divider = '<br>';
			}

			$build[ esc_html( $pledge->Category ) ][] = $input . $label . $divider . $saving . $context;

		}

		ksort( $build );

		// Define Consent text.
		$consent = esc_html__( 'I consent to my details being stored by Spirit of Football and our partner Pledgeball (required)', 'sof-pledgeball' );

		/**
		 * Allow "Consent" text to be filtered.
		 *
		 * @since 1.0
		 *
		 * @param string $consent The default "Consent" text.
		 */
		$consent = apply_filters( 'sof_pledgeball/form/pledge_submit/consent_text', $consent );

		// Define Updates text.
		$updates = esc_html__( 'Tick to receive occasional updates about the impact of you and your fellow Pledgeballers (and if you like freebies). NB please tick even if you have already subscribed otherwise you will be unsubscribed.', 'sof-pledgeball' );

		/**
		 * Allow "Updates" text to be filtered.
		 *
		 * @since 1.0
		 *
		 * @param string $updates The default "Updates" text.
		 */
		$updates = apply_filters( 'sof_pledgeball/form/pledge_submit/updates_text', $updates );

		// Add styles.
		$markup .= $this->form_styles();

		// Start buffering.
		ob_start();

		// Now, instead of echoing, Shortcode output ends up in buffer.
		include SOF_PLEDGEBALL_PATH . 'assets/templates/forms/pledge-submit.php';

		// Save the output and flush the buffer.
		$markup .= ob_get_clean();

		// Enqueue Javascript.
		$this->form_scripts( $pledges );

		// --<
		return $markup;

	}

	/**
	 * Gets the basic styles for the Submit Pledge Form.
	 *
	 * @since 1.0
	 *
	 * @return str $styles The CSS for the Submit Pledge Form.
	 */
	public function form_styles() {

		// Define loading image URL.
		$loader_url = SOF_PLEDGEBALL_URL . 'assets/images/spinners/ajax-loader.gif';

		// Define styles.
		$styles = '<style>

			#pledge_submit {
				padding: 0;
			}

			#pledge_submit h3 {
				margin: 1em 0;
			}

			#pledge_submit .pledgeball_notice {
				color: #000;
				background: #fff;
				border: 1px solid #c3c4c7;
				border-left-width: 4px;
				box-shadow: 0 1px 1px rgb(0 0 0 / 4%);
				padding: 1px 12px;
			}

			#pledge_submit .pledgeball_message {
				border-left-color: #00a32a;
			}

			#pledge_submit .pledgeball_notice p {
				margin: 0.5em 0;
				padding: 2px;
				line-height: 1.5;
			}

			#pledge_submit .pledgeball_error {
				display: none;
				border-left-color: #d63638;
			}

			#pledge_submit fieldset {
				padding: 0;
				margin: 0 0 1em 0;
				border: none;
			}

			#pledge_submit .pledgeball_main_label {
				display: inline-block;
				width: 20%;
				margin-right: 2em;
			}

			#pledge_submit .pledgeball_main_input {
				width: 60%;
				box-shadow: 0 0 0 transparent;
				border-radius: 4px;
				border: 1px solid #8c8f94;
				background-color: #fff;
				color: #2c3338;
				padding: 0 8px;
				line-height: 2;
				min-height: 30px;
			}

			#pledge_submit .pledgeball_other_input {
				width: 90%;
			}

			#pledge_submit .pledgeball_pledges {
				height: 400px;
				overflow-x: scroll;
				padding: 1em;
				border: 1px solid #ddd;
			}

			#pledge_submit ul {
				list-style: none;
				padding-left: 0;
				margin-left: 1.6em;
			}

			#pledge_submit li {
				list-style: none;
				text-indent: -1.6em;
			}

			#pledge_submit h4 {
				border-top: 1px solid #ddd;
				padding-top: 1em;
			}

			#pledge_submit h4:first-child {
				border-top: none;
				margin-top: 0;
				padding-top: 0;
			}

			#pledge_submit input[type="checkbox"] {
				border: 1px solid #8c8f94;
				border-radius: 4px;
				background: #fff;
				color: #50575e;
				clear: none;
				cursor: pointer;
				display: inline-block;
				line-height: 0;
				height: 1rem;
				margin: -0.25rem 0.25rem 0 0;
				margin-right: 0.5em;
				outline: 0;
				padding: 0 !important;
				text-align: center;
				vertical-align: middle;
				width: 1rem;
				min-width: 1rem;
				-webkit-appearance: none;
				box-shadow: inset 0 1px 2px rgb(0 0 0 / 10%);
				transition: .05s border-color ease-in-out;
    		}

			#pledge_submit input[type="checkbox"]:checked::before {
				float: left;
				display: inline-block;
				vertical-align: middle;
				width: 1rem;
				speak: never;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
			}

			#pledge_submit input[type="checkbox"]:checked::before {
				/* Use the "Yes" SVG Dashicon */
				content: url("data:image/svg+xml;utf8,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20viewBox%3D%270%200%2020%2020%27%3E%3Cpath%20d%3D%27M14.83%204.89l1.34.94-5.81%208.38H9.02L5.78%209.67l1.34-1.25%202.57%202.4z%27%20fill%3D%27%233582c4%27%2F%3E%3C%2Fsvg%3E");
				margin: -0.1875rem 0 0 -0.25rem;
				height: 1.3125rem;
				width: 1.3125rem;
			}

			#pledge_submit input[type="checkbox"]:focus {
				border-color: #2271b1;
				box-shadow: 0 0 0 1px #2271b1;
				/* Only visible in Windows High Contrast mode */
				outline: 2px solid transparent;
			}

			#pledge_submit input[type="checkbox"]:disabled,
			#pledge_submit input[type="checkbox"].disabled,
			#pledge_submit input[type="checkbox"]:disabled:checked:before,
			#pledge_submit input[type="checkbox"].disabled:checked:before {
				opacity: 0.7;
			}

			#pledge_submit ul label {
				font-weight: bold;
			}

			#pledge_submit .pledgeball_updates {
				margin-left: 1.8em;
				text-indent: -1.8em;
			}

			#pledge_submit #pledge_submit_button {
				padding: 0.5em;
			}

			#pledge_submit .spinner {
				background: url(' . $loader_url . ') no-repeat;
				background-size: 20px 20px;
				display: inline-block;
				visibility: hidden;
				vertical-align: middle;
				opacity: 0.7;
				filter: alpha(opacity=70);
				width: 20px;
				height: 20px;
				margin: -2px 10px 0;
			}

			@media print,
			(-webkit-min-device-pixel-ratio: 1.25),
			(min-resolution: 120dpi) {
				#pledge_submit .spinner {
					background-image: url(' . $loader_url . ');
				}
			}

			.pledgeball_user_feedback {
				display: none;
				position: fixed;
				z-index: 1000;
				bottom: 30px;
				right: 30px;
				color: #000;
				padding: 0.3em;
				background-color: #ffcc00;
				border: 2px solid #000;
				border-radius: 4px;
				font-size: 200%;
				line-height: 1.3;
				text-align: center;
				min-width: 195px;
   			}

			span.pledgeball_user_intro,
			span.pledgeball_user_outro
			{
				display: block;
				font-size: 50%;
				color: #222;
			}

			span.pledgeball_user_intro
			{
				text-transform: uppercase;
			}

		</style>' . "\n";

		/**
		 * Allow styles to be filtered.
		 *
		 * @since 1.0
		 *
		 * @param string $styles The default styles.
		 */
		return apply_filters( 'sof_pledgeball/form/pledge_submit/styles', $styles );

	}

	/**
	 * Enqueue the necessary scripts.
	 *
	 * @since 1.0
	 *
	 * @param array $pledges The array of all possible Pledges.
	 */
	public function form_scripts( $pledges ) {

		// Enqueue custom javascript.
		wp_enqueue_script(
			'pledge-submit-js',
			SOF_PLEDGEBALL_URL . 'assets/js/pledge-submit.js',
			[ 'jquery' ],
			SOF_PLEDGEBALL_VERSION,
			true // In footer.
		);

		// Init localisation.
		$localisation = [
			'field_required' => __( 'Please complete the fields marked in red.', 'sof-pledgeball' ),
			'pledge_required' => __( 'Please choose at least one Pledge.', 'sof-pledgeball' ),
			'submit' => __( 'Submit Pledge', 'sof-pledgeball' ),
			'submitting' => __( 'Submitting...', 'sof-pledgeball' ),
		];

		// Init settings.
		$settings = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'pledges' => $pledges,
			'scrollto' => '#pledge_submit',
		];

		// Build vars array.
		$vars = [
			'localisation' => $localisation,
			'settings' => $settings,
		];

		/**
		 * Allow vars to be filtered.
		 *
		 * @since 1.0
		 *
		 * @param array $vars The Javascript variables passed to the script.
		 */
		$vars = apply_filters( 'sof_pledgeball/form/pledge_submit/scripts/vars', $vars );

		// Localise the WordPress way.
		wp_localize_script(
			'pledge-submit-js',
			'Pledgeball_Form_Pledge_Submit_Settings',
			$vars
		);

	}

	// -------------------------------------------------------------------------

	/**
	 * Called when the "Submit Pledge" Form is submitted with Javascript.
	 *
	 * @since 1.0
	 */
	public function form_submitted_ajax() {

		// Default response.
		$data = [
			'notice' => __( 'Could not submit the Pledge. Please try again.', 'sof-pledgeball' ),
			'saved' => false,
		];

		// Skip if not AJAX submission.
		if ( ! wp_doing_ajax() ) {
			wp_send_json( $data );
		}

		// Since this is an AJAX request, check security.
		$result = check_ajax_referer( $this->nonce_ajax, false, false );
		if ( $result === false ) {
			$data['notice'] = __( 'Authentication failed. Could not submit the Pledge.', 'sof-pledgeball' );
			wp_send_json( $data );
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'POST' => $_POST,
			//'backtrace' => $trace,
		] );
		*/

		// Extract Event Organiser "Event ID".
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$eo_event_id = isset( $_POST['eo_event_id'] ) ? (int) trim( wp_unslash( $_POST['eo_event_id'] ) ) : 0;
		if ( empty( $eo_event_id ) ) {
			$data['notice'] = __( 'Event not recognized.', 'sof-pledgeball' );
			wp_send_json( $data );
		}

		// Extract Pledgeball "Event ID".
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$event_id = isset( $_POST['event_id'] ) ? (int) trim( wp_unslash( $_POST['event_id'] ) ) : 0;
		if ( empty( $event_id ) ) {
			$data['notice'] = __( 'Pledgeball Event not recognized.', 'sof-pledgeball' );
			wp_send_json( $data );
		}

		// Extract "First Name".
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$first_name_raw = isset( $_POST['first_name'] ) ? trim( wp_unslash( $_POST['first_name'] ) ) : '';
		$first_name = sanitize_text_field( $first_name_raw );
		if ( empty( $first_name ) ) {
			$data['notice'] = __( 'Please enter a First Name.', 'sof-pledgeball' );
			wp_send_json( $data );
		}

		// Extract "Last Name".
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$last_name_raw = isset( $_POST['last_name'] ) ? trim( wp_unslash( $_POST['last_name'] ) ) : '';
		$last_name = sanitize_text_field( $last_name_raw );
		if ( empty( $last_name ) ) {
			$data['notice'] = __( 'Please enter a Last Name.', 'sof-pledgeball' );
			wp_send_json( $data );
		}

		// Extract Email.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$email_raw = isset( $_POST['email'] ) ? trim( wp_unslash( $_POST['email'] ) ) : '';
		$email = sanitize_email( $email_raw );
		if ( empty( $email ) || ! is_email( $email ) ) {
			$data['notice'] = __( 'Please enter a valid Email Address.', 'sof-pledgeball' );
			wp_send_json( $data );
		}

		// Extract Pledge IDs.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$pledge_ids = isset( $_POST['pledge_ids'] ) ? stripslashes_deep( $_POST['pledge_ids'] ) : [];
		array_walk( $pledge_ids, function( &$item ) {
			$item = (int) trim( $item );
		} );
		if ( empty( $pledge_ids ) ) {
			$data['notice'] = __( 'Please choose at least one Pledge.', 'sof-pledgeball' );
			wp_send_json( $data );
		}

		// Extract Consent.
		$consent = false;
		if ( isset( $_POST['consent'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$consent_raw = wp_unslash( $_POST['consent'] );
			if ( $consent_raw === 'true' ) {
				$consent = true;
			}
		}
		if ( $consent === false ) {
			$data['notice'] = __( 'Cannot submit your Pledge unless you consent to us storing your data.', 'sof-pledgeball' );
			wp_send_json( $data );
		}

		// Extract Mailing List.
		$okemails = 0;
		if ( isset( $_POST['okemails'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$okemails_raw = wp_unslash( $_POST['okemails'] );
			if ( $okemails_raw === 'true' ) {
				$okemails = 1;
			}
		}

		// Extract "Other" value.
		$other_pledge = '';
		if ( isset( $_POST['other'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$other_pledge_raw = trim( wp_unslash( $_POST['other'] ) );
			$other_pledge = sanitize_text_field( $other_pledge_raw );
		}

		// Let's format the Pledges properly.
		$pledges = [];
		foreach ( $pledge_ids as $pledge_id ) {
			// Maybe apply the "Other" value.
			$other_value = '';
			if ( $pledge_id === 66 ) {
				$other_value = $other_pledge;
			}
			// Apply formatting.
			$pledges[] = [
				'pledgenumber' => $pledge_id,
				'other' => $other_value,
			];
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'event_id' => $event_id,
			'eventgroup' => SOF_PLEDGEBALL_EVENT_GROUP_ID,
			'first_name' => $first_name,
			'last_name' => $last_name,
			'email' => $email,
			'pledge_ids' => $pledge_ids,
			'pledges' => $pledges,
			'other' => $other_pledge,
			'consent' => $consent ? 'y' : 'n',
			'okemails' => $okemails === 1 ? 'y' : 'n',
			//'backtrace' => $trace,
		] );
		*/

		// Let's make an array of submission data.
		$submission = [
			'eventid' => $event_id,
			'eventgroup' => SOF_PLEDGEBALL_EVENT_GROUP_ID,
			'firstname' => $first_name,
			'lastname' => $last_name,
			'email' => $email,
			'pledges' => $pledges,
			'okemails' => $okemails,
		];

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'submission' => $submission,
			//'backtrace' => $trace,
		] );
		*/

		// Submit the Standalone Pledge.
		if ( false === SOF_PLEDGEBALL_SKIP_SUBMIT ) {
			$response = $this->plugin->pledgeball->remote->pledge_create( $submission );
		} else {
			$response = false;
		}

		// Add the Event Organiser Event ID.
		$submission['eo_event_id'] = (int) $eo_event_id;

		/**
		 * Broadcast that a submission has been completed.
		 *
		 * @since 1.0
		 *
		 * @param array $submission The submitted data.
		 * @param array $response The response from the server.
		 */
		do_action( 'sof_pledgeball/form/pledge_submit/submission', $submission, $response );

		/**
		 * Filters the response when a submission has been completed.
		 *
		 * @since 1.0
		 *
		 * @param array $response The response from the server.
		 * @param array $submission The submitted data.
		 */
		$response = apply_filters( 'sof_pledgeball/form/pledge_submit/response', $response, $submission );

		// Bail with default message.
		if ( $response === false ) {
			wp_send_json( $data );
		}

		// Default message.
		$message = '<p class="pledgeball_thanks">' . __( 'Your pledge has been submitted. Thanks for taking part!', 'sof-pledgeball' ) . '</p>';
		$message .= '<p class="pledgeball_reload">' . __( 'Reload the page to make another pledge.', 'sof-pledgeball' ) . '</p>';

		/**
		 * Allow message to be filtered.
		 *
		 * @since 1.0
		 *
		 * @param string $message The default message.
		 */
		$message = apply_filters( 'sof_pledgeball/form/pledge_submit/submission/message', $message );

		// Data response.
		$data = [
			'message' => $message,
			'saved' => true,
		];

		// Return the data.
		wp_send_json( $data );

	}

	/**
	 * Called when the "Submit Pledge" Form is submitted without Javascript.
	 *
	 * @since 1.0
	 */
	public function form_submitted_post() {

		// Skip if AJAX submission.
		if ( wp_doing_ajax() ) {
			return;
		}

		// Skip if no form nonce.
		if ( ! isset( $_POST[ $this->nonce_name ] ) ) {
			return;
		}

		// Skip if nonce verification fails.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! wp_verify_nonce( wp_unslash( $_POST[ $this->nonce_name ] ), $this->nonce_action ) ) {
			$this->form_redirect( [ 'failure' => 'no-auth' ] );
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'POST' => $_POST,
			//'backtrace' => $trace,
		] );
		*/

		// Extract Event Organiser "Event ID".
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$eo_event_id = isset( $_POST['pledgeball_eo_event_id'] ) ? (int) trim( wp_unslash( $_POST['pledgeball_eo_event_id'] ) ) : 0;
		if ( empty( $eo_event_id ) ) {
			$this->form_redirect( [ 'failure' => 'no-event' ] );
		}

		// Extract "Pledgeball Event ID".
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$event_id = isset( $_POST['pledgeball_event_id'] ) ? (int) trim( wp_unslash( $_POST['pledgeball_event_id'] ) ) : 0;
		if ( empty( $event_id ) ) {
			$this->form_redirect( [ 'failure' => 'no-event' ] );
		}

		// Extract "First Name".
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$first_name_raw = isset( $_POST['pledgeball_first_name'] ) ? trim( wp_unslash( $_POST['pledgeball_first_name'] ) ) : '';
		$first_name = sanitize_text_field( $first_name_raw );
		if ( empty( $first_name ) ) {
			$this->form_redirect( [ 'failure' => 'no-first-name' ] );
		}

		// Extract "Last Name".
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$last_name_raw = isset( $_POST['pledgeball_last_name'] ) ? trim( wp_unslash( $_POST['pledgeball_last_name'] ) ) : '';
		$last_name = sanitize_text_field( $last_name_raw );
		if ( empty( $last_name ) ) {
			$this->form_redirect( [ 'failure' => 'no-last-name' ] );
		}

		// Extract Email.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$email_raw = isset( $_POST['pledgeball_email'] ) ? trim( wp_unslash( $_POST['pledgeball_email'] ) ) : '';
		$email = sanitize_email( $email_raw );
		if ( empty( $email ) ) {
			$this->form_redirect( [ 'failure' => 'no-email' ] );
		}

		// Extract Pledge IDs.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$pledge_ids = isset( $_POST['pledgeball_ids'] ) ? stripslashes_deep( $_POST['pledgeball_ids'] ) : [];
		if ( empty( $pledge_ids ) ) {
			$this->form_redirect( [ 'failure' => 'no-pledges' ] );
		}
		array_walk( $pledge_ids, function( &$item ) {
			$item = (int) trim( $item );
		} );

		// Extract Consent.
		$consent = false;
		if ( isset( $_POST['pledgeball_consent'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$consent_raw = (int) wp_unslash( $_POST['pledgeball_consent'] );
			if ( $consent_raw === 1 ) {
				$consent = true;
			}
		}
		if ( $consent === false ) {
			$this->form_redirect( [ 'failure' => 'no-consent' ] );
		}

		// Extract Mailing List.
		$okemails = 0;
		if ( isset( $_POST['pledgeball_updates'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$okemails_raw = wp_unslash( $_POST['pledgeball_updates'] );
			if ( 1 === (int) trim( $okemails_raw ) ) {
				$okemails = 1;
			}
		}

		// Extract "Other" value.
		$other_pledge = '';
		if ( isset( $_POST['pledgeball_other'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$other_pledge_raw = trim( wp_unslash( $_POST['pledgeball_other'] ) );
			$other_pledge = sanitize_text_field( $other_pledge_raw );
		}

		// Let's format the Pledges properly.
		$pledges = [];
		foreach ( $pledge_ids as $pledge_id ) {
			// Maybe apply the "Other" value.
			$other_value = '';
			if ( $pledge_id === 66 ) {
				$other_value = $other_pledge;
			}
			// Apply formatting.
			$pledges[] = [
				'pledgenumber' => $pledge_id,
				'other' => $other_value,
			];
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'eventid' => $event_id,
			'first_name' => $first_name,
			'last_name' => $last_name,
			'email' => $email,
			'pledges' => $pledges,
			'consent' => $consent ? 'y' : 'n',
			'okemails' => $okemails === 1 ? 'y' : 'n',
			//'backtrace' => $trace,
		] );
		*/

		// Let's make an array of submission data.
		$submission = [
			'eventid' => $event_id,
			'eventgroup' => SOF_PLEDGEBALL_EVENT_GROUP_ID,
			'firstname' => $first_name,
			'lastname' => $last_name,
			'email' => $email,
			'pledges' => $pledges,
			'okemails' => $okemails,
		];

		// Submit the Pledge.
		if ( false === SOF_PLEDGEBALL_SKIP_SUBMIT ) {
			$response = $this->plugin->pledgeball->remote->pledge_create( $submission );
		} else {
			$response = false;
		}

		// Add the Event Organiser Event ID.
		$submission['eo_event_id'] = (int) $eo_event_id;

		/**
		 * Broadcast that a submission has been completed.
		 *
		 * @since 1.0
		 *
		 * @param array $submission The submitted data.
		 * @param array $response The response from the server.
		 */
		do_action( 'sof_pledgeball/form/pledge_submit/submission', $submission, $response );

		/**
		 * Filters the response when a submission has been completed.
		 *
		 * @since 1.0
		 *
		 * @param array $response The response from the server.
		 * @param array $submission The submitted data.
		 */
		$response = apply_filters( 'sof_pledgeball/form/pledge_submit/response', $response, $submission );

		// Bail with message.
		if ( $response === false ) {
			$this->form_redirect( [ 'failure' => 'no-response' ] );
		}

		// Our array of arguments.
		$args = [
			'submitted' => 'true',
		];

		// Redirect.
		$this->form_redirect( $args );

	}

	/**
	 * Redirects after the "Submit Pledge" Form is submitted.
	 *
	 * @since 1.0
	 *
	 * @param array $args The query args.
	 */
	public function form_redirect( $args = [] ) {

		// Get the submitted URL.
		$url = wp_get_raw_referer();

		// Remove existing.
		$url = remove_query_arg( [ 'submitted', 'failure' ], $url );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$this->plugin->log_error( [
			'method' => __METHOD__,
			'url' => $url,
			'args' => $args,
			'wp_get_referer' => wp_get_referer(),
			//'backtrace' => $trace,
		] );
		*/

		// Redirect to prevent re-submission.
		if ( ! empty( $url ) ) {
			wp_safe_redirect( add_query_arg( $args, $url ) );
		} else {
			wp_safe_redirect( get_home_url() );
		}

		exit();

	}

}
