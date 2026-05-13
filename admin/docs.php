<?php
require_once dirname(__DIR__) . '/includes/core.php';
require_once dirname(__DIR__) . '/includes/layout.php';
requireLogin();

$page  = max(1,(int)($_GET['p']??1)); $limit=20; $offset=($page-1)*$limit;
$q     = trim($_GET['q']??'');
$where = $q ? "WHERE (d.title LIKE ? OR d.subtitle LIKE ?)" : '';
$params= $q ? ["%$q%","%$q%"] : [];
$total = dbGet("SELECT COUNT(*) n FROM docs d $where", $params)['n'] ?? 0;
$pages = max(1,ceil($total/$limit));
$docs  = dbAll("SELECT d.*, c.name cat, c.color cat_color,
    (SELECT COUNT(*) FROM doc_clients dc WHERE dc.doc_id=d.id) shared
    FROM docs d LEFT JOIN categories c ON c.id=d.category_id
    $where ORDER BY d.updated_at DESC LIMIT $limit OFFSET $offset", $params);

pgHead('Documents'); pgSidebar('docs'); pgTopbar('Documents');
?>

<div class="gap" style="margin-bottom:18px;flex-wrap:wrap">
  <form method="GET" class="gap" style="flex:1">
    <input type="text" name="q" class="inp" style="max-width:300px" placeholder="🔍 Rechercher..." value="<?= e($q) ?>">
    <button type="submit" class="btn btn-sm">Filtrer</button>
    <?php if($q): ?><a href="/admin/docs.php" class="btn btn-ghost btn-sm">✕</a><?php endif; ?>
  </form>
  <a href="/admin/doc-edit.php" class="btn btn-navy btn-sm">+ Nouveau document</a>
</div>

<div class="card">
  <div class="card-head">
    <div class="card-title"><?= $total ?> document<?= $total>1?'s':'' ?></div>
    <?php if($pages>1): ?><span class="xs c2">Page <?= $page ?>/<?= $pages ?></span><?php endif; ?>
  </div>
  <div class="tw">
    <table class="t">
      <thead><tr><th>Titre</th><th>Catégorie</th><th>Clients</th><th>Statut</th><th>Modifié</th><th></th></tr></thead>
      <tbody>
        <?php foreach($docs as $d): ?>
        <tr>
          <td>
            <a href="/admin/doc-edit.php?id=<?= $d['id'] ?>" style="color:var(--navy);font-weight:600;text-decoration:none"><?= e($d['title']) ?></a>
            <?php if($d['subtitle']): ?><div class="xs c2" style="margin-top:2px"><?= e($d['subtitle']) ?></div><?php endif; ?>
          </td>
          <td><?php if($d['cat']): ?>
            <span class="gap sm"><span class="dot" style="background:<?= e($d['cat_color']) ?>"></span><?= e($d['cat']) ?></span>
          <?php else: ?><span class="c3">—</span><?php endif; ?></td>
          <td><?= $d['shared']>0 ? '<span class="badge b-view">'.$d['shared'].' client'.($d['shared']>1?'s':'').'</span>' : '<span class="c3">—</span>' ?></td>
          <td><span class="badge b-<?= e($d['status']) ?>"><?= $d['status']==='published'?'Publié':'Brouillon' ?></span></td>
          <td class="c2 sm"><?= date('d/m/Y', strtotime($d['updated_at'])) ?></td>
          <td>
            <div class="gap">
              <a href="/admin/doc-edit.php?id=<?= $d['id'] ?>" class="btn btn-ghost btn-xs">Éditer</a>
              <?php if($d['status']==='published'): ?>
              <a href="/share/<?= e($d['uuid']) ?>" target="_blank" class="btn btn-ghost btn-xs">Voir</a>
              <?php endif; ?>
              <button onclick="del(<?=$d['id']?>,'<?= e(addslashes($d['title'])) ?>')" class="btn btn-red btn-xs">×</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$docs): ?><tr><td colspan="6" class="empty"><span class="ico">📄</span>Aucun document.<br><a href="/admin/doc-edit.php" class="btn btn-navy btn-sm" style="margin-top:12px">Créer →</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if($pages>1): ?><div class="pages">
  <?php if($page>1): ?><a href="?p=<?=$page-1?>&q=<?=urlencode($q)?>" class="pg">‹</a><?php endif; ?>
  <?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a href="?p=<?=$i?>&q=<?=urlencode($q)?>" class="pg <?=$i===$page?'on':''?>"><?=$i?></a><?php endfor; ?>
  <?php if($page<$pages): ?><a href="?p=<?=$page+1?>&q=<?=urlencode($q)?>" class="pg">›</a><?php endif; ?>
</div><?php endif; ?>

<script>
function del(id,title){
  if(!confirm('Supprimer "'+title+'" ?'))return;
  fetch('/api/doc-delete.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,_csrf:'<?= csrf() ?>'})})
  .then(r=>r.json()).then(d=>{if(d.ok)location.reload();else alert(d.error);});
}
</script>
<?php pgEnd(); ?>
