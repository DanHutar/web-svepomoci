<?php
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require '/aiwp-tests/seed.php';
if ( ! defined( 'FS_METHOD' ) ) {
    define( 'FS_METHOD', 'direct' );
}
add_filter( 'filesystem_method', function () { return 'direct'; } );
$fixture = aiwp_test_seed();
$manifest = json_decode( file_get_contents( '/tmp/updates.json' ), true );
$target_version = $manifest['version'];
update_option( 'wsp_test_target_version', $target_version );
$ids = array( $fixture['pageId'], $fixture['headerId'], $fixture['footerId'] );
$snapshot = array();
foreach ( $ids as $id ) {
    $snapshot[ $id ] = get_post_meta( $id );
}
update_option( 'wsp_test_snapshot', array(
    'metadata' => $snapshot,
    'settings' => get_option( 'aiwp_settings' ),
    'parts' => get_option( 'aiwp_part_ids' ),
    'theme_mods' => get_theme_mods(),
    'active' => get_option( 'active_plugins' ),
) );
set_site_transient( 'update_plugins', (object) array( 'response' => array(
    'ai-web-studio/ai-web-studio.php' => (object) array(
        'slug' => 'ai-web-studio', 'plugin' => 'ai-web-studio/ai-web-studio.php',
        'new_version' => $target_version, 'package' => '/tmp/web-svepomoci-plugin.zip',
    ),
) ) );
set_site_transient( 'update_themes', (object) array( 'response' => array(
    'ai-web' => array( 'theme' => 'ai-web', 'new_version' => $target_version, 'package' => '/tmp/web-svepomoci-sablona.zip' ),
) ) );
ob_start();
$plugin = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
// The normal AJAX update UI uses bulk_upgrade even for one plugin. Unlike the
// legacy single-upgrade screen, this preserves activation without a browser redirect.
$plugin_results = $plugin->bulk_upgrade( array( 'ai-web-studio/ai-web-studio.php' ) );
$plugin_result = isset( $plugin_results['ai-web-studio/ai-web-studio.php'] )
    ? $plugin_results['ai-web-studio/ai-web-studio.php'] : false;
$theme = new Theme_Upgrader( new WP_Ajax_Upgrader_Skin() );
$theme_result = $theme->upgrade( 'ai-web' );
ob_end_clean();
if ( is_wp_error( $plugin_result ) || is_wp_error( $theme_result ) ) {
    throw new Exception( 'ZIP upgrade failed: ' . wp_json_encode( array( $plugin_result, $theme_result ) ) );
}
echo wp_json_encode( array(
    'plugin' => is_array( $plugin_result ) && ! empty( $plugin_result['destination'] ), 'theme' => $theme_result,
    'plugin_errors' => $plugin->skin->get_errors()->get_error_messages(),
    'theme_errors' => $theme->skin->get_errors()->get_error_messages(),
    'plugin_messages' => $plugin->skin->get_upgrade_messages(),
    'theme_messages' => $theme->skin->get_upgrade_messages(),
) );
