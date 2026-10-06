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

---

## 7. Módulo de Personas y Permisos Oficiales (Fase 1C.1)

En la **Fase 1C.1**, se incorpora la administración del núcleo de identidades humanas (`Persona`):

### 7.1 Catálogo de Permisos Incorporados:
- **`personas.ver`:** Permite consultar el listado y fichas de personas (`GET /api/admin/personas`, `GET /api/admin/personas/{id}`).
- **`personas.crear`:** Permite registrar nuevas identidades de personas (`POST /api/admin/personas`).
- **`personas.editar`:** Permite actualizar datos y estado de personas (`PUT/PATCH /api/admin/personas/{id}`).

> **REGLA DE GOBERNANZA VINCULANTE:**
> - El rol de sistema `admin` recibe explícitamente estos permisos mediante la tabla `role_permissions` a través del seeder reproducible `RbacPermissionSeeder`. Ningún permiso es implícito ni concedido automáticamente por el slug del rol.
> - Se preserva `Persona ≠ User`: la creación o edición de una `Persona` jamás crea ni muta cuentas de acceso `User`.
> - Prohibido Hard Delete: No se expone `DELETE` para personas. Las transiciones operativas se manejan mediante el enum `PersonaStatus` (`activo`, `inactivo`, `archivado`).
> - Detección de duplicidad documental: En `StorePersonaRequest` y `UpdatePersonaRequest`, si se suministra combinación de `tipo_documento` y `numero_documento` idéntica a una existente, se rechaza con `HTTP 422 Unprocessable Entity` para prevenir identidades duplicadas.

---

## 8. Módulo de Administración de Usuarios y Reglas de Seguridad (Fase 1C.2)

En la **Fase 1C.2**, se implementa la administración de cuentas de acceso (`User`) bajo un estricto modelo de seguridad soberano:

### 8.1 Catálogo Granular de Permisos Segregados:
- **`usuarios.ver`:** Consultar listado de usuarios, ficha individual con roles y permisos efectivos agregados, y catálogos de selección (`GET /api/admin/usuarios`, `GET /api/admin/usuarios/{id}`, `GET /api/admin/usuarios/personas-elegibles`, `GET /api/admin/usuarios/roles-disponibles`).
- **`usuarios.crear`:** Crear cuentas de acceso exigiendo el vínculo obligatorio con una Persona activa y sin cuenta previa (`POST /api/admin/usuarios`).
- **`usuarios.editar`:** Actualizar únicamente datos base de la cuenta (`name`, `email`) (`PUT /api/admin/usuarios/{id}`). No permite alterar roles, estados ni credenciales.
- **`usuarios.roles`:** Asignar y desasignar roles a cuentas de acceso (`PUT /api/admin/usuarios/{id}/roles`).
- **`usuarios.estado`:** Cambiar estado del usuario (`active`, `inactive`, `blocked`) (`PATCH /api/admin/usuarios/{id}/estado`).
- **`usuarios.password`:** Restablecer credenciales de acceso de un usuario (`PUT /api/admin/usuarios/{id}/password`).

### 8.2 Principios de Seguridad y Anti-Escalada (`DEFINIDO Y VINCULANTE`):
1. **Persona Obligatoria:** Toda cuenta creada desde el CRUD administrativo debe vincularse obligatoriamente a una Persona activa existente y libre de vínculo (`0..1 ↔ 0..1`). No se permite la creación de usuarios huérfanos desde el panel.
2. **Anti-Escalada Capability-Based (Cero Privilegio por Nombre de Rol):**
   - Un operador que posee `usuarios.roles` solo puede asignar roles cuyos permisos constituyan un **subconjunto estricto de sus propios permisos efectivos**:
     $$\text{Permisos}(\text{Rol Solicitado}) \subseteq \text{Permisos}(\text{Operador Autenticado})$$
   - Si el rol solicitado contiene aunque sea un permiso que el operador no posee, la asignación es rechazada inmediatamente con `HTTP 403 Forbidden` (`No tiene autorización para asignar un rol con mayores privilegios`).
   - Cero bypass basado en `$user->hasRole('admin')`. La seguridad reside en capacidades y conjuntos de permisos.
3. **Roles en Creación:**
   - La asignación de roles durante el alta (`StoreUsuarioRequest`) requiere expresamente que el operador posea `usuarios.roles` además de `usuarios.crear`. Si no lo posee, cualquier rol enviado es rechazado.
4. **Protección del Último Administrador Efectivo:**
   - Un "administrador efectivo" es cualquier usuario con `status = active` que posea los permisos combinados `admin.acceder` y `usuarios.roles`.
   - Se prohíbe inactivar, bloquear o retirar roles a un usuario si dicha operación dejaría al sistema con cero administradores efectivos.
5. **Protección Anti Self-Lockout:**
   - Ningún operador puede auto-inactivarse, auto-bloquearse ni retirarse a sí mismo el acceso administrativo o la capacidad de gestión de roles.
6. **Revocación Forzosa de Sesiones en Base de Datos:**
   - Al cambiar el estado de un usuario a `inactive` o `blocked`, o al restablecer su contraseña, el backend ejecuta la eliminación atómica de sus registros en la tabla `sessions`:
     `DB::table('sessions')->where('user_id', $id)->delete()`.
   - Provoca la invalidación inmediata de cualquier sesión activa en otros navegadores o dispositivos.
7. **Prohibición de Hard Delete:**
   - Queda estrictamente prohibida la eliminación física de registros de usuarios. No existe ruta ni método `DELETE` para usuarios.
