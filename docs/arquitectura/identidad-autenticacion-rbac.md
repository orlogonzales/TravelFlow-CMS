# Identidad, AutenticaciÃ³n y AutorizaciÃ³n (RBAC) â€” TF CMS

**Documento:** `docs/arquitectura/identidad-autenticacion-rbac.md`
**Estado:** `PROPUESTO` (Sujeto a aprobaciÃ³n formal de ChatGPT)
**VersiÃ³n:** 1.0 â€” Fase 0C

---

## 1. Desacoplamiento de Identidad: Persona â‰  Usuario â‰  Actor

Se establece un modelo transversal limpio que evita confundir datos biogrÃ¡ficos con credenciales de acceso al sistema:

```text
       â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
       â”‚        PERSONA         â”‚ â”€â”€â”€ Identidad humana real (Nombre, Apellidos,
       â”‚   (Datos Humanos)      â”‚     Documento, TelÃ©fono, Foto, BiografÃ­a editorial)
       â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜
                   â”‚ 0..1
                   â–¼
       â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
       â”‚        USUARIO         â”‚ â”€â”€â”€ Credencial de acceso (Email/Login, Password Hash,
       â”‚   (Cuenta de Acceso)   â”‚     Estado, MFA, Rol asignado, SesiÃ³n activa)
       â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜
                   â”‚
                   â–¼
       â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
       â”‚     ROL Y PERMISOS     â”‚ â”€â”€â”€ Capacidades operativas autorizadas
       â”‚      (RBAC Core)       â”‚
       â””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜
```

### Ventajas del Modelo:
1. **Personas sin acceso:** Un guÃ­a turÃ­stico, un autor de blog o un conductor puede existir como `Persona` (para figurar en la ficha de un tour o post) sin necesidad de tener un `Usuario` con credenciales de login al CMS.
2. **Privacidad y GDPR / Habeas Data:** Facilita la anonimizaciÃ³n o actualizaciÃ³n de datos personales sin romper claves forÃ¡neas de autorÃ­a histÃ³rica.
3. **MÃºltiples perfiles futuros:** Una persona puede asumir diferentes roles en el ecosistema sin duplicar su identidad biogrÃ¡fica.
4. **Estado provisional de `users`:** La tabla por defecto de Laravel 13 (`users`) se utilizarÃ¡ como base de infraestructura de autenticaciÃ³n, vinculÃ¡ndose mediante `persona_id` a la tabla de personas en la microfase autorizada.

---

## 2. TipologÃ­a de Actores del Sistema

TF CMS clasifica a los actores que interactÃºan con el backend en 4 categorÃ­as explÃ­citas:

1. **`HUMAN_USER`:** Usuario humano autenticado mediante interfaz web o panel administrativo.
2. **`SYSTEM`:** Operaciones autÃ³nomas del sistema (ej. ejecuciÃ³n de tareas programadas en el Scheduler, auto-limpieza de cache).
3. **`SERVICE`:** Procesos de backend desacoplados o workers de colas.
4. **`API_CLIENT / INTEGRATION`:** Clientes externos autenticados mediante tokens criptogrÃ¡ficos especÃ­ficos o firmas HMAC (ej. futura integraciÃ³n con TravelFlow Next). **Prohibido crear usuarios humanos ficticios con contraseÃ±as falsas para integraciones tÃ©cnicas.**

---

## 3. AutenticaciÃ³n Administrativa: Opciones y RecomendaciÃ³n

Se analizÃ³ la estrategia de autenticaciÃ³n para el panel administrativo en Vue 3 alojado en el mismo origen (`https://app.tf-cms.test`):

| Criterio | OpciÃ³n A: Laravel Stateful Session Auth (Recomendada) | OpciÃ³n B: Laravel Sanctum Tokens (Bearer) | OpciÃ³n C: JWT Stateless Personalizado |
| :--- | :--- | :--- | :--- |
| **Mecanismo** | Cookies encriptadas HttpOnly, Secure, SameSite=Strict + X-XSRF-TOKEN | Bearer Token en cabecera HTTP `Authorization` | JSON Web Tokens firmados |
| **Almacenamiento en Cliente** | Gestionado por el navegador (Inaccesible por JavaScript) | `localStorage` o memoria (Vulnerable a robo por XSS) | `localStorage` o memoria |
| **ProtecciÃ³n CSRF** | Nativa mediante middleware `ValidateCsrfToken` de Laravel | Requiere endpoints de cookies o tokens dedicados | Sin protecciÃ³n CSRF nativa |
| **RevocaciÃ³n Inmediata** | DestrucciÃ³n instantÃ¡nea en la tabla `sessions` de MySQL | Requiere eliminar token en base de datos | DifÃ­cil de revocar sin blacklist |
| **Complejidad y Dependencias**| **Cero paquetes adicionales**; 100% nativo de Laravel 13 | Requiere `laravel/sanctum` | Alta complejidad de mantenimiento |
| **Seguridad General** | **MÃ¡xima** para aplicaciones same-origin | Media (riesgo de persistencia insegura) | Media/Baja |

