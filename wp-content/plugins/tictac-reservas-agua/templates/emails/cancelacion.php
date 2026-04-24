<?php
/**
 * Email template: cancelacion.php
 * Variables disponibles: $reserva (object)
 * Enviado al cliente cuando su reserva es cancelada.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nombre_negocio = TTRA_Settings::get( 'nombre_negocio', get_bloginfo('name') );
$telefono       = TTRA_Settings::get( 'telefono_negocio', '' );
$logo_url       = wp_get_attachment_image_url( (int) TTRA_Settings::get('logo_id', 0), 'medium' );
if ( ! $logo_url ) {
    $logo_url = home_url( '/wp-content/uploads/2026/04/Vector.png' );
}

$total     = floatval( $reserva->total );
$fecha_res = date_i18n( 'd \d\e F \d\e Y', strtotime( $reserva->created_at ) );
$reservas_url = TTRA_Helpers::get_reservas_url();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tu reserva ha sido cancelada</title>
</head>
<body style="margin:0;padding:0;background-color:#F8FAFC;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F8FAFC;padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

        <!-- LOGO -->
        <tr>
          <td align="center" style="padding-bottom:24px;">
            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nombre_negocio ); ?>"
                 width="180" style="max-width:180px;height:auto;display:block;margin:0 auto;">
          </td>
        </tr>

        <!-- HERO -->
        <tr>
          <td style="background:linear-gradient(135deg,#1E293B 0%,#334155 100%);border-radius:20px 20px 0 0;padding:40px 40px 32px;text-align:center;">
            <h1 style="margin:0 0 14px;font-size:34px;font-weight:800;color:#ffffff;line-height:1.2;">
              Reserva cancelada
            </h1>
            <p style="margin:0 0 24px;font-size:16px;color:rgba(255,255,255,0.75);line-height:1.5;">
              Hola, <strong style="color:#fff;"><?php echo esc_html($reserva->nombre); ?></strong>.<br>
              Tu reserva ha sido cancelada correctamente.
            </p>
            <div style="display:inline-block;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.20);border-radius:12px;padding:12px 24px;">
              <p style="margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.55);">Reserva</p>
              <p style="margin:0;font-size:18px;font-weight:700;color:#fff;letter-spacing:2px;font-family:monospace;">
                <?php echo esc_html( $reserva->codigo_reserva ); ?>
              </p>
            </div>
          </td>
        </tr>

        <!-- CUERPO -->
        <tr>
          <td style="background:#ffffff;border-radius:0 0 20px 20px;padding:32px 40px 40px;">

            <p style="margin:0 0 24px;font-size:15px;color:#475569;line-height:1.7;">
              Lamentamos que no hayas podido disfrutar de tu aventura acuática esta vez.
              Si la cancelación fue por causas ajenas a ti, esperamos verte muy pronto.
            </p>

            <!-- INFO CANCELACIÓN -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F8FAFC;border-radius:12px;padding:20px 24px;margin-bottom:24px;">
              <tr>
                <td style="padding:6px 0;font-size:13px;color:#64748B;border-bottom:1px solid #E2E8F0;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="font-weight:700;color:#1E293B;">Código de reserva</td>
                      <td style="text-align:right;font-family:monospace;font-size:14px;color:#003B6F;font-weight:700;">
                        <?php echo esc_html( $reserva->codigo_reserva ); ?>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding:6px 0;font-size:13px;color:#64748B;border-bottom:1px solid #E2E8F0;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="font-weight:700;color:#1E293B;">Importe</td>
                      <td style="text-align:right;font-size:16px;font-weight:800;color:#1E293B;">
                        <?php echo number_format($total,2,',','.'); ?> €
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding:6px 0;font-size:13px;color:#64748B;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="font-weight:700;color:#1E293B;">Fecha de reserva</td>
                      <td style="text-align:right;color:#1E293B;"><?php echo esc_html($fecha_res); ?></td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <p style="margin:0 0 8px;font-size:14px;color:#64748B;line-height:1.7;">
              Si tienes dudas sobre el reembolso o necesitas más información,
              no dudes en contactarnos directamente.
            </p>

            <!-- CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
              <tr>
                <td align="center">
                  <a href="<?php echo esc_url($reservas_url); ?>"
                     style="display:inline-block;background:linear-gradient(135deg,#003B6F,#0066CC);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 32px;border-radius:50px;letter-spacing:0.5px;margin-bottom:12px;">
                    Volver a reservar →
                  </a>
                  <?php if ( $telefono ) : ?>
                  <br>
                  <a href="tel:<?php echo esc_attr( preg_replace('/\s+/','',$telefono) ); ?>"
                     style="display:inline-block;margin-top:12px;color:#27A5DE;text-decoration:none;font-size:14px;font-weight:600;">
                    📞 <?php echo esc_html($telefono); ?>
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
              <?php echo esc_html($nombre_negocio); ?>
            </p>
            <p style="margin:0;font-size:12px;color:#94A3B8;line-height:1.6;">
              Este email se generó automáticamente.<br>
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