<?php
/**
 * Tareas programadas (cron) del plugin.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Cron {

    public function register() {
        // Programar eventos si no existen
        if ( ! wp_next_scheduled( 'ttra_cron_enviar_recordatorios' ) ) {
            wp_schedule_event( time(), 'hourly', 'ttra_cron_enviar_recordatorios' );
        }
        if ( ! wp_next_scheduled( 'ttra_cron_limpiar_pendientes' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'ttra_cron_limpiar_pendientes' );
        }
        if ( ! wp_next_scheduled( 'ttra_cron_marcar_completadas' ) ) {
            wp_schedule_event( time(), 'daily', 'ttra_cron_marcar_completadas' );
        }

        add_action( 'ttra_cron_enviar_recordatorios', array( $this, 'enviar_recordatorios' ) );
        add_action( 'ttra_cron_limpiar_pendientes', array( $this, 'limpiar_pendientes' ) );
        add_action( 'ttra_cron_marcar_completadas', array( $this, 'marcar_completadas' ) );
    }

    /**
     * Envía recordatorios X horas antes de la actividad.
     */
    public function enviar_recordatorios() {
        global $wpdb;

        $horas = TTRA_Settings::get( 'recordatorio_horas', 24 );
        $target_date = date( 'Y-m-d', strtotime( "+{$horas} hours" ) );

        $t_reservas = TTRA_DB::table( 'reservas' );
        $t_lineas   = TTRA_DB::table( 'reserva_lineas' );
        $t_email    = TTRA_DB::table( 'email_log' );

        // Reservas con actividades mañana que no hayan recibido recordatorio
        $reservas = $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT r.*
             FROM $t_reservas r
             INNER JOIN $t_lineas l ON l.reserva_id = r.id
             WHERE r.estado IN ('confirmada', 'pagada')
               AND l.fecha = %s
               AND r.id NOT IN (
                   SELECT reserva_id FROM $t_email WHERE tipo = 'recordatorio' AND estado = 'enviado'
               )",
            $target_date
        ) );

        $mailer = new TTRA_Mailer();
        foreach ( $reservas as $reserva ) {
            $mailer->enviar_recordatorio( $reserva );
        }
    }

    /**
     * Cancela reservas pendientes de pago después de 30 minutos.
     */
    public function limpiar_pendientes() {
        global $wpdb;
        $table = TTRA_DB::table( 'reservas' );
        $limite = date( 'Y-m-d H:i:s', strtotime( '-30 minutes' ) );

        $wpdb->query( $wpdb->prepare(
            "UPDATE $table SET estado = 'cancelada'
             WHERE estado = 'pendiente' AND pagado = 0 AND created_at < %s",
            $limite
        ) );
    }

    /**
     * Marca como completadas las reservas cuya fecha ya pasó.
     */
    public function marcar_completadas() {
        global $wpdb;
        $t_reservas = TTRA_DB::table( 'reservas' );
        $t_lineas   = TTRA_DB::table( 'reserva_lineas' );
        $ayer = date( 'Y-m-d', strtotime( '-1 day' ) );

        $wpdb->query( $wpdb->prepare(
            "UPDATE $t_reservas r
             SET r.estado = 'completada'
             WHERE r.estado IN ('confirmada', 'pagada')
               AND r.id NOT IN (
                   SELECT reserva_id FROM $t_lineas WHERE fecha >= %s
               )",
            $ayer
        ) );
    }
}
