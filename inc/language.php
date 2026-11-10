<?php
/** Request-local storefront languages. No WordPress options are changed. */
if ( ! defined( 'ABSPATH' ) ) exit;

function idguard_shop_languages() {
    return [
        'da' => [ 'locale' => 'da_DK', 'name' => 'Dansk' ],
        'en' => [ 'locale' => 'en_GB', 'name' => 'English' ],
        'sv' => [ 'locale' => 'sv_SE', 'name' => 'Svenska' ],
        'no' => [ 'locale' => 'nb_NO', 'name' => 'Norsk bokmål' ],
        'nl' => [ 'locale' => 'nl_NL', 'name' => 'Nederlands' ],
        'fi' => [ 'locale' => 'fi', 'name' => 'Suomi' ],
    ];
}

function idguard_shop_valid_language( $value ) {
    return is_string( $value ) && isset( idguard_shop_languages()[ $value ] );
}

function idguard_shop_frontend_request() {
    if ( ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) return false;
    $request_path = parse_url( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH );
    if ( is_string( $request_path ) && preg_match( '~/(wp-login|wp-signup|wp-activate|xmlrpc)\.php$~', $request_path ) ) return false;
    if ( function_exists( 'is_admin' ) && is_admin() ) {
        $action = isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
        // Only WooCommerce's public cart-fragment endpoint is needed by the
        // storefront. Prefix matching also admits editor/admin product search.
        return function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() && $action === 'woocommerce_get_refreshed_fragments';
    }
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    $rest_route = isset( $_GET['rest_route'] ) && is_string( $_GET['rest_route'] ) ? $_GET['rest_route'] : '';
    // Editing REST requests keep their normal administrator language/data.
    if ( strpos( $uri, '/wp-json/' ) !== false || $rest_route !== '' ) {
        return strpos( $uri, '/wp-json/wc/store/' ) !== false || strpos( $rest_route, '/wc/store/' ) === 0;
    }
    return true;
}

function idguard_shop_language() {
    if ( ! idguard_shop_frontend_request() ) return 'da';
    if ( isset( $_GET['lang'] ) && idguard_shop_valid_language( $_GET['lang'] ) ) return $_GET['lang'];
    $stored = isset( $_COOKIE['idguard_teststore_language'] ) ? $_COOKIE['idguard_teststore_language'] : null;
    return idguard_shop_valid_language( $stored ) ? $stored : 'da';
}

function idguard_shop_locale() {
    return idguard_shop_languages()[ idguard_shop_language() ]['locale'];
}

function idguard_shop_t( $key ) {
    static $copy;
    if ( $copy === null ) $copy = require __DIR__ . '/translations.php';
    $language = idguard_shop_language();
    return isset( $copy[ $language ][ $key ] ) ? $copy[ $language ][ $key ] : ( isset( $copy['da'][ $key ] ) ? $copy['da'][ $key ] : $key );
}

function idguard_shop_url( $url, $language = null ) {
    $language = idguard_shop_valid_language( $language ) ? $language : idguard_shop_language();
    $target = wp_parse_url( $url );
    $home = wp_parse_url( home_url( '/' ) );
    // Never rewrite external, protocol-relative or executable URLs.
    if ( ! is_array( $target ) || ( isset( $target['scheme'] ) && ! in_array( strtolower( $target['scheme'] ), [ 'http', 'https' ], true ) ) ) return $url;
    if ( isset( $target['host'] ) && ( ! isset( $home['host'] ) || strtolower( $target['host'] ) !== strtolower( $home['host'] ) || ( $target['port'] ?? null ) !== ( $home['port'] ?? null ) ) ) return $url;
    if ( strpos( $url, '//' ) === 0 ) return $url;
    return add_query_arg( 'lang', $language, $url );
}

function idguard_shop_marketing_url() {
    $language = idguard_shop_language();
    return 'https://idguard.dk/' . ( $language === 'da' ? '' : $language . '/' );
}

function idguard_shop_language_url( $language ) {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $path = wp_parse_url( $uri, PHP_URL_PATH );
    if ( ! is_string( $path ) || strpos( $path, '/' ) !== 0 || strpos( $path, '//' ) === 0 ) $path = '/';
    // Language navigation never copies callback tokens, order keys or nonces.
    if ( preg_match( '~/(order-received|order-pay|view-order)/~', $path ) ) $path = '/';
    return idguard_shop_url( home_url( $path ), $language );
}

function idguard_shop_switch_locale() {
    if ( ! idguard_shop_frontend_request() ) return;
    // The locale switcher reloads already-loaded core/plugin translations. It
    // requires the official language packs installed by scripts/install-languages.sh.
    switch_to_locale( idguard_shop_locale() );
}
add_action( 'after_setup_theme', 'idguard_shop_switch_locale', 0 );

