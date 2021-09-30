<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PDO;
use Yiisoft\Cache\CacheKeyNormalizer;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\TestUtility\TestConnectionTrait;

/**
 * @group mysql
 */
final class ConnectionTest extends TestCase
{
    use TestConnectionTrait;

    public function testConnection(): void
    {
        $this->assertIsObject($this->getConnection(true));
    }

    public function testInitConnection(): void
    {
        $db = $this->getConnection();

        $db->setEmulatePrepare(true);

        $db->open();

        $this->assertTrue($db->getEmulatePrepare());

        $db->setEmulatePrepare(false);

        $this->assertFalse($db->getEmulatePrepare());

        $db->close();
    }

    public function testConstruct(): void
    {
        $db = $this->getConnection();

        $this->assertEquals(self::DB_DSN, $db->getDsn());
    }

    public function testGetDriverName(): void
    {
        $db = $this->getConnection();

        $this->assertEquals('mysql', $db->getDriverName());
    }

    public function testOpenClose(): void
    {
        $db = $this->getConnection();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPDO());

        $db->open();

        $this->assertTrue($db->isActive());
        $this->assertInstanceOf(PDO::class, $db->getPDO());

        $db->close();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPDO());

        $db = $this->createConnection('unknown::memory:');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('could not find driver');

        $db->open();
    }

    public function testQuoteValue(): void
    {
        $db = $this->getConnection();

        $this->assertEquals(123, $db->quoteValue(123));
        $this->assertEquals("'string'", $db->quoteValue('string'));
        $this->assertEquals("'It\\'s interesting'", $db->quoteValue("It's interesting"));
    }

    public function testQuoteTableName(): void
    {
        $db = $this->getConnection();

        $this->assertEquals('`table`', $db->quoteTableName('table'));
        $this->assertEquals('`table`', $db->quoteTableName('`table`'));
        $this->assertEquals('`schema`.`table`', $db->quoteTableName('schema.table'));
        $this->assertEquals('`schema`.`table`', $db->quoteTableName('schema.`table`'));
        $this->assertEquals('`schema`.`table`', $db->quoteTableName('`schema`.`table`'));
        $this->assertEquals('{{table}}', $db->quoteTableName('{{table}}'));
        $this->assertEquals('(table)', $db->quoteTableName('(table)'));
        $this->assertEquals('`table(0)`', $db->quoteTableName('table(0)'));
    }

    public function testQuoteColumnName(): void
    {
        $db = $this->getConnection();

        $this->assertEquals('`column`', $db->quoteColumnName('column'));
        $this->assertEquals('`column`', $db->quoteColumnName('`column`'));
        $this->assertEquals('[[column]]', $db->quoteColumnName('[[column]]'));
        $this->assertEquals('{{column}}', $db->quoteColumnName('{{column}}'));
        $this->assertEquals('(column)', $db->quoteColumnName('(column)'));

        $this->assertEquals('`column`', $db->quoteSql('[[column]]'));
        $this->assertEquals('`column`', $db->quoteSql('{{column}}'));
    }

    public function testQuoteFullColumnName(): void
    {
        $db = $this->getConnection();

        $this->assertEquals('`table`.`column`', $db->quoteColumnName('table.column'));
        $this->assertEquals('`table`.`column`', $db->quoteColumnName('table.`column`'));
        $this->assertEquals('`table`.`column`', $db->quoteColumnName('`table`.column'));
        $this->assertEquals('`table`.`column`', $db->quoteColumnName('`table`.`column`'));

        $this->assertEquals('[[table.column]]', $db->quoteColumnName('[[table.column]]'));
        $this->assertEquals('{{table}}.`column`', $db->quoteColumnName('{{table}}.column'));
        $this->assertEquals('{{table}}.`column`', $db->quoteColumnName('{{table}}.`column`'));
        $this->assertEquals('{{table}}.[[column]]', $db->quoteColumnName('{{table}}.[[column]]'));
        $this->assertEquals('{{%table}}.`column`', $db->quoteColumnName('{{%table}}.column'));
        $this->assertEquals('{{%table}}.`column`', $db->quoteColumnName('{{%table}}.`column`'));

        $this->assertEquals('`table`.`column`', $db->quoteSql('[[table.column]]'));
        $this->assertEquals('`table`.`column`', $db->quoteSql('{{table}}.[[column]]'));
        $this->assertEquals('`table`.`column`', $db->quoteSql('{{table}}.`column`'));
        $this->assertEquals('`table`.`column`', $db->quoteSql('{{%table}}.[[column]]'));
        $this->assertEquals('`table`.`column`', $db->quoteSql('{{%table}}.`column`'));
    }

    /**
     * Test whether slave connection is recovered when call `getSlavePdo()` after `close()`.
     *
     * {@see https://github.com/yiisoft/yii2/issues/14165}
     */
    public function testGetPdoAfterClose(): void
    {
        $db = $this->getConnection();

        $db->setSlave('1', $this->createConnection(self::DB_DSN));

        $this->assertNotNull($db->getSlavePdo(false));

        $db->close();

        $masterPdo = $db->getMasterPdo();

        $this->assertNotFalse($masterPdo);
        $this->assertNotNull($masterPdo);

        $slavePdo = $db->getSlavePdo(false);

        $this->assertNotFalse($slavePdo);
        $this->assertNotNull($slavePdo);
        $this->assertNotSame($masterPdo, $slavePdo);
    }

    public function testServerStatusCacheWorks(): void
    {
        $cacheKeyNormalizer = new CacheKeyNormalizer();

        $db = $this->getConnection(true);

        $db->setMaster('1', $this->createConnection(self::DB_DSN));

        $db->setShuffleMasters(false);

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', $db->getDsn()]
        );

        $this->assertFalse($this->cache->psr()->has($cacheKey));

        $db->open();

        $this->assertFalse(
            $this->cache->psr()->has($cacheKey),
            'Connection was successful – cache must not contain information about this DSN'
        );

        $db->close();

        $db = $this->getConnection();

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', 'host:invalid']
        );

        $db->setMaster('1', $this->createConnection('host:invalid'));

        $db->setShuffleMasters(true);

        try {
            $db->open();
        } catch (InvalidConfigException $e) {
        }

        $this->assertTrue(
            $this->cache->psr()->has($cacheKey),
            'Connection was not successful – cache must contain information about this DSN'
        );

        $db->close();
    }

    public function testServerStatusCacheCanBeDisabled(): void
    {
        $cacheKeyNormalizer = new CacheKeyNormalizer();

        $db = $this->getConnection();

        $db->setMaster('1', $this->createConnection(self::DB_DSN));

        $this->schemaCache->setEnable(false);

        $db->setShuffleMasters(false);

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially::', $db->getDsn()]
        );

        $this->assertFalse($this->cache->psr()->has($cacheKey));

        $db->open();

        $this->assertFalse($this->cache->psr()->has($cacheKey), 'Caching is disabled');

        $db->close();

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', 'host:invalid']
        );

        $db->setMaster('1', $this->createConnection('host:invalid'));

        try {
            $db->open();
        } catch (InvalidConfigException $e) {
        }

        $this->assertFalse($this->cache->psr()->has($cacheKey), 'Caching is disabled');

        $db->close();
    }
}
