<?php
/**
 * Fired during plugin activation.
 * Creates custom database tables and default options.
 *
 * CHANGELOG v1.1.0
 *   + Columna `precio_pax`  en wp_ttra_actividades  (precio adicional por persona, ej: 25 €/pax)
 *   + Columna `premium`     en wp_ttra_actividades  (0 = normal, 1 = premium)
 *   dbDelta es idempotente: sólo añade columnas nuevas en instalaciones existentes.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TTRA_Activator {

    public static function activate() {
        self::create_tables();
        self::create_default_options();
        self::create_pages();
        flush_rewrite_rules();
    }

    /**
     * Crea / actualiza todas las tablas custom del plugin.
     */
    private static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix . 'ttra_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── Categorías de actividades ──────────────────────────────────────
        $sql_categorias = "CREATE TABLE {$prefix}categorias (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            descripcion TEXT,
            icono VARCHAR(100),
            orden INT DEFAULT 0,
            activa TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset;";
        dbDelta( $sql_categorias );

        // ── Actividades / Servicios ────────────────────────────────────────
        // NUEVAS COLUMNAS (v1.1.0):
        //   precio_pax  → precio adicional por persona (ej: 25€/pax para inflables)
        //   premium     → 1 = actividad Premium (se muestra al final con estilo diferente)
        $sql_actividades = "CREATE TABLE {$prefix}actividades (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            categoria_id BIGINT UNSIGNED NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            subtipo VARCHAR(100) DEFAULT '',
            descripcion TEXT,
            duracion_minutos INT NOT NULL DEFAULT 30,
            precio_base DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            precio_tipo ENUM('fijo','por_persona') DEFAULT 'fijo',
            precio_pax DECIMAL(10,2) DEFAULT NULL,
            min_personas INT DEFAULT 1,
            max_personas INT DEFAULT 10,
            max_sesiones INT DEFAULT 5,
            imagen_id BIGINT UNSIGNED DEFAULT 0,
            icono VARCHAR(100),
            premium TINYINT(1) DEFAULT 0,
            requiere_equipo TINYINT(1) DEFAULT 0,
            cancelacion_gratuita TINYINT(1) DEFAULT 1,
            requiere_fianza TINYINT(1) DEFAULT 0,
            importe_fianza DECIMAL(10,2) DEFAULT 0.00,
            orden INT DEFAULT 0,
            activa TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria_id (categoria_id),
            UNIQUE KEY slug (slug)
        ) $charset;";
        dbDelta( $sql_actividades );

        // ── Horarios disponibles por actividad ────────────────────────────
        $sql_horarios = "CREATE TABLE {$prefix}horarios (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            actividad_id BIGINT UNSIGNED NOT NULL,
            dia_semana TINYINT NOT NULL COMMENT '0=lun,6=dom',
            hora_inicio TIME NOT NULL,
            hora_fin TIME NOT NULL,
            intervalo_minutos INT DEFAULT 30,
            plazas_por_slot INT DEFAULT 10,
            activo TINYINT(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY actividad_id (actividad_id)
        ) $charset;";
        dbDelta( $sql_horarios );

        // ── Fechas bloqueadas / excepciones ───────────────────────────────
        $sql_bloqueos = "CREATE TABLE {$prefix}bloqueos (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            actividad_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL = bloqueo global',
            fecha DATE NOT NULL,
            motivo VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY actividad_id (actividad_id),
            KEY fecha (fecha)
        ) $charset;";
        dbDelta( $sql_bloqueos );

        // ── Reservas ──────────────────────────────────────────────────────
        $sql_reservas = "CREATE TABLE {$prefix}reservas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo_reserva VARCHAR(20) NOT NULL,
            estado ENUM('pendiente','confirmada','pagada','cancelada','completada','no_show') DEFAULT 'pendiente',
            nombre VARCHAR(255) NOT NULL,
            apellidos VARCHAR(255) DEFAULT '',
            email VARCHAR(255) NOT NULL,
            telefono VARCHAR(50) NOT NULL,
            dni_pasaporte VARCHAR(50) NOT NULL,
            fecha_nacimiento DATE DEFAULT NULL,
            direccion TEXT,
            notas TEXT,
            subtotal DECIMAL(10,2) DEFAULT 0.00,
            descuento DECIMAL(10,2) DEFAULT 0.00,
            total DECIMAL(10,2) DEFAULT 0.00,
            metodo_pago VARCHAR(50) DEFAULT '',
            transaccion_id VARCHAR(255) DEFAULT '',
            pagado TINYINT(1) DEFAULT 0,
            fecha_pago DATETIME DEFAULT NULL,
            ip_cliente VARCHAR(45),
            user_agent TEXT,
            idioma VARCHAR(10) DEFAULT 'es',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY codigo_reserva (codigo_reserva),
            KEY estado (estado),
            KEY email (email),
            KEY created_at (created_at)
        ) $charset;";
        dbDelta( $sql_reservas );

        // ── Líneas de reserva (actividades dentro de una reserva) ─────────
        $sql_lineas = "CREATE TABLE {$prefix}reserva_lineas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reserva_id BIGINT UNSIGNED NOT NULL,
            actividad_id BIGINT UNSIGNED NOT NULL,
            fecha DATE NOT NULL,
            hora TIME NOT NULL,
            personas INT NOT NULL DEFAULT 1,
            sesiones INT NOT NULL DEFAULT 1,
            precio_unitario DECIMAL(10,2) NOT NULL,
            precio_total DECIMAL(10,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY reserva_id (reserva_id),
            KEY actividad_id (actividad_id),
            KEY fecha_hora (fecha, hora)
        ) $charset;";
        dbDelta( $sql_lineas );

        // ── Pagos / Transacciones ─────────────────────────────────────────
        $sql_pagos = "CREATE TABLE {$prefix}pagos (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reserva_id BIGINT UNSIGNED NOT NULL,
            metodo VARCHAR(50) NOT NULL,
            estado ENUM('pendiente','completado','fallido','reembolsado') DEFAULT 'pendiente',
            importe DECIMAL(10,2) NOT NULL,
            moneda VARCHAR(3) DEFAULT 'EUR',
            transaccion_id VARCHAR(255),
            codigo_autorizacion VARCHAR(100),
            datos_respuesta LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY reserva_id (reserva_id),
            KEY estado (estado)
        ) $charset;";
        dbDelta( $sql_pagos );

        // ── Log de emails enviados ────────────────────────────────────────
        $sql_email_log = "CREATE TABLE {$prefix}email_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reserva_id BIGINT UNSIGNED DEFAULT NULL,
            destinatario VARCHAR(255) NOT NULL,
            asunto VARCHAR(500) NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            estado ENUM('enviado','fallido') DEFAULT 'enviado',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY reserva_id (reserva_id)
        ) $charset;";
        dbDelta( $sql_email_log );

        // ── Cupones de descuento ──────────────────────────────────────────
        $sql_cupones = "CREATE TABLE {$prefix}cupones (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo VARCHAR(50) NOT NULL,
            tipo ENUM('porcentaje','fijo') DEFAULT 'porcentaje',
            valor DECIMAL(10,2) NOT NULL,
            uso_maximo INT DEFAULT 0 COMMENT '0 = ilimitado',
            uso_actual INT DEFAULT 0,
            fecha_inicio DATE DEFAULT NULL,
            fecha_fin DATE DEFAULT NULL,
            activo TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY codigo (codigo)
        ) $charset;";
        dbDelta( $sql_cupones );

        update_option( 'ttra_db_version', TTRA_DB_VERSION );
    }

    /**
     * Opciones por defecto del plugin.
     */
    private static function create_default_options() {
        $defaults = array(
            'ttra_settings' => array(
                // General
                'nombre_negocio'       => '',
                'email_admin'          => get_option( 'admin_email' ),
                'telefono_negocio'     => '',
                'direccion_negocio'    => '',
                'logo_id'              => 0,
                'moneda'               => 'EUR',
                'idioma_default'       => 'es',

                // Reservas
                'dias_antelacion_min'  => 1,
                'dias_antelacion_max'  => 90,
                'cancelacion_horas'    => 24,
                'plazas_default'       => 10,
                'confirmacion_auto'    => 1,

                // Redsys
                'redsys_entorno'          => 'test',
                'redsys_fuc'              => '',
                'redsys_terminal'         => '001',
                'redsys_clave_secreta'    => '',
                'redsys_nombre_comercio'  => '',

                // Emails
                'email_from_name'      => '',
                'email_from_address'   => '',
                'email_confirmacion'   => 1,
                'email_recordatorio'   => 1,
                'email_cancelacion'    => 1,
                'email_admin_nueva'    => 1,
                'recordatorio_horas'   => 24,

                // Apariencia
                'color_primario'       => '#003B6F',
                'color_secundario'     => '#00A0E3',
                'color_acento'         => '#F47920',
                'color_fondo'          => '#E8F4FD',
                'mostrar_cancelacion_gratuita' => 1,
                'mostrar_pago_seguro'  => 1,
                'mostrar_sin_fianza'   => 1,
                'mostrar_equipo_seguridad' => 1,

                // Métodos de pago
                'pago_tarjeta'         => 1,
                'pago_bizum'           => 0,
                'pago_google_pay'      => 0,
                'pago_apple_pay'       => 0,

                // Descuentos automáticos (3 slots configurables)
                'descuento_auto_1_activo'       => 0,
                'descuento_auto_1_nombre'       => '',
                'descuento_auto_1_tipo'         => 'porcentaje',
                'descuento_auto_1_valor'        => 0,
                'descuento_auto_1_fecha_inicio' => '',
                'descuento_auto_1_fecha_fin'    => '',
                'descuento_auto_1_minimo'       => 0,
                'descuento_auto_1_mensaje'      => '',
                'descuento_auto_2_activo'       => 0,
                'descuento_auto_2_nombre'       => '',
                'descuento_auto_2_tipo'         => 'porcentaje',
                'descuento_auto_2_valor'        => 0,
                'descuento_auto_2_fecha_inicio' => '',
                'descuento_auto_2_fecha_fin'    => '',
                'descuento_auto_2_minimo'       => 0,
                'descuento_auto_2_mensaje'      => '',
                'descuento_auto_3_activo'       => 0,
                'descuento_auto_3_nombre'       => '',
                'descuento_auto_3_tipo'         => 'porcentaje',
                'descuento_auto_3_valor'        => 0,
                'descuento_auto_3_fecha_inicio' => '',
                'descuento_auto_3_fecha_fin'    => '',
                'descuento_auto_3_minimo'       => 0,
                'descuento_auto_3_mensaje'      => '',

                // Términos y condiciones
                'terminos_url'         => '',
                'privacidad_url'       => '',
            ),
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }

    /**
     * Crea la página de reservas automáticamente.
     */
    private static function create_pages() {
        $page_id = get_option( 'ttra_page_reservas' );
        if ( ! $page_id || ! get_post( $page_id ) ) {
            $page_id = wp_insert_post( array(
                'post_title'   => 'Reservas',
                'post_content' => '[ttra_reservas]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => 1,
            ) );
            update_option( 'ttra_page_reservas', $page_id );
        }
    }
}