function idguard_shop_language_headers() {
    if ( ! idguard_shop_frontend_request() ) return;
    if ( ! defined( 'DONOTCACHEPAGE' ) ) define( 'DONOTCACHEPAGE', true );
    nocache_headers();
    header( 'Vary: Cookie', false );
    header( 'Content-Language: ' . ( idguard_shop_language() === 'no' ? 'nb' : idguard_shop_language() ) );
    if ( isset( $_GET['lang'] ) && idguard_shop_valid_language( $_GET['lang'] ) && ! headers_sent() ) {
        setcookie( 'idguard_teststore_language', $_GET['lang'], [
            'expires' => 0, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax',
        ] );
    }
}
add_action( 'send_headers', 'idguard_shop_language_headers' );

/** Add the same request-specific cache/language metadata to native Store API responses. */
function idguard_shop_store_api_response_headers( $response, $server, $request ) {
    if ( ! is_object( $request ) || ! method_exists( $request, 'get_route' ) || strpos( $request->get_route(), '/wc/store/' ) !== 0 ) return $response;
    if ( ! idguard_shop_frontend_request() || ! is_object( $response ) || ! method_exists( $response, 'header' ) ) return $response;

    $vary = 'Origin, Cookie';
    if ( method_exists( $response, 'get_headers' ) ) {
        $headers = $response->get_headers();
        foreach ( $headers as $name => $value ) {
            if ( strtolower( $name ) !== 'vary' ) continue;
            $values = array_map( 'trim', explode( ',', (string) $value ) );
            if ( ! in_array( 'Origin', $values, true ) ) $values[] = 'Origin';
            if ( ! in_array( 'Cookie', $values, true ) ) $values[] = 'Cookie';
            $vary = implode( ', ', $values );
            break;
        }
    }
    $response->header( 'Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0' );
    $response->header( 'Content-Language', idguard_shop_language() === 'no' ? 'nb' : idguard_shop_language() );
    $response->header( 'Vary', $vary );
    return $response;
}
add_filter( 'rest_post_dispatch', 'idguard_shop_store_api_response_headers', 20, 3 );

/** Collapse Store API Vary headers after WordPress's later CORS callback. */
function idguard_shop_store_api_vary_value( $header_lines ) {
    $values = [];
    foreach ( $header_lines as $line ) {
        if ( stripos( $line, 'Vary:' ) !== 0 ) continue;
        foreach ( explode( ',', trim( substr( $line, 5 ) ) ) as $value ) {
            $value = trim( $value );
            if ( $value !== '' && ! in_array( strtolower( $value ), array_map( 'strtolower', $values ), true ) ) $values[] = $value;
        }
    }
    foreach ( [ 'Origin', 'Cookie' ] as $required ) {
        if ( ! in_array( strtolower( $required ), array_map( 'strtolower', $values ), true ) ) $values[] = $required;
    }
    return implode( ', ', $values );
}

function idguard_shop_store_api_wire_vary( $served, $result, $request, $server ) {
    if ( ! is_object( $request ) || ! method_exists( $request, 'get_route' ) || strpos( $request->get_route(), '/wc/store/' ) !== 0 || ! idguard_shop_frontend_request() ) return $served;
    $header_lines = headers_list();
    if ( is_object( $result ) && method_exists( $result, 'get_headers' ) ) {
        foreach ( $result->get_headers() as $name => $value ) {
            if ( strtolower( $name ) === 'vary' ) {
                foreach ( (array) $value as $vary ) $header_lines[] = 'Vary: ' . $vary;
            }
        }
    }
    header( 'Vary: ' . idguard_shop_store_api_vary_value( $header_lines ), true );
    return $served;
}
add_filter( 'rest_pre_serve_request', 'idguard_shop_store_api_wire_vary', 999, 4 );

/** Translate only the three persisted stock cart headings, leaving authored headings intact. */
function idguard_shop_cart_heading_block( $content, $block ) {
    if ( ! idguard_shop_frontend_request() ) return $content;
    if ( ! ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) ) return $content;
    $translations = [
        'Your cart is currently empty!' => 'cart_empty',
        'New in store' => 'cart_new_products',
        'You may be interested in...' => 'cart_cross_sell',
        'You may be interested in…' => 'cart_cross_sell',
    ];
    return preg_replace_callback( '/(<h[1-6]\\b[^>]*>)(.*?)(<\\/h[1-6]>)/s', function ( $matches ) use ( $translations ) {
        $text = html_entity_decode( strip_tags( $matches[2] ), ENT_QUOTES, 'UTF-8' );
        if ( ! isset( $translations[ $text ] ) ) return $matches[0];
        return $matches[1] . esc_html( idguard_shop_t( $translations[ $text ] ) ) . $matches[3];
    }, $content );
}
add_filter( 'render_block_core/heading', 'idguard_shop_cart_heading_block', 20, 2 );

