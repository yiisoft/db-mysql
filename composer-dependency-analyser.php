<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // ext-pdo_mysql is required to select the "mysql" PDO driver, but PDO drivers aren't tied to any
    // PHP-level symbol the analyser can detect, so it's always reported as unused.
    ->ignoreErrorsOnExtension('ext-pdo_mysql', [ErrorType::UNUSED_DEPENDENCY])
    // ext-pcntl/ext-posix are used only in tests/DeadLockTest.php, guarded by function_exists() checks
    // that skip the test when the extensions aren't available (e.g. on Windows); not a hard dependency.
    ->ignoreErrorsOnExtensionAndPath('ext-pcntl', __DIR__ . '/tests/DeadLockTest.php', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnExtensionAndPath('ext-posix', __DIR__ . '/tests/DeadLockTest.php', [ErrorType::SHADOW_DEPENDENCY]);
