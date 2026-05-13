<?php
require_once dirname(__DIR__) . '/includes/core.php';
require_once dirname(__DIR__) . '/includes/layout.php';
requireLogin();

$nd = dbGet("SELECT COUNT(*) n FROM docs")['n']??0;
$np = dbGet("SELECT COUNT(*) n FROM docs WHERE status='published'")['n']??0;
$nc = dbGet("SELECT COUNT(*) n FROM clients WHERE is_active=1")['n']??0;
$nl = dbGet("SELECT COUNT(*) n FROM share_links WHERE is_active=1")['n']??0;
$nv = dbGet("SELECT COALESCE(SUM(views),0) n FROM docs")['n']??0;
$recent = dbAll("SELECT d.*,c.name cat,c.color cc FROM docs d LEFT JOIN categories c ON c.id=d.category_id ORDER BY d.updated_at DESC LIMIT 8");

pgHead('Tableau de bord'); pgSidebar('dash'); pgTopbar('Tableau de bord');
?>

<div class="card-accent">
  <div class="gap" style="flex-wrap:wrap">
    <div style="flex:1">
      <div style="font-size:17px;font-weight:800;color:var(--navy);margin-bottom:4px">Bonjour, <?= e($_SESSION['uname']??'') ?> 👋</div>
      <div class="xs c2"><?= date('l d F Y') ?> · ProactiveGroup Documentation</div>
    </div>
    <a href="/admin/doc-edit.php" class="btn btn-navy btn-sm">+ Nouveau document</a>
  </div>
</div>

<div class="stats">
  <div class="stat"><span class="stat-ico">📄</span><div class="stat-val"><?=$nd?></div><div class="stat-lbl">Documents</div></div>
  <div class="stat"><span class="stat-ico">✅</span><div class="stat-val"><?=$np?></div><div class="stat-lbl">Publiés</div></div>
  <div class="stat"><span class="stat-ico">👥</span><div class="stat-val"><?=$nc?></div><div class="stat-lbl">Clients</div></div>
  <div class="stat"><span class="stat-ico">🔗</span><div class="stat-val"><?=$nl?></div><div class="stat-lbl">Liens actifs</div></div>
  <div class="stat"><span class="stat-ico">👁️</span><div class="stat-val"><?=number_format($nv)?></div><div class="stat-lbl">Vues</div></div>
</div>

<div class="card">
  <div class="card-head"><div class="card-title">Documents récents</div><a href="/admin/docs.php" class="btn btn-ghost btn-sm">Voir tout →</a></div>
  <div class="tw">
    <table class="t">
      <thead><tr><th>Titre</th><th>Catégorie</th><th>Statut</th><th>Modifié</th><th></th></tr></thead>
      <tbody>
        <?php foreach($recent as $d): ?>
        <tr>
          <td><a href="/admin/doc-edit.php?id=<?=$d['id']?>" style="color:var(--navy);font-weight:600;text-decoration:none"><?= e($d['title']) ?></a></td>
          <td><?php if($d['cat']): ?><span class="gap sm"><span class="dot" style="background:<?=e($d['cc'])?>"></span><?=e($d['cat'])?></span><?php else:?>—<?php endif;?></td>
          <td><span class="badge b-<?=e($d['status'])?>"><?=$d['status']==='published'?'Publié':'Brouillon'?></span></td>
          <td class="c2 sm"><?= date('d/m/Y', strtotime($d['updated_at'])) ?></td>
          <td><a href="/admin/doc-edit.php?id=<?=$d['id']?>" class="btn btn-ghost btn-xs">Éditer</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$recent): ?><tr><td colspan="5" class="empty">Aucun document. <a href="/admin/doc-edit.php">Créer →</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="text-align:center;margin-top:24px;padding-top:16px;border-top:1px solid var(--border)">
  <p class="xs c2" style="font-style:italic">"Parce que votre informatique doit être un atout, pas une contrainte." — <a href="https://proactivegroup.fr" target="_blank" style="color:var(--navy);font-weight:700">www.proactivegroup.fr</a></p>
</div>

<?php pgEnd(); ?>
