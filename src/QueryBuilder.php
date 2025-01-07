<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Connection\ServerInfoInterface;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Mysql\Column\ColumnDefinitionBuilder;
use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * Implements MySQL, MariaDB specific query builder.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    public function __construct(QuoterInterface $quoter, SchemaInterface $schema, ServerInfoInterface $serverInfo)
    {
        parent::__construct(
            $quoter,
            $schema,
            $serverInfo,
            new DDLQueryBuilder($this, $quoter, $schema),
            new DMLQueryBuilder($this, $quoter, $schema),
            new DQLQueryBuilder($this, $quoter),
            new ColumnDefinitionBuilder($this),
        );
    }
}
