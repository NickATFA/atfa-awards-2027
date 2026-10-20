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
