<?php
function strangebooks_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    register_nav_menus( array(
        'primary' => __( 'Primary Header Menu', 'strangebooks' ),
        'footer'  => __( 'Footer Menu', 'strangebooks' ),
    ) );
}
add_action( 'after_setup_theme', 'strangebooks_setup' );

function strangebooks_widgets_init() {
    register_sidebar( array( 'name' => __( 'Footer Area 1 (Left)' ), 'id' => 'footer-1', 'before_widget' => '<div id="%1$s" class="widget %2$s">', 'after_widget' => '</div>', 'before_title' => '<h3 class="widget-title">', 'after_title' => '</h3>' ) );
    register_sidebar( array( 'name' => __( 'Footer Area 2 (Center)' ), 'id' => 'footer-2', 'before_widget' => '<div id="%1$s" class="widget %2$s">', 'after_widget' => '</div>', 'before_title' => '<h3 class="widget-title">', 'after_title' => '</h3>' ) );
    register_sidebar( array( 'name' => __( 'Footer Area 3 (Right)' ), 'id' => 'footer-3', 'before_widget' => '<div id="%1$s" class="widget %2$s">', 'after_widget' => '</div>', 'before_title' => '<h3 class="widget-title">', 'after_title' => '</h3>' ) );
}
add_action( 'widgets_init', 'strangebooks_widgets_init' );

function strangebooks_scripts() {
    // Cache-busting version timestamp ensures style.css changes load immediately
    $css_version = file_exists( get_stylesheet_directory() . '/style.css' ) ? filemtime( get_stylesheet_directory() . '/style.css' ) : '1.1';
    wp_enqueue_style( 'strangebooks-style', get_stylesheet_uri(), array(), $css_version );
}
add_action( 'wp_enqueue_scripts', 'strangebooks_scripts' );

/* --- 1. REGISTER 'BOOKS' CUSTOM POST TYPE --- */
function strangebooks_register_books_cpt() {
    register_post_type( 'book', array(
        'labels'      => array( 'name' => 'Books', 'singular_name' => 'Book', 'add_new_item' => 'Add New Book', 'edit_item' => 'Edit Book' ),
        'public'      => true,
        'has_archive' => true,
        'menu_icon'   => 'dashicons-book',
        'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'rewrite'     => array( 'slug' => 'books' ),
    ) );
}
add_action( 'init', 'strangebooks_register_books_cpt' );

/* --- 2. ADD CUSTOM FIELDS (META BOXES) --- */
function strangebooks_add_book_metaboxes() {
    add_meta_box( 'book_details', 'Book Details & Tracking', 'strangebooks_book_details_html', 'book', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'strangebooks_add_book_metaboxes' );

function strangebooks_book_details_html( $post ) {
    wp_nonce_field( 'save_book_details', 'book_details_nonce' );
    $chapters = get_post_meta( $post->ID, '_book_chapters', true );
    $pages = get_post_meta( $post->ID, '_book_pages', true );
    $tracking = get_post_meta( $post->ID, '_book_tracking', true );
    ?>
    <p><label for="book_chapters"><b>Total Chapters:</b></label><br>
    <input type="number" id="book_chapters" name="book_chapters" value="<?php echo esc_attr( $chapters ); ?>" style="width:100%;"></p>
    
    <p><label for="book_pages"><b>Total Pages:</b></label><br>
    <input type="number" id="book_pages" name="book_pages" value="<?php echo esc_attr( $pages ); ?>" style="width:100%;"></p>
    
    <p><label for="book_tracking"><b>End of Book Tracking Link:</b></label><br>
    <input type="url" id="book_tracking" name="book_tracking" value="<?php echo esc_url( $tracking ); ?>" style="width:100%;" placeholder="https://..."></p>
    <?php
}

function strangebooks_save_book_details( $post_id ) {
    if ( ! isset( $_POST['book_details_nonce'] ) || ! wp_verify_nonce( $_POST['book_details_nonce'], 'save_book_details' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['book_chapters'] ) ) update_post_meta( $post_id, '_book_chapters', sanitize_text_field( $_POST['book_chapters'] ) );
    if ( isset( $_POST['book_pages'] ) ) update_post_meta( $post_id, '_book_pages', sanitize_text_field( $_POST['book_pages'] ) );
    if ( isset( $_POST['book_tracking'] ) ) update_post_meta( $post_id, '_book_tracking', esc_url_raw( $_POST['book_tracking'] ) );
}
add_action( 'save_post_book', 'strangebooks_save_book_details' );

/* --- 3. CREATE [display_books] SHORTCODE --- */
function strangebooks_books_shortcode( $atts ) {
    $query = new WP_Query( array( 'post_type' => 'book', 'posts_per_page' => -1 ) );
    if ( ! $query->have_posts() ) return '<p>No books found.</p>';
    
    ob_start();
    echo '<div class="book-grid">';
    while ( $query->have_posts() ) : $query->the_post();
        $chapters = get_post_meta( get_the_ID(), '_book_chapters', true );
        $pages = get_post_meta( get_the_ID(), '_book_pages', true );
        ?>
        <article class="book-item" style="text-align: center;">
            <a href="<?php the_permalink(); ?>">
                <?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } else { echo '<div style="background:#222;height:300px;display:flex;align-items:center;justify-content:center;color:#fff;">No Cover</div>'; } ?>
            </a>
            <div class="book-stats" style="margin-top: 10px; display: flex; justify-content: center; gap: 5px;">
                <?php if ( $chapters ) echo '<span class="stat-btn">' . esc_html( $chapters ) . ' Chapters</span>'; ?>
                <?php if ( $pages ) echo '<span class="stat-btn">' . esc_html( $pages ) . ' Pages</span>'; ?>
            </div>
        </article>
        <?php
    endwhile;
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'display_books', 'strangebooks_books_shortcode' );

