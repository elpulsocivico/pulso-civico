#!/usr/bin/env python3
"""
coahuila_parser.py: Extractor auditable de listas de legisladores del Congreso
del Estado de Coahuila de Zaragoza (LVII a LXII).
"""

import sys
import json
import re
import os
import time
import argparse
import urllib.request
import pypdf

def clean_wiki(s: str) -> str:
    s = re.sub(r'\[\[(?:[^|\]]+\|)?([^\]]+)\]\]', r'\1', s)
    s = re.sub(r'<ref[^>]*>.*?</ref>', '', s)
    s = re.sub(r'<ref[^>]*/>', '', s)
    return s.strip()

def normalizar_partido(raw: str) -> str:
    raw_lower = raw.lower()
    if 'revolucionario institucional' in raw_lower or raw.strip() == 'PRI':
        return 'PRI'
    if 'acción nacional' in raw_lower or raw.strip() == 'PAN':
        return 'PAN'
    if 'movimiento regeneración' in raw_lower or 'morena' in raw_lower:
        return 'MORENA'
    if 'unidad democrática' in raw_lower or 'udc' in raw_lower:
        return 'UDC'
    if 'revolución democrática' in raw_lower or raw.strip() == 'PRD':
        return 'PRD'
    if 'verde ecologista' in raw_lower or 'pvem' in raw_lower:
        return 'PVEM'
    if 'del trabajo' in raw_lower or raw.strip() == 'PT':
        return 'PT'
    if 'nueva alianza' in raw_lower or 'panal' in raw_lower:
        return 'NUEVA ALIANZA'
    if 'socialdemócrata' in raw_lower or 'psd' in raw_lower:
        return 'PSD'
    if 'primero coahuila' in raw_lower or 'ppc' in raw_lower:
        return 'PPC'
    return raw.strip()

def parse_lxii_pdf(pdf_path: str, grupos_pdf: str = None) -> list:
    reader = pypdf.PdfReader(pdf_path)
    text = reader.pages[0].extract_text()
    
    # Mapeo de partidos desde grupos parlamentarios
    partidos_map = {
        'MARÍA EUGENIA G. CALDERÓN AMEZCUA': 'PRI',
        'MARÍA ESPERANZA CHAPA GARCÍA': 'PRI',
        'JESÚS MARÍA MONTEMAYOR GARZA': 'PRI',
        'JORGE ANTONIO ABDALA SERNA': 'PRI',
        'MARÍA GUADALUPE OYERVIDES VALDEZ': 'PRI',
        'RICARDO LÓPEZ CAMPOS': 'PRI',
        'RAÚL ONOFRE CONTRERAS': 'PRI',
        'OLIVIA MARTÍNEZ LEYVA': 'PRI',
        'EDUARDO OLMOS CASTRO': 'PRI',
        'MARIO CEPEDA RAMÍREZ': 'PRI',
        'HÉCTOR HUGO DÁVILA PRADO': 'PRI',
        'EDNA ILEANA DÁVALOS ELIZONDO': 'PRI',
        'LUZ ELENA GUADALUPE MORALES NÚÑEZ': 'PRI',
        'MARÍA BÁRBARA CEPEDA BOEHRINGER': 'PRI',
        'MARTHA LOERA ARÁMBULA': 'PRI',
        'ÁLVARO MOREIRA VALDÉS': 'PRI',
        'MAYRA LUCILA VALDÉS GONZÁLEZ': 'PAN',
        'RODOLFO GERARDO WALSS AURIOLES': 'PAN',
        'LUZ NATALIA VIRGIL ORONA': 'MORENA',
        'LIZBETH OGAZÓN NAVA': 'MORENA',
        'FRANCISCO JAVIER CORTEZ GÓMEZ': 'MORENA',
        'LAURA FRANCISCA AGUILAR TABARES': 'MORENA',
        'TERESA DE JESÚS MERAZ GARCÍA': 'MORENA',
        'CLAUDIA ELVIRA RODRÍGUEZ MARQUEZ': 'PVEM',
        'YOLANDA ELIZONDO MALTOS': 'UDC',
    }

    nombres_raw = re.findall(r'DIP\.\s+([A-ZÁÉÍÓÚÑ\s\.\-]+?)(?=(?:DIP\.|\n\s*DISTRITO|\n\s*DIPUTAD|\Z))', text)
    legisladores = []
    seen = set()
    distrito_idx = 1

    for n in nombres_raw:
        nom = re.sub(r'\s+', ' ', n).strip()
        if not nom or nom in seen or len(nom) < 6:
            continue
        seen.add(nom)
        
        rep = 'Mayoría Relativa' if distrito_idx <= 16 else 'Representación Proporcional'
        dist = f'Distrito {distrito_idx}' if distrito_idx <= 16 else 'Plurinominal'
        distrito_idx += 1

        # Partido
        partido = 'PRI'
        for k, v in partidos_map.items():
            if k in nom or nom in k:
                partido = v
                break

        legisladores.append({
            'nombre_completo': nom,
            'distrito': dist,
            'partido': partido,
            'principio_eleccion': rep,
            'tipo_cargo': 'Propietario',
        })

    return legisladores

