<?php
/**
 * Plugin Name: EWEB - Flex Menu Pro
 * Description: Elite Interactive Food & Restaurant Menu System. Premium performance, customizable animations, and seamless integration for modern editors.
 * Version: 18.2.5
 * Author: Yisus Develop
 * Author URI: https://github.com/Yisus-Develop
 * Plugin URI: https://enlaweb.co/
 * Text Domain: eweb-flex-accordion-pro
 * License: GPL v2 or later
 * Requires at least: 6.0
 * Requires PHP: 8.1+
 *
 * @package EWEB_Flex_Accordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constantes.
define( 'SPFA_PATH', plugin_dir_path( __FILE__ ) );
define( 'SPFA_URL', plugin_dir_url( __FILE__ ) );
define( 'SPFA_VERSION', '18.2.5' );

/**
 * Carga del Actualizador Elite.
 */
if ( is_admin() ) {
	// Elite GitHub Updater (Surgical & Secure).
	if ( ! class_exists( 'EWEB_GitHub_Updater' ) ) {
		require_once SPFA_PATH . 'includes/class-eweb-github-updater.php';
	}
	new EWEB_GitHub_Updater( __FILE__, 'Yisus-Develop', 'eweb-flex-menu-pro' );
}

// Carga de módulos.
require_once SPFA_PATH . 'includes/post-types.php';
require_once SPFA_PATH . 'includes/shortcode.php';
require_once SPFA_PATH . 'includes/enqueue.php';
require_once SPFA_PATH . 'includes/class-spfa-importer.php';

// Inicialización.
add_action( 'init', 'spfa_register_post_types' );
register_activation_hook( __FILE__, 'spfa_plugin_activate' );

/**
 * Menú de Importación.
 */
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'edit.php?post_type=menu_section',
			'Import Menu Data',
			'Import Data',
			'manage_options',
			'spfa-importer',
			function () {
				?>
				<div class="wrap">
					<h1>Import Menu Data</h1>
					<form method="post">
						<?php wp_nonce_field( 'spfa_import_nonce', 'spfa_nonce' ); ?>
						<input type="submit" name="spfa_import_submit" class="button button-primary" value="Import Demo Data">
					</form>
					<?php
					if ( isset( $_POST['spfa_import_submit'] ) && check_admin_referer( 'spfa_import_nonce', 'spfa_nonce' ) ) {
						if ( class_exists( 'SPFA_Importer' ) ) {
							SPFA_Importer::import_demo_data();
							echo '<div class="updated"><p>Demo data imported successfully!</p></div>';
						}
					}
					?>
				</div>
				<?php
			}
		);
	}
);
