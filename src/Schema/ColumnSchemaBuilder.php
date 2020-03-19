<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Schema;

use Yiisoft\Db\Schema\ColumnSchemaBuilder as AbstractColumnSchemaBuilder;

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
        return $this->isUnsigned() ? ' UNSIGNED' : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildAfterString(): string
    {
        return $this->getAfter() !== null ? ' AFTER ' . $this->getDb()->quoteColumnName($this->getAfter()) : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildFirstString(): string
    {
        return $this->isFirst() ? ' FIRST' : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildCommentString(): string
    {
        return $this->getComment() !== null ? ' COMMENT ' . $this->getDb()->quoteValue($this->getComment()) : '';
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
                $format = '{type}{length}{unsigned}{notnull}{default}{unique}{comment}{append}{pos}{check}';
                break;
            default:
                $format = '{type}{length}{notnull}{default}{unique}{comment}{append}{pos}{check}';
        }

        return $this->buildCompleteString($format);
    }
}
