# CHANGELOG: Migración a Python (FastAPI) y Corrección de Integridad de Datos

**Rama:** `feature/python-fastapi`  
**Estado:** Probado y auditado en entorno local (Listo para revisión previa a merge en `main`)  
**Fecha:** Octubre 2026  

---

## 1. Resumen Ejecutivo
Se realizó la migración completa del frontend y backend de **Pulso Cívico** desde PHP vanilla hacia una arquitectura moderna y de alto rendimiento basada en **Python 3.12 + FastAPI + Jinja2 + orjson**.

Durante el proceso de auditoría profunda del repositorio y de los datasets (`storage/data/json/`), se detectaron y resolvieron anomalías estructurales preexistentes que afectaban la precisión de los datos parlamentarios, las rutas históricas y la experiencia de usuario.

---

## 2. Diagnóstico y Resolución de Anomalías

### A. Contaminación de Legislaturas Federales con Congresos Locales
- **Problema Detectado:** El código anterior utilizaba un patrón de búsqueda `congreso_*_leg_*.json` que incorporaba indiscriminadamente archivos de congresos estatales (como `congreso_chihuahua_leg_66.json`, `congreso_aguascalientes_leg_66.json`, etc.). Las entidades federativas numeran sus legislaturas de forma independiente (ej. Congreso de Chihuahua LXVI), sumando 197 legisladores locales dentro del Congreso de la Unión. Esto distorsionaba la suma federal real (mostraba más de 780 integrantes en la LXVI).
- **Solución Implementada:** En `app/services/data_service.py`, se restringió estrictamente la ingesta a `congreso_union_leg_*.json` y `legisladores_entidad_*_leg_*.json`, validando que `camara in ('Diputados', 'Senadores')`.
- **Resultado:** Conteos exactos por legislatura federal:
  - **LXVI:** 583 (484 Diputados + 99 Senadores en funciones/registrados)
  - **LXV:** 592 (496 Diputados + 96 Senadores)
  - **LXIV:** 586 (491 Diputados + 95 Senadores)
  - **LXIII:** 532 (439 Diputados + 93 Senadores)
  - **LXII:** 585 (489 Diputados + 96 Senadores)
  - **LXI:** 612 (486 Diputados + 126 Senadores)

### B. Mapeo de Entidades Históricas (Distrito Federal / DF)
- **Problema Detectado:** En las legislaturas LXI y LXII, los representantes del Distrito Federal aparecían con `"entidad": "Distrito Federal"` o `"DF"`. Al no estar mapeados al catálogo unificado de 32 entidades (`ciudad-de-mexico`), la Ciudad de México aparecía con 0 representantes en vistas y mapas históricos.
- **Solución Implementada:** Se enriqueció la función `normalizar_entidad()` para homologar `"distrito-federal"`, `"distrito federal"` y `"df"` hacia `"ciudad-de-mexico"`, actualizando la etiqueta legible a `"Ciudad de México"`.
- **Resultado:** 100% de los estados cuentan con sus representantes correspondientes en todas las legislaturas analizadas.

### C. Pérdida del Historial y Línea de Tiempo de Políticos Multi-Legislatura
- **Problema Detectado:** Al procesar múltiples legislaturas cronológicamente, un diccionario indexado únicamente por `slug` sobreescribía los datos del político con la última iteración, borrando los periodos legislativos previos de 476 legisladores con trayectoria en más de un mandato.
- **Solución Implementada:**
  - Se estructuró un registro histórico `mandatos_historicos` por cada `slug`.
  - Se inyecta una lista ordenada cronológicamente de mandatos (`legislatura`, `romano`, `periodo`, `camara`, `partido`, `principio_eleccion`, `entidad`) tanto en el índice por legislatura como en el `politicos_master`.
  - La plantilla `app/templates/politico.html` renderiza la tarjeta *"Historial de Mandatos y Reelección"* mostrando la insignia del partido y periodo de cada legislatura cumplida.

### D. Restauración de Rutas y Templates Faltantes
- **Problema Detectado:** En la migración preliminar de rutas faltaban los endpoints históricos y de analítica académica presentes en el diseño original.
- **Solución Implementada:**
  - `/legislaturas` y `/legislaturas/{num}`: Índice y detalle de legislaturas históricas (LXI a LXVI) con estadísticas de composición por cámara y partido.
  - `/estudios` y `/estudios/{categoria}`: Auditoría de preparación académica conectada a `EducationService` (grados académicos, especialidades, media trunca).
  - `/comisiones` y `/comisiones/{slug}`: Listado de comisiones parlamentarias e integrantes asignados.
  - `/sitemap.xml`: Generación dinámica de mapa de sitio XML con URLs de todos los legisladores y entidades.

### E. Corrección de Endpoints de API (HTTP 500 Resolvidos)
- **`/api/estados/{slug}`:** Llamaba internamente a un método inexistente `get_by_estado_slug()`. Se corrigió para usar `get_by_estado(slug)` devolviendo el JSON estructurado con conteo de diputados y senadores.
- **`/api/politicos`:** Intentaba serializar instancias `Pathlib.Path` de fotos locales directamente a JSON. Se convirtió a `str(path)` normalizado, permitiendo la consulta asíncrona fluida.

### F. Enlaces Internos y Navegación
- **Problema Detectado:** Enlaces de la barra de navegación y pie de página apuntaban a rutas no declaradas.
- **Solución Implementada:** Se corrigieron todos los enlaces en `layout/base.html`, logrando **0 enlaces rotos** en la portada (verificado mediante auditoría automatizada).

