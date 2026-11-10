<?php
/** Meaningful request-boundary and URL tests; no WordPress/database/network needed. */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['test_admin'] = false;
$GLOBALS['test_ajax'] = false;
$GLOBALS['test_cart'] = false;
$GLOBALS['test_checkout'] = false;
$GLOBALS['test_front_page'] = false;
$GLOBALS['test_shop'] = false;
$GLOBALS['test_queried_object'] = null;
$GLOBALS['test_hooks'] = [];
function add_action( $hook, $callback, $priority = 10, $arguments = 1 ) { $GLOBALS['test_hooks'][ $hook ][] = $callback; }
function add_filter( $hook, $callback, $priority = 10, $arguments = 1 ) { $GLOBALS['test_hooks'][ $hook ][] = $callback; }
function is_admin() { return $GLOBALS['test_admin']; }
function wp_doing_ajax() { return $GLOBALS['test_ajax']; }
function is_cart() { return $GLOBALS['test_cart']; }
function is_checkout() { return $GLOBALS['test_checkout']; }
function is_front_page() { return $GLOBALS['test_front_page']; }
function is_shop() { return $GLOBALS['test_shop']; }
function get_queried_object() { return $GLOBALS['test_queried_object']; }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function home_url( $path = '' ) { return 'https://teststore.idguard.dk' . ( $path === '' ? '' : '/' . ltrim( $path, '/' ) ); }
function add_query_arg( $key, $value, $url ) {
    $parts = parse_url( $url );
    parse_str( $parts['query'] ?? '', $query );
    $query[ $key ] = $value;
    return ( isset( $parts['scheme'] ) ? $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' ) : '' ) . ( $parts['path'] ?? '' ) . '?' . http_build_query( $query ) . ( isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '' );
}
require __DIR__ . '/../inc/language.php';
$checks = 0;
function check( $condition, $message ) {
    global $checks;
    ++$checks;
    if ( ! $condition ) throw new RuntimeException( $message );
}
function reset_request() {
    $_GET = [];
    $_COOKIE = [];
    $_REQUEST = [];
    $_SERVER['REQUEST_URI'] = '/';
    $GLOBALS['test_admin'] = false;
    $GLOBALS['test_ajax'] = false;
    $GLOBALS['test_cart'] = false;
    $GLOBALS['test_checkout'] = false;
    $GLOBALS['test_front_page'] = false;
    $GLOBALS['test_shop'] = false;
    $GLOBALS['test_queried_object'] = null;
}
reset_request();
check( idguard_shop_language() === 'da', 'Unspecified storefront keeps Danish.' );
foreach ( [ 'da', 'en', 'sv', 'no', 'nl', 'fi' ] as $language ) {
    reset_request();
    $_GET['lang'] = $language;
    check( idguard_shop_language() === $language, 'Explicit language is selected.' );
    check( idguard_shop_t( 'shop' ) !== 'shop', 'Every locale has real UI copy.' );
    $url = idguard_shop_url( 'https://teststore.idguard.dk/cart/?coupon=sale' );
    parse_str( parse_url( $url, PHP_URL_QUERY ), $query );
    check( $query['lang'] === $language && $query['coupon'] === 'sale', 'Safe URLs retain non-language arguments.' );
    check( $GLOBALS['test_hooks']['pre_option_idguard_popup_title'][0]( 'merchant-saved-title' ) === ( $language === 'da' ? 'merchant-saved-title' : idguard_shop_t( 'popup_title' ) ), 'Danish merchant configuration is preserved; foreign popup text is request-only.' );
}
reset_request();
$cod_copy = [ 'enabled' => 'yes', 'title' => 'Efterkrav', 'description' => 'Betal med kontanter ved levering.', 'instructions' => 'Betal med kontanter ved levering.', 'custom_field' => 'keep' ];
check( $GLOBALS['test_hooks']['option_woocommerce_cod_settings'][0]( $cod_copy ) === $cod_copy, 'Danish COD settings remain unchanged.' );
$_GET['lang'] = 'fi';
$localized_cod = $GLOBALS['test_hooks']['option_woocommerce_cod_settings'][0]( $cod_copy );
check( $localized_cod['title'] === idguard_shop_t( 'payment_cod_title' ) && $localized_cod['description'] === idguard_shop_t( 'payment_cod_description' ) && $localized_cod['instructions'] === idguard_shop_t( 'payment_cod_instructions' ), 'Known public COD copy is localized on foreign storefront requests.' );
check( $localized_cod['enabled'] === 'yes' && $localized_cod['custom_field'] === 'keep', 'COD localization preserves enabled state and all unknown settings.' );
$custom_cod = $cod_copy;
$custom_cod['title'] = 'Merchant title';
$custom_cod['description'] = 'Merchant description';
$custom_cod['instructions'] = 'Merchant instructions';
check( $GLOBALS['test_hooks']['option_woocommerce_cod_settings'][0]( $custom_cod ) === $custom_cod, 'Custom merchant COD copy is never overwritten.' );
reset_request();
$_COOKIE['idguard_teststore_language'] = 'fi';
check( idguard_shop_language() === 'fi', 'Cookie persists language across cart/checkout.' );
$_GET['lang'] = 'da';
check( idguard_shop_language() === 'da', 'Explicit Danish overrides foreign cookie.' );
foreach ( [ [ 'fi' ], '<script>', '../en', 'nb', 'en_US', '', null ] as $invalid ) {
    reset_request();
    $_GET['lang'] = $invalid;
    $_COOKIE['idguard_teststore_language'] = $invalid;
    check( idguard_shop_language() === 'da', 'Invalid language input is never reflected.' );
}
reset_request();
$_GET['lang'] = 'fi';
foreach ( [ 'https://evil.example/cart/', '//evil.example/cart/', 'javascript:alert(1)', 'https://teststore.idguard.dk.evil.example/' ] as $external ) {
    check( idguard_shop_url( $external ) === $external, 'External URLs are not rewritten.' );
}
$_SERVER['REQUEST_URI'] = '/cart/?id_token=secret&vtoken=secret&verification_success=true&lang=fi';
$language_url = idguard_shop_language_url( 'sv' );
check( $language_url === 'https://teststore.idguard.dk/cart/?lang=sv', 'Language links never forward callback secrets.' );
$_SERVER['REQUEST_URI'] = '/checkout/order-received/123/?key=secret';
check( idguard_shop_language_url( 'sv' ) === 'https://teststore.idguard.dk/?lang=sv', 'Private order URLs are not propagated.' );
$_SERVER['REQUEST_URI'] = '/cart/';
$GLOBALS['test_admin'] = true;
check( ! idguard_shop_frontend_request(), 'Admin locale is untouched.' );
check( idguard_shop_language() === 'da', 'Store cookie does not select admin locale.' );
$GLOBALS['test_ajax'] = true;
$_REQUEST['action'] = 'heartbeat';
check( ! idguard_shop_frontend_request(), 'Unrelated admin AJAX is untouched.' );
$_REQUEST['action'] = 'woocommerce_get_refreshed_fragments';
check( idguard_shop_frontend_request(), 'Storefront WooCommerce AJAX keeps language.' );
$_REQUEST['action'] = 'woocommerce_json_search_products';
check( ! idguard_shop_frontend_request(), 'Administrative WooCommerce product search does not select storefront language.' );
$_REQUEST['action'] = 'idguard_admin_reset_demo';
check( ! idguard_shop_frontend_request(), 'Administrative plugin AJAX actions do not select storefront language.' );
reset_request();
$_COOKIE['idguard_teststore_language'] = 'fi';
foreach ( [ '/wp-login.php', '/wp-signup.php', '/wp-activate.php', '/xmlrpc.php', '/wp-json/wp/v2/posts' ] as $path ) {
    $_SERVER['REQUEST_URI'] = $path;
    check( ! idguard_shop_frontend_request(), 'Non-store endpoints preserve original locale and data.' );
}
$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart';
check( idguard_shop_frontend_request() && idguard_shop_language() === 'fi', 'Block checkout Store API uses the storefront cookie.' );
reset_request();
$_GET['rest_route'] = '/wp/v2/posts';
check( ! idguard_shop_frontend_request(), 'Plain-permalink editing API is untouched.' );
$_GET['rest_route'] = '/wc/store/v1/cart';
check( idguard_shop_frontend_request(), 'Plain-permalink Store API is localized.' );
class TestRestRequest { private $route; public function __construct( $route ) { $this->route = $route; } public function get_route() { return $this->route; } }
class TestRestResponse {
    private $headers = [ 'Vary' => 'Origin' ];
    public function header( $name, $value ) { $this->headers[ $name ] = $value; }
    public function get_headers() { return $this->headers; }
}
$_GET['lang'] = 'fi';
$response = new TestRestResponse();
check( idguard_shop_store_api_response_headers( $response, null, new TestRestRequest( '/wc/store/v1/products' ) ) === $response, 'Store API response is returned after adding request headers.' );
$rest_headers = $response->get_headers();
check( $rest_headers['Cache-Control'] === 'private, no-store, no-cache, must-revalidate, max-age=0', 'Store API response cannot be cached across language cookies.' );
check( $rest_headers['Content-Language'] === 'fi' && $rest_headers['Vary'] === 'Origin, Cookie', 'Store API response reports its language and varies by origin and cookie.' );
check( idguard_shop_store_api_vary_value( [ 'Vary: Origin', 'Vary: Accept-Encoding', 'Vary: Cookie' ] ) === 'Origin, Accept-Encoding, Cookie', 'Late Store API Vary merge retains all current values without duplicates.' );
check( isset( $GLOBALS['test_hooks']['rest_pre_serve_request'] ), 'Late Store API wire-header filter is registered.' );
$editor_response = new TestRestResponse();
idguard_shop_store_api_response_headers( $editor_response, null, new TestRestRequest( '/wp/v2/products' ) );
check( $editor_response->get_headers() === [ 'Vary' => 'Origin' ], 'Non-Store REST responses do not receive storefront headers.' );
reset_request();
$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/products';
$_COOKIE['idguard_teststore_language'] = 'no';
$response = new TestRestResponse();
idguard_shop_store_api_response_headers( $response, null, new TestRestRequest( '/wc/store/v1/products' ) );
check( $response->get_headers()['Content-Language'] === 'nb', 'Pretty-permalink Store API uses the storefront cookie locale.' );
reset_request();
$_GET['lang'] = 'fi';
$GLOBALS['test_cart'] = true;
$heading = '<h2 class="wp-block-heading" style="margin-bottom:1rem">You may be interested in&hellip;</h2>';
$translated_heading = idguard_shop_cart_heading_block( $heading, [] );
check( strpos( $translated_heading, '<h2 class="wp-block-heading" style="margin-bottom:1rem">' ) === 0 && strpos( $translated_heading, '</h2>' ) !== false, 'Cart heading translation preserves block markup and attributes.' );
check( strpos( $translated_heading, 'Saatat olla kiinnostunut myös näistä…' ) !== false, 'Known persisted stock cart heading is translated.' );
$_GET['lang'] = 'da';
foreach ( [ 'Your cart is currently empty!' => 'Din kurv er tom.', 'New in store' => 'Nyheder i butikken', 'You may be interested in&hellip;' => 'Du kunne også være interesseret i…' ] as $stock_heading => $danish_copy ) {
    $html = '<h2 class="wp-block-heading has-text-align-center">' . $stock_heading . '</h2>';
    check( strpos( idguard_shop_cart_heading_block( $html, [] ), $danish_copy ) !== false, 'Known English stock cart heading is translated on Danish storefront.' );
}
$_GET['lang'] = 'fi';
check( idguard_shop_cart_heading_block( '<h2>Custom merchant heading</h2>', [] ) === '<h2>Custom merchant heading</h2>', 'Custom cart headings remain unchanged.' );
$GLOBALS['test_cart'] = false;
check( idguard_shop_cart_heading_block( '<h2>New in store</h2>', [] ) === '<h2>New in store</h2>', 'Stock phrase outside cart and checkout remains unchanged.' );
reset_request();
$_GET['lang'] = 'fi';
$GLOBALS['test_front_page'] = true;
$title_parts = [ 'title' => '18+ Shoppen', 'tagline' => 'IDguard testbutik', 'page' => 'Page 2' ];
$localized_parts = idguard_shop_document_title_parts( $title_parts );
check( $localized_parts['title'] === '18+ ' . idguard_shop_t( 'shop_name' ) && $localized_parts['tagline'] === idguard_shop_t( 'site_tagline' ), 'Homepage document title matches the localized header brand and tagline.' );
check( $localized_parts['page'] === 'Page 2', 'Document title localization preserves pagination parts.' );
reset_request();
$_GET['lang'] = 'fi';
$GLOBALS['test_queried_object'] = (object) [ 'post_type' => 'page', 'post_name' => 'cart' ];
$localized_parts = idguard_shop_document_title_parts( [ 'title' => 'Cart', 'site' => '18+ Shoppen', 'page' => 'Page 2' ] );
check( $localized_parts['title'] === idguard_shop_t( 'page_cart' ) && $localized_parts['site'] === '18+ ' . idguard_shop_t( 'shop_name' ) && $localized_parts['page'] === 'Page 2', 'Known cart title and brand match the localized storefront while pagination remains unchanged.' );
foreach ( [ 'shop' => 'shop', 'checkout' => 'page_checkout', 'my-account' => 'page_account' ] as $slug => $key ) {
    $GLOBALS['test_queried_object'] = (object) [ 'post_type' => 'page', 'post_name' => $slug ];
    $known_parts = idguard_shop_document_title_parts( [ 'title' => ucfirst( $slug ), 'site' => '18+ Shoppen' ] );
    check( $known_parts['title'] === idguard_shop_t( $key ), 'Known storefront page document title is translated: ' . $slug );
}
$GLOBALS['test_queried_object'] = (object) [ 'post_type' => 'page', 'post_name' => 'privacy-policy' ];
$custom_title_parts = [ 'title' => 'Privacy policy', 'site' => '18+ Shoppen' ];
check( idguard_shop_document_title_parts( $custom_title_parts ) === $custom_title_parts, 'Unknown custom page document title remains unchanged.' );
$GLOBALS['test_queried_object'] = (object) [ 'post_type' => 'product', 'post_name' => 'rodvin', 'post_title' => 'Rødvin - Château Test 2020' ];
$localized_parts = idguard_shop_document_title_parts( [ 'title' => 'Rødvin - Château Test 2020', 'site' => '18+ Shoppen' ] );
check( $localized_parts['title'] === idguard_shop_t( 'product_wine_name' ) && $localized_parts['site'] === '18+ ' . idguard_shop_t( 'shop_name' ), 'Known product document title and brand match their storefront translations.' );
$GLOBALS['test_queried_object'] = (object) [ 'post_type' => 'page', 'post_name' => 'cart' ];
$unbranded_title_parts = [ 'title' => 'Cart', 'site' => 'Custom Shop' ];
check( idguard_shop_document_title_parts( $unbranded_title_parts ) === $unbranded_title_parts, 'Document title filter leaves a custom site brand untouched.' );
$GLOBALS['test_admin'] = true;
$GLOBALS['test_front_page'] = true;
check( idguard_shop_document_title_parts( $title_parts ) === $title_parts, 'Administrative document titles remain unchanged.' );
class TestProduct { public function __construct( private string $name ) {} public function get_data() { return [ 'name' => $this->name ]; } }
reset_request();
$_GET['lang'] = 'fi';
check( idguard_shop_product_translation( 'original', new TestProduct( 'Rødvin - Château Test 2020' ), 'name' ) === idguard_shop_t( 'product_wine_name' ), 'Known demo product is translated.' );
check( idguard_shop_product_translation( 'original', new TestProduct( 'An unrelated product' ), 'name' ) === 'original', 'Unrelated products remain untouched.' );
$GLOBALS['test_admin'] = true;
check( idguard_shop_product_translation( 'original', new TestProduct( 'Rødvin - Château Test 2020' ), 'name' ) === 'original', 'Administrative product data is never translated.' );
$dictionary = require __DIR__ . '/../inc/translations.php';
$keys = array_keys( $dictionary['da'] );
foreach ( $dictionary as $language => $copy ) {
    check( array_keys( $copy ) === $keys, 'All languages have identical copy keys.' );
    foreach ( $copy as $key => $value ) check( is_string( $value ) && $value !== '', 'Every translation is nonempty.' );
}
echo $checks . " language/boundary assertions passed.\n";
