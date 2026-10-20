/*!
 * ATFA Expo & Conference 2027: Hall 5 interactive floor plan
 * Version __VERSION__
 *
 * How it works
 *   1. Draws Hall 5 from the geometry below (stands, stage, seating, exits).
 *   2. Shows the latest stand data the server already had (the "snapshot").
 *   3. Asks WordPress for fresh data (/wp-json/atfa/v1/expo27/stands), which is
 *      a cached copy of the Website tab of the "2027 Exhibitor List" Google Sheet.
 *   4. If WordPress can't answer, reads the published Google CSV directly.
 *   5. If nothing loads, every stand shows its price as "not confirmed" and
 *      visitors can still enquire. Sample or placeholder exhibitors are never shown.
 *
 * Everything is built with DOM methods (no innerHTML), so anything typed into
 * the sheet is always displayed as plain text and can never run as code.
 */
(function () {
  'use strict';

  var root = document.getElementById('atfa-expo27');
  if (!root || root.getAttribute('data-ready') === '1') { return; }
  root.setAttribute('data-ready', '1');

  /* ------------------------------------------------------------------ */
  /* Data and settings                                                   */
  /* ------------------------------------------------------------------ */

  var GEO = __GEOMETRY__;
  var CFG = readJSON('atfa-expo27-config') || {};
  var SNAP = readJSON('atfa-expo27-snapshot');

  var SIZES = GEO.sizes;
  var LOOK = {
    hold:    { fill: '#FBE9C8', line: '#D99A21' },
    sold:    { fill: '#96A29C', line: '#7D8A84' },
    loading: { fill: '#F1F3F2', line: '#C3CBC7' },
    unknown: { fill: '#F1F3F2', line: '#C3CBC7' }
  };
  var STATUS_LABEL = {
    available: 'Available', hold: 'On hold', sold: 'Sold',
    loading: 'Checking availability', unknown: 'Not confirmed'
  };
  var STATUS_DOT = {
    available: '#2E7D4F', hold: '#D99A21', sold: '#96A29C', loading: '#C3CBC7', unknown: '#C3CBC7'
  };

  // Whole drawing, and a closer crop of the stands used on narrow screens.
  var FULL = GEO.view;
  var FOCUS = GEO.focus || GEO.view;
  var NARROW_PX = 700;
  var SHORT_PX = 600;
  // Small print on a stand (size, "On hold", company) is only shown when it would
  // be at least this many screen pixels tall.
  var SMALL_PRINT_PX = 7.5;

  var STANDS = GEO.stands.map(function (s) {
    var big = Math.min(s.w, s.h) >= 6;
    return {
      id: String(s.id), key: idKey(s.id), size: s.size, x: s.x, y: s.y, w: s.w, h: s.h, sqm: s.sqm, price: s.price,
      status: 'loading', name: '', url: '', urlTyped: '', bio: '', open: 0,
      fs: big ? 0.9 : 0.66
    };
  });
  var BY_ID = {};
  STANDS.forEach(function (s) { BY_ID[s.id] = s; });
  STANDS.forEach(function (s) { s.open = openSides(s); });

  var filters = { q: '', sizes: {}, availOnly: false };
  var selectedId = null;
  var openId = null;
  var lastUpdated = null;

  /* ------------------------------------------------------------------ */
  /* Small helpers                                                       */
  /* ------------------------------------------------------------------ */

  function readJSON(id) {
    var node = document.getElementById(id);
    if (!node) { return null; }
    try { return JSON.parse(node.textContent || 'null'); } catch (e) { return null; }
  }

  function q(selector, scope) { return (scope || root).querySelector(selector); }

  function el(tag, props, kids) {
    var node = document.createElement(tag);
    if (props) {
      Object.keys(props).forEach(function (key) {
        var val = props[key];
        if (val === null || val === undefined || val === false) { return; }
        if (key === 'text') { node.textContent = val; }
        else if (key === 'className') { node.className = val; }
        else { node.setAttribute(key, val === true ? '' : String(val)); }
      });
    }
    (kids || []).forEach(function (kid) {
      if (kid === null || kid === undefined || kid === false) { return; }
      node.appendChild(typeof kid === 'string' ? document.createTextNode(kid) : kid);
    });
    return node;
  }

  var SVGNS = 'http://www.w3.org/2000/svg';

  function sv(tag, attrs, parent) {
    var node = document.createElementNS(SVGNS, tag);
    Object.keys(attrs || {}).forEach(function (key) {
      var val = attrs[key];
      if (val === null || val === undefined) { return; }
      node.setAttribute(key, String(val));
    });
    if (parent) { parent.appendChild(node); }
    return node;
  }

  function svText(parent, x, y, str, attrs) {
    var node = sv('text', Object.assign({ x: x, y: y }, attrs || {}), parent);
    node.textContent = str;
    return node;
  }

  function money(n) { return '$' + Math.round(n).toLocaleString('en-AU'); }

  function money2(n) {
    return '$' + Number(n).toLocaleString('en-AU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function sizeText(size) {
    if (SIZES[size] && SIZES[size].label) { return SIZES[size].label; }
    var p = size.split('x'); return p[0] + ' × ' + p[1] + ' m';
  }
  function sizeShort(size) { var p = size.split('x'); return p[0] + ' × ' + p[1]; }
  function sizeSpoken(size) { var p = size.split('x'); return p[0] + ' by ' + p[1] + ' metres'; }

  // Stand numbers are matched loosely, so "s1", "S 01" and "Stand S01" in the
  // sheet all mean S01, and old-style numbers like 305 still work.
  function idKey(value) {
    var v = String(value === null || value === undefined ? '' : value).toUpperCase().replace(ODD_SPACE, '');
    v = v.replace(/^STAND/, '');
    var m = /^([A-Z]{0,3})0*(\d{1,4})$/.exec(v);
    return m ? m[1] + m[2] : '';
  }

  // A rear wall drawn along a side closes it, even with no stand behind it.
  function wallAlong(horiz, lo, hi, at) {
    var e = 1e-6;
    return (GEO.walls || []).some(function (w) {
      if (horiz) {
        return Math.abs(w[1] - at) < e && Math.abs(w[3] - at) < e &&
          Math.min(hi, Math.max(w[0], w[2])) - Math.max(lo, Math.min(w[0], w[2])) > e;
      }
      return Math.abs(w[0] - at) < e && Math.abs(w[2] - at) < e &&
        Math.min(hi, Math.max(w[1], w[3])) - Math.max(lo, Math.min(w[1], w[3])) > e;
    });
  }

  // How many sides of a stand face an aisle (no other stand or rear wall against them).
  function openSides(s) {
    var e = 1e-6, open = 0;
    [['N', s.x, s.x + s.w, s.y], ['S', s.x, s.x + s.w, s.y + s.h],
     ['W', s.y, s.y + s.h, s.x], ['E', s.y, s.y + s.h, s.x + s.w]].forEach(function (side) {
      var horiz = side[0] === 'N' || side[0] === 'S', lo = side[1], hi = side[2], at = side[3];
      var blocked = STANDS.some(function (o) {
        if (o === s) { return false; }
        if (horiz) {
          var edge = side[0] === 'N' ? o.y + o.h : o.y;
          return Math.abs(edge - at) < e && Math.min(hi, o.x + o.w) - Math.max(lo, o.x) > e;
        }
        var edgeX = side[0] === 'W' ? o.x + o.w : o.x;
        return Math.abs(edgeX - at) < e && Math.min(hi, o.y + o.h) - Math.max(lo, o.y) > e;
      }) || wallAlong(horiz, lo, hi, at);
      if (!blocked) { open++; }
    });
    return open;
  }

  function openText(s) {
    if (s.open >= 4) { return 'Island, open on all 4 sides'; }
    return 'Open on ' + s.open + (s.open === 1 ? ' side' : ' sides');
  }

  function openShort(s) {
    if (s.open >= 4) { return 'Island'; }
    return s.open + (s.open === 1 ? ' side open' : ' sides open');
  }

  function priceNote() { return CFG.priceNote || 'excl. GST'; }

  function clamp(v, lo, hi) { return Math.min(Math.max(v, lo), hi); }

  function round3(n) { return Math.round(n * 1000) / 1000; }

  /* ------------------------------------------------------------------ */
  /* Cleaning data that came from the sheet                              */
  /* ------------------------------------------------------------------ */

  // Every kind of space a spreadsheet can hold (the server treats them the same way).
  var ODD_SPACE = /[\s\u0085\u180E\u200B]+/g;

  function normStatus(value) {
    var v = String(value === null || value === undefined ? '' : value).toLowerCase().replace(ODD_SPACE, ' ').trim();
    if (v === 'sold') { return 'sold'; }
    if (v === 'hold' || v === 'on hold' || v === 'held' || v === 'reserved') { return 'hold'; }
    return 'available';
  }

  function cleanText(value, max, multiline) {
    var v = String(value === null || value === undefined ? '' : value).replace(/\r\n?/g, '\n').replace(/\t/g, ' ');
    // Remove control characters except line breaks.
    v = v.replace(/[\u0000-\u0008\u000B-\u001F\u007F]/g, '');
    if (multiline) {
      v = v.replace(/[ \t]+/g, ' ').replace(/ *\n */g, '\n').replace(/\n{3,}/g, '\n\n');
    } else {
      v = v.replace(/\s+/g, ' ');
    }
    v = v.trim();
    if (v.length > max) { v = v.slice(0, max - 1).replace(/\s+$/, '') + '…'; }
    return v;
  }

  // Only http and https links are allowed. "www.example.com.au" becomes
  // "https://www.example.com.au". Anything else is dropped.
  function safeUrl(value) {
    var u = String(value === null || value === undefined ? '' : value).trim().replace(/\s/g, '');
    if (!u) { return ''; }
    if (/^[a-z][a-z0-9+.\-]*:/i.test(u)) {
      if (!/^https?:\/\//i.test(u)) { return ''; }
    } else if (/^[a-z0-9\-]+(\.[a-z0-9\-]+)+([\/?#].*)?$/i.test(u)) {
      u = 'https://' + u;
    } else {
      return '';
    }
    try {
      var parsed = new URL(u);
      return (parsed.protocol === 'http:' || parsed.protocol === 'https:') ? parsed.href : '';
    } catch (e) {
      return '';
    }
  }

  // The website's name as typed in the sheet (so "émile.com.au" isn't shown in its
  // encoded "xn--" form), falling back to the parsed address.
  function domainOf(url, typed) {
    var m = /^(?:[a-z][a-z0-9+.\-]*:\/\/)?([^\/?#:\s]+)/i.exec(String(typed || '').trim());
    if (m && m[1].indexOf('.') > 0) { return m[1].toLowerCase().replace(/^www\./, ''); }
    try { return new URL(url).hostname.replace(/^www\./, ''); } catch (e) { return ''; }
  }

  /* ------------------------------------------------------------------ */
  /* CSV reading (only used if the WordPress endpoint can't be reached)  */
  /* ------------------------------------------------------------------ */

  // RFC 4180 parser: handles quoted commas, doubled quotes and line breaks
  // inside quoted cells.
  function parseCSV(text) {
    var rows = [], row = [], field = '', inQuotes = false, i, c;
    text = String(text || '').replace(/^\uFEFF/, '');
    for (i = 0; i < text.length; i++) {
      c = text.charAt(i);
      if (inQuotes) {
        if (c === '"') {
          if (text.charAt(i + 1) === '"') { field += '"'; i++; }
          else { inQuotes = false; }
        } else {
          field += c;
        }
      } else if (c === '"') {
        inQuotes = true;
      } else if (c === ',') {
        row.push(field); field = '';
      } else if (c === '\n' || c === '\r') {
        if (c === '\r' && text.charAt(i + 1) === '\n') { i++; }
        row.push(field); rows.push(row); row = []; field = '';
      } else {
        field += c;
      }
    }
    if (field !== '' || row.length) { row.push(field); rows.push(row); }
    return rows;
  }

  function headerMap(cells) {
    var map = { stand: -1, status: -1, name: -1, url: -1, bio: -1 };
    cells.forEach(function (raw, i) {
      var h = String(raw || '').toLowerCase().trim();
      if (!h) { return; }
      if (map.stand < 0 && h.indexOf('stand') > -1) { map.stand = i; return; }
      if (map.url < 0 && (h.indexOf('website') > -1 || h.indexOf('url') > -1)) { map.url = i; return; }
      if (map.bio < 0 && (h.indexOf('bio') > -1 || h.indexOf('description') > -1)) { map.bio = i; return; }
      if (map.status < 0 && (h.indexOf('sold') > -1 || h.indexOf('hold') > -1 || h.indexOf('sale') > -1 || h.indexOf('status') > -1)) { map.status = i; return; }
      if (map.name < 0 && (h.indexOf('company') > -1 || h.indexOf('exhibitor') > -1)) { map.name = i; }
    });
    return (map.stand > -1 && map.status > -1) ? map : null;
  }

  function rowsFromCSV(text) {
    var rows = parseCSV(text);
    if (!rows.length) { return []; }
    var map = headerMap(rows[0]);
    var start = 1;
    if (!map) { map = { stand: 0, status: 1, name: 2, url: 3, bio: 4 }; start = 0; }
    var cell = function (r, i) { return i > -1 && r[i] !== undefined ? r[i] : ''; };
    var out = [];
    for (var i = start; i < rows.length; i++) {
      var r = rows[i];
      var id = String(cell(r, map.stand)).trim();
      if (!idKey(id)) { continue; }
      var status = normStatus(cell(r, map.status));
      var item = { id: id, status: status };
      if (status === 'sold') {
        item.name = cell(r, map.name);
        item.url = cell(r, map.url);
        item.bio = cell(r, map.bio);
      }
      out.push(item);
    }
    return out;
  }

  /* ------------------------------------------------------------------ */
  /* DOM references (the HTML shell is printed by WordPress)             */
  /* ------------------------------------------------------------------ */

  var stage = q('[data-role="stage"]');
  var tip = q('[data-role="tip"]');
  var hint = q('[data-role="hint"]');
  var listEl = q('[data-role="list"]');
  var countEl = q('[data-role="count"]');
  var noticeEl = q('[data-role="notice"]');
  var helpEl = q('[data-role="help"]');
  var searchEl = q('[data-role="search"]');
  var modal = document.getElementById('atfa-expo27-modal');
  var mBody = modal ? modal.querySelector('[data-role="mbody"]') : null;
  var mCard = modal ? modal.querySelector('.afpx-modal-card') : null;
  if (!stage || !listEl) { return; }

  // The popup must sit directly inside the page body so "position: fixed" always
  // covers the screen, whatever the page builder wraps the plan in.
  if (modal && modal.parentNode !== document.body) { document.body.appendChild(modal); }

  var coarsePointer = !!(window.matchMedia && window.matchMedia('(pointer: coarse)').matches);
  var isMac = /Mac|iPhone|iPad|iPod/.test(navigator.platform || navigator.userAgent || '');
  if (helpEl) {
    helpEl.textContent = coarsePointer
      ? 'Tap a stand for details · use two fingers to move and zoom'
      : 'Click a stand for details · drag to move · ' + (isMac ? '⌘' : 'Ctrl') + ' + scroll to zoom';
  }

  /* ------------------------------------------------------------------ */
  /* Drawing                                                             */
  /* ------------------------------------------------------------------ */

  var W = GEO.hall.w, D = GEO.hall.d, P = GEO.paths;
  var svg = sv('svg', {
    'class': 'afpx-svg', role: 'group', focusable: 'false',
    'aria-label': 'Hall 5 floor plan. ' + STANDS.length + ' stands. Use the arrow keys to move between stands and Enter to open one.'
  });

  // Background layer: hall, aisles, demo zone, stage and seating.
  var deco = sv('g', { 'aria-hidden': 'true' }, svg);
  var defs = sv('defs', {}, deco);
  var hatch = sv('pattern', {
    id: 'afpxHatch', width: 1.8, height: 1.8, patternUnits: 'userSpaceOnUse', patternTransform: 'rotate(45)'
  }, defs);
  sv('rect', { width: 1.8, height: 1.8, fill: '#FBEDED' }, hatch);
  sv('line', { x1: 0, y1: 0, x2: 0, y2: 1.8, stroke: '#F1CCCC', 'stroke-width': 0.6 }, hatch);

  sv('path', { d: P.outer, fill: '#F5F1E8' }, deco);
  sv('path', { d: GEO.radial, fill: 'none', stroke: '#D9CDB4', 'stroke-width': 0.22, 'stroke-dasharray': '1.6 1.1' }, deco);
  sv('path', { d: P.hall, fill: 'url(#afpxHatch)' }, deco);
  sv('path', { d: P.lane, fill: '#F3F5F4' }, deco);
  var routeSlot = sv('g', {}, deco);
  sv('path', { d: P.demo, fill: '#F7EFE4', stroke: '#A8600C', 'stroke-width': 0.3, 'stroke-dasharray': '2 1.3' }, deco);
  sv('path', { d: GEO.stagePath, fill: '#2F4A3D', stroke: '#16211C', 'stroke-width': 0.2 }, deco);

  var stg = GEO.stage;
  var stx = stg.cx - 0.42 * stg.r, sty = stg.cy - 0.42 * stg.r;
  svText(deco, stx, sty, 'STAGE', { 'text-anchor': 'middle', 'font-size': 1.35, 'font-weight': 700, fill: '#fff' });
  svText(deco, stx, sty + 1.5, '5 × 5 m', { 'text-anchor': 'middle', 'font-size': 1.05, fill: '#BFD8C9' });

  GEO.seats.forEach(function (p) {
    sv('circle', { cx: p.x, cy: p.y, r: 0.42, fill: '#fff', stroke: '#4A5C53', 'stroke-width': 0.13 }, deco);
  });
  GEO.speakers.forEach(function (p) {
    sv('rect', { x: p.x - 0.55, y: p.y - 0.75, width: 1.1, height: 1.5, rx: 0.18, fill: '#B8761F', stroke: '#16211C', 'stroke-width': 0.13 }, deco);
    sv('circle', { cx: p.x, cy: p.y - 0.22, r: 0.26, fill: '#fff' }, deco);
    sv('circle', { cx: p.x, cy: p.y + 0.42, r: 0.16, fill: '#fff' }, deco);
  });
  var mix = GEO.mix;
  sv('rect', { x: mix.x, y: mix.y, width: mix.w, height: mix.h, fill: '#4A5C53', stroke: '#16211C', 'stroke-width': 0.13 }, deco);

  // Main pedestrian route: a clear band from the main entrance to the stage zone.
  var R = GEO.route;
  if (R) {
    var clip = sv('clipPath', { id: 'afpxHallClip' }, defs);
    sv('path', { d: P.hall }, clip);
    var rdx = R.x2 - R.x1, rdy = R.y2 - R.y1, full = Math.sqrt(rdx * rdx + rdy * rdy);
    var ux = rdx / full, uy = rdy / full, hw = R.width / 2;
    // The band stops just short of the stage zone so the zone's name can sit there.
    var rlen = full - (R.stop || 0);
    var along = function (t, off) { return [round3(R.x1 + ux * t - uy * off), round3(R.y1 + uy * t + ux * off)]; };
    var routeG = sv('g', { 'clip-path': 'url(#afpxHallClip)' }, routeSlot);
    sv('polygon', {
      points: [along(-1, -hw), along(rlen, -hw), along(rlen, hw), along(-1, hw)].map(function (p) { return p.join(','); }).join(' '),
      fill: '#E7ECEA'
    }, routeG);
    var routeText = 'MAIN PEDESTRIAN ROUTE · KEEP CLEAR';
    var mid = rlen / 2, gap = routeText.length * 0.29 + 1.2;
    var dash = { stroke: '#A3AEA9', 'stroke-width': 0.16, 'stroke-dasharray': '1.1 0.8', fill: 'none' };
    [[2.2, mid - gap], [mid + gap, rlen - 2.2]].forEach(function (seg) {
      var a = along(seg[0], 0), b = along(seg[1], 0);
      sv('line', Object.assign({ x1: a[0], y1: a[1], x2: b[0], y2: b[1] }, dash), routeG);
    });
    [[1.2, -1], [rlen - 1.2, 1]].forEach(function (end) {
      var tip = along(end[0], 0), l = along(end[0] - end[1] * 1.1, -0.7), r = along(end[0] - end[1] * 1.1, 0.7);
      sv('polyline', {
        points: [l, tip, r].map(function (p) { return p.join(','); }).join(' '),
        fill: 'none', stroke: '#A3AEA9', 'stroke-width': 0.2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round'
      }, routeG);
    });
    var lp = along(mid, 0.3), ang = Math.atan2(uy, ux) * 180 / Math.PI;
    svText(routeSlot, lp[0], lp[1], routeText, {
      'text-anchor': 'middle', 'font-size': 0.8, 'font-weight': 700, fill: '#86928D', 'letter-spacing': 0.08,
      transform: 'rotate(' + ang.toFixed(1) + ' ' + lp[0] + ' ' + lp[1] + ')'
    });
  }

  // Zone labels (multi-line labels use "\n").
  (GEO.labels || []).forEach(function (L) {
    var lines = String(L.text).split('\n');
    var attrs = { 'text-anchor': 'middle', 'font-size': L.size, fill: L.color, 'letter-spacing': 0.06 };
    if (L.bold) { attrs['font-weight'] = 700; }
    if (L.rotate) { attrs.transform = 'rotate(' + L.rotate + ' ' + L.x + ' ' + L.y + ')'; }
    var t = svText(deco, L.x, L.y, '', attrs);
    lines.forEach(function (line, i) {
      var span = sv('tspan', { x: L.x, dy: i ? round3(L.size * 1.18) : 0 }, t);
      span.textContent = line;
    });
  });

  // Stands.
  var standLayer = sv('g', { 'class': 'afpx-stands' }, svg);
  var NODES = {};
  STANDS.forEach(function (s, i) {
    var g = sv('g', { 'class': 'afpx-stand is-loading', 'data-id': s.id, role: 'button', tabindex: i === 0 ? 0 : -1 }, standLayer);
    var rect = sv('rect', { x: s.x, y: s.y, width: s.w, height: s.h, rx: 0.18, 'stroke-width': 0.16 }, g);
    var cx = s.x + s.w / 2;
    var lbl = svText(g, cx, 0, s.id, { 'class': 'afpx-lbl', 'text-anchor': 'middle', 'font-weight': 700 });
    var sub = svText(g, cx, 0, '', { 'class': 'afpx-sub', 'text-anchor': 'middle', 'font-size': s.fs, 'font-weight': 500 });
    NODES[s.id] = { g: g, rect: rect, lbl: lbl, sub: sub };
  });

  // Labels: the stand number plus small print (size, "On hold" or the company)
  // when the small print is big enough on screen to read; otherwise just a larger
  // stand number. Zooming in brings the small print back.
  function placeLabels(pxPerM) {
    STANDS.forEach(function (s) {
      var n = NODES[s.id], far = s.fs * pxPerM < SMALL_PRINT_PX;
      if (n.far === far) { return; }
      n.far = far;
      var big = Math.min(s.w, s.h) >= 6, cy = s.y + s.h / 2;
      var fs = far ? (big ? 1.9 : 1.4) : (big ? 1.5 : 1.1);
      n.lbl.setAttribute('font-size', fs);
      n.lbl.setAttribute('y', round3(far ? cy + fs * 0.36 : cy - (big ? 0.2 : 0.12)));
      n.sub.setAttribute('y', round3(cy + (big ? 1.42 : 0.98)));
      n.sub.setAttribute('display', far ? 'none' : 'inline');
      // Keep the number clear of the stand's edges and any shared wall: if it's
      // too wide, narrow the letters slightly rather than shrink them.
      var room = s.w - 0.7, len = 0;
      n.lbl.removeAttribute('textLength');
      n.lbl.removeAttribute('lengthAdjust');
      try { len = n.lbl.getComputedTextLength(); } catch (e) { len = 0; }
      if (len > room) {
        n.lbl.setAttribute('textLength', round3(room));
        n.lbl.setAttribute('lengthAdjust', 'spacingAndGlyphs');
      }
    });
  }

  // Rear walls (between back-to-back stands, or closing the back of a row).
  var wallLayer = sv('g', { 'aria-hidden': 'true', 'pointer-events': 'none' }, svg);
  (GEO.walls || []).forEach(function (w) {
    sv('line', { x1: w[0], y1: w[1], x2: w[2], y2: w[3], stroke: '#16211C', 'stroke-width': 0.36 }, wallLayer);
  });

  // Walls, entrances, exits and dimensions sit on top.
  var over = sv('g', { 'aria-hidden': 'true', 'pointer-events': 'none' }, svg);
  sv('path', { d: P.outer, fill: 'none', stroke: '#16211C', 'stroke-width': 0.42 }, over);
  sv('path', { d: P.arc, fill: 'none', stroke: '#16211C', 'stroke-width': 0.3, 'stroke-dasharray': '2.2 1.4' }, over);
  sv('line', { x1: 0, y1: 43, x2: 0, y2: D, stroke: '#16211C', 'stroke-width': 0.42 }, over);
  sv('line', { x1: 42, y1: 0, x2: W, y2: 0, stroke: '#16211C', 'stroke-width': 0.42 }, over);
  GEO.entrances.forEach(function (e) {
    sv('path', { d: e.path, fill: 'none', stroke: '#A8600C', 'stroke-width': 1.1, 'stroke-linecap': 'round' }, over);
    svText(over, e.lx, e.ly, e.label, {
      fill: '#A8600C', 'font-size': 1.05, 'font-weight': 700, 'text-anchor': 'middle',
      transform: 'rotate(' + e.rot.toFixed(1) + ' ' + e.lx.toFixed(2) + ' ' + e.ly.toFixed(2) + ')'
    });
  });
  GEO.fire.forEach(function (f) {
    var a = { stroke: '#D64A4A', 'stroke-width': 0.7 };
    if (f.side === 'S') { sv('line', Object.assign({ x1: f.v - 2.2, y1: D, x2: f.v + 2.2, y2: D }, a), over); }
    if (f.side === 'N') { sv('line', Object.assign({ x1: f.v - 2.2, y1: 0, x2: f.v + 2.2, y2: 0 }, a), over); }
    if (f.side === 'E') { sv('line', Object.assign({ x1: W, y1: f.v - 2.2, x2: W, y2: f.v + 2.2 }, a), over); }
  });
  svText(over, W / 2, D + 3.2, '58.0 m', { 'font-size': 1.35, fill: '#7D8A84', 'text-anchor': 'middle' });
  svText(over, W + 3.6, D / 2, '54.0 m', {
    'font-size': 1.35, fill: '#7D8A84', 'text-anchor': 'middle', transform: 'rotate(-90 ' + (W + 3.6) + ' ' + D / 2 + ')'
  });

  stage.insertBefore(svg, stage.firstChild);

  /* ------------------------------------------------------------------ */
  /* Painting stands, list and counters                                  */
  /* ------------------------------------------------------------------ */

  function anySizeFilter() {
    return Object.keys(filters.sizes).some(function (k) { return filters.sizes[k]; });
  }

  function matches(s) {
    if (filters.availOnly && s.status !== 'available') { return false; }
    if (anySizeFilter() && !filters.sizes[s.size]) { return false; }
    if (filters.q) {
      var term = filters.q.toLowerCase().replace(/^stand\s*/, '').replace(/\s+/g, ' ').trim();
      if (!term) { return true; }
      var byId = idMatches(s, term.replace(/\s+/g, ''));
      var byName = s.name && s.name.toLowerCase().indexOf(term) > -1;
      if (!byId && !byName) { return false; }
    }
    return true;
  }

  // Stand-number search. "b0" or "B01" match the start of the number as written;
  // without a leading zero, "b1" finds B01 and B10 to B12, "12" finds B12 and
  // "7" finds S07 and B07; "01" finds every stand numbered 01.
  function idMatches(s, typed) {
    var id = s.id.toLowerCase();
    if (id.indexOf(typed) === 0) { return true; }
    var m = /^([a-z]{0,3})(\d+)$/.exec(typed);
    if (!m) { return false; }
    var own = /^([a-z]*)(\d+)$/.exec(id);
    if (!own || (m[1] && m[1] !== own[1])) { return false; }
    if (m[2].charAt(0) === '0') { return !m[1] && own[2].indexOf(m[2]) === 0; }
    return String(Number(own[2])).indexOf(m[2]) === 0;
  }

  // Rough width of a line of text (in metres) for a sans-serif font of size fs.
  function textWidth(text, fs) {
    var w = 0;
    for (var i = 0; i < text.length; i++) {
      var c = text.charAt(i);
      if (c === ' ') { w += 0.28; }
      else if (/[ijlt.,:;'|!()\-fr]/.test(c)) { w += 0.32; }
      else if (/[MWmw@]/.test(c)) { w += 0.84; }
      else if (/[A-Z0-9&%#]/.test(c)) { w += 0.66; }
      else { w += 0.53; }
    }
    return w * fs;
  }

  function fitText(text, width, fontSize) {
    if (textWidth(text, fontSize) <= width) { return text; }
    var cut = text;
    while (cut.length > 3 && textWidth(cut + '…', fontSize) > width) { cut = cut.slice(0, -1); }
    return cut.replace(/\s+$/, '') + '…';
  }

  function standLabel(s) {
    var base = 'Stand ' + s.id + ', ' + sizeSpoken(s.size) + ', ' + s.sqm + ' square metres, ' + openText(s).toLowerCase();
    if (s.status === 'sold') { return base + (s.name ? ', sold to ' + s.name : ', sold'); }
    if (s.status === 'hold') { return base + ', on hold'; }
    if (s.status === 'available') { return base + ', available, ' + money(s.price) + ' ' + priceNote(); }
    if (s.status === 'loading') { return base + ', ' + money(s.price) + ' ' + priceNote() + ', checking availability'; }
    return base + ', ' + money(s.price) + ' ' + priceNote() + ', availability not confirmed';
  }

  function paint(s) {
    var n = NODES[s.id];
    var z = SIZES[s.size] || {};
    var look = s.status === 'available' ? { fill: z.fill, line: z.line } : LOOK[s.status];
    n.rect.setAttribute('fill', look.fill);
    n.rect.setAttribute('stroke', look.line);
    n.g.setAttribute('class', 'afpx-stand is-' + s.status + (s.id === selectedId ? ' is-sel' : '') + (matches(s) ? '' : ' is-dim'));
    var sub, fs = s.fs;
    if (s.status === 'sold' && s.name) {
      // Long company names get slightly smaller text before they're shortened.
      if (textWidth(s.name, fs) > s.w - 0.4) { fs = round3(s.fs * 0.8); }
      sub = fitText(s.name, s.w - 0.4, fs);
    } else if (s.status === 'sold') { sub = 'Sold'; }
    else if (s.status === 'hold') { sub = 'On hold'; }
    else { sub = sizeShort(s.size); }
    n.sub.setAttribute('font-size', fs);
    n.sub.textContent = sub;
    n.g.setAttribute('aria-label', standLabel(s));
  }

  // The list runs in stand-number order (B01, B02 ... S07), like the PDF schedule.
  var LIST_ORDER = STANDS.slice().sort(function (a, b) {
    var ma = /^([A-Z]*)(\d+)$/.exec(a.id) || [a.id, a.id, '0'], mb = /^([A-Z]*)(\d+)$/.exec(b.id) || [b.id, b.id, '0'];
    return ma[1] < mb[1] ? -1 : ma[1] > mb[1] ? 1 : Number(ma[2]) - Number(mb[2]);
  });

  function renderList() {
    var shown = LIST_ORDER.filter(matches);
    var available = STANDS.filter(function (s) { return s.status === 'available'; }).length;
    listEl.textContent = '';
    if (!shown.length) {
      listEl.appendChild(el('li', { className: 'afpx-empty', text: 'No stands match those filters.' }));
    }
    shown.forEach(function (s) {
      var title = s.status === 'sold' ? (s.name || 'Sold') : STATUS_LABEL[s.status];
      var right = (s.status === 'hold' || s.status === 'sold') ? STATUS_LABEL[s.status] : money(s.price);
      var btn = el('button', {
        type: 'button', className: 'afpx-item' + (s.id === selectedId ? ' is-sel' : ''),
        'data-id': s.id, 'aria-label': standLabel(s)
      }, [
        el('span', { className: 'afpx-dot', style: 'background:' + STATUS_DOT[s.status], 'aria-hidden': 'true' }),
        el('span', { className: 'afpx-no', text: s.id, 'aria-hidden': 'true' }),
        el('span', { className: 'afpx-mid', 'aria-hidden': 'true' }, [
          el('span', { className: 'afpx-nm', text: title }),
          el('span', { className: 'afpx-mt', text: sizeText(s.size) + ' · ' + s.sqm + ' m² · ' + openShort(s) })
        ]),
        el('span', { className: 'afpx-pr is-' + s.status, text: right, 'aria-hidden': 'true' })
      ]);
      listEl.appendChild(el('li', null, [btn]));
    });
    if (countEl) {
      var loading = STANDS.some(function (s) { return s.status === 'loading'; });
      countEl.textContent = 'Showing ' + shown.length + ' of ' + STANDS.length + ' stands' +
        (loading ? '' : ' · ' + available + ' still available');
    }
  }

  function renderStats() {
    var counts = { available: 0, hold: 0, sold: 0 };
    var known = true;
    STANDS.forEach(function (s) {
      if (counts[s.status] !== undefined) { counts[s.status]++; } else { known = false; }
    });
    Object.keys(counts).forEach(function (k) {
      var node = q('[data-stat="' + k + '"]');
      if (node) { node.textContent = known ? String(counts[k]) : '–'; }
    });
  }

  function refreshAll() {
    STANDS.forEach(paint);
    renderList();
    renderStats();
    if (openId && modal && !modal.hidden) { rebuildPopup(BY_ID[openId]); }
  }

  function setNotice(text) {
    if (!noticeEl) { return; }
    noticeEl.textContent = text || '';
    noticeEl.hidden = !text;
  }

  function select(id) {
    var prev = selectedId;
    selectedId = id;
    if (prev && BY_ID[prev]) { paint(BY_ID[prev]); }
    if (id && BY_ID[id]) { paint(BY_ID[id]); }
    Array.prototype.forEach.call(listEl.querySelectorAll('.afpx-item'), function (b) {
      b.classList.toggle('is-sel', b.getAttribute('data-id') === id);
    });
  }

  /* ------------------------------------------------------------------ */
  /* Popup                                                               */
  /* ------------------------------------------------------------------ */

  function mailtoHref(s) {
    var name = CFG.enquiryName ? 'Hi ' + CFG.enquiryName + ',' : 'Hello,';
    var subject = 'ATFA Expo 2027 stand enquiry: Stand ' + s.id;
    var body = name + '\n\nI\'d like to enquire about stand ' + s.id + ' (' + sizeText(s.size) + ', ' + s.sqm +
      ' m²) at the ATFA Expo & Conference 2027.\n\nName:\nCompany:\nPhone:\n\nThanks';
    return 'mailto:' + (CFG.enquiryEmail || '') + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
  }

  function enquiryHref(s) {
    if (CFG.enquiryUrl) {
      return CFG.enquiryUrl + (CFG.enquiryUrl.indexOf('?') > -1 ? '&' : '?') + 'stand=' + encodeURIComponent(s.id);
    }
    return mailtoHref(s);
  }

  function badgeText(s) {
    return { available: 'Available', hold: 'On hold', sold: 'Exhibitor', loading: 'Checking', unknown: 'Not confirmed' }[s.status];
  }

  function buildPopup(s) {
    if (!mBody || !s) { return; }
    mBody.textContent = '';
    var hasName = s.status === 'sold' && s.name;
    var head = el('div', { className: 'afpx-mhead' }, [
      el('span', { className: 'afpx-badge is-' + s.status, text: badgeText(s) }),
      el('h2', { id: 'afpx-mtitle', text: hasName ? s.name : 'Stand ' + s.id }),
      el('p', { className: 'afpx-meta', text: (hasName ? 'Stand ' + s.id + ' · ' : '') + sizeText(s.size) + ' · ' + s.sqm + ' m²' }),
      el('p', { className: 'afpx-open', text: openText(s) })
    ]);
    var body = el('div', { className: 'afpx-mbody' });
    var foot = el('div', { className: 'afpx-mfoot' });

    if (s.status === 'available' || s.status === 'loading' || s.status === 'unknown') {
      var disc = (GEO.sizes[s.size] && GEO.sizes[s.size].disc) || 0;
      body.appendChild(el('p', { className: 'afpx-price', text: money(s.price) }));
      body.appendChild(el('p', {
        className: 'afpx-price-note',
        text: (CFG.currency || 'AUD') + ' ' + priceNote() + ' · ' + money2(s.price / s.sqm) + ' per m²' +
          (disc ? ' · includes a ' + Math.round(disc * 100) + '% size saving' : '')
      }));
      if (s.status === 'loading') {
        body.appendChild(el('p', { className: 'afpx-warn', text: 'Checking live availability…' }));
      } else if (s.status === 'unknown') {
        body.appendChild(el('p', {
          className: 'afpx-warn',
          text: 'We couldn’t confirm live availability just now. Send an enquiry and we’ll check this stand for you.'
        }));
      }
      foot.appendChild(el('a', { className: 'afpx-btn', href: enquiryHref(s), text: 'Enquire about stand ' + s.id }));
      if (CFG.enquiryEmail) {
        foot.appendChild(el('p', { className: 'afpx-alt' }, ['Or email ', el('a', { href: mailtoHref(s), text: CFG.enquiryEmail })]));
      }
    } else if (s.status === 'hold') {
      body.appendChild(el('p', {
        text: 'This stand is on hold at the moment. Ask us and we’ll let you know if it becomes available.'
      }));
      foot.appendChild(el('a', { className: 'afpx-btn', href: enquiryHref(s), text: 'Ask about stand ' + s.id }));
    } else {
      if (s.bio) {
        s.bio.split(/\n{2,}/).forEach(function (para) {
          body.appendChild(el('p', { className: 'afpx-bio', text: para }));
        });
      } else {
        body.appendChild(el('p', { className: 'afpx-muted', text: 'Find them at stand ' + s.id + ' at the ATFA Expo & Conference 2027.' }));
      }
      if (s.url) {
        foot.appendChild(el('a', { className: 'afpx-btn', href: s.url, target: '_blank', rel: 'noopener noreferrer', text: 'Visit website' }));
        foot.appendChild(el('span', { className: 'afpx-site', text: domainOf(s.url, s.urlTyped) }));
      }
    }
    foot.appendChild(el('button', { type: 'button', className: 'afpx-btn is-ghost', 'data-close': true, text: 'Close' }));
    mBody.appendChild(head);
    mBody.appendChild(body);
    mBody.appendChild(foot);
  }

  // Rebuild the open popup (fresh data arrived) without losing the keyboard focus.
  function rebuildPopup(s) {
    var active = document.activeElement;
    var focusables = function () { return Array.prototype.slice.call(mBody.querySelectorAll('a[href], button')); };
    var index = mBody && active && mBody.contains(active) ? focusables().indexOf(active) : -1;
    buildPopup(s);
    if (index > -1) {
      var again = focusables()[index];
      focusNoScroll(again || modal.querySelector('.afpx-x') || mCard);
    }
  }

  var lastFocus = null, lastFocusId = null, lastFocusList = false;
  var savedStyles = null;

  // Stop the page scrolling behind the popup. The scrollbar's width is added back
  // as padding so the page doesn't jump sideways when the scrollbar disappears.
  function lockScroll(on) {
    var html = document.documentElement, body = document.body;
    if (on) {
      if (savedStyles) { return; }
      var before = body.getBoundingClientRect().width;
      savedStyles = [html.style.overflow, body.style.overflow, body.style.paddingRight];
      html.style.overflow = 'hidden';
      body.style.overflow = 'hidden';
      // Only pad by how much wider the page actually got (nothing, if the theme keeps
      // a fixed scrollbar gutter).
      var bar = Math.max(0, Math.round(body.getBoundingClientRect().width - before));
      if (bar) {
        var pad = parseFloat(window.getComputedStyle(body).paddingRight) || 0;
        body.style.paddingRight = (pad + bar) + 'px';
      }
    } else if (savedStyles) {
      html.style.overflow = savedStyles[0];
      body.style.overflow = savedStyles[1];
      body.style.paddingRight = savedStyles[2];
      savedStyles = null;
    }
  }

  function focusNoScroll(node) {
    if (!node || !node.focus) { return; }
    try { node.focus({ preventScroll: true }); } catch (e) { node.focus(); }
  }

  function openPopup(id, opener) {
    var s = BY_ID[id];
    if (!s || !modal) { return; }
    select(id);
    openId = id;
    lastFocus = opener || document.activeElement;
    lastFocusId = id;
    lastFocusList = !!(lastFocus && lastFocus.classList && lastFocus.classList.contains('afpx-item'));
    buildPopup(s);
    if (mCard) { mCard.style.transform = ''; mCard.scrollTop = 0; }
    modal.hidden = false;
    lockScroll(true);
    focusNoScroll(modal.querySelector('.afpx-x') || mCard);
    hideTip();
  }

  function closePopup() {
    if (!modal || modal.hidden) { return; }
    modal.hidden = true;
    openId = null;
    lockScroll(false);
    var back = lastFocus;
    if (!back || !document.documentElement.contains(back)) {
      // The list was redrawn while the popup was open: go back to the same stand.
      back = (lastFocusList && listEl.querySelector('.afpx-item[data-id="' + lastFocusId + '"]')) ||
        (NODES[lastFocusId] && NODES[lastFocusId].g) || null;
    }
    if (back) { focusNoScroll(back); }
  }

  /* Phones: drag the bottom sheet down to close it. */
  if (mCard) {
    var sheet = null;
    var isSheet = function () { return window.matchMedia && window.matchMedia('(max-width: 600px)').matches; };
    mCard.addEventListener('touchstart', function (e) {
      if (e.touches.length !== 1 || !isSheet() || mCard.scrollTop > 0) { sheet = null; return; }
      sheet = { y: e.touches[0].clientY, x: e.touches[0].clientX, t: Date.now(), dy: 0, on: false };
    }, { passive: true });
    mCard.addEventListener('touchmove', function (e) {
      if (!sheet || e.touches.length !== 1) { return; }
      var dy = e.touches[0].clientY - sheet.y, dx = e.touches[0].clientX - sheet.x;
      if (!sheet.on) {
        if (dy < 8 || Math.abs(dx) > dy) { if (Math.abs(dx) > 8 || dy < -8) { sheet = null; } return; }
        sheet.on = true;
        mCard.style.transition = 'none';
      }
      if (e.cancelable) { e.preventDefault(); }
      sheet.dy = Math.max(0, dy);
      mCard.style.transform = 'translateY(' + sheet.dy + 'px)';
    }, { passive: false });
    var endSheet = function () {
      if (!sheet) { return; }
      var s = sheet;
      sheet = null;
      if (!s.on) { return; }
      var fast = s.dy > 40 && s.dy / Math.max(1, Date.now() - s.t) > 0.6;
      mCard.style.transition = 'transform .18s ease-out';
      if (s.dy > Math.min(140, mCard.offsetHeight * 0.3) || fast) {
        mCard.style.transform = 'translateY(100%)';
        setTimeout(function () { closePopup(); mCard.style.transition = ''; mCard.style.transform = ''; }, 170);
      } else {
        mCard.style.transform = '';
        setTimeout(function () { mCard.style.transition = ''; }, 200);
      }
    };
    mCard.addEventListener('touchend', endSheet);
    mCard.addEventListener('touchcancel', endSheet);
  }

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target.closest && e.target.closest('[data-close]')) { closePopup(); }
    });
    modal.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab') { return; }
      var items = Array.prototype.filter.call(
        modal.querySelectorAll('a[href], button, [tabindex]:not([tabindex="-1"])'),
        function (n) { return n.offsetParent !== null; }
      );
      if (!items.length) { return; }
      var first = items[0], last = items[items.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
  }
  document.addEventListener('keydown', function (e) {
    if ((e.key === 'Escape' || e.key === 'Esc') && modal && !modal.hidden) { closePopup(); }
  });

  /* ------------------------------------------------------------------ */
  /* View: fit, zoom and pan                                             */
  /* ------------------------------------------------------------------ */

  var view = null;
  var region = FULL;
  var wasNarrow = null;

  function stageSize() {
    var r = stage.getBoundingClientRect();
    return { w: Math.max(1, r.width), h: Math.max(1, r.height) };
  }

  // Expand a region so its shape matches the stage, so nothing is letterboxed.
  function fit(reg) {
    var sz = stageSize(), ratio = sz.w / sz.h, w = reg.w, h = reg.h;
    if (w / h > ratio) { h = w / ratio; } else { w = h * ratio; }
    return { x: reg.x + (reg.w - w) / 2, y: reg.y + (reg.h - h) / 2, w: w, h: h };
  }

  function clampView(v) {
    var max = fit(FULL);
    if (v.w > max.w) { v.h = v.h * (max.w / v.w); v.w = max.w; }
    var minW = Math.min(12, max.w);
    if (v.w < minW) { v.h = v.h * (minW / v.w); v.w = minW; }
    var cx = v.x + v.w / 2, cy = v.y + v.h / 2;
    var loX = FULL.x + v.w / 2, hiX = FULL.x + FULL.w - v.w / 2;
    var loY = FULL.y + v.h / 2, hiY = FULL.y + FULL.h - v.h / 2;
    cx = loX > hiX ? FULL.x + FULL.w / 2 : clamp(cx, loX, hiX);
    cy = loY > hiY ? FULL.y + FULL.h / 2 : clamp(cy, loY, hiY);
    v.x = cx - v.w / 2;
    v.y = cy - v.h / 2;
    return v;
  }

  function applyView() {
    svg.setAttribute('viewBox', [view.x, view.y, view.w, view.h].map(round3).join(' '));
    placeLabels(stageSize().w / view.w);
  }

  function resetView() {
    view = clampView(fit(region));
    applyView();
  }

  function zoomAt(factor, px, py) {
    var v = { x: px - (px - view.x) * factor, y: py - (py - view.y) * factor, w: view.w * factor, h: view.h * factor };
    view = clampView(v);
    applyView();
  }

  function zoomCentre(factor) { zoomAt(factor, view.x + view.w / 2, view.y + view.h / 2); }

  function toSvg(clientX, clientY) {
    var r = svg.getBoundingClientRect();
    return { x: view.x + (clientX - r.left) / r.width * view.w, y: view.y + (clientY - r.top) / r.height * view.h };
  }

  function ensureVisible(s) {
    var m = 1.5;
    if (s.x - m < view.x || s.y - m < view.y || s.x + s.w + m > view.x + view.w || s.y + s.h + m > view.y + view.h) {
      view = clampView({ x: s.x + s.w / 2 - view.w / 2, y: s.y + s.h / 2 - view.h / 2, w: view.w, h: view.h });
      applyView();
    }
  }

  var hasAspect = !!(window.CSS && CSS.supports && CSS.supports('aspect-ratio', '1 / 1'));

  // Tallest the plan can be on this screen (its CSS max-height), in pixels.
  function maxStageHeight() {
    var v = parseFloat(window.getComputedStyle(stage).maxHeight);
    return isFinite(v) && v > 0 ? v : window.innerHeight * 0.75;
  }

  function layout() {
    // Narrow or short screens (phones, landscape phones, small laptops) start on the
    // hall itself; roomy screens show the breakout and catering areas around it too.
    var narrow = root.getBoundingClientRect().width < NARROW_PX || maxStageHeight() < SHORT_PX;
    region = narrow ? FOCUS : FULL;
    if (hasAspect) {
      stage.style.aspectRatio = region.w + ' / ' + region.h;
    } else {
      // Older browsers: set the height by hand.
      stage.style.height = Math.round(Math.min(stage.getBoundingClientRect().width * region.h / region.w, maxStageHeight())) + 'px';
    }
    if (!view || narrow !== wasNarrow) {
      wasNarrow = narrow;
      resetView();
      return;
    }
    // Same breakpoint: keep the current centre and zoom, re-shaped to the stage.
    var sz = stageSize(), cx = view.x + view.w / 2, cy = view.y + view.h / 2, w = view.w, h = w / (sz.w / sz.h);
    view = clampView({ x: cx - w / 2, y: cy - h / 2, w: w, h: h });
    applyView();
  }

  var layoutQueued = false;
  function queueLayout() {
    if (layoutQueued) { return; }
    layoutQueued = true;
    var run = function () { layoutQueued = false; layout(); };
    if (window.requestAnimationFrame) { window.requestAnimationFrame(run); } else { setTimeout(run, 16); }
  }
  if (window.ResizeObserver) { new ResizeObserver(queueLayout).observe(stage); }
  window.addEventListener('resize', queueLayout);
  layout();

  /* Zoom buttons */
  q('.afpx-toolbar').addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('button') : null;
    if (!btn) { return; }
    var z = btn.getAttribute('data-zoom');
    if (z === 'in') { zoomCentre(0.72); return; }
    if (z === 'out') { zoomCentre(1 / 0.72); return; }
    if (z === 'reset') { resetView(); return; }
    if (btn.classList.contains('afpx-chip')) {
      var on = btn.getAttribute('aria-pressed') !== 'true';
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      var size = btn.getAttribute('data-size');
      if (size) { filters.sizes[size] = on; }
      if (btn.getAttribute('data-filter') === 'available') { filters.availOnly = on; }
      STANDS.forEach(paint);
      renderList();
    }
  });

  /* Hints (each kind is shown a few times at most, so they don't nag) */
  var hintTimer = null, lastHintAt = 0, hintCounts = {};
  function showHint(text, kind) {
    var now = Date.now();
    if (!hint || now - lastHintAt < 2500) { return; }
    kind = kind || text;
    hintCounts[kind] = (hintCounts[kind] || 0) + 1;
    if (hintCounts[kind] > 3) { return; }
    lastHintAt = now;
    hint.textContent = text;
    hint.classList.add('is-on');
    clearTimeout(hintTimer);
    hintTimer = setTimeout(function () { hint.classList.remove('is-on'); }, 1700);
  }

  /* Mouse wheel and trackpad: zoom only with Ctrl/Cmd (trackpad pinch sends
     Ctrl automatically), so normal scrolling still scrolls the page. */
  stage.addEventListener('wheel', function (e) {
    if (!(e.ctrlKey || e.metaKey)) {
      showHint((isMac ? 'Hold ⌘' : 'Hold Ctrl') + ' and scroll to zoom the plan', 'wheel');
      return;
    }
    e.preventDefault();
    var dy = e.deltaY * (e.deltaMode === 1 ? 16 : e.deltaMode === 2 ? 400 : 1);
    var factor = clamp(Math.pow(1.0018, dy), 0.5, 2);
    var p = toSvg(e.clientX, e.clientY);
    zoomAt(factor, p.x, p.y);
  }, { passive: false });

  /* Safari trackpad pinch (desktop) */
  var sawTouch = false, gesture = null;
  stage.addEventListener('gesturestart', function (e) {
    if (sawTouch) { return; }
    e.preventDefault();
    gesture = { view: Object.assign({}, view), p: toSvg(e.clientX, e.clientY) };
  });
  stage.addEventListener('gesturechange', function (e) {
    if (sawTouch || !gesture) { return; }
    e.preventDefault();
    var f = 1 / (e.scale || 1), g = gesture;
    view = clampView({
      x: g.p.x - (g.p.x - g.view.x) * f, y: g.p.y - (g.p.y - g.view.y) * f, w: g.view.w * f, h: g.view.h * f
    });
    applyView();
  });
  stage.addEventListener('gestureend', function () { gesture = null; });

  /* Touch: one finger scrolls the page as normal; two fingers move and zoom the plan. */
  var pinch = null;
  function touchPoint(t) { return { x: t.clientX, y: t.clientY }; }
  function startPinch(touches) {
    var a = touchPoint(touches[0]), b = touchPoint(touches[1]);
    var mid = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
    pinch = { view: Object.assign({}, view), dist: Math.max(10, Math.hypot(a.x - b.x, a.y - b.y)), p: toSvg(mid.x, mid.y) };
  }
  stage.addEventListener('touchstart', function (e) {
    sawTouch = true;
    if (e.touches.length === 2) { startPinch(e.touches); }
  }, { passive: true });
  stage.addEventListener('touchmove', function (e) {
    if (e.touches.length === 2) {
      if (!pinch) { startPinch(e.touches); }
      if (e.cancelable) { e.preventDefault(); }
      var a = touchPoint(e.touches[0]), b = touchPoint(e.touches[1]);
      var dist = Math.max(10, Math.hypot(a.x - b.x, a.y - b.y));
      var mid = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
      var f = pinch.dist / dist;
      var w = pinch.view.w * f, h = pinch.view.h * f;
      var r = svg.getBoundingClientRect();
      view = clampView({
        x: pinch.p.x - (mid.x - r.left) / r.width * w,
        y: pinch.p.y - (mid.y - r.top) / r.height * h,
        w: w, h: h
      });
      applyView();
      hideTip();
    } else if (e.touches.length === 1 && !pinch) {
      showHint('Use two fingers to move and zoom the plan', 'touch');
    }
  }, { passive: false });
  function endTouch(e) { if (!e.touches || e.touches.length < 2) { pinch = null; } }
  stage.addEventListener('touchend', endTouch);
  stage.addEventListener('touchcancel', endTouch);

  /* Mouse drag to pan */
  // A drag ends with a click on whatever is under the mouse; that one click is ignored.
  // Any new press starts afresh, so a real click straight after a drag still works.
  var drag = null, ignoreClick = false, ignoreTimer = null;
  var lastPointer = 'mouse';
  stage.addEventListener('pointerdown', function (e) {
    ignoreClick = false;
    lastPointer = e.pointerType || 'mouse';
    if (e.pointerType === 'touch' || e.button !== 0) { return; }
    drag = { id: e.pointerId, x: e.clientX, y: e.clientY, view: Object.assign({}, view), moved: false };
  });
  stage.addEventListener('pointermove', function (e) {
    if (!drag || e.pointerId !== drag.id) { return; }
    var dx = e.clientX - drag.x, dy = e.clientY - drag.y;
    if (!drag.moved) {
      if (Math.abs(dx) + Math.abs(dy) < 5) { return; }
      drag.moved = true;
      try { stage.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
      stage.classList.add('is-drag');
      hideTip();
    }
    var r = svg.getBoundingClientRect();
    view = clampView({
      x: drag.view.x - dx / r.width * drag.view.w,
      y: drag.view.y - dy / r.height * drag.view.h,
      w: drag.view.w, h: drag.view.h
    });
    applyView();
  });
  function endDrag(e) {
    if (!drag || (e && e.pointerId !== drag.id)) { return; }
    if (drag.moved) {
      ignoreClick = true;
      clearTimeout(ignoreTimer);
      ignoreTimer = setTimeout(function () { ignoreClick = false; }, 300);
    }
    drag = null;
    stage.classList.remove('is-drag');
  }
  stage.addEventListener('pointerup', endDrag);
  stage.addEventListener('pointercancel', endDrag);

  /* Hover tooltip (mouse only) */
  var tipId = null;
  function hideTip() { if (tip) { tip.classList.remove('is-on'); } tipId = null; }
  function tipStatus(s) {
    if (s.status === 'available') { return money(s.price) + ' ' + priceNote(); }
    if (s.status === 'hold') { return 'On hold'; }
    if (s.status === 'sold') { return s.name || 'Sold'; }
    return money(s.price) + ' ' + priceNote();
  }
  svg.addEventListener('pointermove', function (e) {
    if (!tip || e.pointerType !== 'mouse' || (drag && drag.moved)) { return; }
    var g = e.target.closest ? e.target.closest('.afpx-stand') : null;
    if (!g) { hideTip(); return; }
    var s = BY_ID[g.getAttribute('data-id')];
    if (tipId !== s.id) {
      tipId = s.id;
      tip.textContent = '';
      tip.appendChild(el('strong', { text: 'Stand ' + s.id }));
      tip.appendChild(el('span', { text: sizeText(s.size) + ' · ' + s.sqm + ' m² · ' + openShort(s) }));
      tip.appendChild(el('span', { className: 'afpx-tip-st is-' + s.status, text: tipStatus(s) }));
    }
    var sr = stage.getBoundingClientRect();
    var x = e.clientX - sr.left + 14, y = e.clientY - sr.top + 14;
    var tw = tip.offsetWidth || 200, th = tip.offsetHeight || 70;
    if (x + tw > sr.width - 6) { x = e.clientX - sr.left - tw - 12; }
    if (y + th > sr.height - 6) { y = e.clientY - sr.top - th - 12; }
    tip.style.left = Math.max(6, x) + 'px';
    tip.style.top = Math.max(6, y) + 'px';
    tip.classList.add('is-on');
  });
  svg.addEventListener('pointerleave', hideTip);

  /* ------------------------------------------------------------------ */
  /* Selecting stands: map, keyboard and list                            */
  /* ------------------------------------------------------------------ */

  function setRoving(id) {
    STANDS.forEach(function (s) { NODES[s.id].g.setAttribute('tabindex', s.id === id ? 0 : -1); });
  }

  // Arrow keys: the nearest stand in that direction, preferring ones within 45
  // degrees of straight ahead and never more than about 68 degrees off, so Right
  // doesn't jump to a stand that's mostly above.
  function neighbour(s, key) {
    var cx = s.x + s.w / 2, cy = s.y + s.h / 2, best = null, bestScore = Infinity, inCone = false;
    STANDS.forEach(function (o) {
      if (o === s) { return; }
      var dx = o.x + o.w / 2 - cx, dy = o.y + o.h / 2 - cy, along, across;
      if (key === 'ArrowRight') { along = dx; across = dy; }
      else if (key === 'ArrowLeft') { along = -dx; across = dy; }
      else if (key === 'ArrowDown') { along = dy; across = dx; }
      else { along = -dy; across = dx; }
      if (along <= 0.5 || Math.abs(across) > along * 2.5) { return; }
      var cone = Math.abs(across) <= along;
      if (inCone && !cone) { return; }
      var score = along + Math.abs(across) * 2.5;
      if ((cone && !inCone) || score < bestScore) { bestScore = score; best = o; inCone = inCone || cone; }
    });
    return best;
  }

  svg.addEventListener('click', function (e) {
    // The click that ends a drag is not a stand selection.
    if (ignoreClick) { ignoreClick = false; return; }
    var g = e.target.closest ? e.target.closest('.afpx-stand') : null;
    // Fingers are less precise than a mouse: a tap just outside a stand still counts.
    if (!g && lastPointer === 'touch') { g = standNear(e.clientX, e.clientY, 10); }
    if (!g) { return; }
    openPopup(g.getAttribute('data-id'), g);
  });

  // The stand within px screen pixels of a tap, as long as the tap is clearly nearer
  // that stand than any other (a tap in the middle of an aisle opens nothing).
  function standNear(clientX, clientY, px) {
    var p = toSvg(clientX, clientY), tol = px * view.w / stageSize().w, best = null, bestD = Infinity, nextD = Infinity;
    STANDS.forEach(function (s) {
      var dx = Math.max(s.x - p.x, 0, p.x - (s.x + s.w)), dy = Math.max(s.y - p.y, 0, p.y - (s.y + s.h));
      var d = Math.sqrt(dx * dx + dy * dy);
      if (d < bestD) { nextD = bestD; bestD = d; best = s; } else if (d < nextD) { nextD = d; }
    });
    return best && bestD <= tol && bestD < nextD * 0.6 ? NODES[best.id].g : null;
  }

  svg.addEventListener('keydown', function (e) {
    ignoreClick = false;
    var g = e.target.closest ? e.target.closest('.afpx-stand') : null;
    if (!g) { return; }
    var s = BY_ID[g.getAttribute('data-id')];
    if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
      e.preventDefault();
      openPopup(s.id, g);
    } else if (/^Arrow(Up|Down|Left|Right)$/.test(e.key)) {
      e.preventDefault();
      var next = neighbour(s, e.key);
      if (next) { setRoving(next.id); focusNoScroll(NODES[next.id].g); }
    }
  });

  svg.addEventListener('focusin', function (e) {
    var g = e.target.closest ? e.target.closest('.afpx-stand') : null;
    if (!g) { return; }
    var s = BY_ID[g.getAttribute('data-id')];
    setRoving(s.id);
    ensureVisible(s);
  });

  listEl.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('.afpx-item') : null;
    if (!btn) { return; }
    var s = BY_ID[btn.getAttribute('data-id')];
    if (!s) { return; }
    ensureVisible(s);
    setRoving(s.id);
    openPopup(s.id, btn);
  });

  if (searchEl) {
    searchEl.addEventListener('input', function () {
      filters.q = searchEl.value.trim();
      STANDS.forEach(paint);
      renderList();
    });
  }

  /* ------------------------------------------------------------------ */
  /* Loading stand data                                                  */
  /* ------------------------------------------------------------------ */

  function applyRows(rows) {
    var map = {};
    (rows || []).forEach(function (r) {
      var key = r && r.id !== undefined && r.id !== null ? idKey(r.id) : '';
      if (key) { map[key] = r; }
    });
    STANDS.forEach(function (s) {
      var r = map[s.key];
      var status = r ? normStatus(r.status) : 'available';
      s.status = status;
      s.name = status === 'sold' ? cleanText(r.name, 120, false) : '';
      s.url = status === 'sold' ? safeUrl(r.url) : '';
      s.urlTyped = s.url ? cleanText(r.url, 200, false) : '';
      s.bio = status === 'sold' ? cleanText(r.bio, 1200, true) : '';
    });
    refreshAll();
  }

  // Data only counts if it mentions at least one stand on this plan. That stops an
  // old copy (or a sheet still using the previous stand numbers) from showing every
  // stand as available.
  function usable(rows) {
    return (rows || []).some(function (r) {
      var key = r && r.id !== undefined && r.id !== null ? idKey(r.id) : '';
      return key !== '' && STANDS.some(function (s) { return s.key === key; });
    });
  }

  function markUnknown() {
    STANDS.forEach(function (s) { s.status = 'unknown'; s.name = ''; s.url = ''; s.urlTyped = ''; s.bio = ''; });
    refreshAll();
  }

  function asOf(iso) {
    if (!iso) { return 'Availability may be out of date.'; }
    var d = new Date(iso);
    if (isNaN(d.getTime())) { return 'Availability may be out of date.'; }
    var text;
    try {
      text = d.toLocaleString('en-AU', {
        timeZone: 'Australia/Sydney', day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit'
      });
    } catch (e) {
      text = d.toLocaleString();
    }
    return 'Showing availability as of ' + text + '. Live updates couldn’t be loaded just now.';
  }

  function getWithTimeout(url, ms) {
    var ctrl = window.AbortController ? new AbortController() : null;
    var timer = setTimeout(function () { if (ctrl) { ctrl.abort(); } }, ms);
    return fetch(url, { credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined }).then(
      function (res) { clearTimeout(timer); if (!res.ok) { throw new Error('HTTP ' + res.status); } return res; },
      function (err) { clearTimeout(timer); throw err; }
    );
  }

  function newer(a, b) {
    var ta = a ? Date.parse(a) : 0, tb = b ? Date.parse(b) : 0;
    return (ta || 0) > (tb || 0);
  }

  function load(first) {
    var hadData = !first && lastUpdated !== null, liveDone = false;
    if (first && SNAP && usable(SNAP.stands)) {
      applyRows(SNAP.stands);
      lastUpdated = SNAP.updated || null;
      hadData = true;
    } else if (first) {
      refreshAll();
    }
    if (!window.fetch || !CFG.restUrl) {
      if (!hadData) { markUnknown(); }
      return;
    }
    // 1. The fast static copy (usually only a minute or two old).
    if (CFG.staticUrl) {
      getWithTimeout(CFG.staticUrl + (CFG.staticUrl.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now(), 5000)
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (liveDone || !data || !usable(data.stands)) { return; }
          if (hadData && !newer(data.updated, lastUpdated)) { return; }
          applyRows(data.stands);
          lastUpdated = data.updated || lastUpdated;
          hadData = true;
        })
        .catch(function () { /* the live endpoint below covers this */ });
    }
    // 2. The live endpoint (refreshes from the sheet when its copy is over two minutes old).
    getWithTimeout(CFG.restUrl, 15000)
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || !usable(data.stands)) { throw new Error('No stand data'); }
        liveDone = true;
        applyRows(data.stands);
        lastUpdated = data.updated || lastUpdated;
        hadData = true;
        setNotice(data.source === 'stale' ? asOf(data.updated) : '');
      })
      .catch(function () {
        if (!CFG.csvUrl) { throw new Error('No sheet link'); }
        return getWithTimeout(CFG.csvUrl, 10000)
          .then(function (res) { return res.text(); })
          .then(function (text) {
            var rows = rowsFromCSV(text);
            if (!usable(rows)) { throw new Error('No stand rows'); }
            applyRows(rows);
            lastUpdated = new Date().toISOString();
            setNotice('');
          });
      })
      .catch(function () {
        if (hadData) {
          setNotice(asOf(lastUpdated));
        } else {
          markUnknown();
          setNotice('Live availability couldn’t be loaded just now. Prices are shown and you can still enquire about any stand.');
        }
      });
  }

  // Small hook for testing and for staff checking the plan from the console.
  window.ATFAExpo27 = {
    version: CFG.version || '__VERSION__',
    reload: function () { load(false); },
    stands: function () {
      return STANDS.map(function (s) { return { id: s.id, status: s.status, name: s.name, url: s.url, open: s.open }; });
    }
  };

  load(true);
})();
