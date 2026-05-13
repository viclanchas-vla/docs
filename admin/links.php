<?php
// admin/links.php
require_once dirname(__DIR__) . '/includes/core.php';
require_once dirname(__DIR__) . '/includes/layout.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD']==='POST' && csrfOk()) {
    dbUpdate('share_links',['is_active'=>0],['id'=>(int)($_POST['id']??0)]);
    flash('ok','🗑️ Lien révoqué.'); go('/admin/links.php');
}
$page=max(1,(int)($_GET['p']??1));$limit=20;$offset=($page-1)*$limit;
$total=dbGet("SELECT COUNT(*) n FROM share_links WHERE is_active=1")['n']??0;
$pages=max(1,ceil($total/$limit));
$links=dbAll("SELECT sl.*, d.title doc_title, c.name client_name FROM share_links sl LEFT JOIN docs d ON d.id=sl.doc_id LEFT JOIN clients c ON c.id=sl.client_id WHERE sl.is_active=1 ORDER BY sl.created_at DESC LIMIT $limit OFFSET $offset");
pgHead('Liens partagés');pgSidebar('links');pgTopbar('Liens partagés');
?>
<div class="card"><div class="card-head"><div class="card-title"><?=$total?> lien<?=$total>1?'s':''?> actif<?=$total>1?'s':''?></div></div>
<div class="tw"><table class="t">
  <thead><tr><th>Document</th><th>Client</th><th>Vues</th><th>Expiration</th><th>Créé le</th><th></th></tr></thead>
  <tbody>
    <?php foreach($links as $l): ?>
    <tr>
      <td>
        <div class="fw sm navy"><?=e($l['doc_title']??'—')?></div>
        <div onclick="copyUrl('<?=APP_URL?>/share/<?=e($l['token'])?>')"
          style="font-family:monospace;font-size:10px;color:var(--navy);cursor:pointer;margin-top:2px;opacity:.7"
          title="Copier">/share/<?=e(substr($l['token'],0,14))?>...</div>
      </td>
      <td class="c2 sm"><?=$l['client_name']?e($l['client_name']):'<span class="c3">—</span>'?></td>
      <td class="c2 sm"><?=$l['view_count']?></td>
      <td class="c2 sm"><?=$l['expires_at']?date('d/m/Y',strtotime($l['expires_at'])):'<span class="c3">∞</span>'?></td>
      <td class="c2 sm"><?=date('d/m/Y',strtotime($l['created_at']))?></td>
      <td><div class="gap">
        <button onclick="copyUrl('<?=APP_URL?>/share/<?=e($l['token'])?>')" class="btn btn-ghost btn-xs">📋</button>
        <form method="POST" style="display:inline" onsubmit="return confirm('Révoquer ?')"><?=csrfField()?><input type="hidden" name="id" value="<?=$l['id']?>"><button type="submit" class="btn btn-red btn-xs">Révoquer</button></form>
      </div></td>
    </tr>
    <?php endforeach; ?>
    <?php if(!$links): ?><tr><td colspan="6" class="empty"><span class="ico">🔗</span>Aucun lien actif.<br><div class="xs c2" style="margin-top:6px">Créez depuis l'éditeur de document</div></td></tr><?php endif; ?>
  </tbody>
</table></div></div>
<?php if($pages>1): ?><div class="pages"><?php if($page>1): ?><a href="?p=<?=$page-1?>" class="pg">‹</a><?php endif; ?><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a href="?p=<?=$i?>" class="pg <?=$i===$page?'on':''?>"><?=$i?></a><?php endfor; ?><?php if($page<$pages): ?><a href="?p=<?=$page+1?>" class="pg">›</a><?php endif; ?></div><?php endif; ?>
<script>function copyUrl(url){navigator.clipboard?.writeText(url).then(()=>{const t=document.createElement('div');t.style.cssText='position:fixed;bottom:22px;right:22px;background:var(--navy);color:white;border-radius:10px;padding:11px 20px;font-size:12.5px;font-weight:700;z-index:999;font-family:Montserrat,sans-serif';t.textContent='📋 Lien copié !';document.body.appendChild(t);setTimeout(()=>t.remove(),2500);});}</script>
<?php pgEnd(); ?>
