import json
import re
from pathlib import Path

# ===== Configuración fija (sin CLI) usando el directorio del script =====

# Directorio base: donde está este script
BASE_DIR = Path(__file__).resolve().parent

# Archivos de entrada/salida (resueltos respecto a BASE_DIR)
INPUT_JSON = BASE_DIR / "precios-detalle.json"
OUTPUT_JSON = BASE_DIR / "precios-detalle_filtrados.json"
CODIGOS_TXT = BASE_DIR / "codigos_a_eliminar.txt"

# (Opcional) Descomenta la siguiente línea si quieres que el script cambie
# su directorio de trabajo al del script al inicio (para rutas relativas).
# import os
# os.chdir(BASE_DIR)

def cargar_codigos_a_eliminar(txt_path: Path) -> set:
    """Carga la lista de códigos a eliminar desde un TXT (solo números)."""
    if not txt_path.exists():
        print(f"⚠️ No se encontró {txt_path}. Se usarán códigos vacíos.")
        return set()
    with open(txt_path, "r", encoding="utf-8") as f:
        text = f.read()
    codigos = set(re.findall(r"\d+", text))
    print(f"✔ Códigos a eliminar cargados: {len(codigos)}")
    return codigos

def cargar_json(path: Path):
    """Carga un archivo JSON y devuelve una lista de objetos (tu estructura)."""
    if not path.exists():
        print(f"⚠️ Archivo no encontrado: {path}")
        return []
    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)

    if isinstance(data, list):
        return data
    if isinstance(data, dict):
        # Intentar wrappers comunes
        for key in ["data", "tipologias", "items"]:
            if key in data and isinstance(data[key], list):
                return data[key]
        return [data]
    return []

def filtrar_precios_detalle_flat(items, codigos_eliminar: set):
    """
    Filtra los elementos cuyo producto_codigo esté en codigos_eliminar.
    Mantiene la lista tal como está, eliminando solo los ítems correspondientes.
    """
    filtrados = []
    for item in items:
        codigo = item.get("producto_codigo")
        if codigo is not None and str(codigo) in codigos_eliminar:
            # Este elemento debe eliminarse; lo ignoramos
            continue
        filtrados.append(item)
    return filtrados

def salvar_json(obj, path: Path):
    path.parent.mkdir(parents=True, exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(obj, f, ensure_ascii=False, indent=2)

def main():
    codigos_eliminar = cargar_codigos_a_eliminar(CODIGOS_TXT)
    datos = cargar_json(INPUT_JSON)

    if not datos:
        print("No hay datos para procesar.")
        return

    datos_filtrados = filtrar_precios_detalle_flat(datos, codigos_eliminar)

    salvar_json(datos_filtrados, OUTPUT_JSON)
    print(f"✅ Archivo filtrado guardado en: {OUTPUT_JSON.resolve()}")

    # Resumen rápido
    original_count = len(datos)
    filtrados_count = len(datos_filtrados)
    eliminated_count = original_count - filtrados_count
    print("Resumen:")
    print(f"- Originales: {original_count} elementos")
    print(f"- Filtrados: {filtrados_count} elementos")
    print(f"- Eliminados: {eliminated_count} elementos")

if __name__ == "__main__":
    main()

