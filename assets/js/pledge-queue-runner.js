/**
 * Pledgeball Queue Runner Javascript.
 *
 * Implements functionality for the "Send Queued Pledges to Pledgeball" dashboard meta box.
 *
 * @package SOF_Pledgeball
 */

/**
 * Pass the jQuery shortcut in.
 *
 * @since 1.0
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Create Settings Object.
	 *
	 * @since 1.0
	 */
	function Pledgeball_Queue_Runner_Settings() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 1.0
		 */
		this.init = function() {

			// Init localisation.
			me.init_localisation();

			// Init settings.
			me.init_settings();

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 1.0
		 */
		this.dom_ready = function() {

		};

		// Init localisation array.
		me.localisation = [];

		/**
		 * Init localisation from settings object.
		 *
		 * @since 1.0
		 */
		this.init_localisation = function() {
			if ( 'undefined' !== typeof Pledgeball_Queue_Runner_Vars ) {
				me.localisation = Pledgeball_Queue_Runner_Vars.localisation;
			}
		};

		/**
		 * Getter for localisation.
		 *
		 * @since 1.0
		 *
		 * @param {String} The identifier for the desired localisation string.
		 * @return {String} The localised string.
		 */
		this.get_localisation = function( identifier ) {
			return me.localisation[identifier];
		};

		// Init settings array.
		me.settings = [];

		/**
		 * Init settings from settings object.
		 *
		 * @since 1.0
		 */
		this.init_settings = function() {
			if ( 'undefined' !== typeof Pledgeball_Queue_Runner_Vars ) {
				me.settings = Pledgeball_Queue_Runner_Vars.settings;
			}
		};

		/**
		 * Getter for retrieving a setting.
		 *
		 * @since 1.0
		 *
		 * @param {String} The identifier for the desired setting.
		 * @return The value of the setting.
		 */
		this.get_setting = function( identifier ) {
			return me.settings[identifier];
		};

	};

	/**
	 * Create Form Object.
	 *
	 * @since 1.0
	 */
	function Pledgeball_Queue_Runner_Form() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Form.
		 *
		 * This method should only be called once.
		 *
		 * @since 1.0
		 */
		this.init = function() {

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 1.0
		 */
		this.dom_ready = function() {

			// Set up methods.
			me.setup();
			me.listeners();

		};

		/**
		 * Set up Form instance.
		 *
		 * @since 1.0
		 */
		this.setup = function() {

			// Store references.
			me.submit_button = $('#sof-pledgeball-queue-runner-submit');
			me.spinner = me.submit_button.next( '.spinner' );

		};

		/**
		 * Initialise listeners.
		 *
		 * This method should only be called once.
		 *
		 * @since 1.0
		 */
		this.listeners = function() {

			/**
			 * Add a click event listener to the Submit button.
			 *
			 * @param {Object} event The event object.
			 */
			me.submit_button.on( 'click', function( event ) {

				// Define vars.
				var sending = Pledgeball_QR_Settings.get_localisation( 'sending' );

				// Prevent form submission.
				if ( event.preventDefault ) {
					event.preventDefault();
				}

				console.log( 'sending', sending );

				// Hide errors.
				$('.sof-pledgeball-queue-runner-error').hide();

				// Assign text to submit button.
				me.submit_button.val( sending );

				// Make form submit button disabled and show spinner.
				me.submit_button.prop( 'disabled', true );
				me.spinner.css( 'visibility', 'visible' );

				// Send the request to the server.
				me.send();

				// --<
				return false;

			});

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 1.0
		 */
		this.send = function() {

			// Define vars.
			var url = Pledgeball_QR_Settings.get_setting( 'ajax_url' ),
				ajax_nonce = me.submit_button.data( 'security' )
				data = {};

			// Data received by WordPress.
			data = {
				action: 'sof_pledgeball_queue_runner',
				_ajax_nonce: ajax_nonce
			};

			console.log( 'data', data );

			// Use jQuery post.
			$.post( url, data,

				/**
				 * AJAX callback which receives response from the server.
				 *
				 * Calls feedback method on success or shows an error in the console.
				 *
				 * @since 1.0
				 *
				 * @param {Mixed} response The received JSON data array.
				 * @param {String} textStatus The status of the response.
				 */
				function( response, textStatus ) {

					// Update if success, otherwise show error.
					if ( textStatus == 'success' ) {
						me.update( response );
					} else {
						if ( console.log ) {
							console.log( textStatus );
						}
					}

				},

				// Expected format.
				'json'

			);

		};

		/**
		 * Receive data from an AJAX request.
		 *
		 * @since 1.0
		 *
		 * @param {Array} data The data received from the server.
		 */
		this.update = function( data ) {

			var send = Pledgeball_QR_Settings.get_localisation( 'send' ),
				sent = Pledgeball_QR_Settings.get_localisation( 'sent' );

			console.log( 'data-returned', data );

			if ( data.saved ) {

				// Are we done?
				if ( 0 === parseInt( data.pledge_count ) ) {

					// Replace info.
					$('.sof-pledgeball-queue-info p').html( data.message );

					// Assign text to form submit button and hide spinner.
					me.submit_button.val( sent );
					me.spinner.css( 'visibility', 'hidden' );

				} else {

					// Update info.
					$('.sof-pledge-count').html( data.pledge_count );
					$('.sof-event-count').html( data.event_count );

					// Send again.
					me.send();

				}

			} else {

				// Show notice.
				$('.sof-pledgeball-queue-runner-error').html( '<p>' + data.notice + '</p>' );
				$('.sof-pledgeball-queue-runner-error').show();

				// Assign text to form submit button.
				me.submit_button.val( send );

				// Make form submit button enabled and hide spinner.
				me.submit_button.prop( 'disabled', false );
				me.spinner.css( 'visibility', 'hidden' );

			}

		};

	};

	// Init Settings and Form classes.
	var Pledgeball_QR_Settings = new Pledgeball_Queue_Runner_Settings();
	var Pledgeball_QR_Form = new Pledgeball_Queue_Runner_Form();
	Pledgeball_QR_Settings.init();
	Pledgeball_QR_Form.init();

	/**
	 * Trigger dom_ready methods where necessary.
	 *
	 * @since 1.0
	 */
	$(document).ready(function($) {
		Pledgeball_QR_Settings.dom_ready();
		Pledgeball_QR_Form.dom_ready();
	});

} )( jQuery );
