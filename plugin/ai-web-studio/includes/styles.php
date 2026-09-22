<?php
defined( 'ABSPATH' ) || exit;

/** Conservative compaction: preserve strings, URLs and comment token boundaries. */
function aiwp_compact_css( $css ) {
    $out = '';
    $length = strlen( $css );
    for ( $i = 0; $i < $length; $i++ ) {
        $char = $css[ $i ];
        if ( '"' === $char || "'" === $char ) {
            $quote = $char;
            $out .= $char;
            while ( ++$i < $length ) {
                $out .= $css[ $i ];
                if ( '\\' === $css[ $i ] && $i + 1 < $length ) {
                    $out .= $css[ ++$i ];
                } elseif ( $quote === $css[ $i ] ) {
                    break;
                }
            }
        } elseif ( '\\' === $char ) {
            // Escaped identifiers can spell functions such as url(). Keep them verbatim.
            return $css;
        } elseif ( ( 'u' === $char || 'U' === $char ) && 0 === strcasecmp( substr( $css, $i, 4 ), 'url(' ) && ( 0 === $i || ! preg_match( '/[a-zA-Z0-9_-]/', $css[ $i - 1 ] ) ) ) {
            $out .= substr( $css, $i, 4 );
            $i += 3;
            $quote = '';
            while ( ++$i < $length ) {
                $char = $css[ $i ];
                $out .= $char;
                if ( '\\' === $char && $i + 1 < $length ) {
                    $out .= $css[ ++$i ];
                } elseif ( $quote ) {
                    if ( $char === $quote ) { $quote = ''; }
                } elseif ( '"' === $char || "'" === $char ) {
                    $quote = $char;
                } elseif ( ')' === $char ) {
                    break;
                }
            }
        } elseif ( '/' === $char && $i + 1 < $length && '*' === $css[ $i + 1 ] ) {
            $end = strpos( $css, '*/', $i + 2 );
            if ( false === $end ) { return $css; }
            // An empty comment preserves a/**/.b versus a .b and adjacent tokens.
            $out .= '/**/';
            $i = $end + 1;
        } elseif ( false !== strpos( " \t\r\n\f", $char ) ) {
            $out .= ' ';
            while ( $i + 1 < $length && false !== strpos( " \t\r\n\f", $css[ $i + 1 ] ) ) { $i++; }
        } else {
            $out .= $char;
        }
    }
    return trim( $out );
}

/** Only adjacent copies are redundant: A / B / A can intentionally override B. */
function aiwp_join_css( $parts ) {
    $result = array();
    $previous = null;
    foreach ( $parts as $part ) {
        $compact = aiwp_compact_css( $part );
        if ( '' === $compact ) { continue; }
        if ( $compact !== $previous ) { $result[] = $part; }
        $previous = $compact;
    }
    return implode( "\n", $result );
}

function aiwp_frontend_css() {
    $parts = array( aiwp_global_css() );
    foreach ( aiwp_frontend_documents() as $document ) {
        $parts[] = $document['css'];
    }
    return aiwp_join_css( $parts );
}

/** Keep the document's path and query: relative CSS URLs and WP previews keep working. */
function aiwp_styles_url() {
    $origin = preg_replace( '~^(https?://[^/]+).*$~', '$1', home_url( '/' ) );
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    $uri = '/' . ltrim( $uri, '/' );
    return add_query_arg( array(
        'aiwp_styles' => '1',
        'aiwp_css_ver' => hash( 'sha256', AIWP_VERSION . aiwp_frontend_css() ),
    ), $origin . $uri );
}

/** Run after WP resolves the original page and its permissions, before redirects/output. */
add_action( 'template_redirect', function () {
    if ( ! isset( $_GET['aiwp_styles'] ) ) { return; }
    header( 'Content-Type: text/css; charset=UTF-8' );
    header( 'X-Content-Type-Options: nosniff' );
    if ( ! is_string( $_GET['aiwp_styles'] ) || '1' !== $_GET['aiwp_styles'] || is_404() ) {
        status_header( 404 );
        nocache_headers();
        exit;
    }
    $css = aiwp_compact_css( aiwp_frontend_css() );
    $etag = '"' . hash( 'sha256', AIWP_VERSION . $css ) . '"';
    // Never let a shared cache retain privileged previews or password-unlocked styles.
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
        echo $css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS response, never HTML.
    }
    exit;
}, 0 );
