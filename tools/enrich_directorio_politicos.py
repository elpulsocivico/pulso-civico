"""
Script de unificación y enriquecimiento del Directorio de Políticos.
Integra:
1. Catálogo histórico federal (directorio_politicos_index.json)
2. Hitos municipales y estatales oficiales (indice_cargos_multinivel.json)
3. Directorio nacional de 2,474 alcaldes constitucionales vigentes 2024-2027 (directorio_nacional_alcaldes_vigentes.json)
Corrige falsos positivos donde municipio era nulo y enriquece badges con municipio y periodo exacto.
"""
import json
import re
import unicodedata
from pathlib import Path

def slugify(text: str) -> str:
    if not text:
        return ""
    text = unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode("ascii")
    text = re.sub(r"[^\w\s-]", "", text).strip().lower()
    return re.sub(r"[-\s]+", "-", text)

def normalizar_entidad(nombre: str) -> str:
    s = slugify(nombre)
    s = re.sub(r"^(?:el|la|los|las)\s+", "", s)
    reemplazos = {
        "coahuila-de-zaragoza": "coahuila",
        "michoacan-de-ocampo": "michoacan",
        "veracruz-de-ignacio-de-la-llave": "veracruz",
        "estado-de-mexico": "estado-de-mexico",
        "mexico": "estado-de-mexico",
        "cdmx": "ciudad-de-mexico",
        "distrito-federal": "ciudad-de-mexico",
    }
    return reemplazos.get(s, s)

def formatear_nombre_natural(nombre_sil: str) -> str:
    if not nombre_sil:
        return ""
    partes = [p.strip() for p in nombre_sil.split() if p.strip()]
    if len(partes) >= 3:
        # Típicamente los 2 primeros son apellidos y el resto nombres
        return f"{' '.join(partes[2:])} {partes[0]} {partes[1]}".title()
    return nombre_sil.title()

