<!-- admin/product-management.php -->
<?php
function sas_product_management_page() {
    ?>
    <div class="wrap">
        <h1>Product Management</h1>
        <form method="post">
            <input type="text" id="sas_product_search" placeholder="Search for products...">
        </form>
        <div id="sas_product_results"></div>
        <?php sas_display_added_products(); ?>
    </div>
    <script>
        document.getElementById('sas_product_search').addEventListener('input', function() {
            let searchQuery = this.value;
            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sas_search_products&query=' + searchQuery)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('sas_product_results').innerHTML = data;
                });
        });

        document.addEventListener('click', function(e) {
            if (e.target && e.target.className === 'sas-add-product') {
                let productId = e.target.getAttribute('data-product-id');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sas_add_product&product_id=' + productId)
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        e.target.disabled = true;
                        e.target.textContent = 'Added';
                    });
            }

            if (e.target && e.target.className === 'sas-remove-product') {
                let productId = e.target.getAttribute('data-product-id');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sas_remove_product&product_id=' + productId)
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        document.querySelector(`tr[data-product-id="${productId}"]`).remove();
                    });
            }
        });
    </script>
    <?php
}

add_action('wp_ajax_sas_search_products', 'sas_search_products');
function sas_search_products() {
    $search_term = sanitize_text_field($_GET['query']);
    $args = array(
        'post_type' => 'product',
        's' => $search_term,
        'posts_per_page' => 10,
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Image</th><th>Price</th><th>Action</th></tr></thead>';
        echo '<tbody>';

        while ($query->have_posts()) {
            $query->the_post();
            global $product;
            echo '<tr>';
            echo '<td>' . get_the_title() . '</td>';
            echo '<td>' . get_the_post_thumbnail(get_the_ID(), 'thumbnail') . '</td>';
            echo '<td>' . wc_price($product->get_price()) . '</td>';
            echo '<td><button class="sas-add-product" data-product-id="' . get_the_ID() . '">Add</button></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>No products found.</p>';
    }
    wp_reset_postdata();
    wp_die();
}

add_action('wp_ajax_sas_add_product', 'sas_add_product_to_affiliate_list');
function sas_add_product_to_affiliate_list() {
    if (isset($_GET['product_id'])) {
        $product_id = intval($_GET['product_id']);

        // Get product details
        $product = wc_get_product($product_id);
        $product_name = $product->get_name();
        $product_image = wp_get_attachment_url($product->get_image_id());
        $product_price = $product->get_price();
        $product_link = get_permalink($product_id);

        // Save to custom table
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_products';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE product_id = %d", $product_id));

        if ($exists == 0) {
            $wpdb->insert(
                $table_name,
                [
                    'product_id' => $product_id,
                    'name' => $product_name,
                    'image' => $product_image,
                    'price' => $product_price,
                    'link' => $product_link
                ]
            );

            echo 'Product added successfully!';
        } else {
            echo 'Product already added.';
        }
    }
    wp_die();
}

add_action('wp_ajax_sas_remove_product', 'sas_remove_product_from_affiliate_list');
function sas_remove_product_from_affiliate_list() {
    if (isset($_GET['product_id'])) {
        $product_id = intval($_GET['product_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_products';

        $wpdb->delete($table_name, ['product_id' => $product_id]);

        echo 'Product removed successfully!';
    }
    wp_die();
}

function sas_display_added_products() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_products';
    $products = $wpdb->get_results("SELECT * FROM $table_name");

    if ($products) {
        echo '<h2>Promoted Products</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Image</th><th>Price</th><th>Action</th></tr></thead>';
        echo '<tbody>';

        foreach ($products as $product) {
            echo '<tr data-product-id="' . esc_attr($product->product_id) . '">';
            echo '<td>' . esc_html($product->name) . '</td>';
            echo '<td><img src="' . esc_url($product->image) . '" width="50"></td>';
            echo '<td>' . wc_price($product->price) . '</td>';
            echo '<td><button class="sas-remove-product" data-product-id="' . esc_attr($product->product_id) . '">Remove</button></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>No products currently being promoted.</p>';
    }
}
?>
