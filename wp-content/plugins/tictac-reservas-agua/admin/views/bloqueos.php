<?php
/**
 * Vista admin: Bloqueos de fechas.
 * Variables: $actividades, $bloqueos
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$msg   = $_GET['msg'] ?? '';
$nonce = wp_create_nonce( 'ttra_admin_nonce' );
$hoy   = current_time( 'Y-m-d' );

// Agrupar bloqueos
$bloqueos_globales   = array_filter( (array) $bloqueos, fn($b) => is_null( $b->actividad_id ) );
$bloqueos_especificos = array_filter( (array) $bloqueos, fn($b) => ! is_null( $b->actividad_id ) );

// Mapa actividades
$act_map = [];
foreach ( $actividades as $a ) $act_map[ $a->id ] = $a;

// Festivos España 2025-2026 (nacionales)
$festivos = [
    '2025-01-01' => 'Año Nuevo',
    '2025-01-06' => 'Reyes Magos',
    '2025-04-18' => 'Viernes Santo',
    '2025-05-01' => 'Día del Trabajo',
    '2025-08-15' => 'Asunción',
    '2025-10-12' => 'Fiesta Nacional',
    '2025-11-01' => 'Todos los Santos',
    '2025-12-06' => 'Día de la Constitución',
    '2025-12-08' => 'Inmaculada Concepción',
    '2025-12-25' => 'Navidad',
    '2026-01-01' => 'Año Nuevo',
    '2026-01-06' => 'Reyes Magos',
];
?>

<div class="wrap ttra-admin-wrap">

    <h1 class="ttra-admin-title">
        <span class="dashicons dashicons-lock"></span>
        <?php esc_html_e( 'Gestión de Bloqueos', 'tictac-reservas-agua' ); ?>
    </h1>

    <?php if ( $msg === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible"><p>✅ <?php esc_html_e( 'Bloqueo guardado.', 'tictac-reservas-agua' ); ?></p></div>
    <?php elseif ( $msg === 'deleted' ) : ?>
        <div class="notice notice-warning is-dismissible"><p>🗑️ <?php esc_html_e( 'Bloqueo eliminado.', 'tictac-reservas-agua' ); ?></p></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: minmax(360px,420px) 1fr; gap:20px; align-items:start;">

        <!-- ══ FORMULARIO ══ -->
        <div>
            <div class="ttra-admin-card">
                <h2>🚫 <?php esc_html_e( 'Nuevo Bloqueo', 'tictac-reservas-agua' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Bloquea fechas para impedir nuevas reservas ese día (mantenimiento, festivos, temporada cerrada, etc.).', 'tictac-reservas-agua' ); ?></p>

                <form method="POST" action="" id="form-bloqueo">
                    <input type="hidden" name="ttra_action" value="save_bloqueo">
                    <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">

                    <table class="form-table ttra-form-table" style="table-layout:fixed; width:100%;">
                        <tr>
                            <th style="width:100px; padding:10px 10px 10px 0;"><?php esc_html_e( 'Alcance', 'tictac-reservas-agua' ); ?></th>
                            <td style="padding:10px 0;">
                                <label class="ttra-radio-option">
                                    <input type="radio" name="actividad_id" value="" id="blq-global" checked>
                                    <span>🌍 <?php esc_html_e( 'Bloqueo global (todas las actividades)', 'tictac-reservas-agua' ); ?></span>
                                </label>
                                <label class="ttra-radio-option" style="margin-top:8px">
                                    <input type="radio" name="actividad_id" value="especifico" id="blq-especifico">
                                    <span>🎯 <?php esc_html_e( 'Solo una actividad específica', 'tictac-reservas-agua' ); ?></span>
                                </label>
                                <div id="blq-actividad-select" style="display:none; margin-top:12px">
                                    <select name="actividad_id" id="sel-actividad-blq" class="regular-text">
                                        <option value=""><?php esc_html_e( '-- Selecciona --', 'tictac-reservas-agua' ); ?></option>
                                        <?php foreach ( $actividades as $act ) : ?>
                                            <option value="<?php echo $act->id; ?>">
                                                <?php echo esc_html( $act->nombre . ( $act->subtipo ? ' — ' . $act->subtipo : '' ) ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th style="padding:10px 10px 10px 0;"><label for="blq-fecha"><?php esc_html_e( 'Fecha *', 'tictac-reservas-agua' ); ?></label></th>
                            <td style="padding:10px 0;">
                                <input type="date" id="blq-fecha" name="fecha" required min="<?php echo $hoy; ?>"
                                       value="<?php echo $hoy; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th style="padding:10px 10px 10px 0;"><label for="blq-motivo"><?php esc_html_e( 'Motivo (opcional)', 'tictac-reservas-agua' ); ?></label></th>
                            <td style="padding:10px 0;">
                                <input type="text" id="blq-motivo" name="motivo" style="width:100%; box-sizing:border-box;"
                                       placeholder="<?php esc_attr_e( 'ej: Festivo local, Mantenimiento...', 'tictac-reservas-agua' ); ?>">
                            </td>
                        </tr>
                    </table>

                    <div class="ttra-form-actions">
                        <?php submit_button( __( 'Añadir Bloqueo', 'tictac-reservas-agua' ), 'primary', 'submit', false ); ?>
                    </div>
                </form>
            </div>

            <!-- Festivos rápidos -->
            <div class="ttra-admin-card">
                <h2>📅 <?php esc_html_e( 'Festivos nacionales', 'tictac-reservas-agua' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Haz clic para pre-rellenar la fecha del bloqueo.', 'tictac-reservas-agua' ); ?></p>
                <div class="ttra-festivos-grid">
                    <?php foreach ( $festivos as $fecha => $nombre ) :
                        $pasado = $fecha < $hoy;
                        $yaBlq  = !empty( array_filter( (array) $bloqueos_globales, fn($b) => $b->fecha === $fecha ) );
                    ?>
                        <button type="button" class="ttra-festivo-btn <?php echo $pasado ? 'ttra-festivo-btn--past' : ''; ?> <?php echo $yaBlq ? 'ttra-festivo-btn--done' : ''; ?>"
                                data-fecha="<?php echo $fecha; ?>"
                                data-motivo="<?php echo esc_attr( $nombre ); ?>"
                                <?php echo $pasado ? 'disabled' : ''; ?>>
                            <span class="ttra-festivo-btn__fecha"><?php echo date_i18n( 'd M Y', strtotime( $fecha ) ); ?></span>
                            <span class="ttra-festivo-btn__nombre"><?php echo esc_html( $nombre ); ?></span>
                            <?php if ( $yaBlq ) : ?><span class="ttra-festivo-btn__tick">✓</span><?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ══ LISTADO ══ -->
        <div>
            <!-- Filtro búsqueda -->
            <div class="ttra-admin-card">
                <div class="ttra-filter-bar">
                    <input type="text" id="blq-search" placeholder="🔍 <?php esc_attr_e( 'Buscar fecha o motivo...', 'tictac-reservas-agua' ); ?>" class="regular-text">
                    <select id="blq-tipo-filter">
                        <option value=""><?php esc_html_e( 'Todos', 'tictac-reservas-agua' ); ?></option>
                        <option value="global"><?php esc_html_e( 'Globales', 'tictac-reservas-agua' ); ?></option>
                        <option value="especifico"><?php esc_html_e( 'Específicos', 'tictac-reservas-agua' ); ?></option>
                    </select>
                </div>
            </div>

            <div class="ttra-admin-card">
                <div class="ttra-admin-card__header">
                    <h2><?php esc_html_e( 'Bloqueos activos', 'tictac-reservas-agua' ); ?></h2>
                    <span class="ttra-count-badge"><?php echo count( (array) $bloqueos ); ?></span>
                </div>

                <?php if ( empty( $bloqueos ) ) : ?>
                    <p class="ttra-empty-msg">📅 <?php esc_html_e( 'No hay fechas bloqueadas.', 'tictac-reservas-agua' ); ?></p>
                <?php else : ?>
                    <table class="widefat ttra-table" id="ttra-bloqueos-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Fecha', 'tictac-reservas-agua' ); ?></th>
                                <th><?php esc_html_e( 'Motivo', 'tictac-reservas-agua' ); ?></th>
                                <th><?php esc_html_e( 'Alcance', 'tictac-reservas-agua' ); ?></th>
                                <th style="width:80px"><?php esc_html_e( 'Acción', 'tictac-reservas-agua' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( (array) $bloqueos as $blq ) :
                                $es_pasado = $blq->fecha < $hoy;
                                $es_global = is_null( $blq->actividad_id );
                                $tipo_str  = $es_global ? 'global' : 'especifico';
                            ?>
                            <tr class="<?php echo $es_pasado ? 'ttra-row-muted' : ''; ?>"
                                data-tipo="<?php echo $tipo_str; ?>"
                                data-search="<?php echo esc_attr( $blq->fecha . ' ' . $blq->motivo ); ?>">
                                <td>
                                    <strong><?php echo date_i18n( 'D d/m/Y', strtotime( $blq->fecha ) ); ?></strong>
                                    <?php if ( $es_pasado ) : ?>
                                        <br><span class="ttra-badge ttra-badge--muted" style="font-size:10px"><?php esc_html_e( 'pasada', 'tictac-reservas-agua' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $blq->motivo ?: '—' ); ?></td>
                                <td>
                                    <?php if ( $es_global ) : ?>
                                        <span class="ttra-badge ttra-badge--warning">🌍 <?php esc_html_e( 'Global', 'tictac-reservas-agua' ); ?></span>
                                    <?php else : ?>
                                        <span class="ttra-badge ttra-badge--info">
                                            🎯 <?php echo esc_html( $act_map[ $blq->actividad_id ]->nombre ?? '—' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="ttra_action" value="delete_bloqueo">
                                        <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
                                        <input type="hidden" name="bloqueo_id" value="<?php echo intval( $blq->id ); ?>">
                                        <button type="submit" class="button button-small ttra-btn-delete"
                                                data-confirm="<?php esc_attr_e( '¿Eliminar este bloqueo?', 'tictac-reservas-agua' ); ?>">
                                            🗑️ <?php esc_html_e( 'Quitar', 'tictac-reservas-agua' ); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- .ttra-two-col -->
</div>

<script>
(function() {
    // Toggle actividad específica
    document.querySelectorAll('[name="actividad_id"]').forEach(radio => {
        if (!radio.id) return;
        radio.addEventListener('change', function() {
            const show = document.getElementById('blq-especifico')?.checked;
            document.getElementById('blq-actividad-select').style.display = show ? '' : 'none';
            if (!show) document.getElementById('sel-actividad-blq').value = '';
        });
    });
    document.getElementById('blq-global')?.addEventListener('change', function() {
        document.getElementById('blq-actividad-select').style.display = 'none';
    });
    document.getElementById('blq-especifico')?.addEventListener('change', function() {
        document.getElementById('blq-actividad-select').style.display = '';
    });

    // Festivos rápidos → pre-rellenar fecha y motivo
    document.querySelectorAll('.ttra-festivo-btn:not([disabled])').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var fechaInput  = document.getElementById('blq-fecha');
            var motivoInput = document.getElementById('blq-motivo');
            var radioGlobal = document.getElementById('blq-global');
            var divEsp      = document.getElementById('blq-actividad-select');

            if (!fechaInput) return;

            fechaInput.value  = this.dataset.fecha;
            motivoInput.value = this.dataset.motivo;
            radioGlobal.checked = true;
            if (divEsp) divEsp.style.display = 'none';

            // Flash visual en el campo de fecha
            fechaInput.style.transition = 'box-shadow 0.1s';
            fechaInput.style.boxShadow  = '0 0 0 3px #00A0E3';
            fechaInput.focus();
            setTimeout(function() { fechaInput.style.boxShadow = ''; }, 800);

            // Marcar botón como "seleccionado"
            document.querySelectorAll('.ttra-festivo-btn').forEach(function(b) {
                b.style.borderColor = '';
                b.style.background  = '';
            });
            btn.style.borderColor = '#00A0E3';
            btn.style.background  = '#e8f4fd';
        });
    });

    // Filtro texto
    const searchInput = document.getElementById('blq-search');
    const tipoFilter  = document.getElementById('blq-tipo-filter');
    function filtrar() {
        const q    = searchInput.value.toLowerCase();
        const tipo = tipoFilter.value;
        document.querySelectorAll('#ttra-bloqueos-table tbody tr').forEach(tr => {
            const search = tr.dataset.search?.toLowerCase() || '';
            const trTipo = tr.dataset.tipo || '';
            const matchQ    = !q || search.includes(q);
            const matchTipo = !tipo || trTipo === tipo;
            tr.style.display = (matchQ && matchTipo) ? '' : 'none';
        });
    }
    searchInput?.addEventListener('input', filtrar);
    tipoFilter?.addEventListener('change', filtrar);

    // Confirmación borrado
    document.querySelectorAll('.ttra-btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || '¿Estás seguro?')) e.preventDefault();
        });
    });
})();
</script>