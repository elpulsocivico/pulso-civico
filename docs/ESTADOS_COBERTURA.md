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

| ID | Entidad Federativa | Portal Oficial | Cobertura en Local | Estado | Registros |
|---|---|---|---|---|---|
| 01 | **Aguascalientes** | https://congresoags.gob.mx | 10 Legislaturas (LV a LXIV) | ✅ Completado | ~300 dips |
| 02 | **Baja California** | https://www.congresobc.gob.mx | 10 Legislaturas (XVI a XXV) | ✅ Completado | ~250 dips |
| 03 | **Baja California Sur** | https://www.cbcs.gob.mx | 10 Legislaturas | ✅ Completado | ~210 dips |
| 04 | **Campeche** | https://congresocampeche.gob.mx | 10 Legislaturas | ✅ Completado | ~350 dips |
| 05 | **Coahuila** | https://congresocoahuila.gob.mx | 10 Legislaturas | ✅ Completado | ~250 dips |
| 06 | **Colima** | https://congresocolima.gob.mx | 10 Legislaturas | ✅ Completado | ~250 dips |
| 07 | **Chiapas** | https://www.congresochiapas.gob.mx | 10 Legislaturas | ✅ Completado | ~400 dips |
| 08 | **Chihuahua** | https://www.congresochihuahua.gob.mx | 10 Legislaturas | ✅ Completado | ~330 dips |
| 09 | **Ciudad de México** | https://www.congresocdmx.gob.mx | Asambleas / Legislaturas I a III | ✅ Completado | ~660 dips |
| 10 | **Durango** | https://congresodurango.gob.mx | 10 Legislaturas | ✅ Completado | ~250 dips |
| 11 | **Guanajuato** | https://www.congresogto.gob.mx | 10 Legislaturas | ✅ Completado | ~360 dips |
| 12 | **Guerrero** | https://congresogro.gob.mx | 10 Legislaturas | ✅ Completado | ~460 dips |
| 13 | **Hidalgo** | https://www.congresohidalgo.gob.mx | 10 Legislaturas | ✅ Completado | ~300 dips |
| 14 | **Jalisco** | https://www.congresojal.gob.mx | 10 Legislaturas | ✅ Completado | ~380 dips |
| 15 | **Estado de México** | https://legislativoedomex.gob.mx | 10 Legislaturas | ✅ Completado | ~750 dips |
| 16 | **Michoacán** | https://congresomich.gob.mx | 10 Legislaturas | ✅ Completado | ~400 dips |
| 17 | **Morelos** | https://congresomorelos.gob.mx | 10 Legislaturas | ✅ Completado | ~200 dips |
| 18 | **Nayarit** | https://congresonayarit.gob.mx | 10 Legislaturas | ✅ Completado | ~300 dips |
| 19 | **Nuevo León** | https://www.hcnl.gob.mx | 10 Legislaturas | ✅ Completado | ~420 dips |
| 20 | **Oaxaca** | https://www.congresooaxaca.gob.mx | 10 Legislaturas | ✅ Completado | ~420 dips |
| 21 | **Puebla** | https://www.congresopuebla.gob.mx | 10 Legislaturas | ✅ Completado | ~410 dips |
| 22 | **Querétaro** | https://legislaturaqueretaro.gob.mx | 10 Legislaturas | ✅ Completado | ~250 dips |
| 23 | **Quintana Roo** | https://congresoqroo.gob.mx | 10 Legislaturas | ✅ Completado | ~250 dips |
| 24 | **San Luis Potosí** | https://congresosanluis.gob.mx | 10 Legislaturas | ✅ Completado | ~270 dips |
| 25 | **Sinaloa** | https://www.congresosinaloa.gob.mx | 10 Legislaturas | ✅ Completado | ~400 dips |
| 26 | **Sonora** | https://www.congresoson.gob.mx | 10 Legislaturas | ✅ Completado | ~330 dips |
| 27 | **Tabasco** | https://congresotabasco.gob.mx | 10 Legislaturas | ✅ Completado | ~350 dips |
| 28 | **Tamaulipas** | https://www.congresotamaulipas.gob.mx | 10 Legislaturas (LVII a LXVI) | ✅ Completado | ~360 dips |
| 29 | **Tlaxcala** | https://congresotlaxcala.gob.mx | 10 Legislaturas | ✅ Completado | ~250 dips |
| 30 | **Veracruz** | https://www.legisver.gob.mx | 10 Legislaturas | ✅ Completado | ~500 dips |
| 31 | **Yucatán** | https://www.congresoyucatan.gob.mx | 10 Legislaturas | ✅ Completado | ~250 dips |
| 32 | **Zacatecas** | https://www.congresozac.gob.mx | 10 Legislaturas | ✅ Completado | ~300 dips |
| **Total** | **32 Entidades** | **Nacional** | **320 Datasets JSON** | ✅ **100% Cobertura** | **10,728 registros** |

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
