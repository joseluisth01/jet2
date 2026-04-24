<?php
/**
 * Vista admin: Cupones de descuento.
 * Variables: $cupones (array), $editando (object|null)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$msg   = $_GET['msg'] ?? '';
$nonce = wp_create_nonce( 'ttra_admin_nonce' );
$hoy   = current_time( 'Y-m-d' );

function ttra_cupon_estado( $c, $hoy ) {
    if ( ! $c->activo ) return ['muted', 'Inactivo'];
    if ( $c->fecha_fin && $c->fecha_fin < $hoy ) return ['danger', 'Expirado'];
    if ( $c->fecha_inicio && $c->fecha_inicio > $hoy ) return ['warning', 'Pendiente'];
    if ( $c->uso_maximo > 0 && $c->uso_actual >= $c->uso_maximo ) return ['danger', 'Agotado'];
    return ['success', 'Válido'];
}
?>

<div class="wrap ttra-admin-wrap">

    <h1 class="ttra-admin-title">
        <span class="dashicons dashicons-tickets-alt"></span>
        <?php esc_html_e( 'Cupones de Descuento', 'tictac-reservas-agua' ); ?>
    </h1>

    <?php if ( $msg === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible"><p>✅ <?php esc_html_e( 'Cupón guardado.', 'tictac-reservas-agua' ); ?></p></div>
    <?php elseif ( $msg === 'deleted' ) : ?>
        <div class="notice notice-warning is-dismissible"><p>🗑️ <?php esc_html_e( 'Cupón eliminado.', 'tictac-reservas-agua' ); ?></p></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: minmax(360px,420px) 1fr; gap:20px; align-items:start;">

        <!-- ══ FORMULARIO ══ -->
        <div class="ttra-admin-card">
            <h2><?php echo $editando ? esc_html__( 'Editar Cupón', 'tictac-reservas-agua' ) : esc_html__( 'Nuevo Cupón', 'tictac-reservas-agua' ); ?></h2>

            <form method="POST" action="">
                <input type="hidden" name="ttra_action" value="save_cupon">
                <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
                <?php if ( $editando ) : ?>
                    <input type="hidden" name="cupon_id" value="<?php echo intval( $editando->id ); ?>">
                <?php endif; ?>

                <table class="form-table ttra-form-table" style="table-layout:fixed; width:100%;">
                    <tr>
                        <th style="width:110px; padding:10px 10px 10px 0;"><label for="cup-codigo"><?php esc_html_e( 'Código *', 'tictac-reservas-agua' ); ?></label></th>
                        <td style="padding:10px 0;">
                            <div style="display:flex; gap:6px;">
                                <input type="text" id="cup-codigo" name="codigo" style="flex:1; font-family:monospace; font-weight:700; text-transform:uppercase;"
                                       value="<?php echo esc_attr( $editando->codigo ?? '' ); ?>"
                                       required placeholder="VERANO25">
                                <button type="button" id="btn-gen-codigo" class="button" title="<?php esc_attr_e( 'Generar aleatorio', 'tictac-reservas-agua' ); ?>">
                                    🎲
                                </button>
                            </div>
                            <p class="description"><?php esc_html_e( 'Solo mayúsculas, números y guiones. El cliente lo introduce en el paso de pago.', 'tictac-reservas-agua' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 10px 10px 0;"><?php esc_html_e( 'Tipo de descuento *', 'tictac-reservas-agua' ); ?></th>
                        <td style="padding:10px 0;">
                            <div style="display:flex; gap:10px;">
                                <label style="cursor:pointer; flex:1;">
                                    <input type="radio" name="tipo" value="porcentaje" id="tipo-pct"
                                        <?php checked( $editando->tipo ?? 'porcentaje', 'porcentaje' ); ?> style="display:none;">
                                    <div id="box-pct" style="text-align:center; padding:14px 10px; border:2px solid #dcdcde; border-radius:8px; transition:all .2s;">
                                        <div style="font-size:22px; font-weight:700;">%</div>
                                        <div style="font-size:11px; color:#646970;">Porcentaje</div>
                                    </div>
                                </label>
                                <label style="cursor:pointer; flex:1;">
                                    <input type="radio" name="tipo" value="fijo" id="tipo-fijo"
                                        <?php checked( $editando->tipo ?? '', 'fijo' ); ?> style="display:none;">
                                    <div id="box-fijo" style="text-align:center; padding:14px 10px; border:2px solid #dcdcde; border-radius:8px; transition:all .2s;">
                                        <div style="font-size:22px; font-weight:700;">€</div>
                                        <div style="font-size:11px; color:#646970;">Importe fijo</div>
                                    </div>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 10px 10px 0;"><label for="cup-valor"><?php esc_html_e( 'Valor *', 'tictac-reservas-agua' ); ?></label></th>
                        <td style="padding:10px 0;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <input type="number" id="cup-valor" name="valor" class="small-text"
                                       value="<?php echo floatval( $editando->valor ?? 0 ); ?>"
                                       min="0" step="0.01" required>
                                <span id="cup-valor-suffix" class="description">
                                    <?php echo ( $editando->tipo ?? 'porcentaje' ) === 'porcentaje' ? '%' : '€'; ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 10px 10px 0;"><label for="cup-uso-max"><?php esc_html_e( 'Usos máximos', 'tictac-reservas-agua' ); ?></label></th>
                        <td style="padding:10px 0;">
                            <input type="number" id="cup-uso-max" name="uso_maximo" class="small-text"
                                   value="<?php echo intval( $editando->uso_maximo ?? 0 ); ?>" min="0">
                            <span class="description"><?php esc_html_e( '0 = ilimitado', 'tictac-reservas-agua' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 10px 10px 0;"><?php esc_html_e( 'Validez', 'tictac-reservas-agua' ); ?></th>
                        <td style="padding:10px 0;">
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <label style="font-size:12px; color:#646970;"><?php esc_html_e( 'Desde', 'tictac-reservas-agua' ); ?></label>
                                <input type="date" name="fecha_inicio"
                                       value="<?php echo esc_attr( $editando->fecha_inicio ?? '' ); ?>">
                                <label style="font-size:12px; color:#646970;"><?php esc_html_e( 'Hasta', 'tictac-reservas-agua' ); ?></label>
                                <input type="date" name="fecha_fin"
                                       value="<?php echo esc_attr( $editando->fecha_fin ?? '' ); ?>">
                            </div>
                            <p class="description"><?php esc_html_e( 'Deja vacío para que no haya límite de fechas.', 'tictac-reservas-agua' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 10px 10px 0;"><?php esc_html_e( 'Activo', 'tictac-reservas-agua' ); ?></th>
                        <td style="padding:10px 0;">
                            <label class="ttra-toggle">
                                <input type="checkbox" name="activo" value="1"
                                    <?php checked( $editando->activo ?? 1, 1 ); ?>>
                                <span class="ttra-toggle__slider"></span>
                                <span class="ttra-toggle__label"><?php esc_html_e( 'Cupón activo', 'tictac-reservas-agua' ); ?></span>
                            </label>
                        </td>
                    </tr>
                </table>

                <div class="ttra-form-actions">
                    <?php submit_button( $editando ? __( 'Actualizar Cupón', 'tictac-reservas-agua' ) : __( 'Crear Cupón', 'tictac-reservas-agua' ), 'primary', 'submit', false ); ?>
                    <?php if ( $editando ) : ?>
                        <a href="<?php echo admin_url( 'admin.php?page=ttra-cupones' ); ?>" class="button"><?php esc_html_e( 'Cancelar', 'tictac-reservas-agua' ); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- ══ LISTADO ══ -->
        <div class="ttra-admin-card">
            <div class="ttra-admin-card__header">
                <h2><?php esc_html_e( 'Cupones existentes', 'tictac-reservas-agua' ); ?></h2>
                <span class="ttra-count-badge"><?php echo count( $cupones ); ?></span>
            </div>

            <?php if ( empty( $cupones ) ) : ?>
                <p class="ttra-empty-msg">🎟️ <?php esc_html_e( 'No hay cupones. Crea el primero.', 'tictac-reservas-agua' ); ?></p>
            <?php else : ?>
                <table class="widefat ttra-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Código', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Descuento', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Usos', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Validez', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Estado', 'tictac-reservas-agua' ); ?></th>
                            <th style="width:120px"><?php esc_html_e( 'Acciones', 'tictac-reservas-agua' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $cupones as $cup ) :
                            [$color, $estado_lbl] = ttra_cupon_estado( $cup, $hoy );
                            $uso_pct = $cup->uso_maximo > 0 ? min( 100, round( $cup->uso_actual / $cup->uso_maximo * 100 ) ) : 0;
                        ?>
                        <tr>
                            <td>
                                <code class="ttra-codigo-badge"><?php echo esc_html( $cup->codigo ); ?></code>
                                <button type="button" class="ttra-copy-btn" data-code="<?php echo esc_attr( $cup->codigo ); ?>" title="Copiar">📋</button>
                            </td>
                            <td>
                                <strong style="font-size:16px">
                                    <?php echo $cup->tipo === 'porcentaje'
                                        ? floatval( $cup->valor ) . '%'
                                        : TTRA_Helpers::formato_precio( $cup->valor ); ?>
                                </strong>
                            </td>
                            <td>
                                <?php if ( $cup->uso_maximo > 0 ) : ?>
                                    <div class="ttra-uso-bar">
                                        <div class="ttra-uso-bar__fill" style="width:<?php echo $uso_pct; ?>%"></div>
                                    </div>
                                    <small><?php echo intval( $cup->uso_actual ); ?> / <?php echo intval( $cup->uso_maximo ); ?></small>
                                <?php else : ?>
                                    <span><?php echo intval( $cup->uso_actual ); ?> / ∞</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $cup->fecha_inicio || $cup->fecha_fin ) : ?>
                                    <small>
                                        <?php echo $cup->fecha_inicio ? date_i18n( 'd/m/Y', strtotime( $cup->fecha_inicio ) ) : '∞'; ?>
                                        →
                                        <?php echo $cup->fecha_fin ? date_i18n( 'd/m/Y', strtotime( $cup->fecha_fin ) ) : '∞'; ?>
                                    </small>
                                <?php else : ?>
                                    <span class="ttra-text-muted">Sin límite</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="ttra-badge ttra-badge--<?php echo $color; ?>">
                                    <?php echo esc_html( $estado_lbl ); ?>
                                </span>
                            </td>
                            <td>
                                <div class="ttra-row-actions">
                                    <a href="<?php echo admin_url( 'admin.php?page=ttra-cupones&editar=' . $cup->id ); ?>"
                                       class="button button-small">✏️</a>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="ttra_action" value="delete_cupon">
                                        <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
                                        <input type="hidden" name="cupon_id" value="<?php echo intval( $cup->id ); ?>">
                                        <button type="submit" class="button button-small ttra-btn-delete"
                                                data-confirm="<?php esc_attr_e( '¿Eliminar este cupón?', 'tictac-reservas-agua' ); ?>">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
(function() {
    // Resaltar box activo al cargar
    function updateBoxes() {
        var pct  = document.getElementById('tipo-pct');
        var fijo = document.getElementById('tipo-fijo');
        var boxPct  = document.getElementById('box-pct');
        var boxFijo = document.getElementById('box-fijo');
        if (!pct || !boxPct) return;

        if (pct.checked) {
            boxPct.style.borderColor  = '#00A0E3';
            boxPct.style.background   = '#e8f4fd';
            boxFijo.style.borderColor = '#dcdcde';
            boxFijo.style.background  = '';
        } else {
            boxFijo.style.borderColor = '#00A0E3';
            boxFijo.style.background  = '#e8f4fd';
            boxPct.style.borderColor  = '#dcdcde';
            boxPct.style.background   = '';
        }
        document.getElementById('cup-valor-suffix').textContent = pct.checked ? '%' : '€';
    }
    updateBoxes();

    // Clicks en los boxes visuales
    document.getElementById('box-pct')?.closest('label').addEventListener('click', function() {
        document.getElementById('tipo-pct').checked = true;
        updateBoxes();
    });
    document.getElementById('box-fijo')?.closest('label').addEventListener('click', function() {
        document.getElementById('tipo-fijo').checked = true;
        updateBoxes();
    });

    // Generador de código aleatorio
    document.getElementById('btn-gen-codigo')?.addEventListener('click', function() {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        var code  = '';
        for (var i = 0; i < 8; i++) code += chars[Math.floor(Math.random() * chars.length)];
        document.getElementById('cup-codigo').value = code;
    });

    // Forzar mayúsculas en código
    document.getElementById('cup-codigo')?.addEventListener('input', function() {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
        try { this.setSelectionRange(pos, pos); } catch(e) {}
    });

    // Copiar código al portapapeles
    document.querySelectorAll('.ttra-copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var code = this.dataset.code;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(function() {
                    btn.textContent = '✅';
                    setTimeout(function() { btn.textContent = '📋'; }, 1500);
                });
            }
        });
    });

    // Confirmación borrado
    document.querySelectorAll('.ttra-btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || '¿Estás seguro?')) e.preventDefault();
        });
    });
})();
</script>