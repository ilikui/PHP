<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/BookRepository.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirectToList(): never
{
    header('Location: index.php', true, 303);
    exit;
}

$databasePath = getenv('BOOK_MANAGER_DB') ?: __DIR__ . '/books.sqlite';
$directory = dirname($databasePath);
if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
    http_response_code(500);
    exit('数据库目录不可用。');
}

try {
    $repository = new BookRepository(new PDO('sqlite:' . $databasePath));
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('数据库初始化失败，请检查 pdo_sqlite 扩展和目录权限。');
}

$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$errors = [];
$form = ['id' => '', 'title' => '', 'author' => '', 'isbn' => ''];
$flash = (string) ($_SESSION['flash'] ?? '');
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('请求验证失败，请刷新页面后重试。');
    }

    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                throw new InvalidArgumentException('无效的图书 ID。');
            }
            $_SESSION['flash'] = $repository->delete((int) $id) ? '图书已删除。' : '记录不存在或已被删除。';
            redirectToList();
        }
        if ($action !== 'save') {
            throw new InvalidArgumentException('不支持的操作。');
        }

        $rawId = $_POST['id'] ?? '';
        $form = [
            'id' => is_scalar($rawId) ? (string) $rawId : '',
            'title' => is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '',
            'author' => is_string($_POST['author'] ?? null) ? trim($_POST['author']) : '',
            'isbn' => is_string($_POST['isbn'] ?? null) ? trim($_POST['isbn']) : '',
        ];
        if ($form['title'] === '' || mb_strlen($form['title']) > 200) {
            $errors[] = '书名必填，且不能超过 200 个字符。';
        }
        if ($form['author'] === '' || mb_strlen($form['author']) > 120) {
            $errors[] = '作者必填，且不能超过 120 个字符。';
        }
        if (mb_strlen($form['isbn']) > 32) {
            $errors[] = 'ISBN 不能超过 32 个字符。';
        }

        if ($errors === []) {
            if ($form['id'] === '') {
                $repository->create($form['title'], $form['author'], $form['isbn']);
                $_SESSION['flash'] = '图书已添加。';
            } else {
                $id = filter_var($form['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id === false) {
                    throw new InvalidArgumentException('无效的图书 ID。');
                }
                $_SESSION['flash'] = $repository->update((int) $id, $form['title'], $form['author'], $form['isbn'])
                    ? '图书已更新。' : '没有数据变更，或记录不存在。';
            }
            redirectToList();
        }
    } catch (InvalidArgumentException $exception) {
        $errors[] = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log((string) $exception);
        http_response_code(500);
        $errors[] = '操作失败，请稍后重试；详细信息已记录到服务器日志。';
    }
}

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $editId !== false && $editId !== null) {
    $book = $repository->find((int) $editId);
    if ($book !== null) {
        $form = ['id' => (string) $book['id'], 'title' => (string) $book['title'],
            'author' => (string) $book['author'], 'isbn' => (string) $book['isbn']];
    } else {
        $flash = '未找到要编辑的图书。';
    }
}

