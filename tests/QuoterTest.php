<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Mysql\Tests\Provider\QuoterProvider;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractQuoterTest;

/**
 * @group mysql
 */
final class QuoterTest extends AbstractQuoterTest
{
    use TestTrait;

    #[DataProviderExternal(QuoterProvider::class, 'tableNameParts')]
    public function testGetTableNameParts(string $tableName, array $expected): void
    {
        parent::testGetTableNameParts($tableName, $expected);
    }

    #[DataProviderExternal(QuoterProvider::class, 'columnNames')]
    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        parent::testQuoteColumnName($columnName, $expected);
    }

    #[DataProviderExternal(QuoterProvider::class, 'simpleColumnNames')]
    public function testQuoteSimpleColumnName(
        string $columnName,
        string $expectedQuotedColumnName,
        string|null $expectedUnQuotedColumnName = null
    ): void {
        parent::testQuoteSimpleColumnName($columnName, $expectedQuotedColumnName, $expectedUnQuotedColumnName);
    }

    #[DataProviderExternal(QuoterProvider::class, 'simpleTableNames')]
    public function testQuoteTableName(string $tableName, string $expected): void
    {
        parent::testQuoteTableName($tableName, $expected);
    }

    public function testQuoteValue(): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();

        $this->assertSame("'1.1'", $quoter->quoteValue('1.1'));
        $this->assertSame("'1.1e0'", $quoter->quoteValue('1.1e0'));
        $this->assertSame("'test'", $quoter->quoteValue('test'));
        $this->assertSame("'test\'test'", $quoter->quoteValue("test'test"));

        $db->close();
    }
}
