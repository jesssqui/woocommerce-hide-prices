<?php
/**
 * Plugin Name: WooCommerce Hide Prices Until Approved
 * Plugin URI: https://greatwhitenorthdesign.ca/plugins/woocommerce-hide-prices
 * Description: Professional plugin that hides prices and add-to-cart buttons unless the user has an approved role. Includes custom user roles and comprehensive access control.
 * Version: 1.8.4
 * Author: Great White North Design
 * Author URI: https://greatwhitenorthdesign.ca
 * Text Domain: woocommerce-hide-prices
 * Domain Path: /languages
 * Requires at least: 5.6
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.9
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 *
 * @package WooCommerce_Hide_Prices
 * @version 1.8.4
 * @author Great White North Design
 * @copyright 2024 Great White North Design
 * @license GPL-2.0+
 * @since 1.0.0
 */

// Prevent direct access - Security first
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct access forbidden.' );
}

// Check if plugin is already loaded to prevent conflicts
if ( defined( 'WCHP_VERSION' ) ) {
    return;
}

// Define plugin constants for consistent use throughout the plugin
if ( ! defined( 'WCHP_VERSION' ) ) {
    define( 'WCHP_VERSION', '1.8.4' );
}
if ( ! defined( 'WCHP_PLUGIN_FILE' ) ) {
    define( 'WCHP_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WCHP_PLUGIN_DIR' ) ) {
    define( 'WCHP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WCHP_PLUGIN_URL' ) ) {
    define( 'WCHP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Include the update checker
require_once WCHP_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';

/**
 * Declare WooCommerce High-Performance Order Storage (HPOS) compatibility
 *
 * This ensures the plugin works with WooCommerce's new High-Performance Order Storage
 * system introduced in WooCommerce 7.1+. This is essential for future compatibility.
 *
 * @since 1.8.0
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 
            'custom_order_tables', 
            __FILE__, 
            true 
        );
    }
});

/**
 * Check if WooCommerce is active and loaded
 *
 * Comprehensive WooCommerce dependency checking that works across different
 * WordPress configurations including multisite installations.
 *
 * @since 1.8.0
 * @return bool True if WooCommerce is available, false otherwise
 */
if ( ! function_exists( 'wchp_check_woocommerce' ) ) {
    function wchp_check_woocommerce() {
        // Method 1: Check active plugins list
        $active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );
        $wc_plugin_path = 'woocommerce/woocommerce.php';
        
        if ( ! in_array( $wc_plugin_path, $active_plugins, true ) ) {
            // Also check multisite network activation
            if ( ! is_multisite() ) {
                add_action( 'admin_notices', 'wchp_missing_woocommerce_notice' );
                return false;
            }
            
            $network_plugins = get_site_option( 'active_sitewide_plugins', array() );
            if ( ! array_key_exists( $wc_plugin_path, $network_plugins ) ) {
                add_action( 'admin_notices', 'wchp_missing_woocommerce_notice' );
                return false;
            }
        }
        
        // Method 2: Check class existence
        if ( did_action( 'plugins_loaded' ) && ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', 'wchp_missing_woocommerce_notice' );
            return false;
        }
        
        // Method 3: Check WooCommerce version if class exists
        if ( class_exists( 'WooCommerce' ) ) {
            global $woocommerce;
            $wc_version = isset( $woocommerce->version ) ? $woocommerce->version : WC()->version;
            
            if ( version_compare( $wc_version, '5.0', '<' ) ) {
                add_action( 'admin_notices', 'wchp_woocommerce_version_notice' );
                return false;
            }
        }
        
        return true;
    }
}

/**
 * Display admin notice for missing WooCommerce
 *
 * Shows a professional, dismissible notice with helpful action links
 * when WooCommerce is not installed or activated.
 *
 * @since 1.8.0
 * @return void
 */
if ( ! function_exists( 'wchp_missing_woocommerce_notice' ) ) {
    function wchp_missing_woocommerce_notice() {
        if ( ! current_user_can( 'install_plugins' ) ) {
            return;
        }
        
        $install_url = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'install-plugin',
                    'plugin' => 'woocommerce',
                ),
                admin_url( 'update.php' )
            ),
            'install-plugin_woocommerce'
        );
        
        printf(
            '<div class="notice notice-error is-dismissible"><p><strong>%1$s</strong>: %2$s <a href="%3$s" class="button button-primary">%4$s</a></p></div>',
            esc_html__( 'WooCommerce Hide Prices', 'woocommerce-hide-prices' ),
            esc_html__( 'This plugin requires WooCommerce to be installed and active.', 'woocommerce-hide-prices' ),
            esc_url( $install_url ),
            esc_html__( 'Install WooCommerce', 'woocommerce-hide-prices' )
        );
    }
}

/**
 * Display admin notice for outdated WooCommerce version
 *
 * Shows a professional notice when WooCommerce version is below
 * the minimum required version with update link.
 *
 * @since 1.8.0
 * @return void
 */
if ( ! function_exists( 'wchp_woocommerce_version_notice' ) ) {
    function wchp_woocommerce_version_notice() {
        if ( ! current_user_can( 'update_plugins' ) ) {
            return;
        }
        
        $current_version = class_exists( 'WooCommerce' ) ? WC()->version : '0.0.0';
        
        printf(
            '<div class="notice notice-warning is-dismissible"><p><strong>%1$s</strong>: %2$s %3$s <a href="%4$s" class="button">%5$s</a></p></div>',
            esc_html__( 'WooCommerce Hide Prices', 'woocommerce-hide-prices' ),
            esc_html__( 'This plugin requires WooCommerce version 5.0 or higher.', 'woocommerce-hide-prices' ),
            sprintf( 
                esc_html__( 'You are currently running WooCommerce %s.', 'woocommerce-hide-prices' ), 
                esc_html( $current_version )
            ),
            esc_url( admin_url( 'update-core.php' ) ),
            esc_html__( 'Update WooCommerce', 'woocommerce-hide-prices' )
        );
    }
}

