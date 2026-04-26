<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Repository;

use ReportaBlu\Domain\Contracts\UserRepositoryInterface;

final class PdoUserRepository extends BasePdoRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, nome, email, password_hash, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $statement->execute(['email' => $email]);

        $user = $statement->fetch();
        return $user !== false ? $user : null;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);

        return (bool) $statement->fetch();
    }

    public function create(string $name, string $email, string $passwordHash, string $role): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (nome, email, password_hash, role)
             VALUES (:nome, :email, :password_hash, :role)'
        );

        $statement->execute([
            'nome' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
