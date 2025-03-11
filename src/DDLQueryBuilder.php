<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder;

use function preg_match;
use function preg_quote;
use function preg_replace;
use function trim;

/**
 * Implements a (Data Definition Language) SQL statements for MySQL, MariaDB.
 */
final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    /**
     * @throws NotSupportedException
     */
    public function addCheck(string $table, string $name, string $expression): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    /**
     * @throws Throwable
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        /** @var string $definition Strip existing comment which may include escaped quotes */
        $definition = preg_replace("/COMMENT '(?:''|[^'])*'/i", '', $this->getColumnDefinition($table, $column));
        $definition = trim($definition);

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
            . $this->quoter->quoteValue($comment);

        if ($check === 1) {
            $alterSql .= ' ' . $checkMatches[0];
        }

        return $alterSql;
    }

    public function addCommentOnTable(string $table, string $comment): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' COMMENT '
            . $this->quoter->quoteValue($comment);
    }

    public function addDefaultValue(string $table, string $name, string $column, mixed $value): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    public function createIndex(
        string $table,
        string $name,
        array|string $columns,
        ?string $indexType = null,
        ?string $indexMethod = null
    ): string {
        return 'CREATE ' . (!empty($indexType) ? $indexType . ' ' : '') . 'INDEX '
            . $this->quoter->quoteTableName($name)
            . (!empty($indexMethod) ? " USING $indexMethod" : '')
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
    public function dropCheck(string $table, string $name): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    /**
     * @throws Exception
     * @throws Throwable
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

    public function dropDefaultValue(string $table, string $name): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    public function dropForeignKey(string $table, string $name): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' DROP FOREIGN KEY '
            . $this->quoter->quoteColumnName($name);
    }

    public function dropPrimaryKey(string $table, string $name): string
    {
        return 'ALTER TABLE ' . $this->quoter->quoteTableName($table) . ' DROP PRIMARY KEY';
    }

    public function dropUnique(string $table, string $name): string
    {
        return $this->dropIndex($table, $name);
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        $quotedTable = $this->quoter->quoteTableName($table);

        $columnDefinition = $this->getColumnDefinition($table, $oldName);

        /* try to give back an SQL anyway */
        return "ALTER TABLE $quotedTable CHANGE "
            . $this->quoter->quoteColumnName($oldName) . ' '
            . $this->quoter->quoteColumnName($newName)
            . (!empty($columnDefinition) ? ' ' . $columnDefinition : '');
    }

    /**
     * Gets column definition.
     *
     * @param string $table The table name.
     * @param string $column The column name.
     *
     * @return string The column definition or empty string in case when schema does not contain the table
     * or the table doesn't contain the column.
     */
    private function getColumnDefinition(string $table, string $column): string
    {
        $sql = $this->schema->getTableSchema($table)?->getCreateSql();

        if (empty($sql)) {
            return '';
        }

        $quotedColumn = preg_quote($column, '/');

        if (preg_match("/^\s*([`\"])$quotedColumn\\1\s+(.*?),?$/m", $sql, $matches) !== 1) {
            return '';
        }

        return $matches[2];
    }

    /**
     * @throws NotSupportedException MySQL doesn't support cascade drop table.
     */
    public function dropTable(string $table, bool $ifExists = false, bool $cascade = false): string
    {
        if ($cascade) {
            throw new NotSupportedException('MySQL doesn\'t support cascade drop table.');
        }
        return parent::dropTable($table, $ifExists, false);
    }
}
