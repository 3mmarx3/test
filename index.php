<?php
require_once __DIR__ . '/admin/includes/auth.php';
require_once __DIR__ . '/admin/includes/permissions.php';
require_once __DIR__ . '/admin/includes/functions.php';

$rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$basePath = project_base_url();
if ($basePath !== '' && str_starts_with($rawUri, $basePath)) {
    $rawUri = substr($rawUri, strlen($basePath));
}
$rawUri = trim((string)$rawUri, '/');
if (str_starts_with($rawUri, 'u/')) {
    $legacyUser = trim(substr($rawUri, 2), '/');
    if ($legacyUser !== '') {
        header('Location: ' . user_url($legacyUser), true, 301);
        exit;
    }
}

$reqUser = trim($_GET['u'] ?? '');
if ($reqUser === '') {
    if ($rawUri !== '' && $rawUri !== 'index.php') {
        $parts = explode('/', $rawUri);
        if ($parts[0] === 'index.php' && isset($parts[1])) {
            array_shift($parts);
        }
        $firstSeg = $parts[0] ?? '';
        if (preg_match('/^[a-zA-Z0-9-]+$/', $firstSeg)) {
            $reqUser = strtolower($firstSeg);
        }
    }
}

if ($reqUser !== '') {
    if (is_username_reserved($reqUser)) {
        $user = null;
    } else {
        $stmtUser = $pdo->prepare('SELECT id, name, username, handle, bio, photo, status FROM users WHERE username = ? LIMIT 1');
        $stmtUser->execute([$reqUser]);
        $user = $stmtUser->fetch();
        if (!$user) {
            try {
                $stmtAlias = $pdo->prepare('SELECT u.username FROM username_aliases a JOIN users u ON u.id = a.user_id WHERE a.old_username = ? LIMIT 1');
                $stmtAlias->execute([$reqUser]);
                $canonicalName = $stmtAlias->fetchColumn();
                if ($canonicalName) {
                    header('Location: ' . user_url($canonicalName), true, 301);
                    exit;
                }
            } catch (Throwable $e) {}
        }
    }
} else {
    $stmtUser = $pdo->prepare('SELECT id, name, username, handle, bio, photo, status FROM users WHERE status = "active" ORDER BY id LIMIT 1');
    $stmtUser->execute();
    $user = $stmtUser->fetch();
}

if (!$user || $user['status'] !== 'active') {
    http_response_code(404);
    $pageTitle = 'Profile Not Found — Wander';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Profile Not Found — Wander</title>
<link rel="stylesheet" href="<?= asset_url('css/main.css') ?>">
</head>
<body style="min-height:100vh;display:grid;place-items:center;background:#0e1116;color:#f8fafc;font-family:system-ui,-apple-system,sans-serif">
  <div style="text-align:center;padding:32px;max-width:440px">
    <h1 style="font-size:32px;margin-bottom:10px">Profile Not Found</h1>
    <p style="color:rgba(255,255,255,0.6);margin-bottom:24px">The profile you requested does not exist or has been suspended.</p>
    <a href="<?= auth_url('signup.php') ?>" class="btn" style="padding:10px 20px;border-radius:99px;background:#fff;color:#111;text-decoration:none;font-weight:600;display:inline-block">Create Your Own Page</a>
    <p style="margin-top:16px"><a href="<?= auth_url('login.php') ?>" style="color:rgba(255,255,255,0.5);font-size:13px;text-decoration:underline">Sign in to account</a></p>
  </div>
</body>
</html>
    <?php
    exit;
}

http_response_code(200);

