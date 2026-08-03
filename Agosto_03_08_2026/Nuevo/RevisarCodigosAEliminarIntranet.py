#!/usr/bin/env python3
import requests
import time
import urllib.parse
import re
import os
from requests.exceptions import SSLError

# Configuración
BASE = "https://espacios.carvajal.com/lista-precios/categoria-general/"

# Desactiva la verificación SSL para intranets con certificados no verificados (útil temporal)
VERIFY_SSL = False

# Archivos
INPUT_FILE = "codigos_a_eliminar.txt"
OUTPUT_FILE = "codigos_existentes.txt"
CSV_FILE = "results.csv"          # Opcional: genera un CSV detallado
PROGRESS_FILE = "progress_index.txt"  # Guarda índice para reanudar

# Activar/Desactivar salida CSV
OUTPUT_CSV = True

# Depuración
DEBUG = True  # Poner a False para desactivar logs detallados

# Telegrama de salida segura (evita warnings de SSL)
import warnings
warnings.filterwarnings("ignore", message="Unverified HTTPS request")

def code_url(code: str) -> str:
    """Construye la URL de búsqueda para un código dado."""
    return f"{BASE}?q={urllib.parse.quote(code)}&q1=&f1=&f2=Buscar"

def extract_body(html: str) -> str:
    """Devuelve el contenido dentro de <body>...</body> si existe."""
    if not html:
        return ""
    m = re.search(r'<body[^>]*>(.*?)</body>', html, flags=re.IGNORECASE|re.DOTALL)
    if m:
        return m.group(1)
    return html

def extract_results_table(html: str) -> str:
    """Intenta localizar una tabla de resultados en el cuerpo de la página.

    Retorna el bloque de la tabla si se encuentra, o "" si no.
    Enfoque: primero intenta detectar una tabla con cabeceras típicas, luego fallback a patrones generales.
    """
    if not html:
        return ""

    body = extract_body(html)
    if not body:
        return ""

    # 1) Intentar detectar una tabla con cabeceras típicas de listado de precios
    header_match = re.search(
        r'<table[^>]*>.*?<thead>.*?<tr[^>]*>(.*?)</tr>.*?</thead>',
        body, flags=re.IGNORECASE|re.DOTALL
    )
    if header_match:
        header_row = header_match.group(1)
        ths = re.findall(r'<th[^>]*>(.*?)</th>', header_row, flags=re.IGNORECASE|re.DOTALL)
        header_texts = [re.sub(r'<[^>]+>', '', t).strip().lower() for t in ths]
        needed = [
            "imagen","codigo","lista de precio","descripcion",
            "especificaciones","moneda","precio","observaciones",
            "categoria","categoria padre"
        ]
        match_count = sum(1 for need in needed if any(need in h for h in header_texts))
        if match_count >= 4:
            m2 = re.search(r'<table[^>]*>.*?</table>', body, flags=re.IGNORECASE|re.DOTALL)
            if m2:
                table = m2.group(0)
                if re.search(r'<tr[^>]*>.*?</tr>', table, flags=re.IGNORECASE|re.DOTALL):
                    return table

    # 2) Fall back a patrones generales si no detecta cabeceras específicas
    patterns = [
        r'<table[^>]*>.*?</table>',
        r'<table[^>]*id=["\']?lista-precios["\']?[^>]*>.*?</table>',
        r'<table[^>]*class=["\']?dataTable|class=["\']?table["\']?[^>]*>.*?</table>',
        r'<table[^>]*>.*?<thead>.*?</thead>.*?<tbody>.*?</tbody>.*?</table>',
    ]
    for pat in patterns:
        m = re.search(pat, body, flags=re.IGNORECASE|re.DOTALL)
        if m:
            table = m.group(0)
            if re.search(r'<tr[^>]*>.*?</tr>', table, flags=re.IGNORECASE|re.DOTALL):
                return table
    return ""

def code_exists_with_reason(html: str):
    """Detección de existencia basada en presencia de una tabla de resultados.

    Retorna (exists: bool, reason: str)
    """
    if not html:
        return False, "empty_html"

    table = extract_results_table(html)
    if table:
        return True, "table_detected"

    body = extract_body(html).lower()
    not_found_patterns = [
        r"no\s*se\s*encontr(ó|ar)",
        r"no\s*se\s*encontr(ado|ada)?",
        r"sin\s*resultados",
        r"no\s*hay\s*resultados",
        r"no\s*existe",
        r"no\s*se\s*ha\s*encontrado",
        r"consulta\s*no\s*(encontrada|encontrado)",
        r"consulta\s*no\s*(existe|encontrado|encontrada)",
        r"no\s*se\s*encuentra",
        r"consulta\s*no\s*encontrada",
        r"consulta\s*no\s*encontrado",
    ]
    for pat in not_found_patterns:
        if re.search(pat, body):
            return False, f"not_found_pattern:{pat}"

    return False, "no_table_found_and_no_match"

def extract_snippet(html: str, length: int = 600) -> str:
    """Devuelve un snippet corto del HTML para revisión manual."""
    if not html:
        return ""
    snippet = html.replace('\n', ' ').replace('\r', ' ')
    return snippet[:length].strip()

def get_csrf_token(html: str) -> str:
    m = re.search(r'name="csrfmiddlewaretoken" value="([^"]+)"', html)
    return m.group(1) if m else ""

