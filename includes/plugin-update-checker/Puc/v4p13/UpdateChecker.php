<?php
/**
 * Base class for update checker.
 *
 * @package Puc_v4p13_UpdateChecker
 */

if ( !class_exists('Puc_v4p13_UpdateChecker') ):

abstract class Puc_v4p13_UpdateChecker {
	protected $filterSuffix = '';
	protected $updateTransient = '';
	protected $transientType = '';
	protected $optionName = '';
	protected $muPluginFile = '';
	protected $debugMode = false;
	protected $cronHook = null;
	protected $checkPeriod = 12;
	protected $timeout = 10;
	protected $extraFilters = array();
	protected $filterPriority = 10;
	protected $absolutePath = '';
	protected $slug = '';
	protected $metadataUrl = '';
	protected $metadata = null;
	protected $debugBarPlugin = null;
	protected $cronName = '';

	public function __construct($metadataUrl, $absolutePath, $slug = '', $checkPeriod = 12, $optionName = '', $muPluginFile = '') {
		$this->metadataUrl = $metadataUrl;
		$this->absolutePath = $absolutePath;
		$this->slug = $slug;
		$this->checkPeriod = $checkPeriod;
		$this->optionName = $optionName;
		$this->muPluginFile = $muPluginFile;

		//If no slug is specified, use the name of the main plugin file as the slug.
		if (empty($this->slug)) {
			$this->slug = basename($this->absolutePath, '.php');
		}

		//Plugin name and version
		$pluginHeader = $this->getPluginHeader();
		$this->pluginName = $pluginHeader['Name'];
		$this->pluginVersion = $pluginHeader['Version'];

		//Set up the periodic update checks
		$this->cronHook = 'check_plugin_updates-' . $this->slug;
		$this->cronName = 'check_plugin_updates-' . $this->slug;
		$this->debugBarPlugin = 'Puc Debug Bar';
		$this->debugMode = (bool)(constant('WP_DEBUG'));

		//Set up the hooks
		$this->installHooks();
	}

	/**
	 * Install the hooks required to run periodic update checks and inject update info
	 * into WP data structures.
	 *
	 * @return void
	 */
	protected function installHooks() {
		//Override requests for plugin information
		add_filter('pre_set_site_transient_' . $this->updateTransient, array($this, 'injectUpdate'));

		//Override requests for plugin information
		add_filter('site_transient_' . $this->updateTransient, array($this, 'injectUpdate'));

		//Add our "Check for updates" link to the plugin row
		add_filter('plugin_row_meta', array($this, 'addCheckForUpdatesLink'), 10, 2);

		//Add extra filters
		foreach ($this->extraFilters as $filter) {
			add_filter($filter, array($this, 'injectUpdate'));
		}

		//Set up the periodic update checks
		if ($this->checkPeriod > 0) {
			//Schedule the first check
			$nextCheck = get_option($this->optionName);
			if ($nextCheck === false) {
				$nextCheck = time() + $this->checkPeriod * 3600;
				update_option($this->optionName, $nextCheck);
			}

			//Add the cron hook
			add_action($this->cronHook, array($this, 'checkForUpdates'));

			//Register the cron event if it's not already scheduled
			if (!wp_next_scheduled($this->cronHook)) {
				wp_schedule_event(time(), 'daily', $this->cronHook);
			}
		}
	}

	/**
	 * Get the plugin's header data.
	 *
	 * @return array
	 */
	abstract protected function getPluginHeader();

	/**
	 * Inject our update info into the response returned by wp_remote_get.
	 *
	 * @param object $value
	 * @return object Modified $value
	 */
	public function injectUpdate($value) {
		$update = $this->getUpdate();
		if ($update !== null) {
			$value = $this->addUpdateToList($value, $update);
		}
		return $value;
	}

