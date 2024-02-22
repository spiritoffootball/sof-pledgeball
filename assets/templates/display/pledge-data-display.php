<?php
/**
 * "Display Pledge Data" template.
 *
 * Handles markup for the "Display Pledge Data" insert.
 *
 * @package SOF_Pledgeball
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><section class="pledge-data-display">

	<div class="pledge-data-display-inner">

		<header class="pledge-data-header">
			<h2 class="pledge-data-title"><?php esc_html_e( 'The Story So Far', 'sof-pledgeball' ); ?></h2>
		</header><!-- .events-header -->

		<ul class="pledge-data-display-list clear">

			<li class="data-item data-item-1">
				<span class="data-item-number"><?php echo esc_html( $partners ); ?></span>
				<span class="data-item-text"><?php esc_html_e( 'Partners', 'sof-pledgeball' ); ?></span>
			</li>

			<li class="data-item data-item-2">
				<span class="data-item-number"><?php echo esc_html( $events ); ?></span>
				<span class="data-item-text"><?php esc_html_e( 'Events', 'sof-pledgeball' ); ?></span>
			</li>

			<?php
			/*
			?>
			<li class="data-item data-item-2">
				<span class="data-item-number"><?php echo esc_html( $pledges ); ?></span>
				<span class="data-item-text"><?php esc_html_e( 'Pledges', 'sof-pledgeball' ); ?></span>
			</li>
			<?php
			*/
			?>

			<li class="data-item data-item-3">
				<span class="data-item-number"><?php echo esc_html( $co2_saved ); ?></span>
				<span class="data-item-text"><?php /* translators: 1: The opening sub tag, 2: The closing sub tag. */ printf( esc_html__( 'kg/year CO%1$s2%2$se savings pledged', 'sof-pledgeball' ), '<sub>', '</sub>' ); ?></span>
			</li>

		</ul>

	</div>

</section>
