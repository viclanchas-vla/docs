<?php
require_once __DIR__ . '/includes/core.php';
if (isLogged()) go('/admin/');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $att = $_SESSION['att'] ?? 0;
    $lat = $_SESSION['lat'] ?? 0;
    if ($att >= 5 && time()-$lat < 900) {
        $err = 'Trop de tentatives. Réessayez dans 15 minutes.';
    } elseif (doLogin($_POST['user']??'', $_POST['pass']??'')) {
        unset($_SESSION['att'], $_SESSION['lat']);
        go('/admin/');
    } else {
        $_SESSION['att'] = $att+1; $_SESSION['lat'] = time();
        $err = 'Identifiant ou mot de passe incorrect.';
    }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Connexion — ProactiveGroup Docs</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#233874;--slate:#505A73;--muted:#8F98B3;--white:#fff;--bg:#f4f6f9}
body{font-family:'Montserrat',sans-serif;background:var(--bg);color:var(--slate);min-height:100vh;display:flex;flex-direction:column}

/* Header identique à MyES-LecteursReseaux */
.header{background-color:var(--white);padding:30px 40px;border-bottom:3px solid var(--navy);text-align:center}
.header img{max-width:220px;margin-bottom:16px;display:block;margin-left:auto;margin-right:auto}
.header h1{color:var(--navy);font-size:1.7rem;font-weight:700}
.header .subtitle{color:var(--muted);font-size:.95rem;font-weight:500;margin-top:5px}
.logo-fallback{display:none;width:50px;height:50px;background:var(--navy);border-radius:10px;align-items:center;justify-content:center;font-weight:800;font-size:18px;color:white;margin:0 auto 16px}

/* Container identique au style .container */
.container{max-width:480px;margin:40px auto;background:var(--white);border-radius:12px;box-shadow:0 10px 30px rgba(35,56,116,.08);overflow:hidden}
.content{padding:36px 40px}

.err{background:rgba(239,68,68,.07);border-left:5px solid #ef4444;color:#b91c1c;padding:11px 14px;border-radius:0 8px 8px 0;font-size:12.5px;font-weight:600;margin-bottom:16px}

label{display:block;font-size:10px;color:var(--slate);margin-bottom:5px;margin-top:14px;font-weight:700;text-transform:uppercase;letter-spacing:.07em}
input{width:100%;background:var(--bg);border:1px solid rgba(143,152,179,.35);border-radius:8px;padding:10px 13px;color:var(--slate);font-size:13px;outline:none;font-family:'Montserrat',sans-serif;font-weight:500;transition:border-color .15s,box-shadow .15s}
input:focus{border-color:var(--navy);box-shadow:0 0 0 3px rgba(35,56,116,.1)}
input::placeholder{color:var(--muted);font-weight:400}
.btn{display:block;width:100%;margin-top:22px;padding:12px;background:var(--navy);border:none;border-radius:8px;color:white;font-size:13px;font-weight:700;cursor:pointer;font-family:'Montserrat',sans-serif;letter-spacing:.04em;text-transform:uppercase;transition:all .2s;box-shadow:0 4px 10px rgba(35,56,116,.3)}
.btn:hover{background:#1c2e5e;box-shadow:0 6px 20px rgba(35,56,116,.4);transform:translateY(-1px)}

/* Footer identique */
.footer{text-align:center;padding:22px 40px;background-color:var(--bg);border-top:1px solid rgba(143,152,179,.2)}
.footer p{color:var(--slate);font-weight:500;font-style:italic;font-size:.9rem}
.footer a{color:var(--navy);text-decoration:none;font-weight:600;margin-top:8px;display:inline-block;font-size:.85rem}
.footer a:hover{text-decoration:underline}

.main-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:20px}
@media(max-width:520px){.container{margin:0;border-radius:0}.content,.header{padding:24px 20px}.main-wrap{padding:0}}
</style>
</head>
<body>
<div class="main-wrap">
  <div style="width:100%;max-width:480px">
    <div class="container">
      <div class="header">
        <img src="https://proactivegroup.fr/wp-content/uploads/2025/06/Original.svg" alt="ProactiveGroup"
          onerror="this.style.display='none';document.querySelector('.logo-fallback').style.display='flex'">
        <div class="logo-fallback">PG</div>
        <h1>ProactiveGroup Docs</h1>
        <p class="subtitle">Espace d'administration documentaire</p>
      </div>
      <div class="content">
        <?php if($err): ?><div class="err">⚠️ <?= e($err) ?></div><?php endif; ?>
        <form method="POST">
          <?= csrfField() ?>
          <label>Identifiant</label>
          <input type="text" name="user" autofocus autocomplete="username" required placeholder="admin">
          <label>Mot de passe</label>
          <input type="password" name="pass" autocomplete="current-password" required placeholder="••••••••">
          <button type="submit" class="btn">Se connecter →</button>
        </form>
      </div>
      <div class="footer">
        <p>"Parce que votre informatique doit être un atout, pas une contrainte."</p>
        <a href="https://proactivegroup.fr/" target="_blank">www.proactivegroup.fr</a>
      </div>
    </div>
  </div>
</div>
</body></html>
