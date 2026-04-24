<?php
/**
 * Gestión centralizada de los settings del plugin.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Settings {

    private static $option_key = 'ttra_settings';

    public static function get_all() {
        return get_option( self::$option_key, array() );
    }

    public static function get( $key, $default = '' ) {
        return TTRA_Helpers::get_setting( $key, $default );
    }

    public static function set( $key, $value ) {
        TTRA_Helpers::update_setting( $key, $value );
    }

    public static function save_all( $settings ) {
        update_option( self::$option_key, $settings );
    }

    /**
     * Devuelve los colores configurados como variables CSS.
     */
    public static function get_css_vars() {
        return array(
            '--ttra-primary'   => self::get( 'color_primario', '#003B6F' ),
            '--ttra-secondary' => self::get( 'color_secundario', '#00A0E3' ),
            '--ttra-accent'    => self::get( 'color_acento', '#F47920' ),
            '--ttra-bg'        => self::get( 'color_fondo', '#E8F4FD' ),
        );
    }

    /**
     * Métodos de pago activos.
     */
    public static function metodos_pago_activos() {
        $metodos = array();
        if ( self::get( 'pago_tarjeta', 1 ) ) $metodos[] = 'tarjeta';
        if ( self::get( 'pago_bizum', 0 ) )    $metodos[] = 'bizum';
        if ( self::get( 'pago_google_pay', 0 ) ) $metodos[] = 'google_pay';
        if ( self::get( 'pago_apple_pay', 0 ) )  $metodos[] = 'apple_pay';
        return $metodos;
    }
}
