<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Builder;

use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Expression\Value\UuidValue;
use Yiisoft\Db\Helper\DbUuidHelper;
use Yiisoft\Db\Mysql\Builder\UuidValueBuilder;
use Yiisoft\Db\Mysql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

use function strlen;

/**
 * @group mysql
 */
final class UuidValueBuilderTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    private const UUID = '738146be-87b1-49f2-9913-36142fb6fcbe';

    public function testBuildBindsRawBytesAsLob(): void
    {
        $db = $this->getSharedConnection();
        $builder = new UuidValueBuilder($db->getQueryBuilder());

        $params = [];
        $result = $builder->build(new UuidValue(self::UUID), $params);

        $this->assertSame(':qp0', $result);
        $this->assertEquals(
            [':qp0' => new Param(DbUuidHelper::uuidToBlob(self::UUID), DataType::LOB)],
            $params,
        );
    }

    public function testInsertAndSelectUuid(): void
    {
        $db = $this->getSharedConnection();

        $this->dropTable('uuid_value');
        $this->executeStatements('CREATE TABLE [[uuid_value]] ([[id]] binary(16) NOT NULL)');

        $db->createCommand()->insert('uuid_value', ['id' => new UuidValue(self::UUID)])->execute();

        $bytes = $db->createCommand('SELECT [[id]] FROM [[uuid_value]]')->queryScalar();

        $this->assertIsString($bytes);
        $this->assertSame(16, strlen($bytes));
        $this->assertSame(self::UUID, DbUuidHelper::toUuid($bytes));

        $this->dropTable('uuid_value');
    }
}
