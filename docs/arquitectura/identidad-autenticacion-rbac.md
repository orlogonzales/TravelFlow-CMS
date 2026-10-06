# Identidad, Autenticación y Autorización (RBAC) — TF CMS

**Documento:** `docs/arquitectura/identidad-autenticacion-rbac.md`
**Estado:** `DEFINIDO` (Identidad desacoplada, RBAC propio dinámico, Menú ≠ Autorización y Protocolo Orlando) / `PROPUESTO PARA APROBACIÓN FINAL` (Sanctum Stateful Session/Cookie Auth)
**Versión:** 2.0 — Fase 0C.1B

---

## 1. Desacoplamiento de Identidad: Persona ≠ Usuario ≠ Actor

Se establece un modelo conceptual y transversal limpio que evita confundir la identidad biográfica humana con las credenciales de acceso técnico al sistema:

```text
       ┌────────────────────────┐
       │        PERSONA         │ ─── Identidad humana real (Nombre, Apellidos,
       │   (Datos Humanos)      │     Documento, Teléfono, Foto, Biografía editorial)
       └───────────┬────────────┘
                   │ 0..1  (Relación desacoplada, NO congelada a 1:1 físico rígido)
                   ▼
       ┌────────────────────────┐
       │        USUARIO         │ ─── Credencial de acceso (Email/Login, Password Hash,
       │   (Cuenta de Acceso)   │     Estado, MFA, Sesión activa)
       └───────────┬────────────┘
                   │ N:M
                   ▼
       ┌────────────────────────┐
       │     ROLES Y PERMISOS   │ ─── Capacidades operativas autorizadas
       │      (RBAC Core)       │
       └────────────────────────┘
```

### Principios y Reglas de Desacoplamiento:
1. **Sin Congelamiento 1:1 Físico Rígido:** La relación conceptual entre `Persona` y `Usuario` es flexible y desacoplada:
   - **Personas sin Usuario:** Un guía turístico, autor de contenido o chofer puede existir como `Persona` (para figurar en itinerarios o artículos editoriales) sin requerir una cuenta de usuario con credenciales de acceso al CMS.
   - **Usuarios sin Persona obligatoria:** Cuentas administrativas o técnicas iniciales no requieren estar atadas forzosamente a una ficha biográfica humana completa para operar.
2. **Privacidad y Protección de Datos (GDPR / Habeas Data):** Permite anonimizar, archivar o actualizar datos personales biográficos sin romper integridad referencial ni historial de auditoría de cuentas.
3. **Múltiples Perfiles Futuros:** Una persona podrá vincularse a distintos roles o registros dentro del ecosistema sin duplicar su identidad biográfica.
4. **Infraestructura Base de `users`:** La tabla estándar de Laravel 13 (`users`) se preserva como base de autenticación y se conectará de forma desacoplada con el dominio de personas cuando corresponda.

---

## 2. Tipología Oficial de Actores del Sistema

TF CMS clasifica a los actores que interactúan con el backend en 4 categorías formales:

1. **`HUMAN_USER` (o `USER`):** Usuario humano autenticado mediante la interfaz web o el panel administrativo del CMS.
2. **`SYSTEM`:** Operaciones autónomas del sistema (ej. ejecución de tareas programadas en el Laravel Scheduler, limpieza de logs, mantenimiento interno).
3. **`SERVICE`:** Procesos de backend desacoplados, workers de colas o tareas de procesamiento asíncrono.
4. **`INTEGRATION / API_CLIENT`:** Clientes o sistemas externos autenticados mediante tokens criptográficos específicos o firmas seguras.
   > **REGLA VINCULANTE:** Queda estrictamente prohibido crear usuarios humanos ficticios con contraseñas falsas para representar servicios técnicos o integraciones externas.

---

## 3. Autenticación Administrativa: Laravel Sanctum Stateful (`PROPUESTO PARA APROBACIÓN FINAL`)

Para la SPA administrativa desarrollada en Vue 3 y servida bajo el mismo origen (`https://app.tf-cms.test`), la estrategia oficial rectificada es:

### Mecanismo Oficial:
- **Laravel Sanctum en modo Stateful Cookie/Session Authentication:**
  - Emite cookies de sesión encriptadas con atributos estrictos: `HttpOnly`, `Secure` y `SameSite=Strict` o `Lax`.
  - Protección CSRF nativa y obligatoria mediante cookie `XSRF-TOKEN` y cabecera HTTP `X-XSRF-TOKEN`.
  - Sin emisión de Bearer tokens para el panel administrativo first-party.
  - Cero persistencia de tokens de autenticación en el cliente.

