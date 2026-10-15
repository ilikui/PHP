<?php
declare(strict_types=1);

namespace Learning\Mvc\Model;

use InvalidArgumentException;
use PDO;

final class BookRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(200) NOT NULL,
            author VARCHAR(120) NOT NULL,
            isbn VARCHAR(32) NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    }

    /** @return list<array<string, mixed>> */
    public function all(string $keyword = ''): array
    {
        if ($keyword === '') {
            return $this->pdo->query('SELECT id, title, author, isbn, created_at FROM books ORDER BY id DESC')->fetchAll();
        }
        $statement = $this->pdo->prepare(
            'SELECT id, title, author, isbn, created_at FROM books
             WHERE title LIKE :keyword OR author LIKE :keyword OR isbn LIKE :keyword
             ORDER BY id DESC'
        );
        $statement->execute(['keyword' => '%' . $keyword . '%']);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, title, author, isbn, created_at FROM books WHERE id = :id');
        $statement->execute(['id' => $id]);
        $book = $statement->fetch();
        return $book === false ? null : $book;
    }

    public function create(string $title, string $author, string $isbn): int
    {
        $data = $this->validate($title, $author, $isbn);
        $statement = $this->pdo->prepare('INSERT INTO books (title, author, isbn) VALUES (:title, :author, :isbn)');
        $statement->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $title, string $author, string $isbn): bool
    {
        if ($id < 1) {
            throw new InvalidArgumentException('无效的图书 ID。');
        }
        $data = $this->validate($title, $author, $isbn);
        $data['id'] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE books SET title = :title, author = :author, isbn = :isbn WHERE id = :id'
        );
        $statement->execute($data);
        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        if ($id < 1) {
            throw new InvalidArgumentException('无效的图书 ID。');
        }
        $statement = $this->pdo->prepare('DELETE FROM books WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->rowCount() > 0;
    }

    /** @return array{title: string, author: string, isbn: string} */
    private function validate(string $title, string $author, string $isbn): array
    {
        $title = trim($title);
        $author = trim($author);
        $isbn = trim($isbn);
        if ($title === '' || mb_strlen($title) > 200) {
            throw new InvalidArgumentException('书名必填，最多 200 个字符。');
        }
        if ($author === '' || mb_strlen($author) > 120) {
            throw new InvalidArgumentException('作者必填，最多 120 个字符。');
        }
        if (mb_strlen($isbn) > 32) {
            throw new InvalidArgumentException('ISBN 最多 32 个字符。');
        }
        return ['title' => $title, 'author' => $author, 'isbn' => $isbn];
    }
}
