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


## Support version

|  PHP | Mysql Version            |  CI-Actions
|:----:|:------------------------:|:---:|
|**7.4 - 8.0**| **5.7 - 8.0**|[![Build status](https://github.com/yiisoft/db-mysql/workflows/build/badge.svg)](https://github.com/yiisoft/db-mysql/actions?query=workflow%3Abuild) [![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-mysql%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-mysql/master) [![static analysis](https://github.com/yiisoft/db-mysql/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/db-mysql/actions?query=workflow%3A%22static+analysis%22) [![type-coverage](https://shepherd.dev/github/yiisoft/db-mysql/coverage.svg)](https://shepherd.dev/github/yiisoft/db-mysql)


## Installation

The package could be installed via composer:

```php
composer require yiisoft/db-mysql
```

## Configuration

Using yiisoft/composer-config-plugin automatically get the settings of `CacheInterface::class`, `LoggerInterface::class`, and `Profiler::class`.

Di-Container:

```php
use Yiisoft\Db\Mysql\Connection as MysqlConnection;

return [
    MysqlConnection::class => [
        '__class' => MysqlConnection::class,
        '__construct()' => [
            'dsn' => $params['yiisoft/db-mysql']['dsn']
        ],
        'setUsername()' => [$params['yiisoft/db-mysql']['username']],
        'setPassword()' => [$params['yiisoft/db-mysql']['password']]
    ]
];
```

Params.php

```php
use Yiisoft\Db\Connection\Dsn;

return [
    'yiisoft/db-mysql' => [
        'dsn' => (new Dsn('mysql', '127.0.0.1', 'yiitest', '3306'))->asString(),
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

Note: You must have MYSQL installed to run the tests, it supports all MYSQL versions.

## Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```php
./vendor/bin/infection
```

## Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/docs/). To run static analysis:

```php
./vendor/bin/psalm
```
