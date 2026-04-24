<?php
/**
 * Modelo: Reserva.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Reserva {

    const TABLE        = 'reservas';
    const TABLE_LINEAS = 'reserva_lineas';

    /* ─── CRUD ─── */

    public static function get_by_id( $id ) {
        return TTRA_DB::get_by_id( self::TABLE, $id );
    }

    public static function get_by_codigo( $codigo ) {
        global $wpdb;
        $table = TTRA_DB::table( self::TABLE );
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE codigo_reserva = %s", $codigo
        ) );
    }

    public static function create( $data ) {
        $data['codigo_reserva'] = TTRA_Helpers::generar_codigo_reserva();
        $data['created_at']     = current_time( 'mysql' );
        $id = TTRA_DB::insert( self::TABLE, $data );
        return $id ? self::get_by_id( $id ) : false;
    }

    public static function update( $id, $data ) {
        $data['updated_at'] = current_time( 'mysql' );
        return TTRA_DB::update( self::TABLE, $data, array( 'id' => $id ) );
    }

    public static function delete( $id ) {
        // Eliminar líneas primero
        TTRA_DB::delete( self::TABLE_LINEAS, array( 'reserva_id' => $id ) );
        return TTRA_DB::delete( self::TABLE, array( 'id' => $id ) );
    }

    /* ─── Líneas de reserva ─── */

    public static function add_linea( $reserva_id, $data ) {
        $data['reserva_id'] = $reserva_id;
        return TTRA_DB::insert( self::TABLE_LINEAS, $data );
    }

    public static function get_lineas( $reserva_id ) {
        global $wpdb;
        $t_lineas = TTRA_DB::table( self::TABLE_LINEAS );
        $t_act    = TTRA_DB::table( 'actividades' );
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT l.*, a.nombre as actividad_nombre, a.subtipo, a.duracion_minutos
             FROM $t_lineas l
             LEFT JOIN $t_act a ON a.id = l.actividad_id
             WHERE l.reserva_id = %d
             ORDER BY l.fecha ASC, l.hora ASC",
            $reserva_id
        ) );
    }

    /* ─── Cambios de estado ─── */

    public static function confirmar( $id ) {
        return self::update( $id, array( 'estado' => 'confirmada' ) );
    }

    public static function marcar_pagada( $id, $metodo, $transaccion_id = '' ) {
        return self::update( $id, array(
            'estado'         => 'pagada',
            'pagado'         => 1,
            'metodo_pago'    => $metodo,
            'transaccion_id' => $transaccion_id,
            'fecha_pago'     => current_time( 'mysql' ),
        ) );
    }

    public static function cancelar( $id ) {
        return self::update( $id, array( 'estado' => 'cancelada' ) );
    }

    public static function completar( $id ) {
        return self::update( $id, array( 'estado' => 'completada' ) );
    }

    /* ─── Listados admin ─── */

    public static function get_listado( $args = array() ) {
        global $wpdb;
        $table = TTRA_DB::table( self::TABLE );

        $defaults = array(
            'estado'    => '',
            'buscar'    => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'orderby'   => 'created_at',
            'order'     => 'DESC',
            'per_page'  => 20,
            'page'      => 1,
        );
        $args = wp_parse_args( $args, $defaults );

        $where = array( '1=1' );

        if ( $args['estado'] ) {
            $where[] = $wpdb->prepare( "estado = %s", $args['estado'] );
        }
        if ( $args['buscar'] ) {
            $like    = '%' . $wpdb->esc_like( $args['buscar'] ) . '%';
            $where[] = $wpdb->prepare(
                "(nombre LIKE %s OR email LIKE %s OR codigo_reserva LIKE %s OR telefono LIKE %s)",
                $like, $like, $like, $like
            );
        }
        if ( $args['fecha_desde'] ) {
            $where[] = $wpdb->prepare( "DATE(created_at) >= %s", $args['fecha_desde'] );
        }
        if ( $args['fecha_hasta'] ) {
            $where[] = $wpdb->prepare( "DATE(created_at) <= %s", $args['fecha_hasta'] );
        }

        $where_str = implode( ' AND ', $where );
        $offset    = ( $args['page'] - 1 ) * $args['per_page'];

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where_str" );

        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE $where_str ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            $args['per_page'],
            $offset
        ) );

        return array(
            'items' => $items,
            'total' => (int) $total,
            'pages' => ceil( $total / $args['per_page'] ),
        );
    }

    /* ─── Estadísticas ─── */

    public static function stats( $periodo = '30days' ) {
        global $wpdb;
        $table = TTRA_DB::table( self::TABLE );

        switch ( $periodo ) {
            case '7days':  $days = 7; break;
            case '90days': $days = 90; break;
            default:       $days = 30;
        }

        $desde = date( 'Y-m-d', strtotime( "-{$days} days" ) );

        return array(
            'total'      => $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE DATE(created_at) >= '$desde'" ),
            'pendientes' => $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE estado = 'pendiente' AND DATE(created_at) >= '$desde'" ),
            'confirmadas'=> $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE estado IN ('confirmada','pagada') AND DATE(created_at) >= '$desde'" ),
            'canceladas' => $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE estado = 'cancelada' AND DATE(created_at) >= '$desde'" ),
            'ingresos'   => $wpdb->get_var( "SELECT COALESCE(SUM(total), 0) FROM $table WHERE pagado = 1 AND DATE(created_at) >= '$desde'" ),
        );
    }
}
