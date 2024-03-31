<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractQuoterTest;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QuoterTest extends AbstractQuoterTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QuoterProvider::tableNameParts
     */
    public function testGetTableNameParts(string $tableName, string ...$expected): void
    {
        parent::testGetTableNameParts($tableName, ...$expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QuoterProvider::columnNames
     */
    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        parent::testQuoteColumnName($columnName, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QuoterProvider::simpleColumnNames
     */
    public function testQuoteSimpleColumnName(
        string $columnName,
        string $expectedQuotedColumnName,
        string $expectedUnQuotedColumnName = null
    ): void {
        parent::testQuoteSimpleColumnName($columnName, $expectedQuotedColumnName, $expectedUnQuotedColumnName);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\QuoterProvider::simpleTableNames
     */
    public function testQuoteTableName(string $tableName, string $expected): void
    {
        parent::testQuoteTableName($tableName, $expected);
    }

    /**
     * @return void
     */
    public function testQuoteValue(): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();

        $this->assertFalse($quoter->quoteValue(false));
        $this->assertTrue($quoter->quoteValue(true));
        $this->assertNull($quoter->quoteValue(null));
        $this->assertSame("'1.1'", $quoter->quoteValue('1.1'));
        $this->assertSame("'1.1e0'", $quoter->quoteValue('1.1e0'));
        $this->assertSame("'test'", $quoter->quoteValue('test'));
        $this->assertSame("'test\'test'", $quoter->quoteValue("test'test"));
        $this->assertSame(1, $quoter->quoteValue(1));
        $this->assertSame(1.1, $quoter->quoteValue(1.1));
    }
}
