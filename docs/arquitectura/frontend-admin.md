# Frontend Administrativo, Frontend Público y Tooling — TF CMS

**Documento:** `docs/arquitectura/frontend-admin.md`
**Estado:** `DEFINIDO` (Bootstrap 5 y descarte de Tailwind fijados por Dirección Técnica)
**Versión:** 2.0 — Fase 0C.1

---

## 1. Arquitectura del Frontend Administrativo (Panel de Control)

El panel administrativo de TravelFlow CMS se estructurará como una **Single Page Application en Vue 3**, alojada en `resources/admin/` y compilada mediante Vite hacia `public/assets/admin/`, comunicándose con el backend a través de endpoints REST en `/api/v1/` protegidos por sesiones stateful:

```text
Laravel 13
    +
Vue 3 (Composition API con <script setup>)
    +
TypeScript
    +
Pinia (Estado reactivo global)
    +
Vue Router (Navegación SPA con lazy loading)
    +
Vite (Motor de build rápido)
    +
Bootstrap 5 (Infraestructura CSS / Componentes base)
    +
TF Design System (Sistema visual y componentes oficiales del producto)
    +
Font Awesome (Sistema principal de iconografía)
```

---

## 2. Decisión Vinculante de Framework CSS: Bootstrap 5 DEFINIDO / Tailwind DESCARTADO

Por decisión de Producto y Dirección Técnica:

### 2.1 Bootstrap 5 (`DEFINIDO`)
- **Rol:** Infraestructura CSS base y rejilla responsive del panel administrativo.
- **Principio:** Bootstrap 5 provee el andamiaje, pero **NO define la identidad visual final** de TravelFlow CMS.
- La identidad visual pertenece exclusivamente al **TF Design System**.

### 2.2 Tailwind CSS (`DESCARTADO`)
- **Estado:** Totalmente descartado para TF CMS.
- **Tratamiento de dependencias en `package.json`:** Las dependencias actuales `@tailwindcss/vite` y `tailwindcss` se clasifican como:
  ```text
  DEPENDENCIA HEREDADA DEL SKELETON
  PENDIENTE DE RETIRO CONTROLADO
  ```
  No se eliminan en la Fase 0C.1 para mantener la naturaleza puramente documental de esta microfase. Su desinstalación se ejecutará de forma verificada (`npm install`, `npm run build`) en la microfase dedicada a frontend.

---

## 3. No Confundir Bootstrap con TF Design System

Se prohíbe que los módulos del CMS se construyan como una colección desordenada de clases e inputs Bootstrap genéricos.

```text
Bootstrap 5 (Infraestructura base / Grid / Utilities)
       ↓
TF DESIGN SYSTEM (Tokens de diseño propios, coherencia y UX)
       ↓
Componentes Oficiales:
TfButton, TfModal, TfInput, TfSelect, TfSwitch, TfCheckbox,
TfTable, TfTabs, TfBadge, TfChip, TfSkeleton, TfToast, TfPagination
       ↓
Módulos de Negocio TF CMS (Tour Editor, Builder, Media Library, etc.)
```

El **TF Design System** controla:
- Paleta cromática corporativa y modo oscuro/claro.
- Tipografía y jerarquía visual.
- Espaciados estandarizados (escala basada en 4px/8px).
- Radios de borde, elevaciones y sombras.
- Estados de interacción y transiciones.
- Skeletons de carga fluida durante llamadas remotas.
- Patrón UX obligatorio: `Botón -> Modal -> Formulario -> Backend -> Toast Feedback -> Cierre y Refresco Asíncrono`.

---

## 4. Font Awesome: Sistema Oficial de Iconografía

- **Estado:** `DEFINIDO`.
- Sistema exclusivo de iconografía para la interfaz administrativa y componentes core.
- Se encapsula en el componente `<TfIcon>` del Design System para mapear nombres semánticos (ej. `tour`, `itinerary`, `settings`) y respetar estrictamente el licenciamiento Free (sin introducir accidentalmente dependencias Pro).

---

## 5. Frontend Público: Arquitectura SSR Desacoplada

Para la web pública visible para viajeros y motores de búsqueda:
- **Tecnología Principal:** **PHP SSR (Server-Side Rendering)** integrado en el **Theme Engine**.
- **Regla Estricta:** La web pública **NO será una SPA**.
- **Desacoplamiento Total:** Los Temas públicos **NO dependen de Bootstrap ni del bundle administrativo**.
  - Cada Tema público es soberano en su diseño y estructura visual.
  - Los temas públicos utilizarán **CSS Custom Properties (Design Tokens)** declarados en su manifiesto `theme.json`.
  - Un tema puede utilizar su propio CSS o framework ligero sin contaminar el núcleo de TF CMS.
- **Objetivos Clave:**
  1. **100% Crawlabilidad SEO:** Indexación inmediata sin depender de ejecución JavaScript en cliente.
  2. **Core Web Vitals:** TTFB < 200ms y FCP instantáneo.
  3. **Mejora Progresiva:** JS ligero nativo únicamente para componentes interactivos puntuales (carruseles, selector de fechas, modales de reserva).

---

## 6. Cliente HTTP: Fetch Nativo vs Axios

- **Evaluación:** Axios agrega dependencia externa redundante.
- **Recomendación (`PROPUESTO`):** Utilizar **Fetch API nativo** encapsulado en un composable TypeScript ligero (`useApi()`) que gestione cookies de sesión `HttpOnly`, cabeceras `X-XSRF-TOKEN` y redirección automática ante `401 Unauthorized`.
- La dependencia `axios` heredada del esqueleto de Laravel 13 será retirada de forma controlada en la microfase de frontend.
