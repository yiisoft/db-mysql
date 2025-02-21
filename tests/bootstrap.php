<?php

declare(strict_types=1);

if (getenv('ENVIRONMENT', true) === 'local') {
    putenv('YII_MYSQL_DATABASE=yii');
    putenv('YII_MYSQL_HOST=mysql');
    putenv('YII_MYSQL_PASSWORD=root');
}