function idguard_shop_language_picker() {
    ?>
    <details class="shop-language">
        <summary aria-label="<?php echo esc_attr( idguard_shop_t( 'language' ) ); ?>"><?php echo esc_html( strtoupper( idguard_shop_language() ) ); ?> <span aria-hidden="true">⌄</span></summary>
        <ul aria-label="<?php echo esc_attr( idguard_shop_t( 'change_language' ) ); ?>">
        <?php foreach ( idguard_shop_languages() as $language => $settings ) : ?>
            <li><a href="<?php echo esc_url( idguard_shop_language_url( $language ) ); ?>" lang="<?php echo esc_attr( $language === 'no' ? 'nb' : $language ); ?>" hreflang="<?php echo esc_attr( $language === 'no' ? 'nb' : $language ); ?>"<?php if ( $language === idguard_shop_language() ) echo ' aria-current="true"'; ?>><?php echo esc_html( $settings['name'] ); ?></a></li>
        <?php endforeach; ?>
        </ul>
    </details>
    <?php
}

function idguard_shop_localized_url_filter( $url ) {
    return idguard_shop_frontend_request() ? idguard_shop_url( $url ) : $url;
}
foreach ( [ 'woocommerce_get_cart_url', 'woocommerce_get_checkout_url', 'woocommerce_get_shop_page_permalink', 'woocommerce_get_cart_page_permalink', 'woocommerce_get_checkout_page_permalink', 'woocommerce_get_myaccount_page_permalink', 'woocommerce_product_add_to_cart_url' ] as $hook ) {
    add_filter( $hook, 'idguard_shop_localized_url_filter', 20 );
}
add_filter( 'post_type_link', function ( $url, $post ) {
    return $post->post_type === 'product' ? idguard_shop_localized_url_filter( $url ) : $url;
}, 20, 2 );

function idguard_shop_demo_product_key( $name ) {
    $names = [
        'Rødvin - Château Test 2020' => 'wine', 'Whisky - Single Malt Test' => 'whisky',
        'Økologisk Æblejuice' => 'juice', 'CBD Olie 10%' => 'cbd',
        'Gavekort 500 kr' => 'gift', 'E-cigaret Startkit' => 'vape',
    ];
    return isset( $names[ $name ] ) ? $names[ $name ] : null;
}

function idguard_shop_product_translation( $original, $product, $field ) {
    if ( ! idguard_shop_frontend_request() || idguard_shop_language() === 'da' ) return $original;
    $data = $product->get_data();
    $key = idguard_shop_demo_product_key( $data['name'] ?? '' );
    return $key ? idguard_shop_t( 'product_' . $key . '_' . $field ) : $original;
}
add_filter( 'woocommerce_product_get_name', function ( $value, $product ) { return idguard_shop_product_translation( $value, $product, 'name' ); }, 20, 2 );
add_filter( 'woocommerce_product_get_short_description', function ( $value, $product ) { return idguard_shop_product_translation( $value, $product, 'description' ); }, 20, 2 );
add_filter( 'woocommerce_product_get_description', function ( $value, $product ) { return idguard_shop_product_translation( $value, $product, 'description' ); }, 20, 2 );

add_filter( 'the_title', function ( $title, $id ) {
    if ( ! idguard_shop_frontend_request() ) return $title;
    $post = get_post( $id );
    if ( ! $post ) return $title;
    if ( $post->post_type === 'product' && idguard_shop_language() !== 'da' ) {
        $key = idguard_shop_demo_product_key( $post->post_title );
        if ( $key ) return idguard_shop_t( 'product_' . $key . '_name' );
    }
    if ( $post->post_type === 'page' ) {
        $pages = [ 'shop' => 'shop', 'cart' => 'page_cart', 'checkout' => 'page_checkout', 'my-account' => 'page_account' ];
        if ( isset( $pages[ $post->post_name ] ) ) return idguard_shop_t( $pages[ $post->post_name ] );
    }
    return $title;
}, 20, 2 );
add_filter( 'woocommerce_page_title', function ( $title ) { return idguard_shop_frontend_request() && is_shop() ? idguard_shop_t( 'shop' ) : $title; } );

