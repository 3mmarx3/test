<?php
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$currentUser = current_user();
$userId = (int)$currentUser['id'];
$isAdmin = is_admin();

$rawRange = (int)($_GET['range'] ?? 30);
$range = in_array($rawRange, [7, 30, 90], true) ? $rawRange : 30;
$todayStart = strtotime('today midnight');
$start      = strtotime("-{$range} days", $todayStart);
$prevStart  = strtotime('-' . ($range * 2) . ' days', $todayStart);

if ($isAdmin) {
    $stmtVisits = $pdo->query('SELECT created_at AS ts, visitor, country, referrer, ua FROM visits ORDER BY created_at DESC');
    $rows = $stmtVisits->fetchAll();

    $stmtTotalClicks = $pdo->query('SELECT COALESCE(SUM(clicks), 0) FROM links');
    $totalClicks = (int)$stmtTotalClicks->fetchColumn();

    $stmtTopLinks = $pdo->query('SELECT name, clicks FROM links ORDER BY clicks DESC');
    $links = $stmtTopLinks->fetchAll();

    $allVisits = count($rows);
    $allUniques = count(array_unique(array_column($rows, 'visitor') ?: []));
    $totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
} else {
    $stmtVisits = $pdo->prepare('SELECT created_at AS ts, visitor, country, referrer, ua FROM visits WHERE user_id = ? ORDER BY created_at DESC');
    $stmtVisits->execute([$userId]);
    $rows = $stmtVisits->fetchAll();

    $stmtTotalClicks = $pdo->prepare('SELECT COALESCE(SUM(clicks), 0) FROM links WHERE user_id = ?');
    $stmtTotalClicks->execute([$userId]);
    $totalClicks = (int)$stmtTotalClicks->fetchColumn();

    $stmtTopLinks = $pdo->prepare('SELECT name, clicks FROM links WHERE user_id = ? ORDER BY clicks DESC');
    $stmtTopLinks->execute([$userId]);
    $links = $stmtTopLinks->fetchAll();

    $allVisits = count($rows);
    $allUniques = count(array_unique(array_column($rows, 'visitor') ?: []));
    $totalUsers = 0;
}

$ctr = $allVisits > 0 ? round(($totalClicks / $allVisits) * 100, 1) : 0;
$maxClicks = max(1, ...array_map('intval', array_column($links, 'clicks') ?: [0]));

$days = []; $dv = []; $du = [];
for ($i = $range - 1; $i >= 0; $i--) {
    $k = date('Y-m-d', strtotime("-$i days", $todayStart));
    $days[] = $k; $dv[$k] = 0; $du[$k] = [];
}

$first = []; $inR = []; $prevCnt = 0; $prevU = []; $today = 0; $active = 0; $recent = [];
foreach ($rows as $r) {
    $t = ts_of($r['ts']);
    if ($t === null) continue;
    $r['t'] = $t;
    if (!isset($first[$r['visitor']]) || $t < $first[$r['visitor']]) $first[$r['visitor']] = $t;
    if ($t >= $start) $inR[] = $r;
    elseif ($t >= $prevStart) { $prevCnt++; $prevU[$r['visitor']] = 1; }
    if ($t >= $todayStart) $today++;
    if ($t >= time() - 1800) $active++;
    if (count($recent) < 12) $recent[] = $r;
}

$countries = []; $refs = []; $devs = []; $brs = []; $oss = [];
$hours = array_fill(0, 24, 0); $uv = []; $newV = []; $retV = [];
$own = preg_replace('/^www\./', '', strtolower($_SERVER['HTTP_HOST'] ?? ''));

