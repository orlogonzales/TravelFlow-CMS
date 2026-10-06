# Stack Tecnológico y Taxonomía de Decisiones

**Documento:** `docs/arquitectura/stack.md`
**Estado:** Actualizado con Adenda Vinculante 0C.1
**Versión:** 3.0 — Fase 0C.1

---

## 1. Stack Oficial Base

```text
Backend:                 Laravel 13.x
Runtime PHP:             PHP 8.3+ (Local verificado: PHP 8.3.30 ZTS)
Base de Datos:           MySQL 8.4+ (Local verificado: MySQL 8.4.3 LTS en tf_cms)
Frontend Administrativo: Vue 3 + TypeScript + Pinia + Vue Router + Vite
Framework CSS Admin:     Bootstrap 5 (Infraestructura CSS / Grid)
Capa Visual Oficial:     TF Design System (Tokens propios, átomos y componentes del producto)
Iconografía Oficial:     Font Awesome (Free)
Servidor Web Local:      Apache 2.4.66 con VirtualHost dedicado (app.tf-cms.test -> /public)
```

---

## 2. Taxonomía de Decisiones Técnicas

### DEFINIDO
- **Framework Backend:** Laravel 13.x asume la infraestructura completa (routing, sessions, CSRF, queue, storage, validation, console, mail, migrations).
- **Motor de Base de Datos:** **MySQL** oficialmente en todos los entornos (desarrollo, testing, staging, producción).
- **Framework CSS Administrativo:** **Bootstrap 5** como infraestructura CSS base del panel.
- **Capa Visual del Producto:** **TF Design System** (control total de colores, tipografía, espaciado, componentes y UX).
- **Sistema de Iconos:** **Font Awesome** como estándar oficial.
- **Separación Generacional:** TF CMS ≠ TF v1. No se reutiliza código ni arquitectura legacy.
- **TFN / TFNP / TFNL:** Aplicaciones independientes no implementadas. No se inventa API de TFN.
- **DocumentRoot:** Apuntando estrictamente a `D:\laragon\www\app.tf-cms\public`.
- **Gobernanza de Migraciones:** `database/migrations/` es la única fuente ejecutable de migraciones. `DB/travelflow_cms.sql` es el volcado de referencia consolidada del esquema oficial.
- **Base de Datos Local:** MySQL 8.4.3 conectada a `tf_cms`.
- **Base de Datos de Testing:** MySQL independiente `tf_cms_test` (prohibido testing destructivo sobre `tf_cms`).
- **Menú ≠ Autorización:** Backend valida obligatoriamente permisos respondiendo 403 Forbidden. Frontend solo oculta opciones por UX.
- **Protocolo de Verificación para Orlando (Reglas 42-54):** Obligación de entregar credenciales temporales en cada fase navegable.
- **Almacenamiento de TF Builder:** Árbol estructurado JSON (AST), nunca HTML estático monolítico como fuente de verdad.
- **Catálogo de Tours:** Dominio altamente estructurado (itinerarios por días, inclusiones, altitudes, coordenadas, precios referenciales).

### DESCARTADO
- **Tailwind CSS:** Totalmente descartado para TF CMS. Las dependencias actuales en `package.json` se consideran heredadas del skeleton y pendientes de retiro en la microfase frontend.
- **SQLite:** Descartado en todas sus variantes (desarrollo, archivos locales, tests en memoria `:memory:`).

### NO HOMOLOGADO TODAVÍA
- **MariaDB:** No se declara compatible oficialmente hasta contar con una matriz de pruebas dedicada que demuestre paridad estricta.

### PROPUESTO (En FASE 0C, a la espera de auditoría de ChatGPT)
- **Arquitectura Backend:** Modular pragmática por dominios en `app/Domains/` + capa HTTP centralizada en `app/Http/`.
- **Identidad Desacoplada:** Separación estricta de Persona (identidad humana) ≠ Usuario (acceso/credenciales) ≠ Actor (humano, sistema, servicio, cliente API).
- **Autenticación Administrativa:** Laravel Stateful Session Auth nativa (cookies HttpOnly, Secure, SameSite=Strict) sin paquetes externos.
- **Autorización RBAC:** Modelo propio ligero en BD integrado dinámicamente con Gates nativos de Laravel.
- **Identificadores:** Modelo híbrido (PK interna `BIGINT AUTO_INCREMENT` + identificador público `ULID` / `slug`).
- **Persistencia:** Modelos con `$fillable` explícito, `preventLazyLoading` activo en desarrollo, DTOs `readonly` tipados y transacciones atómicas `DB::transaction()`.
- **Soft Delete:** Aplicado selectivamente solo a entidades de contenido editorial clave.
- **Frontend Admin:** Vue 3 SPA desacoplada consumiendo `/api/v1/`.
- **Frontend Público:** PHP SSR + Theme Engine + Progressive Enhancement (100% crawlabilidad SEO).
- **Cliente HTTP Admin:** Fetch API nativo encapsulado en composable `useApi()` (eliminación de Axios).
- **Auditoría Funcional:** Tabla `audit_logs` con snapshots JSON de valores antiguos/nuevos y `correlation_id`.

### PENDIENTE DE INVESTIGACIÓN
- Compresión nativa AVIF bajo la extensión GD en PHP 8.3.
- Keyset (cursor-based) vs Offset pagination para grandes catálogos facetados.

### PENDIENTE DE APROBACIÓN (Por ChatGPT)
- Conjunto de documentos de arquitectura generados en Fase 0C/0C.1.
- Descarte formal de `laravel/boost`.

### BACKLOG
- Aplicación móvil nativa para guías y operaciones.
- Módulo de personalización de temas en tiempo real (Live Theme Customizer).
- Pasarelas de pago directas independientes de TFN.

### PENDIENTE DE TRAVELFLOW NEXT
- Contrato API de integración operacional.
- Adaptador `TravelFlowNextAdapter`.
- Modelos de salidas, disponibilidad en tiempo real, cupos y reservas.
