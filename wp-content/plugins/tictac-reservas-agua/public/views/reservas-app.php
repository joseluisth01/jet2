<?php

/**
 * Vista principal del sistema de reservas (shortcode output).
 * Todo el flujo de 4 pasos se gestiona por JS (SPA).
 */

if (! defined('ABSPATH')) exit;
?>

<div id="ttra-reservas-app" class="ttra-app containerancho" data-categoria="<?php echo esc_attr($atts['categoria'] ?? ''); ?>">

    <!-- ══════════════ STEPPER ══════════════ -->
    <?php
    $base_icons = wp_upload_dir()['baseurl'] . '/2026/04/';
    $step_icons = [
        1 => [$base_icons . 'Icon-4.svg',  $base_icons . 'Icon-8.svg',  $base_icons . 'Icon-12.svg'],
        2 => [$base_icons . 'Icon-5.svg',  $base_icons . 'Icon-9.svg',  $base_icons . 'Icon-13.svg'],
        3 => [$base_icons . 'Icon-6.svg',  $base_icons . 'Icon-10.svg', $base_icons . 'Icon-14.svg'],
        4 => [$base_icons . 'Icon-7.svg',  $base_icons . 'Icon-11.svg', $base_icons . 'Icon-15.svg'],
    ];
    $step_labels = [
        1 => __('ELIGE ACTIVIDADES', 'tictac-reservas-agua'),
        2 => __('FECHA Y HORA',      'tictac-reservas-agua'),
        3 => __('TUS DATOS',         'tictac-reservas-agua'),
        4 => __('MÉTODO DE PAGO',    'tictac-reservas-agua'),
    ];
    ?>
    <div class="ttra-stepper">
        <?php foreach ($step_labels as $num => $label) :
            $icons = $step_icons[$num];
        ?>
            <div class="ttra-stepper__step <?php echo $num === 1 ? 'ttra-stepper__step--active' : ''; ?>"
                data-step="<?php echo $num; ?>">
                <img class="ttra-stepper__icon ttra-stepper__icon--pending"
                    src="<?php echo esc_url($icons[0]); ?>" alt="" aria-hidden="true">
                <img class="ttra-stepper__icon ttra-stepper__icon--active"
                    src="<?php echo esc_url($icons[1]); ?>" alt="" aria-hidden="true">
                <img class="ttra-stepper__icon ttra-stepper__icon--completed"
                    src="<?php echo esc_url($icons[2]); ?>" alt="" aria-hidden="true">
                <span class="ttra-stepper__label"><?php echo esc_html($label); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ══════════════ CONTENIDO PRINCIPAL ══════════════ -->
    <div class="ttra-layout">

        <!-- ÁREA IZQUIERDA: PASOS -->
        <div class="ttra-layout__main">

            <!-- PASO 1: Actividades -->
            <div class="ttra-step" id="ttra-step-1" data-step="1">
                <h2 class="ttra-step__title"><?php esc_html_e('ELIGE LAS ACTIVIDADES DESEADAS', 'tictac-reservas-agua'); ?></h2>
                <img src="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/2026/04/Vector-18.svg'); ?>" alt="">
                <p class="ttra-step__desc" id="ttra-paso1-desc">Selecciona una o más actividades, elige número de personas participantes y cantidad de sesiones. En el siguiente paso concreta el día y la hora que quieres.</p>

                <!-- Filtros de categoría -->
                <div class="ttra-categories" id="ttra-categories-filter"></div>

                <!-- Lista de actividades -->
                <div class="ttra-activities-list" id="ttra-activities-list">
                    <div class="ttra-loader"><?php esc_html_e('Cargando actividades...', 'tictac-reservas-agua'); ?></div>
                </div>

                <!-- Botón continuar -->
                <div class="ttra-step__actions">
                    <button class="ttra-btn ttra-btn--primary ttra-btn--next" data-next="2" disabled>
                        <?php esc_html_e('CONTINUAR (PASO 2)', 'tictac-reservas-agua'); ?> →
                    </button>
                </div>
            </div>

            <!-- PASO 2: Fecha y Hora -->
            <div class="ttra-step ttra-step--hidden" id="ttra-step-2" data-step="2">
                <h2 class="ttra-step__title"><?php esc_html_e('SELECCIONA FECHA Y HORA', 'tictac-reservas-agua'); ?></h2>
                                <img src="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/2026/04/Vector-18.svg'); ?>" alt="">

                <p class="ttra-step__desc" id="ttra-paso2-desc">Elige fecha y hora por cada una de las actividades seleccionadas en tu reserva. En el siguiente paso facilita tus datos personales.</p>

                <!-- Calendarios dinámicos por actividad seleccionada -->
                <div class="ttra-calendars-grid" id="ttra-calendars-grid"></div>

                <div class="ttra-step__actions ttra-step__actions--between">
                    <button class="ttra-btn ttra-btn--outline ttra-btn--prev" data-prev="1">
                        ← <?php esc_html_e('RETROCEDER (PASO 1)', 'tictac-reservas-agua'); ?>
                    </button>
                    <button class="ttra-btn ttra-btn--primary ttra-btn--next" data-next="3" disabled>
                        <?php esc_html_e('CONTINUAR (PASO 3)', 'tictac-reservas-agua'); ?> →
                    </button>
                </div>
            </div>

            <!-- PASO 3: Datos del cliente -->
            <div class="ttra-step ttra-step--hidden" id="ttra-step-3" data-step="3">
                <h2 class="ttra-step__title"><?php esc_html_e('RELLENA CON TUS DATOS', 'tictac-reservas-agua'); ?></h2>
                <img src="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/2026/04/Vector-18.svg'); ?>" alt="">
                <p class="ttra-step__desc" id="ttra-paso3-desc">Facilita tus datos para continuar avanzando en tu reserva. En el siguiente paso aporta la información necesarioa sobre el pago de la reserva de actividades.</p>

                <div class="ttra-form" id="ttra-form-datos">
                    <div class="ttra-form__row">
                        <div class="ttra-form__field ttra-form__field--full">
                            <label><?php esc_html_e('Nombre y apellido*', 'tictac-reservas-agua'); ?></label>
                            <input type="text" name="nombre" required>
                        </div>
                    </div>
                    <div class="ttra-form__row">
                        <div class="ttra-form__field">
                            <label><?php esc_html_e('Teléfono*', 'tictac-reservas-agua'); ?></label>
                            <input type="tel" name="telefono" required>
                        </div>
                        <div class="ttra-form__field">
                            <label><?php esc_html_e('Email*', 'tictac-reservas-agua'); ?></label>
                            <input type="email" name="email" required>
                        </div>
                    </div>
                    <div class="ttra-form__row">
                        <div class="ttra-form__field">
                            <label><?php esc_html_e('Nº DNI/Pasaporte *', 'tictac-reservas-agua'); ?></label>
                            <input type="text" name="dni_pasaporte" required>
                        </div>
                        <div class="ttra-form__field">
                            <label><?php esc_html_e('Fecha de Nacimiento *', 'tictac-reservas-agua'); ?></label>
                            <div class="ttra-form__date-group">
                                <select name="nacimiento_dia" class="ttra-select">
                                    <option value="">--</option>
                                    <?php for ($d = 1; $d <= 31; $d++) : ?>
                                        <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select name="nacimiento_mes" class="ttra-select">
                                    <option value="">--</option>
                                    <?php
                                    $meses = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
                                    foreach ($meses as $i => $m) :
                                    ?>
                                        <option value="<?php echo $i + 1; ?>"><?php echo $m; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="nacimiento_anyo" class="ttra-select">
                                    <option value="">--</option>
                                    <?php for ($y = date('Y'); $y >= 1920; $y--) : ?>
                                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="ttra-form__row">
                        <div class="ttra-form__field ttra-form__field--full">
                            <label><?php esc_html_e('Dirección en España (si aplica)', 'tictac-reservas-agua'); ?></label>
                            <input type="text" name="direccion">
                        </div>
                    </div>
                </div>

                <div class="ttra-step__actions ttra-step__actions--between">
                    <button class="ttra-btn ttra-btn--outline ttra-btn--prev" data-prev="2">
                        ← <?php esc_html_e('RETROCEDER (PASO 2)', 'tictac-reservas-agua'); ?>
                    </button>
                    <button class="ttra-btn ttra-btn--primary ttra-btn--next" data-next="4">
                        <?php esc_html_e('CONTINUAR (PASO 4)', 'tictac-reservas-agua'); ?> →
                    </button>
                </div>
            </div>

            <!-- PASO 4: Método de pago -->
            <div class="ttra-step ttra-step--hidden" id="ttra-step-4" data-step="4">
                <h2 class="ttra-step__title"><?php esc_html_e('FINALIZA: MÉTODOS DE PAGO', 'tictac-reservas-agua'); ?></h2>
                <img src="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/2026/04/Vector-18.svg'); ?>" alt="">
                <p class="ttra-step__desc" id="ttra-paso4-desc">Remata el proceso de reserva seleccionando el método de pago y aceptando terminos. Se requiere el pago completo al realizar la reserva para asegurar tu plaza. No se ofrece opción de depósito para esta actividad.</p>

                <div class="ttra-payment-methods" id="ttra-payment-methods"></div>

                <div class="ttra-step__actions ttra-step__actions--between">
                    <button class="ttra-btn ttra-btn--outline ttra-btn--prev" data-prev="3">
                        ← <?php esc_html_e('RETROCEDER (PASO 3)', 'tictac-reservas-agua'); ?>
                    </button>
                    <button class="ttra-btn ttra-btn--primary ttra-btn--submit" id="ttra-btn-finalizar">
                        <?php esc_html_e('FINALIZAR RESERVA', 'tictac-reservas-agua'); ?> →
                    </button>
                </div>
            </div>

            <!-- PASO 5: Confirmación (post-pago) -->
            <div class="ttra-step ttra-step--hidden" id="ttra-step-confirm" data-step="confirm">
                <div class="ttra-confirmation" id="ttra-confirmation"></div>
            </div>

        </div>

        <!-- SIDEBAR: RESUMEN -->
        <aside class="ttra-layout__sidebar">
            <div class="ttra-summary" id="ttra-summary">
                <h3 class="ttra-summary__title"><?php esc_html_e('RESUMEN DE LA RESERVA', 'tictac-reservas-agua'); ?></h3>
                <img src="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/2026/04/Vector-18.svg'); ?>" alt="">


                <div class="ttra-summary__items" id="ttra-summary-items">
                    <!-- Se rellena dinámicamente por JS -->
                </div>

                <div class="ttra-summary__total">
                    <span class="ttra-summary__total-label"><?php esc_html_e('Total', 'tictac-reservas-agua'); ?></span>
                    <span class="ttra-summary__total-price" id="ttra-summary-total">0 €</span>
                </div>

                <!-- Badges de confianza -->
                <div class="ttra-trust-badges" id="ttra-trust-badges"></div>

                <!-- Botón duplicado en sidebar -->
                <button class="ttra-btn ttra-btn--primary ttra-btn--sidebar-cta" id="ttra-sidebar-cta" disabled>
                    <?php esc_html_e('CONTINUAR (PASO 2)', 'tictac-reservas-agua'); ?> →
                </button>
            </div>
        </aside>

    </div>

    <!-- Form oculto para redirigir a Redsys -->
    <form id="ttra-redsys-form" method="POST" style="display:none;">
        <input type="hidden" name="Ds_SignatureVersion">
        <input type="hidden" name="Ds_MerchantParameters">
        <input type="hidden" name="Ds_Signature">
    </form>

