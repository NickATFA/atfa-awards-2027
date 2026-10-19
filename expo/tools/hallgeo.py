"""
Geometry helpers for checking a Hall 5 stand layout (all units metres, y grows south).

The hall, perimeter lane, demo zone, entrances and exits come from src/geometry.json.
Used by tools/check_layout.py and build.py.
"""
import math

# Inner edge of the 3 m perimeter lane along the curved operable wall: an ellipse
# centred on (42, 43) with semi-axes 39 (x) and 40 (y). The wall itself is 42 x 43.
ARC_CX, ARC_CY, ARC_A, ARC_B = 42.0, 43.0, 39.0, 40.0
LANE = 3.0


def buildable(x, y, eps=1e-6):
    """True if the point is inside the stand-building area (inside the 3 m lane)."""
    if x < LANE - eps or x > 55 + eps or y < LANE - eps:
        return False
    if y > (49.4 if x > 48.5 + eps else 51.0) + eps:
        return False
    if x < ARC_CX and y < ARC_CY:
        if ((x - ARC_CX) / ARC_A) ** 2 + ((y - ARC_CY) / ARC_B) ** 2 > 1 + eps:
            return False
    return True


def rect_buildable(r, step=0.25):
    """Check the whole outline of a rectangle (x, y, w, h), not just its corners."""
    x, y, w, h = r
    n = max(2, int(max(w, h) / step) + 1)
    for i in range(n + 1):
        t = i / n
        for px, py in ((x + w * t, y), (x + w * t, y + h), (x, y + h * t), (x + w, y + h * t)):
            if not buildable(px, py):
                return False, (round(px, 2), round(py, 2))
    return True, None


def seg_point_dist(ax, ay, bx, by, px, py):
    dx, dy = bx - ax, by - ay
    L2 = dx * dx + dy * dy
    t = 0.0 if L2 == 0 else max(0.0, min(1.0, ((px - ax) * dx + (py - ay) * dy) / L2))
    qx, qy = ax + t * dx, ay + t * dy
    return math.hypot(px - qx, py - qy)


def _orient(ax, ay, bx, by, cx, cy):
    v = (bx - ax) * (cy - ay) - (by - ay) * (cx - ax)
    return 0 if abs(v) < 1e-12 else (1 if v > 0 else -1)


def segs_intersect(a, b, c, d):
    o1, o2 = _orient(*a, *b, *c), _orient(*a, *b, *d)
    o3, o4 = _orient(*c, *d, *a), _orient(*c, *d, *b)
    return o1 != o2 and o3 != o4


def seg_seg_dist(a, b, c, d):
    if segs_intersect(a, b, c, d):
        return 0.0
    return min(seg_point_dist(*a, *b, *c), seg_point_dist(*a, *b, *d),
               seg_point_dist(*c, *d, *a), seg_point_dist(*c, *d, *b))


def rect_corners(r):
    x, y, w, h = r
    return [(x, y), (x + w, y), (x + w, y + h), (x, y + h)]


def poly_edges(pts):
    return [(pts[i], pts[(i + 1) % len(pts)]) for i in range(len(pts))]


def point_in_poly(px, py, pts):
    inside = False
    j = len(pts) - 1
    for i in range(len(pts)):
        xi, yi = pts[i]
        xj, yj = pts[j]
        if (yi > py) != (yj > py) and px < (xj - xi) * (py - yi) / (yj - yi) + xi:
            inside = not inside
        j = i
    return inside


def poly_poly_dist(p, q):
    """Distance between two convex polygons (0 if they overlap or touch)."""
    if any(point_in_poly(x, y, q) for x, y in p) or any(point_in_poly(x, y, p) for x, y in q):
        return 0.0
    return min(seg_seg_dist(a, b, c, d) for a, b in poly_edges(p) for c, d in poly_edges(q))


def rect_rect_dist(r1, r2):
    x1, y1, w1, h1 = r1
    x2, y2, w2, h2 = r2
    dx = max(0.0, max(x1, x2) - min(x1 + w1, x2 + w2))
    dy = max(0.0, max(y1, y2) - min(y1 + h1, y2 + h2))
    return math.hypot(dx, dy)


def rect_seg_dist(r, a, b):
    corners = rect_corners(r)
    if point_in_poly(a[0], a[1], corners) or point_in_poly(b[0], b[1], corners):
        return 0.0
    return min(seg_seg_dist(c, d, a, b) for c, d in poly_edges(corners))


def rect_point_dist(r, px, py):
    x, y, w, h = r
    dx = max(x - px, 0.0, px - (x + w))
    dy = max(y - py, 0.0, py - (y + h))
    return math.hypot(dx, dy)