/**
 * Main plugin class - Single file architecture for stability
 *
 * This class handles all core functionality in a single file to avoid
 * complex autoloading issues and ensure maximum compatibility.
 *
 * @since 1.8.0
 */
if ( ! class_exists( 'WooCommerce_Hide_Prices' ) ) {
    final class WooCommerce_Hide_Prices {

        /**
         * Plugin instance
         * @var WooCommerce_Hide_Prices
         */
        private static $instance = null;

        /**
         * Get plugin instance (Singleton pattern)
         *
         * @since 1.8.0
         * @return WooCommerce_Hide_Prices
         */
        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Constructor - Private to enforce singleton
         *
         * @since 1.8.0
         */
        private function __construct() {
            // Use a higher priority to ensure we load after other plugins
            add_action( 'plugins_loaded', array( $this, 'init_plugin' ), 20 );
            
            // Handle plugin activation and deactivation safely
            register_activation_hook( WCHP_PLUGIN_FILE, array( $this, 'activate_plugin' ) );
            register_deactivation_hook( WCHP_PLUGIN_FILE, array( $this, 'deactivate_plugin' ) );
            
            // Show success notice when plugin is loaded successfully
            add_action( 'admin_notices', array( $this, 'show_success_notice' ) );
        }

        /**
         * Prevent cloning and unserialization
         *
         * @since 1.8.0
         */
        private function __clone() {}
        public function __wakeup() {}

        /**
         * Initialize plugin functionality
         *
         * @since 1.8.0
         * @return void
         */
        public function init_plugin() {
            // Check WooCommerce dependency first
            if ( ! wchp_check_woocommerce() ) {
                return;
            }

            // Load text domain for translations
            $this->load_textdomain();
            
            // Initialize basic functionality
            $this->init_basic_functionality();
            
            // Register hooks for price hiding
            $this->init_price_hiding();
            
            // Initialize admin functionality if in admin area
            if ( is_admin() ) {
                $this->init_admin_functionality();
            }
        }

        /**
         * Load plugin text domain
         *
         * @since 1.8.0
         * @return void
         */
        private function load_textdomain() {
            load_plugin_textdomain( 'woocommerce-hide-prices', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }

        /**
         * Initialize basic plugin functionality
         *
         * @since 1.8.0
         * @return void
         */
        private function init_basic_functionality() {
            // Register custom roles on init
            add_action( 'init', array( $this, 'register_custom_roles' ) );
        }

        /**
         * Initialize price hiding functionality
         *
         * @since 1.8.0
         * @return void
         */
        private function init_price_hiding() {
            // Only apply price hiding on frontend
            if ( ! is_admin() ) {
                // Hook into WooCommerce price display
                add_filter( 'woocommerce_get_price_html', array( $this, 'hide_price' ), 10, 2 );
                
                // Hook into add to cart buttons
                add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'hide_add_to_cart_button' ), 10, 2 );
                
                // Hook into single product page
                add_action( 'wp', array( $this, 'maybe_hide_single_add_to_cart' ) );
                
                // Add custom CSS to frontend
                add_action( 'wp_head', array( $this, 'output_custom_css' ) );
            }
        }

        /**
         * Initialize admin functionality
         *
         * @since 1.8.0
         * @return void
         */
        private function init_admin_functionality() {
            // Add custom roles to admin dropdowns
            add_filter( 'editable_roles', array( $this, 'add_custom_roles_to_admin' ) );
            
            // Add user profile fields for access management
            add_action( 'show_user_profile', array( $this, 'add_user_expiration_field' ) );
            add_action( 'edit_user_profile', array( $this, 'add_user_expiration_field' ) );
            add_action( 'personal_options_update', array( $this, 'save_user_expiration_field' ) );
            add_action( 'edit_user_profile_update', array( $this, 'save_user_expiration_field' ) );
            
            // Add admin menu and settings
            add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
            add_action( 'admin_init', array( $this, 'register_settings' ) );
            
            // Add admin styles
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
            
            // Add product meta boxes if enabled
            if ( get_option( 'wchp_enable_product_specific', 'no' ) === 'yes' ) {
                add_action( 'add_meta_boxes', array( $this, 'add_product_meta_boxes' ) );
                add_action( 'save_post', array( $this, 'save_product_meta_boxes' ) );
            }
            
            // Add category fields if enabled  
            if ( get_option( 'wchp_enable_category_restrictions', 'no' ) === 'yes' ) {
                add_action( 'product_cat_add_form_fields', array( $this, 'add_category_fields' ) );
                add_action( 'product_cat_edit_form_fields', array( $this, 'edit_category_fields' ) );
                add_action( 'edited_product_cat', array( $this, 'save_category_fields' ) );
                add_action( 'create_product_cat', array( $this, 'save_category_fields' ) );
            }
        }

        /**
         * Hide prices for unauthorized users
         *
         * @since 1.8.0
         * @param string $price Original price HTML
         * @param WC_Product $product Product object
         * @return string Modified price HTML
         */
        public function hide_price( $price, $product ) {
            // Check if we should hide price for this specific product
            if ( ! $this->should_hide_price_for_product( $product ) ) {
                return $price;
            }

            if ( $this->user_can_see_prices() ) {
                return $price;
            }

            $replacement_text = get_option( 'wchp_replacement_text', __( 'Price visible to approved customers only', 'woocommerce-hide-prices' ) );
            return '<span class="wchp-hidden-price">' . esc_html( $replacement_text ) . '</span>';
        }

        /**
         * Check if price should be hidden for specific product
         *
         * @since 1.8.3
         * @param WC_Product $product Product object
         * @return bool True if price should be hidden, false otherwise
         */
        private function should_hide_price_for_product( $product ) {
            $product_id = $product->get_id();
            
            // Check product-specific settings first
            if ( get_option( 'wchp_enable_product_specific', 'no' ) === 'yes' ) {
                $override_global = get_post_meta( $product_id, '_wchp_override_global', true );
                
                if ( $override_global === 'yes' ) {
                    $hide_price = get_post_meta( $product_id, '_wchp_hide_price', true );
                    return $hide_price === 'yes';
                }
            }
            
            // Check category-specific settings
            if ( get_option( 'wchp_enable_category_restrictions', 'no' ) === 'yes' ) {
                $product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
                
                foreach ( $product_categories as $category_id ) {
                    $hide_category_prices = get_term_meta( $category_id, '_wchp_hide_prices', true );
                    if ( $hide_category_prices === 'yes' ) {
                        return true;
                    }
                }
            }
            
            // Fall back to global setting
            return get_option( 'wchp_global_restriction', 'yes' ) === 'yes';
        }

        /**
         * Hide add to cart buttons for unauthorized users
         *
         * @since 1.8.0
         * @param string $button Original button HTML
         * @param WC_Product $product Product object
         * @return string Modified button HTML
         */
        public function hide_add_to_cart_button( $button, $product ) {
            // Check if we should hide the button for this specific product
            if ( ! $this->should_hide_price_for_product( $product ) ) {
                return $button;
            }

            if ( $this->user_can_see_prices() ) {
                return $button;
            }

            // Check if request access is enabled
            if ( get_option( 'wchp_enable_request_access', 'yes' ) === 'yes' ) {
                $request_url = get_option( 'wchp_request_access_url', '' );
                
                if ( ! empty( $request_url ) ) {
                    // Use configured URL
                    return '<a href="' . esc_url( $request_url ) . '" class="button wchp-request-access">' . esc_html__( 'Request Access', 'woocommerce-hide-prices' ) . '</a>';
                } else {
                    // Use JavaScript popup with message
                    return '<a href="#" class="button wchp-request-access" onclick="wchp_show_request_message(); return false;">' . esc_html__( 'Request Access', 'woocommerce-hide-prices' ) . '</a>';
                }
            }

            // If request access is disabled, just hide the button
            return '';
        }

        /**
         * Maybe hide single product add to cart
         *
         * @since 1.8.0
         * @return void
         */
        public function maybe_hide_single_add_to_cart() {
            if ( ! is_product() ) {
                return;
            }

            global $product;
            if ( ! $product || ! $this->should_hide_price_for_product( $product ) ) {
                return;
            }

            if ( ! $this->user_can_see_prices() ) {
                remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
                
                // Add request access message if enabled
                if ( get_option( 'wchp_enable_request_access', 'yes' ) === 'yes' ) {
                    add_action( 'woocommerce_single_product_summary', array( $this, 'show_request_access_message' ), 30 );
                }
            }
        }

        /**
         * Show request access message on single product page
         *
         * @since 1.8.3
         * @return void
         */
        public function show_request_access_message() {
            $message = get_option( 'wchp_request_access_message', __( 'To view prices and make purchases, please request access to our wholesale program.', 'woocommerce-hide-prices' ) );
            $request_url = get_option( 'wchp_request_access_url', '' );
            
            echo '<div class="wchp-request-access-message" style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin: 10px 0; border-radius: 4px;">';
            echo '<p style="margin: 0 0 10px 0; font-weight: bold;">' . esc_html( $message ) . '</p>';
            
            if ( ! empty( $request_url ) ) {
                // Use configured URL
                echo '<a href="' . esc_url( $request_url ) . '" class="button wchp-request-access" style="margin-top: 10px;">' . esc_html__( 'Request Access', 'woocommerce-hide-prices' ) . '</a>';
            } else {
                // Use JavaScript popup
                echo '<a href="#" class="button wchp-request-access" onclick="wchp_show_request_message(); return false;" style="margin-top: 10px;">' . esc_html__( 'Request Access', 'woocommerce-hide-prices' ) . '</a>';
            }
            
            echo '</div>';
        }

        /**
         * Output custom CSS to frontend
         *
         * @since 1.8.3
         * @return void
         */
        public function output_custom_css() {
            $custom_css = get_option( 'wchp_custom_css', '' );
            
            if ( ! empty( $custom_css ) ) {
                echo '<style type="text/css" id="wchp-custom-css">' . "\n";
                echo '/* WooCommerce Hide Prices - Custom CSS */' . "\n";
                echo wp_strip_all_tags( $custom_css ) . "\n";
                echo '</style>' . "\n";
            }
        }

        /**
         * Enqueue admin scripts and styles
         *
         * @since 1.8.3
         * @param string $hook_suffix Current admin page
         * @return void
         */
        public function enqueue_admin_scripts( $hook_suffix ) {
            // Only load on our settings page
            if ( $hook_suffix !== 'toplevel_page_wchp-settings' ) {
                return;
            }

            // Add inline styles for better admin UI
            $css = '
                .wchp-admin-header { 
                    background: #fff; 
                    border: 1px solid #ccd0d4; 
                    padding: 20px; 
                    margin-bottom: 20px; 
                    border-radius: 4px;
                }
                .wchp-admin-header h2 { 
                    margin-top: 0; 
                    color: #1d2327;
                }
                .wchp-status-good { 
                    color: #00a32a; 
                    font-weight: bold;
                }
                .wchp-status-error { 
                    color: #d63638; 
                    font-weight: bold;
                }
                .wchp-settings-section {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                    margin-bottom: 20px;
                }
                .wchp-settings-section .form-table {
                    margin: 0;
                }
                .wchp-settings-section .form-table th {
                    background: #f6f7f7;
                    border-bottom: 1px solid #ccd0d4;
                }
                .wchp-settings-section .form-table td {
                    border-bottom: 1px solid #f0f0f1;
                }
            ';
            wp_add_inline_style( 'wp-admin', $css );
        }

        /**
         * Add product meta boxes
         *
         * @since 1.8.3
         * @return void
         */
        public function add_product_meta_boxes() {
            add_meta_box(
                'wchp_product_settings',
                __( 'Hide Prices Settings', 'woocommerce-hide-prices' ),
                array( $this, 'product_meta_box_callback' ),
                'product',
                'side',
                'default'
            );
        }

        /**
         * Product meta box callback
         *
         * @since 1.8.3
         * @param WP_Post $post Current post object
         * @return void
         */
        public function product_meta_box_callback( $post ) {
            wp_nonce_field( 'wchp_product_meta_box', 'wchp_product_meta_box_nonce' );
            
            $hide_price = get_post_meta( $post->ID, '_wchp_hide_price', true );
            $override_global = get_post_meta( $post->ID, '_wchp_override_global', true );
            
            ?>
            <p>
                <label>
                    <input type="checkbox" name="wchp_override_global" value="yes" <?php checked( $override_global, 'yes' ); ?> />
                    <?php esc_html_e( 'Override global settings for this product', 'woocommerce-hide-prices' ); ?>
                </label>
            </p>
            
            <p>
                <label>
                    <input type="checkbox" name="wchp_hide_price" value="yes" <?php checked( $hide_price, 'yes' ); ?> />
                    <?php esc_html_e( 'Hide price for this product', 'woocommerce-hide-prices' ); ?>
                </label>
            </p>
            
            <p class="description">
                <?php esc_html_e( 'These settings will override the global plugin settings for this specific product.', 'woocommerce-hide-prices' ); ?>
            </p>
            <?php
        }

        /**
         * Save product meta boxes
         *
         * @since 1.8.3
         * @param int $post_id Post ID
         * @return void
         */
        public function save_product_meta_boxes( $post_id ) {
            // Security checks
            if ( ! isset( $_POST['wchp_product_meta_box_nonce'] ) ) {
                return;
            }
            
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wchp_product_meta_box_nonce'] ) ), 'wchp_product_meta_box' ) ) {
                return;
            }
            
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }
            
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
            
            // Save override global setting
            $override_global = isset( $_POST['wchp_override_global'] ) ? 'yes' : 'no';
            update_post_meta( $post_id, '_wchp_override_global', $override_global );
            
            // Save hide price setting
            $hide_price = isset( $_POST['wchp_hide_price'] ) ? 'yes' : 'no';
            update_post_meta( $post_id, '_wchp_hide_price', $hide_price );
        }

        /**
         * Check if current user can see prices
         *
         * @since 1.8.0
         * @return bool True if user can see prices, false otherwise
         */
        private function user_can_see_prices() {
            // Allow logged out users to see prices for now (can be customized)
            if ( ! is_user_logged_in() ) {
                return false;
            }

            $user = wp_get_current_user();
            $allowed_roles = get_option( 'wchp_allowed_roles', array( 'wholesale_customer', 'vip_customer', 'administrator' ) );

            // Check if user has any allowed role
            foreach ( $allowed_roles as $role ) {
                if ( in_array( $role, $user->roles ) ) {
                    // Check if user access has expired
                    if ( ! $this->user_access_expired( $user->ID ) ) {
                        return true;
                    }
                }
            }

            return false;
        }

        /**
         * Check if user access has expired
         *
         * @since 1.8.0
         * @param int $user_id User ID
         * @return bool True if expired, false otherwise
         */
        private function user_access_expired( $user_id ) {
            $expiration_date = get_user_meta( $user_id, 'wchp_access_expiration', true );
            
            if ( empty( $expiration_date ) ) {
                return false; // No expiration set
            }
            
            $expiration_timestamp = strtotime( $expiration_date );
            $current_timestamp = current_time( 'timestamp' );
            
            if ( $current_timestamp > $expiration_timestamp ) {
                // Mark as expired
                update_user_meta( $user_id, 'wchp_access_expired', true );
                return true;
            }
            
            return false;
        }

        /**
         * Register custom user roles
         *
         * @since 1.8.3
         * @return void
         */
        public function register_custom_roles() {
            // Register wholesale_customer role
            if ( ! get_role( 'wholesale_customer' ) ) {
                add_role(
                    'wholesale_customer',
                    __( 'Wholesale Customer', 'woocommerce-hide-prices' ),
                    array(
                        'read' => true,
                        'edit_posts' => false,
                        'delete_posts' => false,
                    )
                );
            }

            // Register vip_customer role  
            if ( ! get_role( 'vip_customer' ) ) {
                add_role(
                    'vip_customer',
                    __( 'VIP Customer', 'woocommerce-hide-prices' ),
                    array(
                        'read' => true,
                        'edit_posts' => false,
                        'delete_posts' => false,
                    )
                );
            }

            // Store registered roles for cleanup
            update_option( 'wchp_registered_roles', array( 'wholesale_customer', 'vip_customer' ) );
        }

        /**
         * Add custom roles to admin role dropdowns
         *
         * @since 1.8.3
         * @param array $roles Existing roles
         * @return array Modified roles array
         */
        public function add_custom_roles_to_admin( $roles ) {
            // Only add if the roles exist
            if ( get_role( 'wholesale_customer' ) ) {
                $wholesale_role = get_role( 'wholesale_customer' );
                $roles['wholesale_customer'] = array(
                    'name' => esc_html__( 'Wholesale Customer', 'woocommerce-hide-prices' ),
                    'capabilities' => $wholesale_role->capabilities
                );
            }
            
            if ( get_role( 'vip_customer' ) ) {
                $vip_role = get_role( 'vip_customer' );
                $roles['vip_customer'] = array(
                    'name' => esc_html__( 'VIP Customer', 'woocommerce-hide-prices' ),
                    'capabilities' => $vip_role->capabilities
                );
            }
            
            return $roles;
        }

        /**
         * Add user expiration field to profile pages
         *
         * @since 1.8.0
         * @param WP_User $user The user object being edited
         * @return void
         */
        public function add_user_expiration_field( $user ) {
            // Security check - only administrators can manage expiration dates
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            
            $expiration_date = get_user_meta( $user->ID, 'wchp_access_expiration', true );
            $access_expired = get_user_meta( $user->ID, 'wchp_access_expired', true );
            
            ?>
            <h3><?php esc_html_e( 'Price Access Settings', 'woocommerce-hide-prices' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="wchp_access_expiration"><?php esc_html_e( 'Access Expiration Date', 'woocommerce-hide-prices' ); ?></label></th>
                    <td>
                        <input 
                            type="date" 
                            name="wchp_access_expiration" 
                            id="wchp_access_expiration" 
                            value="<?php echo esc_attr( $expiration_date ); ?>" 
                            class="regular-text" 
                        />
                        <p class="description">
                            <?php esc_html_e( 'Leave empty for no expiration. When expired, user will lose price access automatically.', 'woocommerce-hide-prices' ); ?>
                        </p>
                        <?php if ( $access_expired ) : ?>
                            <p class="description" style="color: #dc3232;">
                                <strong><?php esc_html_e( 'Note: This user\'s access has expired.', 'woocommerce-hide-prices' ); ?></strong>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php
        }

        /**
         * Save user expiration field
         *
         * @since 1.8.0
         * @param int $user_id The user ID being updated
         * @return void
         */
        public function save_user_expiration_field( $user_id ) {
            // Security checks
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            
            if ( ! isset( $_POST['wchp_access_expiration'] ) ) {
                return;
            }
            
            // Verify nonce if available (WordPress handles this for user profile forms)
            if ( isset( $_POST['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user_id ) ) {
                return;
            }
            
            $expiration_date = sanitize_text_field( wp_unslash( $_POST['wchp_access_expiration'] ) );
            
            if ( empty( $expiration_date ) ) {
                // Remove expiration date and expired status
                delete_user_meta( $user_id, 'wchp_access_expiration' );
                delete_user_meta( $user_id, 'wchp_access_expired' );
            } else {
                // Validate date format
                $timestamp = strtotime( $expiration_date );
                if ( false !== $timestamp ) {
                    update_user_meta( $user_id, 'wchp_access_expiration', $expiration_date );
                    // Clear expired status when updating date
                    delete_user_meta( $user_id, 'wchp_access_expired' );
                }
            }
        }

        /**
         * Add admin menu
         *
         * @since 1.8.3
         * @return void
         */
        public function add_admin_menu() {
            add_menu_page(
                __( 'WooCommerce Hide Prices', 'woocommerce-hide-prices' ),
                __( 'Hide Prices', 'woocommerce-hide-prices' ),
                'manage_options',
                'wchp-settings',
                array( $this, 'admin_page' ),
                'dashicons-lock',
                58
            );
        }

        /**
         * Register plugin settings
         *
         * @since 1.8.3
         * @return void
         */
        public function register_settings() {
            // Register settings
            register_setting( 'wchp_settings', 'wchp_global_restriction' );
            register_setting( 'wchp_settings', 'wchp_allowed_roles', array( $this, 'sanitize_allowed_roles' ) );
            register_setting( 'wchp_settings', 'wchp_replacement_text' );
            register_setting( 'wchp_settings', 'wchp_enable_request_access' );
            register_setting( 'wchp_settings', 'wchp_request_access_message' );
            register_setting( 'wchp_settings', 'wchp_request_access_url' );
            
            // Advanced settings
            register_setting( 'wchp_settings', 'wchp_enable_product_specific' );
            register_setting( 'wchp_settings', 'wchp_enable_category_restrictions' );
            register_setting( 'wchp_settings', 'wchp_custom_css' );

            // Add settings sections
            add_settings_section(
                'wchp_general_section',
                __( 'General Settings', 'woocommerce-hide-prices' ),
                array( $this, 'general_section_callback' ),
                'wchp-settings'
            );

            add_settings_section(
                'wchp_advanced_section',
                __( 'Advanced Settings', 'woocommerce-hide-prices' ),
                array( $this, 'advanced_section_callback' ),
                'wchp-settings'
            );

            // Add settings fields
            add_settings_field(
                'wchp_global_restriction',
                __( 'Enable Price Hiding', 'woocommerce-hide-prices' ),
                array( $this, 'global_restriction_callback' ),
                'wchp-settings',
                'wchp_general_section'
            );

            add_settings_field(
                'wchp_allowed_roles',
                __( 'Allowed User Roles', 'woocommerce-hide-prices' ),
                array( $this, 'allowed_roles_callback' ),
                'wchp-settings',
                'wchp_general_section'
            );

            add_settings_field(
                'wchp_replacement_text',
                __( 'Replacement Text', 'woocommerce-hide-prices' ),
                array( $this, 'replacement_text_callback' ),
                'wchp-settings',
                'wchp_general_section'
            );

            add_settings_field(
                'wchp_enable_request_access',
                __( 'Enable Request Access', 'woocommerce-hide-prices' ),
                array( $this, 'enable_request_access_callback' ),
                'wchp-settings',
                'wchp_general_section'
            );

            add_settings_field(
                'wchp_request_access_message',
                __( 'Request Access Message', 'woocommerce-hide-prices' ),
                array( $this, 'request_access_message_callback' ),
                'wchp-settings',
                'wchp_general_section'
            );

            add_settings_field(
                'wchp_request_access_url',
                __( 'Request Access Button Link', 'woocommerce-hide-prices' ),
                array( $this, 'request_access_url_callback' ),
                'wchp-settings',
                'wchp_general_section'
            );

            // Advanced settings fields
            add_settings_field(
                'wchp_enable_product_specific',
                __( 'Product-Specific Controls', 'woocommerce-hide-prices' ),
                array( $this, 'enable_product_specific_callback' ),
                'wchp-settings',
                'wchp_advanced_section'
            );

            add_settings_field(
                'wchp_enable_category_restrictions',
                __( 'Category-Based Restrictions', 'woocommerce-hide-prices' ),
                array( $this, 'enable_category_restrictions_callback' ),
                'wchp-settings',
                'wchp_advanced_section'
            );

            add_settings_field(
                'wchp_custom_css',
                __( 'Custom CSS Styling', 'woocommerce-hide-prices' ),
                array( $this, 'custom_css_callback' ),
                'wchp-settings',
                'wchp_advanced_section'
            );
        }

        /**
         * Admin page content
         *
         * @since 1.8.3
         * @return void
         */
        public function admin_page() {
            // Handle settings save confirmation
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
                add_settings_error( 'wchp_messages', 'wchp_message', __( 'Settings saved successfully!', 'woocommerce-hide-prices' ), 'updated' );
            }
            
            ?>
            <div class="wrap">
                <div class="wchp-admin-header">
                    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                    <p><?php esc_html_e( 'Configure price hiding settings and manage user access for your WooCommerce store.', 'woocommerce-hide-prices' ); ?></p>
                </div>
                
                <?php settings_errors( 'wchp_messages' ); ?>
                
                <div class="notice notice-info">
                    <p><strong><?php esc_html_e( 'Plugin Status:', 'woocommerce-hide-prices' ); ?></strong> 
                    <span class="wchp-status-good"><?php esc_html_e( 'Active and functioning normally', 'woocommerce-hide-prices' ); ?> ✅</span></p>
                    <p><strong><?php esc_html_e( 'WooCommerce:', 'woocommerce-hide-prices' ); ?></strong> 
                    <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                        <span class="wchp-status-good"><?php esc_html_e( 'Detected', 'woocommerce-hide-prices' ); ?> ✅</span>
                        (v<?php echo esc_html( WC()->version ); ?>)
                    <?php else : ?>
                        <span class="wchp-status-error"><?php esc_html_e( 'Not Found', 'woocommerce-hide-prices' ); ?> ❌</span>
                    <?php endif; ?>
                    </p>
                </div>
                
                <div class="wchp-settings-section">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'wchp_settings' );
                        do_settings_sections( 'wchp-settings' );
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="postbox" style="margin-top: 20px;">
                    <h3 class="hndle" style="padding: 10px;"><span><?php esc_html_e( 'Custom User Roles Status', 'woocommerce-hide-prices' ); ?></span></h3>
                    <div class="inside">
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Role', 'woocommerce-hide-prices' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'woocommerce-hide-prices' ); ?></th>
                                    <th><?php esc_html_e( 'Users Count', 'woocommerce-hide-prices' ); ?></th>
                                    <th><?php esc_html_e( 'Actions', 'woocommerce-hide-prices' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $custom_roles = array( 'wholesale_customer', 'vip_customer' );
                                foreach ( $custom_roles as $role_slug ) {
                                    $role = get_role( $role_slug );
                                    $users_count = count( get_users( array( 'role' => $role_slug ) ) );
                                    ?>
                                    <tr>
                                        <td><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $role_slug ) ) ); ?></strong></td>
                                        <td><?php echo $role ? '<span class="wchp-status-good">✅ Active</span>' : '<span class="wchp-status-error">❌ Not Found</span>'; ?></td>
                                        <td><?php echo esc_html( $users_count ); ?> users</td>
                                        <td>
                                            <a href="<?php echo esc_url( admin_url( 'users.php?role=' . $role_slug ) ); ?>" class="button button-small">
                                                <?php esc_html_e( 'View Users', 'woocommerce-hide-prices' ); ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                        <p style="margin-top: 15px;">
                            <strong><?php esc_html_e( 'Quick Actions:', 'woocommerce-hide-prices' ); ?></strong><br>
                            <a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>" class="button"><?php esc_html_e( 'Add New User', 'woocommerce-hide-prices' ); ?></a>
                            <a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>" class="button"><?php esc_html_e( 'Manage All Users', 'woocommerce-hide-prices' ); ?></a>
                        </p>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Sanitize allowed roles setting
         *
         * @since 1.8.3
         * @param mixed $input Input value
         * @return array Sanitized array of roles
         */
        public function sanitize_allowed_roles( $input ) {
            if ( ! is_array( $input ) ) {
                return array();
            }
            
            $valid_roles = array_keys( wp_roles()->get_names() );
            $sanitized_roles = array();
            
            foreach ( $input as $role ) {
                if ( in_array( $role, $valid_roles, true ) ) {
                    $sanitized_roles[] = sanitize_text_field( $role );
                }
            }
            
            return $sanitized_roles;
        }

        /**
         * General section callback
         *
         * @since 1.8.3
         * @return void
         */
        public function general_section_callback() {
            echo '<p>' . esc_html__( 'Configure the basic settings for hiding prices from unauthorized users.', 'woocommerce-hide-prices' ) . '</p>';
        }

        /**
         * Global restriction field callback
         *
         * @since 1.8.3
         * @return void
         */
        public function global_restriction_callback() {
            $value = get_option( 'wchp_global_restriction', 'yes' );
            ?>
            <label>
                <input type="checkbox" name="wchp_global_restriction" value="yes" <?php checked( $value, 'yes' ); ?> />
                <?php esc_html_e( 'Enable price hiding globally', 'woocommerce-hide-prices' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'When enabled, prices will be hidden for users without approved roles.', 'woocommerce-hide-prices' ); ?></p>
            <?php
        }

        /**
         * Allowed roles field callback
         *
         * @since 1.8.3
         * @return void
         */
        public function allowed_roles_callback() {
            $allowed_roles = get_option( 'wchp_allowed_roles', array( 'wholesale_customer', 'vip_customer', 'administrator' ) );
            
            // Ensure it's an array
            if ( ! is_array( $allowed_roles ) ) {
                $allowed_roles = array();
            }
            
            $all_roles = wp_roles()->get_names();
            ?>
            <fieldset>
                <?php foreach ( $all_roles as $role_slug => $role_name ) : ?>
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox" name="wchp_allowed_roles[]" value="<?php echo esc_attr( $role_slug ); ?>" 
                               <?php checked( in_array( $role_slug, $allowed_roles, true ) ); ?> />
                        <?php echo esc_html( $role_name ); ?>
                        <?php if ( in_array( $role_slug, array( 'wholesale_customer', 'vip_customer' ), true ) ) : ?>
                            <span style="color: #00a32a; font-weight: bold;">(Custom Role)</span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <p class="description"><?php esc_html_e( 'Users with these roles will be able to see prices and purchase products.', 'woocommerce-hide-prices' ); ?></p>
            <?php
        }

        /**
         * Replacement text field callback
         *
         * @since 1.8.3
         * @return void
         */
        public function replacement_text_callback() {
            $value = get_option( 'wchp_replacement_text', __( 'Price visible to approved customers only', 'woocommerce-hide-prices' ) );
            ?>
            <input type="text" name="wchp_replacement_text" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'This text will be shown instead of the price for unauthorized users.', 'woocommerce-hide-prices' ); ?></p>
            <?php
        }

        /**
         * Enable request access field callback
         *
         * @since 1.8.3
         * @return void
         */
        public function enable_request_access_callback() {
            $value = get_option( 'wchp_enable_request_access', 'yes' );
            ?>
            <label>
                <input type="checkbox" name="wchp_enable_request_access" value="yes" <?php checked( $value, 'yes' ); ?> />
                <?php esc_html_e( 'Show "Request Access" button instead of add-to-cart', 'woocommerce-hide-prices' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'When enabled, unauthorized users will see a "Request Access" button instead of add-to-cart.', 'woocommerce-hide-prices' ); ?></p>
            <?php
        }

        /**
         * Request access message field callback
         *
         * @since 1.8.3
         * @return void
         */
        public function request_access_message_callback() {
            $value = get_option( 'wchp_request_access_message', __( 'To view prices and make purchases, please request access to our wholesale program.', 'woocommerce-hide-prices' ) );
            ?>
            <textarea name="wchp_request_access_message" rows="3" cols="50" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
            <p class="description"><?php esc_html_e( 'This message will be displayed to users who need to request access.', 'woocommerce-hide-prices' ); ?></p>
            <?php
        }

        /**
         * Request access URL field callback
         *
         * @since 1.8.3
         * @return void
         */
        public function request_access_url_callback() {
            $value = get_option( 'wchp_request_access_url', '' );
            ?>
            <input type="text" name="wchp_request_access_url" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="/contact-us" />
            <p class="description">
                <?php esc_html_e( 'URL where the "Request Access" button will link to. Leave empty to use a JavaScript popup with the message above.', 'woocommerce-hide-prices' ); ?><br>
                <strong><?php esc_html_e( 'Examples:', 'woocommerce-hide-prices' ); ?></strong>
                <code>/contact</code>, <code>/wholesale-application</code>, <code>https://yoursite.com/form</code>, <code>mailto:sales@yoursite.com</code>
            </p>
            <?php
        }

        /**
         * Advanced section callback
         *
         * @since 1.8.3
         * @return void
         */
        public function advanced_section_callback() {
            echo '<p>' . esc_html__( 'Advanced features for fine-tuned control over price hiding and styling.', 'woocommerce-hide-prices' ) . '</p>';
        }

        /**
         * Product-specific controls field callback
         *
         * @since 1.8.3
         * @return void
         */
        public function enable_product_specific_callback() {
            $value = get_option( 'wchp_enable_product_specific', 'no' );
            ?>
            <label>
                <input type="checkbox" name="wchp_enable_product_specific" value="yes" <?php checked( $value, 'yes' ); ?> />
                <?php esc_html_e( 'Enable per-product price hiding controls', 'woocommerce-hide-prices' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'When enabled, you can set price hiding rules for individual products in their edit pages.', 'woocommerce-hide-prices' ); ?></p>
            <?php if ( $value === 'yes' ) : ?>
                <p class="description" style="color: #00a32a; font-weight: bold;">
                    ✅ <?php esc_html_e( 'Active: Look for "Hide Prices Settings" meta box when editing products.', 'woocommerce-hide-prices' ); ?>
                </p>
            <?php endif; ?>
            <?php
        }

        /**
         * Category-based restrictions field callback
         *
         * @since 1.8.3
         * @return void
         */
        public function enable_category_restrictions_callback() {
            $value = get_option( 'wchp_enable_category_restrictions', 'no' );
            ?>
            <label>
                <input type="checkbox" name="wchp_enable_category_restrictions" value="yes" <?php checked( $value, 'yes' ); ?> />
                <?php esc_html_e( 'Enable category-based price hiding', 'woocommerce-hide-prices' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'When enabled, you can hide prices for entire product categories.', 'woocommerce-hide-prices' ); ?></p>
            <?php if ( $value === 'yes' && class_exists( 'WooCommerce' ) ) : ?>
                <div style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 4px solid #00a32a;">
                    <strong><?php esc_html_e( 'Category Settings:', 'woocommerce-hide-prices' ); ?></strong><br>
                    <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ); ?>" class="button button-small">
                        <?php esc_html_e( 'Manage Product Categories', 'woocommerce-hide-prices' ); ?>
                    </a>
                </div>
            <?php endif; ?>
            <?php
        }

        /**
         * Custom CSS field callback
         *
         * @since 1.8.3
         * @return void
         */
        public function custom_css_callback() {
            $value = get_option( 'wchp_custom_css', '' );
            ?>
            <textarea name="wchp_custom_css" rows="8" cols="50" class="large-text code" placeholder="/* Add your custom CSS here */
.wchp-hidden-price {
    color: #666;
    font-style: italic;
}

.wchp-request-access {
    background: #0073aa;
    color: white;
}"><?php echo esc_textarea( $value ); ?></textarea>
            <p class="description">
                <?php esc_html_e( 'Add custom CSS to style the hidden price elements and request access buttons.', 'woocommerce-hide-prices' ); ?><br>
                <strong><?php esc_html_e( 'Available CSS classes:', 'woocommerce-hide-prices' ); ?></strong>
                <code>.wchp-hidden-price</code>, <code>.wchp-request-access</code>, <code>.wchp-request-access-message</code>
            </p>
            <?php
        }

        /**
         * Show success notice
         *
         * @since 1.8.3
         * @return void
         */
        public function show_success_notice() {
            // Only show on admin pages and only once per page load
            if ( ! is_admin() || get_transient( 'wchp_success_notice_shown' ) ) {
                return;
            }
            
            set_transient( 'wchp_success_notice_shown', true, 30 );
            
            echo '<div class="notice notice-success is-dismissible"><p><strong>✅ WooCommerce Hide Prices:</strong> Plugin loaded successfully! Custom roles registered and price hiding is active. <a href="' . esc_url( admin_url( 'options-general.php?page=wchp-settings' ) ) . '">Configure Settings</a></p></div>';
        }

        /**
         * Plugin activation handler
         *
         * @since 1.8.0
         * @return void
         */
        public function activate_plugin() {
            // Check minimum WordPress version
            if ( version_compare( get_bloginfo( 'version' ), '5.6', '<' ) ) {
                deactivate_plugins( plugin_basename( WCHP_PLUGIN_FILE ) );
                wp_die( 
                    esc_html__( 'This plugin requires WordPress 5.6 or higher.', 'woocommerce-hide-prices' ),
                    esc_html__( 'Plugin Activation Error', 'woocommerce-hide-prices' ),
                    array( 'back_link' => true )
                );
            }
            
            // Check minimum PHP version
            if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
                deactivate_plugins( plugin_basename( WCHP_PLUGIN_FILE ) );
                wp_die( 
                    esc_html__( 'This plugin requires PHP 7.4 or higher.', 'woocommerce-hide-prices' ),
                    esc_html__( 'Plugin Activation Error', 'woocommerce-hide-prices' ),
                    array( 'back_link' => true )
                );
            }
            
            // Set default options
            $default_options = array(
                'wchp_global_restriction' => 'yes',
                'wchp_allowed_roles' => array( 'wholesale_customer', 'vip_customer', 'administrator' ),
                'wchp_enable_request_access' => 'yes',
                'wchp_request_access_message' => __( 'To view prices and make purchases, please request access to our wholesale program.', 'woocommerce-hide-prices' ),
                'wchp_hide_add_to_cart' => 'yes',
                'wchp_replacement_text' => __( 'Price visible to approved customers only', 'woocommerce-hide-prices' ),
            );

            foreach ( $default_options as $key => $value ) {
                if ( ! get_option( $key ) ) {
                    update_option( $key, $value );
                }
            }

            // Register custom roles
            $this->register_custom_roles();
            
            // Set activation timestamp
            update_option( 'wchp_activated', current_time( 'timestamp' ) );
            update_option( 'wchp_version', WCHP_VERSION );
        }

        /**
         * Plugin deactivation handler
         *
         * @since 1.8.0
         * @return void
         */
        public function deactivate_plugin() {
            // Clean up temporary options
            delete_option( 'wchp_activated' );
            delete_transient( 'wchp_success_notice_shown' );
            
            // Note: We don't remove user roles or settings on deactivation
            // They will be removed only on uninstall
        }

        /**
         * Get plugin version
         *
         * @since 1.8.0
         * @return string Plugin version
         */
        public function get_version() {
            return WCHP_VERSION;
        }

        /**
         * Get plugin file
         *
         * @since 1.8.0
         * @return string Plugin file path
         */
        public function get_plugin_file() {
            return WCHP_PLUGIN_FILE;
        }

        /**
         * Get plugin directory
         *
         * @since 1.8.0
         * @return string Plugin directory path
         */
        public function get_plugin_dir() {
            return WCHP_PLUGIN_DIR;
        }

        /**
         * Get plugin URL
         *
         * @since 1.8.0
         * @return string Plugin URL
         */
        public function get_plugin_url() {
            return WCHP_PLUGIN_URL;
        }
    }
}

