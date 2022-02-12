<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Mysql\ColumnSchema;

/**
 * @group mysql
 */
final class ColumnSchemaTest extends TestCase
{
    public function bigintValueProvider(): array
    {
        return [
            ['8817806877'],
            ['3797444208'],
            ['3199585540'],
            ['1389831585'],
            ['922337203685477580'],
            ['9223372036854775807'],
            ['-9223372036854775808'],
        ];
    }

    /**
     * @dataProvider bigintValueProvider
     */
    public function testColumnBigInt($bigint): void
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $command->insert(
            'negative_default_values',
            [
                'bigint_col' => $bigint,
            ]
        );

        $command->execute();

        $query = (new Query($db))->from('negative_default_values')->one();

        $this->assertSame($bigint, $query['bigint_col']);
    }

    public function testDbTypeCastJson(): void
    {
        $columnSchema = new ColumnSchema();
        $columnSchema->dbType('json');
        $columnSchema->type('json');

        $expected = new JsonExpression('{"a":1}', 'json');
        $this->assertEquals($expected, $columnSchema->dbTypeCast('{"a":1}'));
    }

    public function testPhpTypeCastJson(): void
    {
        $columnSchema = new ColumnSchema();
        $columnSchema->type('json');

        $this->assertSame(['a' => 1], $columnSchema->phpTypeCast('{"a":1}'));
    }
}
