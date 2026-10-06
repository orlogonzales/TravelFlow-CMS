# Stack Tecnológico y Taxonomía Oficial de Decisiones — TF CMS

**Documento:** `docs/arquitectura/stack.md`
**Estado:** `DEFINIDO Y VINCULANTE` (Consolidación Arquitectónica Fase 0C.1B con Adenda Bootstrap 5.3.8)
**Versión:** 4.0 — Fase 0C.1B

---

## 1. Stack Oficial Base

```text
Backend:                 Laravel 13.x
Runtime PHP:             PHP 8.3+ (Local verificado: PHP 8.3.30 ZTS)
Base de Datos:           MySQL 8.4+ (Local verificado: MySQL 8.4.3 LTS en tf_cms)
Frontend Administrativo: Vue 3 + TypeScript + Pinia + Vue Router + Vite
Framework CSS Admin:     Bootstrap 5.3.8 (Infraestructura CSS / Grid / Placeholders)
Capa Visual Oficial:     TF Design System (Tokens de diseño, coherencia UX y componentes propios)
Iconografía Oficial:     Font Awesome (Free)
Cliente HTTP Admin:      Fetch API nativo (composable useApi())
Servidor Web Local:      Apache 2.4.66 con VirtualHost dedicado (app.tf-cms.test -> /public)
```

---

## 2. Taxonomía de Decisiones Técnicas

### 2.1 DEFINIDO Y VINCULANTE
- **Framework Backend:** Laravel 13.x asume la infraestructura completa (routing, sessions, CSRF, queue, storage, validation, console, mail, migrations).
- **Motor de Base de Datos Oficial:** **MySQL** exclusivamente en todos los entornos (desarrollo `tf_cms`, testing `tf_cms_test`, staging, producción).
- **Versión Base CSS Admin:** **Bootstrap 5.3.8** como infraestructura CSS base del panel administrativo. Versión controlada, sin actualizaciones automáticas.
- **Capa Visual del Producto:** **TF Design System** sobre Bootstrap 5.3.8 (gobierno de colores, tipografía, espaciado, componentes y directrices UX).
- **Skeleton Loaders:** Componentes de carga estructural construidos sobre mecanismos de Bootstrap 5.3.8 (`placeholders`) encapsulados por el TF Design System.
- **Patrón Asíncrono de UI:** `FETCH + VUE REACTIVITY + BOOTSTRAP 5.3.8 + TF SKELETON` (estados `LOADING`, `SUCCESS`, `EMPTY`, `ERROR`).
- **Sistema de Iconos:** **Font Awesome (Free)** mediante componente `<TfIcon>`.
- **Arquitectura Backend Modular:** Dominios cohesivos organizados en `app/Domains/` con autoloading PSR-4 nativo de Composer.
- **Flujo de Responsabilidades:** Controladores delgados por responsabilidad, sin métricas arbitrarias de líneas de código. Acciones/Servicios solo cuando exista responsabilidad demostrable.
- **Transacciones Atómicas:** `DB::transaction()` aplicado ante necesidad de atomicidad entre modificaciones relacionadas en MySQL, no por mero conteo de queries.
- **Identidad Desacoplada:** `Persona ≠ Usuario ≠ Actor`. Relación conceptual flexible sin congelamiento físico 1:1 forzoso. Actores: `HUMAN_USER`, `SYSTEM`, `SERVICE`, `INTEGRATION / API_CLIENT`.
- **RBAC Propio Dinámico:** Relación N:M (`users ↔ roles ↔ permissions`) integrado nativamente con Gates y Policies de Laravel.
- **Menú ≠ Autorización:** Backend valida forzosamente permisos respondiendo `HTTP 403 Forbidden`. La interfaz solo oculta elementos por ergonomía UX.
- **Protocolo de Acceso Temporal para Orlando (Reglas 42 al 54):** Obligación de entregar credenciales temporales de verificación en cada fase que entregue módulos navegables.
- **Auditoría Funcional vs Logging Técnico:** Separación estricta entre Monolog (`storage/logs/laravel.log`) y tabla de auditoría en MySQL (`audit_logs`). Prohibición absoluta de almacenar contraseñas, tokens o secretos en auditoría.
- **Frontend Público:** PHP SSR + Theme Engine + Progressive Enhancement. Soberano e independiente de las hojas de estilo de administración (los temas públicos no están forzados a usar Bootstrap 5.3.8).
- **Cliente HTTP Preferido:** **Fetch API nativo** sobre Axios.
- **Gobernanza de Base de Datos:** `database/migrations/` es la única fuente ejecutable de migraciones. `DB/travelflow_cms.sql` es el volcado de referencia consolidada.
- **DocumentRoot:** Apuntando estrictamente a `D:\laragon\www\app.tf-cms\public`.
- **Separación Generacional:** TF CMS ≠ TF legacy. No se reutiliza código ni contratos legacy.

### 2.2 DESCARTADO
- **Tailwind CSS:** Totalmente descartado de la arquitectura oficial del Admin. Residuos en `package.json` catalogados como dependencia del skeleton pendiente de retiro controlado.
- **SQLite:** Descartado en todas sus modalidades (desarrollo, archivos locales, tests en memoria `:memory:`). Residuos en `phpunit.xml` catalogados como deuda técnica del skeleton pendiente de corrección en fase de testing.
- **Spatie Laravel-Permission:** Descartado en favor de RBAC propio integrado con Gates nativos.
- **nwidart/laravel-modules:** Descartado en favor de carpetas PSR-4 nativas en `app/Domains/`.
- **Laravel Boost:** Descartado por ahora.
- **Almacenamiento Inseguro de Credenciales Admin:** Descartado el uso de `localStorage` o `sessionStorage` para credenciales o tokens first-party.
- **Tokens Bearer para Admin First-Party:** Descartado; sustituido por sesiones stateful con cookies HttpOnly.

### 2.3 NO HOMOLOGADO TODAVÍA
- **MariaDB:** No se asume compatibilidad implícita. Requiere matriz de validación futura antes de considerarse soportado.

### 2.4 PROPUESTO PARA APROBACIÓN FINAL
- **Laravel Sanctum Stateful Cookie/Session Authentication:** Autenticación stateful basada en cookies seguras HttpOnly con protección CSRF nativa para el panel administrativo Vue 3 same-origin.
- **Convenciones de API Pública:** Prefijo versionado `/api/v1/` para futura API pública externa (sin congelar envelopes universales rígidos prematuramente).

### 2.5 PENDIENTE DE TRAVELFLOW NEXT
- Proyectos TFN, TFNP y TFNL: Clasificados formalmente como `FUTURO` e inexistentes en la actualidad.
- Se elimina cualquier concepto de adaptador prematuro (`TravelFlowNextAdapter`), endpoints ficticios o esquemas de base de datos anticipados.

### 2.6 BACKLOG
- Aplicación móvil nativa para guías y operaciones turísticas.
- Live Theme Customizer visual avanzado.
- Pasarelas de pago directas independientes de TFN.
