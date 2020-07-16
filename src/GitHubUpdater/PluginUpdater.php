<?php

namespace Stackonet\WP\Framework\GitHubUpdater;

defined( 'ABSPATH' ) || exit;

class PluginUpdater extends Updater {

	/**
	 * Full path of plugin main file
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * basename of a plugin
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Plugin metadata
	 *
	 * @var array
	 */
	private $plugin_data;

	/**
	 * Latest GitHub release info
	 *
	 * @var array
	 */
	protected $latest_release = [];

	/**
	 * @var bool|null
	 */
	protected $has_new_version = null;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( "pre_set_site_transient_update_plugins", array( $this, "set_transient" ) );
		add_filter( "plugins_api", array( $this, "set_plugin_info" ), 10, 3 );
		add_filter( "upgrader_post_install", array( $this, "post_install" ), 10, 3 );

		parent::__construct();
	}

	/**
	 * Get plugin main file
	 *
	 * @return string
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * Set plugin main file
	 *
	 * @param string $plugin_file
	 *
	 * @return self
	 */
	public function set_plugin_file( $plugin_file ) {
		$this->plugin_file = $plugin_file;

		return $this;
	}

	/**
	 * Get information regarding our plugin
	 */
	public function read_plugin_data() {
		$this->slug = plugin_basename( $this->get_plugin_file() );
		$this->get_plugin_data();
		$this->get_latest_release_info();
		$this->has_new_update();
	}

	/**
	 * Get plugin data
	 *
	 * @return array
	 */
	public function get_plugin_data() {
		if ( empty( $this->plugin_data ) ) {
			$this->plugin_data = get_plugin_data( $this->get_plugin_file() );
		}

		return $this->plugin_data;
	}

	/**
	 * Get latest release info
	 *
	 * @return array
	 */
	public function get_latest_release_info() {
		if ( empty( $this->latest_release ) ) {
			$this->latest_release = $this->get_latest_release();
		}

		return $this->latest_release;
	}

	/**
	 * Check if has new version
	 *
	 * @return bool
	 */
	public function has_new_update() {
		if ( ! is_bool( $this->has_new_version ) ) {
			$release     = $this->get_latest_release_info();
			$plugin_info = $this->get_plugin_data();

			$this->has_new_version = version_compare( $release['tag_name'], $plugin_info['Version'] ) === 1;
		}

		return $this->has_new_version;
	}

	/**
	 * Push in plugin version information to get the update notification
	 *
	 * @param mixed $value New value of site transient.
	 *
	 * @return mixed
	 */
	public function set_transient( $value ) {
		// If we have checked the plugin data before, don't re-check
		if ( empty( $value->checked ) ) {
			return $value;
		}

		$this->read_plugin_data();

		// Update the transient to include our updated plugin data
		if ( $this->has_new_update() ) {
			$release_info = $this->get_latest_release_info();
			$plugin_info  = $this->get_plugin_data();

			$package = $release_info['zipball_url'];
			if ( isset( $release_info['assets'][0]['browser_download_url'] ) ) {
				$package = $release_info['assets'][0]['browser_download_url'];
			}

			$obj                            = new \stdClass();
			$obj->slug                      = $this->slug;
			$obj->new_version               = $release_info['tag_name'];
			$obj->url                       = $plugin_info['PluginURI'];
			$obj->package                   = $package;
			$value->response[ $this->slug ] = $obj;
		}

		return $value;
	}

	/**
	 * Perform additional actions to successfully install our plugin
	 *
	 * @param bool $response Installation response.
	 * @param array $hook_extra Extra arguments passed to hooked filters.
	 * @param array $result Installation result data.
	 *
	 * @return mixed
	 */
	public function post_install( $response, $hook_extra, $result ) {
		// Get plugin information
		$this->read_plugin_data();

		// Remember if our plugin was previously activated
		$wasActivated = is_plugin_active( $this->slug );

		// Since we are hosted in GitHub, our plugin folder would have a dirname of
		// reponame-tagname change it to our original one:

		global $wp_filesystem;
		$pluginFolder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $this->slug );
		$wp_filesystem->move( $result['destination'], $pluginFolder );
		$result['destination'] = $pluginFolder;

		// Re-activate plugin if needed
		if ( $wasActivated ) {
			activate_plugin( $this->slug );
		}

		return $result;
	}

	/**
	 * Push in plugin version information to display in the details lightbox
	 *
	 * @param false|object|array $result The result object or array. Default false.
	 * @param string $action The type of information being requested from the Plugin Installation API.
	 * @param object $args Plugin API arguments.
	 *
	 * @return object|bool
	 */
	public function set_plugin_info( $result, $action, $args ) {
		// Get plugin & GitHub release information
		$this->read_plugin_data();

		// If nothing is found, do nothing
		if ( empty( $args->slug ) || $args->slug != $this->slug ) {
			return false;
		}

		$release_info = $this->get_latest_release_info();
		$plugin_info  = $this->get_plugin_data();

		// Add our plugin information
		$args->last_updated = $release_info['published_at'];
		$args->version      = $release_info['tag_name'];
		$args->slug         = $this->slug;
		$args->plugin_name  = $plugin_info["Name"];
		$args->author       = $plugin_info["AuthorName"];
		$args->homepage     = $plugin_info["PluginURI"];

		// This is our release download zip file
		$downloadLink = $release_info['zipball_url'];
		if ( isset( $release_info['assets'][0]['browser_download_url'] ) ) {
			$downloadLink = $release_info['assets'][0]['browser_download_url'];
		}

		// Include the access token for private GitHub repos
		$args->download_link = $downloadLink;

		// We're going to parse the GitHub markdown release notes, include the parser
		// require_once( "Parsedown.php" );

		$changelog = $release_info['body'];

		// Create tabs in the lightbox
		$args->sections = array(
			'description' => $plugin_info["Description"],
			'changelog'   => class_exists( \Parsedown::class )
				? \Parsedown::instance()->parse( $changelog )
				: $changelog
		);

		// Gets the required version of WP if available
		$matches = null;
		preg_match( "/requires:\s([\d\.]+)/i", $changelog, $matches );
		if ( ! empty( $matches ) ) {
			if ( is_array( $matches ) ) {
				if ( count( $matches ) > 1 ) {
					$args->requires = $matches[1];
				}
			}
		}

		// Gets the tested version of WP if available
		$matches = null;
		preg_match( "/tested:\s([\d\.]+)/i", $changelog, $matches );
		if ( ! empty( $matches ) ) {
			if ( is_array( $matches ) ) {
				if ( count( $matches ) > 1 ) {
					$args->tested = $matches[1];
				}
			}
		}

		return $args;
	}
}
