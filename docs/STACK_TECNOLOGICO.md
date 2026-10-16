# 🛠️ Stack Tecnológico — Pulso Cívico

> **Filosofía de Arquitectura:** *Simplicidad radical, Alto Rendimiento, Low-Cost y Cero Barreras de Entrada.*  
> Diseñado para que cualquier ciudadano o colaborador pueda clonar y ejecutar el proyecto en menos de 2 minutos, sin necesidad de servidores costosos ni bases de datos engorrosas.

---

## 1. Resumen Ejecutivo del Stack

| Capa | Tecnología | Justificación y Ventaja |
|---|---|---|
| **Backend Primario (Activo)** | **Python 3.10+ (FastAPI + Uvicorn)** | Arquitectura asíncrona (ASGI) de latencia ultra-baja (< 15 ms), tipado estricto con Pydantic y pre-indexación en memoria de más de 3,200 datasets mediante `orjson`. |
| **Motor de Vistas** | **Jinja2 + HTML5 Semántico** | Renderizado server-side ultrarrápido, componentes modulares (`app/templates/partials/`), herencia de layouts y compatibilidad nativa mobile-first. |
| **Almacenamiento y Datos** | **Datasets JSON Planos (`storage/data/json/`)** | Cero motores de bases de datos pesados (sin MySQL ni PostgreSQL). Más de 33,000 registros cívicos y electorales versionados y auditables directamente en Git. |
| **Frontend & UI** | **Bootstrap 5.3 + JavaScript Vanilla** | Componentes responsivos cívicos, buscador global Spotlight (`Ctrl+K`), visualizador SVG interactivo de entidades y cero frameworks SPA pesados (sin compiladores ni bundlers). |
| **Capa Auxiliar & CLI** | **PHP 8.1+ / PowerShell** | Herramientas de auditoría, runner unificado (`tools/run.ps1`), gateway (`tools/gateway.php`) y scrapers de ingesta histórica. |
| **Integridad & Fe Pública** | **Criptografía SHA-256** | Huella criptográfica única (`sha256_evidencia`) en cada ficha y votación ligada a fuentes oficiales (.gob.mx, POEs, DOF, SIL, INEGI). |
| **Despliegue & Costo** | **Low-Cost / Zero-Cost** | Corre en cualquier VPS modesto ($3-5 USD/mes) o contenedor Docker ligero. Cero dependencias de servicios de pago en la nube. |

---

## 2. Requisitos de Entorno para Desarrolladores

### Opción Principal: Python (FastAPI + Jinja2) — *Entorno Activo*
1. **Python 3.10 o superior**.
2. **Git**.

#### Puesta en marcha en 3 pasos:
```bash
# 1. Copiar archivo de variables de entorno
cp .env.example .env

# 2. Instalar dependencias
pip install -r requirements.txt

# 3. Iniciar el servidor web con recarga automática
uvicorn app.main:app --reload --port 8000
```
Abre tu navegador en: [http://localhost:8000](http://localhost:8000)

---

### Opción de Contingencia: PHP (Hosting compartido / Legacy)
Para entornos de hosting compartido donde no sea viable ejecutar Python:
- **PHP 8.1 o superior** con extensiones nativas (`curl`, `mbstring`, `json`).
```bash
php -S localhost:8000
```

---

## 3. ¿Por qué este Stack Moderno en Python y JSON?

1. **Velocidad y Concurrencia:** FastAPI y Uvicorn ofrecen un rendimiento comparable a NodeJS y Go, permitiendo consultas instantáneas y búsquedas globales en memoria entre miles de expedientes cívicos.
2. **Facilidad de Auditoría:** Al almacenar la información en archivos JSON estructurados y versionados, cualquier persona puede auditar exactamente qué dato cambió, cuándo y con qué commit de Git.
3. **Mantenibilidad para IA y Humanos:** Tanto desarrolladores de la comunidad como asistentes de Inteligencia Artificial leen, prueban y extienden el código con máxima precisión gracias a los contratos de tipo Pydantic y una estructura modular limpia.
