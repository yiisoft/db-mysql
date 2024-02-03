# MySQL driver for Yii Database Change Log

## 1.1.1 under development

- Enh #312: Change property `Schema::$typeMap` to constant `Schema::TYPE_MAP` (@Tigrov)
- Bug #314: Fix `Command::insertWithReturningPks()` method for empty values (@Tigrov)
- Enh #320: Refactor `DDLQueryBuilder::getColumnDefinition()` method (@Tigrov)

## 1.1.0 November 12, 2023

- Chg #297: Remove `QueryBuilder::getColumnType()` child method as legacy code (@Tigrov)
- Enh #300: Refactor insert default values (@Tigrov)
- Enh #309: Move methods from `Command` to `AbstractPdoCommand` class (@Tigrov)
- Bug #302: Refactor `DMLQueryBuilder`, related with yiisoft/db#746 (@Tigrov)

## 1.0.1 July 24, 2023

- Enh #295: Typecast refactoring (@Tigrov)

## 1.0.0 April 12, 2023

- Initial release.
