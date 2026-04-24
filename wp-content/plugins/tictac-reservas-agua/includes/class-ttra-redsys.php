<?php
/**
 * Integración con pasarela de pago Redsys — HMAC_SHA256_V1
 * Fix v1.2: La clave del portal NO está en Base64, se usa directamente.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Redsys {

    private $entorno;
    private $fuc;
    private $terminal;
    private $clave_secreta;
    private $nombre_comercio;

    const URL_TEST = 'https://sis-t.redsys.es:25443/sis/realizarPago';
    const URL_PROD = 'https://sis.redsys.es/sis/realizarPago';

    public function __construct() {
        $this->entorno         = TTRA_Settings::get( 'redsys_entorno', 'test' );
        $this->fuc             = TTRA_Settings::get( 'redsys_fuc' );
        $this->terminal        = TTRA_Settings::get( 'redsys_terminal', '001' );
        $this->clave_secreta   = TTRA_Settings::get( 'redsys_clave_secreta' );
        $this->nombre_comercio = TTRA_Settings::get( 'redsys_nombre_comercio' );
    }

    public function get_url() {
        return $this->entorno === 'produccion' ? self::URL_PROD : self::URL_TEST;
    }

    public function generar_formulario( $reserva, $metodo_pago = 'tarjeta' ) {
        $order_id = $this->generar_order_id( $reserva->id );
        $importe  = intval( round( $reserva->total * 100 ) );

        $params = array(
            'DS_MERCHANT_AMOUNT'             => strval( $importe ),
            'DS_MERCHANT_ORDER'              => $order_id,
            'DS_MERCHANT_MERCHANTCODE'       => $this->fuc,
            'DS_MERCHANT_CURRENCY'           => '978',
            'DS_MERCHANT_TRANSACTIONTYPE'    => '0',
            'DS_MERCHANT_TERMINAL'           => $this->terminal,
            'DS_MERCHANT_MERCHANTURL'        => $this->get_notification_url(),
            'DS_MERCHANT_URLOK'              => $this->get_return_url( $reserva->codigo_reserva, 'ok' ),
            'DS_MERCHANT_URLKO'              => $this->get_return_url( $reserva->codigo_reserva, 'ko' ),
            'DS_MERCHANT_MERCHANTNAME'       => $this->nombre_comercio,
            'DS_MERCHANT_CONSUMERLANGUAGE'   => '001',
            'DS_MERCHANT_PRODUCTDESCRIPTION' => 'Reserva ' . $reserva->codigo_reserva,
        );

        switch ( $metodo_pago ) {
            case 'bizum':      $params['DS_MERCHANT_PAYMETHODS'] = 'z';    break;
            case 'google_pay':
            case 'apple_pay':  $params['DS_MERCHANT_PAYMETHODS'] = 'xpay'; break;
            default:           $params['DS_MERCHANT_PAYMETHODS'] = 'C';    break;
        }

        $params_json   = json_encode( $params );
        $params_base64 = base64_encode( $params_json );
        $signature     = $this->firmar( $params_base64, $order_id );

        TTRA_Pago::create( array(
            'reserva_id'     => $reserva->id,
            'metodo'         => $metodo_pago,
            'estado'         => 'pendiente',
            'importe'        => $reserva->total,
            'transaccion_id' => $order_id,
        ) );

        return array(
            'url'                   => $this->get_url(),
            'Ds_SignatureVersion'   => 'HMAC_SHA256_V1',
            'Ds_MerchantParameters' => $params_base64,
            'Ds_Signature'          => $signature,
        );
    }

    public function procesar_notificacion( $post_data ) {
        if ( empty( $post_data['Ds_MerchantParameters'] ) || empty( $post_data['Ds_Signature'] ) ) {
            return new WP_Error( 'invalid', 'Datos de notificación inválidos.' );
        }

        $params_base64  = $post_data['Ds_MerchantParameters'];
        $firma_recibida = $post_data['Ds_Signature'];
        $params         = json_decode( base64_decode( $params_base64 ), true );

        $order_id        = $params['Ds_Order'] ?? '';
        $firma_calculada = $this->firmar( $params_base64, $order_id );

        // Comparar en base64url para evitar problemas con +/=
        if ( $this->base64url( $firma_calculada ) !== $this->base64url( $firma_recibida ) ) {
            return new WP_Error( 'signature', 'Firma no válida.' );
        }

        $response_code = intval( $params['Ds_Response'] ?? 9999 );
        $auth_code     = $params['Ds_AuthorisationCode'] ?? '';
        $reserva_id    = $this->extraer_reserva_id( $order_id );
        $pago          = TTRA_Pago::get_by_transaccion( $order_id );

        if ( $response_code >= 0 && $response_code <= 99 ) {
            if ( $pago ) {
                TTRA_Pago::update( $pago->id, array(
                    'estado'              => 'completado',
                    'codigo_autorizacion' => $auth_code,
                    'datos_respuesta'     => json_encode( $params ),
                ) );
            }

            TTRA_Reserva::marcar_pagada( $reserva_id, $pago->metodo ?? 'tarjeta', $order_id );

            $reserva = TTRA_Reserva::get_by_id( $reserva_id );
            if ( $reserva ) {
                $mailer = new TTRA_Mailer();
                $mailer->enviar_confirmacion( $reserva );
                $mailer->notificar_admin_nueva_reserva( $reserva );
            }

            return array( 'success' => true, 'reserva_id' => $reserva_id );

        } else {
            if ( $pago ) {
                TTRA_Pago::marcar_fallido( $pago->id, json_encode( $params ) );
            }
            return new WP_Error( 'payment_failed', 'Pago rechazado. Código: ' . $response_code );
        }
    }

    /* ─── Utilidades internas ─── */

    private function generar_order_id( $reserva_id ) {
        $num  = str_pad( intval( $reserva_id ), 4, '0', STR_PAD_LEFT );
        $rand = strtoupper( substr( wp_generate_password( 8, false, false ), 0, 8 ) );
        return $num . $rand;
    }

    private function extraer_reserva_id( $order_id ) {
        return intval( substr( $order_id, 0, 4 ) );
    }

    /**
     * Firma según HMAC_SHA256_V1 (Manual Integración Redirección, pág. 12):
     *
     * 1. Decodificar en Base64 la clave del comercio
     * 2. Cifrar el Ds_Merchant_Order con 3DES-CBC (IV = 8 bytes a cero) usando la clave decodificada
     * 3. HMAC-SHA256 de Ds_MerchantParameters con la clave derivada del paso 2
     * 4. Resultado en Base64
     */
    private function firmar( $params_base64, $order_id ) {
        // ✅ La clave guardada en ajustes ES Base64 — hay que decodificarla antes del 3DES
        $key = base64_decode( $this->clave_secreta );

        // Cifrar order_id con 3DES-CBC, IV = 8 bytes a cero
        $iv          = "\x00\x00\x00\x00\x00\x00\x00\x00";
        $clave_order = openssl_encrypt( $order_id, 'DES-EDE3-CBC', $key, OPENSSL_RAW_DATA, $iv );

        if ( $clave_order === false ) {
            error_log( 'TTRA Redsys: openssl_encrypt falló: ' . openssl_error_string() );
            return '';
        }

        // HMAC-SHA256 y Base64
        $hmac = hash_hmac( 'sha256', $params_base64, $clave_order, true );
        return base64_encode( $hmac );
    }

    private function base64url( $b64 ) {
        return strtr( rtrim( $b64, '=' ), '+/', '-_' );
    }

    private function get_notification_url() {
        return rest_url( 'ttra/v1/redsys/notification' );
    }

    private function get_return_url( $codigo_reserva, $resultado ) {
        $base = TTRA_Helpers::get_reservas_url();
        return add_query_arg( array(
            'ttra_result' => $resultado,
            'code'        => $codigo_reserva,
        ), $base );
    }
}