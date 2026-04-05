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
	 * Handles automatic updates from GitHub repository.
	 */
	class EWEB_GitHub_Updater {

		/**
		 * The plugin main file.
		 *
		 * @var string
		 */
		private $file;

		/**
		 * The plugin slug (folder/file.php).
		 *
		 * @var string
		 */
		private $plugin_slug;

		/**
		 * The GitHub user name.
		 *
		 * @var string
		 */
		private $github_user;

		/**
		 * The GitHub repository name.
		 *
		 * @var string
		 */
		private $github_repo;

		/**
		 * The GitHub API response.
		 *
		 * @var object|false
		 */
		private $github_response;

		/**
		 * Constructor.
		 *
		 * @param string $file        The plugin main file.
		 * @param string $github_user The GitHub user name.
		 * @param string $github_repo The GitHub repository name.
		 */
		public function __construct( $file, $github_user, $github_repo ) {
			$this->file        = $file;
			$this->github_user = $github_user;
			$this->github_repo = $github_repo;
			$this->plugin_slug = plugin_basename( $file );

			// Hook into update checks.
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );

			// Hook into the plugin details popup.
			add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3 );

			// FORCE "View details" link.
			add_filter( 'plugin_action_links_' . $this->plugin_slug, array( $this, 'add_view_details_link' ) );

			// Folder Selection Fix (Elite Folder Mapping).
			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 2 );
		}

		/**
		 * Add a manual "View details" link to the plugin row.
		 *
		 * @param array $links Existing links.
		 * @return array
		 */
		public function add_view_details_link( $links ) {
			$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $this->github_repo . '&section=description&TB_iframe=true&width=600&height=550' );
			$links[]     = '<a href="' . $details_url . '" class="thickbox open-plugin-details-modal">View details</a>';
			return $links;
		}

		/**
		 * Get local plugin data.
		 *
		 * @return array
		 */
		private function get_local_plugin_data() {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			return get_plugin_data( $this->file );
		}

		/**
		 * Check for updates on GitHub.
		 *
		 * @param object $transient The update transient.
		 * @return object
		 */
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

				$transient->response[ $this->plugin_slug ] = $obj;
			}

			return $transient;
		}

		/**
		 * Show plugin information in WordPress popup.
		 *
		 * @param object|false $result The result object.
		 * @param string       $action The action name.
		 * @param object       $args   The action arguments.
		 * @return object|false
		 */
		public function plugin_popup( $result, $action, $args ) {
			if ( 'plugin_information' !== $action || $args->slug !== $this->github_repo ) {
				return $result;
			}

			$github_data = $this->get_github_data();
			$local_data  = $this->get_local_plugin_data();
			$readme_data = $this->get_remote_readme_data();

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

			return $result;
		}

		/**
		 * Get data from remote readme.txt.
		 *
		 * @return array
		 */
		private function get_remote_readme_data() {
			$data = array(
				'requires'     => '6.0',
				'tested'       => '7.0',
				'requires_php' => '8.1',
				'sections'     => array(
					'description'  => '<strong>EWEB - Flex Menu Pro</strong> is a premium, high-performance menu system.',
					'installation' => '<ul><li>Upload via WordPress.</li><li>Activate the Elite Engine.</li></ul>',
					'changelog'    => '<h4>18.2.4</h4><ul><li>Fix: Elite folder selection (Improved Mapping).</li><li>Branding: Unified Elite Menu Pro naming.</li></ul>',
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
				}
			}
			return $data;
		}

		/**
		 * Get latest release data from GitHub API.
		 *
		 * @return object|false
		 */
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
		 * Elite folder mapping logic.
		 *
		 * @param string $source Path to the source directory.
		 * @param string $remote_source Path to the remote source directory.
		 * @return string
		 */
		public function upgrader_source_selection( $source, $remote_source ) {
			if ( strpos( $source, $this->github_repo ) !== false ) {
				$corrected_source = trailingslashit( $remote_source ) . $this->github_repo . '/';
				if ( $source !== $corrected_source ) {
					global $wp_filesystem;
					if ( $wp_filesystem->move( $source, $corrected_source ) ) {
						return $corrected_source;
					}
				}
			}
			return $source;
		}
	}
}
