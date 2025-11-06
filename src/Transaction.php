<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Driver\Pdo\AbstractPdoTransaction;

/**
 * Implements the MySQL, MariaDB specific transaction.
 */
final class Transaction extends AbstractPdoTransaction {}
