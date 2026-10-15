<?php
declare(strict_types=1);

require __DIR__ . '/src/ArticleRepository.php';

use Learning\Cms\ArticleRepository;

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo "PASS: {$message}\n";
}

$pdo = new \PDO('sqlite::memory:');
$repo = new ArticleRepository($pdo);
$id = $repo->save(null, '测试文章', "第一行\n第二行", 'draft');
check($repo->find($id)['status'] === 'draft', 'new article defaults to draft');
check(count($repo->published()) === 0, 'draft is not public');
$repo->save($id, '测试文章', '已发布内容', 'published');
check(count($repo->published()) === 1, 'published article appears publicly');
check(count($repo->searchPublished('测试')) === 1, 'public search matches article title');
check(count($repo->searchPublished('已发布')) === 1, 'public search matches article body');
check(count($repo->searchPublished('不存在')) === 0, 'public search returns no false matches');
check($repo->publishedCount() === 1, 'published count excludes drafts');
check($repo->publishedCount('测试') === 1, 'filtered published count matches search');
check(count($repo->all('测试')) === 1, 'admin search matches title or body');
check(count($repo->all('', 'published')) === 1, 'admin status filter selects published articles');
check(count($repo->all('', 'draft')) === 0, 'admin status filter excludes published articles');
try { $repo->all('', 'unknown'); check(false, 'invalid admin status rejected'); } catch (InvalidArgumentException) { check(true, 'invalid admin status rejected'); }
check(count($repo->searchPublished('测试', 1, 1)) === 0, 'search supports pagination offsets');
check($repo->find($id)['body'] === '已发布内容', 'article can be updated');
try { $repo->save(null, '', 'body', 'draft'); check(false, 'empty title rejected'); } catch (InvalidArgumentException) { check(true, 'empty title rejected'); }
try { $repo->save(null, '标题', '', 'draft'); check(false, 'empty body rejected'); } catch (InvalidArgumentException) { check(true, 'empty body rejected'); }
$repo->delete($id);
check($repo->find($id) === null, 'article can be deleted');
