<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\PDO;

use PDO;
use Yiisoft\Db\Driver\PDO\PDODriver as AbstractPDODriver;

final class PDODriver extends AbstractPDODriver
{
    public function createConnection(): PDO
    {
        $this->attributes += [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $pdo = parent::createConnection();

        if ($this->charset !== null) {
            $pdo->exec('SET NAMES ' . $pdo->quote($this->charset));
        }

        return $pdo;
    }

    public function getDriverName(): string
    {
        return 'mysql';
    }
}
