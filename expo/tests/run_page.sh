#!/bin/bash
# The Expo page ([atfa_expo27_page]): PHP checks (visitor, staff, forwarding, menu links,
# share links), then the browser tests and the phone stress test.
#
#   AWARDS_DIR=<awards plugin folder> FONT_PKG=<npm folder with @fontsource> bash tests/run_page.sh
#
# AWARDS_DIR defaults to the plugin next to this folder; FONT_PKG is optional (true-to-life
# fonts in the screenshots).
set -e
cd "$(dirname "$0")/.."
export AWARDS_DIR="${AWARDS_DIR:-$(cd .. && pwd)/atfa-awards-2027}"
mkdir -p tests/out
python3 build.py
php -l dist/atfa-expo27-floorplan.php > /dev/null
php tests/php_harness.php tests/fixtures/real.csv tests/out > tests/out/real-result.json
cp tests/out/rest.json tests/out/rest-real.json
for m in VISITOR VISITOR_COLD STAFF NOAWARDS FORWARD SHARE; do
  php tests/page_harness.php tests/fixtures/real.csv tests/out $m > tests/out/page-$m.json
done
cp tests/out/page-visitor.html tests/out/page-visitor-real.html
python3 - <<'PY'
import json
r = {m: json.load(open('tests/out/page-%s.json' % m)) for m in ('VISITOR', 'VISITOR_COLD', 'STAFF', 'NOAWARDS', 'FORWARD', 'SHARE')}
v, c, s, n, f, sh = r['VISITOR'], r['VISITOR_COLD'], r['STAFF'], r['NOAWARDS'], r['FORWARD'], r['SHARE']
OLD = 'https://test.local/expo-2027-hall-5-interactive-floor-plan/?from=expo-2027'
checks = [
 ("page: shortcodes registered", v['page_shortcode'] and v['floorplan_shortcode']),
 ("page: never calls Google while printing", v['http_calls'] == 0 and c['http_calls'] == 0 and s['http_calls'] == 0),
 ("page: prints once", v['second_empty']),
 ("page: visitors get no staff bar and no sheet link", not v['staff_bar'] and not v['sheet_link'] and not c['sheet_link'] and not n['sheet_link']),
 ("page: staff get the bar with the sheet link", s['staff_bar'] and s['sheet_link']),
 ("page: no template tokens left", not v['token_left'] and not s['token_left'] and not c['token_left']),
 ("page: snapshot of the stands when there is one", v['snapshot'] and not c['snapshot']),
 ("page: photos, logo and dinner photo", v['photos'] > 10 and v['logo'] and v['dinner']),
 ("page: works without the awards plugin's images", not n['logo'] and n['photos'] > 10),
 ("page: no em dashes in the copy", not v['em_dash'] and not s['em_dash']),
 ("page: nothing private (contact emails, hold names)", not v['private'] and not s['private']),
 ("forward: old page goes to the plan once the Expo page is published", f['old_page_published'] == ['https://test.local/expo-2027/#floor-plan', 302]),
 ("forward: not while the Expo page is a draft, nor from other pages or Elementor", f['old_page_draft'] is None and f['other_page'] is None and f['elementor_preview'] is None),
 ("hidden: visitors to the private page go to the old floor plan page", f['hidden_private_visitor'] == [OLD, 302] and f['hidden_draft_visitor'] == [OLD, 302]),
 ("hidden: not once published, not for other pages, not for staff", f['hidden_published'] is None and f['hidden_other_page_404'] is None and f['hidden_private_staff'] is None),
 ("menu: Expo link hidden from visitors while private, shown to staff and once public",
  list(f['menu_private_visitor'].values()) == ['Annual Awards', 'Social Events'] and len(f['menu_private_staff']) == 3 and len(f['menu_public_visitor']) == 3
  and f['menu_list_private_visitor'] == ['Annual Awards', 'Social Events'] and f['menu_filter_hooked'] and f['menu_list_hooked']),
 ("share: staff make a 24-hour link to the page", sh['made_url'].startswith('https://test.local/expo-2027/?xp-share=') and sh['made_hours'] == 24 and sh['listed'] == 2),
 ("share: the note is plain text; odd durations become 24 hours", sh['made_note'] == 'Sponsor Co' and sh['odd_hours'] == 24),
 ("share: only staff can make, list or stop links", sh['routes_staff_only']),
 ("share: a visitor with the link sees the private page", sh['visitor_with_key'] == [['publish', 'private'], True]),
 ("share: no link, a wrong link or a malformed one changes nothing",
  sh['visitor_no_key'] == ['', False] and sh['visitor_wrong_key'] == ['', False] and sh['visitor_bad_key'] == ['', False]),
 ("share: the link only opens the Expo page, and only the main request", sh['key_on_other_page'] == ['', False] and sh['key_not_main_query'] == ['', False]),
 ("share: shared views ask search engines not to index them", sh['robots_shared'] == {'noindex': True, 'nofollow': True} and sh['robots_normal'] == []),
 ("share: a stopped link stops working", sh['after_stop'] == ['', False]),
 ("share: a link that has run out stops working and is tidied away", sh['expired'] == ['', False] and sh['left_stored'] == 0),
 ("share: hooked into WordPress", sh['hooks']),
 ("share: the tools are only in the staff bar", sh['tools_visitor'] == '' and sh['tools_staff']),
]
bad = [x for x in checks if not x[1]]
for name, ok in checks:
    print(("PASS " if ok else "FAIL ") + name)
assert not bad, bad
PY
python3 tests/e2e_page.py
python3 tests/e2e_monkey.py "${MONKEY_STEPS:-60}"
