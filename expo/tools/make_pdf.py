"""
Make the printable floor plan and rate card (A3 landscape, 2 pages) from the
same geometry the website uses.

    python3 tools/make_pdf.py      ->  dist/ATFA-Expo-2027-Hall-5-Floor-Plan.pdf
"""
import base64
import datetime
import json
import pathlib
import re

from playwright.sync_api import sync_playwright

ROOT = pathlib.Path(__file__).resolve().parents[1]
DIST = ROOT / "dist"
GEO = json.loads((ROOT / "src" / "geometry.json").read_text(encoding="utf-8"))
OUT_PDF = DIST / "ATFA-Expo-2027-Hall-5-Floor-Plan.pdf"
# White version of the ATFA horizontal logo, for the dark title bar (see assets/README.md).
LOGO = ROOT / "assets" / "atfa-horiz-logo-white.png"

SIZES = GEO["sizes"]
USED = [k for k in SIZES if any(s["size"] == k for s in GEO["stands"])]
SIZE_NAMES = {k: z["label"] for k, z in SIZES.items()}
SIZE_FILL = {k: z["fill"] for k, z in SIZES.items()}
SIZE_LINE = {k: z["line"] for k, z in SIZES.items()}


def money(n):
    return "${:,.0f}".format(n)


def open_text(s, stands):
    """Same rule as the website: a side is open if no other stand is against it."""
    e = 1e-6
    sides = 0
    for side in ("N", "S", "W", "E"):
        blocked = False
        for o in stands:
            if o is s:
                continue
            if side in ("N", "S"):
                at = s["y"] if side == "N" else s["y"] + s["h"]
                edge = o["y"] + o["h"] if side == "N" else o["y"]
                if abs(edge - at) < e and min(s["x"] + s["w"], o["x"] + o["w"]) - max(s["x"], o["x"]) > e:
                    blocked = True
            else:
                at = s["x"] if side == "W" else s["x"] + s["w"]
                edge = o["x"] + o["w"] if side == "W" else o["x"]
                if abs(edge - at) < e and min(s["y"] + s["h"], o["y"] + o["h"]) - max(s["y"], o["y"]) > e:
                    blocked = True
        if not blocked:
            sides += 1
    return "Island (4)" if sides == 4 else str(sides)


def plan_svg():
    """Render the live widget with every stand available and lift out its SVG."""
    preview = (DIST / "preview.html").read_text(encoding="utf-8")
    snapshot = {"ok": True, "source": "live", "updated": None,
                "stands": [{"id": s["id"], "status": "available"} for s in GEO["stands"]]}
    preview = preview.replace("SNAPSHOT_JSON", json.dumps(snapshot))
    with sync_playwright() as pw:
        browser = pw.chromium.launch()
        page = browser.new_page(viewport={"width": 1500, "height": 1100})
        page.route("**/*", lambda r: r.fulfill(status=200, content_type="text/html", body=preview)
                   if r.request.url.startswith("https://print.local/") else r.fulfill(status=404, body=""))
        page.goto("https://print.local/")
        page.wait_for_function("() => window.ATFAExpo27 && document.querySelector('.afpx-stand.is-available')")
        # Zoom right in first so every stand gets its full labels (number and size),
        # then put the whole drawing back in view for print.
        for _ in range(6):
            page.click(".afpx-zbtn[data-zoom='in']")
        svg = page.evaluate("""() => {
            const s = document.querySelector('.afpx-svg').cloneNode(true);
            s.setAttribute('viewBox', '-18.5 -18 82.5 78.5');
            s.removeAttribute('role'); s.removeAttribute('aria-label'); s.removeAttribute('focusable');
            s.querySelectorAll('[tabindex]').forEach(n => n.removeAttribute('tabindex'));
            s.querySelectorAll('[aria-label]').forEach(n => n.removeAttribute('aria-label'));
            s.querySelectorAll('[role]').forEach(n => n.removeAttribute('role'));
            s.setAttribute('class', 'plan');
            return s.outerHTML; }""")
        browser.close()
    return svg


def build_html(svg):
    sizes = GEO["sizes"]
    counts = {}
    for s in GEO["stands"]:
        counts[s["size"]] = counts.get(s["size"], 0) + 1
    total_area = sum(s["sqm"] for s in GEO["stands"])

    rate_rows = ""
    for key in USED:
        z = sizes[key]
        saving = "{:.0f}%".format(z["disc"] * 100) if z["disc"] else "Base rate"
        rate_rows += (
            "<tr><td><i style='background:%s;border-color:%s'></i>%s</td><td>%d m²</td><td class='r b'>%s</td>"
            "<td class='r'>$%.2f</td><td class='r'>%s</td><td class='r'>%d</td></tr>"
            % (SIZE_FILL[key], SIZE_LINE[key], SIZE_NAMES[key], z["sqm"], money(z["price"]), z["price"] / z["sqm"], saving, counts[key]))

    def id_order(s):
        m = re.match(r"([A-Z]*)(\d+)$", s["id"])
        return (m.group(1), int(m.group(2))) if m else (s["id"], 0)

    stands = sorted(GEO["stands"], key=id_order)
    ncols = 2 if len(stands) <= 32 else 3
    per_col = -(-len(stands) // ncols)
    cols = [stands[i:i + per_col] for i in range(0, len(stands), per_col)]
    sched = ""
    for col in cols:
        sched += ("<table class='sched'><thead><tr><th>Stand</th><th>Size</th><th class='r'>Area</th><th class='c'>Open sides</th>"
                  "<th class='r'>Price</th></tr></thead><tbody>")
        for s in col:
            sched += ("<tr><td class='b'>%s</td><td><i style='background:%s;border-color:%s'></i>%s</td><td class='r'>%d m²</td>"
                      "<td class='c'>%s</td><td class='r'>%s</td></tr>"
                      % (s["id"], SIZE_FILL[s["size"]], SIZE_LINE[s["size"]], SIZE_NAMES[s["size"]], s["sqm"],
                         open_text(s, GEO["stands"]), money(s["price"])))
        sched += "</tbody></table>"
    savings = []
    for key in USED:
        if SIZES[key]["disc"]:
            savings.append("%s %d%%" % (SIZE_NAMES[key], round(SIZES[key]["disc"] * 100)))
    savings_text = ", ".join(savings[:-1]) + " and " + savings[-1] if len(savings) > 1 else "".join(savings)
    size_legend = "".join("<span><i style='background:%s;border-color:%s'></i>%s</span>" % (SIZE_FILL[k], SIZE_LINE[k], SIZE_NAMES[k])
                          for k in USED)

    today = datetime.date.today().strftime("%-d %B %Y")
    css = """
@page { size: A3 landscape; margin: 0; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { font-family: 'Liberation Sans', Arial, Helvetica, sans-serif; color: #16211C; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.page { width: 420mm; height: 297mm; padding: 11mm 12mm 9mm; display: flex; flex-direction: column; page-break-after: always; position: relative; }
.page:last-child { page-break-after: auto; }
.top { background: #141618; color: #fff; border-radius: 4mm; padding: 6mm 8mm; display: flex; justify-content: space-between; align-items: center; border-bottom: 1.4mm solid #F79320; }
.top .logo { flex: none; display: block; height: 15mm; width: auto; margin-right: 8mm; }
.top .ttl { flex: 1; min-width: 0; padding-left: 8mm; border-left: 0.3mm solid rgba(255,255,255,.28); }
.top h1 { margin: 0; font-size: 22pt; letter-spacing: .2pt; }
.top p { margin: 1.6mm 0 0; color: #F3D9BB; font-size: 11pt; }
.top .facts { flex: none; margin-left: 8mm; display: flex; gap: 9mm; text-align: right; }
.top .facts b { display: block; font-size: 20pt; }
.top .facts span { font-size: 8pt; text-transform: uppercase; letter-spacing: .8pt; color: #F3D9BB; }
.main { flex: 1; display: grid; grid-template-columns: 1fr 104mm; gap: 8mm; margin-top: 6mm; min-height: 0; }
.planbox { position: relative; border: 0.3mm solid #DDE3E0; border-radius: 4mm; background: #fff; overflow: hidden; }
.plan { width: 100%; height: 100%; display: block; }
.plan text { font-family: 'Liberation Sans', Arial, sans-serif; }
.plan .afpx-lbl { fill: #16211C; }
.plan .afpx-sub { fill: #4F5D57; }
.north { position: absolute; left: 6mm; top: 6mm; width: 13mm; text-align: center; font-weight: 700; font-size: 9pt; }
.north svg { width: 11mm; height: 11mm; display: block; margin: 0 auto 0.6mm; }
.side { display: flex; flex-direction: column; gap: 5mm; }
.card { border: 0.3mm solid #DDE3E0; border-radius: 4mm; padding: 5mm 5.5mm; background: #fff; }
.card h2 { margin: 0 0 3mm; font-size: 12.5pt; }
.card p { margin: 0 0 2mm; font-size: 9.5pt; line-height: 1.45; }
table { border-collapse: collapse; width: 100%; font-size: 9.2pt; }
th { text-align: left; font-size: 7.5pt; text-transform: uppercase; letter-spacing: .6pt; color: #5F6B66; border-bottom: 0.3mm solid #DDE3E0; padding: 0 0 1.6mm; }
td { padding: 1.7mm 0; border-bottom: 0.2mm solid #EEF1F0; }
td i, .legend i { display: inline-block; width: 3mm; height: 3mm; border: 0.3mm solid; border-radius: 0.6mm; margin-right: 1.8mm; vertical-align: -0.4mm; }
.r { text-align: right; }
.c { text-align: center; }
.b { font-weight: 700; }
.book { background: #FDF2E4; border-color: #F2C79A; }
.book .big { font-size: 13pt; font-weight: 700; color: #A8600C; margin: 1mm 0 2mm; }
.legend { display: flex; flex-wrap: wrap; gap: 2mm 6mm; font-size: 8.5pt; color: #5F6B66; margin-top: 4mm; align-items: center; }
.legend .note { margin-left: auto; }
.sched-wrap { flex: 1; display: grid; grid-template-columns: repeat(var(--cols), 1fr); gap: 12mm; margin-top: 7mm; align-content: start; }
.sched td, .sched th { padding-left: 1mm; padding-right: 1mm; }
.sched td { padding-top: 2.75mm; padding-bottom: 2.75mm; font-size: 10pt; }
.notes { margin-top: 6mm; display: grid; grid-template-columns: 1.3fr 1fr; gap: 8mm; }
.notes ul { margin: 0; padding-left: 5mm; font-size: 9.5pt; line-height: 1.5; }
.foot { font-size: 8pt; color: #7D8A84; display: flex; justify-content: space-between; margin-top: 3mm; }
"""
    north = ("<div class='north'><svg viewBox='-10 -10 20 20'><circle r='9' fill='#fff' stroke='#16211C' stroke-width='0.9'/>"
             "<path d='M0 -6.6 L -2.9 3.7 L 0 1.6 L 2.9 3.7 Z' fill='#16211C'/></svg>N</div>")
    logo = ("<img class='logo' alt='Australasian Timber Flooring Association' src='data:image/png;base64,%s'>"
            % base64.b64encode(LOGO.read_bytes()).decode("ascii"))
    page1 = f"""
<section class="page">
  <header class="top">
    {logo}
    <div class="ttl"><h1>ATFA Expo &amp; Conference 2027: Hall 5 Floor Plan</h1>
      <p>Sydney Showground, Sydney Olympic Park · May 2027 · Hall 5, 4,136 m²</p></div>
    <div class="facts"><div><b>{len(GEO['stands'])}</b><span>Stands</span></div><div><b>{total_area} m²</b><span>Stand space</span></div><div><b>{money(sizes['3x3']['price'])}</b><span>From, excl. GST</span></div></div>
  </header>
  <div class="main">
    <div class="planbox">{north}{svg}</div>
    <div class="side">
      <div class="card"><h2>Stand sizes and prices</h2>
        <table><thead><tr><th>Size</th><th>Area</th><th class="r">Price</th><th class="r">Per m²</th><th class="r">Saving</th><th class="r">Stands</th></tr></thead>
        <tbody>{rate_rows}</tbody></table>
        <p style="margin-top:3mm">Prices are in AUD and exclude GST. Larger stands include a saving against the same area in 3 × 3 m stands.</p>
      </div>
      <div class="card"><h2>Demonstration and stage zone</h2>
        <p>South-east corner of the hall: a 5 × 5 m quadrant stage with 30 seats around the curved edge, main and delay speakers, and an AV / mix position.</p>
        <p>Breakout / seminar and catering &amp; networking spaces (680 m² each) sit behind the operable wall.</p>
      </div>
      <div class="card book"><h2>Book a stand</h2>
        <p>Contact Cherie at ATFA</p>
        <p class="big">cherie@atfa.com.au</p>
        <p>Live availability and online enquiries:<br><b>atfa.com.au/expo-2027-hall-5-interactive-floor-plan</b></p>
      </div>
    </div>
  </div>
  <div class="legend">
    {size_legend}
    <span><i style="background:#16211C;border-color:#16211C;height:0.8mm;width:5mm;border-radius:0"></i>Shared back wall</span>
    <span><i style="background:#E7ECEA;border-color:#C9D1CD;width:5mm"></i>Main pedestrian route</span>
    <span><i style="background:#F7EFE4;border-color:#A8600C"></i>Demo &amp; stage zone</span>
    <span><i style="background:#FBEDED;border-color:#EFC3C3"></i>Fire / non-build lane</span>
    <span><i style="background:#D64A4A;border-color:#D64A4A"></i>Fire exit</span>
    <span><i style="background:#A8600C;border-color:#A8600C"></i>Entrance</span>
    <span class="note">Layout is a guide and may change before the event. Prepared {today}.</span>
  </div>
</section>"""
    page2 = f"""
<section class="page">
  <header class="top">
    {logo}
    <div class="ttl"><h1>Stand schedule</h1><p>ATFA Expo &amp; Conference 2027 · Sydney Showground, Hall 5 · May 2027</p></div>
    <div class="facts"><div><b>{len(GEO['stands'])}</b><span>Stands</span></div><div><b>{money(sum(s['price'] for s in GEO['stands']))}</b><span>All stands, excl. GST</span></div></div>
  </header>
  <div class="sched-wrap" style="--cols:{ncols}">{sched}</div>
  <div class="notes">
    <div class="card"><h2>Notes</h2><ul>
      <li>All prices are in Australian dollars and exclude GST.</li>
      <li>Savings against the same area in 3 × 3 m stands: {savings_text}.</li>
      <li>"Open sides" are the sides of a stand facing an aisle. Island stands are open on all four.</li>
      <li>Layout is a guide and may change before the event.</li></ul></div>
    <div class="card book"><h2>Book a stand</h2><p class="big">cherie@atfa.com.au</p>
      <p>See which stands are still available, and send an enquiry, at<br><b>atfa.com.au/expo-2027-hall-5-interactive-floor-plan</b></p></div>
  </div>
  <div class="foot"><span>Australasian Timber Flooring Association</span><span>Prepared {today}</span></div>
</section>"""
    # The <title> becomes the PDF's Title, which viewers and browser tabs show instead of the file name.
    return ("<!doctype html><html lang='en-AU'><head><meta charset='utf-8'>"
            "<title>ATFA Expo &amp; Conference 2027: Hall 5 Floor Plan</title><style>" + css + "</style></head><body>"
            + page1 + page2 + "</body></html>")


def main():
    svg = plan_svg()
    html = build_html(svg)
    (DIST / "print.html").write_text(html, encoding="utf-8")
    with sync_playwright() as pw:
        browser = pw.chromium.launch()
        page = browser.new_page()
        page.set_content(html, wait_until="load")
        page.pdf(path=str(OUT_PDF), width="420mm", height="297mm", print_background=True, prefer_css_page_size=True)
        page.set_viewport_size({"width": 1588, "height": 1123})
        page.screenshot(path=str(DIST / "print-page1.png"), full_page=False)
        browser.close()
    print("wrote", OUT_PDF, OUT_PDF.stat().st_size, "bytes")


if __name__ == "__main__":
    main()
