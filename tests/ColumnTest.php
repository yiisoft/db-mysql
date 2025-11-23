<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mysql\Column\ColumnBuilder;
use Yiisoft\Db\Mysql\Column\StringColumn;
use Yiisoft\Db\Mysql\Tests\Provider\ColumnProvider;
use Yiisoft\Db\Mysql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Mysql\Tests\Support\TestConnection;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Tests\Common\CommonColumnTest;

use function iterator_to_array;
use function str_repeat;

/**
 * @group mysql
 */
final class ColumnTest extends CommonColumnTest
{
    use IntegrationTestTrait;

    public function testSelectWithPhpTypecasting(): void
    {
        $db = $this->getSharedConnection();

        $sql = <<<SQL
            SELECT
                null AS `null`,
                1 AS `1`,
                2.5 AS `2.5`,
                true AS `true`,
                false AS `false`,
                'string' AS `string`
            SQL;

        $expected = [
            'null' => null,
            1 => 1,
            '2.5' => 2.5,
            'true' => 1,
            'false' => 0,
            'string' => 'string',
        ];

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->queryOne();

        $this->assertSame($expected, $result);

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->queryAll();

        $this->assertSame([$expected], $result);

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->query();

        $this->assertSame([$expected], iterator_to_array($result));

        $result = $db->createCommand('SELECT 2.5')
            ->withPhpTypecasting()
            ->queryScalar();

        $this->assertSame(2.5, $result);

        $result = $db->createCommand('SELECT 2.5 UNION SELECT 3.3')
            ->withPhpTypecasting()
            ->queryColumn();

        $this->assertSame([2.5, 3.3], $result);

        $db->close();
    }

    #[DataProviderExternal(ColumnProvider::class, 'bigIntValue')]
    public function testColumnBigInt(string $bigint): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $command = $db->createCommand();
        $command->insert('negative_default_values', ['bigint_col' => $bigint]);
        $command->execute();
        $query = (new Query($db))->from('negative_default_values')->one();

        $this->assertSame($bigint, $query['bigint_col']);

