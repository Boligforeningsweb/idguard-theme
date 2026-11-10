    <footer class="site-footer">
        <div class="footer-inner">
            <p class="footer-brand"><span class="logo-age">18+</span> <?php echo esc_html( idguard_shop_t( 'shop_name' ) ); ?></p>
            <p class="footer-tagline"><?php echo esc_html( idguard_shop_t( 'footer_tagline_a' ) ); ?><br><?php echo esc_html( idguard_shop_t( 'footer_tagline_b' ) ); ?></p>
            <div class="footer-powered">
                <span><?php echo esc_html( idguard_shop_t( 'powered_by' ) ); ?></span>
                <a href="<?php echo esc_url( idguard_shop_marketing_url() ); ?>">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/idguard-logo.png'); ?>" alt="IDguard" class="powered-logo">
                </a>
            </div>
            <div class="footer-links">
                <a href="<?php echo esc_url( idguard_shop_marketing_url() ); ?>">idguard.dk</a>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php echo esc_html( idguard_shop_t( 'shop' ) ); ?></a>
            </div>
        </div>
    </footer>
</div><!-- .site-wrapper -->

<?php wp_footer(); ?>
</body>
</html>
