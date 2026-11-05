<?php
/**
 * The ATFA Flooring Industry Weekend 2027: everything the landing page at /weekend-2027/ says about the weekend as a
 * whole, in one place. Read by includes/weekend.php (atfa_wk_info()) and views/weekend.php.
 *
 * The page pulls the rest from the other pages, so each fact lives in one place only:
 *   Expo        stand count and lowest price from the Expo build (atfa_expo27_summary(), expo/src/geometry.json)
 *   Breakfast   date, times, venue and whether they are confirmed from data/breakfast.php
 *   Awards      the twenty categories and their sponsors from data/categories.php
 *   Partner     Crosbe from data/partner.php (staff only until 'public' is true there)
 *
 * Where the dates came from (Nick, 3 November 2026):
 *   ATFA Program Dates 2027 v10 (16 Oct 2026): Fri 21 May ATFA Expo Sydney; Sat 22 May Ladies Breakfast and Awards
 *   Dinner. The Sydney Showground licence has Hall 5 from 5 am Thursday 20 May (move-in) to midnight Friday 21 May.
 *   Thursday 20 May: Nathan Wood (Crosbe) offered an "open afternoon" at Crosbe the day before the Expo (his email to
 *   Phil, 26 Oct 2026): a look behind the curtain at the plant, a walk through the new R&D space and networking
 *   downstairs. The Crosbe agreement says timing and costs are agreed in writing first, so 'confirmed' is false.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return array(
	'title'   => 'ATFA Flooring Industry Weekend 2027',
	// Who can open the page while it is private (everyone else is sent to the awards page): 'manage_options' is
	// administrators only, 'edit_pages' lets staff (editors) in too.
	// Nick, 5 Nov 2026: staff and admins can open it; visitors still go to /awards until it is published.
	'preview' => 'edit_pages',
	'place'   => 'Sydney Olympic Park',

	// Once this page is published, atfa.com.au/awards (the "awards coming soon" page) forwards here, for as long as
	// /awards still shows that coming-soon page. When the entries page takes over /awards, the forward stops by itself.
	// false keeps /awards as it is.
	'forward_awards' => true,

	// The events, in order. Each shows as a day in the program, a card, a venue and a calendar entry.
	//   when       the date (Sydney time) and 'part' of the day, shown as "Friday 21 May · All day"
	//   time       shown when known, e.g. '8.00 am to 10.00 am'; 'tbc' true adds "times to be confirmed"
	//   page       which page tells the rest: 'expo', 'breakfast', 'awards' or '' (none yet)
	//   partner    true shows the event only where the Principal Partner shows (staff only until Crosbe is public)
	//   confirmed  false shows it to logged-in staff only, marked as proposed, until it is confirmed
	'events'  => array(
		'crosbe'    => array(
			'date'      => '2027-05-20',
			'part'      => 'Afternoon',
			'time'      => '',
			'tbc'       => true,
			'kind'      => 'Behind the scenes',
			'title'     => 'Crosbe Open Afternoon',
			'venue'     => 'Crosbe, Seven Hills',
			'address'   => '1 Viewbrook Close, Seven Hills NSW 2147',
			'map'       => 'https://www.google.com/maps/search/?api=1&query=Crosbe+1+Viewbrook+Close+Seven+Hills+NSW+2147',
			'text'      => 'A look behind the curtain at a working flooring plant: how a bag of leveller is made, tested and brought to market, a walk through Crosbe\'s new R&D lab, and drinks and networking downstairs.',
			'page'      => '',
			'partner'   => true,
			'confirmed' => false,
		),
		'expo'      => array(
			'date'      => '2027-05-21',
			'part'      => 'All day',
			'time'      => '',
			'tbc'       => true,
			'kind'      => 'Trade expo',
			'title'     => 'Expo & Conference',
			'venue'     => 'Sydney Showground, Hall 5',
			'address'   => '1 Showground Road, Sydney Olympic Park NSW 2127',
			'map'       => 'https://www.google.com/maps/search/?api=1&query=Sydney+Showground+Hall+5+Sydney+Olympic+Park',
			'text'      => 'The timber flooring industry in one hall for a day. Exhibitor stands, live demonstrations on the stage, and breakout and seminar space.',
			'page'      => 'expo',
			'partner'   => false,
			'confirmed' => true,
		),
		// The date, times and venue come from data/breakfast.php, so the two pages always agree.
		'breakfast' => array(
			'date'      => '2027-05-22',
			'part'      => 'Morning',
			'kind'      => 'For the ladies of ATFA',
			'title'     => 'Ladies Breakfast',
			'text'      => 'Breakfast and bubbles for the women of the timber flooring industry, the morning of the awards dinner. Complimentary, places limited.',
			'page'      => 'breakfast',
			'partner'   => false,
			'confirmed' => true,
		),
		'dinner'    => array(
			'date'      => '2027-05-22',
			'part'      => 'Evening',
			'time'      => '',
			'tbc'       => true,
			'kind'      => 'The awards night',
			'title'     => 'Awards Dinner',
			'venue'     => 'Novotel Sydney Olympic Park',
			'address'   => '11 Olympic Boulevard, Sydney Olympic Park NSW 2127',
			'map'       => 'https://www.google.com/maps/search/?api=1&query=Novotel+Sydney+Olympic+Park',
			'text'      => 'The 2027 ATFA Flooring Awards are presented on the night: twenty awards, including Floor of the Year, in front of the whole industry.',
			'page'      => 'awards',
			'partner'   => false,
			'confirmed' => true,
		),
	),

	// Dinner tickets: '' says "on sale soon"; a link turns the dinner's buttons into "Book tickets".
	'dinner_tickets' => '',

	// When awards entries open (Sydney time), for the countdown in the awards section.
	'entries_open' => '2027-02-01T00:00:00+11:00',

	// The awards season on the way to the dinner (the proposed dates on the awards pages).
	'season'  => array(
		array( 'when' => 'Now', 'title' => 'Stands on sale', 'text' => 'Pick a stand on the Hall 5 floor plan.' ),
		array( 'when' => 'Mon 1 Feb', 'title' => 'Entries open', 'text' => 'Enter from your phone in about ten minutes.' ),
		array( 'when' => 'Wed 31 Mar', 'title' => 'Entries close', 'text' => '11:59 pm AEDT. ATFA checks every entry.' ),
		array( 'when' => '12 Apr to 2 May', 'title' => 'Members vote', 'text' => '3-2-1 voting. The panel judges at the same time.' ),
		array( 'when' => 'Wed 5 May', 'title' => 'Finalists announced', 'text' => 'Top three in every project category.' ),
	),

	// The photos for the dinner, bundled with the plugin (assets/img/weekend/): the 2025 Gala Dinner and Awards Night
	// in Melbourne, 18 October 2025 (the professional shoot in the 2025Awards SharePoint site, Photographer > For mag).
	'dinner_photos' => array(
		array( 'file' => 'weekend/dinner-table.jpg', 'alt' => 'A table set for the 2025 ATFA awards dinner', 'pos' => '50% 60%' ),
		array( 'file' => 'weekend/dinner-applause.jpg', 'alt' => 'Guests applauding a winner at the 2025 ATFA awards dinner', 'pos' => '62% 40%' ),
		array( 'file' => 'weekend/dinner-drinks.jpg', 'alt' => 'Pre-dinner drinks at the 2025 ATFA awards dinner', 'pos' => '50% 55%' ),
	),

	// Updates sign-up: who gets an email for each one (the same list as the awards notify-me, Awards signups).
	'updates' => true,
);
