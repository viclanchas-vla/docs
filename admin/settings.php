<?php
// admin/settings.php
require_once dirname(__DIR__) . '/includes/core.php';
require_once dirname(__DIR__) . '/includes/layout.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD']==='POST' && csrfOk()) {
    $a=$_POST['action']??'';
    if ($a==='password') {
        $cur=$_POST['cur']??''; $new=$_POST['new']??''; $con=$_POST['con']??'';
        $adm=dbGet("SELECT * FROM admins WHERE id=?",[$_SESSION['uid']]);
        if (!password_verify($cur,$adm['password_hash'])) flash('err','❌ Mot de passe actuel incorrect.');
        elseif (strlen($new)<8) flash('err','❌ Nouveau mot de passe trop court.');
        elseif ($new!==$con) flash('err','❌ Mots de passe différents.');
        else { dbUpdate('admins',['password_hash'=>password_hash($new,PASSWORD_BCRYPT,['cost'=>12])],['id'=>$_SESSION['uid']]); flash('ok','✅ Mot de passe mis à jour.'); }
    }
    go('/admin/settings.php');
}
$adm=dbGet("SELECT * FROM admins WHERE id=?",[$_SESSION['uid']]);
$stats=['docs'=>dbGet("SELECT COUNT(*) n FROM docs")['n']??0,'clients'=>dbGet("SELECT COUNT(*) n FROM clients")['n']??0,'links'=>dbGet("SELECT COUNT(*) n FROM share_links WHERE is_active=1")['n']??0];
pgHead('Paramètres');pgSidebar('settings');pgTopbar('Paramètres');
?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px">
  <div class="card"><div class="card-head"><div class="card-title">🔒 Changer le mot de passe</div></div><div class="card-body">
    <form method="POST"><?=csrfField()?><input type="hidden" name="action" value="password">
      <div class="fg"><label class="lbl">Mot de passe actuel</label><input type="password" name="cur" class="inp" required></div>
      <div class="fg"><label class="lbl">Nouveau mot de passe</label><input type="password" name="new" class="inp" required minlength="8"><div class="hint">8 caractères minimum</div></div>
      <div class="fg"><label class="lbl">Confirmer</label><input type="password" name="con" class="inp" required minlength="8"></div>
      <button type="submit" class="btn btn-navy btn-sm">Modifier</button>
    </form>
  </div></div>
  <div class="card"><div class="card-head"><div class="card-title">ℹ️ Informations système</div></div><div class="card-body">
    <table style="width:100%;font-size:12px;border-collapse:collapse">
      <?php foreach([['URL',APP_URL],['Base',DB_NAME.' @ '.DB_HOST],['PHP',phpversion()],['Documents',$stats['docs']],['Clients',$stats['clients']],['Liens actifs',$stats['links']],['Dernière connexion',$adm['last_login']?date('d/m/Y H:i',strtotime($adm['last_login'])):'—']] as [$k,$v]): ?>
      <tr style="border-bottom:1px solid var(--border)"><td style="padding:8px 0;color:var(--muted);font-weight:600;width:45%"><?=e($k)?></td><td style="padding:8px 0;color:var(--navy);font-weight:500"><?=e((string)$v)?></td></tr>
      <?php endforeach; ?>
    </table>
  </div></div>
</div>
<?php pgEnd(); ?>
