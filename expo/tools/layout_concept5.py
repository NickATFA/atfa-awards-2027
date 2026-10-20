"""
Concept 5 stand layout (ATFA Expo 2027, Hall 5), fitted to scale.

Each block is placed by its top-left corner in metres (x east, y south, origin at the
hall's north-west corner). Run this to rewrite the stands, shared rear walls and the
main pedestrian route in src/geometry.json, then run tools/check_layout.py and build.py.

    python3 tools/layout_concept5.py

Moving a block: change its origin below. Every stand in the block moves with it.
"""
import json
import pathlib

ROOT = pathlib.Path(__file__).resolve().parents[1]
GEO_PATH = ROOT / "src" / "geometry.json"

# Block origins (top-left corner, metres).
TOP = (30.0, 6.0)      # S01, O01/O02, B01-B06 along the north side
MID = (33.0, 15.5)     # S02, G01, G02, O03
S03 = (39.0, 25.0)     # S03 + O04
LEFT = (8.5, 23.0)     # G03/G04 + S05 near the curved wall
S06 = (23.0, 31.5)     # island in the middle
BOTTOM = (11.5, 41.0)  # B07-B12, O05/O06, S07

# Main pedestrian route: centre line x - y = ROUTE_C (45 degrees), from the main
# entrance doors to the edge of the demonstration and stage zone.
ROUTE_C = 2.75
ROUTE_WIDTH = 4.0
ROUTE_STOP = 1.6   # the drawn band stops this far short of the zone, leaving room for its name


def stand(sid, size, x, y, w, h):
    return {"id": sid, "size": size, "x": round(x, 2), "y": round(y, 2), "w": w, "h": h}


def layout():
    stands, walls = [], []
    tx, ty = TOP
    stands.append(stand("S01", "6x6", tx, ty, 6, 6))
    stands.append(stand("O01", "6x3", tx + 6, ty, 6, 3))
    stands.append(stand("O02", "6x3", tx + 6, ty + 3, 6, 3))
    for k in range(3):
        stands.append(stand("B%02d" % (k + 1), "3x3", tx + 12 + 3 * k, ty, 3, 3))
    for k in range(3):
        stands.append(stand("B%02d" % (k + 4), "3x3", tx + 12 + 3 * k, ty + 3, 3, 3))
    walls.append([tx + 6, ty + 3, tx + 21, ty + 3])

    mx, my = MID
    stands.append(stand("S02", "6x6", mx, my, 6, 6))
    stands.append(stand("G01", "3x6", mx + 6, my, 3, 6))
    stands.append(stand("G02", "3x6", mx + 9, my, 3, 6))
    stands.append(stand("O03", "6x3", mx + 12, my, 6, 3))
    walls.append([mx + 9, my, mx + 9, my + 6])

    sx, sy = S03
    stands.append(stand("S03", "6x6", sx, sy, 6, 6))
    stands.append(stand("O04", "6x3", sx + 6, sy, 6, 3))

    lx, ly = LEFT
    # Drawn in the concept as a back-to-back pair, 6 m wide and 3 m deep each.
    stands.append(stand("G03", "3x6", lx, ly, 6, 3))
    stands.append(stand("G04", "3x6", lx, ly + 3, 6, 3))
    stands.append(stand("S05", "6x6", lx + 6, ly, 6, 6))
    walls.append([lx, ly + 3, lx + 6, ly + 3])

    stands.append(stand("S06", "6x6", S06[0], S06[1], 6, 6))

    bx, by = BOTTOM
    for k in range(3):
        stands.append(stand("B%02d" % (k + 7), "3x3", bx + 3 * k, by, 3, 3))
    for k in range(3):
        stands.append(stand("B%02d" % (k + 10), "3x3", bx + 3 * k, by + 3, 3, 3))
    stands.append(stand("O05", "6x3", bx + 9, by, 6, 3))
    stands.append(stand("O06", "6x3", bx + 9, by + 3, 6, 3))
    stands.append(stand("S07", "6x6", bx + 15, by, 6, 6))
    walls.append([bx, by + 3, bx + 15, by + 3])
    return stands, walls


def route():
    c = ROUTE_C
    # Start where the centre line meets the curved wall (ellipse centre 42,43; 42 x 43 m).
    lo, hi = 0.0, 40.0
    for _ in range(80):
        m = (lo + hi) / 2
        f = ((m - 42) / 42) ** 2 + ((m - c - 43) / 43) ** 2
        lo, hi = (m, hi) if f > 1 else (lo, m)
    x0 = hi
    # End on the demo zone's long edge (x + y = 86.4).
    x1 = (86.4 + c) / 2
    return {"x1": round(x0, 2), "y1": round(x0 - c, 2), "x2": round(x1, 2), "y2": round(86.4 - x1, 2),
            "width": ROUTE_WIDTH, "stop": ROUTE_STOP}


def main():
    geo = json.loads(GEO_PATH.read_text(encoding="utf-8"))
    stands, walls = layout()
    for s in stands:
        z = geo["sizes"][s["size"]]
        s["sqm"] = z["sqm"]
        s["price"] = z["price"]
    geo["stands"] = stands
    geo["walls"] = [[round(v, 2) for v in w] for w in walls]
    geo["route"] = route()
    geo["layout"] = "Revised Concept 5"
    geo["rules"] = {"demo_clear": 3.5, "arrival_clear": 8.0}
    GEO_PATH.write_text(json.dumps(geo, indent=1, ensure_ascii=False) + "\n", encoding="utf-8")
    print("wrote %d stands, %d shared walls, route %s" % (len(stands), len(walls), geo["route"]))


if __name__ == "__main__":
    main()
