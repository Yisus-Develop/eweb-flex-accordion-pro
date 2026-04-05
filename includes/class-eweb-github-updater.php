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

	class EWEB_GitHub_Updater {

		private $file;
		private $plugin_slug;
		private $github_user;
		private $github_repo;
		private $github_response;

		public function __construct( $file, $github_user, $github_repo ) {
			$this->file        = $file;
			$this->github_user = $github_user;
			$this->github_repo = $github_repo;
			$this->plugin_slug = plugin_basename( $file );

			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
			add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3 );
			
			// Pro-style folder fix during update process.
			add_filter( 'upgrader_source_selection', array( $this, 'fix_folder_name' ), 10, 4 );
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
			$readme_data = $this->get_remote_readme_data();

			$result = new stdClass();
			$result->name           = $local_data['Name'];
			$result->slug           = $this->github_repo;
			$result->version        = ltrim( $github_data->tag_name, 'v' );
			$result->author         = $local_data['Author'];
			$result->homepage       = $local_data['PluginURI'];
			$result->download_link  = $github_data->zipball_url;
			$result->last_updated   = $github_data->published_at;

			$result->requires     = $readme_data['requires'];
			$result->tested       = $readme_data['tested'];
			$result->requires_php = $readme_data['requires_php'];

			// Ensure sections are formatted as HTML.
			$result->sections = array(
				'description'  => wpautop( $readme_data['sections']['description'] ),
				'installation' => wpautop( $readme_data['sections']['installation'] ),
				'changelog'    => wpautop( $readme_data['sections']['changelog'] ),
			);

			$result->banners = array(
				'low'  => $asset_url . 'banner-772x250.png',
				'high' => $asset_url . 'banner-1544x500.png',
			);

			return $result;
		}

		private function get_remote_readme_data() {
			$url      = 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/readme.txt';
			$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
			
			$data = array(
				'requires'     => '6.0',
				'tested'       => '7.0',
				'requires_php' => '8.1',
				'sections'     => array(
					'description'  => 'Elite Interactive Flex Accordion for WordPress.',
					'installation' => '1. Upload via WordPress dashboard. 2. Activate.',
					'changelog'    => '18.1.4: Fixed section parsing and metadata synchronization.',
				),
			);

			if ( is_wp_error( $response ) ) {
				return $data;
			}

			$body = wp_remote_retrieve_body( $response );

			// Parse Headers with improved regex.
			if ( preg_match( '/Requires at least:\s*(.*)/i', $body, $matches ) ) {
				$data['requires'] = trim( $matches[1] );
			}
			if ( preg_match( '/Tested up to:\s*(.*)/i', $body, $matches ) ) {
				$data['tested'] = trim( $matches[1] );
			}
			if ( preg_match( '/Requires PHP:\s*(.*)/i', $body, $matches ) ) {
				$data['requires_php'] = trim( $matches[1] );
			}

			// Parse Sections with robust greedy regex.
			$sections = array(
				'description'  => 'Description',
				'installation' => 'Installation',
				'changelog'    => 'Changelog',
			);

			foreach ( $sections as $key => $header ) {
				$pattern = '/==\s*' . preg_quote( $header ) . '\s*==(.*?)((==\s*[a-z0-9 ]+\s*==)|$)/is';
				if ( preg_match( $pattern, $body, $matches ) ) {
					$data['sections'][ $key ] = trim( $matches[1] );
				}
			}

			return $data;
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

		public function fix_folder_name( $source, $remote_source, $upgrader, $hook_extra ) {
			if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin_slug ) {
				global $wp_filesystem;
				$new_source = trailingslashit( $remote_source ) . $this->github_repo;
				if ( $source !== $new_source ) {
					if ( $wp_filesystem->move( $source, $new_source ) ) {
						return $new_source;
					}
				}
			}
			return $source;
		}
	}
}
