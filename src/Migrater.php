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
}
