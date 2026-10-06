# Stack TecnolÃ³gico y TaxonomÃ­a de Decisiones

**Documento:** `docs/arquitectura/stack.md`
**VersiÃ³n:** 2.0 â€” Fase 0C

---

## 1. Stack Oficial Base

```text
Backend:                 Laravel 13.x
Runtime PHP:             PHP 8.3+ (Local verificado: PHP 8.3.30 ZTS)
Base de Datos:           MySQL 8.4+ / MariaDB 10.6+ (Local verificado: MySQL 8.4.3 LTS en tf_cms)
Frontend Administrativo: Vue 3 + TypeScript + Pinia + Vue Router + Vite
Tooling Frontend:        Node.js v22.x LTS + npm v10.x (Local verificado en Laragon)
Servidor Web Local:      Apache 2.4.66 con VirtualHost dedicado (app.tf-cms.test -> /public)
```

---

## 2. TaxonomÃ­a de Decisiones TÃ©cnicas

### DEFINIDO
- **Framework Backend:** Laravel 13.x asume la infraestructura completa (routing, sessions, CSRF, queue, storage, validation, console, mail, migrations).
- **SeparaciÃ³n Generacional:** TF CMS â‰  TF v1. No se reutiliza cÃ³digo ni arquitectura legacy.
- **TFN / TFNP / TFNL:** Aplicaciones independientes no implementadas. No se inventa API de TFN.
- **DocumentRoot:** Apuntando estrictamente a `D:\laragon\www\app.tf-cms\public`.
- **Gobernanza de Migraciones:** `database/migrations/` es la Ãºnica fuente ejecutable de migraciones. `DB/travelflow_cms.sql` es el volcado de referencia consolidada del esquema oficial.
- **Base de Datos Local:** MySQL 8.4.3 conectada a `tf_cms`.
- **MenÃº â‰  AutorizaciÃ³n:** Backend valida obligatoriamente permisos respondiendo 403 Forbidden. Frontend solo oculta opciones por UX.
- **Protocolo de VerificaciÃ³n para Orlando (Reglas 42-54):** ObligaciÃ³n de entregar credenciales temporales en cada fase navegable.
- **Almacenamiento de TF Builder:** Ãrbol estructurado JSON (AST), nunca HTML estÃ¡tico monolÃ­tico como fuente de verdad.
- **CatÃ¡logo de Tours:** Dominio altamente estructurado (itinerarios por dÃ­as, inclusiones, altitudes, coordenadas, precios referenciales).

### PROPUESTO (En FASE 0C, sujeto a aprobaciÃ³n de ChatGPT)
- **Arquitectura Backend:** Modular pragmÃ¡tica por dominios en `app/Domains/` + capa HTTP centralizada en `app/Http/`.
- **Identidad Desacoplada:** SeparaciÃ³n estricta de Persona (identidad humana) â‰  Usuario (acceso/credenciales) â‰  Actor (humano, sistema, servicio, cliente API).
- **AutenticaciÃ³n Administrativa:** Laravel Stateful Session Auth nativa (cookies HttpOnly, Secure, SameSite=Strict) sin paquetes externos.
- **AutorizaciÃ³n RBAC:** Modelo propio ligero en BD integrado dinÃ¡micamente con Gates nativos de Laravel.
- **Identificadores:** Modelo hÃ­brido (PK interna `BIGINT AUTO_INCREMENT` + identificador pÃºblico `ULID` / `slug`).
- **Persistencia:** Modelos con `$fillable` explÃ­cito, `preventLazyLoading` activo en desarrollo, DTOs `readonly` tipados y transacciones atÃ³micas `DB::transaction()`.
- **Soft Delete:** Aplicado selectivamente solo a entidades de contenido editorial clave (Tours, Destinos, Experiencias, PÃ¡ginas, Posts, Medios).
- **Frontend Admin:** Vue 3 SPA desacoplada consumiendo `/api/v1/`.
- **Frontend PÃºblico:** PHP SSR + Theme Engine + Progressive Enhancement (100% crawlabilidad SEO).
- **Cliente HTTP Admin:** Fetch API nativo encapsulado en composable `useApi()` (eliminaciÃ³n de Axios).
- **Estilos Admin:** Tailwind v4 encapsulado dentro de los componentes del TF Design System; Temas pÃºblicos desacoplados mediante CSS Tokens.
- **AuditorÃ­a Funcional:** Tabla `audit_logs` con snapshots JSON de valores antiguos/nuevos y `correlation_id`.
- **Estrategia de Testing:** Dual (SQLite `:memory:` para desarrollo rÃ¡pido/CI + MySQL real `tf_cms_test` para smoke tests).

### PENDIENTE DE INVESTIGACIÃ“N
- CompresiÃ³n nativa AVIF bajo la extensiÃ³n GD en PHP 8.3 Windows/Linux.
- Keyset (cursor-based) vs Offset pagination para grandes catÃ¡logos facetados.

### PENDIENTE DE APROBACIÃ“N (Por ChatGPT)
- Conjunto de documentos de arquitectura generados en Fase 0C (`arquitectura-laravel.md`, `identidad-autenticacion-rbac.md`, `persistencia.md`, `frontend-admin.md`, `seguridad.md`, `testing.md`).
- Descarte formal de `laravel/boost` (no recomendado para el flujo estricto Orlando â†’ ChatGPT â†’ Gemini).

### BACKLOG
- AplicaciÃ³n mÃ³vil nativa para guÃ­as y operaciones.
- MÃ³dulo de personalizaciÃ³n de temas en tiempo real (Live Theme Customizer).
- Pasarelas de pago directas independientes de TFN.

### PENDIENTE DE TRAVELFLOW NEXT
- Contrato API de integraciÃ³n operacional.
- Adaptador `TravelFlowNextAdapter`.
- Modelos de salidas, disponibilidad en tiempo real, cupos y reservas.
