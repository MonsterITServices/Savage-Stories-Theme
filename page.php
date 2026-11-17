<?php get_header(); ?>

<div class="site-content">
    <main class="site-main" style="padding: 40px; border-radius: 4px;">
        <?php
        while ( have_posts() ) :
            the_post();
            // Only output the default H1 title if the page is NOT using WPBakery
            if ( strpos( get_the_content(), '[vc_row' ) === false ) : ?>
                <h1 style="color: #000; text-align: center; margin-bottom: 30px;"><?php the_title(); ?></h1>
            <?php endif;
            the_content();
        endwhile;
        ?>
    </main>
</div>

<?php get_footer(); ?>