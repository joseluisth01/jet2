<?php
/**
 * Generador de PDF para confirmaciones de reserva.
 * Usa la librería TCPDF o DomPDF (se instala vía composer).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_PDF_Generator {

    /**
     * Genera un PDF de confirmación de reserva.
     * @return string Ruta al archivo PDF generado.
     */
    public static function generar_confirmacion( $reserva ) {
        $lineas = TTRA_Reserva::get_lineas( $reserva->id );

        // Renderizar HTML
        ob_start();
        include TTRA_PLUGIN_DIR . 'templates/pdf/confirmacion.php';
        $html = ob_get_clean();

        // Directorio temporal
        $upload_dir = wp_upload_dir();
        $pdf_dir    = $upload_dir['basedir'] . '/ttra-pdfs/';
        if ( ! file_exists( $pdf_dir ) ) {
            wp_mkdir_p( $pdf_dir );
        }

        $filename = 'reserva-' . $reserva->codigo_reserva . '.pdf';
        $filepath = $pdf_dir . $filename;

        // Intentar usar DomPDF si está disponible
        if ( class_exists( 'Dompdf\Dompdf' ) ) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml( $html );
            $dompdf->setPaper( 'A4', 'portrait' );
            $dompdf->render();
            file_put_contents( $filepath, $dompdf->output() );
        } else {
            // Fallback: guardar HTML como referencia
            file_put_contents( str_replace( '.pdf', '.html', $filepath ), $html );
            $filepath = str_replace( '.pdf', '.html', $filepath );
        }

        return $filepath;
    }
}
