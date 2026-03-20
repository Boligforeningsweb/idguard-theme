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
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <span class="logo-age">18+</span> Shoppen
                </a>
            </div>
            <nav class="header-nav">
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Shop</a>
                <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="cart-link">
                    Kurv (<span class="cart-count"><?php echo idguard_cart_count(); ?></span>)
                </a>
            </nav>
        </div>
    </header>
    <div class="test-banner">
        Psst — det her er en testshop. Dit kort bliver ikke trukket, og vinen er desværre ikke rigtig.
    </div>
