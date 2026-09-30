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
    add_menu_page( __( 'Website Builder', 'ai-web-studio' ), __( 'Website Builder', 'ai-web-studio' ), 'manage_options', 'aiwp', 'aiwp_overview', 'dashicons-editor-code', 25 );
    add_submenu_page( 'aiwp', __( 'Getting started', 'ai-web-studio' ), __( 'Getting started', 'ai-web-studio' ), 'manage_options', 'aiwp', 'aiwp_overview' );
    add_submenu_page( 'aiwp', __( 'Website design', 'ai-web-studio' ), __( 'Website design', 'ai-web-studio' ), 'manage_options', 'aiwp-settings', 'aiwp_settings_page' );
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
        add_settings_error( 'aiwp_settings', 'aiwp_permission', __( 'Only an administrator with permission to insert code can change these settings.', 'ai-web-studio' ) );
        return $old;
    }
    foreach ( array( 'accent', 'font', 'width', 'css' ) as $key ) {
        if ( ! isset( $input[ $key ] ) || ! is_scalar( $input[ $key ] ) ) {
            add_settings_error( 'aiwp_settings', 'aiwp_input', __( 'Complete all settings fields.', 'ai-web-studio' ) );
            return $old;
        }
    }
    $code = aiwp_validate_document( array( 'css' => $input['css'] ) );
    $color = sanitize_hex_color( $input['accent'] );
    if ( is_wp_error( $code ) || ! $color ) {
        add_settings_error( 'aiwp_settings', 'aiwp_settings_code', is_wp_error( $code ) ? $code->get_error_message() : __( 'Enter a valid colour, for example #2563eb.', 'ai-web-studio' ) );
        return $old;
    }
    if ( ! aiwp_valid_font( $input['font'] ) ) {
        add_settings_error( 'aiwp_settings', 'aiwp_font', __( 'Select a font from the Google Fonts list.', 'ai-web-studio' ) );
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
        wp_localize_script( 'aiwp-settings-prompt', 'aiwpDesign', array( 'rules' => aiwp_typography_rules(), 'contentLanguageInstruction' => aiwp_content_language_instruction() ) );
    }
} );

