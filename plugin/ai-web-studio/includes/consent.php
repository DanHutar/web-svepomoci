<?php
defined( 'ABSPATH' ) || exit;

function aiwp_consent_categories() {
    return array( 'analytics' => 'Analytika', 'marketing' => 'Marketing' );
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
    return '<button type="button" class="aiwp-cookie-settings" data-aiwp-consent-open aria-controls="aiwp-consent-panel" aria-expanded="false" hidden>Nastavení cookies</button>';
}

function aiwp_consent_active( $settings, $key ) {
    return empty( $settings['external'] ) && ! empty( $settings['policy'] ) && ! empty( $settings[ $key ]['enabled'] ) && '' !== trim( $settings[ $key ]['description'] ) && '' !== trim( $settings[ $key ]['js'] );
}

function aiwp_consent_names( $text ) {
    return array_values( array_unique( preg_split( '/[\s,]+/', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) ) );
}

function aiwp_sanitize_consent( $input ) {
    $old = aiwp_consent_settings();
    $error = 'Vyplňte platné nastavení soukromí. Zapnutá kategorie potřebuje popis služby, JavaScript a odkaz na informace o soukromí.';
    if ( ! aiwp_can_edit_code() || ! is_array( $input ) ) {
        add_settings_error( 'aiwp_consent_settings', 'permission', 'Nastavení může měnit pouze správce s oprávněním vkládat kód.' );
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
                    $error = 'Seznam cookies nebo úložiště obsahuje neplatný nebo chráněný název. U cookies je povolena hvězdička pouze na konci názvu.';
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
    add_submenu_page( 'aiwp', 'Soukromí a cookies', 'Soukromí a cookies', 'manage_options', 'aiwp-consent', 'aiwp_consent_admin' );
} );

