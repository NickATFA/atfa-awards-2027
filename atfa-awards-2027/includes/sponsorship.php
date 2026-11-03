<?php
/**
 * 2027 Awards category sponsorship: the page at /awards/sponsorship/ (views/sponsorship.php), who can see it, which
 * categories are still available, and requests.
 *
 * WHO SEES WHAT (Awards sponsorship > Settings)
 *   The page is a private WordPress page, so it stays out of menus, site search, feeds and sitemaps.
 *   - Hidden (the default, and the setting from 2 November 2026): administrators only. Everyone else, staff and
 *     Platinum Members with a code included, is sent to the awards page, as with the Ladies Breakfast page.
 *   - Platinum first look (from 3 November 2026): Platinum Members with a live access code see the whole page,
 *     greeted by company name; administrators see it with a staff bar; anyone else, Editors included, gets the code
 *     screen (an Editor with a code sees what members see).
 *   - Everyone: nobody needs a code, and Editors get the staff bar too.
 *
 * ACCESS CODES
 *   One per Platinum Member, made in wp-admin (Awards sponsorship > Access codes). Typing the code on the code screen,
 *   or opening the personal link /awards/sponsorship/?code=ABCD-EFGH, remembers that device for 90 days (a cookie
 *   tied to the code, so stopping or renewing a code shuts it out straight away). Ten wrong tries an hour per
 *   address, then the code screen asks people to wait.
 *
 * REQUESTS (booked by hand for now)
 *   Picking categories and sending the form saves the request, puts those categories on hold, emails Phil (copy to
 *   Nick) and sends the member a confirmation. Staff confirm or release requests in wp-admin. A request sent by a
 *   logged-in staff member is a test: it goes only to them and holds nothing.
 *   When the categories are on EPOXY, put the link in Settings and the page sends people there instead of the form.
 *
 * Nothing on this page is cached by WP Rocket or the browser, and search engines are asked not to index it.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ATFA_AWS_COOKIE', 'atfa_aws27' );
define( 'ATFA_AWS_SETTINGS', 'atfa_aws_settings' );
define( 'ATFA_AWS_STATUS', 'atfa_aws_status' );
define( 'ATFA_AWS_CODES', 'atfa_aws_codes' );
define( 'ATFA_AWS_REQUEST', 'atfa_aws_request' );
// Code letters: no 0/O or 1/I/L to misread.
define( 'ATFA_AWS_ALPHABET', 'ABCDEFGHJKMNPQRSTUVWXYZ23456789' );

/* ---------------------------------------------------------------------------
 * The offer, settings and the page
 * ------------------------------------------------------------------------ */

function atfa_aws_offer() {
	static $offer = null;
	if ( null === $offer ) {
		$offer = include ATFA_AWARDS_DIR . 'data/sponsorship.php';
	}
	return $offer;
}

function atfa_aws_price() {
	$offer = atfa_aws_offer();
	return '$' . number_format( (float) $offer['price'] ) . ( '' !== $offer['price_note'] ? ' ' . $offer['price_note'] : '' );
}

/**
 * Settings (Awards sponsorship > Settings).
 *   access       'admin' (hidden: administrators only), 'codes' (staff and code holders) or 'public' (everyone).
 *   public_from  When the categories go on the ATFA website, in words (optional, shown on the page).
 *   limit        Categories each sponsor can request in this round (Phil's flyer: two).
 *   book_url     The EPOXY link. Empty: the request form (booked by hand). Set: the page sends people there.
 *   to, cc       Who gets each request.
 */
function atfa_aws_settings() {
	$saved = get_option( ATFA_AWS_SETTINGS, array() );
	$s     = array_merge(
		array(
			'access'      => 'admin',
			'public_from' => '',
			'limit'       => 2,
			'book_url'    => '',
			'to'          => 'phil@atfa.com.au',
			'cc'          => 'nick@atfa.com.au',
		),
		is_array( $saved ) ? $saved : array()
	);
	$s['access'] = in_array( $s['access'], array( 'admin', 'codes', 'public' ), true ) ? $s['access'] : 'admin';
	$s['limit']  = max( 1, min( 30, (int) $s['limit'] ) );
	return $s;
}

