<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/BookStore.php';
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function bearerToken(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!is_string($header) || !preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return '';
    return trim($m[1]);
}

$expectedToken = (string) getenv('API_BEARER_TOKEN');
if ($expectedToken === '' || !hash_equals($expectedToken, bearerToken())) {
    header('WWW-Authenticate: Bearer realm="PHP10 API"');
    respond(401, ['error' => ['code' => 'unauthorized', 'message' => 'A valid bearer token is required.']]);
}

$raw = file_get_contents('php://input');
if ($raw !== false && strlen($raw) > 65536) respond(413, ['error' => ['code' => 'payload_too_large', 'message' => 'JSON body must not exceed 64 KiB.']]);
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';
$basePath = '/api/books';
if ($path !== $basePath && !preg_match('#^/api/books/[1-9][0-9]*$#', $path)) {
    respond(404, ['error' => ['code' => 'not_found', 'message' => 'Endpoint not found.']]);
}

$id = null;
if ($path !== $basePath) $id = (int) substr($path, strlen($basePath) + 1);
try {
    $dir = dirname(__DIR__) . '/var';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('Cannot create data directory.');
    $pdo = new PDO('sqlite:' . $dir . '/books.sqlite');
    $store = new BookStore($pdo);

    if ($method === 'GET' && $id === null) {
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
        $perPage = filter_var($_GET['per_page'] ?? 10, FILTER_VALIDATE_INT);
        if ($page === false || $perPage === false || $page < 1 || $perPage < 1 || $perPage > 50) respond(422, ['error' => ['code' => 'validation_error', 'message' => 'page must be >= 1 and per_page must be between 1 and 50.']]);
        $q = $_GET['q'] ?? '';
        $sort = $_GET['sort'] ?? 'id';
        $direction = $_GET['direction'] ?? 'desc';
        if (!is_string($q) || strlen($q) > 200 || !is_string($sort) || !is_string($direction)) respond(422, ['error' => ['code' => 'validation_error', 'message' => 'Invalid query parameters.']]);
        respond(200, ['data' => $store->list($q, $page, $perPage, $sort, $direction)]);
    }

    if ($method === 'GET' && $id !== null) {
        $book = $store->find($id);
        if ($book === null) respond(404, ['error' => ['code' => 'not_found', 'message' => 'Book not found.']]);
        respond(200, ['data' => $book]);
    }

    if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
        if ($method === 'POST' && $id !== null) respond(405, ['error' => ['code' => 'method_not_allowed', 'message' => 'Use POST on /api/books to create a book.']]);
        if ($method !== 'POST' && $id === null) respond(405, ['error' => ['code' => 'method_not_allowed', 'message' => 'Use POST on /api/books to create a book.']]);
        $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
        if ($contentType !== 'application/json') respond(415, ['error' => ['code' => 'unsupported_media_type', 'message' => 'Content-Type must be application/json.']]);
        try { $input = json_decode($raw === false ? '' : $raw, true, 512, JSON_THROW_ON_ERROR); }
        catch (JsonException) { respond(400, ['error' => ['code' => 'invalid_json', 'message' => 'Request body must be valid JSON.']]); }
        if (!is_array($input) || array_is_list($input)) respond(422, ['error' => ['code' => 'validation_error', 'message' => 'JSON body must be an object.']]);
        if ($method !== 'POST' && $method === 'PATCH') {
            // This learning API uses full-resource updates for both PUT and PATCH.
            // Require the same validated title/author payload to avoid accidental partial writes.
        }
        try {
            if ($method === 'POST') respond(201, ['data' => $store->create($input)]);
            $book = $store->update($id, $input);
            if ($book === null) respond(404, ['error' => ['code' => 'not_found', 'message' => 'Book not found.']]);
            respond(200, ['data' => $book]);
        } catch (InvalidArgumentException $e) {
            respond(422, ['error' => ['code' => 'validation_error', 'message' => $e->getMessage()]]);
        }
    }

    if ($method === 'DELETE' && $id !== null) {
        if (!$store->delete($id)) respond(404, ['error' => ['code' => 'not_found', 'message' => 'Book not found.']]);
        http_response_code(204); exit;
    }

    header('Allow: GET, POST, PUT, PATCH, DELETE');
    respond(405, ['error' => ['code' => 'method_not_allowed', 'message' => 'HTTP method is not supported for this endpoint.']]);
} catch (Throwable $e) {
    error_log((string) $e);
    respond(500, ['error' => ['code' => 'internal_error', 'message' => 'An unexpected server error occurred.']]);
}
