<?php

declare(strict_types=1);

namespace OlajosCs\Migrater\Tests\Unit;

use OlajosCs\Migrater\MigraterException;

class MigraterRegisterTest extends MigraterTest
{
    public function test_migrate_register(): void
    {
        $migration1 = new ExampleMigration1($this->pdo);
        $migration2 = new ExampleMigration2($this->pdo);

        $this->migrater->register($migration1);
        $this->migrater->register($migration2);

        $this->assertCount(2, $this->migrater->getMigrations());
    }


    public function test_one_migration_is_allowed(): void
    {
        $migration1 = new ExampleMigration1($this->pdo);
        $migration2 = new ExampleMigration1($this->pdo);

        $this->migrater->register($migration1);

        $this->expectException(MigraterException::class);
        $this->migrater->register($migration2);
    }
}
