<?php
/**
 * Email template: recordatorio.php
 * Variables disponibles: $reserva (object), $lineas (array)
 * Enviado al cliente ~24h antes de su actividad.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nombre_negocio = TTRA_Settings::get( 'nombre_negocio', get_bloginfo('name') );
$telefono       = TTRA_Settings::get( 'telefono_negocio', '' );
$logo_url       = wp_get_attachment_image_url( (int) TTRA_Settings::get('logo_id', 0), 'medium' );
if ( ! $logo_url ) {
    $logo_url = home_url( '/wp-content/uploads/2026/04/Vector.png' );
}

// Primera actividad para mostrar en el hero
$primera = ! empty($lineas) ? $lineas[0] : null;
$fecha_actividad = $primera ? date_i18n( 'l, d \d\e F', strtotime($primera->fecha) ) : '';
$hora_actividad  = $primera ? substr($primera->hora, 0, 5) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>¡Mañana es el gran día!</title>
</head>
<body style="margin:0;padding:0;background-color:#E8F4FD;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#E8F4FD;padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

        <!-- LOGO -->
        <tr>
          <td align="center" style="padding-bottom:24px;">
            <a href="<?php echo esc_url( home_url() ); ?>" style="text-decoration:none;">
              <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nombre_negocio ); ?>"
                   width="180" style="max-width:180px;height:auto;display:block;margin:0 auto;">
            </a>
          </td>
        </tr>

        <!-- HERO -->
        <tr>
          <td style="background:linear-gradient(135deg,#F47920 0%,#FF6B00 50%,#003B6F 100%);border-radius:20px 20px 0 0;padding:40px 40px 32px;text-align:center;">
            <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:4px;text-transform:uppercase;color:rgba(255,255,255,0.80);">
              Recordatorio
            </p>
            <h1 style="margin:0 0 14px;font-size:38px;font-weight:800;color:#ffffff;line-height:1.15;">
              ¡Mañana es<br>el gran día! 🚤
            </h1>
            <p style="margin:0 0 24px;font-size:16px;color:rgba(255,255,255,0.85);line-height:1.5;">
              Hola, <strong style="color:#fff;"><?php echo esc_html($reserva->nombre); ?></strong>.<br>
              Tu aventura acuática está a punto de comenzar.
            </p>

            <!-- Fecha y hora destacadas -->
            <?php if ( $primera ) : ?>
            <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
              <tr>
                <td style="background:rgba(255,255,255,0.15);border:2px solid rgba(255,255,255,0.40);border-radius:14px;padding:16px 24px;text-align:center;">
                  <p style="margin:0 0 6px;font-size:13px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.70);">Tu cita</p>
                  <p style="margin:0 0 4px;font-size:20px;font-weight:800;color:#ffffff;">
                    📅 <?php echo esc_html( $fecha_actividad ); ?>
                  </p>
                  <p style="margin:0;font-size:20px;font-weight:800;color:#ffffff;">
                    🕐 <?php echo esc_html( $hora_actividad ); ?> h
                  </p>
                </td>
              </tr>
            </table>
            <?php endif; ?>
          </td>
        </tr>

        <!-- CUERPO -->
        <tr>
          <td style="background:#ffffff;border-radius:0 0 20px 20px;padding:0 40px 40px;">

            <!-- RESUMEN DE ACTIVIDADES -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:32px;">
              <tr>
                <td style="border-bottom:2px solid #E8F4FD;padding-bottom:10px;">
                  <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#27A5DE;">
                    Tu reserva
                  </p>
                </td>
              </tr>
              <?php if ( ! empty($lineas) ) : foreach ( $lineas as $linea ) : ?>
              <tr>
                <td style="padding:14px 0;border-bottom:1px solid #F0F4F8;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="vertical-align:top;">
                        <p style="margin:0 0 4px;font-size:16px;font-weight:700;color:#003B6F;">
                          <?php echo esc_html( $linea->actividad_nombre ); ?>
                          <?php if ( $linea->subtipo ) echo ' <span style="font-weight:400;font-size:13px;color:#64748B;">— ' . esc_html($linea->subtipo) . '</span>'; ?>
                        </p>
                        <p style="margin:0;font-size:13px;color:#64748B;line-height:1.7;">
                          📅 <?php echo date_i18n( 'l, d \d\e F \d\e Y', strtotime($linea->fecha) ); ?><br>
                          🕐 <?php echo esc_html( substr($linea->hora,0,5) ); ?> h
                          &nbsp;·&nbsp;
                          ⏱ <?php echo intval($linea->duracion_minutos); ?> min
                          &nbsp;·&nbsp;
                          👥 <?php echo intval($linea->personas); ?> persona<?php echo $linea->personas > 1 ? 's' : ''; ?>
                        </p>
                      </td>
                      <td style="vertical-align:top;text-align:right;white-space:nowrap;padding-left:12px;">
                        <p style="margin:0;font-size:15px;font-weight:700;color:#003B6F;">
                          <?php echo number_format( floatval($linea->precio_total), 2, ',', '.' ); ?> €
                        </p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </table>

            <!-- CHECKLIST "QUÉ TRAER" -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
              <tr>
                <td style="border-bottom:2px solid #E8F4FD;padding-bottom:10px;">
                  <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#27A5DE;">
                    Recuerda traer
                  </p>
                </td>
              </tr>
              <tr>
                <td style="padding:16px 0;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <?php
                    $items = [
                        ['🪪', 'Tu DNI o pasaporte', 'Necesario para identificarte al inicio de la actividad'],
                        ['🩱', 'Ropa de baño', 'Cómoda y adecuada para actividades acuáticas'],
                        ['🧴', 'Protector solar', 'Te recomendamos factor 50 o superior'],
                        ['👟', 'Calzado antideslizante', 'O te facilitaremos el material necesario'],
                        ['📱', 'Tu móvil en modo aventura', 'Y recuerda el código de reserva: ' . esc_html($reserva->codigo_reserva)],
                    ];
                    foreach ($items as $item) :
                    ?>
                    <tr>
                      <td style="padding:8px 0;border-bottom:1px solid #F8FAFC;">
                        <table cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="font-size:20px;vertical-align:top;padding-right:12px;padding-top:2px;"><?php echo $item[0]; ?></td>
                            <td style="vertical-align:top;">
                              <strong style="font-size:14px;color:#003B6F;display:block;"><?php echo esc_html($item[1]); ?></strong>
                              <span style="font-size:12px;color:#94A3B8;"><?php echo esc_html($item[2]); ?></span>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </table>
                </td>
              </tr>
            </table>

            <!-- CÓDIGO DE RESERVA -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;">
              <tr>
                <td style="background:#E8F4FD;border-radius:12px;padding:18px 24px;text-align:center;">
                  <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#64748B;">
                    Código de tu reserva
                  </p>
                  <p style="margin:0;font-size:24px;font-weight:800;color:#003B6F;letter-spacing:3px;font-family:monospace;">
                    <?php echo esc_html( $reserva->codigo_reserva ); ?>
                  </p>
                  <p style="margin:6px 0 0;font-size:12px;color:#94A3B8;">
                    Muéstralo al llegar o tenlo a mano en tu móvil
                  </p>
                </td>
              </tr>
            </table>

            <!-- CONTACTO -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
              <tr>
                <td align="center">
                  <p style="margin:0 0 16px;font-size:14px;color:#64748B;line-height:1.6;">
                    ¿Necesitas modificar o cancelar tu reserva?<br>
                    Contáctanos cuanto antes.
                  </p>
                  <?php if ( $telefono ) : ?>
                  <a href="tel:<?php echo esc_attr( preg_replace('/\s+/','',$telefono) ); ?>"
                     style="display:inline-block;background:linear-gradient(135deg,#F47920,#FF6B00);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 32px;border-radius:50px;letter-spacing:0.5px;">
                    📞 <?php echo esc_html( $telefono ); ?>
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
              Recordatorio automático enviado 24h antes de tu actividad.<br>
              Si no esperabas este email, puedes ignorarlo.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>