<?php
defined( 'ABSPATH' ) || exit;

/** Same page context as the HTML: no direct access by document ID. */
function aiwp_script_url( $kind, $js ) {
    $origin = preg_replace( '~^(https?://[^/]+).*$~', '$1', home_url( '/' ) );
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    $uri = '/' . ltrim( $uri, '/' );
    return add_query_arg( array(
        'aiwp_script' => $kind,
        'aiwp_js_ver' => hash( 'sha256', AIWP_VERSION . $js ),
    ), $origin . $uri );
}

add_action( 'template_redirect', function () {
    if ( ! isset( $_GET['aiwp_script'] ) ) { return; }
    header( 'Content-Type: application/javascript; charset=UTF-8' );
    header( 'X-Content-Type-Options: nosniff' );
    $kind = $_GET['aiwp_script'];
    if ( ! is_string( $kind ) || ! in_array( $kind, array( 'header', 'footer', 'page' ), true ) || is_404() ) {
        status_header( 404 );
        nocache_headers();
        exit;
    }
    $documents = aiwp_frontend_documents();
    if ( ! isset( $documents[ $kind ] ) || '' === trim( $documents[ $kind ]['js'] ) ) {
        status_header( 404 );
        nocache_headers();
        exit;
    }
    // Preserve syntax, line comments and the existing per-document function scope.
    $js = "(function(){\n" . $documents[ $kind ]['js'] . "\n})();";
    $etag = '"' . hash( 'sha256', AIWP_VERSION . $js ) . '"';
    if ( is_user_logged_in() || is_preview() || ( is_page() && get_post_field( 'post_password', get_queried_object_id() ) ) ) {
        nocache_headers();
    } else {
        header( 'Cache-Control: private, no-cache, must-revalidate' );
        header( 'ETag: ' . $etag );
        if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && trim( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) === $etag ) {
            status_header( 304 );
            exit;
        }
    }
    status_header( 200 );
    if ( 'HEAD' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
        echo $js; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JavaScript response, not HTML.
    }
    exit;
}, 0 );
