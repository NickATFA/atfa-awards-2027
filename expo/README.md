# ATFA Expo 2027: Hall 5 interactive floor plan

Source for the floor plan at https://www.atfa.com.au/expo-2027-hall-5-interactive-floor-plan/

Version 2.2.0 (Revised Concept 5 layout, staff bar), October 2026.

## What's where on the website

| Piece | Where |
|---|---|
| The plan | Code Snippets, snippet "ATFA Expo 2027 Hall 5 floor plan (v2.2.0)". Prints `[atfa_expo27_floorplan]`. |
| Staff bar | Above the plan, for logged-in Editors and Administrators only: "Open the stand sheet" (Master tab) and "Refresh from the sheet". Visitors never see it, and the sheet link isn't anywhere else on the page. |
| The page | "Expo 2027: Hall 5 Interactive Floor Plan" (page 19308). Its only content is the shortcode. |
| Stand data | `/wp-json/atfa/v1/expo27/stands` (live, cached 2 minutes) and `/wp-content/uploads/atfa-expo27/stands-v3.json` (fast copy). |
| Enquiry form | Gravity Forms "Expo 2027 Stand Enquiry" (form 58) on the page `/expo-2027-stand-enquiry/`. The plan links to it with `?stand=S01`. Notifications go to cherie@atfa.com.au. |
| PDF | Media library: `ATFA-Expo-2027-Hall-5-Floor-Plan.pdf` (A3, plan + rate card + stand schedule). |

## The layout (Revised Concept 5)

28 stands, fitted to scale from the Revised Concept 5 drawing:

| Stands | Size | Price (excl. GST) |
|---|---|---|
| B01 to B12 | 3 × 3 m | $1,200 |
| G01 to G04 | 3 × 6 m | $2,280 |
| O01 to O06 | 6 × 3 m | $2,280 |
| S01 to S03, S05 to S07 | 6 × 6 m | $4,080 |

Black lines on the plan are shared back walls (back-to-back stands). The grey band is the main
pedestrian route from the main entrance to the demonstration and stage zone; it and the arrival
area inside the main entrance are kept clear. Popups, the list and the PDF say how many sides of
each stand are open to an aisle (worked out from the layout).

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
- **Change the enquiry email, form page, PDF or sheet link:** Code Snippets, edit the snippet, change
  `atfa_expo27_settings()` at the top, save.
- **See a sheet change straight away:** "Refresh from the sheet" in the staff bar (the same as adding `?refresh=1`
  to the page address). Google can take up to 5 minutes to publish an edit; visitors' copies catch up within a
  couple of minutes after that.

## Changing the layout, prices or look

1. Edit files in `src/` (or `tools/layout_concept5.py` to move whole blocks of stands, then run it):
   - `geometry.json`: sizes (price, colour, label), stands (id, size, x, y, w, h in metres), shared walls,
     the pedestrian route, labels, hall outline, stage, seats, exits.
   - `floorplan.js`: the interactive plan.
   - `floorplan.css`: styles (all scoped to `#atfa-expo27` and `#atfa-expo27-modal`).
   - `markup.html`: the HTML shell.
   - `snippet.php`: WordPress code (shortcode, data endpoint, caching).
2. `python3 build.py`. It refuses to build if the layout breaks the hall rules (`tools/check_layout.py`):
   stands inside the 3 m perimeter lane and clear of the curved wall, aisles of 3 m or more, 3.5 m around the
   demo zone, the pedestrian route and arrival area clear, 4 m in front of Entry 2, prices matching sizes.
3. `tests/run_all.sh` (PHP checks plus browser tests at desktop, laptop, tablet and phone sizes; needs PHP 8 and
   Python Playwright).
4. Paste `dist/code-snippet.txt` over the snippet's code in Code Snippets and save. Then re-save the floor plan
   page (or clear WP Rocket's cache), otherwise logged-out visitors keep getting the cached old version.
5. `python3 tools/make_pdf.py` to rebuild the PDF if prices or layout changed, and upload it over the old one.
   The ATFA logo in the PDF's title bar comes from `assets/` (see `assets/README.md`).

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
