<?php
/**
 * Modelo: Pagos / Transacciones.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Pago {

    const TABLE = 'pagos';

    public static function create( $data ) {
        return TTRA_DB::insert( self::TABLE, $data );
    }

    public static function update( $id, $data ) {
        return TTRA_DB::update( self::TABLE, $data, array( 'id' => $id ) );
    }

    public static function get_by_reserva( $reserva_id ) {
        return TTRA_DB::get_all( self::TABLE, "reserva_id = " . intval( $reserva_id ), 'created_at', 'DESC' );
    }

    public static function get_by_transaccion( $transaccion_id ) {
        global $wpdb;
        $table = TTRA_DB::table( self::TABLE );
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE transaccion_id = %s", $transaccion_id
        ) );
    }

    public static function marcar_completado( $id, $datos_respuesta = '' ) {
        return self::update( $id, array(
            'estado'          => 'completado',
            'datos_respuesta' => $datos_respuesta,
        ) );
    }

    public static function marcar_fallido( $id, $datos_respuesta = '' ) {
        return self::update( $id, array(
            'estado'          => 'fallido',
            'datos_respuesta' => $datos_respuesta,
        ) );
    }
}
