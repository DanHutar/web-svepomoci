<?php
defined( 'ABSPATH' ) || exit;

function aiwp_has_seo_plugin() {
    return (bool) apply_filters( 'aiwp_external_seo_active',
        defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) || function_exists( 'the_seo_framework' )
    );
}

function aiwp_seo_document() {
    if ( aiwp_has_seo_plugin() || ! is_singular( 'page' ) || is_feed() || is_preview() || post_password_required( get_queried_object_id() ) ) {
        return null;
    }
    return aiwp_get_document( get_queried_object_id() );
}

add_filter( 'pre_get_document_title', function ( $title ) {
    $document = aiwp_seo_document();
    return $document && '' !== $document['seo_title'] ? $document['seo_title'] : $title;
}, 20 );

add_filter( 'get_canonical_url', function ( $url, $post ) {
    $document = aiwp_seo_document();
    return $document && (int) $post->ID === get_queried_object_id() && '' !== $document['seo_canonical'] ? $document['seo_canonical'] : $url;
}, 20, 2 );

add_filter( 'wp_robots', function ( $robots ) {
    $document = aiwp_seo_document();
    if ( $document && $document['seo_noindex'] ) {
        $robots['noindex'] = true;
        unset( $robots['index'] );
    }
    return $robots;
} );

add_action( 'wp_head', function () {
    $document = aiwp_seo_document();
    if ( ! $document ) {
        return;
    }
    $title = $document['seo_title'] ?: wp_get_document_title();
    $url = $document['seo_canonical'] ?: get_permalink();
    if ( '' !== $document['seo_description'] ) {
        echo '<meta name="description" content="' . esc_attr( $document['seo_description'] ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $document['seo_description'] ) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( $document['seo_description'] ) . '">' . "\n";
    }
    foreach ( array( 'og:type' => 'website', 'og:title' => $title, 'og:url' => $url, 'og:site_name' => get_bloginfo( 'name' ) ) as $key => $value ) {
        echo '<meta property="' . esc_attr( $key ) . '" content="' . esc_attr( $value ) . '">' . "\n";
    }
    echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta name="twitter:card" content="' . ( $document['seo_image'] ? 'summary_large_image' : 'summary' ) . '">' . "\n";
    if ( $document['seo_image'] ) {
        echo '<meta property="og:image" content="' . esc_url( $document['seo_image'] ) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( $document['seo_image'] ) . '">' . "\n";
    }
}, 5 );

// Keep explicitly noindexed pages out of WordPress's own sitemap too.
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
    if ( 'page' !== $post_type || aiwp_has_seo_plugin() ) {
        return $args;
    }
    $condition = array( 'relation' => 'OR',
        array( 'key' => '_aiwp_seo_noindex', 'compare' => 'NOT EXISTS' ),
        array( 'key' => '_aiwp_seo_noindex', 'value' => '1', 'compare' => '!=' ),
    );
    $args['meta_query'] = empty( $args['meta_query'] ) ? array( $condition ) : array( 'relation' => 'AND', $args['meta_query'], $condition );
    return $args;
}, 10, 2 );
