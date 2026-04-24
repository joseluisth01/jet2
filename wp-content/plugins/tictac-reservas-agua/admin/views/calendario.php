<?php
/**
 * Vista admin: Calendario de Reservas.
 * Muestra un calendario mensual con las reservas agrupadas por día.
 * Al hacer clic en una reserva se abre un modal con el detalle completo.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

// ── Mes y año activos ───────────────────────────────────────────────────────
$hoy    = current_time( 'Y-m-d' );
$anyo   = intval( $_GET['anyo']  ?? date( 'Y', strtotime( $hoy ) ) );
$mes    = intval( $_GET['mes']   ?? date( 'n', strtotime( $hoy ) ) );
$filtro_act = intval( $_GET['actividad_id'] ?? 0 );

// Navegar mes anterior / siguiente
$prev_mes  = $mes - 1;
$prev_anyo = $anyo;
if ( $prev_mes < 1 )  { $prev_mes = 12; $prev_anyo--; }
$next_mes  = $mes + 1;
$next_anyo = $anyo;
if ( $next_mes > 12 ) { $next_mes = 1;  $next_anyo++; }

$primer_dia   = mktime( 0, 0, 0, $mes, 1, $anyo );
$dias_en_mes  = (int) date( 't', $primer_dia );
$dia_semana_inicio = (int) date( 'N', $primer_dia ); // 1=Lun … 7=Dom

$meses_es = [
    1  => 'Enero',    2  => 'Febrero',  3  => 'Marzo',
    4  => 'Abril',    5  => 'Mayo',     6  => 'Junio',
    7  => 'Julio',    8  => 'Agosto',   9  => 'Septiembre',
    10 => 'Octubre',  11 => 'Noviembre', 12 => 'Diciembre',
];

// ── Cargar todas las reservas del mes ──────────────────────────────────────
$t_reservas = TTRA_DB::table( 'reservas' );
$t_lineas   = TTRA_DB::table( 'reserva_lineas' );
$t_act      = TTRA_DB::table( 'actividades' );

$fecha_inicio = sprintf( '%04d-%02d-01', $anyo, $mes );
$fecha_fin    = sprintf( '%04d-%02d-%02d', $anyo, $mes, $dias_en_mes );

$where_act = $filtro_act ? $wpdb->prepare( ' AND l.actividad_id = %d', $filtro_act ) : '';

$lineas_mes = $wpdb->get_results( $wpdb->prepare(
    "SELECT l.fecha, l.hora, l.personas, l.sesiones, l.precio_total,
            a.nombre as actividad_nombre, a.subtipo,
            r.id as reserva_id, r.codigo_reserva, r.nombre, r.apellidos,
            r.email, r.telefono, r.estado, r.total, r.pagado, r.metodo_pago,
            r.dni_pasaporte, r.created_at, r.notas, r.descuento, r.subtotal
     FROM $t_lineas l
     INNER JOIN $t_reservas r ON r.id = l.reserva_id
     INNER JOIN $t_act a ON a.id = l.actividad_id
     WHERE l.fecha BETWEEN %s AND %s
       AND r.estado NOT IN ('cancelada')
       $where_act
     ORDER BY l.fecha ASC, l.hora ASC",
    $fecha_inicio,
    $fecha_fin
) );

// Agrupar por día
$reservas_por_dia = [];
foreach ( $lineas_mes as $linea ) {
    $d = (int) date( 'j', strtotime( $linea->fecha ) );
    $reservas_por_dia[ $d ][] = $linea;
}

// Cargar también las reservas completas para el modal (datos únicos por reserva_id)
$reservas_detalle = [];
foreach ( $lineas_mes as $linea ) {
    $rid = $linea->reserva_id;
    if ( ! isset( $reservas_detalle[ $rid ] ) ) {
        $reservas_detalle[ $rid ] = [
            'info'   => $linea,
            'lineas' => [],
        ];
    }
    $reservas_detalle[ $rid ]['lineas'][] = $linea;
}

// Lista de actividades para el filtro
$actividades = TTRA_Actividad::get_all();

$estados = TTRA_Helpers::estados_reserva();
$nonce   = wp_create_nonce( 'ttra_admin_nonce' );

// Estadísticas rápidas del mes
$total_reservas_mes  = count( array_unique( array_column( $lineas_mes, 'reserva_id' ) ) );
$total_personas_mes  = array_sum( array_column( $lineas_mes, 'personas' ) );
$total_ingresos_mes  = array_sum( array_map( fn($l) => floatval($l->total), array_unique_by( $lineas_mes, 'reserva_id' ) ) );

function array_unique_by( $array, $key ) {
    $seen = [];
    $result = [];
    foreach ( $array as $item ) {
        $val = is_object($item) ? $item->$key : $item[$key];
        if ( ! in_array( $val, $seen ) ) {
            $seen[] = $val;
            $result[] = $item;
        }
    }
    return $result;
}
?>

<div class="wrap ttra-admin-wrap">

    <h1 class="ttra-admin-title">
        <span class="dashicons dashicons-calendar-alt"></span>
        <?php esc_html_e( 'Calendario de Reservas', 'tictac-reservas-agua' ); ?>
    </h1>

    <!-- ── STATS DEL MES ── -->
    <div class="ttra-stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
        <div class="ttra-stat-card ttra-stat-card--info">
            <div class="ttra-stat-card__icon"><span class="dashicons dashicons-calendar-alt"></span></div>
            <div class="ttra-stat-card__data">
                <span class="ttra-stat-card__value"><?php echo $total_reservas_mes; ?></span>
                <span class="ttra-stat-card__label"><?php echo esc_html( $meses_es[$mes] . ' ' . $anyo ); ?></span>
            </div>
        </div>
        <div class="ttra-stat-card ttra-stat-card--primary">
            <div class="ttra-stat-card__icon"><span class="dashicons dashicons-groups"></span></div>
            <div class="ttra-stat-card__data">
                <span class="ttra-stat-card__value"><?php echo $total_personas_mes; ?></span>
                <span class="ttra-stat-card__label"><?php esc_html_e( 'Personas este mes', 'tictac-reservas-agua' ); ?></span>
            </div>
        </div>
        <div class="ttra-stat-card ttra-stat-card--money">
            <div class="ttra-stat-card__icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div class="ttra-stat-card__data">
                <span class="ttra-stat-card__value"><?php echo TTRA_Helpers::formato_precio( $total_ingresos_mes ); ?></span>
                <span class="ttra-stat-card__label"><?php esc_html_e( 'Ingresos este mes', 'tictac-reservas-agua' ); ?></span>
            </div>
        </div>
    </div>

    <!-- ── CONTROLES: navegación + filtro ── -->
    <div class="ttra-admin-card" style="padding:16px 20px;margin-bottom:0;border-bottom-left-radius:0;border-bottom-right-radius:0">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;justify-content:space-between">

            <!-- Navegación mes -->
            <div style="display:flex;align-items:center;gap:8px">
                <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'ttra-calendario', 'mes' => $prev_mes, 'anyo' => $prev_anyo ] ) ); ?>"
                   class="button">‹ <?php echo esc_html( $meses_es[$prev_mes] ); ?></a>

                <h2 style="margin:0;font-size:20px;font-weight:700;color:#003B6F;min-width:200px;text-align:center">
                    <?php echo esc_html( $meses_es[$mes] . ' ' . $anyo ); ?>
                </h2>

                <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'ttra-calendario', 'mes' => $next_mes, 'anyo' => $next_anyo ] ) ); ?>"
                   class="button"><?php echo esc_html( $meses_es[$next_mes] ); ?> ›</a>

                <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'ttra-calendario', 'mes' => date('n'), 'anyo' => date('Y') ] ) ); ?>"
                   class="button button-secondary">📅 Hoy</a>
            </div>

            <!-- Filtro actividad -->
            <form method="GET" action="" style="display:flex;gap:8px;align-items:center">
                <input type="hidden" name="page" value="ttra-calendario">
                <input type="hidden" name="mes" value="<?php echo $mes; ?>">
                <input type="hidden" name="anyo" value="<?php echo $anyo; ?>">
                <select name="actividad_id" style="border:1px solid #8c8f94;border-radius:4px;padding:6px 10px;font-size:13px">
                    <option value="0"><?php esc_html_e( 'Todas las actividades', 'tictac-reservas-agua' ); ?></option>
                    <?php foreach ( $actividades as $act ) : ?>
                        <option value="<?php echo $act->id; ?>" <?php selected( $filtro_act, $act->id ); ?>>
                            <?php echo esc_html( $act->nombre . ( $act->subtipo ? ' — ' . $act->subtipo : '' ) ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button button-primary">Filtrar</button>
                <?php if ( $filtro_act ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'ttra-calendario', 'mes' => $mes, 'anyo' => $anyo ] ) ); ?>"
                       class="button">✕ Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- ── CALENDARIO ── -->
    <div class="ttra-admin-card ttra-card--full ttra-cal-admin" style="border-top-left-radius:0;border-top-right-radius:0;padding:0;overflow:hidden">

        <!-- Cabecera días semana -->
        <div class="ttra-cal-admin__header">
            <?php foreach ( ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dia_label ) : ?>
                <div class="ttra-cal-admin__header-cell"><?php echo $dia_label; ?></div>
            <?php endforeach; ?>
        </div>

        <!-- Cuadrícula días -->
        <div class="ttra-cal-admin__grid">

            <?php
            // Celdas vacías al inicio
            for ( $i = 1; $i < $dia_semana_inicio; $i++ ) :
            ?>
                <div class="ttra-cal-admin__cell ttra-cal-admin__cell--empty"></div>
            <?php endfor; ?>

            <?php for ( $dia = 1; $dia <= $dias_en_mes; $dia++ ) :
                $fecha_celda = sprintf( '%04d-%02d-%02d', $anyo, $mes, $dia );
                $es_hoy      = $fecha_celda === $hoy;
                $es_pasado   = $fecha_celda < $hoy;
                $reservas_dia = $reservas_por_dia[ $dia ] ?? [];
                $tiene_res    = ! empty( $reservas_dia );
                $num_reservas = count( array_unique( array_column( $reservas_dia, 'reserva_id' ) ) );

                $cell_class = 'ttra-cal-admin__cell';
                if ( $es_hoy )    $cell_class .= ' ttra-cal-admin__cell--hoy';
                if ( $es_pasado ) $cell_class .= ' ttra-cal-admin__cell--pasado';
                if ( $tiene_res ) $cell_class .= ' ttra-cal-admin__cell--tiene-reservas';
            ?>
            <div class="<?php echo $cell_class; ?>" data-fecha="<?php echo $fecha_celda; ?>">

                <!-- Número del día -->
                <div class="ttra-cal-admin__dia-num">
                    <?php echo $dia; ?>
                    <?php if ( $tiene_res ) : ?>
                        <span class="ttra-cal-admin__dia-count"><?php echo $num_reservas; ?></span>
                    <?php endif; ?>
                </div>

                <!-- Lista de reservas del día -->
                <?php if ( $tiene_res ) :
                    // Agrupar por reserva_id para no repetir
                    $reservas_unicas_dia = [];
                    foreach ( $reservas_dia as $linea ) {
                        $rid = $linea->reserva_id;
                        if ( ! isset( $reservas_unicas_dia[$rid] ) ) {
                            $reservas_unicas_dia[$rid] = $linea;
                        }
                    }
                    // Mostrar máximo 3 en la celda, el resto con "+N más"
                    $mostrar    = array_slice( $reservas_unicas_dia, 0, 3, true );
                    $resto      = count( $reservas_unicas_dia ) - count( $mostrar );
                ?>
                    <div class="ttra-cal-admin__reservas">
                        <?php foreach ( $mostrar as $rid => $linea ) :
                            $est     = $estados[ $linea->estado ] ?? [ 'label' => $linea->estado, 'color' => '#999' ];
                            $lineas_reserva = array_filter( $reservas_dia, fn($l) => $l->reserva_id == $rid );
                        ?>
                            <button class="ttra-cal-admin__pill"
                                    style="border-left-color:<?php echo esc_attr( $est['color'] ); ?>"
                                    data-reserva="<?php echo $rid; ?>"
                                    onclick="ttraOpenModal(<?php echo $rid; ?>)"
                                    title="<?php echo esc_attr( $linea->codigo_reserva . ' — ' . trim($linea->nombre.' '.$linea->apellidos) ); ?>">
                                <span class="ttra-cal-admin__pill-code"><?php echo esc_html( $linea->codigo_reserva ); ?></span>
                                <span class="ttra-cal-admin__pill-name"><?php echo esc_html( trim($linea->nombre.' '.$linea->apellidos) ); ?></span>
                            </button>
                        <?php endforeach; ?>

                        <?php if ( $resto > 0 ) : ?>
                            <button class="ttra-cal-admin__pill ttra-cal-admin__pill--mas"
                                    onclick="ttraShowDayReservations('<?php echo $fecha_celda; ?>')">
                                +<?php echo $resto; ?> más
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
            <?php endfor; ?>

            <?php
            // Celdas vacías al final para completar la última fila
            $total_celdas = ($dia_semana_inicio - 1) + $dias_en_mes;
            $resto_celdas = $total_celdas % 7;
            if ( $resto_celdas > 0 ) {
                for ( $i = $resto_celdas; $i < 7; $i++ ) :
            ?>
                <div class="ttra-cal-admin__cell ttra-cal-admin__cell--empty"></div>
            <?php
                endfor;
            }
            ?>

        </div><!-- .ttra-cal-admin__grid -->

    </div><!-- .ttra-cal-admin -->

    <!-- ── LEYENDA ── -->
    <div style="display:flex;gap:20px;margin-top:12px;flex-wrap:wrap;align-items:center">
        <?php foreach ( $estados as $key => $est ) : ?>
            <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#646970">
                <span style="width:12px;height:12px;border-radius:3px;background:<?php echo esc_attr($est['color']); ?>;display:inline-block"></span>
                <?php echo esc_html( $est['label'] ); ?>
            </div>
        <?php endforeach; ?>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#646970;margin-left:auto">
            <span style="background:#003B6F;color:#fff;border-radius:50%;width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700">N</span>
            = número de reservas ese día
        </div>
    </div>

</div><!-- .wrap -->


<!-- ══════════════════════════════════════
     MODAL DE DETALLE DE RESERVA
     ══════════════════════════════════════ -->
<div id="ttra-modal-overlay" class="ttra-modal-overlay" onclick="ttraCloseModal(event)">
    <div class="ttra-modal" role="dialog" aria-modal="true" aria-labelledby="ttra-modal-title">

        <div class="ttra-modal__header">
            <div>
                <h2 id="ttra-modal-title" class="ttra-modal__title">Detalle de Reserva</h2>
                <span id="ttra-modal-codigo" class="ttra-modal__codigo"></span>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-shrink:0">
                <span id="ttra-modal-badge" class="ttra-badge"></span>
                <button class="ttra-modal__close" onclick="ttraCloseModal()" aria-label="Cerrar">✕</button>
            </div>
        </div>

        <div class="ttra-modal__body" id="ttra-modal-body">
            <div class="ttra-loader">Cargando…</div>
        </div>

        <div class="ttra-modal__footer">
            <a id="ttra-modal-ver-btn" href="#" class="button button-primary">
                👁️ Ver ficha completa
            </a>
            <div id="ttra-modal-estado-form">
                <form method="POST" action="" style="display:flex;gap:8px;align-items:center">
                    <input type="hidden" name="ttra_action" value="cambiar_estado_reserva">
                    <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
                    <input type="hidden" name="reserva_id" id="ttra-modal-reserva-id" value="">
                    <select name="nuevo_estado" class="ttra-estado-select" id="ttra-modal-estado-select">
                        <?php foreach ( $estados as $k => $v ) : ?>
                            <option value="<?php echo $k; ?>"><?php echo esc_html( $v['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button">Cambiar estado</button>
                </form>
            </div>
            <button class="button" onclick="ttraCloseModal()">Cerrar</button>
        </div>

    </div>
</div>


<!-- ── DATOS DE RESERVAS EN JSON PARA JS ── -->
<script>
const TTRA_RESERVAS = <?php echo wp_json_encode( $reservas_detalle ); ?>;
const TTRA_ESTADOS  = <?php echo wp_json_encode( $estados ); ?>;
const TTRA_ADMIN_URL = <?php echo wp_json_encode( admin_url( 'admin.php?page=ttra-reservas&reserva_id=' ) ); ?>;

/* ─── Abrir modal ─── */
function ttraOpenModal( reservaId ) {
    const data   = TTRA_RESERVAS[ reservaId ];
    if ( ! data ) return;

    const r      = data.info;
    const lineas = data.lineas;
    const estado = TTRA_ESTADOS[ r.estado ] || { label: r.estado, color: '#999' };

    // Cabecera
    document.getElementById('ttra-modal-title').textContent = 'Reserva ' + r.codigo_reserva;
    document.getElementById('ttra-modal-codigo').textContent = r.nombre + ' ' + r.apellidos;

    const badge = document.getElementById('ttra-modal-badge');
    badge.textContent = estado.label;
    badge.style.background = estado.color;

    // Botón ver ficha
    document.getElementById('ttra-modal-ver-btn').href = TTRA_ADMIN_URL + reservaId;

    // Select estado
    document.getElementById('ttra-modal-reserva-id').value = reservaId;
    document.getElementById('ttra-modal-estado-select').value = r.estado;

    // Cuerpo del modal
    document.getElementById('ttra-modal-body').innerHTML = ttraBuildModalBody( r, lineas );

    // Mostrar
    document.getElementById('ttra-modal-overlay').classList.add('ttra-modal-overlay--visible');
    document.body.style.overflow = 'hidden';
}

