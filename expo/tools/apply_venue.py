"""Put the venue round Hall 5 (from the TF63 magazine plan, 24 November 2026) into geometry.json.

    python3 tools/venue_from_tf63.py <magazine plan.pdf> src/venue/tf63-venue.json   (only if the drawing changes)
    python3 tools/apply_venue.py

The magazine plan is drawn on this plan's own hall geometry (3.628 pt to the metre), so its rooms
come across in metres unchanged. Labels are set here (the PDF's type is outlined).
"""
import json, pathlib
ROOT = pathlib.Path(__file__).resolve().parents[1]
geo_p = ROOT / "src" / "geometry.json"
geo = json.loads(geo_p.read_text(encoding="utf-8"))
raw = json.loads((ROOT / "src" / "venue" / "tf63-venue.json").read_text(encoding="utf-8"))

geo["venue"] = {
    "rooms": [{"kind": r["kind"], "d": r["d"]} for r in raw["rooms"]],
    "cafe": raw["cafe"],
    "closed": raw["hall5a"],
    "forecourt": raw["forecourt"],
    "source": "Timber Floors 63 floor plan (fix v2), 24 November 2026",
}
x0, y0, x1, y1 = raw["car"]
geo["displays"] = [{"id": "DISPLAY", "label": "Display", "status": "Reserved", "x": 22.5, "y": 33.0, "w": 3.0, "h": 6.0}]

ROOM = "#7A6A4C"      # room names on the light plan (page.js lightens them for the dark page)
SUB = "#8C8578"
geo["labels"] = [
    {"text": "CAFÉ / BAR & CATERING", "x": 22.6, "y": -7.6, "size": 1.6, "color": "#9A8455", "rotate": -25, "bold": True},
    {"text": "HALL 5A CLOSED", "x": -8.6, "y": 26.2, "size": 1.45, "color": "#7D8A84", "rotate": -62, "bold": True},
    {"text": "NO ACCESS", "x": -6.6, "y": 28.4, "size": 1.0, "color": "#7D8A84", "rotate": -62, "bold": True},
    {"text": "DEMONSTRATION & STAGE ZONE", "x": 45.65, "y": 40.05, "size": 1.02, "color": "#A8600C", "rotate": -45, "bold": True},
    {"text": "HALL 5B · EXPO AREA", "x": 19.4, "y": 52.6, "size": 0.85, "color": "#7D8A84", "bold": True},
    {"text": "EXHIBITORS\nLOUNGE", "x": 5.5, "y": -20.4, "size": 1.0, "color": ROOM, "bold": True},
    {"text": "FUNCTION ROOM 5", "x": 5.5, "y": -16.4, "size": 0.62, "color": SUB, "bold": True},
    {"text": "ATFA OFFICES", "x": -19.2, "y": 1.0, "size": 0.78, "color": ROOM, "bold": True},
    {"text": "STAFF, JUDGES\n& STEWARDS", "x": -19.2, "y": 2.7, "size": 0.55, "color": SUB, "bold": True},
    {"text": "FIRST\nAID", "x": -12.5, "y": -0.1, "size": 0.6, "color": ROOM, "bold": True},
    {"text": "GREEN ROOM", "x": -16.0, "y": 8.4, "size": 0.78, "color": ROOM, "bold": True},
    {"text": "SPEAKERS · FUNCTION ROOM 4", "x": -16.0, "y": 10.0, "size": 0.55, "color": SUB, "bold": True},
    {"text": "CONFERENCE PRESENTATION AREA", "x": -20.2, "y": 23.7, "size": 0.85, "color": ROOM, "rotate": -90, "bold": True},
    {"text": "FUNCTION ROOM 3", "x": -18.6, "y": 23.7, "size": 0.6, "color": SUB, "rotate": -90, "bold": True},
    {"text": "MEETING\nROOM 2", "x": -20.2, "y": 37.6, "size": 0.72, "color": ROOM, "bold": True},
    {"text": "MEETING\nROOM 1", "x": -17.5, "y": 53.6, "size": 0.72, "color": ROOM, "bold": True},
    {"text": "TOILETS", "x": -15.3, "y": 46.7, "size": 0.65, "color": SUB, "bold": True},
    {"text": "TOILETS", "x": 0.8, "y": -12.0, "size": 0.6, "color": SUB, "rotate": -10, "bold": True},
    {"text": "KITCHEN", "x": 17.5, "y": -20.3, "size": 0.65, "color": SUB, "bold": True},
    {"text": "KITCHEN", "x": 28.2, "y": -20.3, "size": 0.65, "color": SUB, "bold": True},
    {"text": "STORE", "x": 17.5, "y": -14.4, "size": 0.65, "color": SUB, "bold": True},
    {"text": "FUNCTION ROOM 8", "x": 45.2, "y": -17.5, "size": 0.6, "color": SUB, "rotate": -90, "bold": True},
    {"text": "FOYER", "x": -1.4, "y": -1.4, "size": 0.75, "color": SUB, "rotate": -45, "bold": True},
    {"text": "FORECOURT", "x": -15.0, "y": -16.2, "size": 0.7, "color": SUB, "bold": True},
]
# The drawing now takes in the rooms round the hall.
geo["view"] = {"x": -26, "y": -26, "w": 90, "h": 86}
geo_p.write_text(json.dumps(geo, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
print("venue:", len(geo["venue"]["rooms"]), "rooms; labels:", len(geo["labels"]))
