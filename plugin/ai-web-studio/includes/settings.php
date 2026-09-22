<?php
defined( 'ABSPATH' ) || exit;

function aiwp_get_settings() {
    $stored = get_option( 'aiwp_settings', array() );
    $settings = wp_parse_args( is_array( $stored ) ? $stored : array(), array( 'accent' => '#2563eb', 'font' => 'system', 'width' => 1200, 'css' => '' ) );
    $settings['accent'] = sanitize_hex_color( $settings['accent'] ) ?: '#2563eb';
    $settings['font'] = aiwp_valid_font( $settings['font'] ) ? $settings['font'] : 'system';
    $settings['width'] = max( 640, min( 1920, absint( $settings['width'] ) ) );
    $settings['css'] = is_string( $settings['css'] ) ? $settings['css'] : '';
    return $settings;
}

add_action( 'admin_menu', function () {
    add_menu_page( 'Web svépomocí', 'Web svépomocí', 'manage_options', 'aiwp', 'aiwp_overview', 'dashicons-editor-code', 25 );
    add_submenu_page( 'aiwp', 'Začínáme', 'Začínáme', 'manage_options', 'aiwp', 'aiwp_overview' );
    add_submenu_page( 'aiwp', 'Vzhled webu', 'Vzhled webu', 'manage_options', 'aiwp-settings', 'aiwp_settings_page' );
    foreach ( array( 'header' => array( 'Header', 'dashicons-align-wide' ), 'footer' => array( 'Footer', 'dashicons-align-full-width' ) ) as $kind => $label ) {
        $id = aiwp_get_part_id( $kind );
        $url = $id ? 'post.php?post=' . $id . '&action=edit' : 'admin.php?page=aiwp';
        add_menu_page( $label[0], $label[0], 'manage_options', $url, '', $label[1], 'header' === $kind ? 26 : 27 );
    }
} );

add_filter( 'parent_file', function ( $parent ) {
    global $post;
    if ( $post && 'aiwp_part' === $post->post_type ) {
        return 'post.php?post=' . $post->ID . '&action=edit';
    }
    return $parent;
} );

add_action( 'admin_init', function () {
    register_setting( 'aiwp_settings_group', 'aiwp_settings', array( 'type' => 'array', 'sanitize_callback' => 'aiwp_sanitize_settings' ) );
} );

function aiwp_sanitize_settings( $input ) {
    $old = aiwp_get_settings();
    if ( ! aiwp_can_edit_code() || ! is_array( $input ) ) {
        add_settings_error( 'aiwp_settings', 'aiwp_permission', 'Toto nastavení může změnit pouze správce s oprávněním vkládat kód.' );
        return $old;
    }
    foreach ( array( 'accent', 'font', 'width', 'css' ) as $key ) {
        if ( ! isset( $input[ $key ] ) || ! is_scalar( $input[ $key ] ) ) {
            add_settings_error( 'aiwp_settings', 'aiwp_input', 'Vyplňte všechna pole nastavení.' );
            return $old;
        }
    }
    $code = aiwp_validate_document( array( 'css' => $input['css'] ) );
    $color = sanitize_hex_color( $input['accent'] );
    if ( is_wp_error( $code ) || ! $color ) {
        add_settings_error( 'aiwp_settings', 'aiwp_settings_code', is_wp_error( $code ) ? $code->get_error_message() : 'Zadejte platnou barvu, například #2563eb.' );
        return $old;
    }
    if ( ! aiwp_valid_font( $input['font'] ) ) {
        add_settings_error( 'aiwp_settings', 'aiwp_font', 'Vyberte písmo z nabídky Google Fonts.' );
        return $old;
    }
    return array( 'accent' => $color, 'font' => $input['font'], 'width' => max( 640, min( 1920, absint( $input['width'] ) ) ), 'css' => $code['css'] );
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( false === strpos( $hook, 'aiwp' ) ) {
        return;
    }
    wp_enqueue_style( 'aiwp-settings', AIWP_URL . 'assets/settings.css', array(), AIWP_VERSION );
    if ( 'aiwp-settings' === ( isset( $_GET['page'] ) ? $_GET['page'] : '' ) && aiwp_can_edit_code() ) {
        wp_enqueue_script( 'aiwp-settings-prompt', AIWP_URL . 'assets/settings.js', array(), AIWP_VERSION, true );
        wp_localize_script( 'aiwp-settings-prompt', 'aiwpDesign', array( 'rules' => aiwp_typography_rules() ) );
    }
} );

