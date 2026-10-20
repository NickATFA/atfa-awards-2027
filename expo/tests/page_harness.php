<?php
/**
 * Renders the Expo page shortcode outside WordPress, with small stand-ins for the
 * WordPress functions it uses. Usage:
 *   php tests/page_harness.php <fixture.csv|FAIL> <out-dir> <MODE>
 * MODE: VISITOR, VISITOR_COLD (nothing cached yet), STAFF, REFRESH_EDITOR, REFRESH_VISITOR,
 *       NOAWARDS (awards plugin not installed), FORWARD (old page forwarding checks).
 */
error_reporting( E_ALL );
ini_set( 'display_errors', '1' );
define( 'ABSPATH', __DIR__ );

$GLOBALS['__fixture'] = $argv[1] ?? '';
$GLOBALS['__out']     = $argv[2] ?? sys_get_temp_dir();
$mode                 = $argv[3] ?? 'VISITOR';
if ( 0 === strpos( $mode, 'REFRESH' ) ) {
	$_GET['refresh']       = '1';
	$GLOBALS['__can_edit'] = ( 'REFRESH_EDITOR' === $mode );
}
if ( 'STAFF' === $mode ) {
	$GLOBALS['__can_edit'] = true;
}
if ( 'NOAWARDS' !== $mode ) {
	define( 'ATFA_AWARDS_URL', 'https://test.local/awards-plugin/' );
	define( 'ATFA_AWARDS_DIR', ( getenv( 'AWARDS_DIR' ) ?: dirname( __DIR__, 2 ) . '/atfa-awards-2027' ) . '/' );
	define( 'ATFA_AWARDS_VERSION', '1.7.0' );
}
$GLOBALS['__shortcodes'] = array();
$GLOBALS['__actions']    = array();
$GLOBALS['__routes']     = array();
$GLOBALS['__transients'] = array();
$GLOBALS['__options']    = array();
$GLOBALS['__http_calls'] = 0;

class WP_Error {
	private $m;
	public function __construct( $c = '', $m = '' ) { $this->m = $m; }
	public function get_error_message() { return $this->m; }
}
class WP_REST_Response {
	public $data;
	public function __construct( $d ) { $this->data = $d; }
}
class Redirected extends Exception {}
function add_shortcode( $tag, $cb ) { $GLOBALS['__shortcodes'][ $tag ] = $cb; }
function add_action( $hook, $cb ) { $GLOBALS['__actions'][ $hook ][] = $cb; }
function add_filter( $hook, $cb, $prio = 10, $args = 1 ) { $GLOBALS['__filters'][ $hook ][] = $cb; }
function register_rest_route( $ns, $route, $args ) { $GLOBALS['__routes'][ $ns . $route ] = $args; }
function current_user_can( $cap, $id = null ) { return in_array( $cap, array( 'edit_pages', 'read_post' ), true ) && ! empty( $GLOBALS['__can_edit'] ); }
function is_user_logged_in() { return ! empty( $GLOBALS['__can_edit'] ); }
function wp_date( $format, $ts = null ) { return gmdate( $format, null === $ts ? time() : $ts ); }
function rest_ensure_response( $d ) { return new WP_REST_Response( $d ); }
function get_transient( $k ) { return $GLOBALS['__transients'][ $k ] ?? false; }
function set_transient( $k, $v, $t ) { $GLOBALS['__transients'][ $k ] = $v; return true; }
function get_option( $k, $d = false ) { return $GLOBALS['__options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['__options'][ $k ] = $v; return true; }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function wp_remote_get( $url, $args ) {
	$GLOBALS['__http_calls']++;
	$f = $GLOBALS['__fixture'];
	if ( 'FAIL' === $f ) { return new WP_Error( 'http', 'timeout' ); }
	return array( 'code' => 200, 'body' => file_get_contents( $f ) );
}
function wp_remote_retrieve_response_code( $r ) { return $r['code']; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function wp_check_invalid_utf8( $s, $strip = false ) { return mb_check_encoding( $s, 'UTF-8' ) ? $s : mb_convert_encoding( $s, 'UTF-8', 'UTF-8' ); }
function esc_url_raw( $u, $protocols = null ) {
	$scheme = strtolower( (string) parse_url( $u, PHP_URL_SCHEME ) );
	if ( ! in_array( $scheme, $protocols ?: array( 'http', 'https', 'mailto' ), true ) ) { return ''; }
	return str_replace( array( ' ', '"', "'", '<', '>' ), array( '%20', '%22', '%27', '%3C', '%3E' ), $u );
}
function esc_url( $u ) { return htmlspecialchars( esc_url_raw( $u ), ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function wp_json_encode( $d, $f = 0 ) { return json_encode( $d, $f ); }
function rest_url( $p ) { return 'https://test.local/wp-json/' . $p; }
function home_url( $p = '' ) { return 'https://test.local' . $p; }
function wp_upload_dir( $t = null, $c = true ) { return array( 'basedir' => $GLOBALS['__out'] . '/uploads', 'baseurl' => 'https://test.local/wp-content/uploads', 'error' => false ); }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function wp_mkdir_p( $d ) { return is_dir( $d ) || mkdir( $d, 0777, true ); }
// Media library: the seven 2025 photos.
$GLOBALS['__media'] = array(
	19724 => 'procoat', 19725 => 'cfs', 19726 => 'bona', 19727 => 'lagler', 19728 => 'norton', 19729 => 'podcast', 19730 => 'woody',
);
function wp_get_attachment_image_src( $id, $size ) {
	if ( empty( $GLOBALS['__media'][ $id ] ) ) { return false; }
	$f = 'atfa-expo-2025-' . $GLOBALS['__media'][ $id ];
	return 'woody' === $GLOBALS['__media'][ $id ]
		? array( 'https://test.local/media/' . $f . '-768x1024.jpg', 768, 1024, true )
		: array( 'https://test.local/media/' . $f . '-1024x768.jpg', 1024, 768, true );
}
function wp_get_attachment_image_srcset( $id, $size ) {
	if ( empty( $GLOBALS['__media'][ $id ] ) ) { return false; }
	$f = 'https://test.local/media/atfa-expo-2025-' . $GLOBALS['__media'][ $id ];
	return $f . '-768x576.jpg 768w, ' . $f . '-1024x768.jpg 1024w, ' . $f . '.jpg 1600w';
}
// Pages, for the forwarding check.
$GLOBALS['__page']       = 0;
$GLOBALS['__new_status'] = 'publish';
$GLOBALS['__redirect']   = null;
function is_admin() { return false; }
function is_preview() { return false; }
function is_page( $id = null ) { return $GLOBALS['__page'] && ( null === $id || (int) $id === $GLOBALS['__page'] ); }
function get_page_by_path( $slug ) { return 'expo-2027' === $slug ? (object) array( 'ID' => 20001 ) : null; }
function get_post_status( $p ) { $id = is_object( $p ) ? (int) $p->ID : (int) $p; return 19308 === $id ? 'publish' : $GLOBALS['__new_status']; }
function get_permalink( $p ) { $id = is_object( $p ) ? (int) $p->ID : (int) $p; return 19308 === $id ? 'https://test.local/expo-2027-hall-5-interactive-floor-plan/' : 'https://test.local/expo-2027/'; }
function is_404() { return ! empty( $GLOBALS['__is404'] ); }
function nocache_headers() { $GLOBALS['__nocache'] = true; }
function add_query_arg( $k, $v, $url ) { return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . rawurlencode( $k ) . '=' . rawurlencode( $v ); }
$GLOBALS['wp'] = (object) array( 'request' => '' );
if ( ! defined( 'HOUR_IN_SECONDS' ) ) { define( 'HOUR_IN_SECONDS', 3600 ); }
function wp_create_nonce( $a ) { return 'n0nce' . substr( md5( $a ), 0, 6 ); }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function sanitize_text_field( $v ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $v ) ) ); }
function wp_generate_password( $n = 12, $special = true, $extra = false ) {
	$c = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; $o = '';
	for ( $i = 0; $i < $n; $i++ ) { $o .= $c[ random_int( 0, 61 ) ]; }
	return $o;
}
function get_current_user_id() { return empty( $GLOBALS['__can_edit'] ) ? 0 : 7; }
class FakeQuery {
	public $vars; private $main;
	public function __construct( $vars, $main = true ) { $this->vars = $vars; $this->main = $main; }
	public function is_main_query() { return $this->main; }
	public function get( $k ) { return $this->vars[ $k ] ?? ''; }
	public function set( $k, $v ) { $this->vars[ $k ] = $v; }
}
class FakeRequest {
	private $p;
	public function __construct( $p ) { $this->p = $p; }
	public function get_param( $k ) { return $this->p[ $k ] ?? null; }
}
function wp_safe_redirect( $to, $code = 302 ) { $GLOBALS['__redirect'] = array( $to, $code ); throw new Redirected(); }

require dirname( __DIR__ ) . '/dist/atfa-expo27-floorplan.php';
foreach ( $GLOBALS['__actions']['rest_api_init'] ?? array() as $cb ) { $cb(); }

$result = array(
	'mode'                => $mode,
	'page_shortcode'      => isset( $GLOBALS['__shortcodes']['atfa_expo27_page'] ),
	'floorplan_shortcode' => isset( $GLOBALS['__shortcodes']['atfa_expo27_floorplan'] ),
	'forward_hooked'      => in_array( 'atfa_expo27_forward_old_page', $GLOBALS['__actions']['template_redirect'] ?? array(), true ),
);

if ( 'FORWARD' === $mode ) {
	$try = function ( $page, $status, $get = array() ) {
		$GLOBALS['__page']       = $page;
		$GLOBALS['__new_status'] = $status;
		$GLOBALS['__redirect']   = null;
		$_GET                    = $get;
		try { atfa_expo27_forward_old_page(); } catch ( Redirected $e ) { /* recorded */ }
		return $GLOBALS['__redirect'];
	};
	$result['old_page_published'] = $try( 19308, 'publish' );
	$result['old_page_draft']     = $try( 19308, 'draft' );
	$result['other_page']         = $try( 555, 'publish' );
	$result['elementor_preview']  = $try( 19308, 'publish', array( 'elementor-preview' => '19308' ) );
	$hidden = function ( $status, $request, $is404, $staff ) {
		$GLOBALS['__page']       = 0;
		$GLOBALS['__new_status'] = $status;
		$GLOBALS['__is404']      = $is404;
		$GLOBALS['__can_edit']   = $staff;
		$GLOBALS['wp']->request  = $request;
		$GLOBALS['__redirect']   = null;
		try { atfa_expo27_hidden_page_redirect(); } catch ( Redirected $e ) { /* recorded */ }
		return $GLOBALS['__redirect'];
	};
	$result['hidden_private_visitor'] = $hidden( 'private', 'expo-2027', true, false );
	$result['hidden_draft_visitor']   = $hidden( 'draft', 'expo-2027', true, false );
	$result['hidden_published']       = $hidden( 'publish', 'expo-2027', true, false );
	$result['hidden_other_page_404']  = $hidden( 'private', 'something-else', true, false );
	$result['hidden_private_staff']   = $hidden( 'private', 'expo-2027', false, true );
	$menu = function ( $status, $staff ) {
		$GLOBALS['__new_status'] = $status;
		$GLOBALS['__can_edit']   = $staff;
		$items = array(
			3 => (object) array( 'type' => 'post_type', 'object_id' => 555, 'title' => 'Annual Awards' ),
			4 => (object) array( 'type' => 'post_type', 'object_id' => 20001, 'title' => 'Expo & Conference 2027' ),
			5 => (object) array( 'type' => 'custom', 'object_id' => 0, 'title' => 'Social Events' ),
		);
		$out = atfa_expo27_menu_items( $items );
		return array_map( function ( $i ) { return $i->title; }, $out );
	};
	$result['menu_private_visitor'] = $menu( 'private', false );
	$result['menu_private_staff']   = $menu( 'private', true );
	$result['menu_public_visitor']  = $menu( 'publish', false );
	$result['menu_filter_hooked']   = in_array( 'atfa_expo27_menu_items', $GLOBALS['__filters']['wp_nav_menu_objects'] ?? array(), true );
	$result['menu_list_hooked']     = in_array( 'atfa_expo27_menu_items_list', $GLOBALS['__filters']['wp_get_nav_menu_items'] ?? array(), true );
	$GLOBALS['__new_status'] = 'private';
	$GLOBALS['__can_edit']   = false;
	$result['menu_list_private_visitor'] = array_map( function ( $i ) { return $i->title; }, atfa_expo27_menu_items_list( array(
		(object) array( 'type' => 'post_type', 'object_id' => 555, 'title' => 'Annual Awards' ),
		(object) array( 'type' => 'post_type', 'object_id' => 20001, 'title' => 'Expo & Conference 2027' ),
		(object) array( 'type' => 'custom', 'object_id' => 0, 'title' => 'Social Events' ),
	) ) );
	$result['hidden_hooked']        = in_array( 'atfa_expo27_hidden_page_redirect', $GLOBALS['__actions']['template_redirect'] ?? array(), true );
	echo json_encode( $result, JSON_UNESCAPED_SLASHES ), "\n";
	exit;
}

if ( 'SHARE' === $mode ) {
	$GLOBALS['__options'] = array();
	$GLOBALS['__can_edit'] = true;
	$made  = atfa_expo27_rest_share_make( new FakeRequest( array( 'hours' => 24, 'note' => '<b>Sponsor</b> Co' ) ) )->data;
	$made3 = atfa_expo27_rest_share_make( new FakeRequest( array( 'hours' => 5000 ) ) )->data;
	$list  = atfa_expo27_rest_shares()->data['shares'];
	$result['made_url']    = $made['url'];
	$result['made_note']   = $made['note'];
	$result['made_hours']  = round( ( $made['expires'] - time() ) / 3600 );
	$result['odd_hours']   = round( ( $made3['expires'] - time() ) / 3600 );
	$result['listed']      = count( $list );
	$result['routes_staff_only'] = 'atfa_expo27_is_staff' === $GLOBALS['__routes']['atfa/v1/expo27/shares'][0]['permission_callback']
		&& 'atfa_expo27_is_staff' === $GLOBALS['__routes']['atfa/v1/expo27/shares'][1]['permission_callback']
		&& 'atfa_expo27_is_staff' === $GLOBALS['__routes']['atfa/v1/expo27/shares/(?P<key>[A-Za-z0-9]{24,64})']['permission_callback'];
	$GLOBALS['__can_edit'] = false;
	$query = function ( $get, $pagename, $main = true ) {
		$_GET = $get;
		unset( $GLOBALS['atfa_expo27_sharing'] );
		$q = new FakeQuery( array( 'pagename' => $pagename ), $main );
		atfa_expo27_share_query( $q );
		return array( $q->get( 'post_status' ), ! empty( $GLOBALS['atfa_expo27_sharing'] ) );
	};
	$result['visitor_with_key']    = $query( array( 'xp-share' => $made['key'] ), 'expo-2027' );
	$result['visitor_no_key']      = $query( array(), 'expo-2027' );
	$result['visitor_wrong_key']   = $query( array( 'xp-share' => str_repeat( 'a', 32 ) ), 'expo-2027' );
	$result['visitor_bad_key']     = $query( array( 'xp-share' => "x' OR 1=1" ), 'expo-2027' );
	$result['key_on_other_page']   = $query( array( 'xp-share' => $made['key'] ), 'some-other-page' );
	$result['key_not_main_query']  = $query( array( 'xp-share' => $made['key'] ), 'expo-2027', false );
	$GLOBALS['__page'] = 20001;
	$GLOBALS['atfa_expo27_sharing'] = true;
	$result['robots_shared'] = atfa_expo27_share_robots( array() );
	unset( $GLOBALS['atfa_expo27_sharing'] );
	$result['robots_normal'] = atfa_expo27_share_robots( array() );
	$GLOBALS['__can_edit'] = true;
	atfa_expo27_rest_share_stop( new FakeRequest( array( 'key' => $made['key'] ) ) );
	$GLOBALS['__can_edit'] = false;
	$result['after_stop']    = $query( array( 'xp-share' => $made['key'] ), 'expo-2027' );
	// Run out: a link whose time is up stops working and is tidied away.
	$opts = get_option( 'atfa_expo27_shares' );
	$opts[ $made3['key'] ]['expires'] = time() - 5;
	update_option( 'atfa_expo27_shares', $opts );
	$result['expired']       = $query( array( 'xp-share' => $made3['key'] ), 'expo-2027' );
	$result['left_stored']   = count( get_option( 'atfa_expo27_shares' ) );
	$result['hooks']         = in_array( 'atfa_expo27_share_query', $GLOBALS['__actions']['pre_get_posts'] ?? array(), true )
		&& in_array( 'atfa_expo27_share_headers', $GLOBALS['__actions']['template_redirect'] ?? array(), true )
		&& in_array( 'atfa_expo27_share_robots', $GLOBALS['__filters']['wp_robots'] ?? array(), true );
	// The share tools are in the staff bar only.
	$_GET = array();
	$GLOBALS['__can_edit'] = false;
	$result['tools_visitor'] = atfa_expo27_share_tools();
	$GLOBALS['__can_edit'] = true;
	$result['tools_staff']   = false !== strpos( atfa_expo27_share_tools(), 'data-role="share"' );
	echo json_encode( $result, JSON_UNESCAPED_SLASHES ), "\n";
	exit;
}

// Warm the cache like a normal site (someone has viewed the plan before), unless cold.
if ( 'VISITOR_COLD' !== $mode && 0 !== strpos( $mode, 'REFRESH' ) ) {
	atfa_expo27_get_data();
}
$before = $GLOBALS['__http_calls'];
$html   = call_user_func( $GLOBALS['__shortcodes']['atfa_expo27_page'] );
$result['http_calls']   = $GLOBALS['__http_calls'] - $before;
$result['second_empty'] = '' === call_user_func( $GLOBALS['__shortcodes']['atfa_expo27_page'] );
$sheet_id               = '1cYp8wew5Qvfds0_IjBaXA8mb6kuOHh4TmVZqsfbXWsI';
$result['staff_bar']    = false !== strpos( $html, 'class="afpx-staff"' );
$result['sheet_link']   = false !== strpos( $html, $sheet_id );
$result['token_left']   = (bool) preg_match( '/\{\{[A-Z_]+\}\}|__[A-Z_]{4,}__/', $html );
$result['snapshot']     = false === strpos( $html, 'id="atfa-expo27-page-snapshot">null</script>' );
$result['photos']       = substr_count( $html, 'https://test.local/media/' );
$result['logo']         = false !== strpos( $html, 'awards-plugin/assets/img/atfa-logo-white.png?v=1.7.0' );
$result['dinner']       = false !== strpos( $html, 'beaver-commercial.jpg' );
$result['em_dash']      = false !== strpos( $html, "\u{2014}" );
$result['private']      = (bool) preg_match( '/@(?!atfa\.com\.au)[a-z0-9.-]+\.[a-z]+/i', strip_tags( $html ) ) || false !== strpos( $html, 'Secret Prospect' );
$result['bytes']        = strlen( $html );
file_put_contents( $GLOBALS['__out'] . '/page-' . strtolower( $mode ) . '.html', $html );
echo json_encode( $result, JSON_UNESCAPED_SLASHES ), "\n";
