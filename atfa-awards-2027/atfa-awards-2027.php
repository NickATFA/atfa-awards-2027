<?php
/**
 * Plugin Name: ATFA Flooring Awards 2027
 * Description: The 2027 awards season page at /awards/ (blackout splash with a notify-me form), a staff-only preview of the entries-open page at /awards-preview/, the list of notify-me signups under Awards signups, and the ATFA Expo & Conference 2027 page at /expo-2027/ with the live Hall 5 floor plan.
 * Version: 1.9.0
 * Author: ATFA
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Update URI: https://github.com/NickATFA/atfa-awards-2027
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ATFA_AWARDS_VERSION', '1.9.0' );
define( 'ATFA_AWARDS_FILE', __FILE__ );
define( 'ATFA_AWARDS_DIR', plugin_dir_path( __FILE__ ) );
define( 'ATFA_AWARDS_URL', plugin_dir_url( __FILE__ ) );
// Where new versions come from: manifest.json on the main branch of the GitHub repo. See the "Updates" section below.
define( 'ATFA_AWARDS_GITHUB_REPO', 'NickATFA/atfa-awards-2027' );
define( 'ATFA_AWARDS_MANIFEST_URL', 'https://raw.githubusercontent.com/' . ATFA_AWARDS_GITHUB_REPO . '/main/manifest.json' );

// Who gets an email for every notify-me signup, and the reply-to address on the confirmation sent to the subscriber.
if ( ! defined( 'ATFA_AWARDS_NOTIFY_TO' ) ) {
	define( 'ATFA_AWARDS_NOTIFY_TO', 'nick@atfa.com.au' );
}
if ( ! defined( 'ATFA_AWARDS_REPLY_TO' ) ) {
	define( 'ATFA_AWARDS_REPLY_TO', 'admin@atfa.com.au' );
}
// Set to true (in wp-config.php) to 301 the old /training-events/annual-awards/ page to the new /awards/ page.
if ( ! defined( 'ATFA_AWARDS_REDIRECT_OLD_PAGE' ) ) {
	define( 'ATFA_AWARDS_REDIRECT_OLD_PAGE', false );
}

// The ATFA Expo & Conference 2027 page (/expo-2027/) and the Hall 5 floor plan, built from expo/ in the repo
// (see expo/README.md). It used to run as a Code Snippet: if that snippet is still switched on, whichever copy
// loads first runs and the other does nothing, so nothing clashes; switch the snippet off once this is installed.
require_once ATFA_AWARDS_DIR . 'includes/expo-2027.php';

/* ---------------------------------------------------------------------------
 * Data shared by the views
 * ------------------------------------------------------------------------ */

/**
 * The 2025 winners shown in the rotating backdrops. Photos are bundled with the plugin.
 */
function atfa_awards_winners() {
	return array(
		array( 'img' => 'knowhow-stair.jpg', 'cat' => '2025 Prefinished Stair of the Year', 'biz' => 'Know-How Flooring', 'pos' => '40% 50%' ),
		array( 'img' => 'titan-hybrid.jpg', 'cat' => '2025 Hybrid & Composite Floor of the Year', 'biz' => 'Titan Timber Flooring', 'pos' => '50% 50%' ),
		array( 'img' => 'colab-commercial.jpg', 'cat' => '2025 Commercial / Industrial Prefinished Floor of the Year', 'biz' => 'Colab Floor', 'pos' => '50% 50%' ),
		array( 'img' => 'beaver-commercial.jpg', 'cat' => '2025 Commercial / Industrial Solid Timber Floor of the Year', 'biz' => 'Beaver Flooring', 'pos' => '50% 50%' ),
		array( 'img' => 'artistic-design.jpg', 'cat' => '2025 Innovative Design Prefinished Floor of the Year', 'biz' => 'Artistic Wood Floor', 'pos' => '50% 50%' ),
		array( 'img' => 'access-design.jpg', 'cat' => '2025 Innovative Design Solid Timber Floor of the Year', 'biz' => 'Access Timber Flooring', 'pos' => '50% 50%' ),
	);
}

function atfa_awards_img( $file ) {
	return ATFA_AWARDS_URL . 'assets/img/' . $file . '?v=' . ATFA_AWARDS_VERSION;
}

/**
 * The twenty awards with their guidance, rules and example photos (data/categories.php), with the rules that are
 * common to a stream filled in so each category carries its own complete list.
 */
