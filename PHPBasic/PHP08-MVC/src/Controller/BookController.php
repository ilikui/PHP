<?php
declare(strict_types=1);

namespace Learning\Mvc\Controller;

use InvalidArgumentException;
use Learning\Mvc\Model\BookRepository;
use Throwable;

final class BookController
{
    public function __construct(private readonly BookRepository $books)
    {
    }

    public function index(): void
    {
        $keyword = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
        render('books/index', ['books' => $this->books->all($keyword), 'keyword' => $keyword]);
    }

    public function create(): void
    {
        render('books/form', [
            'book' => ['id' => '', 'title' => '', 'author' => '', 'isbn' => ''],
            'errors' => [],
            'heading' => '添加图书',
        ]);
    }

    public function edit(int $id): void
    {
        $book = $this->books->find($id);
        if ($book === null) {
            render('errors/404', [], 404);
            return;
        }
        render('books/form', ['book' => $book, 'errors' => [], 'heading' => '编辑图书']);
    }

    public function store(): void
    {
        verify_csrf();
        $data = $this->formData();
        try {
            $this->books->create($data['title'], $data['author'], $data['isbn']);
            $_SESSION['flash'] = '图书已添加。';
            redirect('/books');
        } catch (InvalidArgumentException $exception) {
            render('books/form', ['book' => $data, 'errors' => [$exception->getMessage()], 'heading' => '添加图书'], 422);
        }
    }

    public function update(int $id): void
    {
        verify_csrf();
        $data = $this->formData();
        $data['id'] = (string) $id;
        try {
            if ($this->books->find($id) === null) {
                render('errors/404', [], 404);
                return;
            }
            $this->books->update($id, $data['title'], $data['author'], $data['isbn']);
            $_SESSION['flash'] = '图书已更新。';
            redirect('/books');
        } catch (InvalidArgumentException $exception) {
            render('books/form', ['book' => $data, 'errors' => [$exception->getMessage()], 'heading' => '编辑图书'], 422);
        }
    }

    public function delete(int $id): void
    {
        verify_csrf();
        $_SESSION['flash'] = $this->books->delete($id) ? '图书已删除。' : '记录不存在或已删除。';
        redirect('/books');
    }

    /** @return array{id: string, title: string, author: string, isbn: string} */
    private function formData(): array
    {
        $read = static fn (string $key): string =>
            is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
        return ['id' => is_string($_POST['id'] ?? null) ? $_POST['id'] : '',
            'title' => $read('title'), 'author' => $read('author'), 'isbn' => $read('isbn')];
    }
}
