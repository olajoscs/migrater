<?php

declare(strict_types=1);

namespace OlajosCs\Migrater\Tests\Unit;

use OlajosCs\Migrater\Migrater;
use PHPUnit\Framework\TestCase;

abstract class MigraterTest extends TestCase
{
    protected $pdo;

    /**
     * @var Migrater
     */
    protected $migrater;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // TODO: Handle first run more elegantly
        if (getenv('DB_TYPE') === false) {
            $this->readEnv();
        }

        $this->pdo = $this->createPdo();
    }


    private function readEnv(): void
    {
        $file = \fopen(__DIR__ .'/../../.env', 'rb');

        while ($row = \fgets($file)) {
            \putenv(\trim($row));
        }
    }


    private function createPdo(): \PDO
    {
        if (getenv('DB_HOST') === ':memory:') {
            $dsn = sprintf(
                '%s:%s',
                getenv('DB_TYPE'),
                getenv('DB_HOST')
            );
        } else {
            $dsn = sprintf(
                '%s:host=%s;dbname=%s',
                getenv('DB_TYPE'),
                getenv('DB_HOST'),
                getenv('DB_DATABASE')
            );
        }

        $pdo = new \PDO($dsn, getenv('DB_USER'), getenv('DB_PASSWORD'));

        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }


    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo->exec('drop table if exists migrations');
        $this->migrater = new Migrater('migrations', $this->pdo);
    }


    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
