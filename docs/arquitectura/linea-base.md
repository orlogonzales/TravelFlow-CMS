# LÃ­nea Base de Gobernanza y Principios â€” TravelFlow CMS (TF CMS)

## 1. Principio Operativo y Gobernanza

El proyecto TravelFlow CMS se rige permanentemente bajo la jerarquÃ­a de roles:

```text
ORLANDO
Define el producto, visiÃ³n y prioridades de negocio
        â†“
CHATGPT
Dirige la ingenierÃ­a, define y aprueba la arquitectura,
fases de trabajo y gates de calidad tÃ©cnica
        â†“
GEMINI
Investiga, implementa, prueba, documenta
y presenta evidencia verificable
```

## 2. Principios TÃ©cnicos Fundamentales

1. **AutonomÃ­a Total:** TravelFlow CMS es un producto autÃ³nomo. Su funcionamiento como gestor de contenidos y catÃ¡logo turÃ­stico no depende de que TravelFlow Next (TFN) estÃ© configurado, licenciado o activo.
2. **Infraestructura vs Dominio:**
   - **Laravel 13** asume la infraestructura base: Routing, Request/Response, Middleware, Container/DI, CSRF, Sessions, Migrations, Validation, Scheduler, Queue, Mail, Filesystem y Logging.
   - **TF CMS** concentra la ingenierÃ­a en el dominio de negocio turÃ­stico, producto y arquitectura propia modular.
   - Queda prohibido construir sustitutos innecesarios de capacidades estÃ¡ndar de Laravel, pero tambiÃ©n queda prohibido el desorden arquitectÃ³nico (prohibidos God Models, Controllers masivos o lÃ³gica en rutas).
3. **Simplicidad para el Usuario, Potencia en el Motor:**
   - *La complejidad pertenece al motor. La simplicidad pertenece al usuario.*
   - Componentes dinÃ¡micos, TF Builder estructurado (AST JSON) y Theme Engine desacoplado.
4. **SoberanÃ­a de Datos:**
   - **TF CMS** es la fuente de verdad Ãºnica para contenido editorial: Tours, Destinos, Experiencias, PÃ¡ginas, Blog, Media, SEO, Traducciones y Builder.
   - **TFN** serÃ¡ la futura fuente de verdad operacional: Salidas, disponibilidad en tiempo real, cupos, tarifas operativas, pasajeros y reservas.
5. **Cero ContaminaciÃ³n Legacy:**
   - ProhibiciÃ³n absoluta de reutilizar cÃ³digo, tablas, endpoints, sesiones o adaptadores del ecosistema legacy (Travel Flow v1 / TFP / TFL).
6. **No Adelantar Funcionalidad Sin Gate:**
   - Ninguna fase puede cerrarse sin evidencia verificable.
   - No se implementan funcionalidades de negocio hasta que su microfase sea formalmente instruida y aprobada por ChatGPT.

## 3. Protocolo Permanente de Acceso Temporal para Orlando (VerificaciÃ³n Manual)

A partir de la primera microfase en que exista autenticaciÃ³n funcional y una interfaz navegable:

1. **ObligaciÃ³n de Entrega:** Cada informe de entrega con componentes navegables incluirÃ¡ la secciÃ³n `ACCESO TEMPORAL DE VERIFICACIÃ“N` con URL, usuario, contraseÃ±a temporal hasheada, rol y flujos exactos a probar.
2. **PropÃ³sito:** Permitir que Orlando compruebe personalmente el comportamiento del CMS en el navegador. La validaciÃ³n automatizada no sustituye la prueba humana.
3. **Seguridad:**
   - ContraseÃ±a de desarrollo generada especÃ­ficamente, nunca en texto plano en la base de datos ni versionada en Git ni `.env.example`.
   - Prohibido hardcodear cuentas fijas o puertas traseras en cÃ³digo productivo (`if ($email === 'admin@...')`).
4. **Criterio de Cierre UI/UX:** Las fases con entregables visuales permanecerÃ¡n en estado `PENDIENTE DE VALIDACIÃ“N VISUAL DE ORLANDO` hasta que Orlando confirme la prueba manual.
