<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql\Query;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Mysql\Expression\JsonExpressionBuilder;
use Yiisoft\Db\Mysql\Schema\Schema;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilder as AbstractQueryBuilder;

class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    protected array $typeMap = [
        Schema::TYPE_PK => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_UPK => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_BIGPK => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_CHAR => 'char(1)',
        Schema::TYPE_STRING => 'varchar(255)',
        Schema::TYPE_TEXT => 'text',
        Schema::TYPE_TINYINT => 'tinyint(3)',
        Schema::TYPE_SMALLINT => 'smallint(6)',
        Schema::TYPE_INTEGER => 'int(11)',
        Schema::TYPE_BIGINT => 'bigint(20)',
        Schema::TYPE_FLOAT => 'float',
        Schema::TYPE_DOUBLE => 'double',
        Schema::TYPE_DECIMAL => 'decimal(10,0)',
        Schema::TYPE_DATETIME => 'datetime',
        Schema::TYPE_TIMESTAMP => 'timestamp',
        Schema::TYPE_TIME => 'time',
        Schema::TYPE_DATE => 'date',
        Schema::TYPE_BINARY => 'blob',
        Schema::TYPE_BOOLEAN => 'tinyint(1)',
        Schema::TYPE_MONEY => 'decimal(19,4)',
        Schema::TYPE_JSON => 'json',
    ];

    /**
     * Contains array of default expression builders. Extend this method and override it, if you want to change default
     * expression builders for this query builder.
     *
     * @return array
     *
     * See {@see \Yiisoft\Db\Expression\ExpressionBuilder} docs for details.
     */
    protected function defaultExpressionBuilders(): array
    {
        return \array_merge(parent::defaultExpressionBuilders(), [
            JsonExpression::class => JsonExpressionBuilder::class,
        ]);
    }

    /**
     * Builds a SQL statement for renaming a column.
     *
     * @param string $table   the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $oldName the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     *
     * @throws Exception
     *
     * @return string the SQL statement for renaming a DB column.
     */
    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        $quotedTable = $this->db->quoteTableName($table);

        $row = $this->db->createCommand('SHOW CREATE TABLE ' . $quotedTable)->queryOne();

        if ($row === false) {
            throw new Exception("Unable to find column '$oldName' in table '$table'.");
        }

        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = \array_values($row);
            $sql = $row[1];
        }

        if (\preg_match_all('/^\s*`(.*?)`\s+(.*?),?$/m', $sql, $matches)) {
            foreach ($matches[1] as $i => $c) {
                if ($c === $oldName) {
                    return "ALTER TABLE $quotedTable CHANGE "
                        . $this->db->quoteColumnName($oldName) . ' '
                        . $this->db->quoteColumnName($newName) . ' '
                        . $matches[2][$i];
                }
            }
        }

        // try to give back a SQL anyway
        return "ALTER TABLE $quotedTable CHANGE "
            . $this->db->quoteColumnName($oldName) . ' '
            . $this->db->quoteColumnName($newName);
    }

    /**
     * Builds a SQL statement for creating a new index.
     *
     * @param string $name the name of the index. The name will be properly quoted by the method.
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by
     * the method.
     * @param string|array $columns the column(s) that should be included in the index. If there are multiple columns,
     * separate them with commas or use an array to represent them. Each column name will be properly quoted by the
     * method, unless a parenthesis is found in the name.
     * @param bool $unique whether to add UNIQUE constraint on the created index.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for creating a new index.
     *
     * {@see https://bugs.mysql.com/bug.php?id=48875}
     */
    public function createIndex(string $name, string $table, $columns, bool $unique = false): string
    {
        return 'ALTER TABLE '
            . $this->db->quoteTableName($table)
            . ($unique ? ' ADD UNIQUE INDEX ' : ' ADD INDEX ')
            . $this->db->quoteTableName($name)
            . ' (' . $this->buildColumns($columns) . ')';
    }

    /**
     * Builds a SQL statement for dropping a foreign key constraint.
     *
     * @param string $name the name of the foreign key constraint to be dropped. The name will be properly quoted by the
     * method.
     * @param string $table the table whose foreign is to be dropped. The name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for dropping a foreign key constraint.
     */
    public function dropForeignKey(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->db->quoteTableName($table)
            . ' DROP FOREIGN KEY ' . $this->db->quoteColumnName($name);
    }

    /**
     * Builds a SQL statement for removing a primary key constraint to an existing table.
     *
     * @param string $name the name of the primary key constraint to be removed.
     * @param string $table the table that the primary key constraint will be removed from.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for removing a primary key constraint from an existing table.
     */
    public function dropPrimaryKey(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->db->quoteTableName($table) . ' DROP PRIMARY KEY';
    }

    /**
     * Creates a SQL command for dropping an unique constraint.
     *
     * @param string $name the name of the unique constraint to be dropped. The name will be properly quoted by the
     * method.
     * @param string $table the table whose unique constraint is to be dropped. The name will be properly quoted by the
     * method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for dropping an unique constraint.
     */
    public function dropUnique(string $name, string $table): string
    {
        return $this->dropIndex($name, $table);
    }

    /**
     * @param string $name
     * @param string $table
     * @param string $expression
     *
     * @throws NotSupportedException Method not supported by Mysql.
     *
     * @return string the SQL statement for adding a check constraint to an existing table.
     */
    public function addCheck(string $name, string $table, string $expression): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    /**
     * @param string $name
     * @param string $table
     *
     * @throws NotSupportedException Method not supported by Mysql.
     *
     * @return string the SQL statement for adding a check constraint to an existing table.
     */
    public function dropCheck(string $name, string $table): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

    /**
     * Creates a SQL statement for resetting the sequence value of a table's primary key.
     *
     * The sequence will be reset such that the primary key of the next new row inserted will have the specified value
     * or 1.
     *
     * @param string $tableName the name of the table whose primary key sequence will be reset.
     * @param mixed $value the value for the primary key of the next new row inserted. If this is not set, the next new
     * row's primary key will have a value 1.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for resetting sequence.
     */
    public function resetSequence(string $tableName, $value = null): string
    {
        $table = $this->db->getTableSchema($tableName);

        if ($table !== null && $table->getSequenceName() !== null) {
            $tableName = $this->db->quoteTableName($tableName);

            if ($value === null) {
                $pk = $table->getPrimaryKey();
                $key = \reset($pk);
                $value = $this->db->createCommand("SELECT MAX(`$key`) FROM $tableName")->queryScalar() + 1;
            } else {
                $value = (int) $value;
            }

            return "ALTER TABLE $tableName AUTO_INCREMENT=$value";
        }

        if ($table === null) {
            throw new \InvalidArgumentException("Table not found: $tableName");
        }

        throw new \InvalidArgumentException("There is no sequence associated with table '$tableName'.");
    }

    /**
     * Builds a SQL statement for enabling or disabling integrity check.
     *
     * @param bool $check  whether to turn on or off the integrity check.
     * @param string $schema the schema of the tables. Meaningless for MySQL.
     * @param string $table  the table name. Meaningless for MySQL.
     *
     * @return string the SQL statement for checking integrity.
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        return 'SET FOREIGN_KEY_CHECKS = ' . ($check ? 1 : 0);
    }

    /**
     * @param int|object|null $limit
     * @param int|object|null $offset
     *
     * @return string the LIMIT and OFFSET clauses.
     */
    public function buildLimit($limit, $offset): string
    {
        $sql = '';

        if ($this->hasLimit($limit)) {
            $sql = 'LIMIT ' . $limit;

            if ($this->hasOffset($offset)) {
                $sql .= ' OFFSET ' . $offset;
            }
        } elseif ($this->hasOffset($offset)) {
            /**
             * limit is not optional in MySQL.
             *
             * http://stackoverflow.com/a/271650/1106908
             * http://dev.mysql.com/doc/refman/5.0/en/select.html#idm47619502796240
             */
            $sql = "LIMIT $offset, 18446744073709551615"; // 2^64-1
        }

        return $sql;
    }

    /**
     * Checks to see if the given limit is effective.
     *
     * @param mixed $limit the given limit.
     *
     * @return bool whether the limit is effective.
     */
    protected function hasLimit($limit): bool
    {
        /** In MySQL limit argument must be non negative integer constant */
        return \ctype_digit((string) $limit);
    }

    /**
     * Checks to see if the given offset is effective.
     *
     * @param mixed $offset the given offset.
     *
     * @return bool whether the offset is effective.
     */
    protected function hasOffset($offset): bool
    {
        /** In MySQL offset argument must be non negative integer constant */
        $offset = (string) $offset;

        return \ctype_digit($offset) && $offset !== '0';
    }

    /**
     * Prepares a `VALUES` part for an `INSERT` SQL statement.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array|Query $columns the column data (name => value) to be inserted into the table or instance of
     * {@see \Yiisoft\Db\Query\Query|Query} to perform INSERT INTO ... SELECT SQL statement.
     * @param array $params the binding parameters that will be generated by this method. They should be bound to the DB
     * command later.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return array array of column names, placeholders, values and params.
     */
    protected function prepareInsertValues(string $table, $columns, array $params = []): array
    {
        [$names, $placeholders, $values, $params] = parent::prepareInsertValues($table, $columns, $params);
        if (!$columns instanceof Query && empty($names)) {
            $tableSchema = $this->db->getSchema()->getTableSchema($table);
            $columns = $tableSchema->getColumns();
            if ($tableSchema !== null) {
                $columns = !empty($tableSchema->getPrimaryKey())
                    ? $tableSchema->getPrimaryKey() : [\reset($columns)->getName()];
                foreach ($columns as $name) {
                    $names[] = $this->db->quoteColumnName($name);
                    $placeholders[] = 'DEFAULT';
                }
            }
        }

        return [$names, $placeholders, $values, $params];
    }

    /**
     * Creates an SQL statement to insert rows into a database table if they do not already exist (matching unique
     * constraints), or update them if they do.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->upsert('pages', [
     *     'name' => 'Front page',
     *     'url' => 'http://example.com/', // url is unique
     *     'visits' => 0,
     * ], [
     *     'visits' => new \Yiisoft\Db\Expression('visits + 1'),
     * ], $params);
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table that new rows will be inserted into/updated in.
     * @param array|Query $insertColumns the column data (name => value) to be inserted into the table or instance of
     * {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns the column data (name => value) to be updated if they already exist. If `true`
     * is passed, the column data will be updated to match the insert column data. If `false` is passed, no update will
     * be performed if the column data already exists.
     * @param array $params the binding parameters that will be generated by this method. They should be bound to the DB
     * command later.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException if this is not supported by the underlying DBMS.
     *
     * @return string the resulting SQL.
     */
    public function upsert(string $table, $insertColumns, $updateColumns, array &$params): string
    {
        $insertSql = $this->insert($table, $insertColumns, $params);

        [$uniqueNames, , $updateNames] = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns);

        if (empty($uniqueNames)) {
            return $insertSql;
        }

        if ($updateColumns === true) {
            $updateColumns = [];
            foreach ($updateNames as $name) {
                $updateColumns[$name] = new Expression('VALUES(' . $this->db->quoteColumnName($name) . ')');
            }
        } elseif ($updateColumns === false) {
            $name = $this->db->quoteColumnName(\reset($uniqueNames));
            $updateColumns = [$name => new Expression($this->db->quoteTableName($table) . '.' . $name)];
        }

        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        return $insertSql . ' ON DUPLICATE KEY UPDATE ' . \implode(', ', $updates);
    }

    /**
     * Builds a SQL command for adding comment to column.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the
     * method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for adding comment on column.
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        /** Strip existing comment which may include escaped quotes */
        $definition = \trim(
            \preg_replace(
                "/COMMENT '(?:''|[^'])*'/i",
                '',
                $this->getColumnDefinition($table, $column)
            )
        );

        $checkRegex = '/CHECK *(\(([^()]|(?-2))*\))/';

        $check = \preg_match($checkRegex, $definition, $checkMatches);

        if ($check === 1) {
            $definition = \preg_replace($checkRegex, '', $definition);
        }

        $alterSql = 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' CHANGE ' . $this->db->quoteColumnName($column)
            . ' ' . $this->db->quoteColumnName($column)
            . (empty($definition) ? '' : ' ' . $definition)
            . ' COMMENT ' . $this->db->quoteValue($comment);

        if ($check === 1) {
            $alterSql .= ' ' . $checkMatches[0];
        }

        return $alterSql;
    }

    /**
     * Builds a SQL command for adding comment to table.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for adding comment on table.
     */
    public function addCommentOnTable(string $table, string $comment): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' COMMENT ' . $this->db->quoteValue($comment);
    }

    /**
     * Builds a SQL command for adding comment to column.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the
     * method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for adding comment on column.
     */
    public function dropCommentFromColumn(string $table, string $column): string
    {
        return $this->addCommentOnColumn($table, $column, '');
    }

    /**
     * Builds a SQL command for adding comment to table.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the SQL statement for adding comment on column.
     */
    public function dropCommentFromTable(string $table): string
    {
        return $this->addCommentOnTable($table, '');
    }

    /**
     * Gets column definition.
     *
     * @param string $table table name.
     * @param string $column column name.
     *
     * @throws Exception in case when table does not contain column.
     *
     * @return string|null the column definition.
     */
    private function getColumnDefinition(string $table, string $column): ?string
    {
        $quotedTable = $this->db->quoteTableName($table);

        $row = $this->db->createCommand('SHOW CREATE TABLE ' . $quotedTable)->queryOne();

        if ($row === false) {
            throw new Exception("Unable to find column '$column' in table '$table'.");
        }

        if (!isset($row['Create Table'])) {
            $row = \array_values($row);
            $sql = $row[1];
        } else {
            $sql = $row['Create Table'];
        }

        if (\preg_match_all('/^\s*`(.*?)`\s+(.*?),?$/m', $sql, $matches)) {
            foreach ($matches[1] as $i => $c) {
                if ($c === $column) {
                    return $matches[2][$i];
                }
            }
        }
    }
}
