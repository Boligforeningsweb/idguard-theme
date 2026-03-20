<?php get_header(); ?>

<main class="site-content">
    <?php
    while (have_posts()): the_post();
        the_title('<h1 style="font-size:2em;font-weight:700;margin-bottom:24px;letter-spacing:-0.5px">', '</h1>');
        the_content();
    endwhile;
    ?>
</main>

<?php get_footer(); ?>
