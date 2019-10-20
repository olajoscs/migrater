<?php

declare(strict_types=1);

namespace OlajosCs\Migrater\Tests\Unit;

class MigraterRollbackTest extends MigraterTest
{
//    public function test_rollback_is_working(): void
//    {
//        $migration1 = new ExampleMigration1($this->pdo);
//        $migration2 = new ExampleMigration2($this->pdo);
//
//        $this->migrater->register($migration1, $migration2);
//        $this->migrater->migrateAll();
//
//        $result = $this->migrater->rollback();
//        $this->assertEquals($result, $migration2->getKey());
//
//        $this->expectException(\PDOException::class);
//        $this->pdo->query('select * from examples_2');
//
//    }
//
//    public function test_rollback_is_working_one(): void
//    {
//        $migration1 = new ExampleMigration1($this->pdo);
//        $migration2 = new ExampleMigration2($this->pdo);
//
//        $this->migrater->register($migration1, $migration2);
//        $this->migrater->migrateAll();
//
//        $result = $this->migrater->rollback();
//        $this->assertEquals($result, $migration2->getKey());
//
//        $this->assertInstanceOf(\PDOStatement::class, $this->pdo->query('select * from examples_1'));
//    }
//
//
//    public function test_rollback_is_working_multiple(): void
//    {
//        $migration1 = new ExampleMigration1($this->pdo);
//        $migration2 = new ExampleMigration2($this->pdo);
//
//        $this->migrater->register($migration1, $migration2);
//        $this->migrater->migrateAll();
//
//        $result1 = $this->migrater->rollback();
//        $this->assertEquals($result1, $migration2->getKey());
//
//        $result2 = $this->migrater->rollback();
//        $this->assertEquals($result2, $migration1->getKey());
//
//        $this->expectException(\PDOException::class);
//        $this->pdo->query('select * from examples_1');
//    }


    public function test_only_registered_migration_is_rollbacked(): void
    {
        $this->migrater->migrateAll();
        $statement = $this->pdo->prepare('insert into migrations (name, created, executed) values (:name, :created, :executed)');
        $statement->execute([
            'name'     => ExampleMigration2::class,
            'created'  => (new \DateTime('2019-10-19'))->getTimestamp(),
            'executed' => true,
        ]);
        $this->migrater->register(new ExampleMigration1($this->pdo));
        $this->migrater->migrateAll();

        $this->assertEquals(ExampleMigration1::class, $this->migrater->rollback());
        $this->assertNull($this->migrater->rollback());
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo->exec('drop table if exists examples_1');
        $this->pdo->exec('drop table if exists examples_2');
    }
}
