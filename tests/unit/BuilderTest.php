<?php

use MetaRush\DataAccess;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    public function testBuilder()
    {
        $builder = (new DataAccess\Builder)
            ->setAdapter('AtlasQuery') // not needed but included for unit test code coverage only
            ->setDbUser('')
            ->setDbPass('')
            ->setDsn('sqlite::memory:');

        $dal = $builder->build();

        $this->assertInstanceOf(DataAccess\DataAccess::class, $dal);
    }

    public function testBuilderWithPdo()
    {
        $pdo = new \PDO('sqlite::memory:');

        $builder = (new DataAccess\Builder)
            ->setPdo($pdo);

        $dal = $builder->build();

        $this->assertInstanceOf(DataAccess\DataAccess::class, $dal);
    }
}