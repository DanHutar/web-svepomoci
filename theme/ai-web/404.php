<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header(); ?>
<main id="main" class="aiwp-content ai-web-not-found" tabindex="-1">
	<div class="ai-web-container">
		<p class="ai-web-not-found__code" aria-hidden="true">404</p>
		<h1><?php esc_html_e( 'Page not found', 'ai-web' ); ?></h1>
		<p><?php esc_html_e( 'This page does not exist or has moved.', 'ai-web' ); ?></p>
		<a class="ai-web-not-found__button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back home', 'ai-web' ); ?></a>
	</div>
</main>
<?php get_footer(); ?>
