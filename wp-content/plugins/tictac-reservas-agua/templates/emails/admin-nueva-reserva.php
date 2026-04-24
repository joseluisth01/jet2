<?php
/**
 * Email template: admin-nueva-reserva.php
 * Variables disponibles: $reserva (object), $lineas (array)
 * Enviado al administrador cuando se crea una nueva reserva pagada.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nombre_negocio = TTRA_Settings::get( 'nombre_negocio', get_bloginfo('name') );
$logo_url       = wp_get_attachment_image_url( (int) TTRA_Settings::get('logo_id', 0), 'medium' );
if ( ! $logo_url ) {
    $logo_url = home_url( '/wp-content/uploads/2026/04/cropped-Vector-1.png' );
}

$admin_url  = admin_url( 'admin.php?page=ttra-reservas&reserva_id=' . intval( $reserva->id ) );
$total      = floatval( $reserva->total );
$descuento  = floatval( $reserva->descuento );
$subtotal   = floatval( $reserva->subtotal );
$fecha_res  = date_i18n( 'd \d\e F \d\e Y, H:i', strtotime( $reserva->created_at ) );
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nueva reserva recibida</title>
</head>
<body style="margin:0;padding:0;background-color:#F0F4F8;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F0F4F8;padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

        <!-- LOGO -->
        <tr>
          <td align="center" style="padding-bottom:20px;">
            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nombre_negocio ); ?>"
                 width="160" style="max-width:160px;height:auto;display:block;margin:0 auto;">
          </td>
        </tr>

        <!-- HERO — Alerta de nueva reserva -->
        <tr>
          <td style="background:linear-gradient(135deg,#1a3a5c 0%,#003B6F 100%);border-radius:20px 20px 0 0;padding:32px 40px;text-align:center;">
            <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:4px;text-transform:uppercase;color:#27A5DE;">
              Panel de administración
            </p>
            <h1 style="margin:0 0 12px;font-size:30px;font-weight:800;color:#ffffff;line-height:1.2;">
              💰 Nueva reserva recibida
            </h1>
            <p style="margin:0;font-size:14px;color:rgba(255,255,255,0.75);">
              <?php echo esc_html( $fecha_res ); ?>
            </p>

            <!-- Importe destacado -->
            <div style="margin:24px auto 0;display:inline-block;background:rgba(39,165,222,0.20);border:2px solid #27A5DE;border-radius:14px;padding:16px 36px;">
              <p style="margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#27A5DE;">Total cobrado</p>
              <p style="margin:0;font-size:32px;font-weight:800;color:#ffffff;">
                <?php echo number_format($total,2,',','.'); ?> €
              </p>
            </div>
          </td>
        </tr>

        <!-- CUERPO -->
        <tr>
          <td style="background:#ffffff;border-radius:0 0 20px 20px;padding:0 40px 40px;">

            <!-- DATOS DEL CLIENTE -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:32px;">
              <tr>
                <td style="border-bottom:2px solid #E8F4FD;padding-bottom:10px;">
                  <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#27A5DE;">
                    Cliente
                  </p>
                </td>
              </tr>
              <tr>
                <td style="padding-top:16px;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td width="50%" style="padding:5px 0;font-size:13px;vertical-align:top;">
                        <strong style="color:#003B6F;display:block;font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">Nombre</strong>
                        <span style="color:#1E293B;"><?php echo esc_html( trim( $reserva->nombre . ' ' . $reserva->apellidos ) ); ?></span>
                      </td>
                      <td width="50%" style="padding:5px 0;font-size:13px;vertical-align:top;">
                        <strong style="color:#003B6F;display:block;font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">Email</strong>
                        <a href="mailto:<?php echo esc_attr($reserva->email); ?>" style="color:#27A5DE;text-decoration:none;">
                          <?php echo esc_html( $reserva->email ); ?>
                        </a>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:5px 0;font-size:13px;vertical-align:top;">
                        <strong style="color:#003B6F;display:block;font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">Teléfono</strong>
                        <a href="tel:<?php echo esc_attr( preg_replace('/\s+/','', $reserva->telefono) ); ?>" style="color:#27A5DE;text-decoration:none;">
                          <?php echo esc_html( $reserva->telefono ); ?>
                        </a>
                      </td>
                      <td style="padding:5px 0;font-size:13px;vertical-align:top;">
                        <strong style="color:#003B6F;display:block;font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">DNI / Pasaporte</strong>
                        <span style="color:#1E293B;"><?php echo esc_html( $reserva->dni_pasaporte ); ?></span>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2" style="padding:5px 0;font-size:13px;">
                        <strong style="color:#003B6F;display:block;font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">Código de reserva</strong>
                        <span style="color:#1E293B;font-family:monospace;font-size:16px;font-weight:700;background:#E8F4FD;padding:3px 10px;border-radius:6px;letter-spacing:2px;">
                          <?php echo esc_html( $reserva->codigo_reserva ); ?>
                        </span>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- ACTIVIDADES -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
              <tr>
                <td style="border-bottom:2px solid #E8F4FD;padding-bottom:10px;">
                  <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#27A5DE;">
                    Actividades contratadas
                  </p>
                </td>
              </tr>
              <?php if ( ! empty( $lineas ) ) : foreach ( $lineas as $linea ) : ?>
              <tr>
                <td style="padding:14px 0;border-bottom:1px solid #F0F4F8;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="vertical-align:top;">
                        <p style="margin:0 0 4px;font-size:15px;font-weight:700;color:#003B6F;">
                          <?php echo esc_html( $linea->actividad_nombre ); ?>
                          <?php if ( $linea->subtipo ) echo ' <span style="font-weight:400;font-size:13px;color:#64748B;">(' . esc_html($linea->subtipo) . ')</span>'; ?>
                        </p>
                        <p style="margin:0;font-size:13px;color:#64748B;line-height:1.7;">
                          📅 <strong><?php echo date_i18n( 'l, d/m/Y', strtotime($linea->fecha) ); ?></strong>
                          &nbsp;·&nbsp;
                          🕐 <strong><?php echo esc_html( substr($linea->hora,0,5) ); ?> h</strong>
                          &nbsp;·&nbsp;
                          ⏱ <?php echo intval($linea->duracion_minutos); ?> min
                          &nbsp;·&nbsp;
                          👥 <?php echo intval($linea->personas); ?> pax
                          <?php if ( intval($linea->sesiones) > 1 ) echo '&nbsp;·&nbsp; 🔁 ' . intval($linea->sesiones) . ' ses.'; ?>
                        </p>
                      </td>
                      <td style="vertical-align:top;text-align:right;white-space:nowrap;padding-left:12px;">
                        <p style="margin:0;font-size:16px;font-weight:800;color:#003B6F;">
                          <?php echo number_format( floatval($linea->precio_total), 2, ',', '.' ); ?> €
                        </p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <?php endforeach; endif; ?>

              <!-- TOTAL -->
              <?php if ( $descuento > 0 ) : ?>
              <tr>
                <td style="padding:10px 0 4px;">
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="font-size:13px;color:#64748B;">Subtotal</td>
                      <td style="text-align:right;font-size:13px;color:#64748B;"><?php echo number_format($subtotal,2,',','.'); ?> €</td>
                    </tr>
                    <tr>
                      <td style="font-size:13px;color:#22C55E;">Descuento</td>
                      <td style="text-align:right;font-size:13px;color:#22C55E;">-<?php echo number_format($descuento,2,',','.'); ?> €</td>
                    </tr>
                  </table>
                </td>
              </tr>
              <?php endif; ?>
              <tr>
                <td style="background:#E8F4FD;border-radius:10px;padding:14px 18px;margin-top:8px;">
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="font-size:15px;font-weight:700;color:#003B6F;">TOTAL COBRADO</td>
                      <td style="text-align:right;font-size:22px;font-weight:800;color:#003B6F;">
                        <?php echo number_format($total,2,',','.'); ?> €
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- MÉTODO DE PAGO -->
            <?php if ( $reserva->metodo_pago ) : ?>
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;">
              <tr>
                <td style="font-size:13px;color:#64748B;padding:8px 0;">
                  💳 Método de pago:
                  <strong style="color:#003B6F;"><?php echo esc_html( strtoupper($reserva->metodo_pago) ); ?></strong>
                  &nbsp;·&nbsp;
                  ID Transacción: <code style="background:#F0F4F8;padding:2px 8px;border-radius:4px;font-size:12px;"><?php echo esc_html( $reserva->transaccion_id ?: '—' ); ?></code>
                </td>
              </tr>
            </table>
            <?php endif; ?>

            <!-- CTA: IR AL PANEL -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
              <tr>
                <td align="center">
                  <a href="<?php echo esc_url( $admin_url ); ?>"
                     style="display:inline-block;background:linear-gradient(135deg,#003B6F,#27A5DE);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:50px;letter-spacing:0.5px;">
                    Ver reserva en el panel →
                  </a>
                </td>
              </tr>
            </table>

          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="padding:24px 40px;text-align:center;">
            <p style="margin:0;font-size:12px;color:#94A3B8;line-height:1.6;">
              Notificación automática de <strong><?php echo esc_html($nombre_negocio); ?></strong><br>
              Generada el <?php echo esc_html( $fecha_res ); ?>
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>