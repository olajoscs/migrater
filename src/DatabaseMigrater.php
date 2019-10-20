<?php

declare(strict_types=1);

namespace OlajosCs\Migrater;

/**
 * Interface DatabaseMigrater
 *
 * Contract of database operations
 */
interface DatabaseMigrater
{
    /**
     * Create migration table if does not exist
     *
     * @return void
     */
    public function createMigrationsTable(): void;


    /**
     * Collect existing migrations from the migrations table
     *
     * @return \stdClass[]
     */
    public function collectMigrations(): array;


    /**
     * Insert migration into table if necessary
     *
     * @param MigrationContract $migration
     *
     * @return int the ID of the new migration
     */
    public function insertMigration(MigrationContract $migration): int;


    /**
     * Mark the migration as executed
     *
     * @param MigrationContract $migration
     *
     * @return void
     */
    public function markAsExecuted(MigrationContract $migration): void;


    /**
     * Mark the migration as not executed
     *
     * @param MigrationContract $migration
     *
     * @return void
     */
    public function markAsNotExecuted(MigrationContract $migration): void;


    /**
     * Return the PDO which is built into the migrater
     *
     * @return \PDO
     */
    public function getPdo(): \PDO;
}
