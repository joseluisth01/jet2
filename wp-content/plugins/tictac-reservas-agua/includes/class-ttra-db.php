<?php
/**
 * Acceso directo a tablas custom del plugin.
 * Wrapper sobre $wpdb para queries comunes.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_DB {

    /**
     * Devuelve el nombre completo de una tabla del plugin.
     */
    public static function table( $name ) {
        global $wpdb;
        return $wpdb->prefix . 'ttra_' . $name;
    }

    /**
     * Inserta un registro y devuelve el ID.
     */
    public static function insert( $table_name, $data, $format = null ) {
        global $wpdb;
        $wpdb->insert( self::table( $table_name ), $data, $format );
        return $wpdb->insert_id;
    }

    /**
     * Actualiza registros.
     */
    public static function update( $table_name, $data, $where, $format = null, $where_format = null ) {
        global $wpdb;
        return $wpdb->update( self::table( $table_name ), $data, $where, $format, $where_format );
    }

    /**
     * Elimina registros.
     */
    public static function delete( $table_name, $where, $where_format = null ) {
        global $wpdb;
        return $wpdb->delete( self::table( $table_name ), $where, $where_format );
    }

    /**
     * Obtiene un registro por ID.
     */
    public static function get_by_id( $table_name, $id ) {
        global $wpdb;
        $table = self::table( $table_name );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
    }

    /**
     * Obtiene todos los registros con condiciones opcionales.
     */
    public static function get_all( $table_name, $where = '', $orderby = 'id', $order = 'ASC', $limit = 0 ) {
        global $wpdb;
        $table = self::table( $table_name );
        $sql   = "SELECT * FROM $table";
        if ( $where ) {
            $sql .= " WHERE $where";
        }
        $sql .= " ORDER BY $orderby $order";
        if ( $limit > 0 ) {
            $sql .= $wpdb->prepare( " LIMIT %d", $limit );
        }
        return $wpdb->get_results( $sql );
    }

    /**
     * Cuenta registros con condición.
     */
    public static function count( $table_name, $where = '' ) {
        global $wpdb;
        $table = self::table( $table_name );
        $sql   = "SELECT COUNT(*) FROM $table";
        if ( $where ) {
            $sql .= " WHERE $where";
        }
        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Query personalizada.
     */
    public static function query( $sql ) {
        global $wpdb;
        return $wpdb->get_results( $sql );
    }
}
