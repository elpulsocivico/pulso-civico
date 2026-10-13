# Issue #15: feat(ui) - Conmutador de legislaturas en perfil de político y enlaces contextuales en directorios

**Estado:** 📋 PENDIENTE (En cola tras Issue #14)  
**Proyecto GitHub:** Frontend y Visualización  
**Rama:** `feat/ui-conmutador-legislaturas-politico`  
**Responsable:** Frontend / Visualización

---

## 1. Contexto y Justificación
Con la data del Issue #14 expuesta en el controlador, la vista del político (`views/politico.php`) necesita un conmutador visual limpio (móvil-primero) para permitir al usuario alternar entre legislaturas sin recargas pesadas ni confusión temporal.
Asimismo, las tarjetas en directorios de legislaturas pasadas deben enlazar con el parámetro `?leg=XX` correspondiente.

## 2. Requerimientos Técnicos
1. **Conmutador de Pastillas en `views/politico.php`:**
   - Si `$legislaturasDisponibles` contiene más de un elemento, renderizar una barra tipo pastilla (`nav-pills` o botones redondeados):
     `[LXVI Legislatura (Actual)] [LXIV Legislatura (2018–2021)]`
   - Resaltar con clase activa la legislatura actualmente seleccionada.
   - Enlaces generados con `Router::url('/politicos/' . $slug . '?leg=' . $lNum)`.
2. **Aviso contextual histórico:**
   - Si `$numLeg !== 66`, mostrar una alerta discreta debajo del hero:
     `🏛️ Estás consultando el expediente histórico oficial durante la LXIV Legislatura (2018–2021). [Ver periodo actual]`.
3. **Enlaces en directorios:**
   - En `views/home.php`, `views/estado.php` y `views/legislatura.php`, asegurar que el botón *"Ver perfil oficial →"* conserve la legislatura activa del contexto:
     `<?= Router::url('/politicos/' . $p['slug'] . ($numLeg !== 66 ? '?leg=' . $numLeg : '')) ?>`.

## 3. Criterios de Aceptación
- [ ] Diseño 100% responsive probado en móvil y escritorio.
- [ ] Cero enlaces duros con `/...` (uso estricto de `Router::url()`).
- [ ] Navegación intuitiva de 1 toque para cambiar de legislatura.
