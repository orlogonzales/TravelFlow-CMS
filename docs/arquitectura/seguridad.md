# Seguridad, AuditorÃ­a y Convenciones de API â€” TF CMS

**Documento:** `docs/arquitectura/seguridad.md`
**Estado:** `PROPUESTO` (Sujeto a aprobaciÃ³n formal de ChatGPT)
**VersiÃ³n:** 1.0 â€” Fase 0C

---

## 1. AuditorÃ­a Funcional vs Logging TÃ©cnico

Se establece una estricta separaciÃ³n de responsabilidades:

```text
LOGGING TÃ‰CNICO (Monolog)            â‰            AUDITORÃA FUNCIONAL (audit_logs)
----------------------------------               -----------------------------------
* Archivo: storage/logs/laravel.log              * Tabla relacional: audit_logs en MySQL
* Excepciones no controladas, fallos             * Trazabilidad de operaciones de negocio
  de base de datos, stack traces de error.         realizadas por usuarios y actores.
* Destinado a ingenieros de software.            * Destinado a administradores y gobernanza.
```

### Arquitectura de la Tabla `audit_logs`:
- `id`: `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `actor_type`: Tipo de actor (`HUMAN_USER`, `SYSTEM`, `SERVICE`, `API_CLIENT`)
- `actor_id`: ID del actor o usuario que ejecutÃ³ la acciÃ³n (nullable para acciones del sistema)
- `action`: Verbo de negocio (`created`, `updated`, `deleted`, `restored`, `login_success`, `login_failed`)
- `domain`: Dominio afectado (`Tour`, `Destination`, `Setting`, `User`, `Role`, `Media`)
- `auditable_type`: Nombre de clase de la entidad intervenida (`App\Domains\Tour\Models\Tour`)
- `auditable_id`: Clave primaria del registro intervenido
- `old_values`: Snapshot JSON de los valores antes de la modificaciÃ³n
- `new_values`: Snapshot JSON de los valores posteriores al cambio
- `ip_address`: DirecciÃ³n IP (anÃ³nima/hasheada si las regulaciones locales lo exigen)
- `user_agent`: Cabecera User-Agent del cliente
- `correlation_id`: Identificador UUIDv4 para trazar la solicitud de extremo a extremo
- `created_at`: Marca temporal exacta en UTC

---

## 2. Controles de Seguridad Obligatorios en el Backend

1. **InyecciÃ³n SQL:** Mitigada al 100% mediante el uso de Prepared Statements y Parameter Binding obligatorio a travÃ©s de Eloquent y Query Builder de Laravel 13.
2. **Cross-Site Request Forgery (CSRF):** Protegido mediante tokens aleatorios validados por el middleware `ValidateCsrfToken` en todas las peticiones `POST`, `PUT`, `PATCH` y `DELETE`.
3. **Cross-Site Scripting (XSS):**
   - En vistas pÃºblicas Blade SSR: Escape contextual nativo `{{ $data }}`.
   - En frontend administrativo Vue: Data binding reactivo `{{ }}` que trata el texto como nodo de texto DOM seguro.
   - Para contenido enriquecido HTML permitido (descripciones de tours): SanitizaciÃ³n mediante lista blanca estricta en el backend antes de persistir.
4. **ProtecciÃ³n contra Fuerza Bruta y Rate Limiting:**
   - Endpoint de autenticaciÃ³n protegido mediante `RateLimiter::for('login')` (mÃ¡ximo 5 intentos por minuto por combinaciÃ³n de IP/usuario, con bloqueo exponencial).
   - Endpoints pÃºblicos de formularios y API protegidos por lÃ­mites de peticiones por minuto.
5. **Carga Segura de Archivos (Biblioteca de Medios):**
   - ValidaciÃ³n estricta del tipo MIME real mediante la extensiÃ³n `fileinfo` (`finfo_file`), no por la extensiÃ³n declarada en la cabecera del cliente.
   - Lista blanca de extensiones permitidas (`jpg`, `jpeg`, `png`, `webp`, `avif`, `pdf`).
   - Renombrado de archivos mediante UUIDv4 o hash SHA-256 para evitar colisiones y ataques de Path Traversal (`../../`).
   - Bloqueo de ejecuciÃ³n de scripts en `/public/uploads/` mediante directivas de servidor Apache.
6. **Cabeceras HTTP de Seguridad:** InyecciÃ³n de `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin` y polÃ­ticas de Content-Security-Policy (CSP) en producciÃ³n.

---

## 3. Convenciones Oficiales de la API TF CMS (`/api/v1/`)

Toda comunicaciÃ³n JSON entre el frontend administrativo y el backend se rige bajo un contrato REST estandarizado:

### 3.1 Envelope EstÃ¡ndar de Respuesta Exitosa (`HTTP 200 / 201`)
```json
{
  "success": true,
  "status": 200,
  "message": "Recurso obtenido exitosamente",
  "data": {},
  "meta": {
    "correlation_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "timestamp": "2026-10-05T23:45:00Z",
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 45,
      "last_page": 3
    }
  },
  "errors": null
}
```

### 3.2 Envelope EstÃ¡ndar de Error (`HTTP 400 / 401 / 403 / 404 / 422 / 500`)
```json
{
  "success": false,
  "status": 422,
  "message": "Los datos proporcionados no son vÃ¡lidos.",
  "data": null,
  "meta": {
    "correlation_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "timestamp": "2026-10-05T23:45:00Z"
  },
  "errors": {
    "title": ["El tÃ­tulo del tour es obligatorio."],
    "slug": ["El slug ingresado ya existe."]
  }
}
```

---

## 4. Frontera Generacional e IntegraciÃ³n Futura con TravelFlow Next

- **Frontera vinculante:** TF CMS es un producto autÃ³nomo. No comparte cÃ³digo, sesiones, tablas ni contratos con la generaciÃ³n legacy (Travel Flow v1, TFP, TFL).
- **TravelFlow Next (TFN):** Proyecto futuro no implementado.
- Durante esta fase y las subsiguientes no se inventan endpoints ni tokens de TFN.
- La arquitectura queda desacoplada para admitir un futuro adaptador (`TravelFlowNextAdapter`) implementado Ãºnicamente tras contar con un contrato formal de API firmado por la DirecciÃ³n TÃ©cnica.
