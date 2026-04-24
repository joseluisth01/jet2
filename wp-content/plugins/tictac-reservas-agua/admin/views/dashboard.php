<?php
/**
 * Vista: Dashboard principal del plugin.
 * Variable disponible: $stats (array con total, pendientes, confirmadas, canceladas, ingresos)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Datos adicionales para el dashboard
global $wpdb;
$t_reservas = TTRA_DB::table( 'reservas' );
$t_lineas   = TTRA_DB::table( 'reserva_lineas' );
$t_act      = TTRA_DB::table( 'actividades' );
$t_cat      = TTRA_DB::table( 'categorias' );

// Últimas 10 reservas
$ultimas_reservas = $wpdb->get_results(
    "SELECT * FROM $t_reservas ORDER BY created_at DESC LIMIT 10"
);

// Reservas de hoy
$hoy = current_time( 'Y-m-d' );
$reservas_hoy = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(DISTINCT r.id) FROM $t_reservas r 
     INNER JOIN $t_lineas l ON l.reserva_id = r.id 
     WHERE l.fecha = %s AND r.estado IN ('confirmada','pagada')", $hoy
) );

// Actividades activas
$total_actividades = $wpdb->get_var( "SELECT COUNT(*) FROM $t_act WHERE activa = 1" );
$total_categorias  = $wpdb->get_var( "SELECT COUNT(*) FROM $t_cat WHERE activa = 1" );

// Top actividades más reservadas (últimos 30 días)
$top_actividades = $wpdb->get_results(
    "SELECT a.nombre, COUNT(l.id) as total_reservas, SUM(l.precio_total) as ingresos
     FROM $t_lineas l
     INNER JOIN $t_act a ON a.id = l.actividad_id
     INNER JOIN $t_reservas r ON r.id = l.reserva_id
     WHERE r.estado NOT IN ('cancelada') AND r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY a.id
     ORDER BY total_reservas DESC
     LIMIT 5"
);

$estados = TTRA_Helpers::estados_reserva();
?>

<div class="wrap ttra-admin-wrap">

    <h1 class="ttra-admin-title">
        <span class="dashicons dashicons-palmtree"></span>
        <?php esc_html_e( 'Reservas Agua — Dashboard', 'tictac-reservas-agua' ); ?>
    </h1>

    <?php if ( isset( $_GET['msg'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Cambios guardados correctamente.', 'tictac-reservas-agua' ); ?></p></div>
    <?php endif; ?>

    <!-- ══════════ STATS CARDS ══════════ -->
    <div class="ttra-stats-grid">

        <div class="ttra-stat-card ttra-stat-card--primary">
            <div class="ttra-stat-card__icon"><span class="dashicons dashicons-calendar-alt"></span></div>
            <div class="ttra-stat-card__data">
                <span class="ttra-stat-card__value"><?php echo intval( $stats['total'] ); ?></span>
                <span class="ttra-stat-card__label"><?php esc_html_e( 'Reservas (30 días)', 'tictac-reservas-agua' ); ?></span>
            </div>
        </div>

        <div class="ttra-stat-card ttra-stat-card--success">
            <div class="ttra-stat-card__icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="ttra-stat-card__data">
                <span class="ttra-stat-card__value"><?php echo intval( $stats['confirmadas'] ); ?></span>
                <span class="ttra-stat-card__label"><?php esc_html_e( 'Confirmadas', 'tictac-reservas-agua' ); ?></span>
            </div>
        </div>

        <div class="ttra-stat-card ttra-stat-card--warning">
            <div class="ttra-stat-card__icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="ttra-stat-card__data">
                <span class="ttra-stat-card__value"><?php echo intval( $stats['pendientes'] ); ?></span>
                <span class="ttra-stat-card__label"><?php esc_html_e( 'Pendientes', 'tictac-reservas-agua' ); ?></span>
            </div>
        </div>

        <div class="ttra-stat-card ttra-stat-card--danger">
            <div class="ttra-stat-card__icon"><span class="dashicons dashicons-dismiss"></span></div>
            <div class="ttra-stat-card__data">
                <span class="ttra-stat-card__value"><?php echo intval( $stats['canceladas'] ); ?></span>
                <span class="ttra-stat-card__label"><?php esc_html_e( 'Canceladas', 'tictac-reservas-agua' ); ?></span>
            </div>
        </div>

        <div class="ttra-stat-card ttra-stat-card--money">
            <div class="ttra-stat-card__icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div class="ttra-stat-card__data">
                <span class="ttra-stat-card__value"><?php echo TTRA_Helpers::formato_precio( $stats['ingresos'] ); ?></span>
                <span class="ttra-stat-card__label"><?php esc_html_e( 'Ingresos (30 días)', 'tictac-reservas-agua' ); ?></span>
            </div>
        </div>

        <div class="ttra-stat-card ttra-stat-card--info">
            <div class="ttra-stat-card__icon"><span class="dashicons dashicons-groups"></span></div>
            <div class="ttra-stat-card__data">
                <span class="ttra-stat-card__value"><?php echo intval( $reservas_hoy ); ?></span>
                <span class="ttra-stat-card__label"><?php esc_html_e( 'Reservas para hoy', 'tictac-reservas-agua' ); ?></span>
            </div>
        </div>

    </div>

    <!-- ══════════ LAYOUT 2 COLUMNAS ══════════ -->
    <div class="ttra-dashboard-grid">

        <!-- COLUMNA IZQUIERDA -->
        <div class="ttra-dashboard-col">

            <!-- Últimas reservas -->
            <div class="ttra-admin-card">
                <div class="ttra-admin-card__header">
                    <h2><?php esc_html_e( 'Últimas reservas', 'tictac-reservas-agua' ); ?></h2>
                    <a href="<?php echo admin_url( 'admin.php?page=ttra-reservas' ); ?>" class="button button-small">
                        <?php esc_html_e( 'Ver todas', 'tictac-reservas-agua' ); ?> →
                    </a>
                </div>

                <?php if ( empty( $ultimas_reservas ) ) : ?>
                    <p class="ttra-empty-msg"><?php esc_html_e( 'No hay reservas todavía.', 'tictac-reservas-agua' ); ?></p>
                <?php else : ?>
                    <table class="widefat ttra-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Código', 'tictac-reservas-agua' ); ?></th>
                                <th><?php esc_html_e( 'Cliente', 'tictac-reservas-agua' ); ?></th>
                                <th><?php esc_html_e( 'Total', 'tictac-reservas-agua' ); ?></th>
                                <th><?php esc_html_e( 'Estado', 'tictac-reservas-agua' ); ?></th>
                                <th><?php esc_html_e( 'Fecha', 'tictac-reservas-agua' ); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $ultimas_reservas as $r ) : 
                                $estado_info = $estados[ $r->estado ] ?? array( 'label' => $r->estado, 'color' => '#999' );
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html( $r->codigo_reserva ); ?></strong></td>
                                <td>
                                    <?php echo esc_html( $r->nombre ); ?><br>
                                    <small><?php echo esc_html( $r->email ); ?></small>
                                </td>
                                <td><strong><?php echo TTRA_Helpers::formato_precio( $r->total ); ?></strong></td>
                                <td>
                                    <span class="ttra-badge" style="background:<?php echo esc_attr( $estado_info['color'] ); ?>">
                                        <?php echo esc_html( $estado_info['label'] ); ?>
                                    </span>
                                </td>
                                <td><?php echo TTRA_Helpers::formato_fecha( $r->created_at, 'd/m/Y H:i' ); ?></td>
                                <td>
                                    <a href="<?php echo admin_url( 'admin.php?page=ttra-reservas&reserva_id=' . $r->id ); ?>" class="button button-small">
                                        <?php esc_html_e( 'Ver', 'tictac-reservas-agua' ); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </div>

        <!-- COLUMNA DERECHA -->
        <div class="ttra-dashboard-col ttra-dashboard-col--sidebar">

            <!-- Info rápida -->
            <div class="ttra-admin-card">
                <h2><?php esc_html_e( 'Información rápida', 'tictac-reservas-agua' ); ?></h2>
                <ul class="ttra-info-list">
                    <li>
                        <span class="dashicons dashicons-tag"></span>
                        <strong><?php echo intval( $total_categorias ); ?></strong>
                        <?php esc_html_e( 'categorías activas', 'tictac-reservas-agua' ); ?>
                    </li>
                    <li>
                        <span class="dashicons dashicons-admin-generic"></span>
                        <strong><?php echo intval( $total_actividades ); ?></strong>
                        <?php esc_html_e( 'actividades activas', 'tictac-reservas-agua' ); ?>
                    </li>
                    <li>
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php 
                        $page_id = get_option( 'ttra_page_reservas' );
                        if ( $page_id && get_post( $page_id ) ) :
                        ?>
                            <a href="<?php echo get_permalink( $page_id ); ?>" target="_blank">
                                <?php esc_html_e( 'Ver página de reservas', 'tictac-reservas-agua' ); ?> ↗
                            </a>
                        <?php else : ?>
                            <span style="color:#dc3545"><?php esc_html_e( 'Página de reservas no configurada', 'tictac-reservas-agua' ); ?></span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>

            <!-- Top actividades -->
            <div class="ttra-admin-card">
                <h2><?php esc_html_e( 'Top actividades (30 días)', 'tictac-reservas-agua' ); ?></h2>
                <?php if ( empty( $top_actividades ) ) : ?>
                    <p class="ttra-empty-msg"><?php esc_html_e( 'Sin datos todavía.', 'tictac-reservas-agua' ); ?></p>
                <?php else : ?>
                    <table class="widefat ttra-table ttra-table--compact">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Actividad', 'tictac-reservas-agua' ); ?></th>
                                <th><?php esc_html_e( 'Reservas', 'tictac-reservas-agua' ); ?></th>
                                <th><?php esc_html_e( 'Ingresos', 'tictac-reservas-agua' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $top_actividades as $ta ) : ?>
                            <tr>
                                <td><?php echo esc_html( $ta->nombre ); ?></td>
                                <td><strong><?php echo intval( $ta->total_reservas ); ?></strong></td>
                                <td><?php echo TTRA_Helpers::formato_precio( $ta->ingresos ); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Acciones rápidas -->
            <div class="ttra-admin-card">
                <h2><?php esc_html_e( 'Acciones rápidas', 'tictac-reservas-agua' ); ?></h2>
                <div class="ttra-quick-actions">
                    <a href="<?php echo admin_url( 'admin.php?page=ttra-categorias' ); ?>" class="button">
                        <span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Gestionar categorías', 'tictac-reservas-agua' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=ttra-actividades' ); ?>" class="button">
                        <span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Gestionar actividades', 'tictac-reservas-agua' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=ttra-horarios' ); ?>" class="button">
                        <span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Configurar horarios', 'tictac-reservas-agua' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=ttra-bloqueos' ); ?>" class="button">
                        <span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Bloquear fechas', 'tictac-reservas-agua' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=ttra-ajustes' ); ?>" class="button">
                        <span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Ajustes', 'tictac-reservas-agua' ); ?>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

<style>
/* ═══ Dashboard Styles ═══ */
.ttra-admin-wrap { max-width: 1400px; }
.ttra-admin-title { display: flex; align-items: center; gap: 8px; font-size: 23px; margin-bottom: 20px; }
.ttra-admin-title .dashicons { font-size: 28px; width: 28px; height: 28px; color: #0073aa; }

/* Stats Grid */
.ttra-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.ttra-stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border-left: 4px solid #ccc;
    transition: transform 0.15s ease;
}
.ttra-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.ttra-stat-card--primary { border-left-color: #0073aa; }
.ttra-stat-card--success { border-left-color: #46b450; }
.ttra-stat-card--warning { border-left-color: #ffb900; }
.ttra-stat-card--danger  { border-left-color: #dc3545; }
.ttra-stat-card--money   { border-left-color: #00a32a; }
.ttra-stat-card--info    { border-left-color: #00a0d2; }

.ttra-stat-card__icon { font-size: 0; }
.ttra-stat-card__icon .dashicons { font-size: 32px; width: 32px; height: 32px; opacity: 0.6; }
.ttra-stat-card--primary .dashicons { color: #0073aa; }
.ttra-stat-card--success .dashicons { color: #46b450; }
.ttra-stat-card--warning .dashicons { color: #ffb900; }
.ttra-stat-card--danger .dashicons  { color: #dc3545; }
.ttra-stat-card--money .dashicons   { color: #00a32a; }
.ttra-stat-card--info .dashicons    { color: #00a0d2; }

.ttra-stat-card__data { display: flex; flex-direction: column; }
.ttra-stat-card__value { font-size: 28px; font-weight: 700; line-height: 1.1; color: #1d2327; }
.ttra-stat-card__label { font-size: 13px; color: #646970; margin-top: 4px; }

/* Dashboard 2-col */
.ttra-dashboard-grid { display: grid; grid-template-columns: 1fr 380px; gap: 24px; }
.ttra-dashboard-col--sidebar { display: flex; flex-direction: column; gap: 16px; }

@media (max-width: 1200px) {
    .ttra-dashboard-grid { grid-template-columns: 1fr; }
}

/* Cards */
.ttra-admin-card {
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 8px;
    padding: 20px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.ttra-admin-card h2 { margin: 0 0 16px; font-size: 16px; color: #1d2327; }
.ttra-admin-card__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.ttra-admin-card__header h2 { margin: 0; }

/* Tables */
.ttra-table { border-collapse: collapse; }
.ttra-table th { background: #f6f7f7; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; color: #646970; padding: 10px 12px; }
.ttra-table td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f0f0f1; }
.ttra-table tbody tr:hover { background: #f6f7f7; }
.ttra-table--compact th, .ttra-table--compact td { padding: 8px 10px; font-size: 13px; }

/* Badge */
.ttra-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    white-space: nowrap;
}

/* Info list */
.ttra-info-list { list-style: none; padding: 0; margin: 0; }
.ttra-info-list li { display: flex; align-items: center; gap: 8px; padding: 10px 0; border-bottom: 1px solid #f0f0f1; font-size: 13px; }
.ttra-info-list li:last-child { border-bottom: none; }
.ttra-info-list .dashicons { color: #646970; font-size: 18px; width: 18px; height: 18px; }

/* Quick actions */
.ttra-quick-actions { display: flex; flex-direction: column; gap: 8px; }
.ttra-quick-actions .button { display: flex; align-items: center; gap: 6px; justify-content: flex-start; width: 100%; text-align: left; }
.ttra-quick-actions .dashicons { font-size: 16px; width: 16px; height: 16px; }

/* Empty message */
.ttra-empty-msg { color: #646970; font-style: italic; padding: 20px 0; text-align: center; }
</style>