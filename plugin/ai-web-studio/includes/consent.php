<?php
defined( 'ABSPATH' ) || exit;

function aiwp_consent_categories() {
    return array( 'analytics' => __( 'Analytics', 'ai-web-studio' ), 'marketing' => 'Marketing' );
}

function aiwp_consent_settings() {
    $stored = get_option( 'aiwp_consent_settings', array() );
    $stored = is_array( $stored ) ? $stored : array();
    $result = array( 'policy' => isset( $stored['policy'] ) && is_string( $stored['policy'] ) ? $stored['policy'] : '' );
    // Built-in consent is retired. Preserve stored settings, but never activate them.
    $result['external'] = true;
    $result['revision'] = isset( $stored['revision'] ) ? absint( $stored['revision'] ) : 0;
    foreach ( aiwp_consent_categories() as $key => $label ) {
        $item = isset( $stored[ $key ] ) && is_array( $stored[ $key ] ) ? $stored[ $key ] : array();
        $result[ $key ] = array( 'enabled' => ! empty( $item['enabled'] ) );
        foreach ( array( 'description', 'js', 'cookies', 'storage' ) as $field ) {
            $result[ $key ][ $field ] = isset( $item[ $field ] ) && is_string( $item[ $field ] ) ? $item[ $field ] : '';
        }
    }
    return $result;
}

function aiwp_consent_version( $settings ) {
    return substr( hash( 'sha256', home_url( '/' ) . wp_json_encode( $settings ) ), 0, 32 );
}

function aiwp_consent_cookie_name() {
    return 'aiwp_consent_' . substr( hash( 'sha256', home_url( '/' ) ), 0, 8 );
}

/** Legacy markers remain harmless in existing saved pages and footers. */
function aiwp_render_cookie_settings_button() { return ''; }
function aiwp_consent_active( $settings, $key ) { return false; }
function aiwp_consent_allows( $settings, $key ) { return false; }

// Keep old script URLs closed, including requests from previously cached pages.
add_action( 'init', function () {
    if ( isset( $_GET['aiwp_consent_script'] ) && ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
} );
add_action( 'template_redirect', function () {
    if ( ! isset( $_GET['aiwp_consent_script'] ) ) { return; }
    header( 'Content-Type: application/javascript; charset=UTF-8' );
    header( 'X-Content-Type-Options: nosniff' );
    nocache_headers();
    status_header( 410 );
    exit;
}, -20 );
