"""
Checks a stand layout in src/geometry.json against the hall's rules:

  - every stand inside the 3 m perimeter (fire / non-build) lane, including the curved wall
  - no overlapping stands; any gap between stands is a usable aisle (3 m or more)
  - nothing on the demonstration and stage zone, and 4 m clear in front of Entry 2
  - sizes, areas and prices consistent; stand numbers unique and well formed

A layout can add its own rules in geometry.json ("rules"): Concept 5 kept 3.5 m clear
around the demo zone ("demo_clear"), an 8 m arrival area inside the main entrance
("arrival_clear") and its main pedestrian route ("route"). The distances are always
measured and printed, whether or not a layout sets a rule for them.

    python3 tools/check_layout.py        (also run automatically by build.py)
"""
import json
import math
import pathlib
import re
import sys

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from hallgeo import (rect_buildable, poly_poly_dist, rect_corners, rect_rect_dist,  # noqa: E402
                     rect_seg_dist, rect_point_dist)

ROOT = pathlib.Path(__file__).resolve().parents[1]

MIN_AISLE = 3.0
ENTRY2_CLEAR = 4.0
ROUTE_MARGIN = 0.5           # extra space between the route band and the nearest stand
ID_RE = re.compile(r"^[A-Z]{1,3}\d{1,3}$|^\d{1,4}$")


def entrance_points(geo, label):
    for e in geo["entrances"]:
        if e["label"] == label:
            nums = [float(v) for v in re.findall(r"-?\d+\.?\d*", e["path"])]
            pts = list(zip(nums[0::2], nums[1::2]))
            return pts[0], pts[-1]
    return None


