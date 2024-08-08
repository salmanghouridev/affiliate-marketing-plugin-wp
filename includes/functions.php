<!-- includes/functions.php -->
<?php

// Start PHP sessions, if necessary
function sas_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'sas_start_session', 1);

// // Create affiliate coupon
// function sas_create_affiliate_coupon($user_id) {
//     $user = get_user_by('ID', $user_id);
//     $coupon_code = $user->user_login; // Coupon code will be the username

//     // Check if coupon already exists
//     if (!get_page_by_title($coupon_code, OBJECT, 'shop_coupon')) {
//         $coupon = array(
//             'post_title' => $coupon_code,
//             'post_content' => '',
//             'post_status' => 'publish',
//             'post_author' => 1,
//             'post_type' => 'shop_coupon'
//         );

//         $new_coupon_id = wp_insert_post($coupon);

//         // Set coupon properties
//         update_post_meta($new_coupon_id, 'discount_type', 'percent');
//         update_post_meta($new_coupon_id, 'coupon_amount', '10'); // Set to 10% discount
//         update_post_meta($new_coupon_id, 'individual_use', 'yes');
//         update_post_meta($new_coupon_id, 'product_ids', '');
//         update_post_meta($new_coupon_id, 'exclude_product_ids', '');
//         update_post_meta($new_coupon_id, 'usage_limit', '');
//         update_post_meta($new_coupon_id, 'expiry_date', '');
//         update_post_meta($new_coupon_id, 'apply_before_tax', 'yes');
//         update_post_meta($new_coupon_id, 'free_shipping', 'no');
//     }
// }
function sas_process_affiliate_registration() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sas_register'])) {
        $username = sanitize_user($_POST['sas_username']);
        $email = sanitize_email($_POST['sas_email']);
        $password = sanitize_text_field($_POST['sas_password']);

        $errors = new WP_Error();

        if (empty($username) || empty($email) || empty($password)) {
            $errors->add('field', 'Please fill all the required fields.');
        }

        if (username_exists($username)) {
            $errors->add('username_exists', 'This username is already registered.');
        }

        if (email_exists($email)) {
            $errors->add('email_exists', 'This email is already registered.');
        }

        if (!is_email($email)) {
            $errors->add('invalid_email', 'Invalid email address.');
        }

        if (empty($errors->get_error_codes())) {
            $user_id = wp_create_user($username, $password, $email);
            if (is_wp_error($user_id)) {
                $errors = $user_id;
            } else {
                // Assign the affiliate role to the new user
                $user = new WP_User($user_id);
                $user->set_role('affiliate');

                // Create affiliate entry
                global $wpdb;
                $wpdb->insert(
                    $wpdb->prefix . 'affiliates',
                    array(
                        'user_id' => $user_id,
                        'payment_details' => '',
                        'status' => 'active',
                        'commission_rate' => 0.00
                    )
                );

                sas_log_activity($user_id, 'registration', 'New user registered');
                echo '<p>Registration successful! <a href="' . esc_url(home_url('/affiliate/login')) . '">Log in</a></p>';
                return;
            }
        }

        foreach ($errors->get_error_messages() as $error) {
            echo "<p>Error: " . esc_html($error) . "</p>";
        }
    }
}

// Process affiliate login
function sas_process_affiliate_login() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sas_login'])) {
        $creds = array(
            'user_login'    => sanitize_user($_POST['sas_username']),
            'user_password' => $_POST['sas_password'],
            'remember'      => true
        );

        $user = wp_signon($creds, false);
        if (is_wp_error($user)) {
            echo '<p>Error: ' . esc_html($user->get_error_message()) . '</p>';
        } else {
            sas_log_activity($user->ID, 'login', 'User logged in');
            wp_redirect(home_url('/affiliate/dashboard'));
            exit;
        }
    }
}

// Log user activity
function sas_log_activity($user_id, $activity_type, $description) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_activity_log';

    $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id,
            'activity_type' => $activity_type,
            'description' => $description
        ]
    );
}

// Log user logout activity
add_action('wp_logout', 'sas_log_logout_activity');
function sas_log_logout_activity() {
    $user_id = get_current_user_id(); // Get user id before they log out
    sas_log_activity($user_id, 'logout', 'User logged out');
}

// End PHP session on logout, if used
function sas_end_session() {
    if (session_id()) {
        session_destroy();
    }
}
add_action('wp_logout', 'sas_end_session');

// Track clicks
function sas_track_clicks() {
    if (isset($_GET['affiliate_id']) && is_numeric($_GET['affiliate_id'])) {
        $affiliate_id = intval($_GET['affiliate_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_clicks';

        $wpdb->insert(
            $table_name,
            [
                'user_id' => $affiliate_id,
                'product_id' => get_the_ID(),
                'timestamp' => current_time('mysql')
            ]
        );

        // Redirect to product page
        wp_redirect(get_permalink());
        exit;
    }
}
add_action('template_redirect', 'sas_track_clicks');

// Create activity log table
function sas_create_activity_log_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_activity_log';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE `$table_name` (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        activity_type varchar(100) NOT NULL,
        description text NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Create affiliate products table
function sas_create_affiliate_products_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_products';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE `$table_name` (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id mediumint(9) NOT NULL,
        name varchar(255) NOT NULL,
        image varchar(255) NOT NULL,
        price float NOT NULL,
        link varchar(255) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY product_id (product_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Create affiliate clicks table
function sas_create_affiliate_clicks_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_clicks';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE `$table_name` (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        product_id mediumint(9) NOT NULL,
        clicks int NOT NULL DEFAULT 0,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY product_id (product_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


// Create affiliates table
function sas_create_affiliates_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliates';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE `$table_name` (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        payment_details varchar(255) DEFAULT '' NOT NULL,
        status varchar(20) DEFAULT 'active' NOT NULL,
        commission_rate float DEFAULT 0.00 NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function sas_create_affiliate_links_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_links';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE `$table_name` (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id mediumint(9) NOT NULL,
        user_id mediumint(9) NOT NULL,
        shortlink varchar(6) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY shortlink (shortlink),
        KEY product_id (product_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
?>
