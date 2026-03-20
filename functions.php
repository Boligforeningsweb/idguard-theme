<?php
/**
 * IDguard Minimal Shop - Theme Functions
 */

// Enqueue styles and fonts
function idguard_enqueue_assets() {
    // Google Fonts - Inter
    wp_enqueue_style(
        'idguard-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
        [],
        null
    );

    // Theme stylesheet
    wp_enqueue_style(
        'idguard-style',
        get_stylesheet_uri(),
        ['idguard-google-fonts'],
        wp_get_theme()->get('Version')
    );

    // Dequeue default WooCommerce styles that conflict with our grid
    wp_dequeue_style('wc-blocks-style');
    wp_dequeue_style('woocommerce-layout');
    wp_dequeue_style('woocommerce-smallscreen');
}

add_action('wp_enqueue_scripts', 'idguard_enqueue_assets', 20);

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

// Demo data admin page
require_once get_template_directory() . '/inc/demo-data.php';

// SEO, Open Graph, schema markup, favicon
require_once get_template_directory() . '/inc/seo.php';

// Testshop disclaimer on checkout
function idguard_checkout_disclaimer() {
    echo '<div class="testshop-checkout-notice">';
    echo 'Husk: Dette er en testshop. Der bliver ikke trukket penge, og du modtager ikke et produkt. Vi tester kun aldersverifikation med IDguard.';
    echo '</div>';
}
add_action( 'woocommerce_before_checkout_form', 'idguard_checkout_disclaimer', 5 );

// Testshop notice on single product pages
function idguard_product_disclaimer() {
    echo '<div class="testshop-product-notice">';
    echo 'Testprodukt — dette produkt er fiktivt og kan ikke købes. Butikken eksisterer kun for at demonstrere aldersverifikation med <a href="https://idguard.dk">IDguard</a>.';
    echo '</div>';
}
add_action( 'woocommerce_single_product_summary', 'idguard_product_disclaimer', 35 );

// Add custom body classes
function idguard_body_classes($classes) {
    $classes[] = 'idguard-theme';
    return $classes;
}
add_filter('body_class', 'idguard_body_classes');