function atfa_awards_categories() {
	static $cats = null;
	if ( null !== $cats ) {
		return $cats;
	}
	$cats = include ATFA_AWARDS_DIR . 'data/categories.php';

	$member_before = array(
		'Open to current financial ATFA members primarily responsible for the work.',
		'The project must be in Australia or New Zealand, completed within the eligibility period, and not installed as part of a school or learning environment.',
		'Submit 5 to 10 high-resolution photos, a short project summary, and the product type, installation method and scope of work.',
	);
	$member_after  = array(
		'Up to three entries per entrant in this category. Enter each project once, in one category, and disclose everyone who contributed.',
		'Get the owner or client\'s permission to use the photos and project details. No branding, logos or watermarks on photos.',
		'Member voted, with a winner and runner-up. Chasing votes on social media or elsewhere is assessed by ATFA and can get the entry removed.',
		'Newcomers Award: tick the box on the entry form if you are in your first five years of membership and have never won an ATFA award or been a runner-up.',
	);
	$panel_before  = array(
		'Open to current financial ATFA members primarily responsible for the work.',
		'The project must be completed within the eligibility period. Work in a school or learning environment, and projects outside Australia and New Zealand, are allowed if clearly disclosed in the entry.',
		'Submit 8 to 15 high-resolution photos, a project summary, a technical statement, and a list of the main products, finishes and systems used.',
	);
	$panel_after   = array(
		'Up to three entries per entrant in this category. Enter each project once, in one category, and disclose everyone who contributed.',
		'Get the owner or client\'s permission to use the photos and project details. No branding, logos or watermarks on judging photos.',
		'Judged by the expert panel, with a winner and runner-up. No member voting, and the scoring criteria are not published. ATFA may ask for further evidence.',
	);

	foreach ( $cats as $key => &$c ) {
		$c['key'] = $key;
		$extra    = isset( $c['extra'] ) ? $c['extra'] : array();
		if ( 'member' === $c['stream'] ) {
			$c['rules'] = array_merge( $member_before, $extra, $member_after );
		} elseif ( 'panel' === $c['stream'] ) {
			$c['rules'] = array_merge( $panel_before, $extra, $panel_after );
		} elseif ( 'excellence' === $c['stream'] ) {
			$c['rules'] = array(
				'Open to current financial ATFA members. Board directors and their businesses are eligible under the same rules.',
				'Complete the official entry form and any published category questions.',
				'Provide a nomination statement addressing the category criteria, supported by ' . $c['evidence'] . '.',
				'Include images or logos where relevant. Everything you supply must be accurate, and you must have permission to supply it and for ATFA to use it.',
				'Judged by ATFA or its appointed judging process. ATFA may ask for evidence to verify claims. One winner, no runner-up.',
			);
		}
		if ( empty( $c['examples'] ) ) {
			$c['examples'] = array();
		}
		if ( empty( $c['sponsor'] ) ) {
			$c['sponsor'] = array( 'name' => '', 'img' => '', 'url' => '' );
		}
	}
	unset( $c );
	return $cats;
}

/**
 * The confirmed category sponsors (set per award in data/categories.php, ATFA's own Floor of the Year sponsorship
 * left out), padded with placeholders to fill the strip on the splash and entries pages.
 */
function atfa_awards_sponsors() {
	$list = array();
	foreach ( atfa_awards_categories() as $c ) {
		if ( empty( $c['sponsor']['img'] ) || ! empty( $c['sponsor']['atfa'] ) ) {
			continue;
		}
		$list[ $c['sponsor']['name'] ] = $c['sponsor'];
	}
	$list = array_values( $list );
	while ( count( $list ) < 6 ) {
		$list[] = array( 'name' => '', 'img' => '', 'url' => '' );
	}
	return $list;
}

/**
 * One sponsor tile: the logo linked to the sponsor's site, or a dashed placeholder until it is confirmed.
 */
function atfa_awards_sponsor_tile( $sp ) {
	if ( empty( $sp['img'] ) ) {
		return '<div class="sponsor">Sponsor logo</div>';
	}
	$img = '<img src="' . esc_url( atfa_awards_img( $sp['img'] ) ) . '" alt="' . esc_attr( $sp['name'] ) . '" loading="lazy">';
	if ( ! empty( $sp['url'] ) ) {
		return '<a class="sponsor has-logo" href="' . esc_url( $sp['url'] ) . '" target="_blank" rel="noopener">' . $img . '</a>';
	}
	return '<div class="sponsor has-logo">' . $img . '</div>';
}

/**
 * The Principal Partner (data/partner.php), or null when there isn't one, or when it is set to staff only and the
 * visitor isn't logged in as staff.
 */
