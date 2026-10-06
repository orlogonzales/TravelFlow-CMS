# Estrategia Integral de Testing — TF CMS

**Documento:** `docs/arquitectura/testing.md`
**Estado:** `DEFINIDO` (MySQL motor oficial único, SQLite descartado totalmente, suite estructurada y guard de seguridad)
**Versión:** 2.1 — Fase 0C.1B

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
- **Base de Datos de Testing:** `tf_cms_test` (MySQL 8.4.3). Base de datos dedicada exclusivamente a la ejecución automatizada de pruebas con transacciones o migraciones controladas.
  > **Nota de Fase 0C.1B:** La base de datos `tf_cms_test` queda definida a nivel conceptual y normativo; **NO se crea físicamente en MySQL durante esta fase documental**.

### 3.2 Barrera de Seguridad Obligatoria en `TestCase`
Queda terminantemente prohibido ejecutar pruebas destructivas (`migrate:fresh`, `db:wipe`, vaciado de tablas) sobre la base de datos de desarrollo `tf_cms`.
- Cuando se configure formalmente la infraestructura de testing en su microfase correspondiente, se implementará un guard estricto en el `TestCase` base de Laravel que abortará de inmediato la suite si la base de datos conectada no contiene el sufijo `_test` o no es explícitamente `tf_cms_test`.

---

## 4. Estado de `phpunit.xml` y Deuda Técnica del Skeleton

El archivo `phpunit.xml` heredado de la instalación inicial de Laravel 13 contiene provisionalmente:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```
- **Clasificación:**
  ```text
  CONFIGURACIÓN HEREDADA DEL SKELETON
  DEUDA TÉCNICA PENDIENTE DE CORRECCIÓN CONTROLADA
  ```
- **Regla de Fase 0C.1B:** No se modifica `phpunit.xml` en la presente fase para preservar la naturaleza puramente documental de 0C.1B.
- Su actualización hacia la conexión dedicada MySQL `tf_cms_test` se ejecutará formalmente cuando se autorice la microfase de infraestructura de testing.
