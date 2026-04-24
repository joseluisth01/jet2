# TicTac Reservas Agua — Estructura del Plugin

## Resumen

Plugin de WordPress para reservas de actividades acuáticas con proceso de 4 pasos, calendario de disponibilidad, pasarela de pago Redsys, notificaciones por correo y panel de administración completo.

**Shortcode:** `[ttra_reservas]`  
**Prefijo BD:** `wp_ttra_`  
**Prefijo clases:** `TTRA_`  
**REST API namespace:** `ttra/v1`

---

## Árbol de archivos (44 archivos)

```
tictac-reservas-agua/
│
├── tictac-reservas-agua.php          # Entry point, constantes, hooks activación
├── uninstall.php                     # Limpieza al desinstalar (borra tablas y opciones)
│
├── includes/                         # CORE: lógica de negocio
│   ├── class-ttra-autoloader.php     # Autoloader PSR-4 simplificado
│   ├── class-ttra-activator.php      # Activación: crea 9 tablas + opciones + página
│   ├── class-ttra-deactivator.php    # Desactivación: limpia cron jobs
│   ├── class-ttra-plugin.php         # Orquestador principal (boot)
│   │
│   ├── class-ttra-db.php             # Wrapper sobre $wpdb (insert, update, delete, query)
│   ├── class-ttra-helpers.php        # Utilidades: formato precio, fechas, sanitización
│   ├── class-ttra-settings.php       # Gestión centralizada de opciones
│   │
│   ├── class-ttra-categoria.php      # Modelo: Categorías de actividades
│   ├── class-ttra-actividad.php      # Modelo: Actividades/servicios
│   ├── class-ttra-horario.php        # Modelo: Horarios + slots disponibles
│   ├── class-ttra-reserva.php        # Modelo: Reservas + líneas + estados + stats
│   ├── class-ttra-pago.php           # Modelo: Pagos/transacciones
│   ├── class-ttra-cupon.php          # Modelo: Cupones de descuento
│   │
│   ├── class-ttra-mailer.php         # Servicio: emails (confirmación, cancelación, recordatorio)
│   ├── class-ttra-redsys.php         # Servicio: pasarela Redsys (tarjeta, Bizum, GPay, Apple Pay)
│   ├── class-ttra-calendario.php     # Servicio: disponibilidad de calendario
│   ├── class-ttra-cron.php           # Servicio: tareas programadas (recordatorios, limpieza)
│   ├── class-ttra-pdf-generator.php  # Servicio: generación de PDF de confirmación
│   ├── class-ttra-export.php         # Servicio: exportar reservas a CSV
│   └── class-ttra-rest-api.php       # REST API: 12 endpoints (públicos + admin)
│
├── admin/                            # PANEL DE ADMINISTRACIÓN
│   ├── class-ttra-admin.php          # Menús, assets, handle CRUD
│   ├── css/admin.css                 # Estilos del admin
│   ├── js/admin.js                   # JS del admin (media uploader, confirmaciones)
│   └── views/                        # Vistas PHP del admin
│       ├── dashboard.php             # Dashboard con estadísticas
│       ├── reservas.php              # Listado de reservas (filtros, paginación)
│       ├── reserva-detalle.php       # Detalle de una reserva
│       ├── categorias.php            # CRUD categorías
│       ├── actividades.php           # CRUD actividades
│       ├── horarios.php              # Configurar horarios por actividad
│       ├── bloqueos.php              # Gestionar fechas bloqueadas
│       ├── cupones.php               # CRUD cupones de descuento
│       ├── emails.php                # Log de emails enviados
│       └── ajustes.php               # Ajustes (General, Redsys, Emails, Apariencia, Pagos)
│
├── public/                           # FRONTEND (parte del cliente)
│   ├── class-ttra-public.php         # Shortcode + enqueue assets + payment return
│   ├── css/reservas.css              # Estilos completos del frontend (≈600 líneas)
│   ├── js/reservas.js                # SPA JS vanilla (≈400 líneas) - toda la lógica de pasos
│   └── views/
│       └── reservas-app.php          # HTML shell del proceso de 4 pasos
│
├── templates/                        # PLANTILLAS
│   ├── emails/
│   │   ├── confirmacion.php          # Email: reserva confirmada
│   │   ├── cancelacion.php           # Email: reserva cancelada
│   │   ├── recordatorio.php          # Email: recordatorio 24h antes
│   │   └── admin-nueva-reserva.php   # Email: notificación al admin
│   └── pdf/
│       └── confirmacion.php          # Plantilla PDF de confirmación
│
├── assets/                           # Recursos estáticos
│   ├── images/                       # Iconos, logos, imágenes
│   └── fonts/                        # Fuentes (si se incluyen localmente)
│
└── languages/                        # Traducciones (.pot, .po, .mo)
```

---

## Base de datos (9 tablas)

| Tabla | Descripción |
|-------|-------------|
| `wp_ttra_categorias` | Categorías (Motos de Agua, Act. Acuáticas, Alquiler Barcos...) |
| `wp_ttra_actividades` | Actividades con precio, duración, personas, tipo precio |
| `wp_ttra_horarios` | Franjas horarias por actividad y día de semana |
| `wp_ttra_bloqueos` | Fechas bloqueadas (por actividad o global) |
| `wp_ttra_reservas` | Reservas maestras con datos cliente y estado |
| `wp_ttra_reserva_lineas` | Líneas de detalle (actividad + fecha + hora + precio) |
| `wp_ttra_pagos` | Transacciones de pago (Redsys responses) |
| `wp_ttra_email_log` | Log de emails enviados |
| `wp_ttra_cupones` | Cupones de descuento |

