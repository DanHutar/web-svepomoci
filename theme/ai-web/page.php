<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) :
	the_post();
	if ( ai_web_is_code_page() ) : ?>
		<main id="main" class="aiwp-page" tabindex="-1"><?php the_content(); ?></main>
	<?php else : ?>
		<main id="main" class="ai-web-main ai-web-container" tabindex="-1">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'ai-web-entry' ); ?>>
				<?php the_title( '<h1>', '</h1>' ); ?>
				<div class="ai-web-entry__content"><?php the_content(); ?></div>
				<?php wp_link_pages( array( 'before' => '<nav aria-label="' . esc_attr__( 'Části stránky', 'ai-web' ) . '">', 'after' => '</nav>' ) ); ?>
			</article>
			<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>
		</main>
	<?php endif;
endwhile;
get_footer();
