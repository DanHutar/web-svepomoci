<?php
defined( 'ABSPATH' ) || exit;

/** Public Google Fonts; fixed names prevent arbitrary stylesheet URLs or CSS injection. */
function aiwp_font_catalog() {
    return array(
        'inter' => array( 'family' => 'Inter', 'fallback' => 'sans-serif' ),
        'roboto' => array( 'family' => 'Roboto', 'fallback' => 'sans-serif' ),
        'open-sans' => array( 'family' => 'Open Sans', 'fallback' => 'sans-serif' ),
        'montserrat' => array( 'family' => 'Montserrat', 'fallback' => 'sans-serif' ),
        'nunito-sans' => array( 'family' => 'Nunito Sans', 'fallback' => 'sans-serif' ),
        'source-sans-3' => array( 'family' => 'Source Sans 3', 'fallback' => 'sans-serif' ),
        'lora' => array( 'family' => 'Lora', 'fallback' => 'serif' ),
        'merriweather' => array( 'family' => 'Merriweather', 'fallback' => 'serif' ),
    );
}

function aiwp_valid_font( $font ) {
    return is_string( $font ) && ( in_array( $font, array( 'system', 'serif' ), true ) || isset( aiwp_font_catalog()[ $font ] ) );
}

function aiwp_font_details( $font ) {
    $catalog = aiwp_font_catalog();
    if ( is_string( $font ) && isset( $catalog[ $font ] ) ) {
        $item = $catalog[ $font ];
        return array(
            'family' => $item['family'],
            'stack' => '"' . $item['family'] . '", ' . $item['fallback'],
            'url' => 'https://fonts.googleapis.com/css2?family=' . urlencode( $item['family'] ) . ':wght@400;500;600;700&display=swap',
        );
    }
    return array(
        'family' => 'serif' === $font ? 'Georgia' : 'Systémové bezpatkové písmo',
        'stack' => 'serif' === $font ? 'Georgia, "Times New Roman", serif' : 'system-ui, -apple-system, "Segoe UI", sans-serif',
        'url' => '',
    );
}

function aiwp_typography_rules() {
    return implode( "\n", array(
        'Navrhni podle zvoleného fontu a jeho proporcí čitelnou typografickou stupnici pro H1, H2, H3, H4, H5, H6, body (běžný text) a small. Neexistuje jediná správná stupnice daná názvem fontu; vysvětli svůj návrh a ověř češtinu.',
        'Pro všech osm úrovní vytvoř zvlášť font-size: clamp(...) i line-height: clamp(...). Velikosti používej v rem, prostřední člen calc(rem + vw), minimum <= maximum. Neměň kořenovou velikost html na pevné px.',
        'U line-height používej kompatibilní délkové jednotky, například clamp(1.15em, calc(1.1em + 0.2vw), 1.35em); nemíchej bezrozměrná čísla s rem/em/vw uvnitř jednoho clamp(). Hodnoty uprav podle konkrétní úrovně a fontu. Řádkování přiřaď přímo každé úrovni, aby se nedědila nevhodná vypočtená délka.',
        'Definuj na :root proměnné --aiwp-size-h1 až --aiwp-size-h6, --aiwp-size-body, --aiwp-size-small a odpovídající --aiwp-leading-h1 až --aiwp-leading-h6, --aiwp-leading-body, --aiwp-leading-small. Všech 16 hodnot má být clamp(). Připoj skutečné CSS selektory, které proměnné používají.',
        'Font aplikuj přes var(--aiwp-font). Tuto proměnnou ani načítání písma nepřepisuj. Google Font načítá plugin s vahami 400, 500, 600, 700; nepřidávej @import, @font-face, link ani další externí font.',
        'Společné styly omez na :where(.aiwp-content, .aiwp-header, .aiwp-footer). Body zde znamená běžný text v těchto obalech, nikoli nutnost měnit globální element body. Styluj h1–h6, p, li a small v těchto obalech; nezmenšuj vnořené seznamy opakovaně.',
        'Běžný text navrhni nejméně 1rem, small zpravidla nejméně 0.875rem; nepoužívej small pro podstatné informace. Ověř šířky 320–1440 px, zvětšení textu na 200 %, dlouhé české nadpisy, zalamování a dostatek místa pro diakritiku. Nepoužívej pevné výšky textových bloků.',
    ) );
}

function aiwp_shared_design_context() {
    $settings = aiwp_get_settings();
    $font = aiwp_font_details( $settings['font'] );
    return 'SPOLEČNÝ VZHLED WEBU' . "\n" . 'Zvolený font: ' . $font['family'] . '. Používej var(--aiwp-font).'
        . "\n" . 'Barva: ' . $settings['accent'] . ' (--aiwp-accent), šířka: ' . $settings['width'] . 'px (--aiwp-width).'
        . "\n" . 'Používej existující společné třídy a proměnné. Nevytvářej znovu společnou typografii ani tlačítka v CSS jednotlivé stránky. CSS stránky obsahuje pouze její odlišnosti. Pokud společná stupnice chybí, upozorni, že ji nejprve vytvořím zadáním ve Vzhled webu.'
        . "\n" . 'Společná stupnice používá clamp() pro font-size i line-height H1–H6, body a small, s proměnnými --aiwp-size-* a --aiwp-leading-*. Zachovej ji; bez výslovného požadavku nepřepisuj velikosti nadpisů ani písmo.'
        . "\n\n" . 'Aktuálně uložené společné CSS (pouze kontext, nevracej je v CSS stránky):' . "\n" . ( $settings['css'] ?: 'Zatím není vytvořené.' );
}
