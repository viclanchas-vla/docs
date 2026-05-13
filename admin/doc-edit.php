<?php
require_once dirname(__DIR__) . '/includes/core.php';
require_once dirname(__DIR__) . '/includes/layout.php';
requireLogin();

$id  = (int)($_GET['id'] ?? 0);
$doc = $id ? dbGet("SELECT * FROM docs WHERE id=?", [$id]) : null;
if ($id && !$doc) { flash('err','Document introuvable.'); go('/admin/docs.php'); }

// POST = sauvegarder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) { flash('err','Token invalide.'); go("/admin/doc-edit.php".($id?"?id=$id":'')); }
    $title    = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $catId    = !empty($_POST['cat_id']) ? (int)$_POST['cat_id'] : null;
    $status   = in_array($_POST['status']??'',['draft','published']) ? $_POST['status'] : 'draft';
    $blocks   = $_POST['blocks'] ?? '[]';
    // Valider JSON
    $decoded = json_decode($blocks, true);
    if (!is_array($decoded)) $blocks = '[]';
    if (!$title) { flash('err','Le titre est requis.'); go("/admin/doc-edit.php".($id?"?id=$id":'')); }
    $data = ['title'=>$title,'subtitle'=>$subtitle,'category_id'=>$catId,'status'=>$status,'blocks'=>$blocks];
    if ($id) {
        dbUpdate('docs', $data, ['id'=>$id]);
        flash('ok','✅ Document sauvegardé.');
    } else {
        $data['uuid']       = uid4();
        $data['created_by'] = (int)$_SESSION['uid'];
        $id = dbInsert('docs', $data);
        flash('ok','✅ Document créé.');
    }
    go("/admin/doc-edit.php?id=$id");
}

$categories = dbAll("SELECT * FROM categories ORDER BY sort_order,name");
$clients    = dbAll("SELECT id,name,color FROM clients WHERE is_active=1 ORDER BY name");
$docClients = $id ? dbAll("SELECT c.id,c.name,c.color,dc.permission FROM clients c INNER JOIN doc_clients dc ON dc.client_id=c.id WHERE dc.doc_id=?",[$id]) : [];
$shareLinks = $id ? dbAll("SELECT sl.*,c.name cn FROM share_links sl LEFT JOIN clients c ON c.id=sl.client_id WHERE sl.doc_id=? AND sl.is_active=1 ORDER BY sl.created_at DESC",[$id]) : [];

$blocksJson = $doc['blocks'] ?? '[]';
$pageTitle  = $id ? 'Éditer : '.($doc['title']??'') : 'Nouveau document';

pgHead($pageTitle); pgSidebar('docs'); pgTopbar($pageTitle);
?>

