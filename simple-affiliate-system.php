<!--  simple-affiliate-system.php-->
<?php
/*
Plugin Name: Simple Affiliate System
Description: A simple affiliate system for WordPress.
Version: 1.2
Author: Your Name
*/

defined('ABSPATH') or die('Access denied.');

define('SAS_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include required files
require_once SAS_PLUGIN_DIR . 'admin/admin-dashboard.php';
require_once SAS_PLUGIN_DIR . 'admin/product-management.php';
require_once SAS_PLUGIN_DIR . 'admin/user-logs.php';
require_once SAS_PLUGIN_DIR . 'includes/functions.php';
require_once SAS_PLUGIN_DIR . 'includes/shortlink.php';

// Add menu pages
add_action('admin_menu', 'sas_add_admin_pages');

function sas_add_admin_pages() {
   
    add_menu_page(
        'Simple Affiliate System Admin',
        'Affiliates Admin',
        'manage_options',
        'sas_admin_dashboard',
        'sas_admin_dashboard_page',
        'dashicons-networking',
        110
    );
    add_submenu_page(
        'sas_admin_dashboard',
        'Registered Affiliates',
        'Registered Affiliates',
        'manage_options',
        'sas_registered_affiliates',
        'sas_registered_affiliates_page'
    );
    add_submenu_page(
        'sas_admin_dashboard',
        'Product Management',
        'Product Management',
        'manage_options',
        'sas_product_management',
        'sas_product_management_page'
    );
    add_submenu_page(
        'sas_admin_dashboard',
        'User Logs',
        'User Logs',
        'manage_options',
        'sas_user_logs',
        'sas_user_logs_page'
    );

}

// Add custom rewrite rules
add_action('init', 'sas_custom_rewrite_rules');
function sas_custom_rewrite_rules() {
    add_rewrite_rule('^affiliate/register/?$', 'index.php?sas_action=register', 'top');
    add_rewrite_rule('^affiliate/login/?$', 'index.php?sas_action=login', 'top');
    add_rewrite_rule('^affiliate/dashboard/?$', 'index.php?sas_action=dashboard', 'top');
    add_rewrite_rule('^go/([a-zA-Z0-9]{6})/?$', 'index.php?sas_shortlink=$matches[1]', 'top');
}

// Add query vars
add_filter('query_vars', 'sas_query_vars');
function sas_query_vars($vars) {
    $vars[] = 'sas_action';
    $vars[] = 'sas_shortlink';
    return $vars;
}

// Template redirect for custom actions
add_action('template_redirect', 'sas_template_redirect');
function sas_template_redirect() {
    $action = get_query_var('sas_action');

    switch ($action) {
        case 'register':
            include SAS_PLUGIN_DIR . 'affiliate/registration-template.php';
            exit;
        case 'login':
            include SAS_PLUGIN_DIR . 'affiliate/login-template.php';
            exit;
        case 'dashboard':
            if (!is_user_logged_in()) {
                wp_redirect(home_url('/affiliate/login'));
                exit;
            }
            include SAS_PLUGIN_DIR . 'affiliate/dashboard-template.php';
            exit;
        default:
            // Handle unknown action or add a log
            break;
    }

    if ($shortlink_code = get_query_var('sas_shortlink')) {
        handle_shortlink_redirect($shortlink_code);
    }
}


function handle_shortlink_redirect($shortlink_code) {
    global $wpdb;
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT product_id, user_id FROM {$wpdb->prefix}affiliate_links WHERE shortlink = %s",
        $shortlink_code
    ));

    if ($result && ($product_link = get_permalink($result->product_id))) {
        $clicks_table = $wpdb->prefix . 'affiliate_clicks';

        // Check if the click entry exists
        $click_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $clicks_table WHERE user_id = %d AND product_id = %d",
            $result->user_id,
            $result->product_id
        ));

        if ($click_entry) {
            $wpdb->update(
                $clicks_table,
                ['clicks' => $click_entry->clicks + 1],
                ['user_id' => $result->user_id, 'product_id' => $result->product_id]
            );
        } else {
            $wpdb->insert(
                $clicks_table,
                [
                    'user_id' => $result->user_id,
                    'product_id' => $result->product_id,
                    'clicks' => 1,
                    'timestamp' => current_time('mysql')
                ]
            );
        }

        // Apply discount coupon
        $user = get_user_by('ID', $result->user_id);
        // if ($user) {
        //     $coupon_code = $user->user_login;
        //     WC()->cart->add_discount($coupon_code);
        // }

        wp_redirect($product_link);
    } else {
        wp_redirect(home_url('/404'));
    }
    exit;
}


// Flush rewrite rules on plugin activation
register_activation_hook(__FILE__, 'sas_flush_rewrite_rules');
function sas_flush_rewrite_rules() {
    sas_custom_rewrite_rules();
    flush_rewrite_rules();
}

// Create necessary tables on plugin activation
register_activation_hook(__FILE__, 'sas_create_tables');

function sas_create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tables = [
        sas_create_affiliate_products_table(),
        sas_create_affiliate_clicks_table(),
        sas_create_activity_log_table(),
        sas_create_affiliates_table(),
        sas_create_affiliate_links_table()
    ];
    foreach ($tables as $table) {
        dbDelta($table);
    }
}


// Add the affiliate role on plugin activation
register_activation_hook(__FILE__, 'sas_add_affiliate_role');
function sas_add_affiliate_role() {
    add_role('affiliate', 'Affiliate', array(
        'read'         => true,  // Allows read access to the site
        'upload_files' => true,  // Allows file uploads
        'edit_posts'   => false, // Disallow editing posts
    ));
}

register_deactivation_hook(__FILE__, 'sas_remove_affiliate_role');
function sas_remove_affiliate_role() {
    remove_role('affiliate');
}


// Enqueue styles
add_action('wp_enqueue_scripts', 'sas_enqueue_styles');
function sas_enqueue_styles() {
    wp_enqueue_style('sas-styles', plugin_dir_url(__FILE__) . 'css/styles.css');
}
?>
<script src="https://cdn.tailwindcss.com"></script>