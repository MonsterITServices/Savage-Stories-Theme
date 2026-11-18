<?php get_header(); ?>

<div class="site-content">
    <!-- Narrower main container for optimal reading width on desktop -->
    <main class="site-main" style="padding: 40px 20px; max-width: 800px; margin: 0 auto;">
        <?php while ( have_posts() ) : the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                
                <?php if ( strpos( get_the_content(), '[vc_row' ) === false ) : ?>
                    <!-- 1. Auto-generate the Title for standard/legacy posts -->
                    <h1 style="text-align: center; margin-bottom: 30px; font-family: 'Times New Roman', Times, serif; font-size: 28px; color: #000; text-transform: uppercase;">
                        <?php the_title(); ?>
                    </h1>
                <?php endif; ?>

                <!-- 2. Output the existing raw content -->
                <div class="entry-content" style="font-size: 14px; line-height: 1.8; color: #000;">
                    <?php 
                    // This pulls your legacy text, or WPBakery layouts if used on future posts
                    the_content(); 
                    ?>
                </div>
                
            </article>

            <?php
            // 3. Load Comments if open or if there is at least one comment
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;
            ?>

        <?php endwhile; ?>
    </main>
</div>

<?php get_footer(); ?>