def validate(geo):
    """Returns (errors, summary)."""
    errors = []
    sizes = geo["sizes"]
    stands = geo["stands"]
    ids = [s["id"] for s in stands]
    for sid in ids:
        if not ID_RE.match(sid):
            errors.append("stand number %r is not like S01, B12 or 305" % sid)
    dupes = sorted({i for i in ids if ids.count(i) > 1})
    if dupes:
        errors.append("duplicate stand numbers: " + ", ".join(dupes))

    for key, z in sizes.items():
        if abs(z["w"] * z["d"] - z["sqm"]) > 1e-9:
            errors.append("size %s: %s x %s is not %s m2" % (key, z["w"], z["d"], z["sqm"]))
        if abs(z["price"] / z["sqm"] - z["rate"]) > 0.006:
            errors.append("size %s: rate %.2f doesn't match price / area" % (key, z["rate"]))
        for k in ("label", "fill", "line"):
            if not z.get(k):
                errors.append("size %s has no %s" % (key, k))

    for s in stands:
        z = sizes.get(s["size"])
        if not z:
            errors.append("stand %s has unknown size %s" % (s["id"], s["size"]))
            continue
        if sorted((s["w"], s["h"])) != sorted((z["w"], z["d"])):
            errors.append("stand %s is drawn %s x %s but its size is %s" % (s["id"], s["w"], s["h"], s["size"]))
        if s.get("sqm") != z["sqm"] or s.get("price") != z["price"]:
            errors.append("stand %s area/price doesn't match size %s" % (s["id"], s["size"]))
        ok, where = rect_buildable((s["x"], s["y"], s["w"], s["h"]))
        if not ok:
            errors.append("stand %s is in the perimeter lane (at %s)" % (s["id"], where))

    rects = {s["id"]: (s["x"], s["y"], s["w"], s["h"]) for s in stands}

    def overlaps(ra, rb):
        ox = min(ra[0] + ra[2], rb[0] + rb[2]) - max(ra[0], rb[0])
        oy = min(ra[1] + ra[3], rb[1] + rb[3]) - max(ra[1], rb[1])
        return ox, oy

    def covered(px, py):
        return any(r[0] < px < r[0] + r[2] and r[1] < py < r[1] + r[3] for r in rects.values())

    def gap_is_empty(ra, rb):
        """True if any of the space between two stands is open floor (not another stand)."""
        x0 = min(ra[0] + ra[2], rb[0] + rb[2]) if min(ra[0] + ra[2], rb[0] + rb[2]) <= max(ra[0], rb[0]) else max(ra[0], rb[0])
        x1 = max(ra[0], rb[0]) if min(ra[0] + ra[2], rb[0] + rb[2]) <= max(ra[0], rb[0]) else min(ra[0] + ra[2], rb[0] + rb[2])
        y0 = min(ra[1] + ra[3], rb[1] + rb[3]) if min(ra[1] + ra[3], rb[1] + rb[3]) <= max(ra[1], rb[1]) else max(ra[1], rb[1])
        y1 = max(ra[1], rb[1]) if min(ra[1] + ra[3], rb[1] + rb[3]) <= max(ra[1], rb[1]) else min(ra[1] + ra[3], rb[1] + rb[3])
        if x1 - x0 < 1e-6 or y1 - y0 < 1e-6:
            return False  # edges in line: a step in the outline, not a gap
        step = 0.25
        nx, ny = max(1, int(round((x1 - x0) / step))), max(1, int(round((y1 - y0) / step)))
        for i in range(nx):
            for j in range(ny):
                if not covered(x0 + (i + 0.5) * (x1 - x0) / nx, y0 + (j + 0.5) * (y1 - y0) / ny):
                    return True
        return False

    # Stands that share a length of edge form a block (a row, a back-to-back pair...).
    block = {sid: sid for sid in rects}

    def find(sid):
        while block[sid] != sid:
            block[sid] = block[block[sid]]
            sid = block[sid]
        return sid

    min_aisle_rule = (geo.get("rules") or {}).get("min_aisle", MIN_AISLE)
    min_aisle = math.inf
    for i, a in enumerate(stands):
        for b in stands[i + 1:]:
            ra, rb = rects[a["id"]], rects[b["id"]]
            ox, oy = overlaps(ra, rb)
            if ox > 1e-6 and oy > 1e-6:
                errors.append("stands %s and %s overlap" % (a["id"], b["id"]))
                continue
            d = rect_rect_dist(ra, rb)
            if d <= 1e-6:
                if ox > 1e-6 or oy > 1e-6:
                    block[find(a["id"])] = find(b["id"])
                else:
                    # Touching only at a corner is fine inside a solid block (both other
                    # quarters around that corner are stands), but if both are open floor
                    # it's a pinch point: two aisles meeting at a point nobody can pass.
                    cx, cy = max(ra[0], rb[0]), max(ra[1], rb[1])
                    quarters = [(cx + dx * 0.1, cy + dy * 0.1) for dx in (-1, 1) for dy in (-1, 1)]
                    free = [q for q in quarters if not covered(*q)]
                    if len(free) >= 2:
                        errors.append("stands %s and %s only touch at a corner (a pinch point, no way between them)" % (a["id"], b["id"]))
                continue
            if d < min_aisle_rule - 1e-6 and gap_is_empty(ra, rb):
                errors.append("gap between %s and %s is %.2f m (aisles need %.1f m)" % (a["id"], b["id"], d, min_aisle_rule))
    blocks = {}
    for sid in rects:
        blocks.setdefault(find(sid), []).append(sid)
    for i, a in enumerate(stands):
        for b in stands[i + 1:]:
            if find(a["id"]) != find(b["id"]):
                min_aisle = min(min_aisle, rect_rect_dist(rects[a["id"]], rects[b["id"]]))

    rules = geo.get("rules") or {}
    demo = [(37, 49.4), (55, 49.4), (55, 31.4)]
    demo_d = {sid: poly_poly_dist(rect_corners(r), demo) for sid, r in rects.items()}
    nearest_demo = min(demo_d, key=demo_d.get)
    min_demo = demo_d[nearest_demo]
    for sid, d in demo_d.items():
        if d <= 1e-6:
            errors.append("stand %s is on the demo and stage zone" % sid)
    demo_clear = rules.get("demo_clear")
    if demo_clear and min_demo < demo_clear - 1e-6:
        errors.append("stand %s is %.2f m from the demo zone (this layout needs %.1f m)" % (nearest_demo, min_demo, demo_clear))

    summary = {"stands": len(stands), "blocks": len(blocks), "area": sum(s["sqm"] for s in stands),
               "total": sum(s["price"] for s in stands), "min_aisle": round(min_aisle, 2),
               "demo_clear": round(min_demo, 2), "demo_nearest": nearest_demo}
    mix = {}
    for s in stands:
        mix[s["size"]] = mix.get(s["size"], 0) + 1
    summary["mix"] = mix

    route = geo.get("route")
    if route:
        a, b = (route["x1"], route["y1"]), (route["x2"], route["y2"])
        need = route["width"] / 2 + ROUTE_MARGIN
        near = min(rect_seg_dist(r, a, b) for r in rects.values())
        summary["route_nearest_stand"] = round(near, 2)
        for sid, r in rects.items():
            d = rect_seg_dist(r, a, b)
            if d < need - 1e-6:
                errors.append("stand %s is %.2f m from the middle of the main route (needs %.1f m)" % (sid, d, need))

    door = entrance_points(geo, "MAIN ENTRANCE")
    if door:
        mid = ((door[0][0] + door[1][0]) / 2, (door[0][1] + door[1][1]) / 2)
        near_id = min(rects, key=lambda sid: rect_point_dist(rects[sid], *mid))
        near = rect_point_dist(rects[near_id], *mid)
        summary["arrival_clear"] = round(near, 2)
        summary["arrival_nearest"] = near_id
        arrival = rules.get("arrival_clear")
        if arrival and near < arrival - 1e-6:
            errors.append("stand %s is %.2f m inside the main entrance (this layout's arrival area needs %.0f m)" % (near_id, near, arrival))
    e2 = entrance_points(geo, "ENTRY 2")
    if e2:
        near = min(rect_seg_dist(r, e2[0], e2[1]) for r in rects.values())
        summary["entry2_clear"] = round(near, 2)
        need = rules.get("entry2_clear", ENTRY2_CLEAR)
        if near < need - 1e-6:
            errors.append("a stand is %.2f m from the Entry 2 doors (needs %.0f m)" % (near, need))

    for w in geo.get("walls", []):
        x1, y1, x2, y2 = w
        if x1 != x2 and y1 != y2:
            errors.append("shared wall %s is not straight" % w)
    return errors, summary


def main():
    geo = json.loads((ROOT / "src" / "geometry.json").read_text(encoding="utf-8"))
    errors, summary = validate(geo)
    print(json.dumps(summary))
    for e in errors:
        print("LAYOUT PROBLEM:", e)
    sys.exit(1 if errors else 0)


if __name__ == "__main__":
    main()