function atfa_awards_partner() {
	static $p = false;
	if ( false === $p ) {
		$file = ATFA_AWARDS_DIR . 'data/partner.php';
		$p    = file_exists( $file ) ? include $file : null;
		if ( ! is_array( $p ) || empty( $p['name'] ) || empty( $p['logo'] ) ) {
			$p = null;
		}
	}
	if ( ! $p || ( empty( $p['public'] ) && ! current_user_can( 'edit_pages' ) ) ) {
		return null;
	}
	return $p;
}

/**
 * The partner's logo as an <img>, sized by CSS (the width and height only give the browser the shape).
 */
function atfa_awards_partner_img( $p, $lazy = false ) {
	return '<img src="' . esc_url( atfa_awards_img( $p['logo'] ) ) . '" alt="' . esc_attr( $p['name'] ) . '" width="' . (int) $p['width'] . '" height="' . (int) $p['height'] . '"' . ( $lazy ? ' loading="lazy"' : '' ) . '>';
}

/**
 * "Presented by" and the partner's logo, linked to their site. $class adds to the "pp" class (pp-sm for the dinner band).
 */
function atfa_awards_partner_lockup( $p, $class = '' ) {
	return '<a class="pp' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" href="' . esc_url( $p['url'] ) . '" target="_blank" rel="noopener sponsored">'
		. '<span class="pp-l">Presented by</span>' . atfa_awards_partner_img( $p ) . '</a>';
}

/**
 * A category row in the lists: the name, with the whole row a button that opens the category's info panel.
 */
function atfa_awards_cat_row( $c ) {
	$tag = ! empty( $c['new'] ) ? ' <span class="tag tag-new">New</span>' : '';
	return '<button type="button" class="cat-row" data-cat="' . esc_attr( $c['key'] ) . '" aria-haspopup="dialog"><span class="cat-n">' . esc_html( $c['name'] ) . $tag . '</span><span class="cat-more">More info<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg></span></button>';
}

/**
 * Example photo markup for a category: the 2025 entries bundled with the plugin.
 */
function atfa_awards_example( $ex ) {
	$code = $ex[0];
	$who  = isset( $ex[1] ) ? $ex[1] : '';
	$note = isset( $ex[2] ) ? $ex[2] : '';
	$src  = atfa_awards_img( 'examples/' . $code . '.jpg' );          // 1200px, for the lightbox
	$thumb = atfa_awards_img( 'examples/thumbs/' . $code . '.jpg' );  // 640px, for the grid (about a quarter of the weight on phones)
	$cap  = $who ? '<strong>' . esc_html( $who ) . '</strong>' . esc_html( $note ) : '<strong>2025 entry</strong>';
	return '<figure class="ex"><button type="button" class="ex-open" data-src="' . esc_url( $src ) . '" aria-label="Enlarge photo"><img src="' . esc_url( $thumb ) . '" alt="" loading="lazy" width="640" height="480"></button><figcaption>' . $cap . '</figcaption></figure>';
}

/**
 * An award's full name for the panel title, with " of the Year" in a span the phone layout hides to save a line.
 */
function atfa_awards_title_html( $full ) {
	if ( ' of the Year' === substr( $full, -12 ) ) {
		return esc_html( substr( $full, 0, -12 ) ) . '<span class="ofy"> of the Year</span>';
	}
	return esc_html( $full );
}

/**
 * The rest of the ATFA weekend in Sydney, shown under the dinner on the entries page and linked in its footer.
 * Dates from ATFA Program Dates 2027 (v8). Leave a url empty until the event's page is up: the card then shows
 * its "soon" line and no link.
 */
function atfa_awards_weekend() {
	return array(
		array(
			'day'    => 'Fri 21',
			'when'   => 'Friday 21 May',
			'title'  => 'Expo & Conference',
			'text'   => 'Sydney Showground, Hall 5. Fifty-eight exhibitor stands, demonstrations on the stage and breakout space.',
			'cta'    => 'See the Expo and floor plan',
			'footer' => 'Expo & Conference 2027',
			'url'    => 'https://www.atfa.com.au/expo-2027/',
		),
		array(
			'day'    => 'Sat 22',
			'when'   => 'Saturday 22 May · morning',
			'title'  => 'Ladies Breakfast',
			'text'   => 'A morning for the ladies of ATFA to get together and network over breakfast.',
			'soon'   => 'Venue and tickets coming soon',
			'cta'    => 'Details and tickets',
			'footer' => 'Ladies Breakfast',
			'url'    => '',
		),
	);
}

/**
 * One date-led card in the "Also on the ATFA weekend" row: the whole card is a link when the event has a page,
 * otherwise the same card with its "soon" line.
 */
