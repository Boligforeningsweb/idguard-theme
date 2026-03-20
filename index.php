<?php get_header(); ?>

<main class="site-content">
    <?php if (is_front_page()): ?>

        <div class="hero">
            <h1>Vi tjekker om du er gammel nok<span class="logo-dot">.</span><br>Du tjekker om vinen er god nok<span class="logo-dot">.</span></h1>
            <p class="subtitle">Aldersverifikation med MitID. Hurtigt, sikkert, og uden at spørge om dit CPR-nummer ved middagsbordet.</p>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="cta-btn">Se vores (test)produkter</a>
        </div>

        <div class="products-section">
            <h2 class="section-title">Udvalgte produkter</h2>
            <?php echo do_shortcode('[products limit="6" columns="3" orderby="date"]'); ?>
        </div>

    <?php else: ?>

        <?php
        if (have_posts()):
            while (have_posts()): the_post();
                the_content();
            endwhile;
        endif;
        ?>

    <?php endif; ?>
</main>

<?php get_footer(); ?>
