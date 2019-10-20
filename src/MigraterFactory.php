<?php

declare(strict_types=1);

namespace OlajosCs\Migrater;

/**
 * Class DatabaseMigraterFactory
 *
 * Craetes migrater with the corresponging DatabaseMigrater object based on pdo
 */
class MigraterFactory
{
    /**
     * Craete migrater with the corresponging DatabaseMigrater object based on pdo
     *
     * @param \PDO   $pdo
     * @param string $tableName
     *
     * @return Migrater
     * @throws MigraterException If required database implementation is missing
     */
    public function create(\PDO $pdo, string $tableName): Migrater
    {
        $databaseMigrater = $this->createDatabaseMigrater($pdo, $tableName);

        return new Migrater($databaseMigrater);
    }


    private function createDatabaseMigrater(\PDO $pdo, string $tableName): DatabaseMigrater
    {
        $client = $pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION);

        if (strpos($client, 'mysql') !== false) {
            return new MysqlMigrater($pdo, $tableName);
        }

        throw new MigraterException('Database type not implemented');
    }
}
