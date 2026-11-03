<?php
/**
 * 2027 Awards category sponsorship: the code screen, or the offer with live availability and the request form.
 * Rendered by [atfa_awards_sponsorship] (includes/sponsorship.php). Same look as the awards pages.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$viewer   = atfa_aws_viewer();
$offer    = atfa_aws_offer();
$settings = atfa_aws_settings();
$winners  = atfa_awards_winners();
$logo     = atfa_awards_img( 'atfa-logo-white.png' );
$contact  = $offer['contact'];
$awards   = home_url( '/awards/' );

$icon = function ( $name, $size = 24 ) {
	$paths = array(
		'trophy'    => '<path d="M8 21h8M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0V4z"/><path d="M17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/>',
		'ticket'    => '<path d="M3.5 7.5h17V10a2 2 0 0 0 0 4v2.5h-17V14a2 2 0 0 0 0-4V7.5z"/><path d="M14.5 7.5v9" stroke-dasharray="1.6 2"/>',
		'screen'    => '<rect x="3" y="4" width="18" height="12.5" rx="1.5"/><path d="M8.5 20h7M12 16.5V20"/>',
		'megaphone' => '<path d="M3.5 10v4h3l7 4.5v-13L6.5 10h-3z"/><path d="M16.5 9.2a4 4 0 0 1 0 5.6M19 6.8a7.5 7.5 0 0 1 0 10.4"/>',
		'star'      => '<path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9L12 3.5z"/>',
		'check'     => '<path d="M5 12.5l4.2 4.2L19 7"/>',
		'plus'      => '<path d="M12 5v14M5 12h14"/>',
		'arrow'     => '<path d="M5 12h14M12 5l7 7-7 7"/>',
		'back'      => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="M3.5 6.5l8.5 6.5 8.5-6.5"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
		'lock'      => '<rect x="4.5" y="10.5" width="15" height="10" rx="1.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
		'close'     => '<path d="M6 6l12 12M18 6L6 18"/>',
	);
	return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[ $name ] . '</svg>';
};

// The rotating 2025 winners behind the hero, shared by the code screen and the page.
$backdrop = function () use ( $winners ) {
	$out = '<div class="bgwrap" aria-hidden="true">';
	foreach ( $winners as $i => $w ) {
		$out .= '<img class="bg' . ( 0 === $i ? ' lead' : '' ) . '" src="' . esc_url( atfa_awards_img( $w['img'] ) ) . '" alt="" style="animation-delay:' . (int) ( $i * 8 - 2 ) . 's;object-position:' . esc_attr( $w['pos'] ) . '">';
	}
	return $out . '</div><div class="veil" aria-hidden="true"></div>';
};
?>
<style>
html{background:#0C0B0A !important}body{background:#0C0B0A !important;margin:0 !important;padding:0 !important}
.aw{position:relative;min-height:100vh;box-sizing:border-box;background:#0C0B0A;color:#F4EFE7;font-family:'Montserrat',system-ui,sans-serif;-webkit-font-smoothing:antialiased;line-height:1.4;overflow-x:clip}
.aw *,.aw *:before,.aw *:after{box-sizing:border-box}
.aw [hidden]{display:none !important}
.aw a{color:#F8931F;text-decoration:none}.aw a:hover{color:#FFA640}
.aw p{margin:0}.aw h1,.aw h2,.aw h3{margin:0;color:#F4EFE7}
.aw svg{flex-shrink:0}
.aw .disp{font-family:'Barlow Condensed','Arial Narrow',sans-serif;text-transform:uppercase;letter-spacing:.01em;line-height:.92;text-wrap:balance}
.aw .eyebrow{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#B4A99A;font-weight:600}
.aw .o{color:#F8931F}
.aw .btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:52px;padding:0 26px;border-radius:4px;font-weight:600;font-size:15px;text-decoration:none;border:1px solid transparent;cursor:pointer;font-family:inherit;white-space:nowrap;line-height:1;text-transform:none;letter-spacing:0;box-shadow:none}
.aw .btn-primary{background:#F8931F;color:#1A1208;border-color:#F8931F}.aw .btn-primary:hover{background:#FFA640;color:#1A1208}
.aw .btn-primary[disabled]{opacity:.55;cursor:default}
.aw .btn-ghost{background:transparent;color:#F4EFE7;border-color:#3A332D}.aw .btn-ghost:hover{color:#FFFFFF;border-color:#F4EFE7}
.aw .btn-ghost[disabled]{opacity:.5;cursor:default}
.aw .btn:focus-visible{outline:2px solid #F8931F;outline-offset:2px}
.aw .wrap{max-width:1280px;margin:0 auto;padding:0 40px}
.aw .card{background:#1A1613;border:1px solid #332C26;border-radius:6px}
.aw .tag{display:inline-block;white-space:nowrap;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:4px 8px;border-radius:3px;line-height:1.2}
.aw .tag-new{background:#F8931F;color:#1A1208}
.aw .tag-dim{background:#2B2622;color:#B4A99A}
.aw .in{display:block;width:100%;min-height:52px;padding:0 16px;border:1px solid #3A332D;border-radius:4px;background:#0C0B0A;color:#F4EFE7;font-family:inherit;font-size:16px;margin:0;box-shadow:none}
.aw textarea.in{min-height:96px;padding:14px 16px;line-height:1.45;resize:vertical}
.aw .in::placeholder{color:#7D7368}
.aw .in:focus{outline:2px solid #F8931F;outline-offset:1px;border-color:#3A332D}
.aw .msg{font-size:14px;line-height:1.5;color:#B4A99A;min-height:20px}
.aw .msg.ok{color:#F8931F}.aw .msg.err{color:#FFB4A0}
.aw .fine{font-size:12.5px;color:#8F857A;line-height:1.5}
/* Hero backdrop: the 2025 winners, as on the awards pages. */
.aw .bgwrap{position:absolute;inset:0;overflow:hidden;--o:.8;opacity:var(--o);animation:aw-fadein 1.8s ease-out both}
.aw .bg{position:absolute;top:0;right:0;width:70%;height:100%;object-fit:cover;object-position:50% 50%;opacity:0;transform-origin:60% 50%;margin:0;border:0;max-width:none;
  -webkit-mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.25) 20%,rgba(0,0,0,1) 50%);mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.25) 20%,rgba(0,0,0,1) 50%);
  animation:aw-show 48s linear infinite both,aw-kb 48s cubic-bezier(.2,.6,.2,1) infinite both}
