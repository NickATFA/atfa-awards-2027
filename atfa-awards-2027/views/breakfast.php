<?php
/**
 * Ladies Breakfast 2027: the blackout page with the pink of the Women's Lunch, the photos from Rippon Lea and
 * Melbourne, and the seat reservation form. Rendered by [atfa_awards_breakfast]. Details come from
 * data/breakfast.php (atfa_bk_info()).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$info    = atfa_bk_info();
$venue   = $info['venue'];
$rsvp    = $info['rsvp'];
$logo    = atfa_awards_img( 'atfa-logo-white.png' );
$mark    = atfa_awards_img( 'atfa-wordmark.svg' );
$awards  = home_url( '/awards/' );
$expo    = home_url( '/expo-2027/' );
$sets    = atfa_bk_photos();
$tbc     = empty( $venue['confirmed'] );
$sponsors = $info['sponsors'];
$sp       = $info['sponsorship'];
$tiers    = $sp['tiers'];
$sp_open  = ! empty( $sp['open'] );
$sp_mail  = 'mailto:' . $sp['to'] . '?subject=' . rawurlencode( $info['title'] . ' sponsorship' );
$thanks   = $sponsors['gold'] || $sponsors['silver'];
$comp     = false !== stripos( $info['price']['text'], 'complimentary' );

// Every photo in page order, with the set it came from, for the gallery and the lightbox.
$all = array();
foreach ( $sets as $key => $set ) {
	foreach ( $set['ids'] as $id ) {
		$all[] = array( 'id' => $id, 'set' => $key, 'label' => $set['label'], 'short' => $set['short'] );
	}
}
$hero = array();
foreach ( $info['hero'] as $id ) {
	foreach ( $all as $p ) {
		if ( $p['id'] === $id ) {
			$hero[] = $p;
			break;
		}
	}
}
if ( ! $hero ) {
	$hero = array_slice( $all, 0, 4 );
}
$hero = array_slice( $hero, 0, 4 );
// The gallery leads with the newest set and shows a dozen before "Show all".
$first    = 12;
$total    = count( $all );
$when     = $info['date_text'] . ( '' !== $info['time_text'] ? ' · ' . $info['time_text'] : '' );
$where    = trim( $venue['name'] . ( '' !== $venue['room'] ? ', ' . $venue['room'] : '' ) );
$price    = $info['price'];
$open     = ! empty( $rsvp['open'] );
$mailto   = 'mailto:' . $rsvp['email'] . '?subject=' . rawurlencode( $info['title'] );
?>
<style>
/* Ladies Breakfast 2027. Same family as the awards and Expo pages (black, Barlow Condensed and Montserrat), with the
   pink of the Women's Lunch instead of the orange: the blush of the 2026 invitation (#FFEEF6) and the magenta of the
   2025 luncheon banner (#9C2A7A, sampled from the photos). The headline alone is set in Playfair Display, the way the
   2026 invitation sets LUNCH in a serif; everything else is the two typefaces of the other pages. */
