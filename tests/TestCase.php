<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Tests;

use PHPUnit\Framework\TestCase as AbstractTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\Dsn;
use Yiisoft\Db\Connection\LazyConnectionDependencies;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Factory\DatabaseFactory;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\TestUtility\IsOneOfAssert;
use Yiisoft\Di\Container;
use Yiisoft\Factory\Definition\Reference;
use Yiisoft\Log\Logger;
use Yiisoft\Profiler\Profiler;
use Yiisoft\Profiler\ProfilerInterface;

use function explode;
use function file_get_contents;
use function str_replace;
use function trim;

class TestCase extends AbstractTestCase
{
    protected array $dataProvider;
    protected string $likeEscapeCharSql = '';
    protected array $likeParameterReplacements = [];
    protected Aliases $aliases;
    protected CacheInterface $cache;
    protected Connection $connection;
    protected LazyConnectionDependencies $dependencies;
    protected ContainerInterface $container;
    protected LoggerInterface $logger;
    protected ProfilerInterface $profiler;
    protected QueryCache $queryCache;
    protected SchemaCache $schemaCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configContainer();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->getConnection()->close();

        unset(
            $this->aliases,
            $this->cache,
            $this->connection,
            $this->container,
            $this->dataProvider,
            $this->dependencies,
            $this->logger,
            $this->queryCache,
            $this->schemaCache,
            $this->profiler
        );
    }

    /**
     * Asserting two strings equality ignoring line endings.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    protected function assertEqualsWithoutLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Asserts that value is one of expected values.
     *
     * @param mixed $actual
     * @param array $expected
     * @param string $message
     */
    protected function assertIsOneOf($actual, array $expected, $message = ''): void
    {
        self::assertThat($actual, new IsOneOfAssert($expected), $message);
    }

    protected function configContainer(): void
    {
        $this->container = new Container($this->config());

        DatabaseFactory::initialize($this->container, []);

        $this->aliases = $this->container->get(Aliases::class);
        $this->cache = $this->container->get(CacheInterface::class);
        $this->connection = $this->container->get(ConnectionInterface::class);
        $this->dependencies = $this->container->get(LazyConnectionDependencies::class);
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->profiler = $this->container->get(ProfilerInterface::class);
        $this->queryCache = $this->container->get(QueryCache::class);
        $this->schemaCache = $this->container->get(SchemaCache::class);
    }

    /**
     * Invokes a inaccessible method.
     *
     * @param object $object
     * @param string $method
     * @param array $args
     * @param bool $revoke whether to make method inaccessible after execution.
     *
     * @throws ReflectionException
     *
     * @return mixed
     */
    protected function invokeMethod(object $object, string $method, array $args = [], bool $revoke = true)
    {
        $reflection = new ReflectionObject($object);

        $method = $reflection->getMethod($method);

        $method->setAccessible(true);

        $result = $method->invokeArgs($object, $args);

        if ($revoke) {
            $method->setAccessible(false);
        }
        return $result;
    }

    /**
     * @param bool $reset whether to clean up the test database.
     *
     * @return Connection
     */
    protected function getConnection($reset = false): Connection
    {
        if ($reset === false && isset($this->connection)) {
            return $this->connection;
        }

        if ($reset === false) {
            $this->configContainer();
            return $this->connection;
        }

        try {
            $this->prepareDatabase();
        } catch (Exception $e) {
            $this->markTestSkipped('Something wrong when preparing database: ' . $e->getMessage());
        }

        return $this->connection;
    }

    protected function prepareDatabase(?string $fixture = null): void
    {
        if ($fixture === null) {
            $fixture = $this->params()['yiisoft/db-mysql']['fixture'];
        }

        $this->connection->open();

        if ($fixture !== null) {
            $lines = explode(';', file_get_contents($this->aliases->get($fixture)));

            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $this->connection->getPDO()->exec($line);
                }
            }
        }
    }

    /**
     * Gets an inaccessible object property.
     *
     * @param object $object
     * @param string $propertyName
     * @param bool $revoke whether to make property inaccessible after getting.
     *
     * @return mixed
     */
    protected function getInaccessibleProperty(object $object, string $propertyName, bool $revoke = true)
    {
        $class = new ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }

        $property = $class->getProperty($propertyName);

        $property->setAccessible(true);

        $result = $property->getValue($object);

        if ($revoke) {
            $property->setAccessible(false);
        }

        return $result;
    }

    /**
     * Adjust dbms specific escaping.
     *
     * @param array|string $sql
     *
     * @return string
     */
    protected function replaceQuotes($sql): string
    {
        return str_replace(['[[', ']]'], '`', $sql);
    }

    /**
     * Sets an inaccessible object property to a designated value.
     *
     * @param object $object
     * @param string $propertyName
     * @param $value
     * @param bool $revoke whether to make property inaccessible after setting
     */
    protected function setInaccessibleProperty(object $object, string $propertyName, $value, bool $revoke = true): void
    {
        $class = new ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }

        $property = $class->getProperty($propertyName);

        $property->setAccessible(true);

        $property->setValue($object, $value);

        if ($revoke) {
            $property->setAccessible(false);
        }
    }

    protected function params(): array
    {
        return [
            'yiisoft/db-mysql' => [
                'dsn' => (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306'))->asString(),
                'username' => 'root',
                'password' => 'root',
                'charset' => 'utf8mb4',
                'fixture' => __DIR__ . '/Data/mysql.sql',
            ],
        ];
    }

    private function config(): array
    {
        $params = $this->params();

        return [
            Aliases::class => [
                '__construct()' => [
                    [
                        '@root' => dirname(__DIR__, 1),
                        '@data' => '@root/tests/Data',
                        '@runtime' => '@data/runtime',
                    ],
                ],
            ],

            CacheInterface::class => [
                'class' => Cache::class,
                '__construct()' => [
                    Reference::to(ArrayCache::class),
                ],
            ],

            LoggerInterface::class => Logger::class,

            ProfilerInterface::class => Profiler::class,

            ConnectionInterface::class => [
                'class' => Connection::class,
                '__construct()' => [
                    'dsn' => $params['yiisoft/db-mysql']['dsn'],
                ],
                'setUsername()' => [$params['yiisoft/db-mysql']['username']],
                'setPassword()' => [$params['yiisoft/db-mysql']['password']],
                'setCharset()' => [$params['yiisoft/db-mysql']['charset']],
            ],
        ];
    }
}
