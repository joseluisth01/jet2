<?php

/**
 * Clase frontend del plugin.
 * Registra shortcodes y carga assets públicos.
 */

if (! defined('ABSPATH')) exit;

class TTRA_Public
{

    public function __construct()
    {
        add_shortcode('ttra_reservas', array($this, 'shortcode_reservas'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Handle resultado de pago (return URL)
        add_action('template_redirect', array($this, 'handle_payment_return'));
    }

    /**
     * Assets frontend (solo se cargan donde está el shortcode).
     */
    public function enqueue_assets()
    {
        global $post;

        // Solo cargar si la página tiene el shortcode
        if (! is_a($post, 'WP_Post') || ! has_shortcode($post->post_content, 'ttra_reservas')) {
            return;
        }

        // CSS principal
        wp_enqueue_style(
            'ttra-public-css',
            TTRA_PLUGIN_URL . 'public/css/reservas.css',
            array(),
            TTRA_VERSION
        );

        // Google Fonts
        wp_enqueue_style(
            'ttra-fonts',
            'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Open+Sans:wght@400;600;700&display=swap',
            array(),
            null
        );

        // JS principal
        wp_enqueue_script(
            'ttra-public-js',
            TTRA_PLUGIN_URL . 'public/js/reservas.js',
            array(),
            TTRA_VERSION,
            true
        );

        // Variables CSS dinámicas desde settings
        $css_vars = TTRA_Settings::get_css_vars();
        $inline_css = ':root {';
        foreach ($css_vars as $var => $val) {
            $inline_css .= "$var: $val;";
        }
        $inline_css .= '}';
        wp_add_inline_style('ttra-public-css', $inline_css);

        // Pasar datos al JS
        wp_localize_script('ttra-public-js', 'ttra_config', array(
            'rest_url'        => rest_url('ttra/v1/'),
            'nonce'           => wp_create_nonce('wp_rest'),
            'currency'        => TTRA_Settings::get('moneda', 'EUR'),
            'currency_symbol' => '€',
            'uploads_url'     => wp_upload_dir()['baseurl'],
            'metodos_pago'    => TTRA_Settings::metodos_pago_activos(),
            'labels'          => array(
                'cancelacion_gratuita' => __('Cancelación gratuita', 'tictac-reservas-agua'),
                'no_fianza'            => __('No se requiere fianza', 'tictac-reservas-agua'),
                'pago_seguro'          => __('Pago seguro', 'tictac-reservas-agua'),
                'equipo_seguridad'     => __('Equipo de seguridad y seguro', 'tictac-reservas-agua'),
            ),
            'badges' => array(
                'cancelacion' => (bool) TTRA_Settings::get('mostrar_cancelacion_gratuita', 1),
                'fianza'      => (bool) TTRA_Settings::get('mostrar_sin_fianza', 1),
                'pago_seguro' => (bool) TTRA_Settings::get('mostrar_pago_seguro', 1),
                'equipo'      => (bool) TTRA_Settings::get('mostrar_equipo_seguridad', 1),
            ),
            'i18n' => array(
                'paso1_titulo'      => __('ELIGE LAS ACTIVIDADES DESEADAS', 'tictac-reservas-agua'),
                'paso1_desc'        => __('Selecciona una o más actividades, elige número de personas participantes y cantidad de sesiones. En el siguiente paso concreta el día y la hora que quieres.', 'tictac-reservas-agua'),
                'paso2_titulo'      => __('SELECCIONA FECHA Y HORA', 'tictac-reservas-agua'),
                'paso2_desc'        => __('Elige fecha y hora por cada una de las actividades seleccionadas en tu reserva. En el siguiente paso facilita tus datos personales.', 'tictac-reservas-agua'),
                'paso3_titulo'      => __('RELLENA CON TUS DATOS', 'tictac-reservas-agua'),
                'paso3_desc'        => __('Facilita tus datos para continuar avanzando en tu reserva. En el siguiente paso aporta la información necesaria sobre el pago de la reserva de actividades.', 'tictac-reservas-agua'),
                'paso4_titulo'      => __('FINALIZA: MÉTODOS DE PAGO', 'tictac-reservas-agua'),
                'paso4_desc'        => __('Remata el proceso de reserva seleccionando el método de pago y aceptando términos. Se requiere el pago completo al realizar la reserva para asegurar tu plaza. No se ofrece opción de depósito para esta actividad.', 'tictac-reservas-agua'),
                'personas'          => __('Personas', 'tictac-reservas-agua'),
                'sesiones'          => __('Sesiones', 'tictac-reservas-agua'),
                'continuar'         => __('CONTINUAR', 'tictac-reservas-agua'),
                'retroceder'        => __('RETROCEDER', 'tictac-reservas-agua'),
                'finalizar'         => __('FINALIZAR RESERVA', 'tictac-reservas-agua'),
                'resumen'           => __('RESUMEN DE LA RESERVA', 'tictac-reservas-agua'),
                'total'             => __('Total', 'tictac-reservas-agua'),
                'seleccion_finalizada' => __('SELECCIÓN FINALIZADA', 'tictac-reservas-agua'),
                'horarios_disponibles' => __('HORARIOS DISPONIBLES', 'tictac-reservas-agua'),
                'todo'              => __('Todo', 'tictac-reservas-agua'),
            ),
        ));
    }

    /**
     * Shortcode principal: [ttra_reservas]
     */
    public function shortcode_reservas($atts)
    {
        $atts = shortcode_atts(array(
            'categoria' => '',
        ), $atts, 'ttra_reservas');

        ob_start();
        include TTRA_PLUGIN_DIR . 'public/views/reservas-app.php';
        return ob_get_clean();
    }

    /**
     * Handle return URL de Redsys.
     */
    public function handle_payment_return()
    {
        if (empty($_GET['ttra_result'])) return;

        $result = sanitize_text_field($_GET['ttra_result']);
        $codigo = sanitize_text_field($_GET['code'] ?? '');

        // Se maneja en el JS frontend
    }
}
