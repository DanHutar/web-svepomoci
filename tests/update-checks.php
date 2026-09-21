<?php
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$passed = array();
function wsp_assert( $condition, $message ) {
    if ( ! $condition ) {
        throw new Exception( $message );
    }
    $GLOBALS['passed'][] = $message;
}
$snapshot = get_option( 'wsp_test_snapshot' );
foreach ( $snapshot['metadata'] as $id => $meta ) {
    wsp_assert( get_post_meta( $id ) === $meta, 'Saved page/part metadata survives ZIP replacement: ' . $id );
}
foreach ( array( 'settings' => 'aiwp_settings', 'parts' => 'aiwp_part_ids', 'active' => 'active_plugins' ) as $key => $option ) {
    wsp_assert( get_option( $option ) === $snapshot[ $key ], 'Preserved option: ' . $option );
}
wsp_assert( get_theme_mods() === $snapshot['theme_mods'], 'Theme mods including assigned menus survive ZIP replacement' );
wsp_assert( 'ai-web' === get_stylesheet(), 'Existing theme stays active' );
$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/ai-web-studio/ai-web-studio.php', false, false );
$theme = wp_get_theme( 'ai-web' );
$target_version = get_option( 'wsp_test_target_version' );
$version_parts = explode( '.', $target_version );
$version_parts[2] = (int) $version_parts[2] + 1;
$next_version = implode( '.', $version_parts );
wsp_assert( 'web-svepomoci-plugin' === $plugin_data['Name'] && $target_version === $plugin_data['Version'], 'Installed plugin has new name and version' );
wsp_assert( 'web-svepomoci-sablona' === $theme->get( 'Name' ) && $target_version === $theme->get( 'Version' ), 'Installed theme has new name and version' );
wsp_assert( AIWP_VERSION === $plugin_data['Version'], 'Plugin runtime version matches installed header' );

$base = WSP_GitHub_Updates::REPOSITORY . '/releases/download/v' . $next_version . '/';
$good_release = array( 'tag_name' => 'v' . $next_version, 'draft' => false, 'prerelease' => false, 'assets' => array() );
foreach ( array( 'updates.json', 'web-svepomoci-plugin.zip', 'web-svepomoci-sablona.zip' ) as $name ) {
    $good_release['assets'][] = array( 'name' => $name, 'state' => 'uploaded', 'browser_download_url' => $base . $name );
}
$good_manifest = array( 'schema' => 1, 'version' => $next_version, 'components' => array() );
foreach ( array( 'plugin', 'theme' ) as $kind ) {
    $good_manifest['components'][ $kind ] = array( 'version' => $next_version, 'requires' => '6.4', 'requires_php' => '7.4' );
}
$mock_release = $good_release;
$mock_manifest = $good_manifest;
$mock_failure = false;
$requests = 0;
add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$mock_release, &$mock_manifest, &$mock_failure, &$requests, $base ) {
    if ( WSP_GitHub_Updates::API === $url || $base . 'updates.json' === $url ) {
        ++$requests;
        if ( $mock_failure ) {
            return new WP_Error( 'offline', 'Test network failure' );
        }
        return array( 'response' => array( 'code' => 200 ), 'headers' => array(),
            'body' => wp_json_encode( WSP_GitHub_Updates::API === $url ? $mock_release : $mock_manifest ) );
    }
    // Keep tests deterministic and never ask the public directory to supply these custom updates.
    return array( 'response' => array( 'code' => 200 ), 'headers' => array(),
        'body' => wp_json_encode( array( 'plugins' => array(), 'themes' => array(), 'translations' => array(), 'no_update' => array() ) ) );
}, 10, 3 );
WSP_GitHub_Updates::clear_cache();
$release = WSP_GitHub_Updates::release();
wsp_assert( $next_version === $release['plugin']['version'] && $next_version === $release['theme']['version'], 'Valid release resolves both packages' );
$initial_requests = $requests;
WSP_GitHub_Updates::release();
wsp_assert( 2 === $initial_requests && $initial_requests === $requests, 'Both components share one cached API and manifest lookup' );