function atfa_aws_page_id() {
	$pages = get_option( 'atfa_awards_pages' );
	$id    = ( is_array( $pages ) && ! empty( $pages['sponsorship'] ) ) ? (int) $pages['sponsorship'] : 0;
	return ( $id && 'page' === get_post_type( $id ) ) ? $id : 0;
}

/**
 * The page's address. Built from its path rather than get_permalink(), which gives anyone who can't read private
 * pages (every visitor, and the emails sent while a member asks) a ?page_id= link instead.
 */
function atfa_aws_page_url() {
	global $wp_rewrite;
	$id = atfa_aws_page_id();
	if ( ! $id ) {
		return home_url( '/awards/sponsorship/' );
	}
	if ( $wp_rewrite && $wp_rewrite->using_permalinks() ) {
		return home_url( user_trailingslashit( get_page_uri( $id ), 'page' ) );
	}
	return home_url( '?page_id=' . $id );
}

function atfa_aws_personal_link( $code ) {
	return add_query_arg( 'code', rawurlencode( (string) $code ), atfa_aws_page_url() );
}

/**
 * Makes the page the first time this version runs: /awards/sponsorship/ (a child of the awards page), private,
 * on the blank canvas template, with the [atfa_awards_sponsorship] shortcode. Kept in the atfa_awards_pages option
 * with the other awards pages, so it gets the share card too.
 */
function atfa_aws_ensure_page() {
	if ( get_option( 'atfa_aws_page_checked' ) === ATFA_AWARDS_VERSION ) {
		return;
	}
	$pages = get_option( 'atfa_awards_pages' );
	$pages = is_array( $pages ) ? $pages : array();
	$id    = atfa_aws_page_id();
	if ( $id && 'trash' === get_post_status( $id ) ) {
		$id = 0;
	}
	if ( ! $id ) {
		$found = get_posts( array(
			'post_type'   => 'page',
			'post_status' => array( 'publish', 'private', 'draft', 'pending', 'future' ),
			'meta_key'    => '_atfa_awards_page',
			'meta_value'  => 'sponsorship',
			'numberposts' => 1,
			'fields'      => 'ids',
		) );
		$id = $found ? (int) $found[0] : 0;
	}
	if ( ! $id ) {
		$parent = ( ! empty( $pages['splash'] ) && 'page' === get_post_type( (int) $pages['splash'] ) ) ? (int) $pages['splash'] : 0;
		$id     = wp_insert_post( array(
			'post_type'      => 'page',
			'post_title'     => '2027 Awards Sponsorship',
			'post_name'      => $parent ? 'sponsorship' : 'awards-sponsorship',
			'post_parent'    => $parent,
			'post_status'    => 'private',
			'post_content'   => '[atfa_awards_sponsorship]',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'meta_input'     => array( '_atfa_awards_page' => 'sponsorship' ),
		), true );
		if ( is_wp_error( $id ) || ! $id ) {
			return;
		}
	}
	update_post_meta( $id, '_wp_page_template', 'atfa-awards-blank.php' );
	$pages['sponsorship'] = (int) $id;
	update_option( 'atfa_awards_pages', $pages );
	update_option( 'atfa_aws_page_checked', ATFA_AWARDS_VERSION );
}
add_action( 'init', 'atfa_aws_ensure_page', 20 );

function atfa_aws_is_page() {
	$id = atfa_aws_page_id();
	return $id && is_page( $id );
}

/**
 * The page's own address shows it to everyone (it's private, so WordPress would otherwise give visitors a 404).
 * Only the main request for that page; nothing else on the site changes.
 */
function atfa_aws_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$id = atfa_aws_page_id();
	if ( ! $id ) {
		return;
	}
	$name = trim( (string) $query->get( 'pagename' ), '/' );
	if ( '' !== $name ? ( get_page_uri( $id ) !== $name ) : ( (int) $query->get( 'page_id' ) !== $id ) ) {
		return;
	}
	$query->set( 'post_status', array( 'publish', 'private' ) );
}
add_action( 'pre_get_posts', 'atfa_aws_query' );

