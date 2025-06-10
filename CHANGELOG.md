# MySQL driver for Yii Database Change Log

## 2.0.0 under development

- Enh #320: Minor refactoring of `DDLQueryBuilder::getColumnDefinition()` method (@Tigrov)
- Bug #320: Change visibility of `DDLQueryBuilder::getColumnDefinition()` method to `private` (@Tigrov)
- Enh #321, #391: Implement and use `SqlParser` class (@Tigrov)
- Chg #339: Replace call of `SchemaInterface::getRawTableName()` to `QuoterInterface::getRawTableName()` (@Tigrov)
- New #342: Add JSON overlaps condition builder (@Tigrov)
- Enh #344: Update `bit` type according to main PR yiisoft/db#860 (@Tigrov)
- New #346, #361: Implement `ColumnFactory` class (@Tigrov)
- Enh #347, #353: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov)
- Bug #349, #352: Restore connection if closed by connection timeout (@Tigrov)
- Enh #354: Separate column type constants (@Tigrov)
- New #355: Realize `ColumnBuilder` class (@Tigrov)
- Enh #357: Update according changes in `ColumnSchemaInterface` (@Tigrov)
- New #358, #365: Add `ColumnDefinitionBuilder` class (@Tigrov)
- Enh #359: Refactor `Dsn` class (@Tigrov)
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
- Enh #384: Refactor according changes in `db` package (@Tigrov)
- New #383: Add `caseSensitive` option to like condition (@vjik)
- Enh #386: Remove `getCacheKey()` and `getCacheTag()` methods from `Schema` class (@Tigrov)
- Bug #388: Set empty `comment` and `extra` properties to `null` when loading table columns (@Tigrov)
- Enh #389, #390: Use `DbArrayHelper::arrange()` instead of `DbArrayHelper::index()` method (@Tigrov)
- New #387: Realize `Schema::loadResultColumn()` method (@Tigrov)
- New #393: Use `DateTimeColumn` class for datetime column types (@Tigrov)
- New #394: Implement `Command::upsertWithReturningPks()` method (@Tigrov)
- Enh #394: Refactor `Command::insertWithReturningPks()` method (@Tigrov)
- Enh #396: Refactor constraints (@Tigrov)
- New #394, #395: Implement `Command::upsertReturning()` method (@Tigrov)
- New #394, #395, #398: Implement `Command::upsertReturning()` method (@Tigrov)
- Enh #394, #395: Refactor `Command::insertWithReturningPks()` method (@Tigrov)

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
