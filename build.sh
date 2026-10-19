#!/usr/bin/env bash
# Builds dist/atfa-awards-2027.zip from the plugin source and writes manifest.json.
# Usage: ./build.sh 1.2.3 "What changed"
set -euo pipefail
cd "$(dirname "$0")"
VERSION="${1:?version, e.g. 1.2.3}"
NOTE="${2:-}"
REPO="$(grep -m1 "ATFA_AWARDS_GITHUB_REPO'," atfa-awards-2027/atfa-awards-2027.php | sed "s/.*, '\([^']*\)'.*/\1/")"
sed -i "s/^ \* Version: .*/ * Version: $VERSION/" atfa-awards-2027/atfa-awards-2027.php
sed -i "s/define( 'ATFA_AWARDS_VERSION', '[^']*' );/define( 'ATFA_AWARDS_VERSION', '$VERSION' );/" atfa-awards-2027/atfa-awards-2027.php
sed -i "s/^Stable tag: .*/Stable tag: $VERSION/" atfa-awards-2027/readme.txt
for f in $(find atfa-awards-2027 -name '*.php'); do php -l "$f" > /dev/null; done
rm -f dist/atfa-awards-2027.zip
zip -rq dist/atfa-awards-2027.zip atfa-awards-2027 -x '*.DS_Store'
TODAY="$(date +%Y-%m-%d)"
CHANGELOG="<p><strong>$VERSION</strong> ($TODAY): ${NOTE//\"/\\\"}</p>"
cat > manifest.json <<JSON
{
  "name": "ATFA Flooring Awards 2027",
  "slug": "atfa-awards-2027",
  "version": "$VERSION",
  "requires": "6.0",
  "tested": "6.9",
  "requires_php": "7.4",
  "url": "https://github.com/$REPO",
  "package": "https://raw.githubusercontent.com/$REPO/main/dist/atfa-awards-2027.zip?v=$VERSION",
  "last_updated": "$TODAY",
  "changelog": "$CHANGELOG"
}
JSON
echo "built $VERSION for $REPO"
