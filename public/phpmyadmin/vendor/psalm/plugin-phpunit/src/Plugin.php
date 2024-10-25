<?php

namespace Psalm\PhpUnitPlugin;

use SimpleXMLElement;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

/** @psalm-suppress UnusedClass */
class Plugin implements PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(RegistrationInterface $psalm, SimpleXMLElement $config = null): void
    {
        if (VersionUtils::packageVersionIs('phpunit/phpunit', '<', '8.0')) {
            $psalm->addStubFile(__DIR__ . '/../stubs/Assert_75.phpstub');
        }
        $psalm->addStubFile(__DIR__ . '/../stubs/TestCase.phpstub');
        $psalm->addStubFile(__DIR__ . '/../stubs/MockBuilder.phpstub');
        $psalm->addStubFile(__DIR__ . '/../stubs/InvocationMocker.phpstub');
        $psalm->addStubFile(__DIR__ . '/../stubs/Prophecy.phpstub');

        class_exists(Hooks\TestCaseHandler::class, true);
        $psalm->registerHooksFromClass(Hooks\TestCaseHandler::class);
    }
}
