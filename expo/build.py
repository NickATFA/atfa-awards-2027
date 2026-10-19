#!/usr/bin/env python3
"""
Build the ATFA Expo 2027 Hall 5 floor plan.

    python3 build.py              # uses settings.json

Inputs (src/):
    geometry.json   stands, hall outline, stage, seats, exits (all in metres)
    floorplan.js    the interactive plan (readable source)
    floorplan.css   styles, all scoped to #atfa-expo27 / #atfa-expo27-modal
    markup.html     the HTML shell printed by the shortcode
    snippet.php     WordPress code (shortcode + cached data endpoint)

Outputs:
    ../atfa-awards-2027/includes/expo-2027.php   the code the awards plugin loads (when this folder
                                 sits in the awards plugin repo, which it does)
Outputs (dist/, for the tests):
    atfa-expo27-floorplan.php    complete PHP file (for reading or version control)
    code-snippet.txt             the same code without "<?php", ready to paste into Code Snippets
    code-snippets-import.json    Code Snippets import file
    preview.html                 standalone page used by the automated tests
"""
import datetime
import html
import json
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent
SRC = ROOT / "src"
DIST = ROOT / "dist"
sys.path.insert(0, str(ROOT / "tools"))
import check_layout  # noqa: E402


def fail(msg):
    print("BUILD FAILED:", msg)
    sys.exit(1)


def main():
    settings = json.loads((ROOT / "settings.json").read_text(encoding="utf-8"))
    version = settings["version"]
    today = datetime.date.today().strftime("%-d %B %Y")

    geometry = json.loads((SRC / "geometry.json").read_text(encoding="utf-8"))
    stands = geometry["stands"]
    # Layout rules: perimeter lane, aisles, demo zone, route, entrances, sizes and prices.
    errors, summary = check_layout.validate(geometry)
    if errors:
        fail("layout problems:\n  " + "\n  ".join(errors))
    total = summary["total"]

    # Filter chips: one per stand size that's actually used, in the order of "sizes".
    used = [k for k in geometry["sizes"] if any(s["size"] == k for s in stands)]
    chips = "\n".join(
        '          <button type="button" class="afpx-chip" data-size="%s" aria-pressed="false">'
        '<span class="afpx-sw" style="background:%s;border-color:%s" aria-hidden="true"></span>%s</button>'
        % (k, geometry["sizes"][k]["fill"], geometry["sizes"][k]["line"], html.escape(geometry["sizes"][k]["label"]))
        for k in used)

    geo_js = json.dumps(geometry, separators=(",", ":"), ensure_ascii=False)
    js = (SRC / "floorplan.js").read_text(encoding="utf-8")
    js = js.replace("__GEOMETRY__", geo_js).replace("__VERSION__", version)
    css = (SRC / "floorplan.css").read_text(encoding="utf-8").replace("__VERSION__", version)
    markup = (SRC / "markup.html").read_text(encoding="utf-8").strip("\n")
    if "__SIZE_CHIPS__" not in markup:
        fail("markup is missing __SIZE_CHIPS__")
    markup = markup.replace("__SIZE_CHIPS__", chips)

    # Safety checks on what ends up inside the page.
    if re.search(r"</script", js, re.I):
        fail("JavaScript contains a closing script tag")
    if re.search(r"<[A-Za-z!/]", js):
        fail("JavaScript contains HTML-like text; page optimisers could rewrite it")
    if "&#" in js:
        fail("JavaScript contains an HTML entity")
    if re.search(r"</style", css, re.I):
        fail("CSS contains a closing style tag")
    for marker, body in (("ATFA_EXPO27_HTML", markup), ("ATFA_EXPO27_CSS", css), ("ATFA_EXPO27_JS", js)):
        if re.search(r"^\s*" + marker + r"\b", body, re.M):
            fail("content contains the heredoc marker " + marker)
    for token in ("{{VERSION}}", "{{EMAIL}}", "{{MAILTO}}", "{{PDF}}", "{{STAFF}}"):
        if token not in markup:
            fail("markup is missing " + token)

    # ---- The Expo page ([atfa_expo27_page]) ----
    sizes = geometry["sizes"]
    order = [k for k in sizes if any(s["size"] == k for s in stands)]
    counts = {k: sum(1 for s in stands if s["size"] == k) for k in order}
    min_price = min(sizes[k]["price"] for k in order)
    best = max(order, key=lambda k: sizes[k].get("disc", 0))

    def money(n):
        return "${:,.0f}".format(n)

    cards = []
    for k in order:
        z = sizes[k]
        disc = z.get("disc", 0)
        feat = k == best and disc > 0
        eyebrow = "Save %d%%" % round(disc * 100) if disc else "Base rate"
        top = ('<span class="eyebrow%s">%s</span>' % ("" if feat else " or", eyebrow)) + (
            '<span class="tag rib">Best value</span>' if feat else "")
        cards.append(
            '<div class="card xp-pc%s"><div class="top">%s</div><span class="disp s">%s</span>'
            '<span class="disp p">%s</span><span class="m">%d m² · %d stands<br>%s per m²</span></div>'
            % (" feat" if feat else "", top, html.escape(z["label"]), money(z["price"]), z["sqm"], counts[k],
               "${:,.2f}".format(z["price"] / z["sqm"])))
    rate = "".join('<li><span>%s · %d m²</span><b>%s</b></li>' % (html.escape(sizes[k]["label"]), sizes[k]["sqm"], money(sizes[k]["price"]))
                   for k in order)
    swatches = "".join('<span><i style="background:%s;border-color:%s"></i>%s</span>' % (sizes[k]["fill"], sizes[k]["line"], html.escape(sizes[k]["label"]))
                       for k in order)
    legend = swatches + "".join([
        '<span><i style="background:#3B2F1D;border-color:#D99A21"></i>On hold</span>',
        '<span><i style="background:#34302B;border-color:#6F665D"></i>Sold</span>',
        '<span><i style="background:#DCEAF7;border-color:#2E6DA4;box-shadow:inset 0 -3px 0 #0C0B0A"></i>Shared back wall</span>',
        '<span><i style="background:#27221E;border-color:#7A6E62;border-style:dashed"></i>Main pedestrian route</span>',
        '<span><i style="background:#2A1D11;border-color:#F8931F;border-style:dashed"></i>Demo &amp; stage zone</span>',
        '<span><i style="background:#2A1C1A;border-color:#4A2C28"></i>Fire / non-build lane</span>',
        '<span><i style="background:#F8931F;border-color:#F8931F"></i>Entrance</span>',
        '<span><i style="background:#E5534B;border-color:#E5534B"></i>Fire exit</span>',
    ])
    page_markup = (SRC / "page.html").read_text(encoding="utf-8").strip("\n")
    for token, val in (("__PRICE_CARDS__", "".join(cards)), ("__RATE_LIST__", rate), ("__LEGEND__", legend),
                       ("__PHONE_KEY__", swatches), ("__FROM_PRICE__", money(min_price)), ("__TOTAL__", str(len(stands)))):
        if token not in page_markup:
            fail("page markup is missing " + token)
        page_markup = page_markup.replace(token, val)
    page_js = (SRC / "page.js").read_text(encoding="utf-8").replace("__GEOMETRY__", geo_js).replace("__VERSION__", version)
    page_css = (SRC / "page.css").read_text(encoding="utf-8").replace("__VERSION__", version)
    if re.search(r"</script", page_js, re.I):
        fail("page JavaScript contains a closing script tag")
    if re.search(r"<[A-Za-z!/]", page_js):
        fail("page JavaScript contains HTML-like text; page optimisers could rewrite it")
    if "&#" in page_js:
        fail("page JavaScript contains an HTML entity")
    if re.search(r"</style", page_css, re.I):
        fail("page CSS contains a closing style tag")
    if re.search(r"__[A-Z_]+__", page_markup):
        fail("page markup has a build token left: " + re.search(r"__[A-Z_]+__", page_markup).group(0))
    for marker, body in (("ATFA_EXPO27_PAGE_HTML", page_markup), ("ATFA_EXPO27_PAGE_CSS", page_css), ("ATFA_EXPO27_PAGE_JS", page_js)):
        if re.search(r"^\s*" + marker + r"\b", body, re.M):
            fail("page content contains the heredoc marker " + marker)
    for token in ("{{VERSION}}", "{{LOGO}}", "{{WORDMARK}}", "{{HERO_IMGS}}", "{{HERO_CAPS}}", "{{GALLERY}}", "{{DINNER_IMG}}",
                  "{{DINNER_CAP}}", "{{AWARDS_URL}}", "{{PDF_URL}}", "{{EMAIL}}", "{{MAILTO}}", "{{ENQUIRY_NAME}}", "{{STAFF}}"):
        if token not in page_markup:
            fail("page markup is missing " + token)
    # Copy rules: no em dashes in anything a visitor reads.
    for name, body in (("page markup", page_markup), ("page script", page_js)):
        if "—" in body:
            fail(name + " contains an em dash")

    def php_single(value):
        return value.replace("\\", "\\\\").replace("'", "\\'")

    php = (SRC / "snippet.php").read_text(encoding="utf-8")
    replacements = {
        "__VERSION__": version,
        "__BUILD_DATE__": today,
        "__CSV_URL__": php_single(settings["csv_url"]),
        "__ENQUIRY_URL__": php_single(settings.get("enquiry_url", "")),
        "__PDF_URL__": php_single(settings.get("pdf_url", "")),
        "__SHEET_URL__": php_single(settings.get("sheet_url", "")),
        "__PAGE_SLUG__": php_single(settings.get("page_slug", "expo-2027")),
        "__OLD_PAGE_ID__": str(int(settings.get("old_page_id", 0))),
    }
    photos = settings.get("photos", {})
    for key in ("procoat", "cfs", "bona", "lagler", "norton", "podcast", "woody"):
        replacements["__PHOTO_%s__" % key.upper()] = str(int(photos.get(key, 0)))
    for key, val in replacements.items():
        php = php.replace(key, val)
    # Big blocks last, so their contents are never searched for tokens.
    for token, body in (("__XP_HTML__", page_markup), ("__XP_CSS__", page_css), ("__XP_JS__", page_js)):
        if php.count(token) != 1:
            fail("snippet should have exactly one " + token)
    php = php.replace("__XP_HTML__", page_markup, 1).replace("__XP_CSS__", page_css, 1).replace("__XP_JS__", page_js, 1)
    php = php.replace("__MARKUP__", markup, 1).replace("__CSS__", css, 1).replace("__JS__", js, 1)
    if "__" + "GEOMETRY__" in php:
        fail("geometry token left in output")
    leftover = re.findall(r"__(?:PHOTO_[A-Z]+|PAGE_SLUG|OLD_PAGE_ID|XP_[A-Z]+)__", php)
    if leftover:
        fail("tokens left in output: " + ", ".join(sorted(set(leftover))))

    DIST.mkdir(exist_ok=True)
    (DIST / "atfa-expo27-floorplan.php").write_text(php, encoding="utf-8")
    # In the awards plugin repo the same code ships inside the plugin, as includes/expo-2027.php.
    plugin_dir = ROOT.parent / "atfa-awards-2027"
    if plugin_dir.is_dir():
        body = php.split("\n", 1)[1] if php.startswith("<?php") else php
        include = ("<?php\n"
                   "// ATFA Expo & Conference 2027 page and Hall 5 floor plan, part of the ATFA Flooring Awards 2027 plugin.\n"
                   "// Generated by expo/build.py from expo/src. Don't edit this file: change the source and rebuild.\n"
                   "if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n" + body)
        (plugin_dir / "includes").mkdir(exist_ok=True)
        (plugin_dir / "includes" / "expo-2027.php").write_text(include, encoding="utf-8")
    code = php.split("\n", 1)[1].lstrip("\n") if php.startswith("<?php") else php
    (DIST / "code-snippet.txt").write_text(code, encoding="utf-8")

    export = {
        "generator": "ATFA Expo 2027 build.py",
        "date_created": datetime.datetime.now().strftime("%Y-%m-%d %H:%M"),
        "snippets": [{
            "name": "ATFA Expo 2027 Hall 5 floor plan (v%s)" % version,
            "desc": "Interactive floor plan for the ATFA Expo & Conference 2027 (Sydney Showground, Hall 5). "
                    "Shortcode [atfa_expo27_floorplan]. Reads the Website tab of the 2027 Exhibitor List "
                    "Google Sheet via /wp-json/atfa/v1/expo27/stands.",
            "tags": ["atfa", "expo-2027"],
            "scope": "global",
            "priority": 10,
            "code": code,
        }],
    }
    (DIST / "code-snippets-import.json").write_text(json.dumps(export, indent=2, ensure_ascii=False), encoding="utf-8")

    # Standalone preview for the automated tests (data endpoints are mocked by the tests).
    preview_cfg = {
        "version": version, "restUrl": "https://test.local/api/stands", "csvUrl": "https://test.local/api/csv",
        "enquiryEmail": "cherie@atfa.com.au", "enquiryName": "Cherie",
        "enquiryUrl": settings.get("enquiry_url", ""), "pdfUrl": settings.get("pdf_url", ""),
        "currency": "AUD", "priceNote": "excl. GST",
    }
    pdf_btn = ('<a class="afpx-btn is-ghost is-small" href="%s" target="_blank" rel="noopener">Download the PDF floor plan</a>'
               % settings["pdf_url"]) if settings.get("pdf_url") else ""
    shell = (markup.replace("{{VERSION}}", version).replace("{{EMAIL}}", "cherie@atfa.com.au")
             .replace("{{MAILTO}}", "mailto:cherie@atfa.com.au").replace("{{PDF}}", pdf_btn)
             .replace("{{STAFF}}", ""))
    preview = (
        "<!doctype html><html lang=\"en\"><head><meta charset=\"utf-8\">"
        "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
        "<title>Floor plan preview</title>"
        "<style>body{margin:0;padding:16px;background:#f2f2f2;font-family:Georgia,serif}"
        "/* hostile theme rules, to prove the plan ignores them */"
        "button{background:#F79320;color:#000;padding:10px 20px;font-size:16px;text-transform:uppercase;border-radius:0}"
        "h1,h2{text-transform:uppercase;color:red;font-family:Georgia}a{color:#00f;text-decoration:underline}"
        "ul{list-style:disc;padding-left:40px}li{margin:6px 0}</style>"
        "<script>window.__SNAPSHOT__=null</script></head><body>"
        "<p>Page content above the plan.</p>"
        "<style id=\"atfa-expo27-css\">" + css + "</style>" + shell +
        "<script type=\"application/json\" id=\"atfa-expo27-config\">" + json.dumps(preview_cfg) + "</script>"
        "<script type=\"application/json\" id=\"atfa-expo27-snapshot\">SNAPSHOT_JSON</script>"
        "<script id=\"atfa-expo27-js\">" + js + "</script>"
        "<p style=\"height:900px\">Page content below the plan.</p></body></html>"
    )
    (DIST / "preview.html").write_text(preview, encoding="utf-8")

    print("built v%s: php %d bytes, js %d, css %d, markup %d" % (version, len(php.encode()), len(js.encode()), len(css.encode()), len(markup.encode())))
    print("layout: %d stands %s, %d m2, total if all sold $%s; narrowest aisle %.1f m, demo zone %.1f m, "
          "route %.1f m to nearest stand, arrival area %.1f m, Entry 2 %.1f m"
          % (len(stands), json.dumps(summary["mix"]), summary["area"], format(total, ","), summary["min_aisle"],
             summary["demo_clear"], summary.get("route_nearest_stand", 0), summary.get("arrival_clear", 0),
             summary.get("entry2_clear", 0)))


if __name__ == "__main__":
    main()