function aiwp_overview() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap aiwp-dashboard">
        <div class="aiwp-dashboard-hero"><span>WEB SVÉPOMOCÍ · <?php echo esc_html( AIWP_VERSION ); ?></span><h1>Váš web začíná nápadem.</h1><p>Nechte AI připravit kód. Tady z něj uděláte stránky vlastního webu.</p><a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>">Vytvořit stránku</a></div>
        <?php if ( ! aiwp_can_edit_code() ) : ?><div class="notice notice-warning inline"><p>Vkládání kódu vyžaduje oprávnění správce a <code>unfiltered_html</code>. V síti WordPress Multisite ho má standardně jen správce sítě. Ostatní uživatelé mohou nadále používat běžný obsah WordPressu.</p></div><?php endif; ?>
        <?php if ( ! current_theme_supports( 'ai-web-parts' ) ) : ?><div class="notice notice-info inline"><p>Pro společnou hlavičku, patičku a stránky bez omezení šířky aktivujte šablonu <strong>web-svepomoci-sablona</strong> v nabídce Vzhled → Šablony.</p></div><?php endif; ?>
        <div class="aiwp-dashboard-grid">
            <section><span class="aiwp-step-number">01</span><h2>Připravte zadání pro AI</h2><p>Vyberte společný vzhled, novou stránku, Header, Footer nebo úpravu stránky. Popište svůj nápad a zkopírujte připravené zadání do své AI.</p><?php if ( aiwp_can_edit_code() ) : ?><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=aiwp-prompts' ) ); ?>">Zadání pro AI</a><?php endif; ?></section>
            <section><span class="aiwp-step-number">02</span><h2>Vložte tři části kódu</h2><p>HTML patří do záložky Obsah, CSS do Vzhledu a JavaScript do Chování. Zapněte „Zobrazovat obsah z AI editoru“.</p></section>
            <section><span class="aiwp-step-number">03</span><h2>Prohlédněte a publikujte</h2><p>Vyzkoušejte náhled pro počítač i mobil, vyplňte SEO a uložte koncept. Až bude stránka hotová, publikujte ji.</p></section>
        </div>
        <div class="aiwp-dashboard-grid aiwp-dashboard-secondary">
            <section><h2>Společná hlavička a patička</h2><p>Položky <strong>Header</strong> a <strong>Footer</strong> v levém menu fungují stejně jako editor stránky. Po zapnutí a uložení se jejich obsah použije na celém webu.</p><p>Dokud je nezapnete, šablona zobrazí základní hlavičku a patičku s názvem webu.</p></section>
            <section><h2>Vlastní styl webu</h2><p>Společné barvy, typ písma a CSS nastavíte na jednom místě. AI může využít proměnné <code>--aiwp-accent</code>, <code>--aiwp-font</code> a <code>--aiwp-width</code>.</p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=aiwp-settings' ) ); ?>">Vzhled webu</a></section>
            <section><h2>Nastavte úvodní stránku</h2><p>V nastavení čtení vyberte „Statická stránka“ a svou novou stránku nastavte jako úvodní.</p><a class="button" href="<?php echo esc_url( admin_url( 'options-reading.php' ) ); ?>">Nastavení čtení</a></section>
        </div>
        <?php if ( current_user_can( 'update_plugins' ) && current_user_can( 'update_themes' ) ) : ?>
        <p>Nové verze pluginu a šablony se načítají z GitHubu a instalují přes běžné aktualizace WordPressu.</p>
        <p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wsp_check_updates' ), 'wsp_check_updates' ) ); ?>">Zkontrolovat aktualizace</a> <a class="button" href="https://github.com/DanHutar/web-svepomoci/releases" target="_blank" rel="noopener">Přehled verzí na GitHubu</a></p>
        <?php endif; ?>
        <p class="aiwp-dashboard-footnote">web-svepomoci-plugin se nepřipojuje k žádné AI službě a nepotřebuje API klíč. Zadání a kód přenášíte kopírováním. Uložené verze stránek, hlavičky i patičky najdete v revizích WordPressu, pokud je instalace povoluje.</p>
    </div>
    <?php
}