function aiwp_overview() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap aiwp-dashboard">
        <div class="aiwp-dashboard-hero"><span><?php echo esc_html__( 'WEBSITE BUILDER ·', 'ai-web-studio' ); ?> <?php echo esc_html( AIWP_VERSION ); ?></span><h1><?php echo esc_html__( 'Your website starts with an idea.', 'ai-web-studio' ); ?></h1><p><?php echo esc_html__( 'Let AI prepare the code. Turn it into pages for your own website here.', 'ai-web-studio' ); ?></p><a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>"><?php echo esc_html__( 'Create a page', 'ai-web-studio' ); ?></a></div>
        <?php if ( ! aiwp_can_edit_code() ) : ?><div class="notice notice-warning inline"><p><?php echo esc_html__( 'Inserting code requires administrator permissions and', 'ai-web-studio' ); ?> <code>unfiltered_html</code><?php echo esc_html__( '. On WordPress Multisite this is normally limited to network administrators. Other users can continue using standard WordPress content.', 'ai-web-studio' ); ?></p></div><?php endif; ?>
        <?php if ( ! current_theme_supports( 'ai-web-parts' ) ) : ?><div class="notice notice-info inline"><p><?php echo esc_html__( 'For shared headers, footers and full-width pages, activate the theme', 'ai-web-studio' ); ?> <strong>web-svepomoci-sablona</strong> <?php echo esc_html__( 'under Appearance → Themes.', 'ai-web-studio' ); ?></p></div><?php endif; ?>
        <div class="aiwp-dashboard-grid">
            <section><span class="aiwp-step-number">01</span><h2><?php echo esc_html__( 'Prepare an AI prompt', 'ai-web-studio' ); ?></h2><p><?php echo esc_html__( 'Choose shared design, a new page, Header, Footer or an existing page to edit. Describe your idea and copy the prepared prompt into your AI.', 'ai-web-studio' ); ?></p><?php if ( aiwp_can_edit_code() ) : ?><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=aiwp-prompts' ) ); ?>"><?php echo esc_html__( 'AI prompts', 'ai-web-studio' ); ?></a><?php endif; ?></section>
            <section><span class="aiwp-step-number">02</span><h2><?php echo esc_html__( 'Paste the three code sections', 'ai-web-studio' ); ?></h2><p><?php echo esc_html__( 'Put HTML into Content, CSS into Design and JavaScript into Behaviour. Enable “Display content from the AI editor”.', 'ai-web-studio' ); ?></p></section>
            <section><span class="aiwp-step-number">03</span><h2><?php echo esc_html__( 'Preview and publish', 'ai-web-studio' ); ?></h2><p><?php echo esc_html__( 'Test desktop and mobile previews, complete SEO and save a draft. Publish when the page is ready.', 'ai-web-studio' ); ?></p></section>
        </div>
        <div class="aiwp-dashboard-grid aiwp-dashboard-secondary">
            <section><h2><?php echo esc_html__( 'Shared header and footer', 'ai-web-studio' ); ?></h2><p><?php echo esc_html__( 'The entries', 'ai-web-studio' ); ?> <strong>Header</strong> <?php esc_html_e( 'and', 'ai-web-studio' ); ?> <strong>Footer</strong> <?php echo esc_html__( 'in the left-hand menu work like the page editor. Once enabled and saved, their content is used across the website.', 'ai-web-studio' ); ?></p><p><?php echo esc_html__( 'Until you enable them, the theme displays a basic header and footer with the website name.', 'ai-web-studio' ); ?></p></section>
            <section><h2><?php echo esc_html__( 'Your website\'s design', 'ai-web-studio' ); ?></h2><p><?php echo esc_html__( 'Set shared colours, fonts and CSS in one place. AI can use the variables', 'ai-web-studio' ); ?> <code>--aiwp-accent</code>, <code>--aiwp-font</code> <?php esc_html_e( 'and', 'ai-web-studio' ); ?> <code>--aiwp-width</code>.</p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=aiwp-settings' ) ); ?>"><?php echo esc_html__( 'Website design', 'ai-web-studio' ); ?></a></section>
            <section><h2><?php echo esc_html__( 'Set the homepage', 'ai-web-studio' ); ?></h2><p><?php echo esc_html__( 'In Reading settings choose “A static page” and set your new page as the homepage.', 'ai-web-studio' ); ?></p><a class="button" href="<?php echo esc_url( admin_url( 'options-reading.php' ) ); ?>"><?php echo esc_html__( 'Reading settings', 'ai-web-studio' ); ?></a></section>
        </div>
        <?php if ( current_user_can( 'update_plugins' ) && current_user_can( 'update_themes' ) ) : ?>
        <p><?php echo esc_html__( 'New plugin and theme versions are retrieved from GitHub and installed through standard WordPress updates.', 'ai-web-studio' ); ?></p>
        <p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wsp_check_updates' ), 'wsp_check_updates' ) ); ?>"><?php echo esc_html__( 'Check for updates', 'ai-web-studio' ); ?></a> <a class="button" href="https://github.com/DanHutar/web-svepomoci/releases" target="_blank" rel="noopener"><?php echo esc_html__( 'View releases on GitHub', 'ai-web-studio' ); ?></a></p>
        <?php endif; ?>
        <p class="aiwp-dashboard-footnote"><?php echo esc_html__( 'web-svepomoci-plugin does not connect to any AI service and needs no API key. You transfer prompts and code by copying. Saved page, header and footer versions are available in WordPress revisions when enabled.', 'ai-web-studio' ); ?></p>
    </div>
    <?php
}

