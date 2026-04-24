<?php
/**
 * Fired when the plugin is uninstalled.
 * Drops all custom tables and removes options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . 'ttra_';

$tables = array(
    'email_log', 'pagos', 'reserva_lineas', 'reservas',
    'bloqueos', 'horarios', 'actividades', 'categorias', 'cupones',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$prefix}{$table}" );
}

delete_option( 'ttra_settings' );
delete_option( 'ttra_db_version' );
delete_option( 'ttra_page_reservas' );