html{background:#0C0B0A !important}body{background:#0C0B0A !important;margin:0 !important;padding:0 !important}
.bk{position:relative;min-height:100vh;box-sizing:border-box;background:#0C0B0A;color:#F7F1F4;font-family:'Montserrat',system-ui,sans-serif;font-size:16px;-webkit-font-smoothing:antialiased;line-height:1.4;overflow-x:clip;text-align:left;--pk:#FF9FC7;--pk2:#FFB9D7;--pki:#2B0F1D;--mg:#9C2A7A;--mg2:#C8328A;--blush:#FFEEF6;--mut:#B9ABB3;--dim:#8E8188;--card:#1A1316;--line:#352830;--band:#1A1316;--bcard:#221A1E;--bline:#3B2E35}
.bk *,.bk *:before,.bk *:after{box-sizing:border-box}
.bk a{color:var(--pk);text-decoration:none}.bk a:hover{color:var(--pk2)}
.bk p,.bk figure,.bk ol,.bk ul,.bk dl,.bk dd{margin:0}
.bk ol,.bk ul{padding:0;list-style:none}
.bk li{margin:0}
.bk h1,.bk h2,.bk h3{margin:0;color:#F7F1F4;font-weight:700}
.bk img{max-width:100%}
.bk button,.bk input,.bk textarea{font:inherit;color:inherit;text-transform:none;letter-spacing:0;box-shadow:none;margin:0;line-height:inherit;border-radius:0}
.bk .disp{font-family:'Barlow Condensed','Arial Narrow',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:.01em;line-height:.92;text-wrap:balance}
.bk .fancy{font-family:'Playfair Display',Georgia,'Times New Roman',serif;font-weight:700;text-transform:uppercase;letter-spacing:.01em;line-height:.95;font-variant-numeric:lining-nums}
.bk .eyebrow{display:block;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--mut);font-weight:600;line-height:1.35}
.bk .pk{color:var(--pk)}
.bk .small{color:var(--dim)}
.bk .btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:52px;padding:0 26px;border-radius:4px;font-weight:600;font-size:15px;text-decoration:none;border:1px solid transparent;cursor:pointer;font-family:inherit;white-space:nowrap;line-height:1;text-transform:none;letter-spacing:0;box-shadow:none;background:none}
.bk .btn-primary{background:var(--pk);color:var(--pki);border-color:var(--pk)}.bk .btn-primary:hover{background:var(--pk2);border-color:var(--pk2);color:var(--pki)}
.bk .btn-primary[disabled]{opacity:.7;cursor:default}
.bk .btn-ghost{background:transparent;color:#F7F1F4;border-color:#3F3238}.bk .btn-ghost:hover{color:#FFFFFF;border-color:#F7F1F4}
.bk a:focus-visible,.bk button:focus-visible{outline:2px solid var(--pk);outline-offset:2px}
.bk .wrap{max-width:1280px;margin:0 auto;padding:0 40px}
.bk .card{background:var(--card);border:1px solid var(--line);border-radius:6px}
.bk .tag{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:4px 8px;border-radius:3px;line-height:1.2}
.bk .tag-pk{background:var(--pk);color:var(--pki)}
.bk .tag-slate{background:#445152;color:var(--blush)}
.bk .sec-head{display:flex;justify-content:space-between;align-items:end;gap:24px;flex-wrap:wrap;margin-bottom:28px}
.bk .sec-head p{max-width:46ch;color:var(--mut);font-size:15px;line-height:1.5}
.bk .sec{scroll-margin-top:16px}
.bk .band{position:relative;margin-top:96px;padding:88px 0;background:var(--band);border-top:1px solid rgba(255,238,246,.08);border-bottom:1px solid rgba(255,238,246,.08)}
.bk .band-tight{padding:64px 0}
.bk .band .card{background:var(--bcard);border-color:var(--bline)}
@keyframes bk-rise{from{opacity:0;transform:translateY(26px)}to{opacity:1;transform:translateY(0)}}
.bk .ld{animation:bk-rise .9s cubic-bezier(.22,.61,.36,1) both}
.bk .ld.d1{animation-delay:.05s}.bk .ld.d2{animation-delay:.18s}.bk .ld.d3{animation-delay:.3s}.bk .ld.d4{animation-delay:.42s}.bk .ld.d5{animation-delay:.54s}.bk .ld.d6{animation-delay:.66s}
@supports (animation-timeline: view()){.bk .sr{animation:bk-rise linear both;animation-timeline:view();animation-range:entry 0% entry 32%}}

/* Header (over the hero on desktop, sticky on phones) */
.bk-head-bar{position:absolute;left:0;right:0;top:0;z-index:6}
.bk-head{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:22px 0;border-bottom:1px solid rgba(255,238,246,.12)}
.bk-head .back{display:inline-flex;align-items:center;gap:8px;color:var(--mut);font-size:13px;font-weight:600}
.bk-head .back:hover{color:#FFFFFF}
.bk-head .logo{display:block;line-height:0}
.bk-head .logo img{height:46px;width:auto;display:block}
.bk-head .logo-text{font-family:'Barlow Condensed','Arial Narrow',sans-serif;font-weight:700;font-size:30px;letter-spacing:.02em;color:#F7F1F4;line-height:1}
.bk-head .btns{display:flex;gap:12px;flex-wrap:wrap}

/* Top of the page: weekend bar and hero, with the photos fading behind */
.bk-top{position:relative;overflow:hidden;padding-top:91px}
.bk-media{position:absolute;inset:0;pointer-events:none}
.bk-bg{position:absolute;inset:0;overflow:hidden;isolation:isolate;--o:.9;opacity:var(--o);animation:bk-fadein 1.8s ease-out both}
.bk-bg img{position:absolute;top:0;right:0;width:70%;height:100%;object-fit:cover;object-position:50% 40%;opacity:0;transform-origin:60% 50%;margin:0;border:0;max-width:none;
  -webkit-mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.25) 20%,rgba(0,0,0,1) 50%);mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.25) 20%,rgba(0,0,0,1) 50%);
  animation:bk-show 32s linear infinite both,bk-kb 32s cubic-bezier(.2,.6,.2,1) infinite both}
.bk-bg img.lead{opacity:1}
.bk-veil{position:absolute;inset:0;background:linear-gradient(90deg,rgba(12,11,10,.86) 32%,rgba(12,11,10,.5) 50%,rgba(12,11,10,0) 66%),linear-gradient(0deg,#0C0B0A 0%,rgba(12,11,10,0) 26%),linear-gradient(180deg,rgba(12,11,10,.7) 0%,rgba(12,11,10,0) 20%)}
/* A soft blush behind the headline, the way the invitation fades from pink to white. */
.bk-glow{position:absolute;left:-12%;top:4%;width:64%;height:96%;background:radial-gradient(closest-side,rgba(156,42,122,.34),rgba(200,50,138,.12) 55%,rgba(156,42,122,0));pointer-events:none}
.bk-caps{position:absolute;z-index:3;right:max(40px,calc((100% - 1280px) / 2 + 40px));bottom:64px;width:420px;max-width:calc(100% - 80px);height:50px}
.bk-cap{position:absolute;right:0;bottom:0;width:max-content;max-width:100%;font-size:12px;color:#F7F1F4;background:rgba(12,11,10,.72);padding:7px 11px;border-radius:3px;line-height:1.45;opacity:0;animation:bk-showcap 32s linear infinite both;text-align:left}
.bk-cap strong{font-weight:600;color:#F7F1F4}
.bk-cap.lead{opacity:1}
@keyframes bk-show{0%{opacity:0;z-index:3}5%{opacity:1;z-index:3}5.1%{opacity:1;z-index:2}30%{opacity:1;z-index:2}30.1%{opacity:1;z-index:1}35%{opacity:0;z-index:1}100%{opacity:0;z-index:1}}
@keyframes bk-showcap{0%,2.5%{opacity:0}5%,27.5%{opacity:1}30%,100%{opacity:0}}
@keyframes bk-kb{0%{transform:scale(1.07)}35%{transform:scale(1)}100%{transform:scale(1)}}
@keyframes bk-fadein{from{opacity:0}to{opacity:var(--o)}}
.bk-top > .wrap{position:relative;z-index:2}
.bk-wk{display:flex;align-items:center;justify-content:space-between;gap:12px 24px;flex-wrap:wrap;padding:14px 0;border-bottom:1px solid rgba(255,238,246,.12)}
.bk-wk-l{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--mut);font-weight:600}
.bk-wk-links{display:flex;gap:8px;flex-wrap:wrap}
.bk a.bk-wk-a{display:inline-flex;align-items:center;gap:10px;min-height:44px;padding:0 16px;border:1px solid #3F3238;border-radius:999px;background:rgba(12,11,10,.55);color:#F7F1F4;font-size:14px;font-weight:600;white-space:nowrap}
.bk a.bk-wk-a:hover{color:#FFFFFF;border-color:#F7F1F4}
.bk-wk-a .d{font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--mut);font-weight:600}
.bk a.bk-wk-a.on{background:var(--pk);border-color:var(--pk);color:var(--pki)}.bk-wk-a.on .d{color:#6B2F4C}
.bk-hero{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px;align-items:end;padding:72px 0 64px}
.bk-hero-t{grid-column:span 9;min-width:0;display:flex;flex-direction:column}
/* The headline is sized to its longest line, BREAKFAST 2027 (8.45em in Playfair), in the 9-column hero */
.bk-h1{margin:16px 0 0;font-size:min(105px,calc(8.8vw - 8px))}
.bk-h1 .a{display:flex;align-items:flex-end;gap:.2em;white-space:nowrap}
.bk-h1 .wm{display:block;height:.82em;width:auto;margin:0 0 .06em}
.bk-h1 .b{display:block;padding-bottom:.08em;color:var(--blush);white-space:nowrap;text-shadow:0 0 70px rgba(200,50,138,.45)}
.bk-h1 .yr{color:var(--pk)}
.bk-lead{font-size:22px;max-width:40ch;line-height:1.4;color:var(--mut);margin:36px 0 0}
.bk-ctas{display:flex;gap:14px;flex-wrap:wrap;margin-top:32px}
.bk-note{font-size:13px;color:var(--mut);margin:18px 0 0;max-width:48ch;line-height:1.5}

/* Status card */
.bk-status{display:flex;align-items:center;justify-content:space-between;gap:12px 24px;padding:20px 28px;flex-wrap:wrap}
.bk-status .l{display:flex;align-items:center;gap:14px;min-width:0;flex-wrap:wrap}
.bk-dot{width:10px;height:10px;border-radius:50%;background:var(--pk);box-shadow:0 0 0 4px rgba(255,159,199,.2);flex-shrink:0}
.bk-dot.is-off{background:var(--dim);box-shadow:none}
.bk-status .t{font-size:28px;color:#F7F1F4}
.bk-status .w{color:var(--mut);font-size:15px}
.bk-status .r{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap}
.bk-status .n{font-size:44px;color:var(--pk);font-variant-numeric:tabular-nums}
.bk-status .nt{color:var(--mut);font-size:14px}

/* The morning: four cards */
.bk-cards{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px}
.bk-c{display:flex;flex-direction:column;gap:8px;padding:24px;min-width:0}
.bk-c .t{font-size:32px}
.bk-c p{color:var(--mut);font-size:14px;line-height:1.55}
.bk .bk-c.pk-card{background:linear-gradient(135deg,#C8328A 0%,#E86BAE 55%,#FF9FC7 100%);border-color:#C8328A;color:#FFFFFF;padding:30px;box-shadow:0 20px 60px rgba(200,50,138,.25)}
.bk-c.pk-card .eyebrow{color:var(--blush)}.bk-c.pk-card .t,.bk-c.pk-card p{color:#FFFFFF}
.bk-c.pk-card .t{font-size:56px}
.bk-c.pk-card p{font-size:15px;max-width:54ch}
.bk-c.pk-card .t{font-size:64px;margin-top:6px}.bk-c.pk-card p{margin-top:auto;padding-top:18px}

/* Details at a glance */
.bk-facts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 24px}
.bk-facts div{display:flex;flex-direction:column;gap:4px;padding:16px 0;border-top:1px solid var(--line)}
.bk-facts dt{font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--dim);font-weight:600}
.bk-facts dd{font-size:15px;line-height:1.45;color:#F7F1F4;font-weight:500}
.bk-facts dd .tbc{display:inline-block;margin-left:8px;font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--pk);font-weight:700;vertical-align:middle}
.bk-facts dd .sub{display:block;color:var(--mut);font-size:13px;font-weight:400;margin-top:3px}

/* Gallery */
.bk-gal{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));grid-auto-rows:minmax(0,auto);grid-auto-flow:dense;gap:12px}
.bk button.bk-gi{position:relative;display:block;width:100%;padding:0;border:0;background:#211A1E;border-radius:6px;overflow:hidden;cursor:zoom-in;aspect-ratio:4/3}
.bk .bk-gi.big{grid-column:span 2;grid-row:span 2}
.bk .bk-gi img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;margin:0;border:0;max-width:none;transition:transform .6s cubic-bezier(.2,.6,.2,1)}
.bk .bk-gi:hover img{transform:scale(1.04)}
.bk .bk-gi .s{position:absolute;left:10px;bottom:10px;padding:4px 8px;border-radius:3px;background:rgba(12,11,10,.72);color:#F7F1F4;font-size:11px;font-weight:600;letter-spacing:.04em;opacity:0;transition:opacity .2s}
.bk .bk-gi:hover .s,.bk .bk-gi:focus-visible .s{opacity:1}
.bk .bk-gi[hidden]{display:none}
.bk-gal-foot{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-top:22px}
.bk-gal-foot .small{font-size:13px}
/* Lightbox */
.bk dialog.bk-lb{position:fixed;inset:0;margin:0;padding:0;border:0;width:100%;height:100vh;height:100dvh;max-width:none;max-height:none;background:#0C0B0A;color:#F7F1F4;overflow:hidden;overscroll-behavior:contain}
.bk dialog.bk-lb::backdrop{background:#0C0B0A}
.bk-lb-img{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:70px 16px 76px}
.bk-lb-img img{display:block;max-width:100%;max-height:100%;width:auto;height:auto;margin:0;border:0;border-radius:4px;user-select:none;-webkit-user-select:none}
.bk-lb-top{position:absolute;left:0;right:0;top:0;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;padding-top:max(12px,env(safe-area-inset-top))}
.bk-lb-cap{min-width:0;display:flex;flex-direction:column;gap:2px;font-size:13px;color:var(--mut)}
.bk-lb-cap strong{color:#F7F1F4;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bk .bk-ibtn{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;border:1px solid #3F3238;background:rgba(26,19,22,.92);color:#F7F1F4;flex-shrink:0;cursor:pointer;padding:0}
.bk .bk-ibtn:hover{border-color:#F7F1F4;color:#FFFFFF}
.bk-lb-nav{position:absolute;left:0;right:0;bottom:0;z-index:2;display:flex;align-items:center;justify-content:center;gap:14px;padding:14px 16px;padding-bottom:max(14px,env(safe-area-inset-bottom))}
.bk-lb-nav .c{font-size:13px;color:var(--mut);min-width:64px;text-align:center;font-variant-numeric:tabular-nums}
html.bk-lock{overflow:hidden}

/* Reserve a seat */
.bk-rsvp-w{background:radial-gradient(90% 70% at 0% 0%,rgba(156,42,122,.32),rgba(156,42,122,0) 60%),linear-gradient(180deg,#211219 0%,#1A1316 100%)}
.bk-rsvp{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:48px;align-items:start}
.bk-rsvp-t{display:flex;flex-direction:column;gap:16px;min-width:0}
.bk-rsvp-t .disp{font-size:64px}
.bk-rsvp-t p{color:var(--mut);font-size:15px;line-height:1.55;max-width:46ch}
.bk-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:28px;border:1px solid var(--bline);border-radius:8px;background:rgba(12,11,10,.4);position:relative}
.bk-form label{display:flex;flex-direction:column;gap:6px;min-width:0;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--mut)}
.bk-form .full{grid-column:1 / -1}
.bk-form label .lt{display:block}.bk-form label .opt{font-weight:500;letter-spacing:0;text-transform:none;color:var(--dim)}
.bk .bk-form input,.bk .bk-form textarea{min-height:50px;padding:12px 14px;border:1px solid #3F3238;border-radius:4px;background:#0C0B0A;color:#F7F1F4;font-family:inherit;font-size:16px;font-weight:500;letter-spacing:0;text-transform:none;width:100%}
.bk .bk-form textarea{min-height:88px;resize:vertical}
.bk .bk-form input::placeholder,.bk .bk-form textarea::placeholder{color:#6F6368}
.bk .bk-form input:focus,.bk .bk-form textarea:focus{outline:2px solid var(--pk);outline-offset:1px;border-color:#3F3238}
.bk-form .act{grid-column:1 / -1;display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:6px}
.bk-form .act .small{font-size:12.5px;line-height:1.45;max-width:40ch}
.bk-form .hp{position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden}
.bk-msg{grid-column:1 / -1;font-size:14px;line-height:1.5;color:var(--mut);min-height:1.2em}
.bk-msg.is-ok{color:#F7F1F4}.bk-msg.is-err{color:#FFB3C9}
.bk-done{display:flex;flex-direction:column;gap:12px;padding:32px;border:1px solid var(--pk);border-radius:8px;background:rgba(255,159,199,.08)}
.bk-done .disp{font-size:44px}
.bk-done p{color:#F7F1F4;font-size:15px;line-height:1.55}
.bk-closed{display:flex;flex-direction:column;gap:10px;padding:28px;border:1px solid var(--bline);border-radius:8px}
.bk-closed .disp{font-size:40px}
.bk-closed p{color:var(--mut);font-size:15px;line-height:1.55}

/* One weekend, three events */
.bk-weekend{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.bk-wkc{display:flex;flex-direction:column;gap:10px;padding:24px;border-top:2px solid #4A3A42;background:var(--bcard);border-radius:0 0 6px 6px;min-width:0}
.bk-wkc.now{border-top-color:var(--pk)}
.bk-wkc-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.bk-wkc-day{font-size:64px;color:#F7F1F4;line-height:.85}
.bk-wkc-day .dm{display:none}
.bk-wkc.now .bk-wkc-day{color:var(--pk)}
.bk-wkc-b{flex:1;min-width:0;display:flex;flex-direction:column;gap:10px}
.bk-wkc .t{font-size:36px}
.bk-wkc .x{color:var(--mut);font-size:14px;line-height:1.5}
.bk a.bk-wkc-a{display:inline-flex;align-items:center;gap:8px;margin-top:auto;padding-top:8px;min-height:40px;font-size:14px;font-weight:600}
.bk-wkc-here{margin-top:auto;padding-top:8px;font-size:14px;font-weight:600;color:var(--dim)}

/* With thanks to the sponsors (only once there are some) */
.bk-thanks{display:flex;align-items:center;gap:16px 36px;flex-wrap:wrap;margin-top:16px;padding:18px 28px}
.bk-thanks .l{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--mut);font-weight:600}
.bk-thanks .g{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.bk-thanks .k{font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--dim);font-weight:700;white-space:nowrap}
.bk .sponsor{display:inline-flex;align-items:center;justify-content:center;height:64px;min-width:120px;max-width:220px;padding:10px 18px;border:1px solid var(--line);border-radius:4px;background:#0C0B0A;color:#F7F1F4;font-size:14px;font-weight:600;text-decoration:none;text-align:center;line-height:1.3}
.bk .sponsor img{max-height:100%;max-width:100%;width:auto;height:auto;object-fit:contain;margin:0;border:0}
.bk a.sponsor:hover{border-color:#F7F1F4;color:#FFFFFF}
.bk-thanks .sponsor.gold{border-color:#E4B65A}

/* Sponsorship: Gold and Silver packages and the enquiry form */
.bk-spon-w{background:radial-gradient(70% 60% at 100% 0%,rgba(228,182,90,.14),rgba(228,182,90,0) 60%),linear-gradient(180deg,#1A1316 0%,#15110F 100%)}
.bk-tiers{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.bk-tier{display:flex;flex-direction:column;gap:14px;padding:28px;border-top:3px solid var(--tier);min-width:0}
.bk-tier.gold{--tier:#E4B65A;--tier-ink:#1A1208}.bk-tier.silver{--tier:#C9CCD3;--tier-ink:#15120F}
.bk-tier .top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.bk-tier .tag{background:var(--tier);color:var(--tier-ink)}
.bk-tier .avail{font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:var(--mut);font-weight:600}
.bk-tier .avail.is-gone{color:#FFB3C9}
.bk-tier .t{font-size:44px}
.bk-tier .price{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-top:-4px}
.bk-tier .price .n{font-size:56px;color:#F7F1F4;font-variant-numeric:tabular-nums}
.bk-tier .price .n.poa{font-size:32px}
.bk-tier .price .u{font-size:13px;color:var(--mut)}
.bk-tier .lead{color:var(--mut);font-size:15px;line-height:1.5}
.bk-tier ul{display:flex;flex-direction:column;gap:9px;margin-top:4px}
.bk-tier li{display:flex;gap:10px;align-items:flex-start;font-size:14px;line-height:1.5;color:#E8DEE3}
.bk-tier li svg{flex-shrink:0;margin-top:4px;color:var(--tier)}
.bk-tier .btn{align-self:flex-start;margin-top:auto}
.bk-tier .btn-tier{background:transparent;color:#F7F1F4;border-color:var(--tier)}
.bk-tier .btn-tier:hover{background:var(--tier);color:var(--tier-ink)}
.bk-sp-form-w{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:48px;align-items:start;margin-top:56px;padding-top:56px;border-top:1px solid rgba(255,238,246,.08)}
.bk-sp-form-t{display:flex;flex-direction:column;gap:16px;min-width:0}
.bk-sp-form-t .disp{font-size:56px}
.bk-sp-form-t p{color:var(--mut);font-size:15px;line-height:1.55;max-width:46ch}
.bk-radios{display:flex;gap:8px;flex-wrap:wrap}
.bk .bk-radios label{position:relative;flex-direction:row;align-items:center;gap:8px;min-height:44px;padding:0 16px;border:1px solid #3F3238;border-radius:999px;cursor:pointer;font-size:14px;letter-spacing:0;text-transform:none;color:#F7F1F4;font-weight:600;background:#0C0B0A}
.bk .bk-radios label:hover{border-color:#F7F1F4}
.bk .bk-radios input{position:absolute;opacity:0;width:1px;height:1px;min-height:0;padding:0;margin:0}
.bk .bk-radios label.is-on{background:var(--pk);border-color:var(--pk);color:var(--pki)}
.bk .bk-radios label.is-focus{outline:2px solid var(--pk);outline-offset:2px}

/* Venue */
.bk-two{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px}
.bk-venue{grid-column:span 12;display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:24px 48px;padding:28px;min-width:0}
.bk-venue .t{font-size:36px}
.bk-venue p{color:var(--mut);font-size:14px;line-height:1.55}
.bk-venue .adr{font-size:15px;color:#F7F1F4;font-weight:500;line-height:1.5}
.bk-venue .col{display:flex;flex-direction:column;gap:12px;min-width:0}
.bk a.bk-go{display:inline-flex;align-items:center;gap:8px;align-self:flex-start;min-height:40px;font-size:14px;font-weight:600}

/* Footer */
.bk-foot{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;padding-top:28px;padding-bottom:48px;font-size:13px;color:var(--dim)}
.bk-foot nav{display:flex;gap:20px;flex-wrap:wrap}
.bk-foot nav a{color:var(--mut)}.bk-foot nav a:hover{color:#FFFFFF}

/* Staff bar (staff only, never on the public page) */
.bk .bk-staff{display:flex;align-items:center;gap:10px 14px;flex-wrap:wrap;margin:16px 0 0;padding:12px 16px;border:1px dashed #D99A21;border-radius:6px;background:rgba(217,154,33,.08);font-size:13px;color:#E6DFD4}
.bk .bk-staff-tag{font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#1A1208;background:#F0C36A;padding:4px 8px;border-radius:3px}
.bk .bk-staff-text{flex:1 1 320px;min-width:0;line-height:1.45}
.bk .bk-staff-status{font-weight:600}.bk .bk-staff-status.is-warn{color:#F0C36A}
.bk .bk-staff-note{color:#B4A99A}
.bk .bk-staff-btns{display:flex;gap:8px;flex-wrap:wrap}
.bk .bk-staff .bk-sbtn{display:inline-flex;align-items:center;min-height:40px;padding:0 14px;border-radius:4px;border:1px solid #F0C36A;background:#F0C36A;color:#1A1208;font-size:13px;font-weight:600}
.bk .bk-staff .bk-sbtn.is-ghost{background:transparent;color:#F4EFE7;border-color:#3A332D}
.bk .bk-staff .bk-sbtn.is-ghost:hover{border-color:#F4EFE7}

@media (prefers-reduced-motion:reduce){.bk .ld,.bk .sr,.bk-bg{animation:none}.bk-bg img,.bk-cap{animation-play-state:paused}.bk .bk-gi img{transition:none}}

@media (max-width:900px){
.bk .wrap{padding:0 32px}
.bk-h1{font-size:min(96px,calc(11.8vw - 8px))}.bk-bg img{width:100%}.bk-bg{--o:.5}
.bk-hero{grid-template-columns:repeat(1,minmax(0,1fr))}.bk-hero-t{grid-column:span 1}
.bk-caps{right:32px;max-width:calc(100% - 64px)}
.bk-cards > *{grid-column:span 12 !important}
.bk-gal{grid-template-columns:repeat(3,minmax(0,1fr))}
.bk-rsvp{grid-template-columns:1fr;gap:28px}
.bk-weekend{grid-template-columns:repeat(1,minmax(0,1fr))}
.bk-two > *{grid-column:span 12 !important}
.bk-venue{grid-template-columns:1fr}
.bk-tiers{grid-template-columns:1fr}
.bk-sp-form-w{grid-template-columns:1fr;gap:28px;margin-top:48px;padding-top:48px}
.bk .band{margin-top:80px;padding:64px 0}.bk .band-tight{padding:52px 0}
}
/* Phones */
@media (max-width:700px){
.bk{font-size:15px}
.bk .wrap{padding:0 20px}
.bk-head-bar{position:sticky;top:0;z-index:20;background:rgba(12,11,10,.9);-webkit-backdrop-filter:blur(14px);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,238,246,.08)}
.bk-head{padding:12px 0;border-bottom:0}
.bk-head .back,.bk-head .btn-ghost{display:none}
.bk-head .logo img{height:34px}
.bk-head .btn{min-height:44px;padding:0 18px;font-size:14.5px;border-radius:999px;font-weight:700}
.bk-top{display:flex;flex-direction:column;padding-top:0}
.bk-wk-w{order:1}
.bk-media{order:2;position:relative;inset:auto;height:280px;flex-shrink:0}
.bk-bg{opacity:1;animation:none}
.bk-bg img{width:100%;-webkit-mask-image:none;mask-image:none;transform-origin:55% 50%}
.bk-glow{display:none}
.bk-veil{z-index:4;background:linear-gradient(180deg,rgba(12,11,10,.45) 0%,rgba(12,11,10,0) 22%,rgba(12,11,10,0) 36%,rgba(12,11,10,.78) 62%,rgba(12,11,10,.95) 80%,#0C0B0A 100%)}
.bk-caps{z-index:5;top:14px;left:16px;right:16px;bottom:auto;width:auto;max-width:none;height:auto}
.bk-cap{right:0;top:0;bottom:auto;font-size:11.5px;text-align:right;white-space:nowrap}
.bk-hero-w{order:3;margin-top:-70px}
.bk-wk{border-bottom:0;padding:14px 0 16px}
.bk-wk-l{font-size:11px;color:var(--dim);font-weight:700}
.bk-wk-links{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;width:100%}
.bk a.bk-wk-a{flex-direction:column;align-items:flex-start;justify-content:space-between;gap:8px;min-height:74px;padding:11px 12px;border-radius:10px;background:#15120F;white-space:normal;font-size:14px;font-weight:700;line-height:1.15}
.bk-wk-a .d{font-size:10.5px;letter-spacing:.12em;font-weight:700}
.bk-hero{padding:0 0 8px;gap:0}
.bk-hero .eyebrow{text-shadow:0 1px 10px rgba(0,0,0,.75)}
.bk-h1{font-size:min(64px,calc(15.3vw - 6px));margin-top:10px}
.bk-h1 .wm{height:.78em}.bk-h1 .yr{display:block}.bk-ey-area{display:none}
.bk-h1 .b{text-shadow:0 1px 10px rgba(0,0,0,.6),0 0 40px rgba(255,159,199,.25)}
.bk-lead{font-size:17px;font-weight:500;line-height:1.55;color:#D9CDD3;margin-top:22px}
.bk-ctas{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:22px}
.bk-ctas .btn{min-height:56px;padding:0 10px;font-size:16px;font-weight:700;border-radius:6px}
.bk-note{font-size:13.5px;font-weight:500;color:#A89AA2;margin-top:14px}
.bk-status-w{margin-top:28px}
.bk-status{padding:20px 20px 18px;gap:10px}
.bk-status .n{font-size:64px}
.bk-status .nt{font-size:15px;font-weight:600}
.bk-status .w{font-size:13.5px;font-weight:500;color:#A89AA2;flex-basis:100%;order:3}
.bk .sec-head{display:block;margin-bottom:0}
.bk .sec-head .disp{font-size:52px !important}
.bk .sec-head p{font-size:16px;font-weight:500;color:#D9CDD3;line-height:1.55;margin-top:14px}
.bk .sec-head .small{display:block;font-size:14px !important;font-weight:500;margin-top:10px}
.bk-cards{gap:12px;margin-top:24px}
.bk-c{padding:20px;border-radius:12px}
.bk-c .t{font-size:28px}
.bk-c p{font-size:14px;font-weight:500;color:#C9BCC3}
.bk .bk-c.pk-card{padding:22px}
.bk-c.pk-card .t{font-size:40px}
.bk-facts{grid-template-columns:1fr;margin-top:8px}
.bk-gal{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:22px}
.bk .bk-gi.big{grid-column:span 2;grid-row:span 1;aspect-ratio:3/2}
.bk .bk-gi{border-radius:10px}
.bk .bk-gi .s{display:none}
.bk-gal-foot{flex-direction:column;align-items:stretch;gap:12px}
.bk-gal-foot .btn{width:100%;min-height:52px}
.bk .band{margin-top:64px;padding:56px 0}.bk .band-tight{padding:48px 0 56px}
.bk-rsvp-t .disp{font-size:52px}
.bk-rsvp-t p{font-size:15px;font-weight:500;color:#D9CDD3}
.bk-form{grid-template-columns:1fr;gap:12px;padding:20px;border-radius:12px}
.bk-form .act .btn{width:100%;min-height:56px;font-size:16px;font-weight:700;border-radius:6px}
.bk-weekend{gap:12px;margin-top:24px}
.bk-wkc{flex-direction:row;gap:16px;padding:20px 18px;border-radius:12px}
.bk-wkc-top{flex:0 0 52px;flex-direction:column;align-items:center;gap:3px}
.bk-wkc-top .tag{display:none}
.bk-wkc-day{display:flex;flex-direction:column;align-items:center;gap:3px;font-size:46px;line-height:.9}
.bk-wkc-day .dw,.bk-wkc-day .dm{display:block;font-family:'Montserrat',system-ui,sans-serif;font-size:11px;font-weight:700;letter-spacing:.12em;color:#A89AA2;line-height:1.2}
.bk-wkc-b{gap:8px}
.bk-wkc .t{font-size:30px}
.bk-wkc .x{font-size:14.5px;font-weight:500;color:#C9BCC3}
.bk-thanks{padding:16px 18px;gap:12px 20px;border-radius:12px}
.bk-tiers{gap:12px;margin-top:24px}
.bk-tier{padding:20px;border-radius:12px;border-top-width:3px}
.bk-tier .t{font-size:34px}
.bk-tier .price .n{font-size:44px}
.bk-tier li{font-size:14px;font-weight:500;color:#C9BCC3}
.bk-tier .btn{align-self:stretch;min-height:52px}
.bk-sp-form-t .disp{font-size:44px}
.bk-sp-form-t p{font-size:15px;font-weight:500;color:#D9CDD3}
.bk-two{gap:12px;margin-top:24px}
.bk-venue{padding:20px;border-radius:12px}
.bk-venue .t{font-size:30px}
.bk-venue p{font-size:14px;font-weight:500;color:#C9BCC3}
.bk-lb-img{padding:64px 10px 72px}
.bk-foot{flex-direction:column;gap:14px;border-top:1px solid rgba(255,238,246,.08);margin-top:8px}
.bk-foot nav{flex-direction:column;gap:0}
.bk-foot nav a{display:flex;align-items:center;min-height:40px;font-weight:600;color:#C9BCC3}
}
@media (max-width:380px){
.bk a.bk-wk-a{font-size:13px;padding:10px}
.bk-ctas .btn{font-size:15px}
}
</style>

<div class="bk" id="bk-main">

<div class="bk-head-bar">
<div class="wrap">
<header class="bk-head ld d1">
  <a class="back" href="https://www.atfa.com.au/"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>atfa.com.au</a>
  <a class="logo" href="https://www.atfa.com.au/"><?php echo $logo ? '<img src="' . esc_url( $logo ) . '" alt="ATFA Australasian Timber Flooring Association" width="200" height="46" data-no-lazy="1">' : '<span class="logo-text">ATFA</span>'; ?></a>
  <div class="btns">
    <a class="btn btn-ghost" href="https://www.atfa.com.au/my-membership/">Member login</a>
    <a class="btn btn-primary" href="#rsvp">Reserve my seat</a>
  </div>
</header>
</div>
</div>

<div class="bk-top">
  <div class="bk-media" aria-hidden="true">
    <div class="bk-bg">
<?php
$i = 0;
foreach ( $hero as $p ) {
	$delay = array( '-2s', '6s', '14s', '22s' );
	echo atfa_bk_photo( $p['id'], array( 'class' => 0 === $i ? 'lead' : '', 'sizes' => '(max-width: 700px) 100vw, (max-width: 900px) 100vw, 70vw', 'style' => 'animation-delay:' . $delay[ $i ], 'alt' => '', 'first' => 0 === $i ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$i++;
}
?>
    </div>
    <div class="bk-veil"></div>
    <div class="bk-glow"></div>
    <div class="bk-caps">
<?php
$i = 0;
foreach ( $hero as $p ) {
	$delay = array( '-2s', '6s', '14s', '22s' );
	echo '<span class="bk-cap' . ( 0 === $i ? ' lead' : '' ) . '" style="animation-delay:' . $delay[ $i ] . '">' . esc_html( $p['label'] ) . '</span>';
	$i++;
}
?>
    </div>
  </div>
  <div class="wrap bk-wk-w">
    <nav class="bk-wk ld d2" aria-label="The ATFA weekend">
      <span class="bk-wk-l">The ATFA weekend · Sydney Olympic Park · 21 and 22 May 2027</span>
      <div class="bk-wk-links">
        <a class="bk-wk-a" href="<?php echo esc_url( $expo ); ?>"><span class="d">Fri 21 May</span>Expo &amp; Conference</a>
        <a class="bk-wk-a on" href="#rsvp" aria-current="page"><span class="d">Sat 22 May</span>Ladies Breakfast</a>
        <a class="bk-wk-a" href="<?php echo esc_url( $awards ); ?>"><span class="d">Sat 22 May</span>Awards Dinner</a>
      </div>
    </nav>
  </div>
  <div class="wrap bk-hero-w">
    <div class="bk-hero">
      <div class="bk-hero-t">
        <span class="eyebrow ld d2">Ladies of ATFA · <?php echo esc_html( $info['day_text'] ); ?><?php echo '' !== $venue['area'] ? '<span class="bk-ey-area"> · ' . esc_html( $venue['area'] ) . '</span>' : ''; ?></span>
        <h1 class="fancy bk-h1 ld d3"><span class="a"><?php echo $mark ? '<img class="wm" src="' . esc_url( $mark ) . '" alt="ATFA" data-no-lazy="1">' : 'ATFA '; ?>Ladies</span><span class="b">Breakfast <span class="yr">2027</span></span></h1>
        <p class="bk-lead ld d4">A relaxed Saturday morning for the women of the timber flooring industry: a full breakfast, a glass of bubbles and good company, before the awards dinner that night.</p>
        <div class="bk-ctas ld d5">
          <a class="btn btn-primary" href="#rsvp">Reserve my seat</a>
          <a class="btn btn-ghost" href="#photos">See the photos</a>
        </div>
        <p class="bk-note ld d6"><?php echo esc_html( $price['text'] ); ?> for the ladies of ATFA. Places are limited and go in order of reservation.</p>
      </div>
    </div>
  </div>
</div>

<div class="wrap sr bk-status-w">
  <div class="card bk-status">
    <div class="l">
      <span class="bk-dot<?php echo $open ? '' : ' is-off'; ?>"></span>
      <span class="disp t"><?php echo $open ? 'Seats open' : 'Reservations closed'; ?></span>
      <span class="w"><?php echo esc_html( $where ? $where . ' · ' : '' ); ?><?php echo esc_html( $info['date_text'] ); ?><?php echo $tbc ? ' · venue to be confirmed' : ''; ?></span>
    </div>
    <div class="r">
      <span class="disp n" data-role="days" hidden></span>
      <span class="nt" data-role="daysnote"><?php echo esc_html( $info['day_text'] ); ?></span>
    </div>
  </div>
  <?php echo atfa_bk_staff_bar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php if ( $thanks ) : ?>
  <div class="card bk-thanks">
    <span class="l">With thanks to</span>
<?php if ( $sponsors['gold'] ) : ?>
    <div class="g"><?php echo atfa_bk_sponsor_tile( $sponsors['gold'], 'gold' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="k">Gold sponsor</span></div>
<?php endif; ?>
<?php if ( $sponsors['silver'] ) : ?>
    <div class="g"><?php
	foreach ( $sponsors['silver'] as $s ) {
		echo atfa_bk_sponsor_tile( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?><span class="k">Silver <?php echo 1 === count( $sponsors['silver'] ) ? 'sponsor' : 'sponsors'; ?></span></div>
<?php endif; ?>
  </div>
<?php endif; ?>
</div>

<section class="wrap sec" id="the-morning" style="margin-top:96px" aria-labelledby="bk-morning-h">
  <div class="sec-head">
    <h2 class="disp" id="bk-morning-h" style="font-size:72px;max-width:12ch">A morning for the ladies of ATFA</h2>
    <p>Breakfast on the Saturday of the ATFA weekend, in a private room at the hotel, hosted by ATFA. Short, relaxed and done by mid-morning, with the whole day ahead before the awards dinner.</p>
  </div>
  <div class="bk-cards">
    <div class="card bk-c pk-card" style="grid-column:span 7">
      <span class="eyebrow">What's on the table</span>
      <span class="disp t">Breakfast with bubbles</span>
      <p>A full plated breakfast and a glass of bubbles, served in the <?php echo esc_html( '' !== $venue['room'] ? $venue['room'] : 'private room' ); ?> at the <?php echo esc_html( '' !== $venue['name'] ? $venue['name'] : 'hotel' ); ?>. Tea, coffee and juice all morning.</p>
    </div>
    <div class="card bk-c" style="grid-column:span 5">
      <span class="eyebrow pk">Details</span>
      <dl class="bk-facts" style="grid-template-columns:1fr">
        <div><dt>When</dt><dd><?php echo esc_html( $when ); ?><?php echo $tbc ? '<span class="tbc">Times to be confirmed</span>' : ''; ?></dd></div>
        <div><dt>Where</dt><dd><?php echo esc_html( $where ? $where : 'Sydney Olympic Park' ); ?><?php echo $tbc ? '<span class="tbc">To be confirmed</span>' : ''; ?><?php echo '' !== $venue['address'] ? '<span class="sub">' . esc_html( $venue['address'] ) . '</span>' : ''; ?></dd></div>
        <div><dt>Cost</dt><dd><?php echo esc_html( $price['text'] ); ?><?php echo '' !== $price['detail'] ? '<span class="sub">' . esc_html( $price['detail'] ) . '</span>' : ''; ?></dd></div>
        <div><dt>Hosted by</dt><dd><?php echo esc_html( preg_replace( '/^Hosted by /i', '', $info['host'] ) ); ?></dd></div>
      </dl>
    </div>
    <div class="card bk-c" style="grid-column:span 4">
      <span class="eyebrow pk">Who comes</span>
      <span class="disp t">Good company</span>
      <p>Business owners, installers, sanders, retailers, suppliers and the people who keep the industry running. Bring a colleague from your business.</p>
    </div>
    <div class="card bk-c" style="grid-column:span 4">
      <span class="eyebrow pk">The weekend</span>
      <span class="disp t">Make a weekend of it</span>
      <p>The Expo is the day before and the awards dinner is that night, a short walk away. Stay in the precinct and do all three.</p>
    </div>
    <div class="card bk-c" style="grid-column:span 4">
      <span class="eyebrow pk">The morning</span>
      <span class="disp t">Short and relaxed</span>
      <p>A welcome from ATFA, breakfast, and time to talk. Done by mid-morning, with the whole day ahead before the dinner.</p>
    </div>
  </div>
</section>

<?php if ( $all ) : ?>
<section class="band sec" id="photos" aria-labelledby="bk-gal-h">
<div class="wrap sr">
  <div class="sec-head" style="margin-bottom:18px">
    <h2 class="disp" id="bk-gal-h" style="font-size:56px">The ladies of ATFA</h2>
    <p><?php
	$labels = array();
	foreach ( $sets as $set ) {
		$labels[] = $set['label'];
	}
	echo esc_html( 'From the ' . implode( ', and the ', $labels ) . '. Tap any photo to see it full size.' );
	?></p>
  </div>
  <div class="bk-gal" data-role="gallery">
<?php
$n = 0;
foreach ( $all as $p ) {
	$big  = in_array( $n, array( 0, 7 ), true );
	$full = atfa_bk_photo_full( $p['id'] );
	$img  = atfa_bk_photo( $p['id'], array( 'size' => $big ? 'large' : 'medium_large', 'sizes' => $big ? '(max-width: 700px) 100vw, (max-width: 900px) 66vw, 420px' : '(max-width: 700px) 50vw, (max-width: 900px) 33vw, 200px', 'lazy' => $n > 1, 'alt' => '' ) );
	if ( ! $img ) {
		continue;
	}
	echo '<button type="button" class="bk-gi' . ( $big ? ' big' : '' ) . '" data-full="' . esc_url( $full ) . '" data-cap="' . esc_attr( $p['label'] ) . '" data-i="' . $n . '" aria-label="Photo ' . ( $n + 1 ) . ', ' . esc_attr( $p['short'] ) . '"' . ( $n >= $first ? ' hidden' : '' ) . '>' . $img . '<span class="s">' . esc_html( $p['short'] ) . '</span></button>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$n++;
}
?>
  </div>
<?php if ( $n > $first ) : ?>
  <div class="bk-gal-foot">
    <button type="button" class="btn btn-ghost" data-role="more">Show all <?php echo (int) $n; ?> photos</button>
    <span class="small">Photos by ATFA. Please ask before using them elsewhere.</span>
  </div>
<?php endif; ?>
</div>
</section>
<?php endif; ?>

<section class="band bk-rsvp-w sec" id="rsvp" aria-labelledby="bk-rsvp-h" style="margin-top:<?php echo $all ? '0' : '96px'; ?>">
<div class="wrap sr">
  <div class="bk-rsvp">
    <div class="bk-rsvp-t">
      <span class="eyebrow pk">Reserve your seat</span>
      <h2 class="disp" id="bk-rsvp-h">Save me a seat</h2>
      <p><?php echo esc_html( $price['text'] ); ?> for the ladies of ATFA. Places are limited and go in order of reservation. We confirm by email straight away and send the final details closer to the day.</p>
      <dl class="bk-facts">
        <div><dt>When</dt><dd><?php echo esc_html( $when ); ?><?php echo $tbc ? '<span class="tbc">TBC</span>' : ''; ?></dd></div>
        <div><dt>Where</dt><dd><?php echo esc_html( $where ? $where : 'Sydney Olympic Park' ); ?><?php echo $tbc ? '<span class="tbc">TBC</span>' : ''; ?></dd></div>
        <div><dt>Cost</dt><dd><?php echo esc_html( $price['text'] ); ?></dd></div>
        <div><dt>Questions</dt><dd><a href="<?php echo esc_url( $mailto ); ?>"><?php echo esc_html( $rsvp['email'] ); ?></a></dd></div>
      </dl>
    </div>
<?php if ( $open ) : ?>
    <form class="bk-form" id="bk-rsvp-form" novalidate data-endpoint="<?php echo esc_url( rest_url( 'atfa-awards/v1/breakfast/rsvp' ) ); ?>" data-nonce="<?php echo esc_attr( is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '' ); ?>">
      <label><span class="lt">Your name</span><input type="text" name="name" autocomplete="name" required maxlength="100" placeholder="First and last name"></label>
      <label><span class="lt">Business</span><input type="text" name="business" autocomplete="organization" required maxlength="120" placeholder="Your business"></label>
      <label><span class="lt">Email</span><input type="email" name="email" autocomplete="email" required maxlength="120" placeholder="you@business.com.au"></label>
      <label><span class="lt">Mobile <span class="opt">(optional)</span></span><input type="tel" name="phone" autocomplete="tel" maxlength="40" placeholder="04..."></label>
      <label class="full"><span class="lt">Bringing a colleague? <span class="opt">Add their name and we will reserve a seat for them too.</span></span><input type="text" name="guest" maxlength="100" placeholder="Colleague's name (optional)"></label>
      <label class="full"><span class="lt">Dietary requirements <span class="opt">(optional)</span></span><textarea name="dietary" maxlength="500" placeholder="Vegetarian, gluten free, allergies..."></textarea></label>
      <label class="hp" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      <div class="act">
        <button type="submit" class="btn btn-primary" id="bk-rsvp-btn">Reserve my seat</button>
        <span class="small">We only use these details for the breakfast. You will get one confirmation email now and the final details closer to the day.</span>
      </div>
      <p class="bk-msg" id="bk-rsvp-msg" aria-live="polite"></p>
    </form>
<?php else : ?>
    <div class="bk-closed">
      <span class="disp">Reservations have closed</span>
      <p>Email <a href="<?php echo esc_url( $mailto ); ?>"><?php echo esc_html( $rsvp['email'] ); ?></a> and <?php echo esc_html( $rsvp['contact'] ); ?> will let you know if a seat comes up.</p>
    </div>
<?php endif; ?>
  </div>
</div>
</section>

<section class="wrap sr sec" id="weekend" aria-labelledby="bk-wk-h" style="margin-top:96px">
  <div class="sec-head" style="margin-bottom:22px">
    <h2 class="disp" id="bk-wk-h" style="font-size:56px">One weekend, three events</h2>
    <span class="small" style="font-size:13px">Sydney Olympic Park · 21 and 22 May 2027 · times to be confirmed</span>
  </div>
  <div class="bk-weekend">
    <div class="bk-wkc">
      <div class="bk-wkc-top"><span class="disp bk-wkc-day"><span class="dw">Fri</span> <span class="dn">21</span><span class="dm">May</span></span></div>
      <div class="bk-wkc-b">
        <span class="eyebrow">Friday 21 May</span>
        <span class="disp t">Expo &amp; Conference</span>
        <span class="x">Sydney Showground, Hall 5. The timber flooring industry in one hall for a day, with a live demonstration stage.</span>
        <a class="bk-wkc-a" href="<?php echo esc_url( $expo ); ?>">See the Expo<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg></a>
      </div>
    </div>
    <div class="bk-wkc now">
      <div class="bk-wkc-top"><span class="disp bk-wkc-day"><span class="dw">Sat</span> <span class="dn">22</span><span class="dm">May</span></span><span class="tag tag-pk">This page</span></div>
      <div class="bk-wkc-b">
        <span class="eyebrow pk">Saturday 22 May · morning</span>
        <span class="disp t">Ladies Breakfast</span>
        <span class="x"><?php echo esc_html( '' !== $venue['name'] ? $venue['name'] : 'Sydney Olympic Park' ); ?>. Breakfast and bubbles for the ladies of ATFA.</span>
        <a class="bk-wkc-a" href="#rsvp">Reserve a seat<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg></a>
      </div>
    </div>
    <div class="bk-wkc">
      <div class="bk-wkc-top"><span class="disp bk-wkc-day"><span class="dw">Sat</span> <span class="dn">22</span><span class="dm">May</span></span></div>
      <div class="bk-wkc-b">
        <span class="eyebrow">Saturday 22 May · evening</span>
        <span class="disp t">Awards Dinner</span>
        <span class="x">Novotel Sydney Olympic Park. The 2027 ATFA Flooring Awards are presented on the night.</span>
        <a class="bk-wkc-a" href="<?php echo esc_url( $awards ); ?>">See the awards<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg></a>
      </div>
    </div>
  </div>
</section>

<section class="band bk-spon-w sec" id="sponsor" aria-labelledby="bk-sp-h">
<div class="wrap sr">
  <div class="sec-head">
    <div>
      <span class="eyebrow pk">Sponsorship</span>
      <h2 class="disp" id="bk-sp-h" style="font-size:64px;max-width:14ch;margin-top:10px">Put your name on the morning</h2>
    </div>
    <p><?php echo $comp ? 'Sponsors are why the breakfast is complimentary for the ladies of ATFA. ' : 'Sponsors make the morning happen. '; ?>Two packages, the same as the Women in Flooring Lunch at Rippon Lea: one Gold sponsor with naming rights and up to <?php echo (int) $tiers['silver']['limit']; ?> Silver sponsors. Your business in front of a room of the industry's women, behind an initiative that connects and supports them.</p>
  </div>
<?php if ( $thanks ) : ?>
  <div class="bk-thanks" style="margin:0 0 24px;padding:0 0 24px;border-bottom:1px solid rgba(255,238,246,.08)">
    <span class="l">Confirmed so far</span>
<?php if ( $sponsors['gold'] ) : ?>
    <div class="g"><?php echo atfa_bk_sponsor_tile( $sponsors['gold'], 'gold' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="k">Gold</span></div>
<?php endif; ?>
<?php if ( $sponsors['silver'] ) : ?>
    <div class="g"><?php
	foreach ( $sponsors['silver'] as $s ) {
		echo atfa_bk_sponsor_tile( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?><span class="k">Silver</span></div>
<?php endif; ?>
  </div>
<?php endif; ?>
<?php if ( $sp_open ) : ?>
  <div class="bk-tiers">
<?php
foreach ( $tiers as $key => $t ) {
	$gone  = $t['limit'] > 0 && 0 === $t['left'];
	$avail = 1 === $t['limit']
		? ( $gone ? 'Taken' : 'One only' )
		: ( $gone ? 'All ' . $t['limit'] . ' taken' : ( $t['taken'] > 0 ? $t['left'] . ' of ' . $t['limit'] . ' positions open' : 'Up to ' . $t['limit'] . ' sponsors' ) );
	echo '<div class="card bk-tier ' . esc_attr( $key ) . '">';
	echo '<div class="top"><span class="tag">' . esc_html( ucfirst( $key ) ) . '</span><span class="avail' . ( $gone ? ' is-gone' : '' ) . '">' . esc_html( $avail ) . '</span></div>';
	echo '<span class="disp t">' . esc_html( $t['name'] ) . '</span>';
	echo '<div class="price">' . ( '' !== $t['price'] ? '<span class="disp n">' . esc_html( $t['price'] ) . '</span><span class="u">for the breakfast</span>' : '<span class="disp n poa">Price on enquiry</span>' ) . '</div>';
	if ( '' !== $t['lead'] ) {
		echo '<p class="lead">' . esc_html( $t['lead'] ) . '</p>';
	}
	if ( $t['includes'] ) {
		echo '<ul>';
		foreach ( $t['includes'] as $inc ) {
			echo '<li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg><span>' . esc_html( $inc ) . '</span></li>';
		}
		echo '</ul>';
	}
	echo '<a class="btn btn-tier" href="#sponsor-form" data-tier="' . esc_attr( $key ) . '">' . ( $gone ? 'Ask about ' . esc_html( $t['name'] ) . 'ship' : 'Enquire about ' . esc_html( ucfirst( $key ) ) ) . '</a>';
	echo '</div>' . "\n";
}
?>
  </div>
  <div class="bk-sp-form-w" id="sponsor-form">
    <div class="bk-sp-form-t">
      <span class="eyebrow pk">Enquire</span>
      <h3 class="disp">Talk to <?php echo esc_html( $sp['contact'] ); ?></h3>
      <p>Tell us which package you have in mind and <?php echo esc_html( $sp['contact'] ); ?> from ATFA will get back to you with the details. Nothing is booked until you say so.</p>
      <p>Prefer email? <a href="<?php echo esc_url( $sp_mail ); ?>"><?php echo esc_html( $sp['to'] ); ?></a></p>
    </div>
    <form class="bk-form" id="bk-sp-form" novalidate data-endpoint="<?php echo esc_url( rest_url( 'atfa-awards/v1/breakfast/sponsor' ) ); ?>" data-nonce="<?php echo esc_attr( is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '' ); ?>">
      <label><span class="lt">Business</span><input type="text" name="business" autocomplete="organization" required maxlength="120" placeholder="Your business"></label>
      <label><span class="lt">Your name</span><input type="text" name="name" autocomplete="name" required maxlength="100" placeholder="First and last name"></label>
      <label><span class="lt">Email</span><input type="email" name="email" autocomplete="email" required maxlength="120" placeholder="you@business.com.au"></label>
      <label><span class="lt">Mobile <span class="opt">(optional)</span></span><input type="tel" name="phone" autocomplete="tel" maxlength="40" placeholder="04..."></label>
      <fieldset class="full" style="border:0;padding:0;margin:0;min-width:0">
        <legend class="lt" style="font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--mut);padding:0;margin-bottom:8px">Package</legend>
        <div class="bk-radios">
<?php
foreach ( $tiers as $key => $t ) {
	echo '<label><input type="radio" name="tier" value="' . esc_attr( $key ) . '">' . esc_html( $t['name'] ) . ( '' !== $t['price'] ? ' · ' . esc_html( $t['price'] ) : '' ) . '</label>';
}
?>
          <label class="is-on"><input type="radio" name="tier" value="unsure" checked>Not sure yet</label>
        </div>
      </fieldset>
      <label class="full"><span class="lt">Anything else? <span class="opt">(optional)</span></span><textarea name="message" maxlength="1000" placeholder="Questions, or what you have in mind"></textarea></label>
      <label class="hp" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      <div class="act">
        <button type="submit" class="btn btn-primary" id="bk-sp-btn">Send my enquiry</button>
        <span class="small">We only use these details to reply to you. You will get a copy of the package details by email.</span>
      </div>
      <p class="bk-msg" id="bk-sp-msg" aria-live="polite"></p>
    </form>
  </div>
<?php else : ?>
  <div class="bk-closed">
    <span class="disp">Sponsorship for 2027 is closed</span>
    <p>Email <a href="<?php echo esc_url( $sp_mail ); ?>"><?php echo esc_html( $sp['to'] ); ?></a> and <?php echo esc_html( $sp['contact'] ); ?> will let you know about the next one.</p>
  </div>
<?php endif; ?>
</div>
</section>

<section class="wrap sr sec" id="venue" aria-label="The venue" style="margin-top:64px">
  <div class="bk-two">
    <div class="card bk-venue">
      <div class="col">
        <span class="eyebrow pk">The venue<?php echo $tbc ? ' · to be confirmed' : ''; ?></span>
        <span class="disp t"><?php echo esc_html( '' !== $venue['name'] ? $venue['name'] : 'Sydney Olympic Park' ); ?></span>
<?php if ( '' !== $venue['address'] ) : ?>
        <span class="adr"><?php echo esc_html( '' !== $venue['room'] ? $venue['room'] . ', ' : '' ); ?><?php echo esc_html( $venue['address'] ); ?></span>
<?php endif; ?>
<?php if ( '' !== $venue['map'] ) : ?>
        <a class="bk-go" href="<?php echo esc_url( $venue['map'] ); ?>" target="_blank" rel="noopener">Open in Maps<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg></a>
<?php endif; ?>
      </div>
      <div class="col">
<?php if ( '' !== $venue['note'] ) : ?>
        <p><?php echo esc_html( $venue['note'] ); ?></p>
<?php endif; ?>
        <p>Staying over? The Pullman, Novotel and Ibis share the Sydney Olympic Park precinct with the awards dinner, and the Expo is at the Showground next door.</p>
      </div>
    </div>
  </div>
</section>

<footer class="wrap bk-foot">
  <nav aria-label="More from ATFA">
    <a href="https://www.atfa.com.au/">atfa.com.au</a>
    <a href="#sponsor">Sponsor the breakfast</a>
    <a href="<?php echo esc_url( $expo ); ?>">Expo &amp; Conference 2027</a>
    <a href="<?php echo esc_url( $awards ); ?>">Flooring Awards 2027</a>
    <a href="https://www.atfa.com.au/contact/">Contact ATFA</a>
  </nav>
  <div>AU 1300 361 693 · NZ 0800 453 715 · © Australasian Timber Flooring Association</div>
</footer>

<dialog class="bk-lb" id="bk-lb" aria-label="Photo">
  <div class="bk-lb-top">
    <div class="bk-lb-cap"><strong data-role="lbcap"></strong><span data-role="lbsub">Ladies of ATFA</span></div>
    <button type="button" class="bk-ibtn" data-role="lbclose" aria-label="Close"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>
  </div>
  <div class="bk-lb-img" data-role="lbimg"><img src="" alt=""></div>
  <div class="bk-lb-nav">
    <button type="button" class="bk-ibtn" data-role="lbprev" aria-label="Previous photo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg></button>
    <span class="c" data-role="lbcount"></span>
    <button type="button" class="bk-ibtn" data-role="lbnext" aria-label="Next photo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"></path></svg></button>
  </div>
</dialog>

</div>
<script>
(function(){
  var root=document.getElementById('bk-main'); if(!root) return;
  var q=function(sel,el){return (el||root).querySelector(sel)}, qa=function(sel,el){return [].slice.call((el||root).querySelectorAll(sel))};

  // Days to go, in Sydney time.
  var ev=new Date('<?php echo esc_js( $info['date'] ); ?>T08:00:00+10:00'), days=Math.ceil((ev.getTime()-Date.now())/86400000), dn=q('[data-role="days"]'), dt=q('[data-role="daysnote"]');
  if(dn&&dt&&days>0){ dn.textContent=days; dn.hidden=false; dt.textContent=days===1?'day to go':'days to go'; }

  // Gallery: show all, and the lightbox.
  var tiles=qa('.bk-gi'), more=q('[data-role="more"]'), lb=document.getElementById('bk-lb'), cur=0;
  if(more){ more.addEventListener('click',function(){ tiles.forEach(function(t){ t.hidden=false; }); more.parentNode.removeChild(more); }); }
  if(lb&&typeof lb.showModal==='function'&&tiles.length){
    var img=q('img',lb), cap=q('[data-role="lbcap"]',lb), cnt=q('[data-role="lbcount"]',lb), touchX=null;
    function show(i){
      cur=(i+tiles.length)%tiles.length; var t=tiles[cur];
      img.src=t.getAttribute('data-full'); img.alt=t.getAttribute('aria-label')||'';
      cap.textContent=t.getAttribute('data-cap'); cnt.textContent=(cur+1)+' / '+tiles.length;
      var nx=tiles[(cur+1)%tiles.length]; if(nx){ var pre=new Image(); pre.src=nx.getAttribute('data-full'); }
    }
    function open(i){ show(i); lb.showModal(); document.documentElement.classList.add('bk-lock'); }
    function close(){ lb.close(); }
    tiles.forEach(function(t){ t.addEventListener('click',function(){ open(parseInt(t.getAttribute('data-i'),10)||0); }); });
    q('[data-role="lbclose"]',lb).addEventListener('click',close);
    q('[data-role="lbprev"]',lb).addEventListener('click',function(){ show(cur-1); });
    q('[data-role="lbnext"]',lb).addEventListener('click',function(){ show(cur+1); });
    q('[data-role="lbimg"]',lb).addEventListener('click',function(e){ if(e.target===e.currentTarget) close(); });
    lb.addEventListener('keydown',function(e){ if(e.key==='ArrowRight'){ e.preventDefault(); show(cur+1); } else if(e.key==='ArrowLeft'){ e.preventDefault(); show(cur-1); } });
    lb.addEventListener('touchstart',function(e){ touchX=e.changedTouches[0].clientX; },{passive:true});
    lb.addEventListener('touchend',function(e){ if(touchX===null) return; var dx=e.changedTouches[0].clientX-touchX; touchX=null; if(Math.abs(dx)>50) show(dx<0?cur+1:cur-1); },{passive:true});
    lb.addEventListener('close',function(){ document.documentElement.classList.remove('bk-lock'); img.src=''; });
  }

  // The two forms post to the plugin's REST endpoints, which save the entry and send the emails.
  function wire(f,btn,msg,o){
    if(!f||!btn||!msg) return;
    var label=btn.textContent;
    function say(t,cls){ msg.textContent=t; msg.className='bk-msg'+(cls?' '+cls:''); }
    f.addEventListener('submit',function(e){
      e.preventDefault();
      var d=o.data(f), bad=o.check(d,f); if(bad){ say(bad[0],'is-err'); bad[1].focus(); return; }
      d.website=f.website.value; d.page=location.href;
      btn.disabled=true; btn.textContent=o.busy; say('');
      var hdr={'Content-Type':'application/json'}, nonce=f.getAttribute('data-nonce'); if(nonce) hdr['X-WP-Nonce']=nonce;
      fetch(f.getAttribute('data-endpoint'),{method:'POST',headers:hdr,credentials:'same-origin',body:JSON.stringify(d)})
        .then(function(r){ return r.json().then(function(j){ return {ok:r.ok,j:j}; }); })
        .then(function(x){
          if(x.ok&&x.j&&x.j.ok){
            var done=document.createElement('div'); done.className='bk-done';
            done.innerHTML='<span class="eyebrow pk">'+o.done(x.j)[0]+'</span><span class="disp">'+o.done(x.j)[1]+'</span><p></p>';
            done.querySelector('p').textContent=x.j.message||o.done(x.j)[2];
            f.parentNode.replaceChild(done,f); done.scrollIntoView({block:'nearest'});
          } else { say((x.j&&x.j.message)||'Something went wrong. Please try again or email '+o.email+'.','is-err'); btn.disabled=false; btn.textContent=label; }
        })
        .catch(function(){ say('Could not reach the website just now. Please try again or email '+o.email+'.','is-err'); btn.disabled=false; btn.textContent=label; });
    });
  }
  var mail=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  // Reserve a seat.
  wire(document.getElementById('bk-rsvp-form'),document.getElementById('bk-rsvp-btn'),document.getElementById('bk-rsvp-msg'),{
    email:'<?php echo esc_js( $rsvp['email'] ); ?>', busy:'Reserving...',
    data:function(f){ return {name:f.name.value.trim(),business:f.business.value.trim(),email:f.email.value.trim(),phone:f.phone.value.trim(),guest:f.guest.value.trim(),dietary:f.dietary.value.trim()}; },
    check:function(d,f){ if(!d.name) return ['Please add your name.',f.name]; if(!d.business) return ['Please add your business.',f.business]; if(!mail.test(d.email)) return ['Please add a valid email address.',f.email]; return null; },
    done:function(j){ return [j.test?'Test reservation':'Seat reserved', j.duplicate?'You are already on the list':(j.test?'Saved as a test':'See you there'), 'Thanks, your seat is reserved.']; }
  });

  // Sponsorship: the package buttons pick the radio, the pills follow the radios, and the enquiry form posts.
  var sf=document.getElementById('bk-sp-form');
  if(sf){
    var pills=qa('.bk-radios label',sf);
    function pick(v){ pills.forEach(function(l){ var r=l.querySelector('input'); if(v!==undefined) r.checked=(r.value===v); l.classList.toggle('is-on',r.checked); }); }
    pills.forEach(function(l){ var r=l.querySelector('input'); r.addEventListener('change',function(){ pick(); }); r.addEventListener('focus',function(){ l.classList.add('is-focus'); }); r.addEventListener('blur',function(){ l.classList.remove('is-focus'); }); });
    qa('[data-tier]').forEach(function(b){ b.addEventListener('click',function(){ pick(b.getAttribute('data-tier')); setTimeout(function(){ if(sf.business) sf.business.focus({preventScroll:true}); },400); }); });
    wire(sf,document.getElementById('bk-sp-btn'),document.getElementById('bk-sp-msg'),{
      email:'<?php echo esc_js( $sp['to'] ); ?>', busy:'Sending...',
      data:function(f){ var t=f.querySelector('input[name="tier"]:checked'); return {business:f.business.value.trim(),name:f.name.value.trim(),email:f.email.value.trim(),phone:f.phone.value.trim(),tier:t?t.value:'unsure',message:f.message.value.trim()}; },
      check:function(d,f){ if(!d.business) return ['Please add your business.',f.business]; if(!d.name) return ['Please add your name.',f.name]; if(!mail.test(d.email)) return ['Please add a valid email address.',f.email]; return null; },
      done:function(j){ return [j.test?'Test enquiry':'Enquiry sent', j.test?'Saved as a test':'Thanks, we will be in touch', 'Thanks, <?php echo esc_js( $sp['contact'] ); ?> will be in touch.']; }
    });
  }
})();
</script>
