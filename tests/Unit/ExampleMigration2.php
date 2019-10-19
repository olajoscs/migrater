<?php

declare(strict_types=1);

namespace OlajosCs\Migrater\Tests\Unit;

use OlajosCs\Migrater\AbstractMigration;

class ExampleMigration2 extends AbstractMigration
{
    public function up(): void
    {
        $this->pdo->exec(
            'create table examples_2 (
                id int primary key auto_increment, 
                name varchar(64)
            )'
        );
    }


    public function down(): void
    {
        $this->pdo->exec(
            'drop table examples_2'
        );
    }
}
