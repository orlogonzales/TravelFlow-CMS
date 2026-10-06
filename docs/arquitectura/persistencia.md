# Persistencia, Eloquent y Estrategia de Datos — TF CMS

**Documento:** `docs/arquitectura/persistencia.md`
**Estado:** `DEFINIDO` (MySQL motor único oficial, SQLite descartado, transacciones por atomicidad y normas de persistencia)
**Versión:** 2.1 — Fase 0C.1B

---

## 1. Política Oficial de Eloquent y Modelos

Eloquent es el ORM principal para la modelación de entidades, relaciones y reglas de dominio de TravelFlow CMS, sujeto a directrices de ingeniería rigurosas:

### 1.1 Prevención de Consultas N+1
- En entornos locales de desarrollo y testing se activará de forma estricta:
  ```php
  Model::preventLazyLoading(!app()->isProduction());
  ```
  Esto garantiza la detección temprana de accesos a relaciones sin carga previa (`eager loading` mediante `with()`).

### 1.2 Control Seguro de Asignación Masiva
- **Prohibición Taxativa:** Queda terminantemente prohibido el uso indiscriminado de `protected $guarded = []`.
- **Control Seguro:** Los modelos deben proteger rigurosamente sus atributos para evitar sobreescritura accidental o maliciosa. Si bien `$fillable` es la convención predeterminada de seguridad, no se impone como dogma ciego frente a DTOs tipados o métodos dedicados de mutación (`$model->updateFromDto(...)`).
- Los atributos de control técnico (`id`, `created_at`, `updated_at`, `deleted_at`, `created_by`, `updated_by`) jamás podrán ser asignados masivamente desde payloads sin procesar.

### 1.3 Tipado y Casts Nativos de Laravel 13
- Se utilizará el método nativo `casts(): array` en lugar de la propiedad obsoleta `$casts`, empleando casts nativos de PHP/Laravel (`boolean`, `datetime`, `decimal:2`, `encrypted`, `AsArrayObject`, enums respaldados, etc.).

### 1.4 Transacciones Atómicas por Consistencia
- Se erradica la regla simplista de conteo de queries. El principio rector es:
  > **TODA OPERACIÓN QUE REQUIERA ATOMICIDAD ENTRE MODIFICACIONES RELACIONADAS DEBE EJECUTARSE DENTRO DE UNA TRANSACCIÓN GESTIONADA (`DB::transaction(...)`).**
- La necesidad de preservar la integridad y coherencia referencial en MySQL ante fallos parciales (ej. creación de un Tour y sus bloques de Itinerario) es lo que exige el uso de transacciones.
- Para operaciones de alta concurrencia o modificación de cupos/inventario futuro, se empleará bloqueo pesimista controlado (`lockForUpdate()`).

---

## 2. Eloquent, Query Builder y Query Objects

Se delimita el uso de las herramientas de acceso a datos según la responsabilidad técnica y el rendimiento:

1. **Eloquent ORM:** Operaciones de dominio, carga de agregados, relaciones directas, casts tipados y persistencia orientada a objetos.
2. **Query Objects:** Se utilizarán **únicamente cuando la complejidad de filtrado dinámico, la reutilización o la necesidad de testing aislado lo justifiquen técnicamente**. No se impone una regla dogmática obligatoria para todo el Collection Engine ni se congelan nombres de clases prematuramente.
3. **Query Builder puro:** Agregaciones estadísticas, reportes masivos (`COUNT`, `SUM`, `AVG`, `GROUP BY`) o transformaciones por lotes donde la hidratación de modelos Eloquent generaría sobrecarga de memoria innecesaria.
4. **Sentencias SQL Parametrizadas (`DB::select`):** Restringidas exclusivamente a consultas altamente optimizadas que requieran hints de índices específicos de MySQL. Queda estrictamente prohibida la concatenación de variables en sentencias SQL.

---

## 3. Estrategia de Identificadores

1. **Clave Primaria Física (Interna):** `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` en MySQL. Garantiza óptima velocidad en índices primarios (B-Tree), claves foráneas y uniones relacionales.
2. **Identificadores Públicos y SEO:**
   - Contenido editorial público (Tours, Destinos, Posts): Campo `slug` único e indexado para URLs amigables con motores de búsqueda.
   - Identificadores técnicos públicos: `ULID` (`CHAR(26)`) donde resulte técnicamente ventajoso proteger contra ataques de enumeración en endpoints o referencias externas, sin imponerlo como dogma universal para cada tabla del sistema.

---

## 4. Política de Eliminación de Datos

La estrategia de eliminación se evalúa y define por entidad y naturaleza operativa:

1. **Soft Delete (`deleted_at`):** Se evalúa para entidades editoriales o de negocio críticas donde la eliminación accidental cause daño operativo inmediato o desindexación SEO no deseada. No se impone una lista rígida y universal previa.
2. **Eliminación Física Definitiva (`HARD DELETE`):** Para tablas de infraestructura técnica, registros temporales o relaciones pivote intermedias (`sessions`, `cache`, `password_reset_tokens`, `jobs`, `failed_jobs`, `role_permissions`).
3. **Estado / Inactivación (`status`):** Para el ciclo de vida del contenido (`draft`, `published`, `archived`) o cuentas de acceso (`active`, `suspended`). La inactivación no equivale a borrado.
4. **Histórico Inmutable:** Registros de auditoría funcional y transacciones contables (solo permiten operaciones de inserción `INSERT`).

---

## 5. Fechas y Moneda

1. **Zonas Horarias:**
   - **Almacenamiento:** Estrictamente en **UTC** (`DATETIME` / `TIMESTAMP`).
   - **Presentación:** Convertido a la zona horaria del sitio/usuario en la capa de salida.
   - **Fechas sin Hora:** Almacenadas como `DATE` (ej. días de itinerario, vigencias de fechas).
2. **Moneda y Valores Financieros:**
   - > **REGLA VINCULANTE:** PROHIBIDO EL USO DE `FLOAT` O `DOUBLE` PARA MONEDA.
   - Todo valor monetario debe almacenarse como entero en centavos (`BIGINT`) o como tipo de punto fijo `DECIMAL(12, 2)` / `DECIMAL(14, 4)` con casts tipados para evitar pérdida de precisión y errores de redondeo.

---

## 6. Motor de Base de Datos y Collation

### 6.1 MySQL Oficial (`DEFINIDO`) y SQLite (`DESCARTADO`)
- **Motor Oficial Único:** **MySQL** para desarrollo local (`tf_cms`), pruebas automatizadas (`tf_cms_test`), staging y producción.
- **SQLite:** **DESCARTADO** en todas sus modalidades (incluido `:memory:`).
- **MariaDB:** Clasificado formalmente como **`NO HOMOLOGADO TODAVÍA`**. TF CMS se desarrolla y optimiza para MySQL. No se asume compatibilidad implícita sin pruebas de laboratorio futuras.

### 6.2 Collation Oficial
- `utf8mb4_0900_ai_ci` (MySQL 8.4 nativo Unicode 9.0).
- La política de versiones mínimas de MySQL para el instalador comercial se fijará formalmente antes de la fase de despliegue productivo.
