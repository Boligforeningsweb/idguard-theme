<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="site-wrapper">
    <header class="site-header">
        <div class="header-inner">
            <div class="site-logo">
                <a href="<?php echo esc_url(idguard_shop_url(home_url('/'))); ?>">
                    <span class="logo-age">18+</span> <?php echo esc_html( idguard_shop_t( 'shop_name' ) ); ?>
                </a>
            </div>
            <nav class="header-nav" aria-label="<?php echo esc_attr( idguard_shop_t( 'navigation_label' ) ); ?>">
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php echo esc_html( idguard_shop_t( 'shop' ) ); ?></a>
                <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="cart-link">
                    <svg class="cart-icon" viewBox="0 0 24 24" aria-hidden="true" width="18" height="18"><path d="M3 3h2l3 12h10l3-8H6M10 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2m8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2" fill="none" stroke="currentColor" stroke-width="1.8"/></svg><span class="cart-label"><?php echo esc_html( idguard_shop_t( 'cart' ) ); ?></span> (<span class="cart-count"><?php echo idguard_cart_count(); ?></span>)
                </a>
                <?php idguard_shop_language_picker(); ?>
            </nav>
        </div>
    </header>
    <div class="test-banner">
        <?php echo esc_html( idguard_shop_t( 'test_banner' ) ); ?>
    </div>