function atfa_awards_weekend_card( $ev ) {
	$inner = '<span class="disp wk-day">' . esc_html( $ev['day'] ) . '</span>'
		. '<span class="eyebrow">' . esc_html( $ev['when'] ) . '</span>'
		. '<span class="disp wk-t">' . esc_html( $ev['title'] ) . '</span>'
		. '<span class="wk-d">' . esc_html( $ev['text'] ) . '</span>';
	if ( empty( $ev['url'] ) ) {
		return '<div class="wk">' . $inner . '<span class="wk-soon">' . esc_html( $ev['soon'] ) . '</span></div>';
	}
	return '<a class="wk" href="' . esc_url( $ev['url'] ) . '">' . $inner . '<span class="wk-go">' . esc_html( $ev['cta'] ) . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg></span></a>';
}

/* ---------------------------------------------------------------------------
 * Blank page template (no site header or footer)
 * ------------------------------------------------------------------------ */

add_filter( 'theme_page_templates', function ( $templates ) {
	$templates['atfa-awards-blank.php'] = 'ATFA Awards (blank canvas)';
	return $templates;
} );

add_filter( 'template_include', function ( $template ) {
	if ( is_singular( 'page' ) && 'atfa-awards-blank.php' === get_page_template_slug( get_queried_object_id() ) ) {
		return ATFA_AWARDS_DIR . 'templates/blank.php';
	}
	return $template;
}, 99 );

/**
 * On the blank template, drop the theme and plugin stylesheets so nothing fights the page's own CSS.
 * Scripts are left alone so analytics keep working.
 */
function atfa_awards_strip_styles() {
	global $wp_styles;
	if ( ! $wp_styles ) {
		return;
	}
	$keep = array( 'admin-bar', 'dashicons' );
	foreach ( (array) $wp_styles->queue as $handle ) {
		if ( ! in_array( $handle, $keep, true ) ) {
			wp_dequeue_style( $handle );
		}
	}
}

/* ---------------------------------------------------------------------------
 * Shortcodes that render the two pages
 * ------------------------------------------------------------------------ */

add_shortcode( 'atfa_awards_splash', function () {
	ob_start();
	include ATFA_AWARDS_DIR . 'views/splash.php';
	return ob_get_clean();
} );

add_shortcode( 'atfa_awards_entries', function () {
	ob_start();
	include ATFA_AWARDS_DIR . 'views/entries.php';
	return ob_get_clean();
} );

/* ---------------------------------------------------------------------------
 * Notify-me signups: custom post type, REST endpoint, emails, CSV export
 * ------------------------------------------------------------------------ */

function atfa_awards_register_cpt() {
	register_post_type( 'atfa_notify', array(
		'labels' => array(
			'name'               => 'Awards signups',
			'singular_name'      => 'Awards signup',
			'menu_name'          => 'Awards signups',
			'all_items'          => 'All signups',
			'edit_item'          => 'Signup',
			'search_items'       => 'Search signups',
			'not_found'          => 'No signups yet.',
			'not_found_in_trash' => 'No signups in the bin.',
		),
		'description'         => 'People who asked to be told when Flooring Awards 2027 entries open.',
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'exclude_from_search' => true,
		'menu_icon'           => 'dashicons-megaphone',
		'menu_position'       => 26,
		'supports'            => array( 'title' ),
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
	) );
}
add_action( 'init', 'atfa_awards_register_cpt' );

add_action( 'rest_api_init', function () {
	register_rest_route( 'atfa-awards/v1', '/notify', array(
		'methods'             => 'POST',
		'callback'            => 'atfa_awards_rest_notify',
		'permission_callback' => '__return_true',
		'args'                => array(
			'email'   => array( 'type' => 'string', 'required' => true ),
			'page'    => array( 'type' => 'string' ),
			'website' => array( 'type' => 'string' ),
		),
	) );
} );

function atfa_awards_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return $ip ? $ip : 'unknown';
}

