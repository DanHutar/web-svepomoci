<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header(); ?>
<main id="main" class="ai-web-main ai-web-container" tabindex="-1">
	<div class="ai-web-entry">
		<h1><?php esc_html_e( 'Stránka nebyla nalezena', 'ai-web' ); ?></h1>
		<p><?php esc_html_e( 'Odkaz už nemusí být platný. Zkuste vyhledávání nebo se vraťte na úvodní stránku.', 'ai-web' ); ?></p>
		<?php get_search_form(); ?>
		<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Přejít na úvodní stránku', 'ai-web' ); ?></a></p>
	</div>
</main>
<?php get_footer(); ?>
