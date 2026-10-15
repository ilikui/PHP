<?php $title = '图书列表'; ?>
<section class="panel">
<h2>图书列表</h2>
<form class="search" method="get" action="/books">
<input name="q" aria-label="搜索书名、作者或 ISBN" placeholder="搜索书名、作者或 ISBN" value="<?= e($keyword) ?>">
<button type="submit">搜索</button>
<?php if ($keyword !== ''): ?><a class="button secondary" href="/books">清除</a><?php endif; ?>
</form>
<div class="table-wrap"><table>
<thead><tr><th>ID</th><th>书名</th><th>作者</th><th>ISBN</th><th>操作</th></tr></thead><tbody>
<?php if ($books === []): ?><tr><td colspan="5" class="muted">暂无记录，添加一本书开始体验吧。</td></tr>
<?php else: foreach ($books as $book): ?><tr>
<td><?= (int) $book['id'] ?></td><td><?= e($book['title']) ?></td><td><?= e($book['author']) ?></td><td><?= e($book['isbn']) ?></td>
<td><div class="actions"><a class="button secondary" href="/books/<?= (int) $book['id'] ?>/edit">编辑</a>
<form class="inline" method="post" action="/books/<?= (int) $book['id'] ?>/delete" onsubmit="return confirm('确定删除这本书吗？');">
<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><button class="danger" type="submit">删除</button>
</form></div></td></tr><?php endforeach; endif; ?>
</tbody></table></div>
</section>
