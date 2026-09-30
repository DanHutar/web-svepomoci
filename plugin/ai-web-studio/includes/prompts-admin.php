<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
    if ( aiwp_can_edit_code() ) {
        add_submenu_page( 'aiwp', __( 'AI prompts', 'ai-web-studio' ), __( 'AI prompts', 'ai-web-studio' ), 'manage_options', 'aiwp-prompts', 'aiwp_prompts_page' );
    }
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'aiwp-prompts' !== ( isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? $_GET['page'] : '' ) || ! aiwp_can_edit_code() ) {
        return;
    }
    wp_enqueue_style( 'aiwp-prompts', AIWP_URL . 'assets/prompts.css', array(), AIWP_VERSION );
    wp_enqueue_script( 'aiwp-prompts', AIWP_URL . 'assets/prompts.js', array(), AIWP_VERSION, true );
    wp_localize_script( 'aiwp-prompts', 'aiwpPrompts', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'aiwp_prompts' ),
    ) );
} );

function aiwp_prompts_page() {
    if ( ! aiwp_can_edit_code() ) {
        wp_die( __( 'Only an administrator with permission to insert code can prepare prompts.', 'ai-web-studio' ), '', array( 'response' => 403 ) );
    }
    ?>
    <div class="wrap aiwp-prompts" id="aiwp-prompts">
        <h1><?php echo esc_html__( 'AI prompts', 'ai-web-studio' ); ?></h1>
        <p class="aiwp-prompts-lead"><?php echo esc_html__( 'Choose what you want to create and describe your idea. We will prepare a prompt for you to copy into your AI.', 'ai-web-studio' ); ?></p>
        <p class="aiwp-prompts-saved"><?php echo esc_html__( 'The prompt uses', 'ai-web-studio' ); ?> <strong><?php echo esc_html__( 'your saved font, shared CSS and, when selected, the current code', 'ai-web-studio' ); ?></strong><?php echo esc_html__( '. Save unfinished changes in other editors first.', 'ai-web-studio' ); ?></p>
        <noscript><div class="notice notice-warning inline"><p><?php echo esc_html__( 'Enable JavaScript in your browser to prepare and copy prompts.', 'ai-web-studio' ); ?></p></div></noscript>
        <form id="aiwp-prompts-form" class="aiwp-prompts-form">
            <div class="aiwp-prompts-field">
                <label for="aiwp-prompt-kind"><?php echo esc_html__( '1. What would you like to create?', 'ai-web-studio' ); ?></label>
                <select id="aiwp-prompt-kind" name="kind" aria-describedby="aiwp-prompt-kind-help">
                    <option value="style"><?php echo esc_html__( 'Shared design and typography', 'ai-web-studio' ); ?></option>
                    <option value="page"><?php echo esc_html__( 'New page', 'ai-web-studio' ); ?></option>
                    <option value="header"><?php echo esc_html__( 'Header — website header', 'ai-web-studio' ); ?></option>
                    <option value="footer"><?php echo esc_html__( 'Footer — website footer', 'ai-web-studio' ); ?></option>
                    <option value="edit"><?php echo esc_html__( 'Edit an existing page', 'ai-web-studio' ); ?></option>
                </select>
                <p id="aiwp-prompt-kind-help" class="description"><?php echo esc_html__( 'AI will propose shared CSS, including clamp() font sizes and line heights for H1–H6, body text and small. Paste the result into Website design → Shared CSS.', 'ai-web-studio' ); ?></p>
            </div>

            <div id="aiwp-prompt-page-picker" class="aiwp-prompts-page-picker" hidden>
                <div class="aiwp-prompts-field">
                    <label for="aiwp-prompt-page-search"><?php echo esc_html__( 'Find a page to edit', 'ai-web-studio' ); ?></label>
                    <div class="aiwp-prompts-search">
                        <input type="search" id="aiwp-prompt-page-search" maxlength="200" placeholder="<?php echo esc_attr__( 'Page title', 'ai-web-studio' ); ?>" autocomplete="off" aria-describedby="aiwp-prompt-pages-status">
                        <button type="button" id="aiwp-prompt-page-search-button" class="button"><?php echo esc_html__( 'Search pages', 'ai-web-studio' ); ?></button>
                    </div>
                </div>
                <div class="aiwp-prompts-field">
                    <label for="aiwp-prompt-page-id"><?php echo esc_html__( 'Page to edit', 'ai-web-studio' ); ?></label>
                    <select id="aiwp-prompt-page-id" name="page_id" disabled aria-describedby="aiwp-prompt-pages-status">
                        <option value=""><?php echo esc_html__( 'Select a page first', 'ai-web-studio' ); ?></option>
                    </select>
                    <p id="aiwp-prompt-pages-status" class="description" role="status" aria-live="polite"></p>
                </div>
            </div>

            <div class="aiwp-prompts-field">
                <label for="aiwp-prompt-instructions"><?php echo esc_html__( '2. What should AI create or change?', 'ai-web-studio' ); ?></label>
                <textarea id="aiwp-prompt-instructions" name="instructions" rows="6" maxlength="10000" required aria-describedby="aiwp-prompt-instructions-help" placeholder="<?php echo esc_attr__( 'For example: I want a calm, clear design for a small flower shop. Use green and easy-to-read buttons.', 'ai-web-studio' ); ?>"></textarea>
                <p id="aiwp-prompt-instructions-help" class="description"><?php echo esc_html__( 'Describe the purpose, content and requested changes in your own words. This field is required, up to 10,000 characters.', 'ai-web-studio' ); ?></p>
            </div>
            <div class="aiwp-prompts-actions">
                <button type="submit" id="aiwp-prompt-generate" class="button button-primary" disabled><?php echo esc_html__( 'Prepare prompt', 'ai-web-studio' ); ?></button>
            </div>
            <p id="aiwp-prompt-status" class="aiwp-prompts-status" role="status" aria-live="polite" aria-atomic="true"></p>
        </form>

        <section id="aiwp-prompt-result" class="aiwp-prompts-result" aria-labelledby="aiwp-prompt-result-heading" hidden>
            <h2 id="aiwp-prompt-result-heading"><?php echo esc_html__( '3. Copy the prompt into your AI', 'ai-web-studio' ); ?></h2>
            <p><?php echo esc_html__( 'Review the entire prompt and copy it into your AI chat. Then paste the response into the fields listed below.', 'ai-web-studio' ); ?></p>
            <ul id="aiwp-prompt-notices" class="aiwp-prompts-notices" hidden></ul>
            <label class="screen-reader-text" for="aiwp-prompt-output"><?php echo esc_html__( 'Complete AI prompt', 'ai-web-studio' ); ?></label>
            <textarea id="aiwp-prompt-output" class="code" rows="16" readonly spellcheck="false"></textarea>
            <div class="aiwp-prompts-actions">
                <button type="button" id="aiwp-prompt-copy" class="button button-primary" disabled><?php echo esc_html__( 'Copy prompt', 'ai-web-studio' ); ?></button>
            </div>
            <p id="aiwp-prompt-copy-status" class="aiwp-prompts-status" role="status" aria-live="polite" aria-atomic="true"></p>
            <div class="aiwp-prompts-destination">
                <h3><?php echo esc_html__( 'Where to paste the AI output', 'ai-web-studio' ); ?></h3>
                <p id="aiwp-prompt-destination-help"></p>
                <a id="aiwp-prompt-destination-link" class="button"><?php echo esc_html__( 'Open editor', 'ai-web-studio' ); ?></a>
            </div>
        </section>
        <p class="aiwp-prompts-footnote"><?php echo esc_html__( 'Preparing a prompt does not change your website. The plugin does not connect to AI or send it anything automatically. You paste the generated code, check the preview and save it yourself.', 'ai-web-studio' ); ?></p>
    </div>
    <?php
}