	/**
	 * Get the update info from the remote server.
	 *
	 * @return Puc_v4p13_Update|null
	 */
	public function getUpdate() {
		$state = get_option($this->optionName);
		if (empty($state)) {
			$state = new StdClass;
			$state->lastCheck = 0;
			$state->checkedVersion = '';
			$state->update = null;
		}

		$state->lastCheck = time();
		$state->checkedVersion = $this->pluginVersion;
		update_option($this->optionName, $state);

		$update = $this->requestUpdate();
		if ($update === null) {
			return null;
		}

		$update = $this->filterUpdateResult($update);
		if ($update === null) {
			return null;
		}

		$state->update = $update;
		update_option($this->optionName, $state);

		return $update;
	}

	/**
	 * Request the update info from the remote server.
	 *
	 * @return Puc_v4p13_Update|null
	 */
	abstract protected function requestUpdate();

	/**
	 * Filter the update info before it's passed to WordPress.
	 *
	 * @param Puc_v4p13_Update $update
	 * @return Puc_v4p13_Update|null
	 */
	protected function filterUpdateResult($update) {
		$update = apply_filters($this->getUniqueName('request_update_result'), $update);
		return $update;
	}

	/**
	 * Add the update to the list maintained by WordPress.
	 *
	 * @param object $updates Update list.
	 * @param Puc_v4p13_Update $update To add.
	 * @return object Modified $updates.
	 */
	abstract protected function addUpdateToList($updates, $update);

	/**
	 * Add a "Check for updates" link to the plugin row in the "Plugins" page. By default,
	 * the new link will appear after the "Visit plugin site" link.
	 *
	 * @param array $pluginMeta Array of meta links.
	 * @param string $pluginFile
	 * @return array
	 */
	public function addCheckForUpdatesLink($pluginMeta, $pluginFile) {
		$isRelevant = ($pluginFile == $this->pluginFile) || ($pluginFile == $this->muPluginFile);
		if ($isRelevant && current_user_can('update_plugins')) {
			$linkUrl = wp_nonce_url(
				add_query_arg(
					'puc_check_for_updates',
					1,
					self_admin_url('plugins.php')
				),
				'puc_check_for_updates'
			);
			$pluginMeta[] = sprintf(
				'<a href="%s">%s</a>',
				esc_attr($linkUrl),
				__('Check for updates', 'plugin-update-checker')
			);
		}
		return $pluginMeta;
	}

	/**
	 * Check for updates. The result is stored in the DB option specified in $optionName.
	 *
	 * @return Puc_v4p13_Update|null
	 */
	public function checkForUpdates() {
		$state = get_option($this->optionName);
		if (empty($state)) {
			$state = new StdClass;
			$state->lastCheck = 0;
			$state->checkedVersion = '';
			$state->update = null;
		}

		$state->lastCheck = time();
		$state->checkedVersion = $this->pluginVersion;
		update_option($this->optionName, $state);

		$update = $this->requestUpdate();
		if ($update === null) {
			return null;
		}

		$update = $this->filterUpdateResult($update);
		if ($update === null) {
			return null;
		}

		$state->update = $update;
		update_option($this->optionName, $state);

		return $update;
	}

	/**
	 * Get the unique name for this update checker instance. Useful if you need to
	 * identify the instance in a filter or action.
	 *
	 * @return string
	 */
	public function getUniqueName($suffix = '') {
		$name = 'puc_' . md5($this->metadataUrl . $this->absolutePath);
		if (!empty($suffix)) {
			$name .= '_' . $suffix;
		}
		return $name;
	}

	/**
	 * Add a filter to the specified hook.
	 *
	 * @param string $hook
	 * @param callable $callback
	 * @param int $priority
	 * @param int $acceptedArgs
	 * @return void
	 */
	protected function addFilter($hook, $callback, $priority = 10, $acceptedArgs = 1) {
		add_filter($hook, $callback, $priority, $acceptedArgs);
	}

	/**
	 * Add an action to the specified hook.
	 *
	 * @param string $hook
	 * @param callable $callback
	 * @param int $priority
	 * @param int $acceptedArgs
	 * @return void
	 */
	protected function addAction($hook, $callback, $priority = 10, $acceptedArgs = 1) {
		add_action($hook, $callback, $priority, $acceptedArgs);
	}
}

endif; 