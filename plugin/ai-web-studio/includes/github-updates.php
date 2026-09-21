<?php
/**
 * Updates from the public DanHutar/web-svepomoci releases.
 * This file is identical in both packages so either active component can check both.
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WSP_GitHub_Updates', false ) ) {
    final class WSP_GitHub_Updates {
        const REPOSITORY = 'https://github.com/DanHutar/web-svepomoci';
        const API = 'https://api.github.com/repos/DanHutar/web-svepomoci/releases/latest';
        const CACHE = 'wsp_github_release_v1';
        const PLUGIN = 'ai-web-studio/ai-web-studio.php';
        const THEME = 'ai-web';
        private static $registered = false;

        public static function init() {
            if ( self::$registered ) {
                return;
            }
            self::$registered = true;
            add_filter( 'update_plugins_github.com', array( __CLASS__, 'plugin_update' ), 10, 4 );
            add_filter( 'update_themes_github.com', array( __CLASS__, 'theme_update' ), 10, 4 );
            add_filter( 'plugins_api', array( __CLASS__, 'plugin_details' ), 10, 3 );
            add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache' ) );
            add_action( 'admin_post_wsp_check_updates', array( __CLASS__, 'check_now' ) );
        }

        public static function clear_cache() {
            delete_site_transient( self::CACHE );
        }

        public static function check_now() {
            if ( ! current_user_can( 'update_plugins' ) || ! current_user_can( 'update_themes' ) ) {
                wp_die( 'Nemáte oprávnění kontrolovat aktualizace.', '', array( 'response' => 403 ) );
            }
            check_admin_referer( 'wsp_check_updates' );
            self::clear_cache();
            delete_site_transient( 'update_plugins' );
            delete_site_transient( 'update_themes' );
            wp_safe_redirect( network_admin_url( 'update-core.php' ) );
            exit;
        }

        private static function get_json( $url ) {
            $response = wp_remote_get( $url, array(
                'timeout' => 10,
                'redirection' => 3,
                'limit_response_size' => 262144,
                'headers' => array(
                    'Accept' => 'application/json',
                    'User-Agent' => 'web-svepomoci-updater',
                ),
            ) );
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                return false;
            }
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            return is_array( $data ) ? $data : false;
        }

        /** No HTML rendering calls this method; network checks use WordPress's update hooks. */
        public static function release() {
            $cached = get_site_transient( self::CACHE );
            if ( false !== $cached ) {
                return isset( $cached['release'] ) ? $cached['release'] : false;
            }
            // Cache failures briefly too, including repositories with no published release yet.
            set_site_transient( self::CACHE, array( 'release' => false ), 5 * MINUTE_IN_SECONDS );
            $release = self::get_json( self::API );
            if ( ! $release || ! empty( $release['draft'] ) || ! empty( $release['prerelease'] )
                || ! isset( $release['tag_name'], $release['assets'] )
                || ! is_string( $release['tag_name'] ) || ! is_array( $release['assets'] )
                || ! preg_match( '/^v(\d+\.\d+\.\d+)$/', $release['tag_name'], $version ) ) {
                return false;
            }
            $base = self::REPOSITORY . '/releases/download/' . $release['tag_name'] . '/';
            $assets = array();
            foreach ( $release['assets'] as $asset ) {
                if ( ! is_array( $asset ) || ! isset( $asset['name'], $asset['browser_download_url'], $asset['state'] )
                    || ! is_string( $asset['name'] ) || 'uploaded' !== $asset['state'] ) {
                    continue;
                }
                if ( in_array( $asset['name'], array( 'updates.json', 'web-svepomoci-plugin.zip', 'web-svepomoci-sablona.zip' ), true )
                    && $base . $asset['name'] === $asset['browser_download_url'] ) {
                    $assets[ $asset['name'] ] = $asset['browser_download_url'];
                }
            }
            if ( count( $assets ) !== 3 ) {
                return false;
            }
            $manifest = self::get_json( $assets['updates.json'] );
            if ( ! $manifest || ! isset( $manifest['schema'], $manifest['version'], $manifest['components'] )
                || 1 !== $manifest['schema'] || $version[1] !== $manifest['version']
                || ! is_array( $manifest['components'] ) ) {
                return false;
            }
            $result = array( 'url' => self::REPOSITORY . '/releases/tag/' . $release['tag_name'] );
            foreach ( array( 'plugin' => 'web-svepomoci-plugin.zip', 'theme' => 'web-svepomoci-sablona.zip' ) as $kind => $filename ) {
                $component = isset( $manifest['components'][ $kind ] ) ? $manifest['components'][ $kind ] : null;
                if ( ! is_array( $component ) || ! isset( $component['version'], $component['requires'], $component['requires_php'] )
                    || $version[1] !== $component['version'] ) {
                    return false;
                }
                foreach ( array( 'requires', 'requires_php' ) as $requirement ) {
                    if ( ! is_string( $component[ $requirement ] )
                        || ! preg_match( '/^\d+\.\d+(?:\.\d+)?$/', $component[ $requirement ] ) ) {
                        return false;
                    }
                }
                $result[ $kind ] = array(
                    'version' => $version[1],
                    'requires' => $component['requires'],
                    'requires_php' => $component['requires_php'],
                    'package' => $assets[ $filename ],
                    'url' => $result['url'],
                );
            }
            set_site_transient( self::CACHE, array( 'release' => $result ), HOUR_IN_SECONDS );
            return $result;
        }

        public static function plugin_update( $update, $data, $file, $locales ) {
            if ( self::PLUGIN !== $file || ! isset( $data['UpdateURI'] ) || self::REPOSITORY !== $data['UpdateURI'] ) {
                return $update;
            }
            $release = self::release();
            if ( ! $release ) {
                return $update;
            }
            return array_merge( $release['plugin'], array( 'id' => self::REPOSITORY, 'slug' => 'ai-web-studio' ) );
        }

        public static function theme_update( $update, $data, $stylesheet, $locales ) {
            if ( self::THEME !== $stylesheet || ! isset( $data['UpdateURI'] ) || self::REPOSITORY !== $data['UpdateURI'] ) {
                return $update;
            }
            $release = self::release();
            if ( ! $release ) {
                return $update;
            }
            return array_merge( $release['theme'], array( 'id' => self::REPOSITORY, 'theme' => self::THEME ) );
        }

        public static function plugin_details( $result, $action, $args ) {
            if ( 'plugin_information' !== $action || empty( $args->slug ) || 'ai-web-studio' !== $args->slug ) {
                return $result;
            }
            $release = self::release();
            if ( ! $release ) {
                return $result;
            }
            return (object) array(
                'name' => 'web-svepomoci-plugin',
                'slug' => 'ai-web-studio',
                'version' => $release['plugin']['version'],
                'author' => 'DanHutar',
                'homepage' => self::REPOSITORY,
                'requires' => $release['plugin']['requires'],
                'requires_php' => $release['plugin']['requires_php'],
                'download_link' => $release['plugin']['package'],
                'sections' => array(
                    'description' => 'HTML, CSS a JavaScript pro stránky, společná hlavička a patička, menu a SEO.',
                    'changelog' => '<p><a href="' . esc_url( $release['url'] ) . '">Změny této verze na GitHubu</a></p>',
                ),
            );
        }
    }
}
WSP_GitHub_Updates::init();

