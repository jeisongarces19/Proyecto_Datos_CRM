import json
import re
import csv
from pathlib import Path
from urllib.parse import urljoin

import requests
import urllib3

# ============================================================
# CONFIGURACIÓN GENERAL
# ============================================================

# Carpeta donde está este script
BASE_DIR = Path(__file__).resolve().parent

HEADERS = {
    "Accept": "application/json",
    "User-Agent": "python-requests/2.x"
}

VERIFY_SSL = False
TIMEOUT = 30
PAGE_SIZE = 500

# Carpeta de salida: misma carpeta del script
OUTPUT_DIR = BASE_DIR

# Archivo de códigos a eliminar: misma carpeta del script
CODIGOS_TXT = BASE_DIR / "codigos_a_eliminar.txt"

# Reporte CSV: misma carpeta del script
REPORTE_CSV = OUTPUT_DIR / "reporte_tipologias_eliminadas.csv"

# Endpoints
ENDPOINTS = [
    {
        "index": 1,
        "label": "tipologias-detalle_Colombia",
        "url": "https://espacios.carvajal.com/api/lista-precios/tipologias-detalle/?lista=MEPAL_CO_Nacionales"
    },
    {
        "index": 2,
        "label": "tipologias-detalle_Ecuador",
        "url": "https://espacios.carvajal.com/api/lista-precios/tipologias-detalle/?lista=MEPAL_EC_Nacionales"
    },
    {
        "index": 3,
        "label": "tipologias-detalle_Distribuidores",
        "url": "https://espacios.carvajal.com/api/lista-precios/tipologias-detalle/?lista=MEPAL_CO_Sur%20America"
    },
    {
        "index": 4,
        "label": "tipologias-detalle_NacionalesUSD",
        "url": "https://espacios.carvajal.com/api/lista-precios/tipologias-detalle/?lista=MEPAL_CO_Nacionales%20USD"
    },
]

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def normalizar_codigo(valor) -> str:
    """
    Convierte el código a texto limpio.
    """
    if valor is None:
        return ""

    return str(valor).strip()


def fetch_all_drf(url: str):
    """
    Recorre una API tipo Django REST Framework con 'results' y 'next'
    hasta descargar todos los registros.
    """
    session = requests.Session()
    all_items = []

    params = {
        "format": "json",
        "page_size": PAGE_SIZE
    }

    next_url = url
    page = 1

    while next_url:
        try:
            # Si la URL ya trae query, requests puede anexar params sin problema.
            # Pero si es un 'next' completo de DRF, normalmente ya trae todo.
            use_params = None if "page=" in next_url or "format=" in next_url else params

            resp = session.get(
                next_url,
                headers=HEADERS,
                timeout=TIMEOUT,
                verify=VERIFY_SSL,
                params=use_params
            )

        except requests.exceptions.RequestException as e:
            print(f"❌ Error de conexión en página {page}: {e}")
            break

        if resp.status_code != 200:
            print(f"⚠️ HTTP {resp.status_code} en: {next_url}")
            print(resp.text[:1000])
            break

        try:
            payload = resp.json()
        except ValueError:
            preview = resp.text[:1000]

            archivo_preview = BASE_DIR / "respuesta_preview.txt"
            archivo_preview.write_text(
                preview,
                encoding="utf-8",
                errors="ignore"
            )

            print(f"❌ La respuesta no es JSON. Revisa: {archivo_preview.resolve()}")
            break

        if isinstance(payload, dict) and "results" in payload:
            items = payload.get("results") or []
            all_items.extend(items)

            next_url = payload.get("next")

            # Por si DRF devuelve next como ruta relativa
            if next_url:
                next_url = urljoin(url, next_url)

            print(f"Página {page} descargada. Total acumulado: {len(all_items)}")

        elif isinstance(payload, list):
            all_items.extend(payload)
            print(f"Lista directa descargada. Total elementos: {len(all_items)}")
            next_url = None

        else:
            archivo_payload = BASE_DIR / "payload_desconocido.json"
            archivo_payload.write_text(
                json.dumps(payload, ensure_ascii=False, indent=2),
                encoding="utf-8"
            )

            print(f"ℹ️ Estructura no DRF. Guardado en: {archivo_payload.resolve()}")
            next_url = None

        page += 1

    return all_items


def cargar_codigos_a_eliminar(txt_path: Path) -> set:
    """
    Carga la lista de códigos a eliminar desde un TXT.
    Acepta códigos separados por saltos de línea, espacios, comas, etc.
    """
    if not txt_path.exists():
        print(f"⚠️ No se encontró el archivo: {txt_path.resolve()}")
        print("⚠️ No se eliminará ningún código.")
        return set()

    text = txt_path.read_text(encoding="utf-8", errors="ignore")

    codigos = set(re.findall(r"\d+", text))

    print(f"✔ Códigos a eliminar cargados: {len(codigos)}")

    if not codigos:
        print("⚠️ El archivo existe, pero no se encontraron códigos numéricos.")

    return codigos


