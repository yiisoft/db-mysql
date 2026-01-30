# MySQL/MariaDB driver for Yii Database Change Log

## 2.0.1 under development

- Enh #459: Explicitly import constants in "use" section (@mspirkov)
- Enh #460: Remove unnecessary files from Composer package (@mspirkov)

## 2.0.0 December 05, 2025

- New #342, #430: Add JSON overlaps condition builder (@Tigrov)
- New #346, #361, #454: Implement `ColumnFactory` class (@Tigrov, @vjik)
- New #355: Realize `ColumnBuilder` class (@Tigrov)
- New #358, #365: Add `ColumnDefinitionBuilder` class (@Tigrov)
- New #374: Add `IndexType` and `IndexMethod` classes (@Tigrov)
- New #379: Add parameters `$ifExists` and `$cascade` to `CommandInterface::dropTable()` and
  `DDLQueryBuilderInterface::dropTable()` methods (@vjik)
- New #383: Add `caseSensitive` option to like condition (@vjik)
- New #387: Realize `Schema::loadResultColumn()` method (@Tigrov)
- New #393: Use `DateTimeColumn` class for datetime column types (@Tigrov)
- New #394, #395, #398, #425, #435, #437: Implement `Command::upsertReturning()` method (@Tigrov, @vjik)
- New #420, #427: Implement `ArrayMergeBuilder`, `LongestBuilder` and `ShortestBuilder` classes (@Tigrov)
- New #421: Add `Connection::getColumnBuilderClass()` method (@Tigrov)
- New #448: Add enumeration column type support (@vjik)
- New #453: Add source of column information (@Tigrov)
- Chg #339: Replace call of `SchemaInterface::getRawTableName()` to `QuoterInterface::getRawTableName()` (@Tigrov)
- Chg #368: Update `QueryBuilder` constructor (@Tigrov)
- Chg #378, #451: Change supported PHP versions to `8.1 - 8.5` (@Tigrov, @vjik)
- Chg #382: Remove `yiisoft/json` dependency (@Tigrov)
- Chg #399: Rename `insertWithReturningPks()` to `insertReturningPks()` in `Command` and `DMLQueryBuilder` classes (@Tigrov)
- Chg #401: Use `\InvalidArgumentException` instead of `Yiisoft\Db\Exception\InvalidArgumentException` (@DikoIbragimov)
- Chg #428: Update expression namespaces according to changes in `yiisoft/db` package (@Tigrov)
- Enh #320: Minor refactoring of `DDLQueryBuilder::getColumnDefinition()` method (@Tigrov)
- Enh #321, #391: Implement and use `SqlParser` class (@Tigrov)
- Enh #344: Update `bit` type according to main PR yiisoft/db#860 (@Tigrov)
- Enh #347, #353: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov)
- Enh #354: Separate column type constants (@Tigrov)
- Enh #357: Update according changes in `ColumnSchemaInterface` (@Tigrov)
- Enh #359, #410: Refactor `Dsn` class (@Tigrov)
- Enh #361, #362: Refactor `Schema::findColumns()` method (@Tigrov)
- Enh #363: Refactor `Schema::normalizeDefaultValue()` method and move it to `ColumnFactory` class (@Tigrov)
- Enh #366: Refactor `Quoter::quoteValue()` method (@Tigrov)
- Enh #367: Use `ColumnDefinitionBuilder` to generate table column SQL representation (@Tigrov)
- Enh #371: Remove `ColumnInterface` (@Tigrov)
- Enh #372: Rename `ColumnSchemaInterface` to `ColumnInterface` (@Tigrov)
- Enh #373: Replace `DbArrayHelper::getColumn()` with `array_column()` (@Tigrov)
- Enh #376: Move `JsonExpressionBuilder` and JSON type tests to `yiisoft/db` package (@Tigrov)
- Enh #378: Minor refactoring (@Tigrov)
- Enh #384, #413: Refactor according changes in `db` package (@Tigrov)
- Enh #386: Remove `getCacheKey()` and `getCacheTag()` methods from `Schema` class (@Tigrov)
- Enh #389, #390: Use `DbArrayHelper::arrange()` instead of `DbArrayHelper::index()` method (@Tigrov)
- Enh #394, #395: Refactor `Command::insertWithReturningPks()` method (@Tigrov)
- Enh #396, #409: Refactor constraints (@Tigrov)
- Enh #403: Refactor `DMLQueryBuilder::upsert()`, allow use `EXCLUDED` table alias to access inserted values (@Tigrov)
- Enh #405: Provide `yiisoft/db-implementation` virtual package (@vjik)
- Enh #407, #408, #411: Adapt to conditions refactoring in `yiisoft/db` package (@vjik)
- Enh #414: Remove `TableSchema` class and refactor `Schema` class (@Tigrov)
- Enh #415: Support column's collation (@Tigrov)
- Enh #423: Refactor `DMLQueryBuilder::upsert()` method (@Tigrov)
- Enh #432, #433: Update `DMLQueryBuilder::update()` method to adapt changes in `yiisoft/db` (@rustamwin, @Tigrov)
- Enh #439: Move "Packets out of order" warning suppression from Yii DB (@vjik)
- Bug #320: Change visibility of `DDLQueryBuilder::getColumnDefinition()` method to `private` (@Tigrov)
- Bug #349, #352: Restore connection if closed by connection timeout (@Tigrov)
- Bug #377: Explicitly mark nullable parameters (@vjik)
- Bug #388: Set empty `comment` and `extra` properties to `null` when loading table columns (@Tigrov)

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