/* ─── Construir HTML del cuerpo ─── */
function ttraBuildModalBody( r, lineas ) {
    const fmt = v => v || '—';
    const fmtPrecio = v => parseFloat(v).toFixed(2).replace('.', ',') + ' €';
    const fmtFecha  = f => {
        if ( !f ) return '—';
        const [y,m,d] = f.split('-');
        return d + '/' + m + '/' + y;
    };

    // Actividades
    let lineasHTML = lineas.map( l => `
        <tr>
            <td><strong>${l.actividad_nombre}${l.subtipo ? ' <small style="color:#646970">— '+l.subtipo+'</small>' : ''}</strong></td>
            <td>${fmtFecha(l.fecha)}</td>
            <td>${l.hora ? l.hora.substring(0,5) : '—'}</td>
            <td>${l.personas}</td>
            <td>${l.sesiones}</td>
            <td><strong>${fmtPrecio(l.precio_total)}</strong></td>
        </tr>
    `).join('');

    return `
    <div class="ttra-modal__section">
        <h3 class="ttra-modal__section-title">🏄 Actividades</h3>
        <table class="ttra-table ttra-table--modal widefat">
            <thead>
                <tr>
                    <th>Actividad</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Pax</th>
                    <th>Ses.</th>
                    <th>Importe</th>
                </tr>
            </thead>
            <tbody>${lineasHTML}</tbody>
            <tfoot>
                ${parseFloat(r.descuento) > 0 ? `
                <tr style="background:#f9f9f9">
                    <td colspan="5" style="text-align:right;color:#646970">Subtotal</td>
                    <td>${fmtPrecio(r.subtotal)}</td>
                </tr>
                <tr style="background:#f9f9f9">
                    <td colspan="5" style="text-align:right;color:#22C55E">🎟️ Descuento</td>
                    <td style="color:#22C55E">-${fmtPrecio(r.descuento)}</td>
                </tr>` : ''}
                <tr style="background:#E8F4FD">
                    <td colspan="5" style="text-align:right;font-weight:700;font-size:14px">TOTAL</td>
                    <td style="font-weight:800;font-size:16px">${fmtPrecio(r.total)}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="ttra-modal__two-col">
        <div class="ttra-modal__section">
            <h3 class="ttra-modal__section-title">👤 Cliente</h3>
            <div class="ttra-modal__data-list">
                <div class="ttra-modal__data-row"><span>Nombre</span><strong>${fmt(r.nombre)} ${fmt(r.apellidos)}</strong></div>
                <div class="ttra-modal__data-row"><span>Email</span><a href="mailto:${r.email}">${fmt(r.email)}</a></div>
                <div class="ttra-modal__data-row"><span>Teléfono</span><a href="tel:${r.telefono}">${fmt(r.telefono)}</a></div>
                <div class="ttra-modal__data-row"><span>DNI/Pasaporte</span><strong>${fmt(r.dni_pasaporte)}</strong></div>
                ${r.notas ? `<div class="ttra-modal__data-row"><span>Notas</span><em>${r.notas}</em></div>` : ''}
            </div>
        </div>
        <div class="ttra-modal__section">
            <h3 class="ttra-modal__section-title">💳 Pago</h3>
            <div class="ttra-modal__data-list">
                <div class="ttra-modal__data-row">
                    <span>Estado</span>
                    <span class="ttra-badge ttra-badge--${r.pagado == 1 ? 'success' : 'danger'}">${r.pagado == 1 ? '✅ Pagado' : '⏳ Pendiente'}</span>
                </div>
                ${r.metodo_pago ? `<div class="ttra-modal__data-row"><span>Método</span><strong>${r.metodo_pago.toUpperCase()}</strong></div>` : ''}
                <div class="ttra-modal__data-row"><span>Reserva creada</span><small>${fmtFecha(r.created_at ? r.created_at.substring(0,10) : '')}</small></div>
            </div>
        </div>
    </div>`;
}

/* ─── Cerrar modal ─── */
function ttraCloseModal( e ) {
    if ( e && e.target !== document.getElementById('ttra-modal-overlay') ) return;
    document.getElementById('ttra-modal-overlay').classList.remove('ttra-modal-overlay--visible');
    document.body.style.overflow = '';
}

/* ─── Tecla ESC ─── */
document.addEventListener('keydown', function(e) {
    if ( e.key === 'Escape' ) ttraCloseModal();
});

/* ─── Ver todas las reservas de un día (abre filtro en listado) ─── */
function ttraShowDayReservations( fecha ) {
    window.location.href = <?php echo wp_json_encode( admin_url('admin.php?page=ttra-reservas') ); ?> + '&fecha_desde=' + fecha + '&fecha_hasta=' + fecha;
}
</script>