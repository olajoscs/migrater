<?php

declare(strict_types=1);

namespace OlajosCs\Migrater;

class Migrater
{
    /**
     * @var MigrationContract[]
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
     * @param MigrationContract[] $migrations
     *
     * @return void
     */
    public function register(MigrationContract ...$migrations): void
    {
        foreach ($migrations as $migration) {
            $key = $migration->getKey();

            if (isset($this->migrations[$key])) {
                throw new MigraterException('Migration already registered: ' . $key);
            }

            $this->migrations[$key] = $migration;
        }
    }


    /**
     * Rollback the last registered and executed migration
     *
     * @return string|null The key of the rollbacked migration
     */
    public function rollback(): ?string
    {
        $this->bootstrap();

        $migrations = array_filter($this->existingMigrations, function(\stdClass $migration) {
            return $migration->executed;
        });

        uasort($migrations, function (\stdClass $migration1, \stdClass $migration2) {
            $createdDifference = $migration2->created <=> $migration1->created;

            if ($createdDifference !== 0) {
                return $createdDifference;
            }

            return $migration2->id <=> $migration1;
        });

        $rollbackableStdclass = array_shift($migrations);

        if ($rollbackableStdclass === null) {
            return null;
        }

        $className = $rollbackableStdclass->name;
        $rollbackable = new $className($this->pdo);

        if (!isset($this->migrations[$rollbackable->getKey()])) {
            return null;
        }

        return $this->performRollback($rollbackable);
    }


    /**
     * Return all the registered migration
     *
     * @return MigrationContract[]
     */
    public function getMigrations(): array
    {
        return $this->migrations;
    }


    /**
     * Migrate the migrations and return the keys in an array
     *
     * @return string[] Keys of the migrated migrations
     */
    public function migrateAll(): array
    {
        $migratedNames = [];
        foreach ($this->migrate() as $migrated) {
            $migratedNames[] = $migrated;
        }

        return $migratedNames;
    }


    /**
     * Migrate the missing migrations
     *
     * @return \Generator|string[] The performed migrations
     */
    public function migrate(): \Generator
    {
        $this->bootstrap();

        $migrations = array_filter($this->existingMigrations, function(\stdClass $migration) {
            return $migration->executed === false;
        });

        uasort($migrations, function (\stdClass $migration1, \stdClass $migration2) {
            $createdDifference = $migration2->created <=> $migration1->created;

            if ($createdDifference !== 0) {
                return $createdDifference;
            }

            return $migration2->id <=> $migration1;
        });

        foreach ($this->migrations as $migration) {
            if ($this->perform($migration)) {
                yield $migration->getKey();
            }
        }
    }


    private function bootstrap(): void
    {
        try {
            $result = $this->pdo->query(sprintf('select 1 from %s', $this->migrationTableName));
        } catch (\PDOException $exception) {
            if ($exception->getCode() === '42S02') {
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
                'select * from %s order by created desc, id desc',
                $this->migrationTableName
            )
        )->fetchAll(\PDO::FETCH_OBJ);

        foreach ($rows as $row) {
            $this->existingMigrations[$row->name] = $row;
        }
    }


    private function perform(MigrationContract $migration): bool
    {
        if (!$this->shouldPerform($migration)) {
            return false;
        }

        $migration->up();
        $this->insertIfNotExists($migration);
        $this->markAsExecuted($migration);

        return true;
    }


    private function performRollback(MigrationContract $migration): string
    {
        $migration->down();
        $this->markAsNotExecuted($migration);

        return $migration->getKey();
    }


    private function shouldPerform(MigrationContract $migration): bool
    {
        if (!isset($this->existingMigrations[$migration->getKey()])) {
            return true;
        }

        return !$this->existingMigrations[$migration->getKey()]->executed;
    }


    private function insertIfNotExists(MigrationContract $migration): void
    {
        $statement = $this->pdo->prepare(
            sprintf(
                'select id 
                from %s 
                where name = :name',
                $this->migrationTableName
            )
        );

        $statement->execute([
            'name' => $migration->getKey(),
        ]);

        $exists = $statement->fetchAll();

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


    private function markAsExecuted(MigrationContract $migration): void
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


    private function markAsNotExecuted(MigrationContract $migration): void
    {
        $this->pdo->prepare(
            sprintf(
                'update %s
                set executed = false 
                where name = :name',
                $this->migrationTableName
            )
        )->execute([
            'name' => $migration->getKey(),
        ]);

        $this->existingMigrations[$migration->getKey()]->executed = false;
    }
}
