# 🇲🇽 Pulso Cívico — Guía y Documentación del Proyecto

> **Plataforma ciudadana, neutral y de código abierto para la transparencia política en México.**

Cruzamos **datos oficiales del gobierno** con **noticias** para mostrar el historial real de cada representante popular: qué iniciativas presentan, cómo votan en el pleno, su trayectoria partidista y el fenómeno del "chapulineo" (cambio de partido). 

**Objetivo único:** Facilitar un **voto informado y consciente**.

---

## 📌 ¿Qué es lo relevante de este proyecto?

1. **Impacto Cívico Directo:**
   - En México, la información de los políticos suele estar oculta en gacetas en PDF o portales legislativos confusos.
   - Pulso Cívico centraliza el historial legislativo para que cualquier ciudadano audite a sus diputados y senadores en segundos.
2. **Neutralidad Absoluta y Verificabilidad:**
   - **Regla de oro:** Todo dato mostrado debe tener un enlace directo a una fuente oficial (`.gob.mx`) o nota periodística verificada con hash criptográfico SHA-256.
   - Cero opiniones, cero adjetivos, cero propaganda partidista.
3. **Arquitectura de Alto Rendimiento y Low-Cost (FastAPI + JSON):**
   - **Stack primario moderno:** Desarrollado en **Python 3.10+ con FastAPI, Uvicorn, Jinja2 y orjson** para máxima velocidad de respuesta (< 15ms), concurrencia asíncrona y pre-indexación en memoria de los más de 3,200 datasets cívicos.
   - **Cero bases de datos pesadas** (sin MySQL/PostgreSQL): los datos viven en archivos JSON en `storage/data/json/` versionados directamente con Git.
   - **Frontend ágil:** Bootstrap 5.3 vía CDN y JavaScript Vanilla nativo (sin frameworks SPA ni compiladores pesados).
   - **Capa CLI y Contingencia:** Mantiene herramientas CLI e ingesta histórica en PHP 8.1+ (`tools/gateway.php`, `tools/run.ps1`) para máxima portabilidad.
   - Cualquier persona puede correrlo en su computadora en menos de 2 minutos.

---

## 🧭 ¿Qué hay hoy y qué es lo que tenemos?

### ✅ Lo que ya está construido y consolidado en local:
- **Poder Legislativo Federal (Diputados y Senadores):** 326 JSONs / 10,983 registros correspondientes a las Legislaturas 57 a 66 (1997–2027). Incluye comisiones, asistencias, suplentes y trayectorias.
- **Congresos Locales (32 Entidades):** 320 JSONs / 10,728 registros cubriendo la totalidad de las 32 legislaturas estatales de México.
- **Poder Ejecutivo Estatal (Gobernadores):** 34 JSONs / 300 mandatarios (32 estados con gobernadores constitucionales e interinos, más cronología completa de CDMX y Regentes DDF 1929–2030).
- **Directorio Nacional de Alcaldes Vigentes:** 1 JSON / 2,474 registros con el 100% de presidentas y presidentes municipales en funciones.
- **Historial Municipal de 54 Años (1970–2024):** 2,474 JSONs / 35,700 mandatos históricos que documentan alternancia y márgenes de victoria comicio por comicio.
- **Gobiernos Municipales (Cabildos Canónicos):** 2,476 JSONs / 8,098 registros (7,422 ediles canónicos), incluyendo sistemas normativos internos (Usos y Costumbres en Oaxaca) y municipios de Yucatán.
- **Votaciones, Asistencias e Iniciativas:** Votaciones nominales y asistencias parlamentarias de la LXVI Legislatura.
- **Total de la Plataforma:** **3,201 JSONs / 33,368 registros** activos en almacenamiento estático local (`storage/data/json/`).
- **Interfaz Web:** Buscador global (`Ctrl+K`), mapa interactivo nacional, directorio de políticos, conmutador de legislaturas, visualizador municipal por clave INEGI (`/municipios/{cvegeo}`), explorador de alcaldes y estudios estadísticos.

### 🚧 Próximos pasos y líneas de colaboración:
1. **Módulo de Noticias y Hechos:**
   - Vincular notas periodísticas de medios acreditados con hash criptográfico SHA-256 a votaciones polémicas o cambios de bancada ("chapulineo").
2. **Profundización de Cabildos Municipales:**
   - Ampliar la cobertura de síndicos y regidores en municipios donde los OPLEs publiquen las sábanas de integración completa.
3. **Optimización de UI y Visualización Cívica:**
   - Mejorar componentes móviles, gráficos de líneas de tiempo de trayectorias políticas y exportación de datos abiertos en CSV.
4. **Refactorización y Rendimiento:**
   - Continuar la modularización de servicios grandes para mantener archivos < 400 líneas.