.aw .veil{position:absolute;inset:0;background:linear-gradient(90deg,rgba(12,11,10,.88) 34%,rgba(12,11,10,.5) 52%,rgba(12,11,10,0) 68%),linear-gradient(0deg,#0C0B0A 0%,rgba(12,11,10,0) 26%),linear-gradient(180deg,rgba(12,11,10,.7) 0%,rgba(12,11,10,0) 20%)}
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
@media (prefers-reduced-motion:reduce){.aw .ld,.aw .sr,.aw .bgwrap{animation:none}.aw .bg,.aw .cap{animation-play-state:paused}}
.aw .top{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:22px 0;flex-wrap:wrap;border-bottom:1px solid rgba(244,239,231,.12)}
.aw .top-back{display:inline-flex;align-items:center;gap:8px;color:#B4A99A;font-size:13px;font-weight:600}
.aw .top-btns{display:flex;gap:12px;flex-wrap:wrap}
.aw .hero-h1{font-size:124px;font-weight:700;margin:18px 0 0;color:#F4EFE7}
.aw .h1-sub{display:block;font-size:.4em;letter-spacing:.02em;margin-top:.22em;color:#F4EFE7}
/* Principal Partner lockup, as on the awards pages. */
.aw .pp{display:inline-flex;align-items:center;gap:16px;align-self:flex-start;margin:24px 0 0;text-decoration:none}
.aw .pp-l{font-size:11px;letter-spacing:.2em;text-transform:uppercase;font-weight:600;color:#B4A99A;white-space:nowrap;padding-right:16px;border-right:1px solid #3A332D;line-height:30px}
.aw .pp img{display:block;height:36px;width:auto;max-width:none;margin:0;border:0}
.aw .pp.pp-dn{margin-top:14px}.aw .pp.pp-dn .pp-l{border-right-color:#463D35}
/* Price */
.aw .price{display:flex;align-items:center;gap:18px;margin-top:30px;flex-wrap:wrap}
.aw .price-n{font-size:88px;font-weight:700;color:#F8931F;line-height:.85}
.aw .price-l{display:flex;flex-direction:column;gap:4px;font-size:14px;color:#B4A99A;line-height:1.35;padding-left:18px;border-left:1px solid #3A332D}
.aw .price-l strong{color:#F4EFE7;font-weight:600;font-size:15px}
/* Status strip */
.aw .status{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:20px 28px;flex-wrap:wrap}
.aw .dot{width:10px;height:10px;border-radius:50%;background:#5BBF7A;box-shadow:0 0 0 4px rgba(91,191,122,.18);flex-shrink:0}
.aw .mine-card{margin-top:12px;padding:18px 28px;display:flex;align-items:center;gap:14px 24px;flex-wrap:wrap;border-color:#F8931F;background:#1F1711}
.aw .mine-list{display:flex;gap:10px;flex-wrap:wrap}
/* Headings */
.aw .sec-h{display:flex;justify-content:space-between;align-items:end;gap:24px;flex-wrap:wrap;margin-bottom:24px}
.aw .sec-h h2{font-size:64px;font-weight:700;max-width:16ch}
.aw .sec-h p{max-width:46ch;color:#B4A99A;font-size:15px;line-height:1.5}
/* What's included */
.aw .inc{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px}
.aw .inc-c{display:flex;flex-direction:column;gap:12px;padding:24px 22px;min-width:0}
.aw .inc-i{width:52px;height:52px;border-radius:50%;display:grid;place-items:center;background:#2A1F14;color:#F8931F;border:1px solid #4A3420}
.aw .inc-t{font-size:26px;font-weight:700}
.aw .inc-d{font-size:14px;line-height:1.5;color:#B4A99A}
/* Section bands, as on the awards pages. */
.aw .band{position:relative;margin-top:96px;padding:88px 0;background:#1A1613;border-top:1px solid rgba(244,239,231,.08);border-bottom:1px solid rgba(244,239,231,.08)}
.aw .band .card{background:#221D19;border-color:#3B332C}
.aw .band .tag-dim{background:#352E28;color:#C3B8A9}
/* Where your brand appears */
.aw .seen{display:grid;grid-template-columns:minmax(0,7fr) minmax(0,5fr);gap:48px;align-items:start}
.aw .mock{margin:0;min-width:0}
.aw .mock-win{border:1px solid #3B332C;border-radius:8px;background:#121010;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,.45)}
.aw .mock-bar{display:flex;align-items:center;gap:6px;padding:10px 14px;background:#0C0B0A;border-bottom:1px solid #2B2622}
.aw .mock-bar i{width:9px;height:9px;border-radius:50%;background:#2F2925;display:block}
.aw .mock-bar em{font-style:normal;font-size:11px;color:#7D7368;margin-left:10px;letter-spacing:.04em}
.aw .mock-head{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;padding:24px 26px 20px;border-bottom:1px solid #2B2622}
.aw .mock-h{display:block;font-size:40px;font-weight:700;margin-top:10px}
.aw .mock-lead{display:block;font-size:13.5px;color:#B4A99A;line-height:1.5;margin-top:10px;max-width:46ch}
.aw .mock-sp{display:flex;flex-direction:column;gap:8px;flex:0 0 190px}
.aw .mock-sp .eyebrow{font-size:10px;color:#7D7368}
.aw .you{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;height:72px;border:1.5px dashed #F8931F;border-radius:4px;background:rgba(248,147,31,.07);color:#F8931F;font-size:11px;letter-spacing:.12em;text-transform:uppercase;font-weight:700;text-align:center;padding:6px 10px;animation:aw-pulse 2.6s ease-in-out infinite}
.aw .you small{font-size:10px;letter-spacing:.06em;text-transform:none;color:#C9A27A;font-weight:600}
@keyframes aw-pulse{0%,100%{box-shadow:0 0 0 0 rgba(248,147,31,0)}50%{box-shadow:0 0 0 6px rgba(248,147,31,.12)}}
@media (prefers-reduced-motion:reduce){.aw .you{animation:none}}
.aw .mock-body{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:20px 26px 24px}
.aw .mock-body img{display:block;width:100%;height:auto;aspect-ratio:4/3;object-fit:cover;border-radius:4px;background:#211D19;margin:0;border:0;max-width:none}
.aw .mock figcaption{font-size:13px;color:#8F857A;line-height:1.5;margin-top:14px;max-width:62ch}
.aw .season{list-style:none;margin:0;padding:0;position:relative}
.aw .season li{position:relative;padding:0 0 26px 30px;margin:0}
.aw .season li:before{content:"";position:absolute;left:5px;top:6px;bottom:-6px;width:1px;background:#463D35}
.aw .season li:last-child{padding-bottom:0}.aw .season li:last-child:before{display:none}
.aw .season li:after{content:"";position:absolute;left:0;top:3px;width:11px;height:11px;border-radius:50%;background:#1A1613;border:2px solid #F8931F}
.aw .season .w{display:block;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#F8931F;font-weight:700}
.aw .season .t{display:block;font-size:28px;font-weight:700;margin-top:6px}
.aw .season .d{display:block;font-size:14px;color:#B4A99A;line-height:1.5;margin-top:6px;max-width:46ch}
/* Choosing categories */
.aw .legend{display:flex;gap:10px 18px;flex-wrap:wrap;align-items:center;font-size:13px;color:#B4A99A;margin-bottom:18px}
.aw .legend span{display:inline-flex;align-items:center;gap:8px}
.aw .grid3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
.aw .stream{padding:26px;display:flex;flex-direction:column;gap:14px;min-width:0}
.aw .stream-top{display:flex;justify-content:space-between;align-items:center;gap:12px}
.aw .stream-h{font-size:38px;font-weight:700}
.aw .stream p{color:#B4A99A;font-size:14px;line-height:1.5}
.aw ul.sl{list-style:none;margin:4px 0 0;padding:0;display:flex;flex-direction:column}
.aw ul.sl li.sc{display:flex;align-items:center;gap:10px;margin:0;padding:0 0 0 2px;border-top:1px solid #2B2622;min-width:0;transition:background-color .2s}
.aw .sc-pick{display:flex;align-items:center;gap:12px;flex:1 1 auto;min-width:0;padding:12px 0;cursor:pointer;margin:0}
.aw .sc-pick input{position:absolute;opacity:0;width:1px;height:1px;margin:0;pointer-events:none}
.aw .sc-box{width:24px;height:24px;border:1.5px solid #5A4E44;border-radius:5px;display:grid;place-items:center;color:transparent;transition:background-color .15s,border-color .15s;flex-shrink:0}
.aw .sc-box svg{width:16px;height:16px}
.aw .sc-pick:hover .sc-box{border-color:#F8931F}
.aw .sc-pick input:checked + .sc-box{background:#F8931F;border-color:#F8931F;color:#1A1208}
.aw .sc-pick input:focus-visible + .sc-box{outline:2px solid #F8931F;outline-offset:2px}
.aw .sc-t{display:flex;flex-direction:column;gap:5px;min-width:0}
.aw .sc-n{font-size:15px;line-height:1.35;color:#F4EFE7;min-width:0}
.aw .sc-s{display:inline-flex;align-items:center;gap:7px;font-size:10.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;line-height:1.2}
.aw .sc-s:before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor;flex-shrink:0}
.aw .s-available{color:#7FD39A}.aw .s-hold{color:#F6B66B}.aw .s-taken{color:#8F857A}.aw .s-mine,.aw .s-mine-taken{color:#F8931F}
.aw .sc-n .tag{margin-left:6px;vertical-align:1px;font-size:9.5px;padding:3px 6px}
.aw li.sc.on{background:linear-gradient(90deg,rgba(248,147,31,.1),rgba(248,147,31,0))}
.aw li.sc.off .sc-pick{cursor:default}
.aw li.sc.off .sc-n{color:#8F857A}
.aw li.sc.off .sc-box{border-color:#3A332D;background:#141110}
.aw li.sc.mine .sc-box{background:#F8931F;border-color:#F8931F;color:#1A1208}
.aw li.sc.mine .sc-n{color:#F4EFE7}
.aw .st{display:inline-block;font-size:10.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 8px;border-radius:3px;white-space:nowrap;line-height:1.2;flex-shrink:0}
.aw .st-available{color:#8FDCA6;background:rgba(91,191,122,.13)}
.aw .st-hold{color:#F6C287;background:rgba(248,147,31,.13)}
.aw .st-taken{color:#9A9086;background:#2B2622}
.aw .st-mine,.aw .st-mine-taken{color:#1A1208;background:#F8931F}
.aw .sc-info{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;flex-shrink:0;padding:0;margin:0;border:1px solid #3A332D;border-radius:50%;background:none;color:#B4A99A;cursor:pointer;box-shadow:none}
.aw .sc-info:hover{color:#FFFFFF;border-color:#F4EFE7}
.aw .sc-info:focus-visible{outline:2px solid #F8931F;outline-offset:2px}
.aw .two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-top:20px}
.aw .foty{padding:28px;display:flex;flex-direction:column;gap:12px;min-width:0;background:#141110}
/* Request */
.aw .req{display:grid;grid-template-columns:minmax(0,7fr) minmax(0,5fr);gap:40px;align-items:start}
.aw .form{padding:32px;display:flex;flex-direction:column;gap:18px;min-width:0}
.aw .form-h{font-size:44px;font-weight:700}
.aw .picks{display:flex;flex-wrap:wrap;gap:10px;min-height:44px;align-items:center}
.aw .chip{display:inline-flex;align-items:center;gap:8px;min-height:40px;padding:0 6px 0 14px;border-radius:20px;background:#F8931F;color:#1A1208;font-size:14px;font-weight:600;border:0;font-family:inherit}
.aw .chip button{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;padding:0;margin:0;border:0;border-radius:50%;background:rgba(26,18,8,.14);color:#1A1208;cursor:pointer;box-shadow:none}
.aw .chip button:hover{background:rgba(26,18,8,.28)}
.aw .chip button svg{width:14px;height:14px}
.aw .picks-empty{font-size:14px;color:#8F857A}
.aw .fgrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.aw .f{display:flex;flex-direction:column;gap:7px;min-width:0;margin:0}
.aw .f > span{font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#B4A99A;font-weight:600}
.aw .f-wide{grid-column:1 / -1}
.aw .done{display:flex;flex-direction:column;gap:14px;align-items:flex-start}
.aw .done-i{width:64px;height:64px;border-radius:50%;display:grid;place-items:center;background:#F8931F;color:#1A1208}
.aw .done-i svg{width:32px;height:32px}
.aw .steps{display:flex;flex-direction:column;gap:0}
.aw .step{display:grid;grid-template-columns:56px minmax(0,1fr);gap:4px 16px;padding:20px 0;border-top:1px solid #3B332C}
.aw .step:first-child{border-top:0;padding-top:0}
.aw .step .n{grid-row:span 2;font-family:'Barlow Condensed','Arial Narrow',sans-serif;font-size:56px;font-weight:700;color:#F8931F;line-height:.85}
.aw .step .t{font-size:28px;font-weight:700}
.aw .step .d{font-size:14px;color:#B4A99A;line-height:1.5}
.aw .alt{margin-top:26px;padding:20px 22px;display:flex;gap:14px;align-items:flex-start}
.aw .alt .ic{color:#F8931F;margin-top:2px}
.aw .alt p{font-size:14px;color:#B4A99A;line-height:1.5}
/* Allocation and links */
.aw .alloc{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.aw .alloc > div{padding:32px;display:flex;flex-direction:column;gap:12px;min-width:0}
.aw .alloc .disp{font-size:44px;font-weight:700}
.aw .alloc p{font-size:15px;line-height:1.55;color:#B4A99A;max-width:56ch}
.aw .alloc .hot{background:#F8931F;color:#1A1208;border-radius:6px}
.aw .alloc .hot .disp,.aw .alloc .hot p,.aw .alloc .hot .eyebrow{color:#1A1208}
.aw .links{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:20px}
.aw a.lk{display:flex;flex-direction:column;gap:10px;padding:26px;border-top:2px solid #3A332D;border-radius:0 0 6px 6px;background:#1A1613;color:#F4EFE7;min-width:0;transition:border-color .2s,background-color .2s}
.aw a.lk:hover{border-top-color:#F8931F;background:#211B17;color:#F4EFE7}
.aw a.lk:focus-visible{outline:2px solid #F8931F;outline-offset:2px}
.aw .lk-t{font-size:36px;font-weight:700}
.aw .lk-d{font-size:14px;line-height:1.5;color:#B4A99A;max-width:56ch}
.aw .lk-go{display:inline-flex;align-items:center;gap:8px;margin-top:auto;padding-top:6px;font-size:14px;font-weight:600;color:#F8931F}
/* Dinner and the weekend, as on the awards pages. */
.aw .weekend{margin-top:44px;padding-top:32px;border-top:1px solid #3B332C}
.aw .weekend-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:18px}
.aw .wk{display:flex;flex-direction:column;gap:10px;min-width:0;padding:24px;border-top:2px solid #463D35;border-radius:0 0 6px 6px;background:#221D19;color:#F4EFE7;text-decoration:none;transition:border-color .2s,background-color .2s}
.aw a.wk,.aw a.wk:hover{color:#F4EFE7}
.aw a.wk:hover{border-top-color:#F8931F;background:#262019}
.aw .wk-day{font-size:64px;font-weight:700;line-height:.85;color:#F4EFE7}
.aw .wk-t{font-size:36px;font-weight:700}
.aw .wk-d{font-size:14px;line-height:1.5;color:#B4A99A;max-width:56ch}
.aw .wk-go{display:inline-flex;align-items:center;gap:8px;margin-top:auto;padding-top:8px;font-size:14px;font-weight:600;color:#F8931F}
.aw .wk-soon{margin-top:auto;padding-top:8px;font-size:14px;font-weight:600;color:#8F857A}
.aw .dinner{display:grid;grid-template-columns:minmax(0,8fr) minmax(0,4fr);gap:24px;align-items:center}
/* Picked bar: follows the visitor down the page once something is picked. */
.aw .pickbar{visibility:hidden;position:fixed;left:0;right:0;bottom:0;z-index:50;background:rgba(18,16,16,.97);border-top:1px solid #F8931F;box-shadow:0 -12px 40px rgba(0,0,0,.45);transform:translateY(110%);transition:transform .3s cubic-bezier(.2,.6,.2,1),visibility 0s linear .3s;padding:12px 0 calc(12px + env(safe-area-inset-bottom))}
.aw .pickbar.show{transform:none;visibility:visible;transition:transform .3s cubic-bezier(.2,.6,.2,1),visibility 0s}
.aw .pickbar-in{display:flex;align-items:center;justify-content:space-between;gap:16px}
.aw .pickbar-t{display:flex;flex-direction:column;gap:2px;min-width:0}
.aw .pickbar-n{font-size:13px;color:#B4A99A}
.aw .pickbar-n strong{color:#F8931F;font-weight:700}
.aw .pickbar-c{font-size:14px;font-weight:600;color:#F4EFE7;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.aw .pickbar .btn{min-height:46px}
.aw .toast{position:fixed;left:50%;bottom:96px;z-index:60;transform:translate(-50%,20px);opacity:0;pointer-events:none;background:#F4EFE7;color:#1A1208;font-size:14px;font-weight:600;padding:12px 18px;border-radius:4px;max-width:calc(100% - 32px);transition:opacity .2s,transform .2s;text-align:center}
.aw .toast.show{opacity:1;transform:translate(-50%,0)}
/* Staff bar */
.aw .staff{position:relative;z-index:5;background:#24313A;color:#E6EEF3;font-size:13px;line-height:1.5}
.aw .staff .wrap{display:flex;align-items:center;justify-content:space-between;gap:10px 20px;flex-wrap:wrap;padding-top:10px;padding-bottom:10px}
.aw .staff a{color:#FFFFFF;text-decoration:underline}
.aw .staff b{display:inline-block;background:#E6EEF3;color:#24313A;font-size:10px;letter-spacing:.12em;text-transform:uppercase;padding:3px 7px;border-radius:3px;margin-right:8px}
/* Category panels, as on the awards pages. */
.aw dialog.catd{position:fixed;inset:0;margin:auto;width:min(1060px,calc(100% - 32px));max-width:none;max-height:calc(100vh - 32px);max-height:calc(100dvh - 32px);padding:0;border:1px solid #2B2622;border-radius:8px;background:#121010;color:#F4EFE7;overflow:auto;overscroll-behavior:contain;box-shadow:0 30px 80px rgba(0,0,0,.6)}
.aw dialog.catd::backdrop{background:rgba(0,0,0,.74);backdrop-filter:blur(2px)}
.aw .catd-in{display:flex;flex-direction:column;min-height:100%}
.aw .catd-head{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;padding:26px 36px 20px;border-bottom:1px solid #2B2622;position:sticky;top:0;background:#121010;z-index:2}
.aw .catd-head h3{font-size:42px;font-weight:700;margin:0;color:#F4EFE7;line-height:.95}
.aw .catd-lead{font-size:15px;color:#B4A99A;line-height:1.5;margin:12px 0 0;max-width:62ch}
.aw .catd-sp{display:flex;flex-direction:column;gap:8px;align-items:stretch;flex-shrink:0;width:200px;margin-left:auto}
.aw .catd-sp .eyebrow{font-size:10px;letter-spacing:.14em;color:#7D7368}
.aw .catd-sp .you{height:64px}
.aw .catd-sp .gone{display:flex;align-items:center;justify-content:center;height:64px;border:1px solid #2B2622;border-radius:4px;background:#171412;color:#8F857A;font-size:11px;letter-spacing:.12em;text-transform:uppercase;font-weight:700}
.aw .catd-bar{display:none}
.aw .catd-close-foot{display:none}
.aw .catd-x{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;flex-shrink:0;margin:-6px -8px 0 0;padding:0;background:none;border:1px solid #2B2622;border-radius:50%;color:#B4A99A;cursor:pointer;box-shadow:none}
.aw .catd-x:hover{color:#FFFFFF;border-color:#F4EFE7}
.aw .catd-grid{display:grid;grid-template-columns:1.05fr 1fr;gap:28px 40px;padding:26px 36px 30px}
.aw .catd-grid > section{min-width:0}
.aw .catd-grid > .exs{grid-column:1 / -1}
.aw .catd-grid h4{font-family:'Barlow Condensed','Arial Narrow',sans-serif;text-transform:uppercase;letter-spacing:.04em;font-size:22px;font-weight:700;color:#F8931F;margin:0 0 12px;line-height:1}
.aw .catd-grid p{font-size:15px;line-height:1.55;color:#E6DFD4}
.aw .catd-grid p + h4{margin-top:24px}
.aw .catd-grid .not{margin-top:14px;padding:12px 14px;border-left:2px solid #F8931F;background:#1A1512;font-size:13.5px;line-height:1.5;color:#B4A99A;border-radius:0 4px 4px 0}
.aw .catd-grid .not strong{color:#F4EFE7;font-weight:600}
.aw .catd-grid ul{margin:0;padding:0 0 0 18px}
.aw .catd-grid ul li{font-size:13.5px;line-height:1.5;color:#D9D0C4;margin:0 0 8px;padding:0}
.aw .catd-grid ul li::marker{color:#F8931F}
.aw .catd-grid .fine{margin-top:14px}
.aw .exgrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
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
/* Code screen */
.aw .gate{position:relative;min-height:100vh;min-height:100dvh;display:flex;flex-direction:column}
.aw .gate-main{position:relative;flex:1;display:flex;align-items:center;padding:40px 0 56px}
.aw .gate-box{max-width:600px}
.aw .gate-h{font-size:112px;font-weight:700;margin:16px 0 0}
.aw .gate-form{display:flex;gap:10px;flex-wrap:wrap;margin-top:30px;max-width:520px}
.aw .gate-form .in{flex:1 1 220px;width:auto;font-size:20px;letter-spacing:.14em;text-transform:uppercase;font-weight:600}
.aw .gate-form .in::placeholder{letter-spacing:.14em;font-weight:500}
.aw .gate-help{display:flex;gap:10px 22px;flex-wrap:wrap;margin-top:26px;font-size:14px;color:#8F857A}
/* Phones and tablets */
@media (max-width:1100px){.aw .inc{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:1000px){.aw .catd-grid{grid-template-columns:1fr 1fr}}
@media (max-width:900px){
  .aw .hero-h1{font-size:100px}.aw .bg{width:100%}.aw .bgwrap{--o:.42}
  .aw .hero{grid-template-columns:minmax(0,1fr) !important}.aw .hero > div{grid-column:span 1 !important}
  .aw .grid3,.aw .two,.aw .alloc,.aw .links,.aw .weekend-grid,.aw .dinner{grid-template-columns:minmax(0,1fr)}
  .aw .seen,.aw .req{grid-template-columns:minmax(0,1fr);gap:36px}
  .aw .inc{grid-template-columns:repeat(2,minmax(0,1fr))}
  .aw .band{margin-top:80px;padding:64px 0}
  .aw .sec-h h2{font-size:52px}
  .aw .gate-h{font-size:88px}
}
@media (max-height:640px){.aw .catd-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;position:sticky;top:0;z-index:3;background:#121010;border-bottom:1px solid #2B2622;padding:8px 12px 8px 20px;min-height:52px}.aw .catd-bar .t{font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#B4A99A;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}.aw .catd-bar .catd-x{margin:0}.aw .catd-head{position:static}.aw .catd-head > .catd-x{display:none}.aw .catd-head h3{font-size:34px}.aw .catd-close-foot{display:inline-flex}}
@media (max-width:700px){
  .aw dialog.catd{width:100%;height:100vh;height:100dvh;max-height:100vh;max-height:100dvh;border-radius:0;border:0;margin:0}
  .aw .catd-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;position:sticky;top:0;z-index:3;background:#121010;border-bottom:1px solid #2B2622;padding:8px 12px 8px 20px;min-height:58px}
  .aw .catd-bar .t{font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#B4A99A;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
  .aw .catd-bar .catd-x{margin:0}
  .aw .catd-head{position:static;padding:18px 20px 16px;flex-wrap:wrap;gap:14px}
  .aw .catd-head > .catd-x{display:none}
  .aw .catd-head h3{font-size:30px}.aw .catd-head h3 .ofy{display:none}
  .aw .catd-lead{font-size:14px;margin-top:8px}
  .aw .catd-sp{width:100%;flex-direction:row;align-items:center;gap:12px;margin:0}
  .aw .catd-sp .eyebrow{flex:0 0 auto}
  .aw .catd-sp .you,.aw .catd-sp .gone{flex:1 1 auto;height:52px}
  .aw .catd-grid{grid-template-columns:1fr;gap:26px;padding:20px 20px 24px}
  .aw .exgrid{grid-template-columns:1fr 1fr;gap:10px}
  .aw .catd-foot{padding:16px 20px calc(20px + env(safe-area-inset-bottom));gap:12px}
  .aw .catd-foot > .btn-primary{width:100%}
  .aw .catd-nav{width:100%}.aw .catd-nav .btn{flex:1}
  .aw .catd-close-foot{display:inline-flex;width:100%}
}
@media (max-width:560px){
  .aw .wrap{padding:0 20px}
  .aw .top{gap:14px;padding:18px 0}.aw .top-btns{display:none}
  .aw .hero-h1{font-size:64px}.aw .h1-sub{font-size:.46em}.aw .hero{padding:44px 0 40px !important}.aw .hero-p{font-size:17px !important}
  .aw .caps{height:auto}.aw .cap{position:static;display:none}.aw .cap.lead{display:block}
  .aw .price{flex-direction:column;align-items:flex-start;gap:10px;margin-top:24px}.aw .price-n{font-size:72px}.aw .price-l{padding-left:0;border-left:0}
  .aw .pp{gap:12px;margin-top:20px}.aw .pp-l{font-size:10px;padding-right:12px;line-height:26px}.aw .pp img{height:28px}
  .aw .status,.aw .mine-card{padding:18px 20px}
  .aw .sec-h{margin-bottom:20px}.aw .sec-h h2{font-size:44px}
  .aw .inc{grid-template-columns:minmax(0,1fr);gap:10px}.aw .inc-c{flex-direction:row;align-items:flex-start;gap:16px;padding:18px}.aw .inc-c > div{display:flex;flex-direction:column;gap:6px;min-width:0}.aw .inc-i{width:44px;height:44px}.aw .inc-t{font-size:23px}
  .aw .band{margin-top:64px;padding:56px 0}
  .aw .mock-head{flex-direction:column;padding:18px 18px 16px}.aw .mock-sp{flex:none;width:100%}.aw .mock-h{font-size:32px}.aw .mock-body{padding:14px 18px 18px}
  .aw .season .t{font-size:24px}
  .aw .stream{padding:20px 18px}.aw .stream-h{font-size:34px}
  .aw ul.sl li.sc{gap:8px}.aw .sc-pick{gap:12px;padding:13px 0}.aw .sc-n{font-size:15px}.aw .sc-info{width:38px;height:38px}
  .aw .foty{padding:22px 20px}
  .aw .form{padding:22px 18px}.aw .form-h{font-size:36px}.aw .fgrid{grid-template-columns:minmax(0,1fr)}
  .aw .form .btn-primary{width:100%}
  .aw .step{grid-template-columns:44px minmax(0,1fr);gap:4px 12px}.aw .step .n{font-size:46px}.aw .step .t{font-size:25px}
  .aw .alloc > div{padding:24px 20px}.aw .alloc .disp{font-size:36px}
  .aw a.lk{padding:22px 20px}.aw .lk-t{font-size:30px}
  .aw .wk{padding:20px}.aw .wk-day{font-size:56px}.aw .wk-t{font-size:32px}
  .aw .pickbar .btn{padding:0 18px}
  .aw .gate-h{font-size:64px}.aw .gate-form .btn{width:100%}
}
</style>
<?php if ( $viewer['gate'] ) : ?>
<div class="aw" id="aw-main">
	<?php if ( $viewer['staff'] ) : ?>
	<div class="staff"><div class="wrap"><span><b>Staff</b>This is the code screen visitors get. Members type the code from their invitation, or open their personal link.</span><a href="<?php echo esc_url( atfa_aws_page_url() ); ?>">Back to the page</a></div></div>
	<?php endif; ?>
	<div class="gate">
		<?php echo $backdrop(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="wrap" style="position:relative;width:100%">
			<div class="top ld d1">
				<a href="<?php echo esc_url( $awards ); ?>"><img src="<?php echo esc_url( $logo ); ?>" alt="ATFA Australasian Timber Flooring Association" style="height:44px;width:auto;display:block"></a>
				<a class="top-back" href="<?php echo esc_url( $awards ); ?>">The 2027 awards<?php echo $icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</div>
		</div>
		<div class="gate-main">
			<div class="wrap" style="width:100%">
				<div class="gate-box">
					<span class="eyebrow o ld d2" style="display:inline-flex;align-items:center;gap:10px"><?php echo $icon( 'lock', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>Platinum Members first look</span>
					<h1 class="disp gate-h ld d3">Awards <span class="o">2027</span><span class="h1-sub">Category sponsorship</span></h1>
					<p class="ld d4" style="font-size:19px;line-height:1.5;color:#B4A99A;margin-top:24px;max-width:42ch">ATFA Platinum Members get first pick of the 2027 award categories before they are listed on the ATFA website. Enter the access code from your invitation.</p>
					<form class="gate-form ld d5" method="post" action="<?php echo esc_url( atfa_aws_page_url() ); ?>">
						<label for="aws-code" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)">Access code</label>
						<input class="in" id="aws-code" name="aws_code" type="text" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" placeholder="ABCD-EFGH" value="<?php echo esc_attr( $viewer['typed'] ); ?>" maxlength="20" required<?php echo $viewer['error'] ? ' aria-invalid="true" aria-describedby="aws-gate-msg"' : ''; ?>>
						<button type="submit" class="btn btn-primary">Open<?php echo $icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						<p class="msg<?php echo $viewer['error'] ? ' err' : ''; ?>" id="aws-gate-msg" style="flex-basis:100%" aria-live="polite"><?php echo esc_html( $viewer['error'] ); ?></p>
					</form>
					<div class="gate-help ld d6">
						<span>No code? Email <a href="mailto:<?php echo esc_attr( $contact['email'] ); ?>?subject=<?php echo rawurlencode( '2027 Awards category sponsorship' ); ?>"><?php echo esc_html( $contact['email'] ); ?></a></span>
						<a href="<?php echo esc_url( $awards ); ?>">See the 2027 awards</a>
					</div>
				</div>
			</div>
		</div>
		<div class="wrap" style="position:relative;width:100%;padding-bottom:28px">
			<span class="fine">Pictured: ATFA award winners from 2025 · AU 1300 361 693 · NZ 0800 453 715 · © Australasian Timber Flooring Association</span>
		</div>
	</div>
</div>
<script data-no-optimize="1" data-cfasync="false" nowprocket>
(function(){
	var root=document.getElementById('aw-main'); if(!root) return;
	var imgs=root.querySelectorAll('.bg'), n=imgs.length, r=Math.floor(Math.random()*n);
	for(var i=0;i<n;i++){ var k=(i-r+n)%n; imgs[i].style.animationDelay=(k*8-2)+'s'; imgs[i].classList.toggle('lead',k===0); }
	var c=document.getElementById('aws-code');
	if(c&&window.matchMedia&&window.matchMedia('(min-width:700px)').matches){ c.focus(); }
})();
</script>
<?php
	return;
endif;

/* ---------------------------------------------------------------- The page */

$member   = $viewer['member'];
$partner  = atfa_awards_partner();
$cats     = atfa_aws_categories();
$all      = atfa_awards_categories();
$statuses = atfa_aws_public_statuses( $member );
$booking  = '' !== $settings['book_url'];
$limit    = $settings['limit'];
$mine     = array();
$free     = 0;
foreach ( $statuses as $k => $st ) {
	if ( 'available' === $st ) {
		$free++;
	}
	if ( 0 === strpos( $st, 'mine' ) ) {
		$mine[] = $k;
	}
}
$streams = array( 'member' => array(), 'panel' => array(), 'excellence' => array(), 'newcomers' => array() );
foreach ( $cats as $k => $c ) {
	if ( isset( $streams[ $c['stream'] ] ) ) {
		$streams[ $c['stream'] ][ $k ] = $c;
	}
}
$labels = array(
	'available'  => 'Available',
	'hold'       => 'On hold',
	'taken'      => 'Secured',
	'mine'       => 'Your request',
	'mine-taken' => 'Yours',
);
$stream_info = array(
	'member'     => array( 'Member voted · Project award', 'Decided by 3-2-1 member voting: members rank their top three floors. Winner and runner-up announced at the dinner.' ),
	'panel'      => array( 'Expert panel · Project award', 'Judged on difficulty, craft and outcome by an ATFA-appointed panel. Winner and runner-up announced at the dinner.' ),
	'excellence' => array( 'Excellence award', 'A nomination with supporting evidence, judged by ATFA. One winner, no runner-up.' ),
	'newcomers'  => array( 'Expert panel selected', 'For members in their first five years who have never won an ATFA award or been a runner-up. The expert panel picks the winner and may name a runner-up.' ),
);
$public_from = '' !== $settings['public_from'] ? ' on ' . $settings['public_from'] : '';

// One pickable category row.
$row = function ( $k, $c ) use ( $statuses, $labels, $icon, $booking ) {
	$st  = $statuses[ $k ];
	$on  = 'available' === $st;
	$cls = 'sc' . ( $on ? '' : ' off' ) . ( 0 === strpos( $st, 'mine' ) ? ' mine' : '' );
	$tag = ! empty( $c['new'] ) ? '<span class="tag tag-new">New</span>' : '';
	$out = '<li class="' . $cls . '" data-key="' . esc_attr( $k ) . '">';
	$txt ='<span class="sc-t"><span class="sc-n">' . esc_html( $c['name'] ) . $tag . '</span><span class="sc-s s-' . esc_attr( $st ) . '" data-role="rs">' . esc_html( $labels[ $st ] ) . '</span></span>';
	if ( $booking ) {
		$out .= '<span class="sc-pick" style="cursor:default">' . $txt . '</span>';
	} else {
		$out .= '<label class="sc-pick"><input type="checkbox" value="' . esc_attr( $k ) . '"' . ( $on ? '' : ' disabled' ) . ( 0 === strpos( $st, 'mine' ) ? ' checked' : '' ) . '>'
			. '<span class="sc-box">' . $icon( 'check', 16 ) . '</span>' . $txt . '</label>';
	}
	$out .= '<button type="button" class="sc-info" data-cat="' . esc_attr( $k ) . '" aria-label="More about ' . esc_attr( $c['name'] ) . '">' . $icon( 'plus', 18 ) . '</button></li>';
	return $out;
};

$first_avail = '';
foreach ( $statuses as $k => $st ) {
	if ( 'available' === $st && ! empty( $cats[ $k ]['examples'] ) ) {
		$first_avail = $k;
		break;
	}
}
$mock = $cats[ isset( $cats['stair'] ) && 'available' === $statuses['stair'] ? 'stair' : ( $first_avail ? $first_avail : array_key_first( $cats ) ) ];

$config = array(
	'rest'     => rest_url( 'atfa-awards/v1/sponsorship/request' ),
	'nonce'    => $viewer['staff'] ? wp_create_nonce( 'wp_rest' ) : '',
	'staff'    => $viewer['staff'],
	'limit'    => $limit,
	'statuses' => $statuses,
	'labels'   => $labels,
	'booking'  => $booking,
	'email'    => $contact['email'],
	'company'  => $member ? $member['company'] : '',
	'cats'     => array_map( function ( $c ) use ( $stream_info ) {
		return array(
			'name'   => $c['name'],
			'full'   => $c['full'],
			'lead'   => $c['lead'],
			'stream' => isset( $stream_info[ $c['stream'] ] ) ? $stream_info[ $c['stream'] ][0] : '',
			'ex'     => ! empty( $c['examples'] ) ? array_map( function ( $ex ) {
				return atfa_awards_img( 'examples/thumbs/' . $ex[0] . '.jpg' );
			}, array_slice( $c['examples'], 0, 2 ) ) : array(),
		);
	}, $cats ),
);
?>
<div class="aw" id="aw-main">

<?php if ( $viewer['staff'] ) : ?>
<div class="staff"><div class="wrap">
	<span><b><?php echo 'public' === $settings['access'] ? 'Staff' : 'Admins only'; ?></b><?php echo 'public' === $settings['access'] ? 'Open to everyone.' : ( 'admin' === $settings['access'] ? 'Hidden for now: everyone else, staff and Platinum Members with a code included, is sent to the awards page. Open it under Awards sponsorship > Settings.' : 'Platinum first look: only Platinum Members with their access code, and administrators, can see this page. Everyone else, staff included, gets the code screen.' ); ?> Requests you send from this login are tests: only you get the emails and nothing is held.</span>
	<span><a href="<?php echo esc_url( add_query_arg( 'aws_preview', 'gate', atfa_aws_page_url() ) ); ?>">See the code screen</a> · <a href="<?php echo esc_url( admin_url( 'admin.php?page=atfa-aws' ) ); ?>">Codes, categories and requests</a></span>
</div></div>
<?php endif; ?>

<div style="position:relative;overflow:hidden">
<?php echo $backdrop(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<div class="wrap" style="position:relative">
<div class="top ld d1">
	<a class="top-back" href="<?php echo esc_url( $awards ); ?>"><?php echo $icon( 'back', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>The 2027 awards</a>
	<a href="<?php echo esc_url( $awards ); ?>"><img src="<?php echo esc_url( $logo ); ?>" alt="ATFA Australasian Timber Flooring Association" style="height:46px;width:auto;display:block"></a>
	<div class="top-btns">
		<a class="btn btn-ghost" href="#included">What's included</a>
		<a class="btn btn-primary" href="#choose"><?php echo $booking ? 'See the categories' : 'Choose categories'; ?></a>
	</div>
</div>
</div>

<div class="wrap" style="position:relative">
<div class="hero" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px;align-items:end;padding:80px 0 60px">
	<div style="grid-column:span 7;min-width:0;display:flex;flex-direction:column">
		<div class="eyebrow ld d2" style="color:#F8931F"><?php echo $member ? 'Platinum Members first look · ' . esc_html( $member['company'] ) : ( 'public' === $settings['access'] ? 'ATFA Flooring Awards · Sydney · ' . esc_html( $offer['dinner']['date'] ) : 'Platinum Members first look' ); ?></div>
		<h1 class="disp hero-h1 ld d3">Awards <span class="o">2027</span><span class="h1-sub">Category sponsorship</span></h1>
<?php if ( $partner ) : ?>
		<?php echo atfa_awards_partner_lockup( $partner, 'ld d4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif; ?>
		<p class="ld d4 hero-p" style="font-size:21px;max-width:36ch;line-height:1.45;color:#B4A99A;margin:26px 0 0">Put your brand behind the best in the industry. The ATFA Flooring Awards return in 2027 as part of what we are planning to be our biggest and best Awards and Expo weekend yet.</p>
		<div class="price ld d5">
			<span class="disp price-n"><?php echo esc_html( '$' . number_format( (float) $offer['price'] ) ); ?></span>
			<span class="price-l"><strong>Per category<?php echo '' !== $offer['price_note'] ? ' ' . esc_html( $offer['price_note'] ) : ''; ?></strong>Two awards dinner tickets included</span>
		</div>
		<div class="ld d6" style="display:flex;gap:14px;flex-wrap:wrap;margin-top:30px">
			<a class="btn btn-primary" href="#choose"><?php echo $booking ? 'See the categories' : 'Choose your categories'; ?></a>
			<a class="btn btn-ghost" href="#seen">Where your brand appears</a>
		</div>
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
<div class="card status">
	<div style="display:flex;align-items:center;gap:14px;min-width:0;flex-wrap:wrap">
		<span class="dot"></span>
		<span class="disp" style="font-size:28px;font-weight:700;color:#F4EFE7"><?php echo 'public' === $settings['access'] ? 'Category sponsorship open' : 'Platinum first look'; ?></span>
		<span style="color:#B4A99A;font-size:15px"><?php echo 'public' === $settings['access'] ? 'Allocated on a first-confirmed basis' : 'Before the categories are listed on the ATFA website' . esc_html( $public_from ); ?></span>
	</div>
	<div style="display:flex;align-items:baseline;gap:10px">
		<span class="disp" id="aws-free" style="font-size:44px;font-weight:700;color:#F8931F;font-variant-numeric:tabular-nums"><?php echo (int) $free; ?></span>
		<span style="color:#B4A99A;font-size:14px">of <?php echo count( $cats ); ?> categories available · up to <?php echo esc_html( atfa_aws_words( $limit ) ); ?> per sponsor</span>
	</div>
</div>
<div class="card mine-card" id="aws-mine"<?php echo $mine ? '' : ' hidden'; ?>>
	<span class="eyebrow o">Your categories</span>
	<div class="mine-list" id="aws-mine-list">
<?php foreach ( $mine as $k ) : ?>
		<span class="st st-<?php echo esc_attr( $statuses[ $k ] ); ?>"><?php echo esc_html( $cats[ $k ]['name'] . ' · ' . $labels[ $statuses[ $k ] ] ); ?></span>
<?php endforeach; ?>
	</div>
	<span style="font-size:13px;color:#B4A99A" id="aws-mine-note"><?php
	$held = count( array_filter( $mine, function ( $k ) use ( $statuses ) {
		return 'mine' === $statuses[ $k ];
	} ) );
	echo ( $mine && ! $held ) ? 'Confirmed for ' . esc_html( $member['company'] ) . '. Thank you.' : 'Requests stay on hold for ' . esc_html( $member ? $member['company'] : 'you' ) . ' while ATFA confirms.';
	?> To change anything, email <a href="mailto:<?php echo esc_attr( $contact['email'] ); ?>"><?php echo esc_html( $contact['email'] ); ?></a>.</span>
</div>
</div>

<div class="wrap sr" id="included" style="margin-top:88px;scroll-margin-top:24px">
<div class="sec-h">
	<h2 class="disp">What's included</h2>
	<p>Every category sponsorship is <?php echo esc_html( atfa_aws_price() ); ?> and includes all five. Each category has one sponsor, so your brand is the only name on it.</p>
</div>
<div class="inc">
<?php foreach ( $offer['includes'] as $inc ) : ?>
	<div class="card inc-c">
		<span class="inc-i"><?php echo $icon( $inc['icon'], 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<div><span class="disp inc-t"><?php echo esc_html( $inc['title'] ); ?></span>
		<p class="inc-d" style="margin-top:8px"><?php echo esc_html( $inc['text'] ); ?></p></div>
	</div>
<?php endforeach; ?>
</div>
</div>

<section class="band" id="seen" style="scroll-margin-top:0">
<div class="wrap sr">
<div class="sec-h">
	<h2 class="disp">Where your brand appears</h2>
	<p>Every award on the ATFA awards page carries its sponsor. Here is how your category's page looks to everyone who enters, votes and follows the awards.</p>
</div>
<div class="seen">
	<figure class="mock">
		<div class="mock-win">
			<div class="mock-bar"><i></i><i></i><i></i><em>atfa.com.au/awards</em></div>
			<div class="mock-head">
				<div style="min-width:0">
					<span class="eyebrow o" id="aws-mock-s"><?php echo esc_html( $stream_info[ $mock['stream'] ][0] ); ?></span>
					<span class="disp mock-h" id="aws-mock-t"><?php echo esc_html( $mock['full'] ); ?></span>
					<span class="mock-lead" id="aws-mock-l"><?php echo esc_html( $mock['lead'] ); ?></span>
				</div>
				<div class="mock-sp">
					<span class="eyebrow">Proudly sponsored by</span>
					<span class="you">Your logo here<?php echo $member ? '<small>' . esc_html( $member['company'] ) . '</small>' : ''; ?></span>
				</div>
			</div>
			<div class="mock-body" id="aws-mock-x">
<?php foreach ( array_slice( (array) $mock['examples'], 0, 2 ) as $ex ) : ?>
				<img src="<?php echo esc_url( atfa_awards_img( 'examples/thumbs/' . $ex[0] . '.jpg' ) ); ?>" alt="" loading="lazy" width="640" height="480">
<?php endforeach; ?>
			</div>
		</div>
		<figcaption><?php echo $booking ? 'Your category on the awards page.' : 'Pick a category below and it shows here.'; ?> Logo, name and link go live on the awards page once your sponsorship is confirmed.</figcaption>
	</figure>
	<div>
		<span class="eyebrow" style="display:block;margin-bottom:22px">Through the 2027 season · proposed dates</span>
		<ol class="season">
<?php foreach ( $offer['season'] as $s ) : ?>
			<li><span class="w"><?php echo esc_html( $s['when'] ); ?></span><span class="disp t"><?php echo esc_html( $s['title'] ); ?></span><span class="d"><?php echo esc_html( $s['text'] ); ?></span></li>
<?php endforeach; ?>
		</ol>
	</div>
</div>
</div>
</section>

<div class="wrap sr" id="choose" style="margin-top:96px;scroll-margin-top:24px">
<div class="sec-h">
	<h2 class="disp"><?php echo $booking ? 'The categories' : 'Choose up to ' . atfa_aws_words( $limit ) . ' ' . _n( 'category', 'categories', $limit ); ?></h2>
	<p><?php echo $booking ? 'See what is still available, then book on the ATFA sponsorship page.' : 'Tick a category to pick it. Tap + for what goes in it and floors from the 2025 entries.'; ?> Twenty awards in all, across three groups.</p>
</div>
<div class="legend">
	<span class="sc-s s-available">Available</span>
	<span><span class="sc-s s-hold">On hold</span>&nbsp; requested, being confirmed</span>
	<span class="sc-s s-taken">Secured</span>
</div>
<div class="grid3">
<?php
$heads = array(
	'member'     => array( 'Member voted', 'Project awards', 'Members rank their top three floors in each category: 3 votes, 2 votes, 1 vote. Winner and runner-up in every category.' ),
	'panel'      => array( 'Expert panel', 'Project awards', 'Judged on difficulty, craft and outcome by an ATFA-appointed panel. Winner and runner-up in each.' ),
	'excellence' => array( 'Excellence', 'People, business & products', 'A nomination with supporting evidence, judged by ATFA. One winner per category, no runner-up.' ),
);
foreach ( $heads as $s => $h ) :
	?>
	<div class="card stream"<?php echo 'panel' === $s ? ' style="border-color:#F8931F"' : ''; ?>>
		<div class="stream-top"><span class="eyebrow o"><?php echo esc_html( $h[0] ); ?></span><?php echo 'panel' === $s ? '<span class="tag tag-new">New for 2027</span>' : '<span class="tag tag-dim">' . count( $streams[ $s ] ) . ' categories</span>'; ?></div>
		<div class="disp stream-h"><?php echo esc_html( $h[1] ); ?></div>
		<p><?php echo esc_html( $h[2] ); ?></p>
		<ul class="sl">
<?php foreach ( $streams[ $s ] as $k => $c ) : ?>
			<?php echo $row( $k, $c ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach; ?>
		</ul>
	</div>
<?php endforeach; ?>
</div>
<div class="two">
<?php foreach ( $streams['newcomers'] as $k => $c ) : ?>
	<div class="card stream">
		<div class="stream-top"><span class="eyebrow o">Expert panel selected</span><span class="tag tag-new">New for 2027</span></div>
		<div class="disp stream-h">Newcomers Award</div>
		<p>For members in their first five years who have never won an ATFA award or been a runner-up. Entrants tick the box on any member-voted entry.</p>
		<ul class="sl"><?php echo $row( $k, $c ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
	</div>
<?php endforeach; ?>
	<div class="card foty">
		<div class="stream-top"><span class="eyebrow o">Major award</span><span class="st st-taken">Secured</span></div>
		<div class="disp stream-h">Floor of the Year</div>
<?php if ( $partner && ! empty( $all['foty']['sponsor']['partner'] ) ) : ?>
		<a class="pp pp-dn" href="<?php echo esc_url( $partner['url'] ); ?>" target="_blank" rel="noopener sponsored" style="margin-top:0"><span class="pp-l">Proudly sponsored by</span><?php echo atfa_awards_partner_img( $partner, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<p style="color:#B4A99A;font-size:14px;line-height:1.5">Floor of the Year is sponsored by <?php echo esc_html( $partner['name'] ); ?>, the 2027 <?php echo esc_html( $partner['role'] ); ?>, and is not available for individual category sponsorship.</p>
<?php else : ?>
		<p style="color:#B4A99A;font-size:14px;line-height:1.5"><?php echo esc_html( $offer['major_note'] ); ?></p>
<?php endif; ?>
		<p style="color:#8F857A;font-size:13px;line-height:1.5">The overall winner and runner-up are chosen from the project category winners and runners-up, so every project category sponsor is part of the night's biggest moment.</p>
	</div>
</div>
</div>

<section class="band" id="request" style="scroll-margin-top:0">
<div class="wrap sr">
<div class="req">
<?php if ( $booking ) : ?>
	<div class="card form">
		<span class="eyebrow o">Book your categories</span>
		<div class="disp form-h">Book on the ATFA sponsorship page</div>
		<p style="font-size:15px;line-height:1.55;color:#B4A99A">Category sponsorships are booked with the rest of ATFA's 2027 sponsorship and advertising. Pick your categories there and check out.</p>
		<a class="btn btn-primary" href="<?php echo esc_url( $settings['book_url'] ); ?>" style="align-self:flex-start">Book a category<?php echo $icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<p class="fine">Questions? Email <a href="mailto:<?php echo esc_attr( $contact['email'] ); ?>"><?php echo esc_html( $contact['email'] ); ?></a>.</p>
	</div>
<?php else : ?>
	<form class="card form" id="aws-form" novalidate>
		<span class="eyebrow o">Request your categories</span>
		<div class="disp form-h">Your picks</div>
		<div class="picks" id="aws-picks" aria-live="polite"><span class="picks-empty">Nothing picked yet. Choose up to <?php echo esc_html( atfa_aws_words( max( 1, $limit - count( $mine ) ) ) . ' ' . _n( 'category', 'categories', max( 1, $limit - count( $mine ) ) ) ); ?> above.</span></div>
		<div class="fgrid">
			<label class="f f-wide"><span>Company</span><input class="in" name="company" autocomplete="organization" value="<?php echo esc_attr( $member ? $member['company'] : '' ); ?>" required></label>
			<label class="f"><span>Your name</span><input class="in" name="name" autocomplete="name" required></label>
			<label class="f"><span>Email</span><input class="in" type="email" name="email" autocomplete="email" required></label>
			<label class="f"><span>Phone (optional)</span><input class="in" type="tel" name="phone" autocomplete="tel"></label>
			<label class="f f-wide"><span>Anything else? (optional)</span><textarea class="in" name="notes" rows="3" maxlength="1000" placeholder="A backup choice in case your first pick goes, or anything we should know."></textarea></label>
		</div>
		<input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-5000px;width:1px;height:1px;opacity:0">
		<div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
			<button type="submit" class="btn btn-primary" id="aws-send">Send my request<?php echo $icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		</div>
		<p class="msg" id="aws-msg" aria-live="polite"></p>
		<p class="fine">Sending puts your categories on hold while ATFA confirms them with you. Allocations are made on a first-confirmed basis.</p>
	</form>
<?php endif; ?>
	<div>
		<h2 class="disp" style="font-size:52px;font-weight:700;margin-bottom:26px">How it works</h2>
		<div class="steps">
			<div class="step"><span class="n">1</span><span class="disp t">Pick up to <?php echo esc_html( atfa_aws_words( $limit ) . ' ' . _n( 'category', 'categories', $limit ) ); ?></span><span class="d">Choose from the 2027 categories above. Floor of the Year isn't available on its own.</span></div>
			<div class="step"><span class="n">2</span><span class="disp t"><?php echo $booking ? 'Book online' : 'Send your request'; ?></span><span class="d"><?php echo $booking ? 'Book on the ATFA sponsorship page with the rest of the 2027 opportunities.' : 'Use the form on this page, or email your picks to ' . esc_html( $contact['email'] ) . '.'; ?></span></div>
			<div class="step"><span class="n">3</span><span class="disp t">Get in early</span><span class="d">Categories are allocated on a first-confirmed basis and we expect demand to be strong.<?php echo 'public' === $settings['access'] ? '' : ' Confirm now, before the categories are listed on the ATFA website' . esc_html( $public_from ) . '.'; ?></span></div>
		</div>
<?php if ( ! $booking ) : ?>
		<div class="card alt">
			<span class="ic"><?php echo $icon( 'mail', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<p>Prefer email? Send your picks to <a id="aws-mailto" href="mailto:<?php echo esc_attr( $contact['email'] ); ?>?subject=<?php echo rawurlencode( '2027 Awards category sponsorship' ); ?>"><?php echo esc_html( $contact['email'] ); ?></a> and <?php echo esc_html( $contact['name'] ); ?> will come back to you.</p>
		</div>
<?php endif; ?>
	</div>
</div>
</div>
</section>

<div class="wrap sr" style="margin-top:96px">
<div class="alloc">
	<div class="card">
		<span class="eyebrow o">Limited category allocation</span>
		<span class="disp">Up to <?php echo esc_html( atfa_aws_words( $limit ) . ' ' . _n( 'category', 'categories', $limit ) ); ?> per sponsor</span>
		<p>To give sponsors meaningful exposure across the awards program, initial allocations are limited to a maximum of <?php echo esc_html( atfa_aws_words( $limit ) . ' ' . _n( 'category', 'categories', $limit ) ); ?> per sponsor. If categories remain available after the first round, sponsors will have the opportunity to secure more.</p>
	</div>
	<div class="hot">
		<span class="eyebrow" style="display:inline-flex;align-items:center;gap:8px"><?php echo $icon( 'clock', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>Get in early</span>
		<span class="disp">Be first to secure your preferred category</span>
		<p>Category allocations are made on a first-confirmed basis and we expect demand to be strong.</p>
	</div>
</div>
<div class="links">
	<a class="lk" href="<?php echo esc_url( $awards ); ?>"><span class="eyebrow">ATFA Flooring Awards 2027</span><span class="disp lk-t">The 2027 awards</span><span class="lk-d">Twenty awards, three ways to win: what is new this year, the season dates and the 2025 winners.</span><span class="lk-go">See the awards<?php echo $icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
	<a class="lk" href="<?php echo esc_url( $offer['guide_url'] ); ?>"><span class="eyebrow">Sponsorship guide</span><span class="disp lk-t">All 2027 sponsorship and advertising</span><span class="lk-d">The rest of ATFA's 2027 opportunities, from events and the Expo to the magazine and Product of the Week.</span><span class="lk-go">Open the sponsorship guide<?php echo $icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
</div>
</div>

<section class="band">
<div class="wrap sr">
<div class="dinner">
	<div style="min-width:0;display:flex;flex-direction:column;gap:10px">
		<span class="eyebrow o">Awards dinner</span>
		<div class="disp" style="font-size:80px;font-weight:700"><?php echo esc_html( $offer['dinner']['date'] ); ?></div>
		<div style="font-size:18px;color:#B4A99A"><?php echo esc_html( $offer['dinner']['venue'] . ', ' . $offer['dinner']['city'] ); ?> · held with the ATFA Expo &amp; Conference 2027</div>
<?php if ( $partner ) : ?>
		<?php echo atfa_awards_partner_lockup( $partner, 'pp-dn' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif; ?>
	</div>
	<div style="min-width:0;display:flex;flex-direction:column;gap:12px;align-items:flex-start">
		<span class="inc-i"><?php echo $icon( 'ticket', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<span style="font-size:15px;line-height:1.5;color:#E6DFD4">Two dinner tickets come with every category, valued at $250 each.</span>
	</div>
</div>
<div class="weekend">
	<div class="eyebrow">Also on the ATFA weekend</div>
	<div class="weekend-grid">
<?php foreach ( atfa_awards_weekend() as $ev ) : ?>
		<?php echo atfa_awards_weekend_card( $ev ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach; ?>
	</div>
</div>
</div>
</section>

<div class="wrap" style="padding-top:28px;padding-bottom:120px">
<div style="display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;font-size:13px;color:#8F857A">
	<div style="display:flex;gap:20px;flex-wrap:wrap">
		<a href="https://www.atfa.com.au/" style="color:#B4A99A">atfa.com.au</a>
		<a href="<?php echo esc_url( $awards ); ?>" style="color:#B4A99A">The 2027 awards</a>
		<a href="<?php echo esc_url( $offer['guide_url'] ); ?>" style="color:#B4A99A">Sponsorship guide</a>
		<a href="mailto:<?php echo esc_attr( $contact['email'] ); ?>" style="color:#B4A99A">Questions: <?php echo esc_html( $contact['email'] ); ?></a>
	</div>
	<div>AU 1300 361 693 · NZ 0800 453 715 · © Australasian Timber Flooring Association</div>
</div>
</div>

<?php if ( ! $booking ) : ?>
<div class="pickbar" id="aws-bar" aria-hidden="true">
	<div class="wrap pickbar-in">
		<div class="pickbar-t"><span class="pickbar-n"><strong id="aws-bar-n">0</strong> of <?php echo (int) max( 0, $limit - count( $mine ) ); ?> picked</span><span class="pickbar-c" id="aws-bar-c"></span></div>
		<a class="btn btn-primary" href="#request" tabindex="-1">Request<?php echo $icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
	</div>
</div>
<div class="toast" id="aws-toast" role="status"></div>
<?php endif; ?>

<?php foreach ( $cats as $k => $c ) : ?>
<?php $st = $statuses[ $k ]; ?>
<dialog class="catd" id="cat-<?php echo esc_attr( $k ); ?>" aria-labelledby="cat-<?php echo esc_attr( $k ); ?>-t">
	<div class="catd-in">
		<div class="catd-bar"><span class="t"><?php echo esc_html( $c['name'] ); ?></span><button type="button" class="catd-x" data-close aria-label="Close"><?php echo $icon( 'close', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button></div>
		<div class="catd-head">
			<div style="min-width:0">
				<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px"><span class="eyebrow o"><?php echo esc_html( $stream_info[ $c['stream'] ][0] ); ?></span><?php if ( ! empty( $c['new'] ) ) : ?><span class="tag tag-new">New for 2027</span><?php endif; ?><span class="st st-<?php echo esc_attr( $st ); ?>" data-role="pill" data-key="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $labels[ $st ] ); ?></span></div>
				<h3 class="disp" id="cat-<?php echo esc_attr( $k ); ?>-t"><?php echo atfa_awards_title_html( $c['full'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
				<p class="catd-lead"><?php echo esc_html( $c['lead'] ); ?></p>
			</div>
			<div class="catd-sp">
				<span class="eyebrow">Proudly sponsored by</span>
				<?php if ( 'available' === $st || 0 === strpos( $st, 'mine' ) ) : ?>
				<span class="you"><?php echo 0 === strpos( $st, 'mine' ) && $member ? esc_html( $member['company'] ) : 'Your logo here'; ?></span>
				<?php else : ?>
				<span class="gone"><?php echo esc_html( $labels[ $st ] ); ?></span>
				<?php endif; ?>
			</div>
			<button type="button" class="catd-x" data-close aria-label="Close"><?php echo $icon( 'close', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		</div>
		<div class="catd-grid">
			<section>
				<h4>What goes in</h4>
				<p><?php echo esc_html( $c['what'] ); ?></p>
<?php if ( ! empty( $c['not'] ) ) : ?>
				<p class="not"><strong>Not this one.</strong> <?php echo esc_html( $c['not'] ); ?></p>
<?php endif; ?>
			</section>
			<section>
				<h4>How it's decided</h4>
				<p><?php echo esc_html( $stream_info[ $c['stream'] ][1] ); ?></p>
				<h4>Sponsor this category</h4>
				<ul>
					<li><?php echo esc_html( atfa_aws_price() ); ?>, with two awards dinner tickets</li>
					<li>Your logo on this category's page on the awards site</li>
					<li>Recognised when the category is presented on the night</li>
				</ul>
			</section>
<?php if ( ! empty( $c['examples'] ) ) : ?>
			<section class="exs">
				<h4>From the 2025 entries</h4>
				<div class="exgrid">
<?php foreach ( $c['examples'] as $ex ) : ?>
					<?php echo atfa_awards_example( $ex ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach; ?>
				</div>
				<p class="fine">Photos as entered in 2025. Winners and runners-up are named; other entries stay anonymous, as they were for voting.</p>
			</section>
<?php endif; ?>
		</div>
		<div class="catd-foot">
<?php if ( $booking ) : ?>
			<a class="btn btn-primary" href="<?php echo esc_url( $settings['book_url'] ); ?>">Book a category</a>
<?php else : ?>
			<button type="button" class="btn btn-primary" data-pick="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( 'available' === $st ? 'Pick this category' : $labels[ $st ] ); ?></button>
<?php endif; ?>
			<div class="catd-nav">
				<button type="button" class="btn btn-ghost" data-nav="prev"><?php echo $icon( 'back', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>Previous</button>
				<button type="button" class="btn btn-ghost" data-nav="next">Next<?php echo $icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</div>
			<button type="button" class="btn btn-ghost catd-close-foot" data-close>Close</button>
		</div>
	</div>
</dialog>
<?php endforeach; ?>
<dialog class="lb" id="aw-lb" aria-label="Photo"><img src="" alt=""></dialog>

</div>
<script type="application/json" id="aws-config"><?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
<script data-no-optimize="1" data-cfasync="false" nowprocket>
(function(){
	var root=document.getElementById('aw-main'); if(!root) return;
	var cfg={}; try{ cfg=JSON.parse(document.getElementById('aws-config').textContent); }catch(e){}
	var $=function(id){ return document.getElementById(id); };

	// A different 2025 winner leads each visit.
	var imgs=root.querySelectorAll('.bg'), caps=root.querySelectorAll('.cap'), n=imgs.length, r=Math.floor(Math.random()*n);
	for(var i=0;i<n;i++){ var k=(i-r+n)%n, d=(k*8-2)+'s'; imgs[i].style.animationDelay=d; if(caps[i]) caps[i].style.animationDelay=d; imgs[i].classList.toggle('lead',k===0); if(caps[i]) caps[i].classList.toggle('lead',k===0); }

	// Category panels and the photo lightbox, as on the awards pages.
	var dialogs=[].slice.call(root.querySelectorAll('dialog.catd')), order=dialogs.map(function(d){return d.id}), lb=$('aw-lb'), canModal=lb&&typeof lb.showModal==='function';
	function lock(){ document.documentElement.classList.toggle('aw-lock', !!root.querySelector('dialog[open]')); }
	function openCat(id){
		var d=$(id); if(!d) return;
		dialogs.forEach(function(o){ if(o!==d&&o.open) o.close(); });
		if(canModal){ d.showModal(); } else { d.setAttribute('open',''); }
		d.scrollTop=0; lock();
		if(history.replaceState) history.replaceState(null,'','#'+id);
	}
	dialogs.forEach(function(d){
		d.addEventListener('click',function(e){ if(e.target!==d) return; var b=d.getBoundingClientRect(); if(e.clientX<b.left||e.clientX>b.right||e.clientY<b.top||e.clientY>b.bottom) d.close(); });
		d.addEventListener('close',function(){ lock(); if(location.hash==='#'+d.id&&history.replaceState) history.replaceState(null,'',location.pathname+location.search); });
	});
	if(lb){ lb.addEventListener('click',function(){ lb.close(); }); lb.addEventListener('close',lock); }

	// Picking categories.
	var statuses=cfg.statuses||{}, labels=cfg.labels||{}, cats=cfg.cats||{}, picked=[];
	function mineCount(){ var c=0; for(var k in statuses){ if(/^mine/.test(statuses[k])) c++; } return c; }
	function room(){ return cfg.staff ? cfg.limit : Math.max(0, cfg.limit-mineCount()); }
	var toastT;
	function toast(t){ var el=$('aws-toast'); if(!el) return; el.textContent=t; el.classList.add('show'); clearTimeout(toastT); toastT=setTimeout(function(){ el.classList.remove('show'); },2600); }
	function words(n){ return ['zero','one','two','three','four','five','six','seven','eight','nine','ten'][n]||String(n); }
	function esc(s){ return String(s).replace(/[&<>"']/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

	function paint(){
		// Rows
		[].forEach.call(root.querySelectorAll('li.sc'),function(li){
			var k=li.getAttribute('data-key'), st=statuses[k]||'taken', on=st==='available', mine=/^mine/.test(st), inp=li.querySelector('input');
			li.classList.toggle('off',!on); li.classList.toggle('mine',mine); li.classList.toggle('on',picked.indexOf(k)>-1);
			if(inp){ inp.disabled=!on; inp.checked=mine||picked.indexOf(k)>-1; }
		});
		[].forEach.call(root.querySelectorAll('[data-role="pill"],[data-role="rs"]'),function(p){
			var k=p.getAttribute('data-key')||p.closest('li.sc').getAttribute('data-key'), st=statuses[k]||'taken';
			p.className=(p.getAttribute('data-role')==='rs'?'sc-s s-':'st st-')+st; p.textContent=labels[st]||st;
		});
		[].forEach.call(root.querySelectorAll('[data-pick]'),function(b){
			var k=b.getAttribute('data-pick'), st=statuses[k]||'taken', on=st==='available', isP=picked.indexOf(k)>-1;
			b.disabled=!on; b.textContent=!on?(labels[st]||st):(isP?'Picked · tap to remove':'Pick this category');
			b.classList.toggle('btn-ghost',isP||!on); b.classList.toggle('btn-primary',!isP&&on);
		});
		// Counts
		var free=0; for(var s in statuses){ if(statuses[s]==='available') free++; }
		if($('aws-free')) $('aws-free').textContent=free;
		// Picks in the form, the bar and the email link
		var names=picked.map(function(k){ return cats[k]?cats[k].name:k; });
		var box=$('aws-picks');
		if(box){
			box.innerHTML=picked.length?picked.map(function(k){ return '<span class="chip">'+esc(cats[k].name)+'<button type="button" data-unpick="'+esc(k)+'" aria-label="Remove '+esc(cats[k].name)+'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button></span>'; }).join(''):'<span class="picks-empty">'+(room()?'Nothing picked yet. Choose up to '+words(room())+' '+(room()===1?'category':'categories')+' above.':'You have '+words(cfg.limit)+' categories with us already. Email '+esc(cfg.email)+' to change them.')+'</span>';
		}
		var bar=$('aws-bar');
		if(bar){ $('aws-bar-n').textContent=picked.length; $('aws-bar-c').textContent=names.join(' · '); bar.classList.toggle('show',picked.length>0&&!formInView); bar.setAttribute('aria-hidden',picked.length?'false':'true'); }
		var ml=$('aws-mailto');
		if(ml){ ml.href='mailto:'+cfg.email+'?subject='+encodeURIComponent('2027 Awards category sponsorship'+(cfg.company?': '+cfg.company:''))+(names.length?'&body='+encodeURIComponent('We would like to sponsor:\n'+names.map(function(x){ return '- '+x; }).join('\n')+'\n'):''); }
		// The mock page shows the latest pick.
		var last=picked.length?picked[picked.length-1]:null;
		if(last&&cats[last]){
			$('aws-mock-t').textContent=cats[last].full; $('aws-mock-l').textContent=cats[last].lead; $('aws-mock-s').textContent=cats[last].stream;
			var x=$('aws-mock-x'); if(x&&cats[last].ex.length){ x.innerHTML=cats[last].ex.map(function(u){ return '<img src="'+esc(u)+'" alt="" width="640" height="480">'; }).join(''); }
		}
	}
	function toggle(k,want){
		var at=picked.indexOf(k), on=(statuses[k]==='available');
		if(want===undefined) want=at<0;
		if(want&&at<0){
			if(!on) return;
			if(room()===0){ toast('You already have '+words(cfg.limit)+' categories with us. Email '+cfg.email+' to change them.'); paint(); return; }
			if(picked.length>=room()){ toast('Up to '+words(room())+' '+(room()===1?'category':'categories')+' each in this round. Untick one to swap.'); paint(); return; }
			picked.push(k);
		} else if(!want&&at>-1){ picked.splice(at,1); }
		paint();
	}
	root.addEventListener('change',function(e){ var inp=e.target.closest('li.sc input'); if(inp) toggle(inp.value,inp.checked); });
	root.addEventListener('click',function(e){
		var t=e.target.closest('[data-cat]'); if(t){ e.preventDefault(); openCat('cat-'+t.getAttribute('data-cat')); return; }
		var p=e.target.closest('[data-pick]'); if(p){ toggle(p.getAttribute('data-pick')); return; }
		var u=e.target.closest('[data-unpick]'); if(u){ toggle(u.getAttribute('data-unpick'),false); return; }
		var x=e.target.closest('[data-close]'); if(x){ x.closest('dialog').close(); return; }
		var nv=e.target.closest('[data-nav]'); if(nv){ var i=order.indexOf(nv.closest('dialog').id), m=order.length; openCat(order[(i+(nv.getAttribute('data-nav')==='next'?1:m-1))%m]); return; }
		var ex=e.target.closest('.ex-open'); if(ex&&canModal){ lb.querySelector('img').src=ex.getAttribute('data-src'); lb.showModal(); lock(); return; }
		var a=e.target.closest('dialog a[href^="#"]'); if(a){ a.closest('dialog').close(); }
	});

	// The picked bar steps aside while the form is on screen.
	var formInView=false, req=$('request');
	if(req&&'IntersectionObserver' in window){ new IntersectionObserver(function(es){ formInView=es[0].isIntersecting; paint(); },{rootMargin:'0px 0px -30% 0px'}).observe(req); }

	// Sending the request.
	var f=$('aws-form'), btn=$('aws-send'), msg=$('aws-msg');
	if(f){
		var label=btn.innerHTML;
		f.addEventListener('submit',function(e){
			e.preventDefault();
			var v=function(n){ return (f.elements[n]&&f.elements[n].value||'').trim(); };
			var say=function(t,ok){ msg.className='msg '+(ok?'ok':'err'); msg.textContent=t; };
			if(!picked.length){ say('Pick at least one category first.'); var c=$('choose'); if(c) c.scrollIntoView({behavior:'smooth'}); return; }
			if(!v('company')){ say('Please add your company name.'); f.elements.company.focus(); return; }
			if(!v('name')){ say('Please add your name.'); f.elements.name.focus(); return; }
			if(!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v('email'))){ say('Please add a valid email address.'); f.elements.email.focus(); return; }
			btn.disabled=true; btn.textContent='Sending'; msg.className='msg'; msg.textContent='';
			var h={'Content-Type':'application/json'}; if(cfg.nonce) h['X-WP-Nonce']=cfg.nonce;
			fetch(cfg.rest,{method:'POST',credentials:'same-origin',headers:h,body:JSON.stringify({categories:picked,company:v('company'),name:v('name'),email:v('email'),phone:v('phone'),notes:v('notes'),website:v('website')})})
				.then(function(res){ return res.json().catch(function(){ return {}; }); })
				.then(function(j){
					btn.disabled=false; btn.innerHTML=label;
					if(j&&j.statuses){ statuses=j.statuses; picked=picked.filter(function(k){ return statuses[k]==='available'; }); }
					if(j&&j.ok&&!j.test){
						picked=[];
						var mineEl=$('aws-mine'), list=$('aws-mine-list');
						if(mineEl&&list){ list.innerHTML=Object.keys(statuses).filter(function(k){ return /^mine/.test(statuses[k]); }).map(function(k){ return '<span class="st st-'+statuses[k]+'">'+esc(cats[k].name+' · '+(labels[statuses[k]]||''))+'</span>'; }).join(''); mineEl.hidden=false; }
						f.innerHTML='<div class="done"><span class="done-i"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.2 4.2L19 7"/></svg></span><span class="eyebrow o">Request sent</span><div class="disp form-h">Thank you</div><p style="font-size:16px;line-height:1.55;color:#E6DFD4;max-width:52ch">'+esc(j.message)+'</p><p class="fine">Questions? Email <a href="mailto:'+esc(cfg.email)+'">'+esc(cfg.email)+'</a>.</p></div>';
						paint(); f.scrollIntoView({behavior:'smooth',block:'start'}); return;
					}
					paint();
					say((j&&j.message)||'Something went wrong. Please email your picks to '+cfg.email+'.', !!(j&&j.ok));
				})
				.catch(function(){ btn.disabled=false; btn.innerHTML=label; say('Something went wrong sending that. Please email your picks to '+cfg.email+'.'); });
		});
	}

	paint();
	if(/^#cat-/.test(location.hash)) openCat(location.hash.slice(1));
})();
</script>
