# Cobertura del Poder Ejecutivo Estatal (Gobernadores y Jefes de Gobierno)

> **Principio de Pureza Documental:** Toda la información proviene estrictamente de fuentes primarias y oficiales de los tres órdenes de gobierno: Periódicos Oficiales Estatales, Diario Oficial de la Federación (DOF), Bandos Solemnes emitidos por los Congresos Locales, Cómputos Definitivos de los Institutos Electorales Estatales (OPLEs) y sentencias del Tribunal Electoral del Poder Judicial de la Federación (TEPJF). Prohibido el uso de Wikipedia como fuente en metadatos.

---

## Estructura del Dataset
Cada entidad cuenta con un archivo JSON en `storage/data/json/ejecutivo/`:
- `entidad`: Nombre oficial del estado.
- `entidad_cve`: Clave INEGI (01 a 32).
- `sede_poder_ejecutivo`: Domicilio oficial del Palacio de Gobierno o sede de despacho.
- `portal_oficial`: Portal institucional (`.gob.mx`).
- `periodo_cobertura`: Años y rango cubierto.
- `mandatarios`: Array ordenado cronológicamente con:
  - `id`: Identificador canónico (`ejec_{cve}_{año}_{apellido}`).
  - `nombre_completo`: Nombre oficial del titular.
  - `cargo_oficial`: Denominación legal constitucional.
  - `tipo_mandato`: Constitucional, Interino, Sustituto, Encargado del Despacho.
  - `fecha_inicio` y `fecha_conclusion`: Formato ISO 8601 (`YYYY-MM-DD`).
  - `partido` y `partido_nombre_completo`.
  - `bando_solemne_decreto`: Referencia al decreto de toma de protesta o bando solemne.
  - `metadata`:
    - `fuente_nombre`: Nombre de la publicación oficial.
    - `fuente_url`: Enlace al portal oficial primario.
    - `sha256_evidencia`: Hash criptográfico SHA-256 de verificación.
    - `fecha_extraccion`: Timestamp de compilación.

---

## Tabla de Cobertura Nacional (32 Entidades Federativas — 100% Completado)

