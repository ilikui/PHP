<?php
declare(strict_types=1);

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        return $user === false ? null : $user;
    }

    public function create(string $username, string $password, string $role = 'user'): int
    {
        $username = trim($username);
        if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
            throw new InvalidArgumentException('用户名须为 3–32 位字母、数字、点、下划线或连字符。');
        }
        if (strlen($password) < 12) {
            throw new InvalidArgumentException('密码至少需要 12 个字符。');
        }
        if (!in_array($role, ['user', 'admin'], true)) {
            throw new InvalidArgumentException('角色无效。');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password_hash, role, created_at)
             VALUES (:username, :password_hash, :role, :created_at)'
        );
        $stmt->execute([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'created_at' => gmdate('c'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $hash, 'id' => $id]);
    }
}