def main():
    base_dir = Path(__file__).resolve().parent.parent
    cache_file = base_dir / "storage" / "cache" / "directorio_politicos_index.json"
    multinivel_file = base_dir / "storage" / "data" / "json" / "indice_cargos_multinivel.json"
    alcaldes_file = base_dir / "storage" / "data" / "json" / "alcaldes" / "directorio_nacional_alcaldes_vigentes.json"

    print("Cargando archivos base...")
    with open(cache_file, "r", encoding="utf-8") as f:
        directorio_base = json.load(f)
    print(f"Registros en directorio actual: {len(directorio_base)}")

    indice_multi = {}
    if multinivel_file.exists():
        with open(multinivel_file, "r", encoding="utf-8") as f:
            raw_multi = json.load(f)
            indice_multi = raw_multi.get("indice", {})
    print(f"Hitos en indice_multinivel: {len(indice_multi)}")

    alcaldes_dict = {}
    if alcaldes_file.exists():
        with open(alcaldes_file, "r", encoding="utf-8") as f:
            raw_alc = json.load(f)
            alcaldes_dict = raw_alc.get("alcaldes", {})
    print(f"Alcaldías vigentes en directorio nacional: {len(alcaldes_dict)}")

    # Mapeo rápido de alcaldes vigentes por slug y por nombre normalizado
    alcaldes_vigentes_by_slug = {}
    for cvegeo, alc in alcaldes_dict.items():
        s = slugify(alc.get("nombre_completo") or "")
        alcaldes_vigentes_by_slug[s] = (cvegeo, alc)

    # 1. Enriquecer y corregir políticos existentes en el directorio
    slugs_existentes = set()
    modificados = 0
    falsos_positivos_removidos = 0

    for pol in directorio_base:
        p_slug = pol.get("slug") or slugify(pol.get("nombre_completo", ""))
        slugs_existentes.add(p_slug)
        roles = pol.setdefault("roles_flags", {})
        badges = pol.setdefault("cargos_badges", [])
        
        # Revisar si tiene hitos oficiales en indice_multinivel
        hitos = indice_multi.get(p_slug, [])
        hitos_municipales = [h for h in hitos if h.get("nivel") == "municipal"]

        # Buscar si fue Alcalde según historial oficial
        alcalde_hitos = [
            h for h in hitos_municipales
            if any(term in (h.get("cargo") or "").lower() for term in ["presidente municipal", "alcalde", "jefe delegacional"])
            and not any(term in (h.get("cargo") or "").lower() for term in ["asesor", "secretario", "coordinador"])
        ]
        edil_hitos = [
            h for h in hitos_municipales
            if any(term in (h.get("cargo") or "").lower() for term in ["regidor", "sindico", "cabildo"])
        ]

        if alcalde_hitos:
            roles["alcalde"] = True
            # Tomar el más reciente o relevante
            h_rec = alcalde_hitos[-1]
            mun = h_rec.get("municipio") or pol.get("municipio_alcalde") or ""
            periodos = sorted(list({h.get("periodo") for h in alcalde_hitos if h.get("periodo")}))
            per_str = " / ".join(periodos) if periodos else h_rec.get("periodo") or ""
            
            pol["municipio_alcalde"] = mun
            # Actualizar o agregar badge de alcalde
            badge_label = f"Alcalde • {mun}" if mun else "Alcalde"
            if per_str:
                badge_label += f" ({per_str})"
            
            # Reemplazar badge de alcalde existente
            badge_encontrado = False
            for b in badges:
                if b.get("tipo") == "alcalde":
                    b["label"] = badge_label
                    b["municipio"] = mun
                    b["periodo"] = per_str
                    badge_encontrado = True
                    break
            if not badge_encontrado:
                badges.append({
                    "label": badge_label,
                    "icono": "bi-houses",
                    "tipo": "alcalde",
                    "badge_class": "bg-warning-subtle text-warning-emphasis border border-warning-subtle",
                    "municipio": mun,
                    "periodo": per_str
                })
            modificados += 1

        elif roles.get("alcalde"):
            mun = pol.get("municipio_alcalde")
            # Si el municipio es nulo/vacío o es un caso conocido de falso positivo (asesores/regidores)
            if not mun or mun in ("None", ""):
                # Comprobar si era edil (Regidor o Síndico)
                if edil_hitos or roles.get("edil"):
                    roles["alcalde"] = False
                    roles["edil"] = True
                    # Quitar badge de alcalde
                    pol["cargos_badges"] = [b for b in badges if b.get("tipo") != "alcalde"]
                    # Agregar badge de edil si no está
                    if not any(b.get("tipo") == "edil" for b in pol["cargos_badges"]):
                        h_ed = edil_hitos[-1] if edil_hitos else {}
                        nom_ed = "Regidor" if "regidor" in (h_ed.get("cargo") or "").lower() else "Síndico"
                        mun_ed = h_ed.get("municipio") or ""
                        per_ed = h_ed.get("periodo") or ""
                        lbl = f"{nom_ed} • {mun_ed}" if mun_ed else nom_ed
                        if per_ed: lbl += f" ({per_ed})"
                        pol["cargos_badges"].append({
                            "label": lbl,
                            "icono": "bi-people",
                            "tipo": "edil",
                            "badge_class": "bg-secondary-subtle text-dark border border-secondary-subtle",
                            "municipio": mun_ed,
                            "periodo": per_ed
                        })
                    falsos_positivos_removidos += 1
                else:
                    # Sin municipio comprobable ni hito
                    roles["alcalde"] = False
                    pol["cargos_badges"] = [b for b in badges if b.get("tipo") != "alcalde"]
                    falsos_positivos_removidos += 1
            else:
                # Tiene municipio, enriquecer el label del badge
                for b in badges:
                    if b.get("tipo") == "alcalde":
                        if not b.get("label") or b.get("label") == "Alcalde":
                            b["label"] = f"Alcalde • {mun}"
                            b["municipio"] = mun

    print(f"Modificados con datos oficiales: {modificados}")
    print(f"Falsos positivos de alcalde corregidos (sin municipio): {falsos_positivos_removidos}")

    # 2. Agregar los alcaldes vigentes 2024-2027 que no estén aún en el directorio
    nuevos_alcaldes = 0
    alcaldes_actualizados = 0

    for cvegeo, alc in alcaldes_dict.items():
        nom_comp = (alc.get("nombre_completo") or "").strip()
        if not nom_comp:
            continue
        p_slug = slugify(nom_comp)
        
        mun_nom = alc.get("municipio") or ""
        ent_nom = alc.get("entidad") or "México"
        ent_slug = normalizar_entidad(ent_nom)
        partido = alc.get("partido") or "Sin Partido"
        periodo = alc.get("periodo") or "2024-2027"
        genero = alc.get("genero") or "M"
        titulo_cargo = "Presidenta Municipal" if genero == "F" else "Presidente Municipal"
        badge_cargo = f"{'Alcaldesa' if genero == 'F' else 'Alcalde'} • {mun_nom} ({periodo})"

        if p_slug in slugs_existentes:
            # Buscar el registro y asegurar que tenga roles_flags.alcalde = True y periodo vigente
            for pol in directorio_base:
                if (pol.get("slug") or slugify(pol.get("nombre_completo", ""))) == p_slug:
                    pol.setdefault("roles_flags", {})["alcalde"] = True
                    pol["municipio_alcalde"] = mun_nom
                    pol["es_activo"] = True
                    pol["estatus_badge_label"] = f"En funciones ({periodo})"
                    
                    # Actualizar badges
                    b_alcs = [b for b in pol.get("cargos_badges", []) if b.get("tipo") == "alcalde"]
                    if b_alcs:
                        b_alcs[0]["label"] = badge_cargo
                        b_alcs[0]["municipio"] = mun_nom
                        b_alcs[0]["periodo"] = periodo
                    else:
                        pol.setdefault("cargos_badges", []).append({
                            "label": badge_cargo,
                            "icono": "bi-houses",
                            "tipo": "alcalde",
                            "badge_class": "bg-warning-subtle text-warning-emphasis border border-warning-subtle",
                            "municipio": mun_nom,
                            "periodo": periodo
                        })
                    alcaldes_actualizados += 1
                    break
        else:
            # Nuevo político para el directorio ciudadano
            nom_nat = nom_comp
            nom_enc = nom_comp.replace(" ", "+")
            foto_url = f"https://ui-avatars.com/api/?name={nom_enc}&background=E5E5EA&color=1C1C1E"
            
            nuevo_item = {
                "slug": p_slug,
                "nombre_completo": nom_comp,
                "nombre_natural": nom_nat,
                "partido": partido,
                "partido_slug": slugify(partido),
                "camara": "Alcaldía",
                "cargo": titulo_cargo,
                "entidad": ent_nom,
                "entidad_slug": ent_slug,
                "municipio": mun_nom,
                "municipio_alcalde": mun_nom,
                "periodo": periodo,
                "referencia_sil": "",
                "foto_oficial_url": "",
                "foto_url": foto_url,
                "es_activo": True,
                "estatus_badge_label": f"En funciones ({periodo})",
                "cargo_resumen": f"{titulo_cargo} de {mun_nom} • Periodo {periodo}",
                "roles_flags": {
                    "senador": False,
                    "diputado_federal": False,
                    "diputado_local": False,
                    "alcalde": True,
                    "edil": False,
                    "gobernador": False,
                    "cambio_partido": False
                },
                "cargos_badges": [{
                    "label": badge_cargo,
                    "icono": "bi-houses",
                    "tipo": "alcalde",
                    "badge_class": "bg-warning-subtle text-warning-emphasis border border-warning-subtle",
                    "municipio": mun_nom,
                    "periodo": periodo
                }],
                "legislaturas_lista": [],
                "tiene_foto_local": False,
                "legislatura": 0,
                "cvegeo": cvegeo
            }
            directorio_base.append(nuevo_item)
            slugs_existentes.add(p_slug)
            nuevos_alcaldes += 1

    print(f"Alcaldes vigentes vinculados a políticos existentes: {alcaldes_actualizados}")
    print(f"Nuevos alcaldes vigentes incorporados al directorio: {nuevos_alcaldes}")
    print(f"Total final en el directorio: {len(directorio_base)}")

    # Guardar índice enriquecido
    temp_target = cache_file.with_suffix(".tmp")
    with open(temp_target, "w", encoding="utf-8") as f:
        json.dump(directorio_base, f, ensure_ascii=False, indent=None, separators=(',', ':'))
    temp_target.replace(cache_file)
    print("Índice directorio_politicos_index.json actualizado exitosamente!")

if __name__ == "__main__":
    main()