function aiwp_consent_admin() {
    if ( ! aiwp_can_edit_code() ) { wp_die( 'Toto nastavení upravuje správce s oprávněním vkládat kód.' ); }
    $settings = aiwp_consent_settings();
    ?>
    <div class="wrap"><h1>Soukromí a cookies</h1>
    <p>Při vlastní správě jsou měření a marketing ve výchozím stavu vypnuté. Naše lišta se automaticky zobrazí až po zapnutí alespoň jedné úplně vyplněné kategorie a Nastavení cookies zůstává dostupné na konci webu. Pro jiného správce souhlasu použijte přepínač níže.</p>
    <p>Při použití naší správy souhlasu vkládejte sledovací kód pouze sem. Kód vložený do HTML/JS stránky, jiného pluginu nebo externí obsah tato funkce automaticky neblokuje. Pro každou službu ověřte cookies, úložiště a skutečné síťové požadavky.</p>
    <?php settings_errors( 'aiwp_consent_settings' ); ?>
    <form method="post" action="options.php">
    <?php settings_fields( 'aiwp_consent' ); ?>
    <p>Pro umístění tlačítka přímo do HTML patičky vložte <code>[aiwp_cookie_settings]</code>. Tlačítko používá třídu <code>aiwp-cookie-settings</code>. Pokud není vykreslené na stránce, zobrazíme záložní ovládání na konci webu. V externím režimu se značka nezobrazí; ovládání poskytuje externí plugin.</p>
    <p><label for="aiwp-consent-external"><input id="aiwp-consent-external" type="checkbox" name="aiwp_consent_settings[external]" value="1" <?php checked( $settings['external'] ); ?>> <strong>Souhlas spravuje externí plugin (například Complianz)</strong></label></p>
    <p>Po uložení vypneme naši lištu, tlačítko i odkaz v patičce, související CSS/JS a spouštění zde uložených měřicích skriptů. Externí plugin musíte samostatně nastavit, včetně měření a možnosti změnit souhlas. Skripty ani souhlasy se do něj nepřenášejí. Vymažte cache webu a ověřte výsledek v novém anonymním okně.</p>
    <?php if ( $settings['external'] ) : ?><p><strong>Externí správa je zapnutá.</strong> Níže uvedené hodnoty jsou uchované, ale nepoužívají se a při uložení v tomto režimu se nemění. Pro návrat zrušte zaškrtnutí a uložte; návštěvníci potom musí zvolit souhlas znovu. Před návratem vypněte správu souhlasu v externím pluginu.</p><?php endif; ?>
    <p><label for="aiwp-consent-policy"><strong>Adresa stránky s informacemi o soukromí a cookies</strong></label><br>
    <input class="large-text" id="aiwp-consent-policy" type="url" name="aiwp_consent_settings[policy]" value="<?php echo esc_attr( $settings['policy'] ); ?>"></p>
    <p>Uveďte provozovatele, poskytovatele služeb, účely, dobu uchování, případné předávání údajů a práva návštěvníka. Text musí odpovídat skutečnému používání webu.</p>
    <?php foreach ( aiwp_consent_categories() as $key => $label ) : ?>
        <h2><?php echo esc_html( $label ); ?></h2>
        <p><label><input type="checkbox" name="aiwp_consent_settings[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $settings[ $key ]['enabled'] ); ?>> Zapnout tuto kategorii po souhlasu návštěvníka</label></p>
        <?php foreach ( array( 'description' => 'Popis pro návštěvníky: poskytovatel, účel, používané cookies a doby uložení', 'js' => 'Spouštěcí JavaScript služby (bez značek script)', 'cookies' => 'Cookies k odstranění při odmítnutí — jeden název na řádek, např. _ga a _ga_*', 'storage' => 'Klíče localStorage/sessionStorage k odstranění — přesné názvy, jeden na řádek' ) as $field => $title ) : ?>
        <p><label for="aiwp-consent-<?php echo esc_attr( $key . '-' . $field ); ?>"><?php echo esc_html( $title ); ?></label><br>
        <textarea class="large-text <?php echo 'js' === $field ? 'code' : ''; ?>" id="aiwp-consent-<?php echo esc_attr( $key . '-' . $field ); ?>" name="aiwp_consent_settings[<?php echo esc_attr( $key ); ?>][<?php echo esc_attr( $field ); ?>]" rows="<?php echo 'js' === $field ? 8 : 3; ?>"><?php echo esc_textarea( $settings[ $key ][ $field ] ); ?></textarea></p>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <p>Volba návštěvníka se uchovává 180 dní v nezbytné cookie bez uživatelského ID. Změna tohoto nastavení vyžádá novou volbu. Odvolání souhlasu obnoví stránku bez odmítnutých skriptů. Cookies třetích stran nebo HttpOnly nemůže tento plugin smazat; u konkrétní služby ověřte její způsob ukončení měření.</p>
    <?php submit_button( 'Uložit soukromí a cookies' ); ?>
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
    <div class="aiwp-consent-footer"><?php echo aiwp_render_cookie_settings_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed trusted markup. ?><?php if ( $settings['policy'] ) : ?> <a href="<?php echo esc_url( $settings['policy'] ); ?>">Soukromí a cookies</a><?php endif; ?><noscript>Volitelné služby spravované tímto pluginem jsou bez JavaScriptu vypnuté.</noscript></div>
    <?php endif; ?>
    <section id="aiwp-consent-panel" aria-labelledby="aiwp-consent-heading" hidden>
        <h2 id="aiwp-consent-heading">Soukromí a cookies</h2>
        <p><?php echo $active ? 'Volitelné služby spustíme pouze s vaším souhlasem. Můžete je odmítnout nebo si vybrat jednotlivé účely. Volbu kdykoliv změníte přes Nastavení cookies na konci webu.' : 'Volitelné služby spravované tímto pluginem nejsou zapnuté.'; ?></p>
        <?php if ( $settings['policy'] ) : ?><p><a href="<?php echo esc_url( $settings['policy'] ); ?>">Informace o soukromí a používaných cookies</a></p><?php endif; ?>
        <div class="aiwp-consent-actions">
            <?php if ( $active ) : ?>
            <button type="button" data-aiwp-consent-action="accept">Přijmout vše</button>
            <button type="button" data-aiwp-consent-action="reject">Odmítnout volitelné</button>
            <button type="button" data-aiwp-consent-action="settings" aria-expanded="false" aria-controls="aiwp-consent-details">Nastavit</button>
            <?php endif; ?>
            <button type="button" data-aiwp-consent-action="close">Zavřít</button>
        </div>
        <div id="aiwp-consent-details" hidden>
            <p><strong>Nezbytné:</strong> uchování vaší volby (cookie <?php echo esc_html( aiwp_consent_cookie_name() ); ?>, 180 dní). Další nezbytné služby popisuje stránka s informacemi o soukromí.</p>
            <?php foreach ( $active as $key ) : ?>
            <label><input type="checkbox" data-aiwp-consent-category="<?php echo esc_attr( $key ); ?>"> <?php echo esc_html( aiwp_consent_categories()[ $key ] ); ?></label>
            <p class="aiwp-consent-description"><?php echo esc_html( $settings[ $key ]['description'] ); ?></p>
            <?php endforeach; ?>
            <?php if ( $active ) : ?><button type="button" data-aiwp-consent-action="save">Uložit výběr</button><?php endif; ?>
        </div>
        <p data-aiwp-consent-status role="status" aria-live="polite"></p>
    </section>
    <?php
}, 5 );