delete_site_transient( 'update_plugins' );
delete_site_transient( 'update_themes' );
wp_update_plugins();
wp_update_themes();
$plugin_updates = get_site_transient( 'update_plugins' );
$theme_updates = get_site_transient( 'update_themes' );
wsp_assert( isset( $plugin_updates->response['ai-web-studio/ai-web-studio.php'] ), 'WordPress native plugin update discovery offers the new release' );
wsp_assert( isset( $theme_updates->response['ai-web'] ), 'WordPress native theme update discovery offers the new release' );
wsp_assert( $base . 'web-svepomoci-plugin.zip' === $plugin_updates->response['ai-web-studio/ai-web-studio.php']->package, 'Plugin update uses plugin ZIP, not source archive' );
wsp_assert( $base . 'web-svepomoci-sablona.zip' === $theme_updates->response['ai-web']['package'], 'Theme update uses theme ZIP, not source archive' );
wsp_assert( false === WSP_GitHub_Updates::plugin_update( false, $plugin_data, 'unrelated/plugin.php', array() ), 'Unrelated GitHub plugins are untouched' );
wsp_assert( false === WSP_GitHub_Updates::theme_update( false, array( 'UpdateURI' => WSP_GitHub_Updates::REPOSITORY ), 'other-theme', array() ), 'Unrelated themes are untouched' );
$details = apply_filters( 'plugins_api', false, 'plugin_information', (object) array( 'slug' => 'ai-web-studio' ) );
wsp_assert( 'web-svepomoci-plugin' === $details->name && $next_version === $details->version, 'Native plugin details use GitHub release information' );

// Core must not offer a downgrade or repeatedly offer the version already installed.
$equal = $release;
$equal['plugin']['version'] = $target_version;
$equal['theme']['version'] = $target_version;
set_site_transient( WSP_GitHub_Updates::CACHE, array( 'release' => $equal ), 60 );
delete_site_transient( 'update_plugins' );
delete_site_transient( 'update_themes' );
wp_update_plugins();
wp_update_themes();
wsp_assert( empty( get_site_transient( 'update_plugins' )->response ) && empty( get_site_transient( 'update_themes' )->response ), 'Installed version is not offered as a new update' );
$equal['plugin']['version'] = '1.1.0';
$equal['theme']['version'] = '1.1.0';
set_site_transient( WSP_GitHub_Updates::CACHE, array( 'release' => $equal ), 60 );
delete_site_transient( 'update_plugins' );
delete_site_transient( 'update_themes' );
wp_update_plugins();
wp_update_themes();
wsp_assert( empty( get_site_transient( 'update_plugins' )->response ) && empty( get_site_transient( 'update_themes' )->response ), 'Older release never causes a downgrade' );

foreach ( array( 'draft', 'prerelease', 'wrong_repository', 'missing_asset', 'unfinished_asset', 'bad_version', 'bad_manifest', 'bad_requirements', 'network_failure' ) as $case ) {
    $mock_release = $good_release;
    $mock_manifest = $good_manifest;
    $mock_failure = false;
    if ( in_array( $case, array( 'draft', 'prerelease' ), true ) ) { $mock_release[ $case ] = true; }
    if ( 'wrong_repository' === $case ) { $mock_release['assets'][1]['browser_download_url'] = 'https://github.com/someone/else/releases/download/v1.3.0/web-svepomoci-plugin.zip'; }
    if ( 'missing_asset' === $case ) { array_pop( $mock_release['assets'] ); }
    if ( 'unfinished_asset' === $case ) { $mock_release['assets'][1]['state'] = 'new'; }
    if ( 'bad_version' === $case ) { $mock_release['tag_name'] = 'v1.3.0-beta'; }
    if ( 'bad_manifest' === $case ) { $mock_manifest['components']['plugin']['version'] = '99.0.0'; }
    if ( 'bad_requirements' === $case ) { $mock_manifest['components']['theme']['requires_php'] = '<script>'; }
    if ( 'network_failure' === $case ) { $mock_failure = true; }
    WSP_GitHub_Updates::clear_cache();
    wsp_assert( false === WSP_GitHub_Updates::release(), 'Reject invalid or unavailable release: ' . $case );
    $before = $requests;
    WSP_GitHub_Updates::release();
    wsp_assert( $before === $requests, 'Failed lookup is cached: ' . $case );
}
WSP_GitHub_Updates::clear_cache();
echo wp_json_encode( array( 'passed' => $passed ) );
