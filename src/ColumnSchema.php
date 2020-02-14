<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Yiisoft\Db\Expressions\ExpressionInterface;
use Yiisoft\Db\Expressions\JsonExpression;

/**
 * Class ColumnSchema for MySQL database.
 */
class ColumnSchema extends \Yiisoft\Db\Schemas\ColumnSchema
{
    /**
     * {@inheritdoc}
     */
    public function dbTypecast($value)
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if ($this->dbType === Schema::TYPE_JSON) {
            return new JsonExpression($value, $this->type);
        }

        return $this->typecast($value);
    }

    /**
     * {@inheritdoc}
     */
    public function phpTypecast($value)
    {
        if ($value === null) {
            return;
        }

        if ($this->type === Schema::TYPE_JSON) {
            return \json_decode($value, true);
        }

        return parent::phpTypecast($value);
    }
}
