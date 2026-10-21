# ATFA Expo 2027: Hall 5 interactive floor plan

Source for the floor plan at https://www.atfa.com.au/expo-2027-hall-5-interactive-floor-plan/

Version 3.3.1, October 2026. Since awards plugin 1.8.0 (19 October 2026) this code ships inside the
ATFA Flooring Awards 2027 plugin; the repo's README.md says how to release a change.

## What's where on the website

| Piece | Where |
|---|---|
| The plan | The ATFA Flooring Awards 2027 plugin (`includes/expo-2027.php`, built from this folder). Prints `[atfa_expo27_floorplan]`, and `[atfa_expo27_page]` for the Expo page. The old Code Snippet "ATFA Expo 2027 page and Hall 5 floor plan" is switched off; leave it off. |
| Staff bar | Above the plan, for logged-in Editors and Administrators only: "Open the stand sheet" (Master tab) and "Refresh from the sheet". On the Expo page it can also make share links (below). Visitors never see it, and the sheet link isn't anywhere else on the page. |
| Share links | While /expo-2027/ is private, staff can let someone without a login see it for 24 hours, 3 days or 7 days: staff bar, "Share this page with someone who doesn't have a login". The link is /expo-2027/?xp-share=<random key>; it shows exactly what visitors will see, is never cached or indexed, and stops when its time is up or when staff press Stop. Kept in the option `atfa_expo27_shares`. |
| The page | "Expo 2027: Hall 5 Interactive Floor Plan" (page 19308). Its only content is the shortcode. |
| Stand data | `/wp-json/atfa/v1/expo27/stands` (live, cached 2 minutes) and `/wp-content/uploads/atfa-expo27/stands-v3.json` (fast copy). |
| Enquiry form | Gravity Forms "Expo 2027 Stand Enquiry" (form 58) on the page `/expo-2027-stand-enquiry/`. The plan links to it with `?stand=S01`. Notifications go to cherie@atfa.com.au. |
| PDF | Ships in the plugin: `assets/expo/ATFA-Expo-2027-Hall-5-Floor-Plan.pdf` (A3, plan + rate card + stand schedule), built by `tools/make_pdf.py`. settings.json points at it with `plugin:`. (The older copy in the media library is no longer linked.) |

## The layout (Alternative layout with 58 booths)

58 stands (828 m²), from Phil's "Alternative layout with 58 booths" of 20 October 2026, drawn on
this plan's own hall geometry, so every booth sits on whole metres (`tools/layout_alt58.py`):

| Stands | Size | Price (excl. GST) |
|---|---|---|
| B01 to B32 | 3 × 3 m | $1,200 |
| G01 to G04 | 3 × 6 m | $2,280 |
| O01 to O18 | 6 × 3 m | $2,280 |
| S01 to S04 | 6 × 6 m | $4,080 |

Rows of 12 × 6 m blocks with nominal 3 m cross lanes between them; each 6 × 6 m booth backs onto
two 6 × 3 m booths. Black lines on the plan are rear walls; a side against a rear wall or
another stand isn't open (G01 to G04 back onto a wall along their south side). Popups, the list
and the PDF say how many sides of each stand are open to an aisle, worked out from the layout.

Phil's drawing is a concept for review with the venue and exhibition contractor. Measured against
the rules the earlier Revised Concept 5 kept: the corner of O12 is 1.7 m from the tip of the demo
zone (Concept 5 kept 3.5 m) and S01 is 4 m inside the main entrance (Concept 5 kept an 8 m arrival
area). Concept 5 is still in `tools/layout_concept5.py` with its own rules, if it's ever needed.

## Where the stand data comes from

Google Sheet "2027 Exhibitor List (import into Google Sheets)" in nick@atfa.com.au's Drive.

