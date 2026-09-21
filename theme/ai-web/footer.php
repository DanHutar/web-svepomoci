<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! ai_web_part_hidden( 'footer' ) && ! ai_web_render_part( 'footer' ) ) : ?>
	<footer class="ai-web-footer">
		<div class="ai-web-container ai-web-footer__inner">
			<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></p>
			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<nav aria-label="<?php esc_attr_e( 'Navigace v patičce', 'ai-web' ); ?>"><?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'menu_class' => 'ai-web-menu', 'fallback_cb' => false, 'depth' => 1 ) ); ?></nav>
			<?php endif; ?>
		</div>
	</footer>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
