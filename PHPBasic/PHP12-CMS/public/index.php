<?php
declare(strict_types=1);

use Learning\Cms\ArticleRepository;

require dirname(__DIR__) . '/src/ArticleRepository.php';

$base = dirname(__DIR__);
$dbPath = getenv('CMS_DB_PATH') ?: $base . '/var/cms.sqlite';
$dir = dirname($dbPath);
if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
    http_response_code(500);
    exit('Application storage is unavailable.');
}

$https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
session_name('PHP12CMS');
session_set_cookie_params(['httponly' => true, 'secure' => $https, 'samesite' => 'Lax', 'path' => '/']);
session_start();

$pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, created_at TEXT NOT NULL)');
$articles = new ArticleRepository($pdo);

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf(): string
{
    if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verifyCsrf(): void
{
    $given = $_POST['_csrf'] ?? '';
    if (!is_string($given) || !hash_equals(csrf(), $given)) {
        http_response_code(419);
        exit('表单已过期，请返回页面重试。');
    }
}
function go(string $path): never { header('Location: ' . $path, true, 303); exit; }
function isAdmin(): bool { return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']); }
function page(string $title, string $content): never
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; style-src \'self\' \'unsafe-inline\'; base-uri \'none\'; frame-ancestors \'none\'; form-action \'self\'');
    echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($title) . '｜PHP12 CMS</title><style>
    :root{color-scheme:light;--ink:#172033;--muted:#667085;--line:#e4e7ec;--accent:#2358c4;--bg:#f6f8fc}
    *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.65 system-ui,-apple-system,"Segoe UI",sans-serif}
    header{background:#fff;border-bottom:1px solid var(--line)}nav,.wrap{max-width:920px;margin:auto;padding:16px 22px}nav{display:flex;align-items:center;gap:18px;flex-wrap:wrap}nav a:first-child{font-weight:750;margin-right:auto}a{color:var(--accent);text-decoration:none}a:hover{text-decoration:underline}
    main{max-width:920px;margin:30px auto;padding:0 22px}.panel,article{background:#fff;border:1px solid var(--line);border-radius:14px;padding:22px;margin-bottom:16px;overflow-wrap:anywhere}
    h1{font-size:clamp(1.7rem,4vw,2.3rem);line-height:1.2;margin:8px 0 22px}h2{margin:0 0 8px;font-size:1.25rem}p{margin:.5em 0}small,.muted{color:var(--muted)}
    label{display:block;font-weight:650;margin:14px 0 6px}input[type=text],input[type=password],textarea,select{width:100%;padding:11px 12px;border:1px solid #cfd5df;border-radius:8px;font:inherit;background:#fff}textarea{min-height:260px;resize:vertical}
    button,.button{display:inline-block;border:0;border-radius:8px;background:var(--accent);color:white;padding:10px 15px;font:inherit;cursor:pointer}button.danger{background:#b42318}.actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:14px}form.inline{display:inline} .status{font-size:.85rem;border-radius:999px;background:#eef2ff;padding:3px 9px}.bodytext{white-space:pre-wrap;overflow-wrap:anywhere}.empty{padding:35px;text-align:center;color:var(--muted)}
    </style></head><body><header><nav><a href="/">PHP12 CMS</a><a href="/">文章</a>' . (isAdmin() ? '<a href="/admin">后台</a><a href="/admin/new">写文章</a><form class="inline" method="post" action="/logout"><input type="hidden" name="_csrf" value="' . e(csrf()) . '"><button type="submit">退出登录</button></form>' : '<a href="/login">管理员登录</a>') . '</nav></header><main>' . $content . '</main></body></html>';
    exit;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($method === 'POST') {
    verifyCsrf();
    if ($path === '/login') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        if (is_array($user) && password_verify($password, (string) $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = (string) $user['username'];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            go('/admin');
        }
        usleep(250000);
        page('登录失败', '<section class="panel"><h1>登录失败</h1><p>账号或密码不正确。</p><a href="/login">返回登录</a></section>');
    }
    if ($path === '/logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        go('/');
    }
    if (!isAdmin()) { http_response_code(403); page('无权访问', '<section class="panel"><h1>需要管理员登录</h1><a href="/login">前往登录</a></section>'); }
    if ($path === '/admin/save') {
        $rawId = $_POST['id'] ?? '';
        $id = $rawId === '' ? null : filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) page('输入无效', '<section class="panel"><h1>文章 ID 无效</h1><a href="/admin">返回后台</a></section>');
        try {
            $savedId = $articles->save($id, (string) ($_POST['title'] ?? ''), (string) ($_POST['body'] ?? ''), (string) ($_POST['status'] ?? 'draft'), (string) ($_POST['category'] ?? ''), (string) ($_POST['tags'] ?? ''));
            go('/admin/edit?id=' . $savedId . '&saved=1');
        } catch (InvalidArgumentException|OutOfBoundsException $exception) {
            page('保存失败', '<section class="panel"><h1>保存失败</h1><p>' . e($exception->getMessage()) . '</p><a href="/admin/new">返回编辑</a></section>');
        }
    }
    if ($path === '/admin/delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) page('输入无效', '<section class="panel"><h1>文章 ID 无效</h1><a href="/admin">返回后台</a></section>');
        $articles->delete((int) $id);
        go('/admin');
    }
    http_response_code(404);
    page('页面不存在', '<section class="panel"><h1>404</h1><a href="/">返回首页</a></section>');
}

if ($path === '/login') {
    if (isAdmin()) go('/admin');
    page('管理员登录', '<section class="panel"><h1>管理员登录</h1><p class="muted">账号通过命令行初始化，不开放公开注册。</p><form method="post" action="/login"><input type="hidden" name="_csrf" value="' . e(csrf()) . '"><label for="username">用户名</label><input id="username" name="username" type="text" maxlength="80" autocomplete="username" required><label for="password">密码</label><input id="password" name="password" type="password" autocomplete="current-password" required><div class="actions"><button type="submit">登录</button></div></form></section>');
}
if (str_starts_with($path, '/admin') && !isAdmin()) go('/login');
if ($path === '/admin') {
    $query = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
    $status = (string) ($_GET['status'] ?? '');
    if (!in_array($status, ['', 'draft', 'published'], true)) $status = '';
    $category = mb_substr(trim((string) ($_GET['category'] ?? '')), 0, 40);
    $rows = $articles->all($query, $status, $category);
    $items = '';
    foreach ($rows as $row) {
        $items .= '<article><h2><a href="/admin/edit?id=' . (int) $row['id'] . '">' . e((string) $row['title']) . '</a></h2><p><span class="status">' . ($row['status'] === 'published' ? '已发布' : '草稿') . '</span> ' . (!empty($row['category']) ? '<span class="status">分类：' . e((string) $row['category']) . '</span> ' : '') . '<small>更新于 ' . e((string) $row['updated_at']) . '</small></p><p class="muted">' . (!empty($row['tags']) ? '标签：' . e((string) $row['tags']) : '暂无标签') . '</p><div class="actions"><a class="button" href="/admin/edit?id=' . (int) $row['id'] . '">编辑</a><form class="inline" method="post" action="/admin/delete" onsubmit="return confirm(\'确定删除这篇文章吗？\')"><input type="hidden" name="_csrf" value="' . e(csrf()) . '"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="danger" type="submit">删除</button></form></div></article>';
    }
    $filter = '<section class="panel"><form method="get" action="/admin"><label for="q">搜索文章</label><input id="q" name="q" type="text" maxlength="100" value="' . e($query) . '" placeholder="标题或正文关键词"><label for="status">发布状态</label><select id="status" name="status"><option value=""' . ($status === '' ? ' selected' : '') . '>全部状态</option><option value="draft"' . ($status === 'draft' ? ' selected' : '') . '>草稿</option><option value="published"' . ($status === 'published' ? ' selected' : '') . '>已发布</option></select><label for="category">分类</label><input id="category" name="category" type="text" maxlength="40" value="' . e($category) . '" placeholder="精确匹配分类"><div class="actions"><button type="submit">筛选</button><a href="/admin">重置</a></div></form><p class="muted">当前找到 ' . count($rows) . ' 篇文章</p></section>';
    page('文章管理', '<h1>文章管理</h1><p class="muted">已登录：' . e((string) ($_SESSION['username'] ?? 'admin')) . '</p><p><a class="button" href="/admin/new">＋ 新建文章</a></p>' . $filter . ($items !== '' ? $items : '<section class="panel empty">没有匹配文章。</section>'));
}

if ($path === '/admin/new' || $path === '/admin/edit') {
    $id = $path === '/admin/edit' ? filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
    $row = null;
    if ($path === '/admin/edit') {
        $row = $id === false ? null : $articles->find((int) $id);
        if ($row === null) { http_response_code(404); page('文章不存在', '<section class="panel"><h1>文章不存在</h1><a href="/admin">返回后台</a></section>'); }
    }
    $title = (string) ($row['title'] ?? '');
    $body = (string) ($row['body'] ?? '');
    $status = (string) ($row['status'] ?? 'draft');
    $category = (string) ($row['category'] ?? '');
    $tags = (string) ($row['tags'] ?? '');
    $saved = isset($_GET['saved']) ? '<p class="status">保存成功</p>' : '';
    page($row ? '编辑文章' : '新建文章', '<section class="panel"><h1>' . ($row ? '编辑文章' : '新建文章') . '</h1>' . $saved . '<form method="post" action="/admin/save"><input type="hidden" name="_csrf" value="' . e(csrf()) . '"><input type="hidden" name="id" value="' . ($row ? (int) $row['id'] : '') . '"><label for="title">标题（最多 160 字符）</label><input id="title" name="title" type="text" maxlength="160" required value="' . e($title) . '"><label for="category">分类（可选，最多 40 字符）</label><input id="category" name="category" type="text" maxlength="40" value="' . e($category) . '" placeholder="例如：PHP、架构设计"><label for="tags">标签（可选，逗号分隔，最多 10 个，每个最多 30 字符）</label><input id="tags" name="tags" type="text" maxlength="400" value="' . e($tags) . '" placeholder="例如：PDO, SQLite, 安全"><label for="body">正文（纯文本，最多 50000 字符）</label><textarea id="body" name="body" maxlength="50000" required>' . e($body) . '</textarea><label for="status">发布状态</label><select id="status" name="status"><option value="draft"' . ($status === 'draft' ? ' selected' : '') . '>草稿</option><option value="published"' . ($status === 'published' ? ' selected' : '') . '>已发布</option></select><div class="actions"><button type="submit">保存文章</button><a href="/admin">取消</a></div></form></section>');
}

if (preg_match('#^/article/(\d+)$#', $path, $matches) === 1) {
    $row = $articles->find((int) $matches[1]);
    if ($row === null || ($row['status'] !== 'published' && !isAdmin())) { http_response_code(404); page('文章不存在', '<section class="panel"><h1>文章不存在</h1><a href="/">返回首页</a></section>'); }
    page((string) $row['title'], '<article><h1>' . e((string) $row['title']) . '</h1><p class="muted">' . (!empty($row['category']) ? '分类：<a href="/?category=' . rawurlencode((string) $row['category']) . '">' . e((string) $row['category']) . '</a> · ' : '') . '更新于 ' . e((string) $row['updated_at']) . '</p>' . (!empty($row['tags']) ? '<p>标签：' . implode(' ', array_map(static fn(string $tag): string => '<a class="status" href="/?q=' . rawurlencode($tag) . '">' . e($tag) . '</a>', array_filter(array_map('trim', explode(',', (string) $row['tags'])))) . '</p>' : '') . '<div class="bodytext">' . e((string) $row['body']) . '</div></article><a href="/">← 所有文章</a>');
}
if ($path === '/') {
    $pageNo = max(1, (int) ($_GET['page'] ?? 1));
    $query = trim((string) ($_GET['q'] ?? ''));
    $query = mb_substr($query, 0, 100);
    $category = mb_substr(trim((string) ($_GET['category'] ?? '')), 0, 40);
    $categories = $articles->categories();
    $total = $articles->publishedCount($query, $category);
    $pages = max(1, (int) ceil($total / 10));
    $pageNo = min($pageNo, $pages);
    $rows = $articles->searchPublished($query, 10, ($pageNo - 1) * 10, $category);
    $items = '';
    foreach ($rows as $row) {
        $preview = mb_strlen((string) $row['body']) > 240 ? mb_substr((string) $row['body'], 0, 240) . '…' : (string) $row['body'];
        $items .= '<article><h2><a href="/article/' . (int) $row['id'] . '">' . e((string) $row['title']) . '</a></h2><p class="muted">' . (!empty($row['category']) ? '<a class="status" href="/?category=' . rawurlencode((string) $row['category']) . '">' . e((string) $row['category']) . '</a> · ' : '') . e((string) $row['updated_at']) . '</p>' . (!empty($row['tags']) ? '<p>' . implode(' ', array_map(static fn(string $tag): string => '<a class="status" href="/?q=' . rawurlencode($tag) . '">' . e($tag) . '</a>', array_filter(array_map('trim', explode(',', (string) $row['tags'])))) . '</p>' : '') . '<div class="bodytext">' . e($preview) . '</div><p><a href="/article/' . (int) $row['id'] . '">阅读全文 →</a></p></article>';
    }
    $searchForm = '<section class="panel"><form method="get" action="/"><label for="q">搜索已发布文章</label><input id="q" name="q" type="text" maxlength="100" value="' . e($query) . '" placeholder="输入标题或正文关键词"><div class="actions"><button type="submit">搜索</button>' . ($query !== '' ? '<a href="/">清除搜索</a>' : '') . '</div></form><p class="muted">共找到 ' . $total . ' 篇文章</p></section>';
    $queryParam = ($query !== '' ? '&amp;q=' . rawurlencode($query) : '') . ($category !== '' ? '&amp;category=' . rawurlencode($category) : '');
    $categoryNav = '<section class="panel"><strong>分类浏览</strong><div class="actions"><a class="status" href="/">全部分类</a>' . implode('', array_map(static fn(string $item): string => '<a class="status" href="/?category=' . rawurlencode($item) . '">' . e($item) . '</a>', $categories)) . '</div>' . ($category !== '' ? '<p class="muted">当前分类：' . e($category) . ' <a href="/">清除</a></p>' : '') . '</section>';
    $pagination = '<div class="actions">' . ($pageNo > 1 ? '<a href="/?page=' . ($pageNo - 1) . $queryParam . '">← 上一页</a>' : '') . '<span class="muted">第 ' . $pageNo . ' / ' . $pages . ' 页</span>' . ($pageNo < $pages ? '<a href="/?page=' . ($pageNo + 1) . $queryParam . '">下一页 →</a>' : '') . '</div>';
    page('文章首页', '<h1>文章与想法</h1><p class="muted">一个使用 PHP、PDO 与 SQLite 构建的轻量内容站点。</p>' . $categoryNav . $searchForm . ($items !== '' ? $items . $pagination : '<section class="panel empty">没有匹配的已发布文章。</section>'));
}
http_response_code(404);
page('页面不存在', '<section class="panel"><h1>404｜页面不存在</h1><a href="/">返回首页</a></section>');
