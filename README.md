# TravelFlow CMS (TF CMS)

CMS turístico autónomo de alto rendimiento construido sobre **Laravel 13.x** y **PHP 8.3+**.

---

## 1. Visión del Producto

TravelFlow CMS (TF CMS) es una plataforma web y de gestión de contenidos especializada para agencias de viajes y operadores turísticos. Proporciona control total sobre contenidos editoriales, catálogo de tours estructurados, itinerarios dinámicos, destinos, experiencias, biblioteca de medios y diseño visual modular mediante **TF Builder** y **Theme Engine**.

---

## 2. Frontera Generacional y Ecosistema

El ecosistema TravelFlow mantiene una separación estricta de dominios y generaciones:

```text
Ecosistema Actual (Legacy):
  TRAVEL FLOW v1 (Existente / Legacy / Proyecto Separado)
  ├── TFP (Plugin legacy)
  └── TFL (Licenciamiento legacy)

Ecosistema Next (Nueva Generación):
  TRAVELFLOW CMS (TF CMS)    ─── CMS Turístico Autónomo (Laravel 13.x)
  TRAVELFLOW NEXT (TFN)      ─── Futuro CRM/ERP y motor operacional [NO IMPLEMENTADO]
  TRAVEL FLOW PLUGIN NEXT    ─── Futuro plugin WordPress para TFN [NO IMPLEMENTADO]
  TF NEXT LICENSE (TFNL)     ─── Futura aplicación de licenciamiento/autorización [NO IMPLEMENTADO]
```

### Reglas Vinculantes:
- **TF CMS ≠ TF v1:** No comparte código, arquitectura, tablas, controladores ni contratos con la generación legacy.
- **TFN, TFNP y TFNL = FUTURO / NO IMPLEMENTADO:** TF CMS es completamente autónomo y no depende de la existencia de TFN para operar.

---

## 3. Stack Tecnológico Base

- **Backend:** Laravel 13.x (PHP 8.3+)
- **Base de Datos:** MySQL 8.4+ / MariaDB 10.6+ (InnoDB, UTF8mb4)
- **Frontend Administrativo:** Vue 3 + TypeScript + Pinia + Vue Router + Vite
- **Web Pública:** Arquitectura híbrida PHP SSR + HTML5 semántico + CSS Moderno + JS Progresivo

---

## 4. Gobernanza del Proyecto

- **Orlando:** Define el producto, visión y prioridades.
- **ChatGPT:** Dirige la ingeniería, aprueba arquitectura, fases y gates técnicos.
- **Gemini:** Reconoce, investiga, implementa, prueba y presenta evidencia técnica verificable.