---

## 🎯 ¿En qué puedes ayudar según tu perfil?

| Tu Perfil | Tareas Recomendadas | Guías de Referencia |
|---|---|---|
| **Programador Python (FastAPI)** | Optimizar routers, endpoints de búsqueda, analítica en Jinja2 o scrapers asíncronos. | [app/](app/), [requirements.txt](requirements.txt) y [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md) |
| **Programador PHP** | Mantener herramientas de consola, gateway, scrapers históricos o scripts de auditoría. | [tools/](tools/) y [docs/COMO_CREAR_UN_SCRAPER.md](docs/COMO_CREAR_UN_SCRAPER.md) |
| **Frontend / Diseñador** | Mejorar la experiencia móvil (*mobile-first*), diseño de tarjetas de legisladores, accesibilidad y componentes interactivos. | [app/templates/](app/templates/) y [docs/MANIFIESTO.md](docs/MANIFIESTO.md) |
| **Investigador / Periodista** | Localizar portales oficiales de congresos locales, verificar datos contra gacetas oficiales y documentar fuentes. | [docs/FUENTES.md](docs/FUENTES.md) y [CONTRIBUTING.md](CONTRIBUTING.md) |
| **Principiante / Estudiante** | Resolver tareas `good first issue`, documentar, reportar inconsistencias o agregar datos asistido por IA. | [AGENTS.md](AGENTS.md) y [docs/DICCIONARIO_DE_DATOS.md](docs/DICCIONARIO_DE_DATOS.md) |

---

## ⚡ Instalación y Puesta en Marcha Local

### 1. Opción Principal: Python (FastAPI + Jinja2) — *Recomendada*
Es el motor activo de la plataforma por su velocidad, concurrencia y manejo en memoria de datasets.

#### Requisitos:
- **Python 3.10 o superior**.
- **Git**.

