<?php
/**
 * Plugin Name: TicTac Reservas Agua
 * Plugin URI: https://tictaccomunicacion.com
 * Description: Sistema completo de reservas para actividadees acuáticas con calendario, pasarela de pago Redsys, notificaciones por correo y panel de administración.
 * Version: 1.0.0
 * Author: TicTac Comunicación
 * Author URI: https://tictaccomunicacion.com
 * License: GPL v2 or later
 * Text Domain: tictac-reservas-agua
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ─── Constants ─── */
define( 'TTRA_VERSION', '1.0.0' );
define( 'TTRA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TTRA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TTRA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'TTRA_DB_VERSION', '1.0.0' );

/* ─── Autoloader ─── */
require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-autoloader.php';
TTRA_Autoloader::register();

/* ─── Core Includes ─── */
require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-activator.php';
require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-deactivator.php';
require_once TTRA_PLUGIN_DIR . 'includes/class-ttra-plugin.php';

/* ─── Activation / Deactivation Hooks ─── */
register_activation_hook( __FILE__, array( 'TTRA_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TTRA_Deactivator', 'deactivate' ) );

/* ─── Boot the Plugin ─── */
function ttra_init() {
    $plugin = new TTRA_Plugin();
    $plugin->run();
}
add_action( 'plugins_loaded', 'ttra_init' );
