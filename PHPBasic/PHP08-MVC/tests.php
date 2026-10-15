<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Learning\Mvc\Model\BookRepository;

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "缺少 pdo_sqlite。\n");
    exit(2);
}
function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('测试失败：' . $message);
    }
}

$repository = new BookRepository(new PDO('sqlite::memory:'));
$id = $repository->create('PHP MVC', '测试作者', 'ISBN-001');
assertTrue($id > 0, '新增返回 ID');
assertTrue($repository->find($id)['title'] === 'PHP MVC', '可按 ID 查询');
assertTrue($repository->update($id, 'PHP MVC 深入', '测试作者', 'ISBN-001'), '更新记录');
assertTrue($repository->find($id)['title'] === 'PHP MVC 深入', '更新结果正确');
assertTrue(count($repository->all('深入')) === 1, '关键词搜索匹配书名');
assertTrue($repository->delete($id), '删除现有记录');
assertTrue($repository->find($id) === null, '删除后查询为空');

$failed = false;
try {
    $repository->create('', '作者', '');
} catch (InvalidArgumentException) {
    $failed = true;
}
assertTrue($failed, '空书名必须被拒绝');

echo "PHP08 MVC 模型测试通过。\n";
