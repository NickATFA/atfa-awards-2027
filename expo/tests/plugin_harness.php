<?php
/**
 * Loads the whole awards plugin the way WordPress does (main file first, then Code Snippets
 * running snippets), with small stand-ins for WordPress, to check the Expo page comes in with
 * the plugin and that the old Code Snippet copy and the plugin never clash.
 *
 *   php expo/tests/plugin_harness.php PLUGIN_ONLY | PLUGIN_THEN_SNIPPET | SNIPPET_THEN_PLUGIN
 *
 * Prints JSON: which copy is running, what got registered, and any PHP warnings.
 */
error_reporting( E_ALL );
$GLOBALS['__warnings'] = array();
set_error_handler( function ( $no, $str, $file, $line ) {
	$GLOBALS['__warnings'][] = $str . ' @ ' . basename( $file ) . ':' . $line;
	return true;
} );
define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
$mode = $argv[1] ?? 'PLUGIN_ONLY';
$GLOBALS['__reg'] = array( 'shortcodes' => array(), 'actions' => array(), 'filters' => array(), 'activation' => 0 );

function add_shortcode( $tag, $cb ) { $GLOBALS['__reg']['shortcodes'][] = $tag; }
function add_action( $hook, $cb = null, $p = 10, $a = 1 ) { $GLOBALS['__reg']['actions'][] = $hook . ':' . ( is_string( $cb ) ? $cb : 'closure' ); return true; }
function add_filter( $hook, $cb = null, $p = 10, $a = 1 ) { $GLOBALS['__reg']['filters'][] = $hook . ':' . ( is_string( $cb ) ? $cb : 'closure' ); return true; }
function register_activation_hook( $file, $cb ) { $GLOBALS['__reg']['activation']++; }
function register_deactivation_hook( $file, $cb ) {}
function plugin_dir_path( $f ) { return dirname( $f ) . '/'; }
function plugin_dir_url( $f ) { return 'https://test.local/wp-content/plugins/' . basename( dirname( $f ) ) . '/'; }
function plugin_basename( $f ) { return basename( dirname( $f ) ) . '/' . basename( $f ); }
function is_admin() { return false; }
function __( $s, $d = null ) { return $s; }
function esc_html__( $s, $d = null ) { return $s; }

$plugin  = dirname( __DIR__, 2 ) . '/atfa-awards-2027/atfa-awards-2027.php';
// The snippet to run alongside: this build, or (LIVE_SNIPPET=path) the older build that is switched on
// on the site when the update arrives.
$snippet = getenv( 'LIVE_SNIPPET' ) ?: __DIR__ . '/../dist/code-snippet.txt';
// Code Snippets runs a snippet's code (no opening tag) with eval.
$run_snippet = function () use ( $snippet ) {
	$code = file_get_contents( $snippet );
	eval( $code );
};

if ( 'SNIPPET_THEN_PLUGIN' === $mode ) {
	$run_snippet();
	require $plugin;
} elseif ( 'PLUGIN_THEN_SNIPPET' === $mode ) {
	require $plugin;
	$run_snippet();
} else {
	require $plugin;
}

$count = function ( $list, $needle ) { return count( array_filter( $list, function ( $x ) use ( $needle ) { return $x === $needle; } ) ); };
$r = $GLOBALS['__reg'];
$which = '';
if ( function_exists( 'atfa_expo27_settings' ) ) {
	$rf = new ReflectionFunction( 'atfa_expo27_settings' );
	$which = false !== strpos( (string) $rf->getFileName(), 'expo-2027.php' ) ? 'plugin' : 'snippet';
}
echo json_encode( array(
	'mode'                  => $mode,
	'expo_running_from'     => $which,
	'page_shortcode'        => $count( $r['shortcodes'], 'atfa_expo27_page' ),
	'floorplan_shortcode'   => $count( $r['shortcodes'], 'atfa_expo27_floorplan' ),
	'forward_hook'          => $count( $r['actions'], 'template_redirect:atfa_expo27_forward_old_page' ),
	'hidden_redirect_hook'  => $count( $r['actions'], 'template_redirect:atfa_expo27_hidden_page_redirect' ),
	'menu_filters'          => $count( $r['filters'], 'wp_nav_menu_objects:atfa_expo27_menu_items' ) + $count( $r['filters'], 'wp_get_nav_menu_items:atfa_expo27_menu_items_list' ),
	'rest_hooks'            => count( array_filter( $r['actions'], function ( $x ) { return 0 === strpos( $x, 'rest_api_init' ); } ) ),
	'awards_version'        => defined( 'ATFA_AWARDS_VERSION' ) ? ATFA_AWARDS_VERSION : null,
	'warnings'              => $GLOBALS['__warnings'],
), JSON_UNESCAPED_SLASHES ), "\n";