| Clave | Entidad | Cobertura | Mandatarios | Fuentes Primarias Oficiales | Estatus |
|---|---|---|---|---|---|
| **09** | **Ciudad de México (y DDF)** | 1929–2030 (101 años) | 35 (25 regentes + 10 jefes) | DOF (`dof.gob.mx`), GOCDMX (`data.consejeria.cdmx.gob.mx`), IECM | ✅ Completado (PR #49) |
| **01** | **Aguascalientes** | 1992–2027 (35 años) | 7 | POEA (`aguascalientes.gob.mx`), IEE Aguascalientes (`ieeags.mx`) | ✅ Completado (PR #51) |
| **02** | **Baja California** | 1989–2027 (38 años) | 8 | POEBC (`periodicooficial.bajacalifornia.gob.mx`), IEEBC (`ieebc.mx`) | ✅ Completado (PR #53) |
| **03** | **Baja California Sur** | 1993–2027 (34 años) | 6 | BOGEBCS (`boletin.bcs.gob.mx`), IEEBCS (`ieebcs.org.mx`) | ✅ Completado (PR #53) |
| **08** | **Chihuahua** | 1992–2027 (35 años) | 6 | POECH (`chihuahua.gob.mx`), IEE Chihuahua (`ieechihuahua.org.mx`) | ✅ Completado (PR #53) |
| **25** | **Sinaloa** | 1993–2027 (34 años) | 6 | POES (`periodicooficial.sinaloa.gob.mx`), IEES (`ieesinaloa.mx`) | ✅ Completado (PR #53) |
| **26** | **Sonora** | 1991–2027 (36 años) | 6 | BOES (`boletinoficial.sonora.gob.mx`), IEE Sonora (`ieesonora.org.mx`) | ✅ Completado (PR #53) |
| **05** | **Coahuila de Zaragoza** | 1993–2029 (36 años) | 7 | POEC (`periodico.coahuila.gob.mx`), IEC (`iec.org.mx`) | ✅ Completado (PR #54) |
| **10** | **Durango** | 1992–2028 (36 años) | 6 | POED (`periodicooficial.durango.gob.mx`), IEPC Durango (`iepcdurango.mx`) | ✅ Completado (PR #54) |
| **19** | **Nuevo León** | 1991–2027 (36 años) | 8 | POENL (`nl.gob.mx/periodico-oficial`), IEEPCNL (`ieepcnl.mx`) | ✅ Completado (PR #54) |
| **24** | **San Luis Potosí** | 1991–2027 (36 años) | 9 | POESLP (`periodicooficial.slp.gob.mx`), CEEPAC (`ceepacslp.org.mx`) | ✅ Completado (PR #54) |
| **28** | **Tamaulipas** | 1993–2028 (35 años) | 6 | POET (`po.tamaulipas.gob.mx`), IETAM (`ietam.org.mx`) | ✅ Completado (PR #54) |
| **32** | **Zacatecas** | 1992–2027 (35 años) | 6 | POEZ (`periodico.zacatecas.gob.mx`), IEEZ (`ieez.org.mx`) | ✅ Completado (PR #54) |
| **06** | **Colima** | 1991–2027 (36 años) | 10 | POEC (`periodicooficial.col.gob.mx`), IEE Colima (`ieecolima.org.mx`) | ✅ Completado (PR #55) |
| **11** | **Guanajuato** | 1991–2030 (39 años) | 9 | POEG (`periodico.guanajuato.gob.mx`), IEEG (`ieeg.mx`) | ✅ Completado (PR #55) |
| **14** | **Jalisco** | 1989–2030 (41 años) | 9 | POEJ (`periodicooficial.jalisco.gob.mx`), IEPC Jalisco (`iepcjalisco.org.mx`) | ✅ Completado (PR #55) |
| **16** | **Michoacán de Ocampo** | 1992–2027 (35 años) | 9 | POEM (`periodicooficial.michoacan.gob.mx`), IEM (`iem.org.mx`) | ✅ Completado (PR #55) |
| **18** | **Nayarit** | 1993–2027 (34 años) | 6 | POEN (`periodicooficial.nayarit.gob.mx`), IEEN (`ieenayarit.org`) | ✅ Completado (PR #55) |
| **22** | **Querétaro** | 1991–2027 (36 años) | 7 | POEQ (`lasombradearteaga.segobqueretaro.gob.mx`), IEEQ (`ieeq.mx`) | ✅ Completado (PR #55) |
| **15** | **Estado de México** | 1993–2029 (36 años) | 7 | Gaceta del Gobierno Edomex (`legislacion.edomex.gob.mx`), IEEM (`ieem.org.mx`) | ✅ Completado (PR #56) |
| **13** | **Hidalgo** | 1993–2028 (35 años) | 7 | POEH (`periodico.hidalgo.gob.mx`), IEEH (`ieehidalgo.org.mx`) | ✅ Completado (PR #56) |
| **17** | **Morelos** | 1994–2030 (36 años) | 8 | POEM Tierra y Libertad (`periodico.morelos.gob.mx`), IMPEPAC (`impepac.mx`) | ✅ Completado (PR #56) |
| **21** | **Puebla** | 1993–2030 (37 años) | 10 | POEP (`periodicooficial.puebla.gob.mx`), IEEP (`ieepuebla.org.mx`) | ✅ Completado (PR #56) |
| **29** | **Tlaxcala** | 1993–2027 (34 años) | 6 | POET (`periodico.tlaxcala.gob.mx`), ITE (`itetlax.org.mx`) | ✅ Completado (PR #56) |
| **04** | **Campeche** | 1991–2027 (36 años) | 7 | POEC (`periodicooficial.campeche.gob.mx`), IEEC (`ieec.org.mx`) | ✅ Completado (Sur-Sureste) |
| **07** | **Chiapas** | 1994–2030 (36 años) | 8 | POECH (`periodicooficial.chiapas.gob.mx`), IEPC Chiapas (`iepc-chiapas.org.mx`) | ✅ Completado (Sur-Sureste) |
| **12** | **Guerrero** | 1993–2027 (34 años) | 8 | POEG (`periodicooficial.guerrero.gob.mx`), IEPC Guerrero (`iepcgro.mx`) | ✅ Completado (Sur-Sureste) |
| **20** | **Oaxaca** | 1992–2028 (36 años) | 6 | POEO (`periodicooficial.oaxaca.gob.mx`), IEEPCO (`ieepco.org.mx`) | ✅ Completado (Sur-Sureste) |
| **23** | **Quintana Roo** | 1993–2027 (34 años) | 6 | POEQROO (`po.qroo.gob.mx`), IEQROO (`ieqroo.org.mx`) | ✅ Completado (Sur-Sureste) |
| **27** | **Tabasco** | 1989–2030 (41 años) | 10 | POET (`periodico.tabasco.gob.mx`), IEPCT (`iepct.mx`) | ✅ Completado (Sur-Sureste) |
| **30** | **Veracruz de Ignacio de la Llave** | 1992–2030 (38 años) | 8 | Gaceta Oficial del Estado (`editoraveracruz.gob.mx`), OPLE Veracruz (`oplever.org.mx`) | ✅ Completado (Sur-Sureste) |
| **31** | **Yucatán** | 1991–2030 (39 años) | 8 | Diario Oficial del Estado (`diariooficial.yucatan.gob.mx`), IEPAC (`iepac.mx`) | ✅ Completado (Sur-Sureste) |
