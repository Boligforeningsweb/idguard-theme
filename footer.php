    <footer class="site-footer">
        <div class="footer-inner">
            <p class="footer-brand"><span class="logo-age">18+</span> Shoppen</p>
            <p class="footer-tagline">En testshop til aldersverifikation med MitID.<br>Ingen rigtige produkter. Ingen rigtige leveringer. Kun rigtig alderskontrol.</p>
            <div class="footer-powered">
                <span>Powered by</span>
                <a href="https://idguard.dk">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/idguard-logo.png'); ?>" alt="IDguard" class="powered-logo">
                </a>
            </div>
            <div class="footer-links">
                <a href="https://idguard.dk">idguard.dk</a>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Shop</a>
            </div>
        </div>
    </footer>
</div><!-- .site-wrapper -->

<?php wp_footer(); ?>
</body>
</html>