<style>
/* ── Éditeur de blocs ── */
.blk-list-edit { display:flex; flex-direction:column; gap:8px; min-height:60px; }
.blk-item {
  background:var(--white); border:1px solid var(--border);
  border-radius:var(--r); overflow:hidden;
  transition:box-shadow .15s;
}
.blk-item:hover { box-shadow:var(--shadow); }
.blk-header {
  display:flex; align-items:center; gap:10px;
  padding:10px 14px; background:var(--bg);
  border-bottom:1px solid var(--border);
  cursor:pointer; user-select:none;
}
.blk-header .blk-icon { font-size:16px; flex-shrink:0; }
.blk-header .blk-label { flex:1; font-size:12.5px; font-weight:600; color:var(--navy); }
.blk-header .blk-preview { font-size:11px; color:var(--muted); flex:2; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.blk-actions { display:flex; gap:4px; }
.blk-body { padding:14px; display:none; }
.blk-body.open { display:block; }
.blk-row { display:flex; gap:8px; margin-bottom:10px; align-items:flex-start; }
.blk-row label { font-size:10px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:3px; display:block; }
.blk-row .fg { flex:1; }
.add-blk-bar {
  display:flex; gap:6px; flex-wrap:wrap; padding:12px;
  background:var(--bg); border-radius:var(--r);
  border:2px dashed var(--border2); margin-top:8px;
}
.add-blk-btn {
  display:inline-flex; align-items:center; gap:5px;
  padding:5px 11px; border-radius:20px;
  background:var(--white); border:1px solid var(--border2);
  font-size:11px; font-weight:600; color:var(--slate);
  cursor:pointer; transition:all .13s; font-family:'Montserrat',sans-serif;
}
.add-blk-btn:hover { background:var(--navy); color:white; border-color:var(--navy); }
.sortable-ghost { opacity:.4; background:rgba(35,56,116,.05); }
</style>

<form method="POST" id="main-form">
<?= csrfField() ?>
<input type="hidden" name="blocks" id="blocks-input" value="<?= e($blocksJson) ?>">

<!-- Actions -->
<div class="gap" style="margin-bottom:16px;flex-wrap:wrap">
  <a href="/admin/docs.php" class="btn btn-ghost btn-sm">← Retour</a>
  <?php if($id): ?>
  <a href="/share/<?= e($doc['uuid']??'') ?>" target="_blank" class="btn btn-ghost btn-sm">👁 Aperçu public</a>
  <?php endif; ?>
  <div class="ml gap">
    <select name="status" class="sel" style="width:140px">
      <option value="draft"     <?= ($doc['status']??'draft')==='draft'    ?'selected':'' ?>>✏️ Brouillon</option>
      <option value="published" <?= ($doc['status']??'')==='published'      ?'selected':'' ?>>✅ Publié</option>
    </select>
    <button type="submit" class="btn btn-navy">💾 Sauvegarder</button>
    <?php if($id): ?>
    <button type="button" onclick="delDoc()" class="btn btn-red btn-sm">Supprimer</button>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 250px;gap:16px;align-items:start">
<div>

  <!-- Titre & sous-titre -->
  <div class="fg">
    <label class="lbl">Titre du document</label>
    <input type="text" name="title" class="inp" style="font-size:17px;font-weight:700;color:var(--navy)"
      placeholder="Ex : Guide d'accès au réseau..." value="<?= e($doc['title']??'') ?>" required>
  </div>
  <div class="fg">
    <label class="lbl">Sous-titre (affiché sous le titre dans le header)</label>
    <input type="text" name="subtitle" class="inp" placeholder="Ex : Guide pour les collaborateurs MyES"
      value="<?= e($doc['subtitle']??'') ?>">
  </div>

  <!-- Éditeur de blocs -->
  <div style="margin-top:4px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
      <label class="lbl" style="margin:0">Contenu — Blocs</label>
      <span class="xs c2">Glissez pour réordonner</span>
    </div>
    <div class="blk-list-edit" id="blk-list"></div>

    <!-- Barre d'ajout de blocs -->
    <div class="add-blk-bar">
      <span style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;align-self:center">+ Ajouter :</span>
      <button type="button" class="add-blk-btn" onclick="addBlk('text')">📝 Texte</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('h2')">H2 Titre</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('h3')">H3 Sous-titre</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('copybox')">📋 Copier-coller</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('alert')">⚠️ Alerte</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('rights-box')">🛡️ Droits</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('navy-box')">📦 Bloc navy</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('folder-grid')">📁 Dossiers</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('step-card')">🔢 Étape</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('border-card')">📌 Info card</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('list')">• Liste</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('image')">🖼️ Image</button>
      <button type="button" class="add-blk-btn" onclick="addBlk('separator')">─ Séparateur</button>
    </div>
  </div>

</div>

<!-- Sidebar -->
<div style="display:flex;flex-direction:column;gap:12px">

  <div class="card">
    <div class="card-head"><div class="card-title">Catégorie</div></div>
    <div class="card-body">
      <select name="cat_id" class="sel">
        <option value="">— Aucune —</option>
        <?php foreach($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= ($doc['category_id']??null)==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <?php if($id): ?>
  <div class="card">
    <div class="card-head">
      <div class="card-title">Clients</div>
      <button type="button" class="btn btn-ghost btn-xs" onclick="openModal('sh-modal')">+ Partager</button>
    </div>
    <div style="padding:6px">
      <?php if($docClients): foreach($docClients as $c): ?>
      <div class="gap" style="padding:7px 10px;font-size:12px;border-radius:6px">
        <span class="dot" style="background:<?= e($c['color']) ?>"></span>
        <span style="flex:1;font-weight:500"><?= e($c['name']) ?></span>
        <span class="badge b-view"><?= e($c['permission']) ?></span>
        <button type="button" onclick="revokeClient(<?=$id?>,<?=$c['id']?>)"
          style="background:none;border:none;color:var(--red);cursor:pointer;font-size:16px;line-height:1">×</button>
      </div>
      <?php endforeach; else: ?>
      <p class="xs c2" style="padding:10px;text-align:center">Aucun client</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="card-title">Liens partagés</div>
      <button type="button" class="btn btn-ghost btn-xs" onclick="openModal('lk-modal')">+ Créer</button>
    </div>
    <div style="padding:6px">
      <?php if($shareLinks): foreach($shareLinks as $l): ?>
      <div style="background:var(--bg);border-radius:6px;padding:8px;margin-bottom:5px;font-size:11.5px">
        <div class="gap">
          <span class="badge b-view">vue</span>
          <?php if($l['cn']): ?><span class="c2"><?= e($l['cn']) ?></span><?php endif; ?>
          <button type="button" onclick="revokeLink(<?=$l['id']?>)"
            style="margin-left:auto;background:none;border:none;color:var(--red);cursor:pointer;font-size:15px;line-height:1">×</button>
        </div>
        <div onclick="copyUrl('<?= APP_URL ?>/share/<?= e($l['token']) ?>')"
          style="font-family:monospace;font-size:10px;color:var(--navy);margin-top:3px;cursor:pointer;opacity:.8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
          title="Copier">/share/<?= e(substr($l['token'],0,14)) ?>...</div>
      </div>
      <?php endforeach; else: ?>
      <p class="xs c2" style="padding:10px;text-align:center">Aucun lien</p>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</div>
</div>
</form>

<?php if($id): ?>
<!-- Modal partage client -->
<div id="sh-modal" class="overlay" style="display:none" onclick="if(this===event.target)this.style.display='none'">
  <div class="modal">
    <div class="modal-title">Partager avec un client</div>
    <div class="fg"><label class="lbl">Client</label>
      <select id="sh-cli" class="sel"><option value="">— Sélectionner —</option>
        <?php foreach($clients as $c): ?><option value="<?=$c['id']?>"><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="modal-foot">
      <button class="btn" onclick="this.closest('.overlay').style.display='none'">Annuler</button>
      <button class="btn btn-navy" onclick="doShare()">Partager</button>
    </div>
  </div>
</div>
<!-- Modal lien -->
<div id="lk-modal" class="overlay" style="display:none" onclick="if(this===event.target)this.style.display='none'">
  <div class="modal">
    <div class="modal-title">Créer un lien partagé</div>
    <div class="fg"><label class="lbl">Client (optionnel)</label>
      <select id="lk-cli" class="sel"><option value="">— Aucun —</option>
        <?php foreach($clients as $c): ?><option value="<?=$c['id']?>"><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="fg"><label class="lbl">Expiration (optionnel)</label><input type="date" id="lk-exp" class="inp"></div>
    <div class="modal-foot">
      <button class="btn" onclick="this.closest('.overlay').style.display='none'">Annuler</button>
      <button class="btn btn-navy" onclick="doLink()">Créer</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- SortableJS pour glisser-déposer les blocs -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const DOC_ID = <?= $id ?: 'null' ?>;
const CSRF   = '<?= csrf() ?>';
const APPURL = '<?= APP_URL ?>';

// ── Définitions des blocs ─────────────────────────────────
const BLK_DEFS = {
  text:       { icon:'📝', label:'Texte',          fields:[{k:'content', l:'Contenu', type:'textarea'}] },
  h2:         { icon:'H2', label:'Titre H2',        fields:[{k:'text',l:'Texte',type:'text'},{k:'icon',l:'Icône Lucide (ex: folder-tree)',type:'text',ph:'folder-tree'}] },
  h3:         { icon:'H3', label:'Sous-titre H3',   fields:[{k:'text',l:'Texte',type:'text'},{k:'icon',l:'Icône Lucide',type:'text',ph:'info'}] },
  copybox:    { icon:'📋', label:'Boîte Copier',    fields:[{k:'value',l:'Valeur à copier',type:'text',ph:'P:\\\\Partages'}] },
  alert:      { icon:'⚠️', label:'Alerte',           fields:[{k:'content',l:'Texte',type:'textarea'},{k:'style',l:'Style',type:'select',opts:['info','warn','ok','error']}] },
  'rights-box':{ icon:'🛡️',label:'Droits d\'accès', fields:[{k:'title',l:'Titre',type:'text'},{k:'content',l:'Contenu',type:'textarea'}] },
  'navy-box': { icon:'📦', label:'Bloc navy',        fields:[{k:'title',l:'Titre',type:'text'},{k:'icon',l:'Icône',type:'text',ph:'life-buoy'},{k:'intro',l:'Introduction',type:'textarea'},{k:'tools_raw',l:'Outils (JSON: [{icon,title,desc}])',type:'textarea'}] },
  'folder-grid':{ icon:'📁',label:'Grille dossiers',fields:[{k:'items_raw',l:'Dossiers (un par ligne)',type:'textarea'}] },
  'step-card':{ icon:'🔢', label:'Carte étape',      fields:[{k:'number',l:'Numéro',type:'text',ph:'1'},{k:'title',l:'Titre',type:'text'},{k:'desc',l:'Description courte',type:'text'},{k:'content',l:'Contenu',type:'textarea'},{k:'copybox',l:'Valeur à copier (optionnel)',type:'text'}] },
  'border-card':{ icon:'📌',label:'Info card',       fields:[{k:'title',l:'Titre (uppercase)',type:'text'},{k:'content',l:'Contenu',type:'textarea'}] },
  list:       { icon:'•',  label:'Liste',            fields:[{k:'title',l:'Titre (optionnel)',type:'text'},{k:'items_raw',l:'Items (un par ligne)',type:'textarea'}] },
  image:      { icon:'🖼️', label:'Image',            fields:[{k:'url',l:'URL image',type:'text'},{k:'alt',l:'Texte alternatif',type:'text'},{k:'caption',l:'Légende (optionnel)',type:'text'}] },
  separator:  { icon:'─',  label:'Séparateur',       fields:[] },
};

// État
let blocks = [];
try { blocks = JSON.parse(document.getElementById('blocks-input').value) || []; } catch(e) { blocks=[]; }

// ── Rendu de la liste ─────────────────────────────────────
function renderList() {
  const container = document.getElementById('blk-list');
  container.innerHTML = '';
  if (!blocks.length) {
    container.innerHTML = '<div style="text-align:center;padding:24px 0;color:var(--muted);font-size:12px">Aucun bloc — ajoutez des éléments ci-dessous</div>';
    return;
  }
  blocks.forEach((blk, idx) => {
    const def = BLK_DEFS[blk.type] || { icon:'?', label: blk.type, fields:[] };
    const d   = blk.data || {};
    const preview = d.text || d.content || d.title || d.value || d.items_raw || '';
    const el = document.createElement('div');
    el.className = 'blk-item'; el.dataset.idx = idx;

    const fieldsHtml = def.fields.map(f => {
      const val = e2(d[f.k] ?? '');
      if (f.type === 'textarea') return `<div class="fg"><label>${f.l}</label><textarea class="txa" style="min-height:80px" data-k="${f.k}" oninput="updateBlk(${idx},this)">${val}</textarea></div>`;
      if (f.type === 'select')   return `<div class="fg"><label>${f.l}</label><select class="sel" data-k="${f.k}" onchange="updateBlk(${idx},this)">
        ${(f.opts||[]).map(o => `<option value="${o}" ${val===o?'selected':''}>${o}</option>`).join('')}
      </select></div>`;
      return `<div class="fg"><label>${f.l}</label><input type="text" class="inp" data-k="${f.k}" value="${val}" placeholder="${f.ph||''}" oninput="updateBlk(${idx},this)"></div>`;
    }).join('');

    el.innerHTML = `
      <div class="blk-header" onclick="toggleBlk(this)">
        <span class="blk-icon">${def.icon}</span>
        <span class="blk-label">${def.label}</span>
        <span class="blk-preview">${e2(preview.substring(0,60))}</span>
        <div class="blk-actions" onclick="event.stopPropagation()">
          <button type="button" class="btn btn-ghost btn-xs" onclick="moveBlk(${idx},-1)" title="Monter">↑</button>
          <button type="button" class="btn btn-ghost btn-xs" onclick="moveBlk(${idx},1)"  title="Descendre">↓</button>
          <button type="button" class="btn btn-red btn-xs"   onclick="delBlk(${idx})"     title="Supprimer">×</button>
        </div>
      </div>
      <div class="blk-body">${fieldsHtml}</div>
    `;
    container.appendChild(el);
  });

  // Drag & drop
  Sortable.create(container, {
    animation: 150,
    ghostClass: 'sortable-ghost',
    handle: '.blk-header',
    onEnd(evt) {
      const moved = blocks.splice(evt.oldIndex, 1)[0];
      blocks.splice(evt.newIndex, 0, moved);
      saveBlocks(); renderList();
    }
  });
}

function e2(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function toggleBlk(header) { header.nextElementSibling.classList.toggle('open'); }

function updateBlk(idx, el) {
  if (!blocks[idx]) return;
  if (!blocks[idx].data) blocks[idx].data = {};
  blocks[idx].data[el.dataset.k] = el.value;
  saveBlocks();
}

function addBlk(type) {
  blocks.push({ type, data:{} });
  saveBlocks(); renderList();
  // Ouvrir le nouveau bloc
  setTimeout(() => {
    const items = document.querySelectorAll('.blk-item');
    const last  = items[items.length-1];
    if (last) last.querySelector('.blk-body')?.classList.add('open');
    last?.scrollIntoView({behavior:'smooth',block:'nearest'});
  }, 50);
}

function delBlk(idx)  { if(confirm('Supprimer ce bloc ?')){ blocks.splice(idx,1); saveBlocks(); renderList(); } }

function moveBlk(idx, dir) {
  const to = idx + dir;
  if (to < 0 || to >= blocks.length) return;
  [blocks[idx], blocks[to]] = [blocks[to], blocks[idx]];
  saveBlocks(); renderList();
}

function saveBlocks() {
  // Transformer les champs raw avant sauvegarder
  const out = blocks.map(blk => {
    const d = {...(blk.data||{})};
    if (d.items_raw !== undefined) { d.items = d.items_raw.split('\n').map(s=>s.trim()).filter(Boolean); }
    if (d.tools_raw !== undefined) { try { d.tools = JSON.parse(d.tools_raw); } catch(e){d.tools=[];} }
    return { type: blk.type, data: d };
  });
  document.getElementById('blocks-input').value = JSON.stringify(out);
}

// ── Actions ───────────────────────────────────────────────
function openModal(id) { document.getElementById(id).style.display='flex'; }

function doShare() {
  const cId = document.getElementById('sh-cli').value;
  if(!cId){alert('Sélectionnez un client.');return;}
  fetch('/api/share-client.php',{method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({doc_id:DOC_ID,client_id:cId,_csrf:CSRF})})
  .then(r=>r.json()).then(d=>{if(d.ok)location.reload();else alert(d.error||'Erreur');});
}
function revokeClient(docId,cliId) {
  if(!confirm('Retirer cet accès ?'))return;
  fetch('/api/share-client.php',{method:'DELETE',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({doc_id:docId,client_id:cliId,_csrf:CSRF})})
  .then(r=>r.json()).then(d=>{if(d.ok)location.reload();});
}
function doLink() {
  const cId=document.getElementById('lk-cli').value||null;
  const exp=document.getElementById('lk-exp').value||null;
  fetch('/api/share-link.php',{method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({doc_id:DOC_ID,client_id:cId,expires_at:exp,_csrf:CSRF})})
  .then(r=>r.json()).then(d=>{if(d.ok){copyUrl(APPURL+'/share/'+d.token);document.getElementById('lk-modal').style.display='none';location.reload();}else alert(d.error||'Erreur');});
}
function revokeLink(id){
  if(!confirm('Révoquer ?'))return;
  fetch('/api/share-link.php',{method:'DELETE',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({link_id:id,_csrf:CSRF})})
  .then(r=>r.json()).then(d=>{if(d.ok)location.reload();});
}
function delDoc(){
  if(!confirm('Supprimer ce document ?'))return;
  fetch('/api/doc-delete.php',{method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({id:DOC_ID,_csrf:CSRF})})
  .then(r=>r.json()).then(d=>{if(d.ok)location.href='/admin/docs.php';});
}
function copyUrl(url){
  navigator.clipboard?.writeText(url).then(()=>{
    const t=document.createElement('div');
    t.style.cssText='position:fixed;bottom:22px;right:22px;background:var(--navy);color:white;border-radius:10px;padding:11px 20px;font-size:12.5px;font-weight:700;z-index:999;font-family:Montserrat,sans-serif;box-shadow:0 4px 20px rgba(35,56,116,.35)';
    t.textContent='📋 Lien copié !'; document.body.appendChild(t);
    setTimeout(()=>t.remove(),2500);
  });
}

// Init
renderList();
</script>

<?php pgEnd(); ?>
