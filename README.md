<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="80px">
    </a>
    <a href="https://www.mysql.com/" target="_blank">
        <img src="https://labs.mysql.com/common/logos/mysql-logo.svg" height="80px">
    </a>
    <h1 align="center">Yii DataBase MySQL Extension</h1>
    <br>
</p>

Yii Database MySQL Extension is a database extension for the [Yii Framework] that allows developers to access and interact with [MySQL] and [MariaDb] databases. It provides a set of classes and methods for connecting to a [MySQL] and [MariaDb] database, executing SQL queries, and managing data within the database. The extension is designed to be easy to use and integrate into Yii-based applications, and supports a wide range of [MySQL], [MariaDb] features and functions. It also includes support for transactions, database schema management, and error handling.

It is used in [Yii Framework] but can be used separately.

[MariaDB]: https://mariadb.org/
[MySQL]: https://www.mysql.com/
[Yii Framework]: https://www.yiiframework.com/

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-mysql/v/stable.png)](https://packagist.org/packages/yiisoft/db-mysql)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-mysql/downloads.png)](https://packagist.org/packages/yiisoft/db-mysql)
[![rector](https://github.com/yiisoft/db-mysql/actions/workflows/rector.yml/badge.svg)](https://github.com/yiisoft/db-mysql/actions/workflows/rector.yml)
[![codecov](https://codecov.io/gh/yiisoft/db-mysql/branch/master/graph/badge.svg?token=gsKVx3WQt4)](https://codecov.io/gh/yiisoft/db-mysql)
[![StyleCI](https://github.styleci.io/repos/145220107/shield?branch=master)](https://github.styleci.io/repos/145220107?branch=master)
[![type-coverage](https://shepherd.dev/github/yiisoft/db-mysql/coverage.svg)](https://shepherd.dev/github/yiisoft/db-mysql)

## Support version

|     PHP     |Mysql/MariaDb|  CI-Actions
|:-----------:|:-----------:|:------------:|
| **8.0-8.2** |**5.7-8.0**/**10.4-10.10**|[![build](https://github.com/yiisoft/db-mysql/actions/workflows/build.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-mysql/actions/workflows/build.yml) [![ansi-mode](https://github.com/yiisoft/db-mysql/actions/workflows/ansi-mode.yml/badge.svg)](https://github.com/yiisoft/db-mysql/actions/workflows/ansi-mode.yml) [![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-mysql%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-mysql/master) [![static analysis](https://github.com/yiisoft/db-mysql/actions/workflows/static.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-mysql/actions/workflows/static.yml)

## Installation

The package could be installed via composer:

```php
composer require yiisoft/db-mysql
```

## Usage 

For config connection to Mysql, MariaDb database check [Connecting MySQL/MariaDb](https://github.com/yiisoft/db/blob/master/docs/en/connection/mysql.md).

[Check the documentation docs](https://github.com/yiisoft/db/blob/master/docs/en/getting-started.md) to learn about usage.

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

### Rector

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or 
use either newest or any specific version of PHP: 

```shell
./vendor/bin/rector
```

### Composer require checker

This package uses [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) to check if all dependencies are correctly defined in `composer.json`.

To run the checker, execute the following command:

```shell
./vendor/bin/composer-require-checker
```

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

## License

The Yii DataBase MySQL Extension is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
