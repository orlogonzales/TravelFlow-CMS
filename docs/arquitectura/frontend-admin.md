# Frontend Administrativo, Frontend Público y Tooling — TF CMS

**Documento:** `docs/arquitectura/frontend-admin.md`
**Estado:** `DEFINIDO Y VINCULANTE` (Bootstrap 5.3.8 versión base, AdminLTE 4 Vue shell administrativo, TF Design System, Skeleton Loaders, Descarte de Tailwind, Axios y Bootstrap Icons)
**Versión:** 2.2 — Fase 1B.1

---

## 1. Arquitectura del Frontend Administrativo (Panel de Control)

El panel administrativo de TravelFlow CMS es una **Single Page Application en Vue 3 first-party**, alojada dentro de la estructura de Laravel en `resources/js/` y compilada mediante Vite hacia `public/build/`, comunicándose con endpoints JSON internos del backend:

```text
Laravel 13
+
Vue 3 (Composition API con <script setup>)
+
TypeScript
+
Pinia (Gestión de estado reactivo global)
+
Vue Router (Navegación SPA con lazy loading)
+
Vite (Motor de build rápido y HMR)
+
Bootstrap 5.3.8 (Infraestructura CSS / Componentes base)
+
AdminLTE 4 Vue (@adminlte/vue v0.8.0 - Sistema visual administrativo)
+
TF Design System (Tokens de diseño corporativo y componentes especializados)
+
Font Awesome Free (Sistema exclusivo de iconografía)
```

---

## 2. Decisión Vinculante: Bootstrap 5.3.8 DEFINIDO / Tailwind DESCARTADO

Por decisión de Producto y Dirección Técnica:

### 2.1 Bootstrap 5.3.8 (`DEFINIDO Y VINCULANTE`)
- **Versión Oficial Inicial:** **Bootstrap 5.3.8** es la versión base exacta de infraestructura CSS para TravelFlow CMS.
- **Versión Controlada y Reproducible:** Durante la futura implementación técnica frontend, se instalará explícitamente dentro de la rama controlada `5.3.8` y el archivo de bloqueo (`package-lock.json`) garantizará instalaciones reproducibles.
- **Prohibición de Actualizaciones Automáticas:** Queda prohibido utilizar comodines o rangos permisivos en `package.json` que habiliten actualizaciones mayores automáticas. Cualquier migración futura de versión deberá ser un cambio técnico planificado que verifique:
  - Compatibilidad de componentes.
  - Comportamiento de JavaScript y Vue.
  - Integridad del TF Design System.
  - Diseño responsive y accesibilidad.
  - Proceso de build y ausencia de regresión visual.

### 2.2 Tailwind CSS (`DESCARTADO`)
- **Estado:** Totalmente descartado de la arquitectura oficial de TF CMS. No coexistirá con Bootstrap en el Admin.
- **Tratamiento de Residuos del Skeleton:** Las referencias a Tailwind existentes en `package.json` se clasifican formalmente como:
  ```text
  DEPENDENCIA HEREDADA DEL SKELETON
  PENDIENTE DE RETIRO CONTROLADO
  ```
- **Regla de Fase:** No se alteran ni eliminan en la presente Fase 0C.1B para preservar su carácter estrictamente documental. Su remoción se ejecutará en la microfase dedicada a tooling frontend.

---

## 3. Jerarquía Visual: Bootstrap 5.3.8 → AdminLTE 4 Vue → TF Design System

Queda terminantemente prohibido construir el panel con apariencia Bootstrap genérica o desarticulada. La arquitectura visual administrativa se estructura en capas bien delimitadas:

```text
Bootstrap 5.3.8 (Infraestructura CSS base / Rejilla / Utilidades de diseño)
        ↓
AdminLTE 4 Vue (@adminlte/vue v0.8.0 - Shell administrativo, layouts, sidebar, topbar y theming)
        ↓
TF DESIGN SYSTEM (Identidad corporativa TravelFlow, tokens de diseño y coherencia UX)
        ↓
Componentes TF Oficiales
(TfButton, TfModal, TfInput, TfSelect, TfSwitch, TfCheckbox,
 TfTable, TfTabs, TfBadge, TfChip, TfToast, TfPagination, etc.)
        ↓
Módulos de Negocio TF CMS (Dashboard, Tour Editor, Builder, Media Library, etc.)
```

El **TF Design System** gobierna:
- Tokens de diseño: Paleta cromática corporativa, tipografía, escalas de espaciado (4px/8px).
- Radios de borde, elevaciones, estados de interacción y transiciones.
- Patrón UX estándar: `Acción -> Modal -> Formulario -> Backend -> Notificación Toast -> Cierre y Actualización Asíncrona`.

