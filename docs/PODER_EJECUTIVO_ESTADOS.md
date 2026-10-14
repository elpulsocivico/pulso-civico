# PODER EJECUTIVO ESTATAL — PULSO CÍVICO

Este documento describe el modelo de datos, metodología y cobertura histórica de los titulares del **Poder Ejecutivo de las 32 Entidades Federativas**, incluyendo los Gobernadores Constitucionales, Gobernadores Sustitutos/Interinos, Jefes de Gobierno de la Ciudad de México y Regentes del Departamento del Distrito Federal (1929–1997).

---

## 1. Principios Rectores y Fuentes Oficiales Primarias
* **Fuentes Oficiales Exclusivas**: Todo mandato debe estar fundado en un nombramiento formal en el Diario Oficial de la Federación (DOF), Periódicos Oficiales de los Estados (POE), Bandos Solemnes de Congresos Locales o cómputos estatales de organismos electorales (IEE/OPPLEs) y TEPJF.
* **Criptografía**: Cada registro cuenta con un hash SHA-256 (`sha256_evidencia`) calculado a partir de la tupla única de entidad, cargo, nombre, periodos y URL oficial.
* **Neutralidad**: No se emiten juicios de valor sobre administraciones ni calificativos políticos.

---

## 2. Clasificación de Mandatos

1. **Constitucional (Elección Popular)**: Mandatario electo mediante voto popular universal, libre, secreto y directo conforme a las constituciones locales.
2. **Sustituto / Interino (Designación Legislativa)**: Designado por el Congreso Local ante falta temporal o definitiva del titular.
3. **Encargado del Despacho**: Secretario de Gobierno o funcionario facultado por la ley orgánica para suplir ausencias momentáneas.
4. **Designación Presidencial (Regente DDF)**: Titular del Departamento del Distrito Federal nombrado por el Presidente de la República (1928–1997).

---

## 3. Catálogo de Entidades y Estado de Cobertura

| ID | Entidad | Titularidad | Cobertura Histórica | Mandatarios | Estado |
|:---:|---|---|:---:|:---:|:---:|
| 01 | **Aguascalientes** | Gobernadores Constitucionales y Sustituto | 1992–2027 (35 años) | 7 | ✅ Completado |
| 09 | **Ciudad de México** | Regentes DDF + Jefes de Gobierno | 1929–2030 (101 años) | 35 | ✅ Completado (PR #49) |
| 02-08, 10-32 | Restantes 30 Entidades | Gobernadores Constitucionales e Interinos | Diseño listo | — | 🚧 En Proceso |

---

## 4. Estructura de Datasets y Fuentes Oficiales Auditadas

Los datasets se almacenan en `storage/data/json/ejecutivo/`:
* `gobernadores_aguascalientes.json`: 7 gobernadores (1992–2027).
  * **Fuentes primarias**: Periódico Oficial del Estado de Aguascalientes (POEA, `https://eservicios2.aguascalientes.gob.mx/servicios/periodicooficial/`) e Instituto Estatal Electoral de Aguascalientes (IEEA, `https://www.ieeags.mx/`).
* `regentes_distrito_federal_1929_1997.json`: 25 Regentes del Departamento del Distrito Federal.
  * **Fuentes primarias**: Diario Oficial de la Federación (`https://www.dof.gob.mx`), decretos presidenciales y Ley Orgánica del DDF (DOF 31/12/1928).
* `jefes_gobierno_cdmx_1997_2030.json`: 10 Jefes de Gobierno del DF y CDMX.
  * **Fuentes primarias**: Gaceta Oficial de la Ciudad de México (`https://data.consejeria.cdmx.gob.mx`), Bandos Solemnes ALDF/Congreso CDMX y Acuerdos IECM/IEDF.
