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
		'date'            => '2027-05-22',
		'date_text'       => 'Saturday 22 May 2027',
		'day_text'        => 'Saturday morning, 22 May 2027',
		'time_text'       => '',
		'venue'           => array(),
		'price'           => array(),
		'places'          => 0,
		'host'            => 'Hosted by ATFA',
		'rsvp'            => array(),
		'sponsor'         => array(),
		'sponsor_contact' => array(),
		'photos'          => array(),
		'hero'            => array(),
	), $info );
	$info['venue']   = array_merge( array( 'name' => '', 'room' => '', 'area' => '', 'address' => '', 'map' => '', 'note' => '', 'confirmed' => false ), (array) $info['venue'] );
	$info['price']   = array_merge( array( 'text' => 'Complimentary', 'detail' => '' ), (array) $info['price'] );
	$info['rsvp']    = array_merge( array( 'open' => true, 'to' => 'cherie@atfa.com.au', 'cc' => '', 'contact' => 'Cherie', 'email' => 'cherie@atfa.com.au' ), (array) $info['rsvp'] );
	$info['sponsor'] = array_merge( array( 'name' => '', 'img' => '', 'url' => '' ), (array) $info['sponsor'] );
	$info['sponsor_contact'] = array_merge( array( 'name' => 'Phil', 'email' => 'phil@atfa.com.au' ), (array) $info['sponsor_contact'] );
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

function atfa_bk_is_staff() {
	return is_user_logged_in() && current_user_can( 'edit_pages' );
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
 * Whether this visitor can open the page: everyone once it is published, staff before that.
 */
function atfa_bk_visible() {
	$id = atfa_bk_page_id();
	return $id && ( 'publish' === get_post_status( $id ) || current_user_can( 'read_post', $id ) );
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
 * While the page is private, anyone who can't open it and goes to its address is sent to the awards page rather
 * than a "page not found". Temporary (302), so browsers forget it once the page is published.
 */
function atfa_bk_hidden_redirect() {
	global $wp;
	if ( is_admin() || ! is_404() || ! isset( $wp->request ) ) {
		return;
	}
	if ( trim( (string) $wp->request, '/' ) !== ATFA_BK_SLUG ) {
		return;
	}
	$id = atfa_bk_page_id();
	if ( ! $id || 'publish' === get_post_status( $id ) ) {
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

// The serif for the word Breakfast (Playfair Display), on this page only. The other two fonts come from the template.
add_action( 'wp_head', function () {
	if ( atfa_bk_is_page() ) {
		echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap">' . "\n";
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
	$status = $public
		? 'This page is live. Visitors can see it and reserve a seat.'
		: 'Staff only for now. Visitors are sent to the awards page until this page is published in wp-admin. Reservations you send from here are tests and only email you.';
	$notes  = array();
	if ( empty( $info['venue']['confirmed'] ) ) {
		$notes[] = 'venue and times show as "to be confirmed"';
	}
	if ( '' === $info['sponsor']['img'] ) {
		$notes[] = 'no breakfast sponsor yet';
	}
	$notes[] = 'cost shows as "' . $info['price']['text'] . '"';
	return '<div class="bk-staff" role="region" aria-label="Staff tools">'
		. '<span class="bk-staff-tag">Staff only</span>'
		. '<div class="bk-staff-text"><span class="bk-staff-status' . ( $public ? '' : ' is-warn' ) . '">' . esc_html( $status ) . '</span> '
		. '<span class="bk-staff-note">' . esc_html( $seats . ' ' . ( 1 === $seats ? 'seat' : 'seats' ) . ' reserved' . ( $info['places'] ? ' of ' . (int) $info['places'] : '' ) . '. ' . ucfirst( implode( ', ', $notes ) ) . ' (data/breakfast.php).' ) . '</span></div>'
		. '<div class="bk-staff-btns">'
		. '<a class="bk-sbtn" href="' . esc_url( admin_url( 'edit.php?post_type=' . ATFA_BK_RSVP ) ) . '">Reservations</a>'
		. ( $id ? '<a class="bk-sbtn is-ghost" href="' . esc_url( get_edit_post_link( $id ) ) . '">' . ( $public ? 'Edit page' : 'Publish page' ) . '</a>' : '' )
		. '</div></div>';
}
