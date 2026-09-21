<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header(); ?>
<main id="main" class="ai-web-main ai-web-container" tabindex="-1">
	<header class="ai-web-archive-header">
		<h1><?php printf( esc_html__( 'Výsledky hledání: %s', 'ai-web' ), esc_html( get_search_query() ) ); ?></h1>
		<?php get_search_form(); ?>
	</header>
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			get_template_part( 'template-parts/content', 'summary' );
		}
		the_posts_pagination();
	} else {
		get_template_part( 'template-parts/content', 'none' );
	}
	?>
</main>
<?php get_footer(); ?>
