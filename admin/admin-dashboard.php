<!-- admin/admin-dashboard.php -->
<?php
function sas_admin_dashboard_page() {
    echo '<div class="wrap"><h1>Affiliate System Admin Dashboard</h1>';
    echo '<p>Manage the affiliate system settings and view user activities.</p>';
    echo '</div>';
}

function sas_registered_affiliates_page() {
    $users = get_users(array('role' => 'affiliate'));  // Use 'affiliate' role for affiliates

    echo '<div class="wrap"><h1>Registered Affiliates</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Registered Date</th></tr></thead>';
    echo '<tbody>';

    foreach ($users as $user) {
        echo '<tr><td>' . esc_html($user->ID) . '</td><td>' . esc_html($user->user_login) . '</td><td>' . esc_html($user->user_email) . '</td><td>' . esc_html($user->user_registered) . '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}
?>
