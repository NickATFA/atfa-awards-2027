"""
Browser tests for the Expo page ([atfa_expo27_page]). Runs the real PHP output
(tests/out/page-*.html) inside the same kind of blank page the awards template prints,
with all network mocked. Desktop, tablet and phone sizes, touch, pinch, back button.

    AWARDS_DIR=... python3 tests/e2e_page.py
"""
import json
import os
import pathlib
import re
import sys

from playwright.sync_api import sync_playwright

ROOT = pathlib.Path(__file__).resolve().parents[1]
OUT = ROOT / "tests" / "out"
SHOTS = OUT / "page-shots"
SHOTS.mkdir(parents=True, exist_ok=True)
PHOTOS = pathlib.Path(os.environ.get("PHOTOS_DIR", "/home/claude/atfa-out/expo-photos"))
AWARDS = pathlib.Path(os.environ.get("AWARDS_DIR", ROOT.parent / "atfa-awards-2027"))
# A 1 x 1 picture stands in for any 2025 photo the test machine doesn't have.
import base64  # noqa: E402
PLACEHOLDER = base64.b64decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=")

results = []


def check(name, ok, detail=""):
    results.append((name, bool(ok), detail))
    print(("PASS " if ok else "FAIL ") + name + ("  -- " + str(detail) if detail and not ok else ""))


def shell(fragment, hostile=False):
    css = ""
    if hostile:
        css = ("<style>body{font-family:Georgia,serif;font-size:20px}button{background:#F79320;color:#000;padding:10px 20px;"
               "text-transform:uppercase;border:2px solid red;border-radius:0}h1,h2,h3{color:red;font-family:Georgia}"
               "a{color:#00f;text-decoration:underline}ul,ol{list-style:disc;padding-left:40px}li{margin:6px 0}p{margin:20px 0}</style>")
    return ("<!DOCTYPE html><html lang='en-AU'><head><meta charset='utf-8'>"
            "<meta name='viewport' content='width=device-width, initial-scale=1'><title>ATFA Expo 2027</title>"
            "<link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&amp;family=Montserrat:wght@400;500;600;700&amp;display=swap'>" + css +
            "</head><body class='atfa-awards-canvas'>" + fragment + "</body></html>")


FONT_PKG = pathlib.Path(os.environ.get("FONT_PKG", "/tmp/fontpkg"))
FONT_DIR = OUT / "fonts"
FONT_DIR.mkdir(parents=True, exist_ok=True)
FONT_CSS = ""
for fam, slug, weights in (("Barlow Condensed", "barlow-condensed", (500, 600, 700)), ("Montserrat", "montserrat", (400, 500, 600, 700))):
    for wt in weights:
        fname = "%s-latin-%d-normal.woff2" % (slug, wt)
        src = FONT_PKG / "node_modules" / "@fontsource" / slug / "files" / fname
        if src.exists():
            (FONT_DIR / fname).write_bytes(src.read_bytes())
            FONT_CSS += ("@font-face{font-family:'%s';font-style:normal;font-weight:%d;font-display:swap;"
                         "src:url(https://fonts.gstatic.com/local/%s) format('woff2')}" % (fam, wt, fname))

REST_REAL = json.loads((OUT / "rest-real.json").read_text())
ALL_AVAIL = {"ok": True, "source": "live", "updated": "2026-10-19T00:00:00+00:00",
             "stands": [{"id": s["id"], "status": "available"} for s in REST_REAL["stands"]]}


def router(html, rest=None, rest_status=200, static=None, csv=None):
    def handle(route):
        url = route.request.url
        if url.startswith("https://test.local/expo-2027/"):
            return route.fulfill(status=200, content_type="text/html; charset=utf-8", body=html)
        m = re.match(r"https://test.local/media/(atfa-expo-2025-[a-z]+)(?:-\d+x\d+)?\.jpg", url)
        if m:
            f = PHOTOS / (m.group(1) + ".jpg")
            if not f.exists():
                return route.fulfill(status=200, content_type="image/png", body=PLACEHOLDER)
            return route.fulfill(status=200, content_type="image/jpeg", body=f.read_bytes())
        m = re.match(r"https://test.local/awards-plugin/assets/img/([a-z0-9\-./]+?)(\?.*)?$", url)
        if m:
            f = AWARDS / "assets" / "img" / m.group(1)
            ctype = "image/svg+xml" if f.suffix == ".svg" else ("image/png" if f.suffix == ".png" else "image/jpeg")
            return route.fulfill(status=200, content_type=ctype, body=f.read_bytes())
        if "/wp-json/atfa/v1/expo27/stands" in url:
            if rest is None:
                return route.fulfill(status=rest_status or 500, content_type="application/json", body="{}")
            return route.fulfill(status=200, content_type="application/json", body=json.dumps(rest))
        if "/atfa-expo27/stands-v3.json" in url:
            if static is None:
                return route.fulfill(status=404, body="")
            return route.fulfill(status=200, content_type="application/json", body=json.dumps(static))
        if url.startswith("https://fonts.googleapis.com/"):
            return route.fulfill(status=200, content_type="text/css", body=FONT_CSS)
        m = re.match(r"https://fonts.gstatic.com/local/([a-z0-9\-]+\.woff2)$", url)
        if m:
            return route.fulfill(status=200, content_type="font/woff2", body=(FONT_DIR / m.group(1)).read_bytes())
        if "docs.google.com" in url:
            if csv is None:
                return route.fulfill(status=500, body="")
            return route.fulfill(status=200, content_type="text/csv", body=csv)
        return route.fulfill(status=404, body="")
    return handle


def statuses(page):
    return page.evaluate("() => Object.fromEntries(window.ATFAExpo27Page.stands().map(s => [s.id, s.status]))")


def overflow(page):
    return page.evaluate("() => document.documentElement.scrollWidth - window.innerWidth")


def stand_point(page, sid):
    """Centre of a stand on screen in the full-screen plan."""
    return page.evaluate("""(sid) => {
        const g = document.querySelector('[data-role="cam"] .xs[data-id="' + sid + '"] rect.st');
        const r = g.getBoundingClientRect(); return {x: r.left + r.width / 2, y: r.top + r.height / 2, w: r.width, h: r.height};
    }""", sid)