function atfa_aws_robots( $robots ) {
	if ( atfa_aws_is_page() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['max-image-preview'] );
	}
	return $robots;
}
add_filter( 'wp_robots', 'atfa_aws_robots', 99 );

add_shortcode( 'atfa_awards_sponsorship', function () {
	ob_start();
	include ATFA_AWARDS_DIR . 'views/sponsorship.php';
	return ob_get_clean();
} );

/* ---------------------------------------------------------------------------
 * Access codes
 * ------------------------------------------------------------------------ */

function atfa_aws_is_staff() {
	return is_user_logged_in() && current_user_can( 'edit_pages' );
}

function atfa_aws_is_admin() {
	return is_user_logged_in() && current_user_can( 'manage_options' );
}

/**
 * Hidden for now (Settings): only administrators can open the page or send the form.
 */
function atfa_aws_hidden() {
	return 'admin' === atfa_aws_settings()['access'];
}

/**
 * Who gets the staff view: administrators while the page is hidden or Platinum Members only, Editors and
 * Administrators once it is open to everyone.
 */
function atfa_aws_can_preview() {
	return 'public' === atfa_aws_settings()['access'] ? atfa_aws_is_staff() : atfa_aws_is_admin();
}

/**
 * All codes: id => array( company, contact, email, code, active, created, by, visits, last ).
 */
function atfa_aws_codes() {
	$codes = get_option( ATFA_AWS_CODES, array() );
	return is_array( $codes ) ? $codes : array();
}

function atfa_aws_save_codes( $codes ) {
	update_option( ATFA_AWS_CODES, $codes, false );
}

function atfa_aws_norm( $raw ) {
	return strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $raw ) );
}

/**
 * A new code like K7QM-4XPA, not already in use ($codes: the codes so far, when some aren't saved yet).
 */
function atfa_aws_make_code( $codes = null ) {
	$taken = array();
	foreach ( ( is_array( $codes ) ? $codes : atfa_aws_codes() ) as $c ) {
		$taken[ atfa_aws_norm( $c['code'] ) ] = true;
	}
	$max = strlen( ATFA_AWS_ALPHABET ) - 1;
	do {
		$raw = '';
		for ( $i = 0; $i < 8; $i++ ) {
			$raw .= ATFA_AWS_ALPHABET[ random_int( 0, $max ) ];
		}
	} while ( isset( $taken[ $raw ] ) );
	return substr( $raw, 0, 4 ) . '-' . substr( $raw, 4 );
}

/**
 * The id of the live code that matches what someone typed (any case, with or without the dash), or ''.
 */
function atfa_aws_find_code( $raw ) {
	$want = atfa_aws_norm( $raw );
	if ( strlen( $want ) < 6 || strlen( $want ) > 20 ) {
		return '';
	}
	foreach ( atfa_aws_codes() as $id => $c ) {
		if ( ! empty( $c['active'] ) && hash_equals( atfa_aws_norm( $c['code'] ), $want ) ) {
			return (string) $id;
		}
	}
	return '';
}

function atfa_aws_cookie_value( $id, $code ) {
	return $id . '.' . hash_hmac( 'sha256', $id . '|' . atfa_aws_norm( $code ), wp_salt( 'auth' ) );
}

/**
 * The member this browser has unlocked the page as (the code's record, with its id), or null.
 */
function atfa_aws_member() {
	static $member = false;
	if ( false !== $member ) {
		return $member;
	}
	$member = null;
	$raw    = isset( $_COOKIE[ ATFA_AWS_COOKIE ] ) ? (string) wp_unslash( $_COOKIE[ ATFA_AWS_COOKIE ] ) : '';
	if ( ! preg_match( '/^([A-Za-z0-9]{8,32})\.([a-f0-9]{64})$/', $raw, $m ) ) {
		return $member;
	}
	$codes = atfa_aws_codes();
	if ( empty( $codes[ $m[1] ] ) || empty( $codes[ $m[1] ]['active'] ) ) {
		return $member;
	}
	if ( hash_equals( atfa_aws_cookie_value( $m[1], $codes[ $m[1] ]['code'] ), $raw ) ) {
		$member       = $codes[ $m[1] ];
		$member['id'] = $m[1];
	}
	return $member;
}

