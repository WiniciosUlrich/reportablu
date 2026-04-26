<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Database;

use PDO;
use ReportaBlu\Domain\Contracts\TransactionManagerInterface;
use Throwable;

final class PdoTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function transactional(callable $operation): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $operation();
        }

        $this->pdo->beginTransaction();

        try {
            $result = $operation();
            $this->pdo->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
