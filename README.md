<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="80px">
    </a>
    <a href="https://www.mysql.com/" target="_blank">
        <img src="https://labs.mysql.com/common/logos/mysql-logo.svg" height="80px">
    </a>
    <h1 align="center">Yii DataBase MySQL Extension</h1>
    <br>
</p>

This package provides [MySQL] extension for [Yii DataBase] library.
It is used in [Yii Framework] but is supposed to be usable separately.

[MySQL]: https://www.mysql.com/
[Yii DataBase]: https://github.com/yiisoft/db
[Yii Framework]: https://www.yiiframework.com/

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-mysql/v/stable.png)](https://packagist.org/packages/yiisoft/db-mysql)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-mysql/downloads.png)](https://packagist.org/packages/yiisoft/db-mysql)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/db-mysql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/db-mysql/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/db-mysql/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/db-mysql/?branch=master)
[![type-coverage](https://shepherd.dev/github/yiisoft/db-mysql/coverage.svg)](https://shepherd.dev/github/yiisoft/db-mysql)


## Support version

|  PHP | Mysql Version            | SO | CI-Actions
|:----:|:------------------------:|:--:|:---:|
|![PHP-7](https://img.shields.io/badge/7:latest-blue) ![PHP-8](https://img.shields.io/badge/8:latest-blue)| ![Mysql-5.7](https://img.shields.io/badge/5:latest-blue) ![Mysql-8.0](https://img.shields.io/badge/8:latest-blue)|![Linux](https://img.shields.io/badge/ubuntu-blue)|[![Build status](https://github.com/yiisoft/db-mssql/workflows/build/badge.svg)](https://github.com/yiisoft/db-mysql/actions/runs/239488310) [![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-mysql%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-mysql/master) [![static analysis](https://github.com/yiisoft/db-mysql/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/db-mysql/actions?query=workflow%3A%22static+analysis%22)


## Installation

The package could be installed via composer:

```php
composer require yiisoft/db-mysql
```

## Configuration

Di-Container:

```php
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Helper\Dsn;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileRotatorInterface;
use Yiisoft\Log\Target\File\FileTarget;
use Yiisoft\Profiler\Profiler;

return [
    ContainerInterface::class => static function (ContainerInterface $container) {
        return $container;
    },

    Aliases::class => [
        '@root' => dirname(__DIR__, 1),
        '@data' =>  '@root/tests/Data',
        '@runtime' => '@data/runtime',
    ],

    CacheInterface::class => static function () {
        return new Cache(new ArrayCache());
    },

    FileRotatorInterface::class => static function () {
        return new FileRotator(10);
    },

    LoggerInterface::class => static function (ContainerInterface $container) {
        $aliases = $container->get(Aliases::class);
        $fileRotator = $container->get(FileRotatorInterface::class);

        $fileTarget = new FileTarget(
            $aliases->get('@runtime/logs/app.log'),
            $fileRotator
        );

        $fileTarget->setLevels(
            [
                LogLevel::EMERGENCY,
                LogLevel::ERROR,
                LogLevel::WARNING,
                LogLevel::INFO,
                LogLevel::DEBUG
            ]
        );

        return new Logger(['file' => $fileTarget]);
    },

    Profiler::class => static function (ContainerInterface $container) {
        return new Profiler($container->get(LoggerInterface::class));
    },

    Dsn::class => static function () use ($params) {
        return new Dsn(
            $params['yiisoft/db-mysql']['dsn']['driver'],
            $params['yiisoft/db-mysql']['dsn']['host'],
            $params['yiisoft/db-mysql']['dsn']['dbname'],
            $params['yiisoft/db-mysql']['dsn']['port'],
        );
    },

    ConnectionInterface::class  => static function (ContainerInterface $container) use ($params) {
        $connection = new Connection(
            $container->get(CacheInterface::class),
            $container->get(LoggerInterface::class),
            $container->get(Profiler::class),
            $container->get(Dsn::class)->getDsn(),
        );

        $connection->setUsername($params['yiisoft/db-mysql']['username']);
        $connection->setPassword($params['yiisoft/db-mysql']['password']);

        return $connection;
    }
];
```

Params.php

```php
return [
    'yiisoft/db-mysql' => [
        'dsn' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'dbname' => 'yiitest',
            'port' => '3306'
        ],
        'username' => 'root',
        'password' => 'root'
    ]
];
```

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
./vendor/bin/phpunit
```

Note: You must have MSSQL installed to run the tests, it supports all MSSQL versions.

## Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```php
./vendor/bin/infection
```

## Static analysis

The code is statically analyzed with [Phan](https://github.com/phan/phan/wiki). To run static analysis:

```php
./vendor/bin/phan
```
