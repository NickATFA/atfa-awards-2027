<?php
/**
 * The ATFA Flooring Industry Weekend 2027: the landing page that brings the Expo & Conference, the Ladies Breakfast
 * and the Awards Dinner together. Rendered by [atfa_awards_weekend]. The weekend's own details come from
 * data/weekend.php (atfa_wk_info()); each event's details come from its own page's data (atfa_wk_events()).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$info     = atfa_wk_info();
$events   = atfa_wk_events();
$days     = atfa_wk_days();
$count    = atfa_wk_count_words();
$range    = atfa_wk_date_range();
$partner  = atfa_awards_partner();
$cats     = atfa_awards_categories();
$expo     = atfa_wk_expo_summary();
$links    = atfa_wk_page_links();
$logo     = atfa_awards_img( 'atfa-logo-white.png' );
$mark     = atfa_awards_img( 'atfa-wordmark.svg' );
$winners  = atfa_awards_winners();
$bk       = function_exists( 'atfa_bk_info' ) ? atfa_bk_info() : null;
$xp_ph    = function_exists( 'atfa_expo27_page_photos' ) ? atfa_expo27_page_photos() : array();
$live     = atfa_wk_entries_live();
$tickets  = (string) $info['dinner_tickets'];
$cal_url  = rest_url( 'atfa-awards/v1/weekend/calendar' );
$arrow    = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>';
$first    = $events ? reset( $events ) : null;
$start    = $first ? $first['date'] : '2027-05-21';
$days_out = max( 0, (int) ceil( ( strtotime( $start . 'T00:00:00+10:00' ) - time() ) / DAY_IN_SECONDS ) );
$entries  = (string) $info['entries_open'];
$days_ent = max( 0, (int) ceil( ( strtotime( $entries ) - time() ) / DAY_IN_SECONDS ) );

// How many awards in each stream, from data/categories.php.
$streams = array( 'member' => 0, 'panel' => 0, 'excellence' => 0 );
foreach ( $cats as $c ) {
	if ( isset( $streams[ $c['stream'] ] ) ) {
		$streams[ $c['stream'] ]++;
	}
}

// "A look inside Crosbe on Thursday, the Expo & Conference on Friday, then the Ladies Breakfast and the Awards Dinner
// on Saturday." Built from what this visitor sees.
$parts = array();
if ( isset( $events['crosbe'] ) ) {
	$parts[] = 'a look inside Crosbe on Thursday';
}
if ( isset( $events['expo'] ) ) {
	$parts[] = 'the Expo & Conference on Friday';
}
$sat = array();
if ( isset( $events['breakfast'] ) ) {
	$sat[] = 'the Ladies Breakfast';
}
if ( isset( $events['dinner'] ) ) {
	$sat[] = 'the Awards Dinner';
}
if ( $sat ) {
	$parts[] = implode( ' and ', $sat ) . ' on Saturday';
}
$last = count( $parts ) > 1 ? array_pop( $parts ) : '';
$lead = implode( ', ', $parts ) . ( '' !== $last ? ', then ' . $last : '' );
$lead = ucfirst( $lead ) . '. ' . ucfirst( $count['events'] ) . ' events over ' . $count['days'] . ' days' . ( isset( $events['crosbe'] ) ? ' in Sydney.' : ' at Sydney Olympic Park.' );

/**
 * A photo for the page: a Media Library photo (by ID) or one bundled with the plugin (by file name).
 */
$photo = function ( $src, $args = array() ) {
	if ( is_int( $src ) || ctype_digit( (string) $src ) ) {
		return function_exists( 'atfa_bk_photo' ) ? atfa_bk_photo( (int) $src, $args ) : '';
	}
	$attrs = array(
		'class'    => isset( $args['class'] ) ? $args['class'] : '',
		'src'      => atfa_awards_img( $src ),
		'alt'      => isset( $args['alt'] ) ? $args['alt'] : '',
		'style'    => isset( $args['style'] ) ? $args['style'] : '',
		'loading'  => ! empty( $args['lazy'] ) ? 'lazy' : '',
		'decoding' => 'async',
		'width'    => '1800',
		'height'   => '1200',
	);
	$html = '<img';
	foreach ( $attrs as $name => $value ) {
		if ( '' === $value && 'alt' !== $name ) {
			continue;
		}
		$html .= ' ' . $name . '="' . ( 'src' === $name ? esc_url( $value ) : esc_attr( $value ) ) . '"';
	}
	return $html . ' data-no-lazy="1">';
};

// The photos for each event: the 2025 Expo, the Women in Flooring lunches, and the 2025 awards dinner.
$xp_ids = array();
foreach ( array( 'bona', 'cfs', 'procoat' ) as $k ) {
	if ( ! empty( $xp_ph[ $k ]['id'] ) ) {
		$xp_ids[] = array( 'src' => (int) $xp_ph[ $k ]['id'], 'pos' => $xp_ph[ $k ]['pos'] );
	}
}
$bk_ids = array();
foreach ( array_slice( $bk ? $bk['hero'] : array(), 0, 3 ) as $id ) {
	$bk_ids[] = array( 'src' => (int) $id, 'pos' => '50% 40%' );
}
$dn_ph = array();
foreach ( (array) $info['dinner_photos'] as $p ) {
	if ( ! empty( $p['file'] ) && file_exists( ATFA_AWARDS_DIR . 'assets/img/' . $p['file'] ) ) {
		$dn_ph[] = array( 'src' => $p['file'], 'pos' => isset( $p['pos'] ) ? $p['pos'] : '50% 50%', 'alt' => isset( $p['alt'] ) ? $p['alt'] : '' );
	}
}
$sets = array(
	'expo'      => $xp_ids,
	'breakfast' => $bk_ids,
	'dinner'    => array( isset( $dn_ph[2] ) ? $dn_ph[2] : null, isset( $dn_ph[1] ) ? $dn_ph[1] : null, isset( $dn_ph[0] ) ? $dn_ph[0] : null ),
);
$sets['dinner'] = array_values( array_filter( $sets['dinner'] ) );
// One photo for each event's card in the program, different from the first one in the hero.
$card_ph = array(
	'expo'      => isset( $xp_ids[1] ) ? $xp_ids[1] : ( $xp_ids ? $xp_ids[0] : null ),
	'breakfast' => isset( $bk_ids[1] ) ? $bk_ids[1] : ( $bk_ids ? $bk_ids[0] : null ),
	'dinner'    => isset( $dn_ph[1] ) ? $dn_ph[1] : ( $dn_ph ? $dn_ph[0] : null ),
);
$night = $dn_ph ? $dn_ph[0] : null;

// The venues, once each, in the order of the weekend.
$venues = array();
foreach ( $events as $ev ) {
	if ( '' === $ev['venue'] ) {
		continue;
	}
	$vk = strtolower( preg_replace( '/[^a-z0-9]+/i', '', $ev['venue'] ) );
	if ( ! isset( $venues[ $vk ] ) ) {
		$venues[ $vk ] = array( 'ev' => $ev, 'what' => array() );
	}
	$venues[ $vk ]['what'][] = $ev['short'] . ' · ' . $ev['title'];
}

// Sponsorship links: the category sponsorship page once it is open to everyone (or for the people who can see it
// now), otherwise an email to Phil; the breakfast's packages once its page is visible.
$aws_url = '';
if ( function_exists( 'atfa_aws_page_id' ) && atfa_aws_page_id() && ( 'public' === atfa_aws_settings()['access'] || atfa_aws_can_preview() ) ) {
	$aws_url = atfa_aws_page_url();
}
$phil    = 'phil@atfa.com.au';
$tri     = array_values( $events );
$n_tri   = count( $tri );
$signup  = rest_url( 'atfa-awards/v1/notify' );
?>
<style>
/* ATFA Flooring Industry Weekend 2027. The same family as the awards, Expo and breakfast pages: black, Barlow
   Condensed and Montserrat, ATFA orange, the warm charcoal bands. Each event keeps its own accent where it appears:
   the breakfast's pink, Crosbe's blue. */
