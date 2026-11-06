<?php
defined( 'ABSPATH' ) || exit;

function aiwp_pencilino_prompt_rules() {
    $rules = 'Pencilino integration exception: editable content may use [aiwp_text id="hero-title"]Heading[/aiwp_text], [aiwp_richtext id="intro"]<p>Text</p>[/aiwp_richtext], [aiwp_button id="cta" url="/contact" class="button"]Contact[/aiwp_button], [aiwp_image id="photo" attachment="123" class="photo" /]. Use only real supplied image attachment IDs; otherwise leave an image placeholder. Put text fields inside headings, richtext outside paragraphs, and button fields outside links. Keep IDs stable and unique within each page/header/footer; use lowercase letters, numbers, dots, dashes or underscores (max 80 characters). Never nest these tags or put them inside attributes, scripts or styles. Do not use PHP. Pencilino stores overrides separately: changing the default does not replace a saved edit. Preserve existing field IDs. Other third-party shortcodes remain unsupported.';
    if ( 'cs' === WSP_Languages::language( 'content' ) ) {
        $rules = 'Výjimka pro Pencilino: upravitelný obsah označ pomocí [aiwp_text id="hero-title"]Nadpis[/aiwp_text], [aiwp_richtext id="intro"]<p>Text</p>[/aiwp_richtext], [aiwp_button id="cta" url="/kontakt" class="button"]Kontakt[/aiwp_button], [aiwp_image id="photo" attachment="123" class="photo" /]. ID obrázku musí být skutečné dodané ID média, jinak ponech místo pro obrázek. Text vlož dovnitř nadpisu, richtext mimo odstavce a tlačítko mimo odkazy. ID musí být stabilní a jedinečné v rámci stránky/headeru/footeru: malá písmena, číslice, tečky, pomlčky nebo podtržítka, nejvýše 80 znaků. Značky nevnořuj a nevkládej do atributů, skriptů ani stylů. Nepoužívej PHP. Pencilino ukládá změny zvlášť; změna výchozího textu nenahradí uloženou editaci. Zachovej existující ID. Ostatní shortcody cizích pluginů nejsou podporované.';
    }
    return $rules;
}

/** Optional bridge. Pencilino owns editing, permissions, validation and storage. */
function aiwp_render_document_html( $html, $document_id ) {
    $html = aiwp_render_dynamic_html( $html );
    $tags = array( 'aiwp_text', 'aiwp_richtext', 'aiwp_button', 'aiwp_image' );
    return preg_replace_callback( '/' . get_shortcode_regex( $tags ) . '/s', function ( $match ) use ( $document_id ) {
        if ( '[' === $match[1] && ']' === $match[6] ) { return substr( $match[0], 1, -1 ); }
        $attrs = shortcode_parse_atts( $match[3] );
        $attrs = is_array( $attrs ) ? $attrs : array();
        $type = substr( $match[2], 5 );
        $content = isset( $match[5] ) ? $match[5] : '';
        $id = isset( $attrs['id'] ) ? $attrs['id'] : '';
        $classes = implode( ' ', array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', $attrs['class'] ?? '' ) ) ) );
        $args = array( 'label' => sanitize_text_field( $attrs['label'] ?? $id ), 'class' => $classes, 'scope' => 'site' );
        $args['default'] = 'richtext' === $type ? wp_kses_post( $content ) : sanitize_text_field( $content );
        if ( 'button' === $type ) {
            $args['default'] = array( 'text' => sanitize_text_field( $content ), 'url' => esc_url_raw( $attrs['url'] ?? '#' ) );
            $args['target'] = '_blank' === ( $attrs['target'] ?? '' ) ? '_blank' : '_self';
        } elseif ( 'image' === $type ) {
            $args['default'] = absint( $attrs['attachment'] ?? 0 );
            $args['size'] = sanitize_key( $attrs['size'] ?? 'full' );
        }
        if ( isset( $attrs['max_length'] ) ) { $args['max_length'] = max( 1, absint( $attrs['max_length'] ) ); }
        $helper = 'pencil_' . $type;
        if ( $document_id && preg_match( '/^[a-z0-9][a-z0-9._-]{0,79}$/D', $id ) && function_exists( $helper ) ) {
            ob_start();
            // Document IDs also keep shared header/footer fields independent of the current page.
            $helper( 'byyourself-' . absint( $document_id ) . '.' . $id, $args );
            return $match[1] . ob_get_clean() . $match[6];
        }
        // No dependency or fatal error when Pencilino is absent; original defaults remain visible.
        if ( 'button' === $type ) {
            return '<a class="' . esc_attr( $classes ) . '" href="' . esc_url( $args['default']['url'] ) . '"'
                . ( '_blank' === $args['target'] ? ' target="_blank" rel="noopener"' : '' ) . '>' . esc_html( $args['default']['text'] ) . '</a>';
        }
        if ( 'image' === $type ) { return wp_get_attachment_image( $args['default'], $args['size'], false, array( 'class' => $classes ) ); }
        if ( 'richtext' === $type ) { return '<div class="pencil-richtext ' . esc_attr( $classes ) . '">' . wp_kses_post( $content ) . '</div>'; }
        return esc_html( $args['default'] );
    }, $html );
}

// Keep WP's editor/media styles intact for authorised frontend Pencilino sessions.
add_filter( 'aiwp_trim_core_styles', function ( $trim ) {
    return class_exists( 'Pencil_Plugin' ) && Pencil_Plugin::can_edit() ? false : $trim;
} );
