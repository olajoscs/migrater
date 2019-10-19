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


    public function test_migration_is_not_performed_if_it_was_executed(): void
    {
        $migration1 = new ExampleMigration1($this->pdo);
        $this->pdo->exec(
            'create table migrations (
              id int primary key auto_increment,
              name varchar(255) not null,
              created timestamp not null,
              executed bool default false
            )'
        );
        $migration1->up();
        $statement = $this->pdo->prepare(
            'insert into migrations (name, created, executed)
            values (:name, now(), true)',
            ['name' => $migration1->getKey()]
        );
        $statement->execute(['name' => $migration1->getKey()]);

        $this->migrater->register($migration1);
        $migrated = $this->migrater->migrate();

        $this->assertCount(0, $migrated);
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo->query('drop table if exists examples_1');
    }
}
