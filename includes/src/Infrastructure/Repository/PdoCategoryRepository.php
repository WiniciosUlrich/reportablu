<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Repository;

use ReportaBlu\Domain\Contracts\CategoryRepositoryInterface;

final class PdoCategoryRepository extends BasePdoRepository implements CategoryRepositoryInterface
{
    public function all(): array
    {
        $statement = $this->pdo->query('SELECT id, nome FROM categories ORDER BY nome');
        return $statement->fetchAll() ?: [];
    }

    public function exists(int $categoryId): bool
    {
        $statement = $this->pdo->prepare('SELECT id FROM categories WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $categoryId]);

        return (bool) $statement->fetch();
    }
}