function atfa_awards_rest_notify( WP_REST_Request $request ) {
	$thanks = 'Thanks, you are on the list. We will email you the moment entries open.';

	// Honeypot: real people never fill the hidden "website" field.
	if ( '' !== trim( (string) $request->get_param( 'website' ) ) ) {
		return new WP_REST_Response( array( 'ok' => true, 'message' => $thanks ), 200 );
	}

	$email = sanitize_email( (string) $request->get_param( 'email' ) );
	if ( ! $email || ! is_email( $email ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => 'Please enter a valid email address.' ), 400 );
	}

	// Ten signups an hour per address is plenty for a human.
	$ip    = atfa_awards_client_ip();
	$key   = 'atfa_nt_' . md5( $ip );
	$count = (int) get_transient( $key );
	if ( $count >= 10 ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => 'Too many attempts. Please try again later or email ' . ATFA_AWARDS_REPLY_TO . '.' ), 429 );
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	$page = esc_url_raw( (string) $request->get_param( 'page' ) );

	// Already on the list: say thanks again, no second email.
	$existing = get_posts( array(
		'post_type'   => 'atfa_notify',
		'post_status' => 'any',
		'title'       => $email,
		'numberposts' => 1,
		'fields'      => 'ids',
	) );
	if ( $existing ) {
		return new WP_REST_Response( array( 'ok' => true, 'message' => $thanks, 'duplicate' => true ), 200 );
	}

	$id = wp_insert_post( array(
		'post_type'   => 'atfa_notify',
		'post_status' => 'publish',
		'post_title'  => $email,
		'meta_input'  => array(
			'page' => $page,
			'ip'   => $ip,
			'ua'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 200 ) : '',
		),
	), true );

	if ( is_wp_error( $id ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => 'Something went wrong. Email ' . ATFA_AWARDS_REPLY_TO . ' and we will add you.' ), 500 );
	}

	atfa_awards_send_signup_emails( $email, $page, $id );

	return new WP_REST_Response( array( 'ok' => true, 'message' => $thanks ), 200 );
}

function atfa_awards_send_signup_emails( $email, $page, $post_id ) {
	$when  = wp_date( 'l j F Y, g:i a' );
	$list  = admin_url( 'edit.php?post_type=atfa_notify' );
	$count = (int) wp_count_posts( 'atfa_notify' )->publish;

	// To ATFA.
	$body = "$email asked to be told when Flooring Awards 2027 entries open.\n\n"
		. "When: $when\n"
		. ( $page ? "Page: $page\n" : '' )
		. "Signups so far: $count\n\n"
		. "All signups (export to CSV from here): $list\n";
	wp_mail( ATFA_AWARDS_NOTIFY_TO, "Awards 2027 notify me: $email", $body, array( 'Reply-To: ' . $email ) );

	// To the subscriber.
	$confirm = "Thanks for your interest in the ATFA Flooring Awards 2027.\n\n"
		. "Entries open Monday 1 February 2027 and we will email you the moment they do. "
		. "The awards dinner is on Saturday 22 May 2027 in Sydney, alongside the ATFA Expo and Conference.\n\n"
		. "Regards,\nThe ATFA team\nAU 1300 361 693 · NZ 0800 453 715\nhttps://www.atfa.com.au/";
	wp_mail( $email, 'You are on the list for the ATFA Flooring Awards 2027', $confirm, array( 'Reply-To: ' . ATFA_AWARDS_REPLY_TO ) );
}

// Admin list: email, page, date, plus an export link.
add_filter( 'manage_atfa_notify_posts_columns', function ( $columns ) {
	return array(
		'cb'    => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title' => 'Email',
		'page'  => 'Signed up from',
		'date'  => 'Date',
	);
} );

add_action( 'manage_atfa_notify_posts_custom_column', function ( $column, $post_id ) {
	if ( 'page' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, 'page', true ) );
	}
}, 10, 2 );

add_filter( 'views_edit-atfa_notify', function ( $views ) {
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=atfa_notify_export' ), 'atfa_notify_export' );
	$views['atfa_export'] = '<a href="' . esc_url( $url ) . '">Export all to CSV</a>';
	return $views;
} );

add_action( 'admin_post_atfa_notify_export', function () {
	if ( ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'atfa_notify_export' ) ) {
		wp_die( 'Not allowed.' );
	}
	$rows = get_posts( array( 'post_type' => 'atfa_notify', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'ASC' ) );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="awards-2027-notify-signups-' . wp_date( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'Email', 'Signed up', 'Page' ) );
	foreach ( $rows as $row ) {
		fputcsv( $out, array( $row->post_title, get_date_from_gmt( $row->post_date_gmt, 'Y-m-d H:i' ), (string) get_post_meta( $row->ID, 'page', true ) ) );
	}
	fclose( $out );
	exit;
} );

/* ---------------------------------------------------------------------------
 * Activation: create the pages, flush rewrites, send a test email
 * ------------------------------------------------------------------------ */

function atfa_awards_ensure_page( $key, $title, $slug, $status, $content ) {
	$found = get_posts( array(
		'post_type'   => 'page',
		'post_status' => 'any',
		'meta_key'    => '_atfa_awards_page',
		'meta_value'  => $key,
		'numberposts' => 1,
		'fields'      => 'ids',
	) );
	if ( $found ) {
		$id = (int) $found[0];
		wp_update_post( array( 'ID' => $id, 'post_content' => $content ) );
	} else {
		$id = wp_insert_post( array(
			'post_type'      => 'page',
			'post_title'     => $title,
			'post_name'      => $slug,
			'post_status'    => $status,
			'post_content'   => $content,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'meta_input'     => array( '_atfa_awards_page' => $key ),
		) );
	}
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_wp_page_template', 'atfa-awards-blank.php' );
		return (int) $id;
	}
	return 0;
}

