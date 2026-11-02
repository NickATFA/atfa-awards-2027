# ATFA Flooring Awards 2027

The WordPress plugin behind atfa.com.au/awards (the 2027 awards season pages) and the notify-me signups.

- `atfa-awards-2027/` is the plugin. Install it once by uploading `dist/atfa-awards-2027.zip` in wp-admin.
- After that, every push to `main` that bumps the version shows up on the site's Plugins page as an update. Click Update now, or turn on auto-updates for the plugin and it installs itself.
- `manifest.json` is what the site checks: the version and the zip to install.

## Releasing a change

```
./build.sh 1.2.3 "What changed"
git add -A && git commit -m "v1.2.3: what changed" && git push
```

The site picks it up within about ten minutes. On the Plugins page, "Check for updates" under the plugin forces a look straight away.

Anyone who can push to this repo can change the live awards page, so keep it under ATFA's control.

## The Expo page (since 1.8.0)

The plugin also runs the ATFA Expo & Conference 2027 page at /expo-2027/ with the live Hall 5 floor plan
(it used to be a Code Snippet). Its readable source, build and tests are in `expo/`:

- `expo/src/` the page (page.html, page.css, page.js), the older floor plan (floorplan.*), the hall
  geometry (geometry.json) and the WordPress code (snippet.php).
- `expo/settings.json` the sheet link, enquiry page, PDF, page slug and photo IDs.
- `python3 expo/build.py` writes `atfa-awards-2027/includes/expo-2027.php`, which the plugin loads.
  Never edit that file by hand.

Releasing an Expo change is the same as any other: edit `expo/src`, run `python3 expo/build.py`, then
`./build.sh x.y.z "What changed"`, commit and push, and click Update now.

If the layout or prices change, run `python3 expo/tools/make_pdf.py` too: it rebuilds the stand PDF into
`atfa-awards-2027/assets/expo/`, so the PDF goes out with the release.

Tests (optional): `bash expo/tests/run_all.sh` for the PHP checks and the older plan, and
`bash expo/tests/run_page.sh` for the Expo page (PHP checks including share links, browser tests, then a
random-tapping stress test on five phone sizes). Both need PHP 8 and Python Playwright; set FONT_PKG to an
npm folder with @fontsource/barlow-condensed and @fontsource/montserrat for true-to-life screenshots.

## Category sponsorship page (since 1.10.0)

The online version of Phil's "2027 Awards Category Sponsorship" flyer, at /awards/sponsorship/. Platinum Members
see it first, each with their own access code; staff manage it under **Awards sponsorship** in wp-admin.

- `atfa-awards-2027/data/sponsorship.php` holds the offer (price, what's included, the dinner, Phil's contact, the
  season timeline, the sponsorship guide link). The categories come from `data/categories.php`, so the page always
  matches the awards pages.
- `atfa-awards-2027/includes/sponsorship.php` makes the page (a private page, child of /awards/, created the first
  time the plugin runs), decides who sees it (staff, code holders, or everyone once it is opened up), tracks which
  categories are available, on hold or secured, and takes requests (REST: `atfa-awards/v1/sponsorship/request`).
- `atfa-awards-2027/includes/sponsorship-admin.php` is the wp-admin screen: Categories (status and sponsor),
  Access codes (add members one per line, copy link or invitation, stop, new code), Requests (confirm or release)
  and Settings (who can see it, the date the categories go public, categories per sponsor, the EPOXY link, who gets
  requests).
- `atfa-awards-2027/views/sponsorship.php` is the page and the code screen.

Requests are booked by hand for now: each one emails Phil (copy to Nick), puts its categories on hold and emails the
member a confirmation. A request sent from a staff login is a test (only the sender gets the emails, nothing is held).
Once the categories are on EPOXY, put the link in Settings and the page sends people there instead of the form. A
category with a sponsor logo set in `data/categories.php` always shows as secured.

## Principal Partner (since 1.9.0)

Crosbe is the 2027 Principal Partner. `atfa-awards-2027/data/partner.php` holds the name, logo, link and the
short line about them, and the splash, the entries page and the Expo page all read it: a "Presented by" lockup
under each headline, a Principal Partner tile on the splash, a feature on the entries and Expo pages, and the
awards dinner bands. Crosbe also sponsors Floor of the Year (`'partner' => true` on that award in
`data/categories.php`): its logo is on the award's panel and card. Set `'public' => false` to show the partner (and
its Floor of the Year sponsorship) to logged-in staff only, with ATFA on Floor of the Year for visitors; return `null`
to take it off every page.

## Ladies Breakfast page (since 1.11.0)

The Ladies Breakfast 2027 page at /ladies-breakfast-2027/: the same blackout family as the awards and Expo pages, in
the pink of the Women's Lunch, with the photos from Rippon Lea (2026) and Melbourne (2025) and a seat reservation
form. The page is created private (staff only) the first time the version runs; publish it in wp-admin when it is
ready, and the awards and Expo pages link to it from then on (staff see the links straight away).

- `atfa-awards-2027/data/breakfast.php` holds everything the page says: date, times, venue (and whether it is
  confirmed), cost, places, host, who gets reservations, the sponsor, and the Media Library IDs of the photos.
- `atfa-awards-2027/includes/breakfast.php` makes the page, keeps it staff-only until published, takes reservations
  (REST: `atfa-awards/v1/breakfast/rsvp`), lists them under **Breakfast RSVPs** in wp-admin with a CSV export, and
  emails Cherie (copy to Nick) and the guest. While the page is private a reservation from a staff login is a test
  (saved as [Test], emailed to that person only).
- `atfa-awards-2027/views/breakfast.php` is the page.
- `atfa-awards-2027/assets/img/share-card-breakfast.jpg` is its link preview card.

The photos live in the Media Library (titles "Women in Flooring Lunch 2026, Rippon Lea (…)" and "Women in Flooring
Luncheon 2025 (…)"); add or reorder IDs in the data file to change the gallery, and `hero` picks the four behind the
headline.
