"""
Browser tests for the floor plan. Runs the real PHP output (tests/out/shortcode-*.html)
inside a page with deliberately hostile theme CSS, with all network mocked.

    python3 tests/e2e.py
"""
import json
import pathlib
import sys
import time

from playwright.sync_api import sync_playwright

ROOT = pathlib.Path(__file__).resolve().parents[1]
OUT = ROOT / "tests" / "out"
SHOTS = OUT / "shots"
SHOTS.mkdir(parents=True, exist_ok=True)

HOSTILE = """
body{margin:0;padding:16px;background:#f2f2f2;font-family:Georgia,serif}
button,.elementor-kit-9 button{background:#F79320;color:#000;padding:10px 20px;font-size:16px;text-transform:uppercase;border-radius:0;border:2px solid red;line-height:2}
h1,h2,h3{text-transform:uppercase;color:red;font-family:Georgia;margin:30px 0}
a{color:#00f;text-decoration:underline}
ul{list-style:disc;padding-left:40px} li{margin:6px 0}
input[type=search]{background:yellow;border:3px dashed red;padding:20px}
p{margin:20px 0;font-size:20px}
"""

results = []


def check(name, ok, detail=""):
    results.append((name, bool(ok), detail))
    print(("PASS " if ok else "FAIL ") + name + ("  -- " + str(detail) if detail and not ok else ""))


def page_html(fragment):
    return ("<!doctype html><html lang='en'><head><meta charset='utf-8'>"
            "<meta name='viewport' content='width=device-width, initial-scale=1'>"
            "<title>Expo 2027 test</title><style>" + HOSTILE + "</style></head><body>"
            "<p>Page content above the plan.</p>" + fragment +
            "<p style='height:1400px'>Page content below the plan.</p></body></html>")


HELD = []


def release_held(rest):
    while HELD:
        HELD.pop().fulfill(status=200, content_type="application/json", body=json.dumps(rest))


def make_router(fragment, rest=None, rest_status=200, hold_rest=False, csv=None, csv_status=200, static=None):
    """rest: dict to return from the WordPress endpoint (or None for 500).
    hold_rest: don't answer the endpoint until release_held() is called."""
    def handler(route):
        url = route.request.url
        if url.startswith("https://test.local/page"):
            return route.fulfill(status=200, content_type="text/html; charset=utf-8", body=page_html(fragment))
        if "/atfa-expo27/stands" in url:
            if static is None:
                return route.fulfill(status=404, body="not found")
            return route.fulfill(status=200, content_type="application/json", body=json.dumps(static))
        if "/wp-json/atfa/v1/expo27/stands" in url:
            if hold_rest:
                HELD.append(route)
                return None
            if rest is None or rest_status != 200:
                return route.fulfill(status=rest_status if rest_status != 200 else 500, body="error")
            return route.fulfill(status=200, content_type="application/json", body=json.dumps(rest))
        if "docs.google.com" in url:
            if csv is None or csv_status != 200:
                return route.fulfill(status=csv_status if csv_status != 200 else 404, body="Not found")
            return route.fulfill(status=200, content_type="text/csv; charset=utf-8", body=csv)
        return route.fulfill(status=404, body="blocked in tests")
    return handler


def strip_snapshot(html):
    import re
    return re.sub(r'(<script type="application/json" id="atfa-expo27-snapshot">).*?(</script>)', r"\1null\2", html, flags=re.S)


def with_snapshot(html, snap):
    import re
    body = json.dumps(snap).replace("</", "<\\/")
    return re.sub(r'(<script type="application/json" id="atfa-expo27-snapshot">).*?(</script>)', lambda m: m.group(1) + body + m.group(2), html, flags=re.S)


OLD_IDS = {"ok": True, "source": "live", "updated": "2026-10-01T00:00:00+00:00",
           "stands": [{"id": str(i), "status": "available"} for i in (101, 102, 201, 305, 401, 501, 601)]}


def counts(page):
    return page.evaluate("""() => ['available','hold','sold'].map(k => document.querySelector('[data-stat="'+k+'"]').textContent).join('/')""")


def stand_state(page, sid):
    return page.evaluate("""(id) => { const g=document.querySelector('.afpx-stand[data-id="'+id+'"]');
        return {cls:g.getAttribute('class'), sub:g.querySelector('.afpx-sub').textContent, label:g.getAttribute('aria-label')}; }""", sid)