def main():
    visitor = (OUT / "page-visitor-real.html").read_text()
    staff = (OUT / "page-staff.html").read_text()
    cold = (OUT / "page-visitor_cold.html").read_text()
    with sync_playwright() as pw:
        browser = pw.chromium.launch()

        # ---------------- Desktop ----------------
        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        errors = []
        page.on("pageerror", lambda e: errors.append(str(e)))
        page.on("console", lambda m: errors.append(m.text) if m.type == "error" and "favicon" not in m.text else None)
        page.route("**/*", router(shell(visitor), rest=REST_REAL, static=REST_REAL))
        page.goto("https://test.local/expo-2027/")
        page.wait_for_function("() => window.ATFAExpo27Page && window.ATFAExpo27Page.stands()[0].status !== 'loading'")
        page.wait_for_timeout(600)
        st = statuses(page)
        check("desktop: live data painted (2 sold, 5 on hold, 51 available)",
              list(st.values()).count("sold") == 2 and list(st.values()).count("hold") == 5 and list(st.values()).count("available") == 51, st)
        stats = page.evaluate("() => ['available','hold','sold'].map(k => document.querySelector('[data-stat=\"' + k + '\"]').textContent)")
        check("desktop: side panel counts 51 / 5 / 2", stats == ["51", "5", "2"], stats)
        check("desktop: status line shows days to go and availability",
              re.search(r"days to go · 51 of 58 stands available", page.inner_text("[data-role='daysnote']")) is not None,
              page.inner_text("[data-role='daysnote']"))
        imgs = page.evaluate("() => Array.from(document.querySelectorAll('.xp-bg img, .xp-gal img')).map(i => i.complete && i.naturalWidth > 0)")
        check("desktop: hero and gallery photos load (4 + 6)", len(imgs) == 10 and all(imgs), imgs)
        check("desktop: awards logo and wordmark load", page.evaluate(
            "() => Array.from(document.querySelectorAll('.xp-head .logo img, .xp-h1 .wm')).every(i => i.complete && i.naturalWidth > 0)"))
        check("desktop: no sideways scrolling", overflow(page) <= 0, overflow(page))
        check("desktop: staff bar and sheet link not in the visitor page",
              'class="afpx-staff"' not in visitor and "1cYp8wew5Qvfds0" not in visitor)
        page.screenshot(path=str(SHOTS / "desktop-top.png"))
        plan = page.locator("#floor-plan")
        plan.scroll_into_view_if_needed()
        page.wait_for_timeout(400)
        # Hover tooltip
        box = page.locator(".xp-map svg .xs[data-id='O01'] rect.st").bounding_box()
        page.mouse.move(box["x"] + box["width"] / 2, box["y"] + box["height"] / 2)
        page.wait_for_timeout(250)
        check("desktop: hover shows a tooltip with the stand", page.locator(".xp-tip.on").count() == 1 and "Stand O01" in page.inner_text(".xp-tip"))
        page.mouse.click(box["x"] + box["width"] / 2, box["y"] + box["height"] / 2)
        page.wait_for_timeout(250)
        det = page.inner_text("[data-role='details']")
        href = page.get_attribute("[data-role='details'] a.btn-primary", "href")
        check("desktop: choosing a stand shows its price and enquiry link",
              "O01" in det and "$2,500" in det and href and href.endswith("?stand=O01"), (det[:120], href))
        check("desktop: open sides diagram has a spoken description",
              page.get_attribute("[data-role='details'] .xp-sides", "aria-label") == "Open to the aisle on the north and east sides",
              page.get_attribute("[data-role='details'] .xp-sides", "aria-label"))
        opn = page.evaluate("() => Object.fromEntries(window.ATFAExpo27Page.stands().map(s => [s.id, s.open]))")
        check("desktop: open sides count rear walls (G02 backs onto a wall: 1 side; G01 2; S01 3; O03 3; B01 2; B06 1)",
              [opn[k] for k in ("G02", "G01", "S01", "O03", "B01", "B06")] == [1, 2, 3, 3, 2, 1], opn)
        page.screenshot(path=str(SHOTS / "desktop-plan-selected.png"), clip={"x": 0, "y": max(0, box["y"] - 420), "width": 1440, "height": 900})
        # Sold and hold stands
        box = page.locator(".xp-map svg .xs[data-id='S01'] rect.st").bounding_box()
        page.mouse.click(box["x"] + box["width"] / 2, box["y"] + box["height"] / 2)
        page.wait_for_timeout(200)
        det = page.inner_text("[data-role='details']")
        check("desktop: sold stand shows the exhibitor, bio and website",
              "BONA AUSTRALIA" in det.upper() and "Floor care" in det and page.locator("[data-role='details'] a[href='https://www.bona.com/']").count() == 1, det[:200])
        box = page.locator(".xp-map svg .xs[data-id='B02'] rect.st").bounding_box()
        page.mouse.click(box["x"] + box["width"] / 2, box["y"] + box["height"] / 2)
        page.wait_for_timeout(200)
        det = page.inner_text("[data-role='details']")
        check("desktop: stand on hold says so, with no name and an Ask button", "ON HOLD" in det.upper() and "Ask about stand B02" in det, det[:200])
        page.click("[data-role='details'] [data-act='clear']")
        check("desktop: Close clears the stand", page.inner_text("[data-role='details']").strip() == "" and page.is_visible("[data-role='sidedefault']"))
        # Chips
        page.click("[data-role='chips'] .xp-chip[data-size='6x6']")
        dims = page.evaluate("() => document.querySelectorAll('.xp-map svg .xs.is-dim').length")
        check("desktop: 6 x 6 chip dims the other 54 stands", dims == 54, dims)
        page.click("[data-role='chips'] .xp-chip[data-size='6x6']")
        page.click("[data-role='chips'] .xp-chip[data-avail]")
        dims = page.evaluate("() => document.querySelectorAll('.xp-map svg .xs.is-dim').length")
        check("desktop: Available only dims the 7 sold or held stands", dims == 7, dims)
        page.click("[data-role='chips'] .xp-chip[data-avail]")
        # Keyboard
        page.focus(".xp-map svg .xs[tabindex='0']")
        first = page.evaluate("() => document.activeElement.getAttribute('data-id')")
        page.keyboard.press("ArrowRight")
        second = page.evaluate("() => document.activeElement.getAttribute('data-id')")
        page.keyboard.press("Enter")
        page.wait_for_timeout(150)
        check("desktop: arrow keys move between stands and Enter chooses one",
              first != second and second in page.inner_text("[data-role='details']"), (first, second))
        # Full screen on desktop, Escape closes
        page.click(".xp-fs")
        page.wait_for_timeout(500)
        opened = page.evaluate("() => window.ATFAExpo27Page.view().open")
        sx = page.evaluate("() => window.ATFAExpo27Page.view().sel")
        if sx:
            page.focus("[data-role='exsin'] [data-act='clear']")
            page.keyboard.press("Enter")
            page.wait_for_timeout(400)
        check("desktop: closing the stand card from the keyboard keeps focus in the plan",
              page.evaluate("() => document.querySelector('[data-role=\"ex\"]').contains(document.activeElement)"))
        page.keyboard.press("Escape")
        page.wait_for_timeout(600)
        check("desktop: Full screen opens the plan and Escape closes it", opened and not page.evaluate("() => window.ATFAExpo27Page.view().open"))
        check("desktop: no script errors", not errors, errors[:5])
        ctx.close()

        # Staff view
        ctx = browser.new_context(viewport={"width": 1280, "height": 900})
        page = ctx.new_page()
        page.route("**/*", router(shell(staff), rest=REST_REAL, static=REST_REAL))
        page.goto("https://test.local/expo-2027/")
        page.wait_for_timeout(500)
        check("staff: bar with the sheet link sits above the plan", page.locator("#floor-plan .afpx-staff").count() == 1
              and page.locator(".afpx-staff a[href*='1cYp8wew5Qvfds0']").count() == 1)
        page.locator("#floor-plan").scroll_into_view_if_needed()
        page.screenshot(path=str(SHOTS / "staff-bar.png"))
        ctx.close()

        # Staff: share links for the private page
        ctx = browser.new_context(viewport={"width": 1280, "height": 900})
        ctx.grant_permissions(["clipboard-read", "clipboard-write"], origin="https://test.local")
        page = ctx.new_page()
        errors = []
        page.on("pageerror", lambda e: errors.append(str(e)))
        store = {"shares": [], "posted": [], "nonce": [], "deleted": []}

        def share_api(route):
            req = route.request
            store["nonce"].append(req.headers.get("x-wp-nonce"))
            if req.method == "POST":
                body = json.loads(req.post_data or "{}")
                store["posted"].append(body)
                sh = {"key": "K" * 32, "url": "https://test.local/expo-2027/?xp-share=" + "K" * 32, "expires": 1, "until": "Tue 21 Oct at 3:15 pm", "note": body.get("note", "")}
                store["shares"] = [sh]
                return route.fulfill(status=200, content_type="application/json", body=json.dumps(sh))
            if req.method == "DELETE":
                store["deleted"].append(req.url)
                store["shares"] = []
                return route.fulfill(status=200, content_type="application/json", body=json.dumps({"stopped": True}))
            return route.fulfill(status=200, content_type="application/json", body=json.dumps({"shares": store["shares"]}))

        page.route("**/*", router(shell(staff), rest=REST_REAL, static=REST_REAL))
        page.route("**/wp-json/atfa/v1/expo27/shares**", share_api)
        page.goto("https://test.local/expo-2027/")
        page.wait_for_timeout(500)
        page.locator("[data-role='share'] summary").scroll_into_view_if_needed()
        page.click("[data-role='share'] summary")
        page.wait_for_timeout(300)
        check("staff share: opening the tools lists the live links (none yet)", "No live links" in page.inner_text("[data-role='sharelist']"))
        page.fill("[data-role='sharenote']", "Sponsor Co")
        page.click("[data-role='sharemake']")
        page.wait_for_timeout(500)
        link = page.input_value("[data-role='sharelist'] input")
        check("staff share: Make a link asks for 24 hours with the note, with the staff nonce",
              store["posted"] == [{"hours": 24, "note": "Sponsor Co"}] and all(n and n.startswith("n0nce") for n in store["nonce"]), store)
        check("staff share: the new link is shown, ready to copy, with when it runs out",
              link.endswith("?xp-share=" + "K" * 32) and "Sponsor Co" in page.inner_text("[data-role='sharelist']")
              and "works until Tue 21 Oct at 3:15 pm" in page.inner_text("[data-role='sharelist']"), (link, page.inner_text("[data-role='sharelist']")))
        page.click("[data-role='sharelist'] button:has-text('Copy link')")
        page.wait_for_timeout(300)
        clip = page.evaluate("() => navigator.clipboard.readText()")
        check("staff share: Copy link copies it", clip == link and "Copied" in page.inner_text("[data-role='sharelist']"), clip)
        page.screenshot(path=str(SHOTS / "staff-share.png"))
        page.click("[data-role='sharelist'] button:has-text('Stop')")
        page.wait_for_timeout(500)
        check("staff share: Stop stops it", store["deleted"] and store["deleted"][0].endswith("/shares/" + "K" * 32)
              and "No live links" in page.inner_text("[data-role='sharelist']"), store["deleted"])
        check("staff share: no script errors", not errors, errors[:3])
        ctx.close()

        # ---------------- Phones ----------------
        for (w, h, name) in ((390, 844, "iphone"), (430, 932, "big"), (360, 780, "small")):
            ctx = browser.new_context(viewport={"width": w, "height": h}, device_scale_factor=2, is_mobile=True, has_touch=True,
                                      user_agent="Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1")
            page = ctx.new_page()
            errors = []
            page.on("pageerror", lambda e: errors.append(str(e)))
            page.route("**/*", router(shell(visitor), rest=ALL_AVAIL if name != "small" else REST_REAL, static=None))
            page.goto("https://test.local/expo-2027/")
            page.wait_for_function("() => window.ATFAExpo27Page && window.ATFAExpo27Page.stands()[0].status !== 'loading'")
            page.wait_for_timeout(900)
            check(name + ": no sideways scrolling", overflow(page) <= 0, overflow(page))
            cols = page.evaluate("() => getComputedStyle(document.querySelector('.xp-wk-links')).gridTemplateColumns.split(' ').length")
            check(name + ": weekend tiles in three columns", cols == 3, cols)
            page.screenshot(path=str(SHOTS / (name + "-top.png")))
            page.mouse.wheel(0, 2400)
            page.wait_for_timeout(400)
            top = page.evaluate("() => document.querySelector('.xp-head-bar').getBoundingClientRect().top")
            check(name + ": header stays at the top while scrolling", abs(top) < 2, top)
            page.locator("#floor-plan").scroll_into_view_if_needed()
            page.wait_for_timeout(500)
            page.screenshot(path=str(SHOTS / (name + "-plan-card.png")))
            hist = page.evaluate("() => history.length")
            page.tap(".xp-ctas .btn-primary") if page.is_visible(".xp-ctas .btn-primary") else page.tap(".xp-open")
            page.wait_for_timeout(700)
            v = page.evaluate("() => window.ATFAExpo27Page.view()")
            locked = page.evaluate("() => getComputedStyle(document.body).overflow")
            check(name + ": Pick your stand opens the full-screen plan, page scroll locked",
                  v["open"] and locked == "hidden" and page.evaluate("() => history.length") == hist + 1, (v, locked))
            page.screenshot(path=str(SHOTS / (name + "-plan-whole.png")))
            check(name + ": nothing covers the plan until a stand is chosen", v["bottom"] == 0 and page.is_hidden("[data-role='exsheet']")
                  and page.is_visible("[data-role='exhint']"), v)
            # Tap a stand
            p = stand_point(page, "S04")
            page.touchscreen.tap(p["x"], p["y"])
            page.wait_for_timeout(900)
            v2 = page.evaluate("() => window.ATFAExpo27Page.view()")
            sheet = page.inner_text("[data-role='exsin']")
            p2 = stand_point(page, "S04")
            in_band = v2["top"] < p2["y"] < page.viewport_size["height"] - v2["bottom"]
            check(name + ": tapping S04 zooms in on it above the card", v2["sel"] == "S04" and v2["px"] > v["px"] * 1.6 and in_band,
                  (v2, p2))
            check(name + ": the hint goes once the plan has been touched", page.evaluate(
                "() => document.querySelector('[data-role=\"ex\"]').classList.contains('is-used')"))
            check(name + ": the stand card is compact (no more than 210 px)", 0 < v2["bottom"] <= 210, v2["bottom"])
            if name == "small":
                check(name + ": sold stand card shows the exhibitor", "TARKETT" in sheet.upper(), sheet[:120])
            else:
                go = page.locator("[data-role='exsin'] a.btn-primary")
                check(name + ": card shows S04, price and Enquire", "S04" in sheet and "$4,200" in sheet and "Enquire" in sheet
                      and go.get_attribute("aria-label") == "Enquire about stand S04", sheet[:160])
                check(name + ": enquiry link carries the stand", go.get_attribute("href").endswith("?stand=S04"))
                hd = page.evaluate("""() => { const h = document.querySelector('[data-role="exsin"] .xp-card-id');
                    const l = h && h.querySelector('.xp-idl');
                    return h ? {text: h.textContent, letter: l ? l.textContent : null, font: l ? getComputedStyle(l).fontFamily : null} : null; }""")
                check(name + ": big stand ID sets its letter apart and still reads S04",
                      bool(hd) and hd["text"] == "S04" and hd["letter"] == "S" and "Montserrat" in (hd["font"] or ""), hd)
            check(name + ": tap target size of a 3 x 3 stand when zoomed >= 44px",
                  stand_point(page, "B30")["w"] >= 44, stand_point(page, "B30"))
            page.screenshot(path=str(SHOTS / (name + "-plan-stand.png")))
            page.tap("[data-role='exsin'] [data-act='more']")
            page.wait_for_timeout(700)
            vm = page.evaluate("() => window.ATFAExpo27Page.view()")
            pm = stand_point(page, "S04")
            check(name + ": More shows the details and keeps the stand in view", vm["more"] and page.is_visible("[data-role='exsin'] .xp-card-detail")
                  and vm["top"] < pm["y"] < page.viewport_size["height"] - vm["bottom"], (vm, pm))
            page.tap("[data-role='exsin'] [data-act='go'][aria-label^='Next']")
            page.wait_for_timeout(800)
            check(name + ": Next goes round to B01", page.evaluate("() => window.ATFAExpo27Page.view().sel") == "B01")
            page.tap("[data-role='exsin'] [data-act='more']")
            page.wait_for_timeout(600)
            check(name + ": Less folds the details away again", not page.evaluate("() => window.ATFAExpo27Page.view().more"))
            page.tap("[data-role='exwhole']")
            page.wait_for_timeout(800)
            v3 = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": Whole hall zooms back out and lets the stand go", v3["sel"] is None and abs(v3["px"] - v3["fit"]) < 0.05
                  and v3["bottom"] == 0, v3)
            # A single tap on open floor does nothing; a double tap zooms in there
            # (24, 36) is open floor at least 3 m from every stand, between the O13 block and O17
            route = page.evaluate("""() => { const v = window.ATFAExpo27Page.view(); const px = v.px;
                return {x: (24 - v.x) * px, y: (36 - v.y) * px}; }""")
            page.touchscreen.tap(route["x"], route["y"])
            page.wait_for_timeout(500)
            v4 = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": a single tap on open floor leaves the plan alone", abs(v4["px"] - v3["px"]) < 0.01 and v4["sel"] is None, v4)
            page.wait_for_timeout(200)
            page.touchscreen.tap(route["x"], route["y"])
            page.wait_for_timeout(120)
            page.touchscreen.tap(route["x"], route["y"])
            page.wait_for_timeout(700)
            v5 = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": double tap zooms in there", v5["px"] > v3["px"] * 1.8 and v5["sel"] is None, (v3, v5))
            # Zoom buttons
            page.tap("[data-role='exwhole']")
            page.wait_for_timeout(700)
            z0 = page.evaluate("() => window.ATFAExpo27Page.view().px")
            out_disabled = page.evaluate("() => document.querySelector('[data-role=\"exout\"]').getAttribute('aria-disabled') === 'true'")
            page.tap("[data-role='exin']")
            page.wait_for_timeout(700)
            z1 = page.evaluate("() => window.ATFAExpo27Page.view().px")
            page.tap("[data-role='exout']")
            page.wait_for_timeout(700)
            z2 = page.evaluate("() => window.ATFAExpo27Page.view().px")
            check(name + ": + and - buttons zoom in and out (- greyed at the whole hall)", out_disabled and z1 > z0 * 1.6 and abs(z2 - z0) < 0.05, (z0, z1, z2))
            # Pinch with two fingers (Chrome DevTools touch events)
            cdp = ctx.new_cdp_session(page)
            cx, cy = w / 2, 420
            def touch(kind, pts):
                cdp.send("Input.dispatchTouchEvent", {"type": kind, "touchPoints": [{"x": x, "y": y, "id": i} for i, (x, y) in enumerate(pts)]})
            before = page.evaluate("() => window.ATFAExpo27Page.view().px")
            touch("touchStart", [(cx - 40, cy), (cx + 40, cy)])
            for k in range(1, 9):
                touch("touchMove", [(cx - 40 - k * 12, cy), (cx + 40 + k * 12, cy)])
                page.wait_for_timeout(16)
            touch("touchEnd", [])
            page.wait_for_timeout(300)
            after = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": pinch zooms in", after["px"] > before * 1.8 and after["sel"] is None, (before, after))
            # One-finger drag pans by moving the drawing (not redrawing it), and keeps sliding after a flick
            vx = after["x"]
            vb0 = page.get_attribute("[data-role='cam'] svg", "viewBox")
            touch("touchStart", [(cx, cy)])
            for k in range(1, 8):
                touch("touchMove", [(cx - k * 20, cy)])
                page.wait_for_timeout(16)
            mid = page.evaluate("() => ({vb: document.querySelector('[data-role=\"cam\"] svg').getAttribute('viewBox'), tf: document.querySelector('[data-role=\"cam\"]').style.transform, x: window.ATFAExpo27Page.view().x})")
            touch("touchEnd", [])
            page.wait_for_timeout(30)
            x_release = page.evaluate("() => window.ATFAExpo27Page.view().x")
            page.wait_for_timeout(900)
            after2 = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": dragging moves the plan", after2["x"] > vx + 2, (vx, after2["x"]))
            check(name + ": while dragging the drawing is moved, not redrawn", mid["vb"] == vb0 and "translate" in mid["tf"], mid)
            check(name + ": after a flick the plan keeps sliding, then settles sharp", after2["x"] > x_release + 0.3
                  and page.evaluate("() => document.querySelector('[data-role=\"cam\"]').style.transform") == "", (x_release, after2["x"]))
            if name == "iphone":
                cam_tf = "() => document.querySelector('[data-role=\"cam\"]').style.transform"
                # A touch that stops a flick only stops it
                page.tap("[data-role='exwhole']")
                page.wait_for_timeout(700)
                page.tap("[data-role='exin']")
                page.wait_for_timeout(700)
                page.tap("[data-role='exin']")
                page.wait_for_timeout(700)
                touch("touchStart", [(cx, cy)])
                for k in range(1, 4):
                    touch("touchMove", [(cx - k * 24, cy)])
                    page.wait_for_timeout(16)
                touch("touchEnd", [])
                page.wait_for_timeout(40)
                sliding = page.evaluate("() => window.ATFAExpo27Page.view().sliding")
                touch("touchStart", [(cx, cy)])
                touch("touchEnd", [])
                page.wait_for_timeout(500)
                vf = page.evaluate("() => window.ATFAExpo27Page.view()")
                check(name + ": a tap that stops a flick doesn't pick a stand", sliding and vf["sel"] is None and page.evaluate(cam_tf) == "", (sliding, vf))
                # Tap a stand, then tap again while the plan is still zooming in: the plan is left sharp,
                # not stretched; then a tap on open floor closes the card
                page.tap("[data-role='exwhole']")
                page.wait_for_timeout(700)
                ps = stand_point(page, "S04")
                page.touchscreen.tap(ps["x"], ps["y"])
                page.wait_for_timeout(150)
                rt = page.evaluate("""() => { const v = window.ATFAExpo27Page.view(); return {x: (24 - v.x) * v.px, y: (36 - v.y) * v.px}; }""")
                page.touchscreen.tap(rt["x"], rt["y"])
                page.wait_for_timeout(700)
                check(name + ": a tap while the plan is zooming in leaves it sharp", page.evaluate(cam_tf) == "", page.evaluate(cam_tf))
                if page.evaluate("() => window.ATFAExpo27Page.view().sel") != "S04":
                    page.evaluate("() => window.ATFAExpo27Page.open('S04')")
                    page.wait_for_timeout(900)
                # (41, 36) is open floor 2 m east of S04, between it and the demo zone
                rt = page.evaluate("""() => { const v = window.ATFAExpo27Page.view(); return {x: (41 - v.x) * v.px, y: (36 - v.y) * v.px}; }""")
                page.touchscreen.tap(rt["x"], rt["y"])
                page.wait_for_timeout(700)
                vb = page.evaluate("() => window.ATFAExpo27Page.view()")
                check(name + ": closing the card by tapping the floor leaves the plan sharp", vb["sel"] is None and vb["bottom"] == 0
                      and page.evaluate(cam_tf) == "", (vb, page.evaluate(cam_tf)))
                # Pinch out past the whole hall with a card open, close the card, then tap a stand: it's chosen
                page.evaluate("() => window.ATFAExpo27Page.open('B08')")
                page.wait_for_timeout(900)
                touch("touchStart", [(cx - 120, cy - 60), (cx + 120, cy - 60)])
                for k in range(1, 10):
                    touch("touchMove", [(cx - 120 + k * 12, cy - 60), (cx + 120 - k * 12, cy - 60)])
                    page.wait_for_timeout(16)
                touch("touchEnd", [])
                page.wait_for_timeout(600)
                page.tap("[data-role='exsin'] [data-act='clear']")
                page.wait_for_timeout(700)
                pb = stand_point(page, "S02")
                page.touchscreen.tap(pb["x"], pb["y"])
                page.wait_for_timeout(800)
                check(name + ": after zooming right out and closing the card, the next tap still picks a stand",
                      page.evaluate("() => window.ATFAExpo27Page.view().sel") == "S02")
                # The card follows a drag on its handle, and a drag down puts it away
                hb = page.locator("[data-role='exgrab']").bounding_box()
                gx, gy = hb["x"] + hb["width"] / 2, hb["y"] + hb["height"] / 2
                touch("touchStart", [(gx, gy)])
                for k in range(1, 6):
                    touch("touchMove", [(gx, gy + k * 14)])
                    page.wait_for_timeout(16)
                moved = page.evaluate("() => getComputedStyle(document.querySelector('[data-role=\"exsheet\"]')).transform")
                touch("touchEnd", [])
                page.wait_for_timeout(600)
                check(name + ": the card follows the handle and a drag down puts it away", moved not in ("none", "") and
                      page.evaluate("() => window.ATFAExpo27Page.view().sel") is None, moved)
            # List view
            page.tap("[data-role='exlist']")
            page.wait_for_timeout(500)
            rows = page.locator("[data-role='exsin'] .xp-row").count()
            check(name + ": List shows every stand", rows == 58, rows)
            page.screenshot(path=str(SHOTS / (name + "-plan-list.png")))
            idl = page.evaluate("""() => { const r = document.querySelector('[data-role="exsin"] .xp-row[data-id="O01"] .id');
                const l = r && r.querySelector('.xp-idl'); const b = r && r.getBoundingClientRect();
                return r ? {text: r.textContent, letter: l ? l.textContent : null, font: l ? getComputedStyle(l).fontFamily : null,
                            fits: r.scrollWidth <= Math.ceil(b.width)} : null; }""")
            check(name + ": list sets the series letter apart, so O01 doesn't read as 001",
                  bool(idl) and idl["text"] == "O01" and idl["letter"] == "O" and "Montserrat" in (idl["font"] or "") and idl["fits"], idl)
            page.locator("[data-role='exsin'] .xp-row[data-id='B08']").tap()
            page.wait_for_timeout(900)
            check(name + ": choosing from the list shows it on the plan",
                  page.evaluate("() => window.ATFAExpo27Page.view().sel") == "B08" and "B08" in page.inner_text("[data-role='exsin']"))
            # Size chip
            page.tap("[data-role='exchips'] .xp-chip[data-size='3x6']")
            page.wait_for_timeout(700)
            sub = page.inner_text("[data-role='exsub']")
            check(name + ": 3 x 6 chip filters and lets B08 go", "of 4 available" in sub and page.evaluate("() => window.ATFAExpo27Page.view().sel") is None, sub)
            page.tap("[data-role='exchips'] .xp-chip[data-size='']")
            # Back button closes
            page.go_back()
            page.wait_for_timeout(600)
            check(name + ": phone back button closes the plan and unlocks the page",
                  not page.evaluate("() => window.ATFAExpo27Page.view().open") and page.evaluate("() => getComputedStyle(document.body).overflow") != "hidden"
                  and page.url.startswith("https://test.local/expo-2027/"))
            # Close button
            page.tap(".xp-open")
            page.wait_for_timeout(500)
            page.tap("[data-role='exclose']")
            page.wait_for_timeout(700)
            check(name + ": X closes the plan", not page.evaluate("() => window.ATFAExpo27Page.view().open"))
            for sel, label in (("#weekend", "weekend"), (".xp-prices", "prices"), (".xp-hall", "hall"), (".xp-steps", "steps"), (".xp-dn", "dinner")):
                page.locator(sel).scroll_into_view_if_needed()
                page.wait_for_timeout(500)
                page.screenshot(path=str(SHOTS / (name + "-" + label + ".png")))
            check(name + ": no sideways scrolling after all that", overflow(page) <= 0, overflow(page))
            check(name + ": no script errors", not errors, errors[:5])
            ctx.close()

        # Real phone screens: with Safari's toolbars there is far less height than the phone's size
        for (w, h, name) in ((390, 664, "safari-14"), (375, 553, "safari-se")):
            ctx = browser.new_context(viewport={"width": w, "height": h}, device_scale_factor=2, is_mobile=True, has_touch=True)
            page = ctx.new_page()
            errors = []
            page.on("pageerror", lambda e: errors.append(str(e)))
            page.route("**/*", router(shell(visitor), rest=REST_REAL))
            page.goto("https://test.local/expo-2027/")
            page.wait_for_function("() => window.ATFAExpo27Page && window.ATFAExpo27Page.stands()[0].status !== 'loading'")
            page.wait_for_timeout(700)
            page.locator("#floor-plan").scroll_into_view_if_needed()
            page.wait_for_timeout(400)
            y_before = page.evaluate("() => window.scrollY")
            page.tap(".xp-open")
            page.wait_for_timeout(800)
            v = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": the plan gets the screen under the header", h - v["top"] >= h * 0.75 and v["bottom"] == 0, v)
            paused = page.evaluate("() => getComputedStyle(document.querySelector('.xp-bg img')).animationPlayState")
            check(name + ": the page behind is paused while the plan is open", paused == "paused", paused)
            page.screenshot(path=str(SHOTS / (name + "-open.png")))
            for sid, kind in (("B08", "available"), ("S01", "sold"), ("B02", "on hold")):
                page.evaluate("(id) => window.ATFAExpo27Page.open(id)", sid)
                page.wait_for_timeout(900)
                vs = page.evaluate("() => window.ATFAExpo27Page.view()")
                r = page.evaluate("""(id) => { const g = document.querySelector('[data-role="cam"] .xs[data-id="' + id + '"] rect.st').getBoundingClientRect();
                    return {top: g.top, bottom: g.bottom, left: g.left, right: g.right}; }""", sid)
                band = h - vs["top"] - vs["bottom"]
                inside = r["top"] >= vs["top"] - 1 and r["bottom"] <= h - vs["bottom"] + 1 and r["left"] >= -1 and r["right"] <= w + 1
                check(name + ": " + kind + " stand " + sid + " sits in full view above a compact card",
                      vs["sel"] == sid and band >= 230 and vs["bottom"] <= 210 and inside, (band, vs["bottom"], r))
                page.screenshot(path=str(SHOTS / (name + "-" + sid + ".png")))
            check(name + ": no sideways scrolling in the plan", page.evaluate("() => document.querySelector('[data-role=\"ex\"]').scrollWidth <= innerWidth"))
            check(name + ": with a card open, the whole-hall button is always there", page.is_visible("[data-role='exwhole']"))
            # A stand low on the screen, where the card is about to appear: the tap picks it and
            # nothing else (the card sliding in under the finger mustn't get the tap too).
            page.tap("[data-role='exwhole']")
            page.wait_for_timeout(800)
            url0 = page.url
            pl = stand_point(page, "B30")
            vw0 = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": B30 sits where the card will appear", pl["y"] > h - 215, (pl, vw0))
            page.touchscreen.tap(pl["x"], pl["y"])
            page.wait_for_timeout(900)
            check(name + ": tapping a stand under where the card appears picks it and doesn't follow the card's links",
                  page.url == url0 and page.evaluate("() => window.ATFAExpo27Page.view().sel") == "B30", (page.url, page.evaluate("() => window.ATFAExpo27Page.view()")))
            before = page.evaluate("() => window.ATFAExpo27Page.view()")
            pn = stand_point(page, "B31")
            page.touchscreen.tap(pn["x"], pn["y"])
            page.wait_for_timeout(800)
            after = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": choosing the stand next door doesn't move the plan",
                  after["sel"] == "B31" and abs(after["x"] - before["x"]) < 0.01 and abs(after["y"] - before["y"]) < 0.01 and abs(after["px"] - before["px"]) < 0.01,
                  (before, after))
            page.tap("[data-role='exsin'] [data-act='more']")
            page.wait_for_timeout(800)
            vm = page.evaluate("() => window.ATFAExpo27Page.view()")
            pm = page.evaluate("""() => { const g = document.querySelector('[data-role="cam"] .xs[data-id="B31"] rect.st').getBoundingClientRect();
                return {top: g.top, bottom: g.bottom}; }""")
            check(name + ": More slides the plan just enough to keep B31 in view", vm["more"] and pm["top"] >= vm["top"] - 1 and pm["bottom"] <= h - vm["bottom"] + 1, (vm, pm))
            page.tap("[data-role='exsin'] [data-act='more']")
            page.wait_for_timeout(700)
            # Turning the phone at the whole hall shows the whole hall again, sized to the screen
            page.tap("[data-role='exwhole']")
            page.wait_for_timeout(800)
            page.set_viewport_size({"width": h + 40, "height": w - 30})
            page.wait_for_timeout(900)
            vr = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": turned sideways at the whole hall, the whole hall fits again", abs(vr["px"] - vr["fit"]) < vr["fit"] * 0.02, vr)
            page.set_viewport_size({"width": w, "height": h})
            page.wait_for_timeout(900)
            vr = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": and turned back, it fits again", abs(vr["px"] - vr["fit"]) < vr["fit"] * 0.02, vr)
            # A double tap on a stand where the card is about to appear: the second tap lands on the
            # card that the first one brought in, and mustn't press anything on it.
            pd = stand_point(page, "B30")
            url0 = page.url
            page.touchscreen.tap(pd["x"], pd["y"])
            page.wait_for_timeout(110)
            under = page.evaluate("([x, y]) => { const n = document.elementFromPoint(x, y); return n && n.closest('[data-role=\"exsheet\"]') ? 'card' : 'map'; }", [pd["x"], pd["y"]])
            page.touchscreen.tap(pd["x"], pd["y"])
            page.wait_for_timeout(900)
            vd = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": a double tap on a stand where the card appears picks it and presses nothing on the card",
                  page.url == url0 and vd["sel"] == "B30" and not vd["more"], (page.url, under, vd))
            page.tap("[data-role='exwhole']")
            page.wait_for_timeout(800)
            page.tap("[data-role='exclose']")
            page.wait_for_timeout(800)
            y_after = page.evaluate("() => window.scrollY")
            running = page.evaluate("() => getComputedStyle(document.querySelector('.xp-bg img')).animationPlayState")
            check(name + ": closing returns to the same place on the page", abs(y_after - y_before) <= 2 and "running" in running and "paused" not in running, (y_before, y_after, running))
            page.locator("#floor-plan").scroll_into_view_if_needed()
            page.wait_for_timeout(300)
            pp = page.evaluate("""() => { const r = document.querySelector('[data-role="map"] .xs[data-id="S03"] rect.st').getBoundingClientRect();
                return {x: r.left + r.width / 2, y: r.top + r.height / 2}; }""")
            page.touchscreen.tap(pp["x"], pp["y"])
            page.wait_for_timeout(900)
            vp = page.evaluate("() => window.ATFAExpo27Page.view()")
            check(name + ": tapping a stand in the page's picture of the plan opens the plan on that stand", vp["open"] and vp["sel"] == "S03", vp)
            page.tap("[data-role='exclose']")
            page.wait_for_timeout(700)
            check(name + ": no script errors", not errors, errors[:5])
            ctx.close()

        # Phone turned sideways: the card sits beside the plan
        ctx = browser.new_context(viewport={"width": 740, "height": 340}, device_scale_factor=2, is_mobile=True, has_touch=True)
        page = ctx.new_page()
        page.route("**/*", router(shell(visitor), rest=REST_REAL))
        page.goto("https://test.local/expo-2027/")
        page.wait_for_timeout(800)
        page.evaluate("() => window.ATFAExpo27Page.open('B08')")
        page.wait_for_timeout(900)
        vl = page.evaluate("() => window.ATFAExpo27Page.view()")
        rl = page.evaluate("""() => { const g = document.querySelector('[data-role="cam"] .xs[data-id="B08"] rect.st').getBoundingClientRect();
            return {left: g.left, right: g.right, top: g.top, bottom: g.bottom}; }""")
        check("landscape: card beside the plan, stand in view to its left", vl["right"] > 200 and vl["bottom"] == 0
              and rl["right"] <= 740 - vl["right"] + 1 and rl["top"] >= vl["top"] - 1 and rl["bottom"] <= 341, (vl, rl))
        page.screenshot(path=str(SHOTS / "landscape-stand.png"))
        # Sideways, the card comes in on the right: a double tap on a stand there picks the stand
        # and presses nothing on the card that has just appeared under the finger.
        page.tap("[data-role='exwhole']")
        page.wait_for_timeout(800)
        cand = page.evaluate("""() => [...document.querySelectorAll('[data-role="cam"] .xs')].map(g => {
            const r = g.querySelector('rect.st').getBoundingClientRect();
            const x = r.left + r.width / 2, y = r.top + r.height / 2, n = document.elementFromPoint(x, y);
            const hit = n && n.closest('.xs');
            return {id: g.getAttribute('data-id'), x: x, y: y, ok: !!hit && hit.getAttribute('data-id') === g.getAttribute('data-id')};
          }).filter(c => c.ok).sort((a, b) => b.x - a.x)[0]""")
        check("landscape: a stand sits where the card will appear", bool(cand) and cand["x"] > 740 - vl["right"] + 10, (cand, vl))
        url0 = page.url
        page.touchscreen.tap(cand["x"], cand["y"])
        page.wait_for_timeout(110)
        under = page.evaluate("([x, y]) => { const n = document.elementFromPoint(x, y); return n && n.closest('[data-role=\"exsheet\"]') ? 'card' : 'map'; }", [cand["x"], cand["y"]])
        page.touchscreen.tap(cand["x"], cand["y"])
        page.wait_for_timeout(900)
        vd = page.evaluate("() => window.ATFAExpo27Page.view()")
        check("landscape: a double tap there picks the stand and presses nothing on the card",
              page.url == url0 and vd["sel"] == cand["id"] and under == "card", (page.url, under, vd, cand))
        ctx.close()

        # Zoomed right in on a big phone, then turned sideways: how close the plan can zoom
        # depends on the screen, and the zoom stays within what the new screen allows.
        ctx = browser.new_context(viewport={"width": 430, "height": 740}, device_scale_factor=2, is_mobile=True, has_touch=True)
        page = ctx.new_page()
        page.route("**/*", router(shell(visitor), rest=REST_REAL))
        page.goto("https://test.local/expo-2027/")
        page.wait_for_timeout(800)
        page.evaluate("() => window.ATFAExpo27Page.open('B15')")
        page.wait_for_timeout(900)
        for _ in range(10):
            if page.get_attribute("[data-role='exin']", "aria-disabled") == "true":
                break
            page.tap("[data-role='exin']")
            page.wait_for_timeout(500)
        vz = page.evaluate("() => window.ATFAExpo27Page.view()")
        page.set_viewport_size({"width": 800, "height": 400})
        page.wait_for_timeout(900)
        vs = page.evaluate("() => window.ATFAExpo27Page.view()")
        page.set_viewport_size({"width": 430, "height": 740})
        page.wait_for_timeout(900)
        vb = page.evaluate("() => window.ATFAExpo27Page.view()")
        check("big phone: zoomed right in and turned sideways, the zoom stays within what the screen allows",
              vz["px"] >= vz["max"] * 0.98 and vz["max"] > vs["max"] + 0.5 and vs["fit"] - 0.01 <= vs["px"] <= vs["max"] + 0.01
              and vb["px"] <= vb["max"] + 0.01 and vs["sel"] == "B15" and vb["sel"] == "B15", (vz, vs, vb))
        ctx.close()

        # Pinched out past the whole hall and dragged straight away, before the plan has sprung
        # back: it still ends at the whole hall, not smaller.
        ctx = browser.new_context(viewport={"width": 390, "height": 664}, device_scale_factor=2, is_mobile=True, has_touch=True)
        page = ctx.new_page()
        page.route("**/*", router(shell(visitor), rest=REST_REAL))
        page.goto("https://test.local/expo-2027/")
        page.wait_for_timeout(800)
        page.evaluate("() => window.ATFAExpo27Page.open()")
        page.wait_for_timeout(900)
        cdp = ctx.new_cdp_session(page)
        def touch(kind, pts):
            cdp.send("Input.dispatchTouchEvent", {"type": kind, "touchPoints": [{"x": x, "y": y, "id": i} for i, (x, y) in enumerate(pts)]})
        cx, cy = 195, 380
        touch("touchStart", [(cx - 90, cy), (cx + 90, cy)])
        for k in range(1, 9):
            touch("touchMove", [(cx - 90 + k * 7, cy), (cx + 90 - k * 7, cy)])
            page.wait_for_timeout(16)
        touch("touchEnd", [])
        page.wait_for_timeout(40)
        touch("touchStart", [(150, 300)])
        for k in range(1, 6):
            touch("touchMove", [(150 + k * 25, 300 - k * 30)])
            page.wait_for_timeout(8)
        touch("touchEnd", [])
        page.wait_for_function("() => !window.ATFAExpo27Page.view().busy", timeout=4000)
        vp = page.evaluate("() => window.ATFAExpo27Page.view()")
        check("iphone: pinched out past the whole hall and dragged at once, the plan still ends at the whole hall",
              vp["px"] >= vp["fit"] * 0.99, vp)
        ctx.close()

        # Closing with X takes the plan's history step away too: the plan shuts at once, the page
        # is back where it was, and the phone's back button then leaves the page as usual.
        ctx = browser.new_context(viewport={"width": 390, "height": 664}, device_scale_factor=2, is_mobile=True, has_touch=True)
        page = ctx.new_page()
        page.route("**/*", router(shell(visitor), rest=REST_REAL))
        page.goto("https://test.local/expo-2027/")
        page.wait_for_timeout(800)
        page.locator("#floor-plan").scroll_into_view_if_needed()
        page.wait_for_timeout(300)
        y0 = page.evaluate("() => scrollY")
        page.tap(".xp-open")
        page.wait_for_timeout(700)
        page.tap("[data-role='exclose']")
        shut = page.evaluate("() => !window.ATFAExpo27Page.view().open")
        page.wait_for_timeout(500)
        y1 = page.evaluate("() => scrollY")
        state = page.evaluate("() => JSON.stringify(history.state)")
        page.go_back()
        page.wait_for_timeout(500)
        check("iphone: X shuts the plan at once, the page is where it was, and back then leaves the page",
              shut and abs(y1 - y0) <= 2 and state == "null" and "expo-2027" not in page.url, (shut, y0, y1, state, page.url))
        ctx.close()

        # Tablet: narrow plan card, full-screen plan
        ctx = browser.new_context(viewport={"width": 820, "height": 1180}, is_mobile=True, has_touch=True)
        page = ctx.new_page()
        page.route("**/*", router(shell(visitor), rest=REST_REAL))
        page.goto("https://test.local/expo-2027/")
        page.wait_for_timeout(800)
        check("tablet: no sideways scrolling", overflow(page) <= 0, overflow(page))
        page.locator("#floor-plan").scroll_into_view_if_needed()
        page.wait_for_timeout(400)
        page.screenshot(path=str(SHOTS / "tablet-plan-card.png"))
        page.tap(".xp-open")
        page.wait_for_timeout(700)
        page.screenshot(path=str(SHOTS / "tablet-plan-open.png"))
        check("tablet: plan opens full screen and fits the hall", page.evaluate("() => window.ATFAExpo27Page.view().open"))
        ctx.close()

        # Hostile theme CSS (if the page ever renders outside the blank template)
        ctx = browser.new_context(viewport={"width": 390, "height": 844}, is_mobile=True, has_touch=True)
        page = ctx.new_page()
        page.route("**/*", router(shell(visitor, hostile=True), rest=REST_REAL))
        page.goto("https://test.local/expo-2027/")
        page.wait_for_timeout(800)
        tt = page.evaluate("() => getComputedStyle(document.querySelector('.xp-open')).textTransform")
        check("theme css: buttons keep their own look", tt == "none", tt)
        check("theme css: no sideways scrolling", overflow(page) <= 0, overflow(page))
        ctx.close()

        # Data failures
        ctx = browser.new_context(viewport={"width": 1280, "height": 900})
        page = ctx.new_page()
        page.route("**/*", router(shell(cold), rest=None, rest_status=500, csv=None))
        page.goto("https://test.local/expo-2027/")
        page.wait_for_timeout(1500)
        st = statuses(page)
        notice = page.inner_text("[data-role='notice']")
        check("failure: nothing loads -> stands not confirmed, notice shown",
              set(st.values()) == {"unknown"} and "couldn’t be loaded" in notice, (set(st.values()), notice))
        page.locator("#floor-plan").scroll_into_view_if_needed()
        box = page.locator(".xp-map svg .xs[data-id='S02'] rect.st").bounding_box()
        page.mouse.click(box["x"] + box["width"] / 2, box["y"] + box["height"] / 2)
        page.wait_for_timeout(200)
        det = page.inner_text("[data-role='details']")
        check("failure: price and enquiry still offered", "$4,200" in det and "Enquire about S02" in det, det[:160])
        ctx.close()

        ctx = browser.new_context(viewport={"width": 1280, "height": 900})
        page = ctx.new_page()
        stale = dict(REST_REAL, source="stale", updated="2026-10-18T22:30:00+00:00")
        page.route("**/*", router(shell(cold), rest=stale))
        page.goto("https://test.local/expo-2027/")
        page.wait_for_timeout(1200)
        check("stale data: notice gives the time it's from", "Showing availability as of" in page.inner_text("[data-role='notice']"))
        ctx.close()

        ctx = browser.new_context(viewport={"width": 1280, "height": 900})
        page = ctx.new_page()
        csv = (ROOT / "tests" / "fixtures" / "real.csv").read_text()
        page.route("**/*", router(shell(cold), rest=None, rest_status=500, csv=csv))
        page.goto("https://test.local/expo-2027/")
        page.wait_for_timeout(1500)
        st = statuses(page)
        check("WordPress down: plan reads the published sheet instead", list(st.values()).count("sold") == 2, st)
        ctx.close()
        browser.close()

    failed = [r for r in results if not r[1]]
    print("\n%d checks, %d failed" % (len(results), len(failed)))
    sys.exit(1 if failed else 0)


if __name__ == "__main__":
    main()
