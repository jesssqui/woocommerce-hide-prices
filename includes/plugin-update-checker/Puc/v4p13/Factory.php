<?php
/**
 * Factory for building update checker instances.
 *
 * @package Puc_v4p13
 */

if ( !class_exists('Puc_v4p13_Factory') ):

class Puc_v4p13_Factory {
	protected static $classVersion = '4.13';
	protected static $minifiedVersion = '';

	/**
	 * Create a new update checker instance.
	 *
	 * @param string $metadataUrl The URL of the metadata file.
	 * @param string $fullPath Full path to the main plugin file or the directory containing the theme.
	 * @param string $slug Custom slug. Defaults to the name of the main plugin file or the directory name.
	 * @param int $checkPeriod How often to check for updates (in hours).
	 * @param string $optionName Where to store book-keeping info about update checks.
	 * @param string $muPluginFile The filename of the must-use plugin file (if any).
	 *
	 * @return Puc_v4p13_Plugin_UpdateChecker|Puc_v4p13_Theme_UpdateChecker
	 */
	public static function buildUpdateChecker($metadataUrl, $fullPath, $slug = '', $checkPeriod = 12, $optionName = '', $muPluginFile = '') {
		$fullPath = self::normalizePath($fullPath);
		$id = null;

		//Plugin or theme?
		if (self::isPluginFile($fullPath)) {
			$type = 'Plugin';
			$id = $fullPath;
		} else if (self::isThemeDirectory($fullPath)) {
			$type = 'Theme';
			$id = basename($fullPath);
		} else {
			throw new RuntimeException(sprintf(
				'The update checker cannot determine if "%s" is a plugin or a theme. ' .
				'This is a bug. Please contact the developer.',
				$fullPath
			));
		}

		$checkerClass = 'Puc_v4p13_' . $type . '_UpdateChecker';
		if (!class_exists($checkerClass)) {
			throw new RuntimeException(sprintf(
				'The update checker class "%s" does not exist. This is a bug. Please contact the developer.',
				$checkerClass
			));
		}

		//Figure out which option name to use
		if (empty($optionName)) {
			//Build an option name that's unique to the plugin/theme and the metadata URL.
			$optionName = 'external_updates-' . md5($id . $metadataUrl);
		}

		$checker = new $checkerClass($metadataUrl, $fullPath, $slug, $checkPeriod, $optionName, $muPluginFile);
		return $checker;
	}

	/**
	 * Normalize a filesystem path. Introduced in WP 3.9 to handle paths with Windows-style slashes.
	 *
	 * @param string $path Path to normalize.
	 * @return string Normalized path.
	 */
	public static function normalizePath($path) {
		if (function_exists('wp_normalize_path')) {
			return wp_normalize_path($path);
		}
		return str_replace('\\', '/', $path);
	}

	/**
	 * Check if the path points to a plugin file.
	 *
	 * @param string $absolutePath Normalized path.
	 * @return bool
	 */
	public static function isPluginFile($absolutePath) {
		//Check if the path is a file
		if (!is_file($absolutePath)) {
			return false;
		}

		//Check if the file is in the plugins directory
		$pluginDir = self::normalizePath(WP_PLUGIN_DIR);
		if (strpos($absolutePath, $pluginDir) !== 0) {
			return false;
		}

		$fileName = basename($absolutePath);
		return $fileName !== 'index.php';
	}

	/**
	 * Check if the path points to a theme directory.
	 *
	 * @param string $absolutePath Normalized path.
	 * @return bool
	 */
	public static function isThemeDirectory($absolutePath) {
		$themeDir = self::normalizePath(get_theme_root());
		return (strpos($absolutePath, $themeDir) === 0) && is_dir($absolutePath);
	}

	/**
	 * Get the version of the update checker library.
	 *
	 * @return string
	 */
	public static function getVersion() {
		return self::$classVersion;
	}

	/**
	 * Get the minified version of the update checker library.
	 *
	 * @return string
	 */
	public static function getMinifiedVersion() {
		return self::$minifiedVersion;
	}
}

endif; 