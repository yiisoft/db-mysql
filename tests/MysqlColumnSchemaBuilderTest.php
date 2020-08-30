<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use Yiisoft\Db\Mysql\Schema\MysqlColumnSchemaBuilder;
use Yiisoft\Db\Mysql\Schema\MysqlSchema;
use Yiisoft\Db\TestUtility\TestColumnSchemaBuilderTrait;

/**
 * @group mysql
 */
final class MysqlColumnSchemaBuilderTest extends TestCase
{
    use TestColumnSchemaBuilderTrait;

    public function getColumnSchemaBuilder($type, $length = null): MysqlColumnSchemaBuilder
    {
        return new MysqlColumnSchemaBuilder($type, $length, $this->getConnection());
    }

    public function typesProvider(): array
    {
        return [
            ['integer UNSIGNED', MysqlSchema::TYPE_INTEGER, null, [
                ['unsigned'],
            ]],
            ['integer(10) UNSIGNED', MysqlSchema::TYPE_INTEGER, 10, [
                ['unsigned'],
            ]],
            ['integer(10) COMMENT \'test\'', MysqlSchema::TYPE_INTEGER, 10, [
                ['comment', 'test'],
            ]],

            /**
             * {@see https://github.com/yiisoft/yii2/issues/11945}, real test against database.
             */
            ['string(50) NOT NULL COMMENT \'Property name\' COLLATE ascii_general_ci', MysqlSchema::TYPE_STRING, 50, [
                ['comment', 'Property name'],
                ['append', 'COLLATE ascii_general_ci'],
                ['notNull']
            ]],
        ];
    }

    /**
     * @dataProvider typesProvider
     *
     * @param string $expected
     * @param string $type
     * @param int|null $length
     * @param mixed $calls
     */
    public function testCustomTypes(string $expected, string $type, ?int $length, $calls): void
    {
        $this->checkBuildString($expected, $type, $length, $calls);
    }
}
