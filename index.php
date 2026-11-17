<?php get_header(); ?>

<div class="site-content">
    <main class="site-main">
        <div class="book-grid">
            <?php
            if ( have_posts() ) :
                while ( have_posts() ) :
                    the_post();
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                                <?php the_post_thumbnail( 'large' ); ?>
                            </a>
                        <?php else : ?>
                            <!-- Fallback if a post has no featured image -->
                            <div style="background: #D3D3D3; padding: 20px; text-align: center; height: 100%;">
                                <a href="<?php the_permalink(); ?>" style="color: #fff; text-decoration: none;">
                                    <?php the_title(); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </article>
                    <?php
                endwhile;
            else :
                echo '<p>No books found.</p>';
            endif;
            ?>
        </div>
        
        <!-- Pagination -->
        <div class="pagination" style="margin-top: 40px; text-align: center;">
            <?php the_posts_pagination(); ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>