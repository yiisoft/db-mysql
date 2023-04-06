# Testing

## Github actions

All our packages have github actions by default, so you can test your [contribution](https://github.com/yiisoft/db/blob/master/.github/CONTRIBUTING.md) in the cloud.

> Note: We recommend pull requesting in draft mode until all tests pass.

## Docker image

For greater ease it is recommended to use docker containers, for this you can use the [docker-compose.yml](https://docs.docker.com/compose/compose-file/) file that is in the docs folder.

1. [MySQL 8](/docker-compose.yml)
2. [MariaDB 10.11](/docker-compose-mariadb.yml)

For running the docker containers you can use the following command:

MySQL 8.0.

```shell
docker compose up -d
```

MariaDB 10.11.

```shell
docker compose -f docker-compose-mariadb.yml up -d
```

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/).

The following steps are required to run the tests:

1. Run the docker container for the dbms.
2. Install the dependencies of the project with composer.
3. Run the tests.

```shell
vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

## Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## Rector

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or 
use either newest or any specific version of PHP: 

```shell
./vendor/bin/rector
```

## Composer require checker

This package uses [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) to check if all dependencies are correctly defined in `composer.json`.

To run the checker, execute the following command:

```shell
./vendor/bin/composer-require-checker
```