function aiwp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $settings = aiwp_get_settings();
    ?>
    <div class="wrap aiwp-dashboard"><h1><?php echo esc_html__( 'Website design', 'ai-web-studio' ); ?></h1><p><?php echo esc_html__( 'Shared settings for all pages. Colours and width affect code that references the corresponding CSS variables.', 'ai-web-studio' ); ?></p>
    <?php settings_errors(); ?>
    <?php if ( ! aiwp_can_edit_code() ) : ?><p><?php echo esc_html__( 'To change settings, sign in as an administrator with permission to insert code.', 'ai-web-studio' ); ?></p></div><?php return; endif; ?>
    <form action="options.php" method="post" class="aiwp-settings-form">
        <?php settings_fields( 'aiwp_settings_group' ); ?>
        <table class="form-table" role="presentation"><tbody>
            <tr><th scope="row"><label for="aiwp-accent"><?php echo esc_html__( 'Accent colour', 'ai-web-studio' ); ?></label></th><td><input type="color" id="aiwp-accent" name="aiwp_settings[accent]" value="<?php echo esc_attr( $settings['accent'] ); ?>"><p class="description"><code>var(--aiwp-accent)</code></p></td></tr>
            <tr><th scope="row"><label for="aiwp-font"><?php echo esc_html__( 'Font — Google Fonts', 'ai-web-studio' ); ?></label></th><td><select id="aiwp-font" name="aiwp_settings[font]">
                <optgroup label="<?php echo esc_attr__( 'Public Google Fonts', 'ai-web-studio' ); ?>">
                <?php foreach ( aiwp_font_catalog() as $key => $font ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" data-family="<?php echo esc_attr( $font['family'] ); ?>" <?php selected( $settings['font'], $key ); ?>><?php echo esc_html( $font['family'] ); ?></option>
                <?php endforeach; ?>
                </optgroup>
                <optgroup label="<?php echo esc_attr__( 'Built-in fonts without downloads', 'ai-web-studio' ); ?>"><option value="system" data-family="<?php echo esc_attr__( 'System sans-serif font', 'ai-web-studio' ); ?>" <?php selected( $settings['font'], 'system' ); ?>><?php echo esc_html__( 'System sans-serif', 'ai-web-studio' ); ?></option><option value="serif" data-family="Georgia" <?php selected( $settings['font'], 'serif' ); ?>>Georgia</option></optgroup>
                </select><p class="description"><?php echo esc_html__( 'Eight public Google Fonts, loaded from Google\'s servers without an API key. Apply the font using', 'ai-web-studio' ); ?> <code>var(--aiwp-font)</code><?php echo esc_html__( '. A custom font-family in existing CSS takes precedence.', 'ai-web-studio' ); ?></p></td></tr>
            <tr><th scope="row"><label for="aiwp-width"><?php echo esc_html__( 'Content width', 'ai-web-studio' ); ?></label></th><td><input type="number" id="aiwp-width" name="aiwp_settings[width]" min="640" max="1920" value="<?php echo esc_attr( $settings['width'] ); ?>"> px<p class="description"><code>max-width: var(--aiwp-width)</code> <?php echo esc_html__( 'in your page CSS.', 'ai-web-studio' ); ?></p></td></tr>
            <tr><th scope="row"><label for="aiwp-global-css"><?php echo esc_html__( 'Shared CSS', 'ai-web-studio' ); ?></label></th><td><textarea class="large-text code" rows="16" id="aiwp-global-css" name="aiwp_settings[css]" spellcheck="false"><?php echo esc_textarea( $settings['css'] ); ?></textarea><p class="description"><?php echo esc_html__( 'CSS only, without <style> tags. Changes apply across the website. These shared settings have no revision history.', 'ai-web-studio' ); ?></p></td></tr>
        </tbody></table>
        <h2><?php echo esc_html__( 'Shared design prompt', 'ai-web-studio' ); ?></h2>
        <p><?php echo esc_html__( 'Select a font and copy the prompt into your AI. It will propose shared CSS with clamp() sizes and line heights for H1–H6, body text and small. Paste the result into Shared CSS and save the website design. Selecting a font alone does not change the type scale.', 'ai-web-studio' ); ?></p>
        <button type="button" class="button" data-aiwp-design-copy><?php echo esc_html__( 'Copy shared CSS prompt', 'ai-web-studio' ); ?></button>
        <p data-aiwp-design-status role="status" aria-live="polite"></p>
        <details data-aiwp-design-fallback hidden><summary><?php echo esc_html__( 'Prompt for manual copying', 'ai-web-studio' ); ?></summary><textarea class="large-text code" rows="12" readonly aria-label="<?php echo esc_attr__( 'Shared CSS prompt', 'ai-web-studio' ); ?>"></textarea></details>
        <?php submit_button( __( 'Save website design', 'ai-web-studio' ) ); ?>
    </form></div>
    <?php
}
