<?php
/**
 * Vista admin: Horarios por actividad.
 * Variables: $actividades, $actividad_id (int), $horarios (array)
 *
 * CHANGELOG v1.1.0
 *   + Select de actividad muestra nombre + duración formateada
 *   + Actividades ordenadas por categoría (orden BD) y duración ASC
 *   + Tabla resumen de horarios de todas las actividades al final
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$msg   = $_GET['msg'] ?? '';
$nonce = wp_create_nonce( 'ttra_admin_nonce' );
$dias  = TTRA_Helpers::dias_semana(); // [0=>'Lunes', ...]
$dias_abrev = [0=>'Lu',1=>'Ma',2=>'Mi',3=>'Ju',4=>'Vi',5=>'Sá',6=>'Do'];
$act_sel = $actividad_id > 0 ? TTRA_Actividad::get_by_id( $actividad_id ) : null;

// ── Ordenar actividades: por categoría (orden BD) y duración ASC ──────────
// Agrupamos por categoría para el select con <optgroup>
$actividades_ordenadas = $actividades; // ya viene ordenado del admin (por orden ASC)
usort( $actividades_ordenadas, function( $a, $b ) {
    // 1. Por categoria_id (usamos orden de categoria si está disponible, si no categoria_id)
    $co_a = isset($a->categoria_orden) ? intval($a->categoria_orden) : intval($a->categoria_id);
    $co_b = isset($b->categoria_orden) ? intval($b->categoria_orden) : intval($b->categoria_id);
    if ( $co_a !== $co_b ) return $co_a - $co_b;
    // 2. Duración ASC dentro de la categoría
    return intval($a->duracion_minutos) - intval($b->duracion_minutos);
} );

// Agrupar por categoría para los <optgroup>
$por_categoria = [];
foreach ( $actividades_ordenadas as $act ) {
    $cat_key = $act->categoria_id;
    if ( ! isset( $por_categoria[ $cat_key ] ) ) {
        $por_categoria[ $cat_key ] = [
            'nombre' => $act->categoria_nombre ?? ( 'Categoría ' . $cat_key ),
            'items'  => [],
        ];
    }
    $por_categoria[ $cat_key ]['items'][] = $act;
}

// Helper: formatea duración en texto legible
function ttra_format_duracion( $minutos ) {
    $m = intval( $minutos );
    if ( $m <= 0 ) return '—';
    if ( $m < 60 ) return $m . ' min';
    $h = floor( $m / 60 );
    $r = $m % 60;
    return $r === 0 ? $h . ' h' : $h . ' h ' . $r . ' min';
}

// Agrupar horarios existentes en franjas (igual que antes)
$franjas = [];
foreach ( (array) $horarios as $h ) {
    $key = $h->hora_inicio . '|' . $h->hora_fin . '|' . $h->intervalo_minutos . '|' . $h->plazas_por_slot . '|' . $h->activo;
    if ( ! isset( $franjas[ $key ] ) ) {
        $franjas[ $key ] = [
            'hora_inicio'       => $h->hora_inicio,
            'hora_fin'          => $h->hora_fin,
            'intervalo_minutos' => $h->intervalo_minutos,
            'plazas_por_slot'   => $h->plazas_por_slot,
            'activo'            => $h->activo,
            'dias'              => [],
            'ids'               => [],
        ];
    }
    $franjas[ $key ]['dias'][] = (int) $h->dia_semana;
    $franjas[ $key ]['ids'][]  = (int) $h->id;
}
$franjas = array_values( $franjas );

// ── Resumen global: todos los horarios de todas las actividades ───────────
global $wpdb;
$t_hor = TTRA_DB::table( 'horarios' );
$t_act = TTRA_DB::table( 'actividades' );
$t_cat = TTRA_DB::table( 'categorias' );

$todos_horarios = $wpdb->get_results(
    "SELECT h.*, a.nombre as act_nombre, a.duracion_minutos,
            c.nombre as cat_nombre, c.orden as cat_orden
     FROM $t_hor h
     INNER JOIN $t_act a ON a.id = h.actividad_id
     INNER JOIN $t_cat c ON c.id = a.categoria_id
     WHERE h.activo = 1
     ORDER BY c.orden ASC, a.duracion_minutos ASC, h.dia_semana ASC, h.hora_inicio ASC"
);

// Consolidar: actividad → dias y horas
$resumen = [];
foreach ( $todos_horarios as $h ) {
    $key = $h->actividad_id;
    if ( ! isset( $resumen[ $key ] ) ) {
        $resumen[ $key ] = [
            'nombre'    => $h->act_nombre,
            'duracion'  => $h->duracion_minutos,
            'categoria' => $h->cat_nombre,
            'franjas'   => [],
        ];
    }
    $fk = $h->hora_inicio . '-' . $h->hora_fin;
    if ( ! isset( $resumen[ $key ]['franjas'][ $fk ] ) ) {
        $resumen[ $key ]['franjas'][ $fk ] = [
            'inicio'  => $h->hora_inicio,
            'fin'     => $h->hora_fin,
            'inter'   => $h->intervalo_minutos,
            'plazas'  => $h->plazas_por_slot,
            'dias'    => [],
        ];
    }
    $resumen[ $key ]['franjas'][ $fk ]['dias'][] = intval( $h->dia_semana );
}
?>

<style>
/* ── Franja card ── */
.ttra-franja {
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 10px;
    padding: 16px 16px 16px 12px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
    transition: border-color .2s, box-shadow .2s;
}
.ttra-franja:hover { border-color: #00A0E3; box-shadow: 0 2px 8px rgba(0,160,227,.1); }
.ttra-franja__grip { color: #ccc; font-size: 18px; cursor: grab; padding-top: 6px; user-select: none; flex-shrink: 0; }
.ttra-franja__body { flex: 1; display: flex; flex-direction: column; gap: 12px; }

.ttra-dias-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.ttra-dias-label { font-size: 11px; font-weight: 700; color: #646970; text-transform: uppercase; letter-spacing: .04em; flex-shrink: 0; min-width: 34px; }

.ttra-dia-chip {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 50%;
    border: 2px solid #dcdcde; background: #f6f7f7;
    font-size: 11px; font-weight: 700; color: #646970;
    cursor: pointer; user-select: none; transition: all .15s; flex-shrink: 0;
}
.ttra-dia-chip:hover { border-color: #00A0E3; color: #00A0E3; background: #e8f4fd; }
.ttra-dia-chip--active { background: #00A0E3; border-color: #00A0E3; color: #fff; }

.ttra-campos-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
.ttra-campo { display: flex; flex-direction: column; gap: 4px; }
.ttra-campo label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #888; }
.ttra-campo input[type="time"], .ttra-campo select, .ttra-campo input[type="number"] {
    border: 1px solid #c3c4c7; border-radius: 5px; padding: 6px 8px; font-size: 13px; background: #fafafa; transition: border-color .2s;
}
.ttra-campo input:focus, .ttra-campo select:focus { border-color: #00A0E3; outline: none; background: #fff; }
.ttra-campo--time input  { width: 100px; }
.ttra-campo--inter select { width: 100px; }
.ttra-campo--plazas input { width: 70px; }
.ttra-campo--toggle { flex-direction: row; align-items: center; gap: 8px; padding-bottom: 4px; }
.ttra-campo--toggle label { margin: 0; font-size: 10px; }

.ttra-preview { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; min-height: 22px; }
.ttra-slots-count { font-size: 12px; font-weight: 700; color: #00A0E3; flex-shrink: 0; }
.ttra-slot-tag { background: #e8f4fd; color: #003B6F; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-family: monospace; font-weight: 600; }
.ttra-preview--empty { color: #dc3545; font-size: 12px; }

.ttra-franja__del {
    background: none; border: 1px solid #e2e4e7; border-radius: 50%;
    width: 28px; height: 28px; cursor: pointer; color: #dc3545; font-size: 14px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    transition: all .2s; margin-top: 2px;
}
.ttra-franja__del:hover { background: #dc3545; color: #fff; border-color: #dc3545; }
.ttra-dias-warning { color: #dc3545; font-size: 11px; margin-top: 2px; display: none; }

/* ── Tabla resumen ── */
.ttra-resumen-table { border-collapse: collapse; width: 100%; font-size: 13px; }
.ttra-resumen-table th {
    background: #f6f7f7; font-size: 11px; text-transform: uppercase;
    letter-spacing: .04em; color: #646970; padding: 10px 12px;
    font-weight: 700; border-bottom: 2px solid #e2e4e7; text-align: left;
}
.ttra-resumen-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }
.ttra-resumen-table tbody tr:hover { background: #f9f9f9; }
.ttra-resumen-table .ttra-cat-row td {
    background: #003B6F; color: #fff; font-weight: 700;
    font-size: 12px; letter-spacing: .05em; text-transform: uppercase; padding: 6px 12px;
}
.ttra-dia-chip-mini {
    display: inline-flex; align-items: center; justify-content: center;
    width: 24px; height: 24px; border-radius: 50%; font-size: 10px; font-weight: 700;
    background: #e8f4fd; color: #003B6F; border: 1px solid #00A0E3;
}
.ttra-dia-chip-mini--off { background: #f0f0f1; color: #aaa; border-color: #dcdcde; }
.ttra-franja-pill {
    display: inline-block; background: #e8f4fd; color: #003B6F;
    border: 1px solid #00A0E3; border-radius: 4px; padding: 2px 8px;
    font-size: 11px; font-family: monospace; font-weight: 600; margin: 1px;
}
.ttra-no-horario { color: #aaa; font-style: italic; }
</style>

<div class="wrap ttra-admin-wrap">

    <h1 class="ttra-admin-title">
        <span class="dashicons dashicons-clock"></span>
        <?php esc_html_e( 'Horarios de Actividades', 'tictac-reservas-agua' ); ?>
    </h1>

    <?php if ( $msg === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible"><p>✅ <?php esc_html_e( 'Horarios guardados correctamente.', 'tictac-reservas-agua' ); ?></p></div>
    <?php endif; ?>

    <!-- ══ SELECTOR ACTIVIDAD ══ -->
    <div class="ttra-admin-card">
        <form method="GET" action="">
            <input type="hidden" name="page" value="ttra-horarios">
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <label style="font-weight:600; font-size:13px;"><?php esc_html_e( 'Actividad:', 'tictac-reservas-agua' ); ?></label>
                <select name="actividad_id" style="min-width:280px;">
                    <option value=""><?php esc_html_e( '-- Selecciona --', 'tictac-reservas-agua' ); ?></option>
                    <?php foreach ( $por_categoria as $cat_id => $cat_data ) : ?>
                        <optgroup label="── <?php echo esc_attr( $cat_data['nombre'] ); ?>">
                            <?php foreach ( $cat_data['items'] as $act ) :
                                $dur_label = ttra_format_duracion( $act->duracion_minutos );
                                $label = $act->nombre;
                                if ( ! empty( $act->subtipo ) ) $label .= ' ' . $act->subtipo;
                                $label .= ' — ' . $dur_label;
                            ?>
                                <option value="<?php echo $act->id; ?>" <?php selected( $actividad_id, $act->id ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Cargar', 'tictac-reservas-agua' ); ?></button>
            </div>
        </form>

        <?php if ( $act_sel ) : ?>
            <div class="ttra-info-banner" style="margin-top:14px;">
                <strong><?php echo esc_html( $act_sel->nombre ); ?></strong>
                <?php if ( $act_sel->subtipo ) echo ' <em>' . esc_html( $act_sel->subtipo ) . '</em>'; ?>
                &nbsp;—&nbsp;<?php echo ttra_format_duracion( $act_sel->duracion_minutos ); ?>
                &nbsp;—&nbsp;<?php echo TTRA_Helpers::formato_precio( $act_sel->precio_base ); ?>
                <?php echo $act_sel->precio_tipo === 'por_persona' ? '/persona' : ''; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ( $act_sel ) : ?>
    <!-- ══ EDITOR DE FRANJAS ══ -->
    <div class="ttra-admin-card ttra-card--full">

        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px; flex-wrap:wrap; gap:10px;">
            <div>
                <h2 style="margin:0 0 4px;"><?php esc_html_e( 'Configurar franjas horarias', 'tictac-reservas-agua' ); ?></h2>
                <p class="description" style="margin:0;">
                    <?php esc_html_e( 'Selecciona los días que aplican a cada franja. Para un día con horario distinto, crea una franja aparte con solo ese día marcado.', 'tictac-reservas-agua' ); ?>
                </p>
            </div>
            <button type="button" id="btn-add-franja" class="button button-primary" style="flex-shrink:0;">
                + <?php esc_html_e( 'Añadir franja', 'tictac-reservas-agua' ); ?>
            </button>
        </div>

        <form method="POST" action="" id="form-horarios">
            <input type="hidden" name="ttra_action" value="save_horario">
            <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
            <input type="hidden" name="actividad_id" value="<?php echo intval( $actividad_id ); ?>">

            <div id="horarios-container" style="display:flex; flex-direction:column; gap:10px; margin-top:16px;">

            <?php if ( empty( $franjas ) ) : ?>
                <p class="ttra-empty-msg" id="msg-sin-franjas">
                    🕐 <?php esc_html_e( 'Sin franjas configuradas. Añade la primera.', 'tictac-reservas-agua' ); ?>
                </p>
            <?php else : ?>
                <?php foreach ( $franjas as $fi => $f ) :
                    $dias_sel = $f['dias'];
                    $inter    = intval( $f['intervalo_minutos'] );
                    $plazas   = intval( $f['plazas_por_slot'] );
                ?>
                <div class="ttra-franja" data-fi="<?php echo $fi; ?>">
                    <span class="ttra-franja__grip">☰</span>
                    <div class="ttra-franja__body">
                        <div class="ttra-dias-row">
                            <span class="ttra-dias-label">Días</span>
                            <?php foreach ( $dias_abrev as $num => $abrev ) :
                                $activo = in_array( $num, $dias_sel );
                            ?>
                            <label class="ttra-dia-chip <?php echo $activo ? 'ttra-dia-chip--active' : ''; ?>">
                                <input type="checkbox"
                                       name="horarios[<?php echo $fi; ?>][dias][]"
                                       value="<?php echo $num; ?>"
                                       <?php checked( $activo ); ?>
                                       style="display:none;">
                                <?php echo $abrev; ?>
                            </label>
                            <?php endforeach; ?>
                            <span class="ttra-dias-warning">⚠️ Selecciona al menos un día</span>
                        </div>

                        <div class="ttra-campos-row">
                            <div class="ttra-campo ttra-campo--time">
                                <label>Desde</label>
                                <input type="time" name="horarios[<?php echo $fi; ?>][hora_inicio]"
                                       value="<?php echo esc_attr( $f['hora_inicio'] ); ?>" required>
                            </div>
                            <div class="ttra-campo ttra-campo--time">
                                <label>Hasta</label>
                                <input type="time" name="horarios[<?php echo $fi; ?>][hora_fin]"
                                       value="<?php echo esc_attr( $f['hora_fin'] ); ?>" required>
                            </div>
                            <div class="ttra-campo ttra-campo--inter">
                                <label>Intervalo</label>
                                <select name="horarios[<?php echo $fi; ?>][intervalo_minutos]">
                                    <?php foreach ( [15,20,30,45,60,90,120] as $min ) : ?>
                                        <option value="<?php echo $min; ?>" <?php selected( $inter, $min ); ?>><?php echo $min; ?> min</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="ttra-campo ttra-campo--plazas">
                                <label>Plazas/slot</label>
                                <input type="number" name="horarios[<?php echo $fi; ?>][plazas_por_slot]"
                                       value="<?php echo $plazas; ?>" min="1" max="999">
                            </div>
                            <div class="ttra-campo ttra-campo--toggle">
                                <label>Activo</label>
                                <label class="ttra-toggle ttra-toggle--sm">
                                    <input type="checkbox" name="horarios[<?php echo $fi; ?>][activo]" value="1"
                                        <?php checked( $f['activo'], 1 ); ?>>
                                    <span class="ttra-toggle__slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="ttra-preview"></div>
                    </div>
                    <button type="button" class="ttra-franja__del" title="Eliminar franja">✕</button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            </div>

            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #f0f0f1; display:flex; gap:10px;">
                <?php submit_button( __( 'Guardar horarios', 'tictac-reservas-agua' ), 'primary', 'submit', false ); ?>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- ══ TABLA RESUMEN DE TODOS LOS HORARIOS ══ -->
    <div class="ttra-admin-card ttra-card--full" style="margin-top:20px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0">📋 <?php esc_html_e( 'Resumen de horarios — todas las actividades', 'tictac-reservas-agua' ); ?></h2>
            <span class="description"><?php esc_html_e( 'Solo se muestran actividades con horarios activos.', 'tictac-reservas-agua' ); ?></span>
        </div>

        <?php if ( empty( $resumen ) ) : ?>
            <p class="ttra-empty-msg">🕐 <?php esc_html_e( 'Ninguna actividad tiene horarios configurados todavía.', 'tictac-reservas-agua' ); ?></p>
        <?php else : ?>

        <?php
        // Agrupar resumen por categoría
        $resumen_por_cat = [];
        foreach ( $resumen as $act_id => $data ) {
            $cat = $data['categoria'];
            if ( ! isset( $resumen_por_cat[ $cat ] ) ) $resumen_por_cat[ $cat ] = [];
            $resumen_por_cat[ $cat ][ $act_id ] = $data;
        }
        ?>

        <table class="widefat ttra-resumen-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Actividad', 'tictac-reservas-agua' ); ?></th>
                    <th><?php esc_html_e( 'Duración', 'tictac-reservas-agua' ); ?></th>
                    <th><?php esc_html_e( 'Días', 'tictac-reservas-agua' ); ?></th>
                    <th><?php esc_html_e( 'Franjas horarias', 'tictac-reservas-agua' ); ?></th>
                    <th><?php esc_html_e( 'Intervalo', 'tictac-reservas-agua' ); ?></th>
                    <th><?php esc_html_e( 'Plazas/slot', 'tictac-reservas-agua' ); ?></th>
                    <th style="width:80px"><?php esc_html_e( 'Editar', 'tictac-reservas-agua' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $resumen_por_cat as $cat_nombre => $actividades_cat ) : ?>
                <!-- Fila separadora de categoría -->
                <tr class="ttra-cat-row">
                    <td colspan="7">📂 <?php echo esc_html( $cat_nombre ); ?></td>
                </tr>
                <?php foreach ( $actividades_cat as $act_id => $data ) :
                    // Consolidar días de todas las franjas
                    $dias_usados = [];
                    foreach ( $data['franjas'] as $fj ) {
                        foreach ( $fj['dias'] as $d ) $dias_usados[] = $d;
                    }
                    $dias_usados = array_unique( $dias_usados );
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html( $data['nombre'] ); ?></strong>
                    </td>
                    <td>
                        <span style="font-family:monospace;font-weight:600">
                            <?php echo esc_html( ttra_format_duracion( $data['duracion'] ) ); ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:3px;flex-wrap:wrap">
                            <?php foreach ( $dias_abrev as $num => $abrev ) : ?>
                                <span class="ttra-dia-chip-mini <?php echo in_array( $num, $dias_usados ) ? '' : 'ttra-dia-chip-mini--off'; ?>">
                                    <?php echo $abrev; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td>
                        <?php foreach ( $data['franjas'] as $fk => $fj ) : ?>
                            <span class="ttra-franja-pill">
                                <?php echo esc_html( substr($fj['inicio'],0,5) . ' – ' . substr($fj['fin'],0,5) ); ?>
                            </span>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php
                        $intervalos = array_unique( array_column( $data['franjas'], 'inter' ) );
                        echo esc_html( implode( ', ', array_map( fn($i) => $i . ' min', $intervalos ) ) );
                        ?>
                    </td>
                    <td>
                        <?php
                        $plazas_arr = array_unique( array_column( $data['franjas'], 'plazas' ) );
                        echo esc_html( implode( ', ', $plazas_arr ) );
                        ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url( 'admin.php?page=ttra-horarios&actividad_id=' . $act_id ); ?>"
                           class="button button-small">✏️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php endif; ?>
    </div>

</div>

<!-- Template nueva franja -->
<script type="text/html" id="tpl-franja">
<div class="ttra-franja" data-fi="__FI__">
    <span class="ttra-franja__grip">☰</span>
    <div class="ttra-franja__body">
        <div class="ttra-dias-row">
            <span class="ttra-dias-label">Días</span>
            <?php foreach ( $dias_abrev as $num => $abrev ) : ?>
            <label class="ttra-dia-chip">
                <input type="checkbox" name="horarios[__FI__][dias][]" value="<?php echo $num; ?>" style="display:none;">
                <?php echo $abrev; ?>
            </label>
            <?php endforeach; ?>
            <span class="ttra-dias-warning">⚠️ Selecciona al menos un día</span>
        </div>
        <div class="ttra-campos-row">
            <div class="ttra-campo ttra-campo--time">
                <label>Desde</label>
                <input type="time" name="horarios[__FI__][hora_inicio]" value="09:00" required>
            </div>
            <div class="ttra-campo ttra-campo--time">
                <label>Hasta</label>
                <input type="time" name="horarios[__FI__][hora_fin]" value="18:00" required>
            </div>
            <div class="ttra-campo ttra-campo--inter">
                <label>Intervalo</label>
                <select name="horarios[__FI__][intervalo_minutos]">
                    <option value="15">15 min</option>
                    <option value="20">20 min</option>
                    <option value="30" selected>30 min</option>
                    <option value="45">45 min</option>
                    <option value="60">60 min</option>
                    <option value="90">90 min</option>
                    <option value="120">120 min</option>
                </select>
            </div>
            <div class="ttra-campo ttra-campo--plazas">
                <label>Plazas/slot</label>
                <input type="number" name="horarios[__FI__][plazas_por_slot]" value="10" min="1" max="999">
            </div>
            <div class="ttra-campo ttra-campo--toggle">
                <label>Activo</label>
                <label class="ttra-toggle ttra-toggle--sm">
                    <input type="checkbox" name="horarios[__FI__][activo]" value="1" checked>
                    <span class="ttra-toggle__slider"></span>
                </label>
            </div>
        </div>
        <div class="ttra-preview"></div>
    </div>
    <button type="button" class="ttra-franja__del" title="Eliminar">✕</button>
</div>
</script>

<script>
(function () {
    var fi        = <?php echo count($franjas); ?>;
    var container = document.getElementById('horarios-container');

    function toMin(t) { var p = t.split(':').map(Number); return p[0]*60+(p[1]||0); }
    function fromMin(m) { return String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0'); }

    function updatePreview(franja) {
        var tIni = franja.querySelector('[name*="hora_inicio"]');
        var tFin = franja.querySelector('[name*="hora_fin"]');
        var tInt = franja.querySelector('[name*="intervalo_minutos"]');
        var prev = franja.querySelector('.ttra-preview');
        if (!tIni || !tFin || !prev) return;
        var ini   = toMin(tIni.value || '09:00');
        var fin   = toMin(tFin.value || '18:00');
        var inter = parseInt(tInt ? tInt.value : 30);
        var slots = [];
        for (var cur = ini; cur < fin; cur += inter) slots.push(fromMin(cur));
        if (slots.length === 0) {
            prev.innerHTML = '<span class="ttra-preview--empty">⚠️ Sin slots — revisa las horas</span>';
        } else {
            prev.innerHTML = '<span class="ttra-slots-count">'+slots.length+' slots:</span> '+
                slots.map(function(s){ return '<span class="ttra-slot-tag">'+s+'</span>'; }).join('');
        }
    }

    function bindChips(franja) {
        franja.querySelectorAll('.ttra-dia-chip').forEach(function(chip) {
            chip.addEventListener('click', function() {
                var cb = chip.querySelector('input[type="checkbox"]');
                cb.checked = !cb.checked;
                chip.classList.toggle('ttra-dia-chip--active', cb.checked);
                var warn   = franja.querySelector('.ttra-dias-warning');
                var alguno = franja.querySelectorAll('.ttra-dia-chip--active').length > 0;
                if (warn) warn.style.display = alguno ? 'none' : '';
            });
        });
    }

    function bindFranja(franja) {
        bindChips(franja);
        franja.querySelectorAll('input[type="time"], select').forEach(function(el) {
            el.addEventListener('change', function() { updatePreview(franja); });
            el.addEventListener('input',  function() { updatePreview(franja); });
        });
        franja.querySelector('.ttra-franja__del')?.addEventListener('click', function() {
            if (confirm('¿Eliminar esta franja horaria?')) {
                franja.style.transition = 'opacity .2s';
                franja.style.opacity = '0';
                setTimeout(function() { franja.remove(); }, 200);
            }
        });
        updatePreview(franja);
    }

    if (container) {
        container.querySelectorAll('.ttra-franja').forEach(bindFranja);
    }

    document.getElementById('btn-add-franja')?.addEventListener('click', function() {
        var sinMsg = document.getElementById('msg-sin-franjas');
        if (sinMsg) sinMsg.remove();
        var tpl  = document.getElementById('tpl-franja').innerHTML.replace(/__FI__/g, fi++);
        var tmp  = document.createElement('div');
        tmp.innerHTML = tpl;
        var nueva = tmp.firstElementChild;
        container.appendChild(nueva);
        bindFranja(nueva);
        nueva.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    document.getElementById('form-horarios')?.addEventListener('submit', function(e) {
        var ok = true;
        container.querySelectorAll('.ttra-franja').forEach(function(franja) {
            var dias = franja.querySelectorAll('.ttra-dia-chip--active').length;
            var warn = franja.querySelector('.ttra-dias-warning');
            if (dias === 0) { ok = false; if (warn) warn.style.display = ''; }
        });
        if (!ok) { e.preventDefault(); alert('Hay franjas sin ningún día seleccionado.'); }
    });
})();
</script>