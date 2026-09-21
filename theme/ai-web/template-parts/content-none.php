<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ai-web-entry">
	<p><?php esc_html_e( 'Zatím zde není žádný obsah, který by odpovídal vašemu výběru.', 'ai-web' ); ?></p>
	<?php if ( ! is_search() ) { get_search_form(); } ?>
</div>
