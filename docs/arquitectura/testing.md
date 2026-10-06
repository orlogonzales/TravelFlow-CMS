# Estrategia Integral de Testing — TF CMS

**Documento:** `docs/arquitectura/testing.md`
**Estado:** `DEFINIDO E IMPLEMENTADO` (MySQL exclusivo, tf_cms_test creada, phpunit.xml configurado y guard de seguridad verificado)
**Versión:** 3.0 — Fase 0D

---

## 1. Principio Vinculante de Consistencia de Entornos

> **REGLA PERMANENTE DE INGENIERÍA:**
> *Queda prohibido utilizar un motor de base de datos distinto en testing para simular el comportamiento que producción ejecutará sobre MySQL.*

Queda terminantemente descartado el uso de SQLite (incluyendo SQLite en memoria `:memory:` o archivos `.sqlite`) para ejecutar pruebas en TravelFlow CMS.

### Justificación de la Eliminación de SQLite:
1. **Divergencias en Tipos y Funciones:** SQLite no replica fielmente el comportamiento de MySQL 8.4 en tipos JSON, constraints de claves foráneas, índices espaciales, bloqueos de concurrencia (`FOR UPDATE`) ni expresiones regulares.
2. **Falsos Positivos y Negativos:** Una prueba que pasa en SQLite puede fallar en producción sobre MySQL por diferencias de collation, sintaxis SQL o semántica transaccional.
3. **Consistencia Total de Entornos:**
   ```text
   DESARROLLO ──► MySQL (tf_cms)
   TESTING     ──► MySQL (tf_cms_test)
   STAGING     ──► MySQL
   PRODUCCIÓN  ──► MySQL
   ```

---

## 2. Tipología y Estructura de la Suite de Pruebas

Se define una suite rigurosa dividida por responsabilidades claras:

1. **Unit Tests (Pruebas Unitarias):**
   - **Sin base de datos cuando sea técnicamente posible.**
   - Pruebas aisladas y puras para Actions, DTOs, validadores, cálculo de tarifas, duraciones de itinerarios, formateadores de moneda y utilidades.
   - Ejecución ultrarrápida sin sobrecarga de operaciones I/O.
2. **Feature & Database Tests:**
   - **Ejecutadas estrictamente sobre MySQL `tf_cms_test`.**
   - Validación de endpoints de la API interna del Admin, endpoints públicos, FormRequests, respuestas tipadas y persistencia real de modelos.
3. **Authorization & Security Tests:**
   - **Ejecutadas sobre MySQL `tf_cms_test` cuando requieran persistencia.**
   - Validación de Gates, Policies y respuesta taxativa `HTTP 403 Forbidden` ante accesos no autorizados.
4. **Integration Tests:**
   - **Ejecutadas sobre MySQL `tf_cms_test`.**
   - Verificación de migraciones, transacciones ACID, integridad referencial y bloqueos concurrentes.
5. **Frontend Unit & Component Tests:**
   - Pruebas de componentes Vue 3 del **TF Design System** (usando Vitest y `@vue/test-utils`).

---

## 3. Separación de Bases de Datos y Protección Contra Pruebas Destructivas

### 3.1 Entornos Separados
- **Base de Datos de Desarrollo:** `tf_cms` (MySQL 8.4.3). Contiene el estado local de trabajo.
- **Base de Datos de Testing:** `tf_cms_test` (MySQL 8.4.3). Base de datos dedicada exclusivamente a la ejecución automatizada de pruebas, creada físicamente en MySQL en la Fase 0D con charset `utf8mb4` y collation `utf8mb4_0900_ai_ci`.

### 3.2 Barrera de Seguridad Implementada en `TestCase` (`IMPLEMENTADO EN FASE 0D`)
Queda terminantemente prohibido ejecutar pruebas destructivas (`migrate:fresh`, `db:wipe`, vaciado de tablas) sobre la base de datos de desarrollo `tf_cms`.
- En `tests/TestCase.php` se implementó el guard `ensureTestingDatabase()` que verifica en cada test:
  1. `config('app.env') === 'testing'`.
  2. `DB::connection()->getDatabaseName() === 'tf_cms_test'`.
- Si alguna condición no se cumple, el guard aborta la suite con `RuntimeException`. Su eficacia está garantizada mediante tests automatizados en `tests/Feature/SecurityGuardTest.php`.

---

## 4. Configuración Oficial de `phpunit.xml` (`IMPLEMENTADO EN FASE 0D`)

La configuración SQLite heredada del esqueleto de Laravel fue retirada en la Fase 0D y sustituida por la conexión oficial MySQL:
```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_HOST" value="127.0.0.1"/>
<env name="DB_PORT" value="3306"/>
<env name="DB_DATABASE" value="tf_cms_test"/>
<env name="DB_USERNAME" value="root"/>
<env name="DB_PASSWORD" value=""/>
```
- La suite de pruebas ejecuta todas sus operaciones contra MySQL `tf_cms_test` sin depender de SQLite ni bases en memoria.
- Se mantiene `.env.testing.example` como plantilla segura y reproducible para otros entornos de desarrollo.
