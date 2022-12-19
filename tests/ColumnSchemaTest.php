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

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnSchemaTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\ColumnSchemaProvider::bigIntValue()
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
        $columnSchema = new ColumnSchema();

        $columnSchema->dbType('json');
        $columnSchema->type('json');

        $this->assertEquals(new JsonExpression('{"a":1}', 'json'), $columnSchema->dbTypeCast('{"a":1}'));
    }

    /**
     * @throws JsonException
     */
    public function testPhpTypeCastJson(): void
    {
        $columnSchema = new ColumnSchema();

        $columnSchema->type('json');

        $this->assertSame(['a' => 1], $columnSchema->phpTypeCast('{"a":1}'));
    }
}
