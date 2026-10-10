<?php
// Run only inside the disposable Playground test installation.
require_once ABSPATH . 'wp-admin/includes/post.php';
require_once ABSPATH . 'wp-admin/includes/template.php';
$passed = array();
function aiwp_security_assert( $condition, $label ) {
    global $passed;
    if ( ! $condition ) { throw new RuntimeException( $label ); }
    $passed[] = $label;
}
$security_page = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Security fixture' ) );
$normal = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Standard fixture' ) );
$_POST = array( 'aiwp_nonce' => wp_create_nonce( 'aiwp_save' ), 'aiwp' => array( 'enabled' => '1', 'html' => '<p>SAFE_DOCUMENT</p>', 'css' => '.safe{color:red}', 'js' => 'const safeDocument=true;' ) );
aiwp_save_document( $security_page, get_post( $security_page ), true );
$_POST = array();
$original = aiwp_get_document( $security_page );
aiwp_security_assert( $original['enabled'], 'Administrator can save valid code' );
$settings = aiwp_get_settings();
foreach ( array( 'anonymous', 'subscriber', 'contributor', 'author', 'editor', 'limited_admin' ) as $role ) {
    $uid = 0;
    if ( 'anonymous' !== $role ) {
        $uid = wp_insert_user( array( 'user_login' => 'security-' . $role, 'user_pass' => wp_generate_password(), 'role' => 'limited_admin' === $role ? 'administrator' : $role ) );
        if ( 'limited_admin' === $role ) { ( new WP_User( $uid ) )->add_cap( 'unfiltered_html', false ); }
    }
    wp_set_current_user( $uid );
    $_POST = array( 'aiwp_nonce' => wp_create_nonce( 'aiwp_save' ), 'aiwp' => array( 'enabled' => '1', 'html' => '<img src=x onerror=alert(1)>' ) );
    aiwp_save_document( $security_page, get_post( $security_page ), true );
    aiwp_save_document( $normal, get_post( $normal ), true );
    aiwp_security_assert( aiwp_get_document( $security_page ) === $original && ! aiwp_get_document( $normal )['enabled'], $role . ': cannot save code even with own valid nonce' );
    aiwp_security_assert( ! current_user_can( 'edit_post', $security_page ) && ! current_user_can( 'delete_post', $security_page ), $role . ': cannot edit or delete protected page' );
    aiwp_security_assert( aiwp_sanitize_settings( array( 'accent' => '#123456', 'font' => 'lora', 'width' => 1200, 'css' => '.attack{}' ) ) === $settings, $role . ': cannot alter global code settings' );
    $request = new WP_REST_Request( 'POST', '/wp/v2/pages/' . $security_page );
    $request->set_param( 'title', 'Unauthorized REST modification' );
    aiwp_security_assert( rest_do_request( $request )->get_status() >= 400, $role . ': REST modification refused' );
    $request = new WP_REST_Request( 'GET', '/wp/v2/pages/' . $security_page );
    $response = rest_do_request( $request );
    aiwp_security_assert( false === strpos( wp_json_encode( $response->get_data() ), '_aiwp_html' ), $role . ': private editor metadata absent from REST' );
}
wp_set_current_user( 1 );
foreach ( array( '', 'invalid', array( 'invalid' ) ) as $nonce ) {
    $_POST = array( 'aiwp_nonce' => $nonce, 'aiwp' => array( 'html' => 'FORGED_DOCUMENT' ) );
    aiwp_save_document( $security_page, get_post( $security_page ), true );
    aiwp_security_assert( aiwp_get_document( $security_page ) === $original, 'Invalid nonce preserves administrator document: ' . wp_json_encode( $nonce ) );
}
$_POST = array();
foreach ( array( array( 'html' => str_repeat( 'x', 500001 ) ), array( 'css' => array( 'bad' ) ), array( 'seo_image' => 'data:text/html,test' ), array( 'seo_canonical' => 'https://user:password@example.org/' ) ) as $input ) {
    aiwp_security_assert( is_wp_error( aiwp_validate_document( $input ) ), 'Malformed, oversized or forbidden URL rejected: ' . implode( ',', array_keys( $input ) ) );
}
$requests = 0;
$block_http = function () use ( &$requests ) { $requests++; return new WP_Error( 'audit_no_network' ); };
add_filter( 'pre_http_request', $block_http );
$seo = aiwp_validate_document( array( 'seo_title' => '<script>alert(1)</script>Title', 'seo_description' => '"><img src=x onerror=alert(1)>', 'seo_image' => 'https://127.0.0.1/image.png' ) );
remove_filter( 'pre_http_request', $block_http );
aiwp_security_assert( ! is_wp_error( $seo ) && 0 === $requests, 'SEO URL validation does not make server requests' );
aiwp_security_assert( false === strpos( $seo['seo_title'], '<' ) && false === strpos( $seo['seo_description'], '<' ), 'SEO text strips executable tags' );
$invalid_settings = $settings;
$invalid_settings['font'] = 'https://attacker.invalid/evil.css';
aiwp_security_assert( aiwp_sanitize_settings( $invalid_settings ) === $settings, 'Font selection cannot inject arbitrary external stylesheet' );
// Exercise the actual output sinks with a hostile translation, not just sanitizer calls.
$_SERVER['REQUEST_URI'] = '/wp-admin/post.php';
$hostile_translation = function ( $translated, $text, $domain ) {
    return 'ai-web-studio' === $domain ? $translated . '<img src=x onerror="alert(1)"><script>alert(1)</script>' : $translated;
};
add_filter( 'gettext', $hostile_translation, 10, 3 );
foreach ( array( true, false ) as $enabled ) {
    update_post_meta( $security_page, '_aiwp_enabled', $enabled );
    ob_start(); AIWP_Admin::editor( get_post( $security_page ) ); $markup = ob_get_clean();
    aiwp_security_assert( ! preg_match( '~<script\b|<img[^>]*onerror\s*=~i', $markup ), 'Translated editor labels cannot emit executable HTML, enabled=' . (int) $enabled );
}
wp_set_current_user( 0 );
foreach ( array( 'editor', 'seo' ) as $method ) {
    ob_start(); AIWP_Admin::$method( get_post( $security_page ) ); $markup = ob_get_clean();
    aiwp_security_assert( false !== strpos( $markup, '<p>' ) && ! preg_match( '~<script\b|<img[^>]*onerror\s*=~i', $markup ), 'Restricted ' . $method . ' notice keeps paragraph but removes executable translation markup' );
}
remove_filter( 'gettext', $hostile_translation, 10 );
wp_set_current_user( 1 );
$private_fixtures = array();
foreach ( array( 'draft', 'private', 'password' ) as $state ) {
    $fixture_id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Private security fixture', 'post_status' => 'password' === $state ? 'publish' : $state, 'post_password' => 'password' === $state ? wp_generate_password() : '' ) );
    update_post_meta( $fixture_id, '_aiwp_enabled', true );
    update_post_meta( $fixture_id, '_aiwp_html', '<p>PRIVATE_SECURITY_SENTINEL</p>' );
    update_post_meta( $fixture_id, '_aiwp_css', '.PRIVATE_SECURITY_SENTINEL{color:red}' );
    update_post_meta( $fixture_id, '_aiwp_js', 'const PRIVATE_SECURITY_SENTINEL=true;' );
    $private_fixtures[ $state ] = $fixture_id;
}
echo wp_json_encode( array( 'passed' => $passed, 'page' => $security_page, 'private_fixtures' => $private_fixtures ) );
