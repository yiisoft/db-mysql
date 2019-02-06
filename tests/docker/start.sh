#!/bin/bash

docker-compose up --build -d
docker-compose exec web bash -c "composer install"
docker-compose exec web bash -c 'echo "<?php \$config[\"databases\"][\"mysql\"][\"dsn\"] = \"mysql:host=db;dbname=yiitest;\"; \$config[\"databases\"][\"mysql\"][\"password\"] = \"travis\" ?>" > vendor/yiisoft/yii-core/config/tests/config.local.php'
docker-compose exec web bash
