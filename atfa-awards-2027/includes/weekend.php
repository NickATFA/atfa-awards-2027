<?php
/**
 * The ATFA Flooring Industry Weekend 2027: the landing page at /weekend-2027/ that brings the Expo & Conference, the
 * Ladies Breakfast and the Awards Dinner together (and Crosbe's open afternoon on the Thursday, once it is confirmed).
 *
 * - The page is made the first time this version runs, private (administrators only, 'preview' in data/weekend.php)
 *   on the blank canvas template with the [atfa_awards_weekend] shortcode. Anyone else who goes to the address is
 *   sent to the awards page until it is published in wp-admin.
 * - Once it is published, atfa.com.au/awards (the "awards coming soon" page) forwards here for as long as /awards
 *   still shows that page ('forward_awards' in data/weekend.php). Staff can still see the old page with ?splash=1.
 * - The Expo, breakfast and awards pages link their "The ATFA weekend" label here (atfa_wk_label()), for the
 *   preview people now and for everyone once the page is published.
 * - Everything the page says about the weekend as a whole is in data/weekend.php; each event's own details come from
 *   its own page's data, so nothing is written down twice.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ATFA_WK_SLUG', 'weekend-2027' );

/**
 * The weekend's details (data/weekend.php), with every key present.
 */
function atfa_wk_info() {
	static $info = null;
	if ( null !== $info ) {
		return $info;
	}
	$file = ATFA_AWARDS_DIR . 'data/weekend.php';
	$info = file_exists( $file ) ? include $file : array();
	$info = is_array( $info ) ? $info : array();
	$info = array_merge( array(
		'title'          => 'ATFA Flooring Industry Weekend 2027',
		'preview'        => 'manage_options',
		'place'          => 'Sydney Olympic Park',
		'forward_awards' => true,
		'events'         => array(),
		'dinner_tickets' => '',
		'entries_open'   => '2027-02-01T00:00:00+11:00',
		'season'         => array(),
		'dinner_photos'  => array(),
		'updates'        => true,
	), $info );
	return $info;
}

/* ---------------------------------------------------------------------------
 * The page: where it is, who can see it, and the forward from /awards
 * ------------------------------------------------------------------------ */

function atfa_wk_page_id() {
	$pages = get_option( 'atfa_awards_pages' );
	$id    = ( is_array( $pages ) && ! empty( $pages['weekend'] ) ) ? (int) $pages['weekend'] : 0;
	return ( $id && 'page' === get_post_type( $id ) ) ? $id : 0;
}

/**
 * The page's address, built from its path so a private page never gives out a ?page_id= link.
 */
function atfa_wk_page_url() {
	global $wp_rewrite;
	$id = atfa_wk_page_id();
	if ( ! $id ) {
		return home_url( '/' . ATFA_WK_SLUG . '/' );
	}
	if ( $wp_rewrite && $wp_rewrite->using_permalinks() ) {
		return home_url( user_trailingslashit( get_page_uri( $id ), 'page' ) );
	}
	return home_url( '?page_id=' . $id );
}

/**
 * Whether this person may open the page while it is private ('preview' in data/weekend.php names the capability).
 */
function atfa_wk_is_staff() {
	$cap = (string) atfa_wk_info()['preview'];
	return is_user_logged_in() && current_user_can( '' !== $cap ? $cap : 'manage_options' );
}

function atfa_wk_is_public() {
	$id = atfa_wk_page_id();
	return $id && 'publish' === get_post_status( $id );
}

/**
 * Whether this visitor can open the page: everyone once it is published, the preview people before that.
 */
function atfa_wk_visible() {
	return atfa_wk_page_id() && ( atfa_wk_is_public() || atfa_wk_is_staff() );
}

function atfa_wk_is_page() {
	$id = atfa_wk_page_id();
	return $id && is_page( $id );
}

/**
 * Makes the page the first time this version runs: /weekend-2027/, private, on the blank canvas template.
 */
