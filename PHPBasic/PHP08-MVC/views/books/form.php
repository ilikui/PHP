<?php $title = $heading; ?>
<section class="panel">
<h2><?= e($heading) ?></h2>
<?php foreach ($errors as $error): ?><div class="error" role="alert"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" action="<?= !empty($book['id']) ? '/books/' . (int) $book['id'] . '/update' : '/books' ?>">
<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
<div class="grid">
<div><label for="title">书名（必填）</label><input id="title" name="title" maxlength="200" required value="<?= e($book['title'] ?? '') ?>"></div>
<div><label for="author">作者（必填）</label><input id="author" name="author" maxlength="120" required value="<?= e($book['author'] ?? '') ?>"></div>
<div><label for="isbn">ISBN（选填）</label><input id="isbn" name="isbn" maxlength="32" value="<?= e($book['isbn'] ?? '') ?>"></div>
</div><p class="actions"><button type="submit">保存</button><a class="button secondary" href="/books">返回列表</a></p>
</form>
</section>