/* --- WPBAKERY DYNAMIC BUTTON SHORTCODES --- */

// 1. Chapter Dropdown (Smart Auto-Titles, Prioritizes "Chapter X" Headings)
function strangebooks_chapter_dropdown_shortcode() {
    global $page, $numpages, $pages;
    
    $html = '<select onchange="location = this.value;" style="padding:10px; background:#000; color:#fff; border:1px solid #333; border-radius:3px; cursor:pointer; font-family:Arial, sans-serif; min-width:200px;">';
    
    for ( $i = 1; $i <= $numpages; $i++ ) {
        $url = ( $i == 1 ) ? get_permalink() : get_permalink() . $i . '/';
        $selected = ( $i == $page ) ? 'selected' : '';
        
        $chapter_title = 'Chapter ' . $i; 
        
        if ( isset( $pages[$i-1] ) ) {
            $raw_content = $pages[$i-1];
            $found_headings = array();

            // Collect all WPBakery custom headings on this page
            if ( preg_match_all( '/\[vc_custom_heading[^\]]*text="([^"]+)"/i', $raw_content, $vc_matches ) ) {
                foreach ( $vc_matches[1] as $match ) {
                    $found_headings[] = sanitize_text_field( $match );
                }
            }
            // Collect any standard HTML H1-H3 headings on this page
            if ( preg_match_all( '/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $raw_content, $h_matches ) ) {
                foreach ( $h_matches[1] as $match ) {
                    $found_headings[] = trim( strip_tags( $match ) );
                }
            }

            if ( ! empty( $found_headings ) ) {
                // Default to the first heading found on the page
                $chapter_title = $found_headings[0];
                
                // If there are multiple headings (e.g. Book Title + Chapter 1 Title on Page 1),
                // prioritize the heading that starts with "Chapter", "Prologue", or "Epilogue"
                foreach ( $found_headings as $heading ) {
                    if ( preg_match( '/^(chapter|prologue|epilogue|part)\b/i', trim( $heading ) ) ) {
                        $chapter_title = $heading;
                        break;
                    }
                }
            }
        }
        
        $html .= '<option value="' . esc_url( $url ) . '" ' . $selected . '>' . esc_html( $chapter_title ) . '</option>';
    }
    $html .= '</select>';
    return '<div style="text-align: right; margin-bottom: 20px;">' . $html . '</div>';
}
add_shortcode( 'chapter_dropdown', 'strangebooks_chapter_dropdown_shortcode' );

// 2. Prev / Next / Track Navigation (Redirects to Homepage)
function strangebooks_chapter_nav_shortcode() {
    global $page, $numpages;
    
    $html = '<div style="display:flex; justify-content:space-between; align-items:center; margin-top:40px; border-top:1px solid #ccc; padding-top:20px;">';
    
    if ( $page > 1 ) {
        $prev_url = ( $page == 2 ) ? get_permalink() : get_permalink() . ( $page - 1 ) . '/';
        $html .= '<a href="' . esc_url( $prev_url ) . '" class="book-btn">Prev. Chapter</a>';
    } else {
        $html .= '<div></div>'; 
    }

    if ( $page < $numpages ) {
        $next_url = get_permalink() . ( $page + 1 ) . '/';
        $html .= '<a href="' . esc_url( $next_url ) . '" class="book-btn">Next Chapter</a>';
    } else {
        $html .= '<a href="' . esc_url( home_url( '/' ) ) . '" class="book-btn track-btn">Finish Book</a>';
    }
    
    $html .= '</div>';
    return $html;
}
add_shortcode( 'chapter_nav', 'strangebooks_chapter_nav_shortcode' );

// 3. Dynamic "Read Now" Button for the Landing Page
function strangebooks_read_now_shortcode() {
    global $numpages;
    if ( $numpages > 1 ) {
        $ch1_url = get_permalink() . '2/';
        return '<div style="text-align:right;"><a href="' . esc_url( $ch1_url ) . '" class="book-btn">Read Now</a></div>';
    }
    return '';
}
add_shortcode( 'read_now_button', 'strangebooks_read_now_shortcode' );

/* --- AUTOMATIC VISUAL PAGE BREAKS (SELF-HEALING) --- */
add_filter( 'the_posts', 'strangebooks_visual_page_breaks', 10, 2 );
function strangebooks_visual_page_breaks( $posts, $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        foreach ( $posts as $post ) {
            if ( $post->post_type === 'book' ) {
                $content = $post->post_content;

                // 1. Remove any leftover manual <!--nextpage--> tags so they don't double-split chapters
                $content = str_replace( '<!--nextpage-->', '', $content );

                // 2. Inject <!--nextpage--> before any row with the 'new-chapter' class
                $content = preg_replace( '/(\[vc_row\b[^\]]*new-chapter[^\]]*\])/i', '<!--nextpage-->' . "\n" . '$1', $content );

                // 3. Remove any accidental page break at the very start of the book (before Chapter 1)
                $content = preg_replace( '/^\s*<!--nextpage-->\s*/i', '', $content );

                // 4. Merge "orphan" pages (rows that had new-chapter or breaks but no heading inside them)
                $raw_chunks = explode( '<!--nextpage-->', $content );
                $clean_pages = array();

                foreach ( $raw_chunks as $chunk ) {
                    $chunk_trimmed = trim( $chunk );
                    if ( empty( $chunk_trimmed ) ) {
                        continue;
                    }

                    // Check if this chunk actually contains a chapter heading
                    $has_heading = (
                        preg_match( '/\[vc_custom_heading[^\]]*text="([^"]+)"/i', $chunk_trimmed ) ||
                        preg_match( '/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $chunk_trimmed )
                    );

                    // If it's the first page OR it has its own heading, make it a real chapter page.
                    // Otherwise, it's just a bottom button row or continuation row—merge it into the previous chapter!
                    if ( empty( $clean_pages ) || $has_heading ) {
                        $clean_pages[] = $chunk_trimmed;
                    } else {
                        $clean_pages[ count( $clean_pages ) - 1 ] .= "\n" . $chunk_trimmed;
                    }
                }

                $post->post_content = implode( "\n<!--nextpage-->\n", $clean_pages );
            }
        }
    }
    return $posts;
}

/* --- GA4 CUSTOM EVENT & SCROLL TRACKING FOR BOOKS --- */
function strangebooks_ga4_chapter_tracking() {
    if ( is_singular( 'book' ) ) {
        global $page, $numpages, $post;
        
        $book_title = esc_js( get_the_title( $post->ID ) );
        $current_chapter = $page ? $page : 1;
        $total_chapters = $numpages ? $numpages : 1;
        
        echo "<script>
        window.addEventListener('load', function() {
            window.dataLayer = window.dataLayer || [];
            
            function fireBookEvent(eventName, eventParams) {
                if (typeof gtag === 'function') {
                    gtag('event', eventName, eventParams);
                } else {
                    window.dataLayer.push(Object.assign({'event': eventName}, eventParams));
                }
            }

            // 1. Event: Reading a specific chapter
            fireBookEvent('reading_chapter', {
                'book_title': '{$book_title}',
                'chapter_number': {$current_chapter}
            });

            // 2. Event: Loaded the final chapter
            if ( {$current_chapter} === {$total_chapters} && {$total_chapters} > 1 ) {
                fireBookEvent('reached_last_chapter', {
                    'book_title': '{$book_title}'
                });
            }

            // 3. Event: Clicked Finish Book (Protected with Delay)
            const finishButton = document.querySelector('.track-btn');
            if (finishButton) {
                finishButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetURL = this.href;
                    
                    fireBookEvent('clicked_finish_book', {
                        'book_title': '{$book_title}'
                    });
                    
                    setTimeout(function() {
                        window.location.href = targetURL;
                    }, 300);
                });
            }

            // 4. Scroll Tracking
            let scrolled50 = false;
            let scrolled90 = false;
            
            window.addEventListener('scroll', function() {
                let scrollPercent = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight;
                
                if (scrollPercent > 0.50 && !scrolled50) {
                    fireBookEvent('chapter_scroll_50', {
                        'book_title': '{$book_title}',
                        'chapter_number': {$current_chapter}
                    });
                    scrolled50 = true;
                }
                
                if (scrollPercent > 0.90 && !scrolled90) {
                    fireBookEvent('chapter_scroll_90', {
                        'book_title': '{$book_title}',
                        'chapter_number': {$current_chapter}
                    });
                    scrolled90 = true;
                }
            });
        });
        </script>";
    }
}
add_action( 'wp_footer', 'strangebooks_ga4_chapter_tracking', 20 );

