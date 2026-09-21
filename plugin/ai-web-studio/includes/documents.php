<?php
defined( 'ABSPATH' ) || exit;

/** Code execution is intentionally limited to trusted administrators. */
function aiwp_can_edit_code() {
    return current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' );
}

// Core autosave/restore writes revisioned meta without invoking its auth_callback.
add_filter( 'wp_post_revision_meta_keys', function ( $keys ) {
    if ( ! aiwp_can_edit_code() ) {
        $keys = array_values( array_filter( $keys, function ( $key ) { return 0 !== strpos( $key, '_aiwp_' ); } ) );
    }
    return $keys;
}, PHP_INT_MAX );

/** The standard Preview shows saved AI fields, never an incomplete native autosave. */
function aiwp_get_meta( $post_id, $key ) {
    $all = get_post_meta( $post_id );
    // Requesting all metadata bypasses core's per-key preview substitution.
    return isset( $all[ $key ][0] ) ? maybe_unserialize( $all[ $key ][0] ) : '';
}

function aiwp_document_defaults() {
    return array(
        'enabled' => false, 'html' => '', 'css' => '', 'js' => '',
        'hide_header' => false, 'hide_footer' => false,
        'seo_title' => '', 'seo_description' => '', 'seo_image' => '',
        'seo_canonical' => '', 'seo_noindex' => false,
    );
}

function aiwp_get_document( $post_id ) {
    $data = aiwp_document_defaults();
    foreach ( $data as $key => $default ) {
        $value = aiwp_get_meta( $post_id, '_aiwp_' . $key );
        $data[ $key ] = is_bool( $default ) ? (bool) $value : ( is_scalar( $value ) ? (string) $value : '' );
    }
    return $data;
}

function aiwp_register_documents() {
    register_post_type( 'aiwp_part', array(
        'labels' => array( 'name' => 'Části webu', 'singular_name' => 'Část webu', 'edit_item' => 'Upravit část webu' ),
        'public' => false, 'publicly_queryable' => false,
        'show_ui' => true, 'show_in_menu' => false, 'show_in_rest' => false,
        'exclude_from_search' => true, 'rewrite' => false, 'query_var' => false,
        'supports' => array( 'title', 'revisions' ),
        'capability_type' => 'post', 'map_meta_cap' => true,
        'capabilities' => array( 'create_posts' => 'do_not_allow' ),
    ) );
    foreach ( array( 'page', 'aiwp_part' ) as $type ) {
        foreach ( aiwp_document_defaults() as $key => $default ) {
            register_post_meta( $type, '_aiwp_' . $key, array(
                'type' => is_bool( $default ) ? 'boolean' : 'string',
                'single' => true, 'default' => $default,
                'show_in_rest' => false, 'revisions_enabled' => true,
                'auth_callback' => 'aiwp_can_edit_code',
            ) );
        }
    }
}

function aiwp_get_part_id( $kind ) {
    if ( ! in_array( $kind, array( 'header', 'footer' ), true ) ) {
        return 0;
    }
    $ids = get_option( 'aiwp_part_ids', array() );
    $id = isset( $ids[ $kind ] ) ? absint( $ids[ $kind ] ) : 0;
    $post = get_post( $id );
    return $post && 'aiwp_part' === $post->post_type && 'trash' !== $post->post_status ? $id : 0;
}

function aiwp_ensure_parts() {
    if ( ! aiwp_can_edit_code() ) {
        return;
    }
    $ids = array();
    foreach ( array( 'header' => 'Header – hlavička', 'footer' => 'Footer – patička' ) as $kind => $title ) {
        $ids[ $kind ] = aiwp_get_part_id( $kind );
        if ( ! $ids[ $kind ] ) {
            $id = wp_insert_post( array( 'post_type' => 'aiwp_part', 'post_status' => 'publish', 'post_title' => $title ), true );
            if ( ! is_wp_error( $id ) ) {
                $ids[ $kind ] = $id;
            }
        }
    }
    if ( $ids !== get_option( 'aiwp_part_ids', array() ) ) {
        update_option( 'aiwp_part_ids', $ids, false );
    }
}

/** Includes revision restore, which otherwise bypasses the custom save form. */
function aiwp_protect_code_documents( $caps, $cap, $user_id, $args ) {
    if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'read_post' ), true ) || empty( $args[0] ) ) {
        return $caps;
    }
    $post = get_post( $args[0] );
    if ( $post && 'revision' === $post->post_type ) {
        $post = get_post( $post->post_parent );
    }
    if ( ! $post ) {
        return $caps;
    }
    $protected = 'aiwp_part' === $post->post_type || ( 'read_post' !== $cap && (bool) aiwp_get_meta( $post->ID, '_aiwp_protected' ) );
    if ( 'page' === $post->post_type && 'read_post' !== $cap ) {
        // Also protect disabled code, so a revision cannot re-enable code for a lower role.
        foreach ( array( 'enabled', 'html', 'css', 'js' ) as $key ) {
            if ( get_post_meta( $post->ID, '_aiwp_' . $key, true ) ) {
                $protected = true;
                break;
            }
        }
    }
    if ( $protected && ( ! user_can( $user_id, 'manage_options' ) || ! user_can( $user_id, 'unfiltered_html' ) ) ) {
        $caps[] = 'do_not_allow';
    }
    return $caps;
}

