<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ai-web-entry">
	<p><?php esc_html_e( 'No content matches your selection yet.', 'ai-web' ); ?></p>
	<?php if ( ! is_search() ) { get_search_form(); } ?>
</div>
