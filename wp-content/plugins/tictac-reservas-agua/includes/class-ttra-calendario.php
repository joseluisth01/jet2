<?php
/**
 * Servicio de calendario: disponibilidad, slots, bloqueos.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Calendario {

    /**
     * Obtiene los días disponibles de un mes para una actividad.
     * Devuelve array de fechas con disponibilidad.
     */
    public static function get_dias_disponibles( $actividad_id, $anyo, $mes ) {
        $dias = array();
        $total_dias = cal_days_in_month( CAL_GREGORIAN, $mes, $anyo );

        $min_dias = TTRA_Settings::get( 'dias_antelacion_min', 1 );
        $max_dias = TTRA_Settings::get( 'dias_antelacion_max', 90 );
        $hoy = current_time( 'Y-m-d' );
        $min_fecha = date( 'Y-m-d', strtotime( "+{$min_dias} days", strtotime( $hoy ) ) );
        $max_fecha = date( 'Y-m-d', strtotime( "+{$max_dias} days", strtotime( $hoy ) ) );

        // Obtener horarios de la actividad
        $horarios = TTRA_Horario::get_by_actividad( $actividad_id );
        $dias_con_horario = array();
        foreach ( $horarios as $h ) {
            if ( $h->activo ) {
                $dias_con_horario[] = $h->dia_semana;
            }
        }

        for ( $d = 1; $d <= $total_dias; $d++ ) {
            $fecha = sprintf( '%04d-%02d-%02d', $anyo, $mes, $d );

            // Fuera de rango de antelación
            if ( $fecha < $min_fecha || $fecha > $max_fecha ) {
                $dias[ $fecha ] = array( 'disponible' => false, 'motivo' => 'fuera_rango' );
                continue;
            }

            // ¿Hay horario configurado para este día de la semana?
            $dia_semana = date( 'N', strtotime( $fecha ) ) - 1;
            if ( ! in_array( $dia_semana, $dias_con_horario ) ) {
                $dias[ $fecha ] = array( 'disponible' => false, 'motivo' => 'sin_horario' );
                continue;
            }

            // ¿Está bloqueado?
            if ( TTRA_Helpers::fecha_bloqueada( $actividad_id, $fecha ) ) {
                $dias[ $fecha ] = array( 'disponible' => false, 'motivo' => 'bloqueado' );
                continue;
            }

            // Comprobar si quedan plazas
            $slots = TTRA_Horario::get_slots_disponibles( $actividad_id, $dia_semana, $fecha );
            if ( empty( $slots ) ) {
                $dias[ $fecha ] = array( 'disponible' => false, 'motivo' => 'completo' );
                continue;
            }

            $dias[ $fecha ] = array(
                'disponible'  => true,
                'slots_count' => count( $slots ),
            );
        }

        return $dias;
    }

    /**
     * Obtiene los slots de hora para una actividad en una fecha.
     */
    public static function get_slots( $actividad_id, $fecha ) {
        $dia_semana = date( 'N', strtotime( $fecha ) ) - 1;
        return TTRA_Horario::get_slots_disponibles( $actividad_id, $dia_semana, $fecha );
    }
}
