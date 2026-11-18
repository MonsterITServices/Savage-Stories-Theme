<?php get_header(); ?>

<div class="site-content">
    <main class="site-main" style="padding: 40px;">
        <?php while ( have_posts() ) : the_post(); ?>
            
            <div class="book-content-area">
                <?php 
                // This outputs the WPBakery content, including your shortcodes
                the_content(); 
                ?>
            </div>

            <!-- Back to Top Button -->
            <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #333;">
                <button onclick="window.scrollTo({top: 0, behavior: 'smooth'});" class="book-btn back-to-top">&#8679; Back to Top</button>
            </div>

            <?php
            // Show comments on the final chapter (or if the book is a single page)
            global $page, $numpages;
            $curr_page   = ( isset( $page ) && $page > 0 ) ? (int) $page : 1;
            $total_pages = ( isset( $numpages ) && $numpages > 0 ) ? (int) $numpages : 1;

            if ( $curr_page === $total_pages && ( comments_open() || get_comments_number() ) ) :
                comments_template();
            endif;
            ?>

        <?php endwhile; ?>
    </main>
</div>

<?php get_footer(); ?>