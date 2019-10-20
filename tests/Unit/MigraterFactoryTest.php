<?php

declare(strict_types=1);

namespace OlajosCs\Migrater\Tests\Unit;

use OlajosCs\Migrater\Migrater;
use OlajosCs\Migrater\MigraterException;
use OlajosCs\Migrater\MigraterFactory;

/**
 * Class MigraterFactoryTest
 *
 *
 */
class MigraterFactoryTest extends MigraterTest
{
    public function test_existing_implementation_mysql(): void
    {
        $factory = new MigraterFactory();
        $migrater = $factory->create($this->pdo, 'tableName');

        $this->assertInstanceOf(Migrater::class, $migrater);
    }


    public function test_not_existing_implementation_sqlite(): void
    {
        $factory = new MigraterFactory();
        $pdo = new \PDO('sqlite::memory:');

        $this->expectException(MigraterException::class);
        $factory->create($pdo, 'table');
    }
}
