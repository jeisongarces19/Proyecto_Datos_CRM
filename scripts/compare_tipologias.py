import json
import sys
from pathlib import Path

def map_by_codigo(data):
    return {item.get("codigo"): item for item in data}

def map_hijos_by_id(hijos):
    return {h.get("id"): h for h in (hijos or [])}

def compare_files(old_path, new_path, skip_known=None):
    with open(old_path, 'r', encoding='utf-8') as f:
        old = json.load(f)
    with open(new_path, 'r', encoding='utf-8') as f:
        new = json.load(f)

    old_map = map_by_codigo(old)
    new_map = map_by_codigo(new)

    diffs = []

    # typologies present in new
    for codigo, new_item in new_map.items():
        old_item = old_map.get(codigo)
        if old_item is None:
            diffs.append({"type": "typology_added", "codigo": codigo})
            continue

        # compare some top-level fields
        for field in ("descripcion", "categoria_tipologia_id", "lista"):
            old_v = old_item.get(field)
            new_v = new_item.get(field)
            if old_v != new_v:
                diffs.append({"type": "field_change", "codigo": codigo, "field": field, "old": old_v, "new": new_v})

        # compare hijos
        old_h_map = map_hijos_by_id(old_item.get("hijos"))
        new_h_map = map_hijos_by_id(new_item.get("hijos"))

        # check new hijos
        for hid, new_h in new_h_map.items():
            old_h = old_h_map.get(hid)
            if old_h is None:
                diffs.append({"type": "hijo_added", "codigo": codigo, "hijo_id": hid})
                continue

            # fields to compare
            for hfield in ("cantidad", "precio", "precio_acumulado", "lista"):
                old_v = old_h.get(hfield)
                new_v = new_h.get(hfield)
                if old_v != new_v:
                    # skip known difference if requested
                    if skip_known and codigo == skip_known.get("codigo") and hid == skip_known.get("hijo_id") and hfield == skip_known.get("field"):
                        continue
                    diffs.append({"type": "hijo_field_change", "codigo": codigo, "hijo_id": hid, "field": hfield, "old": old_v, "new": new_v})

        # check removed hijos
        for hid in old_h_map.keys():
            if hid not in new_h_map:
                diffs.append({"type": "hijo_removed", "codigo": codigo, "hijo_id": hid})

    # typologies removed in new
    for codigo in old_map.keys():
        if codigo not in new_map:
            diffs.append({"type": "typology_removed", "codigo": codigo})

    return diffs

def main():
    if len(sys.argv) < 3:
        print("Usage: compare_tipologias.py <old.json> <new.json>")
        sys.exit(2)

    old_path = Path(sys.argv[1])
    new_path = Path(sys.argv[2])

    # known difference to skip (reported earlier)
    skip_known = {"codigo": "22000116413", "hijo_id": 61439, "field": "precio_acumulado"}

    diffs = compare_files(old_path, new_path, skip_known=skip_known)

    out = {
        "old_path": str(old_path),
        "new_path": str(new_path),
        "total_diffs": len(diffs),
        "diffs": diffs
    }

    out_path = Path("diff_report.json")
    with open(out_path, 'w', encoding='utf-8') as f:
        json.dump(out, f, ensure_ascii=False, indent=2)

    print(f"Done. Total diffs (excluding known): {len(diffs)}. Report saved to {out_path.resolve()}")

if __name__ == '__main__':
    main()
