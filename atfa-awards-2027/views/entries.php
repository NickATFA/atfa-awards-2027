<?php
/**
 * Entries open: the full awards page, published privately as a staff preview. Rendered by [atfa_awards_entries].
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$winners = atfa_awards_winners();
$logo    = atfa_awards_img( 'atfa-logo-white.png' );
?>
<style>
html{background:#0C0B0A !important}body{background:#0C0B0A !important;margin:0 !important;padding:0 !important}
.aw{position:relative;min-height:100vh;box-sizing:border-box;background:#0C0B0A;color:#F4EFE7;font-family:'Montserrat',system-ui,sans-serif;-webkit-font-smoothing:antialiased;line-height:1.4}
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
.aw ul.cats li{padding:12px 0;margin:0;border-top:1px solid #2B2622;font-size:15px;line-height:1.35;display:flex;justify-content:space-between;gap:12px;align-items:center;color:#F4EFE7}
.aw .step{display:flex;flex-direction:column;gap:12px;padding:24px;border-top:2px solid #2B2622;min-width:0}
.aw .step .n{font-family:'Barlow Condensed','Arial Narrow',sans-serif;font-size:40px;font-weight:700;color:#F8931F;line-height:1}
.aw .tl{display:flex;flex-direction:column;gap:8px;padding:18px 20px;border-top:2px solid #2B2622;min-width:0}
.aw .tl.now{border-top-color:#F8931F}
.aw .sponsor{display:flex;align-items:center;justify-content:center;height:84px;border:1px dashed #3A332D;border-radius:4px;color:#7D7368;font-size:12px;letter-spacing:.1em;text-transform:uppercase}
.aw .hero-h1{font-size:172px;font-weight:700;margin:16px 0 0;color:#F4EFE7}
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
@media (max-width:560px){.aw .wrap{padding:0 20px}.aw .hero-h1{font-size:84px}.aw .steps{grid-template-columns:repeat(1,minmax(0,1fr)) !important}.aw .timeline{grid-template-columns:repeat(1,minmax(0,1fr)) !important}.aw .caps{height:auto}.aw .cap{position:static;display:none}.aw .cap.lead{display:block}}
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
    <h1 class="disp hero-h1 ld d3">Flooring<br>Awards <span style="color:#F8931F">2027</span></h1>
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
    <p style="font-size:15px;line-height:1.5;max-width:56ch;color:#1A1208">The four expert panel categories accept projects outside Australia and New Zealand, and work installed in a school or learning environment. Disclose it clearly in your entry and meet the other rules, and the panel judges it on the same footing as everything else.</p>
  </div>
  <div class="card" style="grid-column:span 3;min-width:0;padding:22px;display:flex;flex-direction:column;gap:8px">
    <span class="eyebrow" style="color:#F8931F">New</span>
    <span class="disp" style="font-size:30px;font-weight:700">Expert panel judging</span>
    <span style="color:#B4A99A;font-size:13px;line-height:1.5">Four technical categories judged on difficulty and craft by an appointed panel, not by popularity.</span>
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
  <div class="sponsor">Sponsor logo</div><div class="sponsor">Sponsor logo</div><div class="sponsor">Sponsor logo</div><div class="sponsor">Sponsor logo</div><div class="sponsor">Sponsor logo</div><div class="sponsor">Sponsor logo</div>
</div>
</div>

<div class="wrap sr" id="categories" style="margin-top:96px">
<div style="display:flex;justify-content:space-between;align-items:end;gap:24px;flex-wrap:wrap;margin-bottom:28px">
  <h2 class="disp" style="font-size:72px;font-weight:700;max-width:14ch">Twenty awards. Three ways to win.</h2>
  <p style="max-width:44ch;color:#B4A99A;font-size:15px;line-height:1.5">Project awards are decided by members or by the new expert panel. Excellence awards recognise people, businesses and products. Every category sits beside its own short entry guidance and the full terms and conditions.</p>
</div>
<div class="grid3" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px">

  <div class="card" style="padding:28px;display:flex;flex-direction:column;gap:18px;min-width:0">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px"><span class="eyebrow" style="color:#F8931F">Member voted</span><span class="tag tag-dim">8 categories</span></div>
    <div class="disp" style="font-size:40px;font-weight:700">Project awards</div>
    <p style="color:#B4A99A;font-size:14px;line-height:1.5">Members rank their top three floors in each category: 3 votes, 2 votes, 1 vote. Winner and runner-up in every category. Australia and New Zealand projects only. 5 to 10 photos.</p>
    <ul class="cats">
      <li>Residential Tongue &amp; Groove Timber Floor</li>
      <li>Commercial / Industrial Tongue &amp; Groove Timber Floor</li>
      <li>Stair Project</li>
      <li>Residential Prefinished &amp; Hybrid Floor</li>
      <li>Commercial Prefinished &amp; Hybrid Floor</li>
      <li>Deck / External Timber Project</li>
      <li>Parquetry Floor <span class="tag tag-new">New</span></li>
      <li>Avant Garde Award</li>
    </ul>
  </div>

  <div class="card" style="padding:28px;display:flex;flex-direction:column;gap:18px;min-width:0;border-color:#F8931F">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px"><span class="eyebrow" style="color:#F8931F">Expert panel</span><span class="tag tag-new">New for 2027</span></div>
    <div class="disp" style="font-size:40px;font-weight:700">Project awards</div>
    <p style="color:#B4A99A;font-size:14px;line-height:1.5">Judged on difficulty, craft and outcome by an ATFA-appointed panel. No member voting. Winner and runner-up in each. 8 to 15 photos plus a technical statement.</p>
    <ul class="cats">
      <li>Recycled / Restoration Project</li>
      <li>Sanded &amp; Finished Floor</li>
      <li>Design Feature Floor</li>
      <li>Technical System / Complex Installation</li>
    </ul>
    <div style="border:1px solid #F8931F;border-radius:6px;padding:14px 16px;display:flex;flex-direction:column;gap:6px;background:#1F1711">
      <span class="tag tag-new" style="align-self:flex-start">New</span>
      <span style="font-size:14px;font-weight:700;line-height:1.4">School or learning-environment work and overseas projects are permitted if clearly disclosed and all other rules are met.</span>
    </div>
    <p style="margin-top:auto;color:#7D7368;font-size:13px;line-height:1.5">Judging images are anonymous: no logos, watermarks or branding.</p>
  </div>

  <div class="card" style="padding:28px;display:flex;flex-direction:column;gap:18px;min-width:0">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px"><span class="eyebrow" style="color:#F8931F">Excellence</span><span class="tag tag-dim">6 categories</span></div>
    <div class="disp" style="font-size:40px;font-weight:700">People, business &amp; products</div>
    <p style="color:#B4A99A;font-size:14px;line-height:1.5">A nomination statement with supporting evidence. One winner per category, no runner-up. Judged by ATFA.</p>
    <ul class="cats">
      <li>Apprentice / Trainee of the Year</li>
      <li>Employee of the Year</li>
      <li>Contractor of the Year</li>
      <li>Retailer / Showroom of the Year</li>
      <li>Distributor / Supplier of the Year</li>
      <li>Innovation / Product Excellence</li>
    </ul>
  </div>
</div>

<div class="two" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-top:20px">
  <div style="background:#F8931F;color:#1A1208;border-radius:6px;padding:32px;display:flex;flex-direction:column;gap:12px;min-width:0">
    <span class="eyebrow" style="color:#1A1208">Expert panel selected</span>
    <div class="disp" style="font-size:56px;font-weight:700;color:#1A1208">Newcomers Award</div>
    <p style="font-size:15px;line-height:1.5;max-width:52ch;color:#1A1208">In your first five years of membership and never won an ATFA award or been a runner-up? Tick the Newcomers box on any member-voted entry. No separate entry, and you do not need to place in your category. ATFA checks eligibility against its records.</p>
  </div>
  <div class="card" style="padding:32px;display:flex;flex-direction:column;gap:12px;min-width:0">
    <span class="eyebrow" style="color:#F8931F">Major award</span>
    <div class="disp" style="font-size:56px;font-weight:700">Floor of the Year</div>
    <p style="font-size:15px;line-height:1.5;color:#B4A99A;max-width:52ch">The overall winner and overall runner-up, chosen from the project category winners and runners-up. No separate entry required.</p>
  </div>
</div>
</div>

<div class="wrap sr" id="how-it-works" style="margin-top:96px">
<h2 class="disp" style="font-size:72px;font-weight:700;margin:0 0 28px">How it works</h2>
<div class="steps" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px">
  <div class="step"><span class="n">1</span><span class="disp" style="font-size:30px;font-weight:600">Enter on your phone</span><span style="color:#B4A99A;font-size:14px;line-height:1.5">Log in with your ATFA website login (the same one as My Membership), pick a category, add photos from your camera roll. Save a draft and finish later. Up to three projects per category.</span></div>
  <div class="step"><span class="n">2</span><span class="disp" style="font-size:30px;font-weight:600">ATFA checks every entry</span><span style="color:#B4A99A;font-size:14px;line-height:1.5">Financial membership, project location, completion date and category fit are confirmed before anything goes to a vote or a judge.</span></div>
  <div class="step"><span class="n">3</span><span class="disp" style="font-size:30px;font-weight:600">Members vote, panel judges</span><span style="color:#B4A99A;font-size:14px;line-height:1.5">One ballot per membership in each member-voted category: 3 votes for your first pick, 2 for your second, 1 for your third. The expert panel scores its four categories and picks the Newcomers winner.</span></div>
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

</div>
<script>
(function(){
  var root=document.getElementById('aw-main'); if(!root) return;
  var imgs=root.querySelectorAll('.bg'), caps=root.querySelectorAll('.cap'), n=imgs.length, r=Math.floor(Math.random()*n);
  for(var i=0;i<n;i++){ var k=(i-r+n)%n, d=(k*8-2)+'s'; imgs[i].style.animationDelay=d; caps[i].style.animationDelay=d; imgs[i].classList.toggle('lead',k===0); caps[i].classList.toggle('lead',k===0); }
  var close=new Date('2027-03-31T12:59:00Z'), days=Math.max(0,Math.ceil((close.getTime()-Date.now())/86400000));
  document.getElementById('aw-days').textContent=days;
})();
</script>
