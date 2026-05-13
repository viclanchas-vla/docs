<?php
// includes/layout.php

function pgHead(string $title): void { ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> — ProactiveGroup Docs</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="/assets/admin.css">
</head>
<body class="layout">
<?php }

function pgSidebar(string $active): void {
    try {
        $nd = dbGet("SELECT COUNT(*) n FROM docs")['n'] ?? 0;
        $nc = dbGet("SELECT COUNT(*) n FROM clients WHERE is_active=1")['n'] ?? 0;
        $nl = dbGet("SELECT COUNT(*) n FROM share_links WHERE is_active=1")['n'] ?? 0;
    } catch(Exception $e) { $nd=$nc=$nl=0; }
    $nav = [
        ['dash',    '/admin/',             'Tableau de bord', 0],
        ['docs',    '/admin/docs.php',     'Documents',       $nd],
        ['clients', '/admin/clients.php',  'Clients',         $nc],
        ['cats',    '/admin/cats.php',     'Catégories',      0],
        ['links',   '/admin/links.php',    'Liens partagés',  $nl],
    ];
    $icons = [
        'dash'   => '<rect x="2" y="2" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="9" y="2" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="2" y="9" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="9" y="9" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/>',
        'docs'   => '<rect x="3" y="1" width="10" height="14" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M6 6h4M6 9h3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>',
        'clients'=> '<circle cx="8" cy="5" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M2 15c0-3 2.7-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'cats'   => '<path d="M2 4h12M2 8h9M2 12h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'links'  => '<circle cx="12" cy="4" r="2" stroke="currentColor" stroke-width="1.3"/><circle cx="4" cy="8" r="2" stroke="currentColor" stroke-width="1.3"/><circle cx="12" cy="12" r="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 7L10 5M6 9L10 11" stroke="currentColor" stroke-width="1.3"/>',
        'settings'=>'<circle cx="8" cy="8" r="2.5" stroke="currentColor" stroke-width="1.4"/><path d="M8 1v2M8 13v2M1 8h2M13 8h2M3.1 3.1l1.4 1.4M11.5 11.5l1.4 1.4M3.1 12.9l1.4-1.4M11.5 4.5l1.4-1.4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>',
        'logout' => '<path d="M6 8h7M11 6l2 2-2 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 3H4a1 1 0 00-1 1v8a1 1 0 001 1h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
    ];
    ?>
<nav class="sidebar" id="sidebar">
  <a href="/admin/" class="sb-brand">
    <img src="https://proactivegroup.fr/wp-content/uploads/2025/06/Original.svg" class="sb-logo" alt="PG"
      onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
    <div class="sb-fb">PG</div>
    <div><div class="sb-name">ProactiveGroup</div><div class="sb-sub">Documentation</div></div>
  </a>
  <div class="sb-sec">
    <div class="sb-lbl">Navigation</div>
    <?php foreach($nav as [$key,$href,$label,$badge]): ?>
    <a href="<?= $href ?>" class="sb-a <?= $active===$key?'on':'' ?>">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><?= $icons[$key] ?></svg>
      <?= $label ?>
      <?php if($badge > 0): ?><span class="sb-badge"><?= $badge ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sb-sec" style="margin-top:auto">
    <a href="/admin/settings.php" class="sb-a <?= $active==='settings'?'on':'' ?>">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><?= $icons['settings'] ?></svg> Paramètres
    </a>
    <a href="/logout.php" class="sb-a red">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><?= $icons['logout'] ?></svg> Déconnexion
    </a>
  </div>
</nav>
<?php }

function pgTopbar(string $title): void { ?>
<div class="wrapper">
<div class="topbar">
  <button class="tb-tog" onclick="toggleSB()" aria-label="Menu">
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M2 4h14M2 9h14M2 14h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  </button>
  <div class="tb-title"><?= e($title) ?></div>
  <div class="tb-sep"></div>
  <a href="/admin/doc-edit.php" class="btn btn-white btn-sm">
    <svg width="11" height="11" viewBox="0 0 11 11" fill="none"><path d="M5.5 1v9M1 5.5h9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    Nouveau doc
  </a>
  <div class="tb-user">
    <div class="tb-av"><?= strtoupper(substr($_SESSION['uname']??'A',0,1)) ?></div>
    <span><?= e($_SESSION['uname']??'') ?></span>
  </div>
</div>
<div class="page">
<?php
    if(!empty($_SESSION['flash'])){
        $f=$_SESSION['flash']; unset($_SESSION['flash']);
        $cls=['ok'=>'flash-ok','err'=>'flash-err','inf'=>'flash-inf'][$f['t']]??'flash-inf';
        echo "<div class='flash $cls'><span>".e($f['m'])."</span><button onclick=\"this.parentElement.remove()\">×</button></div>";
    }
}

function pgEnd(): void { ?>
</div></div>
<div id="sb-ov" onclick="toggleSB()" style="display:none;position:fixed;inset:0;z-index:49;background:rgba(35,56,116,.35)"></div>
<script>
function toggleSB(){
  var s=document.getElementById('sidebar'),o=document.getElementById('sb-ov');
  s.classList.toggle('open'); o.style.display=s.classList.contains('open')?'block':'none';
}
</script>
</body></html>
<?php }
