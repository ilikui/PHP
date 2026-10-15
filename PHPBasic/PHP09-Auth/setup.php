<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit("Not found\n"); }
require_once __DIR__ . '/src/UserRepository.php';
$dir = __DIR__ . '/var';
if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) { throw new RuntimeException('Cannot create data directory'); }
$pdo = new PDO('sqlite:' . $dir . '/auth.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL CHECK (role IN ("user", "admin")), created_at TEXT NOT NULL)');
$username = trim((string) getenv('AUTH_ADMIN_USERNAME'));
$password = (string) getenv('AUTH_ADMIN_PASSWORD');
if ($username === '' || $password === '') { fwrite(STDERR, "Set AUTH_ADMIN_USERNAME and AUTH_ADMIN_PASSWORD (12+ chars) before running setup.\n"); exit(2); }
try { (new UserRepository($pdo))->create($username, $password, 'admin'); echo "Admin account created: {$username}\n"; }
catch (PDOException $e) { fwrite(STDERR, "Could not create account; username may already exist.\n"); exit(1); }
