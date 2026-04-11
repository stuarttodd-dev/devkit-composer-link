<?php

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php83: true)
    ->withSets([
        SetList::PHP_83,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
    ])
    ->withSkip([
        ReadOnlyClassRector::class,
        StrictArrayParamDimFetchRector::class,
        ClosureToArrowFunctionRector::class,
    ]);