foreach ($inR as $r) {
    $k = date('Y-m-d', $r['t']);
    if (isset($dv[$k])) { $dv[$k]++; $du[$k][$r['visitor']] = 1; }
    $uv[$r['visitor']] = 1;
    if ($first[$r['visitor']] >= $start) $newV[$r['visitor']] = 1; else $retV[$r['visitor']] = 1;
    $hours[(int)date('G', $r['t'])]++;
    $cc = $r['country'] ?: '??'; $countries[$cc] = ($countries[$cc] ?? 0) + 1;
    $h = $r['referrer'] ? preg_replace('/^www\./', '', strtolower((string)parse_url($r['referrer'], PHP_URL_HOST))) : '';
    if ($h === '' || $h === $own) $h = 'Direct';
    $refs[$h] = ($refs[$h] ?? 0) + 1;
    $ua = $r['ua'] ?? '';
    $d = preg_match('/ipad|tablet/i', $ua) ? 'Tablet' : (preg_match('/mobi|android|iphone/i', $ua) ? 'Mobile' : 'Desktop');
    $devs[$d] = ($devs[$d] ?? 0) + 1;
    $b = preg_match('/edg/i', $ua) ? 'Edge' : (preg_match('/opr|opera/i', $ua) ? 'Opera' : (preg_match('/firefox|fxios/i', $ua) ? 'Firefox'
       : (preg_match('/chrome|crios/i', $ua) ? 'Chrome' : (preg_match('/safari/i', $ua) ? 'Safari' : 'Other'))));
    $brs[$b] = ($brs[$b] ?? 0) + 1;
    $o = preg_match('/iphone|ipad|ios/i', $ua) ? 'iOS' : (preg_match('/android/i', $ua) ? 'Android' : (preg_match('/windows/i', $ua) ? 'Windows'
       : (preg_match('/mac os|macintosh/i', $ua) ? 'macOS' : (preg_match('/linux/i', $ua) ? 'Linux' : 'Other'))));
    $oss[$o] = ($oss[$o] ?? 0) + 1;
}
arsort($countries); arsort($refs); arsort($devs); arsort($brs); arsort($oss);

$A = [
    'rangeVisits'  => count($inR),
    'rangeUniques' => count($uv),
    'prevVisits'   => $prevCnt,
    'prevUniques'  => count($prevU),
    'today'        => $today,
    'active'       => $active,
    'new'          => count($newV),
    'ret'          => count($retV),
    'hours'        => $hours,
    'chart'        => [
        'labels'  => array_map(fn($d) => date('M j', strtotime($d)), $days),
        'visits'  => array_values($dv),
        'uniques' => array_map('count', array_values($du)),
    ],
];

[$dv1, $dc1] = pct_delta($A['rangeVisits'], $A['prevVisits']);
[$du1, $dc2] = pct_delta($A['rangeUniques'], $A['prevUniques']);

