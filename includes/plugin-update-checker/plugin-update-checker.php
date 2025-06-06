<?php
/**
 * Plugin Update Checker Library 4.13
 * http://w-shadow.com/
 *
 * Copyright 2024 Janis Elsts
 * Released under the MIT license. See license.txt for details.
 */

require_once dirname(__FILE__) . '/Puc/v4p13/Factory.php';
require_once dirname(__FILE__) . '/Puc/v4p13/UpdateChecker.php';
require_once dirname(__FILE__) . '/Puc/v4p13/Plugin/UpdateChecker.php';

// Initialize the update checker
function wchp_initialize_update_checker() {
    $updateChecker = Puc_v4p13_Factory::buildUpdateChecker(
        'https://greatwhitenorthdesign.ca/plugins/woocommerce-hide-prices/update.json',
        WCHP_PLUGIN_FILE,
        'woocommerce-hide-prices',
        12, // Check for updates every 12 hours
        'wchp_update_checker'
    );

    // Add custom query arguments
    $updateChecker->addQueryArgFilter(function($queryArgs) {
        $queryArgs['license_key'] = get_option('wchp_license_key', '');
        return $queryArgs;
    });
}
add_action('plugins_loaded', 'wchp_initialize_update_checker'); 