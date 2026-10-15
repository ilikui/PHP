<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Learning\Mvc\Controller\BookController;
use Learning\Mvc\Model\BookRepository;

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');

try {
    $dataDirectory = getenv('PHP08_DATA_DIR') ?: dirname(__DIR__) . '/var';
    if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0770, true) && !is_dir($dataDirectory)) {
        throw new RuntimeException('数据目录不可用。');
    }
    $pdo = new PDO('sqlite:' . $dataDirectory . '/books.sqlite');
    $controller = new BookController(new BookRepository($pdo));

    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    if ($path === '/' || $path === '/books') {
        if ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            exit('Method Not Allowed');
        }
        $controller->index();
    } elseif ($path === '/books/create' && $method === 'GET') {
        $controller->create();
    } elseif ($path === '/books' && $method === 'POST') {
        $controller->store();
    } elseif (preg_match('#^/books/([1-9][0-9]*)/edit$#', $path, $matches) && $method === 'GET') {
        $controller->edit((int) $matches[1]);
    } elseif (preg_match('#^/books/([1-9][0-9]*)/update$#', $path, $matches) && $method === 'POST') {
        $controller->update((int) $matches[1]);
    } elseif (preg_match('#^/books/([1-9][0-9]*)/delete$#', $path, $matches) && $method === 'POST') {
        $controller->delete((int) $matches[1]);
    } else {
        render('errors/404', [], 404);
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    if (http_response_code() < 400) {
        http_response_code(500);
    }
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html lang="zh-CN"><meta charset="utf-8"><title>应用错误</title><body><h1>请求处理失败</h1><p>请稍后重试；详细信息已写入服务器日志。</p></body></html>';
}
