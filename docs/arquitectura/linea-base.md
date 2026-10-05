# Línea Base de Gobernanza y Principios — TravelFlow CMS (TF CMS)

## 1. Principio Operativo y Gobernanza

El proyecto TravelFlow CMS se rige permanentemente bajo la jerarquía de roles:

```text
ORLANDO
Define el producto, visión y prioridades de negocio
        ↓
CHATGPT
Dirige la ingeniería, define y aprueba la arquitectura,
fases de trabajo y gates de calidad técnica
        ↓
GEMINI
Investiga, implementa, prueba, documenta
y presenta evidencia verificable
```

## 2. Principios Técnicos Fundamentales

1. **Autonomía Total:** TravelFlow CMS es un producto autónomo. Su funcionamiento como gestor de contenidos y catálogo turístico no depende de que TravelFlow Next (TFN) esté configurado, licenciado o activo.
2. **Infraestructura vs Dominio:**
   - **Laravel 13** asume la infraestructura base: Routing, Request/Response, Middleware, Container/DI, CSRF, Sessions, Migrations, Validation, Scheduler, Queue, Mail, Filesystem y Logging.
   - **TF CMS** concentra la ingeniería en el dominio de negocio turístico, producto y arquitectura propia modular.
   - Queda prohibido construir sustitutos innecesarios de capacidades estándar de Laravel, pero también queda prohibido el desorden arquitectónico (prohibidos God Models, Controllers masivos o lógica en rutas).
3. **Simplicidad para el Usuario, Potencia en el Motor:**
   - *La complejidad pertenece al motor. La simplicidad pertenece al usuario.*
   - Componentes dinámicos, TF Builder estructurado (AST JSON) y Theme Engine desacoplado.
4. **Soberanía de Datos:**
   - **TF CMS** es la fuente de verdad única para contenido editorial: Tours, Destinos, Experiencias, Páginas, Blog, Media, SEO, Traducciones y Builder.
   - **TFN** será la futura fuente de verdad operacional: Salidas, disponibilidad en tiempo real, cupos, tarifas operativas, pasajeros y reservas.
5. **Cero Contaminación Legacy:**
   - Prohibición absoluta de reutilizar código, tablas, endpoints, sesiones o adaptadores del ecosistema legacy (Travel Flow v1 / TFP / TFL).
6. **No Adelantar Funcionalidad Sin Gate:**
   - Ninguna fase puede cerrarse sin evidencia verificable.
   - No se implementan funcionalidades de negocio hasta que su microfase sea formalmente instruida y aprobada por ChatGPT.
