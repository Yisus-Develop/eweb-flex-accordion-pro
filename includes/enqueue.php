<?php
/**
 * Enqueue assets for Flex Accordion Pro.
 *
 * Professional asset management for frontend and admin.
 *
 * @package EWEB_Flex_Accordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue frontend and admin assets.
 *
 * Loads CSS, JS, and third-party libraries like GLightbox.
 */
function spfa_enqueue_assets() {
	$plugin_url = SPFA_URL;
	$version    = SPFA_VERSION;

	// CSS Principal.
	wp_enqueue_style(
		'spfa-style',
		$plugin_url . 'assets/css/spfa-style.css',
		array(),
		$version
	);

	// Responsive.
	wp_enqueue_style(
		'spfa-responsive',
		$plugin_url . 'assets/css/spfa-responsive.css',
		array(),
		$version
	);

	// GLightbox (Para imágenes).
	wp_enqueue_style(
		'glightbox',
		'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css',
		array(),
		'3.2.0'
	);

	wp_enqueue_script(
		'glightbox',
		'https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js',
		array(),
		'3.2.0',
		true
	);

	// JS Principal.
	wp_enqueue_script(
		'spfa-script',
		$plugin_url . 'assets/js/spfa-script.js',
		array( 'jquery' ),
		$version,
		true
	);

	// Inyectar GLightbox Init.
	wp_add_inline_script(
		'spfa-script',
		'document.addEventListener("DOMContentLoaded", function() { const lightbox = GLightbox({ selector: ".glightbox" }); });'
	);
}
add_action( 'wp_enqueue_scripts', 'spfa_enqueue_assets' );
