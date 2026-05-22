import json
from pathlib import Path
import requests
import urllib3

# ===== Configura aquí =====
BASE_URL = "https://espacios.carvajal.com/api/lista-precios/precios-detalle/"
VERIFY_SSL = False        # True si el certificado es válido
TIMEOUT = 30
PAGE_SIZE = 500           # DRF a veces limita, si no respeta, igual seguiremos 'next'

# Si necesitas token:
# AUTH_TOKEN = "TU_TOKEN"
# HEADERS = {"Authorization": f"Bearer {AUTH_TOKEN}", "Accept": "application/json"}
# Si no:
HEADERS = {"Accept": "application/json", "User-Agent": "python-requests/2.x"}

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

def fetch_all_drf(url: str):
    """Recorre DRF con 'results' y 'next' hasta traer todo."""
    session = requests.Session()
    all_items = []

    # Primera URL con JSON y tamaño de página alto
    params = {"format": "json", "page_size": PAGE_SIZE}
    next_url = url

    while next_url:
        # Si next_url ya viene con query completa, no pases params
        use_params = None if ("?" in next_url and "format=" in next_url) else params
        resp = session.get(next_url, headers=HEADERS, timeout=TIMEOUT, verify=VERIFY_SSL, params=use_params)
        if resp.status_code != 200:
            print(f"⚠️ HTTP {resp.status_code} en: {next_url}")
            break

        # Asegurar JSON
        try:
            payload = resp.json()
        except Exception:
            preview = resp.text[:1000]
            Path("respuesta_preview.txt").write_text(preview, encoding="utf-8", errors="ignore")
            print("❌ La respuesta no es JSON. Revisa 'respuesta_preview.txt'.")
            break

        # DRF clásico: dict con 'results' y 'next'
        if isinstance(payload, dict) and "results" in payload:
            items = payload.get("results") or []
            all_items.extend(items)
            next_url = payload.get("next")  # absoluta o relativa; requests la entiende
            print(f"Descargados: {len(all_items)} (siguiente: {bool(next_url)})")
        else:
            # Si no hay envoltura DRF, pero viene lista directa:
            if isinstance(payload, list):
                all_items.extend(payload)
            else:
                # Guarda el payload desconocido por si acaso
                Path("payload_desconocido.json").write_text(
                    json.dumps(payload, ensure_ascii=False, indent=2),
                    encoding="utf-8"
                )
                print("ℹ️ Estructura no DRF; guardado 'payload_desconocido.json'.")
            next_url = None

    return all_items

def main():
    items = fetch_all_drf(BASE_URL)
    print(f"TOTAL elementos: {len(items)}")

    # Guardar JSON bonito
    out = Path("precios-detalle.json")
    out.write_text(json.dumps(items, ensure_ascii=False, indent=2, sort_keys=True), encoding="utf-8")
    print(f"✅ Guardado en: {out.resolve()}")

if __name__ == "__main__":
    main()
