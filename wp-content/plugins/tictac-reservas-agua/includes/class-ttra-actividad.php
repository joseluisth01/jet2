<?php
/**
 * Modelo: Actividad / Servicio.
 *
 * CHANGELOG v1.1.0
 *   + Soporte para campos `premium` y `precio_pax`
 *   + get_all_with_categoria() ordena: primero normales (por categoría + orden),
 *     luego premium (por categoría + orden) — el JS del frontend aplica además
 *     ordenación por duración dentro de cada categoría.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Actividad {

    const TABLE = 'actividades';

    public static function get_all( $solo_activas = true ) {
        $where = $solo_activas ? "activa = 1" : "1=1";
        return TTRA_DB::get_all( self::TABLE, $where, 'orden', 'ASC' );
    }

    public static function get_by_id( $id ) {
        return TTRA_DB::get_by_id( self::TABLE, $id );
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

    /**
     * Actividades por categoría.
     */
    public static function get_by_categoria( $categoria_id, $solo_activas = true ) {
        $where = "categoria_id = " . intval( $categoria_id );
        if ( $solo_activas ) $where .= " AND activa = 1";
        return TTRA_DB::get_all( self::TABLE, $where, 'orden', 'ASC' );
    }

    /**
     * Actividades con info de categoría.
     *
     * ORDEN (frontend):
     *   1. Actividades NO premium, agrupadas por categoría → por orden + duración
     *   2. Actividades PREMIUM, agrupadas por categoría → por orden + duración
     *
     * Esto permite que el JS pueda separar y renderizar los dos grupos.
     */
    public static function get_all_with_categoria() {
        global $wpdb;
        $t_act = TTRA_DB::table( 'actividades' );
        $t_cat = TTRA_DB::table( 'categorias' );
        return $wpdb->get_results(
            "SELECT a.*, c.nombre as categoria_nombre, c.slug as categoria_slug, c.orden as categoria_orden
             FROM $t_act a
             LEFT JOIN $t_cat c ON c.id = a.categoria_id
             WHERE a.activa = 1
             ORDER BY
                a.premium ASC,
                c.orden   ASC,
                a.orden   ASC,
                a.duracion_minutos ASC"
        );
    }

    /**
     * Calcula el precio para unas personas y sesiones.
     * Si la actividad tiene `precio_pax` definido, se suma al precio base × sesiones.
     *
     * Lógica:
     *   precio_tipo = 'fijo'       → precio_base × sesiones  + (precio_pax × personas × sesiones si existe)
     *   precio_tipo = 'por_persona' → precio_base × personas × sesiones
     */
    public static function calcular_precio( $actividad_id, $personas, $sesiones ) {
        $act = self::get_by_id( $actividad_id );
        if ( ! $act ) return 0;

        if ( $act->precio_tipo === 'por_persona' ) {
            $base = $act->precio_base * $personas * $sesiones;
        } else {
            $base = $act->precio_base * $sesiones;
            // precio_pax adicional (ej: inflables cobran base + 25€/pax)
            if ( ! empty( $act->precio_pax ) && floatval( $act->precio_pax ) > 0 ) {
                $base += floatval( $act->precio_pax ) * $personas * $sesiones;
            }
        }

        return $base;
    }

    /**
     * Obtiene los horarios de una actividad para un día concreto.
     */
    public static function get_horarios_dia( $actividad_id, $fecha ) {
        $dia_semana = date( 'N', strtotime( $fecha ) ) - 1; // 0=lunes
        return TTRA_Horario::get_slots_disponibles( $actividad_id, $dia_semana, $fecha );
    }
}