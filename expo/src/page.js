/*!
 * ATFA Expo & Conference 2027 page: Hall 5 floor plan (dark), live stand data and the
 * full-screen plan for phones. Version __VERSION__
 *
 * How it works
 *   1. Draws Hall 5 from the geometry below, in the dark awards style.
 *   2. Shows the stand data the server already had (the "snapshot"), then fresh data from
 *      the fast static copy and the WordPress endpoint (a cached copy of the Website tab of
 *      the "2027 Exhibitor List" sheet), then the published CSV if WordPress can't answer.
 *   3. Wide screens: the plan sits in the page with a side panel. Narrow screens: the card
 *      opens a full-screen plan (zoom to a stand, size filters, list) that the phone's back
 *      button closes.
 *
 * Everything is built with DOM methods (no innerHTML), so anything typed into the sheet is
 * always shown as plain text and can never run as code.
 */
(function () {
  'use strict';

  var root = document.getElementById('xp-main');
  if (!root || root.getAttribute('data-ready') === '1') { return; }
  root.setAttribute('data-ready', '1');

  /* ------------------------------------------------------------------ */
  /* Data and settings                                                   */
  /* ------------------------------------------------------------------ */

  var GEO = __GEOMETRY__;
  var CFG = readJSON('atfa-expo27-page-config') || {};
  var SNAP = readJSON('atfa-expo27-page-snapshot');
  var SIZES = GEO.sizes;
  var SIZE_KEYS = Object.keys(SIZES).filter(function (k) {
    return GEO.stands.some(function (s) { return s.size === k; });
  });
  // Friday 21 May 2027, midnight in Sydney.
  var EXPO_DAY = Date.UTC(2027, 4, 20, 14, 0, 0);

  // The whole drawing (wide screens) and the stands, entrances and stage (narrow screens).
  var FULL = GEO.view;
  var CROP = { x: 3, y: -3, w: 55, h: 56 };
  var LIMIT = { x: 1, y: -5, w: 59, h: 60 };
  var SMALL_PRINT_PX = 7.5;
  var REDUCED = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

  var LOOK = {
    hold:    { fill: '#3B2F1D', line: '#D99A21', lbl: '#F6DFAE', sub: '#F0C36A' },
    sold:    { fill: '#34302B', line: '#6F665D', lbl: '#E6DFD4', sub: '#C3B8A9' },
    loading: { fill: '#26221E', line: '#4A4038', lbl: '#B4A99A', sub: '#8F857A' },
    unknown: { fill: '#26221E', line: '#4A4038', lbl: '#B4A99A', sub: '#8F857A' }
  };
  var STATUS_LABEL = {
    available: 'Available', hold: 'On hold', sold: 'Sold', loading: 'Checking availability', unknown: 'Not confirmed'
  };
  var BADGE = { available: 'Available', hold: 'On hold', sold: 'Exhibitor', loading: 'Checking', unknown: 'Not confirmed' };

  var STANDS = GEO.stands.map(function (s) {
    var big = Math.min(s.w, s.h) >= 6;
    return {
      id: String(s.id), key: idKey(s.id), size: s.size, x: s.x, y: s.y, w: s.w, h: s.h, sqm: s.sqm, price: s.price,
      status: 'loading', name: '', url: '', urlTyped: '', bio: '', sides: null, open: 0,
      fs: big ? 0.9 : 0.66
    };
  });
  var BY_ID = {};
  STANDS.forEach(function (s) { BY_ID[s.id] = s; });
  STANDS.forEach(function (s) {
    s.sides = sidesOf(s);
    s.open = ['N', 'E', 'S', 'W'].filter(function (k) { return s.sides[k]; }).length;
  });
  var LIST_ORDER = STANDS.slice().sort(function (a, b) {
    var ma = /^([A-Z]*)(\d+)$/.exec(a.id) || [a.id, a.id, '0'], mb = /^([A-Z]*)(\d+)$/.exec(b.id) || [b.id, b.id, '0'];
    return ma[1] < mb[1] ? -1 : ma[1] > mb[1] ? 1 : Number(ma[2]) - Number(mb[2]);
  });
  var MIN_PRICE = Math.min.apply(null, STANDS.map(function (s) { return s.price; }));

  var filt = { size: '', avail: false };
  var selId = null;
  var lastUpdated = null;
  var known = false;

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

  // In Barlow Condensed the letter O and the figure 0 are almost the same shape, so stand O03 read as 003.
  // Big IDs set their series letter in Montserrat instead (.xp-idl).
  function idNodes(id) {
    var m = /^([A-Za-z]+)(.*)$/.exec(String(id));
    return m ? [el('span', { className: 'xp-idl', text: m[1] }), m[2]] : [String(id)];
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

  function icon(paths, size, width) {
    var s = sv('svg', {
      width: size || 18, height: size || 18, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
      'stroke-width': width || 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'aria-hidden': 'true', focusable: 'false'
    });
    paths.forEach(function (d) { sv('path', { d: d }, s); });
    return s;
  }
  var ICON = {
    close: ['M6 6l12 12M18 6L6 18'],
    arrow: ['M5 12h14', 'M12 5l7 7-7 7'],
    left: ['M15 18l-6-6 6-6'],
    right: ['M9 6l6 6-6 6'],
    list: ['M8 6h13M8 12h13M8 18h13', 'M3.5 6h.01M3.5 12h.01M3.5 18h.01'],
    map: ['M9 4L3 6v14l6-2 6 2 6-2V4l-6 2-6-2z', 'M9 4v14', 'M15 6v14'],
    ext: ['M14 4h6v6', 'M20 4l-9 9', 'M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5'],
    down: ['M6 9l6 6 6-6']
  };

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

  // Every kind of space a spreadsheet can hold (the server treats them the same way).
  var ODD_SPACE = /[\s\u0085᠎​]+/g;

  // Stand numbers are matched loosely, so "s1", "S 01" and "Stand S01" in the
  // sheet all mean S01, and old-style numbers like 305 still work.
  function idKey(value) {
    var v = String(value === null || value === undefined ? '' : value).toUpperCase().replace(ODD_SPACE, '');
    v = v.replace(/^STAND/, '');
    var m = /^([A-Z]{0,3})0*(\d{1,4})$/.exec(v);
    return m ? m[1] + m[2] : '';
  }

  // Which sides of a stand face an aisle (no other stand against them).
  function sidesOf(s) {
    var e = 1e-6, out = {};
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
      });
      out[side[0]] = !blocked;
    });
    return out;
  }

  function openText(s) {
    if (s.open >= 4) { return 'Island: open on all four sides'; }
    return s.open === 1 ? '1 side open to the aisle' : s.open + ' sides open to the aisle';
  }
  function openShort(s) {
    if (s.open >= 4) { return 'Island'; }
    return s.open + (s.open === 1 ? ' side open' : ' sides open');
  }
  function sidesSpoken(s) {
    var names = { N: 'north', E: 'east', S: 'south', W: 'west' };
    var open = ['N', 'E', 'S', 'W'].filter(function (k) { return s.sides[k]; }).map(function (k) { return names[k]; });
    if (!open.length) { return 'No sides open to the aisle'; }
    if (open.length === 1) { return 'Open to the aisle on the ' + open[0] + ' side'; }
    return 'Open to the aisle on the ' + open.slice(0, -1).join(', ') + ' and ' + open[open.length - 1] + ' sides';
  }

  function priceNote() { return CFG.priceNote || 'excl. GST'; }
  function clamp(v, lo, hi) { return Math.min(Math.max(v, lo), hi); }
  function round3(n) { return Math.round(n * 1000) / 1000; }
  function sizeOk(s) { return !filt.size || s.size === filt.size; }
  function matches(s) { return sizeOk(s) && (!filt.avail || s.status === 'available'); }

  function savingText(s) {
    var z = SIZES[s.size] || {}, disc = z.disc || 0;
    var per = money2(s.price / s.sqm) + ' per m²';
    if (!disc) { return 'The base rate: ' + per + '.'; }
    var base = SIZES['3x3'] ? SIZES['3x3'].sqm : 9, n = Math.round(s.sqm / base);
    var words = { 2: 'two', 3: 'three', 4: 'four' };
    return 'Saves ' + Math.round(disc * 100) + '% against ' + (words[n] || n) + ' 3 × 3 m stands: ' + per + '.';
  }

  /* ------------------------------------------------------------------ */
  /* Cleaning data that came from the sheet                              */
  /* ------------------------------------------------------------------ */

  function normStatus(value) {
    var v = String(value === null || value === undefined ? '' : value).toLowerCase().replace(ODD_SPACE, ' ').trim();
    if (v === 'sold') { return 'sold'; }
    if (v === 'hold' || v === 'on hold' || v === 'held' || v === 'reserved') { return 'hold'; }
    return 'available';
  }

  function cleanText(value, max, multiline) {
    var v = String(value === null || value === undefined ? '' : value).replace(/\r\n?/g, '\n').replace(/\t/g, ' ');
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

  // Only http and https links. "www.example.com.au" becomes "https://www.example.com.au".
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

  function domainOf(url, typed) {
    var m = /^(?:[a-z][a-z0-9+.\-]*:\/\/)?([^\/?#:\s]+)/i.exec(String(typed || '').trim());
    if (m && m[1].indexOf('.') > 0) { return m[1].toLowerCase().replace(/^www\./, ''); }
    try { return new URL(url).hostname.replace(/^www\./, ''); } catch (e) { return ''; }
  }

  function parseCSV(text) {
    var rows = [], row = [], field = '', inQuotes = false, i, c;
    text = String(text || '').replace(/^﻿/, '');
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

  /* ------------------------------------------------------------------ */
  /* Drawing Hall 5 (one drawing per view: in the page, and full screen) */
  /* ------------------------------------------------------------------ */

  var W = GEO.hall.w, D = GEO.hall.d, P = GEO.paths;
  var LABEL_DARK = { '#9A8455': '#A89A82', '#4F6F8F': '#8FB0CF', '#A8600C': '#F8931F', '#7D8A84': '#8F857A' };

  function drawPlan(uid, vb, opts) {
    var svg = sv('svg', {
      'class': 'xp-svg', viewBox: [vb.x, vb.y, vb.w, vb.h].join(' '), focusable: 'false',
      preserveAspectRatio: 'xMidYMid meet'
    });
    if (opts.label) {
      svg.setAttribute('role', 'group');
      svg.setAttribute('aria-label', opts.label);
    } else {
      svg.setAttribute('aria-hidden', 'true');
    }

    var deco = sv('g', { 'aria-hidden': 'true' }, svg);
    var defs = sv('defs', {}, deco);
    var hatch = sv('pattern', {
      id: uid + 'Hatch', width: 1.8, height: 1.8, patternUnits: 'userSpaceOnUse', patternTransform: 'rotate(45)'
    }, defs);
    sv('rect', { width: 1.8, height: 1.8, fill: '#2A1C1A' }, hatch);
    sv('line', { x1: 0, y1: 0, x2: 0, y2: 1.8, stroke: '#4A2C28', 'stroke-width': 0.6 }, hatch);

    sv('path', { d: P.outer, fill: '#1F1B17' }, deco);
    sv('path', { d: GEO.radial, fill: 'none', stroke: '#4A4038', 'stroke-width': 0.22, 'stroke-dasharray': '1.6 1.1' }, deco);
    sv('path', { d: P.hall, fill: 'url(#' + uid + 'Hatch)' }, deco);
    sv('path', { d: P.lane, fill: '#151210' }, deco);
    var routeSlot = sv('g', {}, deco);
    sv('path', { d: P.demo, fill: '#2A1D11', stroke: '#F8931F', 'stroke-width': 0.3, 'stroke-dasharray': '2 1.3' }, deco);
    sv('path', { d: GEO.stagePath, fill: '#2F4A3D', stroke: '#0C0B0A', 'stroke-width': 0.2 }, deco);

    var stg = GEO.stage;
    var stx = stg.cx - 0.42 * stg.r, sty = stg.cy - 0.42 * stg.r;
    svText(deco, stx, sty, 'STAGE', { 'text-anchor': 'middle', 'font-size': 1.35, 'font-weight': 700, fill: '#fff' });
    svText(deco, stx, sty + 1.5, '5 × 5 m', { 'text-anchor': 'middle', 'font-size': 1.05, fill: '#BFD8C9' });

    GEO.seats.forEach(function (p) {
      sv('circle', { cx: p.x, cy: p.y, r: 0.42, fill: '#E9E1D5', stroke: '#5A6B62', 'stroke-width': 0.13 }, deco);
    });
    GEO.speakers.forEach(function (p) {
      sv('rect', { x: p.x - 0.55, y: p.y - 0.75, width: 1.1, height: 1.5, rx: 0.18, fill: '#B8761F', stroke: '#0C0B0A', 'stroke-width': 0.13 }, deco);
      sv('circle', { cx: p.x, cy: p.y - 0.22, r: 0.26, fill: '#fff' }, deco);
      sv('circle', { cx: p.x, cy: p.y + 0.42, r: 0.16, fill: '#fff' }, deco);
    });
    var mix = GEO.mix;
    sv('rect', { x: mix.x, y: mix.y, width: mix.w, height: mix.h, fill: '#5A6B62', stroke: '#0C0B0A', 'stroke-width': 0.13 }, deco);

    var R = GEO.route;
    if (R) {
      var clip = sv('clipPath', { id: uid + 'HallClip' }, defs);
      sv('path', { d: P.hall }, clip);
      var rdx = R.x2 - R.x1, rdy = R.y2 - R.y1, full = Math.sqrt(rdx * rdx + rdy * rdy);
      var ux = rdx / full, uy = rdy / full, hw = R.width / 2;
      var rlen = full - (R.stop || 0);
      var along = function (t, off) { return [round3(R.x1 + ux * t - uy * off), round3(R.y1 + uy * t + ux * off)]; };
      var routeG = sv('g', { 'clip-path': 'url(#' + uid + 'HallClip)' }, routeSlot);
      sv('polygon', {
        points: [along(-1, -hw), along(rlen, -hw), along(rlen, hw), along(-1, hw)].map(function (p) { return p.join(','); }).join(' '),
        fill: '#27221E'
      }, routeG);
      var routeText = 'MAIN PEDESTRIAN ROUTE · KEEP CLEAR';
      var mid = rlen / 2, gap = routeText.length * 0.29 + 1.2;
      var dash = { stroke: '#7A6E62', 'stroke-width': 0.16, 'stroke-dasharray': '1.1 0.8', fill: 'none' };
      [[2.2, mid - gap], [mid + gap, rlen - 2.2]].forEach(function (seg) {
        var a = along(seg[0], 0), b = along(seg[1], 0);
        sv('line', Object.assign({ x1: a[0], y1: a[1], x2: b[0], y2: b[1] }, dash), routeG);
      });
      [[1.2, -1], [rlen - 1.2, 1]].forEach(function (end) {
        var tip = along(end[0], 0), l = along(end[0] - end[1] * 1.1, -0.7), r = along(end[0] - end[1] * 1.1, 0.7);
        sv('polyline', {
          points: [l, tip, r].map(function (p) { return p.join(','); }).join(' '),
          fill: 'none', stroke: '#7A6E62', 'stroke-width': 0.2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round'
        }, routeG);
      });
      var lp = along(mid, 0.3), ang = Math.atan2(uy, ux) * 180 / Math.PI;
      svText(routeSlot, lp[0], lp[1], routeText, {
        'text-anchor': 'middle', 'font-size': 0.8, 'font-weight': 700, fill: '#93887B', 'letter-spacing': 0.08,
        transform: 'rotate(' + ang.toFixed(1) + ' ' + lp[0] + ' ' + lp[1] + ')'
      });
    }

    (GEO.labels || []).forEach(function (L) {
      var lines = String(L.text).split('\n');
      var color = LABEL_DARK[String(L.color).toUpperCase()] || L.color;
      var attrs = { 'text-anchor': 'middle', 'font-size': L.size, fill: color, 'letter-spacing': 0.06 };
      if (L.bold) { attrs['font-weight'] = 700; }
      if (L.rotate) { attrs.transform = 'rotate(' + L.rotate + ' ' + L.x + ' ' + L.y + ')'; }
      var t = svText(deco, L.x, L.y, '', attrs);
      lines.forEach(function (line, i) {
        var span = sv('tspan', { x: L.x, dy: i ? round3(L.size * 1.18) : 0 }, t);
        span.textContent = line;
      });
    });

    var standLayer = sv('g', { 'class': 'xp-stands' }, svg);
    var nodes = {};
    STANDS.forEach(function (s, i) {
      var g = sv('g', { 'class': 'xs is-loading', 'data-id': s.id }, standLayer);
      if (opts.keyboard) {
        g.setAttribute('role', 'button');
        g.setAttribute('tabindex', i === 0 ? 0 : -1);
      }
      var rect = sv('rect', { 'class': 'st', x: s.x, y: s.y, width: s.w, height: s.h, rx: 0.18, 'stroke-width': 0.16 }, g);
      var cx = s.x + s.w / 2;
      var lbl = svText(g, cx, 0, s.id, { 'class': 'xs-l', 'text-anchor': 'middle', 'font-weight': 700 });
      var sub = svText(g, cx, 0, '', { 'class': 'xs-s', 'text-anchor': 'middle', 'font-size': s.fs, 'font-weight': 600 });
      sv('rect', { 'class': 'ov', x: s.x, y: s.y, width: s.w, height: s.h, rx: 0.18 }, g);
      // The page's plan keeps its rings 3 px wide with non-scaling-stroke. The full-screen plan
      // moves by transform, and non-scaling-stroke would make the browser redraw everything on
      // every frame of a pinch, so its ring width is set (in metres) each time it's drawn.
      var ringAttrs = opts.pulse ? {} : { 'stroke-width': 3, 'vector-effect': 'non-scaling-stroke' };
      if (opts.pulse) {
        sv('rect', Object.assign({ 'class': 'ring pulse', x: s.x - 0.1, y: s.y - 0.1, width: s.w + 0.2, height: s.h + 0.2, rx: 0.3 }, ringAttrs), g);
      }
      sv('rect', Object.assign({ 'class': 'ring', x: s.x - 0.1, y: s.y - 0.1, width: s.w + 0.2, height: s.h + 0.2, rx: 0.3 }, ringAttrs), g);
      nodes[s.id] = { g: g, rect: rect, lbl: lbl, sub: sub, far: null };
    });

    var wallLayer = sv('g', { 'aria-hidden': 'true', 'pointer-events': 'none' }, svg);
    (GEO.walls || []).forEach(function (w) {
      sv('line', { x1: w[0], y1: w[1], x2: w[2], y2: w[3], stroke: '#0C0B0A', 'stroke-width': 0.36 }, wallLayer);
    });

    var over = sv('g', { 'aria-hidden': 'true', 'pointer-events': 'none' }, svg);
    sv('path', { d: P.outer, fill: 'none', stroke: '#E9E1D5', 'stroke-width': 0.42 }, over);
    sv('path', { d: P.arc, fill: 'none', stroke: '#E9E1D5', 'stroke-width': 0.3, 'stroke-dasharray': '2.2 1.4' }, over);
    sv('line', { x1: 0, y1: 43, x2: 0, y2: D, stroke: '#E9E1D5', 'stroke-width': 0.42 }, over);
    sv('line', { x1: 42, y1: 0, x2: W, y2: 0, stroke: '#E9E1D5', 'stroke-width': 0.42 }, over);
    GEO.entrances.forEach(function (e) {
      sv('path', { d: e.path, fill: 'none', stroke: '#F8931F', 'stroke-width': 1.1, 'stroke-linecap': 'round' }, over);
      svText(over, e.lx, e.ly, e.label, {
        fill: '#F8931F', 'font-size': 1.05, 'font-weight': 700, 'text-anchor': 'middle',
        transform: 'rotate(' + e.rot.toFixed(1) + ' ' + e.lx.toFixed(2) + ' ' + e.ly.toFixed(2) + ')'
      });
    });
    GEO.fire.forEach(function (f) {
      var a = { stroke: '#E5534B', 'stroke-width': 0.7 };
      if (f.side === 'S') { sv('line', Object.assign({ x1: f.v - 2.2, y1: D, x2: f.v + 2.2, y2: D }, a), over); }
      if (f.side === 'N') { sv('line', Object.assign({ x1: f.v - 2.2, y1: 0, x2: f.v + 2.2, y2: 0 }, a), over); }
      if (f.side === 'E') { sv('line', Object.assign({ x1: W, y1: f.v - 2.2, x2: W, y2: f.v + 2.2 }, a), over); }
    });
    if (opts.dims) {
      svText(over, W / 2, D + 3.2, '58.0 m', { 'font-size': 1.35, fill: '#8F857A', 'text-anchor': 'middle' });
      svText(over, W + 3.6, D / 2, '54.0 m', {
        'font-size': 1.35, fill: '#8F857A', 'text-anchor': 'middle', transform: 'rotate(-90 ' + (W + 3.6) + ' ' + D / 2 + ')'
      });
    }
    return { svg: svg, nodes: nodes, uid: uid, fade: !!opts.fade };
  }

  // The stand number plus small print (size, "On hold" or the company) when the small print
  // is big enough to read; otherwise just a larger number.
  function placeLabels(inst, pxPerM) {
    var changed = [];
    STANDS.forEach(function (s) {
      var n = inst.nodes[s.id], far = s.fs * pxPerM < SMALL_PRINT_PX;
      if (n.far === far) { return; }
      n.far = far;
      var big = Math.min(s.w, s.h) >= 6, cy = s.y + s.h / 2;
      var fs = far ? (big ? 1.9 : 1.4) : (big ? 1.5 : 1.1);
      n.lbl.setAttribute('font-size', fs);
      n.lbl.setAttribute('y', round3(far ? cy + fs * 0.36 : cy - (big ? 0.2 : 0.12)));
      n.sub.setAttribute('y', round3(cy + (big ? 1.42 : 0.98)));
      n.sub.setAttribute('display', far ? 'none' : 'inline');
      n.lbl.removeAttribute('textLength');
      n.lbl.removeAttribute('lengthAdjust');
      changed.push(s);
    });
    var lens = changed.map(function (s) {
      try { return inst.nodes[s.id].lbl.getComputedTextLength(); } catch (e) { return 0; }
    });
    changed.forEach(function (s, i) {
      var room = s.w - 0.7;
      if (lens[i] > room) {
        inst.nodes[s.id].lbl.setAttribute('textLength', round3(room));
        inst.nodes[s.id].lbl.setAttribute('lengthAdjust', 'spacingAndGlyphs');
      }
    });
  }

  function standAria(s) {
    var base = 'Stand ' + s.id + ', ' + sizeSpoken(s.size) + ', ' + s.sqm + ' square metres, ' + openShort(s).toLowerCase();
    if (s.status === 'sold') { return base + (s.name ? ', sold to ' + s.name : ', sold'); }
    if (s.status === 'hold') { return base + ', on hold'; }
    if (s.status === 'available') { return base + ', available, ' + money(s.price) + ' ' + priceNote(); }
    if (s.status === 'loading') { return base + ', ' + money(s.price) + ' ' + priceNote() + ', checking availability'; }
    return base + ', ' + money(s.price) + ' ' + priceNote() + ', availability not confirmed';
  }

  function paintStand(inst, s) {
    var n = inst.nodes[s.id];
    var z = SIZES[s.size] || {};
    var look = s.status === 'available' ? { fill: z.fill, line: z.line, lbl: '#16211C', sub: '#4F5D57' } : LOOK[s.status];
    n.rect.setAttribute('fill', look.fill);
    n.rect.setAttribute('stroke', look.line);
    n.lbl.setAttribute('fill', look.lbl);
    n.sub.setAttribute('fill', look.sub);
    var cls = 'xs is-' + s.status;
    if (s.id === selId) { cls += ' is-sel'; }
    if (!matches(s)) { cls += ' is-dim'; }
    else if (inst.fade && selId && s.id !== selId) { cls += ' is-fade'; }
    if (inst.hoverId === s.id) { cls += ' is-hover'; }
    n.g.setAttribute('class', cls);
    var sub, fs = s.fs;
    if (s.status === 'sold' && s.name) {
      if (textWidth(s.name, fs) > s.w - 0.4) { fs = round3(s.fs * 0.8); }
      sub = fitText(s.name, s.w - 0.4, fs);
    } else if (s.status === 'sold') { sub = 'Sold'; }
    else if (s.status === 'hold') { sub = 'On hold'; }
    else { sub = sizeShort(s.size); }
    n.sub.setAttribute('font-size', fs);
    n.sub.textContent = sub;
    if (n.g.getAttribute('role')) { n.g.setAttribute('aria-label', standAria(s)); }
  }

  /* ------------------------------------------------------------------ */
  /* Page elements                                                       */
  /* ------------------------------------------------------------------ */

  var mapEl = q('[data-role="map"]');
  var tip = q('[data-role="tip"]');
  var detailsEl = q('[data-role="details"]');
  var sideDefault = q('[data-role="sidedefault"]');
  var noticeEl = q('[data-role="notice"]');
  var chipsEl = q('[data-role="chips"]');
  var ex = q('[data-role="ex"]');
  if (!mapEl || !ex) { return; }

  var mqNarrow = window.matchMedia ? window.matchMedia('(max-width: 899px)') : { matches: false };
  var coarse = !!(window.matchMedia && window.matchMedia('(pointer: coarse)').matches);

  var inline = drawPlan('xpA', FULL, {
    keyboard: true, dims: true,
    label: 'Hall 5 floor plan. ' + STANDS.length + ' stands. Use the arrow keys to move between stands and Enter to choose one.'
  });
  mapEl.insertBefore(inline.svg, mapEl.firstChild);
  var exInst = null;

  function inlineView() { return mqNarrow.matches ? CROP : FULL; }

  function layoutInline() {
    var vb = inlineView();
    inline.svg.setAttribute('viewBox', [vb.x, vb.y, vb.w, vb.h].join(' '));
    var w = inline.svg.getBoundingClientRect().width || mapEl.clientWidth || 800;
    placeLabels(inline, w / vb.w);
    var narrow = mqNarrow.matches;
    inline.svg.setAttribute('aria-hidden', narrow ? 'true' : 'false');
    STANDS.forEach(function (s) {
      var g = inline.nodes[s.id].g;
      if (narrow) { g.removeAttribute('tabindex'); }
      else if (!g.hasAttribute('tabindex')) { g.setAttribute('tabindex', -1); }
    });
    if (!narrow) { setRoving(rovingId || STANDS[0].id); }
  }

  /* ------------------------------------------------------------------ */
  /* Filters (shared by the page and the full-screen plan)               */
  /* ------------------------------------------------------------------ */

  function availableCount(list) {
    return (list || STANDS).filter(function (s) { return s.status === 'available'; }).length;
  }

  function chipButtons(compact) {
    var out = [];
    out.push(el('button', {
      type: 'button', className: 'xp-chip', 'data-size': '', 'aria-pressed': String(!filt.size)
    }, ['All', el('span', { className: 'pr', text: STANDS.length + ' stands' })]));
    SIZE_KEYS.forEach(function (k) {
      var z = SIZES[k];
      out.push(el('button', {
        type: 'button', className: 'xp-chip', 'data-size': k, 'aria-pressed': String(filt.size === k)
      }, [
        el('span', { className: 'sw', style: 'background:' + z.fill + ';border-color:' + z.line, 'aria-hidden': 'true' }),
        compact ? sizeShort(k) : z.label,
        el('span', { className: 'pr', text: money(z.price) })
      ]));
    });
    out.push(el('button', {
      type: 'button', className: 'xp-chip', 'data-avail': '1', 'aria-pressed': String(filt.avail)
    }, ['Available only']));
    return out;
  }

  function renderChips() {
    [[chipsEl, false], [q('[data-role="exchips"]'), true]].forEach(function (pair) {
      var box = pair[0];
      if (!box) { return; }
      var keep = box.scrollLeft, active = document.activeElement, focusIdx = -1;
      if (active && box.contains(active)) { focusIdx = Array.prototype.indexOf.call(box.children, active); }
      box.textContent = '';
      chipButtons(pair[1]).forEach(function (b) { box.appendChild(b); });
      if (pair[1]) { legendTags().forEach(function (t) { box.appendChild(t); }); }
      box.scrollLeft = keep;
      if (focusIdx > -1 && box.children[focusIdx]) { focusNoScroll(box.children[focusIdx]); }
    });
  }

  function onChip(e) {
    var b = e.target.closest ? e.target.closest('.xp-chip') : null;
    if (!b) { return; }
    if (b.hasAttribute('data-avail')) {
      filt.avail = !filt.avail;
    } else {
      var k = b.getAttribute('data-size') || '';
      filt.size = (k && filt.size === k) ? '' : k;
    }
    // A stand that the filter hides is let go.
    if (selId && !matches(BY_ID[selId])) { setSel(null, { keepCamera: false }); }
    refreshAll();
    if (exOpen) { exAfterFilter(); }
  }
  if (chipsEl) { chipsEl.addEventListener('click', onChip); }

  /* ------------------------------------------------------------------ */
  /* Stand details (side panel on wide screens, sheet on narrow ones)    */
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

  function sidesDiagram(s) {
    var z = SIZES[s.size] || {};
    var box = el('div', { className: 'xp-sides', role: 'img', 'aria-label': sidesSpoken(s) }, [
      el('div', { className: 'sq', style: 'background:' + (z.fill || '#DCEAF7') + ';border-color:' + (z.line || '#2E6DA4') })
    ]);
    var pos = {
      N: function (o) { return 'left:18px;width:44px;top:' + (o ? 7 : 18) + 'px;height:' + (o ? 5 : 4) + 'px'; },
      S: function (o) { return 'left:18px;width:44px;top:' + (o ? 68 : 58) + 'px;height:' + (o ? 5 : 4) + 'px'; },
      W: function (o) { return 'top:18px;height:44px;left:' + (o ? 7 : 18) + 'px;width:' + (o ? 5 : 4) + 'px'; },
      E: function (o) { return 'top:18px;height:44px;left:' + (o ? 68 : 58) + 'px;width:' + (o ? 5 : 4) + 'px'; }
    };
    ['N', 'E', 'S', 'W'].forEach(function (k) {
      var o = s.sides[k];
      box.appendChild(el('i', {
        style: pos[k](o) + ';background:' + (o ? '#F8931F;box-shadow:0 0 10px rgba(248,147,31,.6)' : '#0C0B0A')
      }));
    });
    box.appendChild(el('em', { text: 'N ↑', 'aria-hidden': 'true' }));
    return box;
  }

  function buildDetails(s, where) {
    var sheet = where === 'sheet';
    var hasName = s.status === 'sold' && s.name;
    var box = el('div', { className: 'xp-sd' + (sheet ? '' : ' boxed is-' + s.status) });
    var top = el('div', { className: 'xp-sd-top' }, [
      el('span', { className: 'xp-badge is-' + s.status }, [el('i', { 'aria-hidden': 'true' }), BADGE[s.status]])
    ]);
    if (sheet) {
      var x = el('button', { type: 'button', className: 'xp-ibtn', 'data-act': 'clear', 'aria-label': 'Close the stand details', style: 'width:40px;height:40px' });
      x.appendChild(icon(ICON.close, 18, 2.2));
      top.appendChild(x);
    } else {
      top.appendChild(el('span', { className: 'eyebrow', text: 'Hall 5 · stand' }));
    }
    box.appendChild(top);

    var titleBlock = el('div', null, hasName ? [
      el('div', { className: 'disp xp-sd-name', text: s.name }),
      el('div', { className: 'xp-sd-meta', text: 'Stand ' + s.id + ' · ' + sizeText(s.size) + ' · ' + s.sqm + ' m²' })
    ] : [
      el('div', { className: 'disp xp-sd-id' }, idNodes(s.id)),
      el('div', { className: 'xp-sd-meta', text: sizeText(s.size) + ' · ' + s.sqm + ' m²' }),
      el('div', { className: 'xp-sd-open', text: openText(s) })
    ]);
    box.appendChild(el('div', { className: 'xp-sd-row' }, [titleBlock, hasName ? null : sidesDiagram(s)]));

    if (s.status === 'available' || s.status === 'loading' || s.status === 'unknown') {
      box.appendChild(el('div', { className: 'xp-sd-price' }, [
        el('span', { className: 'disp p', text: money(s.price) }),
        el('span', { className: 'gst', text: (CFG.currency || 'AUD') + ' ' + priceNote() })
      ]));
      box.appendChild(el('p', { className: 'xp-sd-save', text: savingText(s) }));
      if (s.status === 'loading') {
        box.appendChild(el('p', { className: 'xp-sd-warn', text: 'Checking live availability…' }));
      } else if (s.status === 'unknown') {
        box.appendChild(el('p', { className: 'xp-sd-warn', text: 'We couldn’t confirm live availability just now. Send an enquiry and we’ll check this stand for you.' }));
      }
      var go = el('a', { className: 'btn btn-primary', href: enquiryHref(s) }, [el('span', { text: 'Enquire about ' + s.id })]);
      go.appendChild(icon(ICON.arrow, 18, 2.2));
      box.appendChild(go);
      if (CFG.enquiryEmail && !sheet) {
        box.appendChild(el('p', { className: 'xp-sd-alt' }, ['Or email ', el('a', { href: mailtoHref(s), text: CFG.enquiryEmail })]));
      }
    } else if (s.status === 'hold') {
      box.appendChild(el('p', { className: 'xp-sd-text', text: 'This stand is on hold at the moment. Ask us and we’ll let you know if it becomes available.' }));
      box.appendChild(el('a', { className: 'btn btn-ghost', href: enquiryHref(s), text: 'Ask about stand ' + s.id }));
    } else {
      if (s.bio) {
        s.bio.split(/\n{2,}/).forEach(function (para) {
          box.appendChild(el('p', { className: 'xp-sd-text', text: para }));
        });
      } else {
        box.appendChild(el('p', { className: 'xp-sd-text', text: 'Find them at stand ' + s.id + ' at the ATFA Expo & Conference 2027.' }));
      }
      if (s.url) {
        var site = el('a', { className: 'btn btn-ghost', href: s.url, target: '_blank', rel: 'noopener noreferrer' }, [el('span', { text: 'Visit website' })]);
        site.appendChild(icon(ICON.ext, 16, 2));
        box.appendChild(site);
        box.appendChild(el('span', { className: 'xp-sd-site', text: domainOf(s.url, s.urlTyped) }));
      }
    }

    if (sheet) {
      var nav = navIds(s.id);
      if (nav) {
        var prev = el('button', { type: 'button', className: 'btn btn-ghost', 'data-act': 'go', 'data-id': nav.prev, 'aria-label': 'Previous stand, ' + nav.prev });
        prev.appendChild(icon(ICON.left, 16, 2.2));
        prev.appendChild(document.createTextNode(nav.prev));
        var next = el('button', { type: 'button', className: 'btn btn-ghost', 'data-act': 'go', 'data-id': nav.next, 'aria-label': 'Next stand, ' + nav.next });
        next.appendChild(document.createTextNode(nav.next));
        next.appendChild(icon(ICON.right, 16, 2.2));
        box.appendChild(el('div', { className: 'xp-sd-nav' }, [prev, next]));
      }
    } else {
      box.appendChild(el('button', { type: 'button', className: 'xp-sd-x', 'data-act': 'clear', text: 'Close' }));
    }
    return box;
  }

  function navIds(id) {
    var list = LIST_ORDER.filter(matches);
    if (list.length < 2) { return null; }
    var i = 0;
    list.forEach(function (s, k) { if (s.id === id) { i = k; } });
    return { prev: list[(i - 1 + list.length) % list.length].id, next: list[(i + 1) % list.length].id };
  }

  function renderSide() {
    if (!detailsEl) { return; }
    var active = document.activeElement, hadFocus = active && detailsEl.contains(active);
    detailsEl.textContent = '';
    var s = selId ? BY_ID[selId] : null;
    if (s) { detailsEl.appendChild(buildDetails(s, 'side')); }
    if (sideDefault) { sideDefault.hidden = !!s; }
    if (hadFocus) {
      var again = detailsEl.querySelector('a[href], button');
      if (again) { focusNoScroll(again); }
    }
  }

  function renderStats() {
    var counts = { available: 0, hold: 0, sold: 0 };
    STANDS.forEach(function (s) { if (counts[s.status] !== undefined) { counts[s.status]++; } });
    Object.keys(counts).forEach(function (k) {
      var node = q('[data-stat="' + k + '"]');
      if (node) { node.textContent = known ? String(counts[k]) : '–'; }
    });
    var badge = q('[data-role="availbadge"]');
    if (badge) { badge.textContent = known ? counts.available + ' available' : STANDS.length + ' stands'; }
    renderStatus(counts);
  }

  function renderStatus(counts) {
    var daysEl = q('[data-role="days"]'), noteEl = q('[data-role="daysnote"]');
    var title = q('[data-role="statustitle"]'), dot = q('[data-role="dot"]');
    var days = Math.ceil((EXPO_DAY - Date.now()) / 86400000);
    var avail = known ? counts.available + ' of ' + STANDS.length + ' stands available' : STANDS.length + ' stands from ' + money(MIN_PRICE) + ' ' + priceNote();
    if (known && counts.available === 0) { avail = 'All ' + STANDS.length + ' stands are taken'; }
    if (daysEl) {
      daysEl.hidden = !(days > 0);
      daysEl.textContent = days > 0 ? String(days) : '';
    }
    if (noteEl) { noteEl.textContent = (days > 0 ? (days === 1 ? 'day to go · ' : 'days to go · ') : '') + avail; }
    if (title) { title.textContent = known && counts.available === 0 ? 'Stands sold out' : 'Stands on sale'; }
    if (dot) { dot.classList.toggle('is-out', known && counts.available === 0); }
  }

  function setNotice(text) {
    if (!noticeEl) { return; }
    noticeEl.textContent = text || '';
    noticeEl.hidden = !text;
  }

  function paintAll() {
    STANDS.forEach(function (s) {
      paintStand(inline, s);
      if (exInst) { paintStand(exInst, s); }
    });
  }

  function refreshAll() {
    paintAll();
    renderStats();
    renderSide();
    renderChips();
    if (exOpen) { renderSheet(false); exSubtitle(); }
  }

  function focusNoScroll(node) {
    if (!node) { return; }
    try { node.focus({ preventScroll: true }); } catch (e) { node.focus(); }
  }

  function setSel(id, opts) {
    selId = id && BY_ID[id] ? id : null;
    paintAll();
    renderSide();
    if (exOpen) {
      if (selId) { exMode = 'sel'; } else if (exMode === 'sel') { exMode = 'peek'; }
      renderSheet(true);
      if (!opts || opts.keepCamera !== true) { exCameraForSel(); }
    }
  }

  /* ------------------------------------------------------------------ */
  /* The plan in the page (wide screens)                                 */
  /* ------------------------------------------------------------------ */

  var rovingId = null;
  function setRoving(id) {
    rovingId = id;
    STANDS.forEach(function (s) { inline.nodes[s.id].g.setAttribute('tabindex', s.id === id ? 0 : -1); });
  }

  // Arrow keys: the nearest stand in that direction.
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

  function tipStatus(s) {
    if (s.status === 'available') { return money(s.price) + ' ' + priceNote(); }
    if (s.status === 'hold') { return 'On hold'; }
    if (s.status === 'sold') { return s.name || 'Sold'; }
    return money(s.price) + ' ' + priceNote();
  }

  function hideTip() {
    if (tip) { tip.classList.remove('on'); }
    if (inline.hoverId) {
      var was = inline.hoverId;
      inline.hoverId = null;
      paintStand(inline, BY_ID[was]);
    }
  }

  inline.svg.addEventListener('pointermove', function (e) {
    if (mqNarrow.matches || e.pointerType !== 'mouse') { return; }
    var g = e.target.closest ? e.target.closest('.xs') : null;
    if (!g) { hideTip(); return; }
    var s = BY_ID[g.getAttribute('data-id')];
    if (inline.hoverId !== s.id) {
      var was = inline.hoverId;
      inline.hoverId = s.id;
      if (was) { paintStand(inline, BY_ID[was]); }
      paintStand(inline, s);
      tip.textContent = '';
      tip.appendChild(el('strong', { text: 'Stand ' + s.id }));
      tip.appendChild(el('span', { text: sizeText(s.size) + ' · ' + s.sqm + ' m² · ' + openShort(s) }));
      tip.appendChild(el('span', { className: 'st is-' + s.status, text: tipStatus(s) }));
    }
    var r = mapEl.getBoundingClientRect();
    var x = e.clientX - r.left + 14, y = e.clientY - r.top + 14;
    var tw = tip.offsetWidth || 200, th = tip.offsetHeight || 70;
    if (x + tw > r.width - 6) { x = e.clientX - r.left - tw - 12; }
    if (y + th > r.height - 6) { y = e.clientY - r.top - th - 12; }
    tip.style.left = Math.max(6, x) + 'px';
    tip.style.top = Math.max(6, y) + 'px';
    tip.classList.add('on');
  });
  inline.svg.addEventListener('pointerleave', hideTip);

  inline.svg.addEventListener('click', function (e) {
    if (mqNarrow.matches) { return; }
    var g = e.target.closest ? e.target.closest('.xs') : null;
    if (!g) { return; }
    var id = g.getAttribute('data-id');
    setRoving(id);
    setSel(selId === id ? null : id);
  });

  inline.svg.addEventListener('keydown', function (e) {
    var g = e.target.closest ? e.target.closest('.xs') : null;
    if (!g) { return; }
    var s = BY_ID[g.getAttribute('data-id')];
    if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
      e.preventDefault();
      setSel(s.id);
    } else if (e.key === 'Escape') {
      setSel(null);
    } else if (/^Arrow(Up|Down|Left|Right)$/.test(e.key)) {
      e.preventDefault();
      var next = neighbour(s, e.key);
      if (next) { setRoving(next.id); focusNoScroll(inline.nodes[next.id].g); }
    }
  });

  root.addEventListener('click', function (e) {
    var act = e.target.closest ? e.target.closest('[data-act]') : null;
    if (act && !ex.contains(act)) {
      if (act.getAttribute('data-act') === 'clear') {
        var was = selId;
        setSel(null);
        if (was && !mqNarrow.matches) { focusNoScroll(inline.nodes[was].g); }
      }
      return;
    }
    var link = e.target.closest ? e.target.closest('[data-plan]') : null;
    if (link) {
      var kind = link.getAttribute('data-plan');
      if (kind === 'full' || mqNarrow.matches) {
        e.preventDefault();
        openEx({ opener: link });
      }
      return;
    }
    // On narrow screens the whole plan card opens the full-screen plan.
    if (mqNarrow.matches && mapEl.contains(e.target)) {
      openEx({ opener: q('.xp-open') });
    }
  });

  /* ------------------------------------------------------------------ */
  /* The full-screen plan                                                */
  /* ------------------------------------------------------------------ */
  //
  // The map gets the screen. Nothing covers it until a stand is picked; then a compact card
  // shows the price and the Enquire button, with the rest under "More about this stand".
  // Dragging and pinching only move a picture that is already drawn (a GPU transform); the
  // plan is redrawn sharp at the new zoom as soon as the finger stops. That keeps it smooth.

  var exMap = q('[data-role="exmap"]', ex);
  var cam = q('[data-role="cam"]', ex);
  var exSheet = q('[data-role="exsheet"]', ex);
  var exGrab = q('[data-role="exgrab"]', ex);
  var exSin = q('[data-role="exsin"]', ex);
  var exList = q('[data-role="exlist"]', ex);
  var exIn = q('[data-role="exin"]', ex);
  var exOut = q('[data-role="exout"]', ex);
  var exWhole = q('[data-role="exwhole"]', ex);
  var exHintText = q('[data-role="exhinttext"]', ex);
  var exClose = q('[data-role="exclose"]', ex);
  var exChips = q('[data-role="exchips"]', ex);
  var exSub = q('[data-role="exsub"]', ex);
  if (ex.parentNode !== root) { root.appendChild(ex); }
  if (exHintText) {
    exHintText.textContent = (coarse ? 'Tap' : 'Click') + ' a stand for its price and open sides';
  }

  var mqSide = window.matchMedia ? window.matchMedia('(orientation: landscape) and (max-height: 520px)') : { matches: false };
  var exOpen = false, exMode = 'peek', exMore = false, pushed = false, lastFocus = null, saved = null;
  var view = { x: CROP.x, y: CROP.y, w: CROP.w, h: CROP.h };   // the camera, in metres
  var drawn = null;                                             // where the plan was last drawn
  var box = { w: 390, h: 664, top: 108, bottom: 0, right: 0 };
  var anim = null, animKind = '', moving = false, commitTimer = null;

  // Phones (Safari especially) scroll the page behind an overlay unless the page is pinned.
  function restoreStyle(node, value) {
    if (value === null) { node.removeAttribute('style'); } else { node.setAttribute('style', value); }
  }
  function lockScroll(on) {
    var html = document.documentElement, body = document.body;
    if (on) {
      if (saved) { return; }
      saved = { y: window.pageYOffset || html.scrollTop || 0, html: html.getAttribute('style'), body: body.getAttribute('style') };
      html.style.overflow = 'hidden';
      body.style.overflow = 'hidden';
      body.style.position = 'fixed';
      body.style.top = -saved.y + 'px';
      body.style.left = '0';
      body.style.right = '0';
      body.style.width = '100%';
      html.classList.add('xp-ex-on');
    } else if (saved) {
      var y = saved.y;
      restoreStyle(html, saved.html);
      restoreStyle(body, saved.body);
      html.classList.remove('xp-ex-on');
      saved = null;
      var behaviour = html.style.scrollBehavior;
      html.style.scrollBehavior = 'auto';
      window.scrollTo(0, y);
      html.style.scrollBehavior = behaviour;
      // Safari can put back an older scroll position after the back button; check once more.
      requestAnimationFrame(function () {
        if (!exOpen && Math.abs((window.pageYOffset || 0) - y) > 2) { window.scrollTo(0, y); }
      });
    }
  }

  // The part of the screen the plan can use: under the header, above (or beside) the card.
  function measure() {
    var shown = !exSheet.hidden;
    ex.setAttribute('data-sheet', shown ? 'on' : 'off');
    var r = exMap.getBoundingClientRect();
    box.w = r.width || window.innerWidth;
    box.h = r.height || window.innerHeight;
    box.top = Math.max(56, Math.round(exChips.getBoundingClientRect().bottom - r.top + 8));
    box.right = 0;
    box.bottom = 0;
    if (shown && exMode !== 'list') {
      if (mqSide.matches) { box.right = exSheet.offsetWidth; } else { box.bottom = exSheet.offsetHeight; }
    }
    ex.style.setProperty('--xp-top', box.top + 'px');
    ex.style.setProperty('--xp-right', box.right + 'px');
    // Short of room above the card (small phones): the pinch is enough, so the zoom buttons go.
    ex.classList.toggle('is-cramped', box.h - box.bottom - box.top < 300);
  }

  function bandW() { return Math.max(80, box.w - box.right); }
  function bandH() { return Math.max(80, box.h - box.bottom - box.top); }
  function bandCx() { return bandW() / 2; }
  function bandCy() { return box.top + bandH() / 2; }
  function fitPx() { return Math.min((bandW() - 12) / CROP.w, (bandH() - 12) / CROP.h); }
  function maxPx() { return Math.max(fitPx() * 6, 44); }
  function pxNow() { return box.w / view.w; }

  // A view (in metres) with the drawing point (mx, my) at screen point (sx, sy).
  function viewAt(px, mx, my, sx, sy) {
    return { x: mx - sx / px, y: my - sy / px, w: box.w / px, h: box.h / px };
  }

  function clampView(v) {
    var px = box.w / v.w, out = { x: v.x, y: v.y, w: v.w, h: v.h };
    var visW = bandW() / px, visH = bandH() / px, visT = v.y + box.top / px;
    if (visW >= LIMIT.w) { out.x = LIMIT.x + LIMIT.w / 2 - visW / 2; }
    else { out.x = clamp(v.x, LIMIT.x, LIMIT.x + LIMIT.w - visW); }
    if (visH >= LIMIT.h) { out.y = LIMIT.y + LIMIT.h / 2 - visH / 2 - box.top / px; }
    else { out.y = clamp(visT, LIMIT.y, LIMIT.y + LIMIT.h - visH) - box.top / px; }
    return out;
  }

  function wholeView() {
    return clampView(viewAt(fitPx(), CROP.x + CROP.w / 2, CROP.y + CROP.h / 2, bandCx(), bandCy()));
  }

  function zoomButtons() {
    var px = pxNow();
    exIn.setAttribute('aria-disabled', String(px >= maxPx() * 0.99));
    exOut.setAttribute('aria-disabled', String(px <= fitPx() * 1.01));
  }
  function zoomOk(b) { return b.getAttribute('aria-disabled') !== 'true'; }

  // Draw the plan sharp for the camera as it is now.
  function commit() {
    clearTimeout(commitTimer);
    commitTimer = null;
    if (!exInst) { return; }
    drawn = { x: view.x, y: view.y, w: view.w, h: view.h };
    exInst.svg.setAttribute('viewBox', [round3(view.x), round3(view.y), round3(view.w), round3(view.h)].join(' '));
    cam.style.transform = '';
    cam.style.setProperty('--xp-ring', (3 / pxNow()).toFixed(4) + 'px');
    placeLabels(exInst, pxNow());
    zoomButtons();
  }

  function scheduleCommit() {
    clearTimeout(commitTimer);
    commitTimer = setTimeout(function () {
      commitTimer = null;
      if (!moving && !anim) { commit(); }
    }, 80);
  }

  // Move the last drawing to where the camera is now (cheap), and redraw once things settle.
  function render() {
    if (!exInst) { return; }
    if (!drawn) { commit(); return; }
    var p0 = box.w / drawn.w, p1 = pxNow(), s = p1 / p0;
    if (s > 3 || s < 0.34) { commit(); return; }
    var tx = (drawn.x - view.x) * p1, ty = (drawn.y - view.y) * p1;
    cam.style.transform = 'translate3d(' + tx.toFixed(2) + 'px,' + ty.toFixed(2) + 'px,0) scale(' + s.toFixed(5) + ')';
    zoomButtons();
    if (!moving && !anim) { scheduleCommit(); }
  }

  function stopMotion() {
    if (anim) { cancelAnimationFrame(anim); anim = null; }
    animKind = '';
  }

  function settle() {
    if (!anim) { scheduleCommit(); }
  }

  function easeOut(t) { return 1 - Math.pow(1 - t, 3); }

  function glide(target, ms) {
    stopMotion();
    target = clampView(target);
    if (REDUCED || !drawn) { view = target; commit(); return; }
    var from = { x: view.x, y: view.y, w: view.w, h: view.h }, t0 = null, dur = ms || 420;
    var step = function (now) {
      if (t0 === null) { t0 = now; }
      var k = Math.min(1, (now - t0) / dur), e = easeOut(k);
      // Zoom in scale space so the motion feels even.
      var lw = Math.log(from.w) + (Math.log(target.w) - Math.log(from.w)) * e;
      var w = Math.exp(lw), f = Math.abs(from.w - target.w) < 1e-6 ? e : (from.w - w) / (from.w - target.w);
      view = { x: from.x + (target.x - from.x) * f, y: from.y + (target.y - from.y) * f, w: w, h: w * box.h / box.w };
      if (k < 1) {
        render();
        anim = requestAnimationFrame(step);
      } else {
        anim = null;
        animKind = '';
        view = target;
        commit();
      }
    };
    animKind = 'glide';
    anim = requestAnimationFrame(step);
  }

  // After a flick the plan keeps sliding and slows down, like a phone's own scrolling.
  function coast(trail) {
    var n = trail.length;
    if (REDUCED || n < 2) { settle(); return; }
    var b = trail[n - 1], a = b;
    for (var i = n - 1; i >= 0 && b.t - trail[i].t <= 100; i--) { a = trail[i]; }
    var dt = b.t - a.t;
    if (dt < 10 || performance.now() - b.t > 90) { settle(); return; }
    var vx = (b.x - a.x) / dt, vy = (b.y - a.y) / dt, sp = Math.sqrt(vx * vx + vy * vy);
    if (sp < 0.2) { settle(); return; }
    if (sp > 3.5) { vx *= 3.5 / sp; vy *= 3.5 / sp; }
    var last = null;
    var step = function (now) {
      if (last === null) { last = now; anim = requestAnimationFrame(step); return; }
      var d = Math.min(34, now - last);
      last = now;
      var px = pxNow();
      var want = { x: view.x - vx * d / px, y: view.y - vy * d / px, w: view.w, h: view.h };
      var got = clampView(want);
      if (Math.abs(got.x - want.x) > 1e-4) { vx = 0; }
      if (Math.abs(got.y - want.y) > 1e-4) { vy = 0; }
      view = got;
      var decay = Math.exp(-d / 300);
      vx *= decay;
      vy *= decay;
      if (Math.sqrt(vx * vx + vy * vy) < 0.03) { anim = null; animKind = ''; commit(); return; }
      render();
      anim = requestAnimationFrame(step);
    };
    animKind = 'coast';
    anim = requestAnimationFrame(step);
  }

  function exCameraForSel() {
    if (!exOpen || !selId) { return; }
    measure();
    var s = BY_ID[selId];
    // Close enough to read the stand, with its neighbours around it: about 18 m across.
    var want = Math.min(bandW(), bandH()) / 18;
    var px = clamp(Math.max(pxNow(), want), fitPx(), maxPx());
    glide(viewAt(px, s.x + s.w / 2, s.y + s.h / 2, bandCx(), bandCy()));
  }

  function exAfterFilter() {
    renderSheet(true);
    exSubtitle();
    if (!selId && exMode !== 'list') { glide(wholeView()); }
  }

  function exSubtitle() {
    if (!exSub) { return; }
    var shown = STANDS.filter(sizeOk);
    var text;
    if (filt.size) {
      var z = SIZES[filt.size];
      text = (known ? availableCount(shown) + ' of ' + shown.length + ' available' : shown.length + ' stands') + ' · ' + money(z.price) + ' each';
    } else {
      text = (known ? availableCount() + ' available' : STANDS.length + ' stands') + ' · from ' + money(MIN_PRICE);
    }
    exSub.textContent = text;
  }

  // On hold and Sold, next to the size buttons (only when there are some).
  function legendTags() {
    var out = [];
    if (!known) { return out; }
    [['hold', 'On hold'], ['sold', 'Sold']].forEach(function (k) {
      if (STANDS.some(function (s) { return s.status === k[0]; })) {
        out.push(el('span', { className: 'xp-tag', 'aria-hidden': 'true' }, [
          el('i', { style: 'background:' + LOOK[k[0]].fill + ';border-color:' + LOOK[k[0]].line }), k[1]
        ]));
      }
    });
    return out;
  }

  function openTiny(s) {
    if (s.open >= 4) { return 'open all round'; }
    return s.open === 1 ? '1 side open' : s.open + ' sides open';
  }

  // The compact card for a chosen stand.
  function buildCard(s) {
    var hasName = s.status === 'sold' && s.name;
    var card = el('div', { className: 'xp-card is-' + s.status });
    var x = el('button', { type: 'button', className: 'xp-ibtn', 'data-act': 'clear', 'aria-label': 'Close the stand details' });
    x.appendChild(icon(ICON.close, 16, 2.2));
    if (hasName) {
      // An exhibitor's name needs the width; the stand details go on the line under it.
      card.appendChild(el('div', { className: 'xp-card-top is-name' }, [el('div', { className: 'disp xp-card-name', text: s.name }), x]));
      card.appendChild(el('div', { className: 'xp-card-sub' }, [
        el('i', { className: 'dot is-sold', 'aria-hidden': 'true' }),
        'Stand ' + s.id + ' · ' + sizeText(s.size) + ' · ' + s.sqm + ' m²'
      ]));
    } else {
      var state = s.status === 'sold' ? 'Sold' : STATUS_LABEL[s.status] + ' · ' + openTiny(s);
      card.appendChild(el('div', { className: 'xp-card-top' }, [
        el('div', { className: 'disp xp-card-id' }, idNodes(s.id)),
        el('div', { className: 'xp-card-info' }, [
          el('span', { className: 'l1', text: sizeText(s.size) + ' · ' + s.sqm + ' m²' }),
          el('span', { className: 'l2' }, [el('i', { className: 'dot is-' + s.status, 'aria-hidden': 'true' }), state])
        ]),
        x
      ]));
    }

    var priced = s.status === 'available' || s.status === 'loading' || s.status === 'unknown';
    if (priced) {
      var go = el('a', { className: 'btn btn-primary', href: enquiryHref(s), 'aria-label': 'Enquire about stand ' + s.id }, [el('span', { text: 'Enquire' })]);
      go.appendChild(icon(ICON.arrow, 18, 2.2));
      card.appendChild(el('div', { className: 'xp-card-act' }, [
        el('div', { className: 'xp-card-price' }, [
          el('span', { className: 'disp p', text: money(s.price) }),
          el('span', { className: 'gst', text: (CFG.currency || 'AUD') + ' ' + priceNote() })
        ]),
        go
      ]));
    } else if (s.status === 'hold') {
      card.appendChild(el('div', { className: 'xp-card-act' }, [
        el('a', { className: 'btn btn-ghost wide', href: enquiryHref(s), text: 'Ask about stand ' + s.id })
      ]));
    } else if (s.url) {
      var site = el('a', { className: 'btn btn-ghost wide', href: s.url, target: '_blank', rel: 'noopener noreferrer' }, [el('span', { text: 'Visit website' })]);
      site.appendChild(icon(ICON.ext, 16, 2));
      card.appendChild(el('div', { className: 'xp-card-act' }, [site]));
    }

    var detail = el('div', { className: 'xp-card-detail', id: 'xp-card-more' });
    detail.hidden = !exMore;
    if (!hasName) {
      detail.appendChild(el('div', { className: 'xp-card-sides' }, [sidesDiagram(s), el('p', { text: openText(s) })]));
    }
    if (priced) {
      detail.appendChild(el('p', { className: 'xp-sd-save', text: savingText(s) }));
      if (s.status === 'loading') {
        detail.appendChild(el('p', { className: 'xp-sd-warn', text: 'Checking live availability…' }));
      } else if (s.status === 'unknown') {
        detail.appendChild(el('p', { className: 'xp-sd-warn', text: 'We couldn’t confirm live availability just now. Send an enquiry and we’ll check this stand for you.' }));
      }
    } else if (s.status === 'hold') {
      detail.appendChild(el('p', { className: 'xp-sd-text', text: 'This stand is on hold at the moment. Ask us and we’ll let you know if it becomes available.' }));
    } else {
      if (s.bio) {
        s.bio.split(/\n{2,}/).forEach(function (para) { detail.appendChild(el('p', { className: 'xp-sd-text', text: para })); });
      } else {
        detail.appendChild(el('p', { className: 'xp-sd-text', text: 'Find them at stand ' + s.id + ' at the ATFA Expo & Conference 2027.' }));
      }
      if (s.url) { detail.appendChild(el('span', { className: 'xp-sd-site', text: domainOf(s.url, s.urlTyped) })); }
    }
    var nav = navIds(s.id);
    if (nav) {
      var prev = el('button', { type: 'button', className: 'btn btn-ghost', 'data-act': 'go', 'data-id': nav.prev, 'aria-label': 'Previous stand, ' + nav.prev });
      prev.appendChild(icon(ICON.left, 16, 2.2));
      prev.appendChild(document.createTextNode(nav.prev));
      var next = el('button', { type: 'button', className: 'btn btn-ghost', 'data-act': 'go', 'data-id': nav.next, 'aria-label': 'Next stand, ' + nav.next });
      next.appendChild(document.createTextNode(nav.next));
      next.appendChild(icon(ICON.right, 16, 2.2));
      detail.appendChild(el('div', { className: 'xp-sd-nav' }, [prev, next]));
    }
    var more = el('button', {
      type: 'button', className: 'xp-card-more', 'data-act': 'more', 'aria-expanded': String(exMore), 'aria-controls': 'xp-card-more'
    }, [exMore ? 'Less' : 'More about this stand']);
    more.appendChild(icon(ICON.down, 16, 2.2));
    card.appendChild(more);
    card.appendChild(detail);
    return card;
  }

  function buildList() {
    var frag = document.createDocumentFragment();
    var shown = LIST_ORDER.filter(matches);
    frag.appendChild(el('div', { className: 'xp-list-h' }, [
      el('h3', { className: 'disp t', text: filt.size ? shown.length + ' stands, ' + sizeText(filt.size) : (filt.avail ? shown.length + ' available stands' : 'All ' + shown.length + ' stands') }),
      el('span', { className: 'small', style: 'font-size:13.5px;font-weight:500', text: 'Choose one to see it on the plan' })
    ]));
    var list = el('div', { className: 'xp-list' });
    if (!shown.length) {
      list.appendChild(el('p', { className: 'xp-sd-text', style: 'padding:14px 0', text: 'No stands match those filters.' }));
    }
    shown.forEach(function (st) {
      var z = SIZES[st.size] || {};
      var right = st.status === 'hold' ? 'On hold' : (st.status === 'sold' ? 'Sold' : money(st.price));
      var title = st.status === 'sold' && st.name ? st.name : sizeText(st.size) + ' · ' + st.sqm + ' m²';
      var meta = st.status === 'sold' && st.name ? sizeText(st.size) + ' · ' + openShort(st) : openText(st);
      list.appendChild(el('button', { type: 'button', className: 'xp-row', 'data-act': 'go', 'data-id': st.id, 'aria-label': standAria(st) }, [
        el('span', { className: 'bar', style: 'background:' + (st.status === 'available' ? z.fill : LOOK[st.status].fill) + ';box-shadow:inset 0 0 0 1px ' + (st.status === 'available' ? z.line : LOOK[st.status].line), 'aria-hidden': 'true' }),
        el('span', { className: 'id', 'aria-hidden': 'true' }, idNodes(st.id)),
        el('span', { className: 'mid', 'aria-hidden': 'true' }, [el('b', { text: title }), meta]),
        el('span', { className: 'rt is-' + st.status, text: right, 'aria-hidden': 'true' })
      ]));
    });
    list.appendChild(el('p', { className: 'xp-list-foot', text: 'Prices in AUD, ' + priceNote() + '. Stands on hold are marked, and sold stands show the exhibitor’s name.' }));
    frag.appendChild(list);
    return frag;
  }

  function renderSheet(animate) {
    var active = document.activeElement, focusAct = null;
    if (active && exSin.contains(active)) {
      focusAct = active.getAttribute('data-act') || (active.classList.contains('xp-row') ? 'row' : null);
    }
    var s = selId ? BY_ID[selId] : null;
    if (exMode === 'sel' && !s) { exMode = 'peek'; }
    if (exMode !== 'sel') { exMore = false; }
    ex.setAttribute('data-mode', exMode);
    exList.textContent = '';
    exList.appendChild(icon(exMode === 'list' ? ICON.map : ICON.list, 18, 2));
    exList.appendChild(document.createTextNode(exMode === 'list' ? 'Map' : 'List'));
    exList.setAttribute('aria-pressed', String(exMode === 'list'));
    exList.setAttribute('aria-label', exMode === 'list' ? 'Show the map' : 'Show every stand as a list');

    exSin.textContent = '';
    if (exMode === 'list') { exSin.appendChild(buildList()); }
    else if (s) { exSin.appendChild(buildCard(s)); }
    var show = exMode === 'list' || !!s;
    var wasShown = !exSheet.hidden;
    exSheet.classList.toggle('is-tall', exMode === 'list');
    if (exSheet.hidden === show) { exSheet.hidden = !show; }
    if (show && animate) {
      exSin.classList.remove('is-in');
      void exSin.offsetWidth;
      exSin.classList.add('is-in');
    }
    exSin.scrollTop = 0;
    if (focusAct) {
      // Keep the keyboard in the plan: if the button that had focus went with the card, use List.
      var again = exSin.querySelector(focusAct === 'row' ? '.xp-row' : '[data-act="' + focusAct + '"]') || exSin.querySelector('button, a[href]');
      focusNoScroll(again || exList);
    }
    if (exOpen) {
      measure();
      zoomButtons();
      if (wasShown && !show && !moving && pxNow() < fitPx() * 0.99) { glide(wholeView(), 300); }
    }
  }

  // The handle on the card: drag down to put it away (or fold "More"), up or tap for "More".
  var grab = null;
  exGrab.addEventListener('pointerdown', function (e) {
    grab = { y: e.clientY, id: e.pointerId };
    try { exGrab.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
  });
  exGrab.addEventListener('pointermove', function (e) {
    if (!grab || e.pointerId !== grab.id) { return; }
    var dy = clamp(e.clientY - grab.y, 0, 200);
    exSheet.style.transform = dy ? 'translateY(' + dy + 'px)' : '';
  });
  function endGrab(e) {
    if (!grab || e.pointerId !== grab.id) { return; }
    var dy = e.clientY - grab.y;
    grab = null;
    exSheet.style.transform = '';
    if (e.type === 'pointercancel') { return; }
    if (dy > 46) {
      if (exMode === 'list') { exMode = selId ? 'sel' : 'peek'; renderSheet(true); if (selId) { exCameraForSel(); } }
      else if (exMore) { exMore = false; renderSheet(false); exCameraForSel(); }
      else if (selId) { setSel(null, { keepCamera: true }); }
    } else if (dy < -24 || Math.abs(dy) < 6) {
      if (exMode === 'sel' && !exMore) { exMore = true; renderSheet(false); exCameraForSel(); }
    }
  }
  exGrab.addEventListener('pointerup', endGrab);
  exGrab.addEventListener('pointercancel', endGrab);

  function openEx(opts) {
    opts = opts || {};
    if (!exInst) {
      exInst = drawPlan('xpB', CROP, { fade: true, pulse: true });
      cam.appendChild(exInst.svg);
      paintAll();
    }
    if (!exOpen) {
      lastFocus = opts.opener || document.activeElement;
      // Add the history step before pinning the page, so the browser remembers where the page was.
      try {
        if (!history.state || !history.state.xpPlan) { history.pushState({ xpPlan: true }, ''); pushed = true; }
      } catch (e) { pushed = false; }
      ex.hidden = false;
      exOpen = true;
      lockScroll(true);
      hideTip();
    }
    exMode = opts.list ? 'list' : (selId ? 'sel' : 'peek');
    exMore = false;
    paintAll();
    renderChips();
    exSubtitle();
    renderSheet(true);
    view = wholeView();
    commit();
    if (selId) { exCameraForSel(); }
    focusNoScroll(exClose);
  }

  function closeEx(fromHistory) {
    if (!exOpen) { return; }
    if (!fromHistory && pushed) {
      pushed = false;
      try { history.back(); } catch (e) { /* ignore */ }
      // If the browser didn't report the step back, close anyway.
      setTimeout(function () { if (exOpen) { closeEx(true); } }, 450);
      return;
    }
    pushed = false;
    stopMotion();
    clearTimeout(commitTimer);
    pointers = {};
    gesture = null;
    moving = false;
    ex.hidden = true;
    exOpen = false;
    lockScroll(false);
    if (selId && !mqNarrow.matches) { setRoving(selId); }
    if (lastFocus && document.contains(lastFocus) && lastFocus.offsetParent !== null) { focusNoScroll(lastFocus); }
    lastFocus = null;
  }

  window.addEventListener('popstate', function () { if (exOpen) { closeEx(true); } });

  exClose.addEventListener('click', function () { closeEx(false); });
  exList.addEventListener('click', function () {
    exMode = exMode === 'list' ? (selId ? 'sel' : 'peek') : 'list';
    renderSheet(true);
    if (exMode === 'sel') { exCameraForSel(); }
  });
  exIn.addEventListener('click', function () { if (zoomOk(exIn)) { zoomAt({ x: bandCx(), y: bandCy() }, 1.8); } });
  exOut.addEventListener('click', function () { if (zoomOk(exOut)) { zoomAt({ x: bandCx(), y: bandCy() }, 1 / 1.8); } });
  exWhole.addEventListener('click', function () {
    if (selId) { setSel(null, { keepCamera: true }); }
    measure();
    glide(wholeView());
  });
  exChips.addEventListener('click', onChip);
  exSin.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('[data-act]') : null;
    if (!b) { return; }
    var act = b.getAttribute('data-act');
    if (act === 'clear') { setSel(null, { keepCamera: true }); }
    else if (act === 'more') { exMore = !exMore; renderSheet(false); exCameraForSel(); }
    else if (act === 'go') {
      if (exMode === 'list') { exMore = false; }
      exMode = 'sel';
      setSel(b.getAttribute('data-id'));
    }
  });

  document.addEventListener('keydown', function (e) {
    if (!exOpen) { return; }
    if (e.key === 'Escape') {
      e.preventDefault();
      if (exMode === 'list' && selId) { exMode = 'sel'; renderSheet(true); }
      else { closeEx(false); }
      return;
    }
    var onField = e.target && /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName);
    if (!onField && (e.key === '+' || e.key === '=')) { e.preventDefault(); zoomAt({ x: bandCx(), y: bandCy() }, 1.8); return; }
    if (!onField && (e.key === '-' || e.key === '_')) { e.preventDefault(); zoomAt({ x: bandCx(), y: bandCy() }, 1 / 1.8); return; }
    if (e.key !== 'Tab') { return; }
    var items = Array.prototype.filter.call(ex.querySelectorAll('button, a[href]'), function (n) {
      return !n.hidden && !n.disabled && n.offsetParent !== null;
    });
    if (!items.length) { return; }
    var first = items[0], last = items[items.length - 1];
    if (!ex.contains(document.activeElement)) { e.preventDefault(); focusNoScroll(e.shiftKey ? last : first); }
    else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); focusNoScroll(last); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); focusNoScroll(first); }
  });

  // Touch and mouse on the map: drag to move (it keeps sliding after a flick), pinch, wheel
  // or double-tap to zoom, tap a stand to see it.
  var pointers = {}, gesture = null, lastTap = null, trail = [];

  // Where the map sits on screen, read once per gesture (reading it on every move makes the
  // browser stop and lay out the page).
  var mapAt = null;
  function local(e) {
    var r = mapAt || exMap.getBoundingClientRect();
    return { x: e.clientX - r.left, y: e.clientY - r.top };
  }
  function toM(p) { var px = pxNow(); return { x: view.x + p.x / px, y: view.y + p.y / px }; }

  function zoomAt(p, f) {
    var m = toM(p);
    glide(viewAt(clamp(pxNow() * f, fitPx(), maxPx()), m.x, m.y, p.x, p.y), 360);
  }

  // Which stand a tap meant. Small stands are hard to hit on a phone, so a near miss counts
  // when it's clearly closest to one stand; between several small ones the plan zooms in.
  function hitTest(p, tolPx, target) {
    var t = target && target.closest ? target.closest('.xs') : null;
    if (t && BY_ID[t.getAttribute('data-id')]) { return { stand: BY_ID[t.getAttribute('data-id')] }; }
    var m = toM(p), tol = tolPx / pxNow(), near = [];
    STANDS.forEach(function (s) {
      var dx = Math.max(s.x - m.x, 0, m.x - (s.x + s.w)), dy = Math.max(s.y - m.y, 0, m.y - (s.y + s.h));
      var d = Math.sqrt(dx * dx + dy * dy);
      if (d <= tol) { near.push({ s: s, d: d }); }
    });
    if (!near.length) { return {}; }
    near.sort(function (a, b) { return a.d - b.d; });
    if (near.length === 1 || near[0].d === 0 || near[0].d < near[1].d * 0.5) { return { stand: near[0].s }; }
    return { crowd: true };
  }

  function startGesture(multi) {
    var ids = Object.keys(pointers);
    stopMotion();
    trail = [];
    if (ids.length >= 2) {
      var a = pointers[ids[0]], b = pointers[ids[1]];
      var mid = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
      gesture = { kind: 'pinch', px0: pxNow(), m0: toM(mid), d0: Math.max(10, Math.hypot(a.x - b.x, a.y - b.y)), moved: true, multi: true };
    } else if (ids.length === 1) {
      var p = pointers[ids[0]];
      gesture = {
        kind: 'pan', v0: { x: view.x, y: view.y, w: view.w, h: view.h }, px0: pxNow(), p0: { x: p.x, y: p.y },
        t0: Date.now(), moved: !!multi, multi: !!multi, target: p.target
      };
    } else {
      gesture = null;
    }
  }

  function tap(p, g, e) {
    var now = Date.now();
    var hit = hitTest(p, e.pointerType === 'mouse' ? 4 : 16, g.target);
    var hitId = hit.stand ? hit.stand.id : '';
    var dbl = lastTap && now - lastTap.t < 340 && Math.abs(p.x - lastTap.x) + Math.abs(p.y - lastTap.y) < 40 && lastTap.id === hitId;
    lastTap = dbl ? null : { t: now, x: p.x, y: p.y, id: hitId };
    if (dbl) {
      var s0 = selId ? BY_ID[selId] : null;
      if (s0) {
        glide(viewAt(clamp(pxNow() * 2, fitPx(), maxPx()), s0.x + s0.w / 2, s0.y + s0.h / 2, bandCx(), bandCy()), 360);
      } else {
        zoomAt(p, 2);
      }
      return;
    }
    if (hit.stand) {
      if (hit.stand.id !== selId) { exMore = false; }
      exMode = 'sel';
      setSel(hit.stand.id);
    } else if (hit.crowd) {
      zoomAt(p, 2.2);
    } else if (selId) {
      setSel(null, { keepCamera: true });
    }
    settle();
  }

  exMap.addEventListener('pointerdown', function (e) {
    if (e.pointerType === 'mouse' && e.button !== 0) { return; }
    if (!Object.keys(pointers).length) { var mr = exMap.getBoundingClientRect(); mapAt = { left: mr.left, top: mr.top }; }
    var p = local(e);
    // A touch that stops the plan sliding after a flick just stops it, like a phone's scrolling.
    var stoppedFlick = animKind === 'coast';
    pointers[e.pointerId] = { x: p.x, y: p.y, target: e.target };
    try { exMap.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
    moving = true;
    clearTimeout(commitTimer);
    startGesture(Object.keys(pointers).length > 1);
    if (gesture) { gesture.stopped = stoppedFlick; }
  });

  exMap.addEventListener('pointermove', function (e) {
    var pt = pointers[e.pointerId];
    if (!pt || !gesture) { return; }
    var p = local(e);
    pt.x = p.x;
    pt.y = p.y;
    var ids = Object.keys(pointers);
    if (gesture.kind === 'pinch' && ids.length >= 2) {
      var a = pointers[ids[0]], b = pointers[ids[1]];
      var mid = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
      var d = Math.max(10, Math.hypot(a.x - b.x, a.y - b.y));
      var px = clamp(gesture.px0 * d / gesture.d0, fitPx() * 0.85, maxPx());
      view = clampView(viewAt(px, gesture.m0.x, gesture.m0.y, mid.x, mid.y));
      render();
    } else if (gesture.kind === 'pan') {
      var dx = p.x - gesture.p0.x, dy = p.y - gesture.p0.y;
      if (!gesture.moved) {
        if (Math.abs(dx) + Math.abs(dy) < (e.pointerType === 'mouse' ? 4 : 10)) { return; }
        gesture.moved = true;
        exMap.classList.add('is-grab');
      }
      view = clampView({ x: gesture.v0.x - dx / gesture.px0, y: gesture.v0.y - dy / gesture.px0, w: gesture.v0.w, h: gesture.v0.h });
      trail.push({ t: performance.now(), x: p.x, y: p.y });
      if (trail.length > 12) { trail.shift(); }
      render();
    }
  });

  function endPointer(e) {
    var pt = pointers[e.pointerId];
    if (!pt) { return; }
    var g = gesture;
    delete pointers[e.pointerId];
    if (Object.keys(pointers).length) { startGesture(true); return; }
    gesture = null;
    moving = false;
    mapAt = null;
    exMap.classList.remove('is-grab');
    if (!g) { settle(); return; }
    if (g.multi && pxNow() < fitPx() * 0.99) { glide(wholeView(), 300); return; }
    if (e.type === 'pointercancel' || e.type === 'lostpointercapture') { settle(); return; }
    if (g.kind === 'pan' && g.moved) {
      if (g.multi) { settle(); } else { coast(trail); }
      return;
    }
    if (g.kind === 'pan' && !g.multi && !g.stopped && Date.now() - g.t0 < 600) { tap(pt, g, e); return; }
    settle();
  }
  exMap.addEventListener('pointerup', endPointer);
  exMap.addEventListener('pointercancel', endPointer);
  exMap.addEventListener('lostpointercapture', endPointer);
  exMap.addEventListener('wheel', function (e) {
    e.preventDefault();
    stopMotion();
    var p = local(e), m = toM(p);
    var px = clamp(pxNow() * Math.exp(-e.deltaY * (e.deltaMode === 1 ? 0.05 : 0.0022)), fitPx(), maxPx());
    view = clampView(viewAt(px, m.x, m.y, p.x, p.y));
    render();
  }, { passive: false });
  exMap.addEventListener('contextmenu', function (e) { e.preventDefault(); });
  // Safari zooms the whole page on a pinch unless it's told not to.
  ['gesturestart', 'gesturechange'].forEach(function (type) {
    ex.addEventListener(type, function (e) { e.preventDefault(); }, { passive: false });
  });

  var resizeQueued = false;
  function onResize() {
    if (resizeQueued) { return; }
    resizeQueued = true;
    requestAnimationFrame(function () {
      resizeQueued = false;
      layoutInline();
      if (exOpen) {
        stopMotion();
        var px = pxNow(), c = toM({ x: bandCx(), y: bandCy() });
        measure();
        view = clampView(viewAt(Math.max(px, fitPx()), c.x, c.y, bandCx(), bandCy()));
        commit();
        if (selId && exMode !== 'list') { exCameraForSel(); }
      }
    });
  }
  window.addEventListener('resize', onResize);
  [mqNarrow, mqSide].forEach(function (mq) {
    if (mq.addEventListener) { mq.addEventListener('change', onResize); }
    else if (mq.addListener) { mq.addListener(onResize); }
  });

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
    known = true;
    refreshAll();
  }

  function usable(rows) {
    return (rows || []).some(function (r) {
      var key = r && r.id !== undefined && r.id !== null ? idKey(r.id) : '';
      return key !== '' && STANDS.some(function (s) { return s.key === key; });
    });
  }

  function markUnknown() {
    STANDS.forEach(function (s) { s.status = 'unknown'; s.name = ''; s.url = ''; s.urlTyped = ''; s.bio = ''; });
    known = false;
    refreshAll();
  }

  function asOf(iso) {
    if (!iso) { return 'Availability may be out of date.'; }
    var d = new Date(iso);
    if (isNaN(d.getTime())) { return 'Availability may be out of date.'; }
    var text;
    try {
      text = d.toLocaleString('en-AU', { timeZone: 'Australia/Sydney', day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });
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
  window.ATFAExpo27Page = {
    version: CFG.version || '__VERSION__',
    reload: function () { load(false); },
    open: function (id) { if (id && BY_ID[id]) { selId = id; } openEx({}); },
    close: function () { closeEx(false); },
    view: function () { return { x: view.x, y: view.y, w: view.w, h: view.h, px: pxNow(), fit: fitPx(), max: maxPx(), top: box.top, bottom: box.bottom, right: box.right, open: exOpen, mode: exMode, sel: selId, more: exMore, sliding: animKind === 'coast' }; },
    stands: function () {
      return STANDS.map(function (s) { return { id: s.id, status: s.status, name: s.name, url: s.url, open: s.open }; });
    }
  };

  renderChips();
  layoutInline();
  load(true);
})();
