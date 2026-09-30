<?php
defined( 'ABSPATH' ) || exit;

function aiwp_consent_categories() {
    return array( 'analytics' => __( 'Analytics', 'ai-web-studio' ), 'marketing' => 'Marketing' );
}

function aiwp_consent_settings() {
    $stored = get_option( 'aiwp_consent_settings', array() );
    $stored = is_array( $stored ) ? $stored : array();
    $result = array( 'policy' => isset( $stored['policy'] ) && is_string( $stored['policy'] ) ? $stored['policy'] : '' );
    $result['external'] = ! array_key_exists( 'external', $stored ) || ! empty( $stored['external'] );
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

/** Called only when rendering a control into the actual page. */
function aiwp_render_cookie_settings_button() {
    if ( aiwp_consent_settings()['external'] ) { return ''; }
    $GLOBALS['aiwp_consent_control_rendered'] = true;
    return __( '<button type="button" class="aiwp-cookie-settings" data-aiwp-consent-open aria-controls="aiwp-consent-panel" aria-expanded="false" hidden>Cookie settings</button>', 'ai-web-studio' );
}

function aiwp_consent_active( $settings, $key ) {
    return empty( $settings['external'] ) && ! empty( $settings['policy'] ) && ! empty( $settings[ $key ]['enabled'] ) && '' !== trim( $settings[ $key ]['description'] ) && '' !== trim( $settings[ $key ]['js'] );
}

function aiwp_consent_names( $text ) {
    return array_values( array_unique( preg_split( '/[\s,]+/', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) ) );
}

function aiwp_sanitize_consent( $input ) {
    $old = aiwp_consent_settings();
    $error = __( 'Enter valid privacy settings. An enabled category needs a service description, JavaScript and a link to privacy information.', 'ai-web-studio' );
    if ( ! aiwp_can_edit_code() || ! is_array( $input ) ) {
        add_settings_error( 'aiwp_consent_settings', 'permission', __( 'Only an administrator with permission to insert code may change these settings.', 'ai-web-studio' ) );
        return $old;
    }
    $external = isset( $input['external'] ) && in_array( $input['external'], array( '1', true ), true );
    $revision = $old['revision'] + ( $external !== $old['external'] ? 1 : 0 );
    // Switching off must work even with incomplete old service settings.
    // Keep their code for a possible return; never migrate it to another plugin.
    if ( $external ) {
        $old['external'] = true;
        $old['revision'] = $revision;
        return $old;
    }
    if ( ! isset( $input['policy'] ) || ! is_string( $input['policy'] ) ) { $input['policy'] = ''; }
    $result = array( 'policy' => esc_url_raw( trim( $input['policy'] ), array( 'http', 'https' ) ) );
    $result['external'] = false;
    $result['revision'] = $revision;
    $valid = '' === trim( $input['policy'] ) || ( $result['policy'] && wp_parse_url( $result['policy'], PHP_URL_HOST ) );
    foreach ( aiwp_consent_categories() as $key => $label ) {
        $item = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array();
        foreach ( array( 'description', 'js', 'cookies', 'storage' ) as $field ) {
            if ( ! isset( $item[ $field ] ) || ! is_string( $item[ $field ] ) ) { $valid = false; $item[ $field ] = ''; }
        }
        $code = aiwp_validate_document( array( 'js' => $item['js'] ) );
        if ( is_wp_error( $code ) ) { $valid = false; }
        $result[ $key ] = array(
            'enabled' => isset( $item['enabled'] ) && in_array( $item['enabled'], array( '1', true ), true ),
            'description' => sanitize_textarea_field( $item['description'] ),
            'js' => is_wp_error( $code ) ? '' : $code['js'],
            'cookies' => implode( "\n", aiwp_consent_names( $item['cookies'] ) ),
            'storage' => implode( "\n", aiwp_consent_names( $item['storage'] ) ),
        );
        foreach ( array( 'cookies', 'storage' ) as $field ) {
            foreach ( aiwp_consent_names( $result[ $key ][ $field ] ) as $name ) {
                if ( ! preg_match( 'cookies' === $field ? '/^[a-zA-Z0-9_.-]+\*?$/' : '/^[a-zA-Z0-9_.:-]+$/', $name ) || preg_match( '/^(?:aiwp|wordpress|wp-settings|PHPSESSID)/i', $name ) ) {
                    $valid = false;
                    $error = __( 'The cookie or storage list contains an invalid or protected name. Cookie names only allow a wildcard at the end.', 'ai-web-studio' );
                }
            }
        }
        if ( $result[ $key ]['enabled'] && ! aiwp_consent_active( $result, $key ) ) { $valid = false; }
    }
    if ( ! $valid ) { add_settings_error( 'aiwp_consent_settings', 'invalid', $error ); return $old; }
    return $result;
}

add_action( 'admin_init', function () {
    register_setting( 'aiwp_consent', 'aiwp_consent_settings', array( 'type' => 'array', 'sanitize_callback' => 'aiwp_sanitize_consent' ) );
} );
add_action( 'admin_menu', function () {
    add_submenu_page( 'aiwp', __( 'Privacy and cookies', 'ai-web-studio' ), __( 'Privacy and cookies', 'ai-web-studio' ), 'manage_options', 'aiwp-consent', 'aiwp_consent_admin' );
} );

function aiwp_consent_admin() {
    if ( ! aiwp_can_edit_code() ) { wp_die( __( 'These settings require an administrator with permission to insert code.', 'ai-web-studio' ) ); }
    $settings = aiwp_consent_settings();
    ?>
    <div class="wrap"><h1><?php echo esc_html__( 'Privacy and cookies', 'ai-web-studio' ); ?></h1>
    <p><?php echo esc_html__( 'With built-in consent management, analytics and marketing are off initially. Our banner appears automatically only after you enable a fully configured category, and Cookie settings remain available at the bottom of the website. Use the switch below for another consent manager.', 'ai-web-studio' ); ?></p>
    <p><?php echo esc_html__( 'When using built-in consent management, place tracking code only here. This feature does not automatically block code inserted into page HTML/JS, another plugin or external content. Verify each service\'s cookies, storage and actual network requests.', 'ai-web-studio' ); ?></p>
    <?php settings_errors( 'aiwp_consent_settings' ); ?>
    <form method="post" action="options.php">
    <?php settings_fields( 'aiwp_consent' ); ?>
    <p><?php echo esc_html__( 'To place the button inside your footer HTML, insert', 'ai-web-studio' ); ?> <code>[aiwp_cookie_settings]</code><?php echo esc_html__( '. The button uses the class', 'ai-web-studio' ); ?> <code>aiwp-cookie-settings</code><?php echo esc_html__( '. If no control is rendered on the page, a fallback appears at the bottom of the website. In external mode the marker produces no output; the external plugin supplies the controls.', 'ai-web-studio' ); ?></p>
    <p><label for="aiwp-consent-external"><input id="aiwp-consent-external" type="checkbox" name="aiwp_consent_settings[external]" value="1" <?php checked( $settings['external'] ); ?>> <strong><?php echo esc_html__( 'Consent is managed by an external plugin (such as Complianz)', 'ai-web-studio' ); ?></strong></label></p>
    <p><?php echo esc_html__( 'Saving disables our banner, footer button and link, related CSS/JS and execution of the tracking scripts saved here. Configure the external plugin separately, including tracking and consent controls. Scripts and consent records are not transferred. Clear the website cache and check in a new private window.', 'ai-web-studio' ); ?></p>
    <?php if ( $settings['external'] ) : ?><p><strong><?php echo esc_html__( 'External consent management is enabled.', 'ai-web-studio' ); ?></strong> <?php echo esc_html__( 'The values below are preserved but not used or changed when saving in this mode. To return, clear the checkbox and save; visitors will need to make a new choice. Disable consent management in the external plugin before returning.', 'ai-web-studio' ); ?></p><?php endif; ?>
    <p><label for="aiwp-consent-policy"><strong><?php echo esc_html__( 'URL of your privacy and cookie information page', 'ai-web-studio' ); ?></strong></label><br>
    <input class="large-text" id="aiwp-consent-policy" type="url" name="aiwp_consent_settings[policy]" value="<?php echo esc_attr( $settings['policy'] ); ?>"></p>
    <p><?php echo esc_html__( 'Identify the operator, service providers, purposes, retention periods, any data transfers and visitors\' rights. The information must reflect how the website actually works.', 'ai-web-studio' ); ?></p>
    <?php foreach ( aiwp_consent_categories() as $key => $label ) : ?>
        <h2><?php echo esc_html( $label ); ?></h2>
        <p><label><input type="checkbox" name="aiwp_consent_settings[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $settings[ $key ]['enabled'] ); ?>> <?php echo esc_html__( 'Enable this category after the visitor consents', 'ai-web-studio' ); ?></label></p>
        <?php foreach ( array( 'description' => __( 'Visitor description: provider, purpose, cookies used and retention periods', 'ai-web-studio' ), 'js' => __( 'Service startup JavaScript (without script tags)', 'ai-web-studio' ), 'cookies' => __( 'Cookies to remove on rejection — one name per line, e.g. _ga and _ga_*', 'ai-web-studio' ), 'storage' => __( 'localStorage/sessionStorage keys to remove — exact names, one per line', 'ai-web-studio' ) ) as $field => $title ) : ?>
        <p><label for="aiwp-consent-<?php echo esc_attr( $key . '-' . $field ); ?>"><?php echo esc_html( $title ); ?></label><br>
        <textarea class="large-text <?php echo 'js' === $field ? 'code' : ''; ?>" id="aiwp-consent-<?php echo esc_attr( $key . '-' . $field ); ?>" name="aiwp_consent_settings[<?php echo esc_attr( $key ); ?>][<?php echo esc_attr( $field ); ?>]" rows="<?php echo 'js' === $field ? 8 : 3; ?>"><?php echo esc_textarea( $settings[ $key ][ $field ] ); ?></textarea></p>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <p><?php echo esc_html__( 'The visitor\'s choice is stored for 180 days in a necessary cookie without a user ID. Changing these settings requires a new choice. Withdrawing consent reloads the page without rejected scripts. This plugin cannot delete third-party or HttpOnly cookies; verify how each service stops tracking.', 'ai-web-studio' ); ?></p>
    <?php submit_button( __( 'Save privacy and cookies', 'ai-web-studio' ) ); ?>
    </form></div>
    <?php
}

/** Cookie is a visitor preference, not an authorization token for private data. */
function aiwp_consent_allows( $settings, $key ) {
    if ( ! empty( $settings['external'] ) ) { return false; }
    $name = aiwp_consent_cookie_name();
    if ( ! isset( $_COOKIE[ $name ] ) || ! is_string( $_COOKIE[ $name ] ) ) { return false; }
    $record = json_decode( wp_unslash( $_COOKIE[ $name ] ), true );
    return is_array( $record ) && isset( $record['v'], $record['t'], $record[ $key ] )
        && aiwp_consent_version( $settings ) === $record['v'] && is_int( $record['t'] )
        && $record['t'] <= time() + 60 && $record['t'] > time() - 180 * DAY_IN_SECONDS && true === $record[ $key ];
}

add_action( 'init', function () {
    if ( isset( $_GET['aiwp_consent_script'] ) && ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
} );
add_action( 'template_redirect', function () {
    if ( ! isset( $_GET['aiwp_consent_script'] ) ) { return; }
    header( 'Content-Type: application/javascript; charset=UTF-8' );
    header( 'X-Content-Type-Options: nosniff' );
    nocache_headers();
    $key = $_GET['aiwp_consent_script'];
    $settings = aiwp_consent_settings();
    if ( ! is_string( $key ) || ! isset( aiwp_consent_categories()[ $key ] ) || ! aiwp_consent_active( $settings, $key ) ) { status_header( 404 ); exit; }
    if ( ! aiwp_consent_allows( $settings, $key ) ) { status_header( 403 ); exit; }
    status_header( 200 );
    if ( 'HEAD' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
        echo "(function(){\n" . $settings[ $key ]['js'] . "\n})();"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JS-only response, trusted administrator code.
    }
    exit;
}, -20 );

add_action( 'wp_enqueue_scripts', function () {
    $settings = aiwp_consent_settings();
    if ( $settings['external'] ) { return; }
    $config = array( 'name' => aiwp_consent_cookie_name(), 'version' => aiwp_consent_version( $settings ), 'path' => wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/', 'categories' => array() );
    foreach ( aiwp_consent_categories() as $key => $label ) {
        $config['categories'][ $key ] = array( 'active' => aiwp_consent_active( $settings, $key ), 'cookies' => aiwp_consent_names( $settings[ $key ]['cookies'] ), 'storage' => aiwp_consent_names( $settings[ $key ]['storage'] ), 'url' => add_query_arg( 'aiwp_consent_script', $key, home_url( '/' ) ) );
    }
    wp_enqueue_style( 'aiwp-consent', AIWP_URL . 'assets/consent.css', array(), AIWP_VERSION );
    wp_enqueue_script( 'aiwp-consent', AIWP_URL . 'assets/consent.js', array(), AIWP_VERSION, true );
    wp_localize_script( 'aiwp-consent', 'aiwpConsent', $config );
} );

add_action( 'wp_footer', function () {
    $settings = aiwp_consent_settings();
    if ( $settings['external'] ) { return; }
    $active = array_filter( array_keys( aiwp_consent_categories() ), function ( $key ) use ( $settings ) { return aiwp_consent_active( $settings, $key ); } );
    ?>
    <?php if ( empty( $GLOBALS['aiwp_consent_control_rendered'] ) ) : ?>
    <div class="aiwp-consent-footer"><?php echo aiwp_render_cookie_settings_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed trusted markup. ?><?php if ( $settings['policy'] ) : ?> <a href="<?php echo esc_url( $settings['policy'] ); ?>"><?php echo esc_html__( 'Privacy and cookies', 'ai-web-studio' ); ?></a><?php endif; ?><noscript><?php echo esc_html__( 'Optional services managed by this plugin are off without JavaScript.', 'ai-web-studio' ); ?></noscript></div>
    <?php endif; ?>
    <section id="aiwp-consent-panel" lang="<?php echo esc_attr( WSP_Languages::language( 'public' ) ); ?>" aria-labelledby="aiwp-consent-heading" hidden>
        <h2 id="aiwp-consent-heading"><?php echo esc_html__( 'Privacy and cookies', 'ai-web-studio' ); ?></h2>
        <p><?php echo $active ? __( 'We only start optional services with your consent. You can reject them or choose individual purposes. Change your choice at any time through Cookie settings at the bottom of the website.', 'ai-web-studio' ) : __( 'Optional services managed by this plugin are not enabled.', 'ai-web-studio' ); ?></p>
        <?php if ( $settings['policy'] ) : ?><p><a href="<?php echo esc_url( $settings['policy'] ); ?>"><?php echo esc_html__( 'Privacy and cookie information', 'ai-web-studio' ); ?></a></p><?php endif; ?>
        <div class="aiwp-consent-actions">
            <?php if ( $active ) : ?>
            <button type="button" data-aiwp-consent-action="accept"><?php echo esc_html__( 'Accept all', 'ai-web-studio' ); ?></button>
            <button type="button" data-aiwp-consent-action="reject"><?php echo esc_html__( 'Reject optional', 'ai-web-studio' ); ?></button>
            <button type="button" data-aiwp-consent-action="settings" aria-expanded="false" aria-controls="aiwp-consent-details"><?php echo esc_html__( 'Settings', 'ai-web-studio' ); ?></button>
            <?php endif; ?>
            <button type="button" data-aiwp-consent-action="close"><?php echo esc_html__( 'Close', 'ai-web-studio' ); ?></button>
        </div>
        <div id="aiwp-consent-details" hidden>
            <p><strong><?php echo esc_html__( 'Necessary:', 'ai-web-studio' ); ?></strong> <?php echo esc_html__( 'remembering your choice (cookie', 'ai-web-studio' ); ?> <?php echo esc_html( aiwp_consent_cookie_name() ); ?><?php echo esc_html__( ', 180 days). Other necessary services are described on the privacy information page.', 'ai-web-studio' ); ?></p>
            <?php foreach ( $active as $key ) : ?>
            <label><input type="checkbox" data-aiwp-consent-category="<?php echo esc_attr( $key ); ?>"> <?php echo esc_html( aiwp_consent_categories()[ $key ] ); ?></label>
            <p class="aiwp-consent-description"><?php echo esc_html( $settings[ $key ]['description'] ); ?></p>
            <?php endforeach; ?>
            <?php if ( $active ) : ?><button type="button" data-aiwp-consent-action="save"><?php echo esc_html__( 'Save selection', 'ai-web-studio' ); ?></button><?php endif; ?>
        </div>
        <p data-aiwp-consent-status role="status" aria-live="polite"></p>
    </section>
    <?php
}, 5 );