register_activation_hook( __FILE__, function () {
	atfa_awards_register_cpt();
	flush_rewrite_rules();

	$splash  = atfa_awards_ensure_page( 'splash', 'Flooring Awards 2027', 'awards', 'publish', '[atfa_awards_splash]' );
	$entries = atfa_awards_ensure_page( 'entries', 'Flooring Awards 2027: entries open (staff preview)', 'awards-preview', 'private', '[atfa_awards_entries]' );
	update_option( 'atfa_awards_pages', array( 'splash' => $splash, 'entries' => $entries ) );
	set_transient( 'atfa_awards_activated', array( 'splash' => $splash, 'entries' => $entries ), 5 * MINUTE_IN_SECONDS );

	$body = "The Flooring Awards 2027 plugin is active.\n\n"
		. "Splash page (public): " . ( $splash ? get_permalink( $splash ) : 'not created' ) . "\n"
		. "Entries-open preview (staff only, log in first): " . ( $entries ? get_permalink( $entries ) : 'not created' ) . "\n"
		. "Signups list: " . admin_url( 'edit.php?post_type=atfa_notify' ) . "\n\n"
		. "This is also a test of the notify-me email. When someone submits the form you get one like this with their address, and they get a confirmation.";
	wp_mail( ATFA_AWARDS_NOTIFY_TO, 'Awards 2027 pages are live (test email)', $body );
} );

add_action( 'admin_notices', function () {
	$pages = get_transient( 'atfa_awards_activated' );
	if ( ! $pages ) {
		return;
	}
	delete_transient( 'atfa_awards_activated' );
	echo '<div class="notice notice-success"><p><strong>Flooring Awards 2027:</strong> ';
	if ( ! empty( $pages['splash'] ) ) {
		echo 'splash page is live at <a href="' . esc_url( get_permalink( $pages['splash'] ) ) . '">' . esc_html( get_permalink( $pages['splash'] ) ) . '</a>. ';
	}
	if ( ! empty( $pages['entries'] ) ) {
		echo 'Staff preview of the entries-open page: <a href="' . esc_url( get_permalink( $pages['entries'] ) ) . '">' . esc_html( get_permalink( $pages['entries'] ) ) . '</a> (private, visible only when logged in). ';
	}
	echo 'A test email went to ' . esc_html( ATFA_AWARDS_NOTIFY_TO ) . '.</p></div>';
} );

// Optional redirect of the old awards page.
add_action( 'template_redirect', function () {
	if ( ! ATFA_AWARDS_REDIRECT_OLD_PAGE || ! is_page( 'annual-awards' ) ) {
		return;
	}
	$pages = get_option( 'atfa_awards_pages' );
	if ( ! empty( $pages['splash'] ) ) {
		wp_redirect( get_permalink( $pages['splash'] ), 301 );
		exit;
	}
} );

/* ---------------------------------------------------------------------------
 * Link previews (LinkedIn, Facebook, WhatsApp, Teams, Slack)
 *
 * The share card becomes each page's featured image and the description its
 * excerpt, which is what SEO plugins read for og:image and og:description.
 * When no SEO plugin is active, the blank template prints the tags itself.
 * ------------------------------------------------------------------------ */

function atfa_awards_share_texts() {
	return array(
		'splash'  => array(
			'title'       => 'ATFA Flooring Awards 2027',
			'description' => 'Entries open Monday 1 February 2027. Overseas and school projects, expert panel judging, 3-2-1 member voting, and new Parquetry and Newcomers awards. Awards dinner Saturday 22 May 2027, Sydney.',
		),
		'entries' => array(
			'title'       => 'ATFA Flooring Awards 2027: entries open',
			'description' => 'Twenty awards, three ways to win. Enter from your phone in about ten minutes with photos straight from your camera roll. Entries close Wednesday 31 March 2027.',
		),
	);
}

function atfa_awards_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SLIM_SEO_VER' ) || class_exists( 'The_SEO_Framework\Load' );
}

/**
 * Puts the bundled share card in the Media Library (once per card version) and sets it as the featured image
 * of both awards pages, with the description as the excerpt. Runs after activation and after each update.
 */
