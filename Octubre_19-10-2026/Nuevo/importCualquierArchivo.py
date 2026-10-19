import json
from pathlib import Path
from urllib.parse import urljoin

import requests
import urllib3

# ===== Carpeta donde está este archivo .py =====
CARPETA_SCRIPT = Path(__file__).resolve().parent

# ===== Configuración general =====
API_BASE = "https://espacios.carvajal.com/api/lista-precios/"
VERIFY_SSL = False
TIMEOUT = 30
PAGE_SIZE = 500

HEADERS = {
    "Accept": "application/json",
    "User-Agent": "python-requests/2.x"
}

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# ===== Archivos a descargar =====
ENDPOINTS = {
    "precios-detalle_Original.json": "precios-detalle/",
    "tipologias-detalle_Colombia_Original.json": "tipologias-detalle/?lista=MEPAL_CO_Nacionales",
    "tipologias-detalle_Distribuidores_Original.json": "tipologias-detalle/?lista=MEPAL_CO_Sur%20America",
    "tipologias-detalle_Ecuador_Original.json": "tipologias-detalle/?lista=MEPAL_EC_Nacionales",
    "tipologias-detalle_NacionalesUSD_Original.json": "tipologias-detalle/?lista=MEPAL_CO_Nacionales%20USD",
    "imagenes.json": "imagenes/",
    "productos-hijos-tipologia_Original.json": "productos-hijos-tipologia/"

    # Agrega aquí los otros endpoints:
    # "nombre-del-archivo.json": "ruta-del-endpoint/",
    # "otro-archivo.json": "otra-ruta/?parametro=valor",
}


def construir_url(endpoint: str) -> str:
    """
    Construye la URL completa a partir de API_BASE + endpoint.
    """
    return urljoin(API_BASE, endpoint)


def fetch_all_drf(url: str):
    """Recorre una API tipo Django REST Framework con 'results' y 'next'."""
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
            # Si la URL ya trae parámetros, no volvemos a enviar params
            use_params = None if "?" in next_url else params

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

            archivo_preview = CARPETA_SCRIPT / "respuesta_preview.txt"
            archivo_preview.write_text(
                preview,
                encoding="utf-8",
                errors="ignore"
            )

            print(f"❌ La respuesta no es JSON. Revisa: {archivo_preview}")
            break

        if isinstance(payload, dict) and "results" in payload:
            items = payload.get("results") or []
            all_items.extend(items)

            next_url = payload.get("next")

            if next_url:
                next_url = urljoin(url, next_url)

            print(f"Página {page} descargada. Total acumulado: {len(all_items)}")

        elif isinstance(payload, list):
            all_items.extend(payload)
            print(f"Lista directa descargada. Total elementos: {len(all_items)}")
            next_url = None

        else:
            archivo_payload = CARPETA_SCRIPT / "payload_desconocido.json"
            archivo_payload.write_text(
                json.dumps(payload, ensure_ascii=False, indent=2),
                encoding="utf-8"
            )

            print(f"ℹ️ Estructura no reconocida. Guardado en: {archivo_payload}")
            next_url = None

        page += 1

    return all_items


def descargar_archivo(nombre_archivo: str, endpoint: str):
    url = construir_url(endpoint)

    print("")
    print("=" * 80)
    print(f"⬇️ Descargando: {nombre_archivo}")
    print(f"🌐 URL: {url}")

    items = fetch_all_drf(url)

    archivo_salida = CARPETA_SCRIPT / nombre_archivo

    archivo_salida.write_text(
        json.dumps(items, ensure_ascii=False, indent=2, sort_keys=True),
        encoding="utf-8"
    )

    print(f"✅ Guardado en: {archivo_salida}")
    print(f"📦 Total elementos: {len(items)}")


def main():
    print(f"📁 Carpeta del script: {CARPETA_SCRIPT}")

    for nombre_archivo, endpoint in ENDPOINTS.items():
        descargar_archivo(nombre_archivo, endpoint)

    print("")
    print("✅ Descarga finalizada.")


if __name__ == "__main__":
    main()
