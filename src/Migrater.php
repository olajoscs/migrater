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
     * @var object[]
     */
    private $existingMigrations = [];

    /**
     * @var DatabaseMigrater
     */
    private $databaseMigrater;


    public function __construct(DatabaseMigrater $databaseMigrater)
    {
        $this->databaseMigrater = $databaseMigrater;
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
        /** @var MigrationContract $rollbackable */
        $rollbackable = new $className($this->databaseMigrater->getPdo());

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


    /**
     * Rollback all the last migrations which were registered, then return their keys in an array
     *
     * @return string[] Keys of rollbacked migrations
     */
    public function resetAll(): array
    {
        $migrationNames = [];
        foreach ($this->reset() as $migration) {
            $migrationNames[] = $migration;
        }

        return $migrationNames;
    }


    /**
     * Rollback all the last migrations which were registered
     *
     * @return \Generator|string[]
     */
    public function reset(): \Generator
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

        foreach ($migrations as $migrationStdclass) {
            if (!isset($this->migrations[$migrationStdclass->name])) {
                continue;
            }

            $migrationClass = $migrationStdclass->name;
            $migration = new $migrationClass($this->databaseMigrater->getPdo());

            yield $this->performRollback($migration);
        }
    }


    private function bootstrap(): void
    {
        $this->databaseMigrater->createMigrationsTable();
        $this->collectExistingMigrations();
    }


    private function collectExistingMigrations(): void
    {
        foreach ($this->databaseMigrater->collectMigrations() as $row) {
            $this->existingMigrations[$row->name] = $row;
        }
    }


    private function perform(MigrationContract $migration): bool
    {
        if (!$this->shouldPerform($migration)) {
            return false;
        }

        $migration->up();
        $this->databaseMigrater->insertMigration($migration);
        $this->databaseMigrater->markAsExecuted($migration);

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


    private function markAsNotExecuted(MigrationContract $migration): void
    {
        $this->databaseMigrater->markAsNotExecuted($migration);
        $this->existingMigrations[$migration->getKey()]->executed = false;
    }
}
