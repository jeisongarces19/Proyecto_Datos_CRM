import json
import re
import os
import csv
from pathlib import Path
import requests
import urllib3

# ===== Configuración general =====
DESKTOP = os.path.join(os.path.expanduser("~"), "Desktop")
HEADERS = {"Accept": "application/json", "User-Agent": "python-requests/2.x"}
VERIFY_SSL = False        # True si el certificado es válido
TIMEOUT = 30
PAGE_SIZE = 500

# Endpoints (con etiquetas para nombres de salida)
ENDPOINTS = [
    {"index": 1, "label": "tipologias-detalle_Colombia", "url": "https://espacios.carvajal.com/api/lista-precios/tipologias-detalle/?lista=MEPAL_CO_Nacionales"},
    {"index": 2, "label": "tipologias-detalle_Ecuador", "url": "https://espacios.carvajal.com/api/lista-precios/tipologias-detalle/?lista=MEPAL_EC_Nacionales"},
    {"index": 3, "label": "tipologias-detalle_Distribuidores", "url": "https://espacios.carvajal.com/api/lista-precios/tipologias-detalle/?lista=MEPAL_CO_Sur%20America"},
    {"index": 4, "label": "tipologias-detalle_NacionalesUSD", "url": "https://espacios.carvajal.com/api/lista-precios/tipologias-detalle/?lista=MEPAL_CO_Nacionales%20USD"},
]

OUTPUT_DIR = Path(DESKTOP)
CODIGOS_TXT = Path(DESKTOP) / "codigos_a_eliminar.txt"
REPORTE_CSV = OUTPUT_DIR / "reporte_tipologias_eliminadas.csv"

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

def fetch_all_drf(url: str):
    """Recorre DRF con 'results' y 'next' hasta traer todo."""
    session = requests.Session()
    all_items = []

    params = {"format": "json", "page_size": PAGE_SIZE}
    next_url = url

    while next_url:
        use_params = None if ("?" in next_url and "format=" in next_url) else params
        resp = session.get(next_url, headers=HEADERS, timeout=TIMEOUT, verify=VERIFY_SSL, params=use_params)
        if resp.status_code != 200:
            print(f"⚠️ HTTP {resp.status_code} en: {next_url}")
            break

        try:
            payload = resp.json()
        except Exception:
            preview = resp.text[:1000]
            Path("respuesta_preview.txt").write_text(preview, encoding="utf-8", errors="ignore")
            print("❌ La respuesta no es JSON. Revisa 'respuesta_preview.txt'.")
            break

        if isinstance(payload, dict) and "results" in payload:
            items = payload.get("results") or []
            all_items.extend(items)
            next_url = payload.get("next")
            print(f"Descargados: {len(all_items)} (siguiente: {bool(next_url)})")
        else:
            if isinstance(payload, list):
                all_items.extend(payload)
            else:
                Path("payload_desconocido.json").write_text(
                    json.dumps(payload, ensure_ascii=False, indent=2),
                    encoding="utf-8"
                )
                print("ℹ️ Estructura no DRF; guardado 'payload_desconocido.json'.")
            next_url = None

    return all_items

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

def filter_tipologias(tipologias, codigos_a_eliminar: set):
    """
    Filtra las tipologías:
      - Elimina hijos cuyo código de producto está en codigos_a_eliminar.
      - Si tras eliminar quedan hijos, guarda el padre con hijos filtrados.
      - Si ya no quedan hijos, elimina también el padre.
    Devuelve (tipologias_filtradas, eliminadas_para_reporte)
    """
    tipologias_filtradas = []
    tipologias_eliminadas = []

    for t in tipologias:
        hijos = t.get("hijos", [])
        eliminated_codes = set()

        filtered_hijos = []
        for h in hijos:
            code = None
            if isinstance(h, dict):
                prod = h.get("producto")
                if isinstance(prod, dict):
                    code = prod.get("codigo")
            if code is not None and str(code) in codigos_a_eliminar:
                eliminated_codes.add(str(code))
            else:
                filtered_hijos.append(h)

        if filtered_hijos:
            t_filtered = dict(t)
            t_filtered["hijos"] = filtered_hijos
            tipologias_filtradas.append(t_filtered)
        else:
            tipologias_eliminadas.append({
                "codigo_padre": t.get("codigo"),
                "descripcion_padre": t.get("descripcion", "")[:200],
                "cantidad_codigos_eliminados": len(eliminated_codes),
                "ejemplo_codigos_eliminados": ", ".join(list(eliminated_codes)[:10])
            })

    return tipologias_filtradas, tipologias_eliminadas

def sanitize_label(label: str) -> str:
    return label.replace(" ", "_").replace("/", "_")

def main():
    # Cargar códigos a eliminar
    codigos_a_eliminar = cargar_codigos_a_eliminar(CODIGOS_TXT)

    all_eliminadas_report = []

    for ep in ENDPOINTS:
        idx = ep["index"]
        label = ep["label"]
        url = ep["url"]

        print(f"\nDescargando endpoint {idx}: {label}")
        datos = fetch_all_drf(url)

        # Si la API devuelve un JSON ya envuelto en un wrapper, no importa; asumimos lista aquí.
        tipologias = datos if isinstance(datos, list) else datos

        tipologias_filtradas, tipologias_eliminadas = filter_tipologias(tipologias, codigos_a_eliminar)

        # Acumular reporte de eliminadas
        all_eliminadas_report.extend(tipologias_eliminadas)

        # Guardar filtrado en archivo final con el formato deseado
        sanitized_label = sanitize_label(label)
        out_path = OUTPUT_DIR / f"{idx}_{sanitized_label}.json"
        with open(out_path, "w", encoding="utf-8") as f:
            json.dump(tipologias_filtradas, f, ensure_ascii=False, indent=2)
        print(f"✅ Guardado filtrado en: {out_path.resolve()}")

        print(f" - Originales: {len(tipologias)} tipologías")
        print(f" - Filtradas (con hijos): {len(tipologias_filtradas)} tipologías")

    # Guardar reporte CSV de eliminadas
    if all_eliminadas_report:
        with open(REPORTE_CSV, "w", newline="", encoding="utf-8") as f_csv:
            fieldnames = ["codigo_padre", "descripcion_padre", "cantidad_codigos_eliminados", "ejemplo_codigos_eliminados"]
            writer = csv.DictWriter(f_csv, fieldnames=fieldnames)
            writer.writeheader()
            writer.writerows(all_eliminadas_report)
        print(f"\n✅ Reporte de eliminadas generado en: {REPORTE_CSV}")

    print("\n===== PROCESO TERMINADO =====")

if __name__ == "__main__":
    main()
