<?php
/*
Plugin Name: WooCommerce Return Requests
Description: Adds return request functionality to WooCommerce customer accounts and admin panel.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register Custom Post Type for Returns
function wcr_register_return_post_type() {
    register_post_type('returns',
        array(
            'labels' => array(
                'name' => __('Returns'),
                'singular_name' => __('Return')
            ),
            'public' => false,
            'has_archive' => false,
            'show_ui' => true,
            'menu_position' => 20,
            'supports' => array('title', 'editor', 'author', 'custom-fields')
        )
    );
}
add_action('init', 'wcr_register_return_post_type');

// Add Return Requests Tab to My Account Menu
function wcr_add_return_requests_endpoint() {
    add_rewrite_endpoint('return-requests', EP_ROOT | EP_PAGES);
}
add_action('init', 'wcr_add_return_requests_endpoint');

// Add Query Vars
function wcr_add_query_vars($vars) {
    $vars[] = 'return-requests';
    return $vars;
}
add_filter('query_vars', 'wcr_add_query_vars', 0);

// Add Return Requests Link to My Account Menu
function wcr_add_return_requests_link_my_account($items) {
    $items['return-requests'] = __('Return Requests', 'woocommerce');
    return $items;
}
add_filter('woocommerce_account_menu_items', 'wcr_add_return_requests_link_my_account');

// Return Requests Content
function wcr_return_requests_content() {
    echo '<h2>Return Requests</h2>';
    $customer_id = get_current_user_id();
    $args = array(
        'post_type' => 'returns',
        'author' => $customer_id,
        'posts_per_page' => -1,
    );
    $returns = get_posts($args);
    if ($returns) {
        foreach ($returns as $return) {
            $order_id = get_post_meta($return->ID, 'order_id', true);
            $status = get_post_meta($return->ID, 'status', true);
            echo '<div>';
            echo '<p>Order ID: ' . esc_html($order_id) . '</p>';
            echo '<p>Status: ' . esc_html($status) . '</p>';
            echo '</div>';
        }
    } else {
        echo '<p>No return requests found.</p>';
    }

    echo '<h2>Submit New Return Request</h2>';
    echo '<form method="post">';
    echo '<p><label for="order_id">Order ID</label><input type="text" name="order_id" required></p>';
    echo '<p><label for="name">Name</label><input type="text" name="name" required></p>';
    echo '<p><label for="products">Product Names</label><textarea name="products" required></textarea></p>';
    echo '<p><label for="purchase_date">Purchase Date</label><input type="date" name="purchase_date" required></p>';
    echo '<p><label for="store_name">Store Name</label><input type="text" name="store_name" required></p>';
    echo '<p><label for="email">Email</label><input type="email" name="email" required></p>';
    echo '<p><label for="phone">Phone</label><input type="text" name="phone" required></p>';
    echo '<p><input type="submit" name="wcr_return_form_submit" value="Submit"></p>';
    echo '</form>';
}
add_action('woocommerce_account_return-requests_endpoint', 'wcr_return_requests_content');

// Handle Return Form Submission
function wcr_handle_return_form_submission() {
    if (isset($_POST['wcr_return_form_submit']) && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $return_data = array(
            'post_title' => sanitize_text_field($_POST['order_id']),
            'post_content' => sanitize_textarea_field($_POST['products']),
            'post_status' => 'publish',
            'post_author' => $current_user->ID,
            'post_type' => 'returns',
        );
        $post_id = wp_insert_post($return_data);
        if ($post_id) {
            update_post_meta($post_id, 'order_id', sanitize_text_field($_POST['order_id']));
            update_post_meta($post_id, 'name', sanitize_text_field($_POST['name']));
            update_post_meta($post_id, 'products', sanitize_textarea_field($_POST['products']));
            update_post_meta($post_id, 'purchase_date', sanitize_text_field($_POST['purchase_date']));
            update_post_meta($post_id, 'store_name', sanitize_text_field($_POST['store_name']));
            update_post_meta($post_id, 'email', sanitize_email($_POST['email']));
            update_post_meta($post_id, 'phone', sanitize_text_field($_POST['phone']));
            update_post_meta($post_id, 'status', 'pending');

            // Notify Admin
            wp_mail(get_option('admin_email'), 'New Return Request', 'A new return request has been submitted.');
        }
    }
}
add_action('template_redirect', 'wcr_handle_return_form_submission');

// Add Admin Menu for Return Requests
function wcr_add_admin_menu() {
    add_menu_page('Return Requests', 'Return Requests', 'manage_options', 'return-requests', 'wcr_return_requests_page');
}
add_action('admin_menu', 'wcr_add_admin_menu');

// Display Return Requests in Admin
function wcr_return_requests_page() {
    $args = array(
        'post_type' => 'returns',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );
    $returns = get_posts($args);
    echo '<h1>Return Requests</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Order ID</th><th>Name</th><th>Products</th><th>Purchase Date</th><th>Store Name</th><th>Email</th><th>Phone</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    foreach ($returns as $return) {
        $order_id = get_post_meta($return->ID, 'order_id', true);
        $name = get_post_meta($return->ID, 'name', true);
        $products = get_post_meta($return->ID, 'products', true);
        $purchase_date = get_post_meta($return->ID, 'purchase_date', true);
        $store_name = get_post_meta($return->ID, 'store_name', true);
        $email = get_post_meta($return->ID, 'email', true);
        $phone = get_post_meta($return->ID, 'phone', true);
        $status = get_post_meta($return->ID, 'status', true);
        echo '<tr>';
        echo '<td>' . esc_html($order_id) . '</td>';
        echo '<td>' . esc_html($name) . '</td>';
        echo '<td>' . esc_html($products) . '</td>';
        echo '<td>' . esc_html($purchase_date) . '</td>';
        echo '<td>' . esc_html($store_name) . '</td>';
        echo '<td>' . esc_html($email) . '</td>';
        echo '<td>' . esc_html($phone) . '</td>';
        echo '<td>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="return_id" value="' . $return->ID . '">';
        echo '<select name="return_status">';
        echo '<option value="pending"' . selected($status, 'pending', false) . '>Pending</option>';
        echo '<option value="approved"' . selected($status, 'approved', false) . '>Approved</option>';
        echo '<option value="rejected"' . selected($status, 'rejected', false) . '>Rejected</option>';
        echo '</select>';
        echo '<input type="submit" name="update_return_status" value="Update">';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

// Handle Status Update
function wcr_update_return_status() {
    if (isset($_POST['update_return_status']) && isset($_POST['return_id']) && isset($_POST['return_status'])) {
        $return_id = intval($_POST['return_id']);
        $status = sanitize_text_field($_POST['return_status']);
        update_post_meta($return_id, 'status', $status);
    }
}
add_action('admin_init', 'wcr_update_return_status');
