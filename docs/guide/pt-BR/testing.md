# Testing

## Github actions

All our packages have github actions by default, so you can test your [contribution](https://github.com/yiisoft/db-mysql/blob/master/.github/CONTRIBUTING.md) in the cloud.

> Note: We recommend pull requesting in draft mode until all tests pass.

## Docker image

For greater ease it is recommended to use docker containers, for this you can use the [docker-compose.yml](https://docs.docker.com/compose/compose-file/) file that is in the docs folder.

1. [MySQL 8](../../../docker-compose.yml)
2. [MariaDB 10.11](../../../docker-compose-mariadb.yml)

For running the docker containers you can use the following command:

MySQL 8.0.

```dockerfile
docker compose up -d
```

MariaDB 10.11.

```dockerfile
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

- More information can be found in the [Internals.](../../internals.md)
