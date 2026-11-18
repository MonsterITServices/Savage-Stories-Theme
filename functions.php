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
    register_sidebar( array(
        'name'          => __( 'Footer Area 1 (Left)' ),
        'id'            => 'footer-1',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Footer Area 2 (Center)' ),
        'id'            => 'footer-2',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Footer Area 3 (Right)' ),
        'id'            => 'footer-3',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'strangebooks_widgets_init' );

function strangebooks_scripts() {
    if ( wp_style_is( 'js_composer_front', 'registered' ) ) {
        wp_enqueue_style( 'js_composer_front' );
    }
    $style_path  = get_stylesheet_directory() . '/style.css';
    $css_version = file_exists( $style_path ) ? filemtime( $style_path ) : '2.0';
    wp_enqueue_style( 'strangebooks-style', get_stylesheet_uri(), array(), $css_version );
}
add_action( 'wp_enqueue_scripts', 'strangebooks_scripts' );

/* --- ALLOW ALL TINYMCE & KSES INLINE BACKGROUND STYLES ON SAVE --- */
function strangebooks_allow_all_tinymce_styles( $init ) {
    if ( is_array( $init ) ) {
        $init['valid_elements']          = '*[*]';
        $init['extended_valid_elements'] = '*[*]';
        $init['verify_html']             = false;
    }
    return $init;
}
add_filter( 'tiny_mce_before_init', 'strangebooks_allow_all_tinymce_styles', 99 );

function strangebooks_allow_kses_background_styles( $styles ) {
    if ( is_array( $styles ) ) {
        $styles[] = 'background';
        $styles[] = 'background-color';
        $styles[] = 'color';
        $styles[] = 'padding';
        $styles[] = 'margin';
        $styles[] = 'border';
        $styles[] = 'border-radius';
        $styles[] = 'display';
        $styles[] = 'text-align';
        return array_unique( $styles );
    }
    return $styles;
}
add_filter( 'safe_style_css', 'strangebooks_allow_kses_background_styles', 99 );

/* --- 1. REGISTER 'BOOKS' CUSTOM POST TYPE --- */
function strangebooks_register_books_cpt() {
    register_post_type( 'book', array(
        'labels'      => array(
            'name'          => 'Books',
            'singular_name' => 'Book',
            'add_new_item'  => 'Add New Book',
            'edit_item'     => 'Edit Book',
        ),
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
    $pages    = get_post_meta( $post->ID, '_book_pages', true );
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
    if ( ! isset( $_POST['book_details_nonce'] ) || ! wp_verify_nonce( $_POST['book_details_nonce'], 'save_book_details' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['book_chapters'] ) ) {
        update_post_meta( $post_id, '_book_chapters', sanitize_text_field( $_POST['book_chapters'] ) );
    }
    if ( isset( $_POST['book_pages'] ) ) {
        update_post_meta( $post_id, '_book_pages', sanitize_text_field( $_POST['book_pages'] ) );
    }
    if ( isset( $_POST['book_tracking'] ) ) {
        update_post_meta( $post_id, '_book_tracking', esc_url_raw( $_POST['book_tracking'] ) );
    }
}
add_action( 'save_post_book', 'strangebooks_save_book_details' );

/* --- 3. CREATE [display_books] SHORTCODE --- */
function strangebooks_books_shortcode( $atts ) {
    $query = new WP_Query( array( 'post_type' => 'book', 'posts_per_page' => -1 ) );
    if ( ! $query->have_posts() ) {
        return '<p>No books found.</p>';
    }
    
    ob_start();
    echo '<div class="book-grid">';
    while ( $query->have_posts() ) :
        $query->the_post();
        $chapters = get_post_meta( get_the_ID(), '_book_chapters', true );
        $pages    = get_post_meta( get_the_ID(), '_book_pages', true );
        ?>
        <article class="book-item" style="text-align: center;">
            <a href="<?php the_permalink(); ?>">
                <?php
                if ( has_post_thumbnail() ) {
                    the_post_thumbnail( 'large' );
                } else {
                    echo '<div style="background:#222;height:300px;display:flex;align-items:center;justify-content:center;color:#fff;">No Cover</div>';
                }
                ?>
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
    
    $total = ( isset( $numpages ) && $numpages > 0 ) ? $numpages : 1;
    $curr  = ( isset( $page ) && $page > 0 ) ? $page : 1;

    $html = '<select onchange="location = this.value;" style="padding:10px; background:#000; color:#fff; border:1px solid #333; border-radius:3px; cursor:pointer; font-family:Arial, sans-serif; min-width:200px;">';
    
    for ( $i = 1; $i <= $total; $i++ ) {
        $url      = ( $i === 1 ) ? get_permalink() : trailingslashit( get_permalink() ) . $i . '/';
        $selected = ( $i === $curr ) ? 'selected' : '';
        
        $chapter_title = 'Chapter ' . $i; 
        
        if ( isset( $pages[ $i - 1 ] ) ) {
            $raw_content    = $pages[ $i - 1 ];
            $found_headings = array();

            if ( preg_match_all( '/\[vc_custom_heading[^\]]*text="([^"]+)"/i', $raw_content, $vc_matches ) ) {
                foreach ( $vc_matches[1] as $match ) {
                    $found_headings[] = sanitize_text_field( $match );
                }
            }
            if ( preg_match_all( '/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $raw_content, $h_matches ) ) {
                foreach ( $h_matches[1] as $match ) {
                    $found_headings[] = trim( strip_tags( $match ) );
                }
            }

            if ( ! empty( $found_headings ) ) {
                $chapter_title = $found_headings[0];
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
    
    $total = ( isset( $numpages ) && $numpages > 0 ) ? $numpages : 1;
    $curr  = ( isset( $page ) && $page > 0 ) ? $page : 1;

    $html = '<div style="display:flex; justify-content:space-between; align-items:center; margin-top:40px; border-top:1px solid #ccc; padding-top:20px;">';
    
    if ( $curr > 1 ) {
        $prev_url = ( $curr === 2 ) ? get_permalink() : trailingslashit( get_permalink() ) . ( $curr - 1 ) . '/';
        $html    .= '<a href="' . esc_url( $prev_url ) . '" class="book-btn">Prev. Chapter</a>';
    } else {
        $html .= '<div></div>'; 
    }

    if ( $curr < $total ) {
        $next_url = trailingslashit( get_permalink() ) . ( $curr + 1 ) . '/';
        $html    .= '<a href="' . esc_url( $next_url ) . '" class="book-btn">Next Chapter</a>';
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
    if ( isset( $numpages ) && $numpages > 1 ) {
        $ch1_url = trailingslashit( get_permalink() ) . '2/';
        return '<div style="text-align:right;"><a href="' . esc_url( $ch1_url ) . '" class="book-btn">Read Now</a></div>';
    }
    return '';
}
add_shortcode( 'read_now_button', 'strangebooks_read_now_shortcode' );

/* --- AUTOMATIC VISUAL PAGE BREAKS (SELF-HEALING) --- */
function strangebooks_visual_page_breaks( $posts, $query ) {
    if ( ! is_admin() && $query->is_main_query() && is_array( $posts ) ) {
        foreach ( $posts as $post ) {
            if ( isset( $post->post_type ) && $post->post_type === 'book' ) {
                $content = $post->post_content;

                $content = str_replace( '<!--nextpage-->', '', $content );
                $content = preg_replace( '/(\[vc_row\b[^\]]*new-chapter[^\]]*\])/i', '<!--nextpage-->' . "\n" . '$1', $content );
                $content = preg_replace( '/^\s*<!--nextpage-->\s*/i', '', $content );

                $raw_chunks  = explode( '<!--nextpage-->', $content );
                $clean_pages = array();

                foreach ( $raw_chunks as $chunk ) {
                    $chunk_trimmed = trim( $chunk );
                    if ( empty( $chunk_trimmed ) ) {
                        continue;
                    }

                    $has_heading = (
                        preg_match( '/\[vc_custom_heading[^\]]*text="([^"]+)"/i', $chunk_trimmed ) ||
                        preg_match( '/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $chunk_trimmed )
                    );

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
add_filter( 'the_posts', 'strangebooks_visual_page_breaks', 10, 2 );

/* --- GA4 CUSTOM EVENT & SCROLL TRACKING FOR BOOKS --- */
function strangebooks_ga4_chapter_tracking() {
    if ( is_singular( 'book' ) ) {
        global $page, $numpages, $post;
        
        $book_title      = isset( $post->ID ) ? esc_js( get_the_title( $post->ID ) ) : '';
        $current_chapter = ( isset( $page ) && $page > 0 ) ? (int) $page : 1;
        $total_chapters  = ( isset( $numpages ) && $numpages > 0 ) ? (int) $numpages : 1;
        
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

            fireBookEvent('reading_chapter', {
                'book_title': '{$book_title}',
                'chapter_number': {$current_chapter}
            });

            if ( {$current_chapter} === {$total_chapters} && {$total_chapters} > 1 ) {
                fireBookEvent('reached_last_chapter', {
                    'book_title': '{$book_title}'
                });
            }

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

/* --- EXTRACT ALL WPBAKERY CUSTOM CSS FROM META AND CONTENT --- */
function strangebooks_get_vc_custom_css() {
    global $post;
    $post_id = get_queried_object_id();
    if ( ! $post_id && isset( $post->ID ) ) {
        $post_id = $post->ID;
    }
    if ( ! $post_id ) {
        return '';
    }

    $vc_css  = (string) get_post_meta( $post_id, '_wpb_shortcodes_custom_css', true );
    $vc_css .= ' ' . (string) get_post_meta( $post_id, '_wpb_post_custom_css', true );
    $vc_css .= ' ' . (string) get_post_meta( $post_id, 'vc_post_custom_css', true );

    $raw_content = get_post_field( 'post_content', $post_id, 'raw' );
    if ( ! empty( $raw_content ) ) {
        if ( preg_match_all( '/css="([^"]+)"/i', $raw_content, $matches ) ) {
            foreach ( $matches[1] as $css_rule ) {
                $vc_css .= ' ' . html_entity_decode( $css_rule, ENT_QUOTES | ENT_XML1, 'UTF-8' );
            }
        }
    }

    return trim( $vc_css );
}

/* --- OUTPUT WPBAKERY CSS & UNIFIED HEADER + SIDEBAR MENU CSS IN HEAD --- */
function strangebooks_vc_precedence_and_sidebar_css() {
    $vc_css = strangebooks_get_vc_custom_css();
    if ( ! empty( $vc_css ) ) {
        echo '<style type="text/css" id="strangebooks-vc-custom-css-head">' . $vc_css . '</style>';
    }
    ?>
    <style type="text/css" id="strangebooks-sidebar-pentagram-css">
        /* Allow WPBakery Custom Headings & Text Blocks full styling control */
        .vc_custom_heading,
        .wpb_text_column,
        .vc_row,
        .vc_column_container > .vc_column-inner {
            box-sizing: border-box;
        }

        /* Keep inline background colors visible on headings, paragraphs, and spans */
        .wpb_text_column h1[style*="background"],
        .wpb_text_column h2[style*="background"],
        .wpb_text_column h3[style*="background"],
        .wpb_text_column h4[style*="background"],
        .wpb_text_column p[style*="background"] {
            padding: 6px 12px;
            display: block;
        }

        .wpb_text_column span[style*="background"] {
            padding: 4px 10px;
            display: inline-block;
        }

        /* Disable static ::after icon on header menu so JS particle engine takes over cleanly */
        .main-navigation li.menu-item-has-children > a::after {
            content: none !important;
            display: none !important;
        }

        .main-navigation a {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .main-navigation ul.sub-menu a {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
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

        .vc_wp_custommenu li > a,
        .wpb_widgetised_column li > a,
        .site-main .widget_nav_menu li > a {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 6px 10px 6px 14px !important;
            color: #ffffff !important;
            background-color: #000000 !important;
            text-decoration: none !important;
            font-size: 13px !important;
            text-transform: uppercase !important;
            line-height: 1.4 !important;
            transition: background-color 0.2s ease !important;
            cursor: pointer !important;
        }

        .vc_wp_custommenu li > a:hover,
        .wpb_widgetised_column li > a:hover,
        .site-main .widget_nav_menu li > a:hover {
            background-color: #222222 !important;
            color: #ffffff !important;
        }

        /* Hide all nested sub-menus in sidebar by default when parent does NOT have .open */
        .vc_wp_custommenu li:not(.open) > ul,
        .wpb_widgetised_column li:not(.open) > ul,
        .site-main .widget_nav_menu li:not(.open) > ul {
            display: none !important;
        }

        /* Show sub-menu immediately when parent has .open class */
        .vc_wp_custommenu li.open > ul,
        .wpb_widgetised_column li.open > ul,
        .site-main .widget_nav_menu li.open > ul {
            display: block !important;
            border: none !important;
            border-top: 1px solid #222222 !important;
        }

        /* Level 2 Indentation */
        .vc_wp_custommenu ul ul li > a,
        .wpb_widgetised_column ul ul li > a,
        .site-main .widget_nav_menu ul ul li > a {
            padding-left: 24px !important;
            background-color: #111111 !important;
            color: #e0e0e0 !important;
            font-size: 12px !important;
            text-transform: none !important;
        }

        /* Level 3 Indentation */
        .vc_wp_custommenu ul ul ul li > a,
        .wpb_widgetised_column ul ul ul li > a,
        .site-main .widget_nav_menu ul ul ul li > a {
            padding-left: 38px !important;
            background-color: #1b1b1b !important;
            color: #cccccc !important;
            font-size: 11px !important;
            text-transform: none !important;
        }

        /* --- TOGGLE BUTTON & 25-ICON SYSTEM (HEADER + SIDEBAR) --- */
        .sidebar-menu-toggle {
            position: relative !important;
            font-family: "Segoe UI Symbol", "Apple Symbols", "Noto Color Emoji", Arial, sans-serif !important;
            font-size: 15px !important;
            margin-left: 6px !important;
            padding: 3px 8px !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #ffffff !important;
            line-height: 1 !important;
            overflow: visible !important;
            user-select: none !important;
            border-radius: 3px !important;
            transition: background-color 0.2s ease !important;
        }

        .main-navigation > div > ul > li > a > .sidebar-menu-toggle,
        .main-navigation > ul > li > a > .sidebar-menu-toggle {
            padding: 2px 5px !important;
            margin-left: 5px !important;
        }

        .sidebar-menu-toggle:hover {
            background-color: rgba(255, 255, 255, 0.12) !important;
        }

        .sidebar-icon-glyph {
            display: inline-block !important;
            transition: transform 0.35s ease, color 0.25s ease, text-shadow 0.25s ease !important;
        }

        /* 1. PENTAGRAM */
        li.open > a .sidebar-menu-toggle[data-icon-type="pentagram"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="pentagram"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="pentagram"].animating .sidebar-icon-glyph {
            color: #cc0000 !important;
            text-shadow: 0 0 6px rgba(204, 0, 0, 0.85), 0 2px 4px #660000 !important;
            transform: rotate(180deg) !important;
        }

        /* 2. HEART */
        li.open > a .sidebar-menu-toggle[data-icon-type="heart"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="heart"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="heart"].animating .sidebar-icon-glyph {
            color: #ff2a6d !important;
            text-shadow: 0 0 8px rgba(255, 42, 109, 0.9), 0 0 14px #ff0055 !important;
            transform: scale(1.25) !important;
        }

        /* 3. SPARKLES / CROWN / BOOK / QUILL / LEMON / PAW */
        li.open > a .sidebar-menu-toggle[data-icon-type="sparkles"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="sparkles"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="sparkles"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="crown"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="crown"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="crown"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="book"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="book"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="book"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="quill"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="quill"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="quill"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="lemon"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="lemon"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="lemon"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="paw"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="paw"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="paw"].animating .sidebar-icon-glyph {
            color: #ffd700 !important;
            text-shadow: 0 0 8px rgba(255, 215, 0, 0.95), 0 0 14px #ffae00 !important;
            transform: scale(1.22) !important;
        }

        /* 4. SKULL */
        li.open > a .sidebar-menu-toggle[data-icon-type="skull"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="skull"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="skull"].animating .sidebar-icon-glyph {
            color: #39ff14 !important;
            text-shadow: 0 0 8px rgba(57, 255, 20, 0.9), 0 0 14px #00aa00 !important;
            transform: scale(1.2) !important;
        }

        /* 5. SCI-FI / SUPERHERO / MYTHOLOGY / WEREWOLF */
        li.open > a .sidebar-menu-toggle[data-icon-type="scifi"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="scifi"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="scifi"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="superhero"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="superhero"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="superhero"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="mythology"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="mythology"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="mythology"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="werewolf"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="werewolf"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="werewolf"].animating .sidebar-icon-glyph {
            color: #00f0ff !important;
            text-shadow: 0 0 8px rgba(0, 240, 255, 0.95), 0 0 16px #facc15 !important;
            transform: scale(1.25) !important;
        }

        /* 6. CRIME / DAGGER / VAMPIRE / YOUTUBE / ANGER */
        li.open > a .sidebar-menu-toggle[data-icon-type="crime"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="crime"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="crime"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="dagger"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="dagger"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="dagger"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="vampire"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="vampire"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="vampire"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="youtube"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="youtube"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="youtube"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="anger"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="anger"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="anger"].animating .sidebar-icon-glyph {
            color: #ff1a1a !important;
            text-shadow: 0 0 8px rgba(255, 26, 26, 0.95), 0 0 14px #8b0000 !important;
            transform: scale(1.25) !important;
        }

        /* 7. MYSTERY / COMEDY / DYSTOPIAN / FLAME */
        li.open > a .sidebar-menu-toggle[data-icon-type="mystery"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="mystery"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="mystery"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="comedy"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="comedy"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="comedy"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="dystopian"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="dystopian"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="dystopian"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="flame"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="flame"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="flame"].animating .sidebar-icon-glyph {
            color: #ff9900 !important;
            text-shadow: 0 0 8px rgba(255, 153, 0, 0.95), 0 0 14px #ff0000 !important;
            transform: scale(1.22) !important;
        }

        /* 8. PSYCHOLOGICAL / MOON / MANGA */
        li.open > a .sidebar-menu-toggle[data-icon-type="psych"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="psych"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="psych"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="moon"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="moon"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="moon"].animating .sidebar-icon-glyph,
        li.open > a .sidebar-menu-toggle[data-icon-type="manga"] .sidebar-icon-glyph,
        .main-navigation li:hover > a .sidebar-menu-toggle[data-icon-type="manga"] .sidebar-icon-glyph,
        .sidebar-menu-toggle[data-icon-type="manga"].animating .sidebar-icon-glyph {
            color: #d946ef !important;
            text-shadow: 0 0 9px rgba(217, 70, 239, 0.95), 0 0 15px #38bdf8 !important;
            transform: scale(1.25) !important;
        }

        /* --- PARTICLE EFFECTS --- */
        .menu-particle {
            position: absolute !important;
            pointer-events: none !important;
            opacity: 0;
            z-index: 99999 !important;
            line-height: 1 !important;
        }

        /* Group A: Dripping Blood Drops (Pentagram, Dagger, Crime) */
        .sidebar-menu-toggle[data-icon-type="pentagram"] .menu-particle,
        .sidebar-menu-toggle[data-icon-type="dagger"] .menu-particle,
        .sidebar-menu-toggle[data-icon-type="crime"] .menu-particle {
            top: 75% !important;
            left: 50% !important;
            width: 4px !important;
            height: 6px !important;
            background: radial-gradient(circle at 35% 35%, #ff1a1a, #8b0000 75%) !important;
            border-radius: 50% 50% 50% 50% / 65% 65% 35% 35% !important;
            box-shadow: 0 0 3px rgba(139, 0, 0, 0.9) !important;
        }
        .sidebar-menu-toggle[data-icon-type="pentagram"] .menu-particle.p-2,
        .sidebar-menu-toggle[data-icon-type="dagger"] .menu-particle.p-2,
        .sidebar-menu-toggle[data-icon-type="crime"] .menu-particle.p-2 {
            left: 30% !important;
            width: 3px !important;
            height: 5px !important;
        }
        .sidebar-menu-toggle[data-icon-type="pentagram"] .menu-particle.p-3,
        .sidebar-menu-toggle[data-icon-type="dagger"] .menu-particle.p-3,
        .sidebar-menu-toggle[data-icon-type="crime"] .menu-particle.p-3 {
            left: 70% !important;
            width: 3.5px !important;
            height: 5.5px !important;
        }
        .sidebar-menu-toggle[data-icon-type="pentagram"].animating .menu-particle.p-1,
        .sidebar-menu-toggle[data-icon-type="dagger"].animating .menu-particle.p-1,
        .sidebar-menu-toggle[data-icon-type="crime"].animating .menu-particle.p-1 {
            animation: pentagramBloodDrip 1.05s cubic-bezier(0.55, 0.055, 0.675, 0.19) forwards !important;
        }
        .sidebar-menu-toggle[data-icon-type="pentagram"].animating .menu-particle.p-2,
        .sidebar-menu-toggle[data-icon-type="dagger"].animating .menu-particle.p-2,
        .sidebar-menu-toggle[data-icon-type="crime"].animating .menu-particle.p-2 {
            animation: pentagramBloodDrip 1.15s cubic-bezier(0.55, 0.055, 0.675, 0.19) 0.16s forwards !important;
        }
        .sidebar-menu-toggle[data-icon-type="pentagram"].animating .menu-particle.p-3,
        .sidebar-menu-toggle[data-icon-type="dagger"].animating .menu-particle.p-3,
        .sidebar-menu-toggle[data-icon-type="crime"].animating .menu-particle.p-3 {
            animation: pentagramBloodDrip 1.1s cubic-bezier(0.55, 0.055, 0.675, 0.19) 0.32s forwards !important;
        }

        /* Group B: Flying Hearts (.icon-heart) */
        .sidebar-menu-toggle[data-icon-type="heart"] .menu-particle {
            top: 40% !important;
            left: 50% !important;
            font-size: 11px !important;
            color: #ff2a6d !important;
            text-shadow: 0 0 4px rgba(255, 42, 109, 0.8) !important;
        }
        .sidebar-menu-toggle[data-icon-type="heart"] .menu-particle.p-2 {
            color: #ff758f !important;
            font-size: 9px !important;
        }
        .sidebar-menu-toggle[data-icon-type="heart"] .menu-particle.p-3 {
            color: #ff0055 !important;
            font-size: 10px !important;
        }
        .sidebar-menu-toggle[data-icon-type="heart"] .menu-particle.p-4 {
            color: #ff99ac !important;
            font-size: 8px !important;
        }
        .sidebar-menu-toggle[data-icon-type="heart"].animating .menu-particle.p-1 {
            animation: flyParticleLeft 1.1s ease-out forwards !important;
        }
        .sidebar-menu-toggle[data-icon-type="heart"].animating .menu-particle.p-2 {
            animation: flyParticleRight 1.2s ease-out 0.1s forwards !important;
        }
        .sidebar-menu-toggle[data-icon-type="heart"].animating .menu-particle.p-3 {
            animation: flyParticleCenter 1.15s ease-out 0.2s forwards !important;
        }
        .sidebar-menu-toggle[data-icon-type="heart"].animating .menu-particle.p-4 {
            animation: flyParticleFarRight 1.25s ease-out 0.28s forwards !important;
        }

        /* Group C: All Other Floating Genre Particles */
        .sidebar-menu-toggle:not([data-icon-type="pentagram"]):not([data-icon-type="dagger"]):not([data-icon-type="crime"]):not([data-icon-type="heart"]) .menu-particle {
            top: 45% !important;
            left: 50% !important;
            font-size: 10px !important;
            color: #ffd700 !important;
            text-shadow: 0 0 5px rgba(255, 215, 0, 0.85) !important;
        }
        .sidebar-menu-toggle[data-icon-type="skull"] .menu-particle {
            color: #39ff14 !important;
            text-shadow: 0 0 5px #00aa00 !important;
        }
        .sidebar-menu-toggle[data-icon-type="scifi"] .menu-particle,
        .sidebar-menu-toggle[data-icon-type="superhero"] .menu-particle,
        .sidebar-menu-toggle[data-icon-type="mythology"] .menu-particle,
        .sidebar-menu-toggle[data-icon-type="werewolf"] .menu-particle {
            color: #00f0ff !important;
            text-shadow: 0 0 6px #0088ff !important;
        }
        .sidebar-menu-toggle[data-icon-type="moon"] .menu-particle,
        .sidebar-menu-toggle[data-icon-type="psych"] .menu-particle,
        .sidebar-menu-toggle[data-icon-type="manga"] .menu-particle {
            color: #e9d5ff !important;
            text-shadow: 0 0 5px #9333ea !important;
        }
        .sidebar-menu-toggle[data-icon-type="vampire"] .menu-particle,
        .sidebar-menu-toggle[data-icon-type="youtube"] .menu-particle,
        .sidebar-menu-toggle[data-icon-type="anger"] .menu-particle,
        .sidebar-menu-toggle[data-icon-type="flame"] .menu-particle {
            color: #ff3333 !important;
            text-shadow: 0 0 5px #ff0000 !important;
        }

        .sidebar-menu-toggle:not([data-icon-type="pentagram"]):not([data-icon-type="dagger"]):not([data-icon-type="crime"]):not([data-icon-type="heart"]).animating .menu-particle.p-1 {
            animation: flyParticleLeft 1.05s ease-out forwards !important;
        }
        .sidebar-menu-toggle:not([data-icon-type="pentagram"]):not([data-icon-type="dagger"]):not([data-icon-type="crime"]):not([data-icon-type="heart"]).animating .menu-particle.p-2 {
            animation: flyParticleRight 1.15s ease-out 0.12s forwards !important;
        }
        .sidebar-menu-toggle:not([data-icon-type="pentagram"]):not([data-icon-type="dagger"]):not([data-icon-type="crime"]):not([data-icon-type="heart"]).animating .menu-particle.p-3 {
            animation: flyParticleCenter 1.2s ease-out 0.22s forwards !important;
        }

        @keyframes pentagramBloodDrip {
            0% {
                opacity: 1;
                transform: translate(-50%, -2px) scale(0.5);
            }
            25% {
                opacity: 1;
                transform: translate(-50%, 3px) scaleX(0.85) scaleY(1.3);
            }
            80% {
                opacity: 0.95;
                transform: translate(-50%, 19px) scaleX(0.75) scaleY(1.45);
            }
            100% {
                opacity: 0;
                transform: translate(-50%, 26px) scale(0.2);
            }
        }

        @keyframes flyParticleLeft {
            0% {
                opacity: 1;
                transform: translate(-50%, 0) scale(0.4) rotate(0deg);
            }
            50% {
                opacity: 1;
                transform: translate(-17px, -14px) scale(1.15) rotate(-18deg);
            }
            100% {
                opacity: 0;
                transform: translate(-24px, -28px) scale(0.6) rotate(-30deg);
            }
        }

        @keyframes flyParticleRight {
            0% {
                opacity: 1;
                transform: translate(-50%, 0) scale(0.4) rotate(0deg);
            }
            50% {
                opacity: 1;
                transform: translate(10px, -16px) scale(1.1) rotate(18deg);
            }
            100% {
                opacity: 0;
                transform: translate(18px, -30px) scale(0.5) rotate(32deg);
            }
        }

        @keyframes flyParticleCenter {
            0% {
                opacity: 1;
                transform: translate(-50%, 0) scale(0.4);
            }
            50% {
                opacity: 1;
                transform: translate(-3px, -20px) scale(1.25);
            }
            100% {
                opacity: 0;
                transform: translate(-5px, -36px) scale(0.5);
            }
        }

        @keyframes flyParticleFarRight {
            0% {
                opacity: 1;
                transform: translate(-50%, 0) scale(0.3);
            }
            60% {
                opacity: 0.95;
                transform: translate(14px, -9px) scale(1);
            }
            100% {
                opacity: 0;
                transform: translate(24px, -20px) scale(0.4);
            }
        }
    </style>
    <?php
}
add_action( 'wp_head', 'strangebooks_vc_precedence_and_sidebar_css', 999 );

/* --- UNIFIED HEADER + SIDEBAR MENU CONTROLLER WITH 25 GENRE ICONS & ANIMATIONS --- */
function strangebooks_collapsible_sidebar_menu() {
    $vc_css = strangebooks_get_vc_custom_css();
    if ( ! empty( $vc_css ) ) {
        echo '<style type="text/css" id="strangebooks-vc-custom-css-footer">' . $vc_css . '</style>';
    }
    ?>
    <script>
    (function() {
        const ICON_PRESETS = {
            pentagram: {
                glyph: '&#9956;',
                particles: ['', '', '']
            },
            heart: {
                glyph: '&#9829;',
                particles: ['&#9829;', '&#10084;', '&#9829;', '&#9825;']
            },
            sparkles: {
                glyph: '&#10022;',
                particles: ['&#10022;', '&#10023;', '&#9733;']
            },
            skull: {
                glyph: '&#9760;',
                particles: ['&#10022;', '&#9760;', '&#10022;']
            },
            scifi: {
                glyph: '&#129680;',
                particles: ['&#10022;', '&#9675;', '&#9733;']
            },
            dystopian: {
                glyph: '&#9762;',
                particles: ['&#9762;', '&#10022;', '&#9889;']
            },
            superhero: {
                glyph: '&#9889;',
                particles: ['&#9889;', '&#9733;', '&#9889;']
            },
            crime: {
                glyph: '&#128481;',
                particles: ['', '', '']
            },
            mystery: {
                glyph: '&#128269;',
                particles: ['?', '&#10022;', '?']
            },
            comedy: {
                glyph: '&#127917;',
                particles: ['&#9733;', '&#10022;', '&#9733;']
            },
            mythology: {
                glyph: '&#128305;',
                particles: ['&#9889;', '&#10022;', '&#9889;']
            },
            psych: {
                glyph: '&#128065;',
                particles: ['&#9678;', '&#10022;', '&#9678;']
            },
            vampire: {
                glyph: '&#129415;',
                particles: ['&#129415;', '&#9829;', '&#129415;']
            },
            werewolf: {
                glyph: '&#128058;',
                particles: ['&#9790;', '&#10022;', '&#9733;']
            },
            manga: {
                glyph: '&#10070;',
                particles: ['&#10022;', '&#9733;', '&#10022;']
            },
            youtube: {
                glyph: '&#9654;',
                particles: ['&#9834;', '&#9835;', '&#9834;']
            },
            book: {
                glyph: '&#128214;',
                particles: ['&#10022;', '&#9733;', '&#10022;']
            },
            quill: {
                glyph: '&#10002;',
                particles: ['&#10022;', '&#9733;', '&#10022;']
            },
            anger: {
                glyph: '&#128162;',
                particles: ['&#9889;', '&#128293;', '&#9889;']
            },
            lemon: {
                glyph: '&#127819;',
                particles: ['&#10022;', '&#9733;', '&#10022;']
            },
            crown: {
                glyph: '&#9819;',
                particles: ['&#9819;', '&#10022;', '&#9733;']
            },
            paw: {
                glyph: '&#128062;',
                particles: ['&#128062;', '&#10022;', '&#128062;']
            },
            flame: {
                glyph: '&#128293;',
                particles: ['&#9679;', '&#10022;', '&#9679;']
            },
            moon: {
                glyph: '&#9790;',
                particles: ['&#10022;', '&#9733;', '&#10023;']
            },
            dagger: {
                glyph: '&#9876;',
                particles: ['', '', '']
            }
        };

        function getExplicitIconClass(li) {
            if (!li || !li.classList) return null;
            if (li.classList.contains('icon-heart') || li.classList.contains('icon-romance')) return 'heart';
            if (li.classList.contains('icon-sparkles') || li.classList.contains('icon-star') || li.classList.contains('icon-magic') || li.classList.contains('icon-fantasy')) return 'sparkles';
            if (li.classList.contains('icon-skull') || li.classList.contains('icon-horror') || li.classList.contains('icon-thriller')) return 'skull';
            if (li.classList.contains('icon-scifi') || li.classList.contains('icon-alien') || li.classList.contains('icon-planet')) return 'scifi';
            if (li.classList.contains('icon-dystopian') || li.classList.contains('icon-hazard')) return 'dystopian';
            if (li.classList.contains('icon-superhero') || li.classList.contains('icon-bolt') || li.classList.contains('icon-lightning')) return 'superhero';
            if (li.classList.contains('icon-crime') || li.classList.contains('icon-blood')) return 'crime';
            if (li.classList.contains('icon-mystery') || li.classList.contains('icon-clue')) return 'mystery';
            if (li.classList.contains('icon-comedy')) return 'comedy';
            if (li.classList.contains('icon-mythology') || li.classList.contains('icon-trident')) return 'mythology';
            if (li.classList.contains('icon-psych') || li.classList.contains('icon-eye')) return 'psych';
            if (li.classList.contains('icon-vampire') || li.classList.contains('icon-bat')) return 'vampire';
            if (li.classList.contains('icon-werewolf') || li.classList.contains('icon-wolf')) return 'werewolf';
            if (li.classList.contains('icon-manga') || li.classList.contains('icon-isekai')) return 'manga';
            if (li.classList.contains('icon-youtube') || li.classList.contains('icon-audio')) return 'youtube';
            if (li.classList.contains('icon-book')) return 'book';
            if (li.classList.contains('icon-quill') || li.classList.contains('icon-blog') || li.classList.contains('icon-author')) return 'quill';
            if (li.classList.contains('icon-anger')) return 'anger';
            if (li.classList.contains('icon-lemon')) return 'lemon';
            if (li.classList.contains('icon-crown') || li.classList.contains('icon-royal')) return 'crown';
            if (li.classList.contains('icon-paw') || li.classList.contains('icon-animal')) return 'paw';
            if (li.classList.contains('icon-flame') || li.classList.contains('icon-fire')) return 'flame';
            if (li.classList.contains('icon-moon')) return 'moon';
            if (li.classList.contains('icon-dagger') || li.classList.contains('icon-sword')) return 'dagger';
            if (li.classList.contains('icon-pentagram')) return 'pentagram';
            return null;
        }

        function getLinkLabelWithoutToggle(link) {
            if (!link) return '';
            const clone = link.cloneNode(true);
            const toggles = clone.querySelectorAll('.sidebar-menu-toggle');
            toggles.forEach(function(t) { t.remove(); });
            return (clone.textContent || '').toLowerCase();
        }

        function matchLabelToIcon(label) {
            if (!label) return null;
            if (label.indexOf('romance') !== -1 || label.indexOf('romantasy') !== -1 || label.indexOf('boy love') !== -1 || label.indexOf('girl love') !== -1 || label.indexOf('harem') !== -1) return 'heart';
            if (label.indexOf('historical') !== -1 || label.indexOf('royal') !== -1 || label.indexOf('princess') !== -1) return 'crown';
            if (label.indexOf('fantasy') !== -1 || label.indexOf('fairy') !== -1 || label.indexOf('magic') !== -1 || label.indexOf('merlin') !== -1 || label.indexOf('harry potter') !== -1 || label.indexOf('crescent city') !== -1) return 'sparkles';
            if (label.indexOf('thriller') !== -1 || label.indexOf('horror') !== -1 || label.indexOf('crimson peak') !== -1 || label.indexOf('zombie') !== -1) return 'skull';
            if (label.indexOf('dystopian') !== -1 || label.indexOf('hunger games') !== -1 || label.indexOf('apocalyp') !== -1) return 'dystopian';
            if (label.indexOf('superhero') !== -1 || label.indexOf('marvel') !== -1) return 'superhero';
            if (label.indexOf('sci-fi') !== -1 || label.indexOf('scifi') !== -1 || label.indexOf('science fiction') !== -1 || label.indexOf('red dwarf') !== -1 || label.indexOf('space') !== -1) return 'scifi';
            if (label.indexOf('crime') !== -1 || label.indexOf('murder') !== -1) return 'crime';
            if (label.indexOf('mystery') !== -1 || label.indexOf('sherlock') !== -1 || label.indexOf('detective') !== -1) return 'mystery';
            if (label.indexOf('comedy') !== -1 || label.indexOf('humour') !== -1) return 'comedy';
            if (label.indexOf('mythology') !== -1) return 'mythology';
            if (label.indexOf('psychological') !== -1) return 'psych';
            if (label.indexOf('vampire') !== -1 || label.indexOf('goth') !== -1) return 'vampire';
            if (label.indexOf('werewolf') !== -1 || label.indexOf('wolf') !== -1 || label.indexOf('shifter') !== -1 || label.indexOf('beast') !== -1) return 'werewolf';
            if (label.indexOf('manga') !== -1 || label.indexOf('isekai') !== -1 || label.indexOf('anime') !== -1) return 'manga';
            if (label.indexOf('youtube') !== -1 || label.indexOf('audiobook') !== -1) return 'youtube';
            if (label.indexOf('aggression') !== -1 || label.indexOf('frustration') !== -1) return 'anger';
            if (label.indexOf('lemon') !== -1) return 'lemon';
            if (label.indexOf('articulates') !== -1 || label.indexOf('poetic') !== -1 || label.indexOf('author') !== -1 || label.indexOf('blog') !== -1) return 'quill';
            if (label.indexOf('book recommendation') !== -1 || label.indexOf('short stories') !== -1) return 'book';
            if (label.indexOf('adventurer') !== -1 || label.indexOf('action') !== -1) return 'dagger';
            if (label.indexOf('animal') !== -1 || label.indexOf('cat') !== -1 || label.indexOf('dog') !== -1) return 'paw';
            return null;
        }

        function detectIconType(li, link) {
            const explicit = getExplicitIconClass(li);
            if (explicit) return explicit;

            const label = getLinkLabelWithoutToggle(link);
            const matched = matchLabelToIcon(label);
            if (matched) return matched;

            return 'pentagram';
        }

        function applyVcCustomInlineStyles() {
            const styleTags = document.querySelectorAll('#strangebooks-vc-custom-css-footer, #strangebooks-vc-custom-css-head, style[data-type="vc_shortcodes-custom-css"]');
            styleTags.forEach(function(tag) {
                if (!tag.textContent) return;
                const regex = /\.(vc_custom_\d+)\s*\{([^}]+)\}/g;
                let match;
                while ((match = regex.exec(tag.textContent)) !== null) {
                    const className = match[1];
                    const rules = match[2].trim();
                    document.querySelectorAll('.' + className).forEach(function(el) {
                        const existing = el.getAttribute('style') || '';
                        if (existing.indexOf(rules) === -1) {
                            el.setAttribute('style', existing + '; ' + rules);
                        }
                    });
                }
            });
        }

        function findSubMenu(li) {
            if (!li || !li.children) return null;
            for (let i = 0; i < li.children.length; i++) {
                if (li.children[i].tagName === 'UL') {
                    return li.children[i];
                }
            }
            return li.querySelector('ul.sub-menu, ul.children, ul');
        }

        function findDirectLink(li) {
            if (!li || !li.children) return null;
            for (let i = 0; i < li.children.length; i++) {
                if (li.children[i].tagName === 'A') {
                    return li.children[i];
                }
            }
            return li.querySelector('a');
        }

        function triggerIconAnimation(toggleBtn) {
            if (!toggleBtn) return;
            toggleBtn.classList.remove('animating');
            void toggleBtn.offsetWidth;
            toggleBtn.classList.add('animating');
            if (toggleBtn._animTimer) {
                clearTimeout(toggleBtn._animTimer);
            }
            toggleBtn._animTimer = setTimeout(function() {
                toggleBtn.classList.remove('animating');
            }, 1600);
        }

        function buildToggleButton(iconType, hasSubMenu) {
            const preset = ICON_PRESETS[iconType] || ICON_PRESETS.pentagram;
            const toggleBtn = document.createElement('span');
            toggleBtn.className = 'sidebar-menu-toggle';
            toggleBtn.setAttribute('data-icon-type', iconType);
            toggleBtn.setAttribute('title', hasSubMenu ? 'Toggle Submenu' : 'Icon Effect');

            let particlesHtml = '';
            for (let i = 0; i < preset.particles.length; i++) {
                particlesHtml += '<span class="menu-particle p-' + (i + 1) + '">' + preset.particles[i] + '</span>';
            }

            toggleBtn.innerHTML = '<span class="sidebar-icon-glyph">' + preset.glyph + '</span>' + particlesHtml;
            return toggleBtn;
        }

        function initAllMenus() {
            // 1. Sidebar Menus (.vc_wp_custommenu, .wpb_widgetised_column, .widget_nav_menu)
            const sidebarContainers = document.querySelectorAll('.vc_wp_custommenu, .wpb_widgetised_column, .site-main .widget_nav_menu');
            sidebarContainers.forEach(function(container) {
                const allItems = container.querySelectorAll('li');
                allItems.forEach(function(item) {
                    const subMenu = findSubMenu(item);
                    const link = findDirectLink(item);
                    const explicitIcon = getExplicitIconClass(item);
                    const autoMatched = matchLabelToIcon(getLinkLabelWithoutToggle(link));

                    if (link && (subMenu || explicitIcon || autoMatched)) {
                        if (subMenu) {
                            item.classList.add('has-collapsible-sub');
                        }
                        const iconType = detectIconType(item, link);
                        let toggleBtn = link.querySelector('.sidebar-menu-toggle');
                        if (!toggleBtn || toggleBtn.getAttribute('data-icon-type') !== iconType) {
                            if (toggleBtn) toggleBtn.remove();
                            toggleBtn = buildToggleButton(iconType, !!subMenu);
                            link.appendChild(toggleBtn);
                        }

                        if (!item.hasAttribute('data-hover-init')) {
                            item.setAttribute('data-hover-init', '1');
                            item.addEventListener('mouseenter', function() {
                                const btn = link.querySelector('.sidebar-menu-toggle');
                                triggerIconAnimation(btn);
                            });
                        }

                        if (subMenu && !item.hasAttribute('data-menu-init')) {
                            item.setAttribute('data-menu-init', '1');
                            if (item.classList.contains('current-menu-parent') || item.classList.contains('current-menu-ancestor') || item.classList.contains('current-menu-item')) {
                                item.classList.add('open');
                                subMenu.style.setProperty('display', 'block', 'important');
                            } else {
                                subMenu.style.setProperty('display', 'none', 'important');
                            }
                        }
                    }
                });
            });

            // 2. Main Header Menu (.main-navigation)
            const headerContainers = document.querySelectorAll('.main-navigation');
            headerContainers.forEach(function(nav) {
                const allHeaderItems = nav.querySelectorAll('li');
                allHeaderItems.forEach(function(item) {
                    const subMenu = findSubMenu(item);
                    const link = findDirectLink(item);
                    const explicitIcon = getExplicitIconClass(item);
                    const autoMatched = matchLabelToIcon(getLinkLabelWithoutToggle(link));

                    if (link && (subMenu || explicitIcon || autoMatched)) {
                        // Top-level header items default to pentagram unless explicitly overridden with an icon-* class
                        const isTopLevelHeader = item.parentElement && item.parentElement.id === 'primary-menu';
                        const iconType = (isTopLevelHeader && !explicitIcon) ? 'pentagram' : detectIconType(item, link);

                        let toggleBtn = link.querySelector('.sidebar-menu-toggle');
                        if (!toggleBtn || toggleBtn.getAttribute('data-icon-type') !== iconType) {
                            if (toggleBtn) toggleBtn.remove();
                            toggleBtn = buildToggleButton(iconType, !!subMenu);
                            link.appendChild(toggleBtn);
                        }

                        if (!item.hasAttribute('data-header-hover-init')) {
                            item.setAttribute('data-header-hover-init', '1');
                            item.addEventListener('mouseenter', function() {
                                const btn = link.querySelector('.sidebar-menu-toggle');
                                triggerIconAnimation(btn);
                            });
                        }
                    }
                });
            });
        }

        // Capture-phase delegation for both Header Menu and Sidebar Menus
        document.addEventListener('click', function(e) {
            const menuContainer = e.target.closest('.vc_wp_custommenu, .wpb_widgetised_column, .site-main .widget_nav_menu, .main-navigation');
            if (!menuContainer) return;

            const clickedLink = e.target.closest('a');
            if (!clickedLink || !menuContainer.contains(clickedLink)) return;

            const parentLi = clickedLink.closest('li');
            if (!parentLi) return;

            const isHeaderMenu = menuContainer.classList.contains('main-navigation');
            const subMenu = findSubMenu(parentLi);
            const clickedToggle = e.target.closest('.sidebar-menu-toggle');
            const toggleBtn = clickedLink.querySelector('.sidebar-menu-toggle');

            // Case 1: Leaf item without a sub-menu
            if (!subMenu) {
                if (clickedToggle) {
                    e.preventDefault();
                    e.stopPropagation();
                    triggerIconAnimation(toggleBtn);
                } else if (toggleBtn) {
                    triggerIconAnimation(toggleBtn);
                }
                return;
            }

            // Case 2: Item in the Top Header Menu (.main-navigation)
            if (isHeaderMenu) {
                const rawHref = (clickedLink.getAttribute('href') || '').trim();
                const isPlaceholderLink = (!rawHref || rawHref === '#' || rawHref === 'javascript:void(0);');

                if (clickedToggle || isPlaceholderLink) {
                    e.preventDefault();
                    e.stopPropagation();
                    parentLi.classList.toggle('open');
                    triggerIconAnimation(toggleBtn);
                } else if (toggleBtn) {
                    triggerIconAnimation(toggleBtn);
                }
                return;
            }

            // Case 3: Parent item with a sub-menu in the Sidebar
            const rawHref = (clickedLink.getAttribute('href') || '').trim();
            const isPlaceholderLink = (!rawHref || rawHref === '#' || rawHref === 'javascript:void(0);');

            if (clickedToggle || isPlaceholderLink) {
                e.preventDefault();
                e.stopPropagation();

                const isOpen = parentLi.classList.toggle('open');
                if (isOpen) {
                    subMenu.style.setProperty('display', 'block', 'important');
                } else {
                    subMenu.style.setProperty('display', 'none', 'important');
                }

                triggerIconAnimation(toggleBtn);
            }
        }, true);

        function bootAll() {
            applyVcCustomInlineStyles();
            initAllMenus();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootAll);
        } else {
            bootAll();
        }

        if (window.MutationObserver) {
            const observer = new MutationObserver(function() {
                applyVcCustomInlineStyles();
                initAllMenus();
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'strangebooks_collapsible_sidebar_menu', 999 );