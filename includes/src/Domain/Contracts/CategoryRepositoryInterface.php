<?php
declare(strict_types=1);

namespace ReportaBlu\Domain\Contracts;

interface CategoryRepositoryInterface
{
    public function all(): array;

    public function exists(int $categoryId): bool;
}
