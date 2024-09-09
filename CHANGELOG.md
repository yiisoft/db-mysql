# MySQL driver for Yii Database Change Log

## 2.0.0 under development

- Enh #320: Minor refactoring of `DDLQueryBuilder::getColumnDefinition()` method (@Tigrov)
- Bug #320: Change visibility of `DDLQueryBuilder::getColumnDefinition()` method to `private` (@Tigrov)
- Enh #321: Implement `SqlParser` and `ExpressionBuilder` driver classes (@Tigrov)
- Chg #339: Replace call of `SchemaInterface::getRawTableName()` to `QuoterInterface::getRawTableName()` (@Tigrov)
- Enh #342: Add JSON overlaps condition builder (@Tigrov)
- Enh #344: Update `bit` type according to main PR yiisoft/db#860 (@Tigrov)
- Enh #346: Implement `ColumnFactory` class (@Tigrov)
- Enh #347, #353: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov)
- Bug #349, #352: Restore connection if closed by connection timeout (@Tigrov)
- Enh #354: Separate column type constants (@Tigrov)

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
