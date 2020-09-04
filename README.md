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
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-mysql%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-mysql/master)
[![static analysis](https://github.com/yiisoft/db-mysql/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/db-mysql/actions?query=workflow%3A%22static+analysis%22)


## Support version

|  PHP | Version                  | Test   | SO  |
|:----:|:------------------------:|:------:|:---:|
|![PHP-7](https://img.shields.io/badge/7:latest-blue) ![PHP-8](https://img.shields.io/badge/8:latest-blue)| ![Mysql-5.7](https://img.shields.io/badge/5:latest-blue) ![Mysql-8.0](https://img.shields.io/badge/8:latest-blue)|[![Build status](https://github.com/yiisoft/db-mssql/workflows/build/badge.svg)](https://github.com/yiisoft/db-mysql/actions/runs/239488310)| ![Linux](https://img.shields.io/badge/ubuntu-blue)|


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
