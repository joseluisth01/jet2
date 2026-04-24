/**
 * TicTac Reservas Agua — Admin JS
 * Funcionalidades: media uploader, auto-slug, emoji picker, cambio de estado,
 * live preview de colores, confirmaciones de borrado, portapapeles, toggles, validación.
 */

(function ($) {
    'use strict';

    /* ══════════════════════════════════════════
       INIT
       ══════════════════════════════════════════ */
    $(document).ready(function () {
        TTRA_Admin.init();
    });

    const TTRA_Admin = {

        init() {
            this.notices();
            this.deleteConfirm();
            this.autoSlug();
            this.emojiPicker();
            this.mediaUploader();
            this.fianzaToggle();
            this.colorPickers();
            this.estadoRapido();
            this.copyToClipboard();
            this.horarioRows();
            this.filterTable();
            this.tabsAjustes();
            this.couponGenerator();
            this.redsysUrlGenerator();
            this.formValidation();
        },

        /* ──────────────────────────────────────
           1. NOTICES — auto-dismiss después de 4s
           ────────────────────────────────────── */
        notices() {
            setTimeout(function () {
                $('.notice.is-dismissible').fadeOut(600, function () {
                    $(this).remove();
                });
            }, 4000);
        },

        /* ──────────────────────────────────────
           2. CONFIRMACIONES DE BORRADO
           ────────────────────────────────────── */
        deleteConfirm() {
            $(document).on('click', '.ttra-btn-delete', function (e) {
                const msg = $(this).data('confirm') || '¿Estás seguro de que quieres eliminar este elemento? Esta acción no se puede deshacer.';
                if (!confirm(msg)) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });

            // También para formularios con clase ttra-delete-form
            $(document).on('submit', '.ttra-delete-form', function (e) {
                const btn = $(this).find('.ttra-btn-delete');
                if (btn.length && !btn.data('confirmed')) {
                    const msg = btn.data('confirm') || '¿Estás seguro de que quieres eliminar este elemento?';
                    if (!confirm(msg)) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        },

        /* ──────────────────────────────────────
           3. AUTO-SLUG DESDE NOMBRE
           ────────────────────────────────────── */
        autoSlug() {
            const sources = [
                { from: '#cat-nombre', to: '#cat-slug' },
                { from: '#act-nombre', to: '#act-slug' },
                { from: '[name="nombre"]', to: '[name="slug"]' },
            ];

            sources.forEach(({ from, to }) => {
                const $from = $(from).first();
                const $to   = $(to).first();
                if (!$from.length || !$to.length) return;

                $from.on('input.autoslug', function () {
                    if ($to.data('manual')) return;
                    $to.val(TTRA_Admin.slugify($(this).val()));
                });

                $to.on('input.autoslug', function () {
                    $to.data('manual', !!$(this).val());
                });
            });
        },

        slugify(text) {
            return text
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/[\s-]+/g, '-');
        },

        /* ──────────────────────────────────────
           4. EMOJI / ICONO PICKER
           ────────────────────────────────────── */
        emojiPicker() {
            $(document).on('click', '.ttra-icon-pick', function (e) {
                e.preventDefault();
                const emoji = $(this).data('emoji');
                const $input = $(this).closest('.ttra-icon-picker, .ttra-icon-field').find('input[name="icono"]');
                $input.val(emoji).trigger('change');

                // Resaltar seleccionado
                $(this).closest('.ttra-icon-suggestions, .ttra-emoji-picker')
                    .find('.ttra-icon-pick, .ttra-emoji-option')
                    .removeClass('ttra-icon-pick--active');
                $(this).addClass('ttra-icon-pick--active');
            });

            $(document).on('click', '.ttra-emoji-option', function () {
                const emoji = $(this).data('emoji');
                $(this).closest('.ttra-emoji-picker').prev('input').val(emoji);
                $(this).siblings().removeClass('ttra-emoji-option--active');
                $(this).addClass('ttra-emoji-option--active');
            });
        },

        /* ──────────────────────────────────────
           5. MEDIA UPLOADER (imagen actividad)
           ────────────────────────────────────── */
        mediaUploader() {
            $(document).on('click', '.ttra-media-upload', function (e) {
                e.preventDefault();
                const $btn     = $(this);
                const $wrap    = $btn.closest('.ttra-media-wrap, .ttra-imagen-wrap');
                const $idInput = $wrap.find('.ttra-media-id, [name="imagen_id"]');
                const $preview = $wrap.find('.ttra-media-preview');
                const $remove  = $wrap.find('.ttra-media-remove');

                if (typeof wp === 'undefined' || !wp.media) return;

                const frame = wp.media({
                    title: 'Seleccionar imagen de la actividad',
                    button: { text: 'Usar esta imagen' },
                    multiple: false,
                    library: { type: 'image' },
                });

                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $idInput.val(attachment.id);

                    const thumbUrl = attachment.sizes?.medium?.url
                        || attachment.sizes?.thumbnail?.url
                        || attachment.url;

                    if ($preview.is('img')) {
                        $preview.attr('src', thumbUrl).show();
                    } else {
                        $preview.html(`<img src="${thumbUrl}" style="max-width:200px;height:auto;border-radius:6px;">`).show();
                    }

                    $btn.text('Cambiar imagen');
                    $remove.show();
                });

                frame.open();
            });

            // Botón eliminar imagen
            $(document).on('click', '.ttra-media-remove', function (e) {
                e.preventDefault();
                const $wrap = $(this).closest('.ttra-media-wrap, .ttra-imagen-wrap');
                $wrap.find('.ttra-media-id, [name="imagen_id"]').val('');
                $wrap.find('.ttra-media-preview').hide().html('');
                $wrap.find('.ttra-media-upload').text('Subir imagen');
                $(this).hide();
            });
        },

        /* ──────────────────────────────────────
           6. TOGGLE FIANZA EN ACTIVIDADES
           ────────────────────────────────────── */
        fianzaToggle() {
            const $chk  = $('[name="requiere_fianza"]');
            const $wrap = $('.ttra-fianza-wrap, #ttra-fianza-importe-wrap');

            if (!$chk.length || !$wrap.length) return;

            function update() {
                if ($chk.is(':checked')) {
                    $wrap.slideDown(200);
                } else {
                    $wrap.slideUp(200);
                }
            }
            update();
            $chk.on('change', update);
        },

        /* ──────────────────────────────────────
           7. COLOR PICKERS + LIVE PREVIEW
           ────────────────────────────────────── */
        colorPickers() {
            const $pickers = $('input[type="color"].ttra-color-picker');
            if (!$pickers.length) return;

            $pickers.on('input change', function () {
                const varName = $(this).data('var');
                const color   = $(this).val();

                // Actualizar CSS variable en tiempo real
                if (varName) {
                    document.documentElement.style.setProperty(varName, color);
                    $(this).siblings('.ttra-color-hex').text(color.toUpperCase());
                }
            });

            // Botón reset colores
            $(document).on('click', '#ttra-reset-colors', function (e) {
                e.preventDefault();
                if (!confirm('¿Restaurar los colores por defecto?')) return;

                const defaults = {
                    'color_primario'   : '#003B6F',
                    'color_secundario' : '#00A0E3',
                    'color_acento'     : '#F47920',
                    'color_fondo'      : '#E8F4FD',
                };

                Object.entries(defaults).forEach(([key, val]) => {
                    $(`[name="ttra_settings[${key}]"]`).val(val).trigger('change');
                });
            });
        },

        /* ──────────────────────────────────────
           8. CAMBIO DE ESTADO RÁPIDO EN RESERVAS
           ────────────────────────────────────── */
        estadoRapido() {
            $(document).on('change', '.ttra-estado-select', function () {
                const $sel      = $(this);
                const reservaId = $sel.data('id');
                const newEstado = $sel.val();
                const oldEstado = $sel.data('original');

                const msgs = {
                    cancelada  : '⚠️ ¿Confirmas CANCELAR esta reserva? Se enviará email al cliente.',
                    completada : '✅ ¿Marcar como COMPLETADA esta reserva?',
                    pagada     : '💳 ¿Marcar esta reserva como PAGADA manualmente?',
                };

                if (msgs[newEstado]) {
                    if (!confirm(msgs[newEstado])) {
                        $sel.val(oldEstado);
                        return;
                    }
                }

                $.ajax({
                    url    : ttra_admin.rest_url + 'admin/reservas/' + reservaId + '/estado',
                    method : 'PUT',
                    headers: { 'X-WP-Nonce': ttra_admin.rest_nonce },
                    contentType: 'application/json',
                    data   : JSON.stringify({ estado: newEstado }),
                    success(res) {
                        $sel.data('original', newEstado);

                        // Actualizar badge visual
                        const $badge = $sel.closest('tr').find('.ttra-badge');
                        const colors = {
                            pendiente : 'warning',
                            confirmada: 'info',
                            pagada    : 'success',
                            cancelada : 'danger',
                            completada: 'muted',
                            no_show   : 'dark',
                        };

                        const labels = {
                            pendiente : 'Pendiente',
                            confirmada: 'Confirmada',
                            pagada    : 'Pagada',
                            cancelada : 'Cancelada',
                            completada: 'Completada',
                            no_show   : 'No Show',
                        };

                        $badge
                            .removeClass((i, cls) => cls.split(' ').filter(c => c.startsWith('ttra-badge--')).join(' '))
                            .addClass('ttra-badge--' + (colors[newEstado] || 'muted'))
                            .text(labels[newEstado] || newEstado);

                        TTRA_Admin.toast('Estado actualizado correctamente', 'success');
                    },
                    error() {
                        $sel.val(oldEstado);
                        TTRA_Admin.toast('Error al cambiar el estado', 'error');
                    },
                });
            });
        },

        /* ──────────────────────────────────────
           9. COPIAR AL PORTAPAPELES
           ────────────────────────────────────── */
        copyToClipboard() {
            $(document).on('click', '.ttra-copy-btn, [data-copy]', function (e) {
                e.preventDefault();
                const text = $(this).data('copy') || $(this).siblings('input, code').val() || $(this).siblings('input, code').text();
                if (!text) return;

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        TTRA_Admin.toast('¡Copiado al portapapeles!', 'success');
                    });
                } else {
                    // Fallback
                    const $tmp = $('<input>').val(text).appendTo('body').select();
                    document.execCommand('copy');
                    $tmp.remove();
                    TTRA_Admin.toast('¡Copiado!', 'success');
                }
            });
        },

        /* ──────────────────────────────────────
           10. HORARIOS — FILAS DINÁMICAS
           ────────────────────────────────────── */
        horarioRows() {
            // Añadir fila
            $(document).on('click', '#ttra-add-horario', function (e) {
                e.preventDefault();
                const $template = $('#ttra-horario-template');
                if (!$template.length) return;

                const idx  = Date.now();
                const html = $template.html().replace(/\[0\]/g, `[${idx}]`).replace(/_0_/g, `_${idx}_`);
                const $row = $(html);

                $('#ttra-horarios-body').append($row);
                $row.hide().slideDown(200);

                TTRA_Admin.updateSlotPreviews();
            });

            // Eliminar fila
            $(document).on('click', '.ttra-remove-horario', function (e) {
                e.preventDefault();
                $(this).closest('.ttra-horario-row').slideUp(200, function () {
                    $(this).remove();
                    TTRA_Admin.updateSlotPreviews();
                });
            });

            // Preview de slots al cambiar hora/intervalo
            $(document).on('change input', '.ttra-horario-row input, .ttra-horario-row select', function () {
                TTRA_Admin.updateSlotPreviews();
            });

            this.updateSlotPreviews();
        },

        updateSlotPreviews() {
            $('.ttra-horario-row').each(function () {
                const $row  = $(this);
                const start = $row.find('[name*="hora_inicio"]').val();
                const end   = $row.find('[name*="hora_fin"]').val();
                const int_m = parseInt($row.find('[name*="intervalo_minutos"]').val()) || 30;
                const $prev = $row.find('.ttra-slots-preview');

                if (!start || !end || !$prev.length) return;

                const slots = [];
                let [sh, sm] = start.split(':').map(Number);
                let [eh, em] = end.split(':').map(Number);

                let startMin = sh * 60 + sm;
                const endMin = eh * 60 + em;

                while (startMin + int_m <= endMin) {
                    const h = String(Math.floor(startMin / 60)).padStart(2, '0');
                    const m = String(startMin % 60).padStart(2, '0');
                    slots.push(`${h}:${m}`);
                    startMin += int_m;
                }

                if (slots.length) {
                    $prev.html(
                        `<span class="ttra-slots-count">${slots.length} slots:</span> ` +
                        slots.map(s => `<code>${s}</code>`).join(' ')
                    ).show();
                } else {
                    $prev.text('Sin slots (revisa horas)').show();
                }
            });
        },

        /* ──────────────────────────────────────
           11. FILTRO EN TABLA (búsqueda local)
           ────────────────────────────────────── */
        filterTable() {
            $(document).on('input', '#ttra-table-search', function () {
                const q = $(this).val().toLowerCase();
                const $rows = $($(this).data('target') || '.ttra-table tbody tr');

                $rows.each(function () {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(!q || text.includes(q));
                });
            });

            // Filtro por tipo (select)
            $(document).on('change', '#ttra-table-filter', function () {
                const val = $(this).val();
                const $rows = $('.ttra-table tbody tr');

                if (!val) {
                    $rows.show();
                    return;
                }

                $rows.each(function () {
                    const rowType = $(this).data('type') || $(this).find('.ttra-badge').text().toLowerCase();
                    $(this).toggle(rowType.includes(val));
                });
            });
        },

        /* ──────────────────────────────────────
           12. TABS DE AJUSTES
           ────────────────────────────────────── */
        tabsAjustes() {
            // Los tabs de ajustes se manejan via URL (?tab=), pero añadimos
            // animación y estado activo sin recargar cuando es posible
            const $tabs = $('.ttra-settings-tab');
            if (!$tabs.length) return;

            // Resaltar tab activo según URL
            const urlTab = new URLSearchParams(window.location.search).get('tab') || 'general';
            $tabs.each(function () {
                $(this).toggleClass('ttra-settings-tab--active', $(this).data('tab') === urlTab);
            });

            // Toggle visibilidad de clave secreta Redsys
            $(document).on('click', '#ttra-toggle-clave', function (e) {
                e.preventDefault();
                const $input = $('[name="ttra_settings[redsys_clave_secreta]"]');
                const isPass = $input.attr('type') === 'password';
                $input.attr('type', isPass ? 'text' : 'password');
                $(this).text(isPass ? '🙈 Ocultar' : '👁️ Mostrar');
            });
        },

        /* ──────────────────────────────────────
           13. GENERADOR DE CÓDIGO DE CUPÓN
           ────────────────────────────────────── */
        couponGenerator() {
            $(document).on('click', '#ttra-generate-coupon', function (e) {
                e.preventDefault();
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                let code = '';
                for (let i = 0; i < 8; i++) {
                    code += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                $('[name="codigo"]').val(code).trigger('input');
            });

            // Forzar mayúsculas en campo código
            $(document).on('input', '[name="codigo"]', function () {
                const pos = this.selectionStart;
                $(this).val($(this).val().toUpperCase());
                try { this.setSelectionRange(pos, pos); } catch(e) {}
            });

            // Radio visual tipo descuento
            $(document).on('change', '[name="tipo"]', function () {
                const val = $(this).val();
                $('.ttra-tipo-option').removeClass('ttra-tipo-option--active');
                $(this).closest('.ttra-tipo-option').addClass('ttra-tipo-option--active');
            });
        },

        /* ──────────────────────────────────────
           14. URL REDSYS AUTO-GENERADA
           ────────────────────────────────────── */
        redsysUrlGenerator() {
            const $urlField = $('#ttra-redsys-notification-url');
            if (!$urlField.length) return;

            // La URL ya viene del PHP, solo mostramos el botón copiar
            const url = $urlField.val() || $urlField.text();
            if (url) {
                $urlField.after(
                    `<button type="button" class="button ttra-copy-btn" data-copy="${url}" style="margin-left:8px">📋 Copiar URL</button>`
                );
            }
        },

        /* ──────────────────────────────────────
           15. VALIDACIÓN DE FORMULARIOS
           ────────────────────────────────────── */
        formValidation() {
            $(document).on('submit', '.ttra-form-validated', function (e) {
                const $form    = $(this);
                const $required = $form.find('[required]');
                let valid = true;

                $required.each(function () {
                    const val = $(this).val().trim();
                    if (!val) {
                        $(this).addClass('ttra-field-error');
                        valid = false;
                    } else {
                        $(this).removeClass('ttra-field-error');
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    TTRA_Admin.toast('Rellena todos los campos obligatorios (*).', 'error');
                    $form.find('.ttra-field-error').first().focus();
                }
            });

            // Eliminar error al escribir
            $(document).on('input change', '.ttra-field-error', function () {
                if ($(this).val().trim()) {
                    $(this).removeClass('ttra-field-error');
                }
            });
        },

        /* ──────────────────────────────────────
           UTILIDADES
           ────────────────────────────────────── */

        toast(msg, type = 'info') {
            const colors = {
                success : '#46b450',
                error   : '#dc3232',
                info    : '#0073aa',
                warning : '#ffb900',
            };

            const $toast = $(`
                <div style="
                    position:fixed; bottom:24px; right:24px; z-index:99999;
                    background:${colors[type] || colors.info}; color:#fff;
                    padding:12px 20px; border-radius:8px; font-size:14px;
                    box-shadow:0 4px 16px rgba(0,0,0,0.2); max-width:320px;
                    animation: ttra-slide-in 0.25s ease;
                ">${msg}</div>
            `);

            $('body').append($toast);
            setTimeout(() => $toast.fadeOut(400, () => $toast.remove()), 3500);
        },
    };

    // Exponer para uso externo si es necesario
    window.TTRA_Admin = TTRA_Admin;

})(jQuery);
