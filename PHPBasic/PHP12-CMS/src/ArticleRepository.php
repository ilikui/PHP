<?php
declare(strict_types=1);

namespace Learning\Cms;

use PDO;

final class ArticleRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            body TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT \'draft\' CHECK(status IN (\'draft\', \'published\')),
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )');
    }

    /** @return list<array<string, mixed>> */
    public function published(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, body, status, created_at, updated_at FROM articles WHERE status = \'published\' ORDER BY updated_at DESC, id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', max(1, min($limit, 100)), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT id, title, body, status, created_at, updated_at FROM articles ORDER BY updated_at DESC, id DESC')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, body, status, created_at, updated_at FROM articles WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function save(?int $id, string $title, string $body, string $status): int
    {
        $title = trim($title);
        $body = trim($body);
        if ($title === '' || mb_strlen($title) > 160) throw new \InvalidArgumentException('标题必须为 1–160 个字符。');
        if ($body === '' || mb_strlen($body) > 50000) throw new \InvalidArgumentException('正文不能为空且不能超过 50000 个字符。');
        if (!in_array($status, ['draft', 'published'], true)) throw new \InvalidArgumentException('文章状态无效。');
        $now = gmdate('c');
        if ($id === null) {
            $stmt = $this->pdo->prepare('INSERT INTO articles(title, body, status, created_at, updated_at) VALUES(:title, :body, :status, :created, :updated)');
            $stmt->execute([':title' => $title, ':body' => $body, ':status' => $status, ':created' => $now, ':updated' => $now]);
            return (int) $this->pdo->lastInsertId();
        }
        $stmt = $this->pdo->prepare('UPDATE articles SET title=:title, body=:body, status=:status, updated_at=:updated WHERE id=:id');
        $stmt->execute([':title' => $title, ':body' => $body, ':status' => $status, ':updated' => $now, ':id' => $id]);
        if ($stmt->rowCount() === 0 && $this->find($id) === null) throw new \OutOfBoundsException('文章不存在。');
        return $id;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM articles WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
