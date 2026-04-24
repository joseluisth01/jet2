<?php

/**
 * Vista admin: Ajustes del plugin — 6 tabs.
 * Variables: $settings (array), $tab (string)
 *
 * FIX v1.2:
 *   - Los checkboxes desactivados ya no se resetean al guardar.
 *     Se elimina el bloque de <input hidden> global que machacaba los valores
 *     y se usa un campo oculto por tab que indica qué campos gestiona ese tab,
 *     para que el PHP sepa poner a 0 los checkboxes no enviados.
 */
if (! defined('ABSPATH')) exit;

$nonce = wp_create_nonce('ttra_admin_nonce');
$msg   = $_GET['msg'] ?? '';
$tabs  = [
    'general'    => ['icon' => '⚙️',  'label' => 'General'],
    'reservas'   => ['icon' => '📅',  'label' => 'Reservas'],
    'redsys'     => ['icon' => '💳',  'label' => 'Redsys / TPV'],
    'emails'     => ['icon' => '📧',  'label' => 'Emails'],
    'apariencia' => ['icon' => '🎨',  'label' => 'Apariencia'],
    'pagos'      => ['icon' => '💰',  'label' => 'Métodos de pago'],
    'descuentos' => ['icon' => '🏷️',  'label' => 'Descuentos auto'],
];

function ttra_s($settings, $key, $default = '')
{
    return $settings[$key] ?? $default;
}
function ttra_checked($settings, $key, $default = 1)
{
    $val = isset($settings[$key]) ? $settings[$key] : $default;
    return $val ? 'checked' : '';
}
?>

