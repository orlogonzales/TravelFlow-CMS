# Mapa del Ecosistema y Frontera Generacional

## 1. ClasificaciÃ³n Generacional

El ecosistema TravelFlow se divide estrictamente en dos generaciones independientes e incomunicadas a nivel de cÃ³digo:

```text
===================================================================
1. ECOSISTEMA ACTUAL (LEGACY) â€” FUERA DEL ALCANCE DE TF CMS
===================================================================
- TRAVEL FLOW v1 (Sistema actual existente / monolÃ­tico)
- TFP legacy (Plugin actual de WordPress)
- TFL legacy (Licenciamiento actual)

* Estado: EXISTENTE.
* Regla: No se migra cÃ³digo. No se construyen adaptadores ni puentes.

===================================================================
2. ECOSISTEMA NEXT (NUEVA GENERACIÃ“N)
===================================================================
- TRAVELFLOW CMS (TF CMS):
  * Estado: EN DESARROLLO (Fase 0B-L).
  * Naturaleza: CMS turÃ­stico autÃ³nomo sobre Laravel 13.x + Vue 3.

- TRAVELFLOW NEXT (TFN):
  * Estado: FUTURO / NO IMPLEMENTADO.
  * Naturaleza: Nueva generaciÃ³n de ERP/CRM turÃ­stico sobre Laravel. Motor operacional soberano.

- TRAVEL FLOW PLUGIN NEXT (TFNP):
  * Estado: FUTURO / NO IMPLEMENTADO.
  * Naturaleza: Nuevo plugin WordPress para conectar webs externas con TFN.

- TRAVEL FLOW NEXT LICENSE (TFNL):
  * Estado: FUTURO / NO IMPLEMENTADO.
  * Naturaleza: Nueva aplicaciÃ³n Laravel para autorizaciÃ³n/licenciamiento de TFN y TFNP.
```

## 2. Arquitectura de Relaciones Futuras

```text
                    TRAVEL FLOW NEXT LICENSE
                             (TFNL)
                               â”‚
                      licencia / autoriza
                               â”‚
                               â–¼
                       TRAVELFLOW NEXT
                            (TFN)
                               â”‚
                     API OFICIAL VERSIONADA
                               â”‚
               â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”´â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
               â”‚                               â”‚
               â–¼                               â–¼
      TRAVEL FLOW PLUGIN NEXT               TF CMS
              (TFNP)                           â”‚
               â”‚                               â”‚
               â–¼                               â–¼
         WORDPRESS WEB                    TF CMS WEB
```

## 3. Principio de AutonomÃ­a de TF CMS

1. **TF CMS NO depende de TFNP:** Son aplicaciones totalmente desconectadas.
2. **TF CMS NO depende de TFN para existir:** Funciona al 100% como CMS turÃ­stico completo sin TFN.
3. **TFNL NO licencia TF CMS:** TFNL licenciarÃ¡ TFN y TFNP. La ausencia de TFN solo desactiva la sincronizaciÃ³n operativa en tiempo real, jamÃ¡s el CMS.
4. **IntegraciÃ³n TF CMS â†” TFN:** Su estado permanente en esta fase es `PENDIENTE DE CONTRATO API REAL`. Queda prohibido inventar endpoints o payloads.
