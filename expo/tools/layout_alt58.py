"""
"Alternative layout with 58 booths" (ATFA Expo 2027, Hall 5), from Phil's drawing of
20 October 2026, fitted to scale.

Phil drew it on this plan's own hall geometry (8.4 points to the metre), so every booth
sits on whole metres: the positions below are read straight off his PDF. Rows of islands
with nominal 3 m cross lanes between them; each 6 x 6 m booth backs onto two 6 x 3 m
booths (a 12 x 6 m island); thick lines on his drawing are rear walls. The demonstration
and stage zone, the entrances, catering, breakout and the perimeter lane are unchanged.

Each block is placed by its top-left corner in metres (x east, y south, origin at the
hall's north-west corner). Run this to rewrite the stands and shared rear walls in
src/geometry.json (it also removes Concept 5's main pedestrian route and arrival area
label, which this layout doesn't have), then run tools/check_layout.py and build.py.

    python3 tools/layout_alt58.py

Moving a block: change its origin below. Every booth in the block moves with it.
"""
import json
import pathlib

ROOT = pathlib.Path(__file__).resolve().parents[1]
GEO_PATH = ROOT / "src" / "geometry.json"

NAME = "Alternative layout with 58 booths (Phil, 20 October 2026)"

# Block origins (top-left corner, metres).
TOP_W = (28.0, 6.0)      # B01-B04 over B05-B08, north side
TOP_E = (43.0, 6.0)      # B09-B12 over B13-B16, north-east corner
ISLAND_1 = (15.0, 15.0)  # S01 + O01/O02, inside the main entrance
G_ROW = (30.0, 15.0)     # G01-G04, rear wall along the south edge
PAIR_E = (45.0, 15.0)    # O03 / O04 back to back
ISLAND_2 = (12.0, 24.0)  # S02 + O05/O06, western lane
O_BLOCK_1 = (27.0, 24.0) # O07 O08 over O09 O10, centre
ISLAND_3 = (42.0, 24.0)  # S03 + O11/O12, eastern demo approach
O_BLOCK_2 = (9.0, 33.0)  # O13 O14 over O15 O16, west
ISLAND_4 = (27.0, 33.0)  # O17/O18 + S04, lower centre
BOTTOM_W = (6.0, 42.0)   # B17-B20 over B21-B24
BOTTOM_E = (21.0, 42.0)  # B25-B28 over B29-B32


def stand(sid, size, x, y, w, h):
    return {"id": sid, "size": size, "x": round(x, 2), "y": round(y, 2), "w": w, "h": h}


def small_block(origin, first, stands, walls):
    """Two rows of four 3 x 3 m booths, back to back (12 x 6 m)."""
    ox, oy = origin
    for k in range(4):
        stands.append(stand("B%02d" % (first + k), "3x3", ox + 3 * k, oy, 3, 3))
    for k in range(4):
        stands.append(stand("B%02d" % (first + 4 + k), "3x3", ox + 3 * k, oy + 3, 3, 3))
    walls.append([ox, oy + 3, ox + 12, oy + 3])


def island(origin, big, first_o, stands, walls, big_on_left=True):
    """A 6 x 6 m booth backing onto two 6 x 3 m booths (12 x 6 m)."""
    ox, oy = origin
    bx, px = (ox, ox + 6) if big_on_left else (ox + 6, ox)
    stands.append(stand(big, "6x6", bx, oy, 6, 6))
    stands.append(stand("O%02d" % first_o, "6x3", px, oy, 6, 3))
    stands.append(stand("O%02d" % (first_o + 1), "6x3", px, oy + 3, 6, 3))
    walls.append([ox + 6, oy, ox + 6, oy + 6])


def o_block(origin, first_o, stands, walls):
    """Two rows of two 6 x 3 m booths, back to back (12 x 6 m)."""
    ox, oy = origin
    stands.append(stand("O%02d" % first_o, "6x3", ox, oy, 6, 3))
    stands.append(stand("O%02d" % (first_o + 1), "6x3", ox + 6, oy, 6, 3))
    stands.append(stand("O%02d" % (first_o + 2), "6x3", ox, oy + 3, 6, 3))
    stands.append(stand("O%02d" % (first_o + 3), "6x3", ox + 6, oy + 3, 6, 3))
    walls.append([ox, oy + 3, ox + 12, oy + 3])


def layout():
    stands, walls = [], []
    small_block(TOP_W, 1, stands, walls)
    small_block(TOP_E, 9, stands, walls)

    island(ISLAND_1, "S01", 1, stands, walls)
    gx, gy = G_ROW
    for k in range(4):
        stands.append(stand("G%02d" % (k + 1), "3x6", gx + 3 * k, gy, 3, 6))
    walls.append([gx, gy + 6, gx + 12, gy + 6])
    ex, ey = PAIR_E
    stands.append(stand("O03", "6x3", ex, ey, 6, 3))
    stands.append(stand("O04", "6x3", ex, ey + 3, 6, 3))
    walls.append([ex, ey + 3, ex + 6, ey + 3])

    island(ISLAND_2, "S02", 5, stands, walls)
    o_block(O_BLOCK_1, 7, stands, walls)
    island(ISLAND_3, "S03", 11, stands, walls)

    o_block(O_BLOCK_2, 13, stands, walls)
    island(ISLAND_4, "S04", 17, stands, walls, big_on_left=False)

    small_block(BOTTOM_W, 17, stands, walls)
    small_block(BOTTOM_E, 25, stands, walls)
    return stands, walls


def main():
    geo = json.loads(GEO_PATH.read_text(encoding="utf-8"))
    stands, walls = layout()
    for s in stands:
        z = geo["sizes"][s["size"]]
        s["sqm"] = z["sqm"]
        s["price"] = z["price"]
    geo["layout"] = NAME
    # Phil's drawing sets 3 m lanes; the demo zone and arrival rules of Concept 5 aren't part
    # of it (check_layout.py still measures them). Venue and contractor review to follow.
    geo["rules"] = {"min_aisle": 3.0, "entry2_clear": 4.0}
    geo["stands"] = stands
    geo["walls"] = [[round(v, 2) for v in w] for w in walls]
    # No single main pedestrian route or kept-clear arrival area in this layout: the
    # cross lanes form a circuit instead.
    geo.pop("route", None)
    geo["labels"] = [l for l in geo.get("labels", []) if "ARRIVAL" not in l["text"]]
    GEO_PATH.write_text(json.dumps(geo, indent=1, ensure_ascii=False) + "\n", encoding="utf-8")
    print("wrote %d stands, %d shared walls" % (len(stands), len(walls)))


if __name__ == "__main__":
    main()
