"""
A standalone preview of the plan for sign-off (no WordPress needed): every stand
shown as For Sale, no live data calls, a banner saying it isn't live.

    python3 tools/make_preview.py     ->  dist/floor-plan-preview.html
"""
import json
import pathlib
import re

ROOT = pathlib.Path(__file__).resolve().parents[1]
DIST = ROOT / "dist"


def main():
    page = (DIST / "preview.html").read_text(encoding="utf-8")
    geo = json.loads((ROOT / "src" / "geometry.json").read_text(encoding="utf-8"))
    settings = json.loads((ROOT / "settings.json").read_text(encoding="utf-8"))
    css = re.search(r'<style id="atfa-expo27-css">.*?</style>', page, re.S).group(0)
    start = page.index('<div id="atfa-expo27"')
    end = page.index('<script type="application/json" id="atfa-expo27-config">')
    shell = page[start:end]
    js = re.search(r'<script id="atfa-expo27-js">.*?</script>', page, re.S).group(0)
    cfg = {"version": settings["version"], "restUrl": "", "csvUrl": "", "staticUrl": "",
           "enquiryEmail": "cherie@atfa.com.au", "enquiryName": "Cherie", "enquiryUrl": settings.get("enquiry_url", ""),
           "pdfUrl": "", "currency": "AUD", "priceNote": "excl. GST"}
    # No PDF button in the preview (the PDF is sent separately).
    shell = re.sub(r'<a class="afpx-btn is-ghost is-small"[^>]*>Download the PDF floor plan</a>', "", shell)
    snap = {"ok": True, "source": "live", "updated": None,
            "stands": [{"id": s["id"], "status": "For Sale"} for s in geo["stands"]]}
    html = (
        "<!doctype html><html lang=\"en\"><head><meta charset=\"utf-8\">"
        "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
        "<title>Expo 2027 floor plan preview</title>"
        "<style>body{margin:0;padding:16px;background:#ECEFEE;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif}"
        ".pv{max-width:1400px;margin:0 auto 14px;padding:10px 14px;border-radius:10px;background:#FFF7E6;border:1px solid #F2D49A;"
        "color:#6B4B12;font-size:14px;line-height:1.45}.pv b{color:#4A3309}</style></head><body>"
        "<div class=\"pv\"><b>Preview, not live.</b> Revised Concept 5 fitted to scale (v" + settings["version"] + "). "
        "Every stand is shown as For Sale. Buttons work as they will on the site.</div>"
        + css + shell +
        "<script type=\"application/json\" id=\"atfa-expo27-config\">" + json.dumps(cfg) + "</script>"
        "<script type=\"application/json\" id=\"atfa-expo27-snapshot\">" + json.dumps(snap) + "</script>"
        + js + "</body></html>")
    out = DIST / "floor-plan-preview.html"
    out.write_text(html, encoding="utf-8")
    print("wrote", out, len(html.encode()), "bytes")


if __name__ == "__main__":
    main()
