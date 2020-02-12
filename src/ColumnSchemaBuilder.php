<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Schemas\ColumnSchemaBuilder as AbstractColumnSchemaBuilder;

/**
 * ColumnSchemaBuilder is the schema builder for MySQL databases.
 */
class ColumnSchemaBuilder extends AbstractColumnSchemaBuilder
{
    /**
     * {@inheritdoc}
     */
    protected function buildUnsignedString(): string
    {
        return $this->isUnsigned ? ' UNSIGNED' : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildAfterString(): string
    {
        return $this->after !== null ? ' AFTER ' . $this->db->quoteColumnName($this->after) : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildFirstString(): string
    {
        return $this->isFirst ? ' FIRST' : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildCommentString(): string
    {
        return $this->comment !== null ? ' COMMENT ' . $this->db->quoteValue($this->comment) : '';
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        switch ($this->getTypeCategory()) {
            case self::CATEGORY_PK:
                $format = '{type}{length}{comment}{check}{append}{pos}';
                break;
            case self::CATEGORY_NUMERIC:
                $format = '{type}{length}{unsigned}{notnull}{unique}{default}{comment}{check}{append}{pos}';
                break;
            default:
                $format = '{type}{length}{notnull}{unique}{default}{comment}{check}{append}{pos}';
        }

        return $this->buildCompleteString($format);
    }
}
