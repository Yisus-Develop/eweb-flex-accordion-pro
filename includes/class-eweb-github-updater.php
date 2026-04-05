<?php
/**
 * EWEB GitHub Updater - Elite Deployment System.
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
		private $github_response;

		/**
		 * Constructor.
		 * 
		 * @param string $file        Plugin main file path.
		 * @param string $github_user GitHub Username.
		 * @param string $github_repo GitHub Repo name.
		 */
		public function __construct( $file, $github_user, $github_repo ) {
			$this->file        = $file;
			$this->github_user = $github_user;
			$this->github_repo = $github_repo;
			$this->plugin_slug = plugin_basename( $file );

			// Hook into WordPress update system.
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
			add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3 );
			
			// Fix folder naming after update (Mandatory for GitHub).
			add_filter( 'upgrader_post_install', array( $this, 'fix_folder_name' ), 10, 3 );
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

			$github_version = ( isset( $github_data->tag_name ) ) ? ltrim( $github_data->tag_name, 'v' ) : '';
			$local_version  = ( isset( $local_data['Version'] ) ) ? $local_data['Version'] : '0.0.0';

			if ( $github_data && ! empty( $github_version ) && version_compare( $github_version, $local_version, '>' ) ) {
				$obj              = new stdClass();
				$obj->slug        = $this->github_repo;
				$obj->plugin      = $this->plugin_slug;
				$obj->new_version = $github_version;
				$obj->url         = 'https://github.com/' . $this->github_user . '/' . $this->github_repo;
				$obj->package     = isset( $github_data->zipball_url ) ? $github_data->zipball_url : '';

				// EWEB Standard Assets (Icons).
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
			if ( 'plugin_information' !== $action || $args->slug !== $this->github_repo ) {
				return $result;
			}

			$github_data = $this->get_github_data();
			if ( ! $github_data ) {
				return $result;
			}

			$local_data  = $this->get_local_plugin_data();
			$asset_url   = 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/assets/';
			$readme_meta = $this->get_readme_metadata();

			$result = new stdClass();
			$result->name           = $local_data['Name'];
			$result->slug           = $this->github_repo;
			$result->version        = ltrim( $github_data->tag_name, 'v' );
			$result->author         = $local_data['Author'];
			$result->homepage       = $local_data['PluginURI'];
			$result->download_link  = $github_data->zipball_url;
			$result->last_updated   = $github_data->published_at;

			// Professional Readme Metadata.
			$result->requires     = $readme_meta['requires'];
			$result->tested       = $readme_meta['tested'];
			$result->requires_php = $readme_meta['requires_php'];

			$result->sections = array(
				'description'  => $local_data['Description'],
				'changelog'    => isset( $github_data->body ) ? $github_data->body : '',
			);

			// EWEB Standard Assets (Banners).
			$result->banners = array(
				'low'  => $asset_url . 'banner-772x250.png',
				'high' => $asset_url . 'banner-1544x500.png',
			);

			return $result;
		}

		private function get_github_data() {
			if ( ! empty( $this->github_response ) ) {
				return $this->github_response;
			}

			$url = 'https://api.github.com/repos/' . $this->github_user . '/' . $this->github_repo . '/releases/latest';
			$response = wp_remote_get( $url, array( 'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) ) );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$this->github_response = json_decode( wp_remote_retrieve_body( $response ) );
			return $this->github_response;
		}

		private function get_readme_metadata() {
			$url      = 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/readme.txt';
			$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
			$defaults = array( 'requires' => '6.0', 'tested' => '6.5', 'requires_php' => '8.1' );

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

		public function fix_folder_name( $true, $hook_extra, $result ) {
			global $wp_filesystem;

			if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_slug ) {
				return $true;
			}

			$proper_destination = WP_PLUGIN_DIR . '/' . $this->github_repo;
			$current_destination = $result['destination'];

			if ( $current_destination !== $proper_destination ) {
				if ( $wp_filesystem->exists( $proper_destination ) ) {
					$wp_filesystem->delete( $proper_destination, true );
				}
				$wp_filesystem->move( $current_destination, $proper_destination );
				$result['destination'] = $proper_destination;
			}

			activate_plugin( $this->github_repo . '/' . basename( $this->file ) );
			return $result;
		}
	}
}
