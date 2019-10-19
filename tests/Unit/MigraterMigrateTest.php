<?php

declare(strict_types=1);

namespace OlajosCs\Migrater\Tests\Unit;

class MigraterMigrateTest extends MigraterTest
{
    public function test_migration_is_done(): void
    {
        $migration1 = new ExampleMigration1($this->pdo);

        $this->migrater->register($migration1);
        $this->migrater->migrate();

        $this->assertCount(0, $this->pdo->query('select * from examples_1'));
    }


    public function test_migration_is_inserted(): void
    {
        $migration1 = new ExampleMigration1($this->pdo);
        $this->migrater->register($migration1);
        $this->migrater->migrate();

        $this->assertCount(1, $this->pdo->query('select * from migrations')->fetchAll());
    }


    public function test_migration_is_marked(): void
    {
        $migration1 = new ExampleMigration1($this->pdo);
        $this->migrater->register($migration1);
        $this->migrater->migrate();

        $statement = $this->pdo->prepare('select * from migrations where name = :name');
        $statement->execute(['name' => $migration1->getKey()]);
        $rows = $statement->fetchAll(\PDO::FETCH_OBJ);
        $row = reset($rows);

        $this->assertTrue((bool)$row->executed);
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo->query('drop table if exists examples_1');
    }
}
