<?php

declare(strict_types=1);

namespace OlajosCs\Migrater\Tests\Unit;

class MigraterResetTest extends MigraterTest
{
    public function test_reset_returns_migration_names(): void
    {
        $this->migrater->register(
            new ExampleMigration1($this->pdo),
            new ExampleMigration2($this->pdo)
        );

        $this->migrater->migrateAll();

        $results = $this->migrater->resetAll();
        $expected = [
            ExampleMigration2::class,
            ExampleMigration1::class,
        ];

        $this->assertEquals($expected, $results);
    }


    public function test_reset_is_done_1(): void
    {
        $this->migrater->register(
            new ExampleMigration1($this->pdo),
            new ExampleMigration2($this->pdo)
        );

        $this->migrater->migrateAll();
        $this->migrater->resetAll();

        $this->expectException(\PDOException::class);
        $this->pdo->exec('select 1 from examples_1');
    }


    public function test_reset_is_done_2(): void
    {
        $this->migrater->register(
            new ExampleMigration1($this->pdo),
            new ExampleMigration2($this->pdo)
        );

        $this->migrater->migrateAll();
        $this->migrater->resetAll();

        $this->expectException(\PDOException::class);
        $this->pdo->exec('select 1 from examples_2');
    }


    public function test_reset_only_registered_migrations(): void
    {
        $this->migrater->migrateAll();

        $statement = $this->pdo->prepare('insert into migrations (name, created, executed) values (:name, :created, :executed)');
        $statement->execute([
            'name'     => ExampleMigration2::class,
            'created'  => (new \DateTime('2019-10-19'))->getTimestamp(),
            'executed' => true,
        ]);

        $this->migrater->register(
            new ExampleMigration1($this->pdo)
        );

        $this->migrater->migrateAll();
        $result = $this->migrater->resetAll();

        $this->assertEquals([ExampleMigration1::class], $result);
    }
}
