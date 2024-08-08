<?php

if (!is_user_logged_in()) {
    wp_redirect(home_url('/affiliate/login'));
    exit;
}

// Function to display affiliate products
function sas_display_affiliate_products() {
    global $wpdb;
    $products_table = $wpdb->prefix . 'affiliate_products';
    $clicks_table = $wpdb->prefix . 'affiliate_clicks';
    $user_id = get_current_user_id();

    $products = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT p.*, IFNULL(c.clicks, 0) as clicks 
             FROM $products_table p 
             LEFT JOIN $clicks_table c ON p.product_id = c.product_id AND c.user_id = %d",
            $user_id
        )
    );

    if ($products) {
        echo '<div class="sas-products mt-10">';
        echo '<h2 class="text-2xl font-semibold mb-6">Available Products</h2>';
        echo '<div class="overflow-x-auto">';
        echo '<table class="min-w-full bg-white shadow-md rounded-lg overflow-hidden">';
        echo '<thead class="bg-indigo-600 text-white">';
        echo '<tr>';
        echo '<th class="px-4 py-3">Name</th>';
        echo '<th class="px-4 py-3">Image</th>';
        echo '<th class="px-4 py-3">Price</th>';
        echo '<th class="px-4 py-3">Clicks</th>';
        echo '<th class="px-4 py-3">Affiliate Link</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody class="bg-gray-50">';

        foreach ($products as $product) {
            $affiliate_link = sas_generate_shortlink($product->product_id, $user_id);
            echo '<tr class="border-b hover:bg-gray-100 transition-colors">';
            echo '<td class="px-4 py-4">' . esc_html($product->name) . '</td>';
            echo '<td class="px-4 py-4"><img class="w-12 h-12 object-cover rounded-lg shadow" src="' . esc_url($product->image) . '" alt="' . esc_attr($product->name) . '"></td>';
            echo '<td class="px-4 py-4">' . wc_price($product->price) . '</td>';
            echo '<td class="px-4 py-4">' . esc_html($product->clicks) . '</td>';
            echo '<td class="px-4 py-4 flex items-center space-x-2">';
            echo '<input type="text" class="border border-gray-300 rounded-lg p-2 w-full text-gray-700 bg-gray-100" value="' . esc_url($affiliate_link) . '" id="link-' . esc_attr($product->product_id) . '" readonly>';
            echo '<button onclick="copyToClipboard(\'link-' . esc_attr($product->product_id) . '\')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-800 transition-colors">Copy</button>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<p class="text-gray-500 mt-6">No products available for promotion.</p>';
    }
}

// Function to display user dashboard statistics
function sas_display_user_dashboard() {
    global $wpdb;
    $clicks_table = $wpdb->prefix . 'affiliate_clicks';
    $user_id = get_current_user_id();

    $total_clicks = $wpdb->get_var($wpdb->prepare("SELECT SUM(clicks) FROM $clicks_table WHERE user_id = %d", $user_id));
    $commission = $total_clicks * 0.01; // Fixed commission per click

    echo '<div class="sas-dashboard mt-10">';
    echo '<h2 class="text-2xl font-semibold mb-6">Your Performance</h2>';
    echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
    echo '<div class="sas-stat-box bg-blue-500 text-white p-6 rounded-lg shadow-md">';
    echo '<h3 class="text-lg font-medium">Total Clicks</h3>';
    echo '<p class="text-3xl font-bold">' . esc_html($total_clicks) . '</p>';
    echo '</div>';
    echo '<div class="sas-stat-box bg-green-500 text-white p-6 rounded-lg shadow-md">';
    echo '<h3 class="text-lg font-medium">Commission</h3>';
    echo '<p class="text-3xl font-bold">' . wc_price($commission) . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

?>

<!-- HTML and JavaScript for Copy functionality -->
<div class="wrap p-8 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-semibold mb-2">Affiliate Dashboard</h1>
            <p class="text-lg text-gray-700">Welcome, <span class="font-bold text-indigo-600"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>!</p>
        </div>
        <a href="<?php echo wp_logout_url(home_url('/affiliate/login')); ?>" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-800 transition-colors">Logout</a>
    </div>
    
    <?php
    sas_display_affiliate_products();
    sas_display_user_dashboard();
    ?>
</div>

<script>
    function copyToClipboard(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand("copy");

        // Optionally, show a copied message
        alert("Copied the link: " + copyText.value);
    }
</script>
