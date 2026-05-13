<?php
// doc-view.php — Rendu public d'un document (style MyES exact)
// Appelé par share/index.php et portal/index.php
// $doc = array du document, $blocks = array des blocs décodés

if (!isset($doc) || !isset($blocks)) die('Erreur');

// Incrémenter les vues
dbRun("UPDATE docs SET views=views+1 WHERE id=?", [$doc['id']]);

$title    = $doc['title'] ?? '';
$subtitle = $doc['subtitle'] ?? '';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> — ProactiveGroup</title>
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
/* ── Charte ProactiveGroup — identique à MyES-LecteursReseaux.html ── */
:root {
  --blue-nuit: #233874;
  --gris-bleute: #505A73;
  --gris-clair: #8F98B3;
  --blanc: #ffffff;
  --bg-light: #f4f6f9;
  --succes: #10B981;
  --danger: #ef4444;
  --amber: #f59e0b;
  --info: #0ea5e9;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Montserrat', sans-serif;
  background-color: var(--bg-light);
  color: var(--gris-bleute);
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}
.container {
  max-width: 820px;
  margin: 40px auto;
  background: var(--blanc);
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(35,56,116,0.08);
  overflow: hidden;
}
/* Header — identique */
.header {
  background-color: var(--blanc);
  padding: 30px 40px;
  border-bottom: 3px solid var(--blue-nuit);
  text-align: center;
}
.header img.logo { max-width: 220px; margin-bottom: 18px; display: block; margin-left: auto; margin-right: auto; }
.header h1 { color: var(--blue-nuit); font-size: 1.7rem; font-weight: 700; }
.header .subtitle { color: var(--gris-clair); font-size: .95rem; font-weight: 500; margin-top: 6px; }

/* Contenu */
.content { padding: 40px; }
.content > * + * { margin-top: 22px; }

/* Bloc texte */
.blk-text p { font-size: 1rem; color: var(--gris-bleute); line-height: 1.75; }
.blk-text strong { color: var(--blue-nuit); }
.blk-text a { color: var(--blue-nuit); font-weight: 600; }

/* Bloc titre h2 */
.blk-h2 h2 {
  color: var(--blue-nuit);
  font-size: 1.35rem; font-weight: 700;
  display: flex; align-items: center; gap: 10px;
  margin: 0;
}

/* Bloc titre h3 */
.blk-h3 h3 {
  color: var(--blue-nuit);
  font-size: 1.1rem; font-weight: 700;
  display: flex; align-items: center; gap: 8px;
  margin: 0;
}

/* Bloc copy-box — identique à usecure */
.copy-box {
  background: #f8fafc;
  border: 2px dashed #cbd5e1;
  border-radius: 8px;
  padding: 12px 18px;
  display: flex; justify-content: space-between; align-items: center;
  font-family: 'Consolas','Monaco',monospace;
  font-size: .95rem; color: #334155;
  transition: all .2s;
  gap: 12px;
}
.copy-box:hover { border-color: var(--blue-nuit); background: var(--blanc); }
.copy-box .val { flex: 1; word-break: break-all; }
.btn-copy {
  background: var(--blue-nuit); color: white; border: none;
  padding: 6px 14px; border-radius: 6px; cursor: pointer;
  font-size: .82rem; font-family: 'Montserrat',sans-serif; font-weight: 600;
  display: flex; align-items: center; gap: 6px; min-width: 90px;
  justify-content: center; transition: all .2s; flex-shrink: 0;
}
.btn-copy:hover { background: var(--gris-bleute); transform: translateY(-1px); }
.btn-copy.copied { background: var(--succes); }

