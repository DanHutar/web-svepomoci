<?php
defined( 'ABSPATH' ) || exit;

/** APNG declares animation before its first IDAT chunk. Unknown PNGs stay untouched. */
function ai_web_is_static_png( $path ) {
    $stream = fopen( $path, 'rb' );
    if ( ! $stream ) { return false; }
    try {
        if ( "\x89PNG\r\n\x1a\n" !== fread( $stream, 8 ) ) { return false; }
        $size = filesize( $path );
        while ( ! feof( $stream ) ) {
            $header = fread( $stream, 8 );
            if ( 8 !== strlen( $header ) ) { return false; }
            $chunk = unpack( 'Nlength/a4type', $header );
            if ( $chunk['length'] > $size - ftell( $stream ) - 4 ) { return false; }
            if ( 'acTL' === $chunk['type'] ) { return false; }
            if ( 'IDAT' === $chunk['type'] ) { return true; }
            if ( 'IEND' === $chunk['type'] || 0 !== fseek( $stream, $chunk['length'] + 4, SEEK_CUR ) ) { return false; }
        }
        return false;
    } finally {
        fclose( $stream );
    }
}

/** Convert new uploads only; existing media and animated images are left alone. */
function ai_web_upload_to_webp( $upload ) {
    if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) || empty( $upload['url'] ) || empty( $upload['type'] ) || ! in_array( $upload['type'], array( 'image/jpeg', 'image/png' ), true ) ) {
        return $upload;
    }
    $source = $upload['file'];
    if ( ! is_file( $source ) || ! is_readable( $source ) || wp_get_image_mime( $source ) !== $upload['type'] ) { return $upload; }
    if ( 'image/png' === $upload['type'] && ! ai_web_is_static_png( $source ) ) { return $upload; }
    if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) { return $upload; }
    $editor = wp_get_image_editor( $source );
    if ( is_wp_error( $editor ) || ! $editor->supports_mime_type( 'image/webp' ) ) { return $upload; }
    // Apply camera orientation before the original JPEG metadata is discarded.
    if ( 'image/jpeg' === $upload['type'] && is_wp_error( $editor->maybe_exif_rotate() ) ) { return $upload; }
    $directory = dirname( $source );
    $name = wp_unique_filename( $directory, pathinfo( $source, PATHINFO_FILENAME ) . '.webp' );
    $destination = trailingslashit( $directory ) . $name;
    $saved = $editor->save( $destination, 'image/webp' );
    $image = is_file( $destination ) ? wp_getimagesize( $destination ) : false;
    if ( is_wp_error( $saved ) || empty( $saved['path'] ) || wp_normalize_path( $saved['path'] ) !== wp_normalize_path( $destination ) || ! $image || 'image/webp' !== ( $image['mime'] ?? '' ) || empty( $image[0] ) || empty( $image[1] ) ) {
        // Only remove the newly allocated output, never the uploaded original.
        if ( is_file( $destination ) ) { wp_delete_file( $destination ); }
        return $upload;
    }
    $upload['file'] = $destination;
    $upload['url'] = substr( $upload['url'], 0, strrpos( $upload['url'], '/' ) + 1 ) . rawurlencode( $name );
    $upload['type'] = 'image/webp';
    wp_delete_file( $source );
    return $upload;
}
add_filter( 'wp_handle_upload', 'ai_web_upload_to_webp' );