/** Reject malformed submissions, preserving code verbatim instead of silently stripping it. */
function aiwp_validate_document( $input ) {
    if ( ! is_array( $input ) ) {
        return new WP_Error( 'aiwp_input', 'Neplatná data editoru.' );
    }
    $result = aiwp_document_defaults();
    foreach ( $result as $key => $default ) {
        $value = $input[ $key ] ?? $default;
        if ( ! is_scalar( $value ) ) {
            return new WP_Error( 'aiwp_input', 'Pole editoru musí obsahovat text.' );
        }
        $result[ $key ] = is_bool( $default ) ? ! empty( $value ) : (string) $value;
    }
    foreach ( array( 'html', 'css', 'js' ) as $key ) {
        if ( strlen( $result[ $key ] ) > 500000 ) {
            return new WP_Error( 'aiwp_size', 'Jedno pole může obsahovat nejvýše 500 kB kódu.' );
        }
        if ( false !== strpos( $result[ $key ], '<?' ) ) {
            return new WP_Error( 'aiwp_php', 'PHP ani otevírací značky <? do editoru nepatří. Vložte pouze HTML, CSS a JavaScript.' );
        }
        if ( preg_match( '/^\s*```/m', $result[ $key ] ) ) {
            return new WP_Error( 'aiwp_fences', 'Odstraňte značky ``` kolem kódu z odpovědi AI.' );
        }
        if ( preg_match( '~<\s*/?\s*(?:script|style)\b~i', $result[ $key ] ) ) {
            return new WP_Error( 'aiwp_tags', 'Značky <script> a <style> vynechte. CSS a JavaScript vložte do samostatných polí bez obalových značek.' );
        }
    }
    if ( preg_match( '~<\s*/?\s*(?:html|head|body|meta|title|link|base)\b|<!doctype\b~i', $result['html'] ) ) {
        return new WP_Error( 'aiwp_document', 'Do HTML vložte jen obsah stránky, bez <!doctype>, <html>, <head>, <body> a metadat. SEO vyplňte v samostatném bloku.' );
    }
    $result['seo_title'] = sanitize_text_field( $result['seo_title'] );
    $result['seo_description'] = sanitize_textarea_field( $result['seo_description'] );
    foreach ( array( 'seo_image', 'seo_canonical' ) as $key ) {
        $url = trim( $result[ $key ] );
        if ( '' !== $url ) {
            // Validate syntax only; SEO fields must not cause server-side DNS or HTTP requests.
            $parts = wp_parse_url( $url );
            if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
                return new WP_Error( 'aiwp_url', 'Obrázek a kanonická adresa musí být úplné adresy začínající https:// nebo http://.' );
            }
        }
        $result[ $key ] = esc_url_raw( $url, array( 'http', 'https' ) );
    }
    return $result;
}

function aiwp_submitted_document() {
    if ( ! isset( $_POST['aiwp'], $_POST['aiwp_nonce'] ) || ! is_string( $_POST['aiwp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aiwp_nonce'] ) ), 'aiwp_save' ) ) {
        return null;
    }
    if ( ! aiwp_can_edit_code() ) {
        return null;
    }
    return aiwp_validate_document( wp_unslash( $_POST['aiwp'] ) );
}

function aiwp_validate_post_submission( $data, $postarr ) {
    if ( in_array( $data['post_type'], array( 'page', 'aiwp_part' ), true ) && ! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
        $document = aiwp_submitted_document();
        if ( is_wp_error( $document ) ) {
            wp_die( esc_html( $document->get_error_message() ), 'Kód nebyl uložen', array( 'response' => 400, 'back_link' => true ) );
        }
    }
    return $data;
}

function aiwp_save_document( $post_id, $post, $update ) {
    if ( ! in_array( $post->post_type, array( 'page', 'aiwp_part' ), true ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    $data = aiwp_submitted_document();
    if ( null === $data || is_wp_error( $data ) ) {
        return;
    }
    if ( $data['enabled'] || $data['html'] || $data['css'] || $data['js'] ) {
        // This sentinel deliberately survives clearing fields and restoring old revisions.
        update_post_meta( $post_id, '_aiwp_protected', 1 );
    }
    foreach ( $data as $key => $value ) {
        update_post_meta( $post_id, '_aiwp_' . $key, is_string( $value ) ? wp_slash( $value ) : $value );
    }
    // WordPress >=6.4 stores registered revisioned meta after save_post via wp_after_insert_post.
}

add_filter( 'wp_get_revision_ui_diff', function ( $diffs, $from, $to ) {
    if ( ! $to || ! in_array( get_post_type( $to->post_parent ), array( 'page', 'aiwp_part' ), true ) || ! aiwp_can_edit_code() ) {
        return $diffs;
    }
    $labels = array( 'enabled' => 'AI obsah zapnutý', 'html' => 'HTML', 'css' => 'CSS', 'js' => 'JavaScript', 'hide_header' => 'Skrýt hlavičku', 'hide_footer' => 'Skrýt patičku', 'seo_title' => 'SEO titulek', 'seo_description' => 'Meta popis', 'seo_image' => 'Obrázek pro sdílení', 'seo_canonical' => 'Kanonická URL', 'seo_noindex' => 'Neindexovat' );
    foreach ( $labels as $key => $label ) {
        $before = $from ? (string) aiwp_get_meta( $from->ID, '_aiwp_' . $key ) : '';
        $after = (string) aiwp_get_meta( $to->ID, '_aiwp_' . $key );
        $diff = wp_text_diff( $before, $after, array( 'show_split_view' => true ) );
        if ( $diff ) {
            $diffs[] = array( 'id' => '_aiwp_' . $key, 'name' => $label, 'diff' => $diff );
        }
    }
    return $diffs;
}, 10, 3 );
