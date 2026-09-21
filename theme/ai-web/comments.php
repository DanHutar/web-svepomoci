<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="ai-web-comments" aria-label="<?php esc_attr_e( 'Komentáře', 'ai-web' ); ?>">
	<?php if ( have_comments() ) : ?>
		<h2><?php printf( esc_html__( 'Komentáře (%s)', 'ai-web' ), esc_html( number_format_i18n( get_comments_number() ) ) ); ?></h2>
		<ol class="comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 48 ) ); ?></ol>
		<?php the_comments_pagination(); ?>
	<?php endif; ?>
	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p><?php esc_html_e( 'Komentáře jsou uzavřeny.', 'ai-web' ); ?></p>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
