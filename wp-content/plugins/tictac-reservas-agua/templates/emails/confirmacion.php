<?php
/**
 * Email template: confirmacion.php
 * Variables disponibles: $reserva (object), $lineas (array)
 * Enviado al cliente cuando su reserva queda confirmada/pagada.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nombre_negocio = TTRA_Settings::get( 'nombre_negocio', get_bloginfo('name') );
$telefono       = TTRA_Settings::get( 'telefono_negocio', '' );
$logo_url       = plugins_url( 'assets/images/logo.png', TTRA_PLUGIN_DIR . 'tictac-reservas-agua.php' );
// Fallback: usar la URL del logo subido
$logo_url       = wp_get_attachment_image_url( (int) TTRA_Settings::get('logo_id', 0), 'medium' );
if ( ! $logo_url ) {
    // Logo hardcodeado relativo al dominio actual (no depende de la URL del servidor)
    $logo_url = home_url( '/wp-content/uploads/2026/04/cropped-Vector-1.png' );
}

$subtotal   = floatval( $reserva->subtotal );
$descuento  = floatval( $reserva->descuento );
$total      = floatval( $reserva->total );
$fecha_res  = date_i18n( 'd \d\e F \d\e Y', strtotime( $reserva->created_at ) );
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>¡Tu aventura está confirmada!</title>
</head>
<body style="margin:0;padding:0;background-color:#E8F4FD;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#E8F4FD;padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

        <!-- CABECERA CON LOGO -->
        <tr>
          <td align="center" style="padding-bottom:24px;">
            <a href="<?php echo esc_url( home_url() ); ?>" style="text-decoration:none;">
              <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nombre_negocio ); ?>"
                   width="180" style="max-width:180px;height:auto;display:block;margin:0 auto;">
            </a>
          </td>
        </tr>

        <!-- HERO CARD -->
        <tr>
          <td style="background:linear-gradient(135deg,#003B6F 0%,#0066CC 100%);border-radius:20px 20px 0 0;padding:40px 40px 32px;text-align:center;">
            <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:4px;text-transform:uppercase;color:#27A5DE;">
              ¡Reserva confirmada!
            </p>
            <h1 style="margin:0 0 16px;font-size:36px;font-weight:800;color:#ffffff;line-height:1.15;">
              Tu aventura<br>te espera 🌊
            </h1>
            <p style="margin:0;font-size:16px;color:rgba(255,255,255,0.80);line-height:1.5;">
              Hola, <strong style="color:#fff;"><?php echo esc_html( $reserva->nombre ); ?></strong>.<br>
              Todo listo. Aquí tienes los detalles de tu reserva.
            </p>

            <!-- CÓDIGO RESERVA -->
            <div style="margin-top:28px;display:inline-block;background:rgba(255,255,255,0.12);border:2px solid rgba(255,255,255,0.30);border-radius:12px;padding:14px 28px;">
              <p style="margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,0.65);">Código de reserva</p>
              <p style="margin:0;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:3px;font-family:monospace;">
                <?php echo esc_html( $reserva->codigo_reserva ); ?>
              </p>
            </div>
          </td>
        </tr>

        <!-- CUERPO PRINCIPAL -->
        <tr>
          <td style="background:#ffffff;border-radius:0 0 20px 20px;padding:0 40px 40px;overflow:hidden;">

            <!-- ACTIVIDADES -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:32px;">
              <tr>
                <td style="border-bottom:2px solid #E8F4FD;padding-bottom:12px;">
                  <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#27A5DE;">
                    Actividades reservadas
                  </p>
                </td>
              </tr>
              <?php if ( ! empty( $lineas ) ) : foreach ( $lineas as $linea ) : ?>
              <tr>
                <td style="padding:16px 0;border-bottom:1px solid #F0F4F8;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="vertical-align:top;">
                        <p style="margin:0 0 4px;font-size:16px;font-weight:700;color:#003B6F;">
                          <?php echo esc_html( $linea->actividad_nombre ); ?>
                          <?php if ( $linea->subtipo ) echo ' <span style="font-weight:400;color:#64748B;font-size:14px;">— ' . esc_html( $linea->subtipo ) . '</span>'; ?>
                        </p>
                        <p style="margin:0;font-size:13px;color:#64748B;line-height:1.6;">
                          📅 <?php echo date_i18n( 'l, d \d\e F \d\e Y', strtotime( $linea->fecha ) ); ?><br>
                          🕐 <?php echo esc_html( substr( $linea->hora, 0, 5 ) ); ?> h
                          &nbsp;·&nbsp;
                          ⏱ <?php echo intval( $linea->duracion_minutos ); ?> min
                          &nbsp;·&nbsp;
                          👥 <?php echo intval( $linea->personas ); ?> persona<?php echo $linea->personas > 1 ? 's' : ''; ?>
                          <?php if ( intval($linea->sesiones) > 1 ) echo '&nbsp;·&nbsp; 🔁 ' . intval($linea->sesiones) . ' sesiones'; ?>
                        </p>
                      </td>
                      <td style="vertical-align:top;text-align:right;white-space:nowrap;padding-left:16px;">
                        <p style="margin:0;font-size:18px;font-weight:800;color:#003B6F;">
                          <?php echo number_format( floatval($linea->precio_total), 2, ',', '.' ); ?> €
                        </p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <?php endforeach; endif; ?>

              <!-- TOTALES -->
              <?php if ( $descuento > 0 ) : ?>
              <tr>
                <td style="padding:12px 0 4px;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="font-size:14px;color:#64748B;">Subtotal</td>
                      <td style="font-size:14px;color:#64748B;text-align:right;"><?php echo number_format($subtotal,2,',','.'); ?> €</td>
                    </tr>
                    <tr>
                      <td style="font-size:14px;color:#22C55E;padding-top:4px;">🎟️ Descuento aplicado</td>
                      <td style="font-size:14px;color:#22C55E;text-align:right;padding-top:4px;">-<?php echo number_format($descuento,2,',','.'); ?> €</td>
                    </tr>
                  </table>
                </td>
              </tr>
              <?php endif; ?>
              <tr>
                <td style="background:#E8F4FD;border-radius:12px;padding:16px 20px;margin-top:12px;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="font-size:16px;font-weight:700;color:#003B6F;">Total pagado</td>
                      <td style="font-size:24px;font-weight:800;color:#003B6F;text-align:right;">
                        <?php echo number_format($total,2,',','.'); ?> €
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- DATOS DEL CLIENTE -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:32px;">
              <tr>
                <td style="border-bottom:2px solid #E8F4FD;padding-bottom:12px;">
                  <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#27A5DE;">
                    Tus datos
                  </p>
                </td>
              </tr>
              <tr>
                <td style="padding-top:16px;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td width="50%" style="padding:6px 0;font-size:13px;color:#64748B;vertical-align:top;">
                        <strong style="color:#003B6F;display:block;margin-bottom:2px;">Nombre</strong>
                        <?php echo esc_html( trim( $reserva->nombre . ' ' . $reserva->apellidos ) ); ?>
                      </td>
                      <td width="50%" style="padding:6px 0;font-size:13px;color:#64748B;vertical-align:top;">
                        <strong style="color:#003B6F;display:block;margin-bottom:2px;">Email</strong>
                        <?php echo esc_html( $reserva->email ); ?>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:6px 0;font-size:13px;color:#64748B;vertical-align:top;">
                        <strong style="color:#003B6F;display:block;margin-bottom:2px;">Teléfono</strong>
                        <?php echo esc_html( $reserva->telefono ); ?>
                      </td>
                      <td style="padding:6px 0;font-size:13px;color:#64748B;vertical-align:top;">
                        <strong style="color:#003B6F;display:block;margin-bottom:2px;">DNI / Pasaporte</strong>
                        <?php echo esc_html( $reserva->dni_pasaporte ); ?>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- BADGES DE CONFIANZA -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:32px;">
              <tr>
                <td align="center">
                  <table cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td align="center" style="padding:0 8px;">
                        <div style="background:#E8F4FD;border-radius:8px;padding:10px 14px;font-size:12px;color:#003B6F;font-weight:600;white-space:nowrap;">
                          ✅ Cancelación gratuita
                        </div>
                      </td>
                      <td align="center" style="padding:0 8px;">
                        <div style="background:#E8F4FD;border-radius:8px;padding:10px 14px;font-size:12px;color:#003B6F;font-weight:600;white-space:nowrap;">
                          🔒 Pago seguro
                        </div>
                      </td>
                      <td align="center" style="padding:0 8px;">
                        <div style="background:#E8F4FD;border-radius:8px;padding:10px 14px;font-size:12px;color:#003B6F;font-weight:600;white-space:nowrap;">
                          🛡️ Seguro incluido
                        </div>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:32px;">
              <tr>
                <td align="center">
                  <p style="margin:0 0 16px;font-size:14px;color:#64748B;line-height:1.6;">
                    ¿Tienes alguna duda? Estamos aquí para ayudarte.
                  </p>
                  <?php if ( $telefono ) : ?>
                  <a href="tel:<?php echo esc_attr( preg_replace('/\s+/', '', $telefono) ); ?>"
                     style="display:inline-block;background:linear-gradient(135deg,#003B6F,#0066CC);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 32px;border-radius:50px;letter-spacing:0.5px;">
                    📞 Llamar: <?php echo esc_html( $telefono ); ?>
                  </a>
                  <?php endif; ?>
                </td>
              </tr>
            </table>

          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="padding:28px 40px;text-align:center;">
            <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#003B6F;">
              <?php echo esc_html( $nombre_negocio ); ?>
            </p>
            <p style="margin:0;font-size:12px;color:#94A3B8;line-height:1.6;">
              Este email se generó automáticamente el <?php echo esc_html( $fecha_res ); ?>.<br>
              Por favor no respondas directamente a este correo.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>