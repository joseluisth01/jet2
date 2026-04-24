<?php
/**
 * Vista admin: Log de emails enviados.
 * Variables: $items (array), $total (int), $page (int)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$pages = ceil( $total / 20 );
$tipo_filter = sanitize_text_field( $_GET['tipo'] ?? '' );
$estado_filter = sanitize_text_field( $_GET['estado'] ?? '' );

// Stats por tipo
global $wpdb;
$t_log = TTRA_DB::table( 'email_log' );
$tipos_info = [
    'confirmacion'      => ['label' => 'Confirmación',     'icon' => '✅'],
    'cancelacion'       => ['label' => 'Cancelación',      'icon' => '❌'],
    'recordatorio'      => ['label' => 'Recordatorio',     'icon' => '🔔'],
    'admin_nueva'       => ['label' => 'Nueva (admin)',    'icon' => '👤'],
];
$stats_tipo = [];
foreach ( array_keys( $tipos_info ) as $tipo ) {
    $stats_tipo[$tipo] = [
        'total'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t_log WHERE tipo = %s", $tipo ) ),
        'enviados'=> (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t_log WHERE tipo = %s AND estado = 'enviado'", $tipo ) ),
        'fallidos'=> (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t_log WHERE tipo = %s AND estado = 'fallido'", $tipo ) ),
    ];
}
?>

<div class="wrap ttra-admin-wrap">

    <h1 class="ttra-admin-title">
        <span class="dashicons dashicons-email-alt"></span>
        <?php esc_html_e( 'Log de Emails', 'tictac-reservas-agua' ); ?>
        <span class="ttra-count-badge"><?php echo $total; ?></span>
    </h1>

    <!-- Stats por tipo -->
    <div class="ttra-stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:24px">
        <?php foreach ( $tipos_info as $tipo_key => $tipo_data ) :
            $s = $stats_tipo[$tipo_key];
        ?>
        <div class="ttra-stat-card ttra-stat-card--info" style="cursor:pointer"
             onclick="window.location='?page=ttra-emails&tipo=<?php echo $tipo_key; ?>'">
            <div class="ttra-stat-card__icon"><span style="font-size:28px"><?php echo $tipo_data['icon']; ?></span></div>
            <div class="ttra-stat-card__data">
                <span class="ttra-stat-card__value"><?php echo $s['total']; ?></span>
                <span class="ttra-stat-card__label"><?php echo esc_html( $tipo_data['label'] ); ?></span>
                <small style="color:#646970">
                    ✅ <?php echo $s['enviados']; ?>
                    <?php if ($s['fallidos']) : ?> · ❌ <span style="color:#dc3545"><?php echo $s['fallidos']; ?></span><?php endif; ?>
                </small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="ttra-admin-card" style="padding:16px 20px;margin-bottom:0;border-bottom-left-radius:0;border-bottom-right-radius:0">
        <form method="GET" action="" class="ttra-filter-bar">
            <input type="hidden" name="page" value="ttra-emails">
            <select name="tipo">
                <option value=""><?php esc_html_e( 'Todos los tipos', 'tictac-reservas-agua' ); ?></option>
                <?php foreach ( $tipos_info as $tk => $td ) : ?>
                    <option value="<?php echo $tk; ?>" <?php selected( $tipo_filter, $tk ); ?>>
                        <?php echo esc_html( $td['icon'] . ' ' . $td['label'] ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="estado">
                <option value=""><?php esc_html_e( 'Todos los estados', 'tictac-reservas-agua' ); ?></option>
                <option value="enviado" <?php selected( $estado_filter, 'enviado' ); ?>>✅ <?php esc_html_e( 'Enviado', 'tictac-reservas-agua' ); ?></option>
                <option value="fallido" <?php selected( $estado_filter, 'fallido' ); ?>>❌ <?php esc_html_e( 'Fallido', 'tictac-reservas-agua' ); ?></option>
            </select>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Filtrar', 'tictac-reservas-agua' ); ?></button>
            <?php if ( $tipo_filter || $estado_filter ) : ?>
                <a href="<?php echo admin_url( 'admin.php?page=ttra-emails' ); ?>" class="button">✕ <?php esc_html_e( 'Limpiar', 'tictac-reservas-agua' ); ?></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla -->
    <div class="ttra-admin-card ttra-card--full" style="border-top-left-radius:0;border-top-right-radius:0">

        <?php if ( empty( $items ) ) : ?>
            <p class="ttra-empty-msg">📧 <?php esc_html_e( 'No hay emails en el log todavía.', 'tictac-reservas-agua' ); ?></p>
        <?php else : ?>

            <table class="widefat ttra-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Fecha', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Tipo', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Destinatario', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Asunto', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Reserva', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Estado', 'tictac-reservas-agua' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $items as $log ) :
                        $tipo_i = $tipos_info[$log->tipo] ?? ['icon' => '📧', 'label' => $log->tipo];
                    ?>
                    <tr class="<?php echo $log->estado === 'fallido' ? 'ttra-row-error' : ''; ?>">
                        <td><small><?php echo TTRA_Helpers::formato_fecha( $log->created_at, 'd/m/Y H:i' ); ?></small></td>
                        <td>
                            <span class="ttra-badge ttra-badge--info" style="font-size:11px">
                                <?php echo $tipo_i['icon']; ?> <?php echo esc_html( $tipo_i['label'] ); ?>
                            </span>
                        </td>
                        <td><small><?php echo esc_html( $log->destinatario ); ?></small></td>
                        <td><small><?php echo esc_html( $log->asunto ); ?></small></td>
                        <td>
                            <?php if ( $log->reserva_id ) : ?>
                                <a href="<?php echo admin_url( 'admin.php?page=ttra-reservas&reserva_id=' . $log->reserva_id ); ?>"
                                   class="button button-small">
                                    #<?php echo intval( $log->reserva_id ); ?>
                                </a>
                            <?php else : ?>
                                <span class="ttra-text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="ttra-badge ttra-badge--<?php echo $log->estado === 'enviado' ? 'success' : 'danger'; ?>">
                                <?php echo $log->estado === 'enviado' ? '✅ ' . esc_html__( 'Enviado', 'tictac-reservas-agua' ) : '❌ ' . esc_html__( 'Fallido', 'tictac-reservas-agua' ); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ( $pages > 1 ) : ?>
            <div class="ttra-pagination">
                <div class="ttra-pagination__links">
                    <?php for ( $p = 1; $p <= $pages; $p++ ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $p ) ); ?>"
                           class="button <?php echo $p == $page ? 'button-primary' : ''; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

</div>
