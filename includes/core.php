<?php
// includes/core.php — Toutes les fonctions du système

ob_start(); // Empêche "headers already sent"

require_once dirname(__DIR__) . '/env.php';

date_default_timezone_set('Europe/Paris');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ── Session ────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 28800,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name(SESS);
    session_start();
}

// ── Base de données ────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function dbGet(string $sql, array $p = []): ?array {
    $s = db()->prepare($sql); $s->execute($p); return $s->fetch() ?: null;
}
function dbAll(string $sql, array $p = []): array {
    $s = db()->prepare($sql); $s->execute($p); return $s->fetchAll();
}
function dbRun(string $sql, array $p = []): PDOStatement {
    $s = db()->prepare($sql); $s->execute($p); return $s;
}
function dbInsert(string $t, array $d): int {
    $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($d)));
    $vals = implode(',', array_fill(0, count($d), '?'));
    db()->prepare("INSERT INTO `$t` ($cols) VALUES ($vals)")->execute(array_values($d));
    return (int) db()->lastInsertId();
}
function dbUpdate(string $t, array $d, array $w): void {
    $set  = implode(',', array_map(fn($k) => "`$k`=?", array_keys($d)));
    $cond = implode(' AND ', array_map(fn($k) => "`$k`=?", array_keys($w)));
    db()->prepare("UPDATE `$t` SET $set WHERE $cond")->execute([...array_values($d), ...array_values($w)]);
}
function dbDelete(string $t, array $w): void {
    $cond = implode(' AND ', array_map(fn($k) => "`$k`=?", array_keys($w)));
    db()->prepare("DELETE FROM `$t` WHERE $cond")->execute(array_values($w));
}

// ── Auth ───────────────────────────────────────────────────
function isLogged(): bool { return !empty($_SESSION['uid']); }

function requireLogin(): void {
    if (!isLogged()) { header('Location: /login.php'); exit; }
}

function doLogin(string $user, string $pass): bool {
    $row = dbGet("SELECT * FROM admins WHERE username=? OR email=? LIMIT 1", [$user, $user]);
    if (!$row || !password_verify($pass, $row['password_hash'])) return false;
    session_regenerate_id(true);
    $_SESSION['uid']   = $row['id'];
    $_SESSION['uname'] = $row['username'];
    $_SESSION['csrf']  = bin2hex(random_bytes(32));
    dbRun("UPDATE admins SET last_login=NOW() WHERE id=?", [$row['id']]);
    return true;
}

function doLogout(): void { session_destroy(); }

// ── CSRF ───────────────────────────────────────────────────
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrfField(): string {
    return '<input type="hidden" name="_csrf" value="' . csrf() . '">';
}
function csrfOk(): bool {
    $t = $_POST['_csrf'] ?? (json_decode(file_get_contents('php://input'), true)['_csrf'] ?? '');
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

// ── Helpers ────────────────────────────────────────────────
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function go(string $url): never { header("Location: $url"); exit; }
function flash(string $t, string $m): void { $_SESSION['flash'] = ['t' => $t, 'm' => $m]; }
function uid4(): string {
    $b = random_bytes(16);
    $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
    $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}
function jsonOk(array $d = []): never { header('Content-Type: application/json'); echo json_encode(['ok'=>true]+$d); exit; }
function jsonErr(string $m, int $c = 400): never { http_response_code($c); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>$m]); exit; }

// ── Headers sécurité ──────────────────────────────────────
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
