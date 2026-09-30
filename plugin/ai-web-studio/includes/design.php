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
        'family' => 'serif' === $font ? 'Georgia' : __( 'System sans-serif font', 'ai-web-studio' ),
        'stack' => 'serif' === $font ? 'Georgia, "Times New Roman", serif' : 'system-ui, -apple-system, "Segoe UI", sans-serif',
        'url' => '',
    );
}

function aiwp_typography_rules() {
    return implode( "\n", array(
        __( 'Using the selected font and its proportions, design a readable type scale for H1, H2, H3, H4, H5, H6, body text and small. A font name does not determine a single correct scale; explain your proposal and check the requested language, including diacritics.', 'ai-web-studio' ),
        __( 'For all eight levels, create separate font-size: clamp(...) and line-height: clamp(...) values. Use rem for sizes and calc(rem + vw) for the middle term, with minimum <= maximum. Do not set the html root font size to fixed pixels.', 'ai-web-studio' ),
        __( 'Use compatible length units in line-height, for example clamp(1.15em, calc(1.1em + 0.2vw), 1.35em); do not mix unitless numbers with rem/em/vw within one clamp(). Adapt the values to each level and font. Assign line height directly to each level so an unsuitable computed length is not inherited.', 'ai-web-studio' ),
        __( 'Define :root variables --aiwp-size-h1 through --aiwp-size-h6, --aiwp-size-body, --aiwp-size-small and matching --aiwp-leading-h1 through --aiwp-leading-h6, --aiwp-leading-body, --aiwp-leading-small. All 16 values must use clamp(). Include actual CSS selectors that use these variables.', 'ai-web-studio' ),
        __( 'Apply the font through var(--aiwp-font). Do not override this variable or font loading. The plugin loads the Google Font with weights 400, 500, 600 and 700; do not add @import, @font-face, link or another external font.', 'ai-web-studio' ),
        __( 'Scope shared styles to :where(.aiwp-content, .aiwp-header, .aiwp-footer). Body means ordinary text inside these wrappers, not a global body element override. Style h1–h6, p, li and small within these wrappers; do not repeatedly shrink nested lists.', 'ai-web-studio' ),
        __( 'Use at least 1rem for body text and usually at least 0.875rem for small; do not use small for essential information. Check widths of 320–1440 px, 200% text enlargement, long headings, wrapping and room for diacritics. Do not use fixed heights for text blocks.', 'ai-web-studio' ),
    ) );
}

function aiwp_shared_design_context() {
    $settings = aiwp_get_settings();
    $font = aiwp_font_details( $settings['font'] );
    return __( 'SHARED WEBSITE DESIGN', 'ai-web-studio' ) . "\n" . __( 'Selected font: ', 'ai-web-studio' ) . $font['family'] . __( '. Use var(--aiwp-font).', 'ai-web-studio' )
        . "\n" . __( 'Colour: ', 'ai-web-studio' ) . $settings['accent'] . __( ' (--aiwp-accent), width: ', 'ai-web-studio' ) . $settings['width'] . 'px (--aiwp-width).'
        . "\n" . __( 'Use existing shared classes and variables. Do not recreate shared typography or buttons in individual page CSS. Page CSS contains only its differences. If a shared type scale is missing, tell me to create it first using the Website design prompt.', 'ai-web-studio' )
        . "\n" . __( 'The shared scale uses clamp() for font-size and line-height of H1–H6, body and small, with --aiwp-size-* and --aiwp-leading-* variables. Preserve it; do not override heading sizes or the font unless explicitly requested.', 'ai-web-studio' )
        . "\n\n" . __( 'Currently saved shared CSS (context only; do not return it as page CSS):', 'ai-web-studio' ) . "\n" . ( $settings['css'] ?: __( 'Not created yet.', 'ai-web-studio' ) );
}
