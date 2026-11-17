<footer class="site-footer">
    <div class="footer-widgets">
        <div class="widget-area">
            <?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
                <?php dynamic_sidebar( 'footer-1' ); ?>
            <?php endif; ?>
        </div>
        
        <div class="widget-area">
            <?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
                <?php dynamic_sidebar( 'footer-2' ); ?>
            <?php endif; ?>
        </div>

        <div class="widget-area">
            <?php if ( is_active_sidebar( 'footer-3' ) ) : ?>
                <?php dynamic_sidebar( 'footer-3' ); ?>
            <?php endif; ?>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>

<!-- Mobile Menu Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.main-navigation');

    if (toggleButton && navMenu) {
        toggleButton.addEventListener('click', function() {
            navMenu.classList.toggle('toggled');
            const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
            toggleButton.setAttribute('aria-expanded', !isExpanded);
        });
    }
});
</script>
</body>
</html>