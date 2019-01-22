Docker setup for tests
======================

This docker setup allows you to run tests for this repository in a pre-configured environement.

Start the containers and get a shell into the PHP container:
```shell
$ docker-compose up --build -d
Recreating docker_db_1 ... done
Recreating docker_web_1 ... done

$ docker-compose exec web bash
```

In the container, create the `vendor/yiisoft/yii-core/config/tests/config.local.php` file
with the following contents:

```php
<?php

$config['database']['mysql]['dsn'] = 'mysql:host=db;dbname=yiitest';
```

then install the composer dependencies and run tests:
```shell
$ composer install
$ ./vendor/bin/phpunit
```
