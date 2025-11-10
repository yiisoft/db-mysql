# MySQL/MariaDB driver for Yii Database Change Log

## 2.0.0 under development

- Chg #401: Use `\InvalidArgumentException` instead of `Yiisoft\Db\Exception\InvalidArgumentException` (@DikoIbragimov)
- Enh #320: Minor refactoring of `DDLQueryBuilder::getColumnDefinition()` method (@Tigrov)
- Bug #320: Change visibility of `DDLQueryBuilder::getColumnDefinition()` method to `private` (@Tigrov)
- Enh #321, #391: Implement and use `SqlParser` class (@Tigrov)
- Chg #339: Replace call of `SchemaInterface::getRawTableName()` to `QuoterInterface::getRawTableName()` (@Tigrov)
- New #342, #430: Add JSON overlaps condition builder (@Tigrov)
- Enh #344: Update `bit` type according to main PR yiisoft/db#860 (@Tigrov)
- New #346, #361: Implement `ColumnFactory` class (@Tigrov)
- Enh #347, #353: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov)
- Bug #349, #352: Restore connection if closed by connection timeout (@Tigrov)
- Enh #354: Separate column type constants (@Tigrov)
- New #355: Realize `ColumnBuilder` class (@Tigrov)
- Enh #357: Update according changes in `ColumnSchemaInterface` (@Tigrov)
- New #358, #365: Add `ColumnDefinitionBuilder` class (@Tigrov)
- Enh #359, #410: Refactor `Dsn` class (@Tigrov)
- Enh #361, #362: Refactor `Schema::findColumns()` method (@Tigrov)
- Enh #363: Refactor `Schema::normalizeDefaultValue()` method and move it to `ColumnFactory` class (@Tigrov)
- Enh #366: Refactor `Quoter::quoteValue()` method (@Tigrov)
- Chg #368: Update `QueryBuilder` constructor (@Tigrov)
- Enh #367: Use `ColumnDefinitionBuilder` to generate table column SQL representation (@Tigrov)
- Enh #371: Remove `ColumnInterface` (@Tigrov)
- Enh #372: Rename `ColumnSchemaInterface` to `ColumnInterface` (@Tigrov)
- Enh #373: Replace `DbArrayHelper::getColumn()` with `array_column()` (@Tigrov)
- New #374: Add `IndexType` and `IndexMethod` classes (@Tigrov)
- Enh #376: Move `JsonExpressionBuilder` and JSON type tests to `yiisoft/db` package (@Tigrov)
- Bug #377: Explicitly mark nullable parameters (@vjik)
- Chg #378: Change supported PHP versions to `8.1 - 8.4` (@Tigrov)
- Enh #378: Minor refactoring (@Tigrov)
- New #379: Add parameters `$ifExists` and `$cascade` to `CommandInterface::dropTable()` and
  `DDLQueryBuilderInterface::dropTable()` methods (@vjik)
- Chg #382: Remove `yiisoft/json` dependency (@Tigrov)
- Enh #384, #413: Refactor according changes in `db` package (@Tigrov)
- New #383: Add `caseSensitive` option to like condition (@vjik)
- Enh #386: Remove `getCacheKey()` and `getCacheTag()` methods from `Schema` class (@Tigrov)
- Bug #388: Set empty `comment` and `extra` properties to `null` when loading table columns (@Tigrov)
- Enh #389, #390: Use `DbArrayHelper::arrange()` instead of `DbArrayHelper::index()` method (@Tigrov)
- New #387: Realize `Schema::loadResultColumn()` method (@Tigrov)
- New #393: Use `DateTimeColumn` class for datetime column types (@Tigrov)
- Enh #396, #409: Refactor constraints (@Tigrov)
- New #394, #395, #398, #425, #435, #437: Implement `Command::upsertReturning()` method (@Tigrov, @vjik)
- Enh #394, #395: Refactor `Command::insertWithReturningPks()` method (@Tigrov)
- Chg #399: Rename `insertWithReturningPks()` to `insertReturningPks()` in `Command` and `DMLQueryBuilder` classes (@Tigrov)
- Enh #403: Refactor `DMLQueryBuilder::upsert()`, allow use `EXCLUDED` table alias to access inserted values (@Tigrov)
- Enh #405: Provide `yiisoft/db-implementation` virtual package (@vjik)
- Enh #407, #408, #411: Adapt to conditions refactoring in `yiisoft/db` package (@vjik)
- Enh #414: Remove `TableSchema` class and refactor `Schema` class (@Tigrov)
- Enh #415: Support column's collation (@Tigrov)
- New #421: Add `Connection::getColumnBuilderClass()` method (@Tigrov)
- New #420, #427: Implement `ArrayMergeBuilder`, `LongestBuilder` and `ShortestBuilder` classes (@Tigrov)
- Enh #423: Refactor `DMLQueryBuilder::upsert()` method (@Tigrov)
- Chg #428: Update expression namespaces according to changes in `yiisoft/db` package (@Tigrov)
- Enh #432, #433: Update `DMLQueryBuilder::update()` method to adapt changes in `yiisoft/db` (@rustamwin, @Tigrov)
- Enh #439: Move "Packets out of order" warning suppression from Yii DB (@vjik)

## 1.2.0 March 21, 2024

- Enh #312: Change property `Schema::$typeMap` to constant `Schema::TYPE_MAP` (@Tigrov)
- Enh #318: Resolve deprecated methods (@Tigrov)
- Enh #319: Minor refactoring of `DDLQueryBuilder` and `Schema` (@Tigrov)
- Bug #314: Fix `Command::insertWithReturningPks()` method for empty values (@Tigrov)

## 1.1.0 November 12, 2023

- Chg #297: Remove `QueryBuilder::getColumnType()` child method as legacy code (@Tigrov)
- Enh #300: Refactor insert default values (@Tigrov)
- Enh #303: Implement `ColumnSchemaInterface` classes according to the data type of database table columns
  for type casting performance. Related with yiisoft/db#752 (@Tigrov)
- Enh #309: Move methods from `Command` to `AbstractPdoCommand` class (@Tigrov)
- Bug #302: Refactor `DMLQueryBuilder`, related with yiisoft/db#746 (@Tigrov)

## 1.0.1 July 24, 2023

- Enh #295: Typecast refactoring (@Tigrov)

## 1.0.0 April 12, 2023

- Initial release.
