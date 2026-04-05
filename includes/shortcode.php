<?php
/**
 * Shortcode Module.
 *
 * Handles the display of the flex accordion menu via shortcode.
 * Optimized for AI-Vault Elite Standards, WPCS compliance, and rich functionality.
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
	$plugin_url = SPFA_URL;

	$sections = get_posts(
		array(
			'post_type'      => 'menu_section',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	if ( empty( $sections ) ) {
		return '<p>No menu sections found.</p>';
	}

	ob_start();
	?>
	<div class="spfa-main-wrapper">
		<div class="spfa-nav-grid">
			<?php
			foreach ( $sections as $s_index => $section ) :
				$color        = get_post_meta( $section->ID, 'spfa_color', true ) ?: '#A52A2A';
				$nav_subtitle = get_post_meta( $section->ID, 'spfa_nav_subtitle', true );
				$thumb        = get_the_post_thumbnail_url( $section->ID, 'large' ) ?: ( $plugin_url . 'assets/images/header.webp' );

				$section_items = get_posts(
					array(
						'post_type'      => 'menu_item',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'meta_query'     => array( array( 'key' => 'spfa_parent_section', 'value' => $section->ID ) ),
					)
				);

				$categories = array();
				if ( ! empty( $section_items ) ) {
					$terms = wp_get_object_terms( $section_items, 'menu_category' );
					if ( ! is_wp_error( $terms ) ) {
						usort(
							$terms,
							function ( $a, $b ) {
								return (int) get_term_meta( $a->term_id, 'spfa_order', true ) - (int) get_term_meta( $b->term_id, 'spfa_order', true );
							}
						);
						$categories = $terms;
					}
				}
				?>
				<div class="spfa-nav-column" style="--section-color: <?php echo esc_attr( $color ); ?>;">
					<div class="spfa-nav-card" data-section="<?php echo esc_attr( $section->post_name ); ?>">
						<div class="spfa-nav-visuals">
							<div class="spfa-nav-thumb-wrap">
								<img src="<?php echo esc_url( $thumb ); ?>" class="spfa-nav-thumb" alt="<?php echo esc_attr( $section->post_title ); ?>">
							</div>
							<div class="spfa-nav-info">
								<h3><?php echo wp_kses_post( $section->post_title ); ?></h3>
								<?php if ( $nav_subtitle ) : ?>
									<span class="spfa-title-sub"><?php echo esc_html( $nav_subtitle ); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<div class="spfa-nav-dropdown">
							<div class="spfa-tab-stack">
								<?php
								foreach ( $categories as $cat_index => $cat ) :
									if ( $cat->parent != 0 ) {
										continue;
									}
									?>
									<button class="spfa-tab-trigger <?php echo ( $s_index === 0 && $cat_index === 0 ) ? 'active' : ''; ?>" 
											data-section="<?php echo esc_attr( $section->post_name ); ?>" 
											data-category="<?php echo esc_attr( $cat->slug ); ?>">
										<?php echo esc_html( $cat->name ); ?>
									</button>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="spfa-sections-container">
			<?php
			foreach ( $sections as $s_index => $section ) :
				$color        = get_post_meta( $section->ID, 'spfa_color', true );
				$sec_subtitle = get_post_meta( $section->ID, 'spfa_subtitle', true );

				$cat_color = get_post_meta( $section->ID, 'spfa_catering_color', true ) ?: '#A98856';
				$cat_title = get_post_meta( $section->ID, 'spfa_catering_title', true );
				$cat_sub   = get_post_meta( $section->ID, 'spfa_catering_subtitle', true );
				$cat_btn   = get_post_meta( $section->ID, 'spfa_catering_btn_text', true ) ?: 'Start Your Catering Inquiry';
				$cat_link  = get_post_meta( $section->ID, 'spfa_catering_btn_link', true ) ?: '/contact-us/';

				$section_items_ids = get_posts(
					array(
						'post_type'      => 'menu_item',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'meta_query'     => array( array( 'key' => 'spfa_parent_section', 'value' => $section->ID ) ),
					)
				);
				$all_terms         = ! empty( $section_items_ids ) ? wp_get_object_terms( $section_items_ids, 'menu_category' ) : array();

				$tabs = array();
				foreach ( $all_terms as $t ) {
					if ( $t->parent == 0 ) {
						$tabs[] = $t;
					}
				}
				usort(
					$tabs,
					function ( $a, $b ) {
						return (int) get_term_meta( $a->term_id, 'spfa_order', true ) - (int) get_term_meta( $b->term_id, 'spfa_order', true );
					}
				);
				?>
				<div id="section-<?php echo esc_attr( $section->post_name ); ?>" class="spfa-menu-section <?php echo $s_index === 0 ? 'active' : ''; ?>" style="--section-color: <?php echo esc_attr( $color ); ?>;">
					<div class="spfa-section-header">
						<div class="spfa-header-text-wrap">
							<h2><?php echo wp_kses_post( $section->post_title ); ?></h2>
							<?php
							$sec_nav_sub = get_post_meta( $section->ID, 'spfa_nav_subtitle', true );
							if ( $sec_nav_sub ) :
								?>
								<p class="spfa-section-nav-subtitle"><?php echo esc_html( $sec_nav_sub ); ?></p>
							<?php endif; ?>
							<div class="spfa-section-line"></div>
						</div>
						<?php if ( $sec_subtitle ) : ?>
							<div class="spfa-section-subtitle"><?php echo wpautop( do_shortcode( $sec_subtitle ) ); ?></div>
						<?php endif; ?>
					</div>

					<?php foreach ( $tabs as $t_index => $tab ) : ?>
						<div id="cat-<?php echo esc_attr( $section->post_name ); ?>-<?php echo esc_attr( $tab->slug ); ?>" class="spfa-category-content <?php echo ( $s_index === 0 && $t_index === 0 ) ? 'active' : ''; ?>">
							<div class="spfa-subcategory-block">
								<div class="spfa-image2-header">
									<div class="spfa-decorative-line"></div>
									<h3 class="spfa-subcategory-title"><?php echo esc_html( $tab->name ); ?></h3>
									<div class="spfa-decorative-line"></div>
								</div>
								<?php
								$tab_desc    = get_term_meta( $tab->term_id, 'spfa_cat_description', true );
								$rich_groups = get_term_meta( $tab->term_id, 'spfa_rich_groups', true ) ?: array();

								$main_group_name = trim( $tab->name );
								$main_group_desc = isset( $rich_groups[ $main_group_name ] ) ? $rich_groups[ $main_group_name ] : '';

								if ( $tab_desc || $main_group_desc ) {
									echo '<div class="spfa-subcategory-desc">';
									if ( $tab_desc ) {
										echo wpautop( do_shortcode( $tab_desc ) );
									}
									if ( $main_group_desc ) {
										echo wpautop( do_shortcode( $main_group_desc ) );
									}
									echo '</div>';
								}

								$items = get_posts(
									array(
										'post_type'      => 'menu_item',
										'posts_per_page' => -1,
										'orderby'        => 'menu_order',
										'order'          => 'ASC',
										'tax_query'      => array(
											array(
												'taxonomy' => 'menu_category',
												'field'    => 'term_id',
												'terms'    => $tab->term_id,
											),
										),
										'meta_query'     => array( array( 'key' => 'spfa_parent_section', 'value' => $section->ID ) ),
									)
								);

								$chunks        = array();
								$current_group = null;
								$current_chunk = array();

								foreach ( $items as $item ) {
									$g_raw = trim( get_post_meta( $item->ID, 'spfa_item_group', true ) ) ?: 'default';
									$g     = ( sanitize_title( $g_raw ) === sanitize_title( $tab->name ) ) ? 'default' : $g_raw;

									if ( $g !== $current_group ) {
										if ( ! empty( $current_chunk ) ) {
											$chunks[] = array(
												'group' => $current_group,
												'items' => $current_chunk,
											);
										}
										$current_group = $g;
										$current_chunk = array( $item );
									} else {
										$current_chunk[] = $item;
									}
								}
								if ( ! empty( $current_chunk ) ) {
									$chunks[] = array(
										'group' => $current_group,
										'items' => $current_chunk,
									);
								}

								foreach ( $chunks as $chunk ) :
									$group_name     = $chunk['group'];
									$items_in_group = $chunk['items'];

									if ( $group_name !== 'default' ) :
										?>
										<div class="spfa-internal-group-header">
											<h4 class="spfa-internal-group-title"><?php echo esc_html( $group_name ); ?></h4>
											<?php if ( isset( $rich_groups[ $group_name ] ) && ! empty( $rich_groups[ $group_name ] ) ) : ?>
												<div class="spfa-internal-group-desc"><?php echo wpautop( do_shortcode( $rich_groups[ $group_name ] ) ); ?></div>
											<?php endif; ?>
										</div>
									<?php endif; ?>
									<div class="spfa-items-grid">
										<?php
										foreach ( $items_in_group as $item ) :
											$dish_subtitle = get_post_meta( $item->ID, 'spfa_item_subtitle', true );
											$details       = get_post_meta( $item->ID, 'spfa_item_details', true );
											$thumb         = get_the_post_thumbnail_url( $item->ID, 'medium_large' ) ?: ( $plugin_url . 'assets/images/header.webp' );
											?>
											<div class="spfa-dish-card">
												<div class="spfa-dish-top-layer">
													<a href="<?php echo esc_url( $thumb ); ?>" class="glightbox spfa-dish-img-link" data-glightbox="type: image">
														<div class="spfa-dish-img-wrap"><img src="<?php echo esc_url( $thumb ); ?>" class="spfa-dish-img" alt="<?php echo esc_attr( $item->post_title ); ?>"></div>
													</a>
													<div class="spfa-dish-title-wrap">
														<h4 class="spfa-dish-title"><?php echo esc_html( $item->post_title ); ?></h4>
														<?php if ( $dish_subtitle ) : ?>
															<span class="spfa-dish-subtitle"><?php echo esc_html( $dish_subtitle ); ?></span>
														<?php endif; ?>
													</div>
												</div>
												<div class="spfa-dish-bottom-layer">
													<div class="spfa-dish-content">
														<div class="spfa-dish-description"><?php echo wpautop( wp_kses_post( $item->post_content ) ); ?></div>
														<?php if ( $details ) : ?>
															<div class="spfa-dish-ingredients">
																<?php echo wpautop( wp_kses_post( $details ) ); ?>
															</div>
														<?php endif; ?>
													</div>
												</div>
											</div>
										<?php endforeach; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
					<div class="spfa-footer-widgets">
						<?php
						$bev_text       = get_post_meta( $section->ID, 'spfa_beverages', true );
						$bev_img_custom = get_post_meta( $section->ID, 'spfa_beverages_img', true );
						$bev_img_url    = $bev_img_custom ?: ( $plugin_url . 'assets/images/beverages-1.webp' );
						?>
						<fieldset class="spfa-beverage-box">
							<legend>Beverages — Passport Beverage Bar</legend>
							<img src="<?php echo esc_url( $bev_img_url ); ?>" class="spfa-bev-img" alt="Beverages">
							<div class="spfa-bev-content">
								<?php if ( $bev_text ) : ?>
									<?php echo wpautop( do_shortcode( $bev_text ) ); ?>
								<?php else : ?>
									<h5>A global-inspired selection of refreshing drinks:</h5>
									<ul>
										<li>Mediterranean mint lemonade, Flavored or Unflavored Tea</li>
										<li>Seasonal fruit waters inspired by our travels</li>
										<li>International soda selection</li>
										<li>Still or sparkling mineral water</li>
									</ul>
								<?php endif; ?>
							</div>
						</fieldset>
						<div class="spfa-catering-box" style="background-color: <?php echo esc_attr( $cat_color ); ?> !important;">
							<div class="spfa-catering-title"><?php echo wpautop( do_shortcode( $cat_title ) ); ?></div>
							<div class="spfa-catering-desc"><?php echo wpautop( do_shortcode( $cat_sub ) ); ?></div>
							<button class="spfa-btn-dark" onclick="window.location.href='<?php echo esc_url( $cat_link ); ?>'"><?php echo esc_html( $cat_btn ); ?></button>
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
