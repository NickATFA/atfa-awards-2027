<?php
/**
 * Ladies Breakfast 2027: the page at /ladies-breakfast-2027/ and the seat reservations it takes.
 *
 * - The page is made the first time this version runs, private (staff only) on the blank canvas template with the
 *   [atfa_awards_breakfast] shortcode, and kept in the atfa_awards_pages option with the other awards pages. Publish
 *   it in wp-admin when it is ready: the other pages link to it from then on, and visitors who find the address
 *   before that are sent to the awards page.
 * - Reservations go to /wp-json/atfa-awards/v1/breakfast/rsvp, are kept as "Breakfast RSVPs" in wp-admin (with a
 *   CSV export), and each one emails Cherie (copy to Nick) and the guest. While the page is still private a
 *   reservation sent from a staff login is a test: saved with a [Test] prefix and emailed to that person only.
 * - Everything the page says about the event is in data/breakfast.php.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ATFA_BK_RSVP', 'atfa_bk_rsvp' );
define( 'ATFA_BK_SPONSOR', 'atfa_bk_sponsor' );
define( 'ATFA_BK_SLUG', 'ladies-breakfast-2027' );

/**
 * The event details (data/breakfast.php), with every key present.
 */
function atfa_bk_info() {
	static $info = null;
	if ( null !== $info ) {
		return $info;
	}
	$file = ATFA_AWARDS_DIR . 'data/breakfast.php';
	$info = file_exists( $file ) ? include $file : array();
	$info = is_array( $info ) ? $info : array();
	$info = array_merge( array(
		'title'           => 'Ladies Breakfast 2027',
		'preview'         => 'manage_options',
		'date'            => '2027-05-22',
		'date_text'       => 'Saturday 22 May 2027',
		'day_text'        => 'Saturday morning, 22 May 2027',
		'time_text'       => '',
		'venue'           => array(),
		'price'           => array(),
		'places'          => 0,
		'host'            => 'Hosted by ATFA',
		'rsvp'            => array(),
		'sponsors'        => array(),
		'sponsorship'     => array(),
		'photos'          => array(),
		'hero'            => array(),
	), $info );
	$info['venue']   = array_merge( array( 'name' => '', 'room' => '', 'area' => '', 'address' => '', 'map' => '', 'note' => '', 'confirmed' => false ), (array) $info['venue'] );
	$info['price']   = array_merge( array( 'text' => 'Complimentary', 'detail' => '' ), (array) $info['price'] );
	$info['rsvp']    = array_merge( array( 'open' => true, 'to' => 'cherie@atfa.com.au', 'cc' => '', 'contact' => 'Cherie', 'email' => 'cherie@atfa.com.au' ), (array) $info['rsvp'] );
	// Sponsors: one Gold (an array, or empty) and a list of Silver, each with name, img and url.
	$sponsors = array_merge( array( 'gold' => array(), 'silver' => array() ), (array) $info['sponsors'] );
	$clean    = function ( $sp ) {
		$sp = array_merge( array( 'name' => '', 'img' => '', 'url' => '' ), (array) $sp );
		return '' !== trim( (string) $sp['name'] ) ? $sp : null;
	};
	$gold   = $clean( $sponsors['gold'] );
	$silver = array();
	foreach ( (array) $sponsors['silver'] as $sp ) {
		$sp = $clean( $sp );
		if ( $sp ) {
			$silver[] = $sp;
		}
	}
	$info['sponsors'] = array( 'gold' => $gold, 'silver' => $silver );
	// The packages, every key present.
	$info['sponsorship'] = array_merge( array( 'open' => true, 'to' => 'phil@atfa.com.au', 'cc' => '', 'contact' => 'Phil', 'tiers' => array() ), (array) $info['sponsorship'] );
	$tiers = array();
	foreach ( array( 'gold', 'silver' ) as $key ) {
		$tier = isset( $info['sponsorship']['tiers'][ $key ] ) ? (array) $info['sponsorship']['tiers'][ $key ] : array();
		$tier = array_merge( array( 'name' => ucfirst( $key ) . ' Sponsor', 'limit' => 'gold' === $key ? 1 : 6, 'price' => '', 'lead' => '', 'includes' => array() ), $tier );
		$tier['limit']    = max( 0, (int) $tier['limit'] );
		$tier['includes'] = array_values( array_filter( array_map( 'strval', (array) $tier['includes'] ) ) );
		$tier['taken']    = 'gold' === $key ? ( $gold ? 1 : 0 ) : count( $silver );
		$tier['left']     = max( 0, $tier['limit'] - $tier['taken'] );
		$tiers[ $key ]    = $tier;
	}
	$info['sponsorship']['tiers'] = $tiers;
	foreach ( $info['photos'] as $k => $set ) {
		$info['photos'][ $k ] = array_merge( array( 'label' => '', 'short' => '', 'ids' => array() ), (array) $set );
		$info['photos'][ $k ]['ids'] = array_values( array_filter( array_map( 'intval', (array) $info['photos'][ $k ]['ids'] ) ) );
	}
	$info['hero'] = array_values( array_filter( array_map( 'intval', (array) $info['hero'] ) ) );
	return $info;
}

/* ---------------------------------------------------------------------------
 * The page
 * ------------------------------------------------------------------------ */

function atfa_bk_page_id() {
	$pages = get_option( 'atfa_awards_pages' );
	$id    = ( is_array( $pages ) && ! empty( $pages['breakfast'] ) ) ? (int) $pages['breakfast'] : 0;
	return ( $id && 'page' === get_post_type( $id ) ) ? $id : 0;
}

