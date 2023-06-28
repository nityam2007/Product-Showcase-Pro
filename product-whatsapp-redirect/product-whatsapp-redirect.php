<?php
/**
 * Plugin Name: Product Showcase
 * Description: Showcase your products with a WhatsApp redirect feature.
 * Version: 1.0.0
 * Author: NityamAS / Nityam2007
 */

// Register custom post type for products
function pws_register_product_post_type() {
    $labels = array(
        'name' => 'Products',
        'singular_name' => 'Product',
        'menu_name' => 'Products',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Product',
        'edit_item' => 'Edit Product',
        'new_item' => 'New Product',
        'view_item' => 'View Product',
        'view_items' => 'View Products',
        'search_items' => 'Search Products',
        'not_found' => 'No products found',
        'not_found_in_trash' => 'No products found in trash',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-products',
        'supports' => array('title', 'thumbnail'),
        'taxonomies' => array('category'),
    );

    register_post_type('product', $args);
}
add_action('init', 'pws_register_product_post_type');

// Add custom meta box for product price
function pws_add_price_meta_box() {
    add_meta_box(
        'pws_price_meta_box',
        'Product Price',
        'pws_render_price_meta_box',
        'product',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'pws_add_price_meta_box');

// Render the product price meta box
function pws_render_price_meta_box($post) {
    $price = get_post_meta($post->ID, 'price', true);
    ?>
    <label for="pws_product_price">Price:</label>
    <input type="text" id="pws_product_price" name="pws_product_price" value="<?php echo esc_attr($price); ?>">
    <?php
}

// Save product price
function pws_save_product_price($post_id) {
    if (array_key_exists('pws_product_price', $_POST)) {
        update_post_meta(
            $post_id,
            'price',
            sanitize_text_field($_POST['pws_product_price'])
        );
    }
}
add_action('save_post_product', 'pws_save_product_price');

// Generate WhatsApp link with product details and your phone number
function pws_generate_whatsapp_link($post_id) {
    $product_title = get_the_title($post_id);
    $product_image = get_the_post_thumbnail_url($post_id, 'medium');
    $product_price = get_post_meta($post_id, 'price', true);
 
    $whatsapp_number = get_option('pws_whatsapp_number', '919664833459'); // Replace with your phone number
 
    $message = "Product: $product_title\n";
    $message .= "Price: ₹$product_price\n";
    $message .= "Image: $product_image\n";
    $message .= "Product Link: " . get_permalink($post_id);
 
    $whatsapp_message = rawurlencode($message);
    $whatsapp_link = "https://wa.me/$whatsapp_number?text=$whatsapp_message";
 
    return $whatsapp_link;
 }

// Shortcode for displaying products with WhatsApp redirect
function pws_product_display_shortcode($atts) {
    ob_start();

    $products = new WP_Query(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));

    if ($products->have_posts()) {
        ?>
        <div class="product-list">
            <div class="product-search">
                <div class="search-input">
                    <input type="text" id="product-search" placeholder="Search for a product">
                    <button id="product-search-btn">Search</button>
                </div>
                <?php if (get_option('pws_enable_sort', true)) : ?>
                    <div class="sort-select">
                        <label for="product-sort-select">Sort by:</label>
                        <select id="product-sort-select">
                            <option value="title_asc">Title (A-Z)</option>
                            <option value="title_desc">Title (Z-A)</option>
                            <option value="date_asc">Date (Oldest to Newest)</option>
                            <option value="date_desc">Date (Newest to Oldest)</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            <div class="products">
                <?php while ($products->have_posts()) {
                    $products->the_post();
                    $product_id = get_the_ID();
                    $whatsapp_link = pws_generate_whatsapp_link($product_id);
                    ?>
                    <div class="product">
                        <a href="<?php echo esc_url($whatsapp_link); ?>">
                            <div class="product-image">
                                <?php if (has_post_thumbnail()) {
                                    the_post_thumbnail('medium');
                                } else {
                                    echo '<img src="' . plugins_url('product-showcase/images/default-product-image.jpg') . '" alt="Product Image">';
                                }
                                ?>
                            </div>
                            <div class="product-details">
                                <h3><?php the_title(); ?></h3>
                                <p class="product-price">₹<?php echo get_post_meta($product_id, 'price', true); ?></p>
                            </div>
                        </a>
                    </div>
                <?php }
                wp_reset_postdata(); ?>
            </div>
        </div>

        <style>
            /* CSS styles for the product display */
            .product-list {
                margin: 20px;
                font-family: Arial, sans-serif;
            }

            .product-search {
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                flex-wrap: wrap;
            }

            .search-input {
                display: flex;
                margin-right: 10px;
                flex: 1 0 300px;
            }

            .search-input input {
                width: 100%;
                padding: 10px;
                font-size: 16px;
                border-radius: 4px;
                border: 1px solid #ccc;
            }

            .search-input button {
                padding: 10px 15px;
                font-size: 16px;
                background-color: #007bff;
                color: #fff;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                margin-top: 10px;
            }

            .sort-select {
                display: flex;
                align-items: center;
                margin-top: 10px;
            }

            .sort-select label {
                margin-right: 5px;
            }

            .sort-select select {
                padding: 5px;
                font-size: 14px;
                border-radius: 4px;
                border: 1px solid #ccc;
            }

            .products {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                grid-gap: 20px;
                justify-items: center;
            }

            .product {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: center;
                position: relative;
                overflow: hidden;
                transition: transform 0.3s ease;
            }

            .product a {
                display: block;
                position: relative;
            }

            .product:hover {
                transform: scale(1.05);
                box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
            }

            .product .product-image img {
                max-width: 100%;
                height: auto;
            }

            .product .product-details {
                margin-top: 10px;
            }

            .product h3 {
                margin: 0;
                font-size: 16px;
                font-weight: bold;
                color: #333;
            }

            .product .product-price {
                margin: 5px 0;
                font-size: 14px;
                color: #888;
            }
        </style>

        <script>
            // JavaScript code for product search and sorting
            jQuery(document).ready(function($) {
                $("#product-search-btn").on("click", function() {
                    var searchTerm = $("#product-search").val().toLowerCase();
                    $(".product").each(function() {
                        var productTitle = $(this).find("h3").text().toLowerCase();
                        if (productTitle.indexOf(searchTerm) !== -1) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });

                $("#product-sort-select").on("change", function() {
                    var sortValue = $(this).val();
                    var productsContainer = $(".products");
                    var products = productsContainer.children(".product");

                    products.sort(function(a, b) {
                        var aValue = $(a).find("h3").text().toLowerCase();
                        var bValue = $(b).find("h3").text().toLowerCase();

                        if (sortValue === "title_asc") {
                            return aValue.localeCompare(bValue);
                        } else if (sortValue === "title_desc") {
                            return bValue.localeCompare(aValue);
                        } else if (sortValue === "date_asc") {
                            var aDate = new Date($(a).data("date"));
                            var bDate = new Date($(b).data("date"));
                            return aDate - bDate;
                        } else if (sortValue === "date_desc") {
                            var aDate = new Date($(a).data("date"));
                            var bDate = new Date($(b).data("date"));
                            return bDate - aDate;
                        }
                    });

                    products.detach().appendTo(productsContainer);
                });
            });
        </script>

        <?php
    } else {
        echo 'No products found.';
    }

    return ob_get_clean();
}

add_shortcode('product_showcase', 'pws_product_display_shortcode');

// Add settings submenu under the Products menu
function pws_add_settings_submenu() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Product Showcase Settings',
        'Settings',
        'manage_options',
        'pws-settings',
        'pws_render_settings_page'
    );
}
add_action('admin_menu', 'pws_add_settings_submenu');

