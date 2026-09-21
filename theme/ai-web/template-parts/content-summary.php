<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'ai-web-entry ai-web-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="ai-web-entry__thumbnail" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'large' ); ?></a>
	<?php endif; ?>
	<h2><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ?: __( 'Bez názvu', 'ai-web' ) ); ?></a></h2>
	<?php if ( 'post' === get_post_type() ) : ?>
		<p class="ai-web-entry__meta"><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
	<?php endif; ?>
	<div class="ai-web-entry__content"><?php the_excerpt(); ?></div>
</article>
