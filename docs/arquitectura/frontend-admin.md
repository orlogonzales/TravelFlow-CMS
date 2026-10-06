# Frontend Administrativo, Frontend Público y Tooling — TF CMS

**Documento:** `docs/arquitectura/frontend-admin.md`
**Estado:** `DEFINIDO Y VINCULANTE` (Bootstrap 5.3.8 versión base oficial, TF Design System, Skeleton Loaders, Descarte de Tailwind y Fetch API)
**Versión:** 2.1 — Fase 0C.1B

---

## 1. Arquitectura del Frontend Administrativo (Panel de Control)

El panel administrativo de TravelFlow CMS es una **Single Page Application en Vue 3 first-party**, alojada dentro de la estructura de Laravel en `resources/admin/` y compilada mediante Vite hacia `public/assets/admin/`, comunicándose con endpoints JSON internos del backend:

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
TF Design System (Sistema visual y componentes oficiales de TravelFlow)
+
Font Awesome (Sistema principal de iconografía)
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

## 3. Jerarquía Visual: TF Design System sobre Bootstrap 5.3.8

Queda terminantemente prohibido construir el panel con apariencia Bootstrap genérica. Bootstrap 5.3.8 actúa únicamente como infraestructura subyacente:

```text
Bootstrap 5.3.8 (Infraestructura base / Rejilla / Utilidades)
        ↓
TF DESIGN SYSTEM (Identidad corporativa, tokens de diseño y coherencia UX)
        ↓
Componentes TF Oficiales
(TfButton, TfModal, TfInput, TfSelect, TfSwitch, TfCheckbox,
 TfTable, TfTabs, TfBadge, TfChip, TfToast, TfPagination, etc.)
        ↓
Módulos de Negocio TF CMS (Tour Editor, Builder, Media Library, etc.)
```

El **TF Design System** gobierna:
- Tokens de diseño: Paleta cromática corporativa, tipografía, escalas de espaciado (4px/8px).
- Radios de borde, elevaciones, estados de interacción y transiciones.
- Patrón UX estándar: `Acción -> Modal -> Formulario -> Backend -> Notificación Toast -> Cierre y Actualización Asíncrona`.

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

## 5. Iconografía Oficial: Font Awesome (`DEFINIDO`)

- Font Awesome es el sistema oficial de iconografía del panel de administración.
- Se implementará encapsulado mediante el componente `<TfIcon>` del Design System, garantizando uso de la variante Free sin dependencias Pro no licenciadas.

---

## 6. Frontend Público: Arquitectura SSR y Theme Engine Independiente

Para el portal público accesible para viajeros y motores de búsqueda:
- **Tecnología:** Laravel SSR (Blade) dentro del **Theme Engine** con **Mejora Progresiva** (JS nativo puntual).
- **No es una SPA:** La web pública no utiliza arquitectura SPA.
- **Independencia Total de Estilos:**
  ```text
  ADMIN TF CMS        ──►  Bootstrap 5.3.8 + TF Design System
  WEB PÚBLICA (THEMES) ──►  Theme Engine + Tecnología visual propia del Tema
  ```
  El Theme Engine público no está forzado a utilizar Bootstrap 5.3.8 ni las hojas de estilo del Admin. Cada tema define sus tokens propios mediante `theme.json`.
- **Rendimiento:** Prioridad a Core Web Vitals, crawlabilidad total para SEO y renderizado rápido medido empíricamente en laboratorio (sin compromisos arbitrarios no verificados).

---

## 7. Cliente HTTP: Fetch Nativo (`DEFINIDO`) vs Axios

- **Cliente Oficial:** **Fetch API nativo** encapsulado en composables TypeScript (`useApi()`), gestionando cookies de sesión `HttpOnly` y protección CSRF (`X-XSRF-TOKEN`).
- **Axios:** Clasificado como deuda del skeleton de Laravel, pendiente de retiro controlado en la microfase frontend.

---

## 8. Regla de Fase 0C.1B: Prohibición de Modificaciones en Código Frontend

> **ADVERTENCIA VINCULANTE:** Durante la Fase 0C.1B NO se instala Bootstrap 5.3.8, NO se edita `package.json`, NO se altera `package-lock.json`, NO se toca Vite ni Vue, y NO se crean componentes de diseño. La presente fase es estrictamente documental.
