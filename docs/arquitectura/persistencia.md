# Persistencia, Eloquent y Consultas Complejas â€” TF CMS

**Documento:** `docs/arquitectura/persistencia.md`
**Estado:** `PROPUESTO` (Sujeto a aprobaciÃ³n formal de ChatGPT)
**VersiÃ³n:** 1.0 â€” Fase 0C

---

## 1. PolÃ­tica Oficial de Eloquent

Eloquent serÃ¡ el ORM principal para la manipulaciÃ³n de entidades y relaciones del dominio de TF CMS, sujeto a las siguientes directrices obligatorias:

### 1.1 PrevenciÃ³n de Problemas de Rendimiento (N+1)
- En entornos locales y de testing, se activarÃ¡ de forma estricta:
  ```php
  Model::preventLazyLoading(!app()->isProduction());
  ```
  Esto lanzarÃ¡ una excepciÃ³n inmediata en tiempo de desarrollo si alguna vista o controlador intenta acceder a una relaciÃ³n sin `eager loading` previo (`with()`).

### 1.2 AsignaciÃ³n Masiva y Seguridad de Modelos
- **Prohibido:** El uso de `$guarded = []`.
- **Obligatorio:** Cada modelo Eloquent definirÃ¡ explÃ­citamente su array `$fillable` con los campos permitidos para escritura masiva.
- Los campos sensibles de control (`id`, `created_at`, `updated_at`, `deleted_at`, `created_by`, `updated_by`) jamÃ¡s formarÃ¡n parte de `$fillable`.

### 1.3 Tipado y Casts Nativos
- Se utilizarÃ¡ el mÃ©todo nativo de Laravel 13 `casts(): array` en lugar de la propiedad `$casts`, aprovechando las clases de cast nativas (`boolean`, `datetime`, `decimal:2`, `encrypted`, `AsArrayObject`, etc.).

### 1.4 Transacciones y Concurrencia
- Cualquier operaciÃ³n que involucre mÃ¡s de una sentencia de escritura debe ejecutarse de forma atÃ³mica dentro de una transacciÃ³n gestionada:
  ```php
  DB::transaction(function () use ($dto) {
      // lÃ³gica atÃ³mica de guardado
  });
  ```
- Para operaciones concurrentes crÃ­ticas de modificaciÃ³n de cupos o inventario en el futuro, se emplearÃ¡ `lockForUpdate()` sobre el registro a modificar.

---

## 2. Eloquent vs Query Builder vs Query Objects

Para garantizar que el catÃ¡logo escale sin degradaciÃ³n hasta mÃ¡s de 1,000 tours con mÃºltiples relaciones e itinerarios, se delimitan las responsabilidades:

| Necesidad de Acceso a Datos | Mecanismo Autorizado | JustificaciÃ³n TÃ©cnica |
| :--- | :--- | :--- |
| **Operaciones CRUD / Detalle de Entidad** | **Eloquent ORM** | HidrataciÃ³n completa, ejecuciÃ³n de casts, disparo de eventos del modelo y gestiÃ³n de relaciones directas. |
| **TF Collection Engine / Listados DinÃ¡micos** | **Query Objects + Query Builder** | Un Query Object especializado (ej. `TourCollectionQuery`) compila la consulta con filtros dinÃ¡micos, ordenamiento y joins sin sobrecargar la memoria hidratando modelos pesados. |
| **Reportes / EstadÃ­sticas Agregadas** | **Query Builder puro** | CÃ¡lculos (`COUNT`, `SUM`, `AVG`, `GROUP BY`) procesados directamente en MySQL. |
| **Consultas SQL Directas** | **`DB::select()` parametrizado** | Restringido exclusivamente a casos extremos donde el optimizador de MySQL requiera hints de Ã­ndices especÃ­ficos. Prohibida la concatenaciÃ³n de variables en SQL. |

---

## 3. Estrategia de Identificadores (Modelo HÃ­brido)

Se compararon 4 estrategias de identificaciÃ³n:

| Enfoque | Rendimiento Ãndices B-Tree | Seguridad / ExposiciÃ³n PÃºblica | TamaÃ±o en Disco | DecisiÃ³n TF CMS |
| :--- | :--- | :--- | :--- | :--- |
| **BIGINT AUTO_INCREMENT puro** | MÃ¡ximo (Claves secuenciales compactas de 8 bytes) | Baja (Expone IDs secuenciales predecibles en URLs) | MÃ­nimo | Solo para uso interno |
| **UUID v4 puro** | Pobre (FragmentaciÃ³n masiva de Ã­ndices B-Tree) | Alta (Completamente opaco e impredecible) | Alto (36 chars o 16 bytes binarios) | No recomendado |
| **ULID puro** | Bueno (Secuencial por timestamp + aleatoriedad) | Alta (Opaco, ordenable por tiempo) | Medio (26 chars alfanumÃ©ricos) | Excelente para APIs |
| **MODELO HÃBRIDO (Recomendado)** | **MÃ¡ximo rendimiento interno + MÃ¡xima seguridad pÃºblica** | **Excelente** (Claves internas protegidas) | **Ã“ptimo** | **RECOMENDADO** |

