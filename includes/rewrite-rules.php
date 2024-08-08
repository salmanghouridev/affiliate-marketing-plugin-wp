<?php
// includes/rewrite-rules.php
function sas_handle_shortlink_redirect() {
    $shortlink_code = get_query_var('sas_shortlink');
    if ($shortlink_code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_links';
        $result = $wpdb->get_row($wpdb->prepare("SELECT product_id FROM $table_name WHERE shortlink = %s", $shortlink_code));

        if ($result) {
            $product_link = get_permalink($result->product_id);
            if ($product_link) {
                wp_redirect($product_link);
                exit;
            }
        }
        wp_redirect(home_url('/404')); // Redirect to 404 if no valid link found
        exit;
    }
}
