<?php
/**
 * The Ladies Breakfast 2027: everything the page at /ladies-breakfast-2027/ says about the event, in one place.
 *
 * Read by includes/breakfast.php (atfa_bk_info()) and views/breakfast.php. Change a line here, release the plugin,
 * and the page follows.
 *
 * Where it came from (Nick, 2 November 2026):
 *   Date      ATFA Program Dates 2027 v10 and the 2027 Sponsor Events Calendar: Saturday 22 May, the morning of
 *             the awards dinner, the day after the Expo.
 *   Venue     The calendar in Phil's sponsorship email (20 Oct 2026) says Pullman Hotel, Homebush; the Pullman
 *             Sydney Olympic Park proposal (ATFA Files > 2027 > 2027 Expo, April 2026) is the Echelon Room,
 *             breakfast 7 to 10 am, 30 guests. The dates sheet still says "location TBC", so 'confirmed' is false
 *             until Phil has the contract: the page then says "to be confirmed" next to the venue and times.
 *   Price     Every past breakfast was complimentary for the ladies of ATFA, paid for by the breakfast sponsor
 *             (2023 Bulcs, Star Casino Gold Coast; 2025 Fiddes, Pullman Melbourne, "Free (AUD 0)" in the board
 *             papers). 2027 isn't decided: change 'price' if it is ticketed.
 *   Sponsor   None yet. The sponsor calendar lists "Breakfast sponsorship: price on enquiry". Fill in 'sponsor'
 *             when one is confirmed and the placeholder tile becomes their logo.
 *   Photos    Media Library IDs. 2026: the Women in Flooring Lunch at Rippon Lea, 18 September 2026 (the
 *             professional shoot in ATFA Files > 2026 > 2026 Events_MASTER). 2025: the Women in Flooring
 *             Luncheon, Melbourne, 28 February 2025 (ATFA's Facebook album). 'hero' picks the ones that rotate
 *             behind the headline; the rest fill the gallery.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return array(
	'title'     => 'Ladies Breakfast 2027',
	'date'      => '2027-05-22',
	'date_text' => 'Saturday 22 May 2027',
	'day_text'  => 'Saturday morning, 22 May 2027',
	// Doors and finish. The Pullman proposal has breakfast from 7 to 10 am; 2025 in Melbourne ran 8 to 10 am.
	'time_text' => '8.00 am to 10.00 am',
	'venue'     => array(
		'name'      => 'Pullman Sydney Olympic Park',
		'room'      => 'Echelon Room',
		'area'      => 'Sydney Olympic Park (Homebush)',
		'address'   => '9 Olympic Boulevard, Sydney Olympic Park NSW 2127',
		'map'       => 'https://www.google.com/maps/search/?api=1&query=Pullman+Sydney+Olympic+Park',
		'note'      => 'Next door to the Novotel, where the awards dinner is held that night, and twenty minutes from the Sydney CBD and the airport.',
		// False while the venue and times are still being settled with the hotel: the page says "to be confirmed".
		'confirmed' => false,
	),
	'price'     => array(
		'text'   => 'Complimentary',
		'detail' => 'For the ladies of ATFA, with thanks to the breakfast sponsor. Places are limited.',
	),
	'places'    => 30,
	'host'      => 'Hosted by ATFA',
	'rsvp'      => array(
		// False closes the form (the page says reservations have closed and gives the email instead).
		'open'    => true,
		'to'      => 'cherie@atfa.com.au',
		'cc'      => 'nick@atfa.com.au',
		'contact' => 'Cherie',
		'email'   => 'cherie@atfa.com.au',
	),
	// The breakfast sponsor. Leave 'img' empty for the placeholder tile. Logos go in assets/img/sponsors/.
	'sponsor'   => array( 'name' => '', 'img' => '', 'url' => '' ),
	'sponsor_contact' => array( 'name' => 'Phil', 'email' => 'phil@atfa.com.au' ),
	'photos'    => array(
		'2026' => array(
			'label' => 'Women in Flooring Lunch, Rippon Lea, September 2026',
			'short' => 'Rippon Lea 2026',
			'ids'   => array(
				19931, 19925, 19924, 19928, 19926, 19921, 19920, 19929, 19930, 19927,
				19923, 19935, 19936, 19934, 19932, 19922, 19933, 19919,
			),
		),
		'2025' => array(
			'label' => 'Women in Flooring Luncheon, Melbourne, February 2025',
			'short' => 'Melbourne 2025',
			'ids'   => array(
				19841, 19873, 19872, 19876, 19838, 19909, 19866, 19875, 19834, 19814,
				19815, 19816, 19817, 19818, 19819, 19820, 19821, 19822, 19823, 19824,
				19825, 19826, 19828, 19829, 19830, 19831, 19832, 19833, 19835, 19836,
				19837, 19839, 19840, 19842, 19843, 19844, 19845, 19846, 19847, 19848,
				19849, 19850, 19851, 19852, 19853, 19854, 19855, 19856, 19857, 19858,
				19859, 19860, 19861, 19862, 19863, 19864, 19865, 19867, 19868, 19869,
				19870, 19871, 19874, 19877, 19878, 19879, 19880, 19881, 19882, 19883,
				19884, 19885, 19886, 19887, 19888, 19889, 19890, 19891, 19892, 19893,
				19894, 19895, 19896, 19897, 19898, 19899, 19900, 19901, 19902, 19903,
				19904, 19905, 19906, 19907, 19908, 19910, 19911, 19912, 19913, 19914,
				19915, 19916, 19917, 19918,
			),
		),
	),
	// Behind the headline, in this order. Landscape photos with people in them work best.
	'hero'      => array( 19931, 19925, 19841, 19928 ),
);
