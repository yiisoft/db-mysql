<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Mysql\ColumnSchemaBuilder;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonColumnSchemaBuilderTest;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnSchemaBuilderTest extends CommonColumnSchemaBuilderTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mysql\Tests\Provider\ColumnSchemaBuilderProvider::types
     */
    public function testCustomTypes(string $expected, string $type, int|null $length, array $calls): void
    {
        $this->checkBuildString($expected, $type, $length, $calls);
    }

    public function testCreateTableWithColumnSchemaBuilder(): void
    {
        $tableName = '{{%test_table_with_comment_column}}';
        $db = $this->getConnection();

        if ($db->getTableSchema($tableName)) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $result = $db->createCommand()->createTable($tableName, [
            'id' => (new ColumnSchemaBuilder('pk'))->comment('test comment'),
        ])->execute();

        $this->assertIsInt($result);
    }

    public function testColumnSchemaBuilderWithoutQuoter(): void
    {
        $this->expectException(InvalidConfigException::class);
        (new ColumnSchemaBuilder('pk'))->comment('test comment')->asString();
    }
}
