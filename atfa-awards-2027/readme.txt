=== ATFA Flooring Awards 2027 ===
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.12.3
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

== Category sponsorship ==

Since 1.10.0 the plugin also runs /awards/sponsorship/, the 2027 awards category sponsorship page (Phil's flyer, online, with live availability). It is made the first time 1.10.0 runs, as a private page under /awards/.

* Who sees it (Awards sponsorship > Settings): Hidden (the default, set 2 November 2026): administrators only, and everyone else, staff and code holders included, is sent to the awards page. Platinum first look (live from 3 November 2026): Platinum Members with their own access code, and administrators; everyone else, staff included, gets the code screen. Everyone: no code needed, and a "Sponsor a category" link goes on the awards pages. The Awards sponsorship screen is for administrators until the page is open to everyone.
* Access codes: wp-admin > Awards sponsorship > Access codes. Add members one per line (Company, contact name, email), then send each one their personal link or the invitation text. A code lets one device in for 90 days. Stop or replace a code at any time.
* Requests: members pick up to two categories (Settings can change the number) and send the form. That emails Phil (copy to Nick), puts the categories on hold and sends the member a confirmation. Confirm or release each request under Requests, or set any category's status by hand under Categories. Requests sent from a staff login are tests: nothing is held and only the sender gets the emails.
* EPOXY: once the categories are on EPOXY, put the link under Settings and the page sends people there instead of the form.
* The offer itself (price, inclusions, dinner, contact) is in data/sponsorship.php.

== The weekend page ==

Since 1.12.0 the plugin also runs /weekend-2027/, the ATFA Flooring Industry Weekend 2027 landing page: the Expo & Conference, the Ladies Breakfast and the Awards Dinner on one page, with a day-by-day program, the awards (what's new, a reminder sign-up, the road to the dinner, category sponsors), the dinner, sponsorship, the venues and an updates sign-up. "Add to my calendar" gives a calendar file of the weekend (/wp-json/atfa-awards/v1/weekend/calendar).

* It is made private the first time 1.12.0 runs. While private, only administrators can open it ('preview' in data/weekend.php); everyone else is sent to the awards page.
* Publish it in wp-admin when it is ready. From then on atfa.com.au/awards (the coming-soon page) forwards to it, until /awards shows the entries page ('forward_awards' in data/weekend.php turns this off). Staff can still see the old page at /awards/?splash=1. Publishing (or taking it back to private) clears WP Rocket's cache, so /awards, the weekend labels on the Expo, breakfast and awards pages, and any menu link change straight away. Publishing the breakfast page does the same, so the weekend page shows its seats as open.
* The Expo, breakfast and awards pages link their "The ATFA weekend" label to it for anyone who can open it.
* Crosbe's open afternoon on Thursday 20 May shows to logged-in staff only, marked Proposed, until Crosbe is public (data/partner.php) and the afternoon is confirmed ('confirmed' in data/weekend.php). With it the page reads "four events over three days"; without it, "three events over two days".
* Sign-ups from the page go on the same list as the awards notify-me (Awards signups), marked "Weekend updates" or "Awards entries".

== Changing things ==

* Who gets the signup emails: define ATFA_AWARDS_NOTIFY_TO in wp-config.php (default nick@atfa.com.au).
* Redirect the old /training-events/annual-awards/ page to /awards/: define ATFA_AWARDS_REDIRECT_OLD_PAGE as true in wp-config.php.
* Page copy and layout: views/splash.php and views/entries.php. Winner photos and captions: atfa_awards_winners() in the main plugin file.
* The award categories (what goes in, rules, example photos) and which stream each one sits in: data/categories.php. Example photos live in assets/img/examples/.
* Category sponsors: add a 'sponsor' (name, logo file, url) to the award in data/categories.php and drop the logo in assets/img/sponsors/. It then shows in that award's panel and in the sponsors strip on both pages. Floor of the Year is sponsored by the Principal Partner (Crosbe), shown wherever the partner shows (staff only until 'public' is true in data/partner.php); visitors see ATFA until then.
* Principal Partner (Crosbe for 2027): data/partner.php holds the name, logo, link and wording, shared by the splash, the entries page and the Expo page. Set 'public' to false there to show the partner to logged-in staff only. Logo files are in assets/img/sponsors/.

== Updating ==

Plugins > Add New > Upload Plugin > choose the new zip > Install Now > "Replace current with uploaded". The pages and signups are kept.