function atfa_wk_ensure_page() {
	if ( get_option( 'atfa_wk_page_checked' ) === ATFA_AWARDS_VERSION ) {
		return;
	}
	$pages = get_option( 'atfa_awards_pages' );
	$pages = is_array( $pages ) ? $pages : array();
	$id    = atfa_wk_page_id();
	if ( $id && 'trash' === get_post_status( $id ) ) {
		$id = 0;
	}
	if ( ! $id ) {
		$found = get_posts( array(
			'post_type'   => 'page',
			'post_status' => array( 'publish', 'private', 'draft', 'pending', 'future' ),
			'meta_key'    => '_atfa_awards_page', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'  => 'weekend', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'numberposts' => 1,
			'fields'      => 'ids',
		) );
		$id = $found ? (int) $found[0] : 0;
	}
	if ( ! $id ) {
		$id = wp_insert_post( array(
			'post_type'      => 'page',
			'post_title'     => atfa_wk_info()['title'],
			'post_name'      => ATFA_WK_SLUG,
			'post_status'    => 'private',
			'post_content'   => '[atfa_awards_weekend]',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'meta_input'     => array( '_atfa_awards_page' => 'weekend' ),
		), true );
		if ( is_wp_error( $id ) || ! $id ) {
			return;
		}
	}
	update_post_meta( $id, '_wp_page_template', 'atfa-awards-blank.php' );
	$pages['weekend'] = (int) $id;
	update_option( 'atfa_awards_pages', $pages );
	update_option( 'atfa_wk_page_checked', ATFA_AWARDS_VERSION );
}
add_action( 'init', 'atfa_wk_ensure_page', 20 );

/**
 * The awards page (/awards/) when it is published and still shows the "awards coming soon" page.
 */
function atfa_wk_splash_id() {
	$pages = get_option( 'atfa_awards_pages' );
	$id    = ( is_array( $pages ) && ! empty( $pages['splash'] ) ) ? (int) $pages['splash'] : 0;
	if ( ! $id || 'publish' !== get_post_status( $id ) ) {
		return 0;
	}
	return false !== strpos( (string) get_post_field( 'post_content', $id ), '[atfa_awards_splash' ) ? $id : 0;
}

/**
 * Whether /awards forwards here: the weekend page is published, the data file allows it, and /awards still shows the
 * coming-soon page. When the entries page takes over /awards, this turns itself off.
 */
function atfa_wk_forwarding() {
	return ! empty( atfa_wk_info()['forward_awards'] ) && atfa_wk_is_public() && atfa_wk_splash_id();
}

/**
 * Where "the awards" links go on every page: the awards section of this page while /awards forwards here, otherwise
 * /awards itself.
 */
function atfa_wk_awards_url() {
	if ( atfa_wk_forwarding() ) {
		return atfa_wk_page_url() . '#awards';
	}
	return home_url( '/awards/' );
}

/**
 * While the page is private, anyone outside the preview group who goes to its address is sent to the awards page
 * (WordPress would show visitors a 404, and logged-in staff could otherwise read a private page). Temporary (302).
 */
function atfa_wk_hidden_redirect() {
	global $wp;
	if ( is_admin() ) {
		return;
	}
	$id = atfa_wk_page_id();
	if ( ! $id || 'publish' === get_post_status( $id ) ) {
		return;
	}
	$here = is_page( $id ) || ( is_404() && isset( $wp->request ) && trim( (string) $wp->request, '/' ) === ATFA_WK_SLUG );
	if ( ! $here || atfa_wk_is_staff() ) {
		return;
	}
	$pages = get_option( 'atfa_awards_pages' );
	$to    = ( is_array( $pages ) && ! empty( $pages['splash'] ) && 'publish' === get_post_status( (int) $pages['splash'] ) ) ? get_permalink( (int) $pages['splash'] ) : home_url( '/' );
	nocache_headers();
	wp_safe_redirect( add_query_arg( 'from', ATFA_WK_SLUG, $to ), 302 );
	exit;
}
add_action( 'template_redirect', 'atfa_wk_hidden_redirect', 5 );

/**
 * Once the page is published, /awards forwards here while it still shows the coming-soon page. Temporary (302), so
 * browsers forget it when the entries page takes over. Campaign tags on the link (utm_ and the like) come along.
 * Staff can still see the old page at /awards/?splash=1.
 */
