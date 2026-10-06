# Seguridad, Auditoría y Convenciones de API — TF CMS

**Documento:** `docs/arquitectura/seguridad.md`
**Estado:** `DEFINIDO` (Auditoría funcional vs Logging técnico, Controles de backend y Frontera generacional) / `PROPUESTO` (Convenciones de API pública)
**Versión:** 2.0 — Fase 0C.1B

---

## 1. Auditoría Funcional vs Logging Técnico (`DEFINIDO`)

Se establece una estricta separación arquitectónica entre eventos de infraestructura técnica y trazabilidad de negocio:

```text
LOGGING TÉCNICO (Monolog)            ≠           AUDITORÍA FUNCIONAL (audit_logs)
----------------------------------               -----------------------------------
* Destino: storage/logs/laravel.log              * Tabla relacional en MySQL
* Errores de PHP, excepciones no                 * Trazabilidad de operaciones de negocio
  capturadas, trazas de depuración.                ejecutadas por actores identificados.
* Público: Ingenieros de software y DevOps.      * Público: Administradores del CMS y auditoría.
```

### Principios de la Auditoría Funcional:
1. **Eventos Significativos de Dominio:** Registra creaciones, mutaciones críticas, eliminaciones, cambios de permisos y accesos administrativos.
2. **Privacidad y Seguridad Estricta:**
   > **REGLA VINCULANTE:** Queda terminantemente prohibido registrar en la auditoría contraseñas, hashes, tokens de autenticación, datos sensibles de pago o credenciales secretas.
3. **Inmutabilidad:** La tabla de auditoría sólo admite operaciones `INSERT`. Queda prohibido modificar o eliminar registros de auditoría funcional.
4. **Diseño No Congelado Prematuramente:** La definición exacta de campos y metadatos se ajustará en la microfase del dominio `Audit`, asegurando compatibilidad con actores (`HUMAN_USER`, `SYSTEM`, `SERVICE`, `INTEGRATION`).

---

## 2. Controles de Seguridad en el Backend

1. **Inyección SQL:** Prevenida al 100% mediante el uso exclusivo de Parameter Binding y Prepared Statements provistos por Eloquent y Query Builder de Laravel.
2. **Cross-Site Request Forgery (CSRF):** Protegido en peticiones estatales mediante el middleware nativo `ValidateCsrfToken` con rotación segura de tokens en sesión.
3. **Cross-Site Scripting (XSS):**
   - Vistas públicas Blade (SSR): Escape contextual automático mediante `{{ $variable }}`.
   - SPA Administrativa (Vue 3): Interpolación reactiva de texto por defecto. Sanitización estricta por lista blanca en backend para contenido HTML de editores enriquecidos.
4. **Credenciales y Criptografía:**
   - Contraseñas almacenadas con algoritmos resistentes: `Argon2id` o `Bcrypt` con factores de trabajo seguros.
   - Prohibición de almacenamiento de tokens o credenciales en `localStorage`/`sessionStorage` para el panel administrativo.
5. **Rate Limiting y Fuerza Bruta:**
   - Límites estrictos en endpoints de autenticación mediante `RateLimiter::for('login')` (máximo 5 intentos por minuto por IP/cuenta con bloqueo incremental).
6. **Carga Segura de Archivos:**
   - Validación obligatoria de tipo MIME en backend vía `finfo_file` (no confiar en la cabecera enviada por el cliente).
   - Lista blanca de extensiones permitidas.
   - Generación de nombres de archivo aleatorios/hasheados para prevenir Path Traversal (`../`).
   - Prevención de ejecución de scripts en directorios de carga en Apache/Nginx.
7. **Cabeceras HTTP de Seguridad:** Inyección de `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin` y directivas de seguridad para producción.

---

## 3. Convenciones de API: Distinción entre Endpoints Internos y API Pública

Se delimita formalmente la naturaleza de las interfaces HTTP:

### 3.1 Endpoints Internos del Admin (SPA First-Party)
- Son endpoints JSON directos orientados a la experiencia del panel administrativo (`/admin/api/...` o rutas internas de administración).
- No requieren obligatoriamente el prefijo ni el versionado rígido `/api/v1/`.
- Priorizan rendimiento, tipado y respuestas directas alineadas a los FormRequests y Resources de Laravel.

### 3.2 API Pública y Externa (`PROPUESTO`)
- Destinada a consumo externo desacoplado o integraciones autorizadas.
- Se propone el versionado estándar bajo `/api/v1/`.
- No se congela un envelope universal rígido para todos los casos de forma prematura; la estructura de payloads y metadatos (paginación, correlación) se formalizará cuando se implemente la capa de exposición externa.

---

## 4. Frontera Generacional e Integración Futura con TravelFlow Next

1. **Separación Generacional Estricta:**
   - TravelFlow CMS (TF CMS) es un producto independiente de nueva generación.
   - Queda totalmente desvinculado de la generación legacy (Travel Flow v1, TFP, TFL).
2. **TravelFlow Next (TFN, TFNP, TFNL):**
   - Son proyectos futuros independientes que actualmente **NO EXISTEN**.
   - **Clasificación:** `FUTURO` / `PENDIENTE DE TRAVELFLOW NEXT`.
   - **Prohibición:** Se elimina cualquier concepto de adaptador prematuro (`TravelFlowNextAdapter`), mocks de prueba, endpoints inventados o esquemas anticipados. No se implementará código de integración hasta que existan contratos de ingeniería formales aprobados por la Dirección Técnica.
