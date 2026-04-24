<?php
/**
 * Vista admin: Detalle de Reserva.
 * Variables: $reserva, $lineas, $pagos
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nonce   = wp_create_nonce( 'ttra_admin_nonce' );
$estados = TTRA_Helpers::estados_reserva();
$est     = $estados[ $reserva->estado ] ?? ['label' => $reserva->estado, 'color' => '#999'];

// Log de emails
global $wpdb;
$t_email = TTRA_DB::table( 'email_log' );
$emails_log = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM $t_email WHERE reserva_id = %d ORDER BY created_at DESC", $reserva->id
) );

$back_url = admin_url( 'admin.php?page=ttra-reservas' );
?>

<div class="wrap ttra-admin-wrap">

    <!-- Cabecera -->
    <div class="ttra-detail-header">
        <div class="ttra-detail-header__left">
            <a href="<?php echo esc_url( $back_url ); ?>" class="ttra-back-link">
                ← <?php esc_html_e( 'Volver al listado', 'tictac-reservas-agua' ); ?>
            </a>
            <h1 class="ttra-admin-title" style="margin-top:8px">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php echo esc_html( $reserva->codigo_reserva ); ?>
                <span class="ttra-badge" style="background:<?php echo esc_attr( $est['color'] ); ?>;font-size:14px;vertical-align:middle">
                    <?php echo esc_html( $est['label'] ); ?>
                </span>
            </h1>
            <p class="ttra-detail-header__meta">
                <?php esc_html_e( 'Reserva creada el', 'tictac-reservas-agua' ); ?>
                <strong><?php echo TTRA_Helpers::formato_fecha( $reserva->created_at, 'd/m/Y \a\s H:i' ); ?></strong>
            </p>
        </div>
        <div class="ttra-detail-header__actions">
            <?php if ( $reserva->telefono ) : ?>
                <a href="tel:<?php echo esc_attr( $reserva->telefono ); ?>" class="button">
                    📞 <?php echo esc_html( $reserva->telefono ); ?>
                </a>
            <?php endif; ?>
            <a href="mailto:<?php echo esc_attr( $reserva->email ); ?>" class="button">
                ✉️ <?php esc_html_e( 'Enviar email', 'tictac-reservas-agua' ); ?>
            </a>
        </div>
    </div>

    <div class="ttra-detail-grid">

        <!-- ══ COLUMNA PRINCIPAL ══ -->
        <div class="ttra-detail-main">

            <!-- Líneas de la reserva -->
            <div class="ttra-admin-card">
                <h2>🏄 <?php esc_html_e( 'Actividades reservadas', 'tictac-reservas-agua' ); ?></h2>
                <table class="widefat ttra-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Actividad', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Fecha', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Hora', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Duración', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Personas', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Sesiones', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Precio unit.', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Total línea', 'tictac-reservas-agua' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $lineas as $l ) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $l->actividad_nombre ); ?></strong>
                                <?php if ( $l->subtipo ) : ?>
                                    <br><small class="ttra-text-muted"><?php echo esc_html( $l->subtipo ); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo TTRA_Helpers::formato_fecha( $l->fecha, 'd/m/Y' ); ?></strong></td>
                            <td><?php echo esc_html( substr( $l->hora, 0, 5 ) ); ?></td>
                            <td><?php echo intval( $l->duracion_minutos ); ?> min</td>
                            <td><?php echo intval( $l->personas ); ?></td>
                            <td><?php echo intval( $l->sesiones ); ?></td>
                            <td><?php echo TTRA_Helpers::formato_precio( $l->precio_unitario ); ?></td>
                            <td><strong><?php echo TTRA_Helpers::formato_precio( $l->precio_total ); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="ttra-tfoot-subtotal">
                            <td colspan="7" style="text-align:right"><strong><?php esc_html_e( 'Subtotal', 'tictac-reservas-agua' ); ?></strong></td>
                            <td><strong><?php echo TTRA_Helpers::formato_precio( $reserva->subtotal ); ?></strong></td>
                        </tr>
                        <?php if ( $reserva->descuento > 0 ) : ?>
                        <tr>
                            <td colspan="7" style="text-align:right;color:#22C55E">🎟️ <?php esc_html_e( 'Descuento cupón', 'tictac-reservas-agua' ); ?></td>
                            <td style="color:#22C55E">-<?php echo TTRA_Helpers::formato_precio( $reserva->descuento ); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="ttra-tfoot-total">
                            <td colspan="7" style="text-align:right;font-size:16px"><strong><?php esc_html_e( 'TOTAL', 'tictac-reservas-agua' ); ?></strong></td>
                            <td style="font-size:18px"><strong><?php echo TTRA_Helpers::formato_precio( $reserva->total ); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Datos del cliente -->
            <div class="ttra-admin-card">
                <h2>👤 <?php esc_html_e( 'Datos del cliente', 'tictac-reservas-agua' ); ?></h2>
                <div class="ttra-data-grid">
                    <div class="ttra-data-item">
                        <span class="ttra-data-item__label"><?php esc_html_e( 'Nombre completo', 'tictac-reservas-agua' ); ?></span>
                        <span class="ttra-data-item__value"><?php echo esc_html( trim( $reserva->nombre . ' ' . $reserva->apellidos ) ); ?></span>
                    </div>
                    <div class="ttra-data-item">
                        <span class="ttra-data-item__label"><?php esc_html_e( 'Email', 'tictac-reservas-agua' ); ?></span>
                        <span class="ttra-data-item__value">
                            <a href="mailto:<?php echo esc_attr( $reserva->email ); ?>"><?php echo esc_html( $reserva->email ); ?></a>
                        </span>
                    </div>
                    <div class="ttra-data-item">
                        <span class="ttra-data-item__label"><?php esc_html_e( 'Teléfono', 'tictac-reservas-agua' ); ?></span>
                        <span class="ttra-data-item__value">
                            <a href="tel:<?php echo esc_attr( $reserva->telefono ); ?>"><?php echo esc_html( $reserva->telefono ); ?></a>
                        </span>
                    </div>
                    <div class="ttra-data-item">
                        <span class="ttra-data-item__label"><?php esc_html_e( 'DNI / Pasaporte', 'tictac-reservas-agua' ); ?></span>
                        <span class="ttra-data-item__value"><?php echo esc_html( $reserva->dni_pasaporte ); ?></span>
                    </div>
                    <?php if ( $reserva->fecha_nacimiento ) : ?>
                    <div class="ttra-data-item">
                        <span class="ttra-data-item__label"><?php esc_html_e( 'Fecha nacimiento', 'tictac-reservas-agua' ); ?></span>
                        <span class="ttra-data-item__value"><?php echo TTRA_Helpers::formato_fecha( $reserva->fecha_nacimiento ); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ( $reserva->direccion ) : ?>
                    <div class="ttra-data-item ttra-data-item--full">
                        <span class="ttra-data-item__label"><?php esc_html_e( 'Dirección', 'tictac-reservas-agua' ); ?></span>
                        <span class="ttra-data-item__value"><?php echo esc_html( $reserva->direccion ); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ( $reserva->notas ) : ?>
                    <div class="ttra-data-item ttra-data-item--full">
                        <span class="ttra-data-item__label"><?php esc_html_e( 'Notas', 'tictac-reservas-agua' ); ?></span>
                        <span class="ttra-data-item__value"><?php echo nl2br( esc_html( $reserva->notas ) ); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historial de pagos -->
            <?php if ( ! empty( $pagos ) ) : ?>
            <div class="ttra-admin-card">
                <h2>💳 <?php esc_html_e( 'Historial de pagos', 'tictac-reservas-agua' ); ?></h2>
                <table class="widefat ttra-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Fecha', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Método', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Importe', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Transacción', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Estado', 'tictac-reservas-agua' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $pagos as $pago ) :
                            $pago_colores = ['completado'=>'success','fallido'=>'danger','reembolsado'=>'warning','pendiente'=>'muted'];
                            $pago_color = $pago_colores[ $pago->estado ] ?? 'muted';
                        ?>
                        <tr>
                            <td><?php echo TTRA_Helpers::formato_fecha( $pago->created_at, 'd/m/Y H:i' ); ?></td>
                            <td><strong><?php echo esc_html( strtoupper( $pago->metodo ) ); ?></strong></td>
                            <td><strong><?php echo TTRA_Helpers::formato_precio( $pago->importe ); ?></strong></td>
                            <td><code style="font-size:11px"><?php echo esc_html( $pago->transaccion_id ?: '—' ); ?></code></td>
                            <td>
                                <span class="ttra-badge ttra-badge--<?php echo $pago_color; ?>">
                                    <?php echo esc_html( ucfirst( $pago->estado ) ); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Log de emails -->
            <?php if ( ! empty( $emails_log ) ) : ?>
            <div class="ttra-admin-card">
                <h2>📧 <?php esc_html_e( 'Emails enviados', 'tictac-reservas-agua' ); ?></h2>
                <table class="widefat ttra-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Fecha', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Tipo', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Destinatario', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Asunto', 'tictac-reservas-agua' ); ?></th>
                            <th><?php esc_html_e( 'Estado', 'tictac-reservas-agua' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $emails_log as $log ) : ?>
                        <tr>
                            <td><small><?php echo TTRA_Helpers::formato_fecha( $log->created_at, 'd/m/Y H:i' ); ?></small></td>
                            <td>
                                <span class="ttra-badge ttra-badge--info" style="font-size:10px">
                                    <?php echo esc_html( $log->tipo ); ?>
                                </span>
                            </td>
                            <td><small><?php echo esc_html( $log->destinatario ); ?></small></td>
                            <td><small><?php echo esc_html( $log->asunto ); ?></small></td>
                            <td>
                                <span class="ttra-badge ttra-badge--<?php echo $log->estado === 'enviado' ? 'success' : 'danger'; ?>" style="font-size:10px">
                                    <?php echo esc_html( $log->estado ); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>

        <!-- ══ SIDEBAR ══ -->
        <aside class="ttra-detail-sidebar">

            <!-- Cambiar estado -->
            <div class="ttra-admin-card">
                <h3>⚡ <?php esc_html_e( 'Cambiar estado', 'tictac-reservas-agua' ); ?></h3>
                <form method="POST" action="">
                    <input type="hidden" name="ttra_action" value="cambiar_estado_reserva">
                    <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">
                    <input type="hidden" name="reserva_id" value="<?php echo intval( $reserva->id ); ?>">
                    <select name="nuevo_estado" class="ttra-estado-select" style="width:100%;margin-bottom:10px">
                        <?php foreach ( $estados as $k => $v ) : ?>
                            <option value="<?php echo $k; ?>" <?php selected( $reserva->estado, $k ); ?>
                                    style="color:<?php echo esc_attr( $v['color'] ); ?>">
                                <?php echo esc_html( $v['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button button-primary" style="width:100%">
                        <?php esc_html_e( 'Actualizar estado', 'tictac-reservas-agua' ); ?>
                    </button>
                </form>
            </div>

            <!-- Resumen financiero -->
            <div class="ttra-admin-card">
                <h3>💰 <?php esc_html_e( 'Resumen financiero', 'tictac-reservas-agua' ); ?></h3>
                <div class="ttra-sidebar-data">
                    <div class="ttra-sidebar-row">
                        <span><?php esc_html_e( 'Subtotal', 'tictac-reservas-agua' ); ?></span>
                        <strong><?php echo TTRA_Helpers::formato_precio( $reserva->subtotal ); ?></strong>
                    </div>
                    <?php if ( $reserva->descuento > 0 ) : ?>
                    <div class="ttra-sidebar-row ttra-sidebar-row--discount">
                        <span>🎟️ <?php esc_html_e( 'Descuento', 'tictac-reservas-agua' ); ?></span>
                        <strong style="color:#22C55E">-<?php echo TTRA_Helpers::formato_precio( $reserva->descuento ); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="ttra-sidebar-row ttra-sidebar-row--total">
                        <span><strong><?php esc_html_e( 'TOTAL', 'tictac-reservas-agua' ); ?></strong></span>
                        <strong style="font-size:18px"><?php echo TTRA_Helpers::formato_precio( $reserva->total ); ?></strong>
                    </div>
                    <div class="ttra-sidebar-row">
                        <span><?php esc_html_e( 'Pagado', 'tictac-reservas-agua' ); ?></span>
                        <?php if ( $reserva->pagado ) : ?>
                            <span class="ttra-badge ttra-badge--success">✅ <?php esc_html_e( 'Sí', 'tictac-reservas-agua' ); ?></span>
                        <?php else : ?>
                            <span class="ttra-badge ttra-badge--danger">❌ <?php esc_html_e( 'No', 'tictac-reservas-agua' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ( $reserva->metodo_pago ) : ?>
                    <div class="ttra-sidebar-row">
                        <span><?php esc_html_e( 'Método', 'tictac-reservas-agua' ); ?></span>
                        <strong><?php echo esc_html( strtoupper( $reserva->metodo_pago ) ); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if ( $reserva->fecha_pago ) : ?>
                    <div class="ttra-sidebar-row">
                        <span><?php esc_html_e( 'Fecha pago', 'tictac-reservas-agua' ); ?></span>
                        <small><?php echo TTRA_Helpers::formato_fecha( $reserva->fecha_pago, 'd/m/Y H:i' ); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info técnica -->
            <div class="ttra-admin-card">
                <h3>🔧 <?php esc_html_e( 'Info técnica', 'tictac-reservas-agua' ); ?></h3>
                <div class="ttra-sidebar-data ttra-sidebar-data--sm">
                    <div class="ttra-sidebar-row">
                        <span><?php esc_html_e( 'ID Reserva', 'tictac-reservas-agua' ); ?></span>
                        <code>#<?php echo intval( $reserva->id ); ?></code>
                    </div>
                    <?php if ( $reserva->transaccion_id ) : ?>
                    <div class="ttra-sidebar-row">
                        <span><?php esc_html_e( 'ID Transacción', 'tictac-reservas-agua' ); ?></span>
                        <code style="font-size:10px;word-break:break-all"><?php echo esc_html( $reserva->transaccion_id ); ?></code>
                    </div>
                    <?php endif; ?>
                    <div class="ttra-sidebar-row">
                        <span><?php esc_html_e( 'IP cliente', 'tictac-reservas-agua' ); ?></span>
                        <code><?php echo esc_html( $reserva->ip_cliente ?: '—' ); ?></code>
                    </div>
                    <div class="ttra-sidebar-row">
                        <span><?php esc_html_e( 'Idioma', 'tictac-reservas-agua' ); ?></span>
                        <code><?php echo esc_html( $reserva->idioma ?: 'es' ); ?></code>
                    </div>
                    <div class="ttra-sidebar-row">
                        <span><?php esc_html_e( 'Última actualiz.', 'tictac-reservas-agua' ); ?></span>
                        <small><?php echo TTRA_Helpers::formato_fecha( $reserva->updated_at, 'd/m/Y H:i' ); ?></small>
                    </div>
                </div>
            </div>

        </aside>

    </div><!-- .ttra-detail-grid -->
</div>
