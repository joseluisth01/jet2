<?php
/**
 * Vista admin: Actividades.
 * Variables: $actividades, $categorias, $editando
 *
 * CHANGELOG v1.1.0
 *   + Campo `premium`    → checkbox en sección Opciones
 *   + Campo `precio_pax` → input numérico en sección Precio y duración
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$msg   = $_GET['msg'] ?? '';
$nonce = wp_create_nonce( 'ttra_admin_nonce' );
$cat_filter = intval( $_GET['cat'] ?? 0 );
?>

<div class="wrap ttra-admin-wrap">

    <h1 class="ttra-admin-title">
        <span class="dashicons dashicons-admin-generic"></span>
        <?php esc_html_e( 'Actividades', 'tictac-reservas-agua' ); ?>
        <a href="<?php echo admin_url( 'admin.php?page=ttra-actividades&nueva=1' ); ?>" class="page-title-action">
            + <?php esc_html_e( 'Nueva', 'tictac-reservas-agua' ); ?>
        </a>
    </h1>

    <?php if ( $msg === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible"><p>✅ <?php esc_html_e( 'Actividad guardada correctamente.', 'tictac-reservas-agua' ); ?></p></div>
    <?php elseif ( $msg === 'deleted' ) : ?>
        <div class="notice notice-warning is-dismissible"><p>🗑️ <?php esc_html_e( 'Actividad eliminada.', 'tictac-reservas-agua' ); ?></p></div>
    <?php endif; ?>

    <?php if ( $editando || isset( $_GET['nueva'] ) ) : ?>
    <!-- ══ FORMULARIO EDICIÓN/NUEVA ══ -->
    <div class="ttra-admin-card ttra-card--full">
        <h2><?php echo $editando ? esc_html__( 'Editar Actividad', 'tictac-reservas-agua' ) : esc_html__( 'Nueva Actividad', 'tictac-reservas-agua' ); ?></h2>

        <form method="POST" action="" id="ttra-act-form">
            <input type="hidden" name="ttra_action" value="save_actividad">
            <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
            <?php if ( $editando ) : ?>
                <input type="hidden" name="actividad_id" value="<?php echo intval( $editando->id ); ?>">
            <?php endif; ?>

            <div class="ttra-form-grid">

                <!-- ══ COLUMNA PRINCIPAL ══ -->
                <div class="ttra-form-col">

                    <!-- Información básica -->
                    <div class="ttra-form-section">
                        <h3 class="ttra-form-section__title">📋 <?php esc_html_e( 'Información básica', 'tictac-reservas-agua' ); ?></h3>

                        <table class="form-table ttra-form-table">
                            <tr>
                                <th><label for="act-cat"><?php esc_html_e( 'Categoría *', 'tictac-reservas-agua' ); ?></label></th>
                                <td>
                                    <select id="act-cat" name="categoria_id" required>
                                        <option value=""><?php esc_html_e( '-- Selecciona --', 'tictac-reservas-agua' ); ?></option>
                                        <?php foreach ( $categorias as $cat ) : ?>
                                            <option value="<?php echo $cat->id; ?>"
                                                <?php selected( $editando->categoria_id ?? 0, $cat->id ); ?>>
                                                <?php echo esc_html( ( $cat->icono ?? '' ) . ' ' . $cat->nombre ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="act-nombre"><?php esc_html_e( 'Nombre *', 'tictac-reservas-agua' ); ?></label></th>
                                <td>
                                    <input type="text" id="act-nombre" name="nombre" class="large-text"
                                           value="<?php echo esc_attr( $editando->nombre ?? '' ); ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="act-slug"><?php esc_html_e( 'Slug', 'tictac-reservas-agua' ); ?></label></th>
                                <td>
                                    <input type="text" id="act-slug" name="slug" class="regular-text"
                                           value="<?php echo esc_attr( $editando->slug ?? '' ); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="act-subtipo"><?php esc_html_e( 'Subtipo / Variante', 'tictac-reservas-agua' ); ?></label></th>
                                <td>
                                    <input type="text" id="act-subtipo" name="subtipo" class="regular-text"
                                           value="<?php echo esc_attr( $editando->subtipo ?? '' ); ?>"
                                           placeholder="<?php esc_attr_e( 'ej: 1 Hora, Nivel avanzado...', 'tictac-reservas-agua' ); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="act-desc"><?php esc_html_e( 'Descripción', 'tictac-reservas-agua' ); ?></label></th>
                                <td>
                                    <textarea id="act-desc" name="descripcion" rows="4" class="large-text"><?php echo esc_textarea( $editando->descripcion ?? '' ); ?></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Precio y duración -->
                    <div class="ttra-form-section">
                        <h3 class="ttra-form-section__title">💶 <?php esc_html_e( 'Precio y duración', 'tictac-reservas-agua' ); ?></h3>

                        <table class="form-table ttra-form-table">
                            <tr>
                                <th><label for="act-duracion"><?php esc_html_e( 'Duración (minutos) *', 'tictac-reservas-agua' ); ?></label></th>
                                <td>
                                    <input type="number" id="act-duracion" name="duracion_minutos" class="small-text"
                                           value="<?php echo intval( $editando->duracion_minutos ?? 30 ); ?>" min="5" step="5" required>
                                    <span class="description"><?php esc_html_e( 'minutos', 'tictac-reservas-agua' ); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Tipo de precio *', 'tictac-reservas-agua' ); ?></th>
                                <td>
                                    <label class="ttra-radio-option">
                                        <input type="radio" name="precio_tipo" value="fijo"
                                            <?php checked( $editando->precio_tipo ?? 'fijo', 'fijo' ); ?>>
                                        <span><?php esc_html_e( 'Precio fijo (por reserva / sesión)', 'tictac-reservas-agua' ); ?></span>
                                    </label>
                                    <label class="ttra-radio-option">
                                        <input type="radio" name="precio_tipo" value="por_persona"
                                            <?php checked( $editando->precio_tipo ?? '', 'por_persona' ); ?>>
                                        <span><?php esc_html_e( 'Por persona', 'tictac-reservas-agua' ); ?></span>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="act-precio"><?php esc_html_e( 'Precio base (€) *', 'tictac-reservas-agua' ); ?></label></th>
                                <td>
                                    <input type="number" id="act-precio" name="precio_base" class="small-text"
                                           value="<?php echo floatval( $editando->precio_base ?? 0 ); ?>" min="0" step="0.01" required>
                                    <span class="description">€</span>
                                </td>
                            </tr>

                            <!-- ─── NUEVO: Precio por persona (€/pax) ─── -->
                            <tr>
                                <th>
                                    <label for="act-precio-pax">
                                        <?php esc_html_e( 'Precio adicional por persona (€/pax)', 'tictac-reservas-agua' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="number" id="act-precio-pax" name="precio_pax" class="small-text"
                                           value="<?php echo isset( $editando->precio_pax ) && $editando->precio_pax !== null ? floatval( $editando->precio_pax ) : ''; ?>"
                                           min="0" step="0.01"
                                           placeholder="<?php esc_attr_e( 'ej: 25', 'tictac-reservas-agua' ); ?>">
                                    <span class="description">€/pax</span>
                                    <p class="description" style="margin-top:4px">
                                        <?php esc_html_e( 'Se suma al precio base. Aparece junto a la duración en la ficha de actividad (ej: "25 €/pax"). Déjalo en blanco si no aplica.', 'tictac-reservas-agua' ); ?>
                                    </p>
                                    <!-- Preview del icono que verá el cliente -->
                                    <?php $uploads_url = wp_upload_dir()['baseurl']; ?>
                                    <div style="margin-top:8px;display:flex;align-items:center;gap:8px;color:#00A0E3;font-size:12px">
                                        <img src="<?php echo esc_url( $uploads_url . '/2026/04/f59a5831c4e43fbcb8399379ef09067f4693fdb8.gif' ); ?>"
                                             alt="icono pax" style="width:22px;height:22px;object-fit:contain">
                                        <span><?php esc_html_e( 'Icono que verá el cliente junto al precio/pax', 'tictac-reservas-agua' ); ?></span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Personas y sesiones -->
                    <div class="ttra-form-section">
                        <h3 class="ttra-form-section__title">👥 <?php esc_html_e( 'Personas y sesiones', 'tictac-reservas-agua' ); ?></h3>

                        <table class="form-table ttra-form-table">
                            <tr>
                                <th><label for="act-min-per"><?php esc_html_e( 'Personas mínimas', 'tictac-reservas-agua' ); ?></label></th>
                                <td>
                                    <input type="number" id="act-min-per" name="min_personas" class="small-text"
                                           value="<?php echo intval( $editando->min_personas ?? 1 ); ?>" min="1">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="act-max-per"><?php esc_html_e( 'Personas máximas', 'tictac-reservas-agua' ); ?></label></th>
                                <td>
                                    <input type="number" id="act-max-per" name="max_personas" class="small-text"
                                           value="<?php echo intval( $editando->max_personas ?? 10 ); ?>" min="1">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="act-max-ses"><?php esc_html_e( 'Sesiones máximas', 'tictac-reservas-agua' ); ?></label></th>
                                <td>
                                    <input type="number" id="act-max-ses" name="max_sesiones" class="small-text"
                                           value="<?php echo intval( $editando->max_sesiones ?? 5 ); ?>" min="1">
                                </td>
                            </tr>
                        </table>
                    </div>

                </div><!-- .ttra-form-col (principal) -->

                <!-- ══ COLUMNA LATERAL ══ -->
                <div class="ttra-form-col ttra-form-col--aside">

                    <!-- Imagen -->
                    <div class="ttra-form-section">
                        <h3 class="ttra-form-section__title">🖼️ <?php esc_html_e( 'Imagen', 'tictac-reservas-agua' ); ?></h3>

                        <div class="ttra-media-box">
                            <?php
                            $img_id  = intval( $editando->imagen_id ?? 0 );
                            $img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : '';
                            ?>
                            <img id="ttra-img-preview"
                                 src="<?php echo esc_url( $img_url ); ?>"
                                 style="<?php echo $img_url ? '' : 'display:none;'; ?> max-width:100%; border-radius:8px; margin-bottom:10px;">
                            <input type="hidden" name="imagen_id" id="ttra-img-id" value="<?php echo $img_id; ?>">
                            <button type="button" id="ttra-media-upload" class="button button-secondary" style="width:100%">
                                📸 <?php esc_html_e( 'Seleccionar imagen', 'tictac-reservas-agua' ); ?>
                            </button>
                            <?php if ( $img_url ) : ?>
                                <button type="button" id="ttra-media-remove" class="button" style="width:100%;margin-top:4px;">
                                    ✕ <?php esc_html_e( 'Quitar imagen', 'tictac-reservas-agua' ); ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top:16px">
                            <label for="act-icono"><strong><?php esc_html_e( 'O icono emoji:', 'tictac-reservas-agua' ); ?></strong></label>
                            <input type="text" id="act-icono" name="icono" class="small-text"
                                   value="<?php echo esc_attr( $editando->icono ?? '' ); ?>" maxlength="4" placeholder="🏄">
                        </div>
                    </div>

                    <!-- Opciones -->
                    <div class="ttra-form-section">
                        <h3 class="ttra-form-section__title">⚙️ <?php esc_html_e( 'Opciones', 'tictac-reservas-agua' ); ?></h3>

                        <div class="ttra-checklist">

                            <label class="ttra-toggle">
                                <input type="checkbox" name="activa" value="1"
                                    <?php checked( $editando->activa ?? 1, 1 ); ?>>
                                <span class="ttra-toggle__slider"></span>
                                <span class="ttra-toggle__label"><?php esc_html_e( 'Actividad activa', 'tictac-reservas-agua' ); ?></span>
                            </label>

                            <!-- ─── NUEVO: Premium ─── -->
                            <label class="ttra-toggle" style="margin-top:4px;padding:10px;border:2px solid #F47920;border-radius:8px;background:#fff8f3">
                                <input type="checkbox" name="premium" value="1" id="cb-premium"
                                    <?php checked( $editando->premium ?? 0, 1 ); ?>>
                                <span class="ttra-toggle__slider" style="background:<?php echo ( $editando->premium ?? 0 ) ? '#F47920' : '#ccc'; ?>"></span>
                                <span class="ttra-toggle__label" style="font-weight:700;color:#F47920">
                                    ⭐ <?php esc_html_e( 'Actividad PREMIUM', 'tictac-reservas-agua' ); ?>
                                </span>
                            </label>
                            <p class="description" style="margin:4px 0 12px 52px;font-size:11px">
                                <?php esc_html_e( 'Las actividades premium se muestran al final de la lista con un estilo visual diferenciado.', 'tictac-reservas-agua' ); ?>
                            </p>

                            <label class="ttra-toggle">
                                <input type="checkbox" name="cancelacion_gratuita" value="1"
                                    <?php checked( $editando->cancelacion_gratuita ?? 1, 1 ); ?>>
                                <span class="ttra-toggle__slider"></span>
                                <span class="ttra-toggle__label"><?php esc_html_e( 'Cancelación gratuita', 'tictac-reservas-agua' ); ?></span>
                            </label>

                            <label class="ttra-toggle">
                                <input type="checkbox" name="requiere_equipo" value="1"
                                    <?php checked( $editando->requiere_equipo ?? 0, 1 ); ?>>
                                <span class="ttra-toggle__slider"></span>
                                <span class="ttra-toggle__label"><?php esc_html_e( 'Requiere equipo de seguridad', 'tictac-reservas-agua' ); ?></span>
                            </label>

                            <label class="ttra-toggle" id="toggle-fianza">
                                <input type="checkbox" name="requiere_fianza" value="1" id="cb-fianza"
                                    <?php checked( $editando->requiere_fianza ?? 0, 1 ); ?>>
                                <span class="ttra-toggle__slider"></span>
                                <span class="ttra-toggle__label"><?php esc_html_e( 'Requiere fianza', 'tictac-reservas-agua' ); ?></span>
                            </label>
                            <div id="fianza-importe" style="<?php echo ( $editando->requiere_fianza ?? 0 ) ? '' : 'display:none'; ?> padding-left: 52px; margin-top: 8px;">
                                <label><?php esc_html_e( 'Importe fianza (€)', 'tictac-reservas-agua' ); ?></label>
                                <input type="number" name="importe_fianza" class="small-text"
                                       value="<?php echo floatval( $editando->importe_fianza ?? 0 ); ?>" min="0" step="0.01">
                            </div>
                        </div>

                        <table class="form-table ttra-form-table" style="margin-top:16px">
                            <tr>
                                <th><label for="act-orden"><?php esc_html_e( 'Orden', 'tictac-reservas-agua' ); ?></label></th>
                                <td>
                                    <input type="number" id="act-orden" name="orden" class="small-text"
                                           value="<?php echo intval( $editando->orden ?? 0 ); ?>" min="0">
                                    <p class="description"><?php esc_html_e( 'Dentro de su categoría. Las actividades de la misma categoría se ordenan también por duración de menor a mayor.', 'tictac-reservas-agua' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div><!-- .ttra-form-col--aside -->

            </div><!-- .ttra-form-grid -->

            <div class="ttra-form-actions">
                <?php submit_button(
                    $editando
                        ? __( 'Actualizar Actividad', 'tictac-reservas-agua' )
                        : __( 'Crear Actividad', 'tictac-reservas-agua' ),
                    'primary', 'submit', false
                ); ?>
                <a href="<?php echo admin_url( 'admin.php?page=ttra-actividades' ); ?>" class="button">
                    <?php esc_html_e( 'Cancelar', 'tictac-reservas-agua' ); ?>
                </a>
            </div>
        </form>
    </div>

    <?php else : ?>
    <!-- ══ LISTADO ══ -->
    <div class="ttra-admin-card ttra-card--full">

        <!-- Filtro por categoría -->
        <div class="ttra-filter-pills" style="margin-bottom:20px">
            <a href="<?php echo admin_url( 'admin.php?page=ttra-actividades' ); ?>"
               class="ttra-pill <?php echo !$cat_filter ? 'ttra-pill--active' : ''; ?>">
                <?php esc_html_e( 'Todas', 'tictac-reservas-agua' ); ?>
                <span class="ttra-pill-count"><?php echo count( $actividades ); ?></span>
            </a>
            <?php foreach ( $categorias as $cat ) :
                $cnt = count( array_filter( $actividades, fn($a) => $a->categoria_id == $cat->id ) );
            ?>
                <a href="<?php echo admin_url( 'admin.php?page=ttra-actividades&cat=' . $cat->id ); ?>"
                   class="ttra-pill <?php echo $cat_filter == $cat->id ? 'ttra-pill--active' : ''; ?>">
                    <?php echo esc_html( ( $cat->icono ?? '' ) . ' ' . $cat->nombre ); ?>
                    <span class="ttra-pill-count"><?php echo $cnt; ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php
        $lista = $cat_filter
            ? array_filter( $actividades, fn($a) => $a->categoria_id == $cat_filter )
            : $actividades;
        ?>

        <?php if ( empty( $lista ) ) : ?>
            <p class="ttra-empty-msg">⚓ <?php esc_html_e( 'No hay actividades. ¡Crea la primera!', 'tictac-reservas-agua' ); ?></p>
            <a href="<?php echo admin_url( 'admin.php?page=ttra-actividades&nueva=1' ); ?>" class="button button-primary">
                + <?php esc_html_e( 'Nueva Actividad', 'tictac-reservas-agua' ); ?>
            </a>
        <?php else : ?>
            <table class="widefat ttra-table">
                <thead>
                    <tr>
                        <th style="width:52px"><?php esc_html_e( 'Imagen', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Nombre', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Categoría', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Duración', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Precio base', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( '€/pax', 'tictac-reservas-agua' ); ?></th>
                        <th><?php esc_html_e( 'Personas', 'tictac-reservas-agua' ); ?></th>
                        <th style="width:80px"><?php esc_html_e( 'Estado', 'tictac-reservas-agua' ); ?></th>
                        <th style="width:80px"><?php esc_html_e( 'Premium', 'tictac-reservas-agua' ); ?></th>
                        <th style="width:140px"><?php esc_html_e( 'Acciones', 'tictac-reservas-agua' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $lista as $act ) :
                        $img_url = $act->imagen_id ? wp_get_attachment_image_url( $act->imagen_id, 'thumbnail' ) : '';
                        $cat_obj = current( array_filter( $categorias, fn($c) => $c->id == $act->categoria_id ) );
                        $is_premium = !empty( $act->premium );
                    ?>
                    <tr style="<?php echo $is_premium ? 'background:linear-gradient(90deg,#fff8f0 0%,#fff 100%);border-left:3px solid #F47920' : ''; ?>">
                        <td class="ttra-cell-icon">
                            <?php if ( $img_url ) : ?>
                                <img src="<?php echo esc_url( $img_url ); ?>" style="width:44px;height:44px;object-fit:cover;border-radius:6px;">
                            <?php else : ?>
                                <span style="font-size:28px"><?php echo esc_html( $act->icono ?: '🏄' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html( $act->nombre ); ?></strong>
                            <?php if ( $act->subtipo ) : ?>
                                <br><span class="ttra-text-muted"><?php echo esc_html( $act->subtipo ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $cat_obj ? esc_html( ( $cat_obj->icono ?? '' ) . ' ' . $cat_obj->nombre ) : '—'; ?></td>
                        <td><?php echo intval( $act->duracion_minutos ); ?> min</td>
                        <td>
                            <strong><?php echo TTRA_Helpers::formato_precio( $act->precio_base ); ?></strong>
                            <br><small class="ttra-text-muted"><?php echo $act->precio_tipo === 'por_persona' ? esc_html__( '/persona', 'tictac-reservas-agua' ) : esc_html__( '/sesión', 'tictac-reservas-agua' ); ?></small>
                            <?php if ( $act->requiere_fianza ) : ?>
                                <br><small class="ttra-text-muted">+ <?php echo TTRA_Helpers::formato_precio( $act->importe_fianza ); ?> <?php esc_html_e( 'fianza', 'tictac-reservas-agua' ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( !empty( $act->precio_pax ) && floatval( $act->precio_pax ) > 0 ) : ?>
                                <strong style="color:#00A0E3"><?php echo TTRA_Helpers::formato_precio( $act->precio_pax ); ?>/pax</strong>
                            <?php else : ?>
                                <span class="ttra-text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo intval( $act->min_personas ); ?>–<?php echo intval( $act->max_personas ); ?></td>
                        <td>
                            <span class="ttra-badge ttra-badge--<?php echo $act->activa ? 'success' : 'muted'; ?>">
                                <?php echo $act->activa ? esc_html__( 'Activa', 'tictac-reservas-agua' ) : esc_html__( 'Inactiva', 'tictac-reservas-agua' ); ?>
                            </span>
                        </td>
                        <td class="ttra-cell-center">
                            <?php if ( $is_premium ) : ?>
                                <span class="ttra-badge" style="background:#F47920">⭐ Premium</span>
                            <?php else : ?>
                                <span class="ttra-text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="ttra-row-actions">
                                <a href="<?php echo admin_url( 'admin.php?page=ttra-actividades&editar=' . $act->id ); ?>"
                                   class="button button-small">✏️ <?php esc_html_e( 'Editar', 'tictac-reservas-agua' ); ?></a>
                                <a href="<?php echo admin_url( 'admin.php?page=ttra-horarios&actividad_id=' . $act->id ); ?>"
                                   class="button button-small">🕐 <?php esc_html_e( 'Horarios', 'tictac-reservas-agua' ); ?></a>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="ttra_action" value="delete_actividad">
                                    <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
                                    <input type="hidden" name="actividad_id" value="<?php echo intval( $act->id ); ?>">
                                    <button type="submit" class="button button-small ttra-btn-delete"
                                            data-confirm="<?php esc_attr_e( '¿Eliminar esta actividad? También se eliminarán sus horarios.', 'tictac-reservas-agua' ); ?>">
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
    <?php endif; ?>

</div>

<script>
(function() {
    // Auto-slug
    const nombre = document.getElementById('act-nombre');
    const slug   = document.getElementById('act-slug');
    if (nombre && slug) {
        nombre.addEventListener('input', function() {
            if (slug.dataset.manual) return;
            slug.value = this.value.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        });
        slug.addEventListener('input', () => slug.dataset.manual = 1);
    }

    // Fianza toggle
    const cbFianza  = document.getElementById('cb-fianza');
    const divFianza = document.getElementById('fianza-importe');
    if (cbFianza && divFianza) {
        cbFianza.addEventListener('change', () => {
            divFianza.style.display = cbFianza.checked ? '' : 'none';
        });
    }

    // Premium toggle → cambiar color del slider dinámicamente
    const cbPremium = document.getElementById('cb-premium');
    if (cbPremium) {
        cbPremium.addEventListener('change', function() {
            const slider = this.nextElementSibling;
            if (slider) slider.style.background = this.checked ? '#F47920' : '#ccc';
        });
    }

    // Media uploader
    const uploadBtn = document.getElementById('ttra-media-upload');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function() {
            const frame = wp.media({ title: 'Seleccionar imagen', button: { text: 'Usar imagen' }, multiple: false });
            frame.on('select', function() {
                const att = frame.state().get('selection').first().toJSON();
                document.getElementById('ttra-img-id').value = att.id;
                const preview = document.getElementById('ttra-img-preview');
                preview.src = att.sizes?.medium?.url || att.url;
                preview.style.display = '';
            });
            frame.open();
        });
    }
    const removeBtn = document.getElementById('ttra-media-remove');
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            document.getElementById('ttra-img-id').value = 0;
            const preview = document.getElementById('ttra-img-preview');
            preview.src = '';
            preview.style.display = 'none';
        });
    }

    // Confirmación borrado
    document.querySelectorAll('.ttra-btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || '¿Estás seguro?')) e.preventDefault();
        });
    });
})();
</script>