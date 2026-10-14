<?php
/**
 * Entries open: the full awards page, published privately as a staff preview. Rendered by [atfa_awards_entries].
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$winners = atfa_awards_winners();
$logo    = atfa_awards_img( 'atfa-logo-white.png' );
$cats    = atfa_awards_categories();
$streams = array( 'member' => array(), 'panel' => array(), 'excellence' => array() );
foreach ( $cats as $c ) {
	if ( isset( $streams[ $c['stream'] ] ) ) {
		$streams[ $c['stream'] ][] = $c;
	}
}
$stream_labels = array(
	'member'     => 'Member voted · Project award',
	'panel'      => 'Expert panel · Project award',
	'excellence' => 'Excellence award',
	'newcomers'  => 'Expert panel selected',
	'major'      => 'Major award',
);
?>
<style>
html{background:#0C0B0A !important}body{background:#0C0B0A !important;margin:0 !important;padding:0 !important}
.aw{position:relative;min-height:100vh;box-sizing:border-box;background:#0C0B0A;color:#F4EFE7;font-family:'Montserrat',system-ui,sans-serif;-webkit-font-smoothing:antialiased;line-height:1.4;overflow-x:clip}
.aw *,.aw *:before,.aw *:after{box-sizing:border-box}
.aw a{color:#F8931F;text-decoration:none}.aw a:hover{color:#FFA640}
.aw p{margin:0}.aw h1,.aw h2{margin:0;color:#F4EFE7}
.aw .disp{font-family:'Barlow Condensed','Arial Narrow',sans-serif;text-transform:uppercase;letter-spacing:.01em;line-height:.92;text-wrap:balance}
.aw .eyebrow{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#B4A99A;font-weight:600}
.aw .btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:52px;padding:0 26px;border-radius:4px;font-weight:600;font-size:15px;text-decoration:none;border:1px solid transparent;cursor:pointer;font-family:inherit;white-space:nowrap;line-height:1;text-transform:none;letter-spacing:0;box-shadow:none}
.aw .btn-primary{background:#F8931F;color:#1A1208;border-color:#F8931F}.aw .btn-primary:hover{background:#FFA640;color:#1A1208}
.aw .btn-ghost{background:transparent;color:#F4EFE7;border-color:#3A332D}.aw .btn-ghost:hover{color:#FFFFFF;border-color:#F4EFE7}
.aw .wrap{max-width:1280px;margin:0 auto;padding:0 40px}
.aw .card{background:#171412;border:1px solid #2B2622;border-radius:6px}
.aw .tag{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:4px 8px;border-radius:3px;line-height:1.2}
.aw .tag-new{background:#F8931F;color:#1A1208}
.aw .tag-dim{background:#2B2622;color:#B4A99A}
.aw ul.cats{list-style:none;margin:0;padding:0;display:flex;flex-direction:column}
.aw ul.cats li{padding:0;margin:0;border-top:1px solid #2B2622;font-size:15px;line-height:1.35;display:block;color:#F4EFE7}
/* Category rows: the whole row is a button that opens the category's info panel. */
.aw .cat-row{display:flex;width:100%;justify-content:space-between;align-items:center;gap:12px;padding:12px 0;margin:0;background:none;border:0;border-radius:0;color:#F4EFE7;font:inherit;text-align:left;cursor:pointer;box-shadow:none;text-transform:none;letter-spacing:0;line-height:1.35}
.aw .cat-row .cat-n{min-width:0}
.aw .cat-row .cat-more{display:inline-flex;align-items:center;gap:5px;flex-shrink:0;font-size:12px;font-weight:600;color:#7D7368;white-space:nowrap;transition:color .2s}
.aw .cat-row:hover{color:#FFFFFF}.aw .cat-row:hover .cat-more{color:#F8931F}
.aw .cat-row:focus-visible{outline:2px solid #F8931F;outline-offset:2px}
.aw .btn-dark{background:#1A1208;color:#F8931F;border-color:#1A1208}.aw .btn-dark:hover{background:#000;color:#FFA640}
/* Category info panels: one dialog per award. Full-screen sheet on phones. */
.aw dialog.catd{position:fixed;inset:0;margin:auto;width:min(1060px,calc(100% - 32px));max-width:none;max-height:calc(100vh - 32px);max-height:calc(100dvh - 32px);padding:0;border:1px solid #2B2622;border-radius:8px;background:#121010;color:#F4EFE7;overflow:auto;overscroll-behavior:contain;box-shadow:0 30px 80px rgba(0,0,0,.6)}
.aw dialog.catd::backdrop{background:rgba(0,0,0,.74);backdrop-filter:blur(2px)}
.aw .catd-in{display:flex;flex-direction:column;min-height:100%}
.aw .catd-head{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;padding:26px 36px 20px;border-bottom:1px solid #2B2622;position:sticky;top:0;background:#121010;z-index:2}
.aw .catd-head h3{font-size:42px;font-weight:700;margin:0;color:#F4EFE7;line-height:.95}
.aw .catd-lead{font-size:15px;color:#B4A99A;line-height:1.5;margin:12px 0 0;max-width:62ch}
.aw .catd-sp{display:flex;flex-direction:column;gap:8px;align-items:flex-start;flex-shrink:0;width:200px;margin-left:auto}
.aw .catd-sp .eyebrow{font-size:10px;letter-spacing:.14em;color:#7D7368}
.aw .catd-sp .sponsor{width:100%;height:64px;font-size:11px}
.aw .catd-sp .sponsor.has-logo{padding:10px 16px}
.aw .catd-bar{display:none}
.aw .catd-close-foot{display:none}
.aw .catd-x{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;flex-shrink:0;margin:-6px -8px 0 0;padding:0;background:none;border:1px solid #2B2622;border-radius:50%;color:#B4A99A;cursor:pointer;box-shadow:none}
.aw .catd-x:hover{color:#FFFFFF;border-color:#F4EFE7}
.aw .catd-grid{display:grid;grid-template-columns:1.05fr 1fr;grid-template-rows:auto 1fr;gap:28px 40px;padding:26px 36px 30px}
.aw .catd-grid > section{min-width:0}
.aw .catd-grid > .rules{grid-column:2;grid-row:1 / span 2}
.aw .catd-grid > .exs{grid-column:1;grid-row:2}
.aw dialog.noex .catd-grid{grid-template-rows:auto}
.aw dialog.noex .catd-grid > .rules{grid-row:1}
.aw .catd-grid h4{font-family:'Barlow Condensed','Arial Narrow',sans-serif;text-transform:uppercase;letter-spacing:.04em;font-size:22px;font-weight:700;color:#F8931F;margin:0 0 12px;line-height:1}
.aw .catd-grid p{font-size:15px;line-height:1.55;color:#E6DFD4}
.aw .catd-grid .not{margin-top:14px;padding:12px 14px;border-left:2px solid #F8931F;background:#1A1512;font-size:13.5px;line-height:1.5;color:#B4A99A;border-radius:0 4px 4px 0}
.aw .catd-grid .not strong{color:#F4EFE7;font-weight:600}
.aw .catd-grid ul{margin:0;padding:0 0 0 18px}
.aw .catd-grid ul li{font-size:13.5px;line-height:1.5;color:#D9D0C4;margin:0 0 9px;padding:0}
.aw .catd-grid ul li::marker{color:#F8931F}
.aw .catd-grid .fine{font-size:12px;color:#7D7368;line-height:1.5;margin-top:14px}
.aw .catd-grid .fine a{color:#B4A99A;text-decoration:underline}
.aw .exgrid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.aw .ex{margin:0;min-width:0}
.aw .ex-open{display:block;width:100%;padding:0;margin:0;background:none;border:0;border-radius:4px;cursor:zoom-in;box-shadow:none;overflow:hidden}
.aw .ex img{display:block;width:100%;height:auto;aspect-ratio:4/3;object-fit:cover;border-radius:4px;background:#211D19;margin:0;border:0;max-width:none;transition:transform .5s cubic-bezier(.2,.6,.2,1)}
.aw .ex-open:hover img{transform:scale(1.04)}
.aw .ex figcaption{font-size:11.5px;color:#B4A99A;line-height:1.35;margin-top:7px}
.aw .ex figcaption strong{display:block;color:#F4EFE7;font-weight:600}
.aw .catd-foot{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;padding:20px 36px 28px;border-top:1px solid #2B2622;margin-top:auto}
.aw .catd-nav{display:flex;gap:10px}
.aw .catd-nav .btn{min-height:44px;padding:0 16px;font-size:13px}
.aw dialog.lb{position:fixed;inset:0;margin:auto;padding:0;border:0;background:transparent;width:auto;max-width:calc(100% - 32px);max-height:calc(100dvh - 32px);cursor:zoom-out;overflow:visible}
.aw dialog.lb::backdrop{background:rgba(0,0,0,.86)}
.aw dialog.lb img{display:block;max-width:100%;max-height:calc(100dvh - 32px);width:auto;height:auto;border-radius:6px;margin:0;border:0}
html.aw-lock{overflow:hidden}
@media (max-width:1000px){.aw .catd-grid{grid-template-columns:1fr 1fr;grid-template-rows:auto auto}.aw .catd-grid > .rules{grid-row:1}.aw .catd-grid > .exs{grid-column:1 / -1;grid-row:2}.aw .exgrid{grid-template-columns:repeat(4,1fr)}}
@media (max-height:640px){.aw .catd-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;position:sticky;top:0;z-index:3;background:#121010;border-bottom:1px solid #2B2622;padding:8px 12px 8px 20px;min-height:52px}.aw .catd-bar .t{font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#B4A99A;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}.aw .catd-bar .catd-x{margin:0}.aw .catd-head{position:static}.aw .catd-head > .catd-x{display:none}.aw .catd-head h3{font-size:34px}.aw .catd-close-foot{display:inline-flex}}
@media (max-width:700px){.aw dialog.catd{width:100%;height:100vh;height:100dvh;max-height:100vh;max-height:100dvh;border-radius:0;border:0;margin:0}.aw .catd-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;position:sticky;top:0;z-index:3;background:#121010;border-bottom:1px solid #2B2622;padding:8px 12px 8px 20px;min-height:58px}.aw .catd-bar .t{font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#B4A99A;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}.aw .catd-bar .catd-x{margin:0}.aw .catd-head{position:static;padding:18px 20px 16px;flex-wrap:wrap;gap:14px}.aw .catd-head > .catd-x{display:none}.aw .catd-head .eyebrow{font-size:11px;letter-spacing:.1em}.aw .catd-head h3{font-size:30px}.aw .catd-head h3 .ofy{display:none}.aw .catd-lead{font-size:14px;margin-top:8px}.aw .catd-sp{width:100%;flex-direction:row;align-items:center;gap:12px;margin:0}.aw .catd-sp .sponsor{width:auto;flex:1 1 120px;max-width:170px;height:52px}.aw .catd-grid{grid-template-columns:1fr;grid-template-rows:auto;gap:26px;padding:20px 20px 24px}.aw .catd-grid > .rules{grid-column:1;grid-row:auto;order:1}.aw .catd-grid > .exs{grid-column:1;grid-row:auto}.aw .catd-grid h4{font-size:20px}.aw .catd-grid p{font-size:14.5px}.aw .catd-grid .not{font-size:13px}.aw .catd-grid ul li{font-size:13.5px}.aw .exgrid{grid-template-columns:1fr 1fr;gap:10px}.aw .catd-foot{padding:16px 20px calc(20px + env(safe-area-inset-bottom));gap:12px}.aw .catd-foot .btn-primary{width:100%}.aw .catd-nav{width:100%}.aw .catd-nav .btn{flex:1}.aw .catd-close-foot{display:inline-flex;width:100%}}
.aw .step{display:flex;flex-direction:column;gap:12px;padding:24px;border-top:2px solid #2B2622;min-width:0}
.aw .step .n{font-family:'Barlow Condensed','Arial Narrow',sans-serif;font-size:40px;font-weight:700;color:#F8931F;line-height:1}
.aw .tl{display:flex;flex-direction:column;gap:8px;padding:18px 20px;border-top:2px solid #2B2622;min-width:0}
.aw .tl.now{border-top-color:#F8931F}
.aw .sponsor{display:flex;align-items:center;justify-content:center;height:84px;border:1px dashed #3A332D;border-radius:4px;color:#7D7368;font-size:12px;letter-spacing:.1em;text-transform:uppercase}
.aw .sponsor.has-logo{border:1px solid #2B2622;background:#171412;padding:14px 18px}.aw .sponsor img{max-height:100%;max-width:100%;width:auto;height:auto;object-fit:contain;margin:0;border:0}
.aw .hero-h1{font-size:172px;font-weight:700;margin:16px 0 0;color:#F4EFE7}
/* The atfa wordmark stands in for the word Flooring on the first line of the headline. */
.aw .wm{display:block;height:.9em;width:auto;margin:0 0 .1em}
/* Hero backdrop: past winners sit behind the header and hero on the right, masked so they fade to black on the left. They rotate with a slow crossfade and a different winner leads each visit. */
.aw .bgwrap{position:absolute;inset:0;overflow:hidden;--o:.88;opacity:var(--o);animation:aw-fadein 1.8s ease-out both}
.aw .bg{position:absolute;top:0;right:0;width:70%;height:100%;object-fit:cover;object-position:50% 50%;opacity:0;transform-origin:60% 50%;margin:0;border:0;max-width:none;
  -webkit-mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.25) 20%,rgba(0,0,0,1) 50%);mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.25) 20%,rgba(0,0,0,1) 50%);
  animation:aw-show 48s linear infinite both,aw-kb 48s cubic-bezier(.2,.6,.2,1) infinite both}
.aw .veil{position:absolute;inset:0;background:linear-gradient(90deg,rgba(12,11,10,.85) 32%,rgba(12,11,10,.5) 50%,rgba(12,11,10,0) 66%),linear-gradient(0deg,#0C0B0A 0%,rgba(12,11,10,0) 26%),linear-gradient(180deg,rgba(12,11,10,.7) 0%,rgba(12,11,10,0) 20%)}
.aw .caps{position:relative;width:420px;max-width:100%;height:50px}
.aw .cap{position:absolute;right:0;bottom:0;width:max-content;max-width:100%;font-size:12px;color:#F4EFE7;background:rgba(12,11,10,.72);padding:7px 11px;border-radius:3px;line-height:1.45;opacity:0;animation:aw-showcap 48s linear infinite both;text-align:left}
.aw .cap strong{font-weight:600;color:#F4EFE7}
.aw .bg.lead,.aw .cap.lead{opacity:1}
@keyframes aw-show{0%{opacity:0;z-index:3}3.333%{opacity:1;z-index:3}3.4%{opacity:1;z-index:2}20%{opacity:1;z-index:2}20.1%{opacity:1;z-index:1}23.333%{opacity:0;z-index:1}100%{opacity:0;z-index:1}}
@keyframes aw-showcap{0%,1.667%{opacity:0}3.333%,18.333%{opacity:1}20%,100%{opacity:0}}
@keyframes aw-kb{0%{transform:scale(1.07)}23.333%{transform:scale(1)}100%{transform:scale(1)}}
@keyframes aw-fadein{from{opacity:0}to{opacity:var(--o)}}
@keyframes aw-rise{from{opacity:0;transform:translateY(26px)}to{opacity:1;transform:translateY(0)}}
.aw .ld{animation:aw-rise .9s cubic-bezier(.22,.61,.36,1) both}
.aw .ld.d1{animation-delay:.05s}.aw .ld.d2{animation-delay:.18s}.aw .ld.d3{animation-delay:.3s}.aw .ld.d4{animation-delay:.42s}.aw .ld.d5{animation-delay:.54s}.aw .ld.d6{animation-delay:.66s}
@supports (animation-timeline: view()){.aw .sr{animation:aw-rise linear both;animation-timeline:view();animation-range:entry 0% entry 32%}}
.aw .win{display:flex;flex-direction:column;gap:8px;min-width:0;text-decoration:none;color:#F4EFE7}
.aw .win .ph{position:relative;aspect-ratio:4/3;width:100%;max-width:100%;border-radius:6px;overflow:hidden;background:#211D19;display:block}
.aw .win .ph img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:transform .6s cubic-bezier(.2,.6,.2,1);margin:0;border:0;max-width:none}
.aw .win:hover .ph img{transform:scale(1.04)}
.aw .win .c{font-size:12px;color:#B4A99A;line-height:1.4}
.aw .win .c strong{color:#F4EFE7;display:block;font-weight:600}
@media (prefers-reduced-motion:reduce){.aw .ld,.aw .sr,.aw .bgwrap{animation:none}.aw .bg,.aw .cap{animation-play-state:paused}}
@media (max-width:900px){.aw .hero-h1{font-size:104px}.aw .bg{width:100%}.aw .bgwrap{--o:.5}.aw .winners{grid-template-columns:repeat(2,minmax(0,1fr)) !important}.aw .whatsnew > div{grid-column:span 12 !important}.aw .grid3{grid-template-columns:repeat(1,minmax(0,1fr)) !important}.aw .hero{grid-template-columns:repeat(1,minmax(0,1fr)) !important}.aw .hero > div{grid-column:span 1 !important}.aw .two{grid-template-columns:repeat(1,minmax(0,1fr)) !important}.aw .steps{grid-template-columns:repeat(2,minmax(0,1fr)) !important}.aw .timeline{grid-template-columns:repeat(2,minmax(0,1fr)) !important}.aw .sponsors{grid-template-columns:repeat(2,minmax(0,1fr)) !important}.aw .dinner{grid-template-columns:repeat(1,minmax(0,1fr)) !important}.aw .dinner > div{grid-column:span 1 !important}.aw .dinner .disp{font-size:56px !important}}
@media (max-width:560px){.aw .wrap{padding:0 20px}.aw .hero-h1{font-size:84px}.aw .two .disp{font-size:44px !important}.aw .steps{grid-template-columns:repeat(1,minmax(0,1fr)) !important}.aw .timeline{grid-template-columns:repeat(1,minmax(0,1fr)) !important}.aw .caps{height:auto}.aw .cap{position:static;display:none}.aw .cap.lead{display:block}}
</style>
<div class="aw" id="aw-main">

<div style="position:relative;overflow:hidden">
<div class="bgwrap" aria-hidden="true">
<?php foreach ( $winners as $i => $w ) : ?>
    <img class="bg<?php echo 0 === $i ? ' lead' : ''; ?>" src="<?php echo esc_url( atfa_awards_img( $w['img'] ) ); ?>" alt="" style="animation-delay:<?php echo (int) ( $i * 8 - 2 ); ?>s;object-position:<?php echo esc_attr( $w['pos'] ); ?>">
<?php endforeach; ?>
</div>
<div class="veil" aria-hidden="true"></div>

<div class="wrap" style="position:relative">
<div class="ld d1" style="display:flex;align-items:center;justify-content:space-between;gap:24px;padding:22px 0;flex-wrap:wrap;border-bottom:1px solid rgba(244,239,231,.12)">
  <a href="https://www.atfa.com.au/" style="display:inline-flex;align-items:center;gap:8px;color:#B4A99A;font-size:13px;font-weight:600">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
    atfa.com.au
  </a>
  <a href="https://www.atfa.com.au/"><img src="<?php echo esc_url( $logo ); ?>" alt="ATFA Australasian Timber Flooring Association" style="height:46px;width:auto;display:block"></a>
  <div style="display:flex;gap:12px;flex-wrap:wrap">
    <a class="btn btn-ghost" href="https://www.atfa.com.au/my-membership/">Member login</a>
    <a class="btn btn-primary" href="#how-it-works">Enter now</a>
  </div>
</div>
</div>

<div class="wrap" style="position:relative">
<div class="hero" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px;align-items:end;padding:88px 0 64px">
  <div style="grid-column:span 7;min-width:0;display:flex;flex-direction:column">
    <div class="eyebrow ld d2">ATFA Flooring Awards · Sydney · Saturday 22 May 2027</div>
    <h1 class="disp hero-h1 ld d3"><img class="wm" src="<?php echo esc_url( atfa_awards_img( 'atfa-wordmark.svg' ) ); ?>" alt="ATFA">Awards <span style="color:#F8931F">2027</span></h1>
    <p class="ld d4" style="font-size:22px;max-width:34ch;line-height:1.4;color:#B4A99A;margin:28px 0 0">Celebrating outstanding floors and the people behind them. Twenty awards, three ways to win.</p>
    <div class="ld d5" style="display:flex;gap:14px;flex-wrap:wrap;margin-top:32px">
      <a class="btn btn-primary" href="#how-it-works">Enter now</a>
      <a class="btn btn-ghost" href="#categories">See the categories</a>
    </div>
    <p class="ld d6" style="font-size:13px;color:#B4A99A;margin:18px 0 0;max-width:44ch;line-height:1.5">Takes about ten minutes to enter, either from your phone or desktop. Photos go straight from your camera roll. This year is easier than ever!</p>
  </div>
  <div class="caps ld d5" style="grid-column:span 5;min-width:0;justify-self:end;align-self:end">
<?php foreach ( $winners as $i => $w ) : ?>
    <span class="cap<?php echo 0 === $i ? ' lead' : ''; ?>" style="animation-delay:<?php echo (int) ( $i * 8 - 2 ); ?>s"><?php echo esc_html( $w['cat'] ); ?><br><strong><?php echo esc_html( $w['biz'] ); ?></strong></span>
<?php endforeach; ?>
  </div>
</div>
</div>
</div>

<div class="wrap sr">
<div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:24px;padding:20px 28px;flex-wrap:wrap">
  <div style="display:flex;align-items:center;gap:14px;min-width:0;flex-wrap:wrap">
    <span style="width:10px;height:10px;border-radius:50%;background:#5BBF7A;box-shadow:0 0 0 4px rgba(91,191,122,.18);flex-shrink:0"></span>
    <span class="disp" style="font-size:28px;font-weight:700;color:#F4EFE7">Entries open</span>
    <span style="color:#B4A99A;font-size:15px">Close Wednesday 31 March 2027, 11:59 pm AEDT</span>
  </div>
  <div style="display:flex;align-items:baseline;gap:10px">
    <span class="disp" id="aw-days" style="font-size:44px;font-weight:700;color:#F8931F;font-variant-numeric:tabular-nums">169</span>
    <span style="color:#B4A99A;font-size:14px">days to go · open to current financial ATFA members</span>
  </div>
</div>
</div>

<div class="wrap sr" style="margin-top:72px">
<div style="display:flex;justify-content:space-between;align-items:end;gap:24px;flex-wrap:wrap;margin-bottom:18px">
  <h2 class="disp" style="font-size:56px;font-weight:700">Floors that have won</h2>
  <span style="color:#7D7368;font-size:13px">The 2025 winners. Yours could be next.</span>
</div>
<div class="winners" style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px">
<?php foreach ( $winners as $w ) : ?>
  <a class="win" href="https://www.atfa.com.au/member-media/2025-awards-dinner-presentation/"><span class="ph"><img src="<?php echo esc_url( atfa_awards_img( $w['img'] ) ); ?>" alt="" loading="lazy"></span><span class="c"><strong><?php echo esc_html( $w['biz'] ); ?></strong><?php echo esc_html( $w['cat'] ); ?></span></a>
<?php endforeach; ?>
</div>
</div>

<div class="wrap sr" style="margin-top:72px">
<div class="eyebrow" style="margin-bottom:18px">The 2027 awards season · proposed dates</div>
<div class="timeline" style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px">
  <div class="tl now"><span class="eyebrow" style="color:#F8931F">Now</span><span class="disp" style="font-size:30px;font-weight:600">Entries open</span><span style="color:#B4A99A;font-size:14px">Mon 1 Feb to Wed 31 Mar</span></div>
  <div class="tl"><span class="eyebrow">Step 2</span><span class="disp" style="font-size:30px;font-weight:600">Eligibility check</span><span style="color:#B4A99A;font-size:14px">1 to 9 Apr · ATFA reviews every entry</span></div>
  <div class="tl"><span class="eyebrow">Step 3</span><span class="disp" style="font-size:30px;font-weight:600">Members vote</span><span style="color:#B4A99A;font-size:14px">Mon 12 Apr to Sun 2 May · panel judges at the same time</span></div>
  <div class="tl"><span class="eyebrow">Step 4</span><span class="disp" style="font-size:30px;font-weight:600">Finalists announced</span><span style="color:#B4A99A;font-size:14px">Wed 5 May · top three in every project category</span></div>
  <div class="tl"><span class="eyebrow">Step 5</span><span class="disp" style="font-size:30px;font-weight:600">Awards dinner</span><span style="color:#B4A99A;font-size:14px">Sat 22 May · Novotel Sydney Olympic Park</span></div>
</div>
</div>

<div class="wrap sr" style="margin-top:72px">
<div style="display:flex;justify-content:space-between;align-items:end;gap:24px;flex-wrap:wrap;margin-bottom:18px">
  <h2 class="disp" style="font-size:56px;font-weight:700">New in 2027</h2>
  <span style="color:#7D7368;font-size:13px">The biggest changes to the awards in years</span>
</div>
<div class="whatsnew" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px">
  <div style="grid-column:span 6;min-width:0;background:#F8931F;color:#1A1208;border-radius:6px;padding:28px;display:flex;flex-direction:column;gap:10px">
    <span class="tag" style="background:#1A1208;color:#F8931F;align-self:flex-start">New</span>
    <span class="disp" style="font-size:44px;font-weight:700;color:#1A1208">Overseas and school projects can now be entered</span>
    <p style="font-size:15px;line-height:1.5;max-width:56ch;color:#1A1208">The three expert panel categories accept projects outside Australia and New Zealand, and work installed in a school or learning environment. Disclose it clearly in your entry and meet the other rules, and the panel judges it on the same footing as everything else.</p>
  </div>
  <div class="card" style="grid-column:span 3;min-width:0;padding:22px;display:flex;flex-direction:column;gap:8px">
    <span class="eyebrow" style="color:#F8931F">New</span>
    <span class="disp" style="font-size:30px;font-weight:700">Expert panel judging</span>
    <span style="color:#B4A99A;font-size:13px;line-height:1.5">Three technical categories judged on difficulty and craft by an appointed panel, not by popularity.</span>
  </div>
  <div class="card" style="grid-column:span 3;min-width:0;padding:22px;display:flex;flex-direction:column;gap:8px">
    <span class="eyebrow" style="color:#F8931F">New</span>
    <span class="disp" style="font-size:30px;font-weight:700">3-2-1 member voting</span>
    <span style="color:#B4A99A;font-size:13px;line-height:1.5">Rank your top three floors in each category: your first pick gets 3 votes, your second 2 votes, your third 1 vote. One ballot per membership.</span>
  </div>
  <div class="card" style="grid-column:span 3;min-width:0;padding:22px;display:flex;flex-direction:column;gap:8px">
    <span class="eyebrow" style="color:#F8931F">New</span>
    <span class="disp" style="font-size:30px;font-weight:700">Parquetry Floor</span>
    <span style="color:#B4A99A;font-size:13px;line-height:1.5">Parquetry gets its own member-voted category.</span>
  </div>
  <div class="card" style="grid-column:span 3;min-width:0;padding:22px;display:flex;flex-direction:column;gap:8px">
    <span class="eyebrow" style="color:#F8931F">New</span>
    <span class="disp" style="font-size:30px;font-weight:700">Newcomers Award</span>
    <span style="color:#B4A99A;font-size:13px;line-height:1.5">For members in their first five years who have never won or placed. Tick the box on any member-voted entry.</span>
  </div>
  <div class="card" style="grid-column:span 6;min-width:0;padding:22px;display:flex;flex-direction:column;gap:8px">
    <span class="eyebrow" style="color:#F8931F">Easier</span>
    <span class="disp" style="font-size:30px;font-weight:700">Enter and vote from your phone</span>
    <span style="color:#B4A99A;font-size:13px;line-height:1.5">Photos straight from your camera roll, drafts that save as you go, and voting that takes five minutes.</span>
  </div>
</div>
</div>

<div class="wrap sr" style="margin-top:72px">
<div class="eyebrow" style="margin-bottom:16px">Category sponsors</div>
<div class="sponsors" style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px">
<?php foreach ( atfa_awards_sponsors() as $sp ) : ?>
  <?php echo atfa_awards_sponsor_tile( $sp ); ?>
<?php endforeach; ?>
</div>
</div>

<div class="wrap sr" id="categories" style="margin-top:96px">
<div style="display:flex;justify-content:space-between;align-items:end;gap:24px;flex-wrap:wrap;margin-bottom:28px">
  <h2 class="disp" style="font-size:72px;font-weight:700;max-width:14ch">Twenty awards. Three ways to win.</h2>
  <p style="max-width:44ch;color:#B4A99A;font-size:15px;line-height:1.5">Project awards are decided by members or by the new expert panel. Excellence awards recognise people, businesses and products. Tap any award for what goes in it, the rules, and examples from 2025.</p>
</div>
<div class="grid3" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px">

  <div class="card" style="padding:28px;display:flex;flex-direction:column;gap:18px;min-width:0">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px"><span class="eyebrow" style="color:#F8931F">Member voted</span><span class="tag tag-dim"><?php echo count( $streams['member'] ); ?> categories</span></div>
    <div class="disp" style="font-size:40px;font-weight:700">Project awards</div>
    <p style="color:#B4A99A;font-size:14px;line-height:1.5">Members rank their top three floors in each category: 3 votes, 2 votes, 1 vote. Winner and runner-up in every category. Australia and New Zealand projects only. 5 to 10 photos.</p>
    <ul class="cats">
<?php foreach ( $streams['member'] as $c ) : ?>
      <li><?php echo atfa_awards_cat_row( $c ); ?></li>
<?php endforeach; ?>
    </ul>
  </div>

  <div class="card" style="padding:28px;display:flex;flex-direction:column;gap:18px;min-width:0;border-color:#F8931F">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px"><span class="eyebrow" style="color:#F8931F">Expert panel</span><span class="tag tag-new">New for 2027</span></div>
    <div class="disp" style="font-size:40px;font-weight:700">Project awards</div>
    <p style="color:#B4A99A;font-size:14px;line-height:1.5">Judged on difficulty, craft and outcome by an ATFA-appointed panel. No member voting. Winner and runner-up in each. 8 to 15 photos plus a technical statement.</p>
    <ul class="cats">
<?php foreach ( $streams['panel'] as $c ) : ?>
      <li><?php echo atfa_awards_cat_row( $c ); ?></li>
<?php endforeach; ?>
    </ul>
    <div style="border:1px solid #F8931F;border-radius:6px;padding:14px 16px;display:flex;flex-direction:column;gap:6px;background:#1F1711">
      <span class="tag tag-new" style="align-self:flex-start">New</span>
      <span style="font-size:14px;font-weight:700;line-height:1.4">School or learning-environment work and overseas projects are permitted if clearly disclosed and all other rules are met.</span>
    </div>
    <p style="margin-top:auto;color:#7D7368;font-size:13px;line-height:1.5">Judging images are anonymous: no logos, watermarks or branding.</p>
  </div>

  <div class="card" style="padding:28px;display:flex;flex-direction:column;gap:18px;min-width:0">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px"><span class="eyebrow" style="color:#F8931F">Excellence</span><span class="tag tag-dim"><?php echo count( $streams['excellence'] ); ?> categories</span></div>
    <div class="disp" style="font-size:40px;font-weight:700">People, business &amp; products</div>
    <p style="color:#B4A99A;font-size:14px;line-height:1.5">A nomination statement with supporting evidence. One winner per category, no runner-up. Judged by ATFA.</p>
    <ul class="cats">
<?php foreach ( $streams['excellence'] as $c ) : ?>
      <li><?php echo atfa_awards_cat_row( $c ); ?></li>
<?php endforeach; ?>
    </ul>
  </div>
</div>

<div class="two" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-top:20px">
  <div style="background:#F8931F;color:#1A1208;border-radius:6px;padding:32px;display:flex;flex-direction:column;gap:12px;min-width:0">
    <span class="eyebrow" style="color:#1A1208">Expert panel selected</span>
    <div class="disp" style="font-size:56px;font-weight:700;color:#1A1208">Newcomers Award</div>
    <p style="font-size:15px;line-height:1.5;max-width:52ch;color:#1A1208">In your first five years of membership and never won an ATFA award or been a runner-up? Tick the Newcomers box on any member-voted entry. No separate entry, and you do not need to place in your category. ATFA checks eligibility against its records.</p>
    <button type="button" class="btn btn-dark" data-cat="newcomers" style="align-self:flex-start;margin-top:6px">More info</button>
  </div>
  <div class="card" style="padding:32px;display:flex;flex-direction:column;gap:12px;min-width:0">
    <span class="eyebrow" style="color:#F8931F">Major award</span>
    <div class="disp" style="font-size:56px;font-weight:700">Floor of the Year</div>
    <p style="font-size:15px;line-height:1.5;color:#B4A99A;max-width:52ch">The overall winner and overall runner-up, chosen from the project category winners and runners-up. No separate entry required.</p>
    <button type="button" class="btn btn-ghost" data-cat="foty" style="align-self:flex-start;margin-top:6px">More info</button>
  </div>
</div>
</div>

<div class="wrap sr" id="how-it-works" style="margin-top:96px">
<h2 class="disp" style="font-size:72px;font-weight:700;margin:0 0 28px">How it works</h2>
<div class="steps" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px">
  <div class="step"><span class="n">1</span><span class="disp" style="font-size:30px;font-weight:600">Enter on your phone</span><span style="color:#B4A99A;font-size:14px;line-height:1.5">Log in with your ATFA website login (the same one as My Membership), pick a category, add photos from your camera roll. Save a draft and finish later. Up to three projects per category.</span></div>
  <div class="step"><span class="n">2</span><span class="disp" style="font-size:30px;font-weight:600">ATFA checks every entry</span><span style="color:#B4A99A;font-size:14px;line-height:1.5">Financial membership, project location, completion date and category fit are confirmed before anything goes to a vote or a judge.</span></div>
  <div class="step"><span class="n">3</span><span class="disp" style="font-size:30px;font-weight:600">Members vote, panel judges</span><span style="color:#B4A99A;font-size:14px;line-height:1.5">One ballot per membership in each member-voted category: 3 votes for your first pick, 2 for your second, 1 for your third. The expert panel scores its three categories and picks the Newcomers winner.</span></div>
  <div class="step"><span class="n">4</span><span class="disp" style="font-size:30px;font-weight:600">Finalists, then the dinner</span><span style="color:#B4A99A;font-size:14px;line-height:1.5">Top three in each project category announced beforehand. Winners and runners-up revealed on the night.</span></div>
</div>
</div>

<div class="wrap sr" style="margin-top:96px">
<div class="card dinner" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:24px;padding:40px;align-items:center">
  <div style="grid-column:span 8;min-width:0;display:flex;flex-direction:column;gap:10px">
    <span class="eyebrow" style="color:#F8931F">Awards dinner</span>
    <div class="disp" style="font-size:80px;font-weight:700">Saturday 22 May 2027</div>
    <div style="font-size:18px;color:#B4A99A">Novotel Sydney Olympic Park · held with the ATFA Expo &amp; Conference 2027</div>
  </div>
  <div style="grid-column:span 4;min-width:0;display:flex;flex-direction:column;gap:12px;align-items:flex-start">
    <a class="btn btn-ghost" href="https://www.atfa.com.au/events/">Tickets on sale soon</a>
    <span style="font-size:13px;color:#7D7368">Finalists are told first so they can book travel.</span>
  </div>
</div>
</div>

<div class="wrap sr" style="margin-top:72px;padding-bottom:48px">
<div style="border-top:1px solid #2B2622;padding-top:24px;display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;font-size:13px;color:#7D7368">
  <div style="display:flex;gap:20px;flex-wrap:wrap">
    <a href="https://www.atfa.com.au/" style="color:#B4A99A">atfa.com.au</a>
    <a href="https://www.atfa.com.au/terms-conditions/" style="color:#B4A99A">Awards entry terms &amp; conditions</a>
    <a href="https://www.atfa.com.au/contact/" style="color:#B4A99A">Contact ATFA</a>
  </div>
  <div>AU 1300 361 693 · NZ 0800 453 715 · © Australasian Timber Flooring Association</div>
</div>
</div>


<?php foreach ( $cats as $c ) : ?>
<dialog class="catd<?php echo $c['examples'] ? '' : ' noex'; ?>" id="cat-<?php echo esc_attr( $c['key'] ); ?>" aria-labelledby="cat-<?php echo esc_attr( $c['key'] ); ?>-t">
  <div class="catd-in">
    <div class="catd-bar"><span class="t"><?php echo esc_html( $c['name'] ); ?></span><button type="button" class="catd-x" data-close aria-label="Close"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div>
    <div class="catd-head">
      <div style="min-width:0">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px"><span class="eyebrow" style="color:#F8931F"><?php echo esc_html( $stream_labels[ $c['stream'] ] ); ?></span><?php if ( ! empty( $c['new'] ) ) : ?><span class="tag tag-new">New for 2027</span><?php endif; ?></div>
        <h3 class="disp" id="cat-<?php echo esc_attr( $c['key'] ); ?>-t"><?php echo atfa_awards_title_html( $c['full'] ); ?></h3>
        <p class="catd-lead"><?php echo esc_html( $c['lead'] ); ?></p>
      </div>
      <div class="catd-sp">
        <span class="eyebrow">Proudly sponsored by</span>
        <?php echo atfa_awards_sponsor_tile( $c['sponsor'] ); ?>
      </div>
      <button type="button" class="catd-x" data-close aria-label="Close"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>
    </div>
    <div class="catd-grid">
      <section>
        <h4>What goes in</h4>
        <p><?php echo esc_html( $c['what'] ); ?></p>
<?php if ( ! empty( $c['not'] ) ) : ?>
        <p class="not"><strong>Not this one.</strong> <?php echo esc_html( $c['not'] ); ?></p>
<?php endif; ?>
      </section>
      <section class="rules">
        <h4>The rules</h4>
        <ul>
<?php foreach ( $c['rules'] as $r ) : ?>
          <li><?php echo esc_html( $r ); ?></li>
<?php endforeach; ?>
        </ul>
        <p class="fine">The full <a href="https://www.atfa.com.au/terms-conditions/">awards entry terms and conditions</a> apply. ATFA may verify, reallocate or reject entries, and its decisions are final.</p>
      </section>
<?php if ( $c['examples'] ) : ?>
      <section class="exs">
        <h4>From the 2025 entries</h4>
        <div class="exgrid">
<?php foreach ( $c['examples'] as $ex ) : ?>
          <?php echo atfa_awards_example( $ex ); ?>
<?php endforeach; ?>
        </div>
        <p class="fine">Photos as entered in 2025. Winners and runners-up are named; other entries stay anonymous, as they were for voting.</p>
      </section>
<?php endif; ?>
    </div>
    <div class="catd-foot">
<?php if ( in_array( $c['stream'], array( 'newcomers', 'major' ), true ) ) : ?>
      <a class="btn btn-primary" href="#how-it-works">How to enter</a>
<?php else : ?>
      <a class="btn btn-primary" href="#how-it-works">Enter this category</a>
<?php endif; ?>
      <div class="catd-nav">
        <button type="button" class="btn btn-ghost" data-nav="prev"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>Previous</button>
        <button type="button" class="btn btn-ghost" data-nav="next">Next<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg></button>
      </div>
      <button type="button" class="btn btn-ghost catd-close-foot" data-close>Close</button>
    </div>
  </div>
</dialog>
<?php endforeach; ?>
<dialog class="lb" id="aw-lb" aria-label="Photo"><img src="" alt=""></dialog>

</div>
<script>
(function(){
  var root=document.getElementById('aw-main'); if(!root) return;
  var imgs=root.querySelectorAll('.bg'), caps=root.querySelectorAll('.cap'), n=imgs.length, r=Math.floor(Math.random()*n);
  for(var i=0;i<n;i++){ var k=(i-r+n)%n, d=(k*8-2)+'s'; imgs[i].style.animationDelay=d; caps[i].style.animationDelay=d; imgs[i].classList.toggle('lead',k===0); caps[i].classList.toggle('lead',k===0); }
  var close=new Date('2027-03-31T12:59:00Z'), days=Math.max(0,Math.ceil((close.getTime()-Date.now())/86400000));
  document.getElementById('aw-days').textContent=days;

  // Category info panels.
  var dialogs=[].slice.call(root.querySelectorAll('dialog.catd')), order=dialogs.map(function(d){return d.id}), lb=document.getElementById('aw-lb'), canModal=lb&&typeof lb.showModal==='function';
  function lock(){ document.documentElement.classList.toggle('aw-lock', !!root.querySelector('dialog[open]')); }
  function openCat(id){
    var d=document.getElementById(id); if(!d) return;
    dialogs.forEach(function(o){ if(o!==d&&o.open) o.close(); });
    if(canModal){ d.showModal(); } else { d.setAttribute('open',''); }
    d.scrollTop=0; lock();
    if(history.replaceState) history.replaceState(null,'','#'+id);
  }
  root.addEventListener('click',function(e){
    var t=e.target.closest('[data-cat]'); if(t){ e.preventDefault(); openCat('cat-'+t.getAttribute('data-cat')); return; }
    var x=e.target.closest('[data-close]'); if(x){ x.closest('dialog').close(); return; }
    var nv=e.target.closest('[data-nav]'); if(nv){ var i=order.indexOf(nv.closest('dialog').id), n=order.length; openCat(order[(i+(nv.getAttribute('data-nav')==='next'?1:n-1))%n]); return; }
    var ex=e.target.closest('.ex-open'); if(ex&&canModal){ lb.querySelector('img').src=ex.getAttribute('data-src'); lb.showModal(); lock(); return; }
    var a=e.target.closest('dialog a[href^="#"]'); if(a){ a.closest('dialog').close(); }
  });
  dialogs.forEach(function(d){
    d.addEventListener('click',function(e){ if(e.target!==d) return; var r=d.getBoundingClientRect(); if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom) d.close(); });
    d.addEventListener('close',function(){ lock(); if(location.hash==='#'+d.id&&history.replaceState) history.replaceState(null,'',location.pathname+location.search); });
  });
  if(lb){ lb.addEventListener('click',function(){ lb.close(); }); lb.addEventListener('close',lock); }
  if(/^#cat-/.test(location.hash)) openCat(location.hash.slice(1));
})();
</script>
