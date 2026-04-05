<?php
/**
 * Professional Menu Importer Module.
 *
 * Provides a method to import demo data for testing.
 *
 * @package EWEB_Flex_Accordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SPFA_Importer
 */
class SPFA_Importer {

	/**
	 * Import generic demo menu items.
	 *
	 * @return void
	 */
	public static function import_demo_data() {
		// Only run if the custom post type exists.
		if ( ! post_type_exists( 'menu_section' ) ) {
			return;
		}

		// Example categories.
		$categories = array( 'Breakfast', 'Main Course', 'Beverages' );

		foreach ( $categories as $cat ) {
			// Check if already exists to avoid duplicates using a modern query.
			$query = new WP_Query(
				array(
					'post_type'              => 'menu_section',
					'title'                  => $cat,
					'posts_per_page'         => 1,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			if ( $query->have_posts() ) {
				continue;
			}

			// Create demo parent item.
			wp_insert_post(
				array(
					'post_title'   => $cat,
					'post_content' => 'Generic demo content for ' . $cat,
					'post_status'  => 'publish',
					'post_type'    => 'menu_section',
				)
			);
		}
	}
}
