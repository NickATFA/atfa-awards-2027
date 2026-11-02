<?php
/**
 * The 2027 awards category sponsorship offer, shown on /awards/sponsorship/ (views/sponsorship.php) and used in the
 * request emails.
 *
 * Source: Phil's flyer "2027 Flooring Awards: Category Sponsorship Now Available" (3 pages, November 2026).
 * The categories themselves come from data/categories.php, so the page always matches the awards pages.
 *
 * Who can see the page, the booking link, where requests go and how many categories each sponsor can request are
 * set in wp-admin (Awards sponsorship > Settings), not here.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	// Per category. 'price_note' prints straight after the price, e.g. '+ GST'. Phil's flyer doesn't say either way.
	'price'      => 2950,
	'price_note' => '',

	// Awards that can't be sponsored on their own. Floor of the Year went with the major awards sponsorship.
	'exclude'    => array( 'foty' ),
	'major_note' => 'The major Floor of the Year sponsorship has already been secured by the Major Awards Sponsor and is not available for individual category sponsorship.',

	'dinner'     => array(
		'date'  => 'Saturday 22 May 2027',
		'venue' => 'Novotel Sydney Olympic Park',
		'city'  => 'Sydney',
	),

	// Who requests and questions go to (the request emails go to the address in Settings, Phil by default).
	'contact'    => array(
		'name'  => 'Phil',
		'email' => 'phil@atfa.com.au',
	),

	// "Your category sponsorship includes" (icon: trophy, ticket, screen, megaphone, star).
	'includes'   => array(
		array(
			'icon'  => 'trophy',
			'title' => 'Category naming and brand recognition',
			'text'  => 'Your company recognised as the sponsor of your selected awards category.',
		),
		array(
			'icon'  => 'ticket',
			'title' => 'Two awards dinner tickets',
			'text'  => 'Two tickets to the 2027 ATFA Awards Dinner, valued at $250 each.',
		),
		array(
			'icon'  => 'screen',
			'title' => 'Website recognition',
			'text'  => 'Sponsor recognition alongside your category on ATFA\'s website.',
		),
		array(
			'icon'  => 'megaphone',
			'title' => 'Marketing and promotion',
			'text'  => 'Brand recognition across ATFA marketing and promotional activity for your category in the lead-up to the awards.',
		),
		array(
			'icon'  => 'star',
			'title' => 'Awards night recognition',
			'text'  => 'Your company recognised with the category during the awards dinner and the category presentation.',
		),
	),

	// Where a category sponsor's brand shows through the season (the proposed 2027 dates, as on the awards page).
	'season'     => array(
		array( 'when' => 'From now', 'title' => 'The awards page', 'text' => 'Your logo joins the category sponsors on atfa.com.au/awards while the industry waits for entries to open.' ),
		array( 'when' => '1 Feb to 31 Mar', 'title' => 'Entries open', 'text' => 'Your logo on your category\'s page, which every entrant reads before they enter.' ),
		array( 'when' => '12 Apr to 2 May', 'title' => 'Members vote', 'text' => 'Your brand alongside your category on the ATFA website while members vote and the panel judges.' ),
		array( 'when' => 'Wed 5 May', 'title' => 'Finalists announced', 'text' => 'Your category promoted in ATFA marketing in the lead-up to the night.' ),
		array( 'when' => 'Sat 22 May', 'title' => 'Awards dinner', 'text' => 'Your company recognised as your category is presented, with two seats at the dinner.' ),
	),

	// The rest of ATFA's 2027 sponsorship and advertising (EPOXY).
	'guide_url'  => 'https://epoxy.atfa.com.au/sponsorship',
);
