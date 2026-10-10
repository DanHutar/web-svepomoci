<?php
defined( 'ABSPATH' ) || exit;

/** Runtime policy: never rewrite post statuses, discussion defaults or stored comments. */
function aiwp_comments_disabled() {
    return '0' !== (string) get_option( 'aiwp_disable_comments', '1' );
}

function aiwp_sanitize_disable_comments( $value ) {
    if ( ! current_user_can( 'manage_options' ) || ! in_array( $value, array( '0', '1' ), true ) ) {
        return aiwp_comments_disabled() ? '1' : '0';
    }
    return $value;
}

add_action( 'admin_init', function () {
    register_setting( 'aiwp_discussion', 'aiwp_disable_comments', array(
        'type' => 'string', 'default' => '1', 'sanitize_callback' => 'aiwp_sanitize_disable_comments',
    ) );
} );
add_action( 'admin_menu', function () {
    add_submenu_page( 'aiwp', __( 'Comments', 'ai-web-studio' ), __( 'Comments', 'ai-web-studio' ), 'manage_options', 'aiwp-comments', 'aiwp_comments_admin' );
} );

function aiwp_comments_admin() {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You do not have permission to change these settings.', 'ai-web-studio' ), '', array( 'response' => 403 ) ); }
    ?>
    <div class="wrap"><h1><?php esc_html_e( 'Comments', 'ai-web-studio' ); ?></h1>
    <?php settings_errors(); ?>
    <form action="options.php" method="post">
        <?php settings_fields( 'aiwp_discussion' ); ?>
        <input type="hidden" name="aiwp_disable_comments" value="0">
        <p><label><input id="aiwp-disable-comments" type="checkbox" name="aiwp_disable_comments" value="1" <?php checked( aiwp_comments_disabled() ); ?>>
        <strong><?php esc_html_e( 'Disable comments across the website', 'ai-web-studio' ); ?></strong></label></p>
        <p><?php esc_html_e( 'Enabled by default, including for existing posts. Hides WordPress comments and forms and blocks new comments, pingbacks and trackbacks. Existing comments remain available in the administration and are not deleted. Uncheck to restore the original discussion settings of each post.', 'ai-web-studio' ); ?></p>
        <p><?php esc_html_e( 'Clear website and CDN caches after changing this setting. External comment services and custom plugin forms are not controlled by this option. Features such as product reviews may also depend on WordPress comments.', 'ai-web-studio' ); ?></p>
        <?php submit_button(); ?>
    </form></div>
    <?php
}

foreach ( array( 'comments_open', 'pings_open' ) as $hook ) {
    add_filter( $hook, function ( $open ) { return aiwp_comments_disabled() ? false : $open; }, PHP_INT_MAX );
}
add_filter( 'xmlrpc_methods', function ( $methods ) {
    if ( aiwp_comments_disabled() ) { unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] ); }
    return $methods;
}, PHP_INT_MAX );

function aiwp_comments_error() {
    return new WP_Error( 'aiwp_comments_disabled', __( 'Comments are disabled on this website.', 'ai-web-studio' ), array( 'status' => 403 ) );
}

// Also covers wp_new_comment callers that skip comments_open, such as admin replies.
add_filter( 'pre_comment_approved', function ( $approved, $data ) {
    $type = $data['comment_type'] ?? '';
    return aiwp_comments_disabled() && in_array( $type, array( '', 'comment', 'pingback', 'trackback' ), true ) ? aiwp_comments_error() : $approved;
}, PHP_INT_MAX, 2 );

// REST privileged users can otherwise bypass the core comments_open check.
add_filter( 'rest_pre_insert_comment', function ( $comment, $request ) {
    return aiwp_comments_disabled() && ! $request->get_param( 'id' ) ? aiwp_comments_error() : $comment;
}, PHP_INT_MAX, 2 );
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
    if ( aiwp_comments_disabled() && preg_match( '~^/wp/v2/comments(?:/\d+)?/?$~', $request->get_route() )
        && in_array( $request->get_method(), array( 'GET', 'HEAD' ), true )
        && ! current_user_can( 'moderate_comments' ) ) { return aiwp_comments_error(); }
    return $result;
}, PHP_INT_MAX, 3 );

add_filter( 'comments_template', function ( $template ) {
    return aiwp_comments_disabled() ? AIWP_DIR . 'includes/comments-empty.php' : $template;
}, PHP_INT_MAX );
add_filter( 'comments_array', function ( $comments ) { return aiwp_comments_disabled() && ! is_admin() ? array() : $comments; }, PHP_INT_MAX );
add_filter( 'get_comments_number', function ( $number ) { return aiwp_comments_disabled() && ! is_admin() ? 0 : $number; }, PHP_INT_MAX );
add_filter( 'render_block', function ( $content, $block ) {
    if ( aiwp_comments_disabled() && ! is_admin() && in_array( $block['blockName'] ?? '', array(
        'core/comments', 'core/comment-template', 'core/post-comments', 'core/post-comments-form',
        'core/comments-title', 'core/latest-comments', 'core/post-comments-link', 'core/post-comments-count',
    ), true ) ) { return ''; }
    return $content;
}, PHP_INT_MAX, 2 );
add_filter( 'widget_display_callback', function ( $instance, $widget ) {
    return aiwp_comments_disabled() && 'recent-comments' === $widget->id_base ? false : $instance;
}, PHP_INT_MAX, 2 );
add_filter( 'feed_links_show_comments_feed', function ( $show ) { return aiwp_comments_disabled() ? false : $show; } );
add_filter( 'feed_links_extra_show_post_comments_feed', function ( $show ) { return aiwp_comments_disabled() ? false : $show; } );
add_action( 'template_redirect', function () {
    if ( aiwp_comments_disabled() && is_comment_feed() ) {
        nocache_headers();
        wp_die( esc_html__( 'Comments are disabled on this website.', 'ai-web-studio' ), '', array( 'response' => 403 ) );
    }
}, -30 );