- **Master** tab: what staff edit. Status is a dropdown (For Sale / On Hold / Sold), Paid is Yes / No.
- **Website** tab: a formula copies Stand, Status, Company, Website and Bio from Master. Company, Website and Bio
  are only filled in for **Sold** stands, so names against holds never go public. Uses range references, so
  inserting columns in Master can't shift a private column into the public tab.
- Only the Website tab is published to the web, as CSV. Don't change the publish scope.
- The Stand column must hold the stand numbers shown on the plan (S01, B12...). Capitals, spaces and a
  missing leading zero don't matter ("s1" works), but a stand missing from the sheet shows as available.

Changes in the sheet show on the site within about 5 to 7 minutes.

## Everyday tasks (no code)

- **Sell a stand:** Master tab, set Status to Sold, fill in Company. Website and bio are optional.
- **Hold a stand:** set Status to On Hold. The website shows "On hold" with no name.
- **Release a stand:** set Status to For Sale.
- **Open the sheet:** log in to WordPress, open the floor plan page and use "Open the stand sheet" in the staff bar.
  (Staff need edit access to the sheet in Google, or they'll be asked to request it.)
- **Share the private Expo page for a day:** staff bar on /expo-2027/, "Share this page with someone who doesn't
  have a login", choose how long, Make a link, Copy link, send it. Stop it there at any time.
- **Change the enquiry form page, PDF or sheet links:** edit `settings.json`. The enquiry email is in
  `atfa_expo27_settings()` near the top of `src/snippet.php`. Then build and release as below.
- **See a sheet change straight away:** "Refresh from the sheet" in the staff bar (the same as adding `?refresh=1`
  to the page address). Google can take up to 5 minutes to publish an edit; visitors' copies catch up within a
  couple of minutes after that.

## Changing the layout, prices or look

1. Edit files in `src/` (or `tools/layout_alt58.py` to move whole blocks of stands, then run it):
   - `geometry.json`: sizes (price, colour, label), stands (id, size, x, y, w, h in metres), rear walls,
     the layout's own rules, labels, hall outline, stage, seats, exits.
   - `floorplan.js`: the interactive plan.
   - `floorplan.css`: styles (all scoped to `#atfa-expo27` and `#atfa-expo27-modal`).
   - `markup.html`: the HTML shell.
   - `snippet.php`: WordPress code (shortcode, data endpoint, caching).
2. `python3 build.py`. It refuses to build if the layout breaks the hall rules (`tools/check_layout.py`):
   stands inside the 3 m perimeter lane and clear of the curved wall, aisles of 3 m or more, nothing on the demo
   zone, 4 m in front of Entry 2, prices matching sizes, plus any rules the layout sets in geometry.json
   ("rules"). It prints the demo zone and arrival area distances either way.
3. `bash tests/run_all.sh` (the older plan: PHP checks plus browser tests at desktop, laptop, tablet and phone
   sizes) and `bash tests/run_page.sh` (the Expo page: PHP checks including share links, browser tests, then
   `tests/e2e_monkey.py`, long random runs of taps, drags, flicks, pinches, buttons, the list, the card, turning
   the phone and the back button on five phone sizes, checking nothing gets stuck). Needs PHP 8 and Python
   Playwright.
4. From the repo root: `./build.sh x.y.z "What changed"`, commit and push, then click Update now on the site's
   Plugins page. If logged-out visitors still get the old version afterwards, clear WP Rocket's cache.
5. If prices or layout changed, `python3 tools/make_pdf.py` before step 4: it rebuilds the PDF and puts it in
   the plugin, so it goes out with the release. The ATFA logo in the PDF's title bar comes from `assets/`
   (see `assets/README.md`).

## Safety notes

- The plan never shows sample data. If no data can be loaded it shows prices as "not confirmed" and still lets people enquire.
- Everything from the sheet is shown as plain text. Website links must be http/https; "www.example.com" gets https:// added.
- The code is printed by the shortcode, so WordPress's editor and WP Rocket don't alter it.
- If someone opens the page with "Edit with Elementor" and saves, put a Shortcode widget with `[atfa_expo27_floorplan]` back on the page.

## v3.0.0: the Expo page (/expo-2027/)

`[atfa_expo27_page]` prints the whole ATFA Expo 2027 page in the awards look (black page,
Barlow Condensed and Montserrat, the orange, charcoal section bands): the weekend bar
(Expo · Ladies Breakfast · Awards Dinner), the hero with 2025 Expo photos, live stand
counts, the Hall 5 plan in the dark style with a side panel, prices, the 2025 gallery, the
weekend, the hall, how booking works and the awards dinner band.

- Page: "ATFA Expo & Conference 2027" at /expo-2027/, template "ATFA Awards (blank canvas)"
  (from the awards plugin, which also supplies the logo, wordmark and dinner photo).
- Phones and tablets (under 900 px): the plan card opens a full-screen plan: tap a stand to
  zoom to it, pinch and drag, size filters with prices, a list of every stand, and the
  phone's back button closes it.
- Photos: media library IDs in settings.json ("photos").
- The old floor plan page (old_page_id) forwards to /expo-2027/#floor-plan once the new page
  is published.
- Source: src/page.html, src/page.css, src/page.js; tests: tests/page_harness.php and
  tests/e2e_page.py (needs AWARDS_DIR = an unzipped awards plugin, FONT_PKG = npm
  @fontsource/barlow-condensed and @fontsource/montserrat for true-to-life screenshots).

v3.0.1: in Barlow Condensed the letter O and the figure 0 look almost the same, so the big
stand IDs (the stand card and the phone list) set the series letter in Montserrat. O03 no
longer reads as 003.

v3.1.0: the phone plan reworked after testing at real phone heights (Safari's toolbars leave
about 660 px on an iPhone 14 and 550 px on an SE, not the phone's full height). The plan now
gets the screen: no panel until a stand is chosen, then a compact card (price and Enquire,
"More about this stand" for the open sides, saving and previous/next). Zoom buttons (+, -,
whole hall), a double tap zooms, a flick keeps sliding. Moving the plan shifts the drawing
already on screen and redraws it sharp when the finger stops, so pans and pinches stay
smooth; the page behind is paused and pinned (no scrolling underneath on iPhones). Sideways
on a phone, the card sits beside the plan. While /expo-2027/ is private, visitors who go to
it are sent (302) to the old floor plan page, and menu links to it only show for staff.

v3.2.0 (awards plugin 1.8.0, 19 October 2026): moved out of Code Snippets into the awards plugin.
Nothing changed for visitors.

v3.3.0 (awards plugin 1.8.1, 20 October 2026): Phil's 58-booth layout; open sides count rear walls;
the PDF ships in the plugin. Phones: choosing the stand next door no longer moves the plan, and a
partly hidden stand slides only far enough to be seen; a tap or double tap on a stand where the card
slides in can't also press the card (it could open the enquiry form); tapping a stand in
the page's picture opens the plan on that stand; turning the phone at the whole hall fits the whole
hall again; the "Tap a stand" hint goes once the plan is touched; the whole-hall button stays on small
phones with a card open; an expanded card always leaves 120 px of plan showing; the chosen stand is
drawn above its neighbours. Staff can share the private page for 24 hours, 3 days or 7 days.

v3.3.1 (awards plugin 1.8.2, 21 October 2026): phones: after turning the phone, the zoom stays
within what the new screen allows; pinching out past the whole hall and dragging straight away still
ends at the whole hall; a touch whose end never arrived can't leave the plan reading every tap as a
pinch; X (or Escape) closes the plan at once and takes its history step away, so the back button then
leaves the page as usual, and the browser is kept from putting back an older place on the page after
the plan closes. The phone stress test taps buttons the way a person would (scrolled into view, not
covered), waits for the card to finish sliding in, and prints the last few touches with each problem.
