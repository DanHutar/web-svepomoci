<?php
/** Prompt generation is read-only and must not expose unrelated saved content. */
$prompt_passed = array();
function aiwp_prompt_test_assert( $condition, $message ) {
    global $prompt_passed;
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
    $prompt_passed[] = $message;
}

$prompt_settings = get_option( 'aiwp_settings', array() );
$prompt_current_user = get_current_user_id();
$prompt_ids = array();
$prompt_editor = 0;
$prompt_deny_page = null;
$prompt_deny_unfiltered = null;
try {
    update_option( 'aiwp_settings', array( 'font' => 'lora', 'accent' => '#345678', 'width' => 1180, 'css' => '.prompt-shared-fixture { color: #345678; }' ) );
    $prompt_ai = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Prompt selected page', 'post_content' => '<p>Old ordinary content</p>' ) );
    $prompt_ids[] = $prompt_ai;
    $prompt_doc = array_merge( aiwp_document_defaults(), array(
        'enabled' => true,
        'html' => '<section class="prompt-selected-fixture"><h1>Český nadpis</h1></section>',
        'css' => '.prompt-selected-fixture { --quoted: "\\2713"; }',
        'js' => 'const promptSelected = /\\d+\\s/;',
        'seo_title' => 'Prompt SEO title fixture',
        'seo_description' => 'Prompt SEO description fixture',
        'seo_canonical' => 'https://example.test/prompt-fixture/',
        'hide_header' => true,
    ) );
    foreach ( $prompt_doc as $key => $value ) {
        update_post_meta( $prompt_ai, '_aiwp_' . $key, wp_slash( $value ) );
    }
    $prompt_plain_content = '<!-- wp:paragraph --><p>Prompt ordinary saved content fixture.</p><!-- /wp:paragraph -->';
    $prompt_plain = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Prompt ordinary page', 'post_content' => $prompt_plain_content ) );
    $prompt_ids[] = $prompt_plain;
    $prompt_private = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'private', 'post_title' => 'Prompt unrelated page', 'post_content' => 'PROMPT_UNRELATED_PRIVATE_CONTENT' ) );
    $prompt_ids[] = $prompt_private;
    update_post_meta( $prompt_private, '_aiwp_html', 'PROMPT_UNRELATED_PRIVATE_AI_HTML' );
    $prompt_trash = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'trash', 'post_title' => 'Prompt trash page' ) );
    $prompt_ids[] = $prompt_trash;
    $prompt_post = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Prompt ordinary post' ) );
    $prompt_ids[] = $prompt_post;
    $prompt_revision = wp_insert_post( array( 'post_type' => 'revision', 'post_status' => 'inherit', 'post_parent' => $prompt_ai, 'post_title' => 'Prompt revision' ) );
    $prompt_ids[] = $prompt_revision;

    $prompt_instruction = 'Přidej nabídku; zachovej "uvozovky" a cestu C:\\web. <script>window.promptInjected = true;</script>';
    $prompt_before = aiwp_get_document( $prompt_ai );
    foreach ( array( 'style', 'page', 'header', 'footer', 'edit' ) as $prompt_kind ) {
        $prompt_result = aiwp_build_prompt( $prompt_kind, $prompt_instruction, 'edit' === $prompt_kind ? $prompt_ai : 0 );
        aiwp_prompt_test_assert( ! is_wp_error( $prompt_result ), 'Build supported prompt: ' . $prompt_kind );
        $prompt_text = $prompt_result['prompt'];
        aiwp_prompt_test_assert( false !== strpos( $prompt_text, $prompt_instruction ), 'User request is preserved verbatim: ' . $prompt_kind );
        aiwp_prompt_test_assert( false !== strpos( $prompt_text, 'Lora' ) && false !== strpos( $prompt_text, '.prompt-shared-fixture' ), 'Saved font and shared CSS are included: ' . $prompt_kind );
        aiwp_prompt_test_assert( false === strpos( $prompt_text, 'PROMPT_UNRELATED_PRIVATE' ), 'Unselected private content is excluded: ' . $prompt_kind );
        aiwp_prompt_test_assert( ! empty( $prompt_result['destination']['label'] ) && ! empty( $prompt_result['destination']['help'] ) && is_array( $prompt_result['notices'] ), 'Output has destination help and notices: ' . $prompt_kind );
        $prompt_url = $prompt_result['destination']['url'];
        if ( 'style' === $prompt_kind ) {
            foreach ( array( 'H1, H2, H3, H4, H5, H6, body', 'small', 'font-size: clamp', 'line-height: clamp', '--aiwp-size-', '--aiwp-leading-' ) as $prompt_rule ) {
                aiwp_prompt_test_assert( false !== strpos( $prompt_text, $prompt_rule ), 'Shared prompt covers typography rule: ' . $prompt_rule );
            }
            aiwp_prompt_test_assert( false !== strpos( $prompt_url, 'page=aiwp-settings' ), 'Shared CSS destination is Appearance settings' );
        } elseif ( 'page' === $prompt_kind ) {
            aiwp_prompt_test_assert( false !== strpos( $prompt_url, 'post-new.php' ) && false !== strpos( $prompt_url, 'post_type=page' ), 'New page destination opens a page editor' );
            aiwp_prompt_test_assert( false === strpos( $prompt_text, 'prompt-selected-fixture' ), 'New page prompt does not include an existing page' );
        } elseif ( 'edit' === $prompt_kind ) {
            foreach ( array( 'html', 'css', 'js', 'seo_title', 'seo_description', 'seo_canonical' ) as $prompt_field ) {
                $prompt_json_value = wp_json_encode( $prompt_doc[ $prompt_field ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
                aiwp_prompt_test_assert( false !== strpos( $prompt_text, $prompt_json_value ), 'Selected saved field is preserved as JSON: ' . $prompt_field );
            }
            aiwp_prompt_test_assert( false !== strpos( $prompt_url, 'post=' . $prompt_ai ) && false !== strpos( $prompt_url, 'action=edit' ), 'Edit destination points to the selected page' );
        } else {
            $prompt_location = 'header' === $prompt_kind ? 'primary' : 'footer';
            aiwp_prompt_test_assert( false !== strpos( $prompt_text, '[aiwp_menu location="' . $prompt_location . '"]' ), 'Part prompt retains native menu marker: ' . $prompt_kind );
            aiwp_prompt_test_assert( false !== strpos( $prompt_url, 'post=' . aiwp_get_part_id( $prompt_kind ) ), 'Part destination opens the saved part: ' . $prompt_kind );
        }
    }
    $prompt_result = aiwp_build_prompt( 'edit', 'Uprav běžnou stránku.', $prompt_plain );
    aiwp_prompt_test_assert( ! is_wp_error( $prompt_result ) && false !== strpos( $prompt_result['prompt'], $prompt_plain_content ), 'Ordinary page prompt preserves saved block content for conversion' );
    foreach ( array(
        array( 'unknown', 'Valid request', 0 ),
        array( array( 'page' ), 'Valid request', 0 ),
        array( 'page', array( 'invalid' ), 0 ),
        array( 'page', " \n\t ", 0 ),
        array( 'page', str_repeat( 'a', 10001 ), 0 ),
        array( 'edit', 'Valid request', 0 ),
        array( 'edit', 'Valid request', -$prompt_ai ),
        array( 'edit', 'Valid request', $prompt_ai . 'bad' ),
        array( 'edit', 'Valid request', array( $prompt_ai ) ),
        array( 'edit', 'Valid request', $prompt_trash ),
        array( 'edit', 'Valid request', $prompt_post ),
        array( 'edit', 'Valid request', $prompt_revision ),
    ) as $prompt_invalid_index => $prompt_invalid ) {
        aiwp_prompt_test_assert( is_wp_error( aiwp_build_prompt( ...$prompt_invalid ) ), 'Reject invalid prompt request case ' . ( $prompt_invalid_index + 1 ) );
    }
    aiwp_prompt_test_assert( aiwp_get_document( $prompt_ai ) === $prompt_before && get_post_field( 'post_content', $prompt_plain, 'raw' ) === $prompt_plain_content, 'Prompt generation leaves saved page data unchanged' );

    $prompt_editor = wp_insert_user( array( 'user_login' => 'prompt-check-editor', 'user_pass' => wp_generate_password(), 'role' => 'editor' ) );
    wp_set_current_user( $prompt_editor );
    aiwp_prompt_test_assert( is_wp_error( aiwp_build_prompt( 'edit', 'Read selected page.', $prompt_ai ) ) && is_wp_error( aiwp_build_prompt( 'style', 'Read shared CSS.' ) ), 'Editor cannot read code through prompt builders' );
    wp_set_current_user( $prompt_current_user );
    $prompt_deny_unfiltered = function ( $caps ) {
        $caps['unfiltered_html'] = false;
        return $caps;
    };
    add_filter( 'user_has_cap', $prompt_deny_unfiltered );
    aiwp_prompt_test_assert( is_wp_error( aiwp_build_prompt( 'style', 'Read shared CSS.' ) ), 'Administrator without unfiltered_html cannot generate code prompts' );
    remove_filter( 'user_has_cap', $prompt_deny_unfiltered );
    $prompt_deny_page = function ( $caps, $cap, $user_id, $args ) use ( $prompt_ai ) {
        if ( 'edit_post' === $cap && isset( $args[0] ) && $prompt_ai === (int) $args[0] ) {
            $caps[] = 'do_not_allow';
        }
        return $caps;
    };
    add_filter( 'map_meta_cap', $prompt_deny_page, 20, 4 );
    aiwp_prompt_test_assert( is_wp_error( aiwp_build_prompt( 'edit', 'Read selected page.', $prompt_ai ) ), 'Selected page requires its own edit_post permission' );
    remove_filter( 'map_meta_cap', $prompt_deny_page, 20 );
    echo wp_json_encode( array( 'passed' => $prompt_passed ) );
} finally {
    if ( $prompt_deny_page ) {
        remove_filter( 'map_meta_cap', $prompt_deny_page, 20 );
    }
    if ( $prompt_deny_unfiltered ) {
        remove_filter( 'user_has_cap', $prompt_deny_unfiltered );
    }
    wp_set_current_user( $prompt_current_user );
    update_option( 'aiwp_settings', $prompt_settings );
    foreach ( $prompt_ids as $prompt_id ) {
        wp_delete_post( $prompt_id, true );
    }
    if ( $prompt_editor && ! is_wp_error( $prompt_editor ) ) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user( $prompt_editor );
    }
}
