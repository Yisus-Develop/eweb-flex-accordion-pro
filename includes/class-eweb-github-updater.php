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
	 *
	 * Handles automatic updates from GitHub repository following industry standards.
	 */
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
			add_filter( 'plugin_action_links_' . $this->plugin_slug, array( $this, 'add_view_details_link' ) );
			add_filter( 'plugin_row_meta', array( $this, 'add_view_details_row_meta' ), 10, 2 );

			// Exact Pro Elements Hook Signature.
			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 4 );
		}

		private function get_details_url() {
			return self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $this->github_repo . '&section=description&TB_iframe=true&width=600&height=550' );
		}

		public function add_view_details_link( $links ) {
			$links[] = '<a href="' . $this->get_details_url() . '" class="thickbox open-plugin-details-modal">View details</a>';
			return $links;
		}

		public function add_view_details_row_meta( $links, $file ) {
			if ( $file === $this->plugin_slug ) {
				$links[] = '<a href="' . $this->get_details_url() . '" class="thickbox open-plugin-details-modal">View details</a>';
			}
			return $links;
		}

		public function check_update( $transient ) {
			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			$github_data = $this->get_github_data();
			if ( ! $github_data ) {
				return $transient;
			}

			$local_data     = $this->get_local_plugin_data();
			$github_version = ltrim( $github_data->tag_name, 'v' );
			$local_version  = $local_data['Version'];

			if ( version_compare( $github_version, $local_version, '>' ) ) {
				$obj              = new stdClass();
				$obj->slug        = $this->github_repo;
				$obj->plugin      = $this->plugin_slug;
				$obj->new_version = $github_version;
				$obj->url         = $local_data['PluginURI'];
				$obj->package     = $github_data->zipball_url;

				$asset_url  = 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/assets/';
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
			$local_data  = $this->get_local_plugin_data();
			$readme_data = $this->get_remote_readme_data();
			$asset_url   = 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/assets/';

			$result = new stdClass();
			$result->name          = $local_data['Name'];
			$result->slug          = $this->github_repo;
			$result->version       = ( $github_data ) ? ltrim( $github_data->tag_name, 'v' ) : $local_data['Version'];
			$result->author        = $local_data['Author'];
			$result->homepage      = $local_data['PluginURI'];
			$result->download_link = ( $github_data ) ? $github_data->zipball_url : '';
			$result->last_updated  = ( $github_data ) ? $github_data->published_at : gmdate( 'Y-m-d H:i:s' );
			$result->requires     = $readme_data['requires'];
			$result->tested       = $readme_data['tested'];
			$result->requires_php = $readme_data['requires_php'];

			$result->sections = array(
				'description'  => $readme_data['sections']['description'],
				'installation' => $readme_data['sections']['installation'],
				'changelog'    => $readme_data['sections']['changelog'],
			);

			$result->banners = array(
				'low'  => $asset_url . 'banner-772x250.png',
				'high' => $asset_url . 'banner-1544x500.png',
			);

			return $result;
		}

		private function get_remote_readme_data() {
			$data = array(
				'requires'     => '6.0',
				'tested'       => '7.0',
				'requires_php' => '8.1',
				'sections'     => array(
					'description'  => '<strong>EWEB - Flex Menu Pro</strong> is a premium, high-performance menu system.',
					'installation' => '<ul><li>Upload via WordPress.</li><li>Activate the Elite Engine.</li></ul>',
					'changelog'    => '<h4>18.3.0</h4><ul><li>Milestone: Full Pro Elements updater alignment.</li><li>Fix: Robust folder mapping logic.</li></ul>',
				),
			);

			$url      = 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/readme.txt';
			$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

			if ( ! is_wp_error( $response ) ) {
				$body = wp_remote_retrieve_body( $response );
				if ( ! empty( $body ) ) {
					if ( preg_match( '/Requires at least:\s*(.*)/i', $body, $matches ) ) {
						$data['requires'] = trim( $matches[1] );
					}
					if ( preg_match( '/Tested up to:\s*(.*)/i', $body, $matches ) ) {
						$data['tested'] = trim( $matches[1] );
					}
					if ( preg_match( '/Requires PHP:\s*(.*)/i', $body, $matches ) ) {
						$data['requires_php'] = trim( $matches[1] );
					}
					$headers = array( 'description' => 'Description', 'installation' => 'Installation', 'changelog' => 'Changelog' );
					foreach ( $headers as $key => $header ) {
						$pattern = '/==\s*' . preg_quote( $header, '/' ) . '\s*==\s*(.*?)\s*((==\s*[a-zA-Z0-9 ]+\s*==)|$)/is';
						if ( preg_match( $pattern, $body, $matches ) ) {
							$content = trim( $matches[1] );
							$content = preg_replace( '/^\*\s+(.*)$/m', '<li>$1</li>', $content );
							if ( strpos( $content, '<li>' ) !== false ) {
								$content = '<ul>' . $content . '</ul>';
							}
							$data['sections'][ $key ] = wpautop( $content );
						}
					}
				}
			}
			return $data;
		}

		private function get_github_data() {
			if ( ! empty( $this->github_response ) ) {
				return $this->github_response;
			}
			$url      = 'https://api.github.com/repos/' . $this->github_user . '/' . $this->github_repo . '/releases/latest';
			$response = wp_remote_get( $url, array( 'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) ) );
			if ( is_wp_error( $response ) ) {
				return false;
			}
			$this->github_response = json_decode( wp_remote_retrieve_body( $response ) );
			return $this->github_response;
		}

		/**
		 * PRO ELEMENTS STYLE: Folder Renaming.
		 */
		public function upgrader_source_selection( $source, $remote_source, $upgrader, $hook_extra ) {
			if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin_slug ) {
				$corrected_source = trailingslashit( $remote_source ) . $this->github_repo . '/';
				if ( $source !== $corrected_source ) {
					global $wp_filesystem;
					$wp_filesystem->move( $source, $corrected_source );
					return $corrected_source;
				}
			}
			return $source;
		}

		private function get_local_plugin_data() {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			return get_plugin_data( $this->file );
		}
	}
}
