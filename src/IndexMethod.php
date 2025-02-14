<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

/**
 * Defines the available index methods for {@see DDLQueryBuilder::createIndex()} method.
 */
final class IndexMethod
{
    /**
     * Define the method of the index as `BTREE`.
     */
    public const BTREE = 'BTREE';
    /**
     * Define the method of the index as `HASH`.
     */
    public const HASH = 'HASH';
}
