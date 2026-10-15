<?php
declare(strict_types=1);

/** 图书数据访问层：所有外部数据均通过预处理语句绑定。 */
final class BookRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS books (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(200) NOT NULL,
                author VARCHAR(120) NOT NULL,
                isbn VARCHAR(32) NOT NULL DEFAULT '',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );
    }

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, perPage: int, pages: int} */
    public function paginate(string $keyword = '', int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = min(50, max(1, $perPage));
        $where = '';
        $params = [];

        if ($keyword !== '') {
            // LIKE 通配符由用户显式输入时按通配符处理；查询值仍通过参数绑定。
            $where = ' WHERE title LIKE :keyword OR author LIKE :keyword OR isbn LIKE :keyword';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $count = $this->pdo->prepare('SELECT COUNT(*) FROM books' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $query = $this->pdo->prepare(
            'SELECT id, title, author, isbn, created_at, updated_at FROM books' .
            $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $name => $value) {
            $query->bindValue(':' . $name, $value, PDO::PARAM_STR);
        }
        $query->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        return [
            'items' => $query->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => $pages,
        ];
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, title, author, isbn, created_at, updated_at FROM books WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function create(string $title, string $author, string $isbn = ''): int
    {
        $this->validate($title, $author, $isbn);
        $statement = $this->pdo->prepare('INSERT INTO books (title, author, isbn) VALUES (:title, :author, :isbn)');
        $statement->execute(['title' => trim($title), 'author' => trim($author), 'isbn' => trim($isbn)]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $title, string $author, string $isbn = ''): bool
    {
        if ($id < 1) {
            throw new InvalidArgumentException('图书 ID 必须是正整数。');
        }
        $this->validate($title, $author, $isbn);
        $statement = $this->pdo->prepare(
            'UPDATE books SET title = :title, author = :author, isbn = :isbn, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute(['title' => trim($title), 'author' => trim($author), 'isbn' => trim($isbn), 'id' => $id]);
        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        if ($id < 1) {
            throw new InvalidArgumentException('图书 ID 必须是正整数。');
        }
        $statement = $this->pdo->prepare('DELETE FROM books WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->rowCount() > 0;
    }

    private function validate(string $title, string $author, string $isbn): void
    {
        $title = trim($title);
        $author = trim($author);
        $isbn = trim($isbn);
        if ($title === '' || mb_strlen($title) > 200) {
            throw new InvalidArgumentException('书名不能为空，且不能超过 200 个字符。');
        }
        if ($author === '' || mb_strlen($author) > 120) {
            throw new InvalidArgumentException('作者不能为空，且不能超过 120 个字符。');
        }
        if (mb_strlen($isbn) > 32) {
            throw new InvalidArgumentException('ISBN 不能超过 32 个字符。');
        }
    }
}
