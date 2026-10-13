<?php
/**
 * Splash: the blackout awards-coming page with the notify-me form. Rendered by [atfa_awards_splash].
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$winners = atfa_awards_winners();
$logo    = atfa_awards_img( 'atfa-logo-white.png' );
?>
<style>
html{background:#050505 !important}body{background:#050505 !important;margin:0 !important;padding:0 !important}
.aw{position:relative;min-height:100vh;box-sizing:border-box;background:#050505;color:#F4EFE7;font-family:'Montserrat',system-ui,sans-serif;-webkit-font-smoothing:antialiased;overflow:hidden;line-height:1.4}
.aw *,.aw *:before,.aw *:after{box-sizing:border-box}
.aw a{color:#F8931F;text-decoration:none}.aw a:hover{color:#FFA640}
.aw p{margin:0}
.aw .disp{font-family:'Barlow Condensed','Arial Narrow',sans-serif;text-transform:uppercase;letter-spacing:.01em;line-height:.9;text-wrap:balance}
.aw .eyebrow{font-size:12px;letter-spacing:.2em;text-transform:uppercase;color:#B4A99A;font-weight:600}
.aw .btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:52px;padding:0 26px;border-radius:4px;font-weight:600;font-size:15px;text-decoration:none;border:1px solid transparent;cursor:pointer;font-family:inherit;white-space:nowrap;line-height:1;text-transform:none;letter-spacing:0;box-shadow:none}
.aw .btn-primary{background:#F8931F;color:#1A1208;border-color:#F8931F}.aw .btn-primary:hover{background:#FFA640;color:#1A1208}
.aw .btn-primary[disabled]{opacity:.7;cursor:default}
.aw .btn-ghost{background:transparent;color:#F4EFE7;border-color:#3A332D}.aw .btn-ghost:hover{color:#FFFFFF;border-color:#F4EFE7}
.aw .in{min-height:52px;height:52px;padding:0 16px;border:1px solid #3A332D;border-radius:4px;background:#0C0B0A;color:#F4EFE7;font-family:inherit;font-size:15px;min-width:0;flex:1 1 240px;margin:0;box-shadow:none}
.aw .in::placeholder{color:#7D7368}
.aw .in:focus{outline:2px solid #F8931F;outline-offset:1px;border-color:#3A332D}
.aw .big{font-size:200px;font-weight:700;margin:14px 0 0;color:#F4EFE7}
/* The atfa wordmark stands in for the word Flooring on the first line of the headline. */
.aw .wm{display:block;height:.9em;width:auto;margin:0 0 .1em}
.aw .new{display:flex;flex-direction:column;gap:6px;padding:18px 20px;border-top:2px solid #2B2622;min-width:0;background:rgba(12,11,10,.55);backdrop-filter:blur(6px)}
.aw .new.lead{border-top-color:#F8931F;background:rgba(248,147,31,.12)}
.aw .new .t{font-family:'Barlow Condensed','Arial Narrow',sans-serif;text-transform:uppercase;font-size:26px;font-weight:700;line-height:.95;color:#F4EFE7}
.aw .new .d{font-size:12px;color:#B4A99A;line-height:1.45}
/* New for 2027: six cards on a slow ticker that drifts right to left, faded at both edges, paused on hover. */
.aw .ticker{position:relative;overflow:hidden;-webkit-mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0,#000 7%,#000 93%,rgba(0,0,0,0) 100%);mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0,#000 7%,#000 93%,rgba(0,0,0,0) 100%)}
.aw .track{display:flex;gap:12px;width:max-content;padding-left:84px;--loop:1872px;animation:aw-ticker 84s linear infinite}
.aw .ticker:hover .track{animation-play-state:paused}
.aw .track .new{width:300px;flex:0 0 300px}
/* --loop is six cards plus six gaps, so the second copy lands exactly where the first started. */
@keyframes aw-ticker{from{transform:translateX(0)}to{transform:translateX(calc(-1 * var(--loop)))}}
/* Backdrop: past winners sit in the right two thirds, masked so they fade out to black on the left, and rotate with a slow crossfade. A different winner leads each visit. */
.aw .bgwrap{position:absolute;inset:0;overflow:hidden;--o:.55;opacity:var(--o);animation:aw-drift 2.4s cubic-bezier(.2,.6,.2,1) both;transform-origin:60% 50%}
.aw .bg{position:absolute;top:0;right:0;width:72%;height:100%;object-fit:cover;object-position:50% 50%;opacity:0;transform-origin:60% 50%;margin:0;border:0;max-width:none;
  -webkit-mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.3) 24%,rgba(0,0,0,1) 56%);mask-image:linear-gradient(90deg,rgba(0,0,0,0) 0%,rgba(0,0,0,.3) 24%,rgba(0,0,0,1) 56%);
  animation:aw-show 48s linear infinite both,aw-kb 48s cubic-bezier(.2,.6,.2,1) infinite both}
.aw .veil{position:absolute;inset:0;background:linear-gradient(90deg,rgba(5,5,5,.55) 26%,rgba(5,5,5,0) 60%),linear-gradient(0deg,#050505 6%,rgba(5,5,5,0) 38%,rgba(5,5,5,0) 84%,rgba(5,5,5,.6) 100%)}
.aw .caps{position:relative;width:400px;max-width:100%;height:50px}
.aw .cap{position:absolute;right:0;bottom:0;width:max-content;max-width:100%;font-size:12px;color:#F4EFE7;background:rgba(5,5,5,.72);padding:7px 11px;border-radius:3px;line-height:1.45;opacity:0;animation:aw-showcap 48s linear infinite both;text-align:left}
.aw .cap strong{font-weight:600;color:#F4EFE7}
.aw .bg.lead,.aw .cap.lead{opacity:1}
@keyframes aw-show{0%{opacity:0;z-index:3}3.333%{opacity:1;z-index:3}3.4%{opacity:1;z-index:2}20%{opacity:1;z-index:2}20.1%{opacity:1;z-index:1}23.333%{opacity:0;z-index:1}100%{opacity:0;z-index:1}}
@keyframes aw-showcap{0%,1.667%{opacity:0}3.333%,18.333%{opacity:1}20%,100%{opacity:0}}
@keyframes aw-kb{0%{transform:scale(1.07)}23.333%{transform:scale(1)}100%{transform:scale(1)}}
@keyframes aw-rise{from{opacity:0;transform:translateY(26px)}to{opacity:1;transform:translateY(0)}}
@keyframes aw-drift{from{opacity:0;transform:scale(1.06)}to{opacity:var(--o);transform:scale(1)}}
.aw .ld{animation:aw-rise .95s cubic-bezier(.22,.61,.36,1) both}
.aw .d1{animation-delay:.1s}.aw .d2{animation-delay:.3s}.aw .d3{animation-delay:.45s}.aw .d4{animation-delay:.6s}.aw .d5{animation-delay:.75s}.aw .d6{animation-delay:.9s}.aw .d7{animation-delay:1.05s}.aw .d8{animation-delay:1.25s}.aw .d9{animation-delay:1.4s}
.aw .msg{font-size:13px;color:#B4A99A;min-height:18px;margin-top:10px}
.aw .msg.ok{color:#F8931F}
.aw .msg.err{color:#FFB4A0}
@media (prefers-reduced-motion:reduce){.aw .ld,.aw .bgwrap,.aw .track{animation:none}.aw .bg,.aw .cap{animation-play-state:paused}.aw .ticker{overflow-x:auto;-webkit-mask-image:none;mask-image:none}.aw .track [aria-hidden="true"]{display:none}}
@media (max-width:900px){.aw .big{font-size:120px}.aw .bg{width:100%}.aw .bgwrap{--o:.35}.aw .track .new{width:260px;flex-basis:260px}.aw .track{--loop:1632px;padding-left:40px}}
@media (max-width:560px){.aw .big{font-size:76px}.aw .track .new{width:240px;flex-basis:240px}.aw .track{--loop:1512px;padding-left:24px}.aw .caps{height:auto}.aw .cap{position:static;display:none}.aw .cap.lead{display:block}}
</style>
<div class="aw" id="aw-splash">
  <div class="bgwrap" aria-hidden="true">
<?php foreach ( $winners as $i => $w ) : ?>
    <img class="bg<?php echo 0 === $i ? ' lead' : ''; ?>" src="<?php echo esc_url( atfa_awards_img( $w['img'] ) ); ?>" alt="" style="animation-delay:<?php echo (int) ( $i * 8 - 2 ); ?>s;object-position:<?php echo esc_attr( $w['pos'] ); ?>">
<?php endforeach; ?>
  </div>
  <div class="veil" aria-hidden="true"></div>

  <div style="position:relative;max-width:1280px;margin:0 auto;padding:0 40px;display:flex;flex-direction:column;min-height:100vh">
    <div class="ld d1" style="display:flex;align-items:center;justify-content:space-between;gap:24px;padding:26px 0;flex-wrap:wrap">
      <a href="https://www.atfa.com.au/"><img src="<?php echo esc_url( $logo ); ?>" alt="ATFA Australasian Timber Flooring Association" style="height:44px;width:auto;display:block"></a>
      <a href="https://www.atfa.com.au/" style="display:inline-flex;align-items:center;gap:8px;color:#B4A99A;font-size:13px;font-weight:600">atfa.com.au
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
      </a>
    </div>

    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <div style="display:flex;flex-direction:column;padding:56px 0 40px;max-width:760px;flex:1 1 560px;min-width:0">
        <span class="eyebrow ld d2" style="color:#F8931F">Coming soon · Sydney · 22 May 2027</span>
        <h1 class="disp big ld d3"><img class="wm" src="<?php echo esc_url( atfa_awards_img( 'atfa-wordmark.png' ) ); ?>" alt="ATFA">Awards <span style="color:#F8931F">2027</span></h1>
        <p class="ld d4" style="font-size:20px;line-height:1.45;color:#B4A99A;margin:26px 0 0;max-width:40ch">The biggest change to the awards in years. Entries open Monday 1 February.</p>
        <div class="ld d5" style="display:flex;align-items:baseline;gap:12px;margin-top:24px">
          <span class="disp" id="aw-days" style="font-size:64px;font-weight:700;color:#F8931F;font-variant-numeric:tabular-nums">111</span>
          <span style="color:#B4A99A;font-size:14px">days until entries open</span>
        </div>
        <form class="ld d6" id="aw-notify" style="display:flex;gap:10px;flex-wrap:wrap;margin:24px 0 0;max-width:560px;position:relative" novalidate data-endpoint="<?php echo esc_url( rest_url( 'atfa-awards/v1/notify' ) ); ?>">
          <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-5000px;width:1px;height:1px;opacity:0">
          <label for="aw-email" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)">Email address</label>
          <input class="in" id="aw-email" name="email" type="email" autocomplete="email" placeholder="you@yourbusiness.com.au" required>
          <button type="submit" class="btn btn-primary" id="aw-notify-btn">Notify me when entries open</button>
          <p class="msg" id="aw-msg" style="flex-basis:100%" aria-live="polite"></p>
        </form>
        <div class="ld d7" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;margin-top:8px">
          <a class="btn btn-ghost" href="https://www.atfa.com.au/my-membership/">Member login</a>
          <span style="font-size:13px;color:#7D7368">Open to current ATFA members. Not a member? <a href="https://epoxy.atfa.com.au/registration">Join ATFA</a></span>
        </div>
      </div>
      <div class="caps ld d7" style="margin:0 0 40px auto">
<?php foreach ( $winners as $i => $w ) : ?>
    <span class="cap<?php echo 0 === $i ? ' lead' : ''; ?>" style="animation-delay:<?php echo (int) ( $i * 8 - 2 ); ?>s"><?php echo esc_html( $w['cat'] ); ?><br><strong><?php echo esc_html( $w['biz'] ); ?></strong></span>
<?php endforeach; ?>
      </div>
    </div>

    <div class="ld d8" style="display:flex;flex-direction:column;gap:14px;padding:24px 0 28px;margin-top:auto">
      <span class="eyebrow">New for 2027</span>
      <div class="ticker" aria-label="What is new for 2027">
        <div class="track">
          <div class="new lead"><span class="eyebrow" style="color:#F8931F">New</span><span class="t">Overseas and school projects can now be entered</span><span class="d">Four expert panel categories accept projects outside Australia and New Zealand, and work in schools and learning environments. Disclose it clearly and you're in.</span></div>
          <div class="new"><span class="eyebrow" style="color:#F8931F">New</span><span class="t">Expert panel judging</span><span class="d">Recycled / Restoration, Sanded &amp; Finished, Design Feature and Technical System, judged on craft and difficulty by an appointed panel.</span></div>
          <div class="new"><span class="eyebrow" style="color:#F8931F">New</span><span class="t">3-2-1 member voting</span><span class="d">Rank your top three in each of eight member-voted categories: 3 votes, 2 votes, 1 vote. One ballot per membership.</span></div>
          <div class="new"><span class="eyebrow" style="color:#F8931F">New</span><span class="t">Parquetry Floor</span><span class="d">Parquetry gets its own member-voted category, decided by 3-2-1 member voting like the other seven.</span></div>
          <div class="new"><span class="eyebrow" style="color:#F8931F">New</span><span class="t">Newcomers Award</span><span class="d">For members in their first five years who have never won or placed. Tick the box on any member-voted entry.</span></div>
          <div class="new"><span class="eyebrow" style="color:#F8931F">Easier</span><span class="t">Enter and vote from your phone</span><span class="d">Photos straight from your camera roll, drafts that save as you go, and voting that takes five minutes. Easier than ever.</span></div>
          <div class="new lead" aria-hidden="true"><span class="eyebrow" style="color:#F8931F">New</span><span class="t">Overseas and school projects can now be entered</span><span class="d">Four expert panel categories accept projects outside Australia and New Zealand, and work in schools and learning environments. Disclose it clearly and you're in.</span></div>
          <div class="new" aria-hidden="true"><span class="eyebrow" style="color:#F8931F">New</span><span class="t">Expert panel judging</span><span class="d">Recycled / Restoration, Sanded &amp; Finished, Design Feature and Technical System, judged on craft and difficulty by an appointed panel.</span></div>
          <div class="new" aria-hidden="true"><span class="eyebrow" style="color:#F8931F">New</span><span class="t">3-2-1 member voting</span><span class="d">Rank your top three in each of eight member-voted categories: 3 votes, 2 votes, 1 vote. One ballot per membership.</span></div>
          <div class="new" aria-hidden="true"><span class="eyebrow" style="color:#F8931F">New</span><span class="t">Parquetry Floor</span><span class="d">Parquetry gets its own member-voted category, decided by 3-2-1 member voting like the other seven.</span></div>
          <div class="new" aria-hidden="true"><span class="eyebrow" style="color:#F8931F">New</span><span class="t">Newcomers Award</span><span class="d">For members in their first five years who have never won or placed. Tick the box on any member-voted entry.</span></div>
          <div class="new" aria-hidden="true"><span class="eyebrow" style="color:#F8931F">Easier</span><span class="t">Enter and vote from your phone</span><span class="d">Photos straight from your camera roll, drafts that save as you go, and voting that takes five minutes. Easier than ever.</span></div>
        </div>
      </div>
    </div>

    <div class="ld d9" style="padding:0 0 32px">
      <span style="font-size:12px;color:#7D7368">Pictured: ATFA award winners from 2025 · AU 1300 361 693 · NZ 0800 453 715 · © Australasian Timber Flooring Association</span>
    </div>
  </div>
</div>
<script>
(function(){
  var root=document.getElementById('aw-splash'); if(!root) return;
  // A different winner leads each visit: shift every photo and caption by a random number of slots.
  var imgs=root.querySelectorAll('.bg'), caps=root.querySelectorAll('.cap'), n=imgs.length, r=Math.floor(Math.random()*n);
  for(var i=0;i<n;i++){ var k=(i-r+n)%n, d=(k*8-2)+'s'; imgs[i].style.animationDelay=d; caps[i].style.animationDelay=d; imgs[i].classList.toggle('lead',k===0); caps[i].classList.toggle('lead',k===0); }
  // Countdown to entries opening, 1 February 2027 AEDT.
  var open=new Date('2027-01-31T13:00:00Z'), days=Math.max(0,Math.ceil((open.getTime()-Date.now())/86400000));
  document.getElementById('aw-days').textContent=days;
  // Notify me: posts to the plugin's REST endpoint, which stores the address and emails ATFA and the subscriber.
  var f=document.getElementById('aw-notify'), btn=document.getElementById('aw-notify-btn'), msg=document.getElementById('aw-msg'), em=document.getElementById('aw-email'), label=btn.textContent;
  f.addEventListener('submit',function(e){
    e.preventDefault();
    var v=em.value.trim();
    if(!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v)){ msg.className='msg err'; msg.textContent='Please enter a valid email address.'; em.focus(); return; }
    btn.disabled=true; btn.textContent='Sending'; msg.className='msg'; msg.textContent='';
    fetch(f.getAttribute('data-endpoint'),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email:v,page:location.href,website:f.website.value})})
      .then(function(r){ return r.json(); })
      .then(function(j){
        if(j && j.ok){ msg.className='msg ok'; msg.textContent='Thanks, you are on the list. We will email you the moment entries open.'; btn.textContent='You are on the list'; em.value=''; }
        else { btn.disabled=false; btn.textContent=label; msg.className='msg err'; msg.textContent=(j && j.message) || 'Please check the email address and try again.'; }
      })
      .catch(function(){ btn.disabled=false; btn.textContent=label; msg.className='msg err'; msg.textContent='Something went wrong. Email admin@atfa.com.au and we will add you.'; });
  });
})();
</script>
