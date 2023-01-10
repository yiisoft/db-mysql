<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Mysql\Schema;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CommandJSONTest extends TestCase
{
    use TestTrait;

    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('json') !== null) {
            $db->createCommand()->dropTable('json')->execute();
        }

        $command = $db->createCommand();
        $command->createTable('json', [
            'id' => Schema::TYPE_PK,
            'data' => Schema::TYPE_JSON,
        ])->execute();

        $this->assertTrue($db->getTableSchema('json') !== null);
        $this->assertSame('data', $db->getTableSchema('json')->getColumn('data')->getName());
        $this->assertSame('json', $db->getTableSchema('json')->getColumn('data')->getType());
    }

    public function testInsertAndSelect(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('storage', ['data' => ['a' => 1, 'b' => 2]])->execute();
        $rowExpected = match (str_contains($db->getServerVersion(), 'MariaDB')) {
            true => '{"a":1,"b":2}',
            default => '{"a": 1, "b": 2}',
        };

        $this->assertSame(
            $rowExpected,
            $command->setSql(
                <<<SQL
                SELECT `data` FROM `storage`
                SQL,
            )->queryScalar(),
        );
    }

    public function testInsertJsonExpressionAndSelect(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('storage', ['data' => new JsonExpression(['a' => 1, 'b' => 2])])->execute();
        $rowExpected = match (str_contains($db->getServerVersion(), 'MariaDB')) {
            true => '{"a":1,"b":2}',
            default => '{"a": 1, "b": 2}',
        };

        $this->assertSame(
            $rowExpected,
            $command->setSql(
                <<<SQL
                SELECT `data` FROM `storage`
                SQL,
            )->queryScalar(),
        );
    }
}
