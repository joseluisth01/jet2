<?php
/**
 * Vista admin: Categorías de actividades.
 * Variables disponibles: $categorias (array), $editando (object|null)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$msg = $_GET['msg'] ?? '';
$nonce = wp_create_nonce( 'ttra_admin_nonce' );
?>

<div class="wrap ttra-admin-wrap">

    <h1 class="ttra-admin-title">
        <span class="dashicons dashicons-category"></span>
        <?php esc_html_e( 'Categorías de Actividades', 'tictac-reservas-agua' ); ?>
    </h1>

    <?php if ( $msg === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible"><p>✅ <?php esc_html_e( 'Categoría guardada correctamente.', 'tictac-reservas-agua' ); ?></p></div>
    <?php elseif ( $msg === 'deleted' ) : ?>
        <div class="notice notice-warning is-dismissible"><p>🗑️ <?php esc_html_e( 'Categoría eliminada.', 'tictac-reservas-agua' ); ?></p></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: minmax(360px,420px) 1fr; gap:20px; align-items:start;">

        <!-- ══ FORMULARIO ══ -->
        <div class="ttra-admin-card">
            <h2><?php echo $editando ? esc_html__( 'Editar Categoría', 'tictac-reservas-agua' ) : esc_html__( 'Nueva Categoría', 'tictac-reservas-agua' ); ?></h2>

            <form method="POST" action="">
                <input type="hidden" name="ttra_action" value="save_categoria">
                <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
                <?php if ( $editando ) : ?>
                    <input type="hidden" name="categoria_id" value="<?php echo intval( $editando->id ); ?>">
                <?php endif; ?>

                <table class="form-table ttra-form-table" style="table-layout:fixed; width:100%;">
                    <tr>
                        <th style="width:120px; padding:10px 10px 10px 0;"><label for="cat-nombre"><?php esc_html_e( 'Nombre *', 'tictac-reservas-agua' ); ?></label></th>
                        <td style="padding:10px 0;">
                            <input type="text" id="cat-nombre" name="nombre" style="width:100%; box-sizing:border-box;"
                                   value="<?php echo esc_attr( $editando->nombre ?? '' ); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:10px 10px 10px 0;"><label for="cat-slug"><?php esc_html_e( 'Slug', 'tictac-reservas-agua' ); ?></label></th>
                        <td style="padding:10px 0;">
                            <input type="text" id="cat-slug" name="slug" style="width:100%; box-sizing:border-box;"
                                   value="<?php echo esc_attr( $editando->slug ?? '' ); ?>"
                                   placeholder="<?php esc_attr_e( 'Se genera automáticamente', 'tictac-reservas-agua' ); ?>">
                            <p class="description"><?php esc_html_e( 'Solo letras, números y guiones.', 'tictac-reservas-agua' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="cat-desc"><?php esc_html_e( 'Descripción', 'tictac-reservas-agua' ); ?></label></th>
                        <td>
                            <textarea id="cat-desc" name="descripcion" rows="3" class="large-text"><?php echo esc_textarea( $editando->descripcion ?? '' ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cat-orden"><?php esc_html_e( 'Orden', 'tictac-reservas-agua' ); ?></label></th>
                        <td>
                            <input type="number" id="cat-orden" name="orden" class="small-text"
                                   value="<?php echo intval( $editando->orden ?? 0 ); ?>" min="0">
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Activa', 'tictac-reservas-agua' ); ?></th>
                        <td>
                            <label class="ttra-toggle">
                                <input type="checkbox" name="activa" value="1"
                                    <?php checked( $editando ? $editando->activa : 1, 1 ); ?>>
                                <span class="ttra-toggle__slider"></span>
                                <span class="ttra-toggle__label"><?php esc_html_e( 'Categoría visible', 'tictac-reservas-agua' ); ?></span>
                            </label>
                        </td>
                    </tr>
                </table>

                <div class="ttra-form-actions">
                    <?php submit_button( $editando ? __( 'Actualizar Categoría', 'tictac-reservas-agua' ) : __( 'Crear Categoría', 'tictac-reservas-agua' ), 'primary', 'submit', false ); ?>
                    <?php if ( $editando ) : ?>
                        <a href="<?php echo admin_url( 'admin.php?page=ttra-categorias' ); ?>" class="button">
                            <?php esc_html_e( 'Cancelar', 'tictac-reservas-agua' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- ══ LISTADO ══ -->
        <div class="ttra-admin-card">
            <div class="ttra-admin-card__header">
                <h2><?php esc_html_e( 'Categorías existentes', 'tictac-reservas-agua' ); ?></h2>
                <span class="ttra-count-badge"><?php echo count( $categorias ); ?></span>
            </div>

            <?php if ( empty( $categorias ) ) : ?>
                <p class="ttra-empty-msg">📂 <?php esc_html_e( 'Aún no hay categorías. Crea la primera.', 'tictac-reservas-agua' ); ?></p>
            <?php else : ?>
                <table class="widefat ttra-table">
                    <thead>
                        <tr>
                            <th style="width:36px"><?php esc_html_e( 'Icono', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Nombre', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Slug', 'tictac-reservas-agua' ); ?></th>
                            <th style="width:60px"><?php esc_html_e( 'Orden', 'tictac-reservas-agua' ); ?></th>
                            <th style="width:80px"><?php esc_html_e( 'Estado', 'tictac-reservas-agua' ); ?></th>
                            <th style="width:120px"><?php esc_html_e( 'Acciones', 'tictac-reservas-agua' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $categorias as $cat ) : ?>
                        <tr>
                            <td class="ttra-cell-icon"><?php echo esc_html( $cat->icono ?: '📂' ); ?></td>
                            <td>
                                <strong><?php echo esc_html( $cat->nombre ); ?></strong>
                                <?php if ( $cat->descripcion ) : ?>
                                    <br><small class="ttra-text-muted"><?php echo esc_html( mb_strimwidth( $cat->descripcion, 0, 60, '...' ) ); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo esc_html( $cat->slug ); ?></code></td>
                            <td class="ttra-cell-center"><?php echo intval( $cat->orden ); ?></td>
                            <td>
                                <span class="ttra-badge ttra-badge--<?php echo $cat->activa ? 'success' : 'muted'; ?>">
                                    <?php echo $cat->activa ? esc_html__( 'Activa', 'tictac-reservas-agua' ) : esc_html__( 'Inactiva', 'tictac-reservas-agua' ); ?>
                                </span>
                            </td>
                            <td>
                                <div class="ttra-row-actions">
                                    <a href="<?php echo admin_url( 'admin.php?page=ttra-categorias&editar=' . $cat->id ); ?>"
                                       class="button button-small" title="<?php esc_attr_e( 'Editar', 'tictac-reservas-agua' ); ?>">
                                        ✏️
                                    </a>
                                    <form method="POST" style="display:inline" class="ttra-delete-form">
                                        <input type="hidden" name="ttra_action" value="delete_categoria">
                                        <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
                                        <input type="hidden" name="categoria_id" value="<?php echo intval( $cat->id ); ?>">
                                        <button type="submit" class="button button-small ttra-btn-delete"
                                                title="<?php esc_attr_e( 'Eliminar', 'tictac-reservas-agua' ); ?>"
                                                data-confirm="<?php esc_attr_e( '¿Eliminar esta categoría? Se perderán todas las actividades asociadas.', 'tictac-reservas-agua' ); ?>">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div><!-- grid layout -->
</div>

<script>
(function() {
    // Auto-slug desde nombre
    const nombre = document.getElementById('cat-nombre');
    const slug   = document.getElementById('cat-slug');
    if (nombre && slug) {
        nombre.addEventListener('input', function() {
            if (slug.dataset.manual) return;
            slug.value = this.value.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        });
        slug.addEventListener('input', () => slug.dataset.manual = 1);
    }

    // Emoji picker
    document.querySelectorAll('.ttra-icon-pick').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('cat-icono').value = this.dataset.emoji;
        });
    });

    // Confirmación borrado
    document.querySelectorAll('.ttra-btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || '¿Estás seguro?')) e.preventDefault();
        });
    });
})();
</script>