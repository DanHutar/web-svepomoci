<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header(); ?>
<main id="main" class="ai-web-main ai-web-container" tabindex="-1">
	<div class="ai-web-entry">
		<h1><?php esc_html_e( 'Page not found', 'ai-web' ); ?></h1>
		<p><?php esc_html_e( 'The link may no longer be valid. Try searching or return to the homepage.', 'ai-web' ); ?></p>
		<?php get_search_form(); ?>
		<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to homepage', 'ai-web' ); ?></a></p>
	</div>
</main>
<?php get_footer(); ?>
