=== ATFA Flooring Awards 2027 ===
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.8.1
License: GPL-2.0-or-later

The 2027 awards season pages for atfa.com.au.

== What it does ==

On activation it creates two pages and sends a test email:

* /awards/ (published): the blackout splash. Countdown to entries opening, rotating 2025 winners, "Notify me when entries open" form, the New for 2027 strip.
* /awards-preview/ (private): the full entries-open page. Only staff logged in to WordPress can see it.

Notify-me signups are stored under Awards signups in the WordPress menu (export to CSV from there). Every signup emails nick@atfa.com.au and sends the person a confirmation.

== The Expo page ==

Since 1.8.0 the plugin also runs the ATFA Expo & Conference 2027 page at /expo-2027/ (the [atfa_expo27_page] shortcode on that page) and the older floor plan shortcode [atfa_expo27_floorplan]. Stand availability comes from the published Website tab of the 2027 Exhibitor List Google Sheet, through /wp-json/atfa/v1/expo27/stands. Logged-in staff see a bar with the stand sheet link and Refresh. While the page is private, visitors who go to it are sent to the old floor plan page and menu links to it only show for staff. Staff can still let someone without a login see it for 24 hours, 3 days or 7 days: staff bar on the page, "Share this page with someone who doesn't have a login". Each link can be stopped there at any time. The stand PDF ships in the plugin (assets/expo/).

This code used to be the Code Snippet "ATFA Expo 2027 page and Hall 5 floor plan". After updating to 1.8.0, switch that snippet off in Snippets (it does nothing while the plugin has the page, so it's safe either way). The source, build and tests are in expo/ in the GitHub repo.

== Changing things ==

* Who gets the signup emails: define ATFA_AWARDS_NOTIFY_TO in wp-config.php (default nick@atfa.com.au).
* Redirect the old /training-events/annual-awards/ page to /awards/: define ATFA_AWARDS_REDIRECT_OLD_PAGE as true in wp-config.php.
* Page copy and layout: views/splash.php and views/entries.php. Winner photos and captions: atfa_awards_winners() in the main plugin file.
* The award categories (what goes in, rules, example photos) and which stream each one sits in: data/categories.php. Example photos live in assets/img/examples/.
* Category sponsors: add a 'sponsor' (name, logo file, url) to the award in data/categories.php and drop the logo in assets/img/sponsors/. It then shows in that award's panel and in the sponsors strip on both pages. Floor of the Year is sponsored by ATFA.

== Updating ==

Plugins > Add New > Upload Plugin > choose the new zip > Install Now > "Replace current with uploaded". The pages and signups are kept.
