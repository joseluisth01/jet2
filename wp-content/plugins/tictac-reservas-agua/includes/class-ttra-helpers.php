<?php
/**
 * Funciones de utilidad del plugin.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Helpers {

    /**
     * Genera un código único de reserva: TTRA-XXXXXX
     */
    public static function generar_codigo_reserva() {
        do {
            $codigo = 'RES-' . strtoupper( wp_generate_password( 8, false, false ) );
            $existe = TTRA_DB::count( 'reservas', "codigo_reserva = '$codigo'" );
        } while ( $existe > 0 );
        return $codigo;
    }

    /**
     * Formatea un precio en euros.
     */
    public static function formato_precio( $precio ) {
        return number_format( (float) $precio, 2, ',', '.' ) . ' €';
    }

    /**
     * Obtiene un setting del plugin.
     */
    public static function get_setting( $key, $default = '' ) {
        $settings = get_option( 'ttra_settings', array() );
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    /**
     * Actualiza un setting.
     */
    public static function update_setting( $key, $value ) {
        $settings = get_option( 'ttra_settings', array() );
        $settings[ $key ] = $value;
        update_option( 'ttra_settings', $settings );
    }

    /**
     * Formatea fecha para mostrar.
     */
    public static function formato_fecha( $fecha, $formato = 'd/m/Y' ) {
        if ( empty( $fecha ) ) return '-';
        return date_i18n( $formato, strtotime( $fecha ) );
    }

    /**
     * Formatea hora para mostrar.
     */
    public static function formato_hora( $hora ) {
        if ( empty( $hora ) ) return '-';
        return date( 'H:i', strtotime( $hora ) );
    }

    /**
     * Días de la semana en español.
     */
    public static function dias_semana() {
        return array(
            0 => __( 'Lunes', 'tictac-reservas-agua' ),
            1 => __( 'Martes', 'tictac-reservas-agua' ),
            2 => __( 'Miércoles', 'tictac-reservas-agua' ),
            3 => __( 'Jueves', 'tictac-reservas-agua' ),
            4 => __( 'Viernes', 'tictac-reservas-agua' ),
            5 => __( 'Sábado', 'tictac-reservas-agua' ),
            6 => __( 'Domingo', 'tictac-reservas-agua' ),
        );
    }

    /**
     * Estados de reserva con labels y colores.
     */
    public static function estados_reserva() {
        return array(
            'pendiente'  => array( 'label' => __( 'Pendiente', 'tictac-reservas-agua' ),  'color' => '#FFA500' ),
            'confirmada' => array( 'label' => __( 'Confirmada', 'tictac-reservas-agua' ), 'color' => '#2196F3' ),
            'pagada'     => array( 'label' => __( 'Pagada', 'tictac-reservas-agua' ),     'color' => '#4CAF50' ),
            'cancelada'  => array( 'label' => __( 'Cancelada', 'tictac-reservas-agua' ),  'color' => '#F44336' ),
            'completada' => array( 'label' => __( 'Completada', 'tictac-reservas-agua' ), 'color' => '#9E9E9E' ),
            'no_show'    => array( 'label' => __( 'No Show', 'tictac-reservas-agua' ),    'color' => '#795548' ),
        );
    }

    /**
     * Comprueba si una fecha está bloqueada para una actividad.
     */
    public static function fecha_bloqueada( $actividad_id, $fecha ) {
        global $wpdb;
        $table = TTRA_DB::table( 'bloqueos' );
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE fecha = %s AND (actividad_id = %d OR actividad_id IS NULL)",
            $fecha,
            $actividad_id
        ) );
        return $count > 0;
    }

    /**
     * Sanitiza un array de datos de formulario.
     */
    public static function sanitize_form_data( $data, $fields ) {
        $sanitized = array();
        foreach ( $fields as $key => $type ) {
            $value = isset( $data[ $key ] ) ? $data[ $key ] : '';
            switch ( $type ) {
                case 'email':
                    $sanitized[ $key ] = sanitize_email( $value );
                    break;
                case 'int':
                    $sanitized[ $key ] = intval( $value );
                    break;
                case 'float':
                    $sanitized[ $key ] = floatval( $value );
                    break;
                case 'textarea':
                    $sanitized[ $key ] = sanitize_textarea_field( $value );
                    break;
                case 'url':
                    $sanitized[ $key ] = esc_url_raw( $value );
                    break;
                default:
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;
            }
        }
        return $sanitized;
    }

    /**
     * Devuelve la URL de la página de reservas.
     */
    public static function get_reservas_url() {
        $page_id = get_option( 'ttra_page_reservas' );
        return $page_id ? get_permalink( $page_id ) : home_url( '/reservas/' );
    }
}
