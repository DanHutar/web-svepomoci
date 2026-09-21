<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="ai-web-skip-link" href="#main"><?php esc_html_e( 'Přejít na obsah', 'ai-web' ); ?></a>
<?php if ( ! ai_web_part_hidden( 'header' ) && ! ai_web_render_part( 'header' ) ) : ?>
	<header class="ai-web-header">
		<div class="ai-web-container ai-web-header__inner">
			<div class="ai-web-brand">
				<?php if ( has_custom_logo() ) { the_custom_logo(); } ?>
				<div>
					<a class="ai-web-site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
					<?php if ( get_bloginfo( 'description', 'display' ) ) : ?>
						<p class="ai-web-site-description"><?php echo esc_html( get_bloginfo( 'description', 'display' ) ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<button class="ai-web-menu-toggle" type="button" aria-controls="ai-web-navigation" aria-expanded="false" hidden><?php esc_html_e( 'Menu', 'ai-web' ); ?></button>
			<nav id="ai-web-navigation" class="ai-web-navigation" aria-label="<?php esc_attr_e( 'Hlavní navigace', 'ai-web' ); ?>">
				<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'ai-web-menu', 'fallback_cb' => 'ai_web_page_menu', 'depth' => 2 ) ); ?>
			</nav>
		</div>
	</header>
<?php endif; ?>
