<!-- includes/shortlink.php -->
<?php

function sas_generate_shortlink($product_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_links';

    // Check if a shortlink already exists for this product and user
    $shortlink = $wpdb->get_var($wpdb->prepare(
        "SELECT shortlink FROM $table_name WHERE product_id = %d AND user_id = %d",
        $product_id,
        $user_id
    ));

    if ($shortlink) {
        return home_url('/go/' . $shortlink);
    }

    // Generate a unique shortlink code
    $shortlink_code = wp_generate_password(6, false, false);

    // Store the shortlink in the database
    $wpdb->insert($table_name, [
        'product_id' => $product_id,
        'user_id' => $user_id,
        'shortlink' => $shortlink_code
    ]);

    return home_url('/go/' . $shortlink_code);
}
?>
