<?php

declare(strict_types=1);

namespace OlajosCs\Migrater;

/**
 * Class MysqlMigrater
 *
 * Database operation in mysql database
 */
class MysqlMigrater implements DatabaseMigrater
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var string
     */
    private $tableName;


    public function __construct(\PDO $pdo, string $tableName)
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }


    public function createMigrationsTable(): void
    {
        if ($this->existsMigrationTable() === true) {
            return;
        }

        $this->pdo->exec(
            sprintf(
                'create table %s (
                  id int primary key auto_increment,
                  name varchar(255) not null,
                  created timestamp not null,
                  executed bool default false
                )',
                $this->tableName
            )
        );
    }


    public function collectMigrations(): array
    {
        return $this->pdo->query(
            sprintf(
                'select * from %s order by created desc, id desc',
                $this->tableName
            )
        )->fetchAll($this->pdo::FETCH_OBJ);
    }


    public function insertMigration(MigrationContract $migration): int
    {
        $statement = $this->pdo->prepare(
            sprintf(
                'select id 
                from %s 
                where name = :name',
                $this->tableName
            )
        );

        $statement->execute([
            'name' => $migration->getKey(),
        ]);

        $exists = $statement->fetchAll($this->pdo::FETCH_OBJ);

        if (\count($exists) > 0) {
            return (int)reset($exists)->id;
        }

        $this->pdo->prepare(
            sprintf(
                'insert into %s (name, created, executed)
                 values (:name, now(), false)',
                $this->tableName
            )
        )->execute([
            'name' => $migration->getKey(),
        ]);

        return (int)$this->pdo->lastInsertId();
    }


    public function markAsExecuted(MigrationContract $migration): void
    {
        $this->pdo->prepare(
            sprintf(
                'update %s
                set executed = true 
                where name = :name',
                $this->tableName
            )
        )->execute([
            'name' => $migration->getKey(),
        ]);
    }


    public function markAsNotExecuted(MigrationContract $migration): void
    {
        $this->pdo->prepare(
            sprintf(
                'update %s
                set executed = false 
                where name = :name',
                $this->tableName
            )
        )->execute([
            'name' => $migration->getKey(),
        ]);
    }


    public function getPdo(): \PDO
    {
        return $this->pdo;
    }


    private function existsMigrationTable(): bool
    {
        try {
            $result = $this->pdo->query(sprintf('select 1 from %s', $this->tableName));
        } catch (\PDOException $exception) {
            if ($exception->getCode() === '42S02') {
                $result = false;
            } else {
                throw $exception;
            }
        }

        return (bool)$result;
    }
}
