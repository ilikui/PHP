<?php
declare(strict_types=1);

require_once __DIR__ . '/BookRepository.php';

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "缺少 pdo_sqlite，无法运行图书管理测试。\n");
    exit(2);
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('测试失败：' . $message);
    }
}

$repository = new BookRepository(new PDO('sqlite::memory:'));
$id = $repository->create('PHP 安全编程', '示例作者', '978-7-0000-0000-0');
check($id > 0, '新增记录应返回正整数 ID');
$book = $repository->find($id);
check($book !== null && $book['title'] === 'PHP 安全编程', '可以读取新增记录');
check($repository->update($id, 'PHP 安全编程（修订版）', '示例作者', ''), '更新应修改记录');
check($repository->find($id)['title'] === 'PHP 安全编程（修订版）', '更新后的书名正确');

$repository->create('C 语言入门', '另一位作者');
$repository->create('数据库设计', '示例作者');
$page = $repository->paginate('示例作者', 1, 2);
check($page['total'] === 2, '搜索应匹配书名、作者或 ISBN');
check(count($page['items']) === 2, '分页结果条数应受 perPage 限制');
check($repository->paginate('不存在的书')['total'] === 0, '无匹配项时总数为零');
check($repository->find(999999) === null, '不存在的 ID 返回 null');
check($repository->delete($id), '删除现有记录应返回 true');
check(!$repository->delete($id), '重复删除应返回 false');

$thrown = false;
try {
    $repository->create('   ', '作者');
} catch (InvalidArgumentException) {
    $thrown = true;
}
check($thrown, '书名为空时必须拒绝新增');

$thrown = false;
try {
    $repository->paginate('', 1, 1000);
} catch (Throwable) {
    $thrown = true;
}
check(!$thrown, '分页大小会被限制，而不是导致异常');

echo "BookRepository 测试通过：新增、查询、更新、删除、搜索、分页与输入校验。\n";
