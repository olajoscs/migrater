<?php

declare(strict_types=1);

namespace OlajosCs\Migrater\Tests\Unit;

class MigrationTableCreationTest extends MigraterTest
{
    public function test_migrations_table_created(): void
    {
        $this->migrater->migrateAll();

        $this->assertEmpty($this->pdo->query('select * from migrations')->fetchAll());
    }
}
