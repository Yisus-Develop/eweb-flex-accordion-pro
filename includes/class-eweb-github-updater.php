<?php
/**
 * Elite GitHub Update System (Public/Token-less).
 *
 * @package EWEB_Flex_Accordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EWEB_GitHub_Updater' ) ) {

	/**
	 * Class EWEB_GitHub_Updater
	 */
	class EWEB_GitHub_Updater {

		private $file;
		private $plugin_slug;
		private $github_user;
		private $github_repo;
		private $proper_folder_name;
		private $github_response;

		/**
		 * Constructor.
		 * 
		 * @param string $file               Plugin main file path.
		 * @param string $github_user        GitHub Username.
		 * @param string $github_repo        GitHub Repo name.
		 * @param string $proper_folder_name Desired plugin slug (folder name).
		 */
		public function __construct( $file, $github_user, $github_repo, $proper_folder_name ) {
			$this->file               = $file;
			$this->proper_folder_name = $proper_folder_name; // 'eweb-flex-accordion-pro'
			$this->github_user        = $github_user;
			$this->github_repo        = $github_repo;
			$this->plugin_slug        = plugin_basename( $file );

			// Check for updates.
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
			
			// Show plugin info in modal.
			add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3 );
			
			// Handle the folder renaming and reactivation after update.
			add_filter( 'upgrader_post_install', array( $this, 'rename_and_reactivate' ), 10, 3 );

			// Add "View details" link even when updated.
			add_filter( 'plugin_row_meta', array( $this, 'add_view_details_link' ), 10, 2 );

			// Set timeout for HTTP requests.
			add_filter( 'http_request_timeout', function() { return 2; } );
		}

		/**
		 * Add "View details" link to the plugins list for coherence.
		 */
		public function add_view_details_link( $links, $file ) {
			if ( $file === $this->plugin_slug ) {
				$new_link = array(
					'view_details' => sprintf(
						'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
						esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $this->proper_folder_name . '&TB_iframe=true&width=600&height=550' ) ),
						esc_attr( 'View details' ),
						esc_attr( 'EWEB Flex Accordion Pro' ),
						__( 'View details' )
					),
				);
				return array_merge( $links, $new_link );
			}
			return $links;
		}

		private function get_local_plugin_data() {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			return get_plugin_data( $this->file );
		}

		public function check_update( $transient ) {
			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			$github_data = $this->get_github_data();
			$local_data  = $this->get_local_plugin_data();

			// Defensive check for versions
			$github_version = ( isset( $github_data->tag_name ) ) ? ltrim( $github_data->tag_name, 'v' ) : '';
			$local_version  = ( isset( $local_data['Version'] ) ) ? $local_data['Version'] : '0.0.0';

			if ( $github_data && ! empty( $github_version ) && version_compare( $github_version, $local_version, '>' ) ) {
				$readme_meta      = $this->get_readme_metadata();
				$obj              = new stdClass();
				$obj->slug        = $this->proper_folder_name;
				$obj->plugin      = $this->plugin_slug;
				$obj->new_version = $github_version;
				$obj->url         = 'https://github.com/' . $this->github_user . '/' . $this->github_repo;
				$obj->package     = isset( $github_data->zipball_url ) ? $github_data->zipball_url : '';

				$obj->tested       = $readme_meta['tested'];
				$obj->requires     = $readme_meta['requires'];
				$obj->requires_php = $readme_meta['requires_php'];

				// Banners & Icons (EWEB Standard)
				$asset_url = 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/assets/';
				$obj->icons = array(
					'128x128' => $asset_url . 'icon-128x128.png',
					'256x256' => $asset_url . 'icon-256x256.png',
					'default' => $asset_url . 'icon-256x256.png',
				);

				$transient->response[ $this->plugin_slug ] = $obj;
			}

			return $transient;
		}

		public function plugin_popup( $result, $action, $args ) {
			if ( 'plugin_information' !== $action || ( $args->slug !== $this->proper_folder_name && $args->slug !== $this->plugin_slug ) ) {
				return $result;
			}

			$github_data = $this->get_github_data();
			if ( ! $github_data ) {
				return $result;
			}

			$local_data  = $this->get_local_plugin_data();
			$readme_meta = $this->get_readme_metadata();

			$result = new stdClass();
			$result->name           = $local_data['Name'];
			$result->slug           = $this->proper_folder_name;
			$result->version        = ltrim( $github_data->tag_name, 'v' );
			$result->author         = $local_data['Author'];
			$result->homepage       = $local_data['PluginURI'];
			$result->download_link  = $github_data->zipball_url;
			$result->last_updated   = $github_data->published_at;

			// Compatibility Metadata
			$result->requires     = $readme_meta['requires'];
			$result->tested       = $readme_meta['tested'];
			$result->requires_php = $readme_meta['requires_php'];

			// Banners & Icons (EWEB Standard)
			$asset_url = 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/assets/';
			$result->banners = array(
				'low'  => $asset_url . 'banner-772x250.png',
				'high' => $asset_url . 'banner-1544x500.png',
			);

			$result->icons = array(
				'128x128' => $asset_url . 'icon-128x128.png',
				'256x256' => $asset_url . 'icon-256x256.png',
				'default' => $asset_url . 'icon-256x256.png',
			);

			$result->sections = array(
				'description'  => $local_data['Description'],
				'installation' => 'Public update system via GitHub API (Token-less).',
				'changelog'    => isset( $github_data->body ) ? $github_data->body : '',
			);

			return $result;
		}

		private function get_github_data() {
			if ( ! empty( $this->github_response ) ) {
				return $this->github_response;
			}

			$url      = 'https://api.github.com/repos/' . $this->github_user . '/' . $this->github_repo . '/releases/latest';
			$response = wp_remote_get( $url, array( 
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ),
			) );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$this->github_response = json_decode( wp_remote_retrieve_body( $response ) );
			return $this->github_response;
		}

		/**
		 * Get metadata from readme.txt (remote).
		 */
		private function get_readme_metadata() {
			$url      = 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/readme.txt';
			$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

			// Default fallbacks if request fails
			$defaults = array(
				'requires'     => '6.0',
				'tested'       => '6.5',
				'requires_php' => '8.1',
			);

			if ( is_wp_error( $response ) ) {
				return $defaults;
			}

			$body = wp_remote_retrieve_body( $response );
			preg_match( '/Requires at least:\s*(.*)/i', $body, $requires );
			preg_match( '/Tested up to:\s*(.*)/i', $body, $tested );
			preg_match( '/Requires PHP:\s*(.*)/i', $body, $requires_php );

			return array(
				'requires'     => isset( $requires[1] ) ? trim( $requires[1] ) : $defaults['requires'],
				'tested'       => isset( $tested[1] ) ? trim( $tested[1] ) : $defaults['tested'],
				'requires_php' => isset( $requires_php[1] ) ? trim( $requires_php[1] ) : $defaults['requires_php'],
			);
		}

		/**
		 * Renames the folder and reactivates the plugin after installation.
		 */
		public function rename_and_reactivate( $true, $hook_extra, $result ) {
			global $wp_filesystem;

			// If this isn't our plugin, bail.
			if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_slug ) {
				return $true;
			}

			$proper_destination = WP_PLUGIN_DIR . '/' . $this->proper_folder_name;
			$current_destination = $result['destination'];

			// Logic to move the contents to the correct folder name.
			if ( $current_destination !== $proper_destination ) {
				// Delete existing if any (to prevent move errors).
				if ( $wp_filesystem->exists( $proper_destination ) ) {
					$wp_filesystem->delete( $proper_destination, true );
				}
				
				// Move it.
				$wp_filesystem->move( $current_destination, $proper_destination );
				$result['destination'] = $proper_destination;
			}

			// Force reactivation of the plugin at the proper path.
			// Path: eweb-flex-accordion-pro/eweb-flex-accordion-pro.php
			$new_plugin_basename = $this->proper_folder_name . '/' . basename( $this->file );
			activate_plugin( $new_plugin_basename );

			return $result;
		}
	}
}