#### Puesta en marcha en 3 pasos:
```bash
# 1. Copiar archivo de entorno
cp .env.example .env

# 2. Instalar dependencias
pip install -r requirements.txt
# (Opcional con uv: uv venv; uv pip install -r requirements.txt)

# 3. Iniciar el servidor FastAPI con recarga en vivo
uvicorn app.main:app --reload --port 8000
```
Abre tu navegador en: [http://localhost:8000](http://localhost:8000)

---

### 2. Opción de Contingencia: PHP (Hosting compartido / Legacy)
Para entornos de hosting compartido donde no se pueda ejecutar Python:
- **PHP 8.1 o superior** con extensiones `curl`, `mbstring` y `json`.

```bash
php -S localhost:8000
```

Abre tu navegador en: [http://localhost:8000](http://localhost:8000)

---

## 🍴 Guía Paso a Paso: Cómo hacer Fork y trabajar en tu propia rama

Para colaborar con tus propios cambios sin alterar el repositorio principal directamente, se utiliza el flujo estándar de **Fork & Pull Request**:

```
[Repositorio Oficial (upstream)] ──(Fork en GitHub)──> [Tu Fork en GitHub (origin)]
             │                                                        │
             │ (git fetch / pull)                                     │ (git push)
             ▼                                                        ▲
  [Tu Copia Local (rama main)] ──(git checkout -b)──> [Tu Rama de Trabajo (feat/...)]
```

### Paso 1: Crear tu Fork en GitHub
1. Ve a [https://github.com/elpulsocivico/pulso-civico](https://github.com/elpulsocivico/pulso-civico).
2. Haz clic en el botón superior derecho **"Fork"**.
3. Confirma la creación en tu cuenta personal (quedará como `https://github.com/<tu-usuario>/pulso-civico`).

---

### Paso 2: Conectar este repositorio local a tu Fork

En tu terminal local dentro de este proyecto:

```bash
# 1. Renombra el repositorio principal como 'upstream' (fuente original)
git remote rename origin upstream

# 2. Agrega tu Fork personal como 'origin' (reemplaza <tu-usuario> con tu usuario de GitHub)
git remote add origin https://github.com/<tu-usuario>/pulso-civico.git

# 3. Verifica que ambos remotos estén configurados correctamente:
git remote -v
```

Deberás ver algo similar a esto:
```text
origin    https://github.com/<tu-usuario>/pulso-civico.git (fetch)
origin    https://github.com/<tu-usuario>/pulso-civico.git (push)
upstream  https://github.com/elpulsocivico/pulso-civico.git (fetch)
upstream  https://github.com/elpulsocivico/pulso-civico.git (push)
```

---

### Paso 3: Crear tu rama de trabajo

**Nunca trabajes directamente en `main`**. Crea siempre una rama temática:

```bash
# Asegúrate de partir de main actualizado
git checkout main
git fetch upstream
git merge upstream/main

# Crea tu rama con un nombre descriptivo
# Ejemplos: feat/nueva-funcionalidad, scraper/congreso-jalisco, fix/error-conteo, docs/guia
git checkout -b feat/mi-primera-contribucion
```

---

### Paso 4: Hacer tus cambios y verificar sintaxis

Edita los archivos necesarios. Antes de hacer commit, valida la sintaxis PHP:

```bash
# Validar archivos específicos con la herramienta interna:
php tools/gateway.php lint "src/Controllers/WebController.php"

# O verificar con PHP nativo:
php -l src/Controllers/WebController.php
```

---

### Paso 5: Guardar tus cambios (Commit)

El proyecto utiliza mensajes semánticos de commit:

```bash
# Agrega ÚNICAMENTE los archivos que modificaste (evita 'git add .')
git add src/Controllers/WebController.php

# Realiza el commit siguiendo el formato estándar:
# Prefijos permitidos: feat:, fix:, scraper:, data:, docs:, refactor:, chore:
git commit -m "feat: agrega soporte para filtrar legisladores por partido en comisiones"
```

---

### Paso 6: Subir la rama a tu Fork en GitHub

```bash
# Sube tu rama a tu repositorio de GitHub
git push -u origin feat/mi-primera-contribucion
```

---

### Paso 7: Abrir un Pull Request (PR)

1. Ve a tu repositorio en GitHub: `https://github.com/<tu-usuario>/pulso-civico`.
2. Verás un cartel verde con el botón **"Compare & pull request"**. Haz clic en él.
3. Asegúrate de que la rama base sea `elpulsocivico/pulso-civico` en `main`.
4. Llena la plantilla del Pull Request describiendo qué cambios hiciste y marcando la lista de verificación (*checklist*).
5. Haz clic en **"Create pull request"**. ¡Un mantenedor revisará y aprobará tus cambios!

---

## 📜 Reglas de Oro para Colaborar

1. **Fuente Oficial Obligatoria:** Todo dato nuevo debe incluir el campo `fuente_url` apuntando a un portal gubernamental oficial.
2. **Neutralidad Absoluta:** No emitir opiniones, adjetivos calificativos ni juicios personales.
3. **URLs Portables:** Nunca uses rutas absolutas como `href="/..."` o `fetch('/...')`. Usa siempre `Router::url('/ruta')` en vistas y controladores.
4. **Seguridad y Escapado:** Escapa siempre las variables en las plantillas HTML con `ViewHelper::e($variable)`.
5. **Cero Secretos:** Jamás subas archivos `.env`, cookies, contraseñas ni credenciales privadas.
6. **Sin Dependencias Innecesarias:** No agregues paquetes de Composer o npm sin discutirlo previamente en un Issue.
7. **Archivos Pequeños:** Mantén las clases y archivos con menos de 400 líneas de código.

---

## 🗂️ Estructura del Proyecto

```text
├── config/              # Configuración general (.env)
├── docs/                # Arquitectura, esquemas de datos y manuales
│   ├── ARQUITECTURA.md  # Detalle del funcionamiento técnico
│   ├── DATA_SCHEMA.md   # Estructura de los archivos JSON
│   ├── ESTADOS_COBERTURA.md # Estado de avance de los 32 estados
│   ├── FUENTES.md       # Directorio de fuentes oficiales
│   └── COMO_CREAR_UN_SCRAPER.md # Guía para nuevos scrapers
├── index.php            # Punto de entrada único (Front Controller)
├── src/
│   ├── Controllers/     # WebController (controladores web)
│   ├── Core/            # Router, HttpClient, ViewHelper, Logger
│   ├── DTO/             # Contratos de datos tipados (Legislador, Votación, Sesión)
│   └── Services/        # Lectura de datos (DataService) y Scrapers
├── storage/
│   ├── data/json/       # Datasets maestros en JSON (la "base de datos")
│   └── raw/             # Caché cruda de HTML y fotos oficiales
├── tools/               # Scripts CLI y runners de mantenimiento
└── views/               # Plantillas PHP/HTML y componentes Bootstrap
```

---

## 🤝 Enlaces y Documentación Adicional

- [CONTRIBUTING.md](CONTRIBUTING.md) — Normas de contribución y flujo de trabajo.
- [AGENTS.md](AGENTS.md) — Instrucciones y restricciones para asistentes de IA (Cursor, Copilot, Claude, Gemini).
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) — Código de conducta y principios de neutralidad.
- [SECURITY.md](SECURITY.md) — Reporte responsable de seguridad.

---

## ⚖️ Licencias

- **Código:** [GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE).
- **Datos generados:** [Creative Commons Atribución 4.0 Internacional (CC BY 4.0)](DATA_LICENSE.md).
