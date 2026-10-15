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
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            body TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft', 'published')),
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        // Backward-compatible migrations for databases created by earlier lessons.
        $columns = array_column($this->pdo->query('PRAGMA table_info(articles)')->fetchAll(), 'name');
        if (!in_array('category', $columns, true)) $this->pdo->exec("ALTER TABLE articles ADD COLUMN category TEXT NOT NULL DEFAULT ''");
        if (!in_array('tags', $columns, true)) $this->pdo->exec("ALTER TABLE articles ADD COLUMN tags TEXT NOT NULL DEFAULT ''");
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_articles_status_category_updated ON articles(status, category, updated_at)');
    }

    /** @return list<array<string, mixed>> */
    public function published(int $limit = 20, int $offset = 0, string $category = ''): array
    {
        $sql = "SELECT id, title, body, status, category, tags, created_at, updated_at FROM articles WHERE status = 'published'";
        if ($category !== '') $sql .= ' AND category = :category';
        $sql .= ' ORDER BY updated_at DESC, id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        if ($category !== '') $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, min($limit, 100)), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function searchPublished(string $query = '', int $limit = 10, int $offset = 0, string $category = ''): array
    {
        $query = trim($query);
        $sql = "SELECT id, title, body, status, category, tags, created_at, updated_at FROM articles WHERE status = 'published'";
        if ($query !== '') $sql .= ' AND (instr(lower(title), lower(:title_query)) > 0 OR instr(lower(body), lower(:body_query)) > 0 OR instr(lower(tags), lower(:tag_query)) > 0)';
        if ($category !== '') $sql .= ' AND category = :category';
        $sql .= ' ORDER BY updated_at DESC, id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        if ($query !== '') {
            $stmt->bindValue(':title_query', $query, PDO::PARAM_STR);
            $stmt->bindValue(':body_query', $query, PDO::PARAM_STR);
            $stmt->bindValue(':tag_query', $query, PDO::PARAM_STR);
        }
        if ($category !== '') $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, min($limit, 100)), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function publishedCount(string $query = '', string $category = ''): int
    {
        $query = trim($query);
        $sql = "SELECT COUNT(*) FROM articles WHERE status = 'published'";
        if ($query !== '') $sql .= ' AND (instr(lower(title), lower(:title_query)) > 0 OR instr(lower(body), lower(:body_query)) > 0 OR instr(lower(tags), lower(:tag_query)) > 0)';
        if ($category !== '') $sql .= ' AND category = :category';
        $stmt = $this->pdo->prepare($sql);
        if ($query !== '') {
            $stmt->bindValue(':title_query', $query, PDO::PARAM_STR);
            $stmt->bindValue(':body_query', $query, PDO::PARAM_STR);
            $stmt->bindValue(':tag_query', $query, PDO::PARAM_STR);
        }
        if ($category !== '') $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** @return list<string> */
    public function categories(): array
    {
        return $this->pdo->query("SELECT DISTINCT category FROM articles WHERE status = 'published' AND category <> '' ORDER BY category COLLATE NOCASE")->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return list<array<string, mixed>> */
    public function all(string $query = '', string $status = '', string $category = ''): array
    {
        $query = trim($query);
        if (!in_array($status, ['', 'draft', 'published'], true)) throw new \InvalidArgumentException('文章状态筛选条件无效。');
        $sql = 'SELECT id, title, body, status, category, tags, created_at, updated_at FROM articles WHERE 1 = 1';
        if ($status !== '') $sql .= ' AND status = :status';
        if ($category !== '') $sql .= ' AND category = :category';
        if ($query !== '') $sql .= ' AND (instr(lower(title), lower(:title_query)) > 0 OR instr(lower(body), lower(:body_query)) > 0 OR instr(lower(tags), lower(:tag_query)) > 0)';
        $sql .= ' ORDER BY updated_at DESC, id DESC';
        $stmt = $this->pdo->prepare($sql);
        if ($status !== '') $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        if ($category !== '') $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        if ($query !== '') {
            $stmt->bindValue(':title_query', $query, PDO::PARAM_STR);
            $stmt->bindValue(':body_query', $query, PDO::PARAM_STR);
            $stmt->bindValue(':tag_query', $query, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, body, status, category, tags, created_at, updated_at FROM articles WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return list<string> */
    private function normalizeTags(string $tags): array
    {
        $items = preg_split('/[,，、;；\n]+/u', $tags) ?: [];
        $result = [];
        foreach ($items as $item) {
            $tag = trim($item);
            if ($tag === '') continue;
            if (mb_strlen($tag) > 30) throw new \InvalidArgumentException('单个标签不能超过 30 个字符。');
            $key = mb_strtolower($tag);
            if (!isset($result[$key])) $result[$key] = $tag;
        }
        if (count($result) > 10) throw new \InvalidArgumentException('每篇文章最多添加 10 个标签。');
        return array_values($result);
    }

    public function save(?int $id, string $title, string $body, string $status, string $category = '', string $tags = ''): int
    {
        $title = trim($title);
        $body = trim($body);
        $category = trim($category);
        $normalizedTags = $this->normalizeTags($tags);
        if ($title === '' || mb_strlen($title) > 160) throw new \InvalidArgumentException('标题必须为 1–160 个字符。');
        if ($body === '' || mb_strlen($body) > 50000) throw new \InvalidArgumentException('正文不能为空且不能超过 50000 个字符。');
        if (!in_array($status, ['draft', 'published'], true)) throw new \InvalidArgumentException('文章状态无效。');
        if (mb_strlen($category) > 40) throw new \InvalidArgumentException('分类名称不能超过 40 个字符。');
        $tagsText = implode(', ', $normalizedTags);
        $now = gmdate('c');
        if ($id === null) {
            $stmt = $this->pdo->prepare('INSERT INTO articles(title, body, status, category, tags, created_at, updated_at) VALUES(:title, :body, :status, :category, :tags, :created, :updated)');
            $stmt->execute([':title' => $title, ':body' => $body, ':status' => $status, ':category' => $category, ':tags' => $tagsText, ':created' => $now, ':updated' => $now]);
            return (int) $this->pdo->lastInsertId();
        }
        $stmt = $this->pdo->prepare('UPDATE articles SET title=:title, body=:body, status=:status, category=:category, tags=:tags, updated_at=:updated WHERE id=:id');
        $stmt->execute([':title' => $title, ':body' => $body, ':status' => $status, ':category' => $category, ':tags' => $tagsText, ':updated' => $now, ':id' => $id]);
        if ($stmt->rowCount() === 0 && $this->find($id) === null) throw new \OutOfBoundsException('文章不存在。');
        return $id;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM articles WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
