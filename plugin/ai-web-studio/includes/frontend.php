<?php
defined( 'ABSPATH' ) || exit;

function aiwp_is_code_page( $post_id = 0 ) {
    $post_id = $post_id ?: get_queried_object_id();
    return 'page' === get_post_type( $post_id ) && (bool) aiwp_get_meta( $post_id, '_aiwp_enabled' );
}

function aiwp_page_hidden( $kind ) {
    return in_array( $kind, array( 'header', 'footer' ), true ) && is_page() && aiwp_is_code_page() && (bool) aiwp_get_meta( get_queried_object_id(), '_aiwp_hide_' . $kind );
}

function aiwp_part_document( $kind ) {
    $id = aiwp_get_part_id( $kind );
    if ( ! $id || 'publish' !== get_post_status( $id ) ) {
        return null;
    }
    $document = aiwp_get_document( $id );
    return $document['enabled'] && '' !== trim( $document['html'] ) ? $document : null;
}

function aiwp_render_part( $kind ) {
    $document = aiwp_part_document( $kind );
    if ( ! $document || aiwp_page_hidden( $kind ) ) {
        return false;
    }
    // Deliberately raw: only administrators with unfiltered_html can save this code.
    echo '<div class="aiwp-' . esc_attr( $kind ) . '">' . aiwp_render_dynamic_html( $document['html'] ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    return true;
}

add_filter( 'the_content', function ( $content ) {
    if ( is_admin() || ! is_page() || ! is_main_query() || ! in_the_loop() || ! aiwp_is_code_page( get_the_ID() ) || post_password_required() ) {
        return $content;
    }
    $document = aiwp_get_document( get_the_ID() );
    return '<div class="aiwp-content">' . aiwp_render_dynamic_html( $document['html'] ) . '</div>';
}, 99 );

function aiwp_global_css() {
    $settings = aiwp_get_settings();
    $font = aiwp_font_details( $settings['font'] )['stack'];
    return ':root{--aiwp-accent:' . $settings['accent'] . ';--aiwp-width:' . $settings['width'] . 'px;--aiwp-font:' . $font . ';}.aiwp-content,.aiwp-header,.aiwp-footer{font-family:var(--aiwp-font);}' . "\n" . $settings['css'];
}

add_action( 'wp_enqueue_scripts', function () {
    $font = aiwp_font_details( aiwp_get_settings()['font'] );
    $dependencies = array();
    if ( $font['url'] ) {
        wp_enqueue_style( 'aiwp-google-font', $font['url'], array(), null );
        $dependencies[] = 'aiwp-google-font';
    }
    wp_enqueue_style( 'aiwp-runtime', AIWP_URL . 'assets/frontend.css', $dependencies, AIWP_VERSION );
    wp_add_inline_style( 'aiwp-runtime', aiwp_global_css() );
    $documents = array();
    // Parts are consumed only by a compatible theme. Avoid leaking their code on other themes.
    if ( current_theme_supports( 'ai-web-parts' ) ) {
        foreach ( array( 'header', 'footer' ) as $kind ) {
            $document = aiwp_part_document( $kind );
            if ( $document && ! aiwp_page_hidden( $kind ) ) {
                $documents[ $kind ] = $document;
            }
        }
    }
    if ( is_page() && aiwp_is_code_page() && ! post_password_required( get_queried_object_id() ) ) {
        $documents['page'] = aiwp_get_document( get_queried_object_id() );
    }
    foreach ( $documents as $kind => $document ) {
        if ( '' !== trim( $document['css'] ) ) {
            wp_add_inline_style( 'aiwp-runtime', "/* AI Web: " . $kind . " */\n" . $document['css'] );
        }
        if ( '' !== trim( $document['js'] ) ) {
            // Separate handles ensure one syntax error does not stop other fragments from parsing.
            $handle = 'aiwp-' . $kind;
            wp_register_script( $handle, false, array(), AIWP_VERSION, true );
            wp_enqueue_script( $handle );
            wp_add_inline_script( $handle, "(function(){\n" . $document['js'] . "\n})();" );
        }
    }
}, 30 );
