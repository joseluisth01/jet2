<?php
/**
 * Modelo: Categoría de actividades.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Categoria {

    const TABLE = 'categorias';

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

    public static function get_by_slug( $slug ) {
        global $wpdb;
        $table = TTRA_DB::table( self::TABLE );
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE slug = %s", $slug
        ) );
    }

    /**
     * Devuelve categorías con el conteo de actividades.
     */
    public static function get_with_count() {
        global $wpdb;
        $t_cat = TTRA_DB::table( 'categorias' );
        $t_act = TTRA_DB::table( 'actividades' );
        return $wpdb->get_results(
            "SELECT c.*, COUNT(a.id) as total_actividades
             FROM $t_cat c
             LEFT JOIN $t_act a ON a.categoria_id = c.id AND a.activa = 1
             WHERE c.activa = 1
             GROUP BY c.id
             ORDER BY c.orden ASC"
        );
    }
}
