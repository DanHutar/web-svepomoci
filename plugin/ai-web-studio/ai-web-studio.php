<?php
/**
 * Plugin Name: web-svepomoci-plugin
 * Plugin URI: https://github.com/DanHutar/web-svepomoci
 * Update URI: https://github.com/DanHutar/web-svepomoci
 * Description: HTML, CSS a JavaScript pro jednotlivé stránky, společná hlavička a patička a základní SEO.
 * Version: 1.3.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: DanHutar
 * License: GPL-2.0-or-later
 * Text Domain: ai-web-studio
 */

defined( 'ABSPATH' ) || exit;
define( 'AIWP_VERSION', '1.3.0' );
define( 'AIWP_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIWP_URL', plugin_dir_url( __FILE__ ) );

require_once AIWP_DIR . 'includes/documents.php';
require_once AIWP_DIR . 'includes/menus.php';
require_once AIWP_DIR . 'includes/frontend.php';
require_once AIWP_DIR . 'includes/seo.php';
require_once AIWP_DIR . 'includes/design.php';
require_once AIWP_DIR . 'includes/settings.php';
require_once AIWP_DIR . 'includes/class-aiwp-admin.php';
require_once AIWP_DIR . 'includes/github-updates.php';

add_action( 'init', 'aiwp_register_documents' );
add_action( 'admin_init', 'aiwp_ensure_parts' );
add_action( 'save_post', 'aiwp_save_document', 10, 3 );
add_filter( 'wp_insert_post_data', 'aiwp_validate_post_submission', 10, 2 );
add_filter( 'map_meta_cap', 'aiwp_protect_code_documents', 10, 4 );
add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) {
    return in_array( $type, array( 'page', 'aiwp_part' ), true ) ? false : $use;
}, 100, 2 );
register_activation_hook( __FILE__, 'aiwp_activate' );
AIWP_Admin::init();

function aiwp_activate() {
    aiwp_register_documents();
    aiwp_ensure_parts();
}
