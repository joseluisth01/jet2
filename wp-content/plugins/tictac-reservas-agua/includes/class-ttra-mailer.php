<?php
/**
 * Sistema de envío de correos del plugin.
 *
 * COMPATIBILIDAD CON WP MAIL SMTP:
 * WP Mail SMTP intercepta wp_mail() y reenvía usando su propio SMTP configurado.
 * Para que funcione correctamente:
 *   1. NO sobreescribimos el from con add_filter('wp_mail_from') aquí — WP Mail SMTP
 *      tiene prioridad y usará el from configurado en su panel.
 *   2. Pasamos el Content-Type como header para emails HTML.
 *   3. Opcionalmente pasamos el Reply-To para que las respuestas lleguen al negocio.
 *
 * Si WP Mail SMTP está en modo "Force From Email", ignorará nuestro header From.
 * Si NO está en modo Force, usará el from_name/from_address que configuramos aquí.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Mailer {

    private $from_name;
    private $from_email;

    public function __construct() {
        $this->from_name  = TTRA_Settings::get( 'email_from_name', get_bloginfo( 'name' ) );
        $this->from_email = TTRA_Settings::get( 'email_from_address', get_option( 'admin_email' ) );
    }

    /**
     * Email de confirmación al cliente.
     * Se llama desde test_confirm_pago() y desde redsys_notification().
     */
    public function enviar_confirmacion( $reserva ) {
        if ( ! TTRA_Settings::get( 'email_confirmacion', 1 ) ) {
            error_log( 'TTRA Mailer: email_confirmacion desactivado en ajustes.' );
            return false;
        }

        $lineas  = TTRA_Reserva::get_lineas( $reserva->id );
        $subject = '¡Tu reserva está confirmada! — ' . esc_html( $reserva->codigo_reserva );
        $body    = $this->render_template( 'confirmacion', array(
            'reserva' => $reserva,
            'lineas'  => $lineas,
        ) );

        $sent = $this->send( $reserva->email, $subject, $body );
        $this->log( $reserva->id, $reserva->email, $subject, 'confirmacion', $sent );

        if ( ! $sent ) {
            error_log( 'TTRA Mailer: FALLO al enviar confirmación a ' . $reserva->email );
        }

        return $sent;
    }

    /**
     * Email de cancelación al cliente.
     */
    public function enviar_cancelacion( $reserva ) {
        if ( ! TTRA_Settings::get( 'email_cancelacion', 1 ) ) {
            error_log( 'TTRA Mailer: email_cancelacion desactivado en ajustes.' );
            return false;
        }

        $subject = 'Tu reserva ha sido cancelada — ' . esc_html( $reserva->codigo_reserva );
        $body    = $this->render_template( 'cancelacion', array( 'reserva' => $reserva ) );

        $sent = $this->send( $reserva->email, $subject, $body );
        $this->log( $reserva->id, $reserva->email, $subject, 'cancelacion', $sent );

        if ( ! $sent ) {
            error_log( 'TTRA Mailer: FALLO al enviar cancelación a ' . $reserva->email );
        }

        return $sent;
    }

    /**
     * Email recordatorio al cliente (24h antes).
     * Llamado por el cron ttra_cron_enviar_recordatorios.
     */
    public function enviar_recordatorio( $reserva ) {
        if ( ! TTRA_Settings::get( 'email_recordatorio', 1 ) ) {
            error_log( 'TTRA Mailer: email_recordatorio desactivado en ajustes.' );
            return false;
        }

        $lineas  = TTRA_Reserva::get_lineas( $reserva->id );
        $subject = '¡Mañana es el gran día! Tu aventura te espera 🌊';
        $body    = $this->render_template( 'recordatorio', array(
            'reserva' => $reserva,
            'lineas'  => $lineas,
        ) );

        $sent = $this->send( $reserva->email, $subject, $body );
        $this->log( $reserva->id, $reserva->email, $subject, 'recordatorio', $sent );

        if ( ! $sent ) {
            error_log( 'TTRA Mailer: FALLO al enviar recordatorio a ' . $reserva->email );
        }

        return $sent;
    }

    /**
     * Notificación al admin de nueva reserva.
     */
    public function notificar_admin_nueva_reserva( $reserva ) {
        if ( ! TTRA_Settings::get( 'email_admin_nueva', 1 ) ) {
            error_log( 'TTRA Mailer: email_admin_nueva desactivado en ajustes.' );
            return false;
        }

        // El admin puede ser diferente al from: usamos el email de administración del plugin
        $admin_email = TTRA_Settings::get( 'email_admin', get_option( 'admin_email' ) );

        // Validar que tenemos un email de admin
        if ( ! is_email( $admin_email ) ) {
            error_log( 'TTRA Mailer: email_admin no configurado o inválido: ' . $admin_email );
            return false;
        }

        $lineas  = TTRA_Reserva::get_lineas( $reserva->id );
        $subject = '💰 Nueva reserva: ' . $reserva->codigo_reserva . ' — ' . trim( $reserva->nombre . ' ' . $reserva->apellidos );
        $body    = $this->render_template( 'admin-nueva-reserva', array(
            'reserva' => $reserva,
            'lineas'  => $lineas,
        ) );

        $sent = $this->send( $admin_email, $subject, $body );
        $this->log( $reserva->id, $admin_email, $subject, 'admin_nueva', $sent );

        if ( ! $sent ) {
            error_log( 'TTRA Mailer: FALLO al enviar notificación admin a ' . $admin_email );
        }

        return $sent;
    }

    /* ════════════════════════════════════════
       UTILIDADES INTERNAS
       ════════════════════════════════════════ */

    /**
     * Envía un email HTML usando wp_mail().
     *
     * WP Mail SMTP intercepta wp_mail() automáticamente si está activo,
     * usando su configuración SMTP. No necesitamos hacer nada especial.
     *
     * Los headers que sí debemos pasar:
     *   - Content-Type: text/html — para que el email se renderice como HTML
     *   - From: — el remitente (WP Mail SMTP puede ignorarlo si tiene Force From activo)
     *   - Reply-To: — para que las respuestas vayan al negocio
     */
    private function send( $to, $subject, $body ) {
        // Validar destinatario
        if ( ! is_email( $to ) ) {
            error_log( 'TTRA Mailer: email destinatario inválido: ' . $to );
            return false;
        }

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        // Solo añadimos From si tenemos un email válido configurado.
        // WP Mail SMTP puede sobreescribirlo según su configuración "Force From".
        if ( is_email( $this->from_email ) ) {
            if ( $this->from_name ) {
                $headers[] = 'From: ' . $this->from_name . ' <' . $this->from_email . '>';
                $headers[] = 'Reply-To: ' . $this->from_name . ' <' . $this->from_email . '>';
            } else {
                $headers[] = 'From: ' . $this->from_email;
                $headers[] = 'Reply-To: ' . $this->from_email;
            }
        }

        // Permitir filtrar headers desde fuera (para plugins de compatibilidad)
        $headers = apply_filters( 'ttra_mail_headers', $headers, $to, $subject );

        // Limpiar el asunto (no debe tener saltos de línea)
        $subject = wp_strip_all_tags( $subject );

        // Limpiar el cuerpo de caracteres problemáticos
        $body = force_balance_tags( $body );

        $result = wp_mail( $to, $subject, $body, $headers );

        return (bool) $result;
    }

    /**
     * Renderiza una plantilla PHP como string HTML.
     */
    private function render_template( $template_name, $data = array() ) {
        // Extraer variables para que estén disponibles en la plantilla
        extract( $data, EXTR_SKIP );

        ob_start();

        $template_path = TTRA_PLUGIN_DIR . 'templates/emails/' . $template_name . '.php';

        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            // Fallback básico si no existe la plantilla
            error_log( 'TTRA Mailer: Plantilla no encontrada: ' . $template_path );
            echo '<html><body>';
            echo '<h2>Reserva: ' . esc_html( isset($reserva) ? $reserva->codigo_reserva : '' ) . '</h2>';
            echo '<p>Plantilla de email no encontrada: ' . esc_html( $template_name ) . '</p>';
            echo '</body></html>';
        }

        return ob_get_clean();
    }

    /**
     * Registra el email enviado en la tabla de log.
     */
    private function log( $reserva_id, $destinatario, $asunto, $tipo, $sent ) {
        TTRA_DB::insert( 'email_log', array(
            'reserva_id'   => $reserva_id,
            'destinatario' => sanitize_email( $destinatario ),
            'asunto'       => substr( $asunto, 0, 500 ),
            'tipo'         => $tipo,
            'estado'       => $sent ? 'enviado' : 'fallido',
        ) );
    }
}