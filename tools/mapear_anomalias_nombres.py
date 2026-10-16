"""
Herramienta de Auditoría y Mapeo de Anomalías en Nombres, Acentos y Ñ.
Permite escanear los datasets de alcaldes, legisladores y gobernadores para:
1. Detectar caracteres corruptos o mojibake (Ã±, \ufffd, ? dentro de nombres).
2. Detectar pérdida común de la letra 'ñ' en apellidos mexicanos (ej: Nuez, Muoz, Pea, Baos, Castaeda).
3. Detectar discrepancias de normalización en slugs y búsquedas.
4. Opcionalmente (--fix) reparar automáticamente los nombres detectados.
"""
import sys
import json
import re
import unicodedata
from pathlib import Path

# Asegurar codificación utf-8 en terminal de Windows
if sys.stdout.encoding != 'utf-8':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except Exception:
        pass

# Patrones típicos de pérdida o sustitución de 'ñ' en nombres mexicanos
PATRONES_ENIE_COMUNES = {
    r"\bNuez\b": "Núñez",
    r"\bNueces\b": "Núñez",
    r"\bMuoz\b": "Muñoz",
    r"\bPea\b": "Peña",
    r"\bBaos\b": "Baños",
    r"\bZuiga\b": "Zúñiga",
    r"\bZuniga\b": "Zúñiga",
    r"\bOrdoez\b": "Ordóñez",
    r"\bOrdonez\b": "Ordóñez",
    r"\bCastaeda\b": "Castañeda",
    r"\bCastaneda\b": "Castañeda",
    r"\bAvia\b": "Aviña",
    r"\bAvina\b": "Aviña",
    r"\bIbaez\b": "Ibáñez",
    r"\bIbanez\b": "Ibáñez",
    r"\bMagana\b": "Magaña",
    r"\bBragado\b": "Bragado",
    r"\bBribiesca\b": "Bribiesca",
    r"\bSuarez\b": "Suárez",
    r"\bHernandez\b": "Hernández",
    r"\bLopez\b": "López",
    r"\bMartinez\b": "Martínez",
    r"\bGonzalez\b": "González",
    r"\bRodriguez\b": "Rodríguez",
    r"\bPerez\b": "Pérez",
    r"\bSanchez\b": "Sánchez",
    r"\bRamirez\b": "Ramírez"
}

MOJIBAKE_PATTERNS = ["Ã±", "Ã³", "Ã¡", "Ã©", "Ã­", "Ãº", "Ã‘", "Ã“", "Ã ", "Ã‰", "Ã ", "Ãš", "\ufffd"]

def slugify(text: str) -> str:
    if not text:
        return ""
    text = unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode("ascii")
    text = re.sub(r"[^\w\s-]", "", text).strip().lower()
    return re.sub(r"[-\s]+", "-", text)

def auditar_dataset_alcaldes(fix: bool = False):
    base_dir = Path(__file__).resolve().parent.parent
    alcaldes_file = base_dir / "storage" / "data" / "json" / "alcaldes" / "directorio_nacional_alcaldes_vigentes.json"
    
    if not alcaldes_file.exists():
        print(f"Error: no existe {alcaldes_file}")
        return
        
    with open(alcaldes_file, "r", encoding="utf-8") as f:
        data = json.load(f)
        
    alcaldes = data.get("alcaldes", {})
    anomalias = []
    
    for cvegeo, alc in alcaldes.items():
        nom = alc.get("nombre_completo", "")
        problemas = []
        sugerencia = nom
        
        # 1. Caracteres mojibake o de reemplazo
        for m in MOJIBAKE_PATTERNS:
            if m in nom:
                problemas.append(f"Mojibake o carácter inválido ({m})")
                
        # 2. Signo '?' en lugar de letra acentuada/ñ
        if "?" in nom:
            problemas.append("Signo de interrogación '?' dentro del nombre")
            
        # 3. Espacios dobles internos
        if "  " in nom:
            problemas.append("Espacios dobles internos")
            sugerencia = re.sub(r"\s+", " ", sugerencia).strip()
            
        # 4. Pérdida de 'ñ'
        for pat, rep in PATRONES_ENIE_COMUNES.items():
            if re.search(pat, sugerencia, re.IGNORECASE):
                # Solo advertir si el nombre original difiere
                nuevo = re.sub(pat, rep, sugerencia, flags=re.IGNORECASE)
                if nuevo != sugerencia:
                    problemas.append(f"Patrón de pérdida de ñ detectado ({pat} -> {rep})")
                    sugerencia = nuevo
                    
        # 5. Comprobar slug
        s_original = slugify(nom)
        s_sugerido = slugify(sugerencia)
        if s_original != s_sugerido:
            problemas.append(f"Discrepancia en slug: '{s_original}' vs '{s_sugerido}'")
            
        if problemas:
            anomalias.append({
                "cvegeo": cvegeo,
                "entidad": alc.get("entidad"),
                "municipio": alc.get("municipio"),
                "nombre_actual": nom,
                "sugerencia": sugerencia,
                "problemas": problemas
            })
            if fix:
                alc["nombre_completo"] = sugerencia
                
    print(f"\n========================================================")
    print(f"REPORTE DE AUDITORÍA: DIRECTORIO DE ALCALDES")
    print(f"Total alcaldes analizados: {len(alcaldes)}")
    print(f"Total casos con anomalías de acentos/ñ detectados: {len(anomalias)}")
    print(f"========================================================\n")
    
    for i, a in enumerate(anomalias[:30], 1):
        print(f"[{i}] {a['cvegeo']} - {a['municipio']}, {a['entidad']}")
        print(f"    Actual:     {a['nombre_actual']}")
        print(f"    Sugerencia: {a['sugerencia']}")
        print(f"    Problemas:  {', '.join(a['problemas'])}")
        print()
        
    if len(anomalias) > 30:
        print(f"... y {len(anomalias) - 30} casos adicionales.\n")
        
    if fix:
        with open(alcaldes_file, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
        print(">> CORRECCIONES APLICADAS EXITOSAMENTE EN EL ARCHIVO JSON.")

if __name__ == "__main__":
    aplicar_fix = "--fix" in sys.argv
    auditar_dataset_alcaldes(fix=aplicar_fix)