<div class="wrap ttra-admin-wrap">

    <h1 class="ttra-admin-title">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e('Ajustes del Plugin', 'tictac-reservas-agua'); ?>
    </h1>

    <?php if ($msg === 'saved') : ?>
        <div class="notice notice-success is-dismissible">
            <p>✅ <?php esc_html_e('Ajustes guardados correctamente.', 'tictac-reservas-agua'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Tabs de navegación -->
    <nav class="ttra-settings-tabs">
        <?php foreach ($tabs as $tab_key => $tab_info) : ?>
            <a href="<?php echo admin_url('admin.php?page=ttra-ajustes&tab=' . $tab_key); ?>"
                class="ttra-settings-tab <?php echo $tab === $tab_key ? 'ttra-settings-tab--active' : ''; ?>">
                <span><?php echo $tab_info['icon']; ?></span>
                <?php echo esc_html($tab_info['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form method="POST" action="" id="ttra-ajustes-form">
        <input type="hidden" name="ttra_action" value="save_settings">
        <input type="hidden" name="ttra_nonce" value="<?php echo $nonce; ?>">

        <?php
        /**
         * FIX CRÍTICO: En lugar de enviar TODOS los settings como hidden (lo que
         * hacía que los checkboxes de otros tabs siempre se enviaran con su valor
         * anterior, sin importar si el usuario los cambió), ahora enviamos:
         *
         * 1) Solo los settings del tab ACTUAL mediante inputs visibles.
         * 2) Los settings de OTROS tabs como hidden, EXCEPTO los checkboxes
         *    que se manejan de forma especial en class-ttra-admin.php.
         *
         * Para ello, enviamos todos los settings de otros tabs como hidden,
         * y también enviamos un campo especial `ttra_tab_actual` para que el
         * PHP sepa qué tab se está guardando y pueda poner a 0 los checkboxes
         * de ese tab que no lleguen en el POST.
         */
        ?>
        <input type="hidden" name="ttra_tab_actual" value="<?php echo esc_attr($tab); ?>">

        <?php
        // Campos de los tabs que NO son el actual → se envían como hidden para no perder su valor
        // Checkboxes de tabs inactivos → se envían explícitamente con su valor actual
        $all_tab_checkboxes = [
    'general'    => [],
    'reservas'   => ['confirmacion_auto'],
    'redsys'     => [],
    'emails'     => ['email_confirmacion', 'email_recordatorio', 'email_cancelacion', 'email_admin_nueva'],
    'apariencia' => ['mostrar_cancelacion_gratuita', 'mostrar_sin_fianza', 'mostrar_pago_seguro', 'mostrar_equipo_seguridad'],
    'pagos'      => ['pago_tarjeta', 'pago_bizum', 'pago_google_pay', 'pago_apple_pay'],
    'descuentos' => ['descuento_auto_activo'],
];

        foreach ($settings as $k => $v) {
            if (is_array($v)) continue;

            // Determinar si este campo pertenece al tab actual
            $pertenece_al_tab_actual = false;
            if (isset($all_tab_checkboxes[$tab]) && in_array($k, $all_tab_checkboxes[$tab])) {
                $pertenece_al_tab_actual = true;
            }

            // Para campos no-checkbox de tabs inactivos, siempre los enviamos hidden
            // Para checkboxes del tab activo, NO los enviamos hidden (los gestiona el formulario visible)
            if (!$pertenece_al_tab_actual) {
                // Tampoco enviamos hidden si es un checkbox del tab actual (serían duplicados)
                echo '<input type="hidden" name="ttra_settings[' . esc_attr($k) . ']" value="' . esc_attr($v) . '">';
            }
        }
        ?>

        <div class="ttra-admin-card ttra-card--full ttra-settings-panel">

            <?php /* ═══════════ TAB: GENERAL ═══════════ */ if ($tab === 'general') : ?>

                <h2>⚙️ <?php esc_html_e('Información del negocio', 'tictac-reservas-agua'); ?></h2>
                <table class="form-table ttra-form-table">
                    <tr>
                        <th><label><?php esc_html_e('Nombre del negocio', 'tictac-reservas-agua'); ?></label></th>
                        <td><input type="text" name="ttra_settings[nombre_negocio]" class="regular-text"
                                value="<?php echo esc_attr(ttra_s($settings, 'nombre_negocio')); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Email de administración', 'tictac-reservas-agua'); ?></label></th>
                        <td><input type="email" name="ttra_settings[email_admin]" class="regular-text"
                                value="<?php echo esc_attr(ttra_s($settings, 'email_admin', get_option('admin_email'))); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Teléfono', 'tictac-reservas-agua'); ?></label></th>
                        <td><input type="text" name="ttra_settings[telefono_negocio]" class="regular-text"
                                value="<?php echo esc_attr(ttra_s($settings, 'telefono_negocio')); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Dirección', 'tictac-reservas-agua'); ?></label></th>
                        <td><input type="text" name="ttra_settings[direccion_negocio]" class="large-text"
                                value="<?php echo esc_attr(ttra_s($settings, 'direccion_negocio')); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Moneda', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <select name="ttra_settings[moneda]">
                                <option value="EUR" <?php selected(ttra_s($settings, 'moneda', 'EUR'), 'EUR'); ?>>EUR (€)</option>
                                <option value="USD" <?php selected(ttra_s($settings, 'moneda', 'EUR'), 'USD'); ?>>USD ($)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('URL Términos y Condiciones', 'tictac-reservas-agua'); ?></label></th>
                        <td><input type="url" name="ttra_settings[terminos_url]" class="large-text"
                                value="<?php echo esc_attr(ttra_s($settings, 'terminos_url')); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('URL Política de Privacidad', 'tictac-reservas-agua'); ?></label></th>
                        <td><input type="url" name="ttra_settings[privacidad_url]" class="large-text"
                                value="<?php echo esc_attr(ttra_s($settings, 'privacidad_url')); ?>"></td>
                    </tr>
                </table>

            <?php /* ═══════════ TAB: RESERVAS ═══════════ */ elseif ($tab === 'reservas') : ?>

                <h2>📅 <?php esc_html_e('Configuración de Reservas', 'tictac-reservas-agua'); ?></h2>
                <table class="form-table ttra-form-table">
                    <tr>
                        <th><label><?php esc_html_e('Días de antelación mínima', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="number" name="ttra_settings[dias_antelacion_min]" class="small-text"
                                value="<?php echo intval(ttra_s($settings, 'dias_antelacion_min', 1)); ?>" min="0">
                            <span class="description"><?php esc_html_e('días antes de la actividad', 'tictac-reservas-agua'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Días de antelación máxima', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="number" name="ttra_settings[dias_antelacion_max]" class="small-text"
                                value="<?php echo intval(ttra_s($settings, 'dias_antelacion_max', 90)); ?>" min="1">
                            <span class="description"><?php esc_html_e('días en el futuro', 'tictac-reservas-agua'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Horas de cancelación gratuita', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="number" name="ttra_settings[cancelacion_horas]" class="small-text"
                                value="<?php echo intval(ttra_s($settings, 'cancelacion_horas', 24)); ?>" min="0">
                            <span class="description"><?php esc_html_e('horas antes de la actividad', 'tictac-reservas-agua'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Tiempo para completar pago', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="number" name="ttra_settings[tiempo_pago_minutos]" class="small-text"
                                value="<?php echo intval(ttra_s($settings, 'tiempo_pago_minutos', 30)); ?>" min="5">
                            <span class="description"><?php esc_html_e('minutos (pasado este tiempo la reserva se cancela automáticamente)', 'tictac-reservas-agua'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Confirmación automática', 'tictac-reservas-agua'); ?></th>
                        <td>
                            <label class="ttra-toggle">
                                <input type="checkbox" name="ttra_settings[confirmacion_auto]" value="1"
                                    <?php echo ttra_checked($settings, 'confirmacion_auto', 1); ?>>
                                <span class="ttra-toggle__slider"></span>
                                <span class="ttra-toggle__label"><?php esc_html_e('Confirmar reserva automáticamente al recibir el pago', 'tictac-reservas-agua'); ?></span>
                            </label>
                        </td>
                    </tr>
                </table>

            <?php /* ═══════════ TAB: REDSYS ═══════════ */ elseif ($tab === 'redsys') : ?>

                <h2>💳 <?php esc_html_e('Configuración Redsys / TPV Virtual', 'tictac-reservas-agua'); ?></h2>
                <div class="notice notice-info inline" style="margin-bottom:16px">
                    <p>ℹ️ <?php esc_html_e('Configura aquí tus credenciales del TPV Virtual de Redsys. Puedes operar en entorno de pruebas antes de activar la producción.', 'tictac-reservas-agua'); ?></p>
                </div>
                <table class="form-table ttra-form-table">
                    <tr>
                        <th><?php esc_html_e('Entorno', 'tictac-reservas-agua'); ?></th>
                        <td>
                            <label class="ttra-radio-option">
                                <input type="radio" name="ttra_settings[redsys_entorno]" value="test"
                                    <?php checked(ttra_s($settings, 'redsys_entorno', 'test'), 'test'); ?>>
                                <span>🧪 <?php esc_html_e('Pruebas (test)', 'tictac-reservas-agua'); ?></span>
                            </label>
                            <label class="ttra-radio-option" style="margin-top:8px">
                                <input type="radio" name="ttra_settings[redsys_entorno]" value="produccion"
                                    <?php checked(ttra_s($settings, 'redsys_entorno', 'test'), 'produccion'); ?>>
                                <span>🚀 <?php esc_html_e('Producción (real)', 'tictac-reservas-agua'); ?></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Código FUC (comercio)', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="text" name="ttra_settings[redsys_fuc]" class="regular-text"
                                value="<?php echo esc_attr(ttra_s($settings, 'redsys_fuc')); ?>"
                                placeholder="999008881">
                            <p class="description"><?php esc_html_e('Número de comercio asignado por tu banco.', 'tictac-reservas-agua'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Terminal', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="text" name="ttra_settings[redsys_terminal]" class="small-text"
                                value="<?php echo esc_attr(ttra_s($settings, 'redsys_terminal', '001')); ?>"
                                placeholder="001">
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Clave secreta (SHA256)', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <div class="ttra-password-field">
                                <input type="password" name="ttra_settings[redsys_clave_secreta]" class="regular-text"
                                    id="redsys-clave" value="<?php echo esc_attr(ttra_s($settings, 'redsys_clave_secreta')); ?>">
                                <button type="button" id="toggle-clave" class="button">👁️</button>
                            </div>
                            <p class="description"><?php esc_html_e('Clave secreta para firmar peticiones. Mantén esto seguro.', 'tictac-reservas-agua'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Nombre del comercio', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="text" name="ttra_settings[redsys_nombre_comercio]" class="regular-text"
                                value="<?php echo esc_attr(ttra_s($settings, 'redsys_nombre_comercio')); ?>">
                            <p class="description"><?php esc_html_e('Nombre que aparece en el TPV al cliente.', 'tictac-reservas-agua'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('URL de notificación', 'tictac-reservas-agua'); ?></th>
                        <td>
                            <code id="redsys-notif-url"><?php echo esc_html(rest_url('ttra/v1/redsys/notification')); ?></code>
                            <button type="button" class="button button-small" onclick="navigator.clipboard?.writeText(document.getElementById('redsys-notif-url').textContent);this.textContent='✅'">📋</button>
                            <p class="description"><?php esc_html_e('Configura esta URL en el panel de tu banco como URL de notificación.', 'tictac-reservas-agua'); ?></p>
                        </td>
                    </tr>
                </table>

            <?php /* ═══════════ TAB: EMAILS ═══════════ */ elseif ($tab === 'emails') : ?>

                <h2>📧 <?php esc_html_e('Configuración de Emails', 'tictac-reservas-agua'); ?></h2>
                <table class="form-table ttra-form-table">
                    <tr>
                        <th><label><?php esc_html_e('Nombre del remitente', 'tictac-reservas-agua'); ?></label></th>
                        <td><input type="text" name="ttra_settings[email_from_name]" class="regular-text"
                                value="<?php echo esc_attr(ttra_s($settings, 'email_from_name', get_bloginfo('name'))); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Email del remitente', 'tictac-reservas-agua'); ?></label></th>
                        <td><input type="email" name="ttra_settings[email_from_address]" class="regular-text"
                                value="<?php echo esc_attr(ttra_s($settings, 'email_from_address', get_option('admin_email'))); ?>"></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <hr>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Emails activos', 'tictac-reservas-agua'); ?></th>
                        <td>
                            <div class="ttra-checklist">
                                <label class="ttra-toggle">
                                    <input type="checkbox" name="ttra_settings[email_confirmacion]" value="1"
                                        <?php echo ttra_checked($settings, 'email_confirmacion', 1); ?>>
                                    <span class="ttra-toggle__slider"></span>
                                    <span class="ttra-toggle__label">✅ <?php esc_html_e('Email de confirmación al cliente', 'tictac-reservas-agua'); ?></span>
                                </label>
                                <label class="ttra-toggle">
                                    <input type="checkbox" name="ttra_settings[email_recordatorio]" value="1"
                                        <?php echo ttra_checked($settings, 'email_recordatorio', 1); ?>>
                                    <span class="ttra-toggle__slider"></span>
                                    <span class="ttra-toggle__label">🔔 <?php esc_html_e('Email recordatorio (antes de la actividad)', 'tictac-reservas-agua'); ?></span>
                                </label>
                                <label class="ttra-toggle">
                                    <input type="checkbox" name="ttra_settings[email_cancelacion]" value="1"
                                        <?php echo ttra_checked($settings, 'email_cancelacion', 1); ?>>
                                    <span class="ttra-toggle__slider"></span>
                                    <span class="ttra-toggle__label">❌ <?php esc_html_e('Email de cancelación al cliente', 'tictac-reservas-agua'); ?></span>
                                </label>
                                <label class="ttra-toggle">
                                    <input type="checkbox" name="ttra_settings[email_admin_nueva]" value="1"
                                        <?php echo ttra_checked($settings, 'email_admin_nueva', 1); ?>>
                                    <span class="ttra-toggle__slider"></span>
                                    <span class="ttra-toggle__label">👤 <?php esc_html_e('Notificación de nueva reserva al administrador', 'tictac-reservas-agua'); ?></span>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Horas para recordatorio', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="number" name="ttra_settings[recordatorio_horas]" class="small-text"
                                value="<?php echo intval(ttra_s($settings, 'recordatorio_horas', 24)); ?>" min="1">
                            <span class="description"><?php esc_html_e('horas antes de la actividad', 'tictac-reservas-agua'); ?></span>
                        </td>
                    </tr>
                </table>

            <?php /* ═══════════ TAB: APARIENCIA ═══════════ */ elseif ($tab === 'apariencia') : ?>

                <h2>🎨 <?php esc_html_e('Apariencia del Frontend', 'tictac-reservas-agua'); ?></h2>
                <table class="form-table ttra-form-table">
                    <tr>
                        <th><label><?php esc_html_e('Color primario', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="color" name="ttra_settings[color_primario]" class="ttra-color-input"
                                value="<?php echo esc_attr(ttra_s($settings, 'color_primario', '#003B6F')); ?>">
                            <code class="ttra-color-code"><?php echo esc_html(ttra_s($settings, 'color_primario', '#003B6F')); ?></code>
                            <small class="description"><?php esc_html_e('Color principal (textos, cabeceras)', 'tictac-reservas-agua'); ?></small>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Color secundario', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="color" name="ttra_settings[color_secundario]" class="ttra-color-input"
                                value="<?php echo esc_attr(ttra_s($settings, 'color_secundario', '#00A0E3')); ?>">
                            <code class="ttra-color-code"><?php echo esc_html(ttra_s($settings, 'color_secundario', '#00A0E3')); ?></code>
                            <small class="description"><?php esc_html_e('Color azul agua (botones, selección)', 'tictac-reservas-agua'); ?></small>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Color acento', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="color" name="ttra_settings[color_acento]" class="ttra-color-input"
                                value="<?php echo esc_attr(ttra_s($settings, 'color_acento', '#F47920')); ?>">
                            <code class="ttra-color-code"><?php echo esc_html(ttra_s($settings, 'color_acento', '#F47920')); ?></code>
                            <small class="description"><?php esc_html_e('Naranja (botones CTA, precio)', 'tictac-reservas-agua'); ?></small>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Color fondo', 'tictac-reservas-agua'); ?></label></th>
                        <td>
                            <input type="color" name="ttra_settings[color_fondo]" class="ttra-color-input"
                                value="<?php echo esc_attr(ttra_s($settings, 'color_fondo', '#E8F4FD')); ?>">
                            <code class="ttra-color-code"><?php echo esc_html(ttra_s($settings, 'color_fondo', '#E8F4FD')); ?></code>
                            <small class="description"><?php esc_html_e('Fondo azul suave', 'tictac-reservas-agua'); ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div id="ttra-color-preview" style="margin-top:16px;padding:20px;border-radius:8px;background:var(--prev-bg,#E8F4FD)">
                                <h4 style="color:var(--prev-primary,#003B6F);margin:0 0 8px">Preview del diseño</h4>
                                <button style="background:var(--prev-accent,#F47920);color:#fff;border:none;padding:10px 20px;border-radius:20px;cursor:pointer;font-weight:700">RESERVAR AHORA</button>
                                <button style="background:var(--prev-secondary,#00A0E3);color:#fff;border:none;padding:10px 20px;border-radius:20px;cursor:pointer;margin-left:8px">CONTINUAR →</button>
                                <button type="button" onclick="resetColores()" class="button" style="margin-left:8px;float:right">↺ Resetear</button>
                            </div>
                        </td>
                    </tr>
                </table>

                <h2 style="margin-top:32px">🏅 <?php esc_html_e('Badges de confianza', 'tictac-reservas-agua'); ?></h2>
                <table class="form-table ttra-form-table">
                    <tr>
                        <th><?php esc_html_e('Badges visibles', 'tictac-reservas-agua'); ?></th>
                        <td>
                            <div class="ttra-checklist">
                                <label class="ttra-toggle">
                                    <input type="checkbox" name="ttra_settings[mostrar_cancelacion_gratuita]" value="1"
                                        <?php echo ttra_checked($settings, 'mostrar_cancelacion_gratuita', 1); ?>>
                                    <span class="ttra-toggle__slider"></span>
                                    <span class="ttra-toggle__label">✅ <?php esc_html_e('Cancelación gratuita', 'tictac-reservas-agua'); ?></span>
                                </label>
                                <label class="ttra-toggle">
                                    <input type="checkbox" name="ttra_settings[mostrar_sin_fianza]" value="1"
                                        <?php echo ttra_checked($settings, 'mostrar_sin_fianza', 1); ?>>
                                    <span class="ttra-toggle__slider"></span>
                                    <span class="ttra-toggle__label">✅ <?php esc_html_e('No se requiere fianza', 'tictac-reservas-agua'); ?></span>
                                </label>
                                <label class="ttra-toggle">
                                    <input type="checkbox" name="ttra_settings[mostrar_pago_seguro]" value="1"
                                        <?php echo ttra_checked($settings, 'mostrar_pago_seguro', 1); ?>>
                                    <span class="ttra-toggle__slider"></span>
                                    <span class="ttra-toggle__label">🔒 <?php esc_html_e('Pago seguro', 'tictac-reservas-agua'); ?></span>
                                </label>
                                <label class="ttra-toggle">
                                    <input type="checkbox" name="ttra_settings[mostrar_equipo_seguridad]" value="1"
                                        <?php echo ttra_checked($settings, 'mostrar_equipo_seguridad', 1); ?>>
                                    <span class="ttra-toggle__slider"></span>
                                    <span class="ttra-toggle__label">🛡️ <?php esc_html_e('Equipo de seguridad y seguro', 'tictac-reservas-agua'); ?></span>
                                </label>
                            </div>
                        </td>
                    </tr>
                </table>

            <?php /* ═══════════ TAB: PAGOS ═══════════ */ elseif ($tab === 'pagos') : ?>

                <h2>💰 <?php esc_html_e('Métodos de Pago', 'tictac-reservas-agua'); ?></h2>
                <p class="description"><?php esc_html_e('Activa los métodos de pago que quieres ofrecer a tus clientes. Todos requieren configuración Redsys activa.', 'tictac-reservas-agua'); ?></p>
                <table class="form-table ttra-form-table">
                    <tr>
                        <th><?php esc_html_e('Métodos activos', 'tictac-reservas-agua'); ?></th>
                        <td>
                            <div class="ttra-payment-methods-admin">
                                <div class="ttra-payment-admin-item">
                                    <label class="ttra-toggle">
                                        <input type="checkbox" name="ttra_settings[pago_tarjeta]" value="1"
                                            <?php echo ttra_checked($settings, 'pago_tarjeta', 1); ?>>
                                        <span class="ttra-toggle__slider"></span>
                                    </label>
                                    <div class="ttra-payment-admin-info">
                                        <strong>💳 <?php esc_html_e('Tarjeta de Crédito / Débito', 'tictac-reservas-agua'); ?></strong>
                                        <small><?php esc_html_e('Visa, Mastercard y otras. Requiere Redsys activo.', 'tictac-reservas-agua'); ?></small>
                                    </div>
                                </div>
                                <div class="ttra-payment-admin-item">
                                    <label class="ttra-toggle">
                                        <input type="checkbox" name="ttra_settings[pago_bizum]" value="1"
                                            <?php echo ttra_checked($settings, 'pago_bizum', 0); ?>>
                                        <span class="ttra-toggle__slider"></span>
                                    </label>
                                    <div class="ttra-payment-admin-info">
                                        <strong>📱 Bizum</strong>
                                        <small><?php esc_html_e('Pago instantáneo con Bizum. Requiere activación en tu banco.', 'tictac-reservas-agua'); ?></small>
                                    </div>
                                </div>
                                <div class="ttra-payment-admin-item">
                                    <label class="ttra-toggle">
                                        <input type="checkbox" name="ttra_settings[pago_google_pay]" value="1"
                                            <?php echo ttra_checked($settings, 'pago_google_pay', 0); ?>>
                                        <span class="ttra-toggle__slider"></span>
                                    </label>
                                    <div class="ttra-payment-admin-info">
                                        <strong>🅖 Google Pay</strong>
                                        <small><?php esc_html_e('Pago con Google Pay a través de Redsys XPay.', 'tictac-reservas-agua'); ?></small>
                                    </div>
                                </div>
                                <div class="ttra-payment-admin-item">
                                    <label class="ttra-toggle">
                                        <input type="checkbox" name="ttra_settings[pago_apple_pay]" value="1"
                                            <?php echo ttra_checked($settings, 'pago_apple_pay', 0); ?>>
                                        <span class="ttra-toggle__slider"></span>
                                    </label>
                                    <div class="ttra-payment-admin-info">
                                        <strong>🍎 Apple Pay</strong>
                                        <small><?php esc_html_e('Pago con Apple Pay. Requiere certificado Apple y HTTPS.', 'tictac-reservas-agua'); ?></small>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>

            <?php /* ═══════════ TAB: DESCUENTOS AUTOMÁTICOS ═══════════ */ elseif ( $tab === 'descuentos' ) : ?>

            <h2>🏷️ <?php esc_html_e( 'Descuentos automáticos por fecha de reserva', 'tictac-reservas-agua' ); ?></h2>
            <p class="description" style="margin-bottom:20px">
                <?php esc_html_e( 'Configura descuentos que se aplican automáticamente a todas las reservas realizadas dentro de un rango de fechas, sin que el cliente necesite introducir ningún cupón.', 'tictac-reservas-agua' ); ?>
            </p>

            <!-- DESCUENTO 1 -->
            <?php for ( $i = 1; $i <= 3; $i++ ) :
                $pfx = 'descuento_auto_' . $i . '_';
            ?>
            <div class="ttra-admin-card" style="margin-bottom:16px;border-left:4px solid #00A0E3">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
                    <h3 style="margin:0;font-size:15px;color:#003B6F">
                        🏷️ <?php printf( esc_html__( 'Descuento automático #%d', 'tictac-reservas-agua' ), $i ); ?>
                    </h3>
                    <label class="ttra-toggle">
                        <input type="checkbox" name="ttra_settings[<?php echo $pfx; ?>activo]" value="1"
                               <?php echo ttra_checked($settings, $pfx . 'activo', 0); ?>>
                        <span class="ttra-toggle__slider"></span>
                        <span class="ttra-toggle__label"><?php esc_html_e( 'Activo', 'tictac-reservas-agua' ); ?></span>
                    </label>
                </div>

                <table class="form-table ttra-form-table">
                    <tr>
                        <th style="width:220px"><label><?php esc_html_e( 'Nombre / descripción', 'tictac-reservas-agua' ); ?></label></th>
                        <td>
                            <input type="text" name="ttra_settings[<?php echo $pfx; ?>nombre]" class="regular-text"
                                   value="<?php echo esc_attr( ttra_s($settings, $pfx . 'nombre') ); ?>"
                                   placeholder="<?php esc_attr_e( 'ej: Promoción early bird mayo 2026', 'tictac-reservas-agua' ); ?>">
                            <p class="description"><?php esc_html_e( 'Solo para tu referencia. No lo ve el cliente.', 'tictac-reservas-agua' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Tipo de descuento', 'tictac-reservas-agua' ); ?></th>
                        <td>
                            <label class="ttra-radio-option">
                                <input type="radio" name="ttra_settings[<?php echo $pfx; ?>tipo]" value="porcentaje"
                                    <?php checked( ttra_s($settings, $pfx . 'tipo', 'porcentaje'), 'porcentaje' ); ?>>
                                <span>% <?php esc_html_e( 'Porcentaje del total', 'tictac-reservas-agua' ); ?></span>
                            </label>
                            <label class="ttra-radio-option" style="margin-top:6px">
                                <input type="radio" name="ttra_settings[<?php echo $pfx; ?>tipo]" value="fijo"
                                    <?php checked( ttra_s($settings, $pfx . 'tipo', 'porcentaje'), 'fijo' ); ?>>
                                <span>€ <?php esc_html_e( 'Importe fijo', 'tictac-reservas-agua' ); ?></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Valor del descuento', 'tictac-reservas-agua' ); ?></label></th>
                        <td>
                            <input type="number" name="ttra_settings[<?php echo $pfx; ?>valor]" class="small-text"
                                   value="<?php echo floatval( ttra_s($settings, $pfx . 'valor', 0) ); ?>"
                                   min="0" step="0.01">
                            <span class="description">
                                <?php echo ttra_s($settings, $pfx . 'tipo', 'porcentaje') === 'porcentaje' ? '%' : '€'; ?>
                                — <?php esc_html_e( '(porcentaje o euros según el tipo seleccionado)', 'tictac-reservas-agua' ); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Aplicar a reservas realizadas', 'tictac-reservas-agua' ); ?></th>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                                <label style="font-size:13px;color:#646970;white-space:nowrap"><?php esc_html_e( 'Desde', 'tictac-reservas-agua' ); ?></label>
                                <input type="date" name="ttra_settings[<?php echo $pfx; ?>fecha_inicio]"
                                       value="<?php echo esc_attr( ttra_s($settings, $pfx . 'fecha_inicio') ); ?>">
                                <label style="font-size:13px;color:#646970;white-space:nowrap"><?php esc_html_e( 'Hasta', 'tictac-reservas-agua' ); ?></label>
                                <input type="date" name="ttra_settings[<?php echo $pfx; ?>fecha_fin]"
                                       value="<?php echo esc_attr( ttra_s($settings, $pfx . 'fecha_fin') ); ?>">
                            </div>
                            <p class="description" style="margin-top:6px">
                                <?php esc_html_e( 'Fecha en que el cliente hace la reserva (no la fecha de la actividad). Deja vacío para sin límite.', 'tictac-reservas-agua' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Importe mínimo de reserva (€)', 'tictac-reservas-agua' ); ?></label></th>
                        <td>
                            <input type="number" name="ttra_settings[<?php echo $pfx; ?>minimo]" class="small-text"
                                   value="<?php echo floatval( ttra_s($settings, $pfx . 'minimo', 0) ); ?>"
                                   min="0" step="0.01">
                            <span class="description"><?php esc_html_e( '0 = sin mínimo', 'tictac-reservas-agua' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Mensaje visible al cliente', 'tictac-reservas-agua' ); ?></label></th>
                        <td>
                            <input type="text" name="ttra_settings[<?php echo $pfx; ?>mensaje]" class="large-text"
                                   value="<?php echo esc_attr( ttra_s($settings, $pfx . 'mensaje') ); ?>"
                                   placeholder="<?php esc_attr_e( 'ej: 🎉 10% de descuento por reservar antes del 20 de mayo', 'tictac-reservas-agua' ); ?>">
                            <p class="description"><?php esc_html_e( 'Se mostrará en el resumen de la reserva si este descuento se aplica. Déjalo vacío para no mostrar ningún mensaje.', 'tictac-reservas-agua' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endfor; ?>

            <div class="notice notice-info inline">
                <p>ℹ️ <?php esc_html_e( 'Los descuentos automáticos se acumulan entre sí si varios están activos y la reserva cumple sus condiciones. Si también hay un cupón aplicado, se descuenta primero el automático y luego el cupón sobre el subtotal restante.', 'tictac-reservas-agua' ); ?></p>
            </div>

        <?php endif; ?>

        </div><!-- .ttra-settings-panel -->

        <div class="ttra-form-actions" style="padding:20px 0">
            <?php submit_button(__('Guardar ajustes', 'tictac-reservas-agua'), 'primary large', 'submit', false); ?>
        </div>
    </form>
</div>

<script>
    (function() {
        // Toggle clave secreta
        document.getElementById('toggle-clave')?.addEventListener('click', function() {
            const input = document.getElementById('redsys-clave');
            input.type = input.type === 'password' ? 'text' : 'password';
            this.textContent = input.type === 'password' ? '👁️' : '🙈';
        });

        // Preview colores en tiempo real
        const colorInputs = document.querySelectorAll('.ttra-color-input');
        colorInputs.forEach(inp => {
            inp.addEventListener('input', function() {
                const code = this.nextElementSibling;
                if (code) code.textContent = this.value;
                updatePreview();
            });
        });

        function updatePreview() {
            const preview = document.getElementById('ttra-color-preview');
            if (!preview) return;
            const colors = {};
            document.querySelectorAll('.ttra-color-input').forEach(i => {
                colors[i.name.match(/\[([^\]]+)\]/)[1]] = i.value;
            });
            preview.style.setProperty('--prev-primary', colors.color_primario || '#003B6F');
            preview.style.setProperty('--prev-secondary', colors.color_secundario || '#00A0E3');
            preview.style.setProperty('--prev-accent', colors.color_acento || '#F47920');
            preview.style.setProperty('--prev-bg', colors.color_fondo || '#E8F4FD');
        }

        window.resetColores = function() {
            const defaults = {
                color_primario: '#003B6F',
                color_secundario: '#00A0E3',
                color_acento: '#F47920',
                color_fondo: '#E8F4FD',
            };
            document.querySelectorAll('.ttra-color-input').forEach(inp => {
                const key = inp.name.match(/\[([^\]]+)\]/)[1];
                if (defaults[key]) {
                    inp.value = defaults[key];
                    const code = inp.nextElementSibling;
                    if (code) code.textContent = defaults[key];
                }
            });
            updatePreview();
        };

        updatePreview();
    })();
</script>