**AdminLTE 4 Vue** aporta:
- Layouts estándar de backoffice (`LteDashboardLayout`, `LteAuthLayout`).
- Sistema de colapso y responsividad de Sidebar integrado con Vue Router (`<RouterLink>`).
- Theming reactivo (Modo Claro / Modo Oscuro / Automático) mediante el composable `useColorMode()`.
- Estructura limpia y desacoplada del frontend público.

---

## 4. Skeleton Loaders y Patrón Asíncrono (`DEFINIDO`)

### 4.1 Skeleton Loaders
Los estados de carga estructural (**Skeleton Loaders**) son obligatorios y se construirán sobre los mecanismos nativos de **Bootstrap 5.3.8** (especialmente `placeholders` y clases utilitarias), encapsulados por el TF Design System:

```text
Bootstrap 5.3.8 (placeholders & utilities)
        ↓
TF Design System
        ↓
Componentes Skeleton Específicos:
TfSkeleton, TfSkeletonText, TfSkeletonCard, TfSkeletonTable, TfSkeletonForm, TfSkeletonStats
```

### 4.2 Patrón Asíncrono Obligatorio
Para toda vista o componente que realice peticiones remotas se aplica la cadena:
```text
FETCH + VUE REACTIVITY + BOOTSTRAP 5.3.8 + TF SKELETON
```

Estados visuales requeridos:
- **`LOADING`:** Muestra la estructura de `TfSkeleton` correspondiente.
- **`SUCCESS`:** Renderiza el contenido real reactivo.
- **`EMPTY`:** Despliega `TfEmptyState` con acción de recuperación o creación.
- **`ERROR`:** Despliega `TfAlert` con opción explícita de reintento (`Retry`).

*Regla de Uso:* No utilizar skeletons indiscriminadamente cuando la operación sea imperceptible o carezca de estructura previa que proyectar.

---

## 5. Iconografía Oficial: Font Awesome (`DEFINIDO`) y Descarte de Bootstrap Icons

- **Font Awesome Free (6.7.2):** Es el sistema oficial y exclusivo de iconografía del panel de administración.
- **Bootstrap Icons (`DESCARTADO`):** Queda formalmente descartado. A pesar de ser la iconografía por defecto en ejemplos de AdminLTE 4, no se instala ni se enlaza `@bootstrap-icons` en el proyecto para evitar polución de dependencias y fuentes duplicadas.
- **Mapeo Transparente:** Los componentes internos de `@adminlte/vue` que emiten selectores de clase Bootstrap Icons (`.bi-list`, `.bi-chevron-right`, `.bi-chevron-down`, `.bi-circle`, etc.) son mapeados a través de CSS pseudo-elementos (`::before`) en `resources/scss/app.scss` hacia los correspondientes glifos unicode de Font Awesome 6 Free.
- Se implementará encapsulado mediante el componente `<TfIcon>` del Design System, garantizando uso de la variante Free sin dependencias Pro no licenciadas.

---

## 6. Frontend Público: Arquitectura SSR y Theme Engine Independiente

Para el portal público accesible para viajeros y motores de búsqueda:
- **Tecnología:** Laravel SSR (Blade) dentro del **Theme Engine** con **Mejora Progresiva** (JS nativo puntual).
- **No es una SPA:** La web pública no utiliza arquitectura SPA.
- **Independencia Total de Estilos:**
  ```text
  ADMIN TF CMS        ──►  Bootstrap 5.3.8 + AdminLTE 4 Vue + TF Design System
  WEB PÚBLICA (THEMES) ──►  Theme Engine + Tecnología visual propia del Tema
  ```
  El Theme Engine público no está forzado a utilizar Bootstrap 5.3.8 ni las hojas de estilo del Admin. Cada tema define sus tokens propios mediante `theme.json`.
- **Rendimiento:** Prioridad a Core Web Vitals, crawlabilidad total para SEO y renderizado rápido medido empíricamente en laboratorio (sin compromisos arbitrarios no verificados).

---

## 7. Cliente HTTP: Fetch Nativo (`DEFINIDO`) vs Axios

- **Cliente Oficial:** **Fetch API nativo** encapsulado en composables TypeScript (`useApi()`), gestionando cookies de sesión `HttpOnly` y protección CSRF (`X-XSRF-TOKEN`).
- **Axios:** Clasificado como deuda del skeleton de Laravel, retirado completamente en Fase 0D.

---

## 8. Superficies y Componentes Implementados (Fase 1B)

