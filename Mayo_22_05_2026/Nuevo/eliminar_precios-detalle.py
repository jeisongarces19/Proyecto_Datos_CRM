import json
import re
from pathlib import Path

# ============================================================
# CONFIGURACIÓN
# ============================================================

# Carpeta donde está este archivo .py
BASE_DIR = Path(__file__).resolve().parent

# Archivos de entrada y salida
INPUT_JSON = BASE_DIR / "precios-detalle.json"
OUTPUT_JSON = BASE_DIR / "precios-detalle_filtrados.json"
OUTPUT_ELIMINADOS_JSON = BASE_DIR / "precios-detalle_eliminados.json"
CODIGOS_TXT = BASE_DIR / "codigos_a_eliminar.txt"


def normalizar_codigo(valor) -> str:
    """
    Convierte cualquier código a texto limpio.
    Ejemplo:
    - 22000132407 -> "22000132407"
    - " 22000132407 " -> "22000132407"
    """
    if valor is None:
        return ""

    return str(valor).strip()


def cargar_codigos_a_eliminar(txt_path: Path) -> set:
    """
    Carga la lista de códigos a eliminar desde un TXT.
    Acepta códigos separados por saltos de línea, comas, espacios, etc.
    """
    if not txt_path.exists():
        print(f"⚠️ No se encontró el archivo de códigos: {txt_path}")
        print("⚠️ No se eliminará ningún código.")
        return set()

    text = txt_path.read_text(encoding="utf-8", errors="ignore")

    # Extrae secuencias numéricas
    codigos = set(re.findall(r"\d+", text))

    print(f"✔ Códigos a eliminar cargados: {len(codigos)}")

    if len(codigos) == 0:
        print("⚠️ El archivo existe, pero no se encontraron códigos numéricos.")

    return codigos


def cargar_json(path: Path):
    """
    Carga el archivo JSON.
    Espera principalmente una lista de objetos.
    """
    if not path.exists():
        print(f"❌ Archivo no encontrado: {path}")
        return []

    try:
        with open(path, "r", encoding="utf-8") as f:
            data = json.load(f)
    except json.JSONDecodeError as e:
        print(f"❌ Error leyendo JSON: {e}")
        return []

    if isinstance(data, list):
        return data

    if isinstance(data, dict):
        # Por si algún archivo viene envuelto en una estructura
        for key in ["results", "data", "tipologias", "items"]:
            if key in data and isinstance(data[key], list):
                return data[key]

        return [data]

    print("⚠️ El JSON no tiene una estructura válida.")
    return []


def filtrar_precios_detalle(items, codigos_eliminar: set):
    """
    Elimina los elementos cuyo producto_codigo esté en codigos_eliminar.
    Retorna dos listas:
    - filtrados: los que se conservan
    - eliminados: los que se removieron
    """
    filtrados = []
    eliminados = []

    for item in items:
        if not isinstance(item, dict):
            filtrados.append(item)
            continue

        codigo = normalizar_codigo(item.get("producto_codigo"))

        if codigo and codigo in codigos_eliminar:
            eliminados.append(item)
        else:
            filtrados.append(item)

    return filtrados, eliminados


def salvar_json(obj, path: Path):
    """
    Guarda un archivo JSON en la misma carpeta del script.
    """
    path.parent.mkdir(parents=True, exist_ok=True)

    with open(path, "w", encoding="utf-8") as f:
        json.dump(obj, f, ensure_ascii=False, indent=2)

    print(f"✅ Guardado: {path.resolve()}")


def main():
    print("============================================================")
    print(" ELIMINADOR DE CÓDIGOS - PRECIOS DETALLE")
    print("============================================================")
    print(f"📁 Carpeta del script: {BASE_DIR}")
    print(f"📄 JSON de entrada: {INPUT_JSON}")
    print(f"📄 TXT de códigos: {CODIGOS_TXT}")
    print("")

    codigos_eliminar = cargar_codigos_a_eliminar(CODIGOS_TXT)

    datos = cargar_json(INPUT_JSON)

    if not datos:
        print("❌ No hay datos para procesar.")
        return

    datos_filtrados, datos_eliminados = filtrar_precios_detalle(
        datos,
        codigos_eliminar
    )

    salvar_json(datos_filtrados, OUTPUT_JSON)
    salvar_json(datos_eliminados, OUTPUT_ELIMINADOS_JSON)

    original_count = len(datos)
    filtrados_count = len(datos_filtrados)
    eliminados_count = len(datos_eliminados)

    print("")
    print("============================================================")
    print(" RESUMEN")
    print("============================================================")
    print(f"- Originales:  {original_count} elementos")
    print(f"- Conservados: {filtrados_count} elementos")
    print(f"- Eliminados:  {eliminados_count} elementos")

    codigos_realmente_eliminados = {
        normalizar_codigo(item.get("producto_codigo"))
        for item in datos_eliminados
        if isinstance(item, dict)
    }

    codigos_no_encontrados = codigos_eliminar - codigos_realmente_eliminados

    print(f"- Códigos únicos eliminados: {len(codigos_realmente_eliminados)}")
    print(f"- Códigos del TXT no encontrados en el JSON: {len(codigos_no_encontrados)}")

    if codigos_no_encontrados:
        archivo_no_encontrados = BASE_DIR / "codigos_no_encontrados.txt"
        archivo_no_encontrados.write_text(
            "\n".join(sorted(codigos_no_encontrados)),
            encoding="utf-8"
        )
        print(f"⚠️ Códigos no encontrados guardados en: {archivo_no_encontrados.resolve()}")

    print("")
    print("✅ Proceso finalizado correctamente.")


if __name__ == "__main__":
    main()
