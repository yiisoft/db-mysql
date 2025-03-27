<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mysql\Column\ColumnBuilder;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\AbstractColumnTest;

use function str_repeat;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnTest extends AbstractColumnTest
{
    use TestTrait;

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
                'time' => '2023-07-11 14:50:23',
                'bool_col' => false,
                'bit_col' => 0b0110_0100, // 100
                'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
            ]
        )->execute();
    }

    private function assertResultValues(array $result): void
    {
        $this->assertSame(1, $result['int_col']);
        $this->assertSame('12345678901234567890', $result['bigunsigned_col']);
        $this->assertSame(str_repeat('x', 100), $result['char_col']);
        $this->assertNull($result['char_col3']);
        $this->assertSame(1.234, $result['float_col']);
        $this->assertSame("\x10\x11\x12", $result['blob_col']);
        $this->assertSame('2023-07-11 14:50:23', $result['time']);
        $this->assertFalse($result['bool_col']);
        $this->assertSame(0b0110_0100, $result['bit_col']);
        $this->assertSame('[{"a":1,"b":null,"c":[1,3,5]}]', $result['json_col']);
    }

    public function testQueryTypecasting(): void
    {
        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $result = (new Query($db))->typecasting()->from('type')->one();

        $this->assertResultValues($result);

        $db->close();
    }

    public function testCommandPhpTypecasting(): void
    {
        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $result = $db->createCommand('SELECT * FROM type')->phpTypecasting()->queryOne();

        $this->assertResultValues($result);

        $db->close();
    }

    public function testSelectPhpTypecasting(): void
    {
        $db = $this->getConnection();

        $result = $db->createCommand("SELECT null, 1, 2.5, true, false, 'string'")->phpTypecasting()->queryOne();

        $this->assertSame(
            [
                'NULL' => null,
                1 => 1,
                '2.5' => 2.5,
                'TRUE' => 1,
                'FALSE' => 0,
                'string' => 'string',
            ],
            $result,
        );

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
        $tableSchema = $schema->getTableSchema('type');

        $this->insertTypeValues($db);

        $query = (new Query($db))->from('type')->one();

        $intColPhpType = $tableSchema->getColumn('int_col')?->phpTypecast($query['int_col']);
        $bigUnsignedColPhpType = $tableSchema->getColumn('bigunsigned_col')?->phpTypecast($query['bigunsigned_col']);
        $charColPhpType = $tableSchema->getColumn('char_col')?->phpTypecast($query['char_col']);
        $charCol3PhpType = $tableSchema->getColumn('char_col3')?->phpTypecast($query['char_col3']);
        $floatColPhpType = $tableSchema->getColumn('float_col')?->phpTypecast($query['float_col']);
        $blobColPhpType = $tableSchema->getColumn('blob_col')?->phpTypecast($query['blob_col']);
        $timePhpType = $tableSchema->getColumn('time')?->phpTypecast($query['time']);
        $boolColPhpType = $tableSchema->getColumn('bool_col')?->phpTypecast($query['bool_col']);
        $bitColPhpType = $tableSchema->getColumn('bit_col')?->phpTypecast($query['bit_col']);
        $jsonColPhpType = $tableSchema->getColumn('json_col')?->phpTypecast($query['json_col']);

        $this->assertSame(1, $intColPhpType);
        $this->assertSame('12345678901234567890', $bigUnsignedColPhpType);
        $this->assertSame(str_repeat('x', 100), $charColPhpType);
        $this->assertNull($charCol3PhpType);
        $this->assertSame(1.234, $floatColPhpType);
        $this->assertSame("\x10\x11\x12", $blobColPhpType);
        $this->assertSame('2023-07-11 14:50:23', $timePhpType);
        $this->assertFalse($boolColPhpType);
        $this->assertSame(0b0110_0100, $bitColPhpType);
        $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $jsonColPhpType);

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
}