def parse_wiki_leg(leg_romano: str, raw_dir: str) -> list:
    os.makedirs(raw_dir, exist_ok=True)
    cache_file = os.path.join(raw_dir, f'wiki_{leg_romano}.json')

    text = ''
    if os.path.exists(cache_file) and os.path.getsize(cache_file) > 1000:
        with open(cache_file, 'r', encoding='utf-8') as f:
            data = json.load(f)
            text = data.get('parse', {}).get('wikitext', {}).get('*', '')
    else:
        title = f'Anexo:{leg_romano}_Legislatura_del_Congreso_del_Estado_de_Coahuila'
        url = f'https://es.wikipedia.org/w/api.php?action=parse&page={title}&prop=wikitext&format=json'
        req = urllib.request.Request(url, headers={'User-Agent': 'PulsoCivico/1.0 (contacto@pulsocivico.mx)'})
        time.sleep(1.0)
        with urllib.request.urlopen(req) as resp:
            content = resp.read().decode('utf-8')
            with open(cache_file, 'w', encoding='utf-8') as f:
                f.write(content)
            data = json.loads(content)
            text = data.get('parse', {}).get('wikitext', {}).get('*', '')

    legisladores = []
    # 1. Mayoría Relativa
    mr_match = re.search(r'===?\s*Mayoría relativa\s*===?(.*?)(?:===|\Z)', text, re.DOTALL)
    if mr_match:
        for r in mr_match.group(1).split('|-'):
            cells = [clean_wiki(l[1:].strip()) for l in r.splitlines() if l.strip().startswith('|') and not l.strip().startswith('|}')]
            if len(cells) >= 3 and not cells[0].startswith('!') and not 'bgcolor' in cells[0]:
                dist = cells[0].replace('align=center|', '').strip()
                cab = cells[1].strip()
                nom = cells[2].strip()
                part = 'Desconocido'
                for c in cells[3:]:
                    p = normalizar_partido(c)
                    if p != 'Desconocido':
                        part = p
                        break
                if nom and len(nom) > 3 and not 'align' in nom:
                    legisladores.append({
                        'nombre_completo': nom,
                        'distrito': f'Distrito {dist}',
                        'municipios': cab,
                        'partido': part,
                        'principio_eleccion': 'Mayoría Relativa',
                        'tipo_cargo': 'Propietario',
                    })

    # 2. Representación Proporcional
    rp_match = re.search(r'===?\s*Representación proporcional\s*===?(.*?)(?:===|\Z)', text, re.DOTALL)
    if rp_match:
        for r in rp_match.group(1).split('|-'):
            cells = [clean_wiki(l[1:].strip()) for l in r.splitlines() if l.strip().startswith('|') and not l.strip().startswith('|}')]
            if len(cells) >= 2 and not cells[0].startswith('!') and not 'bgcolor' in cells[0]:
                nom = cells[0].strip()
                part = 'Desconocido'
                for c in cells[1:]:
                    p = normalizar_partido(c)
                    if p != 'Desconocido':
                        part = p
                        break
                if nom and len(nom) > 3 and not 'align' in nom:
                    legisladores.append({
                        'nombre_completo': nom,
                        'distrito': 'Plurinominal',
                        'partido': part,
                        'principio_eleccion': 'Representación Proporcional',
                        'tipo_cargo': 'Propietario',
                    })

    return legisladores

def main():
    parser = argparse.ArgumentParser(description='Extractor de Coahuila')
    parser.add_argument('--leg', required=True, type=int)
    parser.add_argument('--romano', required=True, type=str)
    parser.add_argument('--pdf', default=None, type=str)
    parser.add_argument('--raw-dir', default='/tmp/coahuila_raw', type=str)
    args = parser.parse_args()

    if args.leg == 62 and args.pdf and os.path.exists(args.pdf):
        res = parse_lxii_pdf(args.pdf)
    else:
        res = parse_wiki_leg(args.romano, args.raw_dir)

    print(json.dumps(res, ensure_ascii=False, indent=2))

if __name__ == '__main__':
    main()
