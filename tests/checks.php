<?php
require_once '/aiwp-tests/seed.php';
require_once ABSPATH . 'wp-admin/includes/post.php';
require_once ABSPATH . 'wp-admin/includes/revision.php';
$passed = array();
function aiwp_test_assert( $condition, $message ) {
    global $passed;
    if ( ! $condition ) { throw new RuntimeException( $message ); }
    $passed[] = $message;
}
foreach ( array( AIWP_DIR, get_template_directory() ) as $directory ) {
    foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory ) ) as $file ) {
        if ( 'php' === $file->getExtension() ) { token_get_all( file_get_contents( $file->getPathname() ), TOKEN_PARSE ); }
    }
}
aiwp_test_assert( true, 'All plugin/theme PHP files parse' );
$fixtures = aiwp_test_seed();
$id = $fixtures['pageId'];
$original = aiwp_get_document( $id );
aiwp_test_assert( $original['enabled'] && strpos( $original['html'], 'sample-page' ) !== false, 'Seed uses native save path' );
$test = $original;
$test['html'] = '<section id="test">Příliš žluťoučký kůň &amp; "uvozovky"</section>';
$test['css'] = '.test::before { content: "\\2713"; }';
$test['js'] = 'const re = /\\d+\\s/; const value = "\\n";';
$_POST = array( 'aiwp_nonce' => wp_create_nonce( 'aiwp_save' ), 'aiwp' => wp_slash( $test ) );
wp_update_post( array( 'ID' => $id, 'post_title' => 'Revize A' ) );
$_POST = array();
aiwp_test_assert( aiwp_get_document( $id ) === $test, 'Unicode, quotes and backslashes round trip without corruption' );
$revisions = wp_get_post_revisions( $id, array( 'orderby' => 'ID', 'order' => 'DESC' ) );
$revision = reset( $revisions );
aiwp_test_assert( $revision && aiwp_get_document( $revision->ID ) === $test, 'Native revision contains all AI fields' );
$_POST = array( 'aiwp_nonce' => wp_create_nonce( 'aiwp_save' ), 'aiwp' => wp_slash( $original ) );
wp_update_post( array( 'ID' => $id, 'post_title' => 'Revize B' ) );
$_POST = array();
wp_restore_post_revision( $revision->ID );
aiwp_test_assert( aiwp_get_document( $id ) === $test, 'Native restore recovers HTML CSS JS SEO together' );
foreach ( array( array( 'html' => '<?php echo 1;' ), array( 'js' => '<script>alert(1)</script>' ), array( 'html' => '<html><body>x</body></html>' ), array( 'css' => '</style><img src=x>' ), array( 'html' => "```html\nx\n```" ), array( 'seo_image' => 'javascript:alert(1)' ), array( 'html' => array( 'invalid' ) ) ) as $bad ) {
    aiwp_test_assert( is_wp_error( aiwp_validate_document( $bad ) ), 'Reject invalid input: ' . json_encode( $bad ) );
}
$_POST = array( 'aiwp' => array( 'html' => 'missing nonce' ) );
aiwp_save_document( $id, get_post( $id ), true );
aiwp_test_assert( aiwp_get_document( $id ) === $test, 'Missing nonce cannot overwrite fields' );
$editor = wp_insert_user( array( 'user_login' => 'test-editor', 'user_pass' => wp_generate_password(), 'role' => 'editor' ) );
wp_set_current_user( $editor );
$_POST = array( 'aiwp_nonce' => wp_create_nonce( 'aiwp_save' ), 'aiwp' => array( 'enabled' => '1', 'html' => 'editor injection' ) );
aiwp_save_document( $id, get_post( $id ), true );
aiwp_test_assert( aiwp_get_document( $id ) === $test && ! current_user_can( 'edit_post', $id ), 'Editor cannot alter code page' );
$clean = $fixtures['normalId'];
$autosave = _wp_put_post_revision( $clean, true );
$_POST = array( '_aiwp_enabled' => '1', '_aiwp_html' => '<img src=x onerror=alert(1)>', '_aiwp_js' => 'alert(1)' );
wp_autosave_post_revisioned_meta_fields( array( 'ID' => $autosave, 'post_parent' => $clean ) );
aiwp_test_assert( ! get_metadata( 'post', $autosave, '_aiwp_html', true ), 'Native autosave cannot inject raw AI metadata for editor' );
$_POST = array();
wp_set_current_user( 1 );
$_POST = array( 'aiwp_nonce' => wp_create_nonce( 'aiwp_save' ), 'aiwp' => aiwp_document_defaults() );
wp_update_post( array( 'ID' => $id, 'post_title' => 'Cleared code' ) );
$_POST = array();
wp_set_current_user( $editor );
aiwp_test_assert( ! current_user_can( 'edit_post', $id ), 'Cleared code page stays protected against older revision restore' );
wp_set_current_user( 1 );
$_POST = array( 'aiwp_nonce' => wp_create_nonce( 'aiwp_save' ), 'aiwp' => wp_slash( $original ) );
wp_update_post( array( 'ID' => $id, 'post_title' => 'Úvod' ) );
$_POST = array();
$settings = aiwp_sanitize_settings( array( 'accent' => '#123abc', 'font' => 'serif', 'width' => '1100', 'css' => '.global{color:red}' ) );
aiwp_test_assert( '#123abc' === $settings['accent'] && 1100 === $settings['width'], 'Shared settings validate and normalize' );
add_filter( 'aiwp_external_seo_active', '__return_true' );
aiwp_test_assert( aiwp_has_seo_plugin(), 'SEO output can defer to another plugin' );
remove_filter( 'aiwp_external_seo_active', '__return_true' );
// Mimic core's preview substitution. The saved-data helper must remain unaffected.
$preview_filter = function ( $value, $object_id, $key ) use ( $id ) { return $id === $object_id && '_aiwp_enabled' === $key ? false : $value; };
add_filter( 'get_post_metadata', $preview_filter, 10, 3 );
aiwp_test_assert( aiwp_get_document( $id )['enabled'], 'Saved AI preview survives missing autosave metadata' );
remove_filter( 'get_post_metadata', $preview_filter, 10 );
echo json_encode( array( 'passed' => $passed, 'fixtures' => $fixtures ), JSON_UNESCAPED_UNICODE );
