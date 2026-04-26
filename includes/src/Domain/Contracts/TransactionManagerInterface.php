<?php
declare(strict_types=1);

namespace ReportaBlu\Domain\Contracts;

interface TransactionManagerInterface
{
    /**
     * @param callable():mixed $operation
     */
    public function transactional(callable $operation): mixed;
}
