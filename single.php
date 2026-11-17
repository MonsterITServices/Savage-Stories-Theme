<?php get_header(); ?>

<div class="site-content">
    <!-- Narrower main container for optimal reading width on desktop -->
    <main class="site-main" style="padding: 40px 20px; max-width: 800px; margin: 0 auto;">
        <?php while ( have_posts() ) : the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                
                <!-- 1. Auto-generate the Title -->
                <h1 style="text-align: center; margin-bottom: 30px; font-family: 'Times New Roman', Times, serif; font-size: 28px; color: #000; text-transform: uppercase;">
                    <?php the_title(); ?>
                </h1>
                
                <!-- 2. Auto-generate the Featured Image (if the post has one) -->
                <?php if ( has_post_thumbnail() ) : ?>
                    <div style="text-align: center; margin-bottom: 30px;">
                        <?php the_post_thumbnail( 'large', array( 'style' => 'max-width: 100%; height: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.3);' ) ); ?>
                    </div>
                <?php endif; ?>

                <!-- 3. Output the existing raw content -->
                <div class="entry-content" style="font-size: 14px; line-height: 1.8; color: #000;">
                    <?php 
                    // This pulls your legacy text, or WPBakery layouts if used on future posts
                    the_content(); 
                    ?>
                </div>
                
            </article>

        <?php endwhile; ?>
    </main>
</div>

<?php get_footer(); ?>