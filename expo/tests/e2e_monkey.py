"""
Robustness test for the full-screen plan on phones. Long random runs of what people do with
a map on a phone: tap stands and empty floor, double tap, drag, flick, pinch in and out,
the +, - and whole-hall buttons, the list, the size filters, the card (More, Next, close,
dragging its handle), turning the phone, the back button, closing and opening again; one
at a time and, now and then, two at once without waiting. After every step it checks that
nothing is stuck, half-drawn, out of bounds or out of step:

  - the plan is drawn sharp for where it is (no picture left stretched or shifted)
  - the zoom is between the whole hall and the closest zoom, and the +/- buttons agree
  - the card, the list and the hint match what's chosen; only the chosen stand is ringed
  - a stand that's just been chosen is in view, clear of the header and the card
  - every stand number fits inside its stand
  - the page behind is pinned while the plan is open, and back where it was afterwards
  - no script errors

    AWARDS_DIR=... python3 tests/e2e_monkey.py [steps per phone] [seed]
"""
import json
import pathlib
import random
import sys

from playwright.sync_api import sync_playwright

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
import e2e_page as T  # noqa: E402

UA = ("Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 "
      "(KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1")
PHONES = [(390, 664, "iphone14-safari"), (375, 553, "iphone-se-safari"), (412, 780, "android"),
          (430, 740, "pro-max-safari"), (360, 640, "small-android")]
STEPS = int(sys.argv[1]) if len(sys.argv) > 1 else 90
SEED = int(sys.argv[2]) if len(sys.argv) > 2 else 2027

STATE_JS = """() => {
  const P = window.ATFAExpo27Page, v = P.view(), o = { v };
  const ex = document.querySelector('[data-role="ex"]'), cam = document.querySelector('[data-role="cam"]');
  const svg = cam && cam.querySelector('svg');
  const sheet = document.querySelector('[data-role="exsheet"]'), sin = document.querySelector('[data-role="exsin"]');
  o.exHidden = ex.hidden;
  o.htmlOn = document.documentElement.classList.contains('xp-ex-on');
  o.bodyPos = document.body.style.position;
  o.tf = cam ? cam.style.transform : '';
  o.vb = svg ? svg.getAttribute('viewBox') : null;
  o.sheetHidden = sheet.hidden;
  o.sheetTf = sheet.style.transform;
  const cid = sin.querySelector('.xp-card-id');
  o.cardId = cid ? cid.textContent : null;
  o.cardSub = (sin.querySelector('.xp-card-sub') || {}).textContent || '';
  o.rows = sin.querySelectorAll('.xp-row').length;
  o.selNodes = svg ? [...svg.querySelectorAll('.xs.is-sel')].map(n => n.getAttribute('data-id')) : [];
  o.inDis = document.querySelector('[data-role="exin"]').getAttribute('aria-disabled');
  o.outDis = document.querySelector('[data-role="exout"]').getAttribute('aria-disabled');
  if (v.sel && svg) {
    const r = svg.querySelector('.xs[data-id="' + v.sel + '"] rect.st').getBoundingClientRect();
    o.selRect = { l: r.left, t: r.top, r: r.right, b: r.bottom };
  }
  o.badLabels = svg && v.open ? [...svg.querySelectorAll('.xs')].filter(g => {
    const st = g.querySelector('rect.st').getBoundingClientRect(), t = g.querySelector('.xs-l').getBoundingClientRect();
    return t.width > st.width + 1 || t.left < st.left - 1 || t.right > st.right + 1 || t.top < st.top - 1 || t.bottom > st.bottom + 1;
  }).map(g => g.getAttribute('data-id')) : [];
  o.scrollY = window.scrollY;
  o.docW = document.documentElement.scrollWidth;
  o.hint = (() => { const h = document.querySelector('[data-role="exhint"]'); return h ? getComputedStyle(h).visibility !== 'hidden' && getComputedStyle(h).display !== 'none' && parseFloat(getComputedStyle(h).opacity) > 0.05 : false; })();
  return o;
}"""

