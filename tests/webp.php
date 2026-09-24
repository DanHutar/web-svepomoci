<?php
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function ai_web_assert_image( $value, $message ) {
    if ( ! $value ) { throw new RuntimeException( $message ); }
}
ai_web_assert_image( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ), 'Test runtime needs WebP encoding support' );
$ids = array();
$paths = array();
$directory = wp_upload_dir()['path'];
wp_mkdir_p( $directory );
$source_paths = array();
$capture = function ( $upload ) use ( &$source_paths ) { $source_paths[] = $upload['file']; return $upload; };
add_filter( 'wp_handle_upload', $capture, 9 );
try {
    foreach ( array( 'png', 'jpg' ) as $extension ) {
        $image = imagecreatetruecolor( 400, 200 );
        imagealphablending( $image, false );
        imagesavealpha( $image, true );
        imagefill( $image, 0, 0, imagecolorallocatealpha( $image, 50, 100, 150, 'png' === $extension ? 127 : 0 ) );
        $temporary = wp_tempnam();
        $paths[] = $temporary;
        if ( 'png' === $extension ) { imagepng( $image, $temporary ); } else { imagejpeg( $image, $temporary ); }
        imagedestroy( $image );
        $collision = $directory . '/webp-fixture.webp';
        if ( ! file_exists( $collision ) ) { file_put_contents( $collision, 'existing file must survive' ); $paths[] = $collision; }
        $rotate = function () { return 6; };
        if ( 'jpg' === $extension ) { add_filter( 'wp_image_maybe_exif_rotate', $rotate ); }
        $id = media_handle_sideload( array( 'name' => 'webp-fixture.' . $extension, 'tmp_name' => $temporary ), 0 );
        remove_filter( 'wp_image_maybe_exif_rotate', $rotate );
        ai_web_assert_image( ! is_wp_error( $id ), 'Media upload failed' );
        $ids[] = $id;
        $file = get_attached_file( $id );
        ai_web_assert_image( 'image/webp' === get_post_mime_type( $id ) && 'image/webp' === wp_get_image_mime( $file ), 'Media MIME and actual WebP disagree' );
        ai_web_assert_image( '.webp' === substr( wp_get_attachment_url( $id ), -5 ), 'Media URL is not WebP' );
        ai_web_assert_image( ! file_exists( end( $source_paths ) ), 'Successfully converted original was not removed' );
        ai_web_assert_image( 'existing file must survive' === file_get_contents( $collision ), 'Existing destination was overwritten' );
        $meta = wp_get_attachment_metadata( $id );
        ai_web_assert_image( ! empty( $meta['sizes']['thumbnail'] ) && 'image/webp' === $meta['sizes']['thumbnail']['mime-type'], 'Thumbnail was not generated as WebP' );
        if ( 'png' === $extension ) {
            $decoded = imagecreatefromwebp( $file );
            ai_web_assert_image( 127 === ( ( imagecolorat( $decoded, 0, 0 ) >> 24 ) & 127 ), 'PNG transparency was lost' );
            imagedestroy( $decoded );
        } else {
            ai_web_assert_image( 200 === $meta['width'] && 400 === $meta['height'], 'JPEG orientation was not corrected' );
        }
    }
    $png = $directory . '/webp-fallback.png';
    $paths[] = $png;
    $image = imagecreatetruecolor( 2, 2 ); imagepng( $image, $png ); imagedestroy( $image );
    $original = array( 'file' => $png, 'url' => 'https://example.test/webp-fallback.png', 'type' => 'image/png' );
    $hash = hash_file( 'sha256', $png );
    $no_editor = function () { return array(); };
    add_filter( 'wp_image_editors', $no_editor );
    ai_web_assert_image( $original === ai_web_upload_to_webp( $original ) && $hash === hash_file( 'sha256', $png ), 'Unsupported server did not preserve original' );
    remove_filter( 'wp_image_editors', $no_editor );
    class AI_Web_Failed_Save extends WP_Image_Editor_GD {
        public function save( $destfilename = null, $mime_type = null ) {
            file_put_contents( $destfilename, 'incomplete webp' );
            return new WP_Error( 'test_write_failure' );
        }
    }
    $failed_editor = function () { return array( 'AI_Web_Failed_Save' ); };
    add_filter( 'wp_image_editors', $failed_editor );
    ai_web_assert_image( $original === ai_web_upload_to_webp( $original ) && $hash === hash_file( 'sha256', $png ), 'Save failure damaged original' );
    ai_web_assert_image( ! file_exists( $directory . '/webp-fallback.webp' ), 'Incomplete output was retained' );
    remove_filter( 'wp_image_editors', $failed_editor );
    $bytes = file_get_contents( $png );
    $animation = pack( 'N', 8 ) . 'acTL' . pack( 'NN', 2, 0 );
    $animation .= pack( 'N', crc32( substr( $animation, 4 ) ) );
    file_put_contents( $png, substr( $bytes, 0, 33 ) . $animation . substr( $bytes, 33 ) );
    ai_web_assert_image( $original === ai_web_upload_to_webp( $original ), 'Animated PNG was converted' );
    foreach ( array( 'image/gif', 'image/webp', 'image/svg+xml', 'application/pdf' ) as $mime ) {
        $other = array_merge( $original, array( 'type' => $mime ) );
        ai_web_assert_image( $other === ai_web_upload_to_webp( $other ), 'Unrelated format was changed' );
    }
    file_put_contents( $png, 'broken image' );
    ai_web_assert_image( $original === ai_web_upload_to_webp( $original ), 'Corrupt PNG was changed' );
    echo wp_json_encode( array( 'webp' => 'passed', 'checks' => 'JPEG/PNG media uploads, alpha, orientation, thumbnails, collisions, failure cleanup, animations and unsupported formats' ) );
} finally {
    remove_filter( 'wp_handle_upload', $capture, 9 );
    foreach ( $ids as $id ) { wp_delete_attachment( $id, true ); }
    foreach ( $paths as $path ) { if ( is_file( $path ) ) { wp_delete_file( $path ); } }
}
