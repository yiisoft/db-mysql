<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Mysql\Column;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryBuilderJsonTest extends TestCase
{
    use TestTrait;

    /**
     * @return void
     */
    public function testAlterColumn(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();
        $columnSchemaBuilder = new Column(SchemaInterface::TYPE_JSON);
        $sql = $qb->alterColumn('storage', 'id', $columnSchemaBuilder);

        $this->assertStringEndsWith(
            <<<SQL
            ALTER TABLE `storage` CHANGE `id` `id` json
            SQL,
            $sql,
        );
    }

    /**
     * @return void
     */
    public function testAddColumn(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();
        $columnSchemaBuilder = new Column(SchemaInterface::TYPE_JSON);
        $sql = $qb->addColumn('storage', 'abc', $columnSchemaBuilder->asString());

        $this->assertSame(
            <<<SQL
            ALTER TABLE `storage` ADD `abc` json
            SQL,
            $sql,
        );
    }

    /**
     * @return void
     */
    public function testCreateTable(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();
        $columnSchemaBuilder = new Column(SchemaInterface::TYPE_JSON);
        $sql = $qb->createTable('storage', ['abc' => $columnSchemaBuilder]);

        $this->assertSame(
            <<<SQL
            CREATE TABLE `storage` (
            \t`abc` json
            )
            SQL,
            $sql,
        );
    }

    /**
     * @return void
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidArgumentException
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     * @throws \Yiisoft\Db\Exception\NotSupportedException
     */
    public function testInsertAndSelect(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            INSERT INTO `storage` (`data`) VALUES (:qp0)
            SQL,
            $qb->insert('storage', ['data' => ['a' => 1, 'b' => 2]]),
        );
    }

    /**
     * @return void
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidArgumentException
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     * @throws \Yiisoft\Db\Exception\NotSupportedException
     */
    public function testInsertJsonExpressionAndSelect(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();


        $this->assertSame(
            <<<SQL
            INSERT INTO `storage` (`data`) VALUES (:qp0)
            SQL,
            $qb->insert('storage', ['data' => new JsonExpression(['a' => 1, 'b' => 2])]),
        );
    }
}
