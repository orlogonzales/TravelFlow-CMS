# Mapa del Ecosistema y Frontera Generacional — TF CMS

**Documento:** `docs/arquitectura/ecosistema.md`
**Estado:** `DEFINIDO` (Separación generacional estricta y autonomía de TF CMS)
**Versión:** 2.0 — Fase 0C.1B

---

## 1. Clasificación Generacional

El ecosistema TravelFlow se divide estrictamente en dos generaciones independientes e incomunicadas a nivel de código:

```text
===================================================================
1. ECOSISTEMA ACTUAL (LEGACY) — FUERA DEL ALCANCE DE TF CMS
===================================================================
- TRAVEL FLOW v1 (Sistema actual existente / monolítico)
- TFP legacy (Plugin actual de WordPress)
- TFL legacy (Licenciamiento actual)

* Estado: EXISTENTE.
* Regla: No se migra código. No se construyen adaptadores ni puentes.

===================================================================
2. ECOSISTEMA NEXT (NUEVA GENERACIÓN)
===================================================================
- TRAVELFLOW CMS (TF CMS):
  * Estado: EN ARQUITECTURA / DESARROLLO (Fase 0C.1B).
  * Naturaleza: CMS turístico autónomo sobre Laravel 13.x + Vue 3.

- TRAVELFLOW NEXT (TFN):
  * Estado: FUTURO / NO IMPLEMENTADO.
  * Naturaleza: Nueva generación de ERP/CRM turístico sobre Laravel. Motor operacional soberano.

- TRAVEL FLOW PLUGIN NEXT (TFNP):
  * Estado: FUTURO / NO IMPLEMENTADO.
  * Naturaleza: Nuevo plugin WordPress para conectar webs externas con TFN.

- TRAVEL FLOW NEXT LICENSE (TFNL):
  * Estado: FUTURO / NO IMPLEMENTADO.
  * Naturaleza: Nueva aplicación Laravel para autorización/licenciamiento de TFN y TFNP.
```

---

## 2. Arquitectura de Relaciones Futuras

```text
                    TRAVEL FLOW NEXT LICENSE
                             (TFNL)
                               │
                      licencia / autoriza
                               │
                               ▼
                        TRAVELFLOW NEXT
                             (TFN)
                               │
                      API OFICIAL VERSIONADA
                               │
                ┌──────────────┴──────────────┐
                │                             │
                ▼                             ▼
       TRAVEL FLOW PLUGIN NEXT             TF CMS
               (TFNP)                         │
                │                             │
                ▼                             ▼
          WORDPRESS WEB                  TF CMS WEB
```

---

## 3. Principio de Autonomía de TF CMS

1. **TF CMS NO depende de TFNP:** Son aplicaciones totalmente desconectadas.
2. **TF CMS NO depende de TFN para existir:** Funciona al 100% como CMS turístico completo sin TFN.
3. **TFNL NO licencia TF CMS:** TFNL licenciará TFN y TFNP. La ausencia de TFN solo desactiva la sincronización operativa en tiempo real, jamás el CMS.
4. **Integración TF CMS ↔ TFN:** Su estado permanente en esta fase es `PENDIENTE DE TRAVELFLOW NEXT`. Queda prohibido inventar endpoints, adaptadores, mocks o esquemas de base de datos prematuros.