$keyword = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$pageInput = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$page = $pageInput === false || $pageInput === null ? 1 : $pageInput;
try {
    $result = $repository->paginate($keyword, $page, 10);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('读取图书列表失败。');
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PHP PDO 图书管理</title>
<style>
:root{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#1f2937;background:#f3f5f9}
body{margin:0;padding:24px}main{max-width:1050px;margin:auto}h1{margin-bottom:8px}
.muted{color:#64748b}.panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;margin:18px 0;box-shadow:0 4px 16px #0f172a0a}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}label{display:block;font-size:.9rem;margin-bottom:6px}
input{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit}
button,.button{display:inline-block;border:0;border-radius:8px;padding:10px 14px;font:inherit;cursor:pointer;text-decoration:none;background:#1d4ed8;color:white}
.secondary{background:#e2e8f0;color:#1e293b}.danger{background:#b91c1c}.inline{display:inline}.inline button{padding:7px 10px}
.notice{background:#eff6ff;border-radius:8px;padding:12px;margin:12px 0}.error{background:#fff1f2;color:#9f1239;border-radius:8px;padding:12px;margin:12px 0}
.search{display:flex;gap:8px;max-width:560px}.search input{flex:1}.table-wrap{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:620px}
th,td{text-align:left;padding:12px 10px;border-bottom:1px solid #e2e8f0}th{color:#475569;font-size:.9rem}
.actions{display:flex;gap:8px;align-items:center}.pagination{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:16px}
@media(max-width:680px){body{padding:12px}.grid{grid-template-columns:1fr}.panel{padding:14px}}
</style>
</head>
<body><main>
<h1>图书管理</h1><p class="muted">PHP 8.2+ · PDO SQLite · 预处理语句 · CSRF 防护 · 服务端校验</p>
<?php if ($flash !== ''): ?><div class="notice" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="error" role="alert"><?= e($error) ?></div><?php endforeach; ?>
<section class="panel">
<h2><?= $form['id'] === '' ? '添加图书' : '编辑图书' ?></h2>
<form method="post" action="index.php">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($form['id']) ?>">
<div class="grid">
<div><label for="title">书名（必填）</label><input id="title" name="title" maxlength="200" required value="<?= e($form['title']) ?>"></div>
<div><label for="author">作者（必填）</label><input id="author" name="author" maxlength="120" required value="<?= e($form['author']) ?>"></div>
<div><label for="isbn">ISBN（选填）</label><input id="isbn" name="isbn" maxlength="32" value="<?= e($form['isbn']) ?>"></div>
</div><p><button type="submit"><?= $form['id'] === '' ? '添加图书' : '保存修改' ?></button>
<?php if ($form['id'] !== ''): ?> <a class="button secondary" href="index.php">取消编辑</a><?php endif; ?></p>
</form></section>
<section class="panel"><h2>图书列表 <small class="muted">共 <?= (int) $result['total'] ?> 条</small></h2>
<form class="search" method="get" action="index.php">
<input name="q" aria-label="搜索书名、作者或 ISBN" placeholder="搜索书名、作者或 ISBN" value="<?= e($keyword) ?>">
<button type="submit">搜索</button><?php if ($keyword !== ''): ?> <a class="button secondary" href="index.php">清除</a><?php endif; ?>
</form><div class="table-wrap"><table><thead><tr><th>ID</th><th>书名</th><th>作者</th><th>ISBN</th><th>操作</th></tr></thead><tbody>
<?php if ($result['items'] === []): ?><tr><td colspan="5" class="muted">没有找到图书。可以添加一条新记录。</td></tr>
<?php else: foreach ($result['items'] as $book): ?><tr>
<td><?= (int) $book['id'] ?></td><td><?= e($book['title']) ?></td><td><?= e($book['author']) ?></td><td><?= e($book['isbn']) ?></td>
<td><div class="actions"><a class="button secondary" href="?edit=<?= (int) $book['id'] ?>">编辑</a>
<form class="inline" method="post" action="index.php" onsubmit="return confirm('确定删除这本书吗？');">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= (int) $book['id'] ?>"><button class="danger" type="submit">删除</button>
</form></div></td></tr><?php endforeach; endif; ?>
</tbody></table></div>
<nav class="pagination" aria-label="分页">
<?php if ($result['page'] > 1): ?><a class="button secondary" href="?q=<?= rawurlencode($keyword) ?>&page=<?= $result['page'] - 1 ?>">上一页</a><?php endif; ?>
<span class="muted">第 <?= (int) $result['page'] ?> / <?= (int) $result['pages'] ?> 页</span>
<?php if ($result['page'] < $result['pages']): ?><a class="button secondary" href="?q=<?= rawurlencode($keyword) ?>&page=<?= $result['page'] + 1 ?>">下一页</a><?php endif; ?>
</nav></section>
<p class="muted">这是学习示例。生产环境还需加入登录授权、速率限制、审计记录与正式部署配置。</p>
</main></body></html>
