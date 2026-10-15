<?php
declare(strict_types=1);
session_name('php_learning_auth');
$https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);
session_start();
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/AuthService.php';
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf(): string { if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return (string) $_SESSION['csrf']; }
function checkCsrf(): void { $t = $_POST['csrf'] ?? ''; if (!is_string($t) || !hash_equals(csrf(), $t)) { http_response_code(400); exit('CSRF validation failed'); } }
function go(string $url): never { header('Location: ' . $url, true, 303); exit; }
$dir = dirname(__DIR__) . '/var';
if (!is_dir($dir)) mkdir($dir, 0700, true);
$pdo = new PDO('sqlite:' . $dir . '/auth.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL CHECK (role IN ("user", "admin")), created_at TEXT NOT NULL)');
$auth = new AuthService(new UserRepository($pdo));
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($path === '/logout' && $method === 'POST') {
    checkCsrf(); $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', ['expires'=>time()-42000,'path'=>$p['path'],'secure'=>$p['secure'],'httponly'=>$p['httponly'],'samesite'=>$p['samesite'] ?? 'Lax']); }
    session_destroy(); go('/login');
}
if ($path === '/login' && $method === 'POST') {
    checkCsrf(); $name = $_POST['username'] ?? ''; $pass = $_POST['password'] ?? '';
    if (!is_string($name) || !is_string($pass) || strlen($name)>32 || strlen($pass)>4096) { $_SESSION['flash']='用户名或密码不正确。'; go('/login'); }
    $_SESSION['attempts'] = (int)($_SESSION['attempts'] ?? 0); $_SESSION['blocked_until'] = (int)($_SESSION['blocked_until'] ?? 0);
    if (time() < $_SESSION['blocked_until']) { $_SESSION['flash']='尝试次数过多，请稍后再试。'; go('/login'); }
    $user = $auth->authenticate($name, $pass);
    if ($user === null) { $_SESSION['attempts']++; if ($_SESSION['attempts'] >= 5) { $_SESSION['blocked_until']=time()+60; $_SESSION['attempts']=0; } $_SESSION['flash']='用户名或密码不正确。'; go('/login'); }
    session_regenerate_id(true); unset($_SESSION['attempts'], $_SESSION['blocked_until']); $_SESSION['user']=$user; $_SESSION['csrf']=bin2hex(random_bytes(32)); go('/dashboard');
}
header('Content-Type: text/html; charset=UTF-8');
if ($path === '/dashboard' && $method === 'GET') {
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) go('/login');
    $u = $_SESSION['user']; echo '<!doctype html><html lang="zh-CN"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>控制台</title><body><main><h1>登录成功</h1><p>你好，'.e((string)$u['username']).'。</p><p>角色：'.e((string)$u['role']).'</p>';
    if ($u['role']==='admin') echo '<p>管理员区域示例</p>';
    echo '<form method="post" action="/logout"><input type="hidden" name="csrf" value="'.e(csrf()).'"><button>退出登录</button></form></main></body></html>'; exit;
}
if (($path==='/' || $path==='/login') && $method==='GET') {
    if (isset($_SESSION['user'])) go('/dashboard'); $flash=(string)($_SESSION['flash']??''); unset($_SESSION['flash']);
    echo '<!doctype html><html lang="zh-CN"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>PHP 登录示例</title><body><main><h1>用户登录</h1>';
    if ($flash!=='') echo '<p role="alert">'.e($flash).'</p>';
    echo '<form method="post" action="/login"><input type="hidden" name="csrf" value="'.e(csrf()).'"><label>用户名 <input name="username" autocomplete="username" required maxlength="32"></label><label>密码 <input type="password" name="password" autocomplete="current-password" required maxlength="4096"></label><button>登录</button></form><p>请先使用 README 中的 CLI 命令创建管理员账号。</p></main></body></html>'; exit;
}
http_response_code(404); echo '<!doctype html><html lang="zh-CN"><meta charset="utf-8"><title>404</title><body><h1>404 - 页面不存在</h1><a href="/login">返回登录</a></body></html>';
