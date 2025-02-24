<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        /**
         * Disabled ./tests directory due to PR #307
         *
         * @link https://github.com/yiisoft/db-mysql/commit/a434897691304378edfd787ecee8ad80338aa200
         */
        // __DIR__ . '/tests',
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
    ]);
    $rectorConfig->rule(\Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector::class);
    $rectorConfig->skip([
        NullToStrictStringFuncCallArgRector::class,
        ReadOnlyPropertyRector::class,
    ]);
};