/**
 * The page's address, built from its path so a private page never gives out a ?page_id= link.
 */
function atfa_bk_page_url() {
	global $wp_rewrite;
	$id = atfa_bk_page_id();
	if ( ! $id ) {
		return home_url( '/' . ATFA_BK_SLUG . '/' );
	}
	if ( $wp_rewrite && $wp_rewrite->using_permalinks() ) {
		return home_url( user_trailingslashit( get_page_uri( $id ), 'page' ) );
	}
	return home_url( '?page_id=' . $id );
}

/**
 * Whether this person may open the page while it is private: 'preview' in data/breakfast.php names the capability
 * (administrators only by default).
 */
function atfa_bk_is_staff() {
	$cap = (string) atfa_bk_info()['preview'];
	return is_user_logged_in() && current_user_can( '' !== $cap ? $cap : 'manage_options' );
}

/**
 * Whether the page is published. While it is private only staff can open it, and the other pages only link to it
 * for them.
 */
function atfa_bk_is_public() {
	$id = atfa_bk_page_id();
	return $id && 'publish' === get_post_status( $id );
}

/**
 * Whether this visitor can open the page: everyone once it is published, the preview people before that.
 */
function atfa_bk_visible() {
	$id = atfa_bk_page_id();
	return $id && ( 'publish' === get_post_status( $id ) || atfa_bk_is_staff() );
}

/**
 * Makes the page the first time this version runs: /ladies-breakfast-2027/, private, on the blank canvas template.
 */
function atfa_bk_ensure_page() {
	if ( get_option( 'atfa_bk_page_checked' ) === ATFA_AWARDS_VERSION ) {
		return;
	}
	$pages = get_option( 'atfa_awards_pages' );
	$pages = is_array( $pages ) ? $pages : array();
	$id    = atfa_bk_page_id();
	if ( $id && 'trash' === get_post_status( $id ) ) {
		$id = 0;
	}
	if ( ! $id ) {
		$found = get_posts( array(
			'post_type'   => 'page',
			'post_status' => array( 'publish', 'private', 'draft', 'pending', 'future' ),
			'meta_key'    => '_atfa_awards_page',
			'meta_value'  => 'breakfast',
			'numberposts' => 1,
			'fields'      => 'ids',
		) );
		$id = $found ? (int) $found[0] : 0;
	}
	if ( ! $id ) {
		$id = wp_insert_post( array(
			'post_type'      => 'page',
			'post_title'     => atfa_bk_info()['title'],
			'post_name'      => ATFA_BK_SLUG,
			'post_status'    => 'private',
			'post_content'   => '[atfa_awards_breakfast]',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'meta_input'     => array( '_atfa_awards_page' => 'breakfast' ),
		), true );
		if ( is_wp_error( $id ) || ! $id ) {
			return;
		}
	}
	update_post_meta( $id, '_wp_page_template', 'atfa-awards-blank.php' );
	$pages['breakfast'] = (int) $id;
	update_option( 'atfa_awards_pages', $pages );
	update_option( 'atfa_bk_page_checked', ATFA_AWARDS_VERSION );
}
add_action( 'init', 'atfa_bk_ensure_page', 20 );

function atfa_bk_is_page() {
	$id = atfa_bk_page_id();
	return $id && is_page( $id );
}

/**
 * While the page is private, anyone outside the preview group who goes to its address is sent to the awards page
 * rather than a "page not found": visitors (WordPress gives them a 404) and logged-in staff who could otherwise read
 * a private page. Temporary (302), so browsers forget it once the page is published.
 */
function atfa_bk_hidden_redirect() {
	global $wp;
	if ( is_admin() ) {
		return;
	}
	$id = atfa_bk_page_id();
	if ( ! $id || 'publish' === get_post_status( $id ) ) {
		return;
	}
	$here = is_page( $id ) || ( is_404() && isset( $wp->request ) && trim( (string) $wp->request, '/' ) === ATFA_BK_SLUG );
	if ( ! $here || atfa_bk_is_staff() ) {
		return;
	}
	$pages = get_option( 'atfa_awards_pages' );
	$to    = ( is_array( $pages ) && ! empty( $pages['splash'] ) && 'publish' === get_post_status( (int) $pages['splash'] ) ) ? get_permalink( (int) $pages['splash'] ) : home_url( '/' );
	nocache_headers();
	wp_safe_redirect( add_query_arg( 'from', ATFA_BK_SLUG, $to ), 302 );
	exit;
}
add_action( 'template_redirect', 'atfa_bk_hidden_redirect', 5 );

/**
 * Menu links to the page only show for people who can open it while it's private (JetMenu reads the items
 * directly, so both filters are needed, as for the Expo page).
 */
