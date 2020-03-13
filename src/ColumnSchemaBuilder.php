<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Schema\ColumnSchemaBuilder as AbstractColumnSchemaBuilder;

/**
 * ColumnSchemaBuilder is the schema builder for MySQL databases.
 */
class ColumnSchemaBuilder extends AbstractColumnSchemaBuilder
{
    protected function buildUnsignedString(): string
    {
        return $this->isUnsigned ? ' UNSIGNED' : '';
    }

    protected function buildAfterString(): string
    {
        return $this->after !== null ? ' AFTER ' . $this->db->quoteColumnName($this->after) : '';
    }

    protected function buildFirstString(): string
    {
        return $this->isFirst ? ' FIRST' : '';
    }

    protected function buildCommentString(): string
    {
        return $this->comment !== null ? ' COMMENT ' . $this->db->quoteValue($this->comment) : '';
    }

    public function __toString(): string
    {
        switch ($this->getTypeCategory()) {
            case self::CATEGORY_PK:
                $format = '{type}{length}{comment}{check}{append}{pos}';
                break;
            case self::CATEGORY_NUMERIC:
                $format = '{type}{length}{unsigned}{notnull}{default}{unique}{comment}{append}{pos}{check}';
                break;
            default:
                $format = '{type}{length}{notnull}{default}{unique}{comment}{append}{pos}{check}';
        }

        return $this->buildCompleteString($format);
    }
}
