import os
import json
from pathlib import Path

def audit_datasets():
    data_dir = Path("storage/data/json")
    mojibake_patterns = [
        "Ã±", "Ã³", "Ã¡", "Ã©", "Ã­", "Ãº", "Ã‘", "Ã“", "Ã", "Ã‰", "Ã", "Ãš", "\ufffd"
    ]
    
    anomalies = []
    total_files = 0
    
    for p in data_dir.glob("**/*.json"):
        total_files += 1
        try:
            content = p.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            try:
                # Intento con latin-1 para verificar si el archivo está en codificación incorrecta
                raw_bytes = p.read_bytes()
                content = raw_bytes.decode("latin-1")
                anomalies.append({
                    "file": str(p.relative_to(data_dir)),
                    "type": "ENCODING_NOT_UTF8",
                    "details": "El archivo no se puede decodificar como UTF-8 directo"
                })
            except Exception as e:
                anomalies.append({
                    "file": str(p.relative_to(data_dir)),
                    "type": "READ_ERROR",
                    "details": str(e)
                })
                continue
                
        # Buscar patrones de mojibake (doble codificación UTF-8 / Latin-1)
        encontrados = {}
        for pat in mojibake_patterns:
            cnt = content.count(pat)
            if cnt > 0:
                encontrados[pat] = cnt
                
        if encontrados:
            anomalies.append({
                "file": str(p.relative_to(data_dir)),
                "type": "MOJIBAKE_OR_REPLACEMENT",
                "patterns": encontrados
            })
            
    print(f"Total archivos escaneados: {total_files}")
    print(f"Archivos con anomalías de codificación: {len(anomalies)}")
    for a in anomalies:
        print(json.dumps(a, ensure_ascii=False, indent=2))

if __name__ == "__main__":
    audit_datasets()
