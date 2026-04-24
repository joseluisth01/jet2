<?php
/**
 * Modelo: Horarios y slots disponibles.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Horario {

    const TABLE = 'horarios';

    public static function get_by_actividad( $actividad_id ) {
        return TTRA_DB::get_all( self::TABLE, "actividad_id = " . intval( $actividad_id ), 'dia_semana', 'ASC' );
    }

    public static function create( $data ) {
        return TTRA_DB::insert( self::TABLE, $data );
    }

    public static function update( $id, $data ) {
        return TTRA_DB::update( self::TABLE, $data, array( 'id' => $id ) );
    }

    public static function delete( $id ) {
        return TTRA_DB::delete( self::TABLE, array( 'id' => $id ) );
    }

    public static function delete_by_actividad( $actividad_id ) {
        return TTRA_DB::delete( self::TABLE, array( 'actividad_id' => $actividad_id ) );
    }

    /**
     * Genera los slots de hora disponibles para una actividad + día.
     * Tiene en cuenta las plazas ya reservadas.
     */
    public static function get_slots_disponibles( $actividad_id, $dia_semana, $fecha ) {
        global $wpdb;

        // Comprobar bloqueo
        if ( TTRA_Helpers::fecha_bloqueada( $actividad_id, $fecha ) ) {
            return array();
        }

        $table_h = TTRA_DB::table( 'horarios' );
        $table_l = TTRA_DB::table( 'reserva_lineas' );
        $table_r = TTRA_DB::table( 'reservas' );

        // Obtener horarios configurados
        $horarios = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_h WHERE actividad_id = %d AND dia_semana = %d AND activo = 1",
            $actividad_id,
            $dia_semana
        ) );

        $slots = array();

        foreach ( $horarios as $horario ) {
            $inicio = strtotime( $horario->hora_inicio );
            $fin    = strtotime( $horario->hora_fin );
            $intervalo = $horario->intervalo_minutos * 60;

            for ( $t = $inicio; $t < $fin; $t += $intervalo ) {
                $hora_slot = date( 'H:i:s', $t );

                // Contar plazas ya reservadas para este slot
                $reservadas = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COALESCE(SUM(l.personas), 0)
                     FROM $table_l l
                     INNER JOIN $table_r r ON r.id = l.reserva_id
                     WHERE l.actividad_id = %d
                       AND l.fecha = %s
                       AND l.hora = %s
                       AND r.estado NOT IN ('cancelada')",
                    $actividad_id,
                    $fecha,
                    $hora_slot
                ) );

                $disponibles = $horario->plazas_por_slot - (int) $reservadas;

                if ( $disponibles > 0 ) {
                    $slots[] = array(
                        'hora'        => date( 'H:i', $t ),
                        'plazas_total' => $horario->plazas_por_slot,
                        'plazas_ocupadas' => (int) $reservadas,
                        'plazas_disponibles' => $disponibles,
                    );
                }
            }
        }

        return $slots;
    }
}
