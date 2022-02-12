<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Query\DDLQueryBuilder as AbstractDDLQueryBuilder;
use Yiisoft\Db\Query\QueryBuilderInterface;

final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder);
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
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' CHANGE '
            . $this->queryBuilder->quoter()->quoteColumnName($column)
            . ' '
            . $this->queryBuilder->quoter()->quoteColumnName($column)
            . (empty($definition) ? '' : ' ' . $definition)
            . ' COMMENT '
            . $this->queryBuilder->quoter()->quoteValue($comment);

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
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' COMMENT '
            . $this->queryBuilder->quoter()->quoteValue($comment);
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
    public function createIndex(string $name, string $table, array|string $columns, bool $unique = false): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ($unique ? ' ADD UNIQUE INDEX ' : ' ADD INDEX ')
            . $this->queryBuilder->quoter()->quoteTableName($name)
            . ' (' . $this->queryBuilder->buildColumns($columns) . ')';
    }

    public function dropForeignKey(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' DROP FOREIGN KEY '
            . $this->queryBuilder->quoter()->quoteColumnName($name);
    }

    public function dropPrimaryKey(string $name, string $table): string
    {
        return 'ALTER TABLE ' . $this->queryBuilder->quoter()->quoteTableName($table) . ' DROP PRIMARY KEY';
    }

    /**
     * @throws Exception|Throwable
     */
    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        $quotedTable = $this->queryBuilder->quoter()->quoteTableName($table);

        /** @psalm-var array<array-key, string> $row */
        $row = $this->queryBuilder->command()->setSql('SHOW CREATE TABLE ' . $quotedTable)->queryOne();

        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }

        if (preg_match_all('/^\s*`(.*?)`\s+(.*?),?$/m', $sql, $matches)) {
            foreach ($matches[1] as $i => $c) {
                if ($c === $oldName) {
                    return "ALTER TABLE $quotedTable CHANGE "
                        . $this->queryBuilder->quoter()->quoteColumnName($oldName) . ' '
                        . $this->queryBuilder->quoter()->quoteColumnName($newName) . ' '
                        . $matches[2][$i];
                }
            }
        }

        /* try to give back a SQL anyway */
        return "ALTER TABLE $quotedTable CHANGE "
            . $this->queryBuilder->quoter()->quoteColumnName($oldName) . ' '
            . $this->queryBuilder->quoter()->quoteColumnName($newName);
    }

    /**
     * Gets column definition.
     *
     * @param string $table table name.
     * @param string $column column name.
     *
     * @throws Exception|Throwable in case when table does not contain column.
     *
     * @return string the column definition.
     */
    private function getColumnDefinition(string $table, string $column): string
    {
        $result = '';
        $quotedTable = $this->queryBuilder->quoter()->quoteTableName($table);

        /** @var array<array-key, string> $row */
        $row = $this->queryBuilder->command()->setSql('SHOW CREATE TABLE ' . $quotedTable)->queryOne();

        if (!isset($row['Create Table'])) {
            $row = array_values($row);
            $sql = $row[1];
        } else {
            $sql = $row['Create Table'];
        }

        if (preg_match_all('/^\s*`(.*?)`\s+(.*?),?$/m', $sql, $matches)) {
            foreach ($matches[1] as $i => $c) {
                if ($c === $column) {
                    $result = $matches[2][$i];
                }
            }
        }

        return $result;
    }
}