function atfa_aws_set_cookie( $id, $code ) {
	if ( headers_sent() ) {
		return;
	}
	setcookie( ATFA_AWS_COOKIE, atfa_aws_cookie_value( $id, $code ), array(
		'expires'  => time() + 90 * DAY_IN_SECONDS,
		'path'     => ( defined( 'COOKIEPATH' ) && COOKIEPATH ) ? COOKIEPATH : '/',
		'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	) );
}

/**
 * Counts a visit (at most one every 30 minutes per code, so the option isn't rewritten on every page view).
 */
function atfa_aws_note_visit( $id, $force = false ) {
	$codes = atfa_aws_codes();
	if ( empty( $codes[ $id ] ) ) {
		return;
	}
	$last = (int) ( $codes[ $id ]['last'] ?? 0 );
	if ( ! $force && $last > time() - 30 * MINUTE_IN_SECONDS ) {
		return;
	}
	$codes[ $id ]['visits'] = (int) ( $codes[ $id ]['visits'] ?? 0 ) + 1;
	$codes[ $id ]['last']   = time();
	atfa_aws_save_codes( $codes );
}

/**
 * Who is looking at the page.
 */
function atfa_aws_viewer() {
	$staff  = atfa_aws_can_preview();
	$member = atfa_aws_hidden() ? null : atfa_aws_member();
	$public = 'public' === atfa_aws_settings()['access'];
	$gate   = ! $staff && ! $member && ! $public;
	// Staff can look at the code screen as visitors see it: ?aws_preview=gate.
	if ( $staff && isset( $_GET['aws_preview'] ) && 'gate' === $_GET['aws_preview'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		$gate = true;
	}
	return array(
		'staff'  => $staff,
		'member' => $member,
		'public' => $public,
		'gate'   => $gate,
		'error'  => isset( $GLOBALS['atfa_aws_gate_error'] ) ? (string) $GLOBALS['atfa_aws_gate_error'] : '',
		'typed'  => isset( $GLOBALS['atfa_aws_gate_typed'] ) ? (string) $GLOBALS['atfa_aws_gate_typed'] : '',
	);
}

/**
 * On the page: never cached or indexed, and a code (typed on the code screen, or from a personal link) is checked,
 * remembered on this device and the visitor comes back to the plain address.
 */
function atfa_aws_template_redirect() {
	if ( ! atfa_aws_is_page() ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );
	header( 'Vary: Cookie', false );

	// Hidden for now: everyone but administrators (staff and code holders included) goes to the awards page, like
	// the Ladies Breakfast page while it is private. Temporary (302), and no code is checked or remembered.
	if ( atfa_aws_hidden() && ! atfa_aws_is_admin() ) {
		$pages = get_option( 'atfa_awards_pages' );
		$to    = ( is_array( $pages ) && ! empty( $pages['splash'] ) && 'publish' === get_post_status( (int) $pages['splash'] ) ) ? get_permalink( (int) $pages['splash'] ) : home_url( '/' );
		wp_safe_redirect( $to, 302 );
		exit;
	}

	$raw = null;
	if ( isset( $_POST['aws_code'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$raw = (string) wp_unslash( $_POST['aws_code'] ); // phpcs:ignore WordPress.Security.NonceVerification
	} elseif ( isset( $_GET['code'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$raw = (string) wp_unslash( $_GET['code'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	if ( null === $raw ) {
		$member = atfa_aws_member();
		if ( $member ) {
			atfa_aws_note_visit( $member['id'] );
		}
		return;
	}

	$key   = 'atfa_aws_try_' . md5( atfa_awards_client_ip() );
	$tries = (int) get_transient( $key );
	if ( $tries >= 10 ) {
		$GLOBALS['atfa_aws_gate_error'] = 'Too many tries. Please wait an hour, or email ' . atfa_aws_offer()['contact']['email'] . ' for help.';
		return;
	}
	$id = atfa_aws_find_code( $raw );
	if ( '' === $id ) {
		set_transient( $key, $tries + 1, HOUR_IN_SECONDS );
		$GLOBALS['atfa_aws_gate_error'] = '' === trim( $raw ) ? 'Enter the access code from your invitation.' : 'That code doesn\'t match. Check it against your invitation and try again.';
		$GLOBALS['atfa_aws_gate_typed'] = substr( sanitize_text_field( $raw ), 0, 20 );
		return;
	}
	$codes = atfa_aws_codes();
	atfa_aws_set_cookie( $id, $codes[ $id ]['code'] );
	atfa_aws_note_visit( $id, true );
	wp_safe_redirect( atfa_aws_page_url(), 303 );
	exit;
}
add_action( 'template_redirect', 'atfa_aws_template_redirect', 1 );

/* ---------------------------------------------------------------------------
 * Categories and their status
 * ------------------------------------------------------------------------ */

/**
 * The awards that can be sponsored (data/categories.php, less the ones in the offer's 'exclude' list), in the order
 * the awards pages list them.
 */
function atfa_aws_categories() {
	$offer = atfa_aws_offer();
	$out   = array();
	foreach ( atfa_awards_categories() as $key => $c ) {
		if ( ! in_array( $key, (array) $offer['exclude'], true ) ) {
			$out[ $key ] = $c;
		}
	}
	return $out;
}

/**
 * Status of every category: key => array( status available|hold|taken, sponsor, member (code id), note, updated ).
 * A category with a confirmed sponsor logo in data/categories.php always counts as taken.
 */
function atfa_aws_statuses() {
	$saved = get_option( ATFA_AWS_STATUS, array() );
	$saved = is_array( $saved ) ? $saved : array();
	$out   = array();
	foreach ( atfa_aws_categories() as $key => $c ) {
		$s = array_merge(
			array( 'status' => 'available', 'sponsor' => '', 'member' => '', 'note' => '', 'updated' => 0 ),
			isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) ? $saved[ $key ] : array()
		);
		if ( ! in_array( $s['status'], array( 'available', 'hold', 'taken' ), true ) ) {
			$s['status'] = 'available';
		}
		if ( ! empty( $c['sponsor']['img'] ) && empty( $c['sponsor']['atfa'] ) ) {
			$s['status']  = 'taken';
			$s['sponsor'] = $c['sponsor']['name'];
		}
		$out[ $key ] = $s;
	}
	return $out;
}

function atfa_aws_set_statuses( $changes ) {
	$saved = get_option( ATFA_AWS_STATUS, array() );
	$saved = is_array( $saved ) ? $saved : array();
	foreach ( $changes as $key => $s ) {
		$s['updated'] = time();
		$s['by']      = get_current_user_id();
		if ( 'available' === $s['status'] ) {
			$s['sponsor'] = '';
			$s['member']  = '';
		}
		$saved[ $key ] = $s;
	}
	update_option( ATFA_AWS_STATUS, $saved, false );
}

/**
 * Whether a held or secured category belongs to this code holder: asked for with their code, or given to their
 * company by hand in wp-admin.
 */
function atfa_aws_is_mine( $s, $member ) {
	if ( ! $member || ! in_array( $s['status'], array( 'hold', 'taken' ), true ) ) {
		return false;
	}
	if ( '' !== $s['member'] ) {
		return $s['member'] === $member['id'];
	}
	return '' !== $s['sponsor'] && 0 === strcasecmp( trim( $s['sponsor'] ), trim( $member['company'] ) );
}

/**
 * Status as a visitor sees it: available, hold, taken, or mine / mine-taken (held or secured for this visitor).
 */
function atfa_aws_public_status( $s, $member ) {
	if ( atfa_aws_is_mine( $s, $member ) ) {
		return 'hold' === $s['status'] ? 'mine' : 'mine-taken';
	}
	return $s['status'];
}

/**
 * A number of categories in words for the page and emails ("two"), as Phil's flyer has it.
 */
function atfa_aws_words( $n ) {
	$w = array( 1 => 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten' );
	return isset( $w[ (int) $n ] ) ? $w[ (int) $n ] : (string) (int) $n;
}

function atfa_aws_public_statuses( $member ) {
	$out = array();
	foreach ( atfa_aws_statuses() as $key => $s ) {
		$out[ $key ] = atfa_aws_public_status( $s, $member );
	}
	return $out;
}

/* ---------------------------------------------------------------------------
 * Requests: stored as private posts (no screen of their own; listed under Awards sponsorship > Requests)
 * ------------------------------------------------------------------------ */

add_action( 'init', function () {
	register_post_type( ATFA_AWS_REQUEST, array(
		'label'               => 'Sponsorship requests',
		'public'              => false,
		'show_ui'             => false,
		'show_in_rest'        => false,
		'exclude_from_search' => true,
		'supports'            => array( 'title' ),
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'atfa-awards/v1', '/sponsorship/request', array(
		'methods'             => 'POST',
		'callback'            => 'atfa_aws_rest_request',
		'permission_callback' => '__return_true',
	) );
} );

function atfa_aws_text( $value, $max ) {
	$value = sanitize_text_field( (string) $value );
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
}

function atfa_aws_rest_request( WP_REST_Request $request ) {
	$settings = atfa_aws_settings();
	$offer    = atfa_aws_offer();
	$member   = atfa_aws_member();
	$staff    = atfa_aws_can_preview();
	$fail     = function ( $message, $status = 400, $extra = array() ) {
		return new WP_REST_Response( array_merge( array( 'ok' => false, 'message' => $message ), $extra ), $status );
	};

	if ( atfa_aws_hidden() && ! atfa_aws_is_admin() ) {
		return $fail( 'This page isn\'t open yet.', 403 );
	}
	if ( ! $staff && ! $member && 'public' !== $settings['access'] ) {
		return $fail( 'This page needs your access code again. Reload the page and enter the code from your invitation.', 403 );
	}
	if ( '' !== $settings['book_url'] && ! $staff ) {
		return $fail( 'Category sponsorships are now booked on the ATFA sponsorship page: ' . $settings['book_url'], 400 );
	}
	// Honeypot: people never fill the hidden "website" field.
	if ( '' !== trim( (string) $request->get_param( 'website' ) ) ) {
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'Thanks. We have your request.' ), 200 );
	}
	$key   = 'atfa_aws_req_' . md5( atfa_awards_client_ip() );
	$count = (int) get_transient( $key );
	if ( $count >= 8 ) {
		return $fail( 'Too many requests from here in the last hour. Please email ' . $offer['contact']['email'] . ' instead.', 429 );
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	$cats = atfa_aws_categories();
	$pick = array();
	foreach ( (array) $request->get_param( 'categories' ) as $k ) {
		$k = sanitize_key( (string) $k );
		if ( isset( $cats[ $k ] ) && ! in_array( $k, $pick, true ) ) {
			$pick[] = $k;
		}
	}
	if ( ! $pick ) {
		return $fail( 'Pick at least one category first.' );
	}

	$company = $member ? $member['company'] : atfa_aws_text( $request->get_param( 'company' ), 120 );
	$typed   = atfa_aws_text( $request->get_param( 'company' ), 120 );
	if ( $member && '' !== $typed && strtolower( $typed ) !== strtolower( $company ) ) {
		$company = $typed . ' (' . $member['company'] . ')';
	}
	$name  = atfa_aws_text( $request->get_param( 'name' ), 100 );
	$email = sanitize_email( (string) $request->get_param( 'email' ) );
	$phone = atfa_aws_text( $request->get_param( 'phone' ), 40 );
	$notes = sanitize_textarea_field( (string) $request->get_param( 'notes' ) );
	$notes = function_exists( 'mb_substr' ) ? mb_substr( $notes, 0, 1000 ) : substr( $notes, 0, 1000 );
	if ( '' === $company ) {
		return $fail( 'Please add your company name.' );
	}
	if ( '' === $name ) {
		return $fail( 'Please add your name.' );
	}
	if ( ! $email || ! is_email( $email ) ) {
		return $fail( 'Please add a valid email address.' );
	}

	// A logged-in staff member sending the form is trying it out: nothing is held and only they get the emails.
	$test     = $staff;
	$statuses = atfa_aws_statuses();
	$mine     = array();
	if ( $member ) {
		foreach ( $statuses as $k => $s ) {
			if ( atfa_aws_is_mine( $s, $member ) ) {
				$mine[] = $k;
			}
		}
	}
	$new = array_values( array_diff( $pick, $mine ) );
	foreach ( $new as $k ) {
		if ( 'available' !== $statuses[ $k ]['status'] && ! $test ) {
			return $fail(
				'Sorry, ' . $cats[ $k ]['name'] . ' has just gone on hold for another sponsor. Pick another category and send again.',
				409,
				array( 'statuses' => atfa_aws_public_statuses( $member ) )
			);
		}
	}
	if ( ! $new ) {
		return $fail( 'You have already asked for ' . ( 1 === count( $pick ) ? 'that category' : 'those categories' ) . '. We will be in touch to confirm.', 400, array( 'statuses' => atfa_aws_public_statuses( $member ) ) );
	}
	$total = count( array_unique( array_merge( $mine, $new ) ) );
	if ( $total > $settings['limit'] && ! $test ) {
		return $fail( 'Each sponsor can ask for up to ' . atfa_aws_words( $settings['limit'] ) . ' ' . _n( 'category', 'categories', $settings['limit'] ) . ' in this round, and you already have ' . atfa_aws_words( count( $mine ) ) . '. To change your picks, email ' . $offer['contact']['email'] . '.' );
	}

	$names = array();
	foreach ( $new as $k ) {
		$names[ $k ] = $cats[ $k ]['name'];
	}
	$id = wp_insert_post( array(
		'post_type'   => ATFA_AWS_REQUEST,
		'post_status' => 'private',
		'post_title'  => ( $test ? '[Test] ' : '' ) . $company . ': ' . implode( ', ', $names ),
		'meta_input'  => array(
			'categories' => $new,
			'company'    => $company,
			'name'       => $name,
			'email'      => $email,
			'phone'      => $phone,
			'notes'      => $notes,
			'member'     => $member ? $member['id'] : '',
			'code'       => $member ? $member['code'] : '',
			'test'       => $test ? 1 : 0,
			'state'      => 'new',
			'ip'         => atfa_awards_client_ip(),
		),
	), true );
	if ( is_wp_error( $id ) || ! $id ) {
		return $fail( 'Something went wrong saving your request. Please email your picks to ' . $offer['contact']['email'] . '.', 500 );
	}

	if ( ! $test ) {
		$hold = array();
		foreach ( $new as $k ) {
			$hold[ $k ] = array(
				'status'  => 'hold',
				'sponsor' => $company,
				'member'  => $member ? $member['id'] : '',
				'note'    => 'Requested by ' . $name . ', ' . wp_date( 'j M' ),
				'request' => (int) $id,
			);
		}
		atfa_aws_set_statuses( $hold );
	}

	atfa_aws_send_request_emails( (int) $id, $test );

	$first = trim( strtok( $name, ' ' ) );
	return new WP_REST_Response( array(
		'ok'       => true,
		'test'     => $test,
		'message'  => $test
			? 'Test request saved and emailed to you only. Nothing was put on hold and ' . $offer['contact']['name'] . ' wasn\'t emailed.'
			: 'Thanks ' . $first . '. ' . implode( ' and ', $names ) . ( 1 === count( $names ) ? ' is' : ' are' ) . ' on hold for ' . $company . ' while we confirm. ' . $offer['contact']['name'] . ' will be in touch, and a copy of your request is on its way to ' . $email . '.',
		'statuses' => atfa_aws_public_statuses( $member ),
		'picked'   => $new,
	), 200 );
}

/**
 * Lines that describe a request, for the emails.
 */
function atfa_aws_request_summary( $post_id ) {
	$cats  = atfa_aws_categories();
	$lines = array();
	foreach ( (array) get_post_meta( $post_id, 'categories', true ) as $k ) {
		$lines[] = '- ' . ( isset( $cats[ $k ] ) ? $cats[ $k ]['full'] : $k );
	}
	return implode( "\n", $lines );
}

function atfa_aws_send_request_emails( $post_id, $test = false ) {
	$settings = atfa_aws_settings();
	$offer    = atfa_aws_offer();
	$m        = function ( $k ) use ( $post_id ) {
		return (string) get_post_meta( $post_id, $k, true );
	};
	$cats     = atfa_aws_request_summary( $post_id );
	$count    = count( (array) get_post_meta( $post_id, 'categories', true ) );
	$company  = $m( 'company' );
	$admin    = admin_url( 'admin.php?page=atfa-aws&tab=requests' );
	$when     = wp_date( 'l j F Y, g:i a' );

	// To Phil (copy to Nick), or to the staff member trying it out.
	$to      = $test ? wp_get_current_user()->user_email : $settings['to'];
	$headers = array( 'Reply-To: ' . $m( 'name' ) . ' <' . $m( 'email' ) . '>' );
	if ( ! $test && '' !== trim( $settings['cc'] ) ) {
		$headers[] = 'Cc: ' . $settings['cc'];
	}
	$body = $company . ' would like to sponsor ' . ( 1 === $count ? 'this category' : 'these categories' ) . " at the 2027 ATFA Flooring Awards:\n\n"
		. $cats . "\n\n"
		. 'Contact: ' . $m( 'name' ) . "\n"
		. 'Email: ' . $m( 'email' ) . "\n"
		. ( '' !== $m( 'phone' ) ? 'Phone: ' . $m( 'phone' ) . "\n" : '' )
		. ( '' !== $m( 'code' ) ? 'Access code: ' . $m( 'code' ) . " (Platinum first look)\n" : '' )
		. ( '' !== $m( 'notes' ) ? "\nNotes:\n" . $m( 'notes' ) . "\n" : '' )
		. "\n" . ( $test
			? "This was a test from a staff login, so nothing was put on hold.\n"
			: ( 1 === $count ? 'It is' : 'They are' ) . ' now on hold for ' . $company . " on the sponsorship page. Confirm or release here:\n" . $admin . "\n" )
		. "\nSent from " . atfa_aws_page_url() . ' on ' . $when . "\n"
		. 'Reply to this email to reach ' . $m( 'name' ) . '.';
	wp_mail( $to, ( $test ? '[Test] ' : '' ) . 'Awards 2027 category request: ' . $company, $body, $headers );

	// To the person who asked.
	$first   = trim( strtok( $m( 'name' ), ' ' ) );
	$confirm = 'Hi ' . $first . ",\n\n"
		. 'Thanks for your request to sponsor ' . ( 1 === $count ? 'this category' : 'these categories' ) . " at the 2027 ATFA Flooring Awards:\n\n"
		. $cats . "\n\n"
		. ( 1 === $count ? 'It is' : 'They are' ) . ' on hold for ' . $company . ' while we confirm. ' . $offer['contact']['name'] . " will be in touch. Categories are allocated on a first-confirmed basis.\n\n"
		. 'Each category is ' . atfa_aws_price() . ' and includes two tickets to the ATFA Awards Dinner on ' . $offer['dinner']['date'] . ' at ' . $offer['dinner']['venue'] . ".\n\n"
		. 'Questions? Reply to this email or contact ' . $offer['contact']['name'] . ' at ' . $offer['contact']['email'] . ".\n"
		. 'Your sponsorship page: ' . atfa_aws_page_url() . "\n\n"
		. "Regards,\nThe ATFA team\nAU 1300 361 693 · NZ 0800 453 715\nhttps://www.atfa.com.au/";
	wp_mail( $test ? wp_get_current_user()->user_email : $m( 'email' ), ( $test ? '[Test] ' : '' ) . 'Your 2027 ATFA Awards category request', $confirm, array( 'Reply-To: ' . $offer['contact']['email'] ) );
}

/* ---------------------------------------------------------------------------
 * The link from the awards pages
 * ------------------------------------------------------------------------ */

/**
 * "Sponsor a category" beside the category sponsors on the awards pages: for everyone once the page is open to
 * all, for administrators before that (the awards pages are cached
 * for visitors, so code holders don't get it there).
 */
function atfa_aws_awards_link() {
	if ( ! atfa_aws_page_id() ) {
		return '';
	}
	if ( 'public' !== atfa_aws_settings()['access'] && ! atfa_aws_can_preview() ) {
		return '';
	}
	return ' · <a href="' . esc_url( atfa_aws_page_url() ) . '" style="color:#F8931F;letter-spacing:.1em">Sponsor a category</a>';
}
