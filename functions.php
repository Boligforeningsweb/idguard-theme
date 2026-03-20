<?php
/**
 * IDguard Minimal Shop - Theme Functions
 */

// Load Google Fonts
function idguard_enqueue_fonts() {
    wp_enqueue_style(
        'idguard-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
        [],
        null
    );
}
add_action('wp_enqueue_scripts', 'idguard_enqueue_fonts');

// WooCommerce support
function idguard_woocommerce_support() {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'idguard_woocommerce_support');

// Remove default WooCommerce sidebar
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

// Change products per row
add_filter('loop_shop_columns', function() { return 3; });

// Change products per page
add_filter('loop_shop_per_page', function() { return 12; });

// Remove additional info tab
add_filter('woocommerce_product_tabs', function($tabs) {
    unset($tabs['additional_information']);
    unset($tabs['reviews']);
    return $tabs;
});

// Cart count for header
function idguard_cart_count() {
    if (function_exists('WC') && WC()->cart) {
        return WC()->cart->get_cart_contents_count();
    }
    return 0;
}

// Cart fragments for AJAX cart update
function idguard_cart_fragments($fragments) {
    $count = idguard_cart_count();
    $fragments['.cart-count'] = '<span class="cart-count">' . $count . '</span>';
    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'idguard_cart_fragments');
