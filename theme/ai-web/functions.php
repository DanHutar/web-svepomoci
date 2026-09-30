<?php
/** Theme setup and ordinary WordPress presentation. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/includes/languages.php';
WSP_Languages::register( 'ai-web', get_template_directory() . '/languages' );
require_once get_template_directory() . '/includes/github-updates.php';
require_once get_template_directory() . '/includes/webp.php';

function ai_web_setup() {
	load_theme_textdomain( 'ai-web', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'ai-web-parts' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 64, 'width' => 180, 'flex-height' => true, 'flex-width' => true ) );
	register_nav_menus( array(
		'primary' => __( 'Primary menu', 'ai-web' ),
		'footer'  => __( 'Footer menu', 'ai-web' ),
	) );
}
add_action( 'after_setup_theme', 'ai_web_setup' );

function ai_web_assets() {
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'ai-web', get_stylesheet_uri(), array(), $version );
	wp_enqueue_script( 'ai-web-navigation', get_template_directory_uri() . '/assets/navigation.js', array(), $version, true );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'ai_web_assets' );

/** Plugin-dependent behavior always has an ordinary WordPress fallback. */
function ai_web_is_code_page() {
	return function_exists( 'aiwp_is_code_page' ) && aiwp_is_code_page( get_the_ID() );
}

function ai_web_part_hidden( $kind ) {
	return function_exists( 'aiwp_page_hidden' ) && aiwp_page_hidden( $kind );
}

function ai_web_render_part( $kind ) {
	return function_exists( 'aiwp_render_part' ) && aiwp_render_part( $kind );
}

function ai_web_page_menu( $args = array() ) {
	$pages = wp_list_pages( array( 'title_li' => '', 'echo' => false, 'depth' => 2 ) );
	if ( $pages ) {
		echo '<ul class="ai-web-menu">' . $pages . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core renders escaped page links.
	}
}

function ai_web_companion_notice() {
	if ( function_exists( 'aiwp_is_code_page' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-info is-dismissible">
		<p><?php esc_html_e( 'The web-svepomoci-sablona theme is ready. Activate web-svepomoci-plugin from the installation package for separate HTML, CSS, JavaScript and SEO fields, and custom headers and footers.', 'ai-web' ); ?> <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Go to plugins', 'ai-web' ); ?></a></p>
	</div>
	<?php
}
add_action( 'admin_notices', 'ai_web_companion_notice' );
