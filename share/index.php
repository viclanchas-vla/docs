<?php
require_once dirname(__DIR__) . '/includes/core.php';

// URL : /share/TOKEN ou /share/UUID
$seg = trim($_GET['t'] ?? '', '/');

// Essayer comme token de partage
$link = null;
$doc  = null;

if (strlen($seg) === 64 && ctype_xdigit($seg)) {
    $link = dbGet(
        "SELECT sl.*, d.* FROM share_links sl
         INNER JOIN docs d ON d.id = sl.doc_id
         WHERE sl.token=? AND sl.is_active=1
           AND (sl.expires_at IS NULL OR sl.expires_at > NOW())
           AND d.status='published'",
        [$seg]
    );
    if ($link) {
        dbRun("UPDATE share_links SET view_count=view_count+1 WHERE token=?", [$seg]);
        $doc = $link;
    }
}

// Essayer comme UUID direct (document public)
if (!$doc) {
    $doc = dbGet("SELECT * FROM docs WHERE uuid=? AND status='published'", [$seg]);
}

if (!$doc) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Introuvable</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:Montserrat,sans-serif;background:#f4f6f9;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;color:#505A73}h1{color:#233874;font-size:1.5rem;margin-bottom:8px}</style>
    </head><body><div><h1>Document introuvable</h1><p>Ce lien est invalide ou a expiré.</p></div></body></html>';
    exit;
}

$blocks = json_decode($doc['blocks'] ?? '[]', true) ?: [];
require dirname(__DIR__) . '/doc-view.php';
