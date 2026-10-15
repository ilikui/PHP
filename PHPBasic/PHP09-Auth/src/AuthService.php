<?php
declare(strict_types=1);

final class AuthService
{
    public function __construct(private UserRepository $users) {}

    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->users->findByUsername(trim($username));
        // Perform a password hash check even when the account does not exist, reducing timing clues.
        $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (!password_verify($password, $hash) || $user === null) {
            return null;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        return [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'role' => (string) $user['role'],
        ];
    }
}
