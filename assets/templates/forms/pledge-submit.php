<?php
/**
 * "Submit Pledge" Form template.
 *
 * Handles markup for the "Submit Pledge" Form.
 *
 * @package SOF_Pledgeball
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Get submitted and failure variables.
$submitted = filter_input( INPUT_GET, 'submitted' );
$failure   = filter_input( INPUT_GET, 'failure' );

?>
<form id="pledge_submit" method="post" action="">

	<div class="pledge_submit_inner">

		<?php if ( ! empty( $submitted ) && 'true' === $submitted ) : ?>
			<div class="pledgeball_notice pledgeball_message">
				<p class="pledgeball_thanks"><?php esc_html_e( 'Your pledge has been submitted. Thanks for taking part!', 'sof-pledgeball' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $failure ) ) : ?>
			<div class="pledgeball_notice pledgeball_error" style="display: block;">
			<p>
			<?php if ( 'no-auth' === $failure ) : ?>
				<?php esc_html_e( 'Authentication failed. Could not submit the Pledge.', 'sof-pledgeball' ); ?>
			<?php else : ?>
				<?php if ( 'no-event' === $failure ) : ?>
					<?php esc_html_e( 'Event not recognized', 'sof-pledgeball' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Please complete all fields.', 'sof-pledgeball' ); ?>
				<?php endif; ?>
				</p>
			<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php wp_nonce_field( $this->nonce_action, $this->nonce_name, true ); ?>
		<?php wp_original_referer_field(); ?>

		<input type="hidden" id="pledgeball_eo_event_id" name="pledgeball_eo_event_id" value="<?php echo esc_attr( $event_id ); ?>">
		<?php if ( ! empty( $pledgeball_event_id ) ) : ?>
			<input type="hidden" id="pledgeball_event_id" name="pledgeball_event_id" value="<?php echo esc_attr( $pledgeball_event_id ); ?>">
		<?php else : ?>
			<input type="hidden" id="pledgeball_event_id" name="pledgeball_event_id" value="0">
		<?php endif; ?>

		<fieldset>
			<h3><?php esc_html_e( 'Your Details', 'sof-pledgeball' ); ?></h3>
			<p>
				<label class="pledgeball_main_label" for="pledgeball_first_name"><?php esc_html_e( 'First Name', 'sof-pledgeball' ); ?></label>
				<input type="text" class="pledgeball_main_input" name="pledgeball_first_name" id="pledgeball_first_name" value="">
			</p>
			<p>
				<label class="pledgeball_main_label" for="pledgeball_last_name"><?php esc_html_e( 'Last Name', 'sof-pledgeball' ); ?></label>
				<input type="text" class="pledgeball_main_input" name="pledgeball_last_name" id="pledgeball_last_name" value="">
			</p>
			<p>
				<label class="pledgeball_main_label" for="pledgeball_email"><?php esc_html_e( 'Email Address', 'sof-pledgeball' ); ?></label>
				<input type="email" class="pledgeball_main_input pledgeball_input_email" name="pledgeball_email" id="pledgeball_email" value="">
			</p>
		</fieldset>

		<fieldset>
			<h3><?php esc_html_e( 'Choose Your Pledges', 'sof-pledgeball' ); ?></h3>
			<p><?php esc_html_e( 'Thank you for supporting Spirit of Football and Pledgeball - and helping the planet too!', 'sof-pledgeball' ); ?></p>
			<div class="pledgeball_pledges">
				<?php foreach ( $build as $heading => $items ) : ?>
					<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
					<h4><?php echo $heading; ?></h4>
					<ul>
						<?php foreach ( $items as $item ) : ?>
							<li>
								<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
								<?php echo $item; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endforeach; ?>
			</div>
		</fieldset>

		<fieldset>
			<h3><?php esc_html_e( 'Almost There', 'sof-pledgeball' ); ?></h3>
			<p>
				<input type="checkbox" class="pledge_submit_consent" name="pledgeball_consent" id="pledgeball_consent" value="1">
				<label for="pledgeball_consent"><?php echo esc_html( $consent ); ?></label>
			</p>
			<p class="pledgeball_updates">
				<input type="checkbox" class="pledge_submit_consent" name="pledgeball_updates" id="pledgeball_updates" value="1">
				<label for="pledgeball_updates"><?php echo esc_html( $updates ); ?></label>
			</p>
		</fieldset>

		<div class="pledgeball_notice pledgeball_error"></div>

		<p class="pledge_submit_button">
			<input type="submit" value="<?php echo esc_html__( 'Submit Pledge', 'sof-pledgeball' ); ?>" id="pledge_submit_button" data-security="<?php echo esc_attr( wp_create_nonce( $this->nonce_ajax ) ); ?>">
			<span class="spinner"></span>
		</p>

	</div>

</form>

<div class="pledgeball_user_feedback">
	<span>
		<span class="pledgeball_user_intro"><?php esc_html_e( 'Total Pledged', 'sof-pledgeball' ); ?></span>
		<?php

		printf(
			/* translators: %s: The number of kilogrammes saved */
			esc_html__( '%s kg', 'sof-pledgeball' ),
			'<span class="pledgeball_user_total">0</span>'
		);

		?>
		<span class="pledgeball_user_outro">
		<?php

		printf(
			/* translators: 1: The opening sub tag, 2: The closing sub tag */
			esc_html__( 'CO%1$s2%2$se per year', 'sof-pledgeball' ),
			'<sub>',
			'</sub>'
		);

		?>
		</span>
	</span>
</div>
