<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="site-branding">
        <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
    </div>
    
    <!-- Mobile Hamburger Button -->
    <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
        &#9776;
    </button>
    
    <nav id="site-navigation" class="main-navigation">
        <?php
        wp_nav_menu( array(
            'theme_location' => 'primary',
            'menu_id'        => 'primary-menu',
            'fallback_cb'    => false,
        ) );
        ?>
    </nav>
</header>