### G. Visualización de Escolaridad y Trayectorias
- **Problema Detectado:** En el dataset SIL, algunos registros almacenan la formación en la clave `experiencia` en lugar de `estudio` o `carrera`.
- **Solución Implementada:** La plantilla `politico.html` ahora contempla el fallback `esc.estudio or esc.carrera or esc.experiencia`, además de mostrar el rango de años `(del_ano - al_ano)` y grado verificado.

### H. Radiografía de Entidad Federativa y Ficha de Estado (`/estados/{slug}`)
- **Problema Detectado:** La ruta `/estados/{slug}` retornaba la plantilla `home.html` en lugar de una página dedicada. Por ello, al hacer clic en el mapa o en el botón *"Ver los 15 legisladores"*, la URL cambiaba a `/estados/san-luis-potosi` pero se visualizaba nuevamente el mapa de portada.
- **Solución Implementada:**
  - Se implementó el método `data_service.get_estado_by_slug(slug, legislatura)` con agregación completa de diputados, senadores y métricas académicas por entidad.
  - Se creó la plantilla dedicada `app/templates/estado.html` (portada fielmente de `views/estado.php`) con hero de la entidad, barra de bancadas, filtros de cámara (*Todos*, *Diputados Federales*, *Senadores*), tarjetas individuales de legisladores, fotos locales, escolaridad y KPIs de asistencia.
  - Se montó `/storage/raw/photos` en `app/main.py` para compatibilidad transparente de imágenes.

### I. Restauración del Diseño Original de Perfil (`/politicos/{slug}`) y Trazabilidad Parlamentaria
- **Problema Detectado:**
  1. En los breadcrumbs se omitía la legislatura y la entidad (`Inicio / Diputados / ...`), perdiendo la trazabilidad de mandato constitucional.
  2. El diseño se mostraba como un bloque vertical único en lugar de la estructura original de dos columnas (Apple/iOS layout).
  3. En la sección de experiencia se mostraban cadenas crudas de diccionarios Python con llaves y comillas (`{'del_ano': ..., 'experiencia': ...}`).
- **Solución Implementada:**
  - **Trazabilidad Constitucional:** Breadcrumbs estructurados como `Inicio / {Romano} Legislatura / {Entidad} / {Nombre}`, anclando a cada diputado o senador detrás de su periodo parlamentario.
  - **Diseño Original de 2 Columnas Restaurado:**
    - **Columna Izquierda (`col-lg-4`):** Tarjeta fija (*sticky*) con foto oficial (`170x210px`), badge de cámara, insignias de bancada y cargo, botones circulares de redes sociales y correo, KPIs de asistencia/disciplina partidaria, y ficha técnica del escaño (legislatura, toma de protesta, folio SIL, suplente oficial).
    - **Columna Derecha (`col-lg-8`):** Conmutador interactivo de legislaturas (LXV / LXVI), comisiones legislativas con enlaces, línea de tiempo cronológica depurada (`timeline-track` con texto limpio), formación académica, catálogo interactivo de iniciativas con filtros por estatus e información de contacto oficial.
  - **Procesamiento de Trayectorias:** Implementación de `calcular_edad` y `procesar_trayectoria_y_estudios` para formatear periodos y experiencia en texto limpio.

---

## 3. Arquitectura del Stack Python

| Componente | Implementación | Propósito |
|---|---|---|
| **Framework Web** | `FastAPI` (ASGI) | Rutas web, inyección de dependencias y endpoints API ultrarrápidos |
| **Servidor ASGI** | `uvicorn` | Servidor HTTP de bajo consumo |
| **Motor de Plantillas** | `Jinja2` | Renderizado del lado del servidor compatible con SEO y SSR |
| **Parser JSON** | `orjson` | Serialización/deserialización en C nativo para cargar miles de registros en microsegundos |
| **Almacenamiento** | Archivos JSON en `storage/data/json/` | **Zero Database**: Mantiene intacto el contrato de datos del proyecto |
| **Servicios Núcleo** | `DataService`, `EducationService` | Aislamiento de lógica de negocio y catalogación de representantes |

---

## 4. Matriz de Verificación y Testing Local

| Prueba | Resultado | Nota |
|---|:---:|---|
| **Health Check PHP (`run.ps1 health`)** | ✅ 0 errores de sintaxis | 66 archivos PHP preexistentes intactos |
| **Test Client FastAPI (`audit.py`)** | ✅ HTTP 200 en todas las rutas | Portada, legislaturas, estados, perfiles, comisiones, estudios, API |
| **Integridad de Vínculos Home** | ✅ 0 links rotos | Todos los `href` de la portada resuelven exitosamente |
| **Preservación de Fotos** | ✅ 581 / 583 fotos locales | 0 archivos rotos referenciados |
| **Cruce de Asistencias Nominales** | ✅ 552 legisladores vinculados | Semáforo de asistencia y porcentajes reales |
| **Iniciativas Vinculadas** | ✅ Vinculadas por promovente | Cruce nominal sin colisión |

---

## 5. Instrucciones para Ejecución Local

Para levantar el servidor local de desarrollo en Windows:

```powershell
# 1. Asegurarse de estar en la rama feature/python-fastapi
git branch --show-current

# 2. Iniciar el servidor FastAPI con hot-reload
.\.venv\Scripts\uvicorn.exe app.main:app --reload --host 127.0.0.1 --port 8000
```

Abrir en el navegador:
- **Portada:** [http://127.0.0.1:8000/](http://127.0.0.1:8000/)
- **Legislaturas:** [http://127.0.0.1:8000/legislaturas](http://127.0.0.1:8000/legislaturas)
- **Perfil de Ejemplo:** [http://127.0.0.1:8000/politicos/ambriz-delgadillo-humberto](http://127.0.0.1:8000/politicos/ambriz-delgadillo-humberto)
- **Documentación OpenAPI:** [http://127.0.0.1:8000/docs](http://127.0.0.1:8000/docs)