### RecomendaciÃ³n TÃ©cnica: Modelo HÃ­brido
1. **Clave Primaria FÃ­sica (Interna):** `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`. Garantiza mÃ¡xima velocidad en Ã­ndices primarios, joins de bases de datos y claves forÃ¡neas.
2. **Identificador PÃºblico / CanÃ³nico:**
   - Para contenido editorial (Tours, Destinos, Posts): El campo `slug` Ãºnico e indexado para URLs amigables con el SEO (ej. `/tours/camino-inca-clasico-4-dias`).
   - Para entidades administrativas o recursos de API: Un campo `ulid CHAR(26) UNIQUE NOT NULL` generado nativamente (`Str::ulid()`) para evitar exponer IDs numÃ©ricos en endpoints y proteger contra ataques de enumeraciÃ³n.

---

## 4. PolÃ­tica de EliminaciÃ³n: Soft Delete vs EliminaciÃ³n FÃ­sica

No se aplicarÃ¡ el trait `SoftDeletes` indiscriminadamente. Se establece la clasificaciÃ³n por dominio:

1. **Soft Delete (`deleted_at`):** Aplicado **Ãºnicamente a contenido editorial clave** donde una eliminaciÃ³n accidental representarÃ­a pÃ©rdida grave de trabajo o desindexaciÃ³n SEO inmediata (`tours`, `destinations`, `experiences`, `pages`, `posts`, `media`).
2. **EliminaciÃ³n FÃ­sica Definitiva (`HARD DELETE`):** Para tablas tÃ©cnicas, temporales o de ciclo de vida corto (`cache`, `sessions`, `password_reset_tokens`, `jobs`, `failed_jobs`, tablas intermedias pivote `role_permissions`).
3. **Estado / InactivaciÃ³n (`status`):** Para el ciclo de vida del contenido (`draft`, `review`, `published`, `archived`) o cuentas de usuario (`active`, `suspended`). La inactivaciÃ³n no debe confundirse con el borrado.
4. **HistÃ³rico Inmutable:** La tabla de auditorÃ­a (`audit_logs`) jamÃ¡s permite borrado ni actualizaciÃ³n (solo `INSERT`).

---

## 5. Fechas y Zonas Horarias

1. **Almacenamiento en Base de Datos:** **Estrictamente en UTC** (`DATETIME` / `TIMESTAMP` configurado en `config/app.php` con `'timezone' => 'UTC'`).
2. **PresentaciÃ³n al Usuario:** ConversiÃ³n en la capa de vista o API Resource a la zona horaria comercial configurada en los ajustes del sitio (ej. `America/Lima`).
3. **DiferenciaciÃ³n SemÃ¡ntica:**
   - *Timestamps tÃ©cnicos:* `created_at`, `updated_at`, `published_at` (Fecha y hora exacta en UTC).
   - *Fechas de itinerario / calendario:* Fechas de dÃ­a completo (ej. DÃ­a 1, DÃ­a 2 de un tour) almacenadas como `DATE` sin alteraciÃ³n horaria.

---

## 6. NormalizaciÃ³n de Datos de Entrada

1. **Strings y Textos:** EliminaciÃ³n automÃ¡tica de espacios sobrantes al inicio y final mediante middleware `TrimStrings`. ConversiÃ³n de cadenas vacÃ­as a `null` mediante `ConvertEmptyStringsToNull`.
2. **PreservaciÃ³n Editorial:** Prohibido forzar mayÃºsculas masivas sobre tÃ­tulos o descripciones. El contenido editorial debe preservar acentos, caracteres especiales y mayÃºsculas/minÃºsculas segÃºn la redacciÃ³n del usuario.
3. **Slugs:** Normalizados siempre en minÃºsculas, sin espacios ni caracteres especiales (`Str::slug($title, '-', $language)`).
4. **Correos ElectrÃ³nicos:** Normalizados en minÃºsculas y limpiados (`strtolower(trim($email))`).
5. **Moneda y Precios Referenciales:** Almacenados en base de datos como enteros en centavos (`BIGINT` o `DECIMAL(12, 2)` con cast tipado) para evitar errores de redondeo de punto flotante.

---

## 7. PolÃ­tica de Collation de MySQL / MariaDB

- **Entorno Local Actual:** MySQL 8.4 utiliza nativamente `utf8mb4_0900_ai_ci`.
- **Estrategia para ProducciÃ³n y Portabilidad:**
  Para asegurar que TF CMS pueda instalarse sin problemas tanto en servidores con MySQL 8.0+ como en entornos MariaDB 10.6+ o hosting compartido tradicional, la migraciÃ³n e instalador utilizarÃ¡ la collation estÃ¡ndar de Laravel:
  - Charset: `utf8mb4`
  - Collation recomendada en `config/database.php`: `utf8mb4_unicode_ci` (compatible universalmente con MySQL 5.7+, MySQL 8.0+ y MariaDB) o detecciÃ³n dinÃ¡mica en el futuro instalador segÃºn las capacidades del motor.
