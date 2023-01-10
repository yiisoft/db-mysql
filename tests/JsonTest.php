<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mysql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\Schema;

/**
 * @group mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class JsonTest extends TestCase
{
    use TestTrait;

    public function testAlterColumn()
    {
        $db = $this->getConnection(true);

        $qb = $this->getConnection()->getQueryBuilder();
        $schema = $this->getConnection()->getSchema();
        $columnSchemaBuilder = $schema->createColumnSchemaBuilder(Schema::TYPE_JSON);
        $sql = $qb->alterColumn('storage', 'id', $columnSchemaBuilder);

        $this->assertStringEndsWith(
            <<<SQL
            ALTER TABLE `storage` CHANGE `id` `id` json
            SQL,
            $sql,
        );
    }

    public function testAddColumn()
    {
        $db = $this->getConnection(true);

        $qb = $this->getConnection()->getQueryBuilder();
        $schema = $this->getConnection()->getSchema();
        $columnSchemaBuilder = $schema->createColumnSchemaBuilder(Schema::TYPE_JSON);
        $sql = $qb->addColumn('storage', 'abc', (string) $columnSchemaBuilder);

        $this->assertSame(
            <<<SQL
            ALTER TABLE `storage` ADD `abc` json
            SQL,
            $sql,
        );
    }

    public function testColumnTypeJSONDefaultValue()
    {
        $db = $this->getConnection(true);

        $table = $db->getTableSchema('storage');

        $this->assertNotNull($table);
        $this->assertSame(['test' => 1], $table->getColumn('data')->getDefaultValue());
    }

    public function testCreateTable()
    {
        $db = $this->getConnection(true);

        $qb = $this->getConnection()->getQueryBuilder();
        $schema = $this->getConnection()->getSchema();
        $columnSchemaBuilder = $schema->createColumnSchemaBuilder(Schema::TYPE_JSON);
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
}
