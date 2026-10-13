#!/usr/bin/env python3
"""
nl_pdf_parser.py: Extractor auditable de listas oficiales de legisladores
del H. Congreso del Estado de Nuevo León en formato PDF (LXXI a LXXIV).
"""

import sys
import json
import re
import argparse
import pypdf

def parse_lxxi(pdf_path: str) -> list:
    reader = pypdf.PdfReader(pdf_path)
    text = '\n'.join([p.extract_text() for p in reader.pages])
    
    cabeceras = r'(CADEREYTA|GPE|MTY|LINARES|SAN NICOLÁS|SAN PEDRO|G\. ESCOBEDO|APODACA|ESCOBEDO|CHINA|HIDALGO|GALEANA|S\. CATARINA|SAB\. HIDALGO|MONTEMORELOS)'
    partidos = r'(PAN|PRI|PT|PRD|NUEVA\s+ALIANZA)'
    pattern = rf'(\d+)\s+(\d+)\s+(.+?)\s+{cabeceras}\s+{partidos}\s+(MAY\.|R\.P\.)'
    
    matches = re.findall(pattern, text)
    legisladores = []
    for m in matches:
        num = int(m[0])
        dist = m[1].strip()
        nombre = re.sub(r'\s+', ' ', m[2]).strip()
        mun = m[3].strip()
        partido = re.sub(r'\s+', ' ', m[4]).strip()
        rep_raw = m[5].strip()
        rep = 'Mayoría Relativa' if 'MAY' in rep_raw else 'Representación Proporcional'
        
        legisladores.append({
            'nombre_completo': nombre,
            'distrito': dist,
            'municipio_cabecera': mun,
            'partido': partido,
            'principio_eleccion': rep,
            'tipo_cargo': 'Propietario',
        })
    return legisladores

def parse_lxxii(pdf_path: str) -> list:
    reader = pypdf.PdfReader(pdf_path)
    text = '\n'.join([p.extract_text() for p in reader.pages])
    
    pattern = r'(PRI|PAN|PRD|PT|PVEM|PNA)\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s]+?)\s*(Mayor[ií]a|proporcional|Proporcional)\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s\(\)]+?)\s*(Propietario)'
    matches = re.findall(pattern, text, re.IGNORECASE)
    legisladores = []
    for m in matches:
        partido = m[0].strip().upper()
        if partido == 'PNA':
            partido = 'NUEVA ALIANZA'
        dist = re.sub(r'\s+', ' ', m[1]).strip()
        rep_raw = m[2].strip().lower()
        rep = 'Mayoría Relativa' if 'mayor' in rep_raw else 'Representación Proporcional'
        nombre = re.sub(r'\s+', ' ', m[3]).strip()
        
        legisladores.append({
            'nombre_completo': nombre,
            'distrito': dist,
            'partido': partido,
            'principio_eleccion': rep,
            'tipo_cargo': 'Propietario',
        })
    return legisladores

