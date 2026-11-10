<?php get_header(); ?>

<main class="site-content">
    <div class="hero">
        <h1><?php echo esc_html( idguard_shop_t( 'hero_title_age' ) ); ?><span class="logo-dot">.</span><br><?php echo esc_html( idguard_shop_t( 'hero_title_wine' ) ); ?><span class="logo-dot">.</span></h1>
        <p class="subtitle"><?php echo esc_html( idguard_shop_t( 'hero_subtitle' ) ); ?></p>
        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="cta-btn"><?php echo esc_html( idguard_shop_t( 'hero_cta' ) ); ?></a>
    </div>

    <div class="products-section">
        <h2 class="section-title"><?php echo esc_html( idguard_shop_t( 'selected_products' ) ); ?></h2>
        <?php echo do_shortcode('[products limit="6" columns="3" orderby="date"]'); ?>
    </div>
</main>

<?php get_footer(); ?>
