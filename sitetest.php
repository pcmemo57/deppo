<?php
// ─── AJAX: Ping isteği ───────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'ping') {
    header('Content-Type: application/json');

    $url     = trim($_POST['url'] ?? '');
    $count   = min(max((int)($_POST['count'] ?? 4), 1), 10);
    $timeout = min(max((int)($_POST['timeout'] ?? 8), 2), 30);

    if (!$url) { echo json_encode(['error' => 'URL boş']); exit; }
    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    if (!filter_var($url, FILTER_VALIDATE_URL)) { echo json_encode(['error' => 'Geçersiz URL']); exit; }

    $times = $ttfbs = $dnsTimes = $connectTimes = $sizes = $codes = [];
    $errors = 0;

    for ($i = 0; $i < $count; $i++) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'PingBench/2.0 PHP/' . PHP_VERSION,
            CURLOPT_HEADER         => false,
            CURLOPT_NOBODY         => false,
        ]);

        $start = microtime(true);
        curl_exec($ch);
        $info  = curl_getinfo($ch);
        $err   = curl_error($ch);
        curl_close($ch);
        $total = (microtime(true) - $start) * 1000;

        if ($err || $info['http_code'] === 0) {
            $errors++;
        } else {
            $times[]        = $total;
            $ttfbs[]        = $info['starttransfer_time'] * 1000;
            $dnsTimes[]     = $info['namelookup_time']    * 1000;
            $connectTimes[] = $info['connect_time']       * 1000;
            $sizes[]        = (int)$info['size_download'];
            $codes[]        = (int)$info['http_code'];
        }

        if ($i < $count - 1) usleep(150000);
    }

    $n = count($times);

    function arr_avg(array $a): float { return $a ? array_sum($a) / count($a) : 0; }
    function arr_stddev(array $a): float {
        if (count($a) < 2) return 0;
        $m = arr_avg($a);
        return sqrt(array_sum(array_map(fn($v) => ($v - $m) ** 2, $a)) / count($a));
    }

    echo json_encode([
        'url'        => $url,
        'host'       => parse_url($url, PHP_URL_HOST),
        'success'    => $n,
        'errors'     => $errors,
        'loss_pct'   => $count > 0 ? round(($errors / $count) * 100, 1) : 100,
        'avg_ms'     => $n ? round(arr_avg($times), 1)        : null,
        'min_ms'     => $n ? round(min($times), 1)             : null,
        'max_ms'     => $n ? round(max($times), 1)             : null,
        'stddev_ms'  => $n ? round(arr_stddev($times), 1)     : null,
        'ttfb_ms'    => $n ? round(arr_avg($ttfbs), 1)        : null,
        'dns_ms'     => $n ? round(arr_avg($dnsTimes), 1)     : null,
        'connect_ms' => $n ? round(arr_avg($connectTimes), 1) : null,
        'avg_size'   => $n ? round(arr_avg($sizes))            : null,
        'http_code'  => $codes ? end($codes) : 0,
        'reachable'  => $n > 0,
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>pingbench</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:      #090c14;
    --border:  rgba(255,255,255,0.07);
    --accent:  #4f8ef7;
    --accent2: #7c6af5;
    --green:   #22c55e;
    --yellow:  #f59e0b;
    --red:     #ef4444;
    --text:    #e2e8f0;
    --muted:   #4b5563;
    --subtle:  #1e2435;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body {
    background: var(--bg); color: var(--text);
    font-family: 'IBM Plex Mono', monospace;
    min-height: 100vh;
  }
  body {
    background-image:
      radial-gradient(ellipse 80% 45% at 50% -5%, rgba(79,142,247,0.09) 0%, transparent 65%),
      repeating-linear-gradient(0deg,  transparent, transparent 48px, rgba(255,255,255,0.012) 48px, rgba(255,255,255,0.012) 49px),
      repeating-linear-gradient(90deg, transparent, transparent 48px, rgba(255,255,255,0.012) 48px, rgba(255,255,255,0.012) 49px);
  }

  .wrap { max-width: 860px; margin: 0 auto; padding: 48px 20px 100px; }

  .header { margin-bottom: 40px; }
  .logo { display: flex; align-items: baseline; gap: 10px; margin-bottom: 8px; }
  .logo-text { font-size: 26px; font-weight: 700; letter-spacing: -1px; color: #f8fafc; }
  .logo-dot  { color: var(--accent); }
  .badge {
    font-size: 9px; letter-spacing: 1.5px; color: var(--accent);
    border: 1px solid rgba(79,142,247,0.35); padding: 2px 8px; border-radius: 4px;
  }
  .subtitle { font-size: 12px; color: var(--muted); }

  .input-bar {
    display: flex; gap: 6px; align-items: stretch;
    background: rgba(79,142,247,0.04);
    border: 1px solid rgba(79,142,247,0.2);
    border-radius: 10px; padding: 5px;
    margin-bottom: 10px; transition: border-color .2s;
  }
  .input-bar:focus-within { border-color: rgba(79,142,247,0.5); }
  .input-prefix { display: flex; align-items: center; padding: 0 10px 0 8px; color: var(--accent); font-size: 13px; user-select: none; }
  #urlInput {
    flex: 1; background: none; border: none; outline: none;
    color: var(--text); font-family: inherit; font-size: 14px;
    caret-color: var(--accent);
  }
  #urlInput::placeholder { color: #2d3748; }

  .btn-add {
    padding: 9px 18px; border: none; border-radius: 7px; cursor: pointer;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff; font-family: inherit; font-size: 12px; font-weight: 600;
    letter-spacing: .3px; white-space: nowrap; transition: all .15s;
  }
  .btn-add:hover:not(:disabled) { filter: brightness(1.12); transform: translateY(-1px); }
  .btn-add:disabled { background: rgba(79,142,247,0.12); color: #374151; cursor: not-allowed; transform: none; filter: none; }

  .options-row {
    display: flex; align-items: center; gap: 12px; margin-bottom: 8px;
    font-size: 11px; color: var(--muted); flex-wrap: wrap;
  }
  .options-row label { display: flex; align-items: center; gap: 5px; }
  .options-row select {
    background: var(--subtle); border: 1px solid var(--border);
    color: var(--text); font-family: inherit; font-size: 11px;
    padding: 3px 6px; border-radius: 5px; outline: none;
  }
  .spacer { flex: 1; }

  .btn-retest-all {
    padding: 5px 14px; font-size: 11px; font-family: inherit; font-weight: 600;
    background: rgba(79,142,247,0.08); border: 1px solid rgba(79,142,247,0.3);
    color: var(--accent); border-radius: 6px; cursor: pointer; transition: all .15s;
    display: none; align-items: center; gap: 5px; white-space: nowrap;
  }
  .btn-retest-all:hover:not(:disabled) { background: rgba(79,142,247,0.18); transform: translateY(-1px); }
  .btn-retest-all:disabled { opacity: .35; cursor: not-allowed; transform: none; }

  .btn-clear {
    padding: 5px 12px; font-size: 11px; font-family: inherit;
    background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);
    color: var(--red); border-radius: 6px; cursor: pointer; transition: all .15s;
    display: none;
  }
  .btn-clear:hover { background: rgba(239,68,68,0.14); }

  #retestProgress {
    height: 2px; border-radius: 99px; background: rgba(79,142,247,0.12);
    margin-bottom: 16px; overflow: hidden; display: none;
  }
  #retestProgressFill {
    height: 100%; border-radius: 99px; width: 0%;
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    transition: width .4s ease;
  }

  .empty {
    text-align: center; padding: 72px 0;
    border: 1px dashed rgba(255,255,255,0.05); border-radius: 14px;
  }
  .empty-icon { font-size: 38px; opacity: .35; margin-bottom: 12px; }
  .empty-text { color: #2d3748; font-size: 13px; }

  #siteList { display: flex; flex-direction: column; gap: 8px; margin-bottom: 8px; }

  .site-card {
    background: rgba(13,17,30,0.92);
    border: 1px solid var(--border);
    border-radius: 11px; padding: 14px 18px;
    position: relative; overflow: hidden;
    transition: border-color .3s, box-shadow .3s;
    animation: fadeIn .25s ease;
  }
  .site-card.testing  { border-color: rgba(79,142,247,0.3); box-shadow: 0 0 20px rgba(79,142,247,0.05); }
  .site-card.has-error { border-color: rgba(239,68,68,0.2); }

  .scan-line {
    position: absolute; top: 0; left: -60%; width: 60%; height: 1px;
    background: linear-gradient(90deg, transparent, var(--accent), transparent);
    animation: sweep 1.6s linear infinite;
  }

  .card-body { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .rank      { width: 28px; text-align: center; font-size: 16px; flex-shrink: 0; }
  .rank-num  { font-size: 10px; color: var(--muted); }
  .host-block { flex: 1; min-width: 130px; }
  .host-name  { font-size: 14px; font-weight: 600; color: #f1f5f9; }
  .host-url   { font-size: 10px; color: #2d3748; margin-top: 2px; word-break: break-all; }

  .metrics { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
  .metric  { text-align: center; min-width: 46px; }
  .metric-label { font-size: 9px; color: var(--muted); letter-spacing: .08em; margin-bottom: 3px; }
  .metric-value { font-size: 13px; font-weight: 600; color: #94a3b8; }
  .metric-value.big { font-size: 17px; }

  .http-badge {
    font-size: 10px; padding: 2px 8px; border-radius: 99px;
    background: rgba(34,197,94,0.08); color: var(--green);
    border: 1px solid rgba(34,197,94,0.18);
  }
  .status-error  { font-size: 12px; color: var(--red); }
  .testing-label { font-size: 11px; color: var(--accent); animation: pulse 1.2s ease-in-out infinite; }

  .card-actions { display: flex; gap: 6px; flex-shrink: 0; margin-left: auto; }
  .btn-icon {
    width: 28px; height: 28px; border-radius: 6px;
    border: 1px solid var(--border);
    background: rgba(255,255,255,0.03); color: var(--muted);
    cursor: pointer; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s; font-family: inherit;
  }
  .btn-icon.retest { border-color: rgba(79,142,247,0.2); color: var(--accent); background: rgba(79,142,247,0.05); }
  .btn-icon.retest:hover:not(:disabled) { background: rgba(79,142,247,0.14); }
  .btn-icon.del:hover { border-color: rgba(239,68,68,0.3); color: var(--red); }
  .btn-icon:disabled { opacity: .3; cursor: not-allowed; }

  .lat-bar    { margin-top: 10px; margin-left: 40px; }
  .lat-bar-bg { height: 3px; background: rgba(255,255,255,0.04); border-radius: 99px; overflow: hidden; }
  .lat-bar-fill { height: 100%; border-radius: 99px; transition: width 1s cubic-bezier(.16,1,.3,1); }

  #summary {
    margin-top: 20px; padding: 18px 22px; border-radius: 11px;
    background: rgba(79,142,247,0.03);
    border: 1px solid rgba(79,142,247,0.1);
    display: none;
  }
  .summary-title { font-size: 10px; color: var(--muted); letter-spacing: .1em; margin-bottom: 14px; }
  .summary-grid  { display: flex; gap: 36px; flex-wrap: wrap; }
  .summary-item .s-label { font-size: 9px; color: var(--muted); margin-bottom: 4px; }
  .summary-item .s-host  { font-size: 14px; font-weight: 700; }
  .summary-item .s-val   { font-size: 12px; }

  .spinner {
    width: 15px; height: 15px; border-radius: 50%;
    border: 2px solid rgba(79,142,247,0.2); border-top-color: var(--accent);
    animation: spin .8s linear infinite; display: inline-block;
  }

  @keyframes spin   { to { transform: rotate(360deg); } }
  @keyframes sweep  { 0% { left: -60%; } 100% { left: 110%; } }
  @keyframes pulse  { 0%,100% { opacity:1; } 50% { opacity:.3; } }
  @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
</style>
</head>
<body>
<div class="wrap">

  <div class="header">
    <div class="logo">
      <span class="logo-text">ping<span class="logo-dot">_</span>bench</span>
      <span class="badge">PHP</span>
    </div>
    <div class="subtitle">site ekle → gerçek curl ile ölç → karşılaştır</div>
  </div>

  <div class="input-bar">
    <div class="input-prefix">▶</div>
    <input id="urlInput" type="text" placeholder="google.com veya https://example.com" autocomplete="off" spellcheck="false">
    <button class="btn-add" id="btnAdd">+ Ekle &amp; Test Et</button>
  </div>

  <div class="options-row">
    <label>İstek:
      <select id="optCount">
        <option value="3">3×</option>
        <option value="4" selected>4×</option>
        <option value="6">6×</option>
        <option value="10">10×</option>
      </select>
    </label>
    <label>Timeout:
      <select id="optTimeout">
        <option value="5">5s</option>
        <option value="8" selected>8s</option>
        <option value="15">15s</option>
      </select>
    </label>
    <div class="spacer"></div>
    <button class="btn-retest-all" id="btnRetestAll">
      <span id="raIcon">⟳</span>
      <span id="raLabel">Tümünü Yeniden Test Et</span>
    </button>
    <button class="btn-clear" id="btnClear">🗑 Temizle</button>
  </div>

  <div id="retestProgress"><div id="retestProgressFill"></div></div>

  <div id="emptyState" class="empty">
    <div class="empty-icon">🛰</div>
    <div class="empty-text">Bir site adresi gir ve Enter'a bas</div>
  </div>

  <div id="siteList"></div>

  <div id="summary">
    <div class="summary-title">// karşılaştırma özeti</div>
    <div class="summary-grid" id="summaryGrid"></div>
  </div>

</div>
<script>
const state = { sites: [], busy: false };
const $     = id => document.getElementById(id);

$('urlInput').addEventListener('keydown', e => { if (e.key === 'Enter') addSite(); });
$('btnAdd').addEventListener('click', addSite);
$('btnClear').addEventListener('click', clearAll);
$('btnRetestAll').addEventListener('click', retestAll);

function normalizeUrl(raw) {
  let u = raw.trim();
  if (!u) return null;
  if (!/^https?:\/\//i.test(u)) u = 'https://' + u;
  try { new URL(u); return u; } catch { return null; }
}

function latColor(ms) {
  if (!ms || ms <= 0) return '#ef4444';
  if (ms < 200) return '#22c55e';
  if (ms < 500) return '#f59e0b';
  return '#ef4444';
}

function humanBytes(b) {
  if (!b || b <= 0) return '—';
  if (b < 1024)    return b + ' B';
  if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
  return (b / 1048576).toFixed(1) + ' MB';
}

function escHtml(s) {
  return String(s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function flashInput() {
  const bar = document.querySelector('.input-bar');
  bar.style.borderColor = 'rgba(239,68,68,0.6)';
  setTimeout(() => { bar.style.borderColor = ''; }, 700);
}

function refreshToolbar() {
  const has = state.sites.length > 0;
  $('btnAdd').disabled = state.busy;
  $('btnAdd').textContent = state.busy ? 'Ölçülüyor...' : '+ Ekle & Test Et';
  $('btnRetestAll').style.display = has ? 'flex' : 'none';
  $('btnRetestAll').disabled = state.busy;
  $('btnClear').style.display = has ? 'block' : 'none';
}

async function runTest(id) {
  state.busy = true;
  refreshToolbar();

  const site = state.sites.find(s => s.id === id);
  if (!site) { state.busy = false; refreshToolbar(); return; }

  const fd = new FormData();
  fd.append('action',  'ping');
  fd.append('url',     site.url);
  fd.append('count',   $('optCount').value);
  fd.append('timeout', $('optTimeout').value);

  try {
    const res  = await fetch(window.location.pathname, { method: 'POST', body: fd });
    const data = await res.json();
    site.result = data;
    site.status = (data.error || !data.reachable) ? 'error' : 'done';
  } catch (e) {
    site.result = null;
    site.status = 'error';
  }

  state.busy = false;
  refreshToolbar();
  renderAll();
}

async function addSite() {
  if (state.busy) return;
  const url = normalizeUrl($('urlInput').value);
  if (!url) { flashInput(); return; }

  let host;
  try { host = new URL(url).hostname; } catch { flashInput(); return; }
  if (state.sites.find(s => s.host === host)) { $('urlInput').value = ''; return; }

  const id = Date.now();
  state.sites.push({ id, url, host, status: 'testing', result: null });
  $('urlInput').value = '';
  renderAll();
  await runTest(id);
}

async function retestSite(id) {
  if (state.busy) return;
  const site = state.sites.find(s => s.id === id);
  if (!site) return;
  site.status = 'testing';
  site.result = null;
  renderAll();
  await runTest(id);
}

async function retestAll() {
  if (state.busy || !state.sites.length) return;

  state.sites.forEach(s => { s.status = 'testing'; s.result = null; });
  renderAll();

  const total = state.sites.length;
  $('retestProgress').style.display = 'block';
  $('retestProgressFill').style.width = '0%';

  for (let i = 0; i < total; i++) {
    const site = state.sites[i];
    if (!site) continue;
    $('raLabel').innerHTML = 'Yeniden Test <span style="color:#94a3b8;font-weight:400">' + (i + 1) + '/' + total + '</span>';
    $('retestProgressFill').style.width = Math.round((i / total) * 100) + '%';
    await runTest(site.id);
  }

  $('retestProgressFill').style.width = '100%';
  setTimeout(function() {
    $('retestProgress').style.display = 'none';
    $('retestProgressFill').style.width = '0%';
    $('raLabel').textContent = 'Tümünü Yeniden Test Et';
    $('raIcon').textContent  = '⟳';
  }, 700);

  state.sites.sort(function(a, b) {
    var aMs = (a.result && a.result.avg_ms) ? a.result.avg_ms : Infinity;
    var bMs = (b.result && b.result.avg_ms) ? b.result.avg_ms : Infinity;
    return aMs - bMs;
  });

  renderAll();
}

function removeSite(id) {
  state.sites = state.sites.filter(s => s.id !== id);
  renderAll();
}

function clearAll() {
  state.sites = [];
  state.busy  = false;
  renderAll();
}

function renderAll() {
  refreshToolbar();

  const hasSites = state.sites.length > 0;
  $('emptyState').style.display = hasSites ? 'none' : 'block';

  const done   = state.sites.filter(s => s.status === 'done' && s.result && s.result.avg_ms);
  const sorted = done.slice().sort((a, b) => a.result.avg_ms - b.result.avg_ms);
  const maxMs  = done.length ? Math.max.apply(null, done.map(s => s.result.avg_ms)) : 1;
  const medals = ['🥇', '🥈', '🥉'];

  const allSettled = hasSites && state.sites.every(s => s.status === 'done' || s.status === 'error');
  const display    = allSettled
    ? state.sites.slice().sort((a, b) => {
        var aMs = (a.result && a.result.avg_ms) ? a.result.avg_ms : Infinity;
        var bMs = (b.result && b.result.avg_ms) ? b.result.avg_ms : Infinity;
        return aMs - bMs;
      })
    : state.sites;

  $('siteList').innerHTML = display.map(function(site) {
    const r       = site.result;
    const testing = site.status === 'testing';
    const rankIdx = sorted.findIndex(s => s.id === site.id);
    const col     = (r && r.avg_ms) ? latColor(r.avg_ms) : '#6b7280';
    const barPct  = (r && r.avg_ms) ? Math.max(4, (r.avg_ms / maxMs) * 100) : 0;

    let rankHtml;
    if (testing)                       rankHtml = '<span class="spinner"></span>';
    else if (rankIdx >= 0 && rankIdx < 3) rankHtml = medals[rankIdx];
    else if (rankIdx >= 3)             rankHtml = '<span class="rank-num">#' + (rankIdx + 1) + '</span>';
    else                               rankHtml = '<span style="color:#2d3748">—</span>';

    let metricsHtml = '';
    if (testing) {
      metricsHtml = '<span class="testing-label">ölçülüyor...</span>';
    } else if (!r || r.reachable === false) {
      metricsHtml = '<span class="status-error">⚠ ulaşılamıyor' + (r && r.error ? ': ' + escHtml(r.error) : '') + '</span>';
    } else {
      metricsHtml =
        '<div class="metric"><div class="metric-label">ORT</div><div class="metric-value big" style="color:' + col + '">' + (r.avg_ms ? Math.round(r.avg_ms) + 'ms' : '—') + '</div></div>' +
        '<div class="metric"><div class="metric-label">MIN</div><div class="metric-value">'  + (r.min_ms ? Math.round(r.min_ms) + 'ms' : '—') + '</div></div>' +
        '<div class="metric"><div class="metric-label">MAKS</div><div class="metric-value">' + (r.max_ms ? Math.round(r.max_ms) + 'ms' : '—') + '</div></div>' +
        '<div class="metric"><div class="metric-label">TTFB</div><div class="metric-value">' + (r.ttfb_ms ? Math.round(r.ttfb_ms) + 'ms' : '—') + '</div></div>' +
        '<div class="metric"><div class="metric-label">DNS</div><div class="metric-value">'  + (r.dns_ms ? Math.round(r.dns_ms) + 'ms' : '—') + '</div></div>' +
        '<div class="metric"><div class="metric-label">KAYIP</div><div class="metric-value" style="color:' + (r.loss_pct > 0 ? '#ef4444' : '#22c55e') + '">%' + (r.loss_pct !== undefined ? r.loss_pct : 0) + '</div></div>' +
        '<div class="metric"><div class="metric-label">BOYUT</div><div class="metric-value">' + humanBytes(r.avg_size) + '</div></div>' +
        (r.http_code > 0 ? '<span class="http-badge">' + r.http_code + '</span>' : '');
    }

    const barHtml = (r && r.avg_ms) ?
      '<div class="lat-bar"><div class="lat-bar-bg"><div class="lat-bar-fill" style="width:' + barPct + '%;background:linear-gradient(90deg,' + col + '55,' + col + ')"></div></div></div>'
      : '';

    const retestBtnHtml = (site.status === 'done' || site.status === 'error')
      ? '<button class="btn-icon retest" onclick="retestSite(' + site.id + ')" ' + (state.busy ? 'disabled' : '') + ' title="Yeniden test et">↺</button>'
      : '';

    return '<div class="site-card ' + (testing ? 'testing' : '') + ' ' + (site.status === 'error' ? 'has-error' : '') + '">' +
      (testing ? '<div class="scan-line"></div>' : '') +
      '<div class="card-body">' +
        '<div class="rank">' + rankHtml + '</div>' +
        '<div class="host-block">' +
          '<div class="host-name">' + escHtml(site.host) + '</div>' +
          '<div class="host-url">'  + escHtml(site.url)  + '</div>' +
        '</div>' +
        '<div class="metrics">' + metricsHtml + '</div>' +
        '<div class="card-actions">' +
          retestBtnHtml +
          '<button class="btn-icon del" onclick="removeSite(' + site.id + ')" title="Kaldır">✕</button>' +
        '</div>' +
      '</div>' +
      barHtml +
    '</div>';
  }).join('');

  const summary = $('summary');
  if (sorted.length >= 2) {
    const best  = sorted[0];
    const worst = sorted[sorted.length - 1];
    $('summaryGrid').innerHTML =
      '<div class="summary-item"><div class="s-label">EN HIZLI</div><div class="s-host" style="color:#22c55e">' + escHtml(best.host) + '</div><div class="s-val" style="color:#22c55e">' + Math.round(best.result.avg_ms) + ' ms</div></div>' +
      '<div class="summary-item"><div class="s-label">EN YAVAŞ</div><div class="s-host" style="color:#ef4444">' + escHtml(worst.host) + '</div><div class="s-val" style="color:#ef4444">' + Math.round(worst.result.avg_ms) + ' ms</div></div>' +
      '<div class="summary-item"><div class="s-label">FARK</div><div class="s-host" style="color:#f1f5f9">' + Math.round(worst.result.avg_ms - best.result.avg_ms) + ' ms</div><div class="s-val" style="color:#4b5563">' + (worst.result.avg_ms / best.result.avg_ms).toFixed(1) + '× yavaş</div></div>' +
      '<div class="summary-item"><div class="s-label">TEST EDİLEN</div><div class="s-host" style="color:#f1f5f9">' + sorted.length + ' site</div></div>';
    summary.style.display = 'block';
  } else {
    summary.style.display = 'none';
  }
}

renderAll();
</script>
</body>
</html>