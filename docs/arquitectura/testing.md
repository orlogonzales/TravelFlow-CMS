# Estrategia Integral de Testing â€” TF CMS

**Documento:** `docs/arquitectura/testing.md`
**Estado:** `PROPUESTO` (Sujeto a aprobaciÃ³n formal de ChatGPT)
**VersiÃ³n:** 1.0 â€” Fase 0C

---

## 1. PirÃ¡mide de Pruebas de TF CMS

Se define una suite de pruebas automatizadas por capas, garantizando cobertura continua sin sobrecargar el tiempo de desarrollo:

```text
               / \
              /   \
             / E2E \           â”€â”€â”€ Playwright (Flujos crÃ­ticos: Login, Guardar Tour)
            /â”€â”€â”€â”€â”€â”€â”€\
           / Feature \         â”€â”€â”€ PHPUnit Feature Tests (API Endpoints, RBAC, FormRequests)
          /â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\
         / Integration \       â”€â”€â”€ Pruebas de integraciÃ³n con base de datos real MySQL
        /â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\
       /   Unit Tests    \     â”€â”€â”€ PHPUnit Unit Tests (Actions, DTOs, Helpers, Reglas de negocio)
      /â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\
```

---

## 2. Estrategia de Bases de Datos para Testing: SQLite vs MySQL Real

Para evitar el grave riesgo de que **una suite que pase en SQLite oculte incompatibilidades o errores con MySQL**, se adopta un enfoque dual:

### 2.1 Pruebas de Desarrollo RÃ¡pido y CI (SQLite `:memory:`)
- **Uso:** Pruebas unitarias y de validaciÃ³n de controladores en tiempo de codificaciÃ³n Ã¡gil.
- **ConfiguraciÃ³n:** Definida en `phpunit.xml` (`<env name="DB_CONNECTION" value="sqlite"/>`, `<env name="DB_DATABASE" value=":memory:"/>`).
- **Ventaja:** EjecuciÃ³n en menos de 1 segundo para retroalimentaciÃ³n inmediata.
- **Aislamiento:** Cada prueba corre dentro del trait `RefreshDatabase` o `DatabaseTransactions`.

### 2.2 Pruebas de IntegraciÃ³n y Smoke Tests (MySQL Real `tf_cms_test`)
- **Uso:** ValidaciÃ³n previa a la entrega de microfases y gates de cierre.
- **Entorno:** Base de datos dedicada de pruebas en MySQL (`tf_cms_test`) con el mismo motor, versiÃ³n (MySQL 8.4) y collation (`utf8mb4_0900_ai_ci`) que el entorno local.
- **VerificaciÃ³n:** Comprueba la sintaxis de tipos especÃ­ficos de MySQL (JSON queries, expresiones regulares, Ã­ndices espaciales de mapas turÃ­sticos).

---

## 3. TipologÃ­a de Pruebas Automatizadas

1. **Unit Tests (Pruebas Unitarias):**
   - ValidaciÃ³n aislada de DTOs, validadores personalizados, cÃ¡lculo de precios y duraciÃ³n de tours, formateadores de moneda e internacionalizaciÃ³n.
2. **Feature Tests (Pruebas de Funcionalidad):**
   - InvocaciÃ³n HTTP a endpoints de la API (`$this->postJson('/api/v1/tours', $data)`).
   - VerificaciÃ³n de cÃ³digos de estado HTTP semÃ¡nticos (200, 201, 401, 403, 422).
   - VerificaciÃ³n del envelope JSON estandarizado.
3. **Authorization & Security Tests:**
   - ComprobaciÃ³n de que usuarios sin el rol o permiso adecuado reciben `403 Forbidden`.
   - Pruebas de rate limiting y bloqueo de fuerza bruta.
   - VerificaciÃ³n de tokens CSRF.
4. **Frontend Unit & Component Tests:**
   - Pruebas de componentes Vue 3 del **TF Design System** mediante **Vitest** y `@vue/test-utils` (ej. renderizado correcto de `<TfButton>`, estados de `<TfSkeleton>`).
