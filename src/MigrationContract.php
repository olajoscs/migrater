<?php

declare(strict_types=1);

namespace OlajosCs\Migrater;

/**
 * Interface Migration
 *
 * The behavior of the migration entities
 */
interface MigrationContract
{
    /**
     * Actions for building
     *
     * @return void
     */
    public function up(): void;


    /**
     * Actions for reverting
     *
     * @return void
     */
    public function down(): void;


    /**
     * Return a unique key: the class name of the migration
     *
     * @return string
     */
    public function getKey(): string;
}