html{background:#050505 !important}body{background:#050505 !important;margin:0 !important;padding:0 !important}
.wk{position:relative;min-height:100vh;box-sizing:border-box;background:#0C0B0A;color:#F4EFE7;font-family:'Montserrat',system-ui,sans-serif;font-size:16px;-webkit-font-smoothing:antialiased;line-height:1.4;overflow-x:clip;text-align:left;--or:#F8931F;--or2:#FFA640;--ori:#1A1208;--ink:#F4EFE7;--mut:#B4A99A;--dim:#8F857A;--card:#1A1613;--line:#332C26;--band:#1A1613;--bcard:#221D19;--bline:#3B332C;--pk:#FF9FC7;--pki:#2B0F1D;--cb:#0095DA;--cb2:#4FC0F0}
.wk *,.wk *:before,.wk *:after{box-sizing:border-box}
.wk a{color:var(--or);text-decoration:none}.wk a:hover{color:var(--or2)}
.wk p,.wk figure,.wk ol,.wk ul,.wk dl,.wk dd{margin:0}
.wk ol,.wk ul{padding:0;list-style:none}
.wk li{margin:0}
.wk h1,.wk h2,.wk h3{margin:0;color:var(--ink);font-weight:700}
.wk img{max-width:100%}
.wk button,.wk input{font:inherit;color:inherit;text-transform:none;letter-spacing:0;box-shadow:none;margin:0;line-height:inherit;border-radius:0}
.wk .disp{font-family:'Barlow Condensed','Arial Narrow',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:.01em;line-height:.92;text-wrap:balance}
.wk .eyebrow{display:block;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--mut);font-weight:600;line-height:1.35}
.wk .or{color:var(--or)}.wk .pk{color:var(--pk)}
.wk .small{color:var(--dim)}
.wk .btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:52px;padding:0 26px;border-radius:4px;font-weight:600;font-size:15px;text-decoration:none;border:1px solid transparent;cursor:pointer;font-family:inherit;white-space:nowrap;line-height:1;text-transform:none;letter-spacing:0;box-shadow:none;background:none}
.wk .btn-primary{background:var(--or);color:var(--ori);border-color:var(--or)}.wk .btn-primary:hover{background:var(--or2);border-color:var(--or2);color:var(--ori)}
.wk .btn-primary[disabled]{opacity:.7;cursor:default}
.wk .btn-ghost{background:transparent;color:var(--ink);border-color:#3A332D}.wk .btn-ghost:hover{color:#FFFFFF;border-color:var(--ink)}
.wk a:focus-visible,.wk button:focus-visible,.wk input:focus-visible{outline:2px solid var(--or);outline-offset:2px}
.wk .wrap{max-width:1280px;margin-left:auto;margin-right:auto;padding-left:40px;padding-right:40px}
.wk .card{background:var(--card);border:1px solid var(--line);border-radius:6px}
.wk .tag{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:4px 8px;border-radius:3px;line-height:1.2;background:#2B2622;color:#C3B8A9;white-space:nowrap}
.wk .tag-or{background:var(--or);color:var(--ori)}
.wk .tag-prop{background:transparent;color:var(--cb2);border:1px solid var(--cb2)}
.wk .sec{scroll-margin-top:16px}
.wk .sec-head{display:flex;justify-content:space-between;align-items:end;gap:24px;flex-wrap:wrap;margin-bottom:32px}
.wk .sec-head h2{font-size:72px}
.wk .sec-head p{max-width:48ch;color:var(--mut);font-size:15px;line-height:1.55}
.wk .band{position:relative;margin-top:96px;padding:88px 0;background:var(--band);border-top:1px solid rgba(244,239,231,.08);border-bottom:1px solid rgba(244,239,231,.08)}
.wk .band .card{background:var(--bcard);border-color:var(--bline)}
.wk .go{display:inline-flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:var(--or)}
@keyframes wk-rise{from{opacity:0;transform:translateY(26px)}to{opacity:1;transform:translateY(0)}}
@keyframes wk-fade{from{opacity:0}to{opacity:1}}
.wk .ld{animation:wk-rise .9s cubic-bezier(.22,.61,.36,1) both}
.wk .ld.d1{animation-delay:.05s}.wk .ld.d2{animation-delay:.18s}.wk .ld.d3{animation-delay:.3s}.wk .ld.d4{animation-delay:.45s}.wk .ld.d5{animation-delay:.6s}.wk .ld.d6{animation-delay:.75s}
@supports (animation-timeline: view()){.wk .sr{animation:wk-rise linear both;animation-timeline:view();animation-range:entry 0% entry 32%}}

/* Header */
.wk-head-bar{position:absolute;left:0;right:0;top:0;z-index:6}
.wk-head{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:22px 0;border-bottom:1px solid rgba(244,239,231,.12)}
.wk-head .back{display:inline-flex;align-items:center;gap:8px;color:var(--mut);font-size:13px;font-weight:600}
.wk-head .back:hover{color:#FFFFFF}
.wk-head .logo{display:block;line-height:0}
.wk-head .logo img{height:46px;width:auto;display:block}
.wk-head .logo-text{font-family:'Barlow Condensed','Arial Narrow',sans-serif;font-weight:700;font-size:30px;color:var(--ink);line-height:1}
.wk-head .btns{display:flex;gap:12px;flex-wrap:wrap}

/* Hero: the headline on black, then the events side by side */
.wk-top{position:relative;background:#050505;padding-top:91px;overflow:hidden;display:flex;flex-direction:column;min-height:100vh;min-height:100svh}
.wk-glow{position:absolute;right:-12%;top:-30%;width:72%;height:120%;background:radial-gradient(closest-side,rgba(248,147,31,.20),rgba(248,147,31,.06) 55%,rgba(248,147,31,0));pointer-events:none;animation:wk-fade 2.4s ease-out both}
/* The headline is as big as the width allows (its two lines are each about 5.85em wide) and, on shorter screens, small
   enough that the events below it still show their names without scrolling. */
.wk .wk-hero{position:relative;flex:0 0 auto;padding-top:34px;padding-bottom:24px}
.wk-h1{margin:12px 0 0;font-size:clamp(72px,min(calc((min(100vw,1280px) - 80px) / 5.85),calc((100vh - 650px) / 1.68)),196px);font-size:clamp(72px,min(calc((min(100vw,1280px) - 80px) / 5.85),calc((100svh - 650px) / 1.68)),196px);line-height:.84;letter-spacing:0;color:var(--ink)}
.wk-h1 .l1,.wk-h1 .l2{display:flex;align-items:flex-end;gap:.16em;white-space:nowrap}
.wk-h1 .wm{display:block;height:.8em;width:auto;margin:0 0 .045em}
.wk-h1 .yr{color:var(--or)}
.wk-meta{display:grid;grid-template-columns:minmax(0,7fr) minmax(0,5fr);gap:24px 48px;align-items:end;margin-top:24px}
.wk-dline{display:flex;align-items:center;gap:10px 28px;flex-wrap:wrap}
.wk .wk-dline .pp{margin:0}
.wk-meta-l{display:flex;flex-direction:column;align-items:flex-start;min-width:0}
.wk-dates{font-size:44px;color:var(--ink)}
.wk-dates .or{white-space:nowrap}
.wk .wk-lead{font-size:18px;line-height:1.55;color:var(--mut);max-width:62ch;margin-top:14px}
.wk-meta-r{display:flex;flex-direction:column;align-items:flex-end;gap:20px;min-width:0}
.wk-ctas{display:flex;gap:12px;flex-wrap:wrap;justify-content:flex-end}
.wk-count{display:flex;align-items:baseline;gap:10px}
.wk-count .n{font-size:64px;color:var(--or);font-variant-numeric:tabular-nums}
.wk-count .nt{color:var(--mut);font-size:14px}
/* Principal Partner: "Presented by" and the logo, as on the other pages */
.wk .pp{display:inline-flex;align-items:center;gap:16px;margin:22px 0 0;text-decoration:none}
.wk .pp-l{font-size:11px;letter-spacing:.2em;text-transform:uppercase;font-weight:600;color:var(--mut);white-space:nowrap;padding-right:16px;border-right:1px solid #3A332D;line-height:30px;transition:color .2s}
.wk a.pp:hover .pp-l{color:var(--ink)}
.wk .pp img{display:block;height:36px;width:auto;max-width:none;margin:0;border:0}
/* The events side by side: photos that change slowly, the day and the name over each */
.wk-tri{position:relative;flex:1 1 auto;display:grid;grid-template-columns:repeat(var(--n,3),minmax(0,1fr));grid-template-rows:minmax(0,1fr);gap:3px;min-height:300px;max-height:640px;margin-top:8px}
.wk a.wk-col{position:relative;display:block;overflow:hidden;background:#15120F;color:var(--ink);isolation:isolate}
.wk-col-ph{position:absolute;inset:0;z-index:0}
.wk-col-ph img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;margin:0;border:0;max-width:none;transform-origin:50% 50%;transition:transform .8s cubic-bezier(.2,.6,.2,1)}
.wk-col-ph img:first-child{opacity:1}
.wk-col-ph.n2 img{animation:wk-x2 14s linear infinite both}
.wk-col-ph.n3 img{animation:wk-x3 21s linear infinite both}
@keyframes wk-x2{0%{opacity:0;transform:scale(1.08)}7%{opacity:1}50%{opacity:1}57%{opacity:0;transform:scale(1)}100%{opacity:0;transform:scale(1)}}
@keyframes wk-x3{0%{opacity:0;transform:scale(1.08)}5%{opacity:1}33.3%{opacity:1}38.3%{opacity:0;transform:scale(1)}100%{opacity:0;transform:scale(1)}}
.wk-col:after{content:"";position:absolute;inset:0;z-index:1;background:linear-gradient(0deg,rgba(5,5,5,.92) 0%,rgba(5,5,5,.55) 34%,rgba(5,5,5,0) 62%),linear-gradient(180deg,rgba(5,5,5,.35) 0%,rgba(5,5,5,0) 18%);transition:opacity .3s}
.wk-col-l{position:absolute;left:0;right:0;bottom:0;z-index:2;display:flex;flex-direction:column;gap:6px;padding:24px 24px 22px;border-top:0}
.wk-col-l .d{font-size:12px;letter-spacing:.16em;text-transform:uppercase;font-weight:700;color:var(--acc,var(--or))}
.wk-col-l .t{font-family:'Barlow Condensed','Arial Narrow',sans-serif;font-weight:700;text-transform:uppercase;font-size:40px;line-height:.92;color:#FFFFFF}
.wk-col-l .v{font-size:13px;color:#D9D0C4;font-weight:500}
.wk-col-l .v .tag{margin-left:6px;vertical-align:1px}
.wk-col-bar{position:absolute;left:0;right:0;top:0;height:3px;z-index:3;background:var(--acc,var(--or));transform:scaleX(0);transform-origin:0 50%;transition:transform .45s cubic-bezier(.2,.6,.2,1)}
.wk a.wk-col:hover .wk-col-bar,.wk a.wk-col:focus-visible .wk-col-bar{transform:scaleX(1)}
.wk a.wk-col:hover .wk-col-ph img{transform:scale(1.04)}
.wk a.wk-col:focus-visible{outline:2px solid var(--or);outline-offset:-2px}
.wk-col.is-bk{--acc:var(--pk)}
.wk-col.is-cb{--acc:var(--cb2)}
/* Crosbe has no photos yet: their logo on a deep blue panel */
.wk-cbp{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:0 14% 30%;background:radial-gradient(120% 90% at 50% 25%,#0A4466 0%,#052638 55%,#03141E 100%)}
.wk-cbp img{display:block;width:100%;max-width:300px;height:auto;margin:0;border:0;opacity:.95}

/* Right now: what you can do today */
.wk .wk-now-w{margin-top:48px}
.wk-now{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.wk-st{display:flex;flex-direction:column;gap:8px;padding:20px 22px;min-width:0;border:1px solid var(--line);border-radius:6px;background:var(--card);color:var(--ink);transition:border-color .2s,background-color .2s}
.wk a.wk-st,.wk a.wk-st:hover{color:var(--ink)}
.wk a.wk-st:hover{border-color:#5A4E43;background:#1E1915}
.wk-st .top{display:flex;align-items:center;gap:10px}
.wk-st .k{font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--mut);font-weight:700}
.wk-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;background:#E0B341;box-shadow:0 0 0 4px rgba(224,179,65,.16)}
.wk-dot.is-on{background:#5BBF7A;box-shadow:0 0 0 4px rgba(91,191,122,.18)}
.wk-dot.is-prop{background:var(--cb2);box-shadow:0 0 0 4px rgba(79,192,240,.18)}
.wk-st .v{font-size:30px;color:var(--ink)}
.wk-st .s{font-size:13px;color:var(--mut);line-height:1.45}
.wk-st .go{margin-top:auto;padding-top:4px;font-size:13.5px}
.wk-st .go.is-quiet{color:var(--dim)}

/* What's on: day by day */
.wk .wk-prog{margin-top:104px}
.wk .wk-gap{margin-top:104px}
.wk-days{display:flex;flex-direction:column;border-bottom:1px solid var(--line)}
.wk-day{display:grid;grid-template-columns:200px minmax(0,1fr);gap:0 40px;padding:40px 0;border-top:1px solid var(--line)}
.wk-date{position:sticky;top:28px;align-self:start;display:flex;flex-direction:column;gap:4px}
.wk-date .dw{font-size:13px;letter-spacing:.2em;text-transform:uppercase;color:var(--or);font-weight:700}
.wk-date .dn{font-size:150px;line-height:.8;color:var(--ink)}
.wk-date .dm{font-size:40px;line-height:.9;color:var(--mut)}
.wk-day.is-cb .wk-date .dw{color:var(--cb2)}
.wk-evs{display:flex;flex-direction:column;gap:20px;min-width:0}
.wk-ev{display:grid;grid-template-columns:minmax(0,11fr) minmax(0,10fr);background:var(--card);border:1px solid var(--line);border-top:3px solid var(--acc,var(--or));border-radius:6px;overflow:hidden;min-width:0;scroll-margin-top:24px}
.wk-ev.is-bk{--acc:var(--pk)}
.wk-ev.is-cb{--acc:var(--cb2)}
.wk-ev .ph{position:relative;min-height:340px;background:#15120F;overflow:hidden}
.wk-ev .ph img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;margin:0;border:0;max-width:none;transition:transform .8s cubic-bezier(.2,.6,.2,1)}
.wk-ev:hover .ph img{transform:scale(1.03)}
.wk-ev .ph .cap{position:absolute;left:12px;bottom:12px;padding:5px 9px;border-radius:3px;background:rgba(12,11,10,.74);font-size:11.5px;color:#E6DFD4;line-height:1.35}
.wk-ev .tx{display:flex;flex-direction:column;gap:12px;padding:30px 32px 32px;min-width:0}
.wk-ev .tx .top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.wk-ev .tx .eyebrow{color:var(--acc,var(--or))}
.wk-ev h3{font-size:56px;line-height:.9}
.wk-ev .where{font-size:15px;font-weight:600;color:var(--ink);line-height:1.45}
.wk-ev .where .tbc{display:inline-block;margin-left:8px;font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--acc,var(--or));font-weight:700;vertical-align:1px}
.wk-ev .x{font-size:15px;line-height:1.55;color:var(--mut)}
.wk-ev .facts{display:flex;flex-wrap:wrap;gap:8px}
.wk-ev .facts li{font-size:12.5px;font-weight:600;color:#D9D0C4;padding:6px 10px;border:1px solid #3A332D;border-radius:999px;line-height:1.2}
.wk-ev .go{margin-top:auto;padding-top:6px;color:var(--acc,var(--or))}
.wk-ev .go:hover{color:#FFFFFF}
.wk-ev .soon{margin-top:auto;padding-top:6px;font-size:14px;font-weight:600;color:var(--dim)}
.wk-ev.flip{grid-template-columns:minmax(0,10fr) minmax(0,11fr)}
.wk-ev.flip .ph{order:2}
.wk-ev.is-cb .ph{background:#03141E}
.wk-ev .ph .wk-cbp img{position:static;inset:auto;width:100%;max-width:300px;height:auto;object-fit:contain;transform:none}

/* The awards */
.wk-aw{overflow:hidden;background:linear-gradient(180deg,#14110F 0%,#1A1613 30%,#1A1613 100%)}
.wk-aw-bg{position:absolute;top:0;right:0;width:62%;height:640px;overflow:hidden;opacity:.5;pointer-events:none;-webkit-mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.6) 40%,#000 70%),linear-gradient(0deg,rgba(0,0,0,0) 0%,#000 60%);-webkit-mask-composite:source-in;mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.6) 40%,#000 70%),linear-gradient(0deg,rgba(0,0,0,0) 0%,#000 60%);mask-composite:intersect}
.wk-aw-bg img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;margin:0;border:0;max-width:none;animation:wk-x6 48s linear infinite both}
.wk-aw-bg img:first-child{opacity:1}
@keyframes wk-x6{0%{opacity:0;transform:scale(1.07)}3.333%{opacity:1}16.667%{opacity:1}20%{opacity:0;transform:scale(1)}100%{opacity:0;transform:scale(1)}}
.wk-aw .wrap{position:relative}
.wk-aw .sec-head h2{max-width:13ch}
.wk-nums{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;border-top:1px solid var(--bline);border-bottom:1px solid var(--bline);margin-bottom:28px}
.wk-nums > div{display:flex;flex-direction:column;gap:6px;padding:22px 24px 22px 0;min-width:0}
.wk-nums > div + div{padding-left:24px;border-left:1px solid var(--bline)}
.wk-nums .n{font-size:84px;line-height:.85;color:var(--or)}
.wk-nums .l{font-size:13px;color:var(--mut);line-height:1.4;max-width:22ch}
.wk-new{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px}
.wk-new .lead{grid-column:span 6;grid-row:span 2;background:var(--or);color:var(--ori);border-radius:6px;padding:30px;display:flex;flex-direction:column;gap:12px}
.wk-new .lead .tag{background:var(--ori);color:var(--or);align-self:flex-start}
.wk-new .lead .disp{font-size:52px;color:var(--ori)}
.wk-new .lead p{font-size:15px;line-height:1.55;color:var(--ori);max-width:54ch;margin-top:auto}
.wk-new .card{grid-column:span 3;padding:22px;display:flex;flex-direction:column;gap:8px;min-width:0}
.wk-new .card .disp{font-size:30px}
.wk-new .card p{font-size:13px;line-height:1.5;color:var(--mut)}
.wk-remind{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:24px 40px;align-items:center;margin-top:28px;padding:28px 32px;border:1px solid rgba(248,147,31,.55);border-radius:8px;background:linear-gradient(120deg,rgba(248,147,31,.13),rgba(248,147,31,.03))}
.wk-remind .l{display:flex;flex-direction:column;gap:8px;min-width:0}
.wk-remind .row{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap}
.wk-remind .n{font-size:72px;line-height:.85;color:var(--or);font-variant-numeric:tabular-nums}
.wk-remind .nt{font-size:15px;font-weight:600;color:var(--ink)}
.wk-remind p{font-size:14px;line-height:1.5;color:var(--mut);max-width:52ch}
.wk-remind .open{display:flex;flex-direction:column;align-items:flex-start;gap:12px}
.wk-form{display:flex;gap:10px;flex-wrap:wrap;position:relative;min-width:0}
.wk-form .in{flex:1 1 240px;min-width:0;min-height:52px;height:52px;padding:0 16px;border:1px solid #4A4038;border-radius:4px;background:#0C0B0A;color:var(--ink);font-family:inherit;font-size:16px;font-weight:500}
.wk-form .in::placeholder{color:#7D7368}
.wk-form .in:focus{outline:2px solid var(--or);outline-offset:1px}
.wk-form .hp{position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden}
.wk-msg{flex-basis:100%;font-size:13.5px;line-height:1.45;color:var(--mut);min-height:1.2em}
.wk-msg.is-ok{color:var(--or)}.wk-msg.is-err{color:#FFB4A0}
.wk .wk-season{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-top:40px}
.wk-tl{display:flex;flex-direction:column;gap:6px;padding:16px 4px 0 0;border-top:2px solid #463D35;min-width:0}
.wk-tl.now{border-top-color:var(--or)}
.wk-tl.end{border-top-color:var(--or)}
.wk-tl .w{font-size:11.5px;letter-spacing:.12em;text-transform:uppercase;font-weight:700;color:var(--mut)}
.wk-tl.now .w,.wk-tl.end .w{color:var(--or)}
.wk-tl .t{font-size:26px}
.wk-tl .x{font-size:13px;color:var(--mut);line-height:1.45}
.wk-spons{display:flex;gap:16px 24px;align-items:flex-end;flex-wrap:wrap;margin-top:48px}
.wk-spons .foty{display:flex;flex-direction:column;gap:12px;flex:0 0 250px;min-width:0}
.wk-spons .cats{display:flex;flex-direction:column;gap:12px;flex:1 1 520px;min-width:0}
.wk-spons .grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px}
.wk .sponsor{display:flex;align-items:center;justify-content:center;height:72px;border:1px dashed #4A4038;border-radius:4px;color:#8F857A;font-size:11px;letter-spacing:.1em;text-transform:uppercase;text-decoration:none;background:rgba(12,11,10,.28);text-align:center}
.wk .sponsor.has-logo{border:1px solid var(--bline);background:#0C0B0A;padding:12px 18px}
.wk .sponsor img{max-height:100%;max-width:100%;width:auto;height:auto;object-fit:contain;margin:0;border:0}
.wk .sponsor.pp-tile{border-color:#5A4E43;padding:12px 26px}

/* The night */
.wk-night{position:relative;min-height:600px;display:flex;align-items:flex-end;overflow:hidden;background:#050505}
.wk-night .ph{position:absolute;top:0;right:0;bottom:0;width:70%;-webkit-mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.5) 22%,#000 50%);mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.5) 22%,#000 50%)}
.wk-night .ph img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;margin:0;border:0;max-width:none;opacity:.9}
.wk-night .veil{position:absolute;inset:0;background:linear-gradient(90deg,rgba(5,5,5,.6) 0%,rgba(5,5,5,.2) 45%,rgba(5,5,5,0) 70%),linear-gradient(0deg,rgba(5,5,5,.7) 0%,rgba(5,5,5,0) 40%)}
.wk-night .in{position:relative;z-index:2;padding-top:96px;padding-bottom:80px;width:100%}
.wk-night h2{font-size:96px;max-width:11ch;margin-top:14px}
.wk-night p{font-size:18px;line-height:1.55;color:#D9D0C4;max-width:44ch;margin-top:20px}
.wk-night .ctas{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}
.wk-night .cap{position:absolute;right:max(40px,calc((100% - 1280px) / 2 + 40px));bottom:24px;z-index:3;font-size:12px;color:#D9D0C4;background:rgba(5,5,5,.7);padding:6px 10px;border-radius:3px}

/* Sponsors and exhibitors */
.wk-ppf{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);border:1px solid var(--line);border-radius:6px;overflow:hidden;background:var(--card);margin-bottom:72px}
.wk a.wk-ppf-logo{display:flex;align-items:center;justify-content:center;padding:48px 40px;background:#050505;border-right:1px solid var(--line);min-height:220px}
.wk-ppf-logo img{display:block;width:100%;max-width:320px;height:auto;margin:0;border:0;transition:transform .5s cubic-bezier(.2,.6,.2,1)}
.wk a.wk-ppf-logo:hover img{transform:scale(1.03)}
.wk-ppf-t{display:flex;flex-direction:column;gap:14px;padding:36px 40px;justify-content:center;min-width:0}
.wk-ppf-t .disp{font-size:48px}
.wk-ppf-t p{font-size:15px;line-height:1.6;color:#C9BFB2;max-width:56ch}
.wk-sp{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.wk-spc{display:flex;flex-direction:column;gap:12px;padding:28px;min-width:0;border-top:3px solid var(--acc,var(--or));color:var(--ink)}
.wk a.wk-spc,.wk a.wk-spc:hover{color:var(--ink)}
.wk a.wk-spc:hover{background:#1E1915}
.wk-spc.is-bk{--acc:var(--pk)}
.wk-spc .eyebrow{color:var(--acc,var(--or))}
.wk-spc .t{font-size:44px}
.wk-spc p{font-size:14.5px;line-height:1.55;color:var(--mut)}
.wk-spc .pr{font-size:15px;font-weight:700;color:var(--ink)}
.wk-spc .go{margin-top:auto;padding-top:6px;color:var(--acc,var(--or))}
.wk .wk-guide{margin-top:20px;font-size:14px;color:var(--mut)}

/* Where it happens */
.wk-venues{display:grid;grid-template-columns:repeat(var(--nv,3),minmax(0,1fr));gap:16px}
.wk-vc{display:flex;flex-direction:column;gap:10px;padding:26px;min-width:0}
.wk-vc .what{display:flex;flex-direction:column;gap:2px}
.wk-vc .what span{font-size:11.5px;letter-spacing:.12em;text-transform:uppercase;font-weight:700;color:var(--acc,var(--or))}
.wk-vc.is-bk{--acc:var(--pk)}.wk-vc.is-cb{--acc:var(--cb2)}
.wk-vc .t{font-size:34px}
.wk-vc .adr{font-size:14px;line-height:1.5;color:var(--mut)}
.wk-vc .tbc{font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--acc,var(--or));font-weight:700}
.wk-vc .go{margin-top:auto;padding-top:6px;color:var(--acc,var(--or))}

/* Updates */
.wk-upd{background:radial-gradient(70% 90% at 100% 0%,rgba(248,147,31,.16),rgba(248,147,31,0) 60%),#1A1613}
.wk-upd-in{display:grid;grid-template-columns:minmax(0,6fr) minmax(0,6fr);gap:32px 56px;align-items:center}
.wk-upd-in h2{font-size:80px;margin-top:10px}
.wk-upd-in p{font-size:16px;line-height:1.55;color:var(--mut);max-width:48ch;margin-top:16px}

/* Footer */
.wk .wk-foot{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;padding-top:28px;padding-bottom:48px;font-size:13px;color:var(--dim)}
.wk-foot nav{display:flex;gap:20px;flex-wrap:wrap}
.wk-foot nav a{color:var(--mut)}.wk-foot nav a:hover{color:#FFFFFF}

/* Staff bar (preview people only, never on the public page) */
.wk .wk-staff{display:flex;align-items:center;gap:10px 14px;flex-wrap:wrap;margin:16px 0 0;padding:12px 16px;border:1px dashed #D99A21;border-radius:6px;background:rgba(217,154,33,.08);font-size:13px;color:#E6DFD4}
.wk .wk-staff-tag{font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#1A1208;background:#F0C36A;padding:4px 8px;border-radius:3px}
.wk .wk-staff-text{flex:1 1 320px;min-width:0;line-height:1.45}
.wk .wk-staff-status{font-weight:600}.wk .wk-staff-status.is-warn{color:#F0C36A}
.wk .wk-staff-note{color:#B4A99A}
.wk .wk-staff-btns{display:flex;gap:8px;flex-wrap:wrap}
.wk .wk-staff .wk-sbtn{display:inline-flex;align-items:center;min-height:40px;padding:0 14px;border-radius:4px;border:1px solid #F0C36A;background:#F0C36A;color:#1A1208;font-size:13px;font-weight:600}
.wk .wk-staff .wk-sbtn.is-ghost{background:transparent;color:#F4EFE7;border-color:#3A332D}
.wk .wk-staff .wk-sbtn.is-ghost:hover{border-color:#F4EFE7}

/* Shorter laptop screens: a shorter strip of events, so the headline can stay big. */
@media (max-height:860px) and (min-width:701px){
.wk-tri{min-height:240px}
.wk-h1{font-size:clamp(88px,min(calc((min(100vw,1280px) - 80px) / 5.85),calc((100vh - 590px) / 1.68)),196px);font-size:clamp(88px,min(calc((min(100vw,1280px) - 80px) / 5.85),calc((100svh - 590px) / 1.68)),196px)}
.wk-col-l .t{font-size:34px}
}
@media (prefers-reduced-motion:reduce){.wk .ld,.wk .sr,.wk-glow{animation:none}.wk .wk-col-ph img,.wk .wk-aw-bg img{animation:none}.wk-ev .ph img,.wk-col-ph img{transition:none}}

@media (max-width:1100px){
.wk-now{grid-template-columns:repeat(2,minmax(0,1fr))}
.wk-day{grid-template-columns:140px minmax(0,1fr);gap:0 28px}
.wk-date .dn{font-size:112px}.wk-date .dm{font-size:32px}
.wk-ev,.wk-ev.flip{grid-template-columns:1fr}
.wk-ev.flip .ph{order:0}
.wk-ev .ph{min-height:0;aspect-ratio:16/9}
.wk-new .lead{grid-column:span 12;grid-row:auto}
.wk-new .card{grid-column:span 6}
.wk-season{grid-template-columns:repeat(3,minmax(0,1fr));row-gap:24px}
.wk-col-l .t{font-size:32px}
}
@media (max-width:900px){
.wk .wrap{padding-left:32px;padding-right:32px}
.wk .wk-gap{margin-top:80px}
.wk .sec-head h2{font-size:60px}
.wk-meta{grid-template-columns:1fr;gap:24px}
.wk-meta-r{align-items:flex-start}
.wk-ctas{justify-content:flex-start}
.wk-nums{grid-template-columns:repeat(2,minmax(0,1fr))}
.wk-nums > div:nth-child(3){padding-left:0;border-left:0}
.wk-nums > div:nth-child(n+3){border-top:1px solid var(--bline)}
.wk-remind{grid-template-columns:1fr}
.wk-night h2{font-size:72px}
.wk-ppf{grid-template-columns:1fr}
.wk a.wk-ppf-logo{border-right:0;border-bottom:1px solid var(--line);min-height:0;padding:40px 32px}
.wk-sp{grid-template-columns:1fr}
.wk-venues{grid-template-columns:1fr}
.wk-upd-in{grid-template-columns:1fr}
.wk .band{margin-top:80px;padding:64px 0}
.wk-spons .grid{grid-template-columns:repeat(3,minmax(0,1fr))}
.wk-aw-bg{width:100%;opacity:.28}
}
/* Phones */
@media (max-width:700px){
.wk{font-size:15px}
.wk .wrap{padding-left:20px;padding-right:20px}
.wk-head-bar{position:sticky;top:0;z-index:20;background:rgba(12,11,10,.9);-webkit-backdrop-filter:blur(14px);backdrop-filter:blur(14px);border-bottom:1px solid rgba(244,239,231,.08)}
.wk-head{padding:12px 0;border-bottom:0}
.wk-head .back,.wk-head .btn-ghost{display:none}
.wk-head .logo img{height:34px}
.wk-head .btn{min-height:44px;padding:0 18px;font-size:14.5px;border-radius:999px;font-weight:700}
.wk-top{padding-top:0;display:block;min-height:0}
.wk .wk-hero{padding-top:26px;padding-bottom:22px}
.wk-hero .eyebrow{font-size:11px;letter-spacing:.12em}
.wk-h1{font-size:calc((100vw - 40px) / 5.85);margin-top:14px}
.wk-dates{font-size:34px}
.wk .wk-lead{font-size:16.5px;font-weight:500;color:#D9D0C4;margin-top:12px}
.wk-meta{margin-top:22px;gap:20px}
.wk-ctas{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;width:100%}
.wk-ctas .btn{min-height:54px;padding:0 10px;font-size:15px;font-weight:700;border-radius:6px;white-space:normal;text-align:center;line-height:1.2}
.wk-count .n{font-size:52px}.wk-count .nt{font-size:14px;font-weight:600}
.wk .pp{gap:12px;margin-top:16px}.wk .pp-l{font-size:10px;padding-right:12px;line-height:26px}.wk .pp img{height:28px}
.wk-tri{height:auto;min-height:0;max-height:none;grid-template-columns:repeat(2,minmax(0,1fr));grid-template-rows:auto;gap:3px;margin-top:0}
.wk-tri.n3 .wk-col:first-child{grid-column:span 2}
.wk a.wk-col{height:200px}
.wk-tri.n3 .wk-col:first-child,.wk-tri.n1 .wk-col{height:230px}
.wk-col-l{padding:14px 14px 16px;gap:4px}
.wk-col-l .d{font-size:10.5px;letter-spacing:.12em}
.wk-col-l .t{font-size:26px}
.wk-col-l .v{display:none}
.wk-cbp{padding:0 16% 34%}
.wk .wk-now-w{margin-top:28px}
.wk-now{grid-template-columns:1fr;gap:10px}
.wk-st{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:4px 12px;padding:16px 18px;border-radius:12px}
.wk-st .top{grid-column:1 / -1}
.wk-st .v{font-size:26px}
.wk-st .s{grid-column:1 / 2;font-size:13.5px;font-weight:500;color:#C9BFB2}
.wk-st .go{grid-column:2;grid-row:2 / span 2;margin:0;padding:0;align-self:center;white-space:nowrap}
.wk .sec-head{display:block;margin-bottom:24px}
.wk .sec-head h2{font-size:52px !important}
.wk .sec-head p{font-size:16px;font-weight:500;color:#D9CDC2;margin-top:14px}
.wk .wk-prog{margin-top:72px}
.wk .wk-gap{margin-top:72px}
.wk-days{border-bottom:0}
.wk-day{display:block;padding:24px 0 8px}
.wk-date{position:static;flex-direction:row;align-items:baseline;gap:10px;margin-bottom:16px}
.wk-date .dw{order:0}
.wk-date .dn{font-size:56px;line-height:.9}
.wk-date .dm{font-size:28px}
.wk-evs{gap:14px}
.wk-ev{border-radius:12px}
.wk-ev .ph{aspect-ratio:16/10}
.wk-ev .tx{padding:20px 20px 22px;gap:10px}
.wk-ev h3{font-size:40px}
.wk-ev .x{font-size:14.5px;font-weight:500;color:#C9BFB2}
.wk-ev .go{min-height:40px}
.wk-nums .n{font-size:64px}
.wk-nums > div{padding:18px 16px 18px 0}
.wk-nums > div + div{padding-left:16px}
.wk-nums > div:nth-child(3){padding-left:0}
.wk-new{gap:12px}
.wk-new .lead{padding:22px;border-radius:12px}
.wk-new .lead .disp{font-size:38px}
.wk-new .card{grid-column:span 12;border-radius:12px;padding:20px}
.wk-remind{padding:22px 20px;border-radius:12px}
.wk-remind .n{font-size:60px}
.wk-form .btn{width:100%;min-height:54px;font-size:16px;font-weight:700;border-radius:6px}
.wk .wk-season{grid-template-columns:1fr;gap:0;margin-top:32px}
.wk-tl{display:grid;grid-template-columns:96px minmax(0,1fr);gap:2px 14px;padding:14px 0;border-top-width:1px}
.wk-tl .w{grid-row:span 2;padding-top:4px}
.wk-tl .t{font-size:24px}
.wk-spons .foty{flex:1 1 100%}
.wk-spons .grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.wk-night{min-height:0}
.wk-night .ph{position:relative;inset:auto;width:100%;height:280px;-webkit-mask-image:none;mask-image:none}
.wk-night .ph img{opacity:1}
.wk-night{flex-direction:column;align-items:stretch}
.wk-night .veil{background:linear-gradient(180deg,rgba(5,5,5,0) 0%,rgba(5,5,5,0) 170px,rgba(5,5,5,.85) 260px,#050505 300px)}
.wk-night .in{margin-top:-60px;padding-top:0;padding-bottom:56px}
.wk-night h2{font-size:56px}
.wk-night p{font-size:16px}
.wk-night .ctas .btn{width:100%;min-height:54px;font-size:16px;font-weight:700;border-radius:6px}
.wk-night .cap{top:12px;bottom:auto;right:12px;font-size:11px}
.wk-ppf{border-radius:12px;margin-bottom:56px}
.wk-ppf-t{padding:24px 20px}.wk-ppf-t .disp{font-size:34px}
.wk-spc{padding:22px 20px;border-radius:12px}
.wk-spc .t{font-size:36px}
.wk-vc{padding:20px;border-radius:12px}
.wk-vc .t{font-size:30px}
.wk-upd-in h2{font-size:56px}
.wk .band{margin-top:64px;padding:56px 0}
.wk-foot{flex-direction:column;gap:14px;border-top:1px solid rgba(244,239,231,.08);margin-top:8px}
.wk-foot nav{flex-direction:column;gap:0}
.wk-foot nav a{display:flex;align-items:center;min-height:40px;font-weight:600;color:#C9BFB2}
}
@media (max-width:380px){
.wk-ctas{grid-template-columns:1fr}
.wk-col-l .t{font-size:22px}
}
</style>

<div class="wk" id="wk-main">

<div class="wk-head-bar">
<div class="wrap">
<header class="wk-head ld d1">
  <a class="back" href="https://www.atfa.com.au/"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>atfa.com.au</a>
  <a class="logo" href="https://www.atfa.com.au/"><?php echo $logo ? '<img src="' . esc_url( $logo ) . '" alt="ATFA Australasian Timber Flooring Association" width="200" height="46" data-no-lazy="1">' : '<span class="logo-text">ATFA</span>'; ?></a>
  <div class="btns">
    <a class="btn btn-ghost" href="https://www.atfa.com.au/my-membership/">Member login</a>
    <a class="btn btn-primary" href="#program">What's on</a>
  </div>
</header>
</div>
</div>

<section class="wk-top" aria-labelledby="wk-h1">
  <div class="wk-glow" aria-hidden="true"></div>
  <div class="wrap wk-hero">
    <span class="eyebrow ld d2"><span class="or">The ATFA Flooring Industry Weekend</span> · <?php echo esc_html( $info['place'] ); ?></span>
    <h1 class="disp wk-h1 ld d3" id="wk-h1"><span class="l1"><?php echo $mark ? '<img class="wm" src="' . esc_url( $mark ) . '" alt="ATFA" data-no-lazy="1">' : 'ATFA '; ?>Industry</span><span class="l2">Weekend <span class="yr">2027</span></span></h1>
    <div class="wk-meta">
      <div class="wk-meta-l ld d4">
        <div class="wk-dline">
          <span class="disp wk-dates"><?php echo esc_html( $range ); ?></span>
<?php if ( $partner ) : ?>
          <?php echo atfa_awards_partner_lockup( $partner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif; ?>
        </div>
        <p class="wk-lead"><?php echo esc_html( $lead ); ?></p>
      </div>
      <div class="wk-meta-r ld d5">
        <div class="wk-ctas">
          <a class="btn btn-primary" href="#program">See what's on</a>
          <a class="btn btn-ghost" href="<?php echo esc_url( $cal_url ); ?>" rel="nofollow"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>Add to my calendar</a>
        </div>
<?php if ( $days_out > 0 ) : ?>
        <div class="wk-count" data-role="count"><span class="disp n" data-days="<?php echo esc_attr( $start ); ?>T00:00:00+10:00"><?php echo (int) $days_out; ?></span><span class="nt">days to go</span></div>
<?php endif; ?>
      </div>
    </div>
  </div>
  <nav class="wk-tri n<?php echo (int) $n_tri; ?> ld d6" style="--n:<?php echo (int) $n_tri; ?>" aria-label="The events">
<?php foreach ( $tri as $col => $ev ) : ?>
<?php
	$cls = 'wk-col' . ( 'breakfast' === $ev['key'] ? ' is-bk' : '' ) . ( 'crosbe' === $ev['key'] ? ' is-cb' : '' );
	$set = isset( $sets[ $ev['key'] ] ) ? $sets[ $ev['key'] ] : array();
	$n   = count( $set );
	?>
    <a class="<?php echo esc_attr( $cls ); ?>" href="#ev-<?php echo esc_attr( $ev['key'] ); ?>">
      <span class="wk-col-bar" aria-hidden="true"></span>
<?php if ( 'crosbe' === $ev['key'] && $partner ) : ?>
      <span class="wk-cbp" aria-hidden="true"><?php echo atfa_awards_partner_img( $partner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
<?php elseif ( $n ) : ?>
      <span class="wk-col-ph<?php echo $n > 1 ? ' n' . (int) min( 3, $n ) : ''; ?>" aria-hidden="true">
<?php
	$i = 0;
	foreach ( array_slice( $set, 0, 3 ) as $p ) {
		$delay = ( $i * 7 ) - 2 - $col * 1.5;
		echo $photo( $p['src'], array( 'alt' => '', 'sizes' => '(max-width: 700px) 50vw, 34vw', 'style' => 'object-position:' . $p['pos'] . ( $n > 1 ? ';animation-delay:' . $delay . 's' : '' ), 'first' => 0 === $i, 'lazy' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$i++;
	}
	?>
      </span>
<?php endif; ?>
      <span class="wk-col-l"><span class="d"><?php echo esc_html( $ev['short'] . ( '' !== $ev['part'] && 'All day' !== $ev['part'] ? ' · ' . strtolower( $ev['part'] ) : '' ) ); ?></span><span class="t"><?php echo esc_html( $ev['title'] ); ?></span><span class="v"><?php echo esc_html( $ev['venue'] ); ?><?php echo $ev['proposed'] ? '<span class="tag tag-prop">Proposed</span>' : ''; ?></span></span>
    </a>
<?php endforeach; ?>
  </nav>
</section>

<section class="wrap sr wk-now-w" aria-label="Right now">
  <div class="wk-now">
<?php
$stands = (int) $expo['stands'] . ' stands from $' . number_format( (int) $expo['from'] ) . ' ' . $expo['note'];
?>
    <a class="wk-st" href="<?php echo esc_url( $links['expo'] . '#floor-plan' ); ?>"><span class="top"><span class="wk-dot is-on"></span><span class="k">Expo stands</span></span><span class="disp v">On sale now</span><span class="s"><?php echo esc_html( $stands ); ?></span><span class="go">Pick a stand<?php echo $arrow; // phpcs:ignore ?></span></a>
<?php if ( $live ) : ?>
    <a class="wk-st" href="<?php echo esc_url( home_url( '/awards/' ) ); ?>"><span class="top"><span class="wk-dot is-on"></span><span class="k">Awards entries</span></span><span class="disp v">Open now</span><span class="s">Close Wednesday 31 March</span><span class="go">Enter now<?php echo $arrow; // phpcs:ignore ?></span></a>
<?php else : ?>
    <a class="wk-st" href="#remind"><span class="top"><span class="wk-dot"></span><span class="k">Awards entries</span></span><span class="disp v">Open Mon 1 Feb</span><span class="s">Twenty awards, three ways to win</span><span class="go">Get a reminder<?php echo $arrow; // phpcs:ignore ?></span></a>
<?php endif; ?>
<?php if ( isset( $events['breakfast'] ) && '' !== $links['breakfast'] && $bk && ! empty( $bk['rsvp']['open'] ) ) : ?>
    <a class="wk-st" href="<?php echo esc_url( $links['breakfast'] . '#rsvp' ); ?>"><span class="top"><span class="wk-dot is-on"></span><span class="k">Ladies Breakfast</span></span><span class="disp v">Seats open</span><span class="s">Complimentary, places limited</span><span class="go">Reserve a seat<?php echo $arrow; // phpcs:ignore ?></span></a>
<?php else : ?>
    <a class="wk-st" href="#updates"><span class="top"><span class="wk-dot"></span><span class="k">Ladies Breakfast</span></span><span class="disp v">Seats soon</span><span class="s">Complimentary, places limited</span><span class="go is-quiet">Tell me when<?php echo $arrow; // phpcs:ignore ?></span></a>
<?php endif; ?>
<?php if ( '' !== $tickets ) : ?>
    <a class="wk-st" href="<?php echo esc_url( $tickets ); ?>"><span class="top"><span class="wk-dot is-on"></span><span class="k">Awards Dinner</span></span><span class="disp v">Tickets on sale</span><span class="s">Saturday 22 May, Novotel Sydney Olympic Park</span><span class="go">Book tickets<?php echo $arrow; // phpcs:ignore ?></span></a>
<?php else : ?>
    <a class="wk-st" href="#updates"><span class="top"><span class="wk-dot"></span><span class="k">Awards Dinner</span></span><span class="disp v">Tickets soon</span><span class="s">Saturday 22 May, Novotel Sydney Olympic Park</span><span class="go is-quiet">Tell me when<?php echo $arrow; // phpcs:ignore ?></span></a>
<?php endif; ?>
  </div>
  <?php echo atfa_wk_staff_bar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>

<section class="wrap sec wk-prog" id="program" aria-labelledby="wk-prog-h">
  <div class="sec-head sr">
    <h2 class="disp" id="wk-prog-h">What's on</h2>
    <p><?php echo esc_html( ucfirst( $count['events'] ) . ' events over ' . $count['days'] . ' days' . ( isset( $events['crosbe'] ) ? ', three of them in Sydney Olympic Park' : ', all in Sydney Olympic Park' ) . '. Times are still being confirmed, and they go up here first.' ); ?></p>
  </div>
  <ol class="wk-days">
<?php
$k = 0;
foreach ( $days as $date => $list ) :
	$d0 = $list[0];
	?>
    <li class="wk-day sr<?php echo 'crosbe' === $d0['key'] ? ' is-cb' : ''; ?>">
      <div class="wk-date" aria-hidden="true"><span class="dw"><?php echo esc_html( gmdate( 'l', strtotime( $date . ' 12:00:00' ) ) ); ?></span><span class="disp dn"><?php echo esc_html( $d0['dn'] ); ?></span><span class="disp dm"><?php echo esc_html( $d0['dm'] ); ?></span></div>
      <div class="wk-evs">
<?php
	foreach ( $list as $ev ) :
		$cls = 'wk-ev' . ( $k % 2 ? ' flip' : '' ) . ( 'breakfast' === $ev['key'] ? ' is-bk' : '' ) . ( 'crosbe' === $ev['key'] ? ' is-cb' : '' );
		$k++;
		$p   = isset( $card_ph[ $ev['key'] ] ) ? $card_ph[ $ev['key'] ] : null;
		?>
        <article class="<?php echo esc_attr( $cls ); ?>" id="ev-<?php echo esc_attr( $ev['key'] ); ?>" aria-labelledby="ev-<?php echo esc_attr( $ev['key'] ); ?>-h">
          <div class="ph">
<?php if ( 'crosbe' === $ev['key'] && $partner ) : ?>
            <span class="wk-cbp" aria-hidden="true"><?php echo atfa_awards_partner_img( $partner, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
<?php elseif ( $p ) : ?>
            <?php echo $photo( $p['src'], array( 'alt' => '', 'sizes' => '(max-width: 1100px) 100vw, 560px', 'style' => 'object-position:' . $p['pos'], 'lazy' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <span class="cap"><?php echo esc_html( 'expo' === $ev['key'] ? 'ATFA Expo 2025, Melbourne' : ( 'breakfast' === $ev['key'] ? 'Women in Flooring Lunch, Rippon Lea, 2026' : '2025 awards dinner, Melbourne' ) ); ?></span>
<?php endif; ?>
          </div>
          <div class="tx">
            <div class="top"><span class="eyebrow"><?php echo esc_html( $ev['when'] ); ?></span><?php echo $ev['proposed'] ? '<span class="tag tag-prop">Proposed</span>' : ( '' !== $ev['kind'] ? '<span class="tag">' . esc_html( $ev['kind'] ) . '</span>' : '' ); ?></div>
            <h3 class="disp" id="ev-<?php echo esc_attr( $ev['key'] ); ?>-h"><?php echo esc_html( $ev['title'] ); ?></h3>
            <p class="where"><?php echo esc_html( $ev['venue'] . ( '' !== $ev['room'] ? ', ' . $ev['room'] : '' ) ); ?><?php echo $ev['venue_tbc'] ? '<span class="tbc">To be confirmed</span>' : ''; ?></p>
            <p class="x"><?php echo esc_html( $ev['text'] ); ?></p>
<?php
		$facts = $ev['facts'];
		if ( '' !== $ev['time_line'] ) {
			$facts[] = $ev['time_line'];
		}
		if ( $facts ) :
			?>
            <ul class="facts"><?php foreach ( $facts as $f ) { echo '<li>' . esc_html( $f ) . '</li>'; } ?></ul>
<?php endif; ?>
<?php if ( '' !== $ev['url'] && ! $ev['proposed'] ) : ?>
            <a class="go" href="<?php echo esc_url( $ev['url'] ); ?>"><?php echo esc_html( $ev['cta'] ); ?><?php echo $arrow; // phpcs:ignore ?></a>
<?php elseif ( '' !== $ev['soon'] ) : ?>
            <span class="soon"><?php echo esc_html( $ev['soon'] ); ?></span>
<?php endif; ?>
          </div>
        </article>
<?php endforeach; ?>
      </div>
    </li>
<?php endforeach; ?>
  </ol>
</section>

<section class="band wk-aw sec" id="awards" aria-labelledby="wk-aw-h">
  <div class="wk-aw-bg" aria-hidden="true">
<?php foreach ( $winners as $i => $w ) : ?>
    <img src="<?php echo esc_url( atfa_awards_img( $w['img'] ) ); ?>" alt="" loading="lazy" style="animation-delay:<?php echo (int) ( $i * 8 - 2 ); ?>s;object-position:<?php echo esc_attr( $w['pos'] ); ?>">
<?php endforeach; ?>
  </div>
  <div class="wrap">
    <div class="sec-head sr">
      <div>
        <span class="eyebrow or">Saturday night · ATFA Flooring Awards 2027</span>
        <h2 class="disp" id="wk-aw-h" style="margin-top:12px">Twenty awards. Three ways to win.</h2>
      </div>
      <p>Enter your best work from Monday 1 February. Members vote, the expert panel judges, and the winners are revealed at the Awards Dinner on Saturday 22 May.</p>
    </div>
    <div class="wk-nums sr">
      <div><span class="disp n"><?php echo (int) $streams['member']; ?></span><span class="l">Member voted project awards, with 3-2-1 voting</span></div>
      <div><span class="disp n"><?php echo (int) $streams['panel']; ?></span><span class="l">Expert panel project awards, new for 2027</span></div>
      <div><span class="disp n"><?php echo (int) $streams['excellence']; ?></span><span class="l">Excellence awards for people, businesses and products</span></div>
      <div><span class="disp n">+2</span><span class="l">The Newcomers Award and Floor of the Year</span></div>
    </div>
    <div class="wk-new sr">
      <div class="lead">
        <span class="tag">New for 2027</span>
        <span class="disp">Overseas and school projects can now be entered</span>
        <p>The three expert panel categories take projects outside Australia and New Zealand, and work in schools and learning environments. Disclose it clearly in your entry and the panel judges it on the same footing as everything else.</p>
      </div>
      <div class="card"><span class="eyebrow or">New</span><span class="disp">Expert panel judging</span><p>Recycled / Restoration, Sanded &amp; Finished and Technical System, judged on craft and difficulty by an appointed panel.</p></div>
      <div class="card"><span class="eyebrow or">New</span><span class="disp">3-2-1 member voting</span><p>Rank your top three in each member-voted category: 3 votes, 2 votes, 1 vote. One ballot per membership.</p></div>
      <div class="card"><span class="eyebrow or">New</span><span class="disp">Parquetry Floor</span><p>Parquetry gets its own member-voted category.</p></div>
      <div class="card"><span class="eyebrow or">New</span><span class="disp">Newcomers Award</span><p>For members in their first five years who have never won or placed. Tick the box on any member-voted entry.</p></div>
    </div>
<?php if ( $live ) : ?>
    <div class="wk-remind sr" id="remind">
      <div class="l"><span class="eyebrow or">Entries are open</span><span class="disp" style="font-size:48px">Enter from your phone in about ten minutes</span><p>Entries close Wednesday 31 March 2027, 11:59 pm AEDT. Open to current financial ATFA members.</p></div>
      <div class="open"><a class="btn btn-primary" href="<?php echo esc_url( home_url( '/awards/' ) ); ?>">Enter now</a></div>
    </div>
<?php else : ?>
    <div class="wk-remind sr" id="remind">
      <div class="l">
<?php if ( $days_ent > 0 ) : ?>
        <div class="row"><span class="disp n" data-days="<?php echo esc_attr( $entries ); ?>"><?php echo (int) $days_ent; ?></span><span class="nt">days until entries open</span></div>
<?php else : ?>
        <span class="disp" style="font-size:48px">Entries open soon</span>
<?php endif; ?>
        <p>Entries open Monday 1 February 2027 and close Wednesday 31 March. Open to current financial ATFA members. Enter from your phone, with photos straight from your camera roll.</p>
      </div>
      <form class="wk-form" data-role="notify" data-topic="awards" novalidate data-endpoint="<?php echo esc_url( $signup ); ?>">
        <label class="hp" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        <label for="wk-email-aw" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)">Email address</label>
        <input class="in" id="wk-email-aw" name="email" type="email" autocomplete="email" placeholder="you@yourbusiness.com.au" required>
        <button type="submit" class="btn btn-primary">Remind me when entries open</button>
        <p class="wk-msg" aria-live="polite"></p>
      </form>
    </div>
<?php endif; ?>
    <ol class="wk-season sr" aria-label="The road to the dinner">
<?php
$s_i = 0;
foreach ( (array) $info['season'] as $s ) :
	$s = array_merge( array( 'when' => '', 'title' => '', 'text' => '' ), (array) $s );
	?>
      <li class="wk-tl<?php echo 0 === $s_i ? ' now' : ''; ?>"><span class="w"><?php echo esc_html( $s['when'] ); ?></span><span class="disp t"><?php echo esc_html( $s['title'] ); ?></span><span class="x"><?php echo esc_html( $s['text'] ); ?></span></li>
<?php
	$s_i++;
endforeach;
?>
      <li class="wk-tl end"><span class="w"><?php echo esc_html( $range ); ?></span><span class="disp t">The weekend</span><span class="x"><?php echo esc_html( 'Expo, Ladies Breakfast and the Awards Dinner in Sydney.' ); ?></span></li>
    </ol>
    <div class="wk-spons sr">
<?php if ( $partner && ! empty( $cats['foty']['sponsor']['partner'] ) ) : ?>
      <div class="foty">
        <span class="eyebrow">Floor of the Year · proudly sponsored by</span>
        <a class="sponsor has-logo pp-tile" href="<?php echo esc_url( $partner['url'] ); ?>" target="_blank" rel="noopener sponsored"><?php echo atfa_awards_partner_img( $partner, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
      </div>
<?php endif; ?>
      <div class="cats">
        <span class="eyebrow">Category sponsors<?php echo function_exists( 'atfa_aws_awards_link' ) ? atfa_aws_awards_link() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
        <div class="grid">
<?php foreach ( atfa_awards_sponsors() as $sp ) : ?>
          <?php echo atfa_awards_sponsor_tile( $sp ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ( isset( $events['dinner'] ) ) : ?>
<section class="wk-night sec" id="the-night" aria-labelledby="wk-night-h">
<?php if ( $night ) : ?>
  <div class="ph" aria-hidden="true"><?php echo $photo( $night['src'], array( 'alt' => '', 'style' => 'object-position:' . $night['pos'], 'lazy' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
<?php endif; ?>
  <div class="veil" aria-hidden="true"></div>
  <div class="wrap in sr">
    <span class="eyebrow or"><?php echo esc_html( $events['dinner']['day'] . ' 2027 · ' . $events['dinner']['venue'] ); ?></span>
    <h2 class="disp" id="wk-night-h">The whole industry in one room</h2>
    <p>Dinner, drinks and the 2027 ATFA Flooring Awards on the Saturday night. Finalists are told first, so they can book their travel.</p>
<?php if ( $partner ) : ?>
    <?php echo atfa_awards_partner_lockup( $partner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif; ?>
    <div class="ctas">
<?php if ( '' !== $tickets ) : ?>
      <a class="btn btn-primary" href="<?php echo esc_url( $tickets ); ?>">Book dinner tickets</a>
<?php else : ?>
      <a class="btn btn-primary" href="#updates">Tell me when tickets go on sale</a>
<?php endif; ?>
      <a class="btn btn-ghost" href="#awards">The awards</a>
    </div>
  </div>
<?php if ( $night ) : ?>
  <span class="cap">Pictured: the 2025 awards dinner, Melbourne</span>
<?php endif; ?>
</section>
<?php endif; ?>

<section class="wrap sec wk-gap" id="partners" aria-labelledby="wk-sp-h">
<?php if ( $partner ) : ?>
  <div class="wk-ppf sr">
    <a class="wk-ppf-logo" href="<?php echo esc_url( $partner['url'] ); ?>" target="_blank" rel="noopener sponsored" aria-label="<?php echo esc_attr( $partner['name'] ); ?> website"><?php echo atfa_awards_partner_img( $partner, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
    <div class="wk-ppf-t">
      <span class="eyebrow or"><?php echo esc_html( $partner['role'] ); ?> · ATFA Flooring Industry Weekend 2027</span>
      <span class="disp">The 2027 weekend is presented by <?php echo esc_html( $partner['name'] ); ?></span>
      <p><?php echo esc_html( $partner['about'] ); ?></p>
      <a class="go" href="<?php echo esc_url( $partner['url'] ); ?>" target="_blank" rel="noopener sponsored">Visit <?php echo esc_html( preg_replace( '#^https?://(www\.)?|/$#', '', $partner['url'] ) ); ?><?php echo $arrow; // phpcs:ignore ?></a>
    </div>
  </div>
<?php endif; ?>
  <div class="sec-head sr">
    <div>
      <span class="eyebrow or">Exhibitors and sponsors</span>
      <h2 class="disp" id="wk-sp-h" style="margin-top:12px;max-width:13ch">Put your brand on the weekend</h2>
    </div>
    <p>The whole industry comes to Sydney for this weekend. Three ways to be part of it, from a stand in Hall 5 to your name on an award.</p>
  </div>
  <div class="wk-sp sr">
    <a class="card wk-spc" href="<?php echo esc_url( $links['expo'] . '#floor-plan' ); ?>">
      <span class="eyebrow">Expo &amp; Conference</span>
      <span class="disp t">Book a stand</span>
      <p><?php echo esc_html( (int) $expo['stands'] ); ?> stands in Hall 5 at Sydney Showground, from 3 × 3 m to 6 × 6 m. Pick yours on the live floor plan and the enquiry goes straight to the ATFA team.</p>
      <span class="pr">From $<?php echo esc_html( number_format( (int) $expo['from'] ) . ' ' . $expo['note'] ); ?></span>
      <span class="go">See the floor plan<?php echo $arrow; // phpcs:ignore ?></span>
    </a>
<?php if ( '' !== $aws_url ) : ?>
    <a class="card wk-spc" href="<?php echo esc_url( $aws_url ); ?>">
<?php else : ?>
    <a class="card wk-spc" href="<?php echo esc_url( 'mailto:' . $phil . '?subject=' . rawurlencode( '2027 Flooring Awards category sponsorship' ) ); ?>">
<?php endif; ?>
      <span class="eyebrow">Flooring Awards</span>
      <span class="disp t">Sponsor an award</span>
      <p>Put your name on one of the 2027 award categories, on its page, through the season and on the night it is presented. Offered to Platinum Members first.</p>
      <span class="go"><?php echo '' !== $aws_url ? 'See the categories' : 'Ask Phil about category sponsorship'; ?><?php echo $arrow; // phpcs:ignore ?></span>
    </a>
<?php if ( isset( $events['breakfast'] ) ) : ?>
<?php if ( '' !== $links['breakfast'] ) : ?>
    <a class="card wk-spc is-bk" href="<?php echo esc_url( $links['breakfast'] . '#sponsor' ); ?>">
<?php else : ?>
    <a class="card wk-spc is-bk" href="<?php echo esc_url( 'mailto:' . $phil . '?subject=' . rawurlencode( 'Ladies Breakfast 2027 sponsorship' ) ); ?>">
<?php endif; ?>
      <span class="eyebrow">Ladies Breakfast</span>
      <span class="disp t">Sponsor the breakfast</span>
      <p>One Gold naming sponsor and up to six Silver sponsors, in front of a room of the industry's women on the Saturday morning.</p>
      <span class="go"><?php echo '' !== $links['breakfast'] ? 'Packages and enquiries' : 'Ask Phil about the packages'; ?><?php echo $arrow; // phpcs:ignore ?></span>
    </a>
<?php endif; ?>
  </div>
  <p class="wk-guide sr">All of ATFA's 2027 sponsorship and advertising is in the <a href="https://epoxy.atfa.com.au/sponsorship">2027 sponsorship guide</a>.</p>
</section>

<section class="wrap sec wk-gap" id="venues" aria-labelledby="wk-v-h">
  <div class="sec-head sr">
    <h2 class="disp" id="wk-v-h">Where it all happens</h2>
    <p>Sydney Showground, the Pullman and the Novotel are all in Sydney Olympic Park, a short walk apart, with Olympic Park station in the middle of the precinct.<?php echo isset( $events['crosbe'] ) ? ' Thursday\'s open afternoon is at Crosbe in Seven Hills.' : ''; ?></p>
  </div>
  <div class="wk-venues sr" style="--nv:<?php echo (int) min( 4, max( 1, count( $venues ) ) ); ?>">
<?php foreach ( $venues as $v ) : ?>
<?php $ev = $v['ev']; ?>
    <div class="card wk-vc<?php echo 'breakfast' === $ev['key'] ? ' is-bk' : ''; ?><?php echo 'crosbe' === $ev['key'] ? ' is-cb' : ''; ?>">
      <div class="what"><?php foreach ( $v['what'] as $w ) { echo '<span>' . esc_html( $w ) . '</span>'; } ?></div>
      <span class="disp t"><?php echo esc_html( $ev['venue'] ); ?></span>
<?php if ( '' !== $ev['room'] ) : ?>
      <span class="adr" style="color:var(--ink);font-weight:600"><?php echo esc_html( $ev['room'] ); ?></span>
<?php endif; ?>
      <span class="adr"><?php echo esc_html( $ev['address'] ); ?></span>
<?php if ( $ev['venue_tbc'] || $ev['proposed'] ) : ?>
      <span class="tbc"><?php echo $ev['proposed'] ? 'Proposed' : 'To be confirmed'; ?></span>
<?php endif; ?>
<?php if ( '' !== $ev['map'] ) : ?>
      <a class="go" href="<?php echo esc_url( $ev['map'] ); ?>" target="_blank" rel="noopener">Directions<?php echo $arrow; // phpcs:ignore ?></a>
<?php endif; ?>
    </div>
<?php endforeach; ?>
  </div>
</section>

<section class="band wk-upd sec" id="updates" aria-labelledby="wk-u-h">
  <div class="wrap sr">
    <div class="wk-upd-in">
      <div>
        <span class="eyebrow or">Stay in the loop</span>
        <h2 class="disp" id="wk-u-h">Don't miss a date</h2>
        <p>We'll email you when awards entries open, when dinner tickets go on sale and when the times and program are confirmed.</p>
      </div>
      <form class="wk-form" data-role="notify" data-topic="weekend" novalidate data-endpoint="<?php echo esc_url( $signup ); ?>">
        <label class="hp" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        <label for="wk-email-up" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)">Email address</label>
        <input class="in" id="wk-email-up" name="email" type="email" autocomplete="email" placeholder="you@yourbusiness.com.au" required>
        <button type="submit" class="btn btn-primary">Keep me posted</button>
        <p class="wk-msg" aria-live="polite"></p>
      </form>
    </div>
  </div>
</section>

<footer class="wrap wk-foot">
  <nav aria-label="More from ATFA">
    <a href="https://www.atfa.com.au/">atfa.com.au</a>
    <a href="<?php echo esc_url( $links['expo'] ); ?>">Expo &amp; Conference 2027</a>
<?php if ( '' !== $links['breakfast'] ) : ?>
    <a href="<?php echo esc_url( $links['breakfast'] ); ?>">Ladies Breakfast 2027</a>
<?php endif; ?>
    <a href="#awards">Flooring Awards 2027</a>
    <a href="https://www.atfa.com.au/terms-conditions/">Awards entry terms &amp; conditions</a>
    <a href="https://www.atfa.com.au/contact/">Contact ATFA</a>
  </nav>
  <div>AU 1300 361 693 · NZ 0800 453 715 · © Australasian Timber Flooring Association</div>
</footer>

</div>
<script>
(function(){
  var root=document.getElementById('wk-main'); if(!root) return;
  var qa=function(sel,el){return [].slice.call((el||root).querySelectorAll(sel))};

  // Days to go, worked out on the day (the page itself may be a cached copy).
  qa('[data-days]').forEach(function(el){
    var t=new Date(el.getAttribute('data-days')).getTime(), d=Math.ceil((t-Date.now())/86400000);
    if(isNaN(t)) return;
    if(d>0){ el.textContent=d; var nt=el.nextElementSibling; if(nt&&d===1){ nt.textContent=nt.textContent.replace(/^days/,'day'); } }
    else { var box=el.closest('[data-role="count"]')||el.parentNode; if(box) box.style.display='none'; }
  });

  // The two sign-up forms: same list as the awards notify-me, with a note of which one it came from.
  var mail=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  qa('form[data-role="notify"]').forEach(function(f){
    var btn=f.querySelector('button[type="submit"]'), msg=f.querySelector('.wk-msg'), em=f.querySelector('input[name="email"]'), label=btn.textContent;
    function say(t,cls){ msg.textContent=t; msg.className='wk-msg'+(cls?' '+cls:''); }
    f.addEventListener('submit',function(e){
      e.preventDefault();
      var v=em.value.trim();
      if(!mail.test(v)){ say('Please enter a valid email address.','is-err'); em.focus(); return; }
      btn.disabled=true; btn.textContent='Sending'; say('');
      fetch(f.getAttribute('data-endpoint'),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email:v,page:location.href.split('#')[0],topic:f.getAttribute('data-topic'),website:f.website?f.website.value:''})})
        .then(function(r){ return r.json(); })
        .then(function(j){
          if(j&&j.ok){ say(j.message||'Thanks, you are on the list.','is-ok'); btn.textContent='You are on the list'; em.value=''; }
          else { btn.disabled=false; btn.textContent=label; say((j&&j.message)||'Please check the email address and try again.','is-err'); }
        })
        .catch(function(){ btn.disabled=false; btn.textContent=label; say('Something went wrong. Email admin@atfa.com.au and we will add you.','is-err'); });
    });
  });
})();
</script>
