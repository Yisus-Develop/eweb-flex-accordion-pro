<?php
/**
 * Shortcode Module.
 *
 * Handles the display of the flex accordion menu via shortcode.
 * (Restored for Original ACF Compatibility).
 *
 * @package EWEB_Flex_Accordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Shortcode function.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function spfa_menu_shortcode( $atts ) {
	$plugin_url = plugin_dir_url( __DIR__ );

	$sections = get_posts( array(
		'post_type'      => 'menu_section',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	) );

	if ( empty( $sections ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="spfa-main-wrapper">
		<div class="spfa-nav-grid">
			<?php foreach ( $sections as $s_index => $section ) : 
				$color = get_post_meta( $section->ID, 'spfa_color', true ) ?: '#A52A2A';
				$thumb = get_the_post_thumbnail_url( $section->ID, 'large' ) ?: ( $plugin_url . 'assets/images/header.webp' );
				?>
				<div class="spfa-nav-column" style="--section-color: <?php echo esc_attr( $color ); ?>;">
					<div class="spfa-nav-card" data-section="<?php echo esc_attr( $section->post_name ); ?>">
						<div class="spfa-nav-visuals">
							<div class="spfa-nav-thumb-wrap">
								<img src="<?php echo esc_url( $thumb ); ?>" class="spfa-nav-thumb">
							</div>
							<div class="spfa-nav-info">
								<h3><?php echo wp_kses_post( $section->post_title ); ?></h3>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'stamped_accordion_menu', 'spfa_menu_shortcode' );
add_shortcode( 'eweb_flex_accordion', 'spfa_menu_shortcode' );
add_shortcode( 'eweb_accordion_menu', 'spfa_menu_shortcode' );
add_shortcode( 'eweb_menu_accordion', 'spfa_menu_shortcode' );
