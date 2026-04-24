<?php
/**
 * Clase principal del plugin. Orquesta todo.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Plugin {

    protected $admin;
    protected $public;

    public function __construct() {
        $this->load_dependencies();
    }

    private function load_dependencies() {
        // Core
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-db.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-helpers.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-settings.php';

        // Models
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-categoria.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-actividad.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-horario.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-reserva.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-pago.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-cupon.php';

        // Servicios
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-mailer.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-redsys.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-calendario.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-cron.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-pdf-generator.php';
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-export.php';

        // Admin
        if ( is_admin() ) {
            require_once TTRA_PLUGIN_DIR . 'admin/class-ttra-admin.php';
        }

        // Frontend
        require_once TTRA_PLUGIN_DIR . 'public/class-ttra-public.php';
    }

    public function run() {
        // Internacionalización
        add_action( 'init', array( $this, 'load_textdomain' ) );

        // Admin
        if ( is_admin() ) {
            $this->admin = new TTRA_Admin();
        }

        // Frontend
        $this->public = new TTRA_Public();

        // Cron
        $cron = new TTRA_Cron();
        $cron->register();

        // REST API endpoints
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'tictac-reservas-agua',
            false,
            dirname( TTRA_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    public function register_rest_routes() {
        require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-rest-api.php';
        $api = new TTRA_REST_API();
        $api->register_routes();
    }
}
