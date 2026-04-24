<?php
/**
 * Exportación de reservas a CSV.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Export {

    /**
     * Exporta reservas a CSV.
     */
    public static function reservas_csv( $args = array() ) {
        $resultado = TTRA_Reserva::get_listado( array_merge( $args, array( 'per_page' => 99999 ) ) );
        $reservas  = $resultado['items'];

        $filename = 'reservas-' . date( 'Y-m-d-His' ) . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output = fopen( 'php://output', 'w' );

        // BOM para Excel
        fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );

        // Cabeceras
        fputcsv( $output, array(
            'Código', 'Estado', 'Nombre', 'Email', 'Teléfono',
            'DNI/Pasaporte', 'Total', 'Método Pago', 'Pagado',
            'Fecha Reserva',
        ), ';' );

        foreach ( $reservas as $r ) {
            fputcsv( $output, array(
                $r->codigo_reserva,
                $r->estado,
                $r->nombre . ' ' . $r->apellidos,
                $r->email,
                $r->telefono,
                $r->dni_pasaporte,
                number_format( $r->total, 2, ',', '.' ),
                $r->metodo_pago,
                $r->pagado ? 'Sí' : 'No',
                TTRA_Helpers::formato_fecha( $r->created_at, 'd/m/Y H:i' ),
            ), ';' );
        }

        fclose( $output );
        exit;
    }
}
