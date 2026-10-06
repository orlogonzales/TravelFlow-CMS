# Arquitectura Modular Laravel y Flujo de Responsabilidades â€” TF CMS

**Documento:** `docs/arquitectura/arquitectura-laravel.md`
**Estado:** `PROPUESTO` (Sujeto a aprobaciÃ³n formal de ChatGPT)
**VersiÃ³n:** 1.0 â€” Fase 0C

---

## 1. Comparativa de Arquitecturas Modulares para TF CMS

Se evaluaron tres enfoques para estructurar el backend sobre Laravel 13.x:

| Criterio | OpciÃ³n A: Convencional por Tipo | OpciÃ³n B: Modular por Dominios en `app/` (Recomendada) | OpciÃ³n C: Paquetes / HMVC Aislados |
| :--- | :--- | :--- | :--- |
| **Estructura** | `app/Models/`, `app/Controllers/`, etc. | `app/Domains/{Domain}/` + `app/Http/` centralizado | MÃ³dulos totalmente independientes con ServiceProviders propios |
| **CohesiÃ³n de Dominio** | Baja (archivos de Tours dispersos en 6 carpetas) | **Alta** (Tours agrupa sus Models, Actions, DTOs y Policies) | Muy Alta (aislamiento extremo) |
| **Escalabilidad (1000+ tours)** | Tiende al caos con 80+ modelos en un solo directorio | **Excelente**; cada dominio gestiona su complejidad | Compleja por cruce de dependencias |
| **Acoplamiento al Framework** | 100% nativo | **100% nativo** (autoloader estÃ¡ndar PSR-4 `App\`) | Requiere paquetes externos (ej. `nwidart/laravel-modules`) |
| **Curva de Onboarding** | Muy baja | **Baja**; sigue convenciones estÃ¡ndar de Laravel | Alta; sobrecarga de configuraciÃ³n |
| **Riesgo de SobreingenierÃ­a**| MÃ­nimo, pero genera deuda tÃ©cnica rÃ¡pida | **Nulo**; pragmatismo equilibrado | **Alto**; ceremony innecesario |

### RecomendaciÃ³n TÃ©cnica: OpciÃ³n B (Pragmatismo por Dominios)

Se recomienda organizar el cÃ³digo de negocio en **Dominios Cohesivos dentro de `app/Domains/`**, manteniendo la capa HTTP (`app/Http/`) organizada por contexto de entrega (Admin API vs Web PÃºblica):

```text
app/
â”œâ”€â”€ Domains/                    # LÃ³gica de Negocio y Persistencia por Dominio
â”‚   â”œâ”€â”€ Tour/                   # Dominio Tours
â”‚   â”‚   â”œâ”€â”€ Actions/            # Operaciones de escritura (CreateTour, PublishTour)
â”‚   â”‚   â”œâ”€â”€ Data/               # Data Transfer Objects (TourData)
â”‚   â”‚   â”œâ”€â”€ Models/             # Modelos Eloquent (Tour, TourItinerary, TourPrice)
â”‚   â”‚   â”œâ”€â”€ Policies/           # AutorizaciÃ³n granular (TourPolicy)
â”‚   â”‚   â””â”€â”€ Queries/            # Consultas complejas y facetas (TourCatalogQuery)
â”‚   â”œâ”€â”€ Destination/            # Dominio Destinos
â”‚   â”œâ”€â”€ Experience/             # Dominio Experiencias
â”‚   â”œâ”€â”€ Content/                # PÃ¡ginas estÃ¡ticas y dinÃ¡micas, Blog
â”‚   â”œâ”€â”€ Media/                  # Biblioteca de Medios y Variantes
â”‚   â”œâ”€â”€ Builder/                # Motor TF Builder (AST JSON, Schemas)
â”‚   â”œâ”€â”€ Theme/                  # Motor de Temas y Manifests
â”‚   â”œâ”€â”€ Multilingual/           # Motor de InternacionalizaciÃ³n
â”‚   â”œâ”€â”€ Seo/                    # Metadatos, Schemas JSON-LD, Sitemaps
â”‚   â”œâ”€â”€ Setting/                # ConfiguraciÃ³n editable de Empresa y Sistema
â”‚   â”œâ”€â”€ User/                   # Identidad, Usuarios, Roles y Permisos
â”‚   â””â”€â”€ Audit/                  # Trazabilidad y Logs Funcionales
â”‚
â”œâ”€â”€ Http/                       # Capa de Entrada y Transporte HTTP
â”‚   â”œâ”€â”€ Controllers/
â”‚   â”‚   â”œâ”€â”€ Api/V1/             # Endpoints JSON para el Panel Administrativo Vue
â”‚   â”‚   â”‚   â”œâ”€â”€ TourController.php
â”‚   â”‚   â”‚   â””â”€â”€ ...
â”‚   â”‚   â””â”€â”€ Web/                # Controladores SSR para la Web PÃºblica
â”‚   â”‚       â”œâ”€â”€ TourWebController.php
â”‚   â”‚       â””â”€â”€ ...
â”‚   â”œâ”€â”€ Middleware/             # Pipeline de Seguridad, Auth, CSRF, RBAC
â”‚   â”œâ”€â”€ Requests/               # Form Requests tipados para validaciÃ³n de entrada
â”‚   â””â”€â”€ Resources/              # JsonResources / DTOs de salida para la API
â”‚
â”œâ”€â”€ Providers/                  # Service Providers de Laravel
â””â”€â”€ Support/                    # Helpers, Traits transversales y Excepciones base
```

---

## 2. Flujo de Responsabilidades (Rule of Responsibilities)

Principio rector:
> *Una capa existe porque tiene una responsabilidad real, no porque el diagrama se vea mÃ¡s empresarial.*

```text
  [HTTP Request]
        â”‚
        â–¼
   [Route & Middleware]   â”€â”€â–º Seguridad, CSRF, Rate Limiting, AutenticaciÃ³n y AutorizaciÃ³n preliminar
        â”‚
        â–¼
   [Form Request]         â”€â”€â–º ValidaciÃ³n tipada estricta, reglas de negocio de entrada y sanitizaciÃ³n
        â”‚
        â–¼
   [Controller]           â”€â”€â–º OrquestaciÃ³n pura (extrae datos validados, invoca Action, devuelve Response).
        â”‚                     Regla: Menos de 40 lÃ­neas por mÃ©todo; sin lÃ³gica de negocio ni SQL.
        â–¼
   [Action / Service]     â”€â”€â–º LÃ³gica de negocio soberana, transacciones ACID, disparo de eventos
        â”‚
        â–¼
   [Model / QueryObject]  â”€â”€â–º Persistencia Eloquent o consultas complejas optimizadas
        â”‚
        â–¼
   [Resource / DTO]       â”€â”€â–º TransformaciÃ³n de salida desacoplada del esquema fÃ­sico de base de datos
        â”‚
        â–¼
  [JSON / SSR Response]
```

### Matriz de Responsabilidades Claras:
- **Controller:** Orquestador de transporte HTTP. Prohibido ejecutar transacciones directas o construir consultas complejas en Ã©l.
- **Form Request:** Barrera de entrada. Si los datos son invÃ¡lidos, la peticiÃ³n se detiene inmediatamente con `422 Unprocessable Entity`.
- **Action:** Clase invocable de responsabilidad Ãºnica (`CreateTourAction`, `TranslateTourAction`). Ideal para operaciones de escritura con efectos colaterales.
- **Service:** Agrupador de lÃ³gica para operaciones que coordinan mÃºltiples dominios o servicios externos.
- **DTO (Data Transfer Object):** Estructuras inmutables tipadas (PHP 8.3 `readonly class`) para mover datos limpios entre capas sin manipular arrays asociativos genÃ©ricos.
- **Policy:** Reglas de autorizaciÃ³n ligadas a la identidad del usuario y la entidad a intervenir.
- **Job:** Tarea asÃ­ncrona enviada a la cola para no bloquear el ciclo de vida de la peticiÃ³n HTTP.

---

## 3. Convenciones de Eventos del Dominio

Regla de oro:
> *No convertir cada operaciÃ³n CRUD en un Event Bus.*

Los eventos de Laravel (`Event` + `Listener`) se reservan exclusivamente para desacoplar **efectos secundarios no crÃ­ticos para la respuesta inmediata**:
- **Uso vÃ¡lido:** `TourPublishedEvent` â”€â”€â–º Invalida cache editorial, actualiza `sitemap.xml`, notifica a webhooks autorizados.
- **Uso invÃ¡lido:** Disparar eventos para guardar un registro hijo dentro de la misma transacciÃ³n (genera trazabilidad opaca y dificulta el debugging).

---

## 4. Estrategia de ConfiguraciÃ³n (`.env` vs `config/` vs `settings`)

Se establece una estricta jerarquÃ­a de configuraciÃ³n en 3 niveles:

```text
1. VARIABLES DE ENTORNO (.env)
   â””â”€â”€ Infraestructura pura, credenciales de servidor, secrets de APIs, URLs de hosts.
       No editable por el usuario. Inmutable en tiempo de ejecuciÃ³n.

2. CONFIGURACIÃ“N TÃ‰CNICA (config/*.php)
   â””â”€â”€ Opciones de framework, drivers por defecto (session, cache, queue), lÃ­mites tÃ©cnicos.
       Controladas por el equipo de ingenierÃ­a en cÃ³digo versionado.

3. AJUSTES EDITABLES DE TF CMS (Tabla settings / Domain Setting)
   â””â”€â”€ ConfiguraciÃ³n de producto gestionable por Orlando/Administrador desde el panel:
       * Datos de la empresa (RazÃ³n social, RUC/TaxID, direcciÃ³n, telÃ©fonos).
       * Canales de contacto (WhatsApp oficial, enlaces a redes sociales).
       * Ajustes SEO globales (Meta title por defecto, OpenGraph fallback).
       * Moneda base referencial del CMS y formatos de fecha.
```