function atfa_awards_sync_share_card() {
	if ( get_option( 'atfa_awards_share_synced' ) === ATFA_AWARDS_VERSION ) {
		return;
	}
	$pages = get_option( 'atfa_awards_pages' );
	if ( empty( $pages ) || ! is_array( $pages ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$src      = ATFA_AWARDS_DIR . 'assets/img/share-card.jpg';
	$hash     = file_exists( $src ) ? md5_file( $src ) : '';
	$existing = get_option( 'atfa_awards_share_attachment' );
	if ( $hash && ( empty( $existing['hash'] ) || $existing['hash'] !== $hash || ! get_post( $existing['id'] ) ) ) {
		$bits = wp_upload_bits( 'atfa-awards-2027-share-card.jpg', null, file_get_contents( $src ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( empty( $bits['error'] ) ) {
			$id = wp_insert_attachment( array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'ATFA Flooring Awards 2027 share card',
				'post_status'    => 'inherit',
			), $bits['file'] );
			if ( $id && ! is_wp_error( $id ) ) {
				wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $bits['file'] ) );
				$existing = array( 'id' => (int) $id, 'hash' => $hash );
				update_option( 'atfa_awards_share_attachment', $existing );
			}
		}
	}

	$texts = atfa_awards_share_texts();
	foreach ( $pages as $key => $page_id ) {
		if ( ! $page_id || ! get_post( $page_id ) ) {
			continue;
		}
		if ( ! empty( $existing['id'] ) ) {
			set_post_thumbnail( $page_id, $existing['id'] );
		}
		if ( isset( $texts[ $key ] ) ) {
			wp_update_post( array( 'ID' => $page_id, 'post_excerpt' => $texts[ $key ]['description'] ) );
		}
	}
	update_option( 'atfa_awards_share_synced', ATFA_AWARDS_VERSION );
}
add_action( 'admin_init', 'atfa_awards_sync_share_card' );

/**
 * The preview tags, printed by the blank template when no SEO plugin is doing it.
 */
function atfa_awards_print_share_meta() {
	if ( atfa_awards_seo_plugin_active() ) {
		return;
	}
	$id    = get_queried_object_id();
	$key   = (string) get_post_meta( $id, '_atfa_awards_page', true );
	$texts = atfa_awards_share_texts();
	$text  = isset( $texts[ $key ] ) ? $texts[ $key ] : array( 'title' => get_the_title( $id ), 'description' => get_the_excerpt( $id ) );
	$image = get_the_post_thumbnail_url( $id, 'full' );
	if ( ! $image ) {
		$image = ATFA_AWARDS_URL . 'assets/img/share-card.jpg';
	}
	$tags = array(
		array( 'name', 'description', $text['description'] ),
		array( 'property', 'og:type', 'website' ),
		array( 'property', 'og:site_name', 'ATFA' ),
		array( 'property', 'og:locale', 'en_AU' ),
		array( 'property', 'og:title', $text['title'] ),
		array( 'property', 'og:description', $text['description'] ),
		array( 'property', 'og:url', get_permalink( $id ) ),
		array( 'property', 'og:image', $image ),
		array( 'property', 'og:image:width', '1200' ),
		array( 'property', 'og:image:height', '630' ),
		array( 'name', 'twitter:card', 'summary_large_image' ),
		array( 'name', 'twitter:title', $text['title'] ),
		array( 'name', 'twitter:description', $text['description'] ),
		array( 'name', 'twitter:image', $image ),
	);
	foreach ( $tags as $tag ) {
		echo '<meta ' . $tag[0] . '="' . esc_attr( $tag[1] ) . '" content="' . esc_attr( $tag[2] ) . '">' . "\n";
	}
}

/* ---------------------------------------------------------------------------
 * Updates from GitHub
 *
 * WordPress reads the "Update URI" header, sees github.com, and asks the
 * update_plugins_github.com filter whether a newer version exists. We answer
 * from manifest.json in the repo (cached for ten minutes). The manifest names
 * the version and the zip to install, so a push to the repo shows up as
 * "Update now" on the Plugins page, or installs itself if auto-updates are on.
 * ------------------------------------------------------------------------ */

function atfa_awards_manifest( $force = false ) {
	$key = 'atfa_awards_manifest';
	if ( ! $force ) {
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}
	// The query string gets past GitHub's five-minute cache of raw files, so a push is seen as soon as we look.
	$response = wp_remote_get( add_query_arg( 'nocache', time(), ATFA_AWARDS_MANIFEST_URL ), array( 'timeout' => 10, 'headers' => array( 'Accept' => 'application/json' ) ) );
	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}
	$manifest = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $manifest ) || empty( $manifest['version'] ) || empty( $manifest['package'] ) ) {
		return null;
	}
	set_transient( $key, $manifest, 10 * MINUTE_IN_SECONDS );
	return $manifest;
}

