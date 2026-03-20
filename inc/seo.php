<?php
/**
 * IDguard SEO — Open Graph, schema markup, meta tags, favicon, preconnect.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Output Open Graph and meta tags in <head>.
 */
function idguard_meta_tags() {
    $site_name   = get_bloginfo( 'name' );
    $description = 'Testshop for IDguard — aldersverifikation med MitID. Køb aldersbegrænsede produkter sikkert online.';
    $og_type     = 'website';
    $url         = home_url( $_SERVER['REQUEST_URI'] );
    $og_image    = get_template_directory_uri() . '/assets/images/og-image.jpg';
    $title       = wp_get_document_title();

    // Product-specific OG tags
    if ( is_singular( 'product' ) ) {
        global $post;
        $product = wc_get_product( $post->ID );
        if ( $product ) {
            $og_type     = 'product';
            $description = $product->get_short_description() ?: $description;
            $description = wp_strip_all_tags( $description );

            $thumb_id = get_post_thumbnail_id( $post->ID );
            if ( $thumb_id ) {
                $og_image = wp_get_attachment_url( $thumb_id );
            }
        }
    }

    // Truncate description
    if ( strlen( $description ) > 160 ) {
        $description = substr( $description, 0, 157 ) . '...';
    }
    ?>

    <!-- SEO Meta -->
    <meta name="description" content="<?php echo esc_attr( $description ); ?>">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#111827">

    <!-- Open Graph -->
    <meta property="og:site_name" content="<?php echo esc_attr( $site_name ); ?>">
    <meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
    <meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
    <meta property="og:type" content="<?php echo esc_attr( $og_type ); ?>">
    <meta property="og:url" content="<?php echo esc_url( $url ); ?>">
    <meta property="og:image" content="<?php echo esc_url( $og_image ); ?>">
    <meta property="og:locale" content="da_DK">

    <?php if ( is_singular( 'product' ) && isset( $product ) ) : ?>
    <meta property="product:price:amount" content="<?php echo esc_attr( $product->get_price() ); ?>">
    <meta property="product:price:currency" content="DKK">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>">
    <meta name="twitter:image" content="<?php echo esc_url( $og_image ); ?>">

    <?php
}
add_action( 'wp_head', 'idguard_meta_tags', 1 );

/**
 * Output favicon links.
 */
function idguard_favicon() {
    $favicon_svg = get_template_directory_uri() . '/assets/images/favicon.svg';
    ?>
    <link rel="icon" type="image/svg+xml" href="<?php echo esc_url( $favicon_svg ); ?>">
    <link rel="apple-touch-icon" href="<?php echo esc_url( $favicon_svg ); ?>">
    <?php
}
add_action( 'wp_head', 'idguard_favicon', 2 );

/**
 * Preconnect to external origins for performance.
 */
function idguard_preconnect() {
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
}
add_action( 'wp_head', 'idguard_preconnect', 0 );

/**
 * Output JSON-LD schema markup.
 */
function idguard_schema_markup() {
    // WebSite schema on all pages
    $website_schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => get_bloginfo( 'name' ),
        'url'      => home_url( '/' ),
    ];

    echo '<script type="application/ld+json">' . wp_json_encode( $website_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";

    // Organization schema on front page
    if ( is_front_page() ) {
        $org_schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => 'IDguard',
            'url'      => 'https://idguard.dk',
            'logo'     => get_template_directory_uri() . '/assets/images/og-image.jpg',
            'description' => 'Aldersverifikation med MitID for danske webshops.',
        ];
        echo '<script type="application/ld+json">' . wp_json_encode( $org_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }

    // Product schema on single product pages
    if ( is_singular( 'product' ) ) {
        global $post;
        $product = wc_get_product( $post->ID );
        if ( ! $product ) return;

        $product_schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $product->get_name(),
            'description' => wp_strip_all_tags( $product->get_short_description() ),
            'url'         => get_permalink( $post->ID ),
            'offers'      => [
                '@type'         => 'Offer',
                'price'         => $product->get_price(),
                'priceCurrency' => 'DKK',
                'availability'  => $product->is_in_stock()
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url'           => get_permalink( $post->ID ),
            ],
        ];

        $thumb_id = get_post_thumbnail_id( $post->ID );
        if ( $thumb_id ) {
            $product_schema['image'] = wp_get_attachment_url( $thumb_id );
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $product_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }
}
add_action( 'wp_head', 'idguard_schema_markup', 5 );

/**
 * Disable WordPress emoji scripts (performance).
 */
function idguard_disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'idguard_disable_emojis' );

/**
 * Remove unnecessary WordPress head clutter.
 */
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );

/**
 * Add 'lang' attribute to html tag for accessibility/SEO.
 */
function idguard_html_lang( $output ) {
    return $output;
}
