# AGENTS.md — Reglas para asistentes de IA

> Este archivo lo leen automáticamente Cursor, Claude Code, GitHub Copilot, Gemini/Antigravity, Codex y otros agentes.
> Si usas ChatGPT u otra IA de chat, **pégale este archivo al inicio de la conversación**.

## Contexto del proyecto y Entorno Activo
- **ENTORNO ACTIVO EN ESTA PC: PYTHON (FastAPI + Jinja2)**.
  - El servidor que se ejecuta y visualiza localmente en esta máquina corre en **Python / FastAPI (`app/main.py`)** con plantillas en `app/templates/`.
  - **REGLA ESTRICTA**: Todo desarrollo nuevo de backend, endpoints, vistas y lógica visual DEBE realizarse en **Python** dentro del directorio `app/` (`app/routers/`, `app/services/`, `app/templates/`), NO en PHP (`src/`, `views/`), para que los cambios se reflejen de inmediato en la aplicación en ejecución.
- Principio rector: **neutralidad**. Todo dato mostrado debe provenir de una fuente oficial o nota periodística **enlazada**. Nunca generar opiniones, calificativos ni datos inventados.

## Mapa del código
| Ruta | Qué contiene | ¿Tocar? |
|---|---|---|
| `index.php` | Front controller | Rara vez |
| `src/Core/` | Router, HttpClient, Logger, ViewHelper | Solo con issue aprobado |
| `src/Controllers/` | Controladores web | Sí |
| `src/Services/` | Lógica de datos y scrapers | Sí |
| `src/DTO/` | Contratos de datos tipados | **Solo con aprobación** (rompe datasets) |
| `views/` | Plantillas PHP/HTML | Sí |
| `config/config.php` | Configuración (lee `.env`) | Con cuidado |
| `tools/` | Runner y utilidades CLI | Sí |
| `storage/data/` | Datasets JSON generados | Solo vía scrapers |
| `storage/raw/` | Descargas crudas (ignorado en git salvo fotos) | No |

## Reglas obligatorias
1. **`declare(strict_types=1);`** en todo archivo PHP. No eliminar tipos.
2. **Sin dependencias nuevas** (Composer/npm) sin issue aprobado. Usar funciones nativas de PHP/JS.
3. **URLs portables**: nunca `href="/..."` ni `fetch('/...')`. Usar `Router::url('/ruta')`. En PHP, rutas con `__DIR__`.
4. **Escapar toda salida** en vistas con `ViewHelper::e()`.
5. **Cero secretos** en código. Configuración vía `.env` (ver `.env.example`).
6. **Archivos < 400 líneas**. Si una clase crece más, dividirla.
7. **Lógica reutilizable** → servicio/helper, nunca copiar-pegar.
8. **Firmas existentes**: parámetros nuevos siempre opcionales con valor por defecto.
9. **Prohibido** escribir `// ... resto del código igual` o resumir código existente. Entregar archivos completos o diffs exactos.
10. **Scrapers**: respetar `rate_limit_ms` (≥ 1000 ms), no saturar servidores de gobierno, guardar siempre `fuente_url` y `fecha_extraccion`.
11. **Datos personales**: solo información pública del cargo (nada de domicilios particulares, familiares ni datos sensibles).

## Verificación antes de entregar
```bash
php tools/gateway.php lint "ruta/archivo1.php,ruta/archivo2.php"
php tools/gateway.php health
```
En Windows también: `powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 lint "<archivos>"`

Si hay error de sintaxis, **corregirlo antes de proponer el cambio**.

## Commits
Prefijos: `feat:`, `fix:`, `data:`, `scraper:`, `docs:`, `refactor:`, `chore:`.
Nunca `git add .` — agregar solo archivos modificados.

## Documentación de referencia
- Arquitectura: `docs/ARQUITECTURA.md`
- Formato de datos: `docs/DATA_SCHEMA.md`
- Crear scraper: `docs/COMO_CREAR_UN_SCRAPER.md`
- Fuentes: `docs/FUENTES.md`
