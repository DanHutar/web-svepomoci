<?php
/** Focused menu fixtures and integration checks. Never packaged in the plugin. */
$passed = array();
function aiwp_menu_test_assert( $condition, $message ) {
    global $passed;
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
    $passed[] = $message;
}

function aiwp_menu_test_save( $id, $html, $css = '' ) {
    $document = aiwp_document_defaults();
    $document['enabled'] = true;
    $document['html'] = $html;
    $document['css'] = $css;
    $document['seo_description'] = 'Menu integration fixture metadata';
    $_POST = array( 'aiwp_nonce' => wp_create_nonce( 'aiwp_save' ), 'aiwp' => wp_slash( $document ) );
    $saved = wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ), true );
    $_POST = array();
    if ( is_wp_error( $saved ) ) {
        throw new RuntimeException( $saved->get_error_message() );
    }
}

$page_id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Menu test page', 'post_status' => 'publish' ), true );
aiwp_menu_test_assert( ! is_wp_error( $page_id ), 'Published page fixture created' );
aiwp_ensure_parts();
$header_id = aiwp_get_part_id( 'header' );
$footer_id = aiwp_get_part_id( 'footer' );
aiwp_menu_test_save( $page_id, '<section id="menu-test-content"><h1>WordPress menu test</h1><nav id="menu-test-page">[aiwp_menu location="footer"]</nav></section>' );
aiwp_menu_test_save( $header_id, '<header id="menu-test-header"><span>Custom header</span><nav aria-label="Test primary">[aiwp_menu location="primary"]</nav></header>', '#menu-test-header { padding: 20px; background: #edf4ff; }' );
aiwp_menu_test_save( $footer_id, '<footer id="menu-test-footer"><span>Custom footer</span><nav aria-label="Test footer">[aiwp_menu location="footer"]</nav></footer>', '#menu-test-footer { padding: 20px; background: #f1f5f9; }' );

$menu_id = wp_create_nav_menu( 'Test primary menu' );
$footer_menu_id = wp_create_nav_menu( 'Test footer menu' );
aiwp_menu_test_assert( ! is_wp_error( $menu_id ) && ! is_wp_error( $footer_menu_id ), 'Separate native menus created' );
$parent_id = wp_update_nav_menu_item( $menu_id, 0, array(
    'menu-item-title' => 'Our services', 'menu-item-type' => 'custom', 'menu-item-url' => home_url( '/services/' ),
    'menu-item-status' => 'publish', 'menu-item-position' => 1,
) );
$item_id = wp_update_nav_menu_item( $menu_id, 0, array(
    'menu-item-title' => 'Current test page', 'menu-item-type' => 'post_type', 'menu-item-object' => 'page',
    'menu-item-object-id' => $page_id, 'menu-item-parent-id' => $parent_id,
    'menu-item-status' => 'publish', 'menu-item-position' => 2,
) );
$footer_item_id = wp_update_nav_menu_item( $footer_menu_id, 0, array(
    'menu-item-title' => 'Footer contact', 'menu-item-type' => 'custom', 'menu-item-url' => home_url( '/contact/' ),
    'menu-item-status' => 'publish',
) );
set_theme_mod( 'nav_menu_locations', array( 'primary' => $menu_id, 'footer' => $footer_menu_id ) );

$primary = aiwp_render_menu( 'primary' );
$footer = aiwp_render_menu( 'footer' );
aiwp_menu_test_assert( false !== strpos( $primary, 'Our services' ) && false !== strpos( $primary, 'Current test page' ) && false === strpos( $primary, 'Footer contact' ), 'Primary location renders only its assigned menu' );
aiwp_menu_test_assert( false !== strpos( $primary, 'sub-menu' ) && false !== strpos( $primary, 'menu-item-has-children' ), 'Native parent and child menu structure survives' );
aiwp_menu_test_assert( false !== strpos( $footer, 'Footer contact' ) && false === strpos( $footer, 'Our services' ), 'Footer location renders its independent menu' );
aiwp_menu_test_assert( false !== strpos( aiwp_render_dynamic_html( '<nav>[aiwp_menu location="primary"]</nav>' ), 'Current test page' ), 'Menu marker expands inside HTML' );
aiwp_menu_test_assert( false !== strpos( aiwp_render_dynamic_html( "[aiwp_menu location='footer']" ), 'Footer contact' ), 'Single-quoted marker attributes work' );
aiwp_menu_test_assert( '' === aiwp_render_dynamic_html( '[aiwp_menu location=primary]' ), 'Unquoted attributes fail closed' );
aiwp_menu_test_assert( '' === aiwp_render_dynamic_html( '[aiwp_menu location="primary" unexpected="value"]' ), 'Unsupported marker attributes fail closed' );
aiwp_menu_test_assert( '' === aiwp_render_menu( 'not-a-location' ) && '' === aiwp_render_dynamic_html( '[aiwp_menu location="not-a-location"]' ), 'Unknown locations render empty output' );
aiwp_menu_test_assert( '' === aiwp_render_menu( '../../primary' ), 'Invalid location is not accepted as a menu name' );

set_theme_mod( 'nav_menu_locations', array( 'primary' => $menu_id ) );
aiwp_menu_test_assert( '' === aiwp_render_menu( 'footer' ), 'Unassigned location does not fall back to another menu or a page list' );
set_theme_mod( 'nav_menu_locations', array( 'primary' => $menu_id, 'footer' => $footer_menu_id ) );

$shortcode_executed = false;
add_shortcode( 'aiwp_test_other', function () use ( &$shortcode_executed ) {
    $shortcode_executed = true;
    return 'UNEXPECTED EXECUTION';
} );
$other = '<p>[aiwp_test_other]</p>[gallery ids="123"]';
aiwp_menu_test_assert( $other === aiwp_render_dynamic_html( $other ) && ! $shortcode_executed, 'Unrelated registered shortcodes remain literal and never execute' );
remove_shortcode( 'aiwp_test_other' );
aiwp_menu_test_assert( '[aiwp_menu location="primary"]' === aiwp_render_dynamic_html( '[[aiwp_menu location="primary"]]' ), 'Escaped menu marker stays literal' );

// Activation is the same idempotent setup WordPress runs when a plugin is reactivated.
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$before = array(
    'page' => get_post_meta( $page_id ), 'header' => get_post_meta( $header_id ), 'footer' => get_post_meta( $footer_id ),
    'parts' => get_option( 'aiwp_part_ids' ), 'settings' => aiwp_get_settings(), 'locations' => get_nav_menu_locations(),
);
deactivate_plugins( 'ai-web-studio/ai-web-studio.php' );
$activation = activate_plugin( 'ai-web-studio/ai-web-studio.php' );
aiwp_menu_test_assert( ! is_wp_error( $activation ), 'Plugin can be reactivated' );
$after = array(
    'page' => get_post_meta( $page_id ), 'header' => get_post_meta( $header_id ), 'footer' => get_post_meta( $footer_id ),
    'parts' => get_option( 'aiwp_part_ids' ), 'settings' => aiwp_get_settings(), 'locations' => get_nav_menu_locations(),
);
aiwp_menu_test_assert( $before === $after, 'Reactivation preserves all page and part metadata, settings and menu assignments' );

echo wp_json_encode( array(
    'passed' => $passed,
    'fixtures' => array( 'pageId' => $page_id, 'headerId' => $header_id, 'footerId' => $footer_id, 'menuId' => $menu_id, 'parentId' => $parent_id, 'itemId' => $item_id, 'footerMenuId' => $footer_menu_id, 'footerItemId' => $footer_item_id ),
) );
