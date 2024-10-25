<?php

namespace Psalm;

use InvalidArgumentException;
use LogicException;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Plugin\EventHandler\FunctionExistenceProviderInterface;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Plugin\EventHandler\MethodExistenceProviderInterface;
use Psalm\Plugin\EventHandler\MethodParamsProviderInterface;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Plugin\EventHandler\MethodVisibilityProviderInterface;
use Psalm\Plugin\EventHandler\PropertyExistenceProviderInterface;
use Psalm\Plugin\EventHandler\PropertyTypeProviderInterface;
use Psalm\Plugin\EventHandler\PropertyVisibilityProviderInterface;
use Psalm\Plugin\Hook\FunctionExistenceProviderInterface as LegacyFunctionExistenceProviderInterface;
use Psalm\Plugin\Hook\FunctionParamsProviderInterface as LegacyFunctionParamsProviderInterface;
use Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface as LegacyFunctionReturnTypeProviderInterface;
use Psalm\Plugin\Hook\MethodExistenceProviderInterface as LegacyMethodExistenceProviderInterface;
use Psalm\Plugin\Hook\MethodParamsProviderInterface as LegacyMethodParamsProviderInterface;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface as LegacyMethodReturnTypeProviderInterface;
use Psalm\Plugin\Hook\MethodVisibilityProviderInterface as LegacyMethodVisibilityProviderInterface;
use Psalm\Plugin\Hook\PropertyExistenceProviderInterface as LegacyPropertyExistenceProviderInterface;
use Psalm\Plugin\Hook\PropertyTypeProviderInterface as LegacyPropertyTypeProviderInterface;
use Psalm\Plugin\Hook\PropertyVisibilityProviderInterface as LegacyPropertyVisibilityProviderInterface;
use Psalm\Plugin\RegistrationInterface;

use function class_exists;
use function in_array;
use function is_a;
use function is_subclass_of;
use function sprintf;

class PluginRegistrationSocket implements RegistrationInterface
{
    /** @var Config */
    private $config;

    /** @var Codebase */
    private $codebase;

    /**
     * @var array<string, class-string<FileScanner>>
     */
    private $additionalFileTypeScanners = [];

    /**
     * @var array<string, class-string<FileAnalyzer>>
     */
    private $additionalFileTypeAnalyzers = [];

    /**
     * @var list<string>
     */
    private $additionalFileExtensions = [];

    /**
     * @internal
     */
    public function __construct(Config $config, Codebase $codebase)
    {
        $this->config = $config;
        $this->codebase = $codebase;
    }

    public function addStubFile(string $file_name): void
    {
        $this->config->addStubFile($file_name);
    }