def parse_lxxiii(pdf_path: str) -> list:
    reader = pypdf.PdfReader(pdf_path)
    text = '\n'.join([p.extract_text() for p in reader.pages])
    
    # Buscamos bloques de Propietario y opcionalmente su suplente
    # Ejemplo: Primero Partido Acción Nacional Diputado Propietario JUAN ENRIQUE BARRIOS RODRIGUEZ
    pattern = r'(Primero|Segundo|Tercer|Cuarto|Quinto|Sexto|Séptimo|Octavo|Noveno|Décimo\s+[A-Za-záéíóúñÁÉÍÓÚÑ]+|Décimo|Vigésimo\s+[A-Za-záéíóúñÁÉÍÓÚÑ]+|Vigésimo|Distrito\s+\d+)\s*(Partido Acción Nacional|Coalición Compromiso por Nuevo León|Nueva Alianza,?\s*Partido Político Nacional|Partido de la Revolución Democrática|Partido del Trabajo)\s*Diputado\s+Propietario\s*([A-ZÁÉÍÓÚÑ\s\.\'\-]+?)(?=(?:Primero|Segundo|Tercer|Cuarto|Quinto|Sexto|Séptimo|Octavo|Noveno|Décimo|Vigésimo|Distrito|\Z|\n\s*Diputado\s+Suplente|\w+\s*Diputado\s+Suplente))'
    
    # Encontremos todos los Diputado Propietario
    # O por líneas/partes:
    chunks = re.split(r'(?=Diputado\s+Propietario)', text)
    legisladores = []
    
    for i in range(1, len(chunks)):
        chunk = chunks[i]
        prev = chunks[i-1][-120:] if i > 0 else ""
        
        # Extraer nombre propietario
        # Inicia justo después de Diputado Propietario
        m_prop = re.match(r'Diputado\s+Propietario\s*([A-ZÁÉÍÓÚÑ\s\.\'\-]+?)(?=(?:Diputado\s+Suplente|Primero|Segundo|Tercer|Cuarto|Quinto|Sexto|Séptimo|Octavo|Noveno|Décimo|Vigésimo|Distrito|\Z))', chunk)
        if not m_prop:
            continue
        nombre = re.sub(r'\s+', ' ', m_prop.group(1)).strip()
        
        # Extraer partido y distrito del bloque previo
        distrito = "Desconocido"
        partido = "Desconocido"
        
        m_dist = re.search(r'(Primero|Segundo|Tercer|Cuarto|Quinto|Sexto|Séptimo|Octavo|Noveno|Décimo\s+[A-Za-záéíóúñÁÉÍÓÚÑ]+|Décimo|Vigésimo\s+[A-Za-záéíóúñÁÉÍÓÚÑ]+|Vigésimo|Distrito\s+\d+)', prev)
        if m_dist:
            distrito = m_dist.group(1).strip()
            
        m_part = re.search(r'(Partido\s+Acción\s+Nacional|Coalición\s+Compromiso\s+por\s+Nuevo\s+León|Nueva\s+Alianza[^\n]*?Nacional|Partido\s+de\s+la\s+Revolución\s+Democrática|Partido\s+del\s+Trabajo)', prev)
        if m_part:
            part_str = m_part.group(1).strip()
            if 'Acción Nacional' in part_str:
                partido = 'PAN'
            elif 'Compromiso' in part_str:
                partido = 'PRI' # Coalición encabezada por PRI
            elif 'Nueva Alianza' in part_str:
                partido = 'NUEVA ALIANZA'
            elif 'Revolución Democrática' in part_str:
                partido = 'PRD'
            elif 'Trabajo' in part_str:
                partido = 'PT'
            else:
                partido = part_str

        # Suplente en chunk
        suplente = None
        m_sup = re.search(r'Diputado\s+Suplente\s*([A-ZÁÉÍÓÚÑ\s\.\'\-]+?)(?=(?:Primero|Segundo|Tercer|Cuarto|Quinto|Sexto|Séptimo|Octavo|Noveno|Décimo|Vigésimo|Distrito|\Z))', chunk)
        if m_sup:
            suplente = re.sub(r'\s+', ' ', m_sup.group(1)).strip()
            
        rep = 'Representación Proporcional' if 'Distrito ' in distrito else 'Mayoría Relativa'

        legisladores.append({
            'nombre_completo': nombre,
            'distrito': distrito,
            'partido': partido,
            'principio_eleccion': rep,
            'tipo_cargo': 'Propietario',
            'suplente_nombre': suplente,
        })
        
    return legisladores

def parse_lxxiv(pdf_path: str) -> list:
    reader = pypdf.PdfReader(pdf_path)
    full_text = '\n'.join([p.extract_text() for p in reader.pages])
    
    parties = r'(PAN|PRI|PT|PRD|PVEM|MOVIMIENTO\s+CIUDADANO|PMC|MORENA|NUEVA\s+ALIANZA|PANAL|INDEPENDIENTE)'
    pattern = rf'DIP\.\s+([A-ZÁÉÍÓÚÑ\s\.\'\-]+?)\s*\|\s*{parties}'
    matches = re.findall(pattern, full_text)
    
    seen = set()
    legisladores = []
    for m in matches:
        name = re.sub(r'\s+', ' ', m[0]).strip()
        party_raw = re.sub(r'\s+', ' ', m[1]).strip().upper()
        if party_raw == 'PMC':
            party_raw = 'MOVIMIENTO CIUDADANO'
        elif party_raw == 'PANAL':
            party_raw = 'NUEVA ALIANZA'
            
        if name not in seen:
            seen.add(name)
            legisladores.append({
                'nombre_completo': name,
                'partido': party_raw,
                'tipo_cargo': 'Propietario',
                'principio_eleccion': 'No especificado en carátula',
            })
    return legisladores

def main():
    parser = argparse.ArgumentParser(description='Extractor de PDFs del H. Congreso de NL')
    parser.add_argument('--file', required=True, help='Ruta al archivo PDF')
    parser.add_argument('--leg', required=True, type=int, help='Número de legislatura (71, 72, 73, 74)')
    args = parser.parse_args()

    if args.leg == 71:
        res = parse_lxxi(args.file)
    elif args.leg == 72:
        res = parse_lxxii(args.file)
    elif args.leg == 73:
        res = parse_lxxiii(args.file)
    elif args.leg == 74:
        res = parse_lxxiv(args.file)
    else:
        print(json.dumps({'error': f'Legislatura {args.leg} no soportada en modo PDF'}))
        sys.exit(1)

    print(json.dumps(res, ensure_ascii=False, indent=2))

if __name__ == '__main__':
    main()
