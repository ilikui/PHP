<?php
declare(strict_types=1);

final class BookStore
{
    public function __construct(private PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            author TEXT NOT NULL,
            isbn TEXT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )');
    }

    public function list(string $q = '', int $page = 1, int $perPage = 10, string $sort = 'id', string $direction = 'desc'): array
    {
        $page = max(1, $page);
        $perPage = min(50, max(1, $perPage));
        $allowedSorts = ['id', 'title', 'author', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'id';
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
        $where = $q === '' ? '' : ' WHERE title LIKE :q OR author LIKE :q OR isbn LIKE :q';
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM books' . $where);
        if ($q !== '') $count->execute(['q' => '%' . $q . '%']); else $count->execute();
        $total = (int) $count->fetchColumn();
        $sql = 'SELECT id, title, author, isbn, created_at, updated_at FROM books' . $where . ' ORDER BY ' . $sort . ' ' . $direction . ' LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        if ($q !== '') $stmt->bindValue(':q', '%' . $q . '%');
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();
        return ['data' => $stmt->fetchAll(), 'page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => (int) ceil($total / $perPage)];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, author, isbn, created_at, updated_at FROM books WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function create(array $input): array
    {
        $data = $this->validate($input);
        $now = gmdate('c');
        $stmt = $this->pdo->prepare('INSERT INTO books (title, author, isbn, created_at, updated_at) VALUES (:title, :author, :isbn, :created_at, :updated_at)');
        $stmt->execute($data + ['created_at' => $now, 'updated_at' => $now]);
        return $this->find((int) $this->pdo->lastInsertId()) ?? throw new RuntimeException('Created book could not be loaded.');
    }

    public function update(int $id, array $input): ?array
    {
        if ($this->find($id) === null) return null;
        $data = $this->validate($input);
        $stmt = $this->pdo->prepare('UPDATE books SET title=:title, author=:author, isbn=:isbn, updated_at=:updated_at WHERE id=:id');
        $stmt->execute($data + ['updated_at' => gmdate('c'), 'id' => $id]);
        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM books WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function validate(array $input): array
    {
        $title = trim(is_string($input['title'] ?? null) ? $input['title'] : '');
        $author = trim(is_string($input['author'] ?? null) ? $input['author'] : '');
        $isbn = trim(is_string($input['isbn'] ?? null) ? $input['isbn'] : '');
        if ($title === '' || mb_strlen($title) > 200) throw new InvalidArgumentException('title is required and must be at most 200 characters.');
        if ($author === '' || mb_strlen($author) > 120) throw new InvalidArgumentException('author is required and must be at most 120 characters.');
        if (mb_strlen($isbn) > 32) throw new InvalidArgumentException('isbn must be at most 32 characters.');
        return ['title' => $title, 'author' => $author, 'isbn' => $isbn === '' ? null : $isbn];
    }
}