### RecomendaciÃ³n TÃ©cnica: OpciÃ³n A (Stateful Session Auth Nativa)
Para el panel administrativo Vue 3 servido bajo el mismo dominio de Laravel, **la autenticaciÃ³n por sesiÃ³n stateful nativa de Laravel es la mÃ¡s segura, robusta y eficiente**. Elimina la necesidad de instalar paquetes externos adicionales y protege las credenciales contra ataques XSS al utilizar cookies con flag `HttpOnly`.

---

## 4. AutorizaciÃ³n y RBAC (Role-Based Access Control)

Se compararon tres enfoques para el control de acceso:

| Factor | OpciÃ³n 1: Laravel Nativo (Gates & Policies) | OpciÃ³n 2: Paquete `spatie/laravel-permission` | OpciÃ³n 3: Modelo RBAC Propio sobre Gates (Recomendada) |
| :--- | :--- | :--- | :--- |
| **Dependencias** | Ninguna | Paquete externo de terceros | **Ninguna (cÃ³digo propio de TF CMS)** |
| **Flexibilidad en DB** | EstÃ¡tico en cÃ³digo (Gates manuales) | DinÃ¡mico en DB con tablas preconfiguradas | **Totalmente dinÃ¡mico en DB**, adaptado a TF CMS |
| **Cache de Permisos** | N/A | Cache por etiquetas | **Cache integrado en driver de aplicaciÃ³n** |
| **AuditorÃ­a de Roles** | Manual | Compleja de extender | **Integrada nativamente con `audit_logs`** |
| **Curva de Aprendizaje**| Baja | Media | **Baja** (consume la interfaz estÃ¡ndar `$user->can()`) |

### RecomendaciÃ³n TÃ©cnica: OpciÃ³n 3 (RBAC Propio Ligero Integrado con Gates)
Implementar una estructura limpia de tablas relacionales:
- `roles` (`id`, `name`, `slug`, `description`, `is_system`)
- `permissions` (`id`, `name`, `slug`, `domain`, `description`)
- `role_permissions` (`role_id`, `permission_id`)
- `user_roles` (`user_id`, `role_id`)

En el `AppServiceProvider`, registrar dinÃ¡micamente los permisos en el `Gate` de Laravel. AsÃ­, toda la autorizaciÃ³n se comprueba mediante las funciones estÃ¡ndar del framework (`$user->can('tour.create')`, `@can`, o middleware de rutas `can:tour.create`), sin arrastrar dependencias pesadas de terceros.

---

## 5. Regla Permanente: El MenÃº NO es AutorizaciÃ³n

> **PRINCIPIO VINCULANTE:**
> *Ocultar una opciÃ³n del menÃº en la interfaz visual NO autoriza ni desautoriza una operaciÃ³n.*

1. El frontend administrativo (Vue 3) consultarÃ¡ los permisos del usuario para decidir si muestra u oculta botones de navegaciÃ³n, Ãºnicamente por ergonomÃ­a y experiencia de usuario (UX).
2. **El backend de Laravel 13 valida obligatoriamente la autorizaciÃ³n en cada endpoint** mediante Middleware y FormRequests/Policies.
3. Cualquier intento de invocaciÃ³n manual directa a una ruta de la API sin el permiso requerido responderÃ¡ ineludiblemente con **`HTTP 403 Forbidden`**.

---

## 6. PolÃ­tica Permanente de Acceso Temporal para Orlando (Apartados 42 al 54)

Conforme a la instrucciÃ³n oficial vinculante, se consolida la norma permanente para todas las fases de implementaciÃ³n que introduzcan componentes navegables:

1. **ActivaciÃ³n:** A partir de la primera microfase en que exista autenticaciÃ³n funcional y panel navegable, el informe de Gemini entregarÃ¡ obligatoriamente la secciÃ³n `ACCESO TEMPORAL DE VERIFICACIÃ“N`.
2. **Naturaleza:** Cuenta humana temporal exclusiva de desarrollo local, con contraseÃ±a generada aleatoriamente y hasheada (Argon2id/Bcrypt), **nunca versionada en Git ni expuesta en texto plano**.
3. **Flujos a Comprobar:** Gemini indicarÃ¡ con precisiÃ³n quÃ© pantallas, botones, modales y respuestas debe validar Orlando manualmente desde su navegador.
4. **Criterio de Cierre Visual:** Toda entrega visual quedarÃ¡ registrada como `PENDIENTE DE VALIDACIÃ“N VISUAL DE ORLANDO`. La fase no podrÃ¡ cerrarse hasta contar con la conformidad explÃ­cita de Orlando tras su prueba humana.