function atfa_bk_menu_items( $items ) {
	if ( is_admin() || ! is_array( $items ) ) {
		return $items;
	}
	$id = atfa_bk_page_id();
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
add_filter( 'wp_nav_menu_objects', 'atfa_bk_menu_items' );
add_filter( 'wp_get_nav_menu_items', function ( $items ) {
	$out = atfa_bk_menu_items( $items );
	return is_array( $out ) ? array_values( $out ) : $out;
} );

// The headline's serif (Playfair Display), on this page only. The other two fonts come from the template.
add_action( 'wp_head', function () {
	if ( atfa_bk_is_page() ) {
		echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap">' . "\n";
	}
}, 2 );

add_shortcode( 'atfa_awards_breakfast', function () {
	ob_start();
	include ATFA_AWARDS_DIR . 'views/breakfast.php';
	return ob_get_clean();
} );

/**
 * <img> for a Media Library photo, with sizes for phones and big screens. '' if it isn't there.
 * $args: class, sizes, style, alt, lazy, first, size (the WordPress size for src: large by default).
 */
function atfa_bk_photo( $id, $args = array() ) {
	$id = (int) $id;
	if ( ! $id || ! function_exists( 'wp_get_attachment_image_src' ) ) {
		return '';
	}
	$size = isset( $args['size'] ) ? $args['size'] : 'large';
	$src  = wp_get_attachment_image_src( $id, $size );
	if ( ! $src || empty( $src[0] ) ) {
		return '';
	}
	$srcset = wp_get_attachment_image_srcset( $id, $size );
	$alt    = isset( $args['alt'] ) ? $args['alt'] : (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
	$attrs  = array(
		'class'    => isset( $args['class'] ) ? $args['class'] : '',
		'src'      => $src[0],
		'srcset'   => $srcset ? $srcset : '',
		'sizes'    => $srcset && isset( $args['sizes'] ) ? $args['sizes'] : '',
		'width'    => ! empty( $src[1] ) ? (int) $src[1] : '',
		'height'   => ! empty( $src[2] ) ? (int) $src[2] : '',
		'alt'      => $alt,
		'style'    => isset( $args['style'] ) ? $args['style'] : '',
		'loading'  => ! empty( $args['lazy'] ) ? 'lazy' : '',
		'decoding' => 'async',
	);
	if ( ! empty( $args['first'] ) ) {
		$attrs['fetchpriority'] = 'high';
	}
	$html = '<img';
	foreach ( $attrs as $name => $value ) {
		if ( '' === $value && 'alt' !== $name ) {
			continue;
		}
		$html .= ' ' . $name . '="' . ( 'src' === $name ? esc_url( $value ) : esc_attr( $value ) ) . '"';
	}
	return $html . ' data-no-lazy="1">';
}

/**
 * The full-size address of a photo, for the lightbox.
 */
function atfa_bk_photo_full( $id ) {
	$src = wp_get_attachment_image_src( (int) $id, 'full' );
	return ( $src && ! empty( $src[0] ) ) ? $src[0] : '';
}

/**
 * The photos that exist in the Media Library, by set, in the order of the data file.
 */
function atfa_bk_photos() {
	$out = array();
	foreach ( atfa_bk_info()['photos'] as $key => $set ) {
		$ids = array();
		foreach ( $set['ids'] as $id ) {
			if ( 'attachment' === get_post_type( $id ) ) {
				$ids[] = (int) $id;
			}
		}
		if ( $ids ) {
			$set['ids'] = $ids;
			$out[ $key ] = $set;
		}
	}
	return $out;
}

/**
 * A sponsor's tile: the logo from assets/img/sponsors/ when there is one, otherwise the name, linked when there is a URL.
 */
function atfa_bk_sponsor_tile( $sp, $class = '' ) {
	$sp    = array_merge( array( 'name' => '', 'img' => '', 'url' => '' ), (array) $sp );
	$inner = '' !== $sp['img']
		? '<img src="' . esc_url( atfa_awards_img( $sp['img'] ) ) . '" alt="' . esc_attr( $sp['name'] ) . '" loading="lazy">'
		: esc_html( $sp['name'] );
	$cls   = 'sponsor' . ( '' !== $class ? ' ' . $class : '' );
	if ( '' !== $sp['url'] ) {
		return '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $sp['url'] ) . '" target="_blank" rel="noopener" title="' . esc_attr( $sp['name'] ) . '">' . $inner . '</a>';
	}
	return '<span class="' . esc_attr( $cls ) . '" title="' . esc_attr( $sp['name'] ) . '">' . $inner . '</span>';
}

/* ---------------------------------------------------------------------------
 * Reservations: post type, REST endpoint, emails, CSV export
 * ------------------------------------------------------------------------ */

add_action( 'init', function () {
	register_post_type( ATFA_BK_RSVP, array(
		'labels' => array(
			'name'               => 'Breakfast RSVPs',
			'singular_name'      => 'Breakfast RSVP',
			'menu_name'          => 'Breakfast RSVPs',
			'all_items'          => 'All RSVPs',
			'edit_item'          => 'RSVP',
			'search_items'       => 'Search RSVPs',
			'not_found'          => 'No reservations yet.',
			'not_found_in_trash' => 'No reservations in the bin.',
		),
		'description'         => 'Seats reserved for the Ladies Breakfast 2027 from the page on the website.',
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'exclude_from_search' => true,
		'menu_icon'           => 'dashicons-coffee',
		'menu_position'       => 26,
		'supports'            => array( 'title' ),
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'atfa-awards/v1', '/breakfast/rsvp', array(
		'methods'             => 'POST',
		'callback'            => 'atfa_bk_rest_rsvp',
		'permission_callback' => '__return_true',
	) );
} );

function atfa_bk_text( $value, $max ) {
	$value = sanitize_text_field( (string) $value );
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
}

/**
 * How many seats are reserved (real reservations only, not tests).
 */
function atfa_bk_rsvp_count() {
	$ids = get_posts( array(
		'post_type'   => ATFA_BK_RSVP,
		'post_status' => 'private',
		'numberposts' => -1,
		'fields'      => 'ids',
		'meta_query'  => array( array( 'key' => 'test', 'value' => '1', 'compare' => '!=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	) );
	$seats = 0;
	foreach ( $ids as $id ) {
		$seats += max( 1, (int) get_post_meta( $id, 'seats', true ) );
	}
	return $seats;
}

function atfa_bk_rest_rsvp( WP_REST_Request $request ) {
	$info  = atfa_bk_info();
	$rsvp  = $info['rsvp'];
	$fail  = function ( $message, $status = 400 ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => $message ), $status );
	};
	$email_help = 'Email ' . $rsvp['email'] . ' and we will reserve it for you.';

	if ( empty( $rsvp['open'] ) ) {
		return $fail( 'Reservations for the breakfast have closed. ' . $email_help );
	}
	// Honeypot: people never fill the hidden "website" field.
	if ( '' !== trim( (string) $request->get_param( 'website' ) ) ) {
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'Thanks, your seat is reserved.' ), 200 );
	}
	$key   = 'atfa_bk_try_' . md5( atfa_awards_client_ip() );
	$count = (int) get_transient( $key );
	if ( $count >= 10 ) {
		return $fail( 'Too many attempts from here in the last hour. ' . $email_help, 429 );
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	$name     = atfa_bk_text( $request->get_param( 'name' ), 100 );
	$business = atfa_bk_text( $request->get_param( 'business' ), 120 );
	$email    = sanitize_email( (string) $request->get_param( 'email' ) );
	$phone    = atfa_bk_text( $request->get_param( 'phone' ), 40 );
	$guest    = atfa_bk_text( $request->get_param( 'guest' ), 100 );
	$dietary  = sanitize_textarea_field( (string) $request->get_param( 'dietary' ) );
	$dietary  = function_exists( 'mb_substr' ) ? mb_substr( $dietary, 0, 500 ) : substr( $dietary, 0, 500 );
	$page     = esc_url_raw( (string) $request->get_param( 'page' ) );
	if ( '' === $name ) {
		return $fail( 'Please add your name.' );
	}
	if ( '' === $business ) {
		return $fail( 'Please add your business.' );
	}
	if ( ! $email || ! is_email( $email ) ) {
		return $fail( 'Please add a valid email address.' );
	}

	// A staff member trying the form while the page is still private: saved as a test, emailed to them only.
	$test = atfa_bk_is_staff() && ! atfa_bk_is_public();

	// Already reserved: say so, no second seat and no second email.
	$existing = get_posts( array(
		'post_type'   => ATFA_BK_RSVP,
		'post_status' => 'private',
		'numberposts' => 1,
		'fields'      => 'ids',
		'meta_key'    => 'email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'  => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	) );
	if ( $existing && ! $test ) {
		$first = trim( strtok( $name, ' ' ) );
		return new WP_REST_Response( array( 'ok' => true, 'duplicate' => true, 'message' => 'Thanks ' . $first . ', you already have a seat reserved under ' . $email . '. To change anything, email ' . $rsvp['email'] . '.' ), 200 );
	}

	$seats = '' !== $guest ? 2 : 1;
	$id    = wp_insert_post( array(
		'post_type'   => ATFA_BK_RSVP,
		'post_status' => 'private',
		'post_title'  => ( $test ? '[Test] ' : '' ) . $name . ', ' . $business,
		'meta_input'  => array(
			'name'     => $name,
			'business' => $business,
			'email'    => $email,
			'phone'    => $phone,
			'guest'    => $guest,
			'dietary'  => $dietary,
			'seats'    => $seats,
			'test'     => $test ? 1 : 0,
			'page'     => $page,
			'ip'       => atfa_awards_client_ip(),
			'ua'       => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 200 ) : '',
		),
	), true );
	if ( is_wp_error( $id ) || ! $id ) {
		return $fail( 'Something went wrong saving your reservation. ' . $email_help, 500 );
	}

	atfa_bk_send_rsvp_emails( (int) $id, $test );

	$first = trim( strtok( $name, ' ' ) );
	return new WP_REST_Response( array(
		'ok'      => true,
		'test'    => $test,
		'message' => $test
			? 'Test reservation saved and emailed to you only. ' . $rsvp['contact'] . ' wasn\'t emailed. It shows as [Test] under Breakfast RSVPs.'
			: 'Thanks ' . $first . ', your seat is reserved' . ( 2 === $seats ? ' and so is ' . $guest . '\'s' : '' ) . '. A confirmation is on its way to ' . $email . ', and we will send the final details closer to the day.',
	), 200 );
}

/**
 * The lines that describe a reservation, for the emails and the list.
 */
function atfa_bk_rsvp_lines( $post_id ) {
	$m     = function ( $k ) use ( $post_id ) {
		return (string) get_post_meta( $post_id, $k, true );
	};
	$lines = array(
		'Name: ' . $m( 'name' ),
		'Business: ' . $m( 'business' ),
		'Email: ' . $m( 'email' ),
	);
	if ( '' !== $m( 'phone' ) ) {
		$lines[] = 'Mobile: ' . $m( 'phone' );
	}
	if ( '' !== $m( 'guest' ) ) {
		$lines[] = 'Bringing: ' . $m( 'guest' );
	}
	if ( '' !== $m( 'dietary' ) ) {
		$lines[] = 'Dietary: ' . $m( 'dietary' );
	}
	return implode( "\n", $lines );
}

/**
 * The event details as plain text, for the guest's confirmation.
 */
function atfa_bk_details_text() {
	$info  = atfa_bk_info();
	$v     = $info['venue'];
	$tbc   = empty( $v['confirmed'] ) ? ' (to be confirmed)' : '';
	$lines = array( 'When: ' . $info['date_text'] . ( '' !== $info['time_text'] ? ', ' . $info['time_text'] : '' ) . $tbc );
	if ( '' !== $v['name'] ) {
		$lines[] = 'Where: ' . $v['name'] . ( '' !== $v['room'] ? ', ' . $v['room'] : '' ) . ( '' !== $v['address'] ? ', ' . $v['address'] : '' ) . $tbc;
	}
	$lines[] = 'Cost: ' . $info['price']['text'] . ( '' !== $info['price']['detail'] ? '. ' . $info['price']['detail'] : '' );
	return implode( "\n", $lines );
}

function atfa_bk_send_rsvp_emails( $post_id, $test = false ) {
	$info  = atfa_bk_info();
	$rsvp  = $info['rsvp'];
	$m     = function ( $k ) use ( $post_id ) {
		return (string) get_post_meta( $post_id, $k, true );
	};
	$seats = max( 1, (int) $m( 'seats' ) );
	$when  = wp_date( 'l j F Y, g:i a' );
	$list  = admin_url( 'edit.php?post_type=' . ATFA_BK_RSVP );
	$total = atfa_bk_rsvp_count();

	// To Cherie (copy to Nick), or to the staff member trying it out.
	$to      = $test ? wp_get_current_user()->user_email : $rsvp['to'];
	$headers = array( 'Reply-To: ' . $m( 'name' ) . ' <' . $m( 'email' ) . '>' );
	if ( ! $test && '' !== trim( (string) $rsvp['cc'] ) ) {
		$headers[] = 'Cc: ' . $rsvp['cc'];
	}
	$body = $m( 'name' ) . ' (' . $m( 'business' ) . ') reserved ' . ( 2 === $seats ? 'two seats' : 'a seat' ) . ' at the ' . $info['title'] . ".\n\n"
		. atfa_bk_rsvp_lines( $post_id ) . "\n\n"
		. ( $test
			? "This was a test from a staff login while the page is still private, so nobody else was emailed.\n"
			: 'Seats reserved so far: ' . $total . ( $info['places'] ? ' of ' . (int) $info['places'] : '' ) . "\n" )
		. "All reservations (export to CSV from here): " . $list . "\n\n"
		. 'Sent from ' . ( '' !== $m( 'page' ) ? $m( 'page' ) : atfa_bk_page_url() ) . ' on ' . $when . "\n"
		. 'Reply to this email to reach ' . $m( 'name' ) . '.';
	wp_mail( $to, ( $test ? '[Test] ' : '' ) . 'Ladies Breakfast 2027 RSVP: ' . $m( 'name' ) . ', ' . $m( 'business' ), $body, $headers );

	// To the guest.
	$first   = trim( strtok( $m( 'name' ), ' ' ) );
	$confirm = 'Hi ' . $first . ",\n\n"
		. 'Your seat at the ' . $info['title'] . ' is reserved' . ( 2 === $seats ? ', and so is ' . $m( 'guest' ) . '\'s' : '' ) . ".\n\n"
		. atfa_bk_details_text() . "\n\n"
		. "We will email the final details closer to the day. If your plans change, please let us know so the seat can go to someone else.\n\n"
		. 'Questions? Reply to this email or contact ' . $rsvp['contact'] . ' at ' . $rsvp['email'] . ".\n"
		. 'The breakfast page: ' . atfa_bk_page_url() . "\n\n"
		. "Regards,\nThe ATFA team\nAU 1300 361 693 · NZ 0800 453 715\nhttps://www.atfa.com.au/";
	wp_mail( $test ? wp_get_current_user()->user_email : $m( 'email' ), ( $test ? '[Test] ' : '' ) . 'Your seat at the ' . $info['title'], $confirm, array( 'Reply-To: ' . $rsvp['email'] ) );
}

// Admin list: name, business, email, mobile, bringing, dietary, date, plus an export link.
add_filter( 'manage_' . ATFA_BK_RSVP . '_posts_columns', function ( $columns ) {
	return array(
		'cb'       => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'    => 'Name',
		'business' => 'Business',
		'email'    => 'Email',
		'phone'    => 'Mobile',
		'guest'    => 'Bringing',
		'dietary'  => 'Dietary',
		'date'     => 'Reserved',
	);
} );

add_action( 'manage_' . ATFA_BK_RSVP . '_posts_custom_column', function ( $column, $post_id ) {
	if ( in_array( $column, array( 'business', 'email', 'phone', 'guest', 'dietary' ), true ) ) {
		echo esc_html( (string) get_post_meta( $post_id, $column, true ) );
	}
}, 10, 2 );

add_filter( 'views_edit-' . ATFA_BK_RSVP, function ( $views ) {
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=atfa_bk_export' ), 'atfa_bk_export' );
	$views['atfa_export'] = '<a href="' . esc_url( $url ) . '">Export all to CSV</a>';
	$views['atfa_page']   = '<a href="' . esc_url( atfa_bk_page_url() ) . '">Open the breakfast page</a>';
	return $views;
} );

add_action( 'admin_post_atfa_bk_export', function () {
	if ( ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'atfa_bk_export' ) ) {
		wp_die( 'Not allowed.' );
	}
	$rows = get_posts( array( 'post_type' => ATFA_BK_RSVP, 'post_status' => 'private', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'ASC' ) );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="ladies-breakfast-2027-rsvps-' . wp_date( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'Name', 'Business', 'Email', 'Mobile', 'Bringing', 'Dietary', 'Seats', 'Reserved', 'Test' ) );
	foreach ( $rows as $row ) {
		$m = function ( $k ) use ( $row ) {
			return (string) get_post_meta( $row->ID, $k, true );
		};
		fputcsv( $out, array( $m( 'name' ), $m( 'business' ), $m( 'email' ), $m( 'phone' ), $m( 'guest' ), $m( 'dietary' ), max( 1, (int) $m( 'seats' ) ), get_date_from_gmt( $row->post_date_gmt, 'Y-m-d H:i' ), '1' === $m( 'test' ) ? 'yes' : '' ) );
	}
	fclose( $out );
	exit;
} );

/* ---------------------------------------------------------------------------
 * Sponsorship enquiries: post type, REST endpoint, emails
 * ------------------------------------------------------------------------ */

add_action( 'init', function () {
	register_post_type( ATFA_BK_SPONSOR, array(
		'labels' => array(
			'name'               => 'Breakfast sponsor enquiries',
			'singular_name'      => 'Sponsor enquiry',
			'menu_name'          => 'Sponsor enquiries',
			'all_items'          => 'Sponsor enquiries',
			'edit_item'          => 'Sponsor enquiry',
			'search_items'       => 'Search enquiries',
			'not_found'          => 'No sponsorship enquiries yet.',
			'not_found_in_trash' => 'No enquiries in the bin.',
		),
		'description'         => 'Gold and Silver sponsorship enquiries from the Ladies Breakfast 2027 page.',
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => 'edit.php?post_type=' . ATFA_BK_RSVP,
		'show_in_rest'        => false,
		'exclude_from_search' => true,
		'supports'            => array( 'title' ),
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'atfa-awards/v1', '/breakfast/sponsor', array(
		'methods'             => 'POST',
		'callback'            => 'atfa_bk_rest_sponsor',
		'permission_callback' => '__return_true',
	) );
} );

/**
 * The tier a visitor asked about, as its key ('gold', 'silver') or 'unsure'.
 */
function atfa_bk_tier_key( $value ) {
	$value = strtolower( trim( (string) $value ) );
	return in_array( $value, array( 'gold', 'silver' ), true ) ? $value : 'unsure';
}

function atfa_bk_tier_label( $key ) {
	$tiers = atfa_bk_info()['sponsorship']['tiers'];
	return isset( $tiers[ $key ] ) ? $tiers[ $key ]['name'] : 'Not sure yet';
}

function atfa_bk_sponsor_count() {
	return count( get_posts( array(
		'post_type'   => ATFA_BK_SPONSOR,
		'post_status' => 'private',
		'numberposts' => -1,
		'fields'      => 'ids',
		'meta_query'  => array( array( 'key' => 'test', 'value' => '1', 'compare' => '!=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	) ) );
}

function atfa_bk_rest_sponsor( WP_REST_Request $request ) {
	$info = atfa_bk_info();
	$sp   = $info['sponsorship'];
	$fail = function ( $message, $status = 400 ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => $message ), $status );
	};
	$email_help = 'Email ' . $sp['to'] . ' and ' . $sp['contact'] . ' will get back to you.';

	if ( empty( $sp['open'] ) ) {
		return $fail( 'Sponsorship enquiries for the breakfast have closed. ' . $email_help );
	}
	if ( '' !== trim( (string) $request->get_param( 'website' ) ) ) {
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'Thanks, we have your enquiry.' ), 200 );
	}
	$key   = 'atfa_bk_sp_try_' . md5( atfa_awards_client_ip() );
	$count = (int) get_transient( $key );
	if ( $count >= 10 ) {
		return $fail( 'Too many attempts from here in the last hour. ' . $email_help, 429 );
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	$business = atfa_bk_text( $request->get_param( 'business' ), 120 );
	$name     = atfa_bk_text( $request->get_param( 'name' ), 100 );
	$email    = sanitize_email( (string) $request->get_param( 'email' ) );
	$phone    = atfa_bk_text( $request->get_param( 'phone' ), 40 );
	$tier     = atfa_bk_tier_key( $request->get_param( 'tier' ) );
	$message  = sanitize_textarea_field( (string) $request->get_param( 'message' ) );
	$message  = function_exists( 'mb_substr' ) ? mb_substr( $message, 0, 1000 ) : substr( $message, 0, 1000 );
	$page     = esc_url_raw( (string) $request->get_param( 'page' ) );
	if ( '' === $business ) {
		return $fail( 'Please add your business.' );
	}
	if ( '' === $name ) {
		return $fail( 'Please add your name.' );
	}
	if ( ! $email || ! is_email( $email ) ) {
		return $fail( 'Please add a valid email address.' );
	}

	// A staff member trying the form while the page is still private: saved as a test, emailed to them only.
	$test = atfa_bk_is_staff() && ! atfa_bk_is_public();

	$id = wp_insert_post( array(
		'post_type'   => ATFA_BK_SPONSOR,
		'post_status' => 'private',
		'post_title'  => ( $test ? '[Test] ' : '' ) . $business . ' (' . atfa_bk_tier_label( $tier ) . ')',
		'meta_input'  => array(
			'business' => $business,
			'name'     => $name,
			'email'    => $email,
			'phone'    => $phone,
			'tier'     => $tier,
			'message'  => $message,
			'test'     => $test ? 1 : 0,
			'page'     => $page,
			'ip'       => atfa_awards_client_ip(),
			'ua'       => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 200 ) : '',
		),
	), true );
	if ( is_wp_error( $id ) || ! $id ) {
		return $fail( 'Something went wrong saving your enquiry. ' . $email_help, 500 );
	}

	atfa_bk_send_sponsor_emails( (int) $id, $test );

	$first = trim( strtok( $name, ' ' ) );
	return new WP_REST_Response( array(
		'ok'      => true,
		'test'    => $test,
		'message' => $test
			? 'Test enquiry saved and emailed to you only. ' . $sp['contact'] . ' wasn\'t emailed. It shows as [Test] under Breakfast RSVPs > Sponsor enquiries.'
			: 'Thanks ' . $first . ', ' . $sp['contact'] . ' from ATFA will be in touch about ' . ( 'unsure' === $tier ? 'the packages' : atfa_bk_tier_label( $tier ) . 'ship' ) . '. A copy of the package details is on its way to ' . $email . '.',
	), 200 );
}

/**
 * A package as plain text, for the emails.
 */
function atfa_bk_tier_text( $key ) {
	$tiers = atfa_bk_info()['sponsorship']['tiers'];
	if ( ! isset( $tiers[ $key ] ) ) {
		return '';
	}
	$t     = $tiers[ $key ];
	$lines = array( strtoupper( $t['name'] ) . ( '' !== $t['price'] ? ': ' . $t['price'] : ': price on enquiry' ) . ( 1 === $t['limit'] ? ' (one only)' : ' (up to ' . $t['limit'] . ')' ) );
	if ( '' !== $t['lead'] ) {
		$lines[] = $t['lead'];
	}
	foreach ( $t['includes'] as $inc ) {
		$lines[] = '- ' . $inc;
	}
	return implode( "\n", $lines );
}

function atfa_bk_send_sponsor_emails( $post_id, $test = false ) {
	$info = atfa_bk_info();
	$sp   = $info['sponsorship'];
	$m    = function ( $k ) use ( $post_id ) {
		return (string) get_post_meta( $post_id, $k, true );
	};
	$tier  = atfa_bk_tier_key( $m( 'tier' ) );
	$label = atfa_bk_tier_label( $tier );
	$when  = wp_date( 'l j F Y, g:i a' );
	$list  = admin_url( 'edit.php?post_type=' . ATFA_BK_SPONSOR );
	$lines = array( 'Business: ' . $m( 'business' ), 'Contact: ' . $m( 'name' ), 'Email: ' . $m( 'email' ) );
	if ( '' !== $m( 'phone' ) ) {
		$lines[] = 'Mobile: ' . $m( 'phone' );
	}
	$lines[] = 'Package: ' . $label;
	if ( '' !== $m( 'message' ) ) {
		$lines[] = "Message:\n" . $m( 'message' );
	}

	// To Phil (copy to Nick), or to the staff member trying it out.
	$to      = $test ? wp_get_current_user()->user_email : $sp['to'];
	$headers = array( 'Reply-To: ' . $m( 'name' ) . ' <' . $m( 'email' ) . '>' );
	if ( ! $test && '' !== trim( (string) $sp['cc'] ) ) {
		$headers[] = 'Cc: ' . $sp['cc'];
	}
	$body = $m( 'name' ) . ' from ' . $m( 'business' ) . ' asked about ' . ( 'unsure' === $tier ? 'sponsoring' : $label . 'ship of' ) . ' the ' . $info['title'] . ".\n\n"
		. implode( "\n", $lines ) . "\n\n"
		. ( $test
			? "This was a test from a staff login while the page is still private, so nobody else was emailed.\n"
			: 'Enquiries so far: ' . atfa_bk_sponsor_count() . "\n" )
		. 'All enquiries: ' . $list . "\n\n"
		. 'Sent from ' . ( '' !== $m( 'page' ) ? $m( 'page' ) : atfa_bk_page_url() ) . ' on ' . $when . "\n"
		. 'Reply to this email to reach ' . $m( 'name' ) . '.';
	wp_mail( $to, ( $test ? '[Test] ' : '' ) . 'Ladies Breakfast 2027 sponsorship enquiry: ' . $m( 'business' ) . ' (' . $label . ')', $body, $headers );

	// To the enquirer, with the package details.
	$first    = trim( strtok( $m( 'name' ), ' ' ) );
	$packages = 'unsure' === $tier ? trim( atfa_bk_tier_text( 'gold' ) . "\n\n" . atfa_bk_tier_text( 'silver' ) ) : atfa_bk_tier_text( $tier );
	$confirm  = 'Hi ' . $first . ",\n\n"
		. 'Thanks for your interest in sponsoring the ' . $info['title'] . ' (' . $info['date_text'] . ( '' !== $info['venue']['name'] ? ', ' . $info['venue']['name'] : '' ) . '). ' . $sp['contact'] . ' from ATFA will be in touch shortly. Nothing is booked until you confirm with ' . $sp['contact'] . ".\n\n"
		. ( '' !== $packages ? $packages . "\n\n" : '' )
		. 'Questions in the meantime? Reply to this email or contact ' . $sp['contact'] . ' at ' . $sp['to'] . ".\n"
		. 'The breakfast page: ' . atfa_bk_page_url() . "\n\n"
		. "Regards,\nThe ATFA team\nAU 1300 361 693 · NZ 0800 453 715\nhttps://www.atfa.com.au/";
	wp_mail( $test ? wp_get_current_user()->user_email : $m( 'email' ), ( $test ? '[Test] ' : '' ) . 'Sponsoring the ' . $info['title'], $confirm, array( 'Reply-To: ' . $sp['to'] ) );
}

// Admin list: business, contact, email, mobile, package, message, date.
add_filter( 'manage_' . ATFA_BK_SPONSOR . '_posts_columns', function ( $columns ) {
	return array(
		'cb'      => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'   => 'Business',
		'name'    => 'Contact',
		'email'   => 'Email',
		'phone'   => 'Mobile',
		'tier'    => 'Package',
		'message' => 'Message',
		'date'    => 'Received',
	);
} );

add_action( 'manage_' . ATFA_BK_SPONSOR . '_posts_custom_column', function ( $column, $post_id ) {
	if ( 'tier' === $column ) {
		echo esc_html( atfa_bk_tier_label( atfa_bk_tier_key( get_post_meta( $post_id, 'tier', true ) ) ) );
	} elseif ( in_array( $column, array( 'name', 'email', 'phone', 'message' ), true ) ) {
		echo esc_html( (string) get_post_meta( $post_id, $column, true ) );
	}
}, 10, 2 );

add_filter( 'views_edit-' . ATFA_BK_SPONSOR, function ( $views ) {
	$views['atfa_page'] = '<a href="' . esc_url( atfa_bk_page_url() . '#sponsor' ) . '">Open the sponsorship section</a>';
	return $views;
} );

/* ---------------------------------------------------------------------------
 * The staff bar on the page
 * ------------------------------------------------------------------------ */

/**
 * Staff only (never cached for visitors): whether the page is live, how many seats are reserved, and the links.
 */
function atfa_bk_staff_bar() {
	if ( ! atfa_bk_is_staff() ) {
		return '';
	}
	$info   = atfa_bk_info();
	$public = atfa_bk_is_public();
	$seats  = atfa_bk_rsvp_count();
	$id     = atfa_bk_page_id();
	$admins = 'manage_options' === (string) $info['preview'];
	$status = $public
		? 'This page is live. Visitors can see it and reserve a seat.'
		: ( $admins ? 'Administrators only for now. Everyone else, staff included, is sent to the awards page' : 'Staff only for now. Visitors are sent to the awards page' ) . ' until this page is published in wp-admin. Reservations and sponsorship enquiries you send from here are tests and only email you.';
	$notes  = array();
	if ( empty( $info['venue']['confirmed'] ) ) {
		$notes[] = 'venue and times show as "to be confirmed"';
	}
	$tiers = $info['sponsorship']['tiers'];
	$gold  = $info['sponsors']['gold'];
	$nsilv = count( $info['sponsors']['silver'] );
	if ( ! $gold && 0 === $nsilv ) {
		$notes[] = 'no sponsors confirmed yet';
	} else {
		$notes[] = 'sponsors: ' . ( $gold ? $gold['name'] . ' (Gold)' : 'no Gold' ) . ', ' . $nsilv . ' Silver';
	}
	if ( ! empty( $info['sponsorship']['open'] ) ) {
		$notes[] = 'packages on offer at ' . ( '' !== $tiers['gold']['price'] ? $tiers['gold']['price'] : 'price on enquiry' ) . ' Gold and ' . ( '' !== $tiers['silver']['price'] ? $tiers['silver']['price'] : 'price on enquiry' ) . ' Silver';
	} else {
		$notes[] = 'sponsorship closed';
	}
	$notes[]   = 'cost shows as "' . $info['price']['text'] . '"';
	$enquiries = atfa_bk_sponsor_count();
	return '<div class="bk-staff" role="region" aria-label="Staff tools">'
		. '<span class="bk-staff-tag">' . ( $admins && ! $public ? 'Admins only' : 'Staff only' ) . '</span>'
		. '<div class="bk-staff-text"><span class="bk-staff-status' . ( $public ? '' : ' is-warn' ) . '">' . esc_html( $status ) . '</span> '
		. '<span class="bk-staff-note">' . esc_html( $seats . ' ' . ( 1 === $seats ? 'seat' : 'seats' ) . ' reserved' . ( $info['places'] ? ' of ' . (int) $info['places'] : '' ) . ', ' . $enquiries . ' sponsorship ' . ( 1 === $enquiries ? 'enquiry' : 'enquiries' ) . '. ' . ucfirst( implode( ', ', $notes ) ) . ' (data/breakfast.php).' ) . '</span></div>'
		. '<div class="bk-staff-btns">'
		. '<a class="bk-sbtn" href="' . esc_url( admin_url( 'edit.php?post_type=' . ATFA_BK_RSVP ) ) . '">Reservations</a>'
		. '<a class="bk-sbtn is-ghost" href="' . esc_url( admin_url( 'edit.php?post_type=' . ATFA_BK_SPONSOR ) ) . '">Sponsor enquiries</a>'
		. ( $id ? '<a class="bk-sbtn is-ghost" href="' . esc_url( get_edit_post_link( $id ) ) . '">' . ( $public ? 'Edit page' : 'Publish page' ) . '</a>' : '' )
		. '</div></div>';
}
