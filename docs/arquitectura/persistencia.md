# Persistencia, Eloquent y Consultas Complejas — TF CMS

**Documento:** `docs/arquitectura/persistencia.md`
**Estado:** `DEFINIDO` (MySQL motor oficial y SQLite descartado por Dirección Técnica)
**Versión:** 2.0 — Fase 0C.1

---

## 1. Política Oficial de Eloquent

Eloquent será el ORM principal para la manipulación de entidades y relaciones del dominio de TF CMS, sujeto a las siguientes directrices obligatorias:

### 1.1 Prevención de Problemas de Rendimiento (N+1)
- En entornos locales y de testing, se activará de forma estricta:
  ```php
  Model::preventLazyLoading(!app()->isProduction());
  ```
  Esto lanzará una excepción inmediata en tiempo de desarrollo si alguna vista o controlador intenta acceder a una relación sin `eager loading` previo (`with()`).

### 1.2 Asignación Masiva y Seguridad de Modelos
- **Prohibido:** El uso de `$guarded = []`.
- **Obligatorio:** Cada modelo Eloquent definirá explícitamente su array `$fillable` con los campos permitidos para escritura masiva.
- Los campos sensibles de control (`id`, `created_at`, `updated_at`, `deleted_at`, `created_by`, `updated_by`) jamás formarán parte de `$fillable`.

### 1.3 Tipado y Casts Nativos
- Se utilizará el método nativo de Laravel 13 `casts(): array` en lugar de la propiedad `$casts`, aprovechando las clases de cast nativas (`boolean`, `datetime`, `decimal:2`, `encrypted`, `AsArrayObject`, etc.).

### 1.4 Transacciones y Concurrencia
- Cualquier operación que involucre más de una sentencia de escritura debe ejecutarse de forma atómica dentro de una transacción gestionada:
  ```php
  DB::transaction(function () use ($dto) {
      // lógica atómica de guardado
  });
  ```
- Para operaciones concurrentes críticas de modificación de cupos o inventario en el futuro, se empleará `lockForUpdate()` sobre el registro a modificar.

---

## 2. Eloquent vs Query Builder vs Query Objects

Para garantizar que el catálogo escale sin degradación hasta más de 1,000 tours con múltiples relaciones e itinerarios, se delimitan las responsabilidades:

| Necesidad de Acceso a Datos | Mecanismo Autorizado | Justificación Técnica |
| :--- | :--- | :--- |
| **Operaciones CRUD / Detalle de Entidad** | **Eloquent ORM** | Hidratación completa, ejecución de casts, disparo de eventos del modelo y gestión de relaciones directas. |
| **TF Collection Engine / Listados Dinámicos** | **Query Objects + Query Builder** | Un Query Object especializado (ej. `TourCollectionQuery`) compila la consulta con filtros dinámicos, ordenamiento y joins sin sobrecargar la memoria hidratando modelos pesados. |
| **Reportes / Estadísticas Agregadas** | **Query Builder puro** | Cálculos (`COUNT`, `SUM`, `AVG`, `GROUP BY`) procesados directamente en MySQL. |
| **Consultas SQL Directas** | **`DB::select()` parametrizado** | Restringido exclusivamente a casos extremos donde el optimizador de MySQL requiera hints de índices específicos. Prohibida la concatenación de variables en SQL. |

---

## 3. Estrategia de Identificadores (Modelo Híbrido)

Se establece el Modelo Híbrido:
1. **Clave Primaria Física (Interna):** `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`. Garantiza máxima velocidad en índices primarios, joins de bases de datos y claves foráneas en MySQL.
2. **Identificador Público / Canónico:**
   - Para contenido editorial (Tours, Destinos, Posts): El campo `slug` único e indexado para URLs amigables con el SEO (ej. `/tours/camino-inca-clasico-4-dias`).
   - Para entidades administrativas o recursos de API: Un campo `ulid CHAR(26) UNIQUE NOT NULL` generado nativamente (`Str::ulid()`) para evitar exponer IDs numéricos en endpoints y proteger contra ataques de enumeración.

