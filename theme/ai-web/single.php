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
				<p class="ai-web-entry__meta"><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
				<?php if ( has_post_thumbnail() ) : ?><div class="ai-web-entry__thumbnail"><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
				<div class="ai-web-entry__content"><?php the_content(); ?></div>
				<?php wp_link_pages( array( 'before' => '<nav aria-label="' . esc_attr__( 'Části článku', 'ai-web' ) . '">', 'after' => '</nav>' ) ); ?>
			</article>
			<?php the_post_navigation( array( 'prev_text' => '&larr; %title', 'next_text' => '%title &rarr;' ) ); ?>
			<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>
		</main>
	<?php endif;
endwhile;
get_footer();
