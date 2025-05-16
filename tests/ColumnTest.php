<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mysql\Column\ColumnBuilder;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Tests\Provider\ColumnProvider;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\Common\CommonColumnTest;

use function str_repeat;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnTest extends CommonColumnTest
{
    use TestTrait;

    protected const COLUMN_BUILDER = ColumnBuilder::class;

    private function insertTypeValues(Connection $db): void
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
            ]
        )->execute();
    }

    private function assertTypecastedValues(array $result, bool $allTypecasted = false): void
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

        if ($allTypecasted) {
            $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $result['json_col']);
        } else {
            $this->assertJsonStringEqualsJsonString('[{"a":1,"b":null,"c":[1,3,5]}]', $result['json_col']);
        }
    }

    public function testQueryTypecasting(): void
    {
        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $query = (new Query($db))->from('type')->withTypecasting();

        $result = $query->one();

        $this->assertTypecastedValues($result);

        $result = $query->all();

        $this->assertTypecastedValues($result[0]);

        $db->close();
    }

    public function testCommandWithPhpTypecasting(): void
    {
        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $command = $db->createCommand('SELECT * FROM type')->withPhpTypecasting();

        $result = $command->queryOne();

        $this->assertTypecastedValues($result);

        $result = $command->queryAll();

        $this->assertTypecastedValues($result[0]);

        $db->close();
    }

    public function testSelectWithPhpTypecasting(): void
    {
        $db = $this->getConnection();

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

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\ColumnProvider::bigIntValue
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testColumnBigInt(string $bigint): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('negative_default_values', ['bigint_col' => $bigint]);
        $command->execute();
        $query = (new Query($db))->from('negative_default_values')->one();

        $this->assertSame($bigint, $query['bigint_col']);

        $db->close();
    }

    public function testPhpTypeCast(): void
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $columns = $schema->getTableSchema('type')->getColumns();

        $this->insertTypeValues($db);

        $query = (new Query($db))->from('type')->one();

        $result = [];

        foreach ($columns as $columnName => $column) {
            $result[$columnName] = $column->phpTypecast($query[$columnName]);
        }

        $this->assertTypecastedValues($result, true);

        $db->close();
    }

    public function testColumnInstance(): void
    {
        $db = $this->getConnection(true);
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
        $db = $this->getConnection();
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
        $db = $this->getConnection();
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
            ]
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
    public function testPredefinedType(string $className, string $type, string $phpType)
    {
        parent::testPredefinedType($className, $type, $phpType);
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
}
