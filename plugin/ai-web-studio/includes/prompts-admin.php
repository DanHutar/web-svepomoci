<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
    if ( aiwp_can_edit_code() ) {
        add_submenu_page( 'aiwp', 'Zadání pro AI', 'Zadání pro AI', 'manage_options', 'aiwp-prompts', 'aiwp_prompts_page' );
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
        wp_die( 'Zadání může připravovat pouze správce s oprávněním vkládat kód.', '', array( 'response' => 403 ) );
    }
    ?>
    <div class="wrap aiwp-prompts" id="aiwp-prompts">
        <h1>Zadání pro AI</h1>
        <p class="aiwp-prompts-lead">Vyberte, co chcete vytvořit, a popište svůj nápad. Připravíme zadání, které zkopírujete do své AI.</p>
        <p class="aiwp-prompts-saved">Zadání použije <strong>uložené písmo, společné CSS a podle výběru také současný kód</strong>. Rozpracované změny v jiných editorech nejprve uložte.</p>
        <noscript><div class="notice notice-warning inline"><p>Pro přípravu a kopírování zadání zapněte JavaScript v prohlížeči.</p></div></noscript>
        <form id="aiwp-prompts-form" class="aiwp-prompts-form">
            <div class="aiwp-prompts-field">
                <label for="aiwp-prompt-kind">1. Co chcete připravit?</label>
                <select id="aiwp-prompt-kind" name="kind" aria-describedby="aiwp-prompt-kind-help">
                    <option value="style">Společný vzhled a typografie</option>
                    <option value="page">Nová stránka</option>
                    <option value="header">Header — hlavička webu</option>
                    <option value="footer">Footer — patička webu</option>
                    <option value="edit">Úprava existující stránky</option>
                </select>
                <p id="aiwp-prompt-kind-help" class="description">AI navrhne společné CSS včetně velikostí písma a výšek řádků pomocí clamp() pro H1–H6, běžný text a small. Výsledek vložíte do Vzhled webu → Společné CSS.</p>
            </div>

            <div id="aiwp-prompt-page-picker" class="aiwp-prompts-page-picker" hidden>
                <div class="aiwp-prompts-field">
                    <label for="aiwp-prompt-page-search">Najděte stránku k úpravě</label>
                    <div class="aiwp-prompts-search">
                        <input type="search" id="aiwp-prompt-page-search" maxlength="200" placeholder="Název stránky" autocomplete="off" aria-describedby="aiwp-prompt-pages-status">
                        <button type="button" id="aiwp-prompt-page-search-button" class="button">Vyhledat stránky</button>
                    </div>
                </div>
                <div class="aiwp-prompts-field">
                    <label for="aiwp-prompt-page-id">Stránka k úpravě</label>
                    <select id="aiwp-prompt-page-id" name="page_id" disabled aria-describedby="aiwp-prompt-pages-status">
                        <option value="">Nejprve vyberte stránku</option>
                    </select>
                    <p id="aiwp-prompt-pages-status" class="description" role="status" aria-live="polite"></p>
                </div>
            </div>

            <div class="aiwp-prompts-field">
                <label for="aiwp-prompt-instructions">2. Co má AI vytvořit nebo změnit?</label>
                <textarea id="aiwp-prompt-instructions" name="instructions" rows="6" maxlength="10000" required aria-describedby="aiwp-prompt-instructions-help" placeholder="Například: Chci klidný, přehledný vzhled pro malé květinářství. Použij zelenou barvu a dobře čitelná tlačítka."></textarea>
                <p id="aiwp-prompt-instructions-help" class="description">Popište účel, obsah a požadované změny vlastními slovy. Toto pole je povinné, nejvýše 10 000 znaků.</p>
            </div>
            <div class="aiwp-prompts-actions">
                <button type="submit" id="aiwp-prompt-generate" class="button button-primary" disabled>Připravit zadání</button>
            </div>
            <p id="aiwp-prompt-status" class="aiwp-prompts-status" role="status" aria-live="polite" aria-atomic="true"></p>
        </form>

        <section id="aiwp-prompt-result" class="aiwp-prompts-result" aria-labelledby="aiwp-prompt-result-heading" hidden>
            <h2 id="aiwp-prompt-result-heading">3. Zkopírujte zadání do své AI</h2>
            <p>Prohlédněte si celé zadání a zkopírujte ho do chatu s AI. Odpověď pak vložte do polí uvedených níže.</p>
            <ul id="aiwp-prompt-notices" class="aiwp-prompts-notices" hidden></ul>
            <label class="screen-reader-text" for="aiwp-prompt-output">Celé zadání pro AI</label>
            <textarea id="aiwp-prompt-output" class="code" rows="16" readonly spellcheck="false"></textarea>
            <div class="aiwp-prompts-actions">
                <button type="button" id="aiwp-prompt-copy" class="button button-primary" disabled>Zkopírovat zadání</button>
            </div>
            <p id="aiwp-prompt-copy-status" class="aiwp-prompts-status" role="status" aria-live="polite" aria-atomic="true"></p>
            <div class="aiwp-prompts-destination">
                <h3>Kam vložit výsledek od AI</h3>
                <p id="aiwp-prompt-destination-help"></p>
                <a id="aiwp-prompt-destination-link" class="button">Otevřít editor</a>
            </div>
        </section>
        <p class="aiwp-prompts-footnote">Příprava zadání nic nemění na webu. Plugin se nepřipojuje k AI a nic jí automaticky neposílá. Vygenerovaný kód vložíte, zkontrolujete v náhledu a uložíte sami.</p>
    </div>
    <?php
}