def login_session():
    """Inicia una sesión autenticada si se proporcionan credenciales en el entorno.
    Requiere: MIA_USERNAME y MIA_PASSWORD
    Retorna una sesión de requests si el login tuvo éxito, o None si no hay credenciales.
    """
    USERNAME = os.getenv("MIA_USERNAME")
    PASSWORD = os.getenv("MIA_PASSWORD")
    if not USERNAME or not PASSWORD:
        print("[INFO] No hay credenciales en el entorno. Se pedirán por consola.")
        import getpass
        USERNAME = input("Usuario: ")
        PASSWORD = getpass.getpass("Contraseña: ")

    login_url = "https://espacios.carvajal.com/usuarios/login/"
    s = requests.Session()
    r = s.get(login_url, verify=VERIFY_SSL)
    csrf = get_csrf_token(r.text)
    payload = {"username": USERNAME, "password": PASSWORD, "csrfmiddlewaretoken": csrf}
    headers = {"Referer": login_url}
    r2 = s.post(login_url, data=payload, headers=headers, verify=VERIFY_SSL, allow_redirects=True)
    print(f"[LOGIN] Status after login: {getattr(r2, 'status_code', 'NA')} URL: {getattr(r2, 'url', '')}")

    # Prueba rápida de autenticación accediendo a una página protegida
    test = s.get("https://espacios.carvajal.com/lista-precios/categoria-general/", verify=VERIFY_SSL)
    print(f"[LOGIN-TEST] Acceso tras login: status={getattr(test, 'status_code', 'NA')} URL={getattr(test, 'url', '')}")

    return s

def main():
    # Cargar códigos
    codes = []
    try:
        with open(INPUT_FILE, "r", encoding="utf-8") as f:
            for line in f:
                c = line.strip()
                if c:
                    codes.append(c)
    except Exception as e:
        print(f"ERROR: al leer el archivo {INPUT_FILE} : {e}")
        return

    total = len(codes)
    if total == 0:
        print(f"No se encontraron códigos en {INPUT_FILE}.")
        return

    print(f"Total de códigos a revisar: {total}")

    # Iniciar sesión (con credenciales del entorno o consola)
    session = login_session()
    if session is None:
        print("No se pudo iniciar sesión. Proseguir sin autenticación no mostrará la lista.")
        return

    # Cargar punto de inicio (si existe) para reanudar
    start_index = 0
    if os.path.exists(PROGRESS_FILE):
        try:
            with open(PROGRESS_FILE, "r", encoding="utf-8") as pf:
                v = pf.read().strip()
                if v.isdigit():
                    start_index = int(v)
            print(f"Reanudando desde el índice: {start_index+1} (1-based)")
        except Exception as e:
            print("Warning: no se pudo leer progress_index.txt, iniciando desde 0:", e)

    found_codes = []
    results = []  # para CSV

    try:
        for idx in range(start_index, total):
            code = codes[idx]
            url = code_url(code)
            print(f"[{idx+1}/{total}] Consultando código: {code} -> {url}")

            try:
                r = session.get(url, timeout=20, verify=VERIFY_SSL)
            except SSLError as e:
                print(f"[WARN] SSL error al consultar {code}: {e} (VERIFY_SSL={VERIFY_SSL})")
                continue
            except KeyboardInterrupt:
                print("\nInterrumpido por el usuario. Guardando progreso…")
                with open(PROGRESS_FILE, "w", encoding="utf-8") as pf:
                    pf.write(str(idx))
                print("Progreso guardado. Puedes reanudar más tarde.")
                return
            except Exception as e:
                print(f"[WARN] Error al consultar {code}: {e}")
                time.sleep(0.25)
                continue

            # Logs HTTP simples para ver conectividad
            print(f"[HTTP] GET {url} -> status={getattr(r, 'status_code', 'NA')} final_url={getattr(r, 'url', url)} len={len(r.text) if hasattr(r, 'text') else 'NA'}")
            if getattr(r, 'is_redirect', False):
                try:
                    loc = r.headers.get('Location')
                    if loc:
                        print(f"[HTTP] Redirect to: {loc}")
                except Exception:
                    pass

            if r.status_code != 200:
                print(f"[WARN] HTTP {r.status_code} para {code}")
                time.sleep(0.25)
                continue

            html = r.text

            body = extract_body(html)
            table_matches = re.findall(r'<table[^>]*>.*?</table>', body, flags=re.IGNORECASE|re.DOTALL)
            first_table_preview = table_matches[0][:200] if table_matches else ""
            print(f"[DEBUG] TABLES_IN_BODY={len(table_matches)}; FIRST_TABLE_PREVIEW='{first_table_preview}'")

            exists, reason = code_exists_with_reason(html)
            price = ""  # detección de precio desactivada por este enfoque
            snippet = extract_snippet(html)

            if exists:
                found_codes.append(code)

            results.append([code, "YES" if exists else "NO", price, url, snippet, reason])

            # Actualizar progreso tras cada código
            with open(PROGRESS_FILE, "w", encoding="utf-8") as pf:
                pf.write(str(idx + 1))

            # if DEBUG:
                # print(f"DEBUG {code}: exists={exists}, reason={reason}, len_html={len(html)}")

            time.sleep(0.25)

    finally:
        session.close()

    # Guardar los códigos que sí existen
    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        for c in found_codes:
            f.write(c + "\n")

    # Opcional: CSV detallado
    if OUTPUT_CSV:
        try:
            with open(CSV_FILE, "w", encoding="utf-8", newline="") as fcsv:
                import csv
                writer = csv.writer(fcsv)
                writer.writerow(["code", "exists", "price", "url", "snippet", "reason"])
                for row in results:
                    writer.writerow(row)
            print(f"CSV detallado generado: {CSV_FILE}")
        except Exception as e:
            print("ERROR al generar CSV:", e)

    # Limpiar progreso si todo salió bien
    if os.path.exists(PROGRESS_FILE):
        os.remove(PROGRESS_FILE)

    print(f"Listo. {len(found_codes)} códigos existentes. Archivo generado: {OUTPUT_FILE}")

if __name__ == "__main__":
    main()
