<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

/**
 * Defines the available index types for {@see DDLQueryBuilder::createIndex()} method.
 */
final class IndexType
{
    /**
     * Define the type of the index as `UNIQUE`.
     */
    public const UNIQUE = 'UNIQUE';
    /**
     * Define the type of the index as `FULLTEXT`.
     */
    public const FULLTEXT = 'FULLTEXT';
    /**
     * Define the type of the index as `SPATIAL`.
     */
    public const SPATIAL = 'SPATIAL';
}
