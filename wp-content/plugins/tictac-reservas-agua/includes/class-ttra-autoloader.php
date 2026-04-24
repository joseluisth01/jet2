<?php
/**
 * Autoloader para clases del plugin.
 * Convierte TTRA_Nombre_Clase → includes/class-ttra-nombre-clase.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Autoloader {

    public static function register() {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    public static function autoload( $class ) {
        // Solo clases con prefijo TTRA_
        if ( strpos( $class, 'TTRA_' ) !== 0 ) {
            return;
        }

        $file_name = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

        // Directorios donde buscar
        $directories = array(
            TTRA_PLUGIN_DIR . 'includes/',
            TTRA_PLUGIN_DIR . 'includes/post-types/',
            TTRA_PLUGIN_DIR . 'includes/taxonomies/',
            TTRA_PLUGIN_DIR . 'admin/',
            TTRA_PLUGIN_DIR . 'public/',
        );

        foreach ( $directories as $dir ) {
            $filepath = $dir . $file_name;
            if ( file_exists( $filepath ) ) {
                require_once $filepath;
                return;
            }
        }
    }
}