---

## 4. Política de Eliminación: Soft Delete vs Eliminación Física

No se aplicará el trait `SoftDeletes` indiscriminadamente. Se establece la clasificación por dominio:

1. **Soft Delete (`deleted_at`):** Aplicado **únicamente a contenido editorial clave** donde una eliminación accidental representaría pérdida grave de trabajo o desindexación SEO inmediata (`tours`, `destinations`, `experiences`, `pages`, `posts`, `media`).
2. **Eliminación Física Definitiva (`HARD DELETE`):** Para tablas técnicas, temporales o de ciclo de vida corto (`cache`, `sessions`, `password_reset_tokens`, `jobs`, `failed_jobs`, tablas intermedias pivote `role_permissions`).
3. **Estado / Inactivación (`status`):** Para el ciclo de vida del contenido (`draft`, `review`, `published`, `archived`) o cuentas de usuario (`active`, `suspended`). La inactivación no debe confundirse con el borrado.
4. **Histórico Inmutable:** La tabla de auditoría (`audit_logs`) jamás permite borrado ni actualización (solo `INSERT`).

---

## 5. Fechas y Zonas Horarias

1. **Almacenamiento en Base de Datos:** **Estrictamente en UTC** (`DATETIME` / `TIMESTAMP` configurado en `config/app.php` con `'timezone' => 'UTC'`).
2. **Presentación al Usuario:** Conversión en la capa de vista o API Resource a la zona horaria comercial configurada en los ajustes del sitio (ej. `America/Lima`).
3. **Diferenciación Semántica:**
   - *Timestamps técnicos:* `created_at`, `updated_at`, `published_at` (Fecha y hora exacta en UTC).
   - *Fechas de itinerario / calendario:* Fechas de día completo (ej. Día 1, Día 2 de un tour) almacenadas como `DATE` sin alteración horaria.

---

## 6. Normalización de Datos de Entrada

1. **Strings y Textos:** Eliminación automática de espacios sobrantes al inicio y final mediante middleware `TrimStrings`. Conversión de cadenas vacías a `null` mediante `ConvertEmptyStringsToNull`.
2. **Preservación Editorial:** Prohibido forzar mayúsculas masivas sobre títulos o descripciones. El contenido editorial debe preservar acentos, caracteres especiales y mayúsculas/minúsculas según la redacción del usuario.
3. **Slugs:** Normalizados siempre en minúsculas, sin espacios ni caracteres especiales (`Str::slug($title, '-', $language)`).
4. **Correos Electrónicos:** Normalizados en minúsculas y limpiados (`strtolower(trim($email))`).
5. **Moneda y Precios Referenciales:** Almacenados en base de datos como enteros en centavos (`BIGINT` o `DECIMAL(12, 2)` con cast tipado) para evitar errores de redondeo de punto flotante.

---

## 7. Motor de Base de Datos y Política de Collation

### 7.1 MySQL Oficial (`DEFINIDO`) y SQLite (`DESCARTADO`)
- **Motor Oficial:** **MySQL** exclusivamente.
- **SQLite:** Descartado en todas sus formas (no en desarrollo, no en pruebas, no en memoria).
- **MariaDB:** Clasificado como **`NO HOMOLOGADO TODAVÍA`**. TF CMS se diseña y optimiza oficialmente para MySQL. No se asume compatibilidad automática con MariaDB ni se introducen compromisos arquitectónicos restrictivos sin una matriz de pruebas que lo demuestre formalmente en el futuro.

### 7.2 Collation
- **Entorno Local y Oficial:** `utf8mb4_0900_ai_ci` (MySQL 8.4 nativo).
- Se mantiene esta collation moderna Unicode 9.0 para la base local y para la futura base de testing `tf_cms_test`.
- La política de versiones mínimas de MySQL y collation final para el instalador se definirá antes de la fase del instalador productivo.
