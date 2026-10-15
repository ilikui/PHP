<?php
declare(strict_types=1);

define('APP_ROOT', __DIR__);
define('APP_VIEW', APP_ROOT . '/views');

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('php08_mvc_session');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Lax',
    'path' => '/',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Learning\\Mvc\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function verify_csrf(): void
{
    $submitted = $_POST['_csrf'] ?? '';
    if (!is_string($submitted) || !isset($_SESSION['_csrf']) ||
        !hash_equals($_SESSION['_csrf'], $submitted)) {
        http_response_code(403);
        throw new RuntimeException('请求验证失败，请刷新页面后重试。');
    }
}

function render(string $view, array $data = [], int $status = 200): void
{
    http_response_code($status);
    extract($data, EXTR_SKIP);
    $viewFile = APP_VIEW . '/' . $view . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException('视图文件不存在。');
    }
    require APP_VIEW . '/layout/header.php';
    require $viewFile;
    require APP_VIEW . '/layout/footer.php';
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}