### Prohibición Estricta de Almacenamiento Inseguro:
> **PROHIBICIÓN TAJANTE:** Queda terminantemente prohibido almacenar credenciales, tokens de acceso o secretos del Admin en `localStorage` o `sessionStorage`. La sesión se mantiene exclusivamente a través de cookies de sesión `HttpOnly` gestionadas por el navegador y verificadas por el backend.

### Comparativa Arquitectónica:

| Criterio | Sanctum Stateful Session/Cookie (Propuesto) | Bearer Tokens en Headers | JWT Stateless Personalizado |
| :--- | :--- | :--- | :--- |
| **Mecanismo** | Cookies encriptadas HttpOnly + CSRF | Bearer Token en cabecera `Authorization` | JWT firmado en cabecera |
| **Almacenamiento Cliente** | Cookie gestionada por navegador (Inaccesible por JS) | `localStorage` o memoria (Vulnerable a XSS) | `localStorage` o memoria (Vulnerable a XSS) |
| **Protección CSRF** | Nativa mediante `ValidateCsrfToken` | Requiere mecanismos adicionales | Inexistente por defecto |
| **Revocación Inmediata** | Instantánea invalidando la sesión en backend | Requiere borrado de token en DB | Compleja (requiere blacklist) |
| **Seguridad General** | **Máxima para SPAs first-party same-origin** | Media (alto riesgo de exfiltración) | Media / Baja |

---

## 4. Autorización y RBAC Dinámico Propio (`DEFINIDO`)

Se adopta un modelo RBAC propio, dinámico y desacoplado, descartando paquetes externos pesados como `spatie/laravel-permission`:

### Estructura Relacional (N:M):
```text
USUARIOS (users)
       │ N
       ▼ M
ROLES (roles) ──────────────┐
       │ N                  │ Soporte multi-rol por usuario
       ▼ M                  │ (user_roles)
PERMISOS (permissions) ◄────┘
```

- **`roles`:** (`id`, `name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`)
- **`permissions`:** (`id`, `name`, `slug`, `domain`, `description`, `created_at`, `updated_at`)
- **`role_permissions`:** (`role_id`, `permission_id`)
- **`user_roles`:** (`user_id`, `role_id`) — Permite asignar múltiples roles a un usuario.

### Integración con Laravel Core:
- Registro dinámico de permisos en el `Gate` de Laravel a través de Service Providers.
- Comprobación nativa estándar en controladores, FormRequests y vistas: `$user->can('tour.create')`, políticas (`Policies`) y middleware de ruta (`can:...`).
- Cache inteligente de permisos en el driver de cache de la aplicación, invalidado automáticamente al mutar roles o permisos.

---

## 5. Regla Permanente: El Menú NO es Autorización (`DEFINIDO Y VINCULANTE`)

> **PRINCIPIO VINCULANTE:**
> *Ocultar una opción del menú en la interfaz visual NO autoriza ni desautoriza una operación. Ocultar es ergonomía (UX); la autorización es un mandato estricto del backend.*

1. **UX Ergonomía:** El frontend administrativo (Vue 3) oculta o muestra botones y enlaces según los permisos del usuario para evitar frustración visual.
2. **Validación Backend Obligatoria:** Todo endpoint interno de la API administrativa valida ineludiblemente la autorización del actor mediante Middleware y Policies/FormRequests.
3. **Respuesta Forzosa:** Cualquier llamada HTTP directa no autorizada responderá taxativamente con **`HTTP 403 Forbidden`**.

---

## 6. Protocolo Permanente de Acceso Temporal para Orlando (Reglas 42 al 54 — `DEFINIDO Y VINCULANTE`)

Se consolida la norma obligatoria para todas las fases de implementación que entreguen módulos navegables:

1. **Activación Condicionada:** La obligación se activa a partir de la primera microfase técnica en que exista autenticación funcional y panel navegable.
2. **Fase 0C.1B Exenta de Credenciales:** Durante la presente fase documental **NO se crean credenciales, usuarios ni vistas de login**.
3. **Naturaleza del Acceso Temporal:** En las fases aplicables, se generará una cuenta humana temporal local con contraseña aleatoria y hasheada (Argon2id/Bcrypt), **nunca versionada en Git ni expuesta en texto plano en repositorios**.
4. **Flujos a Comprobar:** Gemini detallará en su informe las URLs, pantallas y acciones exactas a verificar manualmente por Orlando desde el navegador.
5. **Criterio de Aprobación Visual:** Toda entrega con interfaz quedará registrada como `PENDIENTE DE VALIDACIÓN VISUAL DE ORLANDO`. La fase no se considerará cerrada hasta contar con su confirmación expresa.
