# Stack Tecnológico y Taxonomía de Decisiones

## 1. Stack Oficial Base

```text
Backend:                 Laravel 13.x
Runtime PHP:             PHP 8.3+ (Local verificado: PHP 8.3.30 ZTS)
Base de Datos:           MySQL 8.4+ / MariaDB 10.6+ (Local verificado: MySQL 8.4.3 LTS)
Frontend Administrativo: Vue 3 + TypeScript + Pinia + Vue Router + Vite
Tooling Frontend:        Node.js v22.x LTS + npm v10.x (Local verificado en Laragon)
Servidor Web Local:      Apache 2.4.66 con VirtualHost dedicado (app.tf-cms.test -> /public)
```

## 2. Taxonomía de Decisiones Técnicas

### DEFINIDO
- **Framework Backend:** Laravel 13.x asume la infraestructura completa (routing, sessions, CSRF, queue, storage, validation, console, mail, migrations).
- **Separación Generacional:** TF CMS ≠ TF v1. No se reutiliza código ni arquitectura legacy.
- **TFN / TFNP / TFNL:** Aplicaciones independientes no implementadas. No se inventa API de TFN.
- **DocumentRoot:** Apuntando estrictamente a `D:\laragon\www\app.tf-cms\public`.
- **Gobernanza de Migraciones:** `database/migrations/` es la única fuente ejecutable de migraciones. `DB/travelflow_cms.sql` es el volcado de referencia consolidada del esquema oficial.
- **Almacenamiento de TF Builder:** Árbol estructurado JSON (AST), nunca HTML estático monolítico como fuente de verdad.
- **Catálogo de Tours:** Dominio altamente estructurado (itinerarios por días, inclusiones, altitudes, coordenadas, precios referenciales).

### PROPUESTO
- **Frontend Admin:** SPA basada en Vue 3 (Composition API con `<script setup>`), TypeScript, Pinia para estado global, Vue Router con lazy loading y Vite.
- **Frontend Público:** Arquitectura híbrida PHP SSR + HTML5 semántico + CSS moderno + JavaScript progresivo para 100% SEO y óptimos Core Web Vitals.
- **TF Design System Propio:** Componentes desacoplados de plantillas comerciales (`TfButton`, `TfModal`, `TfTable`, `TfSkeleton`, `TfToast`, etc.).
- **Font Awesome Free:** Sistema de iconos con abstracción interna (`TfIcon`) para desacoplar dependencias comerciales.

### PENDIENTE DE INVESTIGACIÓN / DECISIÓN
- **Autenticación Administrativa:** Elección entre Laravel Session / Stateful Auth nativa vs Laravel Sanctum tokens para el panel Vue 3 (a definir en Fase de Auth).
- **Driver de Medios:** Compatibilidad y generación de variantes WebP/AVIF mediante GD nativo vs Imagick.
- **Manejo de Peticiones HTTP en Frontend:** Fetch API nativo vs cliente Axios (incorporado por defecto en el `package.json` de Laravel 13).
- **Mecanismo de Paginación de Catálogos:** Keyset (cursor-based) vs Offset pagination para grandes volúmenes con filtros facetados.
- **Laravel Boost:** Evaluación técnica para tooling asistido por IA (actualmente dev-only v2.10.2). Clasificado como `PENDIENTE DE APROBACIÓN DE CHATGPT`.

### BACKLOG
- Aplicación móvil nativa para guías y operaciones.
- Módulo de personalización de temas en tiempo real (Live Theme Customizer).
- Pasarelas de pago directas independientes de la futura integración de reservas de TFN.
