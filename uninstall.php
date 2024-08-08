<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Remove custom database tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}affiliates");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}clicks");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sales");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}commissions");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}customers");

// Remove affiliate role
remove_role('affiliate');
