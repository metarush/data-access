<?php

use MetaRush\DataAccess;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    private $dbFile;

    public function setUp(): void
    {
        $this->dbFile = __DIR__ . '/test.db';
    }

    public function testBuilder()
    {
        $builder = (new DataAccess\Builder)
            ->setAdapter('AtlasQuery') // not needed but included for unit test code coverage only
            ->setDbUser('')
            ->setDbPass('')
            ->setDsn('sqlite:' . $this->dbFile);

        $dal = $builder->build();

        $this->assertInstanceOf(DataAccess\DataAccess::class, $dal);
    }

    public function tearDown(): void
    {
        if (\file_exists($this->dbFile))
            \unlink($this->dbFile);
    }

    /**
     * Real intention of this test is for test coverage of our try/catch block in AtlasQuery adapter
     */
    public function testWrongDbDriver()
    {
        $this->expectExceptionMessageMatches('/could not find driver/');

        (new DataAccess\Builder)
            ->setDsn('wrongDbDriver:')
            ->build();
    }
}