<?php
declare(strict_types=1);
require_once __DIR__ . '/src/UserRepository.php';
require_once __DIR__ . '/src/AuthService.php';
function check(bool $ok, string $label): void { if (!$ok) throw new RuntimeException('FAIL: '.$label); echo 'PASS: '.$label.PHP_EOL; }
$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL CHECK (role IN ("user", "admin")), created_at TEXT NOT NULL)');
$repo = new UserRepository($pdo); $auth = new AuthService($repo);
$id = $repo->create('learner_1', 'correct horse battery staple', 'admin');
check($id > 0, 'creates user');
$row = $repo->findByUsername('learner_1');
check($row !== null && $row['password_hash'] !== 'correct horse battery staple', 'stores password hash instead of plaintext');
check(password_verify('correct horse battery staple', $row['password_hash']), 'stored password hash verifies');
check($auth->authenticate('learner_1', 'correct horse battery staple')['role'] === 'admin', 'valid credentials authenticate');
check($auth->authenticate('learner_1', 'wrong password') === null, 'rejects incorrect password');
check($auth->authenticate('missing_user', 'wrong password') === null, 'rejects unknown user');
foreach ([['ab','correct horse battery staple','user'], ['validname','tiny','user'], ['validname','correct horse battery staple','root']] as $case) {
    try { $repo->create($case[0], $case[1], $case[2]); check(false, 'rejects invalid account input'); }
    catch (InvalidArgumentException) { check(true, 'rejects invalid account input'); }
}
echo "All PHP09 auth tests passed.".PHP_EOL;