def filter_tipologias(tipologias, codigos_a_eliminar: set, endpoint_label: str):
    """
    Filtra las tipologías completas:
    - Si el código padre está en codigos_a_eliminar, elimina toda la tipología.
    - Si cualquier hijo tiene producto.codigo en codigos_a_eliminar, elimina toda la tipología.
    - Si no encuentra códigos prohibidos, conserva la tipología completa.
    """
    tipologias_filtradas = []
    tipologias_eliminadas = []

    for t in tipologias:
        if not isinstance(t, dict):
            continue

        codigo_padre = normalizar_codigo(t.get("codigo"))
        hijos = t.get("hijos") or []

        codigos_hijos_encontrados = set()

        # 1. Revisar si el código padre está en los códigos a eliminar
        padre_debe_eliminarse = codigo_padre in codigos_a_eliminar

        # 2. Revisar códigos de hijos
        for h in hijos:
            if not isinstance(h, dict):
                continue

            producto = h.get("producto") or {}

            if not isinstance(producto, dict):
                continue

            codigo_hijo = normalizar_codigo(producto.get("codigo"))

            if codigo_hijo and codigo_hijo in codigos_a_eliminar:
                codigos_hijos_encontrados.add(codigo_hijo)

        # 3. Si el padre o algún hijo coincide, eliminar toda la tipología
        if padre_debe_eliminarse or codigos_hijos_encontrados:
            tipologias_eliminadas.append({
                "endpoint": endpoint_label,
                "codigo_padre": codigo_padre,
                "descripcion_padre": str(t.get("descripcion", ""))[:200],
                "motivo": "Código padre eliminado" if padre_debe_eliminarse else "Contiene hijo eliminado",
                "codigos_hijos_encontrados": ", ".join(sorted(codigos_hijos_encontrados)),
                "cantidad_hijos_originales": len(hijos)
            })
            continue

        # 4. Si no hubo coincidencias, conservar la tipología completa
        tipologias_filtradas.append(t)

    return tipologias_filtradas, tipologias_eliminadas


def sanitize_label(label: str) -> str:
    """
    Limpia el nombre para usarlo como archivo.
    """
    return (
        str(label)
        .replace(" ", "_")
        .replace("/", "_")
        .replace("\\", "_")
        .replace(":", "_")
        .replace("*", "_")
        .replace("?", "_")
        .replace('"', "_")
        .replace("<", "_")
        .replace(">", "_")
        .replace("|", "_")
    )


def guardar_json(data, path: Path):
    """
    Guarda JSON en la carpeta definida.
    """
    path.parent.mkdir(parents=True, exist_ok=True)

    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(f"✅ Guardado filtrado en: {path.resolve()}")


def guardar_reporte_csv(data, path: Path):
    """
    Guarda el reporte CSV de tipologías eliminadas completamente.
    """
    if not data:
        print("ℹ️ No hubo tipologías padre eliminadas completamente. No se generó CSV.")
        return

    fieldnames = ["endpoint","codigo_padre","descripcion_padre","motivo","codigos_hijos_encontrados","cantidad_hijos_originales"]

    with open(path, "w", newline="", encoding="utf-8-sig") as f_csv:
        writer = csv.DictWriter(f_csv, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(data)

    print(f"✅ Reporte de eliminadas generado en: {path.resolve()}")


def main():
    print("============================================================")
    print(" DESCARGA Y FILTRO DE TIPOLOGÍAS DETALLE")
    print("============================================================")
    print(f"📁 Carpeta del script: {BASE_DIR}")
    print(f"📄 Archivo de códigos: {CODIGOS_TXT.resolve()}")
    print("")

    codigos_a_eliminar = cargar_codigos_a_eliminar(CODIGOS_TXT)

    all_eliminadas_report = []

    for ep in ENDPOINTS:
        idx = ep["index"]
        label = ep["label"]
        url = ep["url"]

        print("")
        print("============================================================")
        print(f"⬇️ Descargando endpoint {idx}: {label}")
        print(f"🌐 URL: {url}")
        print("============================================================")

        tipologias = fetch_all_drf(url)

        if not tipologias:
            print(f"⚠️ No se descargaron datos para: {label}")
            continue

        tipologias_filtradas, tipologias_eliminadas = filter_tipologias(
            tipologias,
            codigos_a_eliminar,
            label
        )

        all_eliminadas_report.extend(tipologias_eliminadas)

        sanitized_label = sanitize_label(label)
        out_path = OUTPUT_DIR / f"{idx}_{sanitized_label}.json"

        guardar_json(tipologias_filtradas, out_path)

        total_original = len(tipologias)
        total_filtrado = len(tipologias_filtradas)
        total_padres_eliminados = len(tipologias_eliminadas)

        print("")
        print("Resumen endpoint:")
        print(f"- Originales: {total_original} tipologías")
        print(f"- Conservadas: {total_filtrado} tipologías")
        print(f"- Padres eliminados completamente: {total_padres_eliminados}")

    guardar_reporte_csv(all_eliminadas_report, REPORTE_CSV)

    print("")
    print("============================================================")
    print(" PROCESO TERMINADO")
    print("============================================================")


if __name__ == "__main__":
    main()
