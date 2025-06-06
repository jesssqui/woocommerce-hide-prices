<?php
/**
 * A custom plugin update checker.
 *
 * @package Puc_v4p13_Plugin_UpdateChecker
 */

if ( !class_exists('Puc_v4p13_Plugin_UpdateChecker') ):

class Puc_v4p13_Plugin_UpdateChecker extends Puc_v4p13_UpdateChecker {
	protected $updateTransient = 'update_plugins';
	protected $transientType = 'plugin';
	protected $extraFilters = array('plugin_api');

	public function __construct($metadataUrl, $pluginFile, $slug = '', $checkPeriod = 12, $optionName = '', $muPluginFile = '') {
		parent::__construct($metadataUrl, $pluginFile, $slug, $checkPeriod, $optionName, $muPluginFile);

		//Filter the plugin API response
		add_filter('plugins_api', array($this, 'injectInfo'), 20, 3);
	}

	/**
	 * Get the plugin's basename.
	 *
	 * @return string
	 */
	public function getPluginBasename() {
		return plugin_basename($this->absolutePath);
	}

	/**
	 * Get the plugin's directory name.
	 *
	 * @return string
	 */
	public function getPluginDirName() {
		return dirname($this->getPluginBasename());
	}

	/**
	 * Get the plugin's slug.
	 *
	 * @return string
	 */
	public function getPluginSlug() {
		return $this->slug;
	}

	/**
	 * Get the plugin's file path.
	 *
	 * @return string
	 */
	public function getPluginFilePath() {
		return $this->absolutePath;
	}

	/**
	 * Get the plugin's version.
	 *
	 * @return string
	 */
	public function getPluginVersion() {
		if (!function_exists('get_plugin_data')) {
			require_once(ABSPATH . '/wp-admin/includes/plugin.php');
		}
		$pluginData = get_plugin_data($this->absolutePath, false, false);
		return $pluginData['Version'];
	}

	/**
	 * Inject plugin update info into the response returned by the WordPress plugin API.
	 *
	 * @param object|WP_Error $result Response object or WP_Error.
	 * @param string $action The type of information being requested from the Plugin Installation API.
	 * @param object $args Plugin API arguments.
	 * @return object Updated response object or WP_Error.
	 */
	public function injectInfo($result, $action = null, $args = null) {
		$relevant = ($action == 'plugin_information') && isset($args->slug) && (
			($args->slug == $this->slug) || ($args->slug == dirname($this->getPluginBasename()))
		);
		if (!$relevant) {
			return $result;
		}

		$pluginInfo = $this->requestInfo();
		if ($pluginInfo) {
			return $pluginInfo->toWpFormat();
		}

		return $result;
	}

	protected function createDebugBarExtension() {
		return new Puc_v4p13_DebugBar_PluginExtension($this);
	}

	/**
	 * Register a callback for filtering query arguments.
	 *
	 * The callback function should take one argument - an associative array of query arguments.
	 * It should return a modified array of query arguments.
	 *
	 * @param callable $callback
	 * @return void
	 */
	public function addQueryArgFilter($callback) {
		$this->addFilter('request_info_query_args', $callback);
	}

	/**
	 * Register a callback for filtering arguments used in a plugin update HTTP request.
	 *
	 * The callback function should take one argument - an associative array of HTTP request arguments.
	 * It should return a modified array of arguments.
	 *
	 * @param callable $callback
	 * @return void
	 */
	public function addHttpRequestArgFilter($callback) {
		$this->addFilter('request_info_options', $callback);
	}

	/**
	 * Register a callback for filtering the plugin info retrieved from the external API.
	 *
	 * The callback function should take two arguments. If the plugin info was retrieved
	 * successfully, the first argument passed will be an instance of  Puc_v4p13_Plugin_Info. Otherwise,
	 * it will be NULL. The second argument will be the corresponding return value of wp_remote_get()
	 * (see WP docs for details).
	 *
	 * The callback function should return a new or modified instance of Puc_v4p13_Plugin_Info or NULL.
	 *
	 * @param callable $callback
	 * @return void
	 */
	public function addResultFilter($callback) {
		$this->addFilter('request_info_result', $callback, 10, 2);
	}
}

endif; 