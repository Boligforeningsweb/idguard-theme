<?php
/** Synthetic fixture only. Run by the local Playground blueprint. */
if (!defined('ABSPATH') || strpos(ABSPATH, '/wordpress/') !== 0) {
    throw new RuntimeException('This setup is restricted to the isolated Playground filesystem.');
}
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

foreach ([
    'blogname' => '18+ Shoppen', 'blogdescription' => 'IDguard testbutik',
    'blog_public' => 0, 'WPLANG' => 'da_DK', 'timezone_string' => 'Europe/Copenhagen',
    'woocommerce_currency' => 'DKK', 'woocommerce_currency_pos' => 'right_space',
    'woocommerce_price_thousand_sep' => '.', 'woocommerce_price_decimal_sep' => ',',
    'woocommerce_price_num_decimals' => 2, 'woocommerce_default_country' => 'DK',
    'woocommerce_default_customer_address' => 'base', 'woocommerce_calc_taxes' => 'no',
    'woocommerce_enable_guest_checkout' => 'yes', 'woocommerce_enable_coupons' => 'no',
    'woocommerce_cart_redirect_after_add' => 'no', 'woocommerce_enable_ajax_add_to_cart' => 'yes',
    'woocommerce_coming_soon' => 'no', 'woocommerce_store_pages_only' => 'no',
    'woocommerce_allow_tracking' => 'no', 'woocommerce_analytics_enabled' => 'no',
    'woocommerce_feature_order_attribution_enabled' => 'no',
    'idguard_age_verification_mode' => 'individual', 'idguard_global_age_limit' => '18',
    'idguard_popup_page' => 'cart', 'idguard_show_verification_badge' => '1',
    'idguard_verification_persistence_mode' => 'session', 'idguard_excluded_gateways' => [],
    'idguard_cancel_redirect_option' => 'cart', 'idguard_disable_endpoint_check' => '0',
    'idguard_popup_title' => 'Din ordre indeholder aldersbegrænsede varer',
    'idguard_popup_message' => 'Bekræft din alder med MitID for at gennemføre købet. Webshoppen får kun at vide, om du opfylder aldersgrænsen.',
    'idguard_popup_button_text' => 'Fortsæt købet', 'idguard_popup_cancel_button_text' => 'Gå tilbage',
    'idguard_badge_text' => 'Alder verificeret med MitID', 'dismissed-idguard_notice' => true,
    'idguard_migration_v3_completed' => true, 'show_avatars' => 0,
    'permalink_structure' => '/%postname%/',
] as $key => $value) update_option($key, $value);

// Offline-only payment method. Tests never submit an order or invoke a payment gateway.
update_option('woocommerce_cod_settings', ['enabled' => 'yes', 'title' => 'Efterkrav', 'description' => 'Betal med kontanter ved levering.', 'instructions' => 'Betal med kontanter ved levering.']);
foreach (['cheque', 'bacs', 'paypal', 'ppcp-gateway', 'woocommerce_payments'] as $gateway) {
    update_option("woocommerce_{$gateway}_settings", ['enabled' => 'no']);
}

$block = static function (string $method): string {
    $reflection = new ReflectionMethod('WC_Install', $method);
    $reflection->setAccessible(true);
    return $reflection->invoke(null);
};
$ids = [];
foreach ([
    'shop' => ['Shop', 'woocommerce_shop_page_id', ''],
    'cart' => ['Cart', 'woocommerce_cart_page_id', file_get_contents('/setup/cart-content.html')],
    'checkout' => ['Checkout', 'woocommerce_checkout_page_id', $block('get_checkout_block_content')],
    'my-account' => ['My account', 'woocommerce_myaccount_page_id', '[woocommerce_my_account]'],
    'forside' => ['Forside', 'page_on_front', ''],
] as $slug => [$title, $option, $content]) {
    $existing = get_page_by_path($slug, OBJECT, 'page');
    $id = wp_insert_post(['ID' => $existing ? $existing->ID : 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content]);
    update_option($option, $id);
    $ids[$slug] = $id;
}
update_option('show_on_front', 'page');
update_option('woocommerce_terms_page_id', 0);

$products = [];
foreach (idguard_get_demo_products() as $index => $data) {
    $product = new WC_Product_Simple();
    $product->set_name($data['name']);
    $product->set_description($data['description']);
    $product->set_short_description($data['description']);
    $product->set_regular_price($data['price']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    $product->set_reviews_allowed(false);
    $product->set_sku('TESTSTORE-' . ($index + 1));
    $id = $product->save();
    update_post_meta($id, '_age_limit', in_array($index, [2, 4], true) ? 'none' : '18');
    $image = get_template_directory() . '/assets/images/products/' . $data['image'];
    $upload = wp_upload_bits($data['image'], null, file_get_contents($image));
    if (!empty($upload['error'])) throw new RuntimeException($upload['error']);
    $attachment = wp_insert_attachment(['post_mime_type' => 'image/jpeg', 'post_title' => $data['name'], 'post_status' => 'inherit'], $upload['file'], $id);
    wp_update_attachment_metadata($attachment, wp_generate_attachment_metadata($attachment, $upload['file']));
    set_post_thumbnail($id, $attachment);
    $products[] = ['id' => $id, 'name' => $data['name'], 'url' => get_permalink($id), 'restricted' => !in_array($index, [2, 4], true)];
}
file_put_contents('/setup/fixture.json', wp_json_encode(['pages' => $ids, 'products' => $products], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
flush_rewrite_rules(false);
echo "Isolated teststore fixture ready.\n";
