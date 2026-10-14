# Manifiesto Oficial de Datasets y Almacenamiento — ÁGORA / Pulso Cívico
**Versión de Datos**: v1.0.0 (Cobertura Nacional 32/32 Entidades — 2,478 Municipios)  
**Fecha de Auditoría**: Octubre 2026  
**Integridad**: 100% SHA-256 Verificado | 0 Missing Sources  

---

## 1. Topología del Almacenamiento (`storage/`)

El almacenamiento del proyecto está segmentado estrictamente en dos capas:
1. **Capa Canónica Estructurada (`storage/data/`)**: Datasets JSON normalizados y auditados listos para consumo del API y motor web FastAPI.
2. **Capa Cruda y Evidencias (`storage/raw/`)**: Descargas de origen, actas de cómputo, gacetas parlamentarias, acuerdos OPLE y fotografías.

```
storage/
├── data/                                 [50.93 MB | 2,820 archivos] -> REPOSITORIO GIT (100%)
│   ├── json/                            [50.57 MB | 2,815 archivos]
│   │   ├── municipios/                  [10.25 MB | 2,539 archivos] -> 2,478 cabildos canónicos + directorio
│   │   ├── congresos_locales/           [ 1.18 MB |    32 archivos] -> 32 legislaturas estatales
│   │   ├── ejecutivo/                   [317.18 KB |    32 archivos] -> 32 cronologías de gobernadores
│   │   ├── legisladores_*.json          [18.96 MB |   189 archivos] -> SIL federal (LXI a LXVI)
│   │   ├── votaciones_*.json            [ 92.80 KB |     1 archivo ] -> Votaciones nominales
│   │   ├── iniciativas_*.json           [ 24.50 KB |     1 archivo ] -> Iniciativas de ley
│   │   └── sesiones_asistencia_*.json   [  8.88 KB |     1 archivo ] -> Asistencia en pleno
│   └── cat_municipios_inegi.json        [203.22 KB |     1 archivo ] -> Catálogo Marco Geoestadístico
└── raw/                                 [ 1.07 GB | 10,172 archivos] -> PAQUETES EXTERNOS / LFS / RELEASES
    ├── photos/                          [527.46 MB | 3,933 archivos]
    │   ├── municipios/                  [  4.17 MB | 2,464 archivos] -> INSIGNIAS PNG 200x200 (EN GIT)
    │   └── locales/                     [506.63 MB |   887 archivos] -> FOTOS ALTA RESOLUCIÓN (RELEASE ASSET)
    ├── html/                            [359.69 MB | 5,977 archivos] -> SCRAPING RAW CACHE (.gitignore)
    ├── fichas_sni_2025_2026.zip         [160.43 MB |     1 archivo ] -> ARCHIVO COMPRIMIDO (>100MB, NO GIT DIRECTO)
    ├── GA1IEEPCO_CG_80_2024.pdf         [ 20.11 MB |     1 archivo ] -> ACUERDO OPLE PDF
    └── [carpetas estatales raw/]        [ ~30 MB   |   150 archivos] -> DESCARGAS TEMPORALES (.gitignore)
```

---

## 2. Límites Oficiales de Git y GitHub

| Parámetro | Límite Oficial GitHub | Comportamiento en ÁGORA |
|---|---|---|
| **Límite por Archivo Individual (Hard)** | **100 MB** | ⚠️ `storage/raw/fichas_sni_2025_2026.zip` (160.43 MB) **bloquearía cualquier push**. Debe excluirse del tracking regular. |
| **Advertencia por Archivo (Soft)** | **50 MB** | Ningún dataset JSON supera los 4 MB (máximo es 3.46 MB en `congreso_union_leg_66.json`). |
| **Límite de Carga por Push (`git push`)** | **2 GB** | Si se intenta subir `storage/` entero (1.13 GB), el push suele abortar por timeout o saturación de socket. |
| **Tamaño Recomendado de Repositorio** | **< 1 GB a 2 GB** | Mantener sólo el código y los datos canónicos asegura un `git clone` veloz (< 10 segundos). |
| **GitHub Releases Asset Limit** | **2 GB por asset** | Ideal para alojar los tarballs de evidencia cruda y fotografías pesadas. |

---

## 3. Estrategia de Versionado y Empaquetado

### A. Componentes que DEBEN subirse a Git (Peso: ~55 MB)
- `agora/`: Código fuente 100% Python/FastAPI, templates Jinja2 y pruebas.
- `tools/`: Runner centralizado `tools/gateway.py`.
- `storage/data/`: Todos los datasets canónicos JSON (2,815 archivos, 50.9 MB).
- `storage/raw/photos/municipios/`: Las 2,478 insignias municipales PNG 200x200 (4.17 MB).

### B. Componentes Excluidos de Git / Alojados en Releases (Peso: ~1.07 GB)
- Archivos binarios masivos (`*.zip`, `*.pdf`, `*.xlsx`).
- Caché de scrapers (`storage/raw/html/`, gacetas y descargas temporales).
- Fotografías de alta resolución de congresos locales (`storage/raw/photos/locales/`, 506 MB).

---

## 4. Generación de Paquetes de Distribución (Releases)

Para empaquetar y distribuir los datasets sin inflar el historial de Git, se generan los siguientes paquetes comprimidos:

```bash
# 1. Paquete Canónico Ligero (Solo JSONs de Cabildos y Directorios — ~8 MB)
tar -czvf dist/agora_datasets_canonicos_v1.0.tar.gz storage/data/ storage/raw/photos/municipios/

# 2. Paquete de Evidencias Crudas y Multimedia Completo (~950 MB)
tar -czvf dist/agora_raw_evidence_multimedia_v1.0.tar.gz storage/raw/
```

El paquete canónico permite desplegar la plataforma completa en cualquier servidor o contenedor Docker con memoria RAM mínima, mientras que el paquete de evidencias queda preservado como respaldo inmutable en los Releases de GitHub.