    public function registerHooksFromClass(string $handler): void
    {
        if (!class_exists($handler, false)) {
            throw new InvalidArgumentException('Plugins must be loaded before registration');
        }

        $this->config->eventDispatcher->registerClass($handler);

        if (is_subclass_of($handler, LegacyPropertyExistenceProviderInterface::class) ||
            is_subclass_of($handler, PropertyExistenceProviderInterface::class)
        ) {
            $this->codebase->properties->property_existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, LegacyPropertyVisibilityProviderInterface::class) ||
            is_subclass_of($handler, PropertyVisibilityProviderInterface::class)
        ) {
            $this->codebase->properties->property_visibility_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, LegacyPropertyTypeProviderInterface::class) ||
            is_subclass_of($handler, PropertyTypeProviderInterface::class)
        ) {
            $this->codebase->properties->property_type_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, LegacyMethodExistenceProviderInterface::class) ||
            is_subclass_of($handler, MethodExistenceProviderInterface::class)
        ) {
            $this->codebase->methods->existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, LegacyMethodVisibilityProviderInterface::class) ||
            is_subclass_of($handler, MethodVisibilityProviderInterface::class)
        ) {
            $this->codebase->methods->visibility_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, LegacyMethodReturnTypeProviderInterface::class) ||
            is_subclass_of($handler, MethodReturnTypeProviderInterface::class)
        ) {
            $this->codebase->methods->return_type_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, LegacyMethodParamsProviderInterface::class) ||
            is_subclass_of($handler, MethodParamsProviderInterface::class)
        ) {
            $this->codebase->methods->params_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, LegacyFunctionExistenceProviderInterface::class) ||
            is_subclass_of($handler, FunctionExistenceProviderInterface::class)
        ) {
            $this->codebase->functions->existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, LegacyFunctionParamsProviderInterface::class) ||
            is_subclass_of($handler, FunctionParamsProviderInterface::class)
        ) {
            $this->codebase->functions->params_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, LegacyFunctionReturnTypeProviderInterface::class) ||
            is_subclass_of($handler, FunctionReturnTypeProviderInterface::class)
        ) {
            $this->codebase->functions->return_type_provider->registerClass($handler);
        }
    }

    /**
     * @param string $fileExtension e.g. `'html'`
     * @param class-string<FileScanner> $className
     * @deprecated will be removed in v5.0, use \Psalm\Plugin\FileExtensionsInterface instead (#6788)
     */
    public function addFileTypeScanner(string $fileExtension, string $className): void
    {
        if (!class_exists($className) || !is_a($className, FileScanner::class, true)) {
            throw new LogicException(
                sprintf(
                    'Class %s must be of type %s',
                    $className,
                    FileScanner::class
                ),
                1622727271
            );
        }
        if (!empty($this->config->getFiletypeScanners()[$fileExtension])
            || !empty($this->additionalFileTypeScanners[$fileExtension])
        ) {
            throw new LogicException(
                sprintf('Cannot redeclare scanner for file-type %s', $fileExtension),
                1622727272
            );
        }
        $this->additionalFileTypeScanners[$fileExtension] = $className;
        $this->addFileExtension($fileExtension);
    }

    /**
     * @return array<string, class-string<FileScanner>>
     * @deprecated will be removed in v5.0, use \Psalm\PluginFileExtensionsSocket instead (#6788)
     */
    public function getAdditionalFileTypeScanners(): array
    {
        return $this->additionalFileTypeScanners;
    }

    /**
     * @param string $fileExtension e.g. `'html'`
     * @param class-string<FileAnalyzer> $className
     * @deprecated will be removed in v5.0, use \Psalm\PluginFileExtensionsSocket instead (#6788)
     */
    public function addFileTypeAnalyzer(string $fileExtension, string $className): void
    {
        if (!class_exists($className) || !is_a($className, FileAnalyzer::class, true)) {
            throw new LogicException(
                sprintf(
                    'Class %s must be of type %s',
                    $className,
                    FileAnalyzer::class
                ),
                1622727281
            );
        }
        if (!empty($this->config->getFiletypeAnalyzers()[$fileExtension])
            || !empty($this->additionalFileTypeAnalyzers[$fileExtension])
        ) {
            throw new LogicException(
                sprintf('Cannot redeclare analyzer for file-type %s', $fileExtension),
                1622727282
            );
        }
        $this->additionalFileTypeAnalyzers[$fileExtension] = $className;
        $this->addFileExtension($fileExtension);
    }

    /**
     * @return array<string, class-string<FileAnalyzer>>
     * @deprecated will be removed in v5.0, use \Psalm\PluginFileExtensionsSocket instead (#6788)
     */
    public function getAdditionalFileTypeAnalyzers(): array
    {
        return $this->additionalFileTypeAnalyzers;
    }

    /**
     * @return list<string> e.g. `['html', 'perl']`
     * @deprecated will be removed in v5.0, use \Psalm\PluginFileExtensionsSocket instead (#6788)
     */
    public function getAdditionalFileExtensions(): array
    {
        return $this->additionalFileExtensions;
    }

    /**
     * @param string $fileExtension e.g. `'html'`
     * @deprecated will be removed in v5.0, use \Psalm\PluginFileExtensionsSocket instead (#6788)
     */
    private function addFileExtension(string $fileExtension): void
    {
        if (!in_array($fileExtension, $this->config->getFileExtensions(), true)) {
            $this->additionalFileExtensions[] = $fileExtension;
        }
    }
}
