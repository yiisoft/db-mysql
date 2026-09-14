<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Expression\Value\UuidValue;
use Yiisoft\Db\Helper\DbUuidHelper;
use Yiisoft\Db\Mysql\Builder\UuidValueBuilder;
use Yiisoft\Db\Mysql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

use function hex2bin;
use function strlen;

/**
 * @group mysql
 */
final class UuidValueBuilderTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    private const UUID = '738146be-87b1-49f2-9913-36142fb6fcbe';
    private const HEX = '738146be87b149f2991336142fb6fcbe';

    /**
     * Every form {@see UuidValue} accepts, all denoting the same UUID.
     */
    public static function values(): iterable
    {
        yield 'canonical' => [self::UUID];
        yield 'canonical in upper case' => ['738146BE-87B1-49F2-9913-36142FB6FCBE'];
        yield 'hexadecimal' => [self::HEX];
        yield 'hexadecimal in upper case' => ['738146BE87B149F2991336142FB6FCBE'];
        yield 'bytes' => [hex2bin(self::HEX)];
    }

    /**
     * `UuidValue` normalizes the value to the canonical form on construction, so the builder binds the same 16 bytes
     * whichever form it was created from.
     */
    #[DataProvider('values')]
    public function testBuildBindsRawBytesAsLob(string $value): void
    {
        $db = $this->getSharedConnection();
        $builder = new UuidValueBuilder($db->getQueryBuilder());

        $params = [];
        $result = $builder->build(new UuidValue($value), $params);

        $this->assertSame(':qp0', $result);
        $this->assertEquals(
            [':qp0' => new Param(DbUuidHelper::uuidToBlob(self::UUID), DataType::LOB)],
            $params,
        );
    }

    #[DataProvider('values')]
    public function testInsertAndSelectUuid(string $value): void
    {
        $db = $this->getSharedConnection();

        $this->dropTable('uuid_value');
        $this->executeStatements('CREATE TABLE [[uuid_value]] ([[id]] binary(16) NOT NULL)');

        $db->createCommand()->insert('uuid_value', ['id' => new UuidValue($value)])->execute();

        $bytes = $db->createCommand('SELECT [[id]] FROM [[uuid_value]]')->queryScalar();

        $this->assertIsString($bytes);
        $this->assertSame(16, strlen($bytes));
        $this->assertSame(self::UUID, DbUuidHelper::toUuid($bytes));

        $this->dropTable('uuid_value');
    }
}