function atfa_awards_update_info( $manifest ) {
	return array(
		'id'           => 'https://github.com/' . ATFA_AWARDS_GITHUB_REPO,
		'slug'         => 'atfa-awards-2027',
		'plugin'       => plugin_basename( ATFA_AWARDS_FILE ),
		'version'      => $manifest['version'],
		'new_version'  => $manifest['version'],
		'url'          => isset( $manifest['url'] ) ? $manifest['url'] : '',
		'package'      => $manifest['package'],
		'tested'       => isset( $manifest['tested'] ) ? $manifest['tested'] : '',
		'requires'     => isset( $manifest['requires'] ) ? $manifest['requires'] : '6.0',
		'requires_php' => isset( $manifest['requires_php'] ) ? $manifest['requires_php'] : '7.4',
	);
}

add_filter( 'update_plugins_github.com', function ( $update, $plugin_data, $plugin_file ) {
	if ( plugin_basename( ATFA_AWARDS_FILE ) !== $plugin_file ) {
		return $update;
	}
	$manifest = atfa_awards_manifest();
	return $manifest ? atfa_awards_update_info( $manifest ) : $update;
}, 10, 3 );

// "View version x.y.z details" on the Plugins page.
add_filter( 'plugins_api', function ( $result, $action, $args ) {
	if ( 'plugin_information' !== $action || empty( $args->slug ) || 'atfa-awards-2027' !== $args->slug ) {
		return $result;
	}
	$manifest = atfa_awards_manifest();
	if ( ! $manifest ) {
		return $result;
	}
	$info = atfa_awards_update_info( $manifest );
	return (object) array(
		'name'          => isset( $manifest['name'] ) ? $manifest['name'] : 'ATFA Flooring Awards 2027',
		'slug'          => 'atfa-awards-2027',
		'version'       => $info['version'],
		'author'        => 'ATFA',
		'homepage'      => $info['url'],
		'download_link' => $info['package'],
		'requires'      => $info['requires'],
		'tested'        => $info['tested'],
		'requires_php'  => $info['requires_php'],
		'last_updated'  => isset( $manifest['last_updated'] ) ? $manifest['last_updated'] : '',
		'sections'      => array(
			'description' => 'The 2027 awards season pages for atfa.com.au.',
			'changelog'   => isset( $manifest['changelog'] ) ? wp_kses_post( $manifest['changelog'] ) : '',
		),
	);
}, 10, 3 );

// "Check again" on Dashboard > Updates looks at GitHub afresh. Priority 0, so the cached manifest is gone before
// WordPress runs its own update check on that page (hooked to the same action at priority 10).
add_action( 'load-update-core.php', function () {
	delete_transient( 'atfa_awards_manifest' );
}, 0 );

// The "Check for updates" link under the plugin: forget the cached manifest and WordPress's cached list of updates,
// check now, then go back to the plain Plugins page so "Update now" shows on the first click. Priority 0 runs this
// before WordPress's own check; the redirect stops a refresh from clearing the list again.
add_action( 'load-plugins.php', function () {
	if ( ! isset( $_GET['atfa-check-updates'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}
	delete_transient( 'atfa_awards_manifest' );
	delete_site_transient( 'update_plugins' );
	if ( function_exists( 'wp_update_plugins' ) ) {
		wp_update_plugins();
	}
	wp_safe_redirect( admin_url( 'plugins.php' ) );
	exit;
}, 0 );

// A "Check for updates" link under the plugin on the Plugins page.
add_filter( 'plugin_action_links_' . plugin_basename( ATFA_AWARDS_FILE ), function ( $links ) {
	$links[] = '<a href="' . esc_url( admin_url( 'plugins.php?atfa-check-updates=1' ) ) . '">Check for updates</a>';
	return $links;
} );

// Keep the folder name stable when WordPress unpacks the update.
add_filter( 'upgrader_source_selection', function ( $source, $remote_source, $upgrader, $hook_extra ) {
	if ( empty( $hook_extra['plugin'] ) || plugin_basename( ATFA_AWARDS_FILE ) !== $hook_extra['plugin'] ) {
		return $source;
	}
	$wanted = trailingslashit( $remote_source ) . 'atfa-awards-2027/';
	if ( untrailingslashit( $source ) === untrailingslashit( $wanted ) ) {
		return $source;
	}
	global $wp_filesystem;
	if ( $wp_filesystem && $wp_filesystem->move( $source, $wanted ) ) {
		return $wanted;
	}
	return $source;
}, 10, 4 );
