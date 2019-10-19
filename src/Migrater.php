<?php

declare(strict_types=1);

namespace OlajosCs\Migrater;

class Migrater
{
    /**
     * @var Migration[]
     */
    private $migrations = [];

    /**
     * @var string
     */
    private $migrationTableName;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var object[]
     */
    private $existingMigrations = [];


    public function __construct(string $migrationTableName, \PDO $pdo)
    {
        $this->migrationTableName = $migrationTableName;
        $this->pdo = $pdo;
    }


    /**
     * Register a migration
     *
     * @param Migration $migration
     *
     * @return void
     */
    public function register(Migration $migration): void
    {
        $key = $migration->getKey();

        if (isset($this->migrations[$key])) {
            throw new MigraterException('Migration already registered: ' . $key);
        }

        $this->migrations[$key] = $migration;
    }


    /**
     * Return all the registered migration
     *
     * @return Migration[]
     */
    public function getMigrations(): array
    {
        return $this->migrations;
    }


    /**
     * Migrate the missing migrations
     *
     * @return string[] The performed migrations
     */
    public function migrate(): array
    {
        $this->bootstrap();

        $performed = [];
        foreach ($this->migrations as $migration) {
            if ($this->perform($migration)) {
                $performed[] = $migration->getKey();
            }
        }

        return $performed;
    }


    private function bootstrap(): void
    {
        try {
            $result = $this->pdo->query(sprintf('select 1 from %s', $this->migrationTableName));
        } catch (\PDOException $exception) {
            if ((string)$exception->getCode() === '42S02') {
                $result = false;
            } else {
                throw $exception;
            }
        }

        if ($result === false) {
            $this->createMigrationTable();
        }

        $this->collectExistingMigrations();
    }


    private function createMigrationTable(): void
    {
        $this->pdo->exec(
            sprintf(
                'create table %s (
                  id int primary key auto_increment,
                  name varchar(255) not null,
                  created timestamp not null,
                  executed bool default false
                )',
                $this->migrationTableName
            )
        );
    }


    private function collectExistingMigrations(): void
    {
        $rows = $this->pdo->query(
            sprintf(
                'select * from %s',
                $this->migrationTableName
            )
        )->fetchAll(\PDO::FETCH_OBJ);

        foreach ($rows as $row) {
            $this->existingMigrations[$row->name] = $row;
        }
    }


    private function perform(Migration $migration): bool
    {
        if (!$this->shouldPerform($migration)) {
            return false;
        }

        $migration->up();
        $this->insertIfNotExists($migration);
        $this->markAsExecuted($migration);

        return true;
    }


    private function shouldPerform(Migration $migration): bool
    {
        if (!isset($this->existingMigrations[$migration->getKey()])) {
            return true;
        }

        return !$this->existingMigrations[$migration->getKey()]->executed;
    }


    private function insertIfNotExists(Migration $migration): void
    {
        $exists = $this->pdo->prepare(
            sprintf(
                'select id 
                from %s 
                where name = :name',
                $this->migrationTableName
            ),
            [
                'name' => $migration->getKey(),
            ]
        )->fetchAll();

        if (\count($exists) === 0) {
            $this->pdo->prepare(
                sprintf(
                    'insert into %s (name, created, executed)
                     values (:name, now(), false)',
                    $this->migrationTableName
                )
            )->execute([
                'name' => $migration->getKey(),
            ]);
        }
    }


    private function markAsExecuted(Migration $migration): void
    {
        $this->pdo->prepare(
            sprintf(
                'update %s
                set executed = true 
                where name = :name',
                $this->migrationTableName
            )
        )->execute([
            'name' => $migration->getKey(),
        ]);
    }
}
