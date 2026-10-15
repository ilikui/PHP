<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'PHP08 MVC 图书管理') ?></title>
<style>
:root{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#172033;background:#f4f6fa}
body{margin:0;padding:24px}main{max-width:960px;margin:auto}.top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;margin:18px 0}
a{color:#1d4ed8}button,.button{display:inline-block;background:#1d4ed8;color:#fff;border:0;border-radius:8px;padding:10px 14px;font:inherit;text-decoration:none;cursor:pointer}
.secondary{background:#e2e8f0;color:#172033}.danger{background:#b91c1c}.muted{color:#64748b}
input{box-sizing:border-box;width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;font:inherit}
label{display:block;margin:12px 0 6px}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:12px 8px;border-bottom:1px solid #e2e8f0}
.table-wrap{overflow-x:auto}.flash{padding:12px;border-radius:8px;background:#eff6ff;margin:12px 0}.error{padding:12px;background:#fff1f2;color:#9f1239;border-radius:8px;margin:12px 0}
.actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.inline{display:inline}.search{display:flex;gap:8px;max-width:600px}.search input{flex:1}
@media(max-width:640px){body{padding:12px}.panel{padding:14px}.grid{grid-template-columns:1fr}}
</style>
</head><body><main>
<header class="top"><div><h1>PHP08 · MVC 图书管理</h1><p class="muted">Controller · Model · View · PDO SQLite</p></div><a class="button" href="/books/create">添加图书</a></header>
<?php if (!empty($_SESSION['flash'])): ?><div class="flash" role="status"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif; ?>
