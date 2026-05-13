<?php
require_once dirname(__DIR__) . '/includes/core.php';
require_once dirname(__DIR__) . '/includes/layout.php';
requireLogin();
$clients = dbAll("SELECT c.*,(SELECT COUNT(*) FROM doc_clients dc WHERE dc.client_id=c.id) docs,(SELECT COUNT(*) FROM share_links sl WHERE sl.client_id=c.id AND sl.is_active=1) links FROM clients c ORDER BY c.name");
pgHead('Clients'); pgSidebar('clients'); pgTopbar('Clients');
?>
<div class="gap" style="margin-bottom:18px"><div class="ml"></div><button class="btn btn-navy btn-sm" onclick="openM()">+ Nouveau client</button></div>
<div class="card">
  <div class="card-head"><div class="card-title"><?= count($clients) ?> client<?= count($clients)>1?'s':'' ?></div></div>
  <div class="tw"><table class="t">
    <thead><tr><th>Client</th><th>Email</th><th>Docs</th><th>Liens</th><th>Portail</th><th></th></tr></thead>
    <tbody>
      <?php foreach($clients as $c):
        $init=strtoupper(implode('',array_map(fn($w)=>substr($w,0,1),explode(' ',$c['name']))));
      ?>
      <tr>
        <td><div class="gap"><div style="width:30px;height:30px;border-radius:7px;background:<?=e($c['color'])?>;color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:11px;flex-shrink:0"><?=e(substr($init,0,2))?></div><div><div class="fw sm"><?=e($c['name'])?></div><?php if($c['company']): ?><div class="xs c2"><?=e($c['company'])?></div><?php endif; ?></div></div></td>
        <td class="c2 sm"><?=e($c['email'])?></td>
        <td><span class="badge b-view"><?=$c['docs']?></span></td>
        <td class="sm c2"><?=$c['links']?></td>
        <td><?php if($c['portal_active']): ?>
          <button onclick="copyPortal('<?=APP_URL?>/portal/<?=e($c['portal_token'])?>')" class="btn btn-ghost btn-xs">🔗 Copier lien</button>
        <?php else: ?>
          <button onclick="actPortal(<?=$c['id']?>)" class="btn btn-ghost btn-xs">Activer</button>
        <?php endif; ?></td>
        <td><div class="gap">
          <button onclick='editM(<?=json_encode($c)?>)' class="btn btn-ghost btn-xs">Modifier</button>
          <button onclick="delC(<?=$c['id']?>,'<?=e(addslashes($c['name']))?>')" class="btn btn-red btn-xs">×</button>
        </div></td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$clients): ?><tr><td colspan="6" class="empty"><span class="ico">👥</span>Aucun client.<br><button class="btn btn-navy btn-sm" onclick="openM()" style="margin-top:12px">+ Créer</button></td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>

<div id="cli-modal" class="overlay" style="display:none" onclick="if(this===event.target)this.style.display='none'">
  <div class="modal">
    <div class="modal-title" id="cli-modal-title">Nouveau client</div>
    <input type="hidden" id="cli-id">
    <div class="frow">
      <div class="fg"><label class="lbl">Nom *</label><input id="cli-name" class="inp" placeholder="Acme Corp"></div>
      <div class="fg"><label class="lbl">Société</label><input id="cli-co" class="inp" placeholder="Acme Inc."></div>
    </div>
    <div class="fg"><label class="lbl">Email *</label><input id="cli-email" type="email" class="inp" placeholder="contact@acme.fr"></div>
    <div class="fg"><label class="lbl">Couleur</label>
      <div class="gap" id="cli-colors">
        <?php foreach(['#233874','#10B981','#f59e0b','#ef4444','#6366f1','#ec4899','#0ea5e9'] as $col): ?>
        <div onclick="pickC('<?=$col?>')" data-c="<?=$col?>" style="width:24px;height:24px;border-radius:6px;background:<?=$col?>;cursor:pointer;border:2px solid transparent;transition:all .12s"></div>
        <?php endforeach; ?>
      </div>
      <input type="hidden" id="cli-color" value="#233874">
    </div>
    <div class="modal-foot">
      <button class="btn" onclick="document.getElementById('cli-modal').style.display='none'">Annuler</button>
      <button class="btn btn-navy" onclick="saveC()">Enregistrer</button>
    </div>
  </div>
</div>

<script>
const CSRF='<?= csrf() ?>';
function openM(){document.getElementById('cli-modal').style.display='flex';document.getElementById('cli-modal-title').textContent='Nouveau client';document.getElementById('cli-id').value='';['name','co','email'].forEach(f=>document.getElementById('cli-'+f).value='');pickC('#233874');}
function editM(c){document.getElementById('cli-modal').style.display='flex';document.getElementById('cli-modal-title').textContent='Modifier';document.getElementById('cli-id').value=c.id;document.getElementById('cli-name').value=c.name||'';document.getElementById('cli-co').value=c.company||'';document.getElementById('cli-email').value=c.email||'';pickC(c.color||'#233874');}
function pickC(col){document.getElementById('cli-color').value=col;document.querySelectorAll('#cli-colors [data-c]').forEach(el=>{const on=el.dataset.c===col;el.style.borderColor=on?'white':'transparent';el.style.transform=on?'scale(1.2)':'scale(1)';});}
function saveC(){const d={id:document.getElementById('cli-id').value||null,name:document.getElementById('cli-name').value,company:document.getElementById('cli-co').value,email:document.getElementById('cli-email').value,color:document.getElementById('cli-color').value,_csrf:CSRF};fetch('/api/client-save.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(d)}).then(r=>r.json()).then(d=>{if(d.ok)location.reload();else alert(d.error||'Erreur');});}
function delC(id,n){if(!confirm('Supprimer "'+n+'" ?'))return;fetch('/api/client-delete.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,_csrf:CSRF})}).then(r=>r.json()).then(d=>{if(d.ok)location.reload();});}
function copyPortal(url){navigator.clipboard?.writeText(url).then(()=>alert('Lien copié !\n'+url));}
function actPortal(id){fetch('/api/client-portal.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,_csrf:CSRF})}).then(r=>r.json()).then(d=>{if(d.ok)location.reload();});}
</script>
<?php pgEnd(); ?>
