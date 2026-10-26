<?php
/**
 * ATFA Expo & Conference 2027: Hall 5 interactive floor plan
 * ---------------------------------------------------------------------------
 * Version __VERSION__ · __BUILD_DATE__ · Prepared for ATFA (Nick Warren)
 *
 * WHAT THIS ADDS
 *   [atfa_expo27_page]               The ATFA Expo 2027 page at /expo-2027/: the dark awards-style
 *                                    page with the weekend bar, the live Hall 5 plan (full screen
 *                                    on phones), prices, the hall, booking steps and the dinner.
 *                                    The page uses the "ATFA Awards (blank canvas)" template from
 *                                    the awards plugin, and borrows its logo and dinner photo.
 *   [atfa_expo27_floorplan]          The original light floor plan (kept, not on any page now).
 *   /wp-json/atfa/v1/expo27/stands   Public, read-only stand data for the plan.
 *   /wp-content/uploads/atfa-expo27/stands-v3.json   Fast copy of the same data.
 *   Forwarding: once /expo-2027/ is published, the old floor plan page
 *   (/expo-2027-hall-5-interactive-floor-plan/) forwards to the plan on it, so links in the
 *   PDF, emails and the awards page keep working. While /expo-2027/ is private (staff only),
 *   visitors who go to it are sent to the old floor plan page instead, and menu links to it
 *   only show for staff.
 *
 * WHERE THE STAND DATA COMES FROM
 *   Google Sheet "2027 Exhibitor List (import into Google Sheets)" in nick@atfa.com.au's
 *   Drive. Staff edit the Master tab (status, company, contacts, Paid). The Website tab
 *   copies only Stand, Status, Company, Website and Bio, and only fills in Company,
 *   Website and Bio for stands marked Sold, so On Hold names never go public. Only the
 *   Website tab is published to the web (as CSV). Contact details and Paid stay private.
 *
 *   This snippet fetches that CSV on the server, keeps a copy for two minutes, and
 *   remembers the last good copy so the plan keeps working if Google has a hiccup.
 *   Changes in the sheet show on the site within about 5 to 7 minutes (Google
 *   republishes every few minutes, plus the two-minute cache here).
 *
 * TO CHANGE SETTINGS
 *   Edit atfa_expo27_settings() just below: enquiry email, enquiry form page, PDF link,
 *   the published CSV link, the staff sheet link, or the cache time.
 *
 * STAFF BAR
 *   Logged-in staff (Editors and Administrators) see a bar above the plan with buttons to
 *   open the stand sheet and to refresh the plan from it, and when the sheet was last read.
 *   Visitors never get the bar, and the sheet link isn't printed anywhere else.
 *   ("Refresh from the sheet" is the same as adding ?refresh=1 to the page address.)
 *
 * SHARING THE PAGE WHILE IT'S PRIVATE
 *   On the Expo page the staff bar can also make a link that lets someone without a login
 *   (a potential sponsor, say) see the page for 24 hours, 3 days or 7 days:
 *   /expo-2027/?xp-share=<random key>. They see exactly what visitors will see once the page
 *   is public: no staff bar, no sheet link, no menu link. A link stops working when its time
 *   is up or when staff stop it, and then goes to the old floor plan page like any visitor.
 *   Shared views are never cached and ask search engines not to index them. The links are
 *   kept in the option atfa_expo27_shares; staff manage them with /wp-json/atfa/v1/expo27/shares.
 *
 * WHERE THIS LIVES
 *   In the ATFA Flooring Awards 2027 plugin (includes/expo-2027.php), built from expo/ in the
 *   plugin's GitHub repo (NickATFA/atfa-awards-2027). It used to be a Code Snippet; if that
 *   snippet is still switched on, whichever copy loads first runs and the other does nothing.
 *
 * TO CHANGE THE LAYOUT, PRICES OR LOOK
 *   Don't edit the long JavaScript/CSS blocks by hand. Edit expo/src, run expo/build.py, then
 *   release the plugin as usual (./build.sh x.y.z "What changed", commit, push, Update now).
 *
 * Apart from that forwarding and the Expo page's menu links, no other page, snippet, form or
 * setting is changed by this code.
 */

if ( ! function_exists( 'atfa_expo27_settings' ) ) {

	// Where the cached sheet data is kept. The "v3" keys started with the Concept 5 layout
	// (stand numbers like S01), so data cached by the older plan is never reused.
	define( 'ATFA_EXPO27_CACHE_KEY', 'atfa_expo27_stands_v3' );
	define( 'ATFA_EXPO27_LAST_GOOD_KEY', 'atfa_expo27_stands_last_good_v3' );
	// Share links for the private Expo page: the address argument and where they're kept.
	define( 'ATFA_EXPO27_SHARE_ARG', 'xp-share' );
	define( 'ATFA_EXPO27_SHARES_OPTION', 'atfa_expo27_shares' );

	/**
	 * Settings. Safe to edit.
	 */
	function atfa_expo27_settings() {
		return array(
			// "Publish to web" CSV link for the Website tab of the 2027 Exhibitor List sheet.
			'csv_url'       => '__CSV_URL__',
			// Where stand enquiries go.
			'enquiry_email' => 'cherie@atfa.com.au',
			'enquiry_name'  => 'Cherie',
			// Page with the stand enquiry form. The stand number is added as ?stand=S01.
			// Leave empty to use email instead.
			'enquiry_url'   => '__ENQUIRY_URL__',
			// Downloadable PDF of the floor plan and rate card. Leave empty to hide the button.
			// "plugin:..." is the copy that ships in the awards plugin (tools/make_pdf.py).
			'pdf_url'       => atfa_expo27_asset_url( '__PDF_URL__' ),
			// Staff only: the sheet staff edit (Master tab). Shown as a button in the staff bar.
			// Leave empty to hide the button.
			'sheet_url'     => '__SHEET_URL__',
			// How long (seconds) to keep a copy of the sheet before asking Google again.
			'cache_seconds' => 120,
			'currency'      => 'AUD',
			'price_note'    => 'excl. GST',
			// The Expo page's address, and the old floor plan page that forwards to it.
			'page_slug'     => '__PAGE_SLUG__',
			'old_page_id'   => __OLD_PAGE_ID__,
		);
	}

	/**
	 * Photos from the 2025 Expo in Melbourne (Fancast Productions), in the media library.
	 * id = attachment ID. Safe to edit: swap an ID to change a photo.
	 */
	function atfa_expo27_page_photos() {
		return array(
			'procoat' => array( 'id' => __PHOTO_PROCOAT__, 'name' => 'Pro-Coat', 'note' => 'ATFA Expo 2025', 'alt' => 'The Pro-Coat stand at ATFA Expo 2025', 'pos' => '45% 50%' ),
			'cfs'     => array( 'id' => __PHOTO_CFS__, 'name' => 'CFS', 'note' => 'ATFA Expo 2025', 'alt' => 'Visitors at the CFS stand at ATFA Expo 2025', 'pos' => '50% 45%' ),
			'bona'    => array( 'id' => __PHOTO_BONA__, 'name' => 'Bona', 'note' => 'ATFA Expo 2025', 'alt' => 'Visitors at the Bona stand at ATFA Expo 2025', 'pos' => '55% 50%' ),
			'lagler'  => array( 'id' => __PHOTO_LAGLER__, 'name' => 'Lägler Australia', 'note' => 'ATFA Expo 2025', 'alt' => 'The Lägler Australia stand at ATFA Expo 2025', 'pos' => '50% 50%' ),
			'norton'  => array( 'id' => __PHOTO_NORTON__, 'name' => 'Norton', 'note' => 'ATFA Expo 2025', 'alt' => 'The Norton stand at ATFA Expo 2025', 'pos' => '50% 50%' ),
			'podcast' => array( 'id' => __PHOTO_PODCAST__, 'name' => 'Clever Choice', 'note' => 'The Underlay podcast, Expo 2025', 'alt' => 'The Underlay podcast corner at ATFA Expo 2025', 'pos' => '50% 50%' ),
			'woody'   => array( 'id' => __PHOTO_WOODY__, 'name' => 'Woody', 'note' => 'ATFA\'s mascot, Expo 2025', 'alt' => 'Woody, the ATFA mascot, at ATFA Expo 2025', 'pos' => '50% 28%' ),
		);
	}

	add_shortcode( 'atfa_expo27_floorplan', 'atfa_expo27_shortcode' );
	add_shortcode( 'atfa_expo27_page', 'atfa_expo27_page_shortcode' );
	add_action( 'template_redirect', 'atfa_expo27_forward_old_page' );
	add_action( 'template_redirect', 'atfa_expo27_hidden_page_redirect', 5 );
	add_filter( 'wp_nav_menu_objects', 'atfa_expo27_menu_items' );
	add_filter( 'wp_get_nav_menu_items', 'atfa_expo27_menu_items_list', 10, 3 );
	add_action( 'pre_get_posts', 'atfa_expo27_share_query' );
	add_action( 'template_redirect', 'atfa_expo27_share_headers', 1 );
	add_filter( 'wp_robots', 'atfa_expo27_share_robots' );

	add_action( 'rest_api_init', function () {
		register_rest_route( 'atfa/v1', '/expo27/stands', array(
			'methods'             => 'GET',
			'callback'            => 'atfa_expo27_rest_stands',
			'permission_callback' => '__return_true',
		) );
		register_rest_route( 'atfa/v1', '/expo27/shares', array(
			array(
				'methods'             => 'GET',
				'callback'            => 'atfa_expo27_rest_shares',
				'permission_callback' => 'atfa_expo27_is_staff',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'atfa_expo27_rest_share_make',
				'permission_callback' => 'atfa_expo27_is_staff',
			),
		) );
		register_rest_route( 'atfa/v1', '/expo27/shares/(?P<key>[A-Za-z0-9]{24,64})', array(
			'methods'             => 'DELETE',
			'callback'            => 'atfa_expo27_rest_share_stop',
			'permission_callback' => 'atfa_expo27_is_staff',
		) );
	} );

	/**
	 * "plugin:assets/expo/file.pdf" is a file that ships in the awards plugin (built from expo/,
	 * see tools/make_pdf.py), so it updates with the plugin; anything else is an ordinary
	 * address. The ?v= changes whenever the plugin brings a new copy, so nobody gets an old
	 * one from their browser's cache.
	 */
	function atfa_expo27_asset_url( $value ) {
		$value = (string) $value;
		if ( 0 !== strpos( $value, 'plugin:' ) ) {
			return $value;
		}
		$path = ltrim( substr( $value, 7 ), '/' );
		if ( ! defined( 'ATFA_AWARDS_URL' ) || ! defined( 'ATFA_AWARDS_DIR' ) || ! is_file( ATFA_AWARDS_DIR . $path ) ) {
			return '';
		}
		return ATFA_AWARDS_URL . $path . '?v=' . (int) filemtime( ATFA_AWARDS_DIR . $path );
	}

	/**
	 * Staff = logged-in users who can edit pages (Editors and Administrators).
	 */
	function atfa_expo27_is_staff() {
		return is_user_logged_in() && current_user_can( 'edit_pages' );
	}

	/**
	 * REST: /wp-json/atfa/v1/expo27/stands
	 * ?refresh=1 skips the two-minute cache for logged-in staff. WordPress only treats a
	 * REST request as logged in when it carries a nonce, so from the address bar use the
	 * floor plan page instead (the staff bar's "Refresh from the sheet" button).
	 */
	function atfa_expo27_rest_stands( $request ) {
		$force = $request->get_param( 'refresh' ) && atfa_expo27_is_staff();
		return rest_ensure_response( atfa_expo27_get_data( $force ) );
	}

	/**
	 * Returns array( ok, source, updated, stands[] ).
	 * source: live (fresh from the sheet), stale (Google failed, last good copy) or none.
	 */
	function atfa_expo27_get_data( $force = false ) {
		$settings  = atfa_expo27_settings();
		$cache_key = ATFA_EXPO27_CACHE_KEY;
		$last_key  = ATFA_EXPO27_LAST_GOOD_KEY;

		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$rows = atfa_expo27_fetch_rows( $settings['csv_url'] );
		if ( is_array( $rows ) ) {
			$data = array(
				'ok'      => true,
				'source'  => 'live',
				'updated' => gmdate( 'c' ),
				'stands'  => $rows,
			);
			set_transient( $cache_key, $data, max( 30, (int) $settings['cache_seconds'] ) );
			update_option( $last_key, $data, false );
			atfa_expo27_write_static( $data );
			return $data;
		}

		$last = get_option( $last_key );
		if ( is_array( $last ) && ! empty( $last['stands'] ) ) {
			$last['source'] = 'stale';
			set_transient( $cache_key, $last, 60 );
			atfa_expo27_write_static( $last );
			return $last;
		}

		$none = array(
			'ok'      => false,
			'source'  => 'none',
			'updated' => null,
			'stands'  => array(),
		);
		set_transient( $cache_key, $none, 60 );
		return $none;
	}

	/**
	 * A plain copy of the latest data at /wp-content/uploads/atfa-expo27/stands-v3.json
	 * ("v3" = Concept 5 stand numbers; the older stands.json is never read by this version).
	 * WordPress takes about 3 seconds to answer any request on this site; the web server
	 * hands out this file in a fraction of a second, so the plan can show recent data
	 * straight away and then confirm it with the live endpoint.
	 */
	function atfa_expo27_static_path() {
		$uploads = wp_upload_dir( null, false );
		if ( ! empty( $uploads['error'] ) ) {
			return null;
		}
		return array(
			'path' => trailingslashit( $uploads['basedir'] ) . 'atfa-expo27/stands-v3.json',
			'url'  => trailingslashit( $uploads['baseurl'] ) . 'atfa-expo27/stands-v3.json',
		);
	}

	function atfa_expo27_write_static( $data ) {
		$where = atfa_expo27_static_path();
		if ( ! $where ) {
			return;
		}
		$dir = dirname( $where['path'] );
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return;
		}
		$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( ! $json ) {
			return;
		}
		// Write a temporary file and swap it in, so a visitor never reads a half-written copy.
		$tmp = $where['path'] . '.' . getmypid() . '-' . uniqid() . '.tmp';
		if ( false !== @file_put_contents( $tmp, $json ) && @rename( $tmp, $where['path'] ) ) { // phpcs:ignore -- best effort only
			return;
		}
		@unlink( $tmp ); // phpcs:ignore
		@file_put_contents( $where['path'], $json, LOCK_EX ); // phpcs:ignore
	}

	/**
	 * Data already on hand for the first paint. Never calls Google, so a slow sheet
	 * can't slow down the page itself.
	 */
	function atfa_expo27_snapshot() {
		$cached = get_transient( ATFA_EXPO27_CACHE_KEY );
		if ( is_array( $cached ) && ! empty( $cached['stands'] ) ) {
			return $cached;
		}
		$last = get_option( ATFA_EXPO27_LAST_GOOD_KEY );
		if ( is_array( $last ) && ! empty( $last['stands'] ) ) {
			return $last;
		}
		return null;
	}

	/**
	 * Fetch and parse the published CSV. Returns an array of rows or a WP_Error.
	 */
	function atfa_expo27_fetch_rows( $url ) {
		if ( empty( $url ) ) {
			return new WP_Error( 'atfa_expo27', 'No sheet link set.' );
		}
		$response = wp_remote_get( $url, array( 'timeout' => 8, 'redirection' => 5 ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'atfa_expo27', 'Sheet returned HTTP ' . $code );
		}
		if ( '' === trim( $body ) || preg_match( '/^\s*</', $body ) ) {
			return new WP_Error( 'atfa_expo27', 'Sheet did not return CSV.' );
		}
		$rows = atfa_expo27_parse_csv( $body );
		if ( empty( $rows ) ) {
			return new WP_Error( 'atfa_expo27', 'No stand rows found.' );
		}
		return $rows;
	}

	/**
	 * Parse the Website-tab CSV. Handles commas, quotes and line breaks inside cells.
	 * Company, website and bio are only kept for Sold stands.
	 */
	function atfa_expo27_parse_csv( $csv ) {
		$csv = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $csv );
		$fh  = fopen( 'php://temp', 'r+' );
		fwrite( $fh, $csv );
		rewind( $fh );

		$map = null;
		$out = array();
		while ( false !== ( $r = fgetcsv( $fh, 0, ',', '"', '' ) ) ) {
			if ( ! is_array( $r ) || array( null ) === $r ) {
				continue;
			}
			if ( null === $map ) {
				$map = atfa_expo27_header_map( $r );
				if ( $map ) {
					continue;
				}
				$map = array( 'stand' => 0, 'status' => 1, 'name' => 2, 'url' => 3, 'bio' => 4 );
			}
			$id = atfa_expo27_stand_id( atfa_expo27_cell( $r, $map['stand'] ) );
			if ( '' === $id ) {
				continue;
			}
			$status = atfa_expo27_status( atfa_expo27_cell( $r, $map['status'] ) );
			$row    = array( 'id' => $id, 'status' => $status );
			if ( 'sold' === $status ) {
				$row['name'] = atfa_expo27_text( atfa_expo27_cell( $r, $map['name'] ), 120, false );
				$row['url']  = atfa_expo27_url( atfa_expo27_cell( $r, $map['url'] ) );
				$row['bio']  = atfa_expo27_text( atfa_expo27_cell( $r, $map['bio'] ), 1200, true );
			}
			// "S1", "s01" and "S 01" are the same stand; the last row for a stand wins.
			$out[ preg_replace( '/^([A-Z]*)0*(\d)/', '$1$2', $id ) ] = $row;
		}
		fclose( $fh );
		return array_values( $out );
	}

	function atfa_expo27_header_map( $cells ) {
		$map = array( 'stand' => -1, 'status' => -1, 'name' => -1, 'url' => -1, 'bio' => -1 );
		foreach ( $cells as $i => $raw ) {
			$h = strtolower( trim( (string) $raw ) );
			if ( '' === $h ) {
				continue;
			}
			if ( $map['stand'] < 0 && false !== strpos( $h, 'stand' ) ) {
				$map['stand'] = $i;
			} elseif ( $map['url'] < 0 && ( false !== strpos( $h, 'website' ) || false !== strpos( $h, 'url' ) ) ) {
				$map['url'] = $i;
			} elseif ( $map['bio'] < 0 && ( false !== strpos( $h, 'bio' ) || false !== strpos( $h, 'description' ) ) ) {
				$map['bio'] = $i;
			} elseif ( $map['status'] < 0 && ( false !== strpos( $h, 'sold' ) || false !== strpos( $h, 'hold' ) || false !== strpos( $h, 'sale' ) || false !== strpos( $h, 'status' ) ) ) {
				$map['status'] = $i;
			} elseif ( $map['name'] < 0 && ( false !== strpos( $h, 'company' ) || false !== strpos( $h, 'exhibitor' ) ) ) {
				$map['name'] = $i;
			}
		}
		return ( $map['stand'] > -1 && $map['status'] > -1 ) ? $map : null;
	}

	function atfa_expo27_cell( $row, $i ) {
		return ( $i > -1 && isset( $row[ $i ] ) ) ? (string) $row[ $i ] : '';
	}

	/**
	 * Stand numbers look like S01, B12 or G03 (old plans used 305). Returns the number
	 * in capitals without spaces, or '' if the cell isn't a stand number at all.
	 */
	function atfa_expo27_stand_id( $value ) {
		$v = strtoupper( atfa_expo27_squash( $value, '' ) );
		$v = preg_replace( '/^STAND/', '', $v );
		return preg_match( '/^[A-Z]{0,3}\d{1,4}$/', $v ) ? $v : '';
	}

	/**
	 * Collapses every kind of space a spreadsheet can contain (normal, non-breaking,
	 * wide, zero-width, byte-order mark, tabs, line breaks) into $with, and trims.
	 * Matches what the browser treats as space, so the site and the plan agree.
	 */
	function atfa_expo27_squash( $value, $with = ' ' ) {
		$v   = (string) $value;
		$out = preg_replace( '/[\s\p{Z}\x{200B}\x{FEFF}]+/u', $with, $v );
		if ( null === $out ) {
			// Not valid UTF-8: fall back to plain ASCII spaces.
			$out = preg_replace( '/\s+/', $with, $v );
		}
		return trim( (string) $out );
	}

	function atfa_expo27_status( $value ) {
		$v = strtolower( atfa_expo27_squash( $value ) );
		if ( 'sold' === $v ) {
			return 'sold';
		}
		if ( in_array( $v, array( 'on hold', 'hold', 'held', 'reserved' ), true ) ) {
			return 'hold';
		}
		return 'available';
	}

	function atfa_expo27_text( $value, $max, $multiline ) {
		$v = wp_check_invalid_utf8( (string) $value, true );
		$v = str_replace( array( "\r\n", "\r", "\t" ), array( "\n", "\n", ' ' ), $v );
		$v = preg_replace( '/[\x00-\x08\x0B-\x1F\x7F]/', '', $v );
		if ( $multiline ) {
			$v = preg_replace( '/[ \t]+/', ' ', $v );
			$v = preg_replace( '/ *\n */', "\n", $v );
			$v = preg_replace( '/\n{3,}/', "\n\n", $v );
		} else {
			$v = preg_replace( '/\s+/u', ' ', $v );
		}
		$v = trim( (string) $v );
		if ( function_exists( 'mb_strlen' ) && mb_strlen( $v, 'UTF-8' ) > $max ) {
			$v = rtrim( mb_substr( $v, 0, $max - 1, 'UTF-8' ) ) . '…';
		}
		return $v;
	}

	/**
	 * Only http(s) links. "www.example.com.au" becomes "https://www.example.com.au".
	 */
	function atfa_expo27_url( $value ) {
		$u = preg_replace( '/\s+/', '', (string) $value );
		if ( '' === $u ) {
			return '';
		}
		if ( preg_match( '#^[a-z][a-z0-9+.\-]*:#i', $u ) ) {
			if ( ! preg_match( '#^https?://#i', $u ) ) {
				return '';
			}
		} elseif ( preg_match( '#^[a-z0-9\-]+(\.[a-z0-9\-]+)+([/?\#].*)?$#i', $u ) ) {
			$u = 'https://' . $u;
		} else {
			return '';
		}
		$clean = esc_url_raw( $u, array( 'http', 'https' ) );
		return $clean ? $clean : '';
	}

	/**
	 * The shortcode. Prints the plan once per page.
	 */
	function atfa_expo27_shortcode( $atts = array() ) {
		static $done = false;
		if ( $done ) {
			return '';
		}
		$done     = true;
		$settings = atfa_expo27_settings();
		$version  = '__VERSION__';

		// Staff: "Refresh from the sheet" in the staff bar (the page with ?refresh=1 on the end)
		// pulls the latest from the sheet straight away, skipping the 2-minute cache.
		$refreshed = null;
		if ( isset( $_GET['refresh'] ) && atfa_expo27_is_staff() ) { // phpcs:ignore WordPress.Security.NonceVerification
			$refreshed = atfa_expo27_get_data( true );
		}

		$config = array(
			'version'      => $version,
			'restUrl'      => rest_url( 'atfa/v1/expo27/stands' ),
			'staticUrl'    => ( $static = atfa_expo27_static_path() ) ? $static['url'] : '',
			'csvUrl'       => $settings['csv_url'],
			'enquiryEmail' => $settings['enquiry_email'],
			'enquiryName'  => $settings['enquiry_name'],
			'enquiryUrl'   => $settings['enquiry_url'],
			'pdfUrl'       => $settings['pdf_url'],
			'currency'     => $settings['currency'],
			'priceNote'    => $settings['price_note'],
		);
		$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

		$pdf = '';
		if ( ! empty( $settings['pdf_url'] ) ) {
			$pdf = '<a class="afpx-btn is-ghost is-small" href="' . esc_url( $settings['pdf_url'] ) . '" target="_blank" rel="noopener">Download the PDF floor plan</a>';
		}
		$html = strtr( atfa_expo27_markup(), array(
			'{{VERSION}}' => esc_attr( $version ),
			'{{EMAIL}}'   => esc_html( $settings['enquiry_email'] ),
			'{{MAILTO}}'  => esc_attr( 'mailto:' . $settings['enquiry_email'] ),
			'{{PDF}}'     => $pdf,
			'{{STAFF}}'   => atfa_expo27_staff_bar( $settings, $refreshed ),
		) );

		return '<style id="atfa-expo27-css">' . atfa_expo27_css() . '</style>'
			. $html
			. '<script type="application/json" id="atfa-expo27-config">' . wp_json_encode( $config, $flags ) . '</script>'
			. '<script type="application/json" id="atfa-expo27-snapshot">' . wp_json_encode( atfa_expo27_snapshot(), $flags ) . '</script>'
			. '<script id="atfa-expo27-js" data-no-optimize="1" data-cfasync="false" nowprocket>' . atfa_expo27_js() . '</script>';
	}

	/**
	 * The staff bar above the plan: open the sheet, refresh the plan from it, and when it
	 * was last read. Empty for visitors, so neither the bar nor the sheet link ever reaches
	 * the public page (or WP Rocket's cached copy of it).
	 *
	 * $refreshed: what a "Refresh from the sheet" just fetched, or null.
	 */
	function atfa_expo27_staff_bar( $settings, $refreshed = null, $extra = '' ) {
		if ( ! atfa_expo27_is_staff() ) {
			return '';
		}
		$data = is_array( $refreshed ) ? $refreshed : atfa_expo27_snapshot();
		$when = ( is_array( $data ) && ! empty( $data['updated'] ) ) ? atfa_expo27_when( $data['updated'] ) : '';
		$warn = true;
		if ( is_array( $refreshed ) && 'live' === $refreshed['source'] ) {
			$warn   = false;
			$status = 'Read from the sheet just now.';
		} elseif ( is_array( $data ) && 'stale' === $data['source'] && '' !== $when ) {
			$status = ( $refreshed ? 'Couldn\'t reach the sheet just now.' : 'Couldn\'t reach the sheet at the last check.' )
				. ' The plan is showing the copy read ' . $when . '.';
		} elseif ( is_array( $data ) && ! empty( $data['stands'] ) && '' !== $when ) {
			$warn   = false;
			$status = 'Last read from the sheet ' . $when . '.';
		} elseif ( $refreshed ) {
			$status = 'Couldn\'t reach the sheet just now, and there\'s no earlier copy. Check the sheet is still published to the web.';
		} else {
			$status = 'The plan hasn\'t read the sheet yet. Try "Refresh from the sheet".';
		}

		$buttons = '';
		if ( ! empty( $settings['sheet_url'] ) ) {
			$buttons .= '<a class="afpx-btn is-small" href="' . esc_url( $settings['sheet_url'] ) . '" target="_blank" rel="noopener">'
				. 'Open the stand sheet<span class="afpx-sr"> (opens in a new tab)</span></a>';
		}
		// A bare query string keeps the page address and replaces anything after the "?".
		$buttons .= '<a class="afpx-btn is-ghost is-small" href="?refresh=1" title="Reads the sheet again now, instead of waiting up to 2 minutes">'
			. 'Refresh from the sheet</a>';

		return '<div class="afpx-staff" role="region" aria-label="Staff tools">'
			. '<span class="afpx-staff-tag">Staff only</span>'
			. '<div class="afpx-staff-text"><span class="afpx-staff-status' . ( $warn ? ' is-warn' : '' ) . '">' . esc_html( $status ) . '</span> '
			. '<span class="afpx-staff-note">Sheet edits can take up to 5 minutes to come through from Google. Visitors don\'t see this bar.</span></div>'
			. '<div class="afpx-staff-btns">' . $buttons . '</div>'
			. $extra
			. '</div>';
	}

	/**
	 * "at 9:52 am" today, otherwise "on 15 Oct at 9:52 am", in the site's time zone.
	 */
	function atfa_expo27_when( $iso ) {
		$ts = strtotime( (string) $iso );
		if ( ! $ts ) {
			return '';
		}
		if ( ! function_exists( 'wp_date' ) ) {
			return 'at ' . gmdate( 'g:i a', $ts ) . ' UTC';
		}
		$time = wp_date( 'g:i a', $ts );
		return wp_date( 'Y-m-d', $ts ) === wp_date( 'Y-m-d' ) ? 'at ' . $time : 'on ' . wp_date( 'j M', $ts ) . ' at ' . $time;
	}

	/**
	 * Once the Expo page is published, the old floor plan page forwards to the plan on it.
	 * Not while someone is editing or previewing the old page.
	 */
	function atfa_expo27_forward_old_page() {
		$settings = atfa_expo27_settings();
		$old      = (int) $settings['old_page_id'];
		if ( ! $old || is_admin() || ! is_page( $old ) || is_preview() ) {
			return;
		}
		if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['preview_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$page = get_page_by_path( $settings['page_slug'] );
		if ( ! $page || 'publish' !== get_post_status( $page ) || (int) $page->ID === $old ) {
			return;
		}
		// Temporary (302), so browsers don't remember it if the page is ever taken down again.
		nocache_headers();
		wp_safe_redirect( get_permalink( $page ) . '#floor-plan', 302 );
		exit;
	}

	/**
	 * While the Expo page is private (staff only) or a draft, anyone who can't open it is sent
	 * to the old floor plan page, not to a "page not found" or a WordPress guess. Also
	 * temporary (302); the ?from= on the end stops any browser that remembered the old page's
	 * forwarding from going round in a circle.
	 */
	function atfa_expo27_hidden_page_redirect() {
		global $wp;
		if ( is_admin() || ! is_404() || ! isset( $wp->request ) ) {
			return;
		}
		$settings = atfa_expo27_settings();
		if ( trim( (string) $wp->request, '/' ) !== $settings['page_slug'] ) {
			return;
		}
		$page = get_page_by_path( $settings['page_slug'] );
		$old  = (int) $settings['old_page_id'];
		if ( ! $page || ! $old || 'publish' === get_post_status( $page ) || 'publish' !== get_post_status( $old ) ) {
			return;
		}
		nocache_headers();
		wp_safe_redirect( add_query_arg( 'from', 'expo-2027', get_permalink( $old ) ), 302 );
		exit;
	}

	/**
	 * Menu links to the Expo page only show for people who can open it while it's private.
	 * (WordPress would otherwise show a link to a private page to everyone.)
	 */
	// Some menu builders (the site header uses JetMenu) read the items directly, not through
	// wp_nav_menu(), so the same check runs on the item list too.
	function atfa_expo27_menu_items_list( $items, $menu = null, $args = array() ) {
		$out = atfa_expo27_menu_items( $items );
		return is_array( $out ) ? array_values( $out ) : $out;
	}

	function atfa_expo27_menu_items( $items ) {
		if ( is_admin() || ! is_array( $items ) ) {
			return $items;
		}
		static $page = false; // looked up once per page view; menus are read several times
		if ( false === $page ) {
			$settings = atfa_expo27_settings();
			$page     = get_page_by_path( $settings['page_slug'] );
		}
		if ( ! $page || 'publish' === get_post_status( $page ) || current_user_can( 'read_post', $page->ID ) ) {
			return $items;
		}
		foreach ( $items as $key => $item ) {
			if ( isset( $item->type, $item->object_id ) && 'post_type' === $item->type && (int) $item->object_id === (int) $page->ID ) {
				unset( $items[ $key ] );
			}
		}
		return $items;
	}

	/**
	 * Share links that haven't run out yet (key => array( expires, created, by, note )).
	 * Links that have run out are tidied away here.
	 */
	function atfa_expo27_shares() {
		$all  = get_option( ATFA_EXPO27_SHARES_OPTION, array() );
		$all  = is_array( $all ) ? $all : array();
		$live = array();
		foreach ( $all as $key => $share ) {
			if ( is_array( $share ) && preg_match( '/^[A-Za-z0-9]{24,64}$/', (string) $key ) && (int) ( $share['expires'] ?? 0 ) > time() ) {
				$live[ (string) $key ] = $share;
			}
		}
		if ( count( $live ) !== count( $all ) ) {
			update_option( ATFA_EXPO27_SHARES_OPTION, $live, false );
		}
		return $live;
	}

	/**
	 * The share key on this request, if it's one that's still live; otherwise ''.
	 */
	function atfa_expo27_share_key() {
		if ( ! isset( $_GET[ ATFA_EXPO27_SHARE_ARG ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return '';
		}
		$key = (string) wp_unslash( $_GET[ ATFA_EXPO27_SHARE_ARG ] ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! preg_match( '/^[A-Za-z0-9]{24,64}$/', $key ) ) {
			return '';
		}
		foreach ( array_keys( atfa_expo27_shares() ) as $live ) {
			if ( hash_equals( (string) $live, $key ) ) {
				return $key;
			}
		}
		return '';
	}

	/**
	 * With a live share key, the Expo page's own address shows the page even while it's private.
	 * Only the main request for that page; nothing else on the site changes.
	 */
	function atfa_expo27_share_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! isset( $_GET[ ATFA_EXPO27_SHARE_ARG ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$settings = atfa_expo27_settings();
		if ( trim( (string) $query->get( 'pagename' ), '/' ) !== $settings['page_slug'] || '' === atfa_expo27_share_key() ) {
			return;
		}
		$query->set( 'post_status', array( 'publish', 'private' ) );
		$GLOBALS['atfa_expo27_sharing'] = true;
	}

	function atfa_expo27_sharing() {
		return ! empty( $GLOBALS['atfa_expo27_sharing'] ) && is_page();
	}

	/**
	 * A shared view is never kept by WP Rocket or the browser, and search engines are asked
	 * not to index it.
	 */
	function atfa_expo27_share_headers() {
		if ( ! atfa_expo27_sharing() ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}

	function atfa_expo27_share_robots( $robots ) {
		if ( atfa_expo27_sharing() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}

	function atfa_expo27_share_url( $key ) {
		$settings = atfa_expo27_settings();
		$page     = get_page_by_path( $settings['page_slug'] );
		$base     = $page ? get_permalink( $page ) : home_url( '/' . $settings['page_slug'] . '/' );
		return add_query_arg( ATFA_EXPO27_SHARE_ARG, $key, $base );
	}

	function atfa_expo27_share_public( $key, $share ) {
		return array(
			'key'     => $key,
			'url'     => atfa_expo27_share_url( $key ),
			'expires' => (int) $share['expires'],
			'until'   => function_exists( 'wp_date' ) ? wp_date( 'D j M \a\t g:i a', (int) $share['expires'] ) : gmdate( 'D j M H:i', (int) $share['expires'] ) . ' UTC',
			'note'    => (string) ( $share['note'] ?? '' ),
		);
	}

	function atfa_expo27_rest_shares() {
		$out = array();
		foreach ( atfa_expo27_shares() as $key => $share ) {
			$out[] = atfa_expo27_share_public( $key, $share );
		}
		usort( $out, function ( $a, $b ) { return $b['expires'] - $a['expires']; } );
		return rest_ensure_response( array( 'shares' => $out ) );
	}

	function atfa_expo27_rest_share_make( $request ) {
		$hours = (int) $request->get_param( 'hours' );
		if ( ! in_array( $hours, array( 24, 72, 168 ), true ) ) {
			$hours = 24;
		}
		$note   = sanitize_text_field( (string) $request->get_param( 'note' ) );
		$note   = function_exists( 'mb_substr' ) ? mb_substr( $note, 0, 80 ) : substr( $note, 0, 80 );
		$shares = atfa_expo27_shares();
		if ( count( $shares ) >= 20 ) {
			return new WP_Error( 'atfa_expo27_too_many', 'There are already 20 live links. Stop one before making another.', array( 'status' => 400 ) );
		}
		$key            = wp_generate_password( 32, false, false );
		$share          = array( 'expires' => time() + $hours * HOUR_IN_SECONDS, 'created' => time(), 'by' => get_current_user_id(), 'note' => $note );
		$shares[ $key ] = $share;
		update_option( ATFA_EXPO27_SHARES_OPTION, $shares, false );
		return rest_ensure_response( atfa_expo27_share_public( $key, $share ) );
	}

	function atfa_expo27_rest_share_stop( $request ) {
		$key    = (string) $request->get_param( 'key' );
		$shares = atfa_expo27_shares();
		unset( $shares[ $key ] );
		update_option( ATFA_EXPO27_SHARES_OPTION, $shares, false );
		return rest_ensure_response( array( 'stopped' => true ) );
	}

	/**
	 * The share tools in the staff bar on the Expo page (staff only, so never cached). The page
	 * script wires them up; the links come from /wp-json/atfa/v1/expo27/shares.
	 */
	function atfa_expo27_share_tools() {
		if ( ! atfa_expo27_is_staff() ) {
			return '';
		}
		return '<details class="xp-share" data-role="share" data-rest="' . esc_url( rest_url( 'atfa/v1/expo27/shares' ) ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'wp_rest' ) ) . '">'
			. '<summary>Share this page with someone who doesn\'t have a login</summary>'
			. '<p class="xp-share-note">They\'ll see the page as visitors will once it\'s public, until the link runs out. Stop a link at any time.</p>'
			. '<div class="xp-share-form">'
			. '<label><span>For</span><input type="text" data-role="sharenote" maxlength="80" placeholder="Who it\'s for (optional)"></label>'
			. '<label><span>For how long</span><select data-role="sharehours"><option value="24">24 hours</option><option value="72">3 days</option><option value="168">7 days</option></select></label>'
			. '<button type="button" class="afpx-btn is-small" data-role="sharemake">Make a link</button>'
			. '</div>'
			. '<p class="xp-share-msg" data-role="sharemsg" aria-live="polite"></p>'
			. '<ul class="xp-share-list" data-role="sharelist"></ul>'
			. '</details>';
	}

	/**
	 * An image from the awards plugin (logo, wordmark, dinner photo), or '' if the plugin
	 * or the file isn't there.
	 */
	function atfa_expo27_awards_img( $file ) {
		if ( ! defined( 'ATFA_AWARDS_URL' ) || ! defined( 'ATFA_AWARDS_DIR' ) ) {
			return '';
		}
		if ( ! file_exists( ATFA_AWARDS_DIR . 'assets/img/' . $file ) ) {
			return '';
		}
		return ATFA_AWARDS_URL . 'assets/img/' . $file . ( defined( 'ATFA_AWARDS_VERSION' ) ? '?v=' . ATFA_AWARDS_VERSION : '' );
	}

	/**
	 * The Principal Partner from the awards plugin (data/partner.php, shared with the awards pages), or null when
	 * there isn't one, the awards plugin or the logo isn't there, or the partner is set to staff only and this
	 * visitor isn't staff. Share links show what visitors see, so they follow the same rule.
	 */
	function atfa_expo27_partner() {
		if ( ! defined( 'ATFA_AWARDS_DIR' ) || ! file_exists( ATFA_AWARDS_DIR . 'data/partner.php' ) ) {
			return null;
		}
		$p = include ATFA_AWARDS_DIR . 'data/partner.php';
		if ( ! is_array( $p ) || empty( $p['name'] ) || empty( $p['logo'] ) || empty( $p['url'] ) ) {
			return null;
		}
		if ( empty( $p['public'] ) && ! current_user_can( 'edit_pages' ) ) {
			return null;
		}
		$p['src'] = atfa_expo27_awards_img( $p['logo'] );
		return $p['src'] ? $p : null;
	}

	/**
	 * The partner's pieces of the page: "Presented by" under the headline and on the dinner band, and the
	 * Principal Partner feature after the 2025 photos. Empty strings when there's no partner to show.
	 */
	function atfa_expo27_partner_html() {
		$out = array( 'hero' => '', 'feature' => '', 'dinner' => '' );
		$p   = atfa_expo27_partner();
		if ( ! $p ) {
			return $out;
		}
		$img   = '<img src="' . esc_url( $p['src'] ) . '" alt="' . esc_attr( $p['name'] ) . '" width="' . (int) $p['width'] . '" height="' . (int) $p['height'] . '" decoding="async" data-no-lazy="1">';
		$link  = ' href="' . esc_url( $p['url'] ) . '" target="_blank" rel="noopener sponsored"';
		$arrow = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>';
		$site  = preg_replace( '#^https?://(www\.)?|/$#', '', $p['url'] );
		$role  = ! empty( $p['role'] ) ? $p['role'] : 'Principal Partner';

		$out['hero']    = '<a class="xp-pp ld d4"' . $link . '><span class="xp-pp-l">Presented by</span>' . $img . '</a>';
		$out['dinner']  = '<a class="xp-pp xp-pp-dn"' . $link . '><span class="xp-pp-l">Presented by</span>' . $img . '</a>';
		$out['feature'] = '<section class="wrap sr xp-ppf-w" aria-labelledby="xp-ppf-h"><div class="xp-ppf">'
			. '<a class="xp-ppf-logo"' . $link . ' aria-label="' . esc_attr( $p['name'] ) . ' website">' . $img . '</a>'
			. '<div class="xp-ppf-t"><span class="eyebrow or">' . esc_html( $role ) . ' · ATFA Expo and Awards 2027</span>'
			. '<h2 class="disp" id="xp-ppf-h">The 2027 Expo is presented by ' . esc_html( $p['name'] ) . '</h2>'
			. ( ! empty( $p['about'] ) ? '<p>' . esc_html( $p['about'] ) . '</p>' : '' )
			. '<a class="xp-ppf-go"' . $link . '>Visit ' . esc_html( $site ) . $arrow . '</a>'
			. '</div></div></section>';
		return $out;
	}

	/**
	 * <img> for one of the 2025 photos, with sizes for phones and big screens. '' if missing.
	 */
	function atfa_expo27_photo( $key, $args ) {
		$photos = atfa_expo27_page_photos();
		if ( empty( $photos[ $key ] ) || ! function_exists( 'wp_get_attachment_image_src' ) ) {
			return '';
		}
		$p   = $photos[ $key ];
		$src = $p['id'] ? wp_get_attachment_image_src( (int) $p['id'], 'large' ) : false;
		if ( ! $src || empty( $src[0] ) ) {
			return '';
		}
		$srcset = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( (int) $p['id'], 'large' ) : '';
		$attrs  = array(
			'class'    => isset( $args['class'] ) ? $args['class'] : '',
			'src'      => $src[0],
			'srcset'   => $srcset ? $srcset : '',
			'sizes'    => $srcset && isset( $args['sizes'] ) ? $args['sizes'] : '',
			'width'    => ! empty( $src[1] ) ? (int) $src[1] : '',
			'height'   => ! empty( $src[2] ) ? (int) $src[2] : '',
			'alt'      => ! empty( $args['decor'] ) ? '' : $p['alt'],
			'style'    => isset( $args['style'] ) ? $args['style'] : 'object-position:' . $p['pos'],
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
	 * The Expo page. Prints once per page.
	 */
	function atfa_expo27_page_shortcode( $atts = array() ) {
		static $done = false;
		if ( $done ) {
			return '';
		}
		$done     = true;
		$settings = atfa_expo27_settings();
		$version  = '__VERSION__';
		$photos   = atfa_expo27_page_photos();

		// Staff: "Refresh from the sheet" reads the sheet straight away, skipping the cache.
		$refreshed = null;
		if ( isset( $_GET['refresh'] ) && atfa_expo27_is_staff() ) { // phpcs:ignore WordPress.Security.NonceVerification
			$refreshed = atfa_expo27_get_data( true );
		}

		$config = array(
			'version'      => $version,
			'restUrl'      => rest_url( 'atfa/v1/expo27/stands' ),
			'staticUrl'    => ( $static = atfa_expo27_static_path() ) ? $static['url'] : '',
			'csvUrl'       => $settings['csv_url'],
			'enquiryEmail' => $settings['enquiry_email'],
			'enquiryName'  => $settings['enquiry_name'],
			'enquiryUrl'   => $settings['enquiry_url'],
			'pdfUrl'       => $settings['pdf_url'],
			'currency'     => $settings['currency'],
			'priceNote'    => $settings['price_note'],
		);
		$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

		$logo = atfa_expo27_awards_img( 'atfa-logo-white.png' );
		$logo = $logo
			? '<img src="' . esc_url( $logo ) . '" alt="ATFA Australasian Timber Flooring Association" width="200" height="46" data-no-lazy="1">'
			: '<span class="logo-text">ATFA</span>';
		$mark = atfa_expo27_awards_img( 'atfa-wordmark.svg' );
		$mark = $mark ? '<img class="wm" src="' . esc_url( $mark ) . '" alt="ATFA" data-no-lazy="1">' : 'ATFA ';

		// Hero: four photos fading in turn, each with its caption.
		$hero = '';
		$caps = '';
		$i    = 0;
		foreach ( array( 'procoat' => '-2s', 'cfs' => '6s', 'bona' => '14s', 'lagler' => '22s' ) as $key => $delay ) {
			$img = atfa_expo27_photo( $key, array(
				'class' => 0 === $i ? 'lead' : '',
				'sizes' => '(max-width: 700px) 100vw, (max-width: 900px) 100vw, 70vw',
				'style' => 'animation-delay:' . $delay . ';object-position:' . $photos[ $key ]['pos'],
				'decor' => true,
				'first' => 0 === $i,
			) );
			if ( $img ) {
				$hero .= $img;
				$caps .= '<span class="xp-cap' . ( 0 === $i ? ' lead' : '' ) . '" style="animation-delay:' . $delay . '">ATFA Expo 2025 · Melbourne<br><strong>'
					. esc_html( $photos[ $key ]['name'] ) . '</strong></span>';
				$i++;
			}
		}

		$gallery = '';
		foreach ( array( 'procoat', 'norton', 'cfs', 'podcast', 'bona', 'woody' ) as $key ) {
			$img = atfa_expo27_photo( $key, array( 'sizes' => '(max-width: 700px) 280px, (max-width: 900px) 33vw, 220px', 'lazy' => true ) );
			if ( $img ) {
				$gallery .= '<figure class="xp-gi"><span class="ph">' . $img . '</span><figcaption><strong>'
					. esc_html( $photos[ $key ]['name'] ) . '</strong>' . esc_html( $photos[ $key ]['note'] ) . '</figcaption></figure>';
			}
		}

		$dinner     = atfa_expo27_awards_img( 'beaver-commercial.jpg' );
		$dinner_cap = $dinner ? '<div class="xp-dn-cap">2025 Commercial / Industrial Solid Timber Floor of the Year<br><strong>Beaver Flooring</strong></div>' : '';
		$dinner     = $dinner ? '<img src="' . esc_url( $dinner ) . '" alt="" loading="lazy" decoding="async" data-no-lazy="1">' : '';

		$awards  = home_url( '/awards/' );
		$partner = atfa_expo27_partner_html();
		$html    = strtr( atfa_expo27_page_markup(), array(
			'{{VERSION}}'      => esc_attr( $version ),
			'{{LOGO}}'         => $logo,
			'{{WORDMARK}}'     => $mark,
			'{{HERO_IMGS}}'    => $hero,
			'{{HERO_CAPS}}'    => $caps,
			'{{GALLERY}}'      => $gallery,
			'{{DINNER_IMG}}'   => $dinner,
			'{{DINNER_CAP}}'   => $dinner_cap,
			'{{AWARDS_URL}}'   => esc_url( $awards ),
			'{{PDF_URL}}'      => esc_url( $settings['pdf_url'] ),
			'{{EMAIL}}'        => esc_html( $settings['enquiry_email'] ),
			'{{MAILTO}}'       => esc_attr( 'mailto:' . $settings['enquiry_email'] ),
			'{{ENQUIRY_NAME}}' => esc_html( $settings['enquiry_name'] ),
			'{{STAFF}}'        => atfa_expo27_staff_bar( $settings, $refreshed, atfa_expo27_share_tools() ),
			'{{PARTNER_HERO}}'    => $partner['hero'],
			'{{PARTNER_FEATURE}}' => $partner['feature'],
			'{{PARTNER_DINNER}}'  => $partner['dinner'],
		) );

		return '<style id="atfa-expo27-page-css">' . atfa_expo27_page_css() . '</style>'
			. $html
			. '<script type="application/json" id="atfa-expo27-page-config">' . wp_json_encode( $config, $flags ) . '</script>'
			. '<script type="application/json" id="atfa-expo27-page-snapshot">' . wp_json_encode( atfa_expo27_snapshot(), $flags ) . '</script>'
			. '<script id="atfa-expo27-page-js" data-no-optimize="1" data-cfasync="false" nowprocket>' . atfa_expo27_page_js() . '</script>';
	}

	function atfa_expo27_page_markup() {
		return <<<'ATFA_EXPO27_PAGE_HTML'
__XP_HTML__
ATFA_EXPO27_PAGE_HTML;
	}

	function atfa_expo27_page_css() {
		return <<<'ATFA_EXPO27_PAGE_CSS'
__XP_CSS__
ATFA_EXPO27_PAGE_CSS;
	}

	function atfa_expo27_page_js() {
		return <<<'ATFA_EXPO27_PAGE_JS'
__XP_JS__
ATFA_EXPO27_PAGE_JS;
	}

	function atfa_expo27_markup() {
		return <<<'ATFA_EXPO27_HTML'
__MARKUP__
ATFA_EXPO27_HTML;
	}

	function atfa_expo27_css() {
		return <<<'ATFA_EXPO27_CSS'
__CSS__
ATFA_EXPO27_CSS;
	}

	function atfa_expo27_js() {
		return <<<'ATFA_EXPO27_JS'
__JS__
ATFA_EXPO27_JS;
	}
}
