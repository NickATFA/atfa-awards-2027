#!/bin/bash
# Build, run the PHP checks, then the browser checks.
set -e
cd "$(dirname "$0")/.."
mkdir -p tests/out
python3 build.py
php -l dist/atfa-expo27-floorplan.php > /dev/null
php tests/php_harness.php tests/fixtures/edge.csv tests/out > tests/out/edge-result.json
cp tests/out/rest.json tests/out/rest-edge.json; cp tests/out/shortcode.html tests/out/shortcode-edge.html
php tests/php_harness.php tests/fixtures/real.csv tests/out > tests/out/real-result.json
cp tests/out/rest.json tests/out/rest-real.json; cp tests/out/shortcode.html tests/out/shortcode-real.html
php tests/php_harness.php FAIL tests/out > tests/out/fail-result.json
php tests/php_harness.php HTML tests/out > tests/out/html-result.json
php tests/php_harness.php tests/fixtures/unicode.csv tests/out > tests/out/unicode-result.json
cp tests/out/rest.json tests/out/rest-unicode.json
php tests/php_harness.php tests/fixtures/real.csv tests/out REFRESH_EDITOR > tests/out/refresh-editor.json
php tests/php_harness.php tests/fixtures/real.csv tests/out REFRESH_VISITOR > tests/out/refresh-visitor.json
php tests/php_harness.php FAIL tests/out REFRESH_EDITOR > tests/out/refresh-editor-fail.json
php tests/php_harness.php tests/fixtures/real.csv tests/out STAFF > tests/out/staff-cold.json
php tests/php_harness.php tests/fixtures/real.csv tests/out STAFF_WARM > tests/out/staff-warm.json
python3 - <<'PY'
import json
e=json.load(open('tests/out/edge-result.json')); f=json.load(open('tests/out/fail-result.json')); h=json.load(open('tests/out/html-result.json'))
r=json.load(open('tests/out/real-result.json'))
re_=json.load(open('tests/out/refresh-editor.json')); rv=json.load(open('tests/out/refresh-visitor.json'))
ref=json.load(open('tests/out/refresh-editor-fail.json')); sc=json.load(open('tests/out/staff-cold.json')); sw=json.load(open('tests/out/staff-warm.json'))
SHEET='https://docs.google.com/spreadsheets/d/1cYp8wew5Qvfds0_IjBaXA8mb6kuOHh4TmVZqsfbXWsI/edit#gid=885922016'
U={s['id']:s for s in json.load(open('tests/out/unicode-result.json'))['rest']['stands']}
S={s['id']:s for s in e['rest']['stands']}
ids=e['ids']
checks=[
 ("php: shortcode + public route registered", e['shortcode_registered'] and e['route_registered'] and e['route_public']),
 ("php: page render never calls Google", e['shortcode_cold_http_calls']==0),
 ("php: second request served from cache", e['second_call_http']==0),
 ("php: Google down after a good fetch -> last good copy", e['after_failure_source']=='stale' and e['after_failure_count']==13),
 ("php: hold names never leave the server", 'name' not in S['O01'] and 'name' not in S['G02'] and 'name' not in S['B12']),
 ("php: javascript: links dropped", S['O02']['url']==''),
 ("php: www links get https://", S['S02']['url']=='https://www.smithjones.com.au'),
 ("php: multi-line bios kept", '\n\n' in S['S02']['bio']),
 ("php: 'Unsold' is not sold", S['G01']['status']=='available'),
 ("php: stand numbers tidied (s4 -> S4, 'Stand B12' -> B12)", S['S4']['status']=='sold' and S['B12']['status']=='hold', sorted(S)),
 ("php: stand number check", ids=={'s01':'S01','stand_b12':'B12','305':'305','total':'','s01a':'','empty':''}, ids),
 ("php: all 58 stands of the 58-booth layout read from the sheet", len(r['rest']['stands'])==58),
 ("php: Google down, nothing cached -> ok:false, no rows", f['rest']['ok'] is False and f['rest']['stands']==[]),
 ("php: Google returns a web page -> treated as failure", h['rest']['ok'] is False),
 ("php: shortcode prints once per page", e['second_shortcode_empty']),
 ("php: fast static copy written and matches the endpoint", e['static_written']),
 ("php: static copy swapped in whole (no temp files left)", e['no_tmp_files']),
 ("php: page tells the plan where the static copy is", e['config_has_static_url']),
 ("php: odd spaces from the sheet (non-breaking, wide, byte-order mark) are ignored",
  U.get('S01',{}).get('status')=='sold' and U['S01'].get('name')=='Acme Timber' and U.get('S02',{}).get('status')=='sold'
  and U.get('S03',{}).get('status')=='hold' and 'name' not in U['S03'] and U.get('O05',{}).get('status')=='sold'
  and U.get('O06',{}).get('status')=='sold' and U.get('O01',{}).get('status')=='sold' and U.get('O02',{}).get('status')=='hold', U),
 ("php: ?refresh=1 by a logged-in editor fetches the sheet", re_['http_calls']==1 and re_['snapshot']),
 ("php: ?refresh=1 by a visitor does nothing", rv['http_calls']==0 and not rv['snapshot']),
 ("php: visitors get no staff bar and no sheet link",
  not e['staff_bar'] and not e['sheet_link'] and not r['staff_bar'] and not r['sheet_link'] and not f['sheet_link']
  and not rv['staff_bar'] and not rv['sheet_link'], (e['staff_bar'], e['sheet_link'], rv)),
 ("php: no template tokens left in the page", not e['token_left'] and not sc['token_left'] and not rv['token_left']),
 ("php: staff get the bar with the sheet link (Master tab)", sc['staff_bar'] and sc['link']==SHEET and sw['link']==SHEET, sc),
 ("php: staff bar never calls Google by itself", sc['http_calls']==0 and sw['http_calls']==0),
 ("php: staff bar says when the sheet was last read", sw['status'].startswith('Last read from the sheet at '), sw['status']),
 ("php: staff bar with nothing read yet says so", sc['status'].startswith("The plan hasn't read the sheet yet"), sc['status']),
 ("php: staff refresh reports success", re_['staff_bar'] and re_['status']=='Read from the sheet just now.', re_),
 ("php: staff refresh with Google down reports it", ref['http_calls']==1 and ref['status'].startswith("Couldn't reach the sheet just now"), ref),
]
bad=[c for c in checks if not c[1]]
for c in checks: print(("PASS " if c[1] else "FAIL ")+c[0]+("  -- "+str(c[2]) if len(c)>2 and not c[1] else ""))
assert not bad, bad
PY
python3 tests/e2e.py