function atfa_wk_forward_awards() {
	if ( is_admin() || ! atfa_wk_forwarding() ) {
		return;
	}
	$splash = atfa_wk_splash_id();
	if ( ! is_page( $splash ) ) {
		return;
	}
	if ( isset( $_GET['splash'] ) && is_user_logged_in() && current_user_can( 'edit_pages' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$to   = atfa_wk_page_url();
	$args = array();
	foreach ( (array) $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = sanitize_key( $key );
		if ( '' !== $key && is_string( $value ) && 'splash' !== $key ) {
			$args[ $key ] = rawurlencode( sanitize_text_field( wp_unslash( $value ) ) );
		}
	}
	if ( $args ) {
		$to = add_query_arg( $args, $to );
	}
	nocache_headers();
	wp_safe_redirect( $to, 302 );
	exit;
}
add_action( 'template_redirect', 'atfa_wk_forward_awards', 4 );

/**
 * When the page is published or taken back to private, clear WP Rocket's cached copy of /awards (and of this page),
 * so visitors get the forward (or the coming-soon page back) straight away rather than an old cached copy.
 */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( $new === $old || ! $post || (int) $post->ID !== atfa_wk_page_id() ) {
		return;
	}
	if ( ! function_exists( 'rocket_clean_post' ) ) {
		return;
	}
	$pages = get_option( 'atfa_awards_pages' );
	if ( is_array( $pages ) && ! empty( $pages['splash'] ) ) {
		rocket_clean_post( (int) $pages['splash'] );
	}
	rocket_clean_post( (int) $post->ID );
}, 10, 3 );

/**
 * Menu links to the page only show for people who can open it while it's private (JetMenu reads the items
 * directly, so both filters are needed, as for the Expo and breakfast pages).
 */