---

## Flujo de reserva (4 pasos)

### Paso 1 — Elige Actividades
- Filtro por categorías (Todo, Motos de Agua, Act. Acuáticas, Alquiler Barcos)
- Tarjetas con checkbox, selector personas/sesiones, precio dinámico
- Sidebar con resumen que se actualiza en tiempo real

### Paso 2 — Fecha y Hora
- Un calendario por cada actividad seleccionada
- Navegación por meses, días disponibles resaltados
- Selector de hora con plazas disponibles
- Botón "Selección finalizada" por actividad

### Paso 3 — Datos del Cliente
- Nombre y apellido, teléfono, email
- DNI/Pasaporte, fecha de nacimiento (3 selects)
- Dirección en España (opcional)
- Validación de campos obligatorios

### Paso 4 — Método de Pago
- Tarjeta de Crédito/Débito (Redsys)
- Bizum (Redsys)
- Google Pay (Redsys)
- Apple Pay (Redsys)
- Redirige al TPV de Redsys → callback → confirmación

---

## REST API Endpoints

### Públicos
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/ttra/v1/categorias` | Lista categorías activas |
| GET | `/ttra/v1/actividades` | Lista actividades (filtro por categoría) |
| GET | `/ttra/v1/actividades/{id}` | Detalle de actividad |
| GET | `/ttra/v1/calendario/{act_id}/{año}/{mes}` | Días disponibles del mes |
| GET | `/ttra/v1/slots/{act_id}/{fecha}` | Horarios disponibles del día |
| POST | `/ttra/v1/reservas` | Crear nueva reserva |
| GET | `/ttra/v1/reservas/{codigo}` | Consultar reserva pública |
| POST | `/ttra/v1/cupones/validar` | Validar cupón de descuento |
| POST | `/ttra/v1/pago/iniciar` | Iniciar pago (genera form Redsys) |
| POST | `/ttra/v1/redsys/notification` | Callback de Redsys |

### Admin (requiere manage_options)
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/ttra/v1/admin/reservas` | Listado con filtros y paginación |
| PUT | `/ttra/v1/admin/reservas/{id}/estado` | Cambiar estado |
| GET | `/ttra/v1/admin/stats` | Estadísticas (total, ingresos, etc.) |

---

## Panel de Administración (Menús)

1. **Dashboard** — Estadísticas rápidas (reservas, ingresos, pendientes)
2. **Reservas** — Listado con filtros + detalle + cambio de estado + exportar CSV
3. **Categorías** — CRUD de categorías de actividades
4. **Actividades** — CRUD de actividades (nombre, precio, duración, personas, fianza...)
5. **Horarios** — Configurar franjas horarias por actividad y día de semana
6. **Bloqueos** — Bloquear fechas específicas (festivos, mantenimiento...)
7. **Cupones** — CRUD de cupones (%, fijo, límite uso, fechas)
8. **Emails** — Log de emails enviados
9. **Ajustes** — Tabs: General, Reservas, Redsys, Emails, Apariencia, Métodos de pago

---

## Cron Jobs

| Tarea | Frecuencia | Descripción |
|-------|-----------|-------------|
| `ttra_cron_enviar_recordatorios` | Cada hora | Envía email 24h antes de la actividad |
| `ttra_cron_limpiar_pendientes` | 2x/día | Cancela reservas pendientes >30 min |
| `ttra_cron_marcar_completadas` | Diario | Marca como completadas las reservas pasadas |

---

## Estado actual

| Componente | Estado |
|-----------|--------|
| Estructura de archivos | ✅ Completa |
| Entry point + autoloader | ✅ Completo |
| Activador (tablas + opciones) | ✅ Completo |
| Modelos (Categoría, Actividad, Horario, Reserva, Pago, Cupón) | ✅ Completos |
| Servicios (Redsys, Mailer, Calendario, Cron, PDF, Export) | ✅ Completos |
| REST API (12 endpoints) | ✅ Completo |
| Admin class (menús + CRUD handlers) | ✅ Completo |
| Public class (shortcode + assets) | ✅ Completo |
| Frontend HTML (reservas-app.php) | ✅ Completo |
| Frontend CSS (reservas.css) | ✅ Completo |
| Frontend JS (reservas.js) | ✅ Completo |
| Admin views (dashboard, reservas, etc.) | 🟡 Placeholder (por implementar) |
| Email templates | 🟡 Estructura base (por completar contenido) |
| Admin CSS/JS | 🟡 Base (por completar) |

---

## Próximos pasos sugeridos

1. **Implementar vistas admin** — Dashboard, listado reservas, formularios CRUD
2. **Completar plantillas email** — HTML responsive con datos dinámicos
3. **Testing de Redsys** — Probar en entorno sandbox con datos de test
4. **Pulir CSS frontend** — Ajustar responsive y animaciones
5. **Añadir traducciones** — Generar .pot y traducir al inglés
