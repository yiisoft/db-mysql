<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mysql;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mysql\PDO\QueryBuilderPDOMysql;
use Yiisoft\Db\Query\DDLQueryBuilder as AbstractDDLQueryBuilder;
use Yiisoft\Db\Query\QueryBuilderInterface;

use function array_values;
use function in_array;
use function preg_match;
use function preg_replace;
use function trim;

final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder);
    }

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
     * @throws Exception|InvalidArgumentException
     */
    public function createIndex(string $name, string $table, array|string $columns, bool $unique = false): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ($unique ? ' ADD UNIQUE INDEX ' : ' ADD INDEX ')
            . $this->quoter->quoteTableName($name)
            . ' (' . $this->queryBuilder->buildColumns($columns) . ')';
    }

    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        return 'SET FOREIGN_KEY_CHECKS = ' . ($check ? 1 : 0);
    }

    public function dropCheck(string $name, string $table): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by MySQL.');
    }

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
     * @throws Exception|Throwable
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
     * @todo need work with bit, tinybit and other new not suppoerted types (v.5.7 and later)
     */
//    public function getColumnDefinitionFromSchema($table, $oldName): string
//    {
//        $schema = $this->schema;
//        $tableSchema = $schema->getTableSchema($table, true);
//        if ($tableSchema === null) {
//            throw new InvalidArgumentException("Table not found: $table");
//        }
//
//        $oldColumnSchema = $tableSchema->getColumn($oldName);
//        if ($oldColumnSchema === null) {
//            return '';
//        }
//
//        $columnSchemaBuilder = $this->schema->createColumnSchemaBuilder(
//            $oldColumnSchema->getType(),
//            $oldColumnSchema->getPrecision() ?? $oldColumnSchema->getSize()
//        );
//
//        $defaultValue = $oldColumnSchema->getDefaultValue();
//
//        if ($oldColumnSchema->isAllowNull()) {
//            if ($defaultValue === null) {
//                if (!in_array($oldColumnSchema->getType(), [Schema::TYPE_TEXT, Schema::TYPE_BINARY], true)) {
//                    $columnSchemaBuilder->defaultValue('NULL');
//                }
//                if ($oldColumnSchema->getType() === Schema::TYPE_TIMESTAMP) {
//                    $columnSchemaBuilder->null();
//                }
//            } elseif ($oldColumnSchema->getType() !== Schema::TYPE_BINARY) {
//                $columnSchemaBuilder->defaultValue($defaultValue);
//            }
//        } else {
//            $columnSchemaBuilder->notNull();
//            if ($defaultValue !== null && ($oldColumnSchema->getDbType() !== 'bit(1)' || !empty($defaultValue))) {
//                $columnSchemaBuilder->defaultValue($defaultValue);
//            }
//        }
//
//        if (!empty($oldColumnSchema->getComment())) {
//            $columnSchemaBuilder->comment($oldColumnSchema->getComment());
//        }
//
//        if ($oldColumnSchema->isUnsigned()) {
//            $columnSchemaBuilder->unsigned();
//        }
//
//        if ($oldColumnSchema->isAutoIncrement()) {
//            $columnSchemaBuilder->append('AUTO_INCREMENT');
//        } elseif (!empty($oldColumnSchema->getExtra())) {
//            $columnSchemaBuilder->append($oldColumnSchema->getExtra());
//        }
//
//        return $this->queryBuilder->getColumnType($columnSchemaBuilder);
//    }

    /**
     * Gets column definition.
     *
     * @param string $table table name.
     * @param string $column column name.
     *
     * @throws Exception|Throwable in case when table does not contain column.
     *
     * @return string the column definition.
     * @todo need change to getColumnDefinitionFromSchema with deep research
     */
    public function getColumnDefinition(string $table, string $column): string
    {
        $result = '';
        $quotedTable = $this->quoter->quoteTableName($table);

        /** @var QueryBuilderPDOMysql $qb */
        $qb = $this->queryBuilder;

        /** @var array<array-key, string> $row */
        $row = $qb->command()->setSql('SHOW CREATE TABLE ' . $quotedTable)->queryOne();

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
