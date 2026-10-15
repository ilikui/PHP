<?php
declare(strict_types=1);
require_once __DIR__ . '/src/BookStore.php';
function check(bool $condition, string $message): void { if (!$condition) throw new RuntimeException('FAIL: ' . $message); echo 'PASS: ' . $message . PHP_EOL; }
$pdo = new PDO('sqlite::memory:');
$store = new BookStore($pdo);
$created = $store->create(['title' => 'Clean Code', 'author' => 'Robert C. Martin', 'isbn' => '9780132350884']);
check((int)$created['id'] > 0, 'creates a book and returns resource representation');
check($store->find((int)$created['id'])['title'] === 'Clean Code', 'reads book by ID');
$updated = $store->update((int)$created['id'], ['title' => 'Clean Code 2', 'author' => 'Robert C. Martin', 'isbn' => '']);
check($updated['title'] === 'Clean Code 2' && $updated['isbn'] === null, 'updates book and normalizes empty ISBN');
$store->create(['title' => 'Refactoring', 'author' => 'Martin Fowler']);
$page = $store->list('Martin', 1, 1, 'title', 'asc');
check($page['total'] === 2 && count($page['data']) === 1 && $page['per_page'] === 1, 'searches and paginates results');
check($store->delete((int)$created['id']), 'deletes existing book');
check(!$store->delete((int)$created['id']), 'returns false when deleting missing book');
check($store->update(999, ['title'=>'No book', 'author'=>'Nobody']) === null, 'returns null when updating missing book');
try { $store->create(['title'=>'', 'author'=>'Someone']); check(false, 'rejects empty title'); }
catch (InvalidArgumentException) { check(true, 'rejects empty title'); }
try { $store->create(['title'=>str_repeat('x', 201), 'author'=>'Someone']); check(false, 'rejects oversized title'); }
catch (InvalidArgumentException) { check(true, 'rejects oversized title'); }
echo "All PHP10 REST API tests passed." . PHP_EOL;