En la **Fase 1B**, el frontend administrativo materializó sus dos primeras superficies y componentes base:
- **`<TfButton>`:** Botón estilizado con soporte de variantes Bootstrap 5.3.8, estados de carga con spinner Font Awesome y anchos responsivos.
- **`<TfInput>`:** Campo de formulario accesible con etiquetas asociadas, atributos `aria-*`, iconos prefijo y soporte nativo para mostrar/ocultar contraseñas.
- **`<TfAlert>`:** Notificaciones de advertencia, error y éxito con iconografía tipada y opción de descarte.
- **`<TfSkeleton>`:** Indicador de carga estructural basado en placeholders nativos de Bootstrap 5.3.8, desplegado durante la resolución de sesión (`/api/auth/me`) y consulta de recursos protegidos.
- **Navigation Guard:** `router.beforeEach` consulta la sesión activa una sola vez al cargar la SPA (`/api/auth/me`). Si el usuario no está autenticado, redirige a `/login?redirect=...`.
- **Auth Store:** Implementado con Pinia en `resources/js/stores/auth.ts`. Mantiene el perfil del usuario en memoria reactiva sin persistir credenciales ni tokens en almacenamiento local.
- **Cliente HTTP:** Implementado en `resources/js/composables/useApi.ts` sobre Fetch API nativo con manejo automático de CSRF y recuperación de 419.

---

## 9. Adopción Oficial de AdminLTE 4 Vue (Fase 1B.1)

En la **Fase 1B.1**, la Dirección Técnica homologa **AdminLTE 4 Vue (`@adminlte/vue` v0.8.0)** como el sistema visual administrativo oficial de TravelFlow CMS, reemplazando el shell provisional de Fase 1B.

### 9.1 Ámbito y Restricciones Vinculantes
- **Ámbito Exclusivo Backoffice:** Aplica única y exclusivamente a la administración/backoffice. El frontend público continúa reservado para el Theme Engine SSR Blade.
- **Paquete Oficial Único:** Se utiliza `@adminlte/vue` bajo licencia MIT. Quedan explícitamente descartados:
  - `colorlibhq/adminlte-laravel` (AdminLTE Laravel/Blade: incompatible con nuestra SPA Vue).
  - `@adminlte/nuxt` (incompatible con nuestro stack Vite).
  - jQuery (totalmente descartado).
  - Bootstrap Icons (reemplazado por mapeo a Font Awesome Free).
- **Bootstrap 5.3.8:** La versión exacta de Bootstrap permanece anclada en `5.3.8`. Se verifica mediante deduplicación estricta de npm (`npm ls bootstrap`).
- **Política de Almacenamiento Local (`localStorage`):**
  - Permitido **exclusivamente** para la preferencia visual de tema (`lte-theme`), administrada por el composable `useColorMode()`.
  - Queda **estrictamente prohibido** almacenar passwords, tokens Bearer, tokens de sesión o datos sensibles en `localStorage` o `sessionStorage`. La autenticación se sostiene únicamente mediante cookies `HttpOnly` de Laravel Sanctum.

### 9.2 Superficies Homologadas en Fase 1B.1
- **`LteDashboardLayout` (`AdminLayout.vue`):**
  - Shell oficial de administración con `brand-text="TravelFlow CMS"`.
  - Menú lateral conectado a Vue Router mediante `<RouterLink>` con resaltado de ruta activa (`Dashboard`).
  - Menú lateral limpio: únicamente opciones funcionales existentes (`Dashboard`). Prohibidos placeholders de desarrollo o módulos futuros mostrados como deshabilitados.
  - `#topbar-end`: Selector de tema accesible (`TfThemeToggle.vue`) con opciones Claro / Oscuro / Automático (Sistema) usando Font Awesome Free y `useColorMode()`.
  - `#user-menu`: Menú de usuario con nombre, correo, rol activo y botón de cierre de sesión interactivo protegido con SweetAlert2.
- **`LteAuthLayout` (`LoginView.vue`):**
  - Shell oficial de autenticación (variante `v2`) con tarjeta centrada y branding TravelFlow CMS.
  - Integra los componentes de diseño del TF Design System (`TfInput`, `TfButton`, `TfAlert`), alternador de contraseña, checkbox "Recordarme" y flujo CSRF Sanctum intacto.
- **`DashboardView.vue`:**
  - Vista limpia y neutral de bienvenida para el operador.
  - Tarjeta de estado de plataforma conectada con el backend.
  - Eliminados dumps técnicos de desarrollo (permisos JSON en bruto, banderas de debugging).
  - Prohibidos KPIs simulados, gráficas ficticias o datos inventados no respaldados por endpoints de dominio.
