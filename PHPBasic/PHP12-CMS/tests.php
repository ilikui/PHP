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
check($repo->find($id)['category'] === '', 'category defaults to empty for older save calls');
$categorizedId = $repo->save(null, '分类与标签', '学习 PDO 和 SQLite', 'published', 'PHP 学习', 'PDO, SQLite，安全, PDO');
$categorized = $repo->find($categorizedId);
check($categorized['category'] === 'PHP 学习', 'article category is saved');
check($categorized['tags'] === 'PDO, SQLite, 安全', 'tags are trimmed and deduplicated');
check(count($repo->searchPublished('SQLite')) === 1, 'public search can find article by tag');
check(count($repo->published(10, 0, 'PHP 学习')) === 1, 'public article list can filter by category');
check($repo->publishedCount('', 'PHP 学习') === 1, 'public count respects category filter');
check($repo->all('', '', 'PHP 学习')[0]['id'] === $categorizedId, 'admin list can filter by category');
$repo->save($categorizedId, '分类与标签', '更新内容', 'published', '数据库', 'SQLite; migration');
check($repo->find($categorizedId)['category'] === '数据库', 'category and tags can be updated');
check($repo->find($categorizedId)['tags'] === 'SQLite, migration', 'tag updates replace previous tags');
try { $repo->save(null, '太多标签', 'body', 'draft', '', '一,二,三,四,五,六,七,八,九,十,十一'); check(false, 'more than ten tags rejected'); } catch (InvalidArgumentException) { check(true, 'more than ten tags rejected'); }
try { $repo->save(null, '超长标签', 'body', 'draft', '', str_repeat('标', 31)); check(false, 'overlong tag rejected'); } catch (InvalidArgumentException) { check(true, 'overlong tag rejected'); }
try { $repo->save(null, '超长分类', 'body', 'draft', str_repeat('分', 41), 'tag'); check(false, 'overlong category rejected'); } catch (InvalidArgumentException) { check(true, 'overlong category rejected'); }

try { $repo->save(null, '', 'body', 'draft'); check(false, 'empty title rejected'); } catch (InvalidArgumentException) { check(true, 'empty title rejected'); }
try { $repo->save(null, '标题', '', 'draft'); check(false, 'empty body rejected'); } catch (InvalidArgumentException) { check(true, 'empty body rejected'); }
$repo->delete($id);
check($repo->find($id) === null, 'article can be deleted');