/** Localize only the known storefront document-title parts and retain the brand/page number. */
function idguard_shop_document_title_parts( $parts ) {
    if ( ! idguard_shop_frontend_request() || ! is_array( $parts ) ) return $parts;
    $post = function_exists( 'get_queried_object' ) ? get_queried_object() : null;
    $front_page = function_exists( 'is_front_page' ) && is_front_page();
    $page_keys = [ 'shop' => 'shop', 'cart' => 'page_cart', 'checkout' => 'page_checkout', 'my-account' => 'page_account' ];
    $page_key = is_object( $post ) && isset( $post->post_type, $post->post_name ) && $post->post_type === 'page' && isset( $page_keys[ $post->post_name ] )
        ? $page_keys[ $post->post_name ]
        : null;
    $is_shop = function_exists( 'is_shop' ) && is_shop();
    $product_key = is_object( $post ) && isset( $post->post_type, $post->post_title ) && $post->post_type === 'product'
        ? idguard_shop_demo_product_key( $post->post_title )
        : null;

    // Unknown content keeps its entire document title, including the site name.
    if ( ! $front_page && $page_key === null && ! $is_shop && $product_key === null ) return $parts;
    $brand_key = $front_page ? 'title' : 'site';
    if ( ! isset( $parts[ $brand_key ] ) || $parts[ $brand_key ] !== '18+ Shoppen' ) return $parts;
    $localized_brand = '18+ ' . idguard_shop_t( 'shop_name' );
    $parts[ $brand_key ] = $localized_brand;

    if ( $front_page ) {
        if ( isset( $parts['tagline'] ) && $parts['tagline'] === 'IDguard testbutik' ) $parts['tagline'] = idguard_shop_t( 'site_tagline' );
        return $parts;
    }
    if ( ! isset( $parts['title'] ) || ! is_string( $parts['title'] ) ) return $parts;
    if ( $page_key !== null ) {
        $parts['title'] = idguard_shop_t( $page_key );
        return $parts;
    }
    if ( $is_shop ) {
        $parts['title'] = idguard_shop_t( 'shop' );
        return $parts;
    }
    if ( $product_key !== null ) {
        $parts['title'] = idguard_shop_t( 'product_' . $product_key . '_name' );
        return $parts;
    }
    return $parts;
}
add_filter( 'document_title_parts', 'idguard_shop_document_title_parts', 20 );

// Existing merchant-editable popup options are translated for this demo only.
// Danish values, verification settings, authentication and plugin files are untouched.
foreach ( [ 'idguard_popup_title' => 'popup_title', 'idguard_popup_message' => 'popup_message', 'idguard_popup_button_text' => 'popup_confirm', 'idguard_popup_cancel_button_text' => 'popup_cancel', 'idguard_badge_text' => 'popup_badge' ] as $option => $key ) {
    add_filter( 'pre_option_' . $option, function ( $value ) use ( $key ) {
        return idguard_shop_frontend_request() && idguard_shop_language() !== 'da' ? idguard_shop_t( $key ) : $value;
    } );
}

/** Translate only the known public COD copy for storefront reads; preserve all gateway settings. */
function idguard_shop_cod_settings_copy( $settings ) {
    if ( ! idguard_shop_frontend_request() || idguard_shop_language() === 'da' || ! is_array( $settings ) ) return $settings;
    $known_copy = [
        'title' => [ 'Efterkrav' => 'payment_cod_title' ],
        'description' => [ 'Betal med kontanter ved levering.' => 'payment_cod_description' ],
        'instructions' => [ 'Betal med kontanter ved levering.' => 'payment_cod_instructions' ],
    ];
    foreach ( $known_copy as $field => $known_values ) {
        if ( isset( $settings[ $field ] ) && is_string( $settings[ $field ] ) && isset( $known_values[ $settings[ $field ] ] ) ) {
            $settings[ $field ] = idguard_shop_t( $known_values[ $settings[ $field ] ] );
        }
    }
    return $settings;
}
add_filter( 'option_woocommerce_cod_settings', 'idguard_shop_cod_settings_copy', 20 );


function idguard_shop_enqueue_presentation() {
    if ( ! idguard_shop_frontend_request() ) return;
    wp_enqueue_script( 'idguard-shop-language', get_template_directory_uri() . '/assets/language.js', [], wp_get_theme()->get( 'Version' ), true );
    $alerts = [
        'Du skal verificere din alder for at fortsætte til kassen.' => idguard_shop_t( 'alert_verify_required' ),
        'Konfigurationsfejl: Ugyldig aldersgrænse. Kontakt venligst support.' => idguard_shop_t( 'alert_invalid_age' ),
        'Du afbrød verifikationsprocessen. Du skal verificere din alder for at fortsætte.' => idguard_shop_t( 'alert_cancelled' ),
        'Ingen ID token indstillet eller ukendt fejl. Kontakt support.' => idguard_shop_t( 'alert_token_error' ),
    ];
    wp_localize_script( 'idguard-shop-language', 'idguardShopPresentation', [
        'language' => idguard_shop_language(), 'alerts' => $alerts,
        'popupAria' => idguard_shop_t( 'popup_aria' ), 'popupLoading' => idguard_shop_t( 'popup_loading' ),
        'popupClose' => idguard_shop_t( 'popup_close' ),
    ] );
}
// Register before the existing plugin's frontend script, including error alerts
// that can occur immediately while that script initializes.
add_action( 'wp_enqueue_scripts', 'idguard_shop_enqueue_presentation', 1 );
