# Arquitectura Modular Laravel y Flujo de Responsabilidades — TF CMS

**Documento:** `docs/arquitectura/arquitectura-laravel.md`
**Estado:** `DEFINIDO` (Arquitectura modular por dominios aprobada por Dirección Técnica)
**Versión:** 2.0 — Fase 0C.1B

---

## 1. Arquitectura Modular por Dominios (`DEFINIDO`)

Se establece formalmente la organización del backend de TravelFlow CMS bajo una **arquitectura modular pragmática orientada a dominios/capacidades dentro de `app/Domains/`**:

```text
app/
├── Domains/                    # Módulos de Dominio (Cohesión de Negocio)
│   ├── Tour/                   # Dominio Tours e Itinerarios
│   ├── Destination/            # Dominio Destinos
│   ├── Experience/             # Dominio Experiencias
│   ├── Content/                # Páginas estáticas y Blog
│   ├── Media/                  # Biblioteca de Medios
│   ├── Builder/                # Motor TF Builder
│   ├── Theme/                  # Motor de Temas
│   ├── Multilingual/           # Motor de Internacionalización
│   ├── Seo/                    # Metadatos y Sitemaps
│   ├── Setting/                # Configuración de Empresa y Sistema
│   ├── User/                   # Identidad, Usuarios, Roles y Permisos
│   └── Audit/                  # Trazabilidad y Logs Funcionales
│
├── Http/                       # Capa de Entrada y Transporte HTTP
│   ├── Controllers/
│   │   ├── Admin/              # Endpoints internos para la SPA Administrativa
│   │   └── Web/                # Controladores SSR para la Web Pública
│   ├── Middleware/             # Pipeline de Seguridad, Auth, CSRF, RBAC
│   ├── Requests/               # Form Requests tipados para validación de entrada
│   └── Resources/              # JsonResources de transformación
│
├── Providers/                  # Service Providers de Laravel
└── Support/                    # Helpers y DTOs transversales
```

### Principios de Implementación:
1. **Sin Paquetes Aislados Innecesarios:** Queda descartado el uso de paquetes externos como `nwidart/laravel-modules`. Los dominios son carpetas estructuradas que aprovechan el autoloading estándar PSR-4 de Composer (`App\Domains\...`).
2. **Creación Incremental:** No se crearán carpetas vacías por adelantado. Cada dominio se materializará físicamente en su microfase correspondiente.

---

## 2. Flujo de Responsabilidades (Eliminación de Reglas Arbitrarias)

Se elimina cualquier regla rígida basada en conteo de líneas (como "Controller < 40 líneas"). El principio rector es:

> **EL CONTROLADOR DEBE SER DELGADO POR RESPONSABILIDAD, NO POR UN NÚMERO ARBITRARIO DE LÍNEAS.**

```text
HTTP Request ──► Route ──► Middleware (Auth, CSRF, RateLimit, RBAC)
     ──► Form Request (Validación y sanitización tipada)
     ──► Controller (Orquestación HTTP pura)
     ──► Action / Service (Reglas de negocio, transacciones atómicas)
     ──► Model / Query (Acceso a datos y persistencia en MySQL)
     ──► Resource / Response (Transformación de salida)
     ──► HTTP Response
```

### Reglas de Existencia de Capas:
- **Sin Ceremony Innecesario:** Una capa (Action, Service, DTO, Repository, Query Object, Event o Job) existirá **únicamente cuando tenga una responsabilidad real demostrable**, no por cumplir un patrón visual.
- **Controller:** Orquesta la petición HTTP, invoca la operación de dominio correspondiente y retorna la respuesta. No contiene lógica de negocio pesada ni consultas SQL complejas.
- **Form Request:** Valida y sanitiza las entradas. Si los datos son inválidos, detiene el flujo antes de tocar el dominio.
- **Action / Service:** Acciones de responsabilidad única para operaciones de escritura con efectos colaterales; Servicios para coordinación entre múltiples dominios.
- **DTOs:** Utilizados cuando la complejidad o estructura del payload justifique tipado estricto inmutable (`readonly class`).

---

## 3. Política de Transacciones Atómicas

Se elimina la regla simplista de "más de una escritura = transacción". Se sustituye por el principio de ingeniería:

> **TODA OPERACIÓN QUE REQUIERA ATOMICIDAD ENTRE MODIFICACIONES RELACIONADAS DEBE EJECUTARSE DENTRO DE UNA TRANSACCIÓN.**

La necesidad de atomicidad para garantizar consistencia e integridad referencial en MySQL (ej. crear un Tour y sus Días de Itinerario correspondientes), y no el mero conteo de queries, es lo que determina el uso de `DB::transaction()`.

---

## 4. Convenciones de Eventos del Dominio

- Se utilizarán eventos y listeners de Laravel para reacciones relevantes que justifiquen desacoplamiento (ej. `TourPublished -> [Invalidar Cache, Actualizar Sitemap]`).
- **Prohibido:** Convertir operaciones CRUD rutinarias en un Event Bus ceremonial por defecto.

---

## 5. Scheduler de Laravel

- Se utilizará la abstracción estándar de **Laravel Scheduler** mediante una única llamada periódica de cron:
  ```bash
  * * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
  ```
- **Corrección de reglas globales:** Se eliminan las directivas globales indiscriminadas.
  - `withoutOverlapping()` se aplicará **solamente cuando el solapamiento de ejecuciones represente un riesgo real** de concurrencia o duplicación.
  - `runInBackground()` se utilizará **solamente cuando sea técnicamente aplicable** en el entorno y aporte una ventaja real demostrable.

---

## 6. Queues y Cache

- **Queues:** El driver `database` (sobre MySQL `jobs`) es el candidato inicial para compatibilidad con hosting convencional. No se asume la existencia obligatoria de un worker permanente en todos los entornos; la estrategia operacional final se adaptará al despliegue real.
- **Cache:** Arquitectura desacoplada del driver. TF CMS funcionará plenamente sobre almacenamiento en archivos (`file`). **Redis es una optimización opcional futura**, nunca una dependencia obligatoria.

---

## 7. Jerarquía de Configuración

1. **Variables de Entorno (`.env`):** Parámetros de infraestructura, credenciales locales y secrets (inmutables por el usuario final).
2. **Configuración Técnica (`config/*.php`):** Opciones estáticas del framework gestionadas por ingeniería.
3. **Ajustes de Producto (Tabla `settings`):** Configuración editable de negocio gestionada por Orlando desde el panel (Razón social, RUC, WhatsApp, moneda base, fallbacks SEO).
