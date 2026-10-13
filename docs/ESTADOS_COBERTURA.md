# Mapa de Cobertura y Tareas por Estado (32 Congresos Locales)

> **Regla de adopción:** Si vas a scrapear un estado, abre un Issue en GitHub con el nombre `[Scraper] Congreso de <Estado>` o coméntalo en el grupo para asignártelo aquí.
> 
> **Convención de archivos:** `storage/data/json/congreso_<estado_slug>_leg_<numero>.json`  
> **Convención de scraper:** `src/Services/Scrapers/Congreso<Estado>ScraperService.php`

---

## Leyenda de Estado
- ✅ **Completado**: Dataset integrado en `main`, verificado con SHA-256 y fuente `.gob.mx`.
- 🚧 **En Progreso**: Asignado a un colaborador con branch/PR activo.
- 🙋 **Disponible**: Pendiente de asignar. ¡Cualquiera puede adoptarlo!

---

## Tabla de Avance de las 32 Entidades

| ID | Entidad Federativa | Portal Oficial | Legislaturas | Estado | Responsable / PR |
|---|---|---|---|---|---|
| 01 | **Aguascalientes** | https://congresoags.gob.mx | — | 🙋 Disponible | — |
| 02 | **Baja California** | https://www.congresobc.gob.mx | XXV (2024-2027, 25 dips) | ✅ Completado | Issue #12 |
| 03 | **Baja California Sur** | https://www.cbcs.gob.mx | — | 🙋 Disponible | — |
| 04 | **Campeche** | https://congresocampeche.gob.mx | — | 🙋 Disponible | — |
| 05 | **Coahuila** | https://congresocoahuila.gob.mx | — | 🙋 Disponible | — |
| 06 | **Colima** | https://congresocolima.gob.mx | — | 🙋 Disponible | — |
| 07 | **Chiapas** | https://www.congresochiapas.gob.mx | — | 🙋 Disponible | — |
| 08 | **Chihuahua** | https://www.congresochihuahua.gob.mx | — | 🙋 Disponible | — |
| 09 | **Ciudad de México** | https://www.congresocdmx.gob.mx | — | 🙋 Disponible | — |
| 10 | **Durango** | https://congresodurango.gob.mx | — | 🙋 Disponible | — |
| 11 | **Guanajuato** | https://www.congresogto.gob.mx | — | 🙋 Disponible | — |
| 12 | **Guerrero** | https://congresogro.gob.mx | — | 🙋 Disponible | — |
| 13 | **Hidalgo** | https://www.congresohidalgo.gob.mx | — | 🙋 Disponible | — |
| 14 | **Jalisco** | https://www.congresojal.gob.mx | — | 🙋 Disponible | — |
| 15 | **Estado de México** | https://legislativoedomex.gob.mx | — | 🙋 Disponible | — |
| 16 | **Michoacán** | https://congresomich.gob.mx | — | 🙋 Disponible | — |
| 17 | **Morelos** | https://congresomorelos.gob.mx | — | 🙋 Disponible | — |
| 18 | **Nayarit** | https://congresonayarit.gob.mx | — | 🙋 Disponible | — |
| 19 | **Nuevo León** | https://www.hcnl.gob.mx | — | 🙋 Disponible | — |
| 20 | **Oaxaca** | https://www.congresooaxaca.gob.mx | — | 🙋 Disponible | — |
| 21 | **Puebla** | https://www.congresopuebla.gob.mx | — | 🙋 Disponible | — |
| 22 | **Querétaro** | https://legislaturaqueretaro.gob.mx | — | 🙋 Disponible | — |
| 23 | **Quintana Roo** | https://congresoqroo.gob.mx | — | 🙋 Disponible | — |
| 24 | **San Luis Potosí** | https://congresosanluis.gob.mx | — | 🙋 Disponible | — |
| 25 | **Sinaloa** | https://www.congresosinaloa.gob.mx | — | 🙋 Disponible | — |
| 26 | **Sonora** | https://www.congresoson.gob.mx | — | 🙋 Disponible | — |
| 27 | **Tabasco** | https://congresotabasco.gob.mx | — | 🙋 Disponible | — |
| 28 | **Tamaulipas** | https://www.congresotamaulipas.gob.mx | LVII a LXVI (10 legs, 306 dips) | ✅ Completado | PR #8 |
| 29 | **Tlaxcala** | https://congresotlaxcala.gob.mx | — | 🙋 Disponible | — |
| 30 | **Veracruz** | https://www.legisver.gob.mx | — | 🙋 Disponible | — |
| 31 | **Yucatán** | https://www.congresoyucatan.gob.mx | — | 🙋 Disponible | — |
| 32 | **Zacatecas** | https://www.congresozac.gob.mx | — | 🙋 Disponible | — |

---

## Protocolo para Reclamar y Entregar un Estado

1. **Reclamar un estado**:
   - Avisa en el grupo o abre un Issue en GitHub: *"Tomo el Congreso de Jalisco"*.
   - Se actualiza la tabla marcando 🚧 **En Progreso** y tu nombre/usuario de GitHub.
2. **Crear el Scraper**:
   - Programar en `src/Services/Scrapers/Congreso<Estado>ScraperService.php`.
   - Seguir `docs/COMO_CREAR_UN_SCRAPER.md`.
   - Rate limit mínimo: 1,000 ms.
3. **Subir los datos con evidencia**:
   - Los registros deben incluir `fuente_url` al portal oficial `.gob.mx` y hash SHA-256 de respaldo.
4. **Validación y Merge**:
   - Correr en terminal: `powershell -File .\tools\run.ps1 lint:dir src/Services`
   - Someter Pull Request para auditoría automatizada (`tools/run.ps1 audit:pr`).
