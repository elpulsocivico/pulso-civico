# Issue #14: feat(core) - Indexar historial de legislaturas por político en DataService y soportar ?leg=XX

**Estado:** 🚧 EN PROGRESO  
**Proyecto GitHub:** Core, Auditoría y Herramientas CLI  
**Rama:** `feat/backend-trazabilidad-legislaturas`  
**Responsable:** Core / Backend

---

## 1. Contexto y Justificación
Actualmente los perfiles de los legisladores se indexan únicamente bajo `/politicos/{slug}` sin persistir de forma estructurada qué cargos y comisiones tuvieron en legislaturas anteriores (ej. LXVI, LXIV, LXII o congresos locales de Tamaulipas y Baja California).
Al navegar desde un directorio histórico (ej. LXIV 2018–2021), el usuario requiere ver la foto fija y el desempeño de ese político en ese periodo constitucional exacto.

## 2. Requerimientos Técnicos
1. **Indexación agrupada por Persona en `src/Services/DataService.php`:**
   - Agrupar por identidad (`nombre_completo` normalizado) todos los mandatos que un político tiene en los datasets cargados (`congreso_union_leg_*.json`, `congreso_tamaulipas_leg_*.json`, `congreso_bajacalifornia_leg_25.json`).
   - Inyectar en el político la lista de sus periodos: `'legislaturas_disponibles' => [66, 64, ...]`.
   - Almacenar el detalle por mandato: `'mandatos_historicos' => [ 64 => [ ...comisiones, partido, cargo... ], 66 => [ ... ] ]`.
2. **Soporte de consulta parametrizada:**
   - Permitir `DataService::getPoliticoBySlug(string $slug, int $leg = 66)`.
   - Si `$leg` es proporcionado y existe en sus mandatos históricos, hidratar el perfil con las comisiones, partido, suplente y foto de esa legislatura.
3. **Controlador `src/Controllers/WebController.php`:**
   - En `WebController::politico(array $params)`:
     - Leer `$_GET['leg']`.
     - Pasar `$numLeg` y `$legislaturasDisponibles` a la vista `views/politico.php`.

## 3. Criterios de Aceptación
- [ ] `powershell -File .\tools\run.ps1 lint "src/Services/DataService.php,src/Controllers/WebController.php"` pasa sin errores de sintaxis (0 errores).
- [ ] Consultar `/politicos/sylvia-garfias` entrega sus datos activos por defecto.
- [ ] Consultar `/politicos/sylvia-garfias?leg=64` entrega sus comisiones oficiales de la LXIV Legislatura (ej. Secretaria de Derechos Humanos 2018–2021).
- [ ] Cero regresiones en `/` y `/estados/{slug}`.
