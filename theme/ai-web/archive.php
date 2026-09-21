<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header(); ?>
<main id="main" class="ai-web-main ai-web-container" tabindex="-1">
	<header class="ai-web-archive-header">
		<?php the_archive_title( '<h1>', '</h1>' ); ?>
		<?php the_archive_description( '<div>', '</div>' ); ?>
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
