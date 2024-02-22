<?php
/**
 * "Pledgeball Queue Runner" Dashboard meta box template.
 *
 * Handles markup for the "Pledgeball Queue Runner" Dashboard meta box.
 *
 * @package SOF_Pledgeball
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/metaboxes/pledge-queue-runner.php -->
<form name="sof-pledgeball-queue-runner" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="post" id="sof-pledgeball-queue-runner" class="initial-form">

	<?php wp_nonce_field( $this->nonce_action, $this->nonce_name ); ?>

	<div class="sof-pledgeball-queue-runner-error notice notice-error inline" style="background-color: #f7f7f7;<?php echo esc_attr( $error_css ); ?>">
		<p><?php echo esc_html( $error ); ?></p>
	</div>

	<div class="sof-pledgeball-queue-info">
		<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
		<p><?php echo $info; ?></p>
	</div>

	<p class="submit">
		<?php submit_button( esc_html__( 'Send', 'sof-pledgeball' ), 'primary', 'sof-pledgeball-queue-runner-submit', false, $options ); ?>
		<span class="spinner"></span>
		<br class="clear" />
	</p>

</form>
