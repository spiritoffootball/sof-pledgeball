<?php
/**
 * "Pledge Info" Dashboard meta box template.
 *
 * Handles markup for the "Pledge Info" Dashboard meta box.
 *
 * @package SOF_Pledgeball
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/metaboxes/pledge-info.php -->
<div class="sof-pledgeball-info">
	<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
	<p><?php echo $info; ?></p>
	<?php if ( ! empty( $data ) ) : ?>
		<ul>
		<?php foreach ( $data as $item ) : ?>
			<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
			<li><?php echo $item; ?></li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
