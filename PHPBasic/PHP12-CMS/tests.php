<?php
declare(strict_types=1);

require __DIR__ . '/src/ArticleRepository.php';

use Learning\Cms\ArticleRepository;
use PDO;

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo "PASS: {$message}\n";
}

$pdo = new PDO('sqlite::memory:');
$repo = new ArticleRepository($pdo);
$id = $repo->save(null, '测试文章', "第一行\n第二行", 'draft');
check($repo->find($id)['status'] === 'draft', 'new article defaults to draft');
check(count($repo->published()) === 0, 'draft is not public');
$repo->save($id, '测试文章', '已发布内容', 'published');
check(count($repo->published()) === 1, 'published article appears publicly');
check($repo->find($id)['body'] === '已发布内容', 'article can be updated');
try { $repo->save(null, '', 'body', 'draft'); check(false, 'empty title rejected'); } catch (InvalidArgumentException) { check(true, 'empty title rejected'); }
try { $repo->save(null, '标题', '', 'draft'); check(false, 'empty body rejected'); } catch (InvalidArgumentException) { check(true, 'empty body rejected'); }
$repo->delete($id);
check($repo->find($id) === null, 'article can be deleted');
