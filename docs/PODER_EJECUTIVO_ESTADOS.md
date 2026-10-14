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

## Tabla de Avance Nacional (32 Entidades)

| Clave | Entidad | Cobertura | Mandatarios | Fuentes Primarias Oficiales | Estatus |
|---|---|---|---|---|---|
| **09** | **Ciudad de México (y DDF)** | 1929–2030 (101 años) | 35 (25 regentes + 10 jefes) | DOF (`dof.gob.mx`), GOCDMX (`data.consejeria.cdmx.gob.mx`), IECM | ✅ Completado (PR #49) |
| **01** | **Aguascalientes** | 1992–2027 (35 años) | 7 | POEA (`aguascalientes.gob.mx`), IEE Aguascalientes (`ieeags.mx`) | ✅ Completado (PR #51) |
| **02** | **Baja California** | 1989–2027 (38 años) | 8 | POEBC (`periodicooficial.bajacalifornia.gob.mx`), IEEBC (`ieebc.mx`) | ✅ Completado (Noroeste) |
| **03** | **Baja California Sur** | 1993–2027 (34 años) | 6 | BOGEBCS (`boletin.bcs.gob.mx`), IEEBCS (`ieebcs.org.mx`) | ✅ Completado (Noroeste) |
| **08** | **Chihuahua** | 1992–2027 (35 años) | 6 | POECH (`chihuahua.gob.mx`), IEE Chihuahua (`ieechihuahua.org.mx`) | ✅ Completado (Noroeste) |
| **25** | **Sinaloa** | 1993–2027 (34 años) | 6 | POES (`periodicooficial.sinaloa.gob.mx`), IEES (`ieesinaloa.mx`) | ✅ Completado (Noroeste) |
| **26** | **Sonora** | 1991–2027 (36 años) | 6 | BOES (`boletinoficial.sonora.gob.mx`), IEE Sonora (`ieesonora.org.mx`) | ✅ Completado (Noroeste) |
| **05** | **Coahuila** | 1993–2029 | En integración | POEC, IEC | 🚧 Bloque Noreste |
| **10** | **Durango** | 1992–2028 | En integración | POED, IEPC Durango | 🚧 Bloque Noreste |
| **19** | **Nuevo León** | 1991–2027 | En integración | POENL, IEEPCNL | 🚧 Bloque Noreste |
| **24** | **San Luis Potosí** | 1991–2027 | En integración | POESLP, CEEPAC | 🚧 Bloque Noreste |
| **28** | **Tamaulipas** | 1993–2028 | En integración | POET, IETAM | 🚧 Bloque Noreste |
| **32** | **Zacatecas** | 1992–2027 | En integración | POEZ, IEEZ | 🚧 Bloque Noreste |
| **06** | **Colima** | 1991–2027 | Pendiente | POEC, IEE Colima | ⏳ Próximo bloque |
| **11** | **Guanajuato** | 1991–2030 | Pendiente | POEG, IEEG | ⏳ Próximo bloque |
| **14** | **Jalisco** | 1992–2030 | Pendiente | POEJ, IEPC Jalisco | ⏳ Próximo bloque |
| **16** | **Michoacán** | 1992–2027 | Pendiente | POEM, IEM | ⏳ Próximo bloque |
| **18** | **Nayarit** | 1993–2027 | Pendiente | POEN, IEEN | ⏳ Próximo bloque |
| **22** | **Querétaro** | 1991–2027 | Pendiente | POEQ "La Sombra de Arteaga", IEEQ | ⏳ Próximo bloque |
| **13** | **Hidalgo** | 1993–2028 | Pendiente | POEH, IEEH | ⏳ Próximo bloque |
| **15** | **Estado de México** | 1993–2029 | Pendiente | Gaceta del Gobierno Edomex, IEEM | ⏳ Próximo bloque |
| **17** | **Morelos** | 1994–2030 | Pendiente | POEM "Tierra y Libertad", IMPEPAC | ⏳ Próximo bloque |
| **21** | **Puebla** | 1993–2030 | Pendiente | POEP, IEEP | ⏳ Próximo bloque |
| **29** | **Tlaxcala** | 1992–2027 | Pendiente | POET, ITE | ⏳ Próximo bloque |
| **04** | **Campeche** | 1991–2027 | Pendiente | POEC, IEEC | ⏳ Próximo bloque |
| **07** | **Chiapas** | 1994–2030 | Pendiente | POECH, IEPC Chiapas | ⏳ Próximo bloque |
| **12** | **Guerrero** | 1993–2027 | Pendiente | POEG, IEPC Guerrero | ⏳ Próximo bloque |
| **20** | **Oaxaca** | 1992–2028 | Pendiente | POEO, IEEPCO | ⏳ Próximo bloque |
| **23** | **Quintana Roo** | 1993–2027 | Pendiente | POEQROO, IEQROO | ⏳ Próximo bloque |
| **27** | **Tabasco** | 1988–2030 | Pendiente | POET, IEPCT | ⏳ Próximo bloque |
| **30** | **Veracruz** | 1992–2030 | Pendiente | Gaceta Oficial del Estado, OPLE Veracruz | ⏳ Próximo bloque |
| **31** | **Yucatán** | 1991–2030 | Pendiente | Diario Oficial del Estado, IEPAC | ⏳ Próximo bloque |