$userId = (int)$user['id'];
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
$visitor = $_COOKIE['wv'] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/', $visitor)) {
    $visitor = bin2hex(random_bytes(16));
}
setcookie('wv', $visitor, ['expires' => time() + 31536000, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
if (!isset($_COOKIE['wvs_' . $userId]) && !preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit/i', $ua)) {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $country = strtoupper(substr($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '', 0, 2));
    if (!preg_match('/^[A-Z]{2}$/', $country) || $country === 'XX') $country = null;
    try {
        $pdo->prepare('INSERT INTO visits (user_id, visitor, ip_hash, country, referrer, ua) VALUES (?,?,?,?,?,?)')
            ->execute([$userId, $visitor, hash('sha256', $ip . 'wander-salt'), $country,
                       substr($_SERVER['HTTP_REFERER'] ?? '', 0, 255), $ua]);
    } catch (Throwable $e) {}
    setcookie('wvs_' . $userId, '1', ['expires' => time() + 1800, 'path' => '/', 'samesite' => 'Lax']);
}

$stmtLinks = $pdo->prepare('SELECT id, name, url FROM links WHERE user_id = ? ORDER BY sort_order, id');
$stmtLinks->execute([$userId]);
$links = $stmtLinks->fetchAll();

$profile = [
    'name'   => $user['name']   ?? 'Your Name',
    'handle' => $user['handle'] ? $user['handle'] : ('@' . ($user['username'] ?? 'username')),
    'bio'    => $user['bio']    ?? '',
    'photo'  => photo_url($user['photo'] ?? ''),
];
$canonicalUrl = full_user_url($user['username']);
$pageTitle = $profile['name'] . ' (@' . $user['username'] . ') — Wander';
$metaDesc = $profile['bio'] !== '' ? $profile['bio'] : ('Explore links and social destinations for ' . $profile['name'] . ' on Wander.');
$ogImage = $user['photo'] ? photo_url($user['photo']) : '';
if ($ogImage && !preg_match('#^https?://#i', $ogImage)) {
    $ogImage = app_origin() . $ogImage;
}
$J = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<link rel="canonical" href="<?= e($canonicalUrl) ?>">
<meta property="og:type" content="profile">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($metaDesc) ?>">
<meta property="og:url" content="<?= e($canonicalUrl) ?>">
<?php if ($ogImage): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php endif; ?>
<meta name="twitter:card" content="<?= $ogImage ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($metaDesc) ?>">
<?php if ($ogImage): ?>
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php endif; ?>
<meta name="theme-color" content="#2a2f38">
<link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@500;600;700&family=Instrument+Serif&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#2a2f38;
    --gl-font-sans:"Inter Tight",sans-serif;
    --gl-font-city:"Instrument Serif",serif;
    --ease-out:cubic-bezier(.16,1,.3,1);
    --ease-io:cubic-bezier(.65,0,.35,1);
  }
  *{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
  [hidden]{display:none!important}
  button{font:inherit;color:inherit;background:none;border:0;padding:0;margin:0;cursor:pointer}
  h1,h2,p{margin:0}
  html,body{height:100%;margin:0;overflow:hidden;overscroll-behavior:none;-webkit-text-size-adjust:100%;
    background:var(--bg);color:#f3f1ec;font-family:var(--gl-font-sans)}
  #app{position:fixed;inset:0;height:100dvh;touch-action:none;user-select:none;-webkit-user-select:none;overflow:hidden;
    background:radial-gradient(120% 90% at 30% 40%,#323843 0%,#2a2f38 55%,#22262d 100%)}

  .top{position:absolute;top:0;left:0;right:0;z-index:10;display:flex;align-items:flex-start;justify-content:space-between;
    pointer-events:none;padding:max(22px,env(safe-area-inset-top)) max(22px,4.5vw) 0}
  .top-title{margin-top:8px}
  .top-sub{font-size:clamp(11px,1.3vw,15px);color:#ffffffb3}
  .top h1{margin-top:4px;font-weight:600;letter-spacing:-.03em;line-height:.95;font-size:clamp(26px,min(7.2vw,6vh),64px)}
  .top-btns{display:flex;gap:8px;pointer-events:auto}

  .city{position:absolute;left:0;top:0;white-space:nowrap;transform-origin:0 50%;cursor:pointer;line-height:1;
    font-family:var(--gl-font-city);will-change:transform,opacity}
  .arc{position:absolute;border:1.5px solid #ffffff59;border-radius:50%;pointer-events:none;
    -webkit-mask-image:linear-gradient(to bottom,transparent 12%,#000 50%,transparent 88%);
    mask-image:linear-gradient(to bottom,transparent 12%,#000 50%,transparent 88%)}
  .track{position:absolute;right:max(20px,4.5vw);top:20vh;bottom:24vh;width:4px;border-radius:4px;background:#ffffff1f}
  .thumb{position:absolute;left:50%;width:24px;height:44px;margin-left:-12px;border-radius:14px;background:#fff;display:grid;place-items:center;cursor:grab}
  .glass{background:#ffffff1f;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px)}

  .dock{width:54px;height:54px;border-radius:50%;display:grid;place-items:center;flex:none;line-height:1;color:#fff}
  .dock-home{background:#fff;color:#222}
  .dock-plus{font-size:24px}
  .ibtn{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;color:#fff}
  .bottom{position:absolute;z-index:10;left:0;right:0;display:flex;align-items:center;justify-content:center;gap:12px;
    bottom:max(22px,env(safe-area-inset-bottom))}
  .pbtn{display:flex;align-items:center;gap:10px;flex:none;height:54px;padding:0 20px 0 5px;border-radius:999px;
    font-size:14px;font-weight:600;color:#fff;transition:opacity .22s ease}
  #soundBtn[aria-pressed=true] .off,#soundBtn[aria-pressed=false] .on{display:none}
  .in{opacity:0;animation:rise .9s cubic-bezier(.2,.7,.2,1) forwards}
  @keyframes rise{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}
  :focus-visible{outline:2px solid #fff;outline-offset:3px}

  .nav-av{position:relative;display:block;width:44px;height:44px;flex:none;border-radius:50%;overflow:hidden;
    background:linear-gradient(135deg,#f5c96b,#ea7c8c)}
  .nav-av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:50%}

  .pf-bg{position:absolute;inset:0;z-index:30;background:#12151acc;
    backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
    opacity:0;pointer-events:none;transition:opacity .5s ease;will-change:opacity}
  .pf-bg.open{opacity:1;pointer-events:auto}

  .pf-wrap{position:absolute;z-index:31;left:50%;bottom:max(22px,env(safe-area-inset-bottom));width:min(86vw,330px);
    transform:translateX(-50%);pointer-events:none}

  .pf-wrap::before{content:"";position:absolute;inset:0;border-radius:24px;box-shadow:0 24px 48px -10px #000c;
    opacity:0;transition:opacity .35s ease}
  .pf-wrap.open::before{opacity:1;transition:opacity .6s ease .1s}

  .pf-card{position:relative;width:100%;max-height:calc(100dvh - 44px);overflow-y:auto;scrollbar-width:none;outline:none;
    padding:16px 16px 16px;text-align:left;
    background:linear-gradient(160deg,#3d4451 0%,#2c313a 60%,#252931 100%);
    clip-path:inset(var(--ct,100%) var(--cr,0px) 0 var(--cl,0px) round 24px);
    opacity:0;pointer-events:none;will-change:clip-path,opacity;
    transition:clip-path .55s var(--ease-io),opacity .1s linear .45s}
  .pf-card::-webkit-scrollbar{display:none}
  .pf-card.open{clip-path:inset(0 0 0 0 round 24px);opacity:1;pointer-events:auto;
    transition:clip-path .75s var(--ease-out),opacity .05s linear 0s}

  .pf-head{display:flex;flex-direction:row;align-items:center;gap:14px;}
  .pf-photo{position:relative;flex:none;width:72px;height:72px;border-radius:50%;padding:3px;
    background:conic-gradient(from 200deg,#f5c96b,#ea7c8c,#7fb4ff,#f5c96b);
    box-shadow:0 10px 24px -10px #ea7c8c88}
  .pf-inner{width:100%;height:100%;border-radius:50%;overflow:hidden;position:relative;
    display:grid;place-items:center;font-family:var(--gl-font-city);font-size:30px;color:#fff;
    background:linear-gradient(135deg,#f5c96b,#ea7c8c)}
  .pf-inner img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
  .pf-info{min-width:0;flex:1;text-align:left}
  .pf-name{font-family:var(--gl-font-city);font-weight:400;font-size:25px;line-height:1.05;letter-spacing:-.01em;color:#fff;overflow-wrap:anywhere}
  .pf-handle{margin-top:3px;font-size:12.5px;color:#ffffff99;overflow-wrap:anywhere}
  .pf-bio{margin-top:12px;font-size:13px;line-height:1.5;color:#e6e3dd}
  .pf-bio:empty{display:none}
  .pf-x{position:absolute;right:10px;top:10px;width:28px;height:28px;border-radius:50%;background:#ffffff1f;color:#fff;display:grid;place-items:center;z-index:2}
  .pf-stats{display:grid;grid-template-columns:repeat(3,1fr);margin-top:12px;border-radius:14px;background:#00000026;border:1px solid #ffffff1a;padding:8px 4px;text-align:center}
  .pf-stats.off{display:none}
  .pf-stat+.pf-stat{border-left:1px solid #ffffff1f}
  .pf-stat b{display:block;font:600 16px var(--gl-font-sans);color:#fff;font-variant-numeric:tabular-nums}
  .pf-stat span{font-size:11px;color:#ffffff99}
  .pf-live{display:inline-block;width:7px;height:7px;border-radius:50%;background:#5bd68a;margin-right:6px;vertical-align:middle}

  .st{opacity:0;transform:translateY(14px);
    transition:opacity .22s ease,transform .3s ease}
  .pf-card.open .st{opacity:1;transform:none;
    transition:opacity .55s ease calc(.18s + var(--i)*.06s),
               transform .7s var(--ease-out) calc(.18s + var(--i)*.06s)}
  .pf-photo.st{transform:scale(.7)}
  .pf-card.open .pf-photo.st{transform:none}

  .toast{position:absolute;z-index:40;left:50%;transform:translateX(-50%);bottom:112px;padding:12px 20px;border-radius:999px;
    font-size:14px;background:#000a;white-space:nowrap}

  @media (max-width:380px){.pf-photo{width:64px;height:64px}.pf-name{font-size:22px}.pf-head{gap:12px}}
  @media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}.in{opacity:1}.st{opacity:1;transform:none}}
</style>
</head>
<body>
<main id="app">

  <header class="top">
    <div class="in top-title" style="animation-delay:.1s">
      <div class="top-sub">Select a social</div>
      <h1>Choose your<br>destination</h1>
    </div>
    <div class="top-btns in" style="animation-delay:.3s">
      <button id="soundBtn" class="ibtn glass" aria-pressed="true" aria-label="Toggle sound">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 9v6h4l5 4V5L8 9z"/><path class="on" d="M16 9a4 4 0 010 6M18.5 6.5a8 8 0 010 11"/><path class="off" d="M17 9l5 6M22 9l-5 6"/></svg>
      </button>
      <?php if (is_logged_in()): ?>
        <a href="<?= dashboard_url('index.php') ?>" class="ibtn glass" title="Go to Dashboard" style="display:grid;place-items:center;text-decoration:none">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11 12 3l9 8v10H3z"/></svg>
        </a>
      <?php else: ?>
        <a href="<?= auth_url('login.php') ?>" class="ibtn glass" title="Sign In" style="display:grid;place-items:center;text-decoration:none">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        </a>
      <?php endif; ?>
    </div>
  </header>

  <div class="arc" id="arc"></div>
  <div id="cities"></div>

  <div class="track" id="track">
    <div class="thumb" id="thumb" role="slider" tabindex="0" aria-label="Social selector" aria-valuemin="0">
      <svg width="12" height="26" viewBox="0 0 12 26" fill="none" stroke="#222" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 7l4-4 4 4M2 19l4 4 4-4M2 12h8M2 15h8"/></svg>
    </div>
  </div>

  <nav class="bottom in" style="animation-delay:.5s">
    <button class="dock dock-home" aria-label="Home">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M3 11 12 3l9 8v10H3z"/></svg></button>
    <button id="plusBtn" class="dock dock-plus glass" aria-label="Open profile" aria-haspopup="dialog">+</button>
    <button id="profileBtn" class="pbtn glass" aria-label="Profile" aria-haspopup="dialog">
      <span class="nav-av"><img id="navImg" alt="" hidden></span>Profile</button>
  </nav>

  <div class="pf-bg" id="pfBg"></div>
  <div class="pf-wrap" id="pfWrap">
    <section class="pf-card" id="pfCard" role="dialog" aria-modal="true" aria-labelledby="pfName" aria-hidden="true" tabindex="-1">
      <button class="pf-x" id="pfClose" aria-label="Close">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
      <div class="pf-head">
        <div class="pf-photo st" style="--i:0">
          <div class="pf-inner"><span id="pfInitial"></span><img id="pfImg" alt="" hidden></div>
        </div>
        <div class="pf-info">
          <h2 class="pf-name st" id="pfName" style="--i:1"></h2>
          <p class="pf-handle st" id="pfHandle" style="--i:2"></p>
        </div>
      </div>
      <p class="pf-bio st" id="pfBio" style="--i:3"></p>
      <div class="pf-stats off st" id="pfStats" style="--i:4">
        <div class="pf-stat"><b data-k="followers_count">—</b><span>Followers</span></div>
        <div class="pf-stat"><b data-k="follows_count">—</b><span>Following</span></div>
        <div class="pf-stat"><b data-k="media_count">—</b><span>Posts</span></div>
      </div>
    </section>
  </div>

  <div id="toast" class="toast" hidden></div>
</main>

<script>
const API_URL="";
const PROFILE=<?= json_encode($profile, $J) ?>;

const LINKS_DATA=<?= json_encode($links, $J) ?>;
const LINKS={},IDS={};
LINKS_DATA.forEach(l=>{LINKS[l.name]=l.url;IDS[l.name]=l.id});
const CITIES=Object.keys(LINKS);
const N=CITIES.length, STEP=14*Math.PI/180, clamp=v=>Math.max(0,Math.min(N-1,v));
let target=Math.max(0,Math.min(4,N-1)),cur=target,hapIdx=target,soundOn=true,ctx=null,noiseBuf=null,W,H,R,cx,cy,big;

const app=document.getElementById('app'),wrap=document.getElementById('cities'),arc=document.getElementById('arc'),
 track=document.getElementById('track'),thumb=document.getElementById('thumb');
const els=CITIES.map((c,i)=>{const s=document.createElement('span');s.className='city';s.textContent=c;
 s.onclick=()=>{unlock();if(Math.abs(i-cur)<.5)openCity(c);else setT(i)};wrap.appendChild(s);return s});
thumb.setAttribute('aria-valuemax',Math.max(0,N-1));

function layout(){
 W=innerWidth;H=innerHeight;cy=H*.52;
 if(W<700){cx=-W*.1;R=W*.52;big=W*.12}
 else if(H>W&&W<1100){cx=-W*.06;R=W*.5;big=W*.085}
 else if(H<520){cx=W*.08;R=Math.min(W*.3,H*.9);big=H*.13}
 else{cx=W*.1;R=Math.min(W*.32,H*.62);big=Math.min(W*.05,H*.1)}
 const r=R*.8;
 Object.assign(arc.style,{width:r*2+'px',height:r*2+'px',left:(cx-r)+'px',top:(cy-r)+'px'});
}

function render(){
 cur+=(target-cur)*.16;if(Math.abs(target-cur)<.001)cur=target;
 els.forEach((el,i)=>{
  const d=i-cur,a=d*STEP,ad=Math.abs(d);
  const x=cx+R*Math.cos(a),y=cy+R*Math.sin(a);
  const size=ad<1?big+(big*.46-big)*ad:Math.max(big*.28,big*.46-(ad-1)*big*.05);
  const act=ad<.5;
  el.style.fontSize=size+'px';
  el.style.opacity=Math.max(0,1-ad*.28);
  el.style.color=act?'#fff':'#aeb3bb';
  el.style.textTransform=act?'uppercase':'none';
  el.style.transform=`translate(${x}px,${y}px) translateY(-50%) rotate(${a}rad)`;
  el.style.pointerEvents=ad>3.5?'none':'auto';
 });
 thumb.style.top=(N>1?(cur/(N-1)):0)*(track.clientHeight-44)+'px';
 thumb.setAttribute('aria-valuenow',Math.round(cur));
 requestAnimationFrame(render);
}

function unlock(){
 if(!ctx){try{ctx=new(window.AudioContext||window.webkitAudioContext)()}catch(e){}}
 if(ctx&&ctx.state!=='running')ctx.resume();
}
['pointerdown','touchstart','keydown','wheel','click'].forEach(ev=>addEventListener(ev,unlock,{passive:true}));
try{unlock()}catch(e){}
function sound(){
 if(!soundOn||!ctx)return;const t=ctx.currentTime;
 if(!noiseBuf){noiseBuf=ctx.createBuffer(1,Math.floor(ctx.sampleRate*.03),ctx.sampleRate);
  const d=noiseBuf.getChannelData(0);for(let i=0;i<d.length;i++)d[i]=Math.random()*2-1}
 const n=ctx.createBufferSource();n.buffer=noiseBuf;
 const bp=ctx.createBiquadFilter();bp.type='bandpass';bp.frequency.value=3200;bp.Q.value=1.2;
 const g=ctx.createGain();g.gain.setValueAtTime(.55,t);g.gain.exponentialRampToValueAtTime(.001,t+.012);
 n.connect(bp).connect(g).connect(ctx.destination);n.start(t);n.stop(t+.02);
 const o=ctx.createOscillator(),og=ctx.createGain();
 o.type='sine';o.frequency.setValueAtTime(260,t);o.frequency.exponentialRampToValueAtTime(120,t+.02);
 og.gain.setValueAtTime(.22,t);og.gain.exponentialRampToValueAtTime(.001,t+.025);
 o.connect(og).connect(ctx.destination);o.start(t);o.stop(t+.03);
}
function haptic(){
 try{
  if(navigator.vibrate&&navigator.vibrate(10))return;
  const l=document.createElement('label');l.setAttribute('aria-hidden','true');l.style.display='none';
  const i=document.createElement('input');i.type='checkbox';i.setAttribute('switch','');
  l.appendChild(i);document.body.appendChild(l);l.click();setTimeout(()=>l.remove(),60);
 }catch(e){}
}
function setT(v){
 target=clamp(v);const i=Math.round(target);
 if(i!==hapIdx){hapIdx=i;haptic();sound()}
}

const sb=document.getElementById('soundBtn');
sb.onclick=()=>{unlock();soundOn=!soundOn;sb.setAttribute('aria-pressed',soundOn);if(soundOn){sound()}haptic()};

app.addEventListener('wheel',e=>{e.preventDefault();unlock();setT(target+Math.sign(e.deltaY)*.45);
 clearTimeout(app._w);app._w=setTimeout(()=>target=Math.round(target),140)},{passive:false});
let dragY=null,startT=0,sliding=false;
app.addEventListener('pointerdown',e=>{unlock();if(e.target.closest('button,a,.track,label,.pf-card'))return;dragY=e.clientY;startT=target});
addEventListener('pointermove',e=>{
 if(dragY!==null)setT(startT-(e.clientY-dragY)/42);
 if(sliding){const r=track.getBoundingClientRect();setT(((e.clientY-r.top)/r.height)*(N-1))}});
addEventListener('pointerup',()=>{if(dragY!==null||sliding)target=Math.round(target);dragY=null;sliding=false});
addEventListener('pointercancel',()=>{dragY=null;sliding=false});
thumb.addEventListener('pointerdown',e=>{sliding=true;unlock();e.preventDefault()});
track.addEventListener('pointerdown',e=>{if(e.target.closest('.thumb'))return;unlock();const r=track.getBoundingClientRect();setT(Math.round(((e.clientY-r.top)/r.height)*(N-1)))});
addEventListener('keydown',e=>{
 if(pfOpen){if(e.key==='Escape')closeProfile();return}
 if(['ArrowDown','ArrowRight'].includes(e.key))setT(Math.round(target)+1);
 if(['ArrowUp','ArrowLeft'].includes(e.key))setT(Math.round(target)-1)});
function toast(msg,ms){
 const t=document.getElementById('toast');t.textContent=msg;t.hidden=false;
 clearTimeout(t._h);t._h=setTimeout(()=>t.hidden=true,ms);
}

function openCity(name){
 toast('Opening '+name+'…',1600);
 try{
  const fd=new FormData();fd.append('id',IDS[name]);
  if(!(navigator.sendBeacon&&navigator.sendBeacon('api.php',fd)))
   fetch('api.php',{method:'POST',body:fd,keepalive:true});
 }catch(e){}
 window.open(LINKS[name],'_blank','noopener');
}

const pfBg=document.getElementById('pfBg'),pfCard=document.getElementById('pfCard'),pfWrap=document.getElementById('pfWrap'),
 pfImg=document.getElementById('pfImg'),pfInitial=document.getElementById('pfInitial'),
 navImg=document.getElementById('navImg'),
 profileBtn=document.getElementById('profileBtn');
let pfOpen=false,restoreT=null;

document.getElementById('pfName').textContent=PROFILE.name;
document.getElementById('pfHandle').textContent=PROFILE.handle;
document.getElementById('pfBio').textContent=PROFILE.bio;
pfInitial.textContent=(PROFILE.name.trim()[0]||'?').toUpperCase();

function setPhoto(src){
 if(!src){
  pfImg.hidden=true;pfInitial.hidden=false;
  navImg.hidden=true;navImg.removeAttribute('src');
  return;
 }
 pfImg.onload=()=>{pfImg.hidden=false;pfInitial.hidden=true};
 pfImg.onerror=()=>{pfImg.hidden=true;pfInitial.hidden=false};
 navImg.onload=()=>{navImg.hidden=false};
 navImg.onerror=()=>{navImg.hidden=true};
 pfImg.src=src;
 navImg.src=src;
}
setPhoto(PROFILE.photo);

const fmt=n=>n>=1e6?(n/1e6).toFixed(1).replace('.0','')+'M':n>=1e4?(n/1e3).toFixed(1).replace('.0','')+'K':String(n);
function countUp(el,to){
 const t0=performance.now(),D=900;
 (function f(t){const k=Math.min(1,(t-t0)/D),e=1-Math.pow(1-k,3);
  el.textContent=fmt(Math.round(to*e));if(k<1)requestAnimationFrame(f)})(t0);
}
let liveAt=0;
async function loadLive(){
 if(!API_URL||Date.now()-liveAt<60000)return;
 try{
  const r=await fetch(API_URL,{cache:'no-store'});if(!r.ok)throw 0;
  const d=await r.json();liveAt=Date.now();
  if(d.name||d.username){document.getElementById('pfName').textContent=d.name||d.username;
   pfInitial.textContent=(d.name||d.username)[0].toUpperCase()}
  if(d.username)document.getElementById('pfHandle').innerHTML='<i class="pf-live"></i>@'+String(d.username).replace(/[<>&"']/g,'');
  if(d.biography)document.getElementById('pfBio').textContent=d.biography;
  if(d.profile_picture_url)setPhoto(d.profile_picture_url);
  const stats=document.getElementById('pfStats');
  if(['followers_count','follows_count','media_count'].some(k=>typeof d[k]==='number')){
   stats.classList.remove('off');
   stats.querySelectorAll('[data-k]').forEach(b=>{
    if(typeof d[b.dataset.k]==='number')setTimeout(()=>countUp(b,d[b.dataset.k]),700)});
  }
 }catch(e){
  toast('Could not load live data',2200);
 }
}

function morphFrom(btn){
 const b=btn.getBoundingClientRect(),c=pfCard.getBoundingClientRect();
 pfCard.style.setProperty('--cl',Math.max(0,b.left-c.left)+'px');
 pfCard.style.setProperty('--cr',Math.max(0,c.right-b.right)+'px');
 pfCard.style.setProperty('--ct',Math.max(0,c.height-b.height)+'px');
}

function openProfile(){
 if(pfOpen)return;
 clearTimeout(restoreT);
 unlock();haptic();loadLive();
 morphFrom(profileBtn);
 void pfCard.offsetWidth;
 pfOpen=true;
 profileBtn.style.opacity='0';
 pfBg.classList.add('open');pfWrap.classList.add('open');
 pfCard.classList.add('open');pfCard.setAttribute('aria-hidden','false');
 setTimeout(()=>{if(pfOpen)pfCard.focus({preventScroll:true})},450);
}
function closeProfile(){
 if(!pfOpen)return;pfOpen=false;haptic();
 morphFrom(profileBtn);
 pfBg.classList.remove('open');pfWrap.classList.remove('open');
 pfCard.classList.remove('open');pfCard.setAttribute('aria-hidden','true');

 restoreT=setTimeout(()=>{profileBtn.style.opacity=''},380);
 profileBtn.focus({preventScroll:true});
}
document.getElementById('plusBtn').onclick=openProfile;
profileBtn.onclick=openProfile;
document.getElementById('pfClose').onclick=closeProfile;
pfBg.onclick=closeProfile;

layout();addEventListener('resize',layout);
addEventListener('orientationchange',()=>setTimeout(layout,200));
requestAnimationFrame(render);
</script>
</body>
</html>