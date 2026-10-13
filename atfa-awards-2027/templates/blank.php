<?php
/**
 * Blank canvas template for the awards pages: no site header, footer or theme styles.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Keep WP Rocket from minifying, delaying or stripping anything on these pages.
if ( ! defined( 'DONOTROCKETOPTIMIZE' ) ) {
	define( 'DONOTROCKETOPTIMIZE', true );
}

add_action( 'wp_enqueue_scripts', 'atfa_awards_strip_styles', 9999 );
add_action( 'wp_print_styles', 'atfa_awards_strip_styles', 9999 );
// Some plugins (JetSmartFilters, for one) enqueue their stylesheet late, in the footer.
add_action( 'wp_print_footer_scripts', 'atfa_awards_strip_styles', 1 );

// Link preview tags, unless an SEO plugin already prints them.
add_action( 'wp_head', 'atfa_awards_print_share_meta', 1 );

// The page content is just a shortcode; wpautop would wrap it in a stray paragraph.
remove_filter( 'the_content', 'wpautop' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&amp;family=Montserrat:wght@400;500;600;700&amp;display=swap">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'atfa-awards-canvas' ); ?>>
<?php wp_body_open(); ?>
<?php
while ( have_posts() ) {
	the_post();
	the_content();
}
?>
<?php wp_footer(); ?>
</body>
</html>
