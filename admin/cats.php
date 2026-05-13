<?php
// admin/cats.php
require_once dirname(__DIR__) . '/includes/core.php';
require_once dirname(__DIR__) . '/includes/layout.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD']==='POST' && csrfOk()) {
    $a=$_POST['action']??'';
    if($a==='save'){$n=trim($_POST['name']??'');$col=preg_match('/^#[0-9a-f]{6}$/i',$_POST['color']??'')?$_POST['color']:'#233874';$o=(int)($_POST['sort_order']??0);if($n){if($_POST['id'])dbUpdate('categories',['name'=>$n,'color'=>$col,'sort_order'=>$o],['id'=>(int)$_POST['id']]);else dbInsert('categories',['name'=>$n,'color'=>$col,'sort_order'=>$o]);flash('ok','✅ Catégorie enregistrée.');}}
    elseif($a==='delete'){dbDelete('categories',['id'=>(int)($_POST['id']??0)]);flash('ok','🗑️ Supprimée.');}
    go('/admin/cats.php');
}
$cats=dbAll("SELECT c.*,(SELECT COUNT(*) FROM docs d WHERE d.category_id=c.id) nb FROM categories c ORDER BY c.sort_order,c.name");
pgHead('Catégories');pgSidebar('cats');pgTopbar('Catégories');
?>
<div class="gap" style="margin-bottom:18px"><div class="ml"></div><button class="btn btn-navy btn-sm" onclick="openM()">+ Nouvelle</button></div>
<div class="card"><div class="card-head"><div class="card-title"><?=count($cats)?> catégorie<?=count($cats)>1?'s':''?></div></div>
<div class="tw"><table class="t">
  <thead><tr><th>Catégorie</th><th>Couleur</th><th>Docs</th><th>Ordre</th><th></th></tr></thead>
  <tbody>
    <?php foreach($cats as $c): ?>
    <tr>
      <td><div class="gap"><span class="dot" style="background:<?=e($c['color'])?>;width:12px;height:12px"></span><span class="fw"><?=e($c['name'])?></span></div></td>
      <td><code style="font-size:11px;background:var(--bg);padding:2px 6px;border-radius:4px;color:var(--muted)"><?=e($c['color'])?></code></td>
      <td><span class="badge b-view"><?=$c['nb']?></span></td>
      <td class="c2 sm"><?=$c['sort_order']?></td>
      <td><div class="gap">
        <button onclick='editM(<?=json_encode($c)?>)' class="btn btn-ghost btn-xs">Modifier</button>
        <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$c['id']?>"><button type="submit" class="btn btn-red btn-xs">×</button></form>
      </div></td>
    </tr>
    <?php endforeach; ?>
    <?php if(!$cats): ?><tr><td colspan="5" class="empty"><span class="ico">🗂️</span>Aucune catégorie.<br><button class="btn btn-navy btn-sm" onclick="openM()" style="margin-top:12px">+ Créer</button></td></tr><?php endif; ?>
  </tbody>
</table></div></div>

<div id="cat-modal" class="overlay" style="display:none" onclick="if(this===event.target)this.style.display='none'">
  <div class="modal">
    <div class="modal-title" id="cat-modal-title">Nouvelle catégorie</div>
    <form method="POST"><?= csrfField() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="cat-id" value="">
      <div class="fg"><label class="lbl">Nom *</label><input type="text" name="name" id="cat-name" class="inp" required placeholder="Guides, Procédures..."></div>
      <div class="frow">
        <div class="fg"><label class="lbl">Couleur</label><input type="color" name="color" id="cat-color" class="inp" value="#233874" style="height:42px;padding:4px;cursor:pointer"></div>
        <div class="fg"><label class="lbl">Ordre</label><input type="number" name="sort_order" id="cat-order" class="inp" value="0" min="0"></div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn" onclick="document.getElementById('cat-modal').style.display='none'">Annuler</button>
        <button type="submit" class="btn btn-navy">Enregistrer</button>
      </div>
    </form>
  </div>
</div>
<script>
function openM(){document.getElementById('cat-modal').style.display='flex';document.getElementById('cat-modal-title').textContent='Nouvelle catégorie';document.getElementById('cat-id').value='';document.getElementById('cat-name').value='';document.getElementById('cat-color').value='#233874';document.getElementById('cat-order').value='0';}
function editM(c){document.getElementById('cat-modal').style.display='flex';document.getElementById('cat-modal-title').textContent='Modifier';document.getElementById('cat-id').value=c.id;document.getElementById('cat-name').value=c.name;document.getElementById('cat-color').value=c.color;document.getElementById('cat-order').value=c.sort_order;}
</script>
<?php pgEnd(); ?>
