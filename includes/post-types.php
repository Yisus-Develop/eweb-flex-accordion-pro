<?php
/**
 * Post Types Module.
 *
 * Registers the original custom post types for ACF compatibility.
 *
 * @package EWEB_Flex_Accordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the custom post types.
 */
function spfa_register_post_types() {
	// Register Sections.
	register_post_type(
		'menu_section',
		array(
			'labels'      => array(
				'name'          => 'Menu Sections',
				'singular_name' => 'Menu Section',
			),
			'public'      => true,
			'has_archive' => false,
			'supports'    => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'menu_icon'   => 'dashicons-menu-alt',
		)
	);

	// Register Items.
	register_post_type(
		'menu_item',
		array(
			'labels'      => array(
				'name'          => 'Menu Items',
				'singular_name' => 'Menu Item',
			),
			'public'      => true,
			'has_archive' => false,
			'supports'    => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'menu_icon'   => 'dashicons-products',
		)
	);

	// Register Categories Taxonomy.
	register_taxonomy(
		'menu_category',
		array( 'menu_item' ),
		array(
			'labels'       => array(
				'name'          => 'Menu Categories',
				'singular_name' => 'Menu Category',
			),
			'hierarchical' => true,
			'show_ui'      => true,
		)
	);
}

/**
 * Handle activation.
 */
function spfa_plugin_activate() {
	spfa_register_post_types();
	flush_rewrite_rules();
}

/**
 * Save meta data with nonce verification.
 *
 * @param int $post_id The ID of the post being saved.
 */
function spfa_save_post_meta( $post_id ) {
	if ( ! isset( $_POST['spfa_master_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spfa_master_nonce'] ) ), 'spfa_save_meta' ) ) {
		return;
	}
	// Meta saving logic here if needed...
}
add_action( 'save_post', 'spfa_save_post_meta' );