/* Bloc alerte — style usecure */
.alert-warn  { background:#fff8e1; color:#856404; border-left:5px solid #ffc107; padding:14px 18px; border-radius:0 8px 8px 0; display:flex; gap:13px; align-items:flex-start; font-size:.95rem; }
.alert-info  { background:#e0f2fe; color:#0369a1; border-left:5px solid var(--info);  padding:14px 18px; border-radius:0 8px 8px 0; display:flex; gap:13px; align-items:flex-start; font-size:.95rem; }
.alert-ok    { background:rgba(16,185,129,.08); color:#065f46; border-left:5px solid var(--succes); padding:14px 18px; border-radius:0 8px 8px 0; display:flex; gap:13px; align-items:flex-start; font-size:.95rem; }
.alert-error { background:rgba(239,68,68,.07); color:#b91c1c; border-left:5px solid var(--danger); padding:14px 18px; border-radius:0 8px 8px 0; display:flex; gap:13px; align-items:flex-start; font-size:.95rem; }

/* Bloc rights-box — identique à MyES */
.rights-box {
  background: rgba(143,152,179,.1);
  border-left: 4px solid var(--gris-clair);
  padding: 18px 20px;
  border-radius: 0 8px 8px 0;
  font-size: .95rem;
}
.rights-box h3 { color: var(--blue-nuit); margin-bottom: 8px; display:flex; align-items:center; gap:8px; font-size:1rem; }

/* Bloc it-support-box navy — identique à MyES */
.navy-box {
  background: var(--blue-nuit);
  color: var(--blanc);
  padding: 28px 30px;
  border-radius: 12px;
}
.navy-box h2 { color: var(--blanc); margin-bottom: 12px; border-bottom:1px solid rgba(255,255,255,.2); padding-bottom:10px; font-size:1.3rem; display:flex; align-items:center; gap:10px; }
.navy-box p  { color: var(--blanc); margin-bottom: 18px; font-size:.95rem; }
.navy-box .tools { display: flex; flex-direction: column; gap: 12px; }
.navy-box .tool  { background: rgba(255,255,255,.1); padding: 14px; border-radius: 8px; display: flex; align-items: center; gap: 14px; }
.navy-box .tool .ico-wrap { background: var(--blanc); color: var(--blue-nuit); padding: 9px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.navy-box .tool .txt strong { display: block; color: var(--blanc); font-size:1rem; }
.navy-box .tool .txt span   { color: var(--gris-clair); font-size:.87rem; }

/* Bloc folder-grid — identique à MyES */
.folder-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px,1fr));
  gap: 13px;
}
.folder-item {
  background: var(--bg-light);
  padding: 13px 18px;
  border-radius: 8px;
  display: flex; align-items: center; gap: 11px;
  font-weight: 600; color: var(--blue-nuit);
  transition: transform .2s;
  border: 1px solid rgba(143,152,179,.2);
  font-size: .92rem;
}
.folder-item:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(35,56,116,.1); }

/* Bloc step-card — identique à usecure */
.step-card {
  background: var(--blanc);
  border-radius: 14px;
  padding: 26px 28px;
  box-shadow: 0 5px 15px rgba(0,0,0,.06);
  border: 1px solid rgba(0,0,0,.03);
  position: relative;
  overflow: visible;
}
.step-number {
  position: absolute; top: -18px; left: 18px;
  background: var(--blue-nuit); color: white;
  width: 42px; height: 42px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 1.3rem; border-radius: 10px;
  box-shadow: 0 4px 10px rgba(35,56,116,.4);
}
.step-title { color: var(--blue-nuit); font-size: 1.4rem; font-weight: 700; margin: 6px 0 4px; }
.step-desc  { color: var(--gris-bleute); font-size:1rem; margin-bottom: 16px; }

/* Bloc image */
.blk-img img { max-width: 100%; border-radius: 10px; box-shadow: 0 5px 20px rgba(35,56,116,.1); display: block; margin: 0 auto; }
.blk-img .img-caption { text-align: center; color: var(--gris-clair); font-size: .85rem; margin-top: 7px; font-style: italic; }

/* Bloc liste */
.blk-list ul { color: var(--gris-bleute); padding-left: 22px; }
.blk-list li { margin-bottom: 6px; line-height: 1.7; }

/* Bloc separator */
.blk-sep hr { border: none; border-top: 2px solid rgba(143,152,179,.25); }

/* Bloc border-left card */
.border-card {
  background: var(--blanc);
  padding: 18px 22px;
  border-radius: 0 10px 10px 0;
  border-left: 4px solid var(--blue-nuit);
  box-shadow: 0 3px 12px rgba(35,56,116,.07);
}
.border-card h3 { color: var(--blue-nuit); font-size: .85rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }

/* Pied de page — identique */
.footer {
  text-align: center;
  padding: 24px 40px;
  background: var(--bg-light);
  border-top: 1px solid rgba(143,152,179,.2);
}
.footer p { color: var(--gris-bleute); font-weight: 500; font-style: italic; font-size: .9rem; }
.footer a  { color: var(--blue-nuit); text-decoration: none; font-weight: 600; margin-top: 8px; display: inline-block; font-size: .85rem; }
.footer a:hover { text-decoration: underline; }

/* Dynamic value */
.dyn-val { border: 2px solid var(--danger); color: var(--danger); background: #fff1f1; font-weight: bold; padding: 2px 6px; border-radius: 4px; font-family: monospace; }

@media(max-width:600px){
  .container { margin:0; border-radius:0; }
  .content { padding: 24px; }
  .folder-grid { grid-template-columns: 1fr; }
  .copy-box { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>
<div class="container">

  <!-- Header identique à MyES -->
  <div class="header">
    <img src="https://proactivegroup.fr/wp-content/uploads/2025/06/Original.svg"
      class="logo" alt="ProactiveGroup"
      onerror="this.style.display='none'">
    <h1><?= e($title) ?></h1>
    <?php if($subtitle): ?>
    <p class="subtitle"><?= e($subtitle) ?></p>
    <?php endif; ?>
  </div>

  <!-- Blocs de contenu -->
  <div class="content">
  <?php foreach($blocks as $blk):
    $type = $blk['type'] ?? 'text';
    $data = $blk['data'] ?? [];
  ?>

  <?php if($type === 'text'): ?>
    <div class="blk-text"><p><?= nl2br(e($data['content'] ?? '')) ?></p></div>

  <?php elseif($type === 'h2'): ?>
    <div class="blk-h2"><h2>
      <?php if(!empty($data['icon'])): ?><i data-lucide="<?= e($data['icon']) ?>" style="width:22px;height:22px;flex-shrink:0"></i><?php endif; ?>
      <?= e($data['text'] ?? '') ?>
    </h2></div>

  <?php elseif($type === 'h3'): ?>
    <div class="blk-h3"><h3>
      <?php if(!empty($data['icon'])): ?><i data-lucide="<?= e($data['icon']) ?>" style="width:18px;height:18px;flex-shrink:0"></i><?php endif; ?>
      <?= e($data['text'] ?? '') ?>
    </h3></div>

  <?php elseif($type === 'copybox'): ?>
    <div class="copy-box">
      <span class="val"><?= e($data['value'] ?? '') ?></span>
      <button class="btn-copy" onclick="copyText(this,'<?= htmlspecialchars(addslashes($data['value']??'')) ?>')">
        <i data-lucide="copy" style="width:14px;height:14px"></i> Copier
      </button>
    </div>

  <?php elseif($type === 'alert'): ?>
    <?php $cls = 'alert-'.($data['style']??'info'); ?>
    <div class="<?= e($cls) ?>">
      <i data-lucide="<?= ['warn'=>'alert-triangle','info'=>'info','ok'=>'check-circle','error'=>'x-circle'][$data['style']??'info'] ?>" style="width:20px;height:20px;flex-shrink:0;margin-top:2px"></i>
      <div><?= nl2br(e($data['content'] ?? '')) ?></div>
    </div>

  <?php elseif($type === 'rights-box'): ?>
    <div class="rights-box">
      <?php if(!empty($data['title'])): ?>
      <h3><i data-lucide="shield-check" style="width:18px;height:18px"></i><?= e($data['title']) ?></h3>
      <?php endif; ?>
      <p><?= nl2br(e($data['content'] ?? '')) ?></p>
    </div>

  <?php elseif($type === 'navy-box'): ?>
    <div class="navy-box">
      <?php if(!empty($data['title'])): ?>
      <h2>
        <?php if(!empty($data['icon'])): ?><i data-lucide="<?= e($data['icon']) ?>" style="width:22px;height:22px"></i><?php endif; ?>
        <?= e($data['title']) ?>
      </h2>
      <?php endif; ?>
      <?php if(!empty($data['intro'])): ?><p><?= nl2br(e($data['intro'])) ?></p><?php endif; ?>
      <?php if(!empty($data['tools'])): ?>
      <div class="tools">
        <?php foreach($data['tools'] as $tool): ?>
        <div class="tool">
          <div class="ico-wrap"><i data-lucide="<?= e($tool['icon']??'info') ?>" style="width:20px;height:20px"></i></div>
          <div class="txt">
            <strong><?= e($tool['title']??'') ?></strong>
            <span><?= e($tool['desc']??'') ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  <?php elseif($type === 'folder-grid'): ?>
    <div class="folder-grid">
      <?php foreach(($data['items']??[]) as $item): ?>
      <div class="folder-item">
        <i data-lucide="folder" style="width:20px;height:20px;color:#FFB02E;fill:#FFD479;flex-shrink:0"></i>
        <?= e($item) ?>
      </div>
      <?php endforeach; ?>
    </div>

  <?php elseif($type === 'step-card'): ?>
    <div class="step-card" style="margin-top:30px">
      <?php if(!empty($data['number'])): ?><div class="step-number"><?= e($data['number']) ?></div><?php endif; ?>
      <?php if(!empty($data['title'])): ?><h2 class="step-title" style="margin-top:<?= !empty($data['number'])?'14px':'0' ?>"><?= e($data['title']) ?></h2><?php endif; ?>
      <?php if(!empty($data['desc'])): ?><p class="step-desc"><?= e($data['desc']) ?></p><?php endif; ?>
      <?php if(!empty($data['content'])): ?><div><?= nl2br(e($data['content'])) ?></div><?php endif; ?>
      <?php if(!empty($data['copybox'])): ?>
      <div class="copy-box" style="margin-top:12px">
        <span class="val"><?= e($data['copybox']) ?></span>
        <button class="btn-copy" onclick="copyText(this,'<?= htmlspecialchars(addslashes($data['copybox'])) ?>')">
          <i data-lucide="copy" style="width:14px;height:14px"></i> Copier
        </button>
      </div>
      <?php endif; ?>
    </div>

  <?php elseif($type === 'border-card'): ?>
    <div class="border-card">
      <?php if(!empty($data['title'])): ?><h3><?= e($data['title']) ?></h3><?php endif; ?>
      <p><?= nl2br(e($data['content'] ?? '')) ?></p>
    </div>

  <?php elseif($type === 'image'): ?>
    <div class="blk-img">
      <img src="<?= e($data['url'] ?? '') ?>" alt="<?= e($data['alt'] ?? '') ?>">
      <?php if(!empty($data['caption'])): ?><p class="img-caption"><?= e($data['caption']) ?></p><?php endif; ?>
    </div>

  <?php elseif($type === 'list'): ?>
    <div class="blk-list">
      <?php if(!empty($data['title'])): ?><p style="font-weight:700;color:var(--blue-nuit);margin-bottom:8px"><?= e($data['title']) ?></p><?php endif; ?>
      <ul><?php foreach(($data['items']??[]) as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul>
    </div>

  <?php elseif($type === 'separator'): ?>
    <div class="blk-sep"><hr></div>

  <?php endif; ?>
  <?php endforeach; ?>
  </div>

  <!-- Footer identique -->
  <div class="footer">
    <p>"Parce que votre informatique doit être un atout, pas une contrainte."</p>
    <a href="https://proactivegroup.fr/" target="_blank">www.proactivegroup.fr</a>
  </div>
</div>

<script>
function copyText(btn, text) {
  navigator.clipboard?.writeText(text).then(() => {
    btn.innerHTML = '<i data-lucide="check" style="width:14px;height:14px"></i> Copié !';
    btn.classList.add('copied');
    lucide.createIcons();
    setTimeout(() => {
      btn.innerHTML = '<i data-lucide="copy" style="width:14px;height:14px"></i> Copier';
      btn.classList.remove('copied');
      lucide.createIcons();
    }, 2500);
  });
}
lucide.createIcons();
</script>
</body>
</html>
