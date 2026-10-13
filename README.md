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
