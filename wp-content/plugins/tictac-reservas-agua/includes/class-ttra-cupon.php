<?php
/**
 * Modelo: Cupones de descuento.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Cupon {

    const TABLE = 'cupones';

    public static function get_all() {
        return TTRA_DB::get_all( self::TABLE, '', 'created_at', 'DESC' );
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
     * Valida y aplica un cupón.
     * @return array|WP_Error
     */
    public static function validar( $codigo, $subtotal ) {
        global $wpdb;
        $table = TTRA_DB::table( self::TABLE );
        $cupon = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE codigo = %s AND activo = 1", $codigo
        ) );

        if ( ! $cupon ) {
            return new WP_Error( 'invalid', __( 'Cupón no válido.', 'tictac-reservas-agua' ) );
        }

        $hoy = current_time( 'Y-m-d' );
        if ( $cupon->fecha_inicio && $hoy < $cupon->fecha_inicio ) {
            return new WP_Error( 'not_started', __( 'Este cupón aún no es válido.', 'tictac-reservas-agua' ) );
        }
        if ( $cupon->fecha_fin && $hoy > $cupon->fecha_fin ) {
            return new WP_Error( 'expired', __( 'Este cupón ha expirado.', 'tictac-reservas-agua' ) );
        }
        if ( $cupon->uso_maximo > 0 && $cupon->uso_actual >= $cupon->uso_maximo ) {
            return new WP_Error( 'limit', __( 'Este cupón ha alcanzado su límite de uso.', 'tictac-reservas-agua' ) );
        }

        $descuento = $cupon->tipo === 'porcentaje'
            ? round( $subtotal * ( $cupon->valor / 100 ), 2 )
            : min( $cupon->valor, $subtotal );

        return array(
            'cupon_id'   => $cupon->id,
            'codigo'     => $cupon->codigo,
            'tipo'       => $cupon->tipo,
            'valor'      => $cupon->valor,
            'descuento'  => $descuento,
            'total'      => $subtotal - $descuento,
        );
    }

    /**
     * Incrementa el uso de un cupón.
     */
    public static function incrementar_uso( $id ) {
        global $wpdb;
        $table = TTRA_DB::table( self::TABLE );
        $wpdb->query( $wpdb->prepare(
            "UPDATE $table SET uso_actual = uso_actual + 1 WHERE id = %d", $id
        ) );
    }
}