function aiwp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $settings = aiwp_get_settings();
    ?>
    <div class="wrap aiwp-dashboard"><h1>Vzhled webu</h1><p>Společné nastavení pro všechny stránky. Barvu a šířku využijí části kódu, které odkazují na příslušné CSS proměnné.</p>
    <?php settings_errors(); ?>
    <?php if ( ! aiwp_can_edit_code() ) : ?><p>Pro změnu nastavení se přihlaste jako správce s oprávněním vkládat kód.</p></div><?php return; endif; ?>
    <form action="options.php" method="post" class="aiwp-settings-form">
        <?php settings_fields( 'aiwp_settings_group' ); ?>
        <table class="form-table" role="presentation"><tbody>
            <tr><th scope="row"><label for="aiwp-accent">Hlavní barva</label></th><td><input type="color" id="aiwp-accent" name="aiwp_settings[accent]" value="<?php echo esc_attr( $settings['accent'] ); ?>"><p class="description"><code>var(--aiwp-accent)</code></p></td></tr>
            <tr><th scope="row"><label for="aiwp-font">Písmo — Google Fonts</label></th><td><select id="aiwp-font" name="aiwp_settings[font]">
                <optgroup label="Veřejné Google Fonts">
                <?php foreach ( aiwp_font_catalog() as $key => $font ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" data-family="<?php echo esc_attr( $font['family'] ); ?>" <?php selected( $settings['font'], $key ); ?>><?php echo esc_html( $font['family'] ); ?></option>
                <?php endforeach; ?>
                </optgroup>
                <optgroup label="Původní písma bez stahování"><option value="system" data-family="Systémové bezpatkové písmo" <?php selected( $settings['font'], 'system' ); ?>>Systémové bezpatkové</option><option value="serif" data-family="Georgia" <?php selected( $settings['font'], 'serif' ); ?>>Georgia</option></optgroup>
                </select><p class="description">Výběr osmi veřejných Google Fonts, načítaných ze serverů Googlu bez API klíče. Písmo použijte přes <code>var(--aiwp-font)</code>. Vlastní font-family ve starém CSS má přednost.</p></td></tr>
            <tr><th scope="row"><label for="aiwp-width">Šířka obsahu</label></th><td><input type="number" id="aiwp-width" name="aiwp_settings[width]" min="640" max="1920" value="<?php echo esc_attr( $settings['width'] ); ?>"> px<p class="description"><code>max-width: var(--aiwp-width)</code> v CSS vaší stránky.</p></td></tr>
            <tr><th scope="row"><label for="aiwp-global-css">Společné CSS</label></th><td><textarea class="large-text code" rows="16" id="aiwp-global-css" name="aiwp_settings[css]" spellcheck="false"><?php echo esc_textarea( $settings['css'] ); ?></textarea><p class="description">Pouze CSS bez značek &lt;style&gt;. Změna se projeví na celém webu. Tato společná nastavení nemají historii revizí.</p></td></tr>
        </tbody></table>
        <h2>Zadání pro společný vzhled</h2>
        <p>Vyberte písmo a zkopírujte zadání do AI. Navrhne společné CSS včetně velikostí a výšek řádků pomocí clamp() pro H1–H6, běžný text a small. Výsledek vložte do Společného CSS a uložte vzhled webu. Samotná volba písma typografickou stupnici nepřepisuje.</p>
        <button type="button" class="button" data-aiwp-design-copy>Zkopírovat zadání pro společné CSS</button>
        <p data-aiwp-design-status role="status" aria-live="polite"></p>
        <details data-aiwp-design-fallback hidden><summary>Zadání pro ruční kopírování</summary><textarea class="large-text code" rows="12" readonly aria-label="Zadání pro společné CSS"></textarea></details>
        <?php submit_button( 'Uložit vzhled webu' ); ?>
    </form></div>
    <?php
}
