# PODER EJECUTIVO ESTATAL — PULSO CÍVICO

Este documento describe el modelo de datos, metodología y cobertura histórica de los titulares del **Poder Ejecutivo de las 32 Entidades Federativas**, incluyendo los Gobernadores Constitucionales, Gobernadores Sustitutos/Interinos, Jefes de Gobierno de la Ciudad de México y Regentes del Departamento del Distrito Federal (1929–1997).

---

## 1. Principios Rectores y Neutralidad
* **Trazabilidad estricta**: Todo mandato debe estar fundado en un nombramiento formal en el Diario Oficial de la Federación (DOF), Bando Solemne de Congreso Local o cómputo estatal de organismo electoral (IEE/OPPLE).
* **Criptografía**: Cada registro cuenta con un hash SHA-256 (`sha256_evidencia`) calculado a partir de la tupla única de entidad, cargo, nombre, periodos y slug.
* **Neutralidad**: No se emiten juicios de valor sobre administraciones ni calificativos políticos.

---

## 2. Clasificación de Mandatos

1. **Constitucional (Elección Popular)**: Mandatario electo mediante voto popular universal, libre, secreto y directo conforme a las constituciones locales.
2. **Sustituto / Interino (Designación Legislativa)**: Designado por el Congreso Local o Asamblea Legislativa ante falta temporal o definitiva del titular.
3. **Encargado del Despacho**: Secretario de Gobierno o funcionario facultado por la ley orgánica para suplir ausencias momentáneas.
4. **Designación Presidencial (Regente DDF)**: Titular del Departamento del Distrito Federal nombrado libremente por el Presidente de la República (1928–1997).

---

## 3. Catálogo de Entidades y Estado de Cobertura

| ID | Entidad | Titularidad | Cobertura Histórica | Mandatarios | Estado |
|:---:|---|---|:---:|:---:|:---:|
| 01 | **Aguascalientes** | Gobernadores Constitucionales y Sustituto | 1992–2027 (35 años) | 7 | ✅ Completado |
| 09 | **Ciudad de México / Distrito Federal** | Regentes DDF + Jefes de Gobierno | 1929–2030 (101 años) | 35 | ✅ Completado |
| 02–08, 10–32 | **Restantes 30 Entidades Federativas** | Gobernadores Constitucionales e Interinos | Cronologías estatales oficiales | ~258 | ✅ Completado |
| **Total** | **32 Entidades + Cronologías Nacionales** | **Poder Ejecutivo Estatal** | **Histórico consolidado** | **34 JSONs / 300 reg.** | ✅ **100% Match** |

---

## 4. Estructura de Datasets y Fuentes Oficiales Auditadas

Los datasets se almacenan en `storage/data/json/ejecutivo/` (34 archivos JSON en total):
* `gobernadores_<estado_slug>.json`: 32 archivos individuales con mandatarios constitucionales, sustitutos y periodos electorales.
  * **Fuentes primarias**: Periódicos Oficiales Estatales (POEs), Institutos Electorales Locales (IEEs/OPLEs) y gacetas legislativas.
* `regentes_distrito_federal_1929_1997.json`: 25 Regentes del Departamento del Distrito Federal.
  * **Fuente primaria**: Diario Oficial de la Federación (`https://www.dof.gob.mx`), decretos presidenciales de nombramiento y Ley Orgánica del DDF (DOF 31/12/1928).
* `jefes_gobierno_cdmx_1997_2030.json`: 10 Jefes de Gobierno del DF y CDMX.
  * **Fuente primaria**: Gaceta Oficial del Distrito Federal y de la Ciudad de México (`https://data.consejeria.cdmx.gob.mx`), Bandos Solemnes de la ALDF/Congreso CDMX y Acuerdos de Cómputo del IEDF/IECM.
* `ejecutivo_cdmx_cronologia_completa.json`: 35 mandatarios en secuencia cronológica ininterrumpida (1929–2030) con hashes SHA-256 independientes por registro.


