<?php
defined( 'ABSPATH' ) || exit;

/** Be conservative: copied block markup and WP presets still need core CSS. */
function aiwp_needs_core_styles( $text ) {
    $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    return (bool) preg_match( '/--wp--|\bwp[-:]|\bhas-[a-z0-9_-]+|\bis-(?:layout|style)-|\balign(?:wide|full|left|right|center)\b/i', $text );
}

function aiwp_can_trim_core_styles() {
    // Other themes, custom page templates and Customizer previews may add blocks.
    if ( is_admin() || 'ai-web' !== get_stylesheet() || ! is_page() || ! aiwp_is_code_page() || is_customize_preview() || get_page_template_slug( get_queried_object_id() ) || post_password_required( get_queried_object_id() ) ) {
        return false;
    }
    $texts = array( aiwp_get_settings()['css'], wp_get_custom_css() );
    foreach ( aiwp_frontend_documents() as $document ) {
        foreach ( array( 'html', 'css', 'js' ) as $field ) { $texts[] = $document[ $field ]; }
    }
    // Menu item classes are editable separately from the AI HTML.
    foreach ( array_unique( array_values( get_nav_menu_locations() ) ) as $menu_id ) {
        foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $item ) {
            $texts[] = implode( ' ', $item->classes );
        }
    }
    foreach ( $texts as $text ) {
        if ( aiwp_needs_core_styles( $text ) ) { return false; }
    }
    // Integrations that add their own block markup can retain core styles explicitly.
    return (bool) apply_filters( 'aiwp_trim_core_styles', true, get_queried_object_id() );
}

function aiwp_dequeue_unused_core_styles() {
    foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles', 'wp-global-styles-placeholder' ) as $handle ) {
        wp_dequeue_style( $handle );
    }
}

add_action( 'wp', function () {
    if ( is_admin() || is_feed() || 'ai-web' !== get_stylesheet() ) { return; }

    // Leave the editor, login, mail and feed behavior alone; use device emoji on the site.
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_footer_scripts', '_print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
    add_action( 'wp_enqueue_scripts', function () { wp_dequeue_style( 'wp-emoji-styles' ); }, 100 );

    if ( ! aiwp_can_trim_core_styles() ) { return; }
    // Global styles may be enqueued in either the head or footer, depending on WP version.
    remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
    remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
    add_action( 'wp_enqueue_scripts', 'aiwp_dequeue_unused_core_styles', 100 );
    add_action( 'wp_print_styles', 'aiwp_dequeue_unused_core_styles', 100 );
    add_action( 'wp_footer', 'aiwp_dequeue_unused_core_styles', 19 );
} );
