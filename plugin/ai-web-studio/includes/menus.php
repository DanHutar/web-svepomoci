<?php
defined( 'ABSPATH' ) || exit;

/** Render only the menu assigned in Appearance > Menus, never an arbitrary fallback. */
function aiwp_render_menu( $location ) {
    if ( ! in_array( $location, array( 'primary', 'footer' ), true ) || ! has_nav_menu( $location ) ) {
        return '';
    }
    $html = wp_nav_menu( array(
        'theme_location' => $location,
        'container' => false,
        'echo' => false,
        'fallback_cb' => false,
        'menu_class' => 'aiwp-menu',
        'items_wrap' => '<ul class="%2$s">%3$s</ul>',
        'depth' => 0,
        'aiwp_dynamic_menu' => true,
    ) );
    return is_string( $html ) ? $html : '';
}

// The same assigned menu may appear in multiple fragments; avoid duplicate item IDs.
add_filter( 'nav_menu_item_id', function ( $id, $item, $args ) {
    return ! empty( $args->aiwp_dynamic_menu ) ? '' : $id;
}, 10, 3 );

/** A small allowlist of dynamic markup; third-party shortcodes are never executed here. */
function aiwp_render_dynamic_html( $html ) {
    if ( false === strpos( $html, '[aiwp_menu' ) ) {
        return $html;
    }
    return preg_replace_callback( '/(\[?)\[aiwp_menu(?=\s|\])([^\]]*)\](\]?)/', function ( $match ) {
        if ( '[' === $match[1] && ']' === $match[3] ) {
            return substr( $match[0], 1, -1 );
        }
        // Keep the marker intentionally simple so it is easy to copy into an HTML nav.
        if ( ! preg_match( '/^\s+location\s*=\s*([\'"])(primary|footer)\1\s*$/', $match[2], $attributes ) ) {
            return '';
        }
        return $match[1] . aiwp_render_menu( $attributes[2] ) . $match[3];
    }, $html );
}
