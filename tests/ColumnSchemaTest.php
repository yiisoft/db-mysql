<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use JsonException;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Mysql\ColumnSchema;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\SchemaInterface;

use function str_repeat;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnSchemaTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\ColumnSchemaProvider::bigIntValue
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
    }

    public function testDbTypeCastJson(): void
    {
        $columnSchema = new ColumnSchema('json');

        $columnSchema->dbType(SchemaInterface::TYPE_JSON);
        $columnSchema->type(SchemaInterface::TYPE_JSON);

        $this->assertEquals(new JsonExpression('{"a":1}', SchemaInterface::TYPE_JSON), $columnSchema->dbTypeCast('{"a":1}'));
    }

    /**
     * @throws JsonException
     */
    public function testPhpTypeCastJson(): void
    {
        $columnSchema = new ColumnSchema('json');

        $columnSchema->type(SchemaInterface::TYPE_JSON);

        $this->assertSame(['a' => 1], $columnSchema->phpTypeCast('{"a":1}'));
    }

    /**
     * @return void
     * @throws Exception
     * @throws Throwable
     * @throws \Yiisoft\Db\Exception\InvalidArgumentException
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     * @throws \Yiisoft\Db\Exception\NotSupportedException
     */
    public function testPhpTypeCast(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $command->insert(
            'type',
            [
                'int_col' => 1,
                'char_col' => str_repeat('x', 100),
                'char_col3' => null,
                'float_col' => 1.234,
                'blob_col' => "\x10\x11\x12",
                'time' => '2023-07-11 14:50:23',
                'bool_col' => false,
                'bit_col' => 0b0110_0100, // 100
                'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
            ]
        );
        $command->execute();
        $query = (new Query($db))->from('type')->one();

        $this->assertNotNull($tableSchema);

        $intColPhpType = $tableSchema->getColumn('int_col')?->phpTypecast($query['int_col']);
        $charColPhpType = $tableSchema->getColumn('char_col')?->phpTypecast($query['char_col']);
        $charCol3PhpType = $tableSchema->getColumn('char_col3')?->phpTypecast($query['char_col3']);
        $floatColPhpType = $tableSchema->getColumn('float_col')?->phpTypecast($query['float_col']);
        $blobColPhpType = $tableSchema->getColumn('blob_col')?->phpTypecast($query['blob_col']);
        $timePhpType = $tableSchema->getColumn('time')?->phpTypecast($query['time']);
        $boolColPhpType = $tableSchema->getColumn('bool_col')?->phpTypecast($query['bool_col']);
        $bitColPhpType = $tableSchema->getColumn('bit_col')?->phpTypecast($query['bit_col']);
        $jsonColPhpType = $tableSchema->getColumn('json_col')?->phpTypecast($query['json_col']);

        $this->assertSame(1, $intColPhpType);
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
}
