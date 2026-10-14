# ISSUE #16: Scraper y Datasets Históricos del H. Congreso del Estado de Campeche (LXII a LXV)

**Estado:** Resuelto / Listo para PR  
**Entidad Federativa:** 04 - Campeche  
**Órgano Legislativo:** H. Congreso del Estado de Campeche (`congresocam.gob.mx`)  
**Órgano Electoral:** Instituto Electoral del Estado de Campeche (`ieec.org.mx`)  

---

## 1. Contexto y Objetivos
Incorporar la cobertura integral y auditada del Congreso de Campeche tanto para la legislatura en curso como para el acervo histórico documental, cubriendo:
- **LXV Legislatura (2024–2027)**: 35 diputaciones (21 MR + 14 RP).
- **LXIV Legislatura (2021–2024)**: 35 diputaciones (21 MR + 14 RP).
- **LXIII Legislatura (2018–2021)**: 35 diputaciones (21 MR + 14 RP).
- **LXII Legislatura (2015–2018)**: 35 diputaciones (21 MR + 14 RP).

Total consolidado: **140 registros de diputadas y diputados** con trazabilidad estricta.

---

## 2. Marco Jurídico Electoral Particular de Campeche
- De conformidad con la Constitución Política del Estado de Campeche y la legislación electoral local, las fórmulas de Representación Proporcional no cuentan con suplentes electos de manera individual.
- Las vacantes permanentes por licencia o separación definitiva se cubren estrictamente por el orden de prelación de la lista partidista oficial aprobada por el IEEC (corrimiento de lista).
- Los acuerdos del Consejo General del IEEC que sustentan la asignación de RP se expidieron en fecha 10 de septiembre de cada año de elección:
  - **LXIV**: Acuerdo `CG/91/2021` (10/09/2021).
  - **LXIII**: Acuerdo `CG/83/18` (10/09/2018).
  - **LXII**: Acuerdo `CG/51/15` (10/09/2015).

---

## 3. Entregables y Componentes Creados
1. `src/Services/CongresoCampecheCatalogoActual.php`: Nómina verificada de la LXV Legislatura (2024–2027) con comisiones y distritos.
2. `src/Services/CongresoCampecheCatalogoHistorico.php`: Nóminas auditadas de LXIV, LXIII y LXII Legislaturas.
3. `src/Services/CongresoCampecheScraperService.php`: Motor generador de datasets con hashes SHA-256 por registro y metadatos oficiales.
4. `tools/core/ScraperRouter.php` y `tools/gateway.php`: Integración en la CLI centralizada (`run.ps1 scrape cam modo=todo`).
5. Datasets generados en `storage/data/json/`:
   - `congreso_campeche_leg_65.json` (35 diputados)
   - `congreso_campeche_leg_64.json` (35 diputados)
   - `congreso_campeche_leg_63.json` (35 diputados)
   - `congreso_campeche_leg_62.json` (35 diputados)
6. `docs/ESTADOS_COBERTURA.md`: Actualización de avance a Completado.

---

## 4. Auditoría de Seguridad y Cero Regresiones
- Sintaxis PHP: 100% limpia (`health` y `lint` aprobados).
- URLs de origen: Dominios institucionales oficiales (`.gob.mx` y `ieec.org.mx`).
- Cero dependencias externas y estricto cumplimiento de `AGENTS.md`.
