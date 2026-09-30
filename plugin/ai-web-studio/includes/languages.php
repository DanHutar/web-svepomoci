<?php
/** Shared language preferences. Keep the theme copy identical. */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WSP_Languages', false ) ) {
    final class WSP_Languages {
        private static $domains = array();

        public static function register( $domain, $directory ) {
            self::$domains[ $domain ] = $directory;
            if ( ! isset( self::$domains['web-svepomoci'] ) ) { self::$domains['web-svepomoci'] = $directory; }
        }

        public static function settings() {
            $stored = get_option( 'wsp_languages', false );
            // Only pre-existing plugin data triggers migration; activation initializes before creating parts.
            $legacy = false !== get_option( 'aiwp_part_ids', false ) || false !== get_option( 'aiwp_settings', false ) || (bool) get_option( 'theme_mods_ai-web', array() );
            $default = $legacy ? 'cs' : 'en';
            $result = array();
            foreach ( array( 'ui', 'public', 'content' ) as $scope ) {
                $value = is_array( $stored ) && isset( $stored[ $scope ] ) ? $stored[ $scope ] : $default;
                $result[ $scope ] = in_array( $value, array( 'en', 'cs' ), true ) ? $value : 'en';
            }
            return $result;
        }

        public static function language( $scope = '' ) {
            $scope = $scope ?: ( is_admin() ? 'ui' : 'public' );
            $settings = self::settings();
            return isset( $settings[ $scope ] ) ? $settings[ $scope ] : 'en';
        }

        public static function initialize() {
            if ( false === get_option( 'wsp_languages', false ) ) { add_option( 'wsp_languages', self::settings(), '', false ); }
            $locale = 'cs' === self::language() ? 'cs_CZ' : 'en_US';
            foreach ( self::$domains as $domain => $directory ) {
                load_textdomain( $domain, $directory . '/' . $domain . '-' . $locale . '.mo', $locale );
            }
        }

        public static function sanitize( $input ) {
            $old = self::settings();
            if ( ! current_user_can( 'manage_options' ) || ! is_array( $input ) ) { return $old; }
            $result = array();
            foreach ( array( 'ui', 'public', 'content' ) as $scope ) {
                if ( ! isset( $input[ $scope ] ) || ! in_array( $input[ $scope ], array( 'en', 'cs' ), true ) ) {
                    add_settings_error( 'wsp_languages', 'invalid', __( 'Choose English or Czech for every language setting.', 'web-svepomoci' ) );
                    return $old;
                }
                $result[ $scope ] = $input[ $scope ];
            }
            return $result;
        }

        public static function page() {
            if ( ! current_user_can( 'manage_options' ) ) { return; }
            $settings = self::settings();
            ?>
            <div class="wrap" lang="<?php echo esc_attr( self::language( 'ui' ) ); ?>">
            <h1><?php esc_html_e( 'Language', 'web-svepomoci' ); ?></h1>
            <p><?php esc_html_e( 'These preferences apply to both ByYourself components. They do not change the WordPress dashboard language or translate saved pages, code, menus or service descriptions.', 'web-svepomoci' ); ?></p>
            <?php settings_errors( 'wsp_languages' ); ?>
            <form action="options.php" method="post">
            <?php settings_fields( 'wsp_languages' ); ?>
            <table class="form-table" role="presentation"><tbody>
            <?php foreach ( array( 'ui' => __( 'Plugin and theme interface', 'web-svepomoci' ), 'public' => __( 'Public labels and cookie controls', 'web-svepomoci' ), 'content' => __( 'Requested AI content language', 'web-svepomoci' ) ) as $scope => $label ) : ?>
                <tr><th scope="row"><label for="wsp-language-<?php echo esc_attr( $scope ); ?>"><?php echo esc_html( $label ); ?></label></th><td>
                <select id="wsp-language-<?php echo esc_attr( $scope ); ?>" name="wsp_languages[<?php echo esc_attr( $scope ); ?>]">
                <option value="en" lang="en" <?php selected( $settings[ $scope ], 'en' ); ?>>English</option>
                <option value="cs" lang="cs" <?php selected( $settings[ $scope ], 'cs' ); ?>>Čeština</option>
                </select></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <p><?php esc_html_e( 'English is the default for new installations. Existing configured websites keep Czech when upgrading. After changing public labels, clear the website and CDN caches. Configure external consent plugins separately.', 'web-svepomoci' ); ?></p>
            <?php submit_button( __( 'Save languages', 'web-svepomoci' ) ); ?>
            </form></div>
            <?php
        }

        public static function scripts() {
            if ( ! defined( 'AIWP_DIR' ) ) { return; }
            foreach ( array( 'aiwp-admin', 'aiwp-settings-prompt', 'aiwp-prompts', 'aiwp-consent' ) as $handle ) {
                if ( wp_script_is( $handle, 'enqueued' ) ) {
                    wp_set_script_translations( $handle, 'ai-web-studio', AIWP_DIR . 'languages' );
                }
            }
        }
    }

    add_action( 'after_setup_theme', array( 'WSP_Languages', 'initialize' ), -20 );
    add_action( 'admin_init', function () {
        register_setting( 'wsp_languages', 'wsp_languages', array( 'type' => 'array', 'sanitize_callback' => array( 'WSP_Languages', 'sanitize' ) ) );
    } );
    add_action( 'admin_menu', function () {
        $title = __( 'Language', 'web-svepomoci' );
        if ( defined( 'AIWP_DIR' ) ) { add_submenu_page( 'aiwp', $title, $title, 'manage_options', 'wsp-languages', array( 'WSP_Languages', 'page' ) ); }
        else { add_theme_page( $title, $title, 'manage_options', 'wsp-languages', array( 'WSP_Languages', 'page' ) ); }
    }, 30 );
    add_action( 'admin_enqueue_scripts', array( 'WSP_Languages', 'scripts' ), 100 );
    add_action( 'wp_enqueue_scripts', array( 'WSP_Languages', 'scripts' ), 100 );
    add_filter( 'load_script_translation_file', function ( $file, $handle, $domain ) {
        if ( 'ai-web-studio' !== $domain || ! defined( 'AIWP_DIR' ) || ! in_array( $handle, array( 'aiwp-admin', 'aiwp-settings-prompt', 'aiwp-prompts', 'aiwp-consent' ), true ) ) { return $file; }
        return AIWP_DIR . 'languages/' . $handle . '-' . WSP_Languages::language() . '.json';
    }, 10, 3 );
}
