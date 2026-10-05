<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveReturnTagIncompatibleWithNativeTypeRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use RectorLaravel\Rector\ArrayDimFetch\EnvVariableToEnvHelperRector;
use RectorLaravel\Rector\Class_\AddHasFactoryToModelsRector;
use RectorLaravel\Rector\ClassMethod\MakeModelAttributesAndScopesProtectedRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withSets([
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_FACTORIES,
        LaravelSetList::LARAVEL_IF_HELPERS,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
    ])
    ->withImportNames(removeUnusedImports: true)
    ->withComposerBased(laravel: true)
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/bootstrap/tests.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
    ])
    ->withSkip([
        AddHasFactoryToModelsRector::class,
        // Manual schedules execute the claimed task directly. dispatchSync() adds
        // queue serialization and failure hooks, changing the execution lifecycle.
        RenameMethodRector::class => [
            __DIR__.'/app/Actions/Schedules/ProcessSchedule.php',
        ],
        MakeModelAttributesAndScopesProtectedRector::class => [
            __DIR__.'/app/Models/Traits/HasRealtimeIdentifier.php',
        ],
        EnvVariableToEnvHelperRector::class => [
            __DIR__.'/bootstrap/app.php',
        ],
        __DIR__.'/database/migrations/old',
        // These return tags carry project type aliases and array shapes that cannot
        // be expressed by the native array return type.
        RemoveReturnTagIncompatibleWithNativeTypeRector::class,
        // Empty top-level Fractal metadata is intentionally omitted from generated
        // OpenAPI schemas; the guard call does not make that array non-empty.
        RemoveAlwaysTrueIfConditionRector::class => [
            __DIR__.'/app/Extensions/Scribe/OpenApi/PterodactylOpenApiGenerator.php',
        ],
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        codingStyle: true,
    )
    ->withPhpSets()
    // Rector 2 removed the old strictBooleans prepared set; this is the rule that
    // survives from it, rewriting empty() into type-aware strict comparisons.
    ->withRules([
        DisallowedEmptyRuleFixerRector::class,
    ]);