        $db->close();
    }

    public function testColumnInstance(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertInstanceOf(IntegerColumn::class, $tableSchema->getColumn('int_col'));
        $this->assertInstanceOf(StringColumn::class, $tableSchema->getColumn('char_col'));
        $this->assertInstanceOf(DoubleColumn::class, $tableSchema->getColumn('float_col'));
        $this->assertInstanceOf(BinaryColumn::class, $tableSchema->getColumn('blob_col'));
        $this->assertInstanceOf(BooleanColumn::class, $tableSchema->getColumn('bool_col'));
        $this->assertInstanceOf(JsonColumn::class, $tableSchema->getColumn('json_col'));

        $db->close();
    }

    public function testLongtextType(): void
    {
        $db = $this->getSharedConnection();
        $command = $db->createCommand();

        try {
            $command->dropTable('text_type')->execute();
        } catch (Exception) {
        }

        $command->createTable(
            'text_type',
            [
                'tinytext' => ColumnBuilder::text(63),
                'text' => ColumnBuilder::text(16_383),
                'mediumtext' => ColumnBuilder::text(4_194_303),
                'longtext' => ColumnBuilder::text(4_294_967_295),
            ],
        )->execute();

        $table = $db->getSchema()->getTableSchema('text_type');

        $this->assertSame('tinytext', $table->getColumn('tinytext')->getDbType());
        $this->assertSame('text', $table->getColumn('text')->getDbType());
        $this->assertSame('mediumtext', $table->getColumn('mediumtext')->getDbType());
        $this->assertSame('longtext', $table->getColumn('longtext')->getDbType());

        $db->close();
    }

    public function testTimestampColumnOnDifferentTimezones(): void
    {
        $db = $this->createConnection();
        $schema = $db->getSchema();
        $command = $db->createCommand();
        $tableName = 'timestamp_column_test';

        $command->setSql("SET @@session.time_zone = '+03:00'")->execute();

        $this->assertSame('+03:00', $db->getServerInfo()->getTimezone());

        $phpTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        if ($schema->hasTable($tableName)) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable(
            $tableName,
            [
                'timestamp_col' => ColumnBuilder::timestamp(),
                'datetime_col' => ColumnBuilder::datetime(),
            ],
        )->execute();

        $command->insert($tableName, [
            'timestamp_col' => new DateTimeImmutable('2025-04-19 14:11:35'),
            'datetime_col' => new DateTimeImmutable('2025-04-19 14:11:35'),
        ])->execute();

        $command->setSql("SET @@session.time_zone = '+04:00'")->execute();

        $this->assertSame('+04:00', $db->getServerInfo()->getTimezone(true));

        $columns = $schema->getTableSchema($tableName, true)->getColumns();
        $query = (new Query($db))->from($tableName);

        $result = $query->one();

        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35'), $columns['timestamp_col']->phpTypecast($result['timestamp_col']));
        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35'), $columns['datetime_col']->phpTypecast($result['datetime_col']));

        $result = $query->withTypecasting()->one();

        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35'), $result['timestamp_col']);
        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35'), $result['datetime_col']);

        date_default_timezone_set($phpTimezone);

        $db->close();
    }

    #[DataProviderExternal(ColumnProvider::class, 'predefinedTypes')]
    public function testPredefinedType(string $className, string $type)
    {
        parent::testPredefinedType($className, $type);
    }

    #[DataProviderExternal(ColumnProvider::class, 'dbTypecastColumns')]
    public function testDbTypecastColumns(ColumnInterface $column, array $values)
    {
        parent::testDbTypecastColumns($column, $values);
    }

    #[DataProviderExternal(ColumnProvider::class, 'phpTypecastColumns')]
    public function testPhpTypecastColumns(ColumnInterface $column, array $values)
    {
        parent::testPhpTypecastColumns($column, $values);
    }

    public function testStringColumnCharacterSet(): void
    {
        $stringCol = new StringColumn();

        $this->assertNull($stringCol->getCharacterSet());
        $this->assertSame($stringCol, $stringCol->characterSet('utf8mb4_bin'));
        $this->assertSame('utf8mb4_bin', $stringCol->getCharacterSet());
    }

    protected function insertTypeValues(ConnectionInterface $db): void
    {
        $db->createCommand()->insert(
            'type',
            [
                'int_col' => 1,
                'bigunsigned_col' => '12345678901234567890',
                'char_col' => str_repeat('x', 100),
                'char_col3' => null,
                'float_col' => 1.234,
                'blob_col' => "\x10\x11\x12",
                'timestamp_col' => '2023-07-11 14:50:23',
                'timestamp_default' => new DateTimeImmutable('2023-07-11 14:50:23'),
                'bool_col' => false,
                'bit_col' => 0b0110_0100, // 100
                'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
            ],
        )->execute();
    }

    protected function assertTypecastedValues(array $result, bool $allTypecasted = false): void
    {
        $this->assertSame(1, $result['int_col']);
        $this->assertSame('12345678901234567890', $result['bigunsigned_col']);
        $this->assertSame(str_repeat('x', 100), $result['char_col']);
        $this->assertNull($result['char_col3']);
        $this->assertSame(1.234, $result['float_col']);
        $this->assertSame("\x10\x11\x12", $result['blob_col']);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:23', new DateTimeZone('UTC')), $result['timestamp_col']);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:23'), $result['timestamp_default']);
        $this->assertFalse($result['bool_col']);
        $this->assertSame(0b0110_0100, $result['bit_col']);

        // JSON column is always typecasted in MySQL after this fix: https://github.com/php/php-src/issues/20122
        // PHP 8.3.28+, 8.4.15+
        $isPhpWithFix = (PHP_VERSION_ID >= 80328 && PHP_VERSION_ID < 80400) || PHP_VERSION_ID >= 80415;
        if ($allTypecasted || (!TestConnection::isMariadb() && $isPhpWithFix)) {
            $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $result['json_col']);
        } else {
            $this->assertJsonStringEqualsJsonString('[{"a":1,"b":null,"c":[1,3,5]}]', $result['json_col']);
        }
    }
}
