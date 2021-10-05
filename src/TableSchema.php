<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Schema\TableSchema as AbstractTableSchema;

/**
 * The class TableSchema represents the metadata of a database table.
 *
 * @property array $columnNames List of column names. This property is read-only.
 */
final class TableSchema extends AbstractTableSchema
{
    private ?string $comment = null;
    private array $foreignKeys = [];

    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @return array foreign keys of this table. Each array element is of the following structure:
     *
     * ```php
     * [
     *  'ForeignTableName',
     *  'fk1' => 'pk1',  // pk1 is in foreign table
     *  'fk2' => 'pk2',  // if composite foreign key
     * ]
     * ```
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function comment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function foreignKey(string $id, array $to): void
    {
        $this->foreignKeys[$id] = $to;
    }

    public function foreignKeys(array $value): void
    {
        $this->foreignKeys = $value;
    }
}
