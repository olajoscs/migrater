<?php

declare(strict_types=1);

namespace OlajosCs\Migrater;

/**
 * Class AbstractMigration
 *
 * Default parent for migrations which provides a PDO instance
 */
abstract class AbstractMigration implements Migration
{
    protected $pdo;


    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    public function getKey(): string
    {
        return static::class;
    }
}
