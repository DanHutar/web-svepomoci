<?php
/** Test-only fixtures; never included in installable plugin/theme ZIPs. */
function aiwp_test_seed() {
    $page = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Úvod', 'post_name' => 'uvod', 'post_status' => 'publish', 'post_content' => '<p>Standardní obsah zachován.</p>' ) );
    $normal = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Kontakt', 'post_name' => 'kontakt', 'post_status' => 'publish', 'post_content' => '<p id="normal-content">Běžný obsah WordPressu.</p>' ) );
    // Demo navigation uses the same Appearance > Menus data as a normal installation.
    $primary = wp_create_nav_menu( 'Ukázkové hlavní menu' );
    $footer = wp_create_nav_menu( 'Ukázkové menu v patičce' );
    foreach ( array( $primary => array( 'Co tvoříme' => '/#sluzby', 'Jak pracujeme' => '/#postup', 'Kontakt' => '/?page_id=' . $normal ), $footer => array( 'Úvod' => '/', 'Kontakt' => '/?page_id=' . $normal ) ) as $menu_id => $items ) {
        foreach ( $items as $title => $url ) {
            wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => $title, 'menu-item-url' => home_url( $url ), 'menu-item-status' => 'publish', 'menu-item-type' => 'custom' ) );
        }
    }
    set_theme_mod( 'nav_menu_locations', array( 'primary' => $primary, 'footer' => $footer ) );
    aiwp_ensure_parts();
    foreach ( array( 'page' => $page, 'header' => aiwp_get_part_id( 'header' ), 'footer' => aiwp_get_part_id( 'footer' ) ) as $kind => $id ) {
        $data = aiwp_document_defaults();
        $data['enabled'] = true;
        foreach ( array( 'html', 'css', 'js' ) as $field ) {
            $data[ $field ] = file_get_contents( '/aiwp-examples/' . $kind . '.' . $field );
        }
        if ( 'page' === $kind ) {
            $data['seo_title'] = 'Ateliér – ukázka webu s AI';
            $data['seo_description'] = 'Ukázková stránka vytvořená pomocí HTML, CSS a JavaScriptu v AI Web Studiu.';
        }
        $_POST['aiwp_nonce'] = wp_create_nonce( 'aiwp_save' );
        $_POST['aiwp'] = wp_slash( $data );
        wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
        unset( $_POST['aiwp_nonce'], $_POST['aiwp'] );
    }
    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $page );
    return array( 'pageId' => $page, 'normalId' => $normal, 'headerId' => aiwp_get_part_id( 'header' ), 'footerId' => aiwp_get_part_id( 'footer' ) );
}