// Render the settings page
function pws_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings
    if (isset($_POST['pws_save_settings'])) {
        $whatsapp_number = sanitize_text_field($_POST['pws_whatsapp_number']);
        $enable_search = isset($_POST['pws_enable_search']) ? true : false;
        $enable_sort = isset($_POST['pws_enable_sort']) ? true : false;

        update_option('pws_whatsapp_number', $whatsapp_number);
        update_option('pws_enable_search', $enable_search);
        update_option('pws_enable_sort', $enable_sort);

        echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
    }

    // Get current settings
    $whatsapp_number = get_option('pws_whatsapp_number', '919664833459'); // Replace with your phone number
    $enable_search = get_option('pws_enable_search', true);
    $enable_sort = get_option('pws_enable_sort', true);
    ?>
    <div class="wrap">
        <h1>Product Showcase Settings</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="pws_whatsapp_number">WhatsApp Number</label></th>
                    <td>
                        <input type="text" id="pws_whatsapp_number" name="pws_whatsapp_number" value="<?php echo esc_attr($whatsapp_number); ?>" class="regular-text">
                        <p class="description">Enter your WhatsApp number in international format without any spaces or special characters (e.g., 919664833459).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable Search</th>
                    <td>
                        <label for="pws_enable_search">
                            <input type="checkbox" id="pws_enable_search" name="pws_enable_search" value="1" <?php checked($enable_search, true); ?>>
                            Enable search functionality for products
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable Sort</th>
                    <td>
                        <label for="pws_enable_sort">
                            <input type="checkbox" id="pws_enable_sort" name="pws_enable_sort" value="1" <?php checked($enable_sort, true); ?>>
                            Enable sorting options for products
                        </label>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="pws_save_settings" class="button-primary" value="Save Settings">
            </p>
        </form>
    </div>
    <?php
}

?>
