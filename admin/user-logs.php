<!-- admin/user-logs.php -->
<?php
function sas_user_logs_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_activity_log';

    $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");

    echo '<div class="wrap"><h1>User Logs</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>User ID</th><th>Activity</th><th>Description</th><th>Date & Time</th></tr></thead>';
    echo '<tbody>';

    foreach ($logs as $log) {
        echo '<tr>';
        echo '<td>' . esc_html($log->user_id) . '</td>';
        echo '<td>' . esc_html($log->activity_type) . '</td>';
        echo '<td>' . esc_html($log->description) . '</td>';
        echo '<td>' . esc_html($log->timestamp) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}
?>