$pageTitle  = 'Wander — Overview';
$activePage = 'overview';
$J = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
include __DIR__ . '/includes/header.php';
?>
<div class="shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main page">
    <div class="head">
      <div>
        <h1>Overview</h1>
        <p><span class="dot"></span><?= nf($A['active']) ?> active in the last 30 minutes · <?= nf($A['today']) ?> visits today <?= $isAdmin ? 'across platform' : 'on your page' ?></p>
      </div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <div class="seg">
          <a href="?range=7" class="<?= $range === 7 ? 'on' : '' ?>">7 days</a>
          <a href="?range=30" class="<?= $range === 30 ? 'on' : '' ?>">30 days</a>
          <a href="?range=90" class="<?= $range === 90 ? 'on' : '' ?>">90 days</a>
        </div>
        <?php if ($isAdmin): ?>
          <form method="post" action="<?= page_url('export.php') ?>" style="display:inline;margin:0">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="type" value="analytics">
            <button type="submit" class="btn s g" title="Export Analytics CSV" style="cursor:pointer">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
              Export CSV
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <?php $myPublicUrl = full_user_url($currentUser['username']); ?>
    <div class="card mb" style="border-color:rgba(255,255,255,0.12)">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px">
        <div>
          <div style="font-size:11.5px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);margin-bottom:4px;font-weight:600">My Public Profile</div>
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <h2 style="margin:0;font-size:18px;line-height:1.2"><?= e($currentUser['name']) ?></h2>
            <span style="font-size:13.5px;color:var(--mut)">@<?= e($currentUser['username']) ?></span>
            <span class="pill <?= ($currentUser['status'] ?? 'active') === 'active' ? 'up' : 'down' ?>" style="font-size:11px">
              <?= ($currentUser['status'] ?? 'active') === 'active' ? 'Public & Active' : 'Suspended' ?>
            </span>
          </div>
          <div style="margin-top:6px;font-family:monospace;font-size:13px;color:var(--a3);word-break:break-all">
            <?= e($myPublicUrl) ?>
          </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <button type="button" class="btn s copy-btn" data-url="<?= e($myPublicUrl) ?>" style="cursor:pointer">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            <span class="btn-text">Copy Link</span>
          </button>
          <a href="<?= e(user_url($currentUser['username'])) ?>" target="_blank" rel="noopener" class="btn s g" title="Open profile in new tab">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            <span>Open Profile</span>
          </a>
          <button type="button" class="btn s g share-btn" data-title="<?= e($currentUser['name']) ?>" data-url="<?= e($myPublicUrl) ?>" style="cursor:pointer">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            <span class="btn-text">Share</span>
          </button>
        </div>
      </div>
    </div>

    <div class="grid g4 mb">
      <div class="card kpi" style="--c:var(--a1)">
        <div class="l">Visits (<?= $range ?>d)</div>
        <div class="v"><?= nf($A['rangeVisits']) ?></div>
        <div class="d"><span class="pill <?= $dc1 ?>"><?= $dv1 ?></span>vs previous <?= $range ?> days</div>
      </div>
      <div class="card kpi" style="--c:var(--a2)">
        <div class="l">Unique visitors (<?= $range ?>d)</div>
        <div class="v"><?= nf($A['rangeUniques']) ?></div>
        <div class="d"><span class="pill <?= $dc2 ?>"><?= $du1 ?></span>vs previous <?= $range ?> days</div>
      </div>
      <div class="card kpi" style="--c:var(--a3)">
        <div class="l"><?= $isAdmin ? 'Platform link clicks' : 'My link clicks' ?></div>
        <div class="v"><?= nf($totalClicks) ?></div>
        <div class="d">across <?= count($links) ?> destination<?= count($links) === 1 ? '' : 's' ?></div>
      </div>
      <div class="card kpi" style="--c:var(--ok)">
        <div class="l"><?= $isAdmin ? 'Registered members' : 'Click rate' ?></div>
        <div class="v"><?= $isAdmin ? nf($totalUsers) : $ctr . '%' ?></div>
        <div class="d"><?= $isAdmin ? nf($allVisits) . ' platform visits' : nf($allVisits) . ' visits · ' . nf($allUniques) . ' visitors' ?></div>
      </div>
    </div>

    <div class="card mb">
      <h2>Traffic
        <div class="legend"><span><i style="background:var(--a1)"></i>Visits</span><span><i style="background:var(--a3)"></i>Unique visitors</span></div>
      </h2>
      <div id="chartBox"><svg id="chart" viewBox="0 0 800 260" preserveAspectRatio="none"></svg><div id="tip"></div></div>
    </div>

    <div class="grid g3 mb">
      <div class="card"><h2>Top countries <span>visits</span></h2>
        <?php bars($countries, $A['rangeVisits'], fn($c) => flag($c === '??' ? '' : $c) . ' ' . e($c === '??' ? 'Unknown' : country_name($c))); ?></div>
      <div class="card"><h2>Traffic sources <span>visits</span></h2>
        <?php bars($refs, $A['rangeVisits']); ?></div>
      <div class="card"><h2>Devices <span>visits</span></h2>
        <?php bars($devs, $A['rangeVisits']); ?>
        <h2 style="margin-top:18px">New vs returning</h2>
        <?php $nt = max(1, $A['new'] + $A['ret']); ?>
        <div class="split"><div style="width:<?= round($A['new'] / $nt * 100) ?>%;background:var(--a1)"></div><div style="width:<?= round($A['ret'] / $nt * 100) ?>%;background:var(--a2)"></div></div>
        <div class="legend2"><span>New<b><?= nf($A['new']) ?></b></span><span>Returning<b><?= nf($A['ret']) ?></b></span></div>
      </div>
    </div>

    <div class="grid g3 mb">
      <div class="card"><h2>Browsers <span>visits</span></h2><?php bars($brs, $A['rangeVisits'], null, 6); ?></div>
      <div class="card"><h2>Operating systems <span>visits</span></h2><?php bars($oss, $A['rangeVisits'], null, 6); ?></div>
      <div class="card"><h2>Busiest hours <span><?= e(VIEW_TZ) ?></span></h2>
        <?php $hm = max(1, ...$A['hours']); ?>
        <div class="hours"><?php foreach ($A['hours'] as $h => $c): ?><div style="height:<?= max(3, round($c / $hm * 100)) ?>%" title="<?= sprintf('%02d:00', $h) ?> — <?= $c ?> visits"></div><?php endforeach; ?></div>
        <div class="hlab"><span>12 AM</span><span>6 AM</span><span>12 PM</span><span>6 PM</span><span>11 PM</span></div>
      </div>
    </div>

    <div class="card mb">
      <h2>Most clicked links <span>all time</span></h2>
      <?php if (!$links): ?>
        <p class="empty">No destinations recorded yet. Manage your destinations on the <a href="<?= page_url('links.php') ?>" style="text-decoration:underline">Pages</a> section.</p>
      <?php else:
        $sorted = $links; usort($sorted, fn($a, $b) => $b['clicks'] <=> $a['clicks']);
        foreach (array_slice($sorted, 0, 8) as $l):
          $w = round($l['clicks'] / $maxClicks * 100); $p = $totalClicks ? round($l['clicks'] / $totalClicks * 100) : 0; ?>
        <div class="bar"><div class="bar-fill" style="width:<?= $w ?>%"></div><span class="bar-l"><?= e($l['name']) ?></span><span class="bar-v"><?= nf($l['clicks']) ?> <i><?= $p ?>%</i></span></div>
      <?php endforeach; endif; ?>
    </div>

    <div class="card">
      <h2>Recent visits <span>latest 12</span></h2>
      <div class="scroll"><table class="tbl">
        <thead><tr><th>When</th><th>Country</th><th>Source</th><th>Device</th><th>Browser</th></tr></thead>
        <tbody>
        <?php if (!$recent): ?><tr><td colspan="5" class="empty">No visits recorded yet.</td></tr><?php endif; ?>
        <?php foreach ($recent as $r):
          $ua = $r['ua'] ?? '';
          $h = $r['referrer'] ? preg_replace('/^www\./', '', strtolower((string)parse_url($r['referrer'], PHP_URL_HOST))) : '';
          if ($h === '' || $h === $own) $h = 'Direct';
          $d = preg_match('/ipad|tablet/i', $ua) ? 'Tablet' : (preg_match('/mobi|android|iphone/i', $ua) ? 'Mobile' : 'Desktop');
          $b = preg_match('/edg/i', $ua) ? 'Edge' : (preg_match('/opr|opera/i', $ua) ? 'Opera' : (preg_match('/firefox|fxios/i', $ua) ? 'Firefox' : (preg_match('/chrome|crios/i', $ua) ? 'Chrome' : (preg_match('/safari/i', $ua) ? 'Safari' : 'Other')))); ?>
          <tr><td title="<?= e(date('Y-m-d H:i', $r['t'])) ?>"><?= e(ago($r['t'])) ?></td>
              <td><?= flag($r['country']) ?> <?= e($r['country'] ? country_name($r['country']) : 'Unknown') ?></td>
              <td><span class="tag"><?= e($h) ?></span></td><td><?= e($d) ?></td><td><?= e($b) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>
  </main>