# What's at a point: a stand (its id), a control, or the plan's floor.
HIT_JS = """([x, y]) => {
  const n = document.elementFromPoint(x, y);
  if (!n) { return { kind: 'none' }; }
  const st = n.closest('[data-role="cam"] .xs');
  if (st) { return { kind: 'stand', id: st.getAttribute('data-id') }; }
  if (n.closest('button, a, [data-role="exsheet"], .xp-ex-top, .xp-zoom')) { return { kind: 'control' }; }
  if (n.closest('[data-role="exmap"]')) { return { kind: 'floor' }; }
  return { kind: 'other' };
}"""


class Run:
    def __init__(self, browser, w, h, name, seed, visitor, log):
        self.rng = random.Random(seed)
        self.w, self.h, self.name = w, h, name
        self.log = log
        self.ctx = browser.new_context(viewport={"width": w, "height": h}, device_scale_factor=2, is_mobile=True,
                                       has_touch=True, user_agent=UA)
        self.page = self.ctx.new_page()
        self.errors = []
        self.page.on("pageerror", lambda e: self.errors.append(str(e)))
        self.page.route("**/*", T.router(T.shell(visitor), rest=T.REST_REAL, static=T.REST_REAL))
        self.navs = []
        self.page.on("framenavigated", lambda f: self.navs.append(f.url) if f == self.page.main_frame else None)
        self.page.goto("https://test.local/expo-2027/")
        self.page.wait_for_function("() => window.ATFAExpo27Page && window.ATFAExpo27Page.stands()[0].status !== 'loading'")
        self.page.wait_for_timeout(500)
        self.cdp = self.ctx.new_cdp_session(self.page)
        self.portrait = True
        self.saved_y = None
        self.expect = None
        self.problems = []
        self.history = []

    # ---------------------------------------------------------------- input
    def touch(self, kind, pts):
        self.cdp.send("Input.dispatchTouchEvent",
                      {"type": kind, "touchPoints": [{"x": x, "y": y, "id": i} for i, (x, y) in enumerate(pts)]})

    def tap_at(self, x, y):
        self.touch("touchStart", [(x, y)])
        self.page.wait_for_timeout(self.rng.choice((30, 60, 90)))
        self.touch("touchEnd", [])

    def view(self):
        return self.page.evaluate("() => window.ATFAExpo27Page.view()")

    def idle(self, ms=3500):
        try:
            self.page.wait_for_function("() => !window.ATFAExpo27Page.view().busy", timeout=ms)
        except Exception:
            self.problems.append((self.history[-6:], "still busy after %d ms" % ms, self.view()))
        self.page.wait_for_timeout(60)

    def band(self, v):
        return (6, v["top"] + 6, v["bw"] - v["right"] - 6, v["bh"] - v["bottom"] - 6)

    def random_point(self, v):
        x0, y0, x1, y1 = self.band(v)
        return self.rng.uniform(x0, max(x0 + 1, x1 - 60)), self.rng.uniform(y0, max(y0 + 1, y1))

    def stand_centre(self, sid):
        return self.page.evaluate("""(sid) => { const r = document.querySelector('[data-role="cam"] .xs[data-id="' + sid + '"] rect.st').getBoundingClientRect();
            return {x: r.left + r.width / 2, y: r.top + r.height / 2, w: r.width, h: r.height}; }""", sid)

    def tap_sel(self, selector):
        b = self.page.locator(selector).first
        if not b.count() or not b.is_visible():
            return False
        bb = b.bounding_box()
        if not bb:
            return False
        self.tap_at(bb["x"] + bb["width"] / 2, bb["y"] + bb["height"] / 2)
        return True

    # ---------------------------------------------------------------- actions
    def a_open(self):
        if self.view()["open"]:
            return "open (already)"
        self.saved_y = None
        how = self.rng.choice(("button", "picture", "picture-stand", "api"))
        if how == "api":
            self.saved_y = self.page.evaluate("() => window.scrollY")
            self.page.evaluate("() => window.ATFAExpo27Page.open()")
            return "open via api"
        self.page.locator("#floor-plan").scroll_into_view_if_needed()
        self.page.wait_for_timeout(150)
        self.saved_y = self.page.evaluate("() => window.scrollY")
        if how == "button":
            self.tap_sel(".xp-open") or self.tap_sel(".xp-ctas .btn-primary")
            return "open via button"
        sid = self.rng.choice(["B10", "S02", "O07", "G03", "B30"])
        r = self.page.evaluate("""(sid) => { const g = document.querySelector('[data-role="map"] .xs[data-id="' + sid + '"] rect.st');
            const r = g.getBoundingClientRect(); return {x: r.left + r.width / 2, y: r.top + r.height / 2}; }""", sid)
        if how == "picture":
            m = self.page.locator("[data-role='map']").bounding_box()
            self.tap_at(m["x"] + 8, m["y"] + 8)
            return "open via the picture"
        self.tap_at(r["x"], r["y"])
        self.expect = ("sel", sid)
        return "open on " + sid + " via the picture"

    def a_tap_stand(self):
        v = self.view()
        ids = [s["id"] for s in self.page.evaluate("() => window.ATFAExpo27Page.stands()")]
        self.rng.shuffle(ids)
        x0, y0, x1, y1 = self.band(v)
        for sid in ids[:20]:
            c = self.stand_centre(sid)
            if x0 < c["x"] < x1 and y0 < c["y"] < y1:
                hit = self.page.evaluate(HIT_JS, [c["x"], c["y"]])
                if hit["kind"] == "stand" and hit["id"] == sid:
                    self.tap_at(c["x"], c["y"])
                    self.expect = ("sel", sid)
                    return "tap stand " + sid
        return self.a_tap_floor()

    def a_tap_floor(self):
        v = self.view()
        for _ in range(25):
            x, y = self.random_point(v)
            hit = self.page.evaluate(HIT_JS, [x, y])
            if hit["kind"] == "floor":
                self.tap_at(x, y)
                return "tap floor %.0f,%.0f" % (x, y)
        return "tap floor (none found)"

    def a_double_tap(self):
        v = self.view()
        x, y = self.random_point(v)
        self.tap_at(x, y)
        self.page.wait_for_timeout(self.rng.choice((60, 110, 180)))
        self.tap_at(x, y)
        return "double tap %.0f,%.0f" % (x, y)

    def a_drag(self, flick=None):
        v = self.view()
        x, y = self.random_point(v)
        dx, dy = self.rng.uniform(-260, 260), self.rng.uniform(-260, 260)
        flick = self.rng.random() < 0.4 if flick is None else flick
        n = 5 if flick else self.rng.randint(6, 14)
        self.touch("touchStart", [(x, y)])
        for k in range(1, n + 1):
            self.touch("touchMove", [(x + dx * k / n, y + dy * k / n)])
            self.page.wait_for_timeout(8 if flick else 16)
        self.touch("touchEnd", [])
        return "%s %.0f,%.0f by %.0f,%.0f" % ("flick" if flick else "drag", x, y, dx, dy)

    def a_pinch(self):
        v = self.view()
        cx, cy = self.random_point(v)
        f = self.rng.choice((0.35, 0.6, 0.8, 1.4, 2.2, 3.5))
        d0 = self.rng.uniform(40, 120)
        d1 = d0 * f
        ang = self.rng.uniform(0, 3.14)
        import math
        ux, uy = math.cos(ang), math.sin(ang)
        drift = (self.rng.uniform(-40, 40), self.rng.uniform(-40, 40))
        pts = lambda d, t: [(cx + drift[0] * t - ux * d / 2, cy + drift[1] * t - uy * d / 2),
                            (cx + drift[0] * t + ux * d / 2, cy + drift[1] * t + uy * d / 2)]
        self.touch("touchStart", pts(d0, 0))
        n = 8
        for k in range(1, n + 1):
            self.touch("touchMove", pts(d0 + (d1 - d0) * k / n, k / n))
            self.page.wait_for_timeout(16)
        if self.rng.random() < 0.3:
            # lift one finger first, then carry on with the other
            p = pts(d1, 1)[1]
            self.touch("touchEnd", [p])
            self.page.wait_for_timeout(16)
            self.touch("touchMove", [(p[0] - 30, p[1] - 20)])
            self.page.wait_for_timeout(16)
        self.touch("touchEnd", [])
        return "pinch x%.2f at %.0f,%.0f" % (f, cx, cy)

    def a_button(self):
        which = self.rng.choice(("exin", "exout", "exwhole"))
        ok = self.tap_sel("[data-role='%s']" % which)
        return "button " + which + ("" if ok else " (hidden)")

    def a_list(self):
        self.tap_sel("[data-role='exlist']")
        return "List/Map"

    def a_list_row(self):
        if self.view()["mode"] != "list":
            self.tap_sel("[data-role='exlist']")
            self.idle()
        ids = self.page.evaluate("() => [...document.querySelectorAll('[data-role=\"exsin\"] .xp-row')].map(r => r.dataset.id)")
        if not ids:
            return "list row (no rows)"
        sid = self.rng.choice(ids)
        self.page.evaluate("(sid) => document.querySelector('[data-role=\"exsin\"] .xp-row[data-id=\"' + sid + '\"]').scrollIntoView({block: 'center'})", sid)
        self.page.wait_for_timeout(120)
        if self.tap_sel("[data-role='exsin'] .xp-row[data-id='%s']" % sid):
            self.expect = ("sel", sid)
        return "list row " + sid

    def a_chip(self):
        chips = self.page.evaluate("() => [...document.querySelectorAll('[data-role=\"exchips\"] .xp-chip')].map((c, i) => i)")
        if not chips:
            return "chip (none)"
        i = self.rng.choice(chips)
        self.page.evaluate("(i) => document.querySelectorAll('[data-role=\"exchips\"] .xp-chip')[i].scrollIntoView({inline: 'center', block: 'nearest'})", i)
        self.page.wait_for_timeout(80)
        b = self.page.evaluate("(i) => { const r = document.querySelectorAll('[data-role=\"exchips\"] .xp-chip')[i].getBoundingClientRect(); return {x: r.left + r.width / 2, y: r.top + r.height / 2}; }", i)
        self.tap_at(b["x"], b["y"])
        return "chip %d" % i

    def a_card(self):
        what = self.rng.choice(("more", "go", "go", "clear"))
        if what == "go":
            ok = self.tap_sel("[data-role='exsin'] .xp-sd-nav [data-act='go']:%s-child" % self.rng.choice(("first", "last")))
            if ok:
                self.expect = ("sel-any", None)
            return "card next/prev" + ("" if ok else " (none)")
        ok = self.tap_sel("[data-role='exsin'] [data-act='%s']" % what)
        return "card " + what + ("" if ok else " (none)")

    def a_handle(self):
        b = self.page.locator("[data-role='exgrab']")
        if not b.count() or not b.is_visible():
            return "handle (none)"
        bb = b.bounding_box()
        x, y = bb["x"] + bb["width"] / 2, bb["y"] + bb["height"] / 2
        dy = self.rng.choice((90, 60, -50, 2))
        self.touch("touchStart", [(x, y)])
        for k in range(1, 6):
            self.touch("touchMove", [(x, y + dy * k / 5)])
            self.page.wait_for_timeout(16)
        self.touch("touchEnd", [])
        return "handle drag %d" % dy

    def a_rotate(self):
        self.portrait = not self.portrait
        w, h = (self.w, self.h) if self.portrait else (self.h + 60, self.w - 30)
        self.page.set_viewport_size({"width": w, "height": h})
        return "turn to %dx%d" % (w, h)

    def a_back(self):
        if not self.view()["open"]:
            return "back (closed; skipped)"
        self.page.go_back()
        return "back button"

    def a_close(self):
        if not self.view()["open"]:
            return "close (already)"
        self.tap_sel("[data-role='exclose']")
        return "close"

    def a_escape(self):
        self.page.keyboard.press("Escape")
        return "Escape key"

    # ---------------------------------------------------------------- checks
    def check(self, step):
        o = self.page.evaluate(STATE_JS)
        v = o["v"]
        bad = []
        if self.errors:
            bad.append("script error: %s" % self.errors[-1])
            self.errors.clear()
        if v["open"]:
            if o["exHidden"] or not o["htmlOn"] or o["bodyPos"] != "fixed":
                bad.append("plan open but the page isn't pinned or the plan is hidden %s" % [o["exHidden"], o["htmlOn"], o["bodyPos"]])
            if o["tf"]:
                bad.append("plan left moved/stretched (transform %s)" % o["tf"])
            vb = [float(n) for n in o["vb"].split()]
            if max(abs(vb[0] - v["x"]), abs(vb[1] - v["y"]), abs(vb[2] - v["w"]), abs(vb[3] - v["h"])) > 0.02:
                bad.append("drawing out of step with the camera %s vs %s" % (o["vb"], [v["x"], v["y"], v["w"], v["h"]]))
            if not (v["fit"] * 0.99 <= v["px"] <= v["max"] * 1.01):
                bad.append("zoom out of range: %.2f (whole hall %.2f, closest %.2f)" % (v["px"], v["fit"], v["max"]))
            want_in = "true" if v["px"] >= v["max"] * 0.99 else "false"
            want_out = "true" if v["px"] <= v["fit"] * 1.01 else "false"
            if o["inDis"] != want_in or o["outDis"] != want_out:
                bad.append("zoom buttons out of step: + %s - %s at %.2f" % (o["inDis"], o["outDis"], v["px"]))
            if v["mode"] == "peek" and (not o["sheetHidden"] or v["sel"]):
                bad.append("nothing chosen but the card shows (or a stand is chosen with no card)")
            if v["mode"] == "sel":
                if o["sheetHidden"] or not v["sel"]:
                    bad.append("stand mode with no card")
                elif o["cardId"] != v["sel"] and ("Stand " + v["sel"] + " ") not in (o["cardSub"] + " "):
                    bad.append("card shows %r / %r but %s is chosen" % (o["cardId"], o["cardSub"], v["sel"]))
            if v["mode"] == "list" and (o["sheetHidden"] or not o["rows"]):
                if o["rows"] == 0 and "No stands" not in self.page.inner_text("[data-role='exsin']"):
                    bad.append("list mode without the list")
            if o["selNodes"] != ([v["sel"]] if v["sel"] else []):
                bad.append("ringed stands %s but chosen %s" % (o["selNodes"], v["sel"]))
            if o["sheetTf"]:
                bad.append("card left dragged (%s)" % o["sheetTf"])
            if o["badLabels"]:
                bad.append("stand numbers spill out of %s" % o["badLabels"][:6])
            if self.expect and v["mode"] != "list":
                kind, sid = self.expect
                if kind == "sel" and v["sel"] != sid:
                    bad.append("expected %s chosen, got %s" % (sid, v["sel"]))
                elif v["sel"] and "selRect" in o:
                    r = o["selRect"]
                    x0, y0 = 0, v["top"]
                    x1, y1 = v["bw"] - v["right"], v["bh"] - v["bottom"]
                    fits = (r["r"] - r["l"]) <= (x1 - x0) and (r["b"] - r["t"]) <= (y1 - y0)
                    if fits:
                        inside = r["l"] >= x0 - 1 and r["r"] <= x1 + 1 and r["t"] >= y0 - 1 and r["b"] <= y1 + 1
                    else:
                        cx, cy = (r["l"] + r["r"]) / 2, (r["t"] + r["b"]) / 2
                        inside = x0 <= cx <= x1 and y0 <= cy <= y1
                    if not inside:
                        bad.append("chosen stand %s not in view: %s, band %s" % (v["sel"], {k: round(val) for k, val in r.items()}, (x0, y0, x1, y1)))
        else:
            if not o["exHidden"] or o["htmlOn"] or o["bodyPos"]:
                bad.append("plan closed but the page is still pinned %s" % [o["exHidden"], o["htmlOn"], o["bodyPos"]])
            if self.saved_y is not None and abs(o["scrollY"] - self.saved_y) > 2:
                bad.append("page not back where it was: %s, was %s" % (o["scrollY"], self.saved_y))
            if o["docW"] > self.page.viewport_size["width"] + 1:
                bad.append("page scrolls sideways (%s)" % o["docW"])
        self.expect = None
        for b in bad:
            self.problems.append((self.history[-6:], b, v))
        return not bad

    # ---------------------------------------------------------------- run
    def step(self, i):
        v = self.view()
        if not v["open"]:
            acts = [(self.a_open, 1)]
        else:
            acts = [(self.a_tap_stand, 7), (self.a_tap_floor, 2), (self.a_double_tap, 2), (self.a_drag, 6),
                    (self.a_pinch, 5), (self.a_button, 4), (self.a_list, 2), (self.a_list_row, 2), (self.a_chip, 2),
                    (self.a_card, 4), (self.a_handle, 2), (self.a_rotate, 1), (self.a_back, 0.6), (self.a_close, 0.6),
                    (self.a_escape, 0.3)]
        tot = sum(wt for _, wt in acts)
        r = self.rng.uniform(0, tot)
        for fn, wt in acts:
            r -= wt
            if r <= 0:
                break
        desc = fn()
        self.history.append("%d %s" % (i, desc))
        # Now and then do the next thing straight away, before this one has finished.
        if v["open"] and self.rng.random() < 0.18 and self.view()["open"]:
            fn2 = self.rng.choice((self.a_tap_stand, self.a_drag, self.a_pinch, self.a_button, self.a_tap_floor))
            self.expect = None
            self.page.wait_for_timeout(self.rng.choice((20, 80, 160)))
            d2 = fn2()
            self.history.append("%d +quick %s" % (i, d2))
            if fn2 is not self.a_tap_stand:
                self.expect = None
        self.page.wait_for_timeout(30)
        if self.view()["open"]:
            self.idle()
        else:
            self.page.wait_for_timeout(700)
        self.check(i)

    def reload(self):
        self.page.goto("https://test.local/expo-2027/")
        self.page.wait_for_function("() => window.ATFAExpo27Page && window.ATFAExpo27Page.stands()[0].status !== 'loading'")
        self.page.wait_for_timeout(300)
        self.navs = []
        self.saved_y = None
        self.expect = None
        if not self.portrait:
            self.portrait = True
            self.page.set_viewport_size({"width": self.w, "height": self.h})

    def run(self, steps):
        self.navs = []
        for i in range(steps):
            try:
                self.step(i)
            except Exception as e:  # noqa: BLE001
                self.problems.append((self.history[-6:], "test step failed: %s" % str(e).split("\n")[0][:200], {}))
            if self.navs and any(not u.startswith("https://test.local/expo-2027/") for u in self.navs):
                self.problems.append((self.history[-6:], "left the page: %s" % self.navs, {}))
                self.reload()
            self.navs = []
        self.ctx.close()
        return self.problems


def main():
    visitor = (T.OUT / "page-visitor-real.html").read_text()
    total = 0
    found = []
    with sync_playwright() as pw:
        browser = pw.chromium.launch()
        for k, (w, h, name) in enumerate(PHONES):
            r = Run(browser, w, h, name, SEED + k, visitor, print)
            probs = r.run(STEPS)
            total += STEPS
            print("%s: %d steps, %d problems" % (name, STEPS, len(probs)))
            for hist, what, v in probs[:12]:
                print("  PROBLEM:", what)
                print("    after:", " | ".join(hist))
                print("    view:", json.dumps({k2: (round(val, 2) if isinstance(val, float) else val) for k2, val in v.items()}))
            found += [(name,) + p for p in probs]
        browser.close()
    print("\n%d steps on %d phones, %d problems" % (total, len(PHONES), len(found)))
    sys.exit(1 if found else 0)


if __name__ == "__main__":
    main()