function atfa_wk_menu_items( $items ) {
	if ( is_admin() || ! is_array( $items ) ) {
		return $items;
	}
	$id = atfa_wk_page_id();
	if ( ! $id || 'publish' === get_post_status( $id ) || current_user_can( 'read_post', $id ) ) {
		return $items;
	}
	foreach ( $items as $key => $item ) {
		if ( isset( $item->type, $item->object_id ) && 'post_type' === $item->type && (int) $item->object_id === $id ) {
			unset( $items[ $key ] );
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'atfa_wk_menu_items' );
add_filter( 'wp_get_nav_menu_items', function ( $items ) {
	$out = atfa_wk_menu_items( $items );
	return is_array( $out ) ? array_values( $out ) : $out;
} );

add_shortcode( 'atfa_awards_weekend', function () {
	ob_start();
	include ATFA_AWARDS_DIR . 'views/weekend.php';
	return ob_get_clean();
} );

/* ---------------------------------------------------------------------------
 * The weekend label on the other pages
 * ------------------------------------------------------------------------ */

/**
 * "The ATFA weekend · Sydney Olympic Park · 21 and 22 May 2027" for the weekend bar on the Expo, breakfast and awards
 * pages: a link to this page for anyone who can open it, plain text for everyone else. $class is the bar's own
 * label class (xp-wk-l, bk-wk-l and so on).
 */
function atfa_wk_label( $class ) {
	$text = '<span class="wkl-a">The ATFA weekend</span> · Sydney Olympic Park · 21 and 22 May 2027';
	if ( ! atfa_wk_visible() ) {
		return '<span class="' . esc_attr( $class ) . '">' . $text . '</span>';
	}
	return '<a class="' . esc_attr( $class ) . ' wkl" href="' . esc_url( atfa_wk_page_url() ) . '">' . $text
		. ' <span class="wkl-go">See the whole weekend<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg></span></a>';
}

/* ---------------------------------------------------------------------------
 * The events, as this visitor sees them
 * ------------------------------------------------------------------------ */

/**
 * The Expo's stand count and lowest price, from the Expo build (so they always match the floor plan).
 */
function atfa_wk_expo_summary() {
	$s = function_exists( 'atfa_expo27_summary' ) ? atfa_expo27_summary() : array();
	return array_merge( array( 'stands' => 58, 'from' => 1400, 'note' => 'excl. GST' ), is_array( $s ) ? $s : array() );
}

/**
 * Where each page is, for this visitor ('' when they can't open it yet). The awards live in this page's own awards
 * section until the entries page takes over /awards.
 */
function atfa_wk_page_links() {
	return array(
		'expo'      => home_url( '/expo-2027/' ),
		'breakfast' => function_exists( 'atfa_awards_breakfast_url' ) ? atfa_awards_breakfast_url() : '',
		'awards'    => atfa_wk_entries_live() ? home_url( '/awards/' ) : '#awards',
	);
}

/**
 * Whether /awards is the entries page now (its content is the entries shortcode), so entries are open.
 */
function atfa_wk_entries_live() {
	$pages = get_option( 'atfa_awards_pages' );
	$id    = ( is_array( $pages ) && ! empty( $pages['splash'] ) ) ? (int) $pages['splash'] : 0;
	return $id && 'publish' === get_post_status( $id ) && false !== strpos( (string) get_post_field( 'post_content', $id ), '[atfa_awards_entries' );
}

/**
 * The events this visitor sees, in order, with everything the page needs worked out: the day labels, the time line,
 * the link and its wording, and whether it is still a proposal. The breakfast's date, times and venue come from
 * data/breakfast.php.
 */
function atfa_wk_events() {
	static $out = null;
	if ( null !== $out ) {
		return $out;
	}
	$info    = atfa_wk_info();
	$links   = atfa_wk_page_links();
	$partner = function_exists( 'atfa_awards_partner' ) ? atfa_awards_partner() : null;
	$staff   = is_user_logged_in() && current_user_can( 'edit_pages' );
	$expo    = atfa_wk_expo_summary();
	$out     = array();
	foreach ( (array) $info['events'] as $key => $ev ) {
		$ev = array_merge( array(
			'date' => '', 'part' => '', 'time' => '', 'tbc' => true, 'kind' => '', 'title' => '', 'venue' => '', 'room' => '',
			'address' => '', 'map' => '', 'text' => '', 'page' => '', 'partner' => false, 'confirmed' => true,
		), (array) $ev );
		if ( ! empty( $ev['partner'] ) && ! $partner ) {
			continue;
		}
		if ( empty( $ev['confirmed'] ) && ! $staff ) {
			continue;
		}
		// The breakfast's own data wins, so the two pages never disagree.
		if ( 'breakfast' === $key && function_exists( 'atfa_bk_info' ) ) {
			$bk              = atfa_bk_info();
			$ev['date']      = $bk['date'];
			$ev['time']      = (string) $bk['time_text'];
			$ev['tbc']       = empty( $bk['venue']['confirmed'] );
			$ev['venue']     = '' !== $bk['venue']['name'] ? $bk['venue']['name'] : 'Sydney Olympic Park';
			$ev['room']      = (string) $bk['venue']['room'];
			$ev['address']   = (string) $bk['venue']['address'];
			$ev['map']       = (string) $bk['venue']['map'];
			$ev['venue_tbc'] = empty( $bk['venue']['confirmed'] );
		}
		$ts = strtotime( $ev['date'] . ' 12:00:00' );
		if ( ! $ts ) {
			continue;
		}
		$ev['key']      = $key;
		$ev['dw']       = gmdate( 'D', $ts );
		$ev['dn']       = gmdate( 'j', $ts );
		$ev['dm']       = gmdate( 'M', $ts );
		$ev['day']      = gmdate( 'l j F', $ts );
		$ev['short']    = gmdate( 'D j M', $ts );
		$ev['proposed'] = empty( $ev['confirmed'] );
		$ev['venue_tbc'] = ! empty( $ev['venue_tbc'] );
		$ev['when']     = $ev['day'] . ( '' !== $ev['part'] ? ' · ' . strtolower( $ev['part'] ) : '' );
		$ev['time_line'] = '' !== $ev['time'] ? $ev['time'] . ( $ev['tbc'] ? ', to be confirmed' : '' ) : ( $ev['tbc'] ? 'Times to be confirmed' : '' );
		$ev['facts']    = array();
		$ev['url']      = '';
		$ev['cta']      = '';
		$ev['soon']     = '';
		switch ( $ev['page'] ) {
			case 'expo':
				$ev['url']     = $links['expo'] . '#floor-plan';
				$ev['cta']     = 'See the Expo and floor plan';
				$ev['facts'][] = (int) $expo['stands'] . ' stands from $' . number_format( (int) $expo['from'] ) . ' ' . $expo['note'];
				$ev['facts'][] = 'Live demonstration stage';
				break;
			case 'breakfast':
				$ev['url']     = '' !== $links['breakfast'] ? $links['breakfast'] . '#rsvp' : '';
				$ev['cta']     = 'Details and reservations';
				$ev['soon']    = 'Reservations open soon';
				$ev['facts'][] = 'Complimentary, places limited';
				break;
			case 'awards':
				$ev['url']     = $links['awards'];
				$ev['cta']     = 'See the awards';
				$ev['facts'][] = '' !== (string) $info['dinner_tickets'] ? 'Tickets on sale now' : 'Tickets on sale soon';
				$ev['facts'][] = 'Twenty awards presented';
				break;
		}
		if ( $ev['proposed'] ) {
			$ev['soon'] = 'Proposed: time to be agreed with Crosbe';
		}
		$out[ $key ] = $ev;
	}
	return $out;
}

/**
 * The events grouped by day, for the program.
 */
function atfa_wk_days() {
	$days = array();
	foreach ( atfa_wk_events() as $ev ) {
		$days[ $ev['date'] ][] = $ev;
	}
	return $days;
}

/**
 * The dates in words: "20 to 22 May 2027" or "21 and 22 May 2027".
 */
function atfa_wk_date_range() {
	$dates = array_keys( atfa_wk_days() );
	if ( ! $dates ) {
		return 'May 2027';
	}
	$first = strtotime( reset( $dates ) . ' 12:00:00' );
	$last  = strtotime( end( $dates ) . ' 12:00:00' );
	if ( $first === $last ) {
		return gmdate( 'j F Y', $first );
	}
	$joiner = count( $dates ) > 2 ? ' to ' : ' and ';
	if ( gmdate( 'm', $first ) === gmdate( 'm', $last ) ) {
		return gmdate( 'j', $first ) . $joiner . gmdate( 'j F Y', $last );
	}
	return gmdate( 'j F', $first ) . $joiner . gmdate( 'j F Y', $last );
}

/**
 * "Three events over two days" and the like, from what this visitor sees.
 */
function atfa_wk_count_words() {
	$words  = array( 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six' );
	$events = count( atfa_wk_events() );
	$days   = count( atfa_wk_days() );
	return array(
		'events' => isset( $words[ $events ] ) ? $words[ $events ] : (string) $events,
		'days'   => isset( $words[ $days ] ) ? $words[ $days ] : (string) $days,
		'n_ev'   => $events,
		'n_days' => $days,
	);
}

/* ---------------------------------------------------------------------------
 * "Add to my calendar": the weekend as a calendar file
 *
 * /wp-json/atfa-awards/v1/weekend/calendar answers with an .ics file of the events a visitor sees (all-day entries,
 * since the times are still to be confirmed). Phones open it straight into the calendar app.
 * ------------------------------------------------------------------------ */

add_action( 'rest_api_init', function () {
	register_rest_route( 'atfa-awards/v1', '/weekend/calendar', array(
		'methods'             => 'GET',
		'callback'            => 'atfa_wk_rest_calendar',
		'permission_callback' => '__return_true',
	) );
} );

function atfa_wk_ics_text( $s ) {
	$s = str_replace( array( '\\', ';', ',', "\r\n", "\n", "\r" ), array( '\\\\', '\;', '\,', '\n', '\n', '' ), (string) $s );
	return $s;
}

/**
 * Lines longer than 75 bytes are folded onto continuation lines that start with a space (RFC 5545).
 */
function atfa_wk_ics_fold( $line ) {
	$out = '';
	while ( strlen( $line ) > 74 ) {
		$cut  = function_exists( 'mb_strcut' ) ? mb_strcut( $line, 0, 74, 'UTF-8' ) : substr( $line, 0, 74 );
		$out .= $cut . "\r\n ";
		$line = substr( $line, strlen( $cut ) );
	}
	return $out . $line . "\r\n";
}

function atfa_wk_calendar() {
	$url   = atfa_wk_page_url();
	$lines = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//ATFA//Flooring Industry Weekend 2027//EN',
		'CALSCALE:GREGORIAN',
		'METHOD:PUBLISH',
		'X-WR-CALNAME:' . atfa_wk_ics_text( atfa_wk_info()['title'] ),
	);
	$stamp = gmdate( 'Ymd\THis\Z' );
	foreach ( atfa_wk_events() as $ev ) {
		$start = strtotime( $ev['date'] . ' 12:00:00' );
		$desc  = $ev['text'] . "\n" . ( '' !== $ev['time_line'] ? $ev['time_line'] . ".\n" : '' ) . $url;
		$where = $ev['venue'] . ( '' !== $ev['room'] ? ', ' . $ev['room'] : '' ) . ( '' !== $ev['address'] ? ', ' . $ev['address'] : '' );
		$lines = array_merge( $lines, array(
			'BEGIN:VEVENT',
			'UID:' . $ev['key'] . '-2027@atfa.com.au',
			'DTSTAMP:' . $stamp,
			'DTSTART;VALUE=DATE:' . gmdate( 'Ymd', $start ),
			'DTEND;VALUE=DATE:' . gmdate( 'Ymd', $start + DAY_IN_SECONDS ),
			'SUMMARY:' . atfa_wk_ics_text( 'ATFA ' . $ev['title'] . ( $ev['proposed'] ? ' (proposed)' : '' ) ),
			'LOCATION:' . atfa_wk_ics_text( $where ),
			'DESCRIPTION:' . atfa_wk_ics_text( $desc ),
			'URL:' . $url,
			'TRANSP:TRANSPARENT',
			'END:VEVENT',
		) );
	}
	$lines[] = 'END:VCALENDAR';
	$out     = '';
	foreach ( $lines as $line ) {
		$out .= atfa_wk_ics_fold( $line );
	}
	return $out;
}

function atfa_wk_rest_calendar() {
	$ics = atfa_wk_calendar();
	nocache_headers();
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="ATFA-Industry-Weekend-2027.ics"' );
	header( 'Content-Length: ' . strlen( $ics ) );
	echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a calendar file, not HTML
	exit;
}

/* ---------------------------------------------------------------------------
 * The staff bar on the page
 * ------------------------------------------------------------------------ */

/**
 * Preview people only (never cached for visitors): whether the page is live, what /awards does, and the links.
 */
function atfa_wk_staff_bar() {
	if ( ! atfa_wk_is_staff() ) {
		return '';
	}
	$info    = atfa_wk_info();
	$public  = atfa_wk_is_public();
	$id      = atfa_wk_page_id();
	$admins  = 'manage_options' === (string) $info['preview'];
	$splash  = atfa_wk_splash_id();
	$notes   = array();
	if ( $public ) {
		$status = 'This page is live.';
		if ( atfa_wk_forwarding() ) {
			$notes[] = 'atfa.com.au/awards forwards here while it still shows the coming-soon page (/awards/?splash=1 shows you the old page)';
		} elseif ( empty( $info['forward_awards'] ) ) {
			$notes[] = '/awards does not forward here (forward_awards is off in data/weekend.php)';
		} else {
			$notes[] = '/awards no longer shows the coming-soon page, so it does not forward here';
		}
	} else {
		$status  = ( $admins ? 'Administrators only for now. Everyone else, staff included, is sent to the awards page' : 'Staff only for now. Visitors are sent to the awards page' ) . ' until this page is published in wp-admin.';
		$notes[] = $splash && ! empty( $info['forward_awards'] )
			? 'once it is published, atfa.com.au/awards forwards here until the entries page takes over'
			: 'publishing it leaves /awards as it is';
	}
	$all = isset( $info['events']['crosbe'] ) ? (array) $info['events']['crosbe'] : array();
	if ( $all ) {
		$p = function_exists( 'atfa_awards_partner' ) ? atfa_awards_partner() : null;
		$pub_partner = $p && ! empty( $p['public'] );
		if ( empty( $all['confirmed'] ) || ! $pub_partner ) {
			$notes[] = 'Thursday at Crosbe shows to staff only until ' . ( empty( $all['confirmed'] ) && ! $pub_partner ? 'Crosbe is public and the afternoon is confirmed' : ( empty( $all['confirmed'] ) ? 'the afternoon is confirmed' : 'Crosbe is public' ) ) . ' (data/weekend.php, data/partner.php)';
		}
	}
	$signups = (int) wp_count_posts( 'atfa_notify' )->publish;
	$notes[] = $signups . ' on the updates list';
	$btns    = '';
	if ( $id ) {
		$btns .= '<a class="wk-sbtn" href="' . esc_url( get_edit_post_link( $id ) ) . '">' . ( $public ? 'Edit page' : 'Publish page' ) . '</a>';
	}
	$btns .= '<a class="wk-sbtn is-ghost" href="' . esc_url( admin_url( 'edit.php?post_type=atfa_notify' ) ) . '">Updates list</a>';
	$pages = get_option( 'atfa_awards_pages' );
	if ( is_array( $pages ) && ! empty( $pages['entries'] ) ) {
		$btns .= '<a class="wk-sbtn is-ghost" href="' . esc_url( get_permalink( (int) $pages['entries'] ) ) . '">Entries page preview</a>';
	}
	return '<div class="wk-staff" role="region" aria-label="Staff tools">'
		. '<span class="wk-staff-tag">' . ( $admins && ! $public ? 'Admins only' : 'Staff only' ) . '</span>'
		. '<div class="wk-staff-text"><span class="wk-staff-status' . ( $public ? '' : ' is-warn' ) . '">' . esc_html( $status ) . '</span> '
		. '<span class="wk-staff-note">' . esc_html( ucfirst( implode( '; ', $notes ) ) . '.' ) . '</span></div>'
		. '<div class="wk-staff-btns">' . $btns . '</div></div>';
}
