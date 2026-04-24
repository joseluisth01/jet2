<?php
/**
 * Fired during plugin deactivation.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Deactivator {

    public static function deactivate() {
        // Limpiar cron jobs
        wp_clear_scheduled_hook( 'ttra_cron_enviar_recordatorios' );
        wp_clear_scheduled_hook( 'ttra_cron_limpiar_pendientes' );
        wp_clear_scheduled_hook( 'ttra_cron_marcar_completadas' );

        flush_rewrite_rules();
    }
}
