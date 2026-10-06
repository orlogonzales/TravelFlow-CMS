# Frontend Administrativo, Frontend PÃºblico y Tooling â€” TF CMS

**Documento:** `docs/arquitectura/frontend-admin.md`
**Estado:** `PROPUESTO` (Sujeto a aprobaciÃ³n formal de ChatGPT)
**VersiÃ³n:** 1.0 â€” Fase 0C

---

## 1. Arquitectura del Frontend Administrativo (Panel de Control)

Se compararon 3 opciones para la implementaciÃ³n del panel administrativo en Vue 3 + TypeScript + Pinia + Vue Router + Vite:

| Criterio | OpciÃ³n A: Vue 3 SPA Desacoplada sobre API JSON (Recomendada) | OpciÃ³n B: Vue 3 con Laravel Inertia.js | OpciÃ³n C: Arquitectura HÃ­brida Blade + Vue Widgets |
| :--- | :--- | :--- | :--- |
| **SeparaciÃ³n de Responsabilidades** | **Total**: Frontend independiente consumiendo `/api/v1/` | Acoplamiento fuerte entre controladores y vistas Vue | Dispersa; Blade gestiona pÃ¡ginas y Vue componentes |
| **ErgonomÃ­a de TF Builder** | **Ã“ptima**: Canvas interactivo continuo sin recargas Blade | Buena, pero con restricciones en el protocolo Inertia | Compleja de sincronizar con estado global |
| **ReutilizaciÃ³n de API** | **100%**: Los mismos endpoints sirven para Admin, integraciones o Apps | Limitada a las pÃ¡ginas y props de Inertia | Nula |
| **Complejidad y Dependencias** | Cero dependencias intermedias (Vite nativo + Vue) | Requiere paquete Inertia en PHP y npm | Media (mezcla dos paradigmas) |
| **Rendimiento Administrativo** | **Excelente**; transiciones instantÃ¡neas y carga perezosa | Muy bueno | Regular (recargas completas frecuentes) |
| **Aislamiento del Frontend PÃºblico**| **Total**: La web pÃºblica no carga nada del bundle de Admin | Aislado mediante layouts | Riesgo de mezcla de assets |

### RecomendaciÃ³n TÃ©cnica: OpciÃ³n A (Vue 3 SPA Desacoplada sobre API JSON)
Se recomienda estructurar el panel administrativo como una **Single Page Application en `resources/admin/`**, compilada con Vite hacia `public/assets/admin/`, comunicÃ¡ndose con el backend a travÃ©s de endpoints REST en `/api/v1/` protegidos por sesiones stateful.
- Proporciona una experiencia fluida e interactiva imprescindible para herramientas avanzadas como **TF Builder** y la gestiÃ³n de **Itinerarios DinÃ¡micos**.
- Mantiene la API REST limpia, versionada y reutilizable para cualquier consumidor autorizado.

---

## 2. Frontend PÃºblico: Arquitectura SSR Desacoplada

Para la web pÃºblica visible para viajeros y motores de bÃºsqueda:
- **TecnologÃ­a Principal:** **PHP SSR (Server-Side Rendering)** integrado en el **Theme Engine**.
- **Regla Estricta:** La web pÃºblica **NO serÃ¡ una SPA**.
- **Objetivos Clave:**
  1. **100% Crawlabilidad SEO:** IndexaciÃ³n inmediata de itinerarios, precios, fotos y metadatos estructurados sin depender de renderizado JavaScript en el cliente.
  2. **Core Web Vitals:** TTFB (Time to First Byte) inferior a 200ms y FCP (First Contentful Paint) ultrarrÃ¡pido.
  3. **Mejora Progresiva (Progressive Enhancement):** JavaScript nativo o Alpine/Vue ligero solo para interactividad especÃ­fica (carruseles, selector de fechas, modales de reserva, filtros dinÃ¡micos con Load More).

---

## 3. Cliente HTTP: Fetch Nativo vs Axios

Laravel 13 incorpora `axios` por defecto en su `package.json`. Se realizÃ³ un anÃ¡lisis comparativo:

| CaracterÃ­stica | Fetch API Nativo de Navegador | Axios (`axios: ^1.11.0`) |
| :--- | :--- | :--- |
| **Dependencia externa** | **0 KB (Nativo en todos los navegadores modernos)** | ~30 KB minificado |
| **Soporte de Promesas** | Nativo (`async/await`) | Nativo (`async/await`) |
| **Interceptores** | FÃ¡cilmente implementable mediante una funciÃ³n wrapper (`useApi`) | Incorporado en la librerÃ­a |
| **GestiÃ³n de CSRF** | InyecciÃ³n sencilla de la cabecera `X-XSRF-TOKEN` | AutomÃ¡tica |
| **Mantenimiento futuro** | EstÃ¡ndar web permanente (W3C) | Sujeto a parches y actualizaciones de terceros |

### RecomendaciÃ³n TÃ©cnica: Fetch API Nativo
Implementar un composable ligero `useApi()` en TypeScript (~40 lÃ­neas de cÃ³digo) que encapsule las llamadas a `/api/v1/`, inyecte automÃ¡ticamente la cabecera `X-XSRF-TOKEN` leÃ­da de la cookie de Laravel y capture los errores `401 Unauthorized` para redirecciÃ³n a login.
- **AcciÃ³n propuesta para la microfase de frontend:** Eliminar la dependencia `axios` del `package.json` para reducir la superficie de ataque y evitar dependencias redundantes.

---

## 4. Estrategia de Estilos: Tailwind CSS vs TF Design System con Tokens

Laravel 13 incorpora `@tailwindcss/vite` y `tailwindcss ^4.0.0` en el esqueleto. Se evaluÃ³ su pertinencia para TF CMS:

### 4.1 Aislamiento Estricto: Admin vs Themes PÃºblicos
1. **Frontend Admin (Panel de Control):**
   - El uso de **Tailwind CSS v4** dentro de `resources/admin/` es una herramienta de productividad muy Ã¡gil para componer las interfaces del panel administrativo.
   - Sin embargo, los componentes de UI deben encapsularse como Ã¡tomos del **TF Design System** (`<TfButton>`, `<TfModal>`, `<TfInput>`, `<TfTable>`, `<TfSkeleton>`). Los desarrolladores de TF CMS usarÃ¡n los componentes del Design System en lugar de escribir clases de utilidad sueltas en cada vista.
2. **Themes de la Web PÃºblica:**
   - **Completamente aislados de Tailwind**.
   - Los temas pÃºblicos utilizarÃ¡n **CSS Custom Properties (Design Tokens)** definidos en su manifiesto `theme.json` (`--tf-color-primary`, `--tf-font-base`, etc.). Esto garantiza que cualquier diseÃ±ador pueda crear un tema turÃ­stico con CSS puro, Bootstrap o cualquier framework sin estar atado a la compilaciÃ³n de Tailwind del Core.

---

## 5. Arquitectura del TF Design System

El sistema de diseÃ±o propio se organiza en 4 capas conceptuales:

```text
1. FOUNDATIONS (Tokens)
   â”œâ”€â”€ Paleta de colores (Surface, Primary, Neutral, Danger, Success)
   â”œâ”€â”€ Escala tipogrÃ¡fica y espaciados modulares (mÃºltiplos de 4px/8px)
   â””â”€â”€ Radios de borde, elevaciones/sombras y z-index

2. COMPONENTS (Ãtomos y MolÃ©culas Vue 3)
   â”œâ”€â”€ TfButton, TfInput, TfSelect, TfSwitch, TfCheckbox
   â”œâ”€â”€ TfModal, TfDropdown, TfBadge, TfTooltip
   â””â”€â”€ TfTable, TfPagination, TfSkeleton, TfToast, TfEmptyState

3. PATTERNS (Patrones UX consolidados)
   â”œâ”€â”€ Barra de filtros asÃ­ncrona con debounce
   â”œâ”€â”€ Listado con skeletons de carga durante refrescos remotos
   â””â”€â”€ PatrÃ³n UX preferido: BotÃ³n -> Modal -> Formulario -> Backend -> Toast -> Cierre y Refresco

4. WORKSPACES (Vistas Administrativas)
   â”œâ”€â”€ Tour Editor (Ficha estructurada de itinerarios por dÃ­as)
   â”œâ”€â”€ Media Library (Gestor modal y centralizado de archivos)
   â””â”€â”€ TF Builder Canvas
```