</div>

<script>
const D=<?= json_encode($A['chart'], $J) ?>;
function drawChart(){
 const svg=document.getElementById('chart'),tip=document.getElementById('tip'),box=document.getElementById('chartBox');
 if(!svg||!box||!D||!D.labels)return;
 const boxWidth=box.clientWidth||800;
 const W=Math.max(280,boxWidth);
 const H=Math.max(160,Math.min(260,Math.round(W*0.36)));
 svg.setAttribute('viewBox','0 0 '+W+' '+H);
 const pl=W<480?26:34,pr=8,pt=12,pb=W<480?22:26,n=D.labels.length;
 const max=Math.max(4,...D.visits,...D.uniques),nice=Math.ceil(max/4)*4;
 const x=i=>pl+(n>1?i/(n-1):.5)*(W-pl-pr),y=v=>pt+(1-v/nice)*(H-pt-pb);
 const path=a=>a.map((v,i)=>(i?'L':'M')+x(i).toFixed(1)+' '+y(v).toFixed(1)).join(' ');
 const area=a=>path(a)+` L${x(n-1)} ${H-pb} L${x(0)} ${H-pb} Z`;
 let g='<defs><linearGradient id="g1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f5c96b" stop-opacity=".35"/><stop offset="1" stop-color="#f5c96b" stop-opacity="0"/></linearGradient>'+
  '<linearGradient id="g2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7fb4ff" stop-opacity=".28"/><stop offset="1" stop-color="#7fb4ff" stop-opacity="0"/></linearGradient></defs>';
 for(let k=0;k<=4;k++){const v=nice/4*k,yy=y(v);
  g+=`<line x1="${pl}" x2="${W-pr}" y1="${yy}" y2="${yy}" stroke="#ffffff14" vector-effect="non-scaling-stroke"/><text x="${pl-6}" y="${yy+4}" text-anchor="end" font-size="11" fill="#ffffff80">${Math.round(v)}</text>`}
 const step=Math.ceil(n/(W<450?3:(W<680?5:7)));
 D.labels.forEach((l,i)=>{if(i%step===0||i===n-1)g+=`<text x="${x(i)}" y="${H-6}" text-anchor="middle" font-size="11" fill="#ffffff80">${l}</text>`});
 g+=`<path d="${area(D.visits)}" fill="url(#g1)"/><path d="${area(D.uniques)}" fill="url(#g2)"/>`;
 g+=`<path d="${path(D.visits)}" fill="none" stroke="#f5c96b" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>`;
 g+=`<path d="${path(D.uniques)}" fill="none" stroke="#7fb4ff" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>`;
 g+='<g id="hv" style="display:none"><line id="hl" y1="'+pt+'" y2="'+(H-pb)+'" stroke="#ffffff40" vector-effect="non-scaling-stroke"/><circle id="h1" r="4" fill="#f5c96b"/><circle id="h2" r="4" fill="#7fb4ff"/></g>';
 svg.innerHTML=g;
 const hv=svg.querySelector('#hv'),hl=svg.querySelector('#hl'),h1=svg.querySelector('#h1'),h2=svg.querySelector('#h2');
 function move(e){
  const r=svg.getBoundingClientRect(),cxp=(e.touches?e.touches[0].clientX:e.clientX)-r.left;
  const i=Math.max(0,Math.min(n-1,Math.round(((cxp/r.width)*W-pl)/(W-pl-pr)*(n-1))));
  hv.style.display='';hl.setAttribute('x1',x(i));hl.setAttribute('x2',x(i));
  h1.setAttribute('cx',x(i));h1.setAttribute('cy',y(D.visits[i]));h2.setAttribute('cx',x(i));h2.setAttribute('cy',y(D.uniques[i]));
  tip.innerHTML=`<b>${D.labels[i]}</b>Visits: ${D.visits[i]}<br>Visitors: ${D.uniques[i]}`;
  tip.style.left=(x(i)/W*r.width)+'px';tip.style.top=(y(Math.max(D.visits[i],D.uniques[i]))/H*r.height)+'px';tip.style.opacity=1;
 }
 const out=()=>{hv.style.display='none';tip.style.opacity=0};
 box.onmousemove=move;box.onmouseleave=out;box.ontouchstart=move;box.ontouchmove=move;box.ontouchend=out;
}
drawChart();
addEventListener('resize',drawChart);
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
