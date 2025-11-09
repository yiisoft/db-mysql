<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="80px" alt="Yii">
    </a>
    <a href="https://www.mysql.com/" target="_blank">
        <img src="https://labs.mysql.com/common/logos/mysql-logo.svg" height="80px" alt="MySQL">
    </a>
    <h1 align="center">Yii Database MySQL driver</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-mysql/v)](https://packagist.org/packages/yiisoft/db-mysql)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-mysql/downloads)](https://packagist.org/packages/yiisoft/db-mysql)
[![Build status](https://github.com/yiisoft/db-mysql/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/db-mysql/actions/workflows/build.yml)
[![Code Coverage](https://codecov.io/gh/yiisoft/db-mysql/branch/master/graph/badge.svg?token=UF9VERNMYU)](https://codecov.io/gh/yiisoft/db-mysql)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-mysql%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-mysql/master)
[![static analysis](https://github.com/yiisoft/db-mysql/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/db-mysql/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/db-mysql/coverage.svg)](https://shepherd.dev/github/yiisoft/db-mysql)
[![psalm-level](https://shepherd.dev/github/yiisoft/db-mysql/level.svg)](https://shepherd.dev/github/yiisoft/db-mysql)

MySQL driver for [Yii Database](https://github.com/yiisoft/db) is a package for working with
[MySQL](https://www.mysql.com/) and [MariaDB](https://mariadb.org/) databases in PHP. It includes a database connection
class, a command builder class, and a set of classes for representing database tables and rows as PHP objects.

Driver supports MySQL 5.7 or higher, and MariaDB 10.4 or higher.

## Requirements

- PHP 8.1 - 8.4.
- `pdo_mysql` PHP extension.
- `ctype` PHP extension.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/db-mysql
```

> [!IMPORTANT]
> See also [installation notes](https://github.com/yiisoft/db/?tab=readme-ov-file#installation) for `yiisoft/db`
> package.

## Documentation

For config connection to MySQL and MariaDB database check
[Connection config for MySQL and MariaDB](https://github.com/yiisoft/db/blob/master/docs/guide/en/connection/mysql.md).

Check the `yiisoft/db` [docs](https://github.com/yiisoft/db/blob/master/docs/guide/en/README.md) to learn about usage.

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Database MySQL Driver is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
