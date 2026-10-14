# Relación Fotos e Insignias vs Fichas Independientes

Este documento define la arquitectura de vinculación entre los archivos físicos de imagen (fotografías oficiales e insignias municipales) almacenados en `storage/raw/photos/` y las fichas canónicas de perfil político en `storage/data/json/`.

---

## 1. Catálogo Maestro Unificado

Para evitar inferencias complejas o lecturas múltiples en disco por parte de la aplicación consumidora (Pulso Cívico), existe el catálogo central:

**Ruta:** [`storage/data/cat_fotos_fichas_nacional.json`](file:///Users/edward/Documents/pulsocivico/storage/data/cat_fotos_fichas_nacional.json)  
**Tamaño:** ~2.01 MB (3,945 registros indexados)

### Resumen de Cobertura

| Nivel de Gobierno | Total Registros | Fotos / Insignias Vinculadas | % Cobertura | Archivo de Destino |
|---|---|---|---|---|
| **Federal** (Cámara y Senado) | 582 | 582 fotos JPG | 100.0% | `congreso_union_leg_66.json` |
| **Estatal** (32 Congresos Locales) | 887 | 885 fotos JPG/PNG | 99.8% | `congresos_locales/*.json` |
| **Municipal** (32 Entidades / Cabildos) | 2,478 | 2,462 insignias PNG | 99.4% | `municipios/<cve>_<edo>/<cve_mun>_*.json` |
| **TOTAL CONSOLIDADO** | **3,945** | **3,929** | **99.6%** | `cat_fotos_fichas_nacional.json` |

---

## 2. Esquema del Catálogo (`cat_fotos_fichas_nacional.json`)

Cada elemento del arreglo `relaciones` sigue una estructura tipada que permite enlazar directamente la interfaz web con el archivo físico y su ficha de datos:

```json
{
  "tipo": "alcalde_municipal",
  "id": "mun_21114",
  "nombre_completo": "José Chedraui Budib",
  "municipio": "Puebla",
  "cve_mun": "21114",
  "entidad": "Puebla",
  "cve_entidad": "21",
  "cargo": "Presidenta/e Municipal",
  "partido": "MORENA",
  "foto_url": "/static/photos/municipios/21_puebla/21114.png",
  "foto_archivo": "storage/raw/photos/municipios/21_puebla/21114.png",
  "ficha_json": "storage/data/json/municipios/21_puebla/21114_puebla.json"
}
```

---

## 3. Reglas Deterministas de Resolución Directa

Si una aplicación desea resolver los archivos sin consultar el catálogo maestro, las reglas de nomenclatura estricta son:

### A. Nivel Federal (Congreso de la Unión: SIL SEGOB)
- **Clave Foránea (FK):** `referencia_sil` (ej. `9228115`).
- **Archivo de Foto:** `storage/raw/photos/{referencia_sil}.jpg`.
- **Ruta Web FastAPI:** `/static/photos/{referencia_sil}.jpg`.
- **Ficha Canónica:** `storage/data/json/congreso_union_leg_66.json` o `storage/data/json/legisladores_entidad_{cve}_leg_66.json`.

### B. Nivel Estatal (Congresos Locales)
- **Clave Foránea (FK):** `{cve_entidad}_{referencia_sil}` (ej. `21_leg_local_21_62_mr_13`).
- **Archivo de Foto:** `storage/raw/photos/locales/{cve_entidad}_{referencia_sil}.{jpg|png}` o el nombre explícito indicado en `foto_oficial_url` (ej. `01_105.jpg`).
- **Ruta Web FastAPI:** `/static/photos/locales/{archivo}`.
- **Ficha Canónica:** `storage/data/json/congresos_locales/congreso_{estado}_leg_{num}.json`.

### C. Nivel Municipal (Alcaldes y Cabildos 2024–2027)
- **Clave Foránea (FK):** `municipio_cve` (clave INEGI de 5 dígitos, ej. `21114`).
- **Archivo de Foto/Insignia:** `storage/raw/photos/municipios/{cve_entidad}_{nombre_estado}/{municipio_cve}.png`.
- **Ruta Web FastAPI:** `/static/photos/municipios/{cve_entidad}_{nombre_estado}/{municipio_cve}.png`.
- **Ficha Canónica Individual:** `storage/data/json/municipios/{cve_entidad}_{nombre_estado}/{municipio_cve}_{nombre_municipio}.json`.
- **Directorio Global:** `storage/data/json/municipios/directorio_nacional_alcaldes.json`.

---

## 4. Ejemplos de Consumo en Código

### Ejemplo en Python
```python
import json
from pathlib import Path

# Cargar catálogo en memoria RAM (O(1) lookups)
with open("storage/data/cat_fotos_fichas_nacional.json", "r", encoding="utf-8") as f:
    catalogo = json.load(f)["relaciones"]

# Indexar por clave municipal o ID SIL
indice_por_id = {item["id"]: item for item in catalogo}

# Obtener foto y ficha del Presidente Municipal de Puebla (21114)
puebla = indice_por_id.get("mun_21114")
print("Foto:", puebla["foto_archivo"])   # storage/raw/photos/municipios/21_puebla/21114.png
print("Ficha:", puebla["ficha_json"])    # storage/data/json/municipios/21_puebla/21114_puebla.json
```

### Ejemplo en TypeScript / JavaScript (Node / Next.js)
```typescript
import catalogoNacional from './storage/data/cat_fotos_fichas_nacional.json';

interface Relacion {
  tipo: string;
  id: string;
  nombre_completo: string;
  cargo: string;
  foto_url: string;
  ficha_json: string;
}

const relaciones: Relacion[] = catalogoNacional.relaciones;

export function getFichaYFoto(id: string): Relacion | undefined {
  return relaciones.find((r) => r.id === id);
}
```

---

## 5. Script de Regeneración

En caso de agregar nuevos municipios o fotografías de legisladores, el catálogo puede regenerarse ejecutando:

```bash
python3 tools/gateway.py py scripts/generar_catalogo_fotos_fichas.py
```
El script recorre todos los directorios canónicos, valida la existencia de cada archivo en disco e informa el balance consolidado.
