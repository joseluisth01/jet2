<?php
/**
 * Vista admin: Listado de Reservas.
 * Variables: $resultado (items, total, pages), $args
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nonce    = wp_create_nonce( 'ttra_admin_nonce' );
$estados  = TTRA_Helpers::estados_reserva();
$items    = $resultado['items'] ?? [];
$total    = $resultado['total'] ?? 0;
$pages    = $resultado['pages'] ?? 1;
$cur_page = $args['page'] ?? 1;

// Conteo por estado
global $wpdb;
$t_res = TTRA_DB::table( 'reservas' );
$conteos = [];
foreach ( array_keys( $estados ) as $est ) {
    $conteos[ $est ] = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $t_res WHERE estado = %s", $est
    ) );
}
$conteos[''] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t_res" );

$base_url = admin_url( 'admin.php?page=ttra-reservas' );
?>

<div class="wrap ttra-admin-wrap">

    <h1 class="ttra-admin-title">
        <span class="dashicons dashicons-calendar-alt"></span>
        <?php esc_html_e( 'Reservas', 'tictac-reservas-agua' ); ?>
        <span class="ttra-count-badge" style="margin-left:8px"><?php echo $conteos['']; ?></span>

        <!-- Exportar CSV -->
        <form method="POST" style="display:inline;margin-left:auto">
            <input type="hidden" name="ttra_action" value="export_csv">
            <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
            <?php if ( $args['estado'] )     : ?><input type="hidden" name="estado"      value="<?php echo esc_attr( $args['estado'] ); ?>"><?php endif; ?>
            <?php if ( $args['buscar'] )     : ?><input type="hidden" name="buscar"      value="<?php echo esc_attr( $args['buscar'] ); ?>"><?php endif; ?>
            <?php if ( $args['fecha_desde'] ) : ?><input type="hidden" name="fecha_desde" value="<?php echo esc_attr( $args['fecha_desde'] ); ?>"><?php endif; ?>
            <?php if ( $args['fecha_hasta'] ) : ?><input type="hidden" name="fecha_hasta" value="<?php echo esc_attr( $args['fecha_hasta'] ); ?>"><?php endif; ?>
            <button type="submit" class="button">
                📥 <?php esc_html_e( 'Exportar CSV', 'tictac-reservas-agua' ); ?>
            </button>
        </form>
    </h1>

    <!-- ══ FILTROS ══ -->
    <div class="ttra-admin-card" style="padding:16px 20px">
        <form method="GET" action="" class="ttra-filter-bar">
            <input type="hidden" name="page" value="ttra-reservas">
            <input type="text" name="buscar" value="<?php echo esc_attr( $args['buscar'] ); ?>"
                   placeholder="🔍 <?php esc_attr_e( 'Buscar por nombre, email, código...', 'tictac-reservas-agua' ); ?>"
                   class="regular-text">
            <input type="date" name="fecha_desde" value="<?php echo esc_attr( $args['fecha_desde'] ); ?>"
                   title="<?php esc_attr_e( 'Desde', 'tictac-reservas-agua' ); ?>">
            <input type="date" name="fecha_hasta" value="<?php echo esc_attr( $args['fecha_hasta'] ); ?>"
                   title="<?php esc_attr_e( 'Hasta', 'tictac-reservas-agua' ); ?>">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Filtrar', 'tictac-reservas-agua' ); ?></button>
            <?php if ( $args['buscar'] || $args['fecha_desde'] || $args['fecha_hasta'] ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'estado', $args['estado'], $base_url ) ); ?>" class="button">✕ <?php esc_html_e( 'Limpiar', 'tictac-reservas-agua' ); ?></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ══ TABS DE ESTADO ══ -->
    <div class="ttra-status-tabs">
        <a href="<?php echo esc_url( $base_url ); ?>" class="ttra-status-tab <?php echo !$args['estado'] ? 'ttra-status-tab--active' : ''; ?>">
            <?php esc_html_e( 'Todas', 'tictac-reservas-agua' ); ?>
            <span class="ttra-status-tab__count"><?php echo $conteos['']; ?></span>
        </a>
        <?php foreach ( $estados as $est_key => $est_info ) : ?>
            <a href="<?php echo esc_url( add_query_arg( 'estado', $est_key, $base_url ) ); ?>"
               class="ttra-status-tab <?php echo $args['estado'] === $est_key ? 'ttra-status-tab--active' : ''; ?>"
               style="--tab-color:<?php echo esc_attr( $est_info['color'] ); ?>">
                <?php echo esc_html( $est_info['label'] ); ?>
                <span class="ttra-status-tab__count"><?php echo $conteos[ $est_key ] ?? 0; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ══ TABLA ══ -->
    <div class="ttra-admin-card ttra-card--full" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0">

        <?php if ( empty( $items ) ) : ?>
            <p class="ttra-empty-msg">
                📋 <?php esc_html_e( 'No se encontraron reservas con los filtros aplicados.', 'tictac-reservas-agua' ); ?>
            </p>
        <?php else : ?>

            <table class="widefat ttra-table ttra-table--reservas">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Código', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Cliente', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Actividades', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Total', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Pago', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Estado', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Fecha reserva', 'tictac-reservas-agua' ); ?></th>
                        <th style="width:80px"><?php esc_html_e( 'Acción', 'tictac-reservas-agua' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $items as $r ) :
                        $est = $estados[ $r->estado ] ?? ['label' => $r->estado, 'color' => '#999'];
                        $lineas = TTRA_Reserva::get_lineas( $r->id );
                    ?>
                    <tr class="ttra-row-reserva">
                        <td>
                            <strong class="ttra-codigo"><?php echo esc_html( $r->codigo_reserva ); ?></strong>
                        </td>
                        <td>
                            <div class="ttra-cliente-cell">
                                <strong><?php echo esc_html( trim( $r->nombre . ' ' . $r->apellidos ) ); ?></strong>
                                <small><a href="mailto:<?php echo esc_attr( $r->email ); ?>"><?php echo esc_html( $r->email ); ?></a></small>
                                <small>📞 <?php echo esc_html( $r->telefono ); ?></small>
                            </div>
                        </td>
                        <td>
                            <?php foreach ( $lineas as $linea ) : ?>
                                <div class="ttra-linea-mini">
                                    <span class="ttra-linea-mini__act"><?php echo esc_html( $linea->actividad_nombre ); ?></span>
                                    <small class="ttra-text-muted">
                                        📅 <?php echo date_i18n( 'd/m/Y', strtotime( $linea->fecha ) ); ?>
                                        🕐 <?php echo esc_html( substr( $linea->hora, 0, 5 ) ); ?>
                                        · <?php echo intval( $linea->personas ); ?> <?php esc_html_e( 'pax', 'tictac-reservas-agua' ); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <strong class="ttra-price-cell"><?php echo TTRA_Helpers::formato_precio( $r->total ); ?></strong>
                            <?php if ( $r->descuento > 0 ) : ?>
                                <br><small class="ttra-text-muted">-<?php echo TTRA_Helpers::formato_precio( $r->descuento ); ?> dto.</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $r->pagado ) : ?>
                                <span class="ttra-badge ttra-badge--success">✅ <?php echo esc_html( strtoupper( $r->metodo_pago ) ); ?></span>
                            <?php else : ?>
                                <span class="ttra-badge ttra-badge--muted">⏳ <?php esc_html_e( 'Pendiente', 'tictac-reservas-agua' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="ttra-inline-estado-form">
                                <input type="hidden" name="ttra_action" value="cambiar_estado_reserva">
                                <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
                                <input type="hidden" name="reserva_id" value="<?php echo intval( $r->id ); ?>">
                                <select name="nuevo_estado" class="ttra-estado-select ttra-estado-select--inline"
                                        style="border-color:<?php echo esc_attr( $est['color'] ); ?>">
                                    <?php foreach ( $estados as $k => $v ) : ?>
                                        <option value="<?php echo $k; ?>" <?php selected( $r->estado, $k ); ?>>
                                            <?php echo esc_html( $v['label'] ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <small><?php echo TTRA_Helpers::formato_fecha( $r->created_at, 'd/m/Y' ); ?></small>
                            <br><small class="ttra-text-muted"><?php echo TTRA_Helpers::formato_fecha( $r->created_at, 'H:i' ); ?></small>
                        </td>
                        <td>
                            <a href="<?php echo admin_url( 'admin.php?page=ttra-reservas&reserva_id=' . $r->id ); ?>"
                               class="button button-small button-primary">
                                👁️ <?php esc_html_e( 'Ver', 'tictac-reservas-agua' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ( $pages > 1 ) : ?>
            <div class="ttra-pagination">
                <span class="ttra-pagination__info">
                    <?php printf(
                        esc_html__( 'Mostrando %1$d–%2$d de %3$d reservas', 'tictac-reservas-agua' ),
                        ( $cur_page - 1 ) * 20 + 1,
                        min( $cur_page * 20, $total ),
                        $total
                    ); ?>
                </span>
                <div class="ttra-pagination__links">
                    <?php if ( $cur_page > 1 ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $cur_page - 1 ) ); ?>" class="button">← <?php esc_html_e( 'Anterior', 'tictac-reservas-agua' ); ?></a>
                    <?php endif; ?>
                    <?php for ( $p = max(1, $cur_page-2); $p <= min($pages, $cur_page+2); $p++ ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $p ) ); ?>"
                           class="button <?php echo $p == $cur_page ? 'button-primary' : ''; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ( $cur_page < $pages ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $cur_page + 1 ) ); ?>" class="button"><?php esc_html_e( 'Siguiente', 'tictac-reservas-agua' ); ?> →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

</div>

<script>
(function() {
    // Cambio de estado inline → submit automático con confirmación para cancelar
    document.querySelectorAll('.ttra-estado-select--inline').forEach(sel => {
        const original = sel.value;
        sel.addEventListener('change', function() {
            const nuevo = this.value;
            let msg = '¿Cambiar estado a "' + this.options[this.selectedIndex].text + '"?';
            if (nuevo === 'cancelada') msg = '⚠️ ¿Cancelar esta reserva? Se enviará email al cliente.';
            if (confirm(msg)) {
                this.closest('form').submit();
            } else {
                this.value = original;
            }
        });
    });
})();
</script>
