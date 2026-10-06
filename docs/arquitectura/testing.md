# Estrategia Integral de Testing — TF CMS

**Documento:** `docs/arquitectura/testing.md`
**Estado:** `DEFINIDO` (MySQL exclusivo y descarte total de SQLite fijados por Dirección Técnica)
**Versión:** 2.0 — Fase 0C.1

---

## 1. Principio Vinculante de Consistencia de Entornos

> **REGLA PERMANENTE DE INGENIERÍA:**
> *No utilizar un motor de base de datos distinto en testing para simular el comportamiento que producción ejecutará sobre MySQL.*

Queda terminantemente descartado el uso de SQLite (incluyendo SQLite en memoria `:memory:` o archivos `.sqlite`) para homologar pruebas en TravelFlow CMS.

### Justificación de la Eliminación de SQLite:
1. **Divergencias en tipos y funciones:** SQLite no emula con fidelidad el comportamiento de MySQL 8.4 en tipos JSON, constraints de claves foráneas, índices espaciales, bloqueos de concurrencia (`FOR UPDATE`) ni expresiones regulares.
2. **Falsos positivos / negativos:** Una prueba que pasa en SQLite puede fallar en producción sobre MySQL por diferencias de collation o sintaxis SQL.
3. **Consistencia total:**
   ```text
   DESARROLLO ──► MySQL (tf_cms)
   TESTING     ──► MySQL (tf_cms_test)
   STAGING     ──► MySQL
   PRODUCCIÓN  ──► MySQL
   ```

---

## 2. Nueva Política de Pruebas Automatizadas

Se define una suite rigurosa dividida por responsabilidades:

1. **Unit Tests (Pruebas Unitarias):**
   - **Sin base de datos cuando sea posible.**
   - Pruebas aisladas y puras para Actions, DTOs, validadores, cálculo de tarifas, duraciones de tours, formateadores de moneda y utilidades.
   - Ejecución ultrarrápida sin sobrecarga de I/O.
2. **Feature & Database Tests:**
   - **Ejecutadas estrictamente sobre MySQL `tf_cms_test`.**
   - Validación de endpoints de la API REST (`/api/v1/`), FormRequests, respuestas JSON tipadas y persistencia real de modelos.
3. **Authorization & Security Tests:**
   - **Ejecutadas sobre MySQL `tf_cms_test` cuando requieran persistencia.**
   - Validación de Gates, Policies y respuesta `403 Forbidden` ante accesos no autorizados.
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

### 3.2 Barrera de Seguridad Obligatoria
Queda terminantemente prohibido ejecutar pruebas destructivas (`migrate:fresh`, `db:wipe`, borrado masivo) sobre `tf_cms`.
- Cuando se configure formalmente la infraestructura de testing en su microfase correspondiente, se implementará un guard en el `TestCase` base de Laravel que abortará la ejecución de pruebas si la base de datos conectada no contiene el sufijo `_test` o no es explícitamente `tf_cms_test`.

---

## 4. Estado de `phpunit.xml`

La configuración actual de `phpunit.xml` heredada del esqueleto de Laravel contiene provisionalmente:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```
- **Clasificación:**
  ```text
  CONFIGURACIÓN HEREDADA DEL SKELETON
  PENDIENTE DE CORRECCIÓN CONTROLADA
  ```
- **Regla de 0C.1:** No se modifica `phpunit.xml` en esta microfase para preservar la naturaleza estrictamente documental de 0C.1.
- Su actualización hacia la conexión dedicada MySQL `tf_cms_test` se realizará de forma controlada cuando se autorice la microfase de infraestructura de testing.