<?php $uploads = wp_upload_dir()['baseurl'] . '/2026/04/'; ?>

<div class="garantias">
    <div class="garantias__item">
        <div class="garantias__icon">
            <img src="<?php echo esc_url( $uploads . '93f14017b33b48a35dc04af0a33c6b88a8185c36.gif' ); ?>" alt="Cancelación gratis disponible">
        </div>
        <p class="garantias__label">CANCELACIÓN GRATIS DISPONIBLE</p>
    </div>
    <div class="garantias__item">
        <div class="garantias__icon">
            <img src="<?php echo esc_url( $uploads . 'f59a5831c4e43fbcb8399379ef09067f4693fdb8.gif' ); ?>" alt="No se requiere fianza">
        </div>
        <p class="garantias__label">NO SE REQUIERE FIANZA</p>
    </div>
    <div class="garantias__item">
        <div class="garantias__icon">
            <img src="<?php echo esc_url( $uploads . '91b8037f2597f5f9e26b9ecbf98793179e951e1c.gif' ); ?>" alt="Pago totalmente seguro">
        </div>
        <p class="garantias__label">PAGO TOTALMENTE SEGURO</p>
    </div>
    <div class="garantias__item">
        <div class="garantias__icon">
            <img src="<?php echo esc_url( $uploads . 'f57ba4110a8f76a070d7e2e8617f182e48193253.gif' ); ?>" alt="Seguro y supervisión">
        </div>
        <p class="garantias__label">SEGURO Y SUPERVISIÓN</p>
    </div>
</div>

</div>