def main():
    edge_html = (OUT / "shortcode-edge.html").read_text(encoding="utf-8")
    real_html = (OUT / "shortcode-real.html").read_text(encoding="utf-8")
    rest_edge = json.loads((OUT / "rest-edge.json").read_text(encoding="utf-8"))
    rest_real = json.loads((OUT / "rest-real.json").read_text(encoding="utf-8"))
    csv_edge = (ROOT / "tests" / "fixtures" / "edge.csv").read_text(encoding="utf-8")
    console_errors = []

    with sync_playwright() as pw:
        browser = pw.chromium.launch()

        # ---------------------------------------------------------------- desktop, warm snapshot + REST
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.on("console", lambda m: console_errors.append(m.text)
                if m.type == "error" and not m.text.startswith("Failed to load resource") else None)
        page.on("pageerror", lambda e: console_errors.append(str(e)))
        page.route("**/*", make_router(real_html, rest=rest_edge, hold_rest=True))
        page.goto("https://test.local/page", wait_until="domcontentloaded")
        page.wait_for_function("() => window.ATFAExpo27")
        first = counts(page)
        check("desktop: first paint uses the server snapshot (no placeholders)", first == "51/5/2", first)
        release_held(rest_edge)
        page.wait_for_function("() => document.querySelector('[data-stat=available]').textContent !== '51'", timeout=5000)
        check("desktop: REST data replaces the snapshot", counts(page) == "49/3/6", counts(page))
        sS02 = stand_state(page, "S02")
        check("desktop: sold stand shows company on the map", sS02["sub"].startswith("Smith") and "is-sold" in sS02["cls"], sS02)
        sO01 = stand_state(page, "O01")
        check("desktop: hold stand shows 'On hold' and no name", sO01["sub"] == "On hold" and "Secret" not in sO01["label"], sO01)
        sO02 = stand_state(page, "O02")
        check("desktop: HTML in a company name is shown as text", sO02["sub"].startswith("<b>"), sO02)
        check("desktop: no fake/sample exhibitors anywhere",
              page.evaluate("() => !/Example Timber|Coatings Australia|Subfloor Systems|Sanding Machinery/.test(document.body.innerText)"))
        check("desktop: unknown stand Z99 in data is ignored",
              page.evaluate("() => !document.querySelector('.afpx-stand[data-id=\"Z99\"]')"))
        sS04 = stand_state(page, "S04")
        check("desktop: 's4' in the sheet matches stand S04", "is-sold" in sS04["cls"] and "Lower Case Id Co" in sS04["label"], sS04)
        sB12 = stand_state(page, "B12")
        check("desktop: 'Stand B12' in the sheet matches B12, name kept private", "is-hold" in sB12["cls"] and "Hidden" not in sB12["label"], sB12)
        drawn = page.evaluate("""() => ({stands: document.querySelectorAll('.afpx-stand').length,
            walls: [...document.querySelectorAll('.afpx-svg line')].filter(l => l.getAttribute('stroke-width') === '0.36').length,
            route: !!document.querySelector('.afpx-svg polygon'),
            routeText: [...document.querySelectorAll('.afpx-svg text')].some(t => /MAIN PEDESTRIAN ROUTE/.test(t.textContent)),
            arrival: [...document.querySelectorAll('.afpx-svg text')].some(t => /ARRIVAL AREA/.test(t.textContent)),
            oldAisles: [...document.querySelectorAll('.afpx-svg text')].some(t => /MAIN AISLE|CROSS AISLE/.test(t.textContent))})""")
        check("desktop: 58-booth layout drawn (58 stands, 12 rear walls, no Concept 5 route or arrival area)",
              drawn == {"stands": 58, "walls": 12, "route": False, "routeText": False, "arrival": False, "oldAisles": False}, drawn)
        chips = page.evaluate("() => [...document.querySelectorAll('.afpx-chip[data-size]')].map(c => c.dataset.size + ':' + c.textContent.trim())")
        check("desktop: size filters match the sizes used", chips == ["3x3:3 × 3 m", "3x6:3 × 6 m", "6x3:6 × 3 m", "6x6:6 × 6 m"], chips)
        sides = page.evaluate("() => Object.fromEntries(ATFAExpo27.stands().map(s => [s.id, s.open]))")
        check("desktop: open sides worked out from the layout, rear walls included",
              [sides[k] for k in ("S01", "S04", "B01", "B03", "G01", "G02", "O03", "O05", "O12")] == [3, 3, 2, 1, 2, 1, 3, 2, 2], sides)

        # theme-proofing
        h1 = page.evaluate("() => { const s=getComputedStyle(document.querySelector('#atfa-expo27 h1')); return [s.textTransform, s.color, s.marginTop]; }")
        check("theme: H1 not uppercased/recoloured by theme", h1 == ["none", "rgb(255, 255, 255)", "0px"], h1)
        chip = page.evaluate("() => { const s=getComputedStyle(document.querySelector('.afpx-chip')); return [s.backgroundColor, s.textTransform, s.paddingLeft, s.fontSize]; }")
        check("theme: filter chips stay white, not theme orange", chip[0] == "rgb(255, 255, 255)" and chip[1] == "none", chip)
        zb = page.evaluate("() => { const b=document.querySelector('.afpx-zbtn'); const s=getComputedStyle(b); return [s.paddingTop, s.height, s.lineHeight]; }")
        check("theme: zoom buttons not padded by theme", zb[0] == "0px" and zb[1] == "32px", zb)
        inp = page.evaluate("() => { const s=getComputedStyle(document.querySelector('#afpx-q')); return [s.backgroundColor, s.borderTopStyle, s.height]; }")
        check("theme: search box not restyled", inp[0] == "rgb(255, 255, 255)" and inp[1] == "solid", inp)
        check("a11y: exactly one H1 in the plan", page.evaluate("() => document.querySelectorAll('#atfa-expo27 h1').length") == 1)
        check("a11y: search has a label", page.evaluate("() => !!document.querySelector('label[for=afpx-q]')"))
        check("a11y: svg is a group, not an image", page.evaluate("() => document.querySelector('.afpx-svg').getAttribute('role')") == "group")
        lbl = stand_state(page, "G01")["label"]
        check("a11y: stand label includes size, open sides, status and price",
              "available" in lbl and "$2,280" in lbl and "open on 2 sides" in lbl and "3 by 6 metres" in lbl, lbl)

        # layout
        geo = page.evaluate("""() => { const st=document.querySelector('.afpx-stage').getBoundingClientRect();
            const side=document.querySelector('.afpx-side').getBoundingClientRect();
            const s=document.querySelector('.afpx-stand[data-id="B07"] rect').getBoundingClientRect();
            return {stageH:Math.round(st.height), stageW:Math.round(st.width), sideH:Math.round(side.height), stand3x3:Math.round(s.width), vh:innerHeight}; }""")
        check("desktop: plan height leaves room to scroll past it (<= 78% of screen)", geo["stageH"] <= 0.78 * geo["vh"] + 1, geo)
        check("desktop: side list matches the plan column height", abs(geo["sideH"] - (geo["stageH"] + 110)) < 160, geo)
        page.screenshot(path=str(SHOTS / "desktop.png"), full_page=False)

        # wheel: no modifier -> page scrolls, plan unchanged; ctrl -> zoom
        page.mouse.move(400, 500)
        vb0 = page.evaluate("() => document.querySelector('.afpx-svg').getAttribute('viewBox')")
        y0 = page.evaluate("() => scrollY")
        page.mouse.wheel(0, 400)
        page.wait_for_timeout(300)
        vb1 = page.evaluate("() => document.querySelector('.afpx-svg').getAttribute('viewBox')")
        y1 = page.evaluate("() => scrollY")
        check("desktop: plain scroll over the plan scrolls the page", y1 > y0 and vb1 == vb0, (y0, y1, vb0 == vb1))
        hint = page.evaluate("() => document.querySelector('[data-role=hint]').className")
        check("desktop: hint suggests Ctrl/Cmd + scroll", "is-on" in hint, hint)
        page.keyboard.down("Control")
        page.mouse.wheel(0, -300)
        page.keyboard.up("Control")
        page.wait_for_timeout(200)
        vb2 = page.evaluate("() => document.querySelector('.afpx-svg').getAttribute('viewBox')")
        y2 = page.evaluate("() => scrollY")
        check("desktop: Ctrl + scroll zooms the plan without scrolling the page", vb2 != vb1 and y2 == y1, (vb1, vb2, y1, y2))

        # drag to pan
        page.evaluate("() => scrollTo(0,0)")
        box = page.locator(".afpx-stage").bounding_box()
        vb3 = page.evaluate("() => document.querySelector('.afpx-svg').getAttribute('viewBox')")
        page.mouse.move(box["x"] + 300, box["y"] + 300)
        page.mouse.down()
        page.mouse.move(box["x"] + 380, box["y"] + 360, steps=6)
        page.mouse.up()
        vb4 = page.evaluate("() => document.querySelector('.afpx-svg').getAttribute('viewBox')")
        check("desktop: drag pans the zoomed plan", vb4 != vb3, (vb3, vb4))
        check("desktop: drag doesn't open a popup", page.evaluate("() => document.getElementById('atfa-expo27-modal').hidden"))
        page.click(".afpx-zreset")

        # popups
        before_w = page.evaluate("() => Math.round(document.querySelector('.afpx-stage').getBoundingClientRect().width)")
        page.click(".afpx-stand[data-id='S02']")
        page.wait_for_selector("#atfa-expo27-modal:not([hidden])")
        after_w = page.evaluate("() => Math.round(document.querySelector('.afpx-stage').getBoundingClientRect().width)")
        check("popup: page behind doesn't shift when it opens", before_w == after_w, (before_w, after_w))
        pop = page.evaluate("""() => { const m=document.getElementById('atfa-expo27-modal');
            return {title:m.querySelector('h2').textContent, meta:m.querySelector('.afpx-meta').textContent,
                    bios:[...m.querySelectorAll('.afpx-bio')].map(p=>p.textContent),
                    link:(m.querySelector('a.afpx-btn')||{}).href, site:(m.querySelector('.afpx-site')||{}).textContent,
                    parent:m.parentNode===document.body, focus:document.activeElement.className}; }""")
        check("popup: sold stand shows company, stand and size", pop["title"] == "Smith, Jones & Co" and "Stand S02 · 6 × 6 m" in pop["meta"], pop)
        check("popup: bio paragraphs kept", len(pop["bios"]) == 2 and '"quotes"' in pop["bios"][1], pop["bios"])
        check("popup: website without https:// fixed", pop["link"] == "https://www.smithjones.com.au/", pop["link"])
        check("popup: website name shown without www", pop["site"] == "smithjones.com.au", pop["site"])
        check("popup: moved to body and focus on close button", pop["parent"] and "afpx-x" in pop["focus"], pop)
        page.screenshot(path=str(SHOTS / "desktop-popup-sold.png"))
        page.keyboard.press("Escape")
        check("popup: Escape closes", page.evaluate("() => document.getElementById('atfa-expo27-modal').hidden"))
        check("popup: focus returns to the stand", page.evaluate("() => document.activeElement.getAttribute('data-id')") == "S02")

        page.click(".afpx-stand[data-id='O02']")
        p104 = page.evaluate("""() => { const m=document.getElementById('atfa-expo27-modal');
            return {title:m.querySelector('h2').textContent, html:m.querySelector('h2').innerHTML, hasLink:!!m.querySelector('a.afpx-btn'),
                    bio:(m.querySelector('.afpx-bio')||{}).textContent}; }""")
        check("popup: javascript: link dropped", not p104["hasLink"], p104)
        check("popup: tags in names are text, not markup", p104["title"] == "<b>Bold</b> Co" and "&lt;b&gt;" in p104["html"], p104)
        check("popup: '<' and '>' in bios survive", p104["bio"] == "Boards < 5mm and > 2mm thick", p104["bio"])
        page.click("#atfa-expo27-modal .afpx-modal-bd", position={"x": 5, "y": 5})
        page.click(".afpx-stand[data-id='O05']")
        site = page.evaluate("() => document.querySelector('#atfa-expo27-modal .afpx-site').textContent")
        check("popup: international website names shown as typed (not xn--)", site == "émile.example", site)
        page.keyboard.press("Escape")
        check("popup: clicking the backdrop closes", page.evaluate("() => document.getElementById('atfa-expo27-modal').hidden"))

        page.click(".afpx-stand[data-id='O01']")
        p103 = page.evaluate("""() => { const m=document.getElementById('atfa-expo27-modal');
            return {title:m.querySelector('h2').textContent, badge:m.querySelector('.afpx-badge').textContent, text:m.innerText, btn:(m.querySelector('a.afpx-btn')||{}).textContent}; }""")
        check("popup: hold shows no company and an 'Ask about' button",
              p103["title"] == "Stand O01" and "Secret" not in p103["text"] and p103["btn"] == "Ask about stand O01", p103)
        page.keyboard.press("Escape")

        page.click(".afpx-stand[data-id='G01']")
        p202 = page.evaluate("""() => { const m=document.getElementById('atfa-expo27-modal');
            const a=m.querySelector('a.afpx-btn'); return {price:m.querySelector('.afpx-price').textContent, note:m.querySelector('.afpx-price-note').textContent,
            btn:a.textContent, href:a.getAttribute('href'), alt:(m.querySelector('.afpx-alt')||{}).textContent,
            open:(m.querySelector('.afpx-open')||{}).textContent, mail:(m.querySelector('.afpx-alt a')||{}).href}; }""")
        check("popup: available stand shows price and enquiry", p202["price"] == "$2,280" and p202["btn"] == "Enquire about stand G01", p202)
        check("popup: says how many sides are open", p202["open"] == "Open on 2 sides", p202["open"])
        check("popup: email link has the stand in the subject", "Stand%20G01" in (p202["mail"] or ""), p202["mail"])
        check("popup: price note shows $/m2 and the size saving", "$126.67 per m²" in p202["note"] and "5% size saving" in p202["note"], p202["note"])
        check("popup: email fallback shown", "cherie@atfa.com.au" in (p202["alt"] or ""), p202)
        check("popup: Enquire goes to the form with the stand filled in", p202["href"] == "https://www.atfa.com.au/expo-2027-stand-enquiry/?stand=G01", p202["href"])
        pdf = page.evaluate("() => { const a=document.querySelector('#atfa-expo27 .afpx-legend a.afpx-btn'); return a ? a.getAttribute('href') : null; }")
        check("legend: PDF download button present (the copy in the plugin)", pdf and "/atfa-awards-2027/assets/expo/ATFA-Expo-2027-Hall-5-Floor-Plan.pdf?v=" in pdf, pdf)
        check("popup: no em dashes in visible text", page.evaluate("() => !/—/.test(document.getElementById('atfa-expo27').innerText + document.getElementById('atfa-expo27-modal').innerText)"))
        page.screenshot(path=str(SHOTS / "desktop-popup-available.png"))
        # Tab trap
        for _ in range(8):
            page.keyboard.press("Tab")
        inside = page.evaluate("() => document.getElementById('atfa-expo27-modal').contains(document.activeElement)")
        check("popup: Tab stays inside the popup", inside)
        page.keyboard.press("Escape")

        # keyboard on the map
        page.evaluate("() => document.querySelector('.afpx-stand[data-id=\"S01\"]').focus()")
        page.keyboard.press("ArrowRight")
        f1 = page.evaluate("() => document.activeElement.getAttribute('data-id')")
        check("keyboard: arrow moves to the neighbouring stand", f1 == "O01", f1)
        page.keyboard.press("Enter")
        check("keyboard: Enter opens the popup", not page.evaluate("() => document.getElementById('atfa-expo27-modal').hidden"))
        page.keyboard.press("Escape")
        tabstops = page.evaluate("() => document.querySelectorAll('.afpx-stand[tabindex=\"0\"]').length")
        check("keyboard: one tab stop for the whole map", tabstops == 1, tabstops)
        ids = page.evaluate("() => ATFAExpo27.stands().map(s => s.id)")
        graph = {}
        for sid in ids:
            graph[sid] = []
            for key in ("ArrowRight", "ArrowLeft", "ArrowUp", "ArrowDown"):
                page.evaluate("(id) => document.querySelector('.afpx-stand[data-id=\"' + id + '\"]').focus()", sid)
                page.keyboard.press(key)
                graph[sid].append(page.evaluate("() => document.activeElement.getAttribute('data-id')"))
        seen, todo = {"S01"}, ["S01"]
        while todo:
            for nxt in graph[todo.pop()]:
                if nxt and nxt not in seen:
                    seen.add(nxt); todo.append(nxt)
        check("keyboard: every stand can be reached with the arrow keys", len(seen) == 58, sorted(set(ids) - seen))
        check("keyboard: Right from O04 doesn't jump up to the top row", graph["O04"][0] not in ("B13", "B14", "B15", "B16"), graph["O04"])

        # filters and search
        page.click(".afpx-chip[data-filter='available']")
        page.mouse.move(5, 5)
        page.wait_for_timeout(300)
        avail = page.evaluate("() => document.querySelectorAll('.afpx-item').length")
        chip_on = page.evaluate("() => { const c=document.querySelector('.afpx-chip[data-filter=available]'); return [c.getAttribute('aria-pressed'), getComputedStyle(c).backgroundColor]; }")
        check("filters: Available only shows available stands", avail == 49, avail)
        check("filters: pressed chip is clearly highlighted", chip_on == ["true", "rgb(168, 96, 12)"], chip_on)
        page.click(".afpx-chip[data-filter='available']")
        def search(term):
            page.fill("#afpx-q", term)
            return page.evaluate("() => [...document.querySelectorAll('.afpx-item')].map(b => b.dataset.id)")
        found = search("smith")
        check("search: finds a sold exhibitor by name", found == ["S02"], found)
        found = search("s0")
        check("search: stand-number prefix", found == ["S01", "S02", "S03", "S04"], found)
        found = search("b1")
        check("search: 'b1' finds B01 and B10 to B19", sorted(found) == ["B01"] + ["B%d" % n for n in range(10, 20)], found)
        found = search("12")
        check("search: a plain number finds every stand numbered 12", sorted(found) == ["B12", "O12"], found)
        found = search("B01")
        check("search: a full stand number finds just that stand", found == ["B01"], found)
        found = search("01")
        check("search: '01' finds every stand numbered 01", found == ["B01", "G01", "O01", "S01"], found)
        found = search("Stand O0")
        check("search: 'Stand O0' finds O01 to O09", sorted(found) == ["O0%d" % n for n in range(1, 10)], found)
        found = search("Secret")
        check("search: hold names can't be found", found == [], found)
        found = search("Hidden Hold")
        check("search: hold names can't be found (B12)", found == [], found)
        search("")
        page.click(".afpx-item[data-id='B05']")
        check("list: clicking a stand opens its popup", page.evaluate("() => document.getElementById('atfa-expo27-modal').querySelector('h2').textContent") == "Stand B05")
        page.keyboard.press("Escape")
        check("list: focus goes back to the list item", page.evaluate("() => document.activeElement.dataset.id + '|' + document.activeElement.className") == "B05|afpx-item is-sel")

        # data refresh while the popup is open keeps keyboard focus inside it
        page.click(".afpx-stand[data-id='G01']")
        page.keyboard.press("Tab")
        before = page.evaluate("() => document.activeElement.textContent")
        page.evaluate("() => { window.__rebuilt = 0; new MutationObserver(() => window.__rebuilt++).observe(document.querySelector('#atfa-expo27-modal [data-role=mbody]'), {childList: true}); ATFAExpo27.reload(); }")
        page.wait_for_timeout(300)
        held = len(HELD)
        release_held(rest_edge)
        page.wait_for_timeout(400)
        check("popup: reload asked the live endpoint and rebuilt the popup", held == 1 and page.evaluate("() => window.__rebuilt > 0"), held)
        after = page.evaluate("() => [document.getElementById('atfa-expo27-modal').contains(document.activeElement), document.activeElement.textContent]")
        check("popup: fresh data while it's open doesn't lose focus", after == [True, before], (before, after))
        page.keyboard.press("Escape")
        ctx.close()

        # ---------------------------------------------------------------- REST fails -> CSV fallback, no snapshot
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.on("pageerror", lambda e: console_errors.append(str(e)))
        page.route("**/*", make_router(strip_snapshot(edge_html), rest=None, rest_status=500, csv=csv_edge))
        page.goto("https://test.local/page")
        page.wait_for_function("() => document.querySelector('[data-stat=sold]').textContent !== '–'", timeout=8000)
        c = counts(page)
        check("fallback: REST down, Google CSV used", c == "49/3/6", c)
        page.click(".afpx-stand[data-id='S02']")
        bios = page.evaluate("() => [...document.querySelectorAll('#atfa-expo27-modal .afpx-bio')].map(p=>p.textContent)")
        check("fallback: client CSV parser keeps multi-line bios and pipes", len(bios) == 2 and "|" in bios[1], bios)
        page.keyboard.press("Escape")
        check("fallback: hold names dropped client-side too", not page.evaluate("() => /Secret|Hidden Hold/.test(document.body.innerText + JSON.stringify(ATFAExpo27.stands()))"))
        ctx.close()

        # ---------------------------------------------------------------- everything fails, no snapshot
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.on("pageerror", lambda e: console_errors.append(str(e)))
        page.route("**/*", make_router(strip_snapshot(edge_html), rest=None, rest_status=500, csv=None))
        page.goto("https://test.local/page")
        page.wait_for_function("() => !document.querySelector('[data-role=notice]').hidden", timeout=8000)
        note = page.evaluate("() => document.querySelector('[data-role=notice]').textContent")
        st = stand_state(page, "S01")
        check("offline: clear notice, stands 'not confirmed', nothing invented", "couldn" in note and "is-unknown" in st["cls"], (note, st))
        page.click(".afpx-stand[data-id='S01']")
        txt = page.evaluate("() => document.getElementById('atfa-expo27-modal').innerText")
        check("offline: popup still offers price and enquiry", "$4,080" in txt and "Enquire about stand S01" in txt and "couldn" in txt, txt[:200])
        page.screenshot(path=str(SHOTS / "offline.png"))
        ctx.close()

        # ---------------------------------------------------------------- odd spaces from the sheet, read in the browser
        csv_uni = (ROOT / "tests" / "fixtures" / "unicode.csv").read_text(encoding="utf-8")
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.on("pageerror", lambda e: console_errors.append(str(e)))
        page.route("**/*", make_router(strip_snapshot(edge_html), rest=None, rest_status=500, csv=csv_uni))
        page.goto("https://test.local/page")
        page.wait_for_function("() => document.querySelector('[data-stat=sold]').textContent !== '–'", timeout=8000)
        st = page.evaluate("() => Object.fromEntries(ATFAExpo27.stands().filter(s => s.status !== 'available').map(s => [s.id, s.status + ':' + s.name]))")
        check("fallback: odd spaces in the sheet read the same in the browser as on the server",
              st == {"S01": "sold:Acme Timber", "S02": "sold:Beta Floors", "S03": "hold:", "O05": "sold:Wide Space Co", "O06": "sold:Spaced Co",
                     "O01": "sold:Zero Width Co", "O02": "hold:"}, st)
        ctx.close()
        rest_uni = json.loads((OUT / "rest-unicode.json").read_text(encoding="utf-8"))
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.route("**/*", make_router(strip_snapshot(edge_html), rest=rest_uni))
        page.goto("https://test.local/page")
        page.wait_for_function("() => document.querySelector('[data-stat=sold]').textContent !== '–'", timeout=8000)
        check("server: odd spaces in the sheet give the same counts", counts(page) == "51/2/5", counts(page))
        ctx.close()

        # ---------------------------------------------------------------- data that doesn't match these stands is ignored
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.route("**/*", make_router(with_snapshot(real_html, OLD_IDS), rest=rest_edge, hold_rest=True,
                                       static=dict(OLD_IDS, updated="2099-01-01T00:00:00+00:00")))
        page.goto("https://test.local/page", wait_until="domcontentloaded")
        page.wait_for_function("() => window.ATFAExpo27")
        page.wait_for_timeout(500)
        early = (counts(page), stand_state(page, "S01")["cls"])
        check("old stand numbers: an old snapshot or fast copy isn't shown as 'all available'", early[0] == "–/–/–" and "is-loading" in early[1], early)
        release_held(rest_edge)
        page.wait_for_function("() => document.querySelector('[data-stat=available]').textContent === '49'", timeout=5000)
        check("old stand numbers: live data still arrives", counts(page) == "49/3/6", counts(page))
        ctx.close()
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.route("**/*", make_router(strip_snapshot(real_html), rest=OLD_IDS, csv=None))
        page.goto("https://test.local/page")
        page.wait_for_function("() => !document.querySelector('[data-role=notice]').hidden", timeout=8000)
        st = stand_state(page, "S01")
        check("old stand numbers: a sheet still on the old numbers shows 'not confirmed', not 'available'", "is-unknown" in st["cls"], st)
        ctx.close()

        # ---------------------------------------------------------------- everything fails, snapshot present
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.route("**/*", make_router(real_html, rest=None, rest_status=500, csv=None))
        page.goto("https://test.local/page")
        page.wait_for_function("() => !document.querySelector('[data-role=notice]').hidden", timeout=8000)
        note = page.evaluate("() => document.querySelector('[data-role=notice]').textContent")
        check("stale: keeps last known data and says when it's from", counts(page) == "51/5/2" and "as of" in note, (counts(page), note))
        ctx.close()

        # ---------------------------------------------------------------- slow REST, no snapshot: loading state, never sample data
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.route("**/*", make_router(strip_snapshot(real_html), rest=rest_real, hold_rest=True))
        page.goto("https://test.local/page", wait_until="domcontentloaded")
        page.wait_for_function("() => window.ATFAExpo27")
        early = stand_state(page, "S01")
        check("loading: stands show as loading, not as sample exhibitors", "is-loading" in early["cls"] and counts(page) == "–/–/–", (early, counts(page)))
        page.click(".afpx-stand[data-id='S03']")
        txt = page.evaluate("() => document.getElementById('atfa-expo27-modal').innerText")
        check("loading: popup says it's checking, not that it failed", "Checking live availability" in txt and "couldn" not in txt, txt[:160])
        page.screenshot(path=str(SHOTS / "loading.png"))
        release_held(rest_real)
        page.wait_for_function("() => document.querySelector('[data-stat=available]').textContent === '51'", timeout=8000)
        check("loading: real data arrives", counts(page) == "51/5/2")
        txt = page.evaluate("() => document.getElementById('atfa-expo27-modal').innerText")
        check("loading: open popup updates when data arrives (S03 on hold)", "on hold at the moment" in txt and "Ask about stand S03" in txt, txt[:200])
        page.keyboard.press("Escape")
        ctx.close()

        # ---------------------------------------------------------------- fast static copy
        newer_static = dict(rest_edge, updated="2099-01-01T00:00:00+00:00")
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.route("**/*", make_router(real_html, rest=rest_edge, hold_rest=True, static=newer_static))
        page.goto("https://test.local/page", wait_until="domcontentloaded")
        page.wait_for_function("() => document.querySelector('[data-stat=available]').textContent === '49'", timeout=5000)
        check("static copy: newer data shows before the slow live endpoint answers", counts(page) == "49/3/6" and len(HELD) == 1, counts(page))
        release_held(rest_edge)
        ctx.close()
        older_static = dict(rest_edge, updated="2001-01-01T00:00:00+00:00")
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.route("**/*", make_router(real_html, rest=rest_edge, hold_rest=True, static=older_static))
        page.goto("https://test.local/page", wait_until="domcontentloaded")
        page.wait_for_timeout(600)
        check("static copy: older than the page's own data is ignored", counts(page) == "51/5/2", counts(page))
        release_held(rest_edge)
        page.wait_for_function("() => document.querySelector('[data-stat=available]').textContent === '49'", timeout=5000)
        check("static copy: live endpoint still wins afterwards", counts(page) == "49/3/6", counts(page))
        ctx.close()

        # ---------------------------------------------------------------- phone
        iphone = pw.devices["iPhone 13"]
        ctx = browser.new_context(**iphone)
        page = ctx.new_page()
        page.on("pageerror", lambda e: console_errors.append(str(e)))
        page.route("**/*", make_router(real_html, rest=rest_edge))
        page.goto("https://test.local/page")
        page.wait_for_function("() => document.querySelector('[data-stat=available]').textContent === '49'", timeout=8000)
        m = page.evaluate("""() => { const st=document.querySelector('.afpx-stage'); const r=st.getBoundingClientRect();
            const s=document.querySelector('.afpx-stand[data-id="B07"] rect').getBoundingClientRect();
            const big=document.querySelector('.afpx-stand[data-id="S01"] rect').getBoundingClientRect();
            const side=document.querySelector('.afpx-side').getBoundingClientRect();
            return {vw:innerWidth, vh:innerHeight, stageW:Math.round(r.width), stageH:Math.round(r.height), s3:Math.round(s.width), s6:Math.round(big.width),
                    touchAction:getComputedStyle(st).touchAction, sideBelow: side.top > r.bottom - 1, docW:document.documentElement.scrollWidth,
                    help:document.querySelector('[data-role=help]').textContent}; }""")
        check("phone: no sideways scrolling", m["docW"] <= m["vw"], m)
        check("phone: plan starts zoomed on the stands (3x3 >= 20px)", m["s3"] >= 20, m)
        check("phone: plan is not taller than ~70% of the screen", m["stageH"] <= 0.73 * m["vh"], m)
        check("phone: one finger scrolls the page", m["touchAction"] == "pan-x pan-y", m)
        check("phone: list sits under the plan", m["sideBelow"], m)
        check("phone: help text mentions two fingers", "two fingers" in m["help"], m["help"])
        tb = page.evaluate("() => { const t=document.querySelector('.afpx-toolbar').getBoundingClientRect(); return Math.round(t.height); }")
        check("phone: filters and zoom fit on one row", tb <= 56, tb)
        chipw = page.evaluate("() => [...document.querySelectorAll('.afpx-chip')].map(c => Math.round(c.getBoundingClientRect().width))")
        check("phone: filter chips keep their full width (scroll sideways)", min(chipw) >= 60, chipw)
        targets = page.evaluate("""() => ({chip: Math.round(document.querySelector('.afpx-chip').getBoundingClientRect().height),
            zoom: [...document.querySelectorAll('.afpx-zbtn')].map(b => { const r = b.getBoundingClientRect(); return Math.round(Math.min(r.width, r.height)); }),
            item: Math.round(document.querySelector('.afpx-item').getBoundingClientRect().height),
            searchFont: parseFloat(getComputedStyle(document.querySelector('#afpx-q')).fontSize)})""")
        check("phone: touch targets are a comfortable size (chips, zoom, list rows)",
              targets["chip"] >= 36 and min(targets["zoom"]) >= 38 and targets["item"] >= 44, targets)
        check("phone: search text is 16px so iPhones don't zoom in when it's tapped", targets["searchFont"] >= 16, targets)
        lst = page.evaluate("""() => { const l=document.querySelector('.afpx-list'); const cs=getComputedStyle(l);
            return {overflow: cs.overflowY, scroll: l.scrollHeight - l.clientHeight, items: l.querySelectorAll('.afpx-item').length}; }""")
        check("phone: stand list scrolls with the page (no box-within-a-box scrolling)", lst["overflow"] == "visible" and lst["scroll"] <= 1 and lst["items"] == 58, lst)
        lbl = page.evaluate("""() => ['B07','S01'].map(id => { const g=document.querySelector('.afpx-stand[data-id="'+id+'"]');
            return [g.querySelector('.afpx-sub').getAttribute('display'), Math.round(g.querySelector('.afpx-lbl').getBoundingClientRect().height)]; })""")
        check("phone: zoomed out, stands show just a big, readable number", lbl[0][0] == "none" and lbl[0][1] >= 8 and lbl[1][1] >= 11, lbl)
        fits = page.evaluate("""() => [...document.querySelectorAll('.afpx-stand')].filter(g => {
            const r=g.querySelector('rect').getBoundingClientRect(), t=g.querySelector('.afpx-lbl').getBoundingClientRect();
            return t.width > r.width + 0.5 || t.left < r.left - 0.5 || t.right > r.right + 0.5; }).map(g => g.dataset.id)""")
        check("phone: every stand number fits inside its stand", fits == [], fits)
        page.screenshot(path=str(SHOTS / "phone.png"))
        page.screenshot(path=str(SHOTS / "phone-full.png"), full_page=True)

        # two-finger pinch via CDP
        cdp = ctx.new_cdp_session(page)
        box = page.locator(".afpx-stage").bounding_box()
        cx, cy = box["x"] + box["width"] / 2, box["y"] + box["height"] / 2
        vb0 = page.evaluate("() => document.querySelector('.afpx-svg').getAttribute('viewBox')")
        def touch(kind, pts):
            cdp.send("Input.dispatchTouchEvent", {"type": kind, "touchPoints": [{"x": x, "y": y, "id": i} for i, (x, y) in enumerate(pts)]})
        touch("touchStart", [(cx - 30, cy), (cx + 30, cy)])
        for step in range(1, 7):
            touch("touchMove", [(cx - 30 - step * 15, cy), (cx + 30 + step * 15, cy)])
        touch("touchEnd", [])
        page.wait_for_timeout(200)
        vb1 = page.evaluate("() => document.querySelector('.afpx-svg').getAttribute('viewBox')")
        w0, w1 = float(vb0.split()[2]), float(vb1.split()[2])
        check("phone: two-finger pinch zooms the plan", w1 < w0 * 0.8, (vb0, vb1))

        page.click(".afpx-zreset")
        for _ in range(3):
            page.click(".afpx-zbtn[data-zoom='in']")
        shown = page.evaluate("() => document.querySelector('.afpx-stand[data-id=\"B07\"] .afpx-sub').getAttribute('display')")
        check("phone: zooming in brings back the small print on stands", shown == "inline", shown)
        page.click(".afpx-zreset")

        page.tap(".afpx-stand[data-id='G01']")
        page.wait_for_selector("#atfa-expo27-modal:not([hidden])")
        page.wait_for_timeout(300)
        card = page.evaluate("""() => { const c=document.querySelector('#atfa-expo27-modal .afpx-modal-card').getBoundingClientRect();
            const x=document.querySelector('#atfa-expo27-modal .afpx-x').getBoundingClientRect();
            return {bottom:Math.round(c.bottom), top:Math.round(c.top), w:Math.round(c.width), vh:innerHeight, vw:innerWidth, close:Math.round(Math.min(x.width, x.height)),
                    open:document.querySelector('#atfa-expo27-modal .afpx-open').textContent}; }""")
        check("phone: tapping an available stand opens a bottom sheet", abs(card["bottom"] - card["vh"]) <= 1 and card["w"] == card["vw"] and card["top"] > 0, card)
        check("phone: sheet close button is easy to tap", card["close"] >= 40, card)
        page.screenshot(path=str(SHOTS / "phone-popup.png"))

        # drag the sheet down: a short drag springs back, a long one closes it
        def drag_sheet(dist):
            c = page.evaluate("() => { const r=document.querySelector('#atfa-expo27-modal .afpx-modal-card').getBoundingClientRect(); return [r.left + r.width/2, r.top + 30]; }")
            touch("touchStart", [(c[0], c[1])])
            steps = 8
            for k in range(1, steps + 1):
                touch("touchMove", [(c[0], c[1] + dist * k / steps)])
                page.wait_for_timeout(25)
            touch("touchEnd", [])
            page.wait_for_timeout(450)
        drag_sheet(40)
        still = page.evaluate("() => [document.getElementById('atfa-expo27-modal').hidden, document.querySelector('#atfa-expo27-modal .afpx-modal-card').style.transform]")
        check("phone: a small drag on the sheet springs back", still == [False, ""], still)
        drag_sheet(260)
        check("phone: dragging the sheet down closes it", page.evaluate("() => document.getElementById('atfa-expo27-modal').hidden"))
        page.tap(".afpx-stand[data-id='G02']")
        page.wait_for_selector("#atfa-expo27-modal:not([hidden])")
        isl = page.evaluate("() => document.querySelector('#atfa-expo27-modal .afpx-open').textContent")
        check("phone: a stand against a rear wall says it's open on 1 side", isl == "Open on 1 side", isl)
        page.tap("#atfa-expo27-modal .afpx-modal-bd", position={"x": 20, "y": 20})
        page.wait_for_timeout(150)
        check("phone: tapping outside the sheet closes it", page.evaluate("() => document.getElementById('atfa-expo27-modal').hidden"))
        # B01 is at the west end of its row, with open floor to its left
        b = page.evaluate("() => { const r=document.querySelector('.afpx-stand[data-id=\"B01\"] rect').getBoundingClientRect(); return [r.left, r.top + r.height/2]; }")
        page.touchscreen.tap(b[0] - 5, b[1])
        page.wait_for_timeout(200)
        near = page.evaluate("() => !document.getElementById('atfa-expo27-modal').hidden && document.querySelector('#afpx-mtitle').textContent")
        check("phone: a tap just outside a small stand still opens it", near == "Stand B01", near)
        page.keyboard.press("Escape")
        page.wait_for_timeout(100)
        # open floor between the O13 block and O17, 3 m from every stand
        far = page.evaluate("() => { const svg=document.querySelector('.afpx-svg'); const p=svg.createSVGPoint(); p.x=24; p.y=36; const q=p.matrixTransform(svg.getScreenCTM()); return [q.x, q.y]; }")
        page.touchscreen.tap(far[0], far[1])
        page.wait_for_timeout(200)
        check("phone: tapping empty floor opens nothing", page.evaluate("() => document.getElementById('atfa-expo27-modal').hidden"))
        # middle of the 3 m cross lane between the top row (B07) and G03 below it
        mid = page.evaluate("() => { const svg=document.querySelector('.afpx-svg'); const p=svg.createSVGPoint(); p.x=36.5; p.y=13.5; const q=p.matrixTransform(svg.getScreenCTM()); return [q.x, q.y]; }")
        page.touchscreen.tap(mid[0], mid[1])
        page.wait_for_timeout(200)
        # The lanes are 3 m (about 20 px here), so the phone's own fat-finger help may give the
        # tap to a stand on either side; it must never open one further away.
        got = page.evaluate("() => document.getElementById('atfa-expo27-modal').hidden ? null : document.querySelector('#afpx-mtitle').textContent")
        check("phone: a tap in the middle of a lane opens nothing, or a stand right next to it",
              got in (None, "Stand B07", "Stand B08", "Stand G02", "Stand G03"), got)
        ctx.close()

        # ---------------------------------------------------------------- small phone and landscape phone
        for label, opts in (("small phone 320px", dict(viewport={"width": 320, "height": 568}, device_scale_factor=2, is_mobile=True, has_touch=True)),
                            ("landscape phone", dict(viewport={"width": 844, "height": 390}, device_scale_factor=3, is_mobile=True, has_touch=True))):
            ctx = browser.new_context(**opts)
            page = ctx.new_page()
            page.on("pageerror", lambda e: console_errors.append(str(e)))
            page.route("**/*", make_router(real_html, rest=rest_edge))
            page.goto("https://test.local/page")
            page.wait_for_function("() => document.querySelector('[data-stat=available]').textContent === '49'", timeout=8000)
            d = page.evaluate("""() => ({docW: document.documentElement.scrollWidth, vw: innerWidth, vh: innerHeight,
                stageH: Math.round(document.querySelector('.afpx-stage').getBoundingClientRect().height),
                tb: Math.round(document.querySelector('.afpx-toolbar').getBoundingClientRect().height)})""")
            s3 = page.evaluate("() => Math.round(document.querySelector('.afpx-stand[data-id=\"B07\"] rect').getBoundingClientRect().width)")
            check(label + ": no sideways scrolling, plan fits the screen", d["docW"] <= d["vw"] and d["stageH"] <= 0.9 * d["vh"] and d["stageH"] >= 180, d)
            check(label + ": stands big enough to tap (3x3 >= 16px)", s3 >= 16, s3)
            page.tap(".afpx-stand[data-id='O05']")
            page.wait_for_selector("#atfa-expo27-modal:not([hidden])")
            page.wait_for_timeout(300)
            c = page.evaluate("() => { const r=document.querySelector('#atfa-expo27-modal .afpx-modal-card').getBoundingClientRect(); return [Math.round(r.top), Math.round(r.bottom), innerHeight]; }")
            check(label + ": popup fits on screen", c[0] >= 0 and c[1] <= c[2] + 1, c)
            page.screenshot(path=str(SHOTS / (label.replace(" ", "-") + ".png")))
            ctx.close()

        # ---------------------------------------------------------------- theme that keeps a fixed scrollbar gutter
        ctx = browser.new_context(viewport={"width": 1366, "height": 900})
        page = ctx.new_page()
        page.route("**/*", make_router(real_html.replace('<style id="atfa-expo27-css">', '<style>html{scrollbar-gutter:stable}</style><style id="atfa-expo27-css">'), rest=rest_edge))
        page.goto("https://test.local/page")
        page.wait_for_function("() => document.querySelector('[data-stat=available]').textContent === '49'", timeout=8000)
        w0 = page.evaluate("() => Math.round(document.querySelector('.afpx-stage').getBoundingClientRect().width)")
        page.click(".afpx-stand[data-id='S04']")
        page.wait_for_selector("#atfa-expo27-modal:not([hidden])")
        w1 = page.evaluate("() => Math.round(document.querySelector('.afpx-stage').getBoundingClientRect().width)")
        check("popup: no shift behind it on themes with a fixed scrollbar gutter", w0 == w1, (w0, w1))
        page.keyboard.press("Escape")
        w2 = page.evaluate("() => Math.round(document.querySelector('.afpx-stage').getBoundingClientRect().width)")
        check("popup: page width restored after closing", w2 == w0, (w0, w2))
        ctx.close()

        # ---------------------------------------------------------------- short laptop screen
        ctx = browser.new_context(viewport={"width": 1366, "height": 768})
        page = ctx.new_page()
        page.route("**/*", make_router(real_html, rest=rest_edge))
        page.goto("https://test.local/page")
        page.wait_for_function("() => document.querySelector('[data-stat=available]').textContent === '49'", timeout=8000)
        lap = page.evaluate("""() => ({b07: Math.round(document.querySelector('.afpx-stand[data-id="B07"] rect').getBoundingClientRect().width),
            vb: document.querySelector('.afpx-svg').getAttribute('viewBox')})""")
        check("laptop 1366x768: starts on the hall so stands are a usable size", lap["b07"] >= 28, lap)
        page.click(".afpx-zbtn[data-zoom='out']")
        page.click(".afpx-zbtn[data-zoom='out']")
        page.click(".afpx-zbtn[data-zoom='out']")
        wider = page.evaluate("() => parseFloat(document.querySelector('.afpx-svg').getAttribute('viewBox').split(' ')[3])")
        check("laptop 1366x768: zooming out still shows the whole drawing", wider >= 78.9, wider)
        ctx.close()

        # ---------------------------------------------------------------- tablet width (single column via container query)
        ctx = browser.new_context(viewport={"width": 820, "height": 1180}, has_touch=True)
        page = ctx.new_page()
        page.route("**/*", make_router(real_html, rest=rest_edge))
        page.goto("https://test.local/page")
        page.wait_for_function("() => document.querySelector('[data-stat=available]').textContent === '49'", timeout=8000)
        page.wait_for_timeout(300)
        fills = page.evaluate("""() => ['S01','S02','S03','G01'].map(id => getComputedStyle(document.querySelector('.afpx-stand[data-id="'+id+'"] rect')).fill)""")
        check("tablet: stand colours follow the latest data", fills == ["rgb(228, 218, 241)", "rgb(150, 162, 156)", "rgb(150, 162, 156)", "rgb(207, 230, 214)"], fills)
        cols = page.evaluate("() => getComputedStyle(document.querySelector('.afpx-list')).gridTemplateColumns.split(' ').length")
        check("tablet: stand list uses two columns under the plan", cols >= 2, cols)
        page.screenshot(path=str(SHOTS / "tablet.png"))
        dw = page.evaluate("() => [document.documentElement.scrollWidth, innerWidth]")
        check("tablet: no sideways scrolling", dw[0] <= dw[1], dw)
        ctx.close()

        # ---------------------------------------------------------------- staff bar (logged-in staff only)
        sheet = "https://docs.google.com/spreadsheets/d/1cYp8wew5Qvfds0_IjBaXA8mb6kuOHh4TmVZqsfbXWsI/edit#gid=885922016"
        staff_html = with_snapshot((OUT / "shortcode-staff.html").read_text(encoding="utf-8"), rest_real)
        for label, opts in (("staff desktop", dict(viewport={"width": 1366, "height": 900})),
                            ("staff phone", dict(viewport={"width": 375, "height": 667}, device_scale_factor=2, is_mobile=True, has_touch=True)),
                            ("staff small phone 320px", dict(viewport={"width": 320, "height": 568}, device_scale_factor=2, is_mobile=True, has_touch=True))):
            ctx = browser.new_context(**opts)
            page = ctx.new_page()
            page.on("pageerror", lambda e: console_errors.append(str(e)))
            page.route("**/*", make_router(staff_html, rest=rest_edge))
            page.goto("https://test.local/page")
            page.wait_for_function("() => document.querySelector('[data-stat=available]').textContent === '49'", timeout=8000)
            st = page.evaluate("""() => {
                const bar = document.querySelector('#atfa-expo27 .afpx-staff'), wrap = document.querySelector('#atfa-expo27 .afpx-wrap');
                const a = [...bar.querySelectorAll('a')];
                const r = bar.getBoundingClientRect();
                return { visible: r.height > 0 && getComputedStyle(bar).display !== 'none',
                  above: r.bottom <= wrap.getBoundingClientRect().top,
                  barH: Math.round(r.height), docW: document.documentElement.scrollWidth, vw: innerWidth,
                  links: a.map(x => ({ href: x.href, target: x.target, rel: x.rel, text: x.textContent.trim(),
                    h: Math.round(x.getBoundingClientRect().height), right: Math.round(x.getBoundingClientRect().right),
                    deco: getComputedStyle(x).textDecorationLine, color: getComputedStyle(x).color })) }; }""")
            sheet_a, refresh_a = st["links"][0], st["links"][1]
            check(label + ": staff bar shows above the plan", st["visible"] and st["above"], st)
            check(label + ": 'Open the stand sheet' opens the Master tab in a new tab",
                  sheet_a["href"] == sheet and sheet_a["target"] == "_blank" and "noopener" in sheet_a["rel"]
                  and sheet_a["text"].startswith("Open the stand sheet"), sheet_a)
            check(label + ": 'Refresh from the sheet' reloads this page with ?refresh=1",
                  refresh_a["href"] == "https://test.local/page?refresh=1", refresh_a)
            check(label + ": staff buttons keep their look under the theme's link styles",
                  all(x["deco"] == "none" for x in st["links"]) and sheet_a["color"] == "rgb(255, 255, 255)", st["links"])
            check(label + ": no sideways scrolling, buttons fit", st["docW"] <= st["vw"] and all(x["right"] <= st["vw"] for x in st["links"]), st)
            if opts.get("has_touch"):
                check(label + ": staff buttons big enough to tap (>= 40px)", all(x["h"] >= 40 for x in st["links"]), st["links"])
            else:
                check(label + ": staff bar is one slim row on a wide screen", st["barH"] <= 60, st["barH"])
            page.screenshot(path=str(SHOTS / (label.replace(" ", "-") + ".png")))
            ctx.close()

        browser.close()

    check("no JavaScript errors in any run", not console_errors, console_errors[:5])
    failed = [r for r in results if not r[1]]
    print("\n%d checks, %d failed" % (len(results), len(failed)))
    sys.exit(1 if failed else 0)


if __name__ == "__main__":
    main()