/**
 * Get plugin instance
 *
 * @since 1.8.0
 * @return WooCommerce_Hide_Prices
 */
if ( ! function_exists( 'WCHP' ) ) {
    function WCHP() {
        return WooCommerce_Hide_Prices::get_instance();
    }
}

/**
 * Global role functions for backward compatibility
 *
 * @since 1.8.3
 */
if ( ! function_exists( 'wchp_register_custom_roles' ) ) {
    function wchp_register_custom_roles() {
        // Register wholesale_customer role
        if ( ! get_role( 'wholesale_customer' ) ) {
            add_role(
                'wholesale_customer',
                __( 'Wholesale Customer', 'woocommerce-hide-prices' ),
                array(
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                )
            );
        }

        // Register vip_customer role
        if ( ! get_role( 'vip_customer' ) ) {
            add_role(
                'vip_customer',
                __( 'VIP Customer', 'woocommerce-hide-prices' ),
                array(
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                )
            );
        }

        update_option( 'wchp_registered_roles', array( 'wholesale_customer', 'vip_customer' ) );
    }
}

if ( ! function_exists( 'wchp_remove_custom_roles' ) ) {
    function wchp_remove_custom_roles() {
        $registered_roles = get_option( 'wchp_registered_roles', array() );
        
        foreach ( $registered_roles as $role_slug ) {
            if ( in_array( $role_slug, array( 'wholesale_customer', 'vip_customer' ), true ) ) {
                remove_role( $role_slug );
            }
        }
        
        delete_option( 'wchp_registered_roles' );
    }
}

// Initialize the plugin
WCHP(); 