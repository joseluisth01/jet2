<?php
/**
 * Clase principal del panel de administración.
 * Registra menús, carga assets y gestiona las vistas admin.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'handle_actions' ) );
    }

    /**
     * Registra el menú principal y submenús.
     */
    public function register_menus() {
        // Menú principal
        add_menu_page(
            __( 'Reservas Agua', 'tictac-reservas-agua' ),
            __( 'Reservas Agua', 'tictac-reservas-agua' ),
            'manage_options',
            'ttra-dashboard',
            array( $this, 'page_dashboard' ),
            'dashicons-palmtree',
            30
        );

        // Dashboard
        add_submenu_page(
            'ttra-dashboard',
            __( 'Dashboard', 'tictac-reservas-agua' ),
            __( 'Dashboard', 'tictac-reservas-agua' ),
            'manage_options',
            'ttra-dashboard',
            array( $this, 'page_dashboard' )
        );

        // Reservas
        add_submenu_page(
            'ttra-dashboard',
            __( 'Reservas', 'tictac-reservas-agua' ),
            __( 'Reservas', 'tictac-reservas-agua' ),
            'manage_options',
            'ttra-reservas',
            array( $this, 'page_reservas' )
        );

        // Calendario
        add_submenu_page(
            'ttra-dashboard',
            __( 'Calendario', 'tictac-reservas-agua' ),
            __( 'Calendario', 'tictac-reservas-agua' ),
            'manage_options',
            'ttra-calendario',
            array( $this, 'page_calendario' )
        );

        // Categorías
        add_submenu_page(
            'ttra-dashboard',
            __( 'Categorías', 'tictac-reservas-agua' ),
            __( 'Categorías', 'tictac-reservas-agua' ),
            'manage_options',
            'ttra-categorias',
            array( $this, 'page_categorias' )
        );

        // Actividades
        add_submenu_page(
            'ttra-dashboard',
            __( 'Actividades', 'tictac-reservas-agua' ),
            __( 'Actividades', 'tictac-reservas-agua' ),
            'manage_options',
            'ttra-actividades',
            array( $this, 'page_actividades' )
        );

        // Horarios
        add_submenu_page(
            'ttra-dashboard',
            __( 'Horarios', 'tictac-reservas-agua' ),
            __( 'Horarios', 'tictac-reservas-agua' ),
            'manage_options',
            'ttra-horarios',
            array( $this, 'page_horarios' )
        );

        // Bloqueos / Fechas especiales
        add_submenu_page(
            'ttra-dashboard',
            __( 'Bloqueos', 'tictac-reservas-agua' ),
            __( 'Bloqueos', 'tictac-reservas-agua' ),
            'manage_options',
            'ttra-bloqueos',
            array( $this, 'page_bloqueos' )
        );

        // Cupones
        add_submenu_page(
            'ttra-dashboard',
            __( 'Cupones', 'tictac-reservas-agua' ),
            __( 'Cupones', 'tictac-reservas-agua' ),
            'manage_options',
            'ttra-cupones',
            array( $this, 'page_cupones' )
        );

        // Emails Log
        add_submenu_page(
            'ttra-dashboard',
            __( 'Emails', 'tictac-reservas-agua' ),
            __( 'Emails', 'tictac-reservas-agua' ),
            'manage_options',
            'ttra-emails',
            array( $this, 'page_emails' )
        );

        // Ajustes
        add_submenu_page(
            'ttra-dashboard',
            __( 'Ajustes', 'tictac-reservas-agua' ),
            __( 'Ajustes', 'tictac-reservas-agua' ),
            'manage_options',
            'ttra-ajustes',
            array( $this, 'page_ajustes' )
        );
    }

    /**
     * Assets del admin.
     */
    public function enqueue_assets( $hook ) {
        // Solo en páginas del plugin
        if ( strpos( $hook, 'ttra-' ) === false ) return;

        wp_enqueue_style(
            'ttra-admin-css',
            TTRA_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            TTRA_VERSION
        );

        // CSS extra del calendario
        wp_enqueue_style(
            'ttra-calendario-css',
            TTRA_PLUGIN_URL . 'admin/css/calendario.css',
            array( 'ttra-admin-css' ),
            TTRA_VERSION
        );

        wp_enqueue_script(
            'ttra-admin-js',
            TTRA_PLUGIN_URL . 'admin/js/admin.js',
            array( 'jquery', 'wp-util' ),
            TTRA_VERSION,
            true
        );

        wp_localize_script( 'ttra-admin-js', 'ttra_admin', array(
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'rest_url'   => rest_url( 'ttra/v1/' ),
            'nonce'      => wp_create_nonce( 'ttra_admin_nonce' ),
            'rest_nonce' => wp_create_nonce( 'wp_rest' ),
        ) );

        // Media uploader para imágenes
        wp_enqueue_media();
    }

    /**
     * Gestiona acciones POST del admin (guardar settings, CRUD, etc.)
     */
    public function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        if ( empty( $_POST['ttra_action'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['ttra_nonce'] ?? '', 'ttra_admin_nonce' ) ) return;

        $action = sanitize_text_field( $_POST['ttra_action'] );

        switch ( $action ) {
            case 'save_settings':
                $this->save_settings();
                break;
            case 'save_categoria':
                $this->save_categoria();
                break;
            case 'delete_categoria':
                $this->delete_categoria();
                break;
            case 'save_actividad':
                $this->save_actividad();
                break;
            case 'delete_actividad':
                $this->delete_actividad();
                break;
            case 'save_horario':
                $this->save_horario();
                break;
            case 'save_bloqueo':
                $this->save_bloqueo();
                break;
            case 'delete_bloqueo':
                $this->delete_bloqueo();
                break;
            case 'save_cupon':
                $this->save_cupon();
                break;
            case 'delete_cupon':
                $this->delete_cupon();
                break;
            case 'cambiar_estado_reserva':
                $this->cambiar_estado_reserva();
                break;
            case 'export_csv':
                TTRA_Export::reservas_csv( $_POST );
                break;
        }
    }

    /* ══════════════════════════════════════════
       PÁGINAS / VISTAS
       ══════════════════════════════════════════ */

    public function page_dashboard() {
        $stats = TTRA_Reserva::stats( '30days' );
        include TTRA_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function page_reservas() {
        $args = array(
            'estado'      => sanitize_text_field( $_GET['estado'] ?? '' ),
            'buscar'      => sanitize_text_field( $_GET['buscar'] ?? '' ),
            'fecha_desde' => sanitize_text_field( $_GET['fecha_desde'] ?? '' ),
            'fecha_hasta' => sanitize_text_field( $_GET['fecha_hasta'] ?? '' ),
            'page'        => max( 1, intval( $_GET['paged'] ?? 1 ) ),
        );
        $resultado = TTRA_Reserva::get_listado( $args );

        // ¿Ver detalle de una reserva?
        if ( ! empty( $_GET['reserva_id'] ) ) {
            $reserva = TTRA_Reserva::get_by_id( intval( $_GET['reserva_id'] ) );
            $lineas  = TTRA_Reserva::get_lineas( $reserva->id );
            $pagos   = TTRA_Pago::get_by_reserva( $reserva->id );
            include TTRA_PLUGIN_DIR . 'admin/views/reserva-detalle.php';
            return;
        }

        include TTRA_PLUGIN_DIR . 'admin/views/reservas.php';
    }

    public function page_calendario() {
        include TTRA_PLUGIN_DIR . 'admin/views/calendario.php';
    }

    public function page_categorias() {
        $categorias = TTRA_Categoria::get_all( false );
        $editando   = null;
        if ( ! empty( $_GET['editar'] ) ) {
            $editando = TTRA_Categoria::get_by_id( intval( $_GET['editar'] ) );
        }
        include TTRA_PLUGIN_DIR . 'admin/views/categorias.php';
    }

    public function page_actividades() {
        $actividades = TTRA_Actividad::get_all( false );
        $categorias  = TTRA_Categoria::get_all();
        $editando    = null;
        if ( ! empty( $_GET['editar'] ) ) {
            $editando = TTRA_Actividad::get_by_id( intval( $_GET['editar'] ) );
        }
        include TTRA_PLUGIN_DIR . 'admin/views/actividades.php';
    }

    public function page_horarios() {
        $actividades  = TTRA_Actividad::get_all();
        $actividad_id = intval( $_GET['actividad_id'] ?? 0 );
        $horarios     = $actividad_id ? TTRA_Horario::get_by_actividad( $actividad_id ) : array();
        include TTRA_PLUGIN_DIR . 'admin/views/horarios.php';
    }

    public function page_bloqueos() {
        $actividades = TTRA_Actividad::get_all();
        $bloqueos    = TTRA_DB::get_all( 'bloqueos', '', 'fecha', 'ASC' );
        include TTRA_PLUGIN_DIR . 'admin/views/bloqueos.php';
    }

    public function page_cupones() {
        $cupones  = TTRA_Cupon::get_all();
        $editando = null;
        if ( ! empty( $_GET['editar'] ) ) {
            $editando = TTRA_Cupon::get_by_id( intval( $_GET['editar'] ) );
        }
        include TTRA_PLUGIN_DIR . 'admin/views/cupones.php';
    }

    public function page_emails() {
        global $wpdb;
        $page  = max( 1, intval( $_GET['paged'] ?? 1 ) );
        $table = TTRA_DB::table( 'email_log' );
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT 20 OFFSET %d",
            ( $page - 1 ) * 20
        ) );
        include TTRA_PLUGIN_DIR . 'admin/views/emails.php';
    }

    public function page_ajustes() {
        $settings = TTRA_Settings::get_all();
        $tab      = sanitize_text_field( $_GET['tab'] ?? 'general' );
        include TTRA_PLUGIN_DIR . 'admin/views/ajustes.php';
    }

    /* ══════════════════════════════════════════
       ACCIONES CRUD
       ══════════════════════════════════════════ */

    private function save_settings() {
        $settings_actuales = get_option( 'ttra_settings', array() );

        // Settings que llegan en el POST del tab actual
        $fields = $_POST['ttra_settings'] ?? array();

        // Empezamos con los settings actuales y los sobrescribimos con los del POST
        $settings = $settings_actuales;

        foreach ( $fields as $key => $value ) {
            $settings[ sanitize_key( $key ) ] = sanitize_text_field( $value );
        }

        // Checkboxes por tab: los que NO lleguen en el POST se ponen a 0
        // Solo afecta al tab que se está guardando (ttra_tab_actual)
        $tab_actual = sanitize_text_field( $_POST['ttra_tab_actual'] ?? '' );

        $checkboxes_por_tab = array(
            'general'    => array(),
            'reservas'   => array( 'confirmacion_auto' ),
            'redsys'     => array(),
            'emails'     => array(
                'email_confirmacion', 'email_recordatorio',
                'email_cancelacion', 'email_admin_nueva',
            ),
            'apariencia' => array(
                'mostrar_cancelacion_gratuita', 'mostrar_pago_seguro',
                'mostrar_sin_fianza', 'mostrar_equipo_seguridad',
            ),
            'pagos'      => array(
                'pago_tarjeta', 'pago_bizum', 'pago_google_pay', 'pago_apple_pay',
            ),
            'descuentos' => array(
                'descuento_auto_1_activo',
                'descuento_auto_2_activo',
                'descuento_auto_3_activo',
            ),
        );

        // Si sabemos qué tab se guardó, ponemos a 0 los checkboxes no enviados de ese tab
        if ( $tab_actual && isset( $checkboxes_por_tab[ $tab_actual ] ) ) {
            foreach ( $checkboxes_por_tab[ $tab_actual ] as $cb ) {
                $settings[ $cb ] = isset( $fields[ $cb ] ) ? 1 : 0;
            }
        }

        TTRA_Settings::save_all( $settings );
        wp_redirect( add_query_arg( 'msg', 'saved', wp_get_referer() ) );
        exit;
    }

    private function save_categoria() {
        $id   = intval( $_POST['categoria_id'] ?? 0 );
        $data = array(
            'nombre'      => sanitize_text_field( $_POST['nombre'] ),
            'slug'        => sanitize_title( $_POST['slug'] ?: $_POST['nombre'] ),
            'descripcion' => sanitize_textarea_field( $_POST['descripcion'] ?? '' ),
            'icono'       => sanitize_text_field( $_POST['icono'] ?? '' ),
            'orden'       => intval( $_POST['orden'] ?? 0 ),
            'activa'      => isset( $_POST['activa'] ) ? 1 : 0,
        );

        if ( $id ) {
            TTRA_Categoria::update( $id, $data );
        } else {
            TTRA_Categoria::create( $data );
        }

        wp_redirect( admin_url( 'admin.php?page=ttra-categorias&msg=saved' ) );
        exit;
    }

    private function delete_categoria() {
        $id = intval( $_POST['categoria_id'] );
        TTRA_Categoria::delete( $id );
        wp_redirect( admin_url( 'admin.php?page=ttra-categorias&msg=deleted' ) );
        exit;
    }

    private function save_actividad() {
        $id   = intval( $_POST['actividad_id'] ?? 0 );
        $data = array(
            'categoria_id'         => intval( $_POST['categoria_id'] ),
            'nombre'               => sanitize_text_field( $_POST['nombre'] ),
            'slug'                 => sanitize_title( $_POST['slug'] ?: $_POST['nombre'] ),
            'subtipo'              => sanitize_text_field( $_POST['subtipo'] ?? '' ),
            'descripcion'          => sanitize_textarea_field( $_POST['descripcion'] ?? '' ),
            'duracion_minutos'     => intval( $_POST['duracion_minutos'] ),
            'precio_base'          => floatval( $_POST['precio_base'] ),
            'precio_tipo'          => sanitize_text_field( $_POST['precio_tipo'] ?? 'fijo' ),

            // Precio adicional por persona
            'precio_pax'           => ( isset( $_POST['precio_pax'] ) && $_POST['precio_pax'] !== '' )
                                       ? floatval( $_POST['precio_pax'] )
                                       : null,

            'min_personas'         => intval( $_POST['min_personas'] ?? 1 ),
            'max_personas'         => intval( $_POST['max_personas'] ?? 10 ),
            'max_sesiones'         => intval( $_POST['max_sesiones'] ?? 5 ),
            'imagen_id'            => intval( $_POST['imagen_id'] ?? 0 ),
            'icono'                => sanitize_text_field( $_POST['icono'] ?? '' ),

            // Premium
            'premium'              => isset( $_POST['premium'] ) ? 1 : 0,

            'requiere_equipo'      => isset( $_POST['requiere_equipo'] ) ? 1 : 0,
            'cancelacion_gratuita' => isset( $_POST['cancelacion_gratuita'] ) ? 1 : 0,
            'requiere_fianza'      => isset( $_POST['requiere_fianza'] ) ? 1 : 0,
            'importe_fianza'       => floatval( $_POST['importe_fianza'] ?? 0 ),
            'orden'                => intval( $_POST['orden'] ?? 0 ),
            'activa'               => isset( $_POST['activa'] ) ? 1 : 0,
        );

        if ( $id ) {
            TTRA_Actividad::update( $id, $data );
        } else {
            TTRA_Actividad::create( $data );
        }

        wp_redirect( admin_url( 'admin.php?page=ttra-actividades&msg=saved' ) );
        exit;
    }

    private function delete_actividad() {
        $id = intval( $_POST['actividad_id'] );
        TTRA_Actividad::delete( $id );
        TTRA_Horario::delete_by_actividad( $id );
        wp_redirect( admin_url( 'admin.php?page=ttra-actividades&msg=deleted' ) );
        exit;
    }

    private function save_horario() {
        $actividad_id = intval( $_POST['actividad_id'] );

        // Borrar todos los horarios existentes de esta actividad
        TTRA_Horario::delete_by_actividad( $actividad_id );

        $horarios = $_POST['horarios'] ?? array();

        foreach ( $horarios as $h ) {
            if ( empty( $h['hora_inicio'] ) || empty( $h['hora_fin'] ) ) continue;

            $dias = isset( $h['dias'] ) ? (array) $h['dias'] : array();
            if ( empty( $dias ) ) continue;

            // Crear un registro por día
            foreach ( $dias as $dia ) {
                TTRA_Horario::create( array(
                    'actividad_id'      => $actividad_id,
                    'dia_semana'        => intval( $dia ),
                    'hora_inicio'       => sanitize_text_field( $h['hora_inicio'] ),
                    'hora_fin'          => sanitize_text_field( $h['hora_fin'] ),
                    'intervalo_minutos' => intval( $h['intervalo_minutos'] ?? 30 ),
                    'plazas_por_slot'   => intval( $h['plazas_por_slot'] ?? 10 ),
                    'activo'            => isset( $h['activo'] ) ? 1 : 0,
                ) );
            }
        }

        wp_redirect( admin_url( 'admin.php?page=ttra-horarios&actividad_id=' . $actividad_id . '&msg=saved' ) );
        exit;
    }

    private function save_bloqueo() {
        TTRA_DB::insert( 'bloqueos', array(
            'actividad_id' => intval( $_POST['actividad_id'] ) ?: null,
            'fecha'        => sanitize_text_field( $_POST['fecha'] ),
            'motivo'       => sanitize_text_field( $_POST['motivo'] ?? '' ),
        ) );
        wp_redirect( admin_url( 'admin.php?page=ttra-bloqueos&msg=saved' ) );
        exit;
    }

    private function delete_bloqueo() {
        TTRA_DB::delete( 'bloqueos', array( 'id' => intval( $_POST['bloqueo_id'] ) ) );
        wp_redirect( admin_url( 'admin.php?page=ttra-bloqueos&msg=deleted' ) );
        exit;
    }

    private function save_cupon() {
        $id   = intval( $_POST['cupon_id'] ?? 0 );
        $data = array(
            'codigo'       => strtoupper( sanitize_text_field( $_POST['codigo'] ) ),
            'tipo'         => sanitize_text_field( $_POST['tipo'] ),
            'valor'        => floatval( $_POST['valor'] ),
            'uso_maximo'   => intval( $_POST['uso_maximo'] ?? 0 ),
            'fecha_inicio' => sanitize_text_field( $_POST['fecha_inicio'] ?? '' ) ?: null,
            'fecha_fin'    => sanitize_text_field( $_POST['fecha_fin'] ?? '' ) ?: null,
            'activo'       => isset( $_POST['activo'] ) ? 1 : 0,
        );

        if ( $id ) {
            TTRA_Cupon::update( $id, $data );
        } else {
            TTRA_Cupon::create( $data );
        }

        wp_redirect( admin_url( 'admin.php?page=ttra-cupones&msg=saved' ) );
        exit;
    }

    private function delete_cupon() {
        TTRA_Cupon::delete( intval( $_POST['cupon_id'] ) );
        wp_redirect( admin_url( 'admin.php?page=ttra-cupones&msg=deleted' ) );
        exit;
    }

    private function cambiar_estado_reserva() {
        $id     = intval( $_POST['reserva_id'] );
        $estado = sanitize_text_field( $_POST['nuevo_estado'] );
        TTRA_Reserva::update( $id, array( 'estado' => $estado ) );

        if ( $estado === 'cancelada' ) {
            $reserva = TTRA_Reserva::get_by_id( $id );
            $mailer  = new TTRA_Mailer();
            $mailer->enviar_cancelacion( $reserva );
        }

        wp_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=ttra-reservas' ) );
        exit;
    }
}