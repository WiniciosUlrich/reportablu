<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Repository;

use PDO;
use Throwable;

abstract class BasePdoRepository
{
    /**
     * @var array<string,bool>
     */
    private static array $tableExistsCache = [];

    public function __construct(
        protected PDO $pdo
    ) {
    }

    protected function tableExists(string $tableName): bool
    {
        $cacheKey = spl_object_id($this->pdo) . ':' . $tableName;

        if (array_key_exists($cacheKey, self::$tableExistsCache)) {
            return self::$tableExistsCache[$cacheKey];
        }

        $exists = false;

        try {
            $statement = $this->pdo->prepare(
                'SELECT 1
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = :table_name
                 LIMIT 1'
            );
            $statement->execute(['table_name' => $tableName]);
            $exists = $statement->fetchColumn() !== false;
        } catch (Throwable $exception) {
            $exists = false;
        }

        self::$tableExistsCache[$cacheKey] = $exists;
        return $exists;
    }
}
