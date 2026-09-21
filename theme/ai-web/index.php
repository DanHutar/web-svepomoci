<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header(); ?>
<main id="main" class="ai-web-main ai-web-container" tabindex="-1">
	<header class="ai-web-archive-header">
		<h1><?php
			$posts_page = (int) get_option( 'page_for_posts' );
			echo esc_html( is_home() && $posts_page ? get_the_title( $posts_page ) : __( 'Články', 'ai-web' ) );
		?></h1>
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
