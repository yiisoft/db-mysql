<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function preg_match;
use function preg_replace;
use function trim;

final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
        parent::__construct($queryBuilder, $quoter, $schema);
    }

    /**
     * @throws NotSupportedException
     */
    public function addCheck(string $name, string $table, string $expression): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    /**
     * @throws Exception|Throwable
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        /* Strip existing comment which may include escaped quotes */
        $definition = trim(
            preg_replace(
                "/COMMENT '(?:''|[^'])*'/i",
                '',
                $this->getColumnDefinition($table, $column)
            )
        );

        $checkRegex = '/CHECK *(\(([^()]|(?-2))*\))/';
        $check = preg_match($checkRegex, $definition, $checkMatches);

        if ($check === 1) {
            $definition = preg_replace($checkRegex, '', $definition);
        }

        $alterSql = 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' CHANGE '
            . $this->quoter->quoteColumnName($column)
            . ' '
            . $this->quoter->quoteColumnName($column)
            . (empty($definition) ? '' : ' ' . $definition)
            . ' COMMENT '
            . (string) $this->quoter->quoteValue($comment);

        if ($check === 1) {
            $alterSql .= ' ' . $checkMatches[0];
        }

        return $alterSql;
    }

    /**
     * @throws \Exception
     */
    public function addCommentOnTable(string $table, string $comment): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' COMMENT '
            . (string) $this->quoter->quoteValue($comment);
    }

    /**
     * @throws NotSupportedException
     */
    public function addDefaultValue(string $name, string $table, string $column, mixed $value): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function createIndex(
        string $name,
        string $table,
        array|string $columns,
        string $indexType = null,
        string $indexMethod = null
    ): string {
        return 'CREATE ' . ($indexType ? ($indexType . ' ') : '') . 'INDEX '
            . $this->quoter->quoteTableName($name)
            . ($indexMethod !== null ? " USING $indexMethod" : '')
            . ' ON ' . $this->quoter->quoteTableName($table)
            . ' (' . $this->queryBuilder->buildColumns($columns) . ')';
    }

    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        return 'SET FOREIGN_KEY_CHECKS = ' . ($check ? 1 : 0);
    }

    /**
     * @throws NotSupportedException
     */
    public function dropCheck(string $name, string $table): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    /**
     * @throws Exception|Throwable
     */
    public function dropCommentFromColumn(string $table, string $column): string
    {
        return $this->addCommentOnColumn($table, $column, '');
    }

    /**
     * @throws \Exception
     */
    public function dropCommentFromTable(string $table): string
    {
        return $this->addCommentOnTable($table, '');
    }

    /**
     * @throws NotSupportedException
     */
    public function dropDefaultValue(string $name, string $table): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    public function dropForeignKey(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' DROP FOREIGN KEY '
            . $this->quoter->quoteColumnName($name);
    }

    public function dropPrimaryKey(string $name, string $table): string
    {
        return 'ALTER TABLE ' . $this->quoter->quoteTableName($table) . ' DROP PRIMARY KEY';
    }

    public function dropUnique(string $name, string $table): string
    {
        return $this->dropIndex($name, $table);
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        $quotedTable = $this->quoter->quoteTableName($table);

        $columnDefinition = $this->getColumnDefinition($table, $oldName);

        /* try to give back a SQL anyway */
        return "ALTER TABLE $quotedTable CHANGE "
            . $this->quoter->quoteColumnName($oldName) . ' '
            . $this->quoter->quoteColumnName($newName)
            . (!empty($columnDefinition) ? ' ' . $columnDefinition : '');
    }

    /**
     * Gets column definition.
     *
     * @param string $table table name.
     * @param string $column column name.
     *
     * @throws Throwable in case when table does not contain column.
     *
     * @return string the column definition.
     */
    public function getColumnDefinition(string $table, string $column): string
    {
        $result = '';

        $sql = $this->schema->getTableSchema($table)?->getCreateSql();

        if (empty($sql)) {
            return '';
        }

        if (preg_match_all('/^\s*([`"])(.*?)\\1\s+(.*?),?$/m', $sql, $matches)) {
            foreach ($matches[2] as $i => $c) {
                if ($c === $column) {
                    $result = $matches[3][$i];
                }
            }
        }

        return $result;
    }
}
