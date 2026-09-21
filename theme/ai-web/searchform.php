<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$ai_web_search_id = wp_unique_id( 'ai-web-search-' );
?>
<form role="search" method="get" class="ai-web-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="<?php echo esc_attr( $ai_web_search_id ); ?>">
		<span class="screen-reader-text"><?php esc_html_e( 'Hledat na webu', 'ai-web' ); ?></span>
		<input type="search" id="<?php echo esc_attr( $ai_web_search_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Co hledáte?', 'ai-web' ); ?>">
	</label>
	<button type="submit"><?php esc_html_e( 'Hledat', 'ai-web' ); ?></button>
</form>
