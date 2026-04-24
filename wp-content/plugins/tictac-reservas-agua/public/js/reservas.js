/**
 * TicTac Reservas Agua - Frontend Booking App
 * v1.3.0 — Flechas calendario SVG, selects estilizados, botón done con estadooo
 */

(function () {
    'use strict';

    const PAX_ICON_URL = ttra_config.uploads_url + '/2026/04/f59a5831c4e43fbcb8399379ef09067f4693fdb8.gif';

    // URL de la flecha del calendario (relativa al tema/uploads, sin dominio hardcodeado)
    // Se usa la misma imagen para ambas direcciones; la flecha derecha se voltea con CSS
    const ARROW_LEFT_URL = ttra_config.uploads_url.replace(/\/wp-content\/uploads.*/, '') + '/wp-content/uploads/2026/04/Vector-19.svg';

    function esPremium(act) {
        return parseInt(act.premium || 0) === 1;
    }

    const BARCOS_SLUGS = ['alquiler-barcos', 'alquiler-de-barcos', 'barcos', 'alquiler_barcos'];
    const BARCOS_NOMBRES = ['alquiler barcos', 'alquiler de barcos', 'barcos'];

    function esCategoriaBarcos(cat) {
        if (!cat) return false;
        const slug = (cat.slug || '').toLowerCase().replace(/_/g, '-').trim();
        const nombre = (cat.nombre || '').toLowerCase().trim();
        return BARCOS_SLUGS.includes(slug) || BARCOS_NOMBRES.some(n => nombre.includes(n));
    }

    function getExtrasIcons() {
        const base = ttra_config.uploads_url + '/2026/04/';
        return {
            capitan: base + 'system-regular-162-update-hover-update-1-2.svg',
            refrescos: base + 'system-regular-162-update-hover-update-1-3.svg',
            fruta: base + 'system-regular-162-update-hover-update-1-4.svg',
        };
    }

    function getExtrasPremium() {
        const i = getExtrasIcons();
        return [
            { icon: i.capitan, label: 'Capitán profesional' },
            { icon: i.refrescos, label: 'Refrescos' },
            { icon: i.fruta, label: 'Bandeja de fruta' },
        ];
    }

    function getExtrasBarcos() {
        const i = getExtrasIcons();
        return [
            { icon: i.capitan, label: 'Capitán profesional' },
        ];
    }

    /**
     * Formatea minutos en texto legible: "30 min" / "1 h" / "1 h 30 min"
     */
    function formatDuracion(min) {
        min = parseInt(min) || 0;
        if (min <= 0) return '-';
        if (min < 60) return min + ' min';
        const h = Math.floor(min / 60);
        const r = min % 60;
        return r === 0 ? h + ' h' : h + ' h ' + r + ' min';
    }

    /**
     * Envuelve un <select> en un .ttra-select-wrapper para el diseño pill.
     * Se llama después de insertar HTML en el DOM.
     */
    function wrapSelects(container) {
        container.querySelectorAll('select.ttra-select:not([data-wrapped])').forEach(sel => {
            // Evitar doble wrap
            if (sel.parentElement.classList.contains('ttra-select-wrapper')) return;
            sel.setAttribute('data-wrapped', '1');
            const wrapper = document.createElement('div');
            wrapper.className = 'ttra-select-wrapper';
            // Si es el select de slots, añadir clase adicional
            if (sel.classList.contains('ttra-select--slots')) {
                wrapper.classList.add('ttra-select-wrapper--slots');
            }
            sel.parentNode.insertBefore(wrapper, sel);
            wrapper.appendChild(sel);
        });
    }

    const App = {
        state: {
            currentStep: 1,
            categorias: [],
            actividades: [],
            selectedActivities: [],
            clientData: {},
            paymentMethod: '',
            total: 0,
            descuentoAuto: 0,
            totalConDescuento: 0,
            mensajesDescuento: [],
            activeCatFilter: 'all',
            categoriaMap: {},
        },

        init() {
    // Detectar retorno desde Redsys (URL_OK o URL_KO)
    const urlParams = new URLSearchParams(window.location.search);
    const ttraResult = urlParams.get('ttra_result');
    const codigoReserva = urlParams.get('code');

    if (ttraResult && codigoReserva) {
        this.handlePaymentReturn(ttraResult, codigoReserva);
        return; // No inicializar el flujo normal
    }

    this.loadCategorias();
    this.loadActividades();
    this.bindEvents();
    this.renderTrustBadges();
    this.updateSummary();
    this.fixSidebarSticky();
},

        fixSidebarSticky() {
            const sidebar = document.getElementById('ttra-summary');
            if (!sidebar) return;
            function updateTop() {
                const stepper = document.querySelector('.ttra-stepper');
                if (!stepper) return;
                sidebar.style.top = (stepper.offsetHeight + 105 + 16) + 'px';
            }
            updateTop();
            window.addEventListener('resize', updateTop);
            window.addEventListener('scroll', updateTop, { passive: true });
        },

        async api(endpoint, options = {}) {
            const response = await fetch(ttra_config.rest_url + endpoint, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ttra_config.nonce,
                },
                ...options,
            });
            return response.json();
        },

        async loadCategorias() {
            this.state.categorias = await this.api('categorias');
            this.state.categorias.forEach(cat => {
                this.state.categoriaMap[String(cat.id)] = cat;
            });
            this.renderCategoryFilters();
        },

        async loadActividades() {
            const all = await this.api('actividades');
            this.state.actividades = all;
            this.renderActivities(null);
        },

        async loadCalendar(actividadId, year, month) {
            return this.api(`calendario/${actividadId}/${year}/${month}`);
        },

        async loadSlots(actividadId, fecha) {
            return this.api(`slots/${actividadId}/${fecha}`);
        },

        getExtras(act) {
            if (esPremium(act)) return getExtrasPremium();
            const cat = this.state.categoriaMap[String(act.categoria_id)];
            if (esCategoriaBarcos(cat)) return getExtrasBarcos();
            return null;
        },

        sortAndFilter(actividades, categoriaId) {
            let lista = categoriaId
                ? actividades.filter(a => String(a.categoria_id) === String(categoriaId))
                : actividades;

            const catOrder = {};
            this.state.categorias.forEach((cat, idx) => { catOrder[String(cat.id)] = idx; });

            return lista.slice().sort((a, b) => {
                const pa = esPremium(a) ? 1 : 0;
                const pb = esPremium(b) ? 1 : 0;
                if (pa !== pb) return pa - pb;
                const oa = catOrder[String(a.categoria_id)] ?? 99;
                const ob = catOrder[String(b.categoria_id)] ?? 99;
                if (oa !== ob) return oa - ob;
                return parseInt(a.duracion_minutos) - parseInt(b.duracion_minutos);
            });
        },

        bindEvents() {
            document.querySelectorAll('.ttra-btn--next').forEach(btn =>
                btn.addEventListener('click', () => this.goToStep(parseInt(btn.dataset.next)))
            );
            document.querySelectorAll('.ttra-btn--prev').forEach(btn =>
                btn.addEventListener('click', () => this.goToStep(parseInt(btn.dataset.prev)))
            );
            document.getElementById('ttra-btn-finalizar')?.addEventListener('click', () => this.submitReservation());
            document.getElementById('ttra-sidebar-cta')?.addEventListener('click', () =>
                this.goToStep(this.state.currentStep + 1)
            );
        },

        goToStep(step) {
            if (step < 1 || step > 4) return;
            if (step > this.state.currentStep && !this.validateStep(this.state.currentStep)) return;

            document.getElementById(`ttra-step-${this.state.currentStep}`)?.classList.add('ttra-step--hidden');
            document.getElementById(`ttra-step-${step}`)?.classList.remove('ttra-step--hidden');

            document.querySelectorAll('.ttra-stepper__step').forEach(el => {
                const s = parseInt(el.dataset.step);
                el.classList.toggle('ttra-stepper__step--active', s === step);
                el.classList.toggle('ttra-stepper__step--completed', s < step);
            });

            this.state.currentStep = step;
            if (step === 2) this.initCalendars();
            if (step === 4) this.renderPaymentMethods();
            this.updateSummary();
            this.updateSidebarCTA();
            document.getElementById('ttra-reservas-app')?.scrollIntoView({ behavior: 'smooth' });
        },

        validateStep(step) {
            if (step === 1) {
                if (!this.state.selectedActivities.length) { alert('Selecciona al menos una actividad.'); return false; }
                return true;
            }
            if (step === 2) {
                if (this.state.selectedActivities.find(a => !a.fecha || !a.hora)) {
                    alert('Selecciona fecha y hora para todas las actividades.'); return false;
                }
                return true;
            }
            if (step === 3) return this.validateForm();
            return true;
        },

        validateForm() {
            const form = document.getElementById('ttra-form-datos');
            let valid = true;
            form.querySelectorAll('[required]').forEach(input => {
                if (!input.value.trim()) { input.classList.add('ttra-input--error'); valid = false; }
                else input.classList.remove('ttra-input--error');
            });
            if (!valid) alert('Rellena todos los campos obligatorios.');
            return valid;
        },

        // ── Renders ────────────────────────────────────────────────────────
        renderCategoryFilters() {
            const container = document.getElementById('ttra-categories-filter');
            if (!container) return;
            let html = `<button class="ttra-cat-btn ttra-cat-btn--active" data-cat="all">${ttra_config.i18n.todo}</button>`;
            this.state.categorias.forEach(cat => {
                html += `<button class="ttra-cat-btn" data-cat="${cat.id}">${cat.nombre}</button>`;
            });
            container.innerHTML = html;
            container.querySelectorAll('.ttra-cat-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    container.querySelectorAll('.ttra-cat-btn').forEach(b => b.classList.remove('ttra-cat-btn--active'));
                    btn.classList.add('ttra-cat-btn--active');
                    const catId = btn.dataset.cat === 'all' ? null : btn.dataset.cat;
                    this.renderActivities(catId);
                    this.state.activeCatFilter = catId || 'all';
                });
            });
        },

        renderActivities(categoriaId = null) {
            const container = document.getElementById('ttra-activities-list');
            if (!container) return;
            if (!this.state.actividades.length) {
                container.innerHTML = '<p class="ttra-empty">No hay actividades disponibles.</p>';
                return;
            }
            const sorted = this.sortAndFilter(this.state.actividades, categoriaId);
            const normales = sorted.filter(a => !esPremium(a));
            const premium = sorted.filter(a => esPremium(a));

            let html = '';
            normales.forEach(act => { html += this.renderCard(act); });
            if (premium.length) {
                html += `<div class="ttra-premium-divider"><span class="ttra-premium-divider__label">PREMIUM</span></div>`;
                premium.forEach(act => { html += this.renderCard(act); });
            }
            container.innerHTML = html;
            // Envolver selects con el wrapper
            wrapSelects(container);
            this.bindActivityEvents();
        },

        renderCard(act) {
            const premium = esPremium(act);
            const selected = this.state.selectedActivities.find(s => s.actividad_id == act.id);
            const personas = selected ? selected.personas : (parseInt(act.min_personas) || 1);
            const sesiones = selected ? selected.sesiones : 1;
            const isSelected = !!selected;

            const durMin = parseInt(act.duracion_minutos);
            const durLabel = durMin >= 60
                ? (durMin % 60 === 0 ? `${Math.floor(durMin / 60)} h.` : `${Math.floor(durMin / 60)} h. ${durMin % 60} min`)
                : `${durMin} minutos`;

            const paxHtml = act.precio_tipo === 'por_persona'
                ? `<span class="ttra-activity-card__pax"><img src="${PAX_ICON_URL}" alt="" class="ttra-pax-icon"><span>${parseFloat(act.precio_base).toFixed(0)}€/pax</span></span>`
                : '';

            const precioDisplay = this.calcPrice(act, personas, sesiones);
            const cardClass = premium ? 'ttra-activity-card ttra-activity-card--premium' : 'ttra-activity-card';

            const extras = this.getExtras(act);
            const includePanel = (isSelected && extras) ? this.renderIncludePanel(extras, premium) : '';

            return `
            <div class="${cardClass}" data-id="${act.id}">
                <div class="ttra-activity-card_left">
                    <div class="ttra-activity-card__check">
                        <input type="checkbox" ${isSelected ? 'checked' : ''} data-id="${act.id}">
                    </div>
                    <div class="ttra-activity-card__info">
                        <strong>${act.nombre}</strong>
                        <span class="ttra-activity-card__subtipo">${act.subtipo || ''}</span>
                    </div>
                </div>
                <div class="ttra-activity-card_right">
                    <div class="ttra-activity-card__duration">
                        <img style="width:25px" src="${ttra_config.uploads_url}/2026/04/482a1009433466516284834f81ab0dee0c0aa4ff.gif" alt="">
                        ${durLabel}${paxHtml}
                    </div>
                    <div class="ttra-activity-card__config">
                        <label>${ttra_config.i18n.personas}
                            <div class="ttra-select-wrapper">
                                <select class="ttra-select ttra-select--sm" data-field="personas" data-id="${act.id}" data-wrapped="1">
                                    ${this.generateOptions(parseInt(act.min_personas) || 1, parseInt(act.max_personas) || 10, personas)}
                                </select>
                            </div>
                        </label>
                        <label>${ttra_config.i18n.sesiones}
                            <div class="ttra-select-wrapper">
                                <select class="ttra-select ttra-select--sm" data-field="sesiones" data-id="${act.id}" data-wrapped="1">
                                    ${this.generateOptions(1, parseInt(act.max_sesiones) || 5, sesiones)}
                                </select>
                            </div>
                        </label>
                    </div>
                    <div class="ttra-activity-card__price">
                        <span class="ttra-price" data-id="${act.id}">${precioDisplay} ${ttra_config.currency_symbol}</span>
                    </div>
                    <div class="ttra-activity-card__icon">
                        <img style="width:45px" src="${ttra_config.uploads_url}/2026/04/2bd28d06b9f597517eaac0dafb1be33829c3798c.gif" alt="">
                    </div>
                </div>
            </div>
            ${includePanel}`;
        },

        renderIncludePanel(extras, isPremium = false) {
            const items = extras.map(e =>
                `<span class="ttra-include-item">
                    <img class="ttra-include-icon" src="${e.icon}" alt="${e.label}">
                    <span class="ttra-include-label">${e.label}</span>
                 </span>`
            ).join('');
            const colorClass = isPremium ? 'ttra-include-panel--premium' : 'ttra-include-panel--barcos';
            return `<div class="ttra-include-panel ${colorClass}"><span class="ttra-include-title">Incluye:</span>${items}</div>`;
        },

        renderTrustBadges() {
            const container = document.getElementById('ttra-trust-badges');
            if (!container) return;
            const l = ttra_config.labels;
            const u = ttra_config.uploads_url + '/2026/04/Icon-3.svg';
            container.innerHTML = [
                l.cancelacion_gratuita, l.no_fianza, l.pago_seguro, l.equipo_seguridad,
            ].map(label =>
                `<div class="ttra-badge ttra-badge--trust"><img src="${u}" alt=""> ${label}</div>`
            ).join('');
        },

        renderPaymentMethods() {
            const container = document.getElementById('ttra-payment-methods');
            if (!container) return;
            const u = ttra_config.uploads_url + '/2026/04/';
            const labels = {
                tarjeta: { name: 'Tarjeta de Crédito/Débito', sub: 'Visa, Mastercard', icon: `<img src="${u}Icons-Cards.svg"   style="height:28px;object-fit:contain" alt="">` },
                bizum: { name: 'Bizum', sub: '', icon: `<img src="${u}Icons-Cards-1.svg" style="height:28px;object-fit:contain" alt="">` },
                google_pay: { name: 'Google Pay', sub: '', icon: `<img src="${u}Icons-Cards-2.svg" style="height:28px;object-fit:contain" alt="">` },
                apple_pay: { name: 'Apple Pay', sub: '', icon: `<img src="${u}Icons-Cards-3.svg" style="height:28px;object-fit:contain" alt="">` },
            };
            container.innerHTML = ttra_config.metodos_pago.map(m => {
                const info = labels[m] || { name: m, sub: '', icon: '💰' };
                return `
                <div class="ttra-payment-option" data-method="${m}">
                    <input type="radio" name="metodo_pago" value="${m}">
                    <div class="ttra-payment-option__info">
                        <strong>${info.name}</strong>
                        ${info.sub ? `<span>${info.sub}</span>` : ''}
                    </div>
                    <div class="ttra-payment-option__icon">${info.icon}</div>
                </div>`;
            }).join('');

            container.querySelectorAll('.ttra-payment-option').forEach(opt => {
                opt.addEventListener('click', () => {
                    container.querySelectorAll('.ttra-payment-option').forEach(o => o.classList.remove('ttra-payment-option--selected'));
                    opt.classList.add('ttra-payment-option--selected');
                    opt.querySelector('input').checked = true;
                    this.state.paymentMethod = opt.dataset.method;
                });
            });
        },

        // ── Calendarios ────────────────────────────────────────────────────
        initCalendars() {
            const container = document.getElementById('ttra-calendars-grid');
            if (!container) return;
            container.innerHTML = this.state.selectedActivities.map((sel, idx) => {
                const act = this.state.actividades.find(a => a.id == sel.actividad_id);
                if (!act) return '';
                return `
                <div class="ttra-calendar-block" data-idx="${idx}" data-actividad="${sel.actividad_id}">
                    <p class="ttra-calendar-block__label">
                        Selecciona fecha y hora para la actividad:<br>
                        <strong>${act.nombre}</strong> <em>${act.subtipo || ''}</em>
                    </p><br>
                    <div class="ttra-calendar" id="ttra-cal-${idx}"></div>
                    <div class="ttra-slots-section">
                        <label class="ttra-slots-label">${ttra_config.i18n.horarios_disponibles}</label>
                        <div class="ttra-select-wrapper ttra-select-wrapper--slots">
                            <select class="ttra-select ttra-select--slots" id="ttra-slots-${idx}" data-wrapped="1">
                                <option value="">Selecciona hora</option>
                            </select>
                        </div>
                    </div>
                    <div style="text-align:center">
                        <button class="ttra-btn ttra-btn--done" data-idx="${idx}" disabled>
                            ${ttra_config.i18n.seleccion_finalizada}
                        </button>
                    </div>
                </div>`;
            }).join('');
            this.state.selectedActivities.forEach((sel, idx) =>
                this.buildCalendar(idx, sel.actividad_id, new Date().getFullYear(), new Date().getMonth() + 1)
            );
        },

        async buildCalendar(idx, actividadId, year, month) {
            const cal = document.getElementById(`ttra-cal-${idx}`);
            if (!cal) return;
            const dias = await this.loadCalendar(actividadId, year, month);
            const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            const firstDay = new Date(year, month - 1, 1).getDay();
            const offset = firstDay === 0 ? 6 : firstDay - 1;
            const total = new Date(year, month, 0).getDate();

            const siteBase = ttra_config.uploads_url.replace(/\/wp-content\/uploads.*$/, '');
            const arrowUrl = siteBase + '/wp-content/uploads/2026/04/Vector-19.svg';

            let html = `
            <div class="ttra-cal-header">
                <button class="ttra-cal-nav ttra-cal-nav--left" data-dir="-1" data-idx="${idx}" data-act="${actividadId}" aria-label="Mes anterior">
                    <img src="${arrowUrl}" alt="←">
                </button>
                <span class="ttra-cal-month">${months[month - 1]} ${year}</span>
                <button class="ttra-cal-nav ttra-cal-nav--right" data-dir="1" data-idx="${idx}" data-act="${actividadId}" aria-label="Mes siguiente">
                    <img src="${arrowUrl}" alt="→">
                </button>
            </div>
            <div class="ttra-cal-grid">
                ${'<div class="ttra-cal-day-header">L</div><div class="ttra-cal-day-header">M</div><div class="ttra-cal-day-header">X</div><div class="ttra-cal-day-header">J</div><div class="ttra-cal-day-header">V</div><div class="ttra-cal-day-header">S</div><div class="ttra-cal-day-header">D</div>'}
                ${'<div class="ttra-cal-day ttra-cal-day--empty"></div>'.repeat(offset)}`;

            for (let d = 1; d <= total; d++) {
                const fecha = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const info = dias[fecha];
                const avail = info && info.disponible;
                const sel = this.state.selectedActivities[idx]?.fecha === fecha;
                html += `<div class="ttra-cal-day ${avail ? 'ttra-cal-day--available' : 'ttra-cal-day--disabled'} ${sel ? 'ttra-cal-day--selected' : ''}"
                              data-fecha="${fecha}" data-idx="${idx}" data-act="${actividadId}">${d}</div>`;
            }
            html += '</div>';
            cal.innerHTML = html;

            cal.querySelectorAll('.ttra-cal-day--available').forEach(day =>
                day.addEventListener('click', () => this.selectDate(idx, actividadId, day.dataset.fecha))
            );
            cal.querySelectorAll('.ttra-cal-nav').forEach(btn =>
                btn.addEventListener('click', () => {
                    let nm = month + parseInt(btn.dataset.dir), ny = year;
                    if (nm < 1) { nm = 12; ny--; }
                    if (nm > 12) { nm = 1; ny++; }
                    this.buildCalendar(idx, actividadId, ny, nm);
                })
            );
        },

        async selectDate(idx, actividadId, fecha) {
            const cal = document.getElementById(`ttra-cal-${idx}`);
            cal.querySelectorAll('.ttra-cal-day--selected').forEach(d => d.classList.remove('ttra-cal-day--selected'));
            cal.querySelector(`[data-fecha="${fecha}"]`)?.classList.add('ttra-cal-day--selected');

            this.state.selectedActivities[idx].hora = '';
            this.updateDoneButton(idx, false);

            const slots = await this.loadSlots(actividadId, fecha);
            const select = document.getElementById(`ttra-slots-${idx}`);
            if (select) {
                select.innerHTML = '<option value="">Selecciona hora</option>' +
                    slots.map(s => `<option value="${s.hora}">${s.hora} (${s.plazas_disponibles} plazas)</option>`).join('');

                select.onchange = () => {
                    const horaSeleccionada = select.value;
                    this.state.selectedActivities[idx].fecha = fecha;
                    this.state.selectedActivities[idx].hora = horaSeleccionada;
                    this.updateDoneButton(idx, !!horaSeleccionada);
                    this.updateSummary();
                    this.checkStep2Complete();
                };
            }
            this.state.selectedActivities[idx].fecha = fecha;
            this.updateSummary();
        },

        updateDoneButton(idx, active) {
            const block = document.querySelector(`.ttra-calendar-block[data-idx="${idx}"]`);
            if (!block) return;
            const btn = block.querySelector('.ttra-btn--done');
            if (!btn) return;
            if (active) {
                btn.disabled = false;
                btn.classList.add('ttra-btn--done-active');
            } else {
                btn.disabled = true;
                btn.classList.remove('ttra-btn--done-active');
            }
        },

        // ── Actividades events ─────────────────────────────────────────────
        bindActivityEvents() {
            document.querySelectorAll('.ttra-activity-card input[type="checkbox"]').forEach(cb =>
                cb.addEventListener('change', () => this.toggleActivity(cb))
            );
            document.querySelectorAll('.ttra-activity-card select').forEach(sel =>
                sel.addEventListener('change', () => this.updateActivityConfig(sel))
            );
        },

        toggleActivity(checkbox) {
            const id = parseInt(checkbox.dataset.id);
            const card = checkbox.closest('.ttra-activity-card');
            const act = this.state.actividades.find(a => a.id == id);

            if (checkbox.checked) {
                const personas = parseInt(card.querySelector('[data-field="personas"]').value) || 1;
                const sesiones = parseInt(card.querySelector('[data-field="sesiones"]').value) || 1;
                this.state.selectedActivities.push({
                    actividad_id: id, personas, sesiones,
                    fecha: '', hora: '',
                    precio: this.calcPrice(act, personas, sesiones),
                });
                card.classList.add('ttra-activity-card--selected');

                const extras = this.getExtras(act);
                if (extras && !card.nextElementSibling?.classList.contains('ttra-include-panel')) {
                    card.insertAdjacentHTML('afterend', this.renderIncludePanel(extras, esPremium(act)));
                }
            } else {
                this.state.selectedActivities = this.state.selectedActivities.filter(a => a.actividad_id !== id);
                card.classList.remove('ttra-activity-card--selected');
                if (card.nextElementSibling?.classList.contains('ttra-include-panel')) {
                    card.nextElementSibling.remove();
                }
            }

            this.updateTotal();
            this.updateSummary();
            this.updateNextButton();
        },

        updateActivityConfig(select) {
            const id = parseInt(select.dataset.id);
            const act = this.state.actividades.find(a => a.id == id);
            const sel = this.state.selectedActivities.find(s => s.actividad_id == id);
            if (!sel) return;
            sel[select.dataset.field] = parseInt(select.value);
            const card = select.closest('.ttra-activity-card');
            const personas = parseInt(card.querySelector('[data-field="personas"]').value);
            const sesiones = parseInt(card.querySelector('[data-field="sesiones"]').value);
            sel.precio = this.calcPrice(act, personas, sesiones);
            const priceEl = card.querySelector('.ttra-price');
            if (priceEl) priceEl.textContent = `${sel.precio} ${ttra_config.currency_symbol}`;
            this.updateTotal();
            this.updateSummary();
        },

        // ── Utilidades ─────────────────────────────────────────────────────
        calcPrice(act, personas, sesiones) {
            if (act.precio_tipo === 'por_persona') return Math.round(parseFloat(act.precio_base) * personas * sesiones);
            let base = parseFloat(act.precio_base) * sesiones;
            if (act.precio_pax && parseFloat(act.precio_pax) > 0) base += parseFloat(act.precio_pax) * personas * sesiones;
            return Math.round(base);
        },

        generateOptions(min, max, selected) {
            let html = '';
            for (let i = min; i <= max; i++) html += `<option value="${i}" ${i == selected ? 'selected' : ''}>${i}</option>`;
            return html;
        },

        updateTotal() {
            this.state.total = this.state.selectedActivities.reduce((s, a) => s + parseFloat(a.precio || 0), 0);
            this.state.totalConDescuento = this.state.total;
            this.state.descuentoAuto = 0;
            this.state.mensajesDescuento = [];

            // Si no hay actividades seleccionadas, pintar directamente
            if (this.state.total <= 0) {
                this.updateSummary();
                return;
            }

            // Consultar descuento al servidor y luego pintar
            clearTimeout(this._descuentoTimer);
            this._descuentoTimer = setTimeout(() => {
                fetch(ttra_config.rest_url + 'descuentos/preview', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': ttra_config.nonce,
                    },
                    body: JSON.stringify({ subtotal: this.state.total }),
                })
                .then(function(r) { return r.json(); })
                .then((res) => {
                    this.state.descuentoAuto       = res.descuento || 0;
                    this.state.totalConDescuento   = res.total     || this.state.total;
                    this.state.mensajesDescuento   = res.mensajes  || [];
                    this.updateSummary();
                })
                .catch(() => {
                    this.updateSummary();
                });
            }, 250);
        },

        updateSummary() {
            const container = document.getElementById('ttra-summary-items');
            const totalEl = document.getElementById('ttra-summary-total');
            if (!container) return;
            container.innerHTML = this.state.selectedActivities.map((sel, idx) => {
                const act = this.state.actividades.find(a => a.id == sel.actividad_id);
                if (!act) return '';
                return `
                <div class="ttra-summary__item">
                    <div class="ttra-summary__item-header">
                        <span>Actividad ${String(idx + 1).padStart(2, '0')}</span>
                        <span>${sel.precio} ${ttra_config.currency_symbol}</span>
                    </div>
                    <div class="ttra-summary__item-detail"><span>${act.nombre} ${act.subtipo || ''}</span></div>
                    <div class="ttra-summary__item-detail"><span>Duración</span><span>${formatDuracion(act.duracion_minutos)}</span></div>
                    <div class="ttra-summary__item-detail"><span>${ttra_config.i18n.personas}</span><span>${sel.personas}</span></div>
                    <div class="ttra-summary__item-detail"><span>${ttra_config.i18n.sesiones}</span><span>${sel.sesiones}</span></div>
                    <div class="ttra-summary__item-detail"><span>Fecha</span><span>${sel.fecha || '-'}</span></div>
                    <div class="ttra-summary__item-detail"><span>Hora</span><span>${sel.hora || '-'}</span></div>
                </div>`;
            }).join('');
            // Mostrar mensajes de descuento automático
            const descMsgContainer = document.getElementById('ttra-descuento-auto-msg');
            if (descMsgContainer) descMsgContainer.remove();

            if (this.state.descuentoAuto > 0) {
                const totalFinal = this.state.totalConDescuento;
                if (totalEl) {
                    totalEl.innerHTML =
                        '<span style="text-decoration:line-through;opacity:.5;font-size:.8em;display:block">' +
                        this.state.total + ' ' + ttra_config.currency_symbol + '</span>' +
                        '<span style="color:#22c55e">' + totalFinal + ' ' + ttra_config.currency_symbol + '</span>';
                }
                // Insertar mensaje de descuento encima del total
                const totalRow = totalEl ? totalEl.closest('.ttra-summary__total') : null;
                if (totalRow) {
                    const msgHtml = '<div id="ttra-descuento-auto-msg" style="' +
                        'background:#dcfce7;border-left:3px solid #22c55e;padding:8px 12px;' +
                        'border-radius:6px;margin-bottom:8px;font-size:12px;color:#166534;font-weight:600">' +
                        (this.state.mensajesDescuento.length
                            ? this.state.mensajesDescuento.join('<br>')
                            : '🎉 Descuento aplicado: -' + this.state.descuentoAuto + ' ' + ttra_config.currency_symbol
                        ) +
                        '</div>';
                    totalRow.insertAdjacentHTML('beforebegin', msgHtml);
                }
            } else {
                // Limpiar mensaje de descuento previo
            var prevMsg = document.getElementById('ttra-descuento-auto-msg');
            if (prevMsg) prevMsg.remove();

            if (this.state.descuentoAuto > 0) {
                // Precio tachado + precio final en verde
                if (totalEl) {
                    totalEl.innerHTML =
                        '<span style="text-decoration:line-through;opacity:.5;font-size:.85em;display:block;line-height:1.2">' +
                        this.state.total + ' ' + ttra_config.currency_symbol + '</span>' +
                        '<span style="color:#22c55e">' + this.state.totalConDescuento + ' ' + ttra_config.currency_symbol + '</span>';
                }
                // Mensaje encima del bloque total
                var totalRow = totalEl ? totalEl.closest('.ttra-summary__total') : null;
                if (totalRow) {
                    var texto = this.state.mensajesDescuento.length
                        ? this.state.mensajesDescuento.join('<br>')
                        : '🎉 Descuento aplicado: -' + this.state.descuentoAuto + ' ' + ttra_config.currency_symbol;
                    var msgDiv = document.createElement('div');
                    msgDiv.id = 'ttra-descuento-auto-msg';
                    msgDiv.style.cssText = 'background:#dcfce7;border-left:3px solid #22c55e;padding:8px 12px;border-radius:6px;margin-bottom:8px;font-size:12px;color:#166534;font-weight:600';
                    msgDiv.innerHTML = texto;
                    totalRow.parentNode.insertBefore(msgDiv, totalRow);
                }
            } else {
                if (totalEl) totalEl.textContent = this.state.total + ' ' + ttra_config.currency_symbol;
            }
            }
        },

        updateNextButton() {
            const btn = document.querySelector('#ttra-step-1 .ttra-btn--next');
            if (btn) btn.disabled = !this.state.selectedActivities.length;
            this.updateSidebarCTA();
        },

        updateSidebarCTA() {
            const btn = document.getElementById('ttra-sidebar-cta');
            if (!btn) return;
            const next = this.state.currentStep + 1;
            btn.textContent = next <= 4
                ? `${ttra_config.i18n.continuar} (PASO ${next}) →`
                : `${ttra_config.i18n.finalizar} →`;
            if (this.state.currentStep === 1) btn.disabled = !this.state.selectedActivities.length;
            else if (this.state.currentStep === 2) btn.disabled = !this.state.selectedActivities.every(a => a.fecha && a.hora);
            else btn.disabled = false;
        },

        async fetchDescuentoPreview() {
            if ( this.state.total <= 0 ) return;
            try {
                const res = await this.api('descuentos/preview', {
                    method: 'POST',
                    body: JSON.stringify({ subtotal: this.state.total }),
                });
                this.state.descuentoAuto = res.descuento || 0;
                this.state.totalConDescuento = res.total || this.state.total;
                this.state.mensajesDescuento = res.mensajes || [];
            } catch(e) {
                this.state.descuentoAuto = 0;
                this.state.totalConDescuento = this.state.total;
                this.state.mensajesDescuento = [];
            }
        },

        checkStep2Complete() {
            const done = this.state.selectedActivities.every(a => a.fecha && a.hora);
            const nextBtn = document.querySelector('#ttra-step-2 .ttra-btn--next');
            if (nextBtn) nextBtn.disabled = !done;
            if (this.state.currentStep === 2) {
                const sb = document.getElementById('ttra-sidebar-cta');
                if (sb) sb.disabled = !done;
            }
        },

        // ── Submit ──────────────────────────────────────────────────────────
        async submitReservation() {
            if (!this.state.paymentMethod) { alert('Selecciona un método de pago.'); return; }

            const btn = document.getElementById('ttra-btn-finalizar');
            if (btn) { btn.disabled = true; btn.textContent = '⏳ Procesando...'; }

            const form = document.getElementById('ttra-form-datos');
            const formData = {
                nombre: form.querySelector('[name="nombre"]').value,
                email: form.querySelector('[name="email"]').value,
                telefono: form.querySelector('[name="telefono"]').value,
                dni_pasaporte: form.querySelector('[name="dni_pasaporte"]').value,
                fecha_nacimiento: this.buildFechaNacimiento(form),
                direccion: form.querySelector('[name="direccion"]').value,
                actividades: this.state.selectedActivities,
            };

            try {
                const result = await this.api('reservas', { method: 'POST', body: JSON.stringify(formData) });
                if (!result.success) {
                    alert(result.message || 'Error al crear la reserva.');
                    if (btn) { btn.disabled = false; btn.textContent = ttra_config.i18n.finalizar; }
                    return;
                }

                // Mostrar mensajes de descuento automático si los hay
                if (result.mensajes_descuento && result.mensajes_descuento.length) {
                    result.mensajes_descuento.forEach(function(msg) {
                        const el = document.getElementById('ttra-summary-items');
                        if (el) {
                            el.insertAdjacentHTML('beforeend',
                                '<div style="background:#dcfce7;border-left:4px solid #22c55e;padding:8px 12px;border-radius:6px;margin-top:8px;font-size:13px;color:#166534">' +
                                msg + '</div>'
                            );
                        }
                    });
                }
                // Actualizar total mostrado en sidebar si hay descuento
                if (result.descuento > 0) {
                    const totalEl = document.getElementById('ttra-summary-total');
                    if (totalEl) {
                        totalEl.innerHTML =
                            '<span style="text-decoration:line-through;opacity:.55;font-size:.8em">' + result.subtotal + ' €</span> ' +
                            result.total + ' €';
                    }
                }
// Iniciar pago real con Redsys
const pagoData = await this.api('pago/iniciar', {
    method: 'POST',
    body: JSON.stringify({
        codigo_reserva: result.codigo_reserva,
        metodo_pago: this.state.paymentMethod,
    }),
});

// Debug: eliminar en producción
console.log('pagoData recibido:', pagoData);

// Detectar error de WordPress (WP_Error devuelve { code, message, data })
if (!pagoData || pagoData.code || !pagoData.Ds_MerchantParameters || !pagoData.Ds_Signature) {
    const errMsg = pagoData && pagoData.message
        ? pagoData.message
        : 'Error al generar el formulario de pago. Comprueba la configuración de Redsys.';
    alert(errMsg);
    if (btn) { btn.disabled = false; btn.textContent = ttra_config.i18n.finalizar; }
    return;
}

// Rellenar el formulario oculto y redirigir al TPV
var redsysForm = document.getElementById('ttra-redsys-form');
redsysForm.action = pagoData.url;
redsysForm.querySelector('[name="Ds_SignatureVersion"]').value   = 'HMAC_SHA256_V1';
redsysForm.querySelector('[name="Ds_MerchantParameters"]').value = pagoData.Ds_MerchantParameters;
redsysForm.querySelector('[name="Ds_Signature"]').value          = pagoData.Ds_Signature.replace(/\+/g, '%2B');

console.log('Enviando a Redsys:', redsysForm.action);
redsysForm.submit();
            } catch (error) {
                console.error(error);
                alert('Ha ocurrido un error. Por favor, inténtalo de nuevo.');
                if (btn) { btn.disabled = false; btn.textContent = ttra_config.i18n.finalizar; }
            }
        },

        showThankYou(codigo, nombre, email, total, subtotal, descuento) {
            document.getElementById('ttra-step-4')?.classList.add('ttra-step--hidden');
            document.querySelectorAll('.ttra-stepper__step').forEach(el => {
                el.classList.remove('ttra-stepper__step--active');
                el.classList.add('ttra-stepper__step--completed');
            });
            const confirmDiv = document.getElementById('ttra-step-confirm');
            const confirmBody = document.getElementById('ttra-confirmation');
            if (confirmDiv && confirmBody) {
                confirmBody.innerHTML = `
                <div class="ttra-thankyou">
                    <div class="ttra-thankyou__icon">✅</div>
                    <h2 class="ttra-thankyou__title">¡Reserva confirmada!</h2>
                    <p class="ttra-thankyou__subtitle">Gracias, <strong>${nombre}</strong>. Tu reserva ha sido procesada correctamente.</p>
                    <div class="ttra-thankyou__card">
                        <div class="ttra-thankyou__row"><span>Código de reserva</span><strong class="ttra-thankyou__code">${codigo}</strong></div>
                        <div class="ttra-thankyou__row"><span>Total pagado</span><strong>${
                            (descuento && descuento > 0)
                                ? '<span style="text-decoration:line-through;opacity:.5;font-size:.85em;margin-right:6px">' + subtotal + ' €</span><span style="color:#22c55e">' + total + ' €</span>'
                                : total + ' €'
                        }</strong></div>
                        <div class="ttra-thankyou__row"><span>Confirmación enviada a</span><strong>${email}</strong></div>
                    </div>
                    <p class="ttra-thankyou__note">📧 Hemos enviado un email con todos los detalles.<br>Si no lo recibes en unos minutos, revisa tu carpeta de spam.</p>
                    <div class="ttra-thankyou__actions">
                        <button class="ttra-btn ttra-btn--primary" onclick="window.location.reload()">Realizar otra reserva</button>
                    </div>
                </div>`;
                confirmDiv.classList.remove('ttra-step--hidden');
                confirmDiv.scrollIntoView({ behavior: 'smooth' });
            }
            const si = document.getElementById('ttra-summary-items');
            if (si) si.innerHTML = '';
        },

        async handlePaymentReturn(resultado, codigo) {
    // Ocultar todos los pasos y el sidebar
    document.querySelectorAll('.ttra-step').forEach(s => s.classList.add('ttra-step--hidden'));
    document.getElementById('ttra-summary')?.style.setProperty('display', 'none');
    document.querySelector('.ttra-stepper')?.style.setProperty('display', 'none');

    // Marcar stepper como completado
    document.querySelectorAll('.ttra-stepper__step').forEach(el => {
        el.classList.remove('ttra-stepper__step--active');
        el.classList.add('ttra-stepper__step--completed');
    });

    if (resultado === 'ko') {
        // Pago fallido o cancelado
        const confirmDiv = document.getElementById('ttra-step-confirm');
        const confirmBody = document.getElementById('ttra-confirmation');
        if (confirmDiv && confirmBody) {
            confirmBody.innerHTML = `
            <div class="ttra-thankyou">
                <div class="ttra-thankyou__icon">❌</div>
                <h2 class="ttra-thankyou__title">Pago no completado</h2>
                <p class="ttra-thankyou__subtitle">El pago no se ha podido procesar o fue cancelado.</p>
                <p class="ttra-thankyou__note">
                    Tu reserva <strong>${codigo}</strong> ha quedado pendiente.<br>
                    Si el problema persiste, contacta con nosotros.
                </p>
                <div class="ttra-thankyou__actions">
                    <button class="ttra-btn ttra-btn--primary" onclick="window.location.href=window.location.pathname">
                        Intentar de nuevo
                    </button>
                </div>
            </div>`;
            confirmDiv.classList.remove('ttra-step--hidden');
        }
        // Limpiar parámetros de la URL sin recargar
        window.history.replaceState({}, '', window.location.pathname);
        return;
    }

    // Resultado OK — cargar datos de la reserva
    const confirmDiv = document.getElementById('ttra-step-confirm');
    const confirmBody = document.getElementById('ttra-confirmation');
    if (!confirmDiv || !confirmBody) return;

    confirmBody.innerHTML = '<div class="ttra-loader">⏳ Cargando confirmación...</div>';
    confirmDiv.classList.remove('ttra-step--hidden');

    try {
        const reserva = await this.api('reservas/' + codigo);

        if (!reserva || !reserva.codigo) {
            throw new Error('Reserva no encontrada');
        }

        const descuento = parseFloat(reserva.total) < parseFloat(reserva.total) ? 0 : 0; // placeholder
        confirmBody.innerHTML = `
        <div class="ttra-thankyou">
            <div class="ttra-thankyou__icon">✅</div>
            <h2 class="ttra-thankyou__title">¡Reserva confirmada!</h2>
            <p class="ttra-thankyou__subtitle">
                Gracias, <strong>${reserva.nombre}</strong>.<br>
                Tu pago ha sido procesado correctamente.
            </p>
            <div class="ttra-thankyou__card">
                <div class="ttra-thankyou__row">
                    <span>Código de reserva</span>
                    <strong class="ttra-thankyou__code">${reserva.codigo}</strong>
                </div>
                <div class="ttra-thankyou__row">
                    <span>Total pagado</span>
                    <strong>${parseFloat(reserva.total).toFixed(2).replace('.', ',')} €</strong>
                </div>
                <div class="ttra-thankyou__row">
                    <span>Estado</span>
                    <strong style="color:#22c55e">✅ Pagada</strong>
                </div>
                ${reserva.lineas && reserva.lineas.length ? `
                <div class="ttra-thankyou__row" style="flex-direction:column;align-items:flex-start;gap:6px">
                    <span style="font-weight:700;color:#213975;margin-bottom:4px">Actividades</span>
                    ${reserva.lineas.map(l => `
                        <div style="font-size:13px;color:#333;padding:4px 0;border-bottom:1px solid #E8F4FD;width:100%">
                            <strong>${l.actividad_nombre || ''}</strong>
                            ${l.fecha ? ' · ' + l.fecha : ''}
                            ${l.hora ? ' · ' + l.hora.substring(0,5) + ' h' : ''}
                            ${l.personas ? ' · ' + l.personas + ' pax' : ''}
                        </div>
                    `).join('')}
                </div>` : ''}
            </div>
            <p class="ttra-thankyou__note">
                📧 Hemos enviado un email con todos los detalles a tu correo.<br>
                Si no lo recibes en unos minutos, revisa tu carpeta de spam.
            </p>
            <div class="ttra-thankyou__actions">
                <button class="ttra-btn ttra-btn--primary" onclick="window.location.href=window.location.pathname">
                    Realizar otra reserva
                </button>
            </div>
        </div>`;

    } catch(e) {
        confirmBody.innerHTML = `
        <div class="ttra-thankyou">
            <div class="ttra-thankyou__icon">✅</div>
            <h2 class="ttra-thankyou__title">¡Pago realizado!</h2>
            <p class="ttra-thankyou__subtitle">Tu reserva <strong>${codigo}</strong> ha sido procesada.</p>
            <p class="ttra-thankyou__note">
                📧 Recibirás un email de confirmación en breve.
            </p>
            <div class="ttra-thankyou__actions">
                <button class="ttra-btn ttra-btn--primary" onclick="window.location.href=window.location.pathname">
                    Realizar otra reserva
                </button>
            </div>
        </div>`;
    }

    // Limpiar parámetros de la URL sin recargar
    window.history.replaceState({}, '', window.location.pathname);
},

        buildFechaNacimiento(form) {
            const d = form.querySelector('[name="nacimiento_dia"]').value;
            const m = form.querySelector('[name="nacimiento_mes"]').value;
            const y = form.querySelector('[name="nacimiento_anyo"]').value;
            return (d && m && y) ? `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}` : '';
        },
    };

    document.addEventListener('DOMContentLoaded', () => App.init());

})();