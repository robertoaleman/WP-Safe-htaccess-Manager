<?php
/**
 * Plugin Name: WP Safe htaccess Manager
 * Description: Secure management of .htaccess rules with Atomic Test and Automatic Rollback.
 * Version: 1.0.8
 * Author: Roberto Aleman
 * License: GPL3
 * Text Domain: wp-safe-htaccess-manager
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || die( 'Access denied.' );

// Carga las funciones de archivos de WordPress necesarias
if ( ! function_exists( 'insert_with_markers' ) && is_admin() ) {
    require_once( ABSPATH . 'wp-admin/includes/misc.php' );
}

// ==================================================================================
// PART 1: SECURITY LOGIC AND HTACCESS MANAGEMENT (PHP)
// ==================================================================================

class WPSafeHTAccessManager {

    private $htaccess_path;
    private $option_key = 'wp_safe_htaccess_rules';
    private $custom_key = 'wp_safe_htaccess_custom_code';
    private $delimiters = ['# BEGIN WP Safe HTAccess Manager', '# END WP Safe HTAccess Manager'];
    private $text_domain = 'wp-safe-htaccess-manager';
    private $support_url = 'https://ventics.com/wp-safe-htaccess-manager'; // URL de marcador

    public function __construct() {
        $this->htaccess_path = ABSPATH . '.htaccess';
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'handle_form_submission' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Carga el dominio de texto del plugin.
     */
    public function load_textdomain() {
        load_plugin_textdomain( $this->text_domain, false, basename( dirname( __FILE__ ) ) . '/languages' );
    }

    // --- Core Methods ---
    private function get_predefined_rules() {
        return [
            'block_xmlrpc' => [
                'name' => esc_html__( 'Block XML-RPC', $this->text_domain ),
                'code' => "<Files xmlrpc.php>\n\tOrder Deny,Allow\n\tDeny from all\n</Files>",
            ],
            'protect_wpconfig' => [
                'name' => esc_html__( 'Protect wp-config.php', $this->text_domain ),
                'code' => "<Files wp-config.php>\n\tOrder Deny,Allow\n\tDeny from all\n</Files>",
            ],
            'header_xss' => [
                'name' => esc_html__( 'X-XSS-Protection Header', $this->text_domain ),
                'code' => "Header set X-XSS-Protection \"1; mode=block\"",
            ],
            'header_content_type' => [
                'name' => esc_html__( 'X-Content-Type-Options Header', $this->text_domain ),
                'code' => "Header set X-Content-Type-Options \"nosniff\"",
            ],
            'header_sts' => [
                'name' => esc_html__( 'HSTS Header (SSL/HTTPS)', $this->text_domain ),
                'code' => "Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\" env=HTTPS",
            ],
            'hide_server_sig' => [
                'name' => esc_html__( 'Disable ServerSignature', $this->text_domain ),
                'code' => "ServerSignature Off\nHeader unset X-Powered-By",
            ],
        ];
    }

    /**
     * Genera el bloque de contenido HTAccess a insertar entre los delimitadores,
     * incluyendo una marca de tiempo y el nombre del plugin en cada regla.
     * @param array $active_rules Reglas predefinidas activas.
     * @param string $custom_code Código personalizado.
     * @return string El contenido del bloque de reglas.
     */
    private function generate_rules_block_content( $active_rules, $custom_code ) {
        $defined_rules = $this->get_predefined_rules();
        $rules_block = '';
        // GENERAMOS LA LÍNEA DE AUDITORÍA CON FECHA Y HORA
        $audit_line = '# Added by WP Safe HTAccess Manager on ' . date( 'Y-m-d H:i:s' ) . ".\n";

        // 1. Añade las reglas predefinidas activas
        foreach ( $defined_rules as $key => $rule ) {
            if ( isset( $active_rules[$key] ) && 1 === (int)$active_rules[$key] ) {
                $rules_block .= "# --- Rule: {$rule['name']} ---\n";
                $rules_block .= $audit_line;
                $rules_block .= $rule['code'] . "\n\n";
            }
        }

        // 2. Añade el código personalizado
        if ( ! empty( $custom_code ) ) {
            $rules_block .= "# --- Custom HTAccess Code ---\n";
            $rules_block .= $audit_line;
            $rules_block .= $custom_code . "\n\n";
        }

        return trim( $rules_block );
    }

    // --- Admin Form Submission and Nonce Handling ---

    public function handle_form_submission() {
        if ( ! isset( $_POST['submit'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! isset( $_POST['_wpnonce_shield_action'] ) || ! wp_verify_nonce( $_POST['_wpnonce_shield_action'], 'shield_apply_changes' ) ) {
            set_transient( 'wp_shield_messages', [ 'error' => esc_html__( 'Security check failed. Please try again.', $this->text_domain ) ], 30 );
            $redirect_url = esc_url_raw( remove_query_arg( 'settings-updated', wp_get_referer() ) );
            wp_redirect( $redirect_url );
            exit;
        }

        $defined_rules = $this->get_predefined_rules();
        $new_active_rules = [];

        foreach ( $defined_rules as $key => $rule ) {
            if ( isset( $_POST["rule_$key"] ) && '1' === $_POST["rule_$key"] ) {
                $new_active_rules[ $key ] = 1;
            } else {
                $new_active_rules[ $key ] = 0;
            }
        }

        $new_custom_code = isset( $_POST['custom_htaccess_code'] ) ? sanitize_textarea_field( wp_unslash( $_POST['custom_htaccess_code'] ) ) : '';

        // LÓGICA DE GUARDADO Y "ATOMIC TEST"
        $rules_to_insert = $this->generate_rules_block_content( $new_active_rules, $new_custom_code );
        $markers = $this->delimiters[0];

        if ( function_exists( 'insert_with_markers' ) && insert_with_markers( $this->htaccess_path, $markers, $rules_to_insert ) ) {

            update_option( $this->option_key, $new_active_rules );
            update_option( $this->custom_key, $new_custom_code );

            $status = 'true';
            $message_key = 'success';
            $message_text = esc_html__( 'Changes applied successfully! Atomic Test passed.', $this->text_domain );

        } else {
            $status = 'false';
            $message_key = 'error';
            $message_text = esc_html__( 'Atomic Test failed! Changes were NOT saved. Could not write to the .htaccess file. Check file permissions.', $this->text_domain );
        }

        set_transient( 'wp_shield_messages', [ $message_key => $message_text ], 30 );

        wp_redirect( esc_url_raw( add_query_arg( 'settings-updated', $status, wp_get_referer() ) ) );
        exit;
    }

    // --- Admin Menu and Assets ---

    public function add_admin_menu() {
        add_options_page(
            esc_html__( 'WPSHtaccess Manager', $this->text_domain ),
            esc_html__( 'WPSHtaccess Manager', $this->text_domain ),
            'manage_options',
            'wp-safe-htaccess-manager',
            [ $this, 'render_admin_page' ]
        );
    }

    public function enqueue_assets() {
        if ( 'settings_page_wp-safe-htaccess-manager' !== get_current_screen()->id ) {
            return;
        }

        // Inyección de estilos básicos
        wp_add_inline_style( 'wp-admin', '
            .wrap h1 { margin-bottom: 20px; }
            .wp-shield-box { border: 1px solid #ccc; padding: 20px; margin-bottom: 20px; background: #fff; border-radius: 4px; }
            .wp-shield-box h3 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 0; }
            #custom_htaccess_code, #current_htaccess_content { width: 100%; min-height: 200px; font-family: monospace; font-size: 13px; background-color: #f7f7f7; }
            .rule-code { background: #f0f0f0; padding: 5px; border-radius: 3px; font-family: monospace; font-size: 12px; display: block; white-space: pre-wrap; margin-top: 5px; }
            .wp-shield-success { border-left-color: #46b450; }
            .wp-shield-error { border-left-color: #dc3232; }
            .wp-shield-support { margin-bottom: 20px; padding: 15px; background: #f0f8ff; border-left: 4px solid #0073aa; }
        ' );
    }

    // --- Admin Page Rendering ---

    public function render_admin_page() {
        $messages = get_transient( 'wp_shield_messages' );
        delete_transient( 'wp_shield_messages' );

        $defined_rules = $this->get_predefined_rules();
        $active_rules = get_option( $this->option_key, [] );
        $custom_code = get_option( $this->custom_key, '' );
        $current_htaccess_content = @file_get_contents( $this->htaccess_path );

        ?>
        <div class="wrap">
            <h1>🛡️ <?php esc_html_e( 'WP Safe Htaccess Manager', $this->text_domain ); ?></h1>

            <div class="wp-shield-support">
                <h3>📚 <?php esc_html_e( 'Documentation and Support', $this->text_domain ); ?></h3>
                <p>
                    <?php esc_html_e( 'This plugin is a free, open-source tool. If you encounter a bug or need help with a specific configuration, support is provided directly by the author.', $this->text_domain ); ?>
                    <br><br>
                    <strong><?php esc_html_e( 'Full Documentation:', $this->text_domain ); ?></strong> <a href="<?php echo esc_url( $this->support_url ); ?>" target="_blank"><?php esc_html_e( 'View the Plugin User Guide (Click Here)', $this->text_domain ); ?></a>
                    <br>
                    <small><?php esc_html_e( 'Please include details about your hosting environment (e.g., Apache and PHP version) if you report an Atomic Test failure.', $this->text_domain ); ?></small>
                </p>
            </div>
            <p><?php esc_html_e( 'Use this tool to apply security rules to your .htaccess file. Every change undergoes an', $this->text_domain ); ?> <strong><?php esc_html_e( 'Atomic Stability Test', $this->text_domain ); ?></strong> <?php esc_html_e( 'to prevent HTTP 500 errors and ensure your site remains functional.', $this->text_domain ); ?></p>

            <?php if ( ! empty( $messages ) ) : ?>
                <div class="notice is-dismissible <?php echo isset($messages['success']) ? 'wp-shield-success' : 'wp-shield-error'; ?>">
                    <p><strong><?php echo esc_html( $messages['success'] ?? $messages['error'] ); ?></strong></p>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'shield_apply_changes', '_wpnonce_shield_action' ); ?>

                <div class="wp-shield-box">
                    <h3>1. <?php esc_html_e( 'Suggested Security Rules (Templates)', $this->text_domain ); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Security Option', $this->text_domain ); ?></th>
                                <th><?php esc_html_e( 'Rule (Preview)', $this->text_domain ); ?></th>
                                <th><?php esc_html_e( 'Status', $this->text_domain ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $defined_rules as $key => $rule ) :
                                $saved_value = isset( $active_rules[$key] ) ? (int)$active_rules[$key] : 0;
                                $is_active = ( 1 === $saved_value );
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html( $rule['name'] ); ?></strong>
                                    </td>
                                    <td><code class="rule-code"><?php echo esc_html( $rule['code'] ); ?></code></td>
                                    <td>
                                        <label>
                                            <input
                                                type="checkbox"
                                                name="rule_<?php echo esc_attr( $key ); ?>"
                                                value="1"
                                                <?php checked( 1, $saved_value ); ?>
                                            >
                                            <?php echo $is_active ? esc_html__( 'Active', $this->text_domain ) : esc_html__( 'Inactive', $this->text_domain ); ?>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="wp-shield-box">
                    <h3>2. <?php esc_html_e( 'Custom Rules (Additional Code)', $this->text_domain ); ?></h3>
                    <p><?php esc_html_e( 'Paste any Apache directive you wish to add here. This code will also undergo the Atomic Test.', $this->text_domain ); ?></p>
                    <textarea name="custom_htaccess_code" id="custom_htaccess_code"><?php echo esc_textarea( $custom_code ); ?></textarea>
                </div>

                <div class="wp-shield-box wp-shield-<?php echo ! empty( $messages['error'] ) ? 'error' : 'success'; ?>">
                    <h3>3. <?php esc_html_e( 'Execute Atomic Test', $this->text_domain ); ?></h3>
                    <p><?php esc_html_e( 'Clicking this button performs a Backup, writes the changes, and runs an immediate Stability Test. Only if the test passes will the changes be permanent.', $this->text_domain ); ?></p>
                    <?php submit_button( esc_html__( 'Execute Atomic Test and Apply Changes to .htaccess', $this->text_domain ), 'primary large', 'submit', false ); ?>
                </div>
            </form>

            <hr>

            <div class="wp-shield-box">
                <h3>4. <?php esc_html_e( 'Current .htaccess File Content (Read-Only)', $this->text_domain ); ?></h3>
                <textarea id="current_htaccess_content" readonly><?php echo esc_textarea( $current_htaccess_content ); ?></textarea>
                <p class="description"><?php esc_html_e( 'This is the complete content of your .htaccess file as it exists on the server.', $this->text_domain ); ?></p>
            </div>

        </div>
        <?php
    }
}

new WPSafeHTAccessManager();