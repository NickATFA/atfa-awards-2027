<?php
/**
 * Runs the snippet outside WordPress with small stand-ins for the WordPress
 * functions it uses. Usage:
 *   php tests/php_harness.php <fixture.csv|FAIL|HTML> <out-dir>
 */
error_reporting( E_ALL );
ini_set( 'display_errors', '1' );
define( 'ABSPATH', __DIR__ );

$GLOBALS['__fixture'] = $argv[1] ?? '';
$GLOBALS['__out']     = $argv[2] ?? sys_get_temp_dir();
// Optional third argument: REFRESH_EDITOR or REFRESH_VISITOR opens the page with ?refresh=1;
// STAFF opens it as a logged-in editor without ?refresh=1. Add _WARM to start with data
// already cached (STAFF_WARM).
$mode = $argv[3] ?? '';
if ( 0 === strpos( $mode, 'REFRESH' ) ) {
	$_GET['refresh'] = '1';
	$GLOBALS['__can_edit'] = ( 'REFRESH_EDITOR' === $mode );
}
if ( 0 === strpos( $mode, 'STAFF' ) ) {
	$GLOBALS['__can_edit'] = true;
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
	public $data; public $headers = array();
	public function __construct( $d ) { $this->data = $d; }
	public function header( $k, $v ) { $this->headers[ $k ] = $v; }
}
class Fake_Request {
	private $p; public function __construct( $p = array() ) { $this->p = $p; }
	public function get_param( $k ) { return $this->p[ $k ] ?? null; }
}
function add_shortcode( $tag, $cb ) { $GLOBALS['__shortcodes'][ $tag ] = $cb; }
function add_action( $hook, $cb ) { $GLOBALS['__actions'][ $hook ][] = $cb; }
function add_filter( $hook, $cb, $prio = 10, $args = 1 ) { $GLOBALS['__filters'][ $hook ][] = $cb; }
function register_rest_route( $ns, $route, $args ) { $GLOBALS['__routes'][ $ns . $route ] = $args; }
function current_user_can( $cap ) { return 'edit_pages' === $cap && ! empty( $GLOBALS['__can_edit'] ); }
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
	if ( 'HTML' === $f ) { return array( 'code' => 200, 'body' => "<!DOCTYPE html><html><body>Not published</body></html>" ); }
	return array( 'code' => 200, 'body' => file_get_contents( $f ) );
}
function wp_remote_retrieve_response_code( $r ) { return $r['code']; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function wp_check_invalid_utf8( $s, $strip = false ) { return mb_check_encoding( $s, 'UTF-8' ) ? $s : mb_convert_encoding( $s, 'UTF-8', 'UTF-8' ); }
function esc_url_raw( $u, $protocols = null ) {
	$scheme = strtolower( (string) parse_url( $u, PHP_URL_SCHEME ) );
	if ( ! in_array( $scheme, $protocols ?: array( 'http', 'https' ), true ) ) { return ''; }
	return str_replace( array( ' ', '"', "'", '<', '>' ), array( '%20', '%22', '%27', '%3C', '%3E' ), $u );
}
function esc_url( $u ) { return htmlspecialchars( esc_url_raw( $u ), ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function wp_json_encode( $d, $f = 0 ) { return json_encode( $d, $f ); }
function rest_url( $p ) { return 'https://test.local/wp-json/' . $p; }
function wp_upload_dir( $t = null, $c = true ) { return array( 'basedir' => $GLOBALS['__out'] . '/uploads', 'baseurl' => 'https://test.local/wp-content/uploads', 'error' => false ); }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function wp_mkdir_p( $d ) { return is_dir( $d ) || mkdir( $d, 0777, true ); }

require dirname( __DIR__ ) . '/dist/atfa-expo27-floorplan.php';

// Fire rest_api_init so the route registers.
foreach ( $GLOBALS['__actions']['rest_api_init'] ?? array() as $cb ) { $cb(); }

$route = $GLOBALS['__routes']['atfa/v1/expo27/stands'] ?? null;
$result = array(
	'shortcode_registered' => isset( $GLOBALS['__shortcodes']['atfa_expo27_floorplan'] ),
	'route_registered'     => (bool) $route,
	'route_public'         => $route && '__return_true' === $route['permission_callback'],
);

// STAFF_WARM: the sheet was read a minute ago, so the staff bar can say when.
if ( false !== strpos( $mode, '_WARM' ) ) {
	$GLOBALS['__transients'][ ATFA_EXPO27_CACHE_KEY ] = array(
		'ok' => true, 'source' => 'live', 'updated' => gmdate( 'c', time() - 60 ),
		'stands' => array( array( 'id' => 'S01', 'status' => 'available' ) ),
	);
}

// Snapshot before any fetch: should be null and must not call Google.
$before = $GLOBALS['__http_calls'];
$html_cold = call_user_func( $GLOBALS['__shortcodes']['atfa_expo27_floorplan'] );
$result['shortcode_cold_http_calls'] = $GLOBALS['__http_calls'] - $before;
$result['shortcode_cold_has_null_snapshot'] = false !== strpos( $html_cold, 'id="atfa-expo27-snapshot">null</script>' );
// The sheet's document id (staff link) must only ever appear in the staff bar.
$sheet_id = '1cYp8wew5Qvfds0_IjBaXA8mb6kuOHh4TmVZqsfbXWsI';
$result['staff_bar']  = false !== strpos( $html_cold, 'class="afpx-staff"' );
$result['sheet_link'] = false !== strpos( $html_cold, $sheet_id );
$result['token_left'] = false !== strpos( $html_cold, '{{' );
if ( $mode ) {
	$status = preg_match( '/class="afpx-staff-status[^"]*">([^<]*)</', $html_cold, $m ) ? html_entity_decode( $m[1], ENT_QUOTES ) : '';
	$link   = preg_match( '/<a class="afpx-btn is-small" href="([^"]*)" target="_blank" rel="noopener">/', $html_cold, $l ) ? html_entity_decode( $l[1], ENT_QUOTES ) : '';
	echo json_encode( array(
		'mode'       => $mode,
		'http_calls' => $result['shortcode_cold_http_calls'],
		'snapshot'   => ! $result['shortcode_cold_has_null_snapshot'],
		'staff_bar'  => $result['staff_bar'],
		'sheet_link' => $result['sheet_link'],
		'link'       => $link,
		'status'     => $status,
		'token_left' => $result['token_left'],
	), JSON_UNESCAPED_SLASHES ), "\n";
	if ( 0 === strpos( $mode, 'STAFF' ) ) {
		file_put_contents( $GLOBALS['__out'] . '/shortcode-staff.html', $html_cold );
	}
	exit;
}

// REST call.
$resp = call_user_func( $route['callback'], new Fake_Request() );
$static_file = $GLOBALS['__out'] . '/uploads/atfa-expo27/stands-v3.json';
$result['static_written'] = file_exists( $static_file ) && json_decode( file_get_contents( $static_file ), true ) == $resp->data;
$result['config_has_static_url'] = false !== strpos( $html_cold, 'atfa-expo27/stands-v3.json' );
$result['rest'] = $resp->data;

// Second REST call should be served from the cache.
$before = $GLOBALS['__http_calls'];
call_user_func( $route['callback'], new Fake_Request() );
$result['second_call_http'] = $GLOBALS['__http_calls'] - $before;

// Simulate Google failing after a good fetch: cache expired, last good copy should be used.
if ( ! in_array( $GLOBALS['__fixture'], array( 'FAIL', 'HTML' ), true ) ) {
	$GLOBALS['__transients'] = array();
	$GLOBALS['__fixture'] = 'FAIL';
	$stale = call_user_func( $route['callback'], new Fake_Request() );
	$result['after_failure_source'] = $stale->data['source'];
	$result['after_failure_count']  = count( $stale->data['stands'] );
}

// Render again (shortcode is once-per-page, so reset the static by calling the
// function through a fresh process instead; here we just render the markup pieces).
file_put_contents( $GLOBALS['__out'] . '/rest.json', json_encode( $resp->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );

// Build a page with a warm snapshot for browser tests.
$GLOBALS['__transients'][ ATFA_EXPO27_CACHE_KEY ] = $resp->data;
$snapshot_json = wp_json_encode( atfa_expo27_snapshot(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
$html_warm = str_replace( 'id="atfa-expo27-snapshot">null</script>', 'id="atfa-expo27-snapshot">' . $snapshot_json . '</script>', $html_cold );
file_put_contents( $GLOBALS['__out'] . '/shortcode.html', $html_warm );
$result['shortcode_bytes'] = strlen( $html_warm );
$result['second_shortcode_empty'] = '' === call_user_func( $GLOBALS['__shortcodes']['atfa_expo27_floorplan'] );

// Static copy is swapped in whole: no temporary files left behind.
$result['no_tmp_files'] = array() === glob( $GLOBALS['__out'] . '/uploads/atfa-expo27/*.tmp' );

// Stand numbers: tidy-up and validation.
$result['ids'] = array(
	's01'       => atfa_expo27_stand_id( ' s01 ' ),
	'stand_b12' => atfa_expo27_stand_id( 'Stand B12' ),
	'305'       => atfa_expo27_stand_id( '305' ),
	'total'     => atfa_expo27_stand_id( 'Total' ),
	's01a'      => atfa_expo27_stand_id( 'S01a' ),
	'empty'     => atfa_expo27_stand_id( '' ),
);


echo json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), "\n";
