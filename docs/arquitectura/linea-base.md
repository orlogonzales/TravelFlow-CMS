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

## 3. Protocolo Permanente de Acceso Temporal para Orlando (Verificación Manual)

A partir de la primera microfase en que exista autenticación funcional y una interfaz navegable:

1. **Obligación de Entrega:** Cada informe de entrega con componentes navegables incluirá la sección `ACCESO TEMPORAL DE VERIFICACIÓN` con URL, usuario, contraseña temporal hasheada, rol y flujos exactos a probar.
2. **Propósito:** Permitir que Orlando compruebe personalmente el comportamiento del CMS en el navegador. La validación automatizada no sustituye la prueba humana.
3. **Seguridad:**
   - Contraseña de desarrollo generada específicamente, nunca en texto plano en la base de datos ni versionada en Git ni `.env.example`.
   - Prohibido hardcodear cuentas fijas o puertas traseras en código productivo (`if ($email === 'admin@...')`).
4. **Criterio de Cierre UI/UX:** Las fases con entregables visuales permanecerán en estado `PENDIENTE DE VALIDACIÓN VISUAL DE ORLANDO` hasta que Orlando confirme la prueba manual.