/* --- FORCE WPBAKERY DESIGN OPTIONS & COLLAPSIBLE PENTAGRAM SIDEBAR MENU --- */
function strangebooks_vc_precedence_and_sidebar_css() {
    $post_id = get_queried_object_id();
    if ( $post_id ) {
        $vc_css  = get_post_meta( $post_id, '_wpb_shortcodes_custom_css', true );
        $vc_css .= get_post_meta( $post_id, '_wpb_post_custom_css', true );
        $vc_css .= get_post_meta( $post_id, 'vc_post_custom_css', true );
        
        if ( ! empty( $vc_css ) ) {
            // Ensure WPBakery Design Options always take precedence over theme defaults
            $vc_css_important = preg_replace( '/(?<!\!important)\s*;/', ' !important;', strip_tags( $vc_css ) );
            echo '<style type="text/css" id="strangebooks-vc-custom-css">' . $vc_css_important . '</style>';
        }
    }
    ?>
    <style type="text/css" id="strangebooks-sidebar-pentagram-css">
        /* Preserve WPBakery inline background and text colors */
        .site-main *[style*="background-color"],
        .site-main *[style*="background:"] {
            padding: 4px 8px;
        }

        /* Collapsible WP Custom Menu in Sidebar */
        .vc_wp_custommenu ul,
        .wpb_widgetised_column ul,
        .site-main .widget_nav_menu ul {
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
            background-color: #000000 !important;
            border: 1px solid #222222 !important;
            font-family: "Times New Roman", Times, serif !important;
        }

        .vc_wp_custommenu li,
        .wpb_widgetised_column li,
        .site-main .widget_nav_menu li {
            list-style: none !important;
            border-bottom: 1px solid #222222 !important;
            margin: 0 !important;
            padding: 0 !important;
            position: relative !important;
        }

        .vc_wp_custommenu li:last-child,
        .wpb_widgetised_column li:last-child,
        .site-main .widget_nav_menu li:last-child {
            border-bottom: none !important;
        }

        .vc_wp_custommenu li a,
        .wpb_widgetised_column li a,
        .site-main .widget_nav_menu li a {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 10px 14px !important;
            color: #ffffff !important;
            background-color: #000000 !important;
            text-decoration: none !important;
            font-size: 13px !important;
            text-transform: uppercase !important;
            line-height: 1.4 !important;
            transition: background-color 0.2s ease !important;
        }

        .vc_wp_custommenu li a:hover,
        .wpb_widgetised_column li a:hover,
        .site-main .widget_nav_menu li a:hover {
            background-color: #222222 !important;
            color: #ffffff !important;
        }

        /* Hide all nested sub-menus by default */
        .vc_wp_custommenu li > ul,
        .wpb_widgetised_column li > ul,
        .site-main .widget_nav_menu li > ul {
            display: none !important;
            border: none !important;
            border-top: 1px solid #222222 !important;
        }

        /* Show sub-menu only when parent has .open class */
        .vc_wp_custommenu li.open > ul,
        .wpb_widgetised_column li.open > ul,
        .site-main .widget_nav_menu li.open > ul {
            display: block !important;
        }

        /* Level 2 Indentation */
        .vc_wp_custommenu ul ul li a,
        .wpb_widgetised_column ul ul li a,
        .site-main .widget_nav_menu ul ul li a {
            padding-left: 24px !important;
            background-color: #111111 !important;
            color: #e0e0e0 !important;
            font-size: 12px !important;
            text-transform: none !important;
        }

        /* Level 3 Indentation */
        .vc_wp_custommenu ul ul ul li a,
        .wpb_widgetised_column ul ul ul li a,
        .site-main .widget_nav_menu ul ul ul li a {
            padding-left: 38px !important;
            background-color: #1b1b1b !important;
            color: #cccccc !important;
            font-size: 11px !important;
            text-transform: none !important;
        }

        /* Pentagram Indicator (\26E4) matching header menu */
        .sidebar-menu-toggle {
            font-family: Arial, sans-serif !important;
            font-size: 14px !important;
            margin-left: 8px !important;
            cursor: pointer !important;
            display: inline-block !important;
            transition: transform 0.3s ease !important;
            color: #ffffff !important;
            line-height: 1 !important;
        }

        li.open > a > .sidebar-menu-toggle {
            transform: rotate(180deg) !important;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'strangebooks_vc_precedence_and_sidebar_css', 999 );

/* --- COLLAPSIBLE SIDEBAR MENU JS CONTROLLER (PENTAGRAM) --- */
function strangebooks_collapsible_sidebar_menu() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuContainers = document.querySelectorAll('.vc_wp_custommenu, .wpb_widgetised_column, .site-main .widget_nav_menu');

        menuContainers.forEach(function(container) {
            const allItems = container.querySelectorAll('li');

            allItems.forEach(function(item) {
                const subMenu = item.querySelector(':scope > ul');
                const link = item.querySelector(':scope > a');

                if (subMenu && link) {
                    item.classList.add('has-collapsible-sub');

                    // Remove any old star icons and inject the exact Pentagram (\26E4 / &#9956;)
                    const oldToggle = link.querySelector('.sidebar-menu-toggle');
                    if (oldToggle) {
                        oldToggle.remove();
                    }
                    const toggleBtn = document.createElement('span');
                    toggleBtn.className = 'sidebar-menu-toggle';
                    toggleBtn.innerHTML = '&#9956;';
                    link.appendChild(toggleBtn);

                    // Auto-expand if viewing an active child page
                    if (item.classList.contains('current-menu-parent') || item.classList.contains('current-menu-ancestor')) {
                        item.classList.add('open');
                    }

                    // Toggle accordion on click
                    link.addEventListener('click', function(e) {
                        const href = link.getAttribute('href');
                        if (!href || href === '#' || href.startsWith('#') || e.target.classList.contains('sidebar-menu-toggle') || subMenu) {
                            e.preventDefault();
                            e.stopPropagation();
                            item.classList.toggle('open');
                        }
                    });
                }
            });
        });
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'strangebooks_collapsible_sidebar_menu', 99 );