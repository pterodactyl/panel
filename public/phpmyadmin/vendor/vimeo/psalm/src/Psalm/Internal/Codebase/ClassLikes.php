<?php

namespace Psalm\Internal\Codebase;

use InvalidArgumentException;
use PhpParser;
use PhpParser\NodeTraverser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Exception\UnpopulatedClasslikeException;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\FileManipulation\ClassDocblockManipulator;
use Psalm\Internal\FileManipulation\CodeMigration;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\PhpVisitor\TraitFinder;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\PossiblyUnusedMethod;
use Psalm\Issue\PossiblyUnusedParam;
use Psalm\Issue\PossiblyUnusedProperty;
use Psalm\Issue\PossiblyUnusedReturnValue;
use Psalm\Issue\UnusedClass;
use Psalm\Issue\UnusedConstructor;
use Psalm\Issue\UnusedMethod;
use Psalm\Issue\UnusedParam;
use Psalm\Issue\UnusedProperty;
use Psalm\Issue\UnusedReturnValue;
use Psalm\IssueBuffer;
use Psalm\Node\VirtualNode;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\StatementsSource;
use Psalm\Storage\ClassConstantStorage;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Union;
use ReflectionClass;
use ReflectionProperty;
use UnexpectedValueException;

use function array_filter;
use function array_merge;
use function array_pop;
use function count;
use function end;
use function explode;
use function get_declared_classes;
use function get_declared_interfaces;
use function implode;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;

use const PHP_EOL;

/**
 * @internal
 *
 * Handles information about classes, interfaces and traits
 */
class ClassLikes
{
    /**
     * @var ClassLikeStorageProvider
     */
    private $classlike_storage_provider;

    /**
     * @var FileReferenceProvider
     */
    public $file_reference_provider;

    /**
     * @var array<lowercase-string, bool>
     */
    private $existing_classlikes_lc = [];

    /**
     * @var array<lowercase-string, bool>
     */
    private $existing_classes_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_classes = [];

    /**
     * @var array<lowercase-string, bool>
     */
    private $existing_interfaces_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_interfaces = [];

    /**
     * @var array<lowercase-string, bool>
     */
    private $existing_traits_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_traits = [];

    /**
     * @var array<lowercase-string, bool>
     */
    private $existing_enums_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_enums = [];

    /**
     * @var array<lowercase-string, string>
     */
    private $classlike_aliases_map = [];

    /**
     * @var array<string, bool>
     */
    private $existing_classlike_aliases = [];

    /**
     * @var array<string, PhpParser\Node\Stmt\Trait_>
     */
    private $trait_nodes = [];

    /**
     * @var bool
     */
    public $collect_references = false;

    /**
     * @var bool
     */
    public $collect_locations = false;

    /**
     * @var StatementsProvider
     */
    private $statements_provider;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Scanner
     */
    private $scanner;

    public function __construct(
        Config $config,
        ClassLikeStorageProvider $storage_provider,
        FileReferenceProvider $file_reference_provider,
        StatementsProvider $statements_provider,
        Scanner $scanner
    ) {
        $this->config = $config;
        $this->classlike_storage_provider = $storage_provider;
        $this->file_reference_provider = $file_reference_provider;
        $this->statements_provider = $statements_provider;
        $this->scanner = $scanner;

        $this->collectPredefinedClassLikes();
    }

    private function collectPredefinedClassLikes(): void
    {
        /** @var array<int, string> */
        $predefined_classes = get_declared_classes();

        foreach ($predefined_classes as $predefined_class) {
            $predefined_class = preg_replace('/^\\\/', '', $predefined_class, 1);
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflection_class = new ReflectionClass($predefined_class);

            if (!$reflection_class->isUserDefined()) {
                $predefined_class_lc = strtolower($predefined_class);
                $this->existing_classlikes_lc[$predefined_class_lc] = true;
                $this->existing_classes_lc[$predefined_class_lc] = true;
                $this->existing_classes[$predefined_class] = true;
            }
        }

        /** @var array<int, string> */
        $predefined_interfaces = get_declared_interfaces();

        foreach ($predefined_interfaces as $predefined_interface) {
            $predefined_interface = preg_replace('/^\\\/', '', $predefined_interface, 1);
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflection_class = new ReflectionClass($predefined_interface);

            if (!$reflection_class->isUserDefined()) {
                $predefined_interface_lc = strtolower($predefined_interface);
                $this->existing_classlikes_lc[$predefined_interface_lc] = true;
                $this->existing_interfaces_lc[$predefined_interface_lc] = true;
                $this->existing_interfaces[$predefined_interface] = true;
            }
        }
    }

    public function addFullyQualifiedClassName(string $fq_class_name, ?string $file_path = null): void
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_classes_lc[$fq_class_name_lc] = true;
        $this->existing_classes[$fq_class_name] = true;

        $this->existing_traits_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces_lc[$fq_class_name_lc] = false;
        $this->existing_enums_lc[$fq_class_name_lc] = false;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    public function addFullyQualifiedInterfaceName(string $fq_class_name, ?string $file_path = null): void
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_interfaces_lc[$fq_class_name_lc] = true;
        $this->existing_interfaces[$fq_class_name] = true;

        $this->existing_classes_lc[$fq_class_name_lc] = false;
        $this->existing_traits_lc[$fq_class_name_lc] = false;
        $this->existing_enums_lc[$fq_class_name_lc] = false;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    public function addFullyQualifiedTraitName(string $fq_class_name, ?string $file_path = null): void
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_traits_lc[$fq_class_name_lc] = true;
        $this->existing_traits[$fq_class_name] = true;

        $this->existing_classes_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces_lc[$fq_class_name_lc] = false;
        $this->existing_enums[$fq_class_name] = false;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    public function addFullyQualifiedEnumName(string $fq_class_name, ?string $file_path = null): void
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_enums_lc[$fq_class_name_lc] = true;
        $this->existing_enums[$fq_class_name] = true;

        $this->existing_traits_lc[$fq_class_name_lc] = false;
        $this->existing_classes_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces_lc[$fq_class_name_lc] = false;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    public function addFullyQualifiedClassLikeName(string $fq_class_name_lc, ?string $file_path = null): void
    {
        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @return list<string>
     */
    public function getMatchingClassLikeNames(string $stub): array
    {
        $matching_classes = [];

        if ($stub[0] === '*') {
            $stub = substr($stub, 1);
        }

        $fully_qualified = false;

        if ($stub[0] === '\\') {
            $fully_qualified = true;
            $stub = substr($stub, 1);
        } else {
            // for any not-fully-qualified class name the bit we care about comes after a dash
            [, $stub] = explode('-', $stub);
        }

        $stub = preg_quote(strtolower($stub));

        if ($fully_qualified) {
            $stub = '^' . $stub;
        } else {
            $stub = '(^|\\\)' . $stub;
        }

        foreach ($this->existing_classes as $fq_classlike_name => $found) {
            if (!$found) {
                continue;
            }

            if (preg_match('@' . $stub . '.*@i', $fq_classlike_name)) {
                $matching_classes[] = $fq_classlike_name;
            }
        }

        foreach ($this->existing_interfaces as $fq_classlike_name => $found) {
            if (!$found) {
                continue;
            }

            if (preg_match('@' . $stub . '.*@i', $fq_classlike_name)) {
                $matching_classes[] = $fq_classlike_name;
            }
        }

        return $matching_classes;
    }

    public function hasFullyQualifiedClassName(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        $fq_class_name_lc = strtolower($this->getUnAliasedName($fq_class_name));

        if ($code_location) {
            if ($calling_method_id) {
                $this->file_reference_provider->addMethodReferenceToClass(
                    $calling_method_id,
                    $fq_class_name_lc
                );
            } elseif (!$calling_fq_class_name || strtolower($calling_fq_class_name) !== $fq_class_name_lc) {
                $this->file_reference_provider->addNonMethodReferenceToClass(
                    $code_location->file_path,
                    $fq_class_name_lc
                );

                if ($calling_fq_class_name) {
                    $class_storage = $this->classlike_storage_provider->get($calling_fq_class_name);

                    if ($class_storage->location
                        && $class_storage->location->file_path !== $code_location->file_path
                    ) {
                        $this->file_reference_provider->addNonMethodReferenceToClass(
                            $class_storage->location->file_path,
                            $fq_class_name_lc
                        );
                    }
                }
            }
        }

        if (!isset($this->existing_classes_lc[$fq_class_name_lc])
            || !$this->existing_classes_lc[$fq_class_name_lc]
            || !$this->classlike_storage_provider->has($fq_class_name_lc)
        ) {
            if ((
                !isset($this->existing_classes_lc[$fq_class_name_lc])
                    || $this->existing_classes_lc[$fq_class_name_lc]
                )
                && !$this->classlike_storage_provider->has($fq_class_name_lc)
            ) {
                if (!isset($this->existing_classes_lc[$fq_class_name_lc])) {
                    $this->existing_classes_lc[$fq_class_name_lc] = false;

                    return false;
                }

                return $this->existing_classes_lc[$fq_class_name_lc];
            }

            return false;
        }

        if ($this->collect_locations && $code_location) {
            $this->file_reference_provider->addCallingLocationForClass(
                $code_location,
                strtolower($fq_class_name)
            );
        }

        return true;
    }

    public function hasFullyQualifiedInterfaceName(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        $fq_class_name_lc = strtolower($this->getUnAliasedName($fq_class_name));

        if (!isset($this->existing_interfaces_lc[$fq_class_name_lc])
            || !$this->existing_interfaces_lc[$fq_class_name_lc]
            || !$this->classlike_storage_provider->has($fq_class_name_lc)
        ) {
            if ((
                !isset($this->existing_classes_lc[$fq_class_name_lc])
                    || $this->existing_classes_lc[$fq_class_name_lc]
                )
                && !$this->classlike_storage_provider->has($fq_class_name_lc)
            ) {
                if (!isset($this->existing_interfaces_lc[$fq_class_name_lc])) {
                    $this->existing_interfaces_lc[$fq_class_name_lc] = false;

                    return false;
                }

                return $this->existing_interfaces_lc[$fq_class_name_lc];
            }

            return false;
        }

        if ($this->collect_references && $code_location) {
            if ($calling_method_id) {
                $this->file_reference_provider->addMethodReferenceToClass(
                    $calling_method_id,
                    $fq_class_name_lc
                );
            } else {
                $this->file_reference_provider->addNonMethodReferenceToClass(
                    $code_location->file_path,
                    $fq_class_name_lc
                );

                if ($calling_fq_class_name) {
                    $class_storage = $this->classlike_storage_provider->get($calling_fq_class_name);

                    if ($class_storage->location
                        && $class_storage->location->file_path !== $code_location->file_path
                    ) {
                        $this->file_reference_provider->addNonMethodReferenceToClass(
                            $class_storage->location->file_path,
                            $fq_class_name_lc
                        );
                    }
                }
            }
        }

        if ($this->collect_locations && $code_location) {
            $this->file_reference_provider->addCallingLocationForClass(
                $code_location,
                strtolower($fq_class_name)
            );
        }

        return true;
    }

    public function hasFullyQualifiedEnumName(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        $fq_class_name_lc = strtolower($this->getUnAliasedName($fq_class_name));

        if (!isset($this->existing_enums_lc[$fq_class_name_lc])
            || !$this->existing_enums_lc[$fq_class_name_lc]
            || !$this->classlike_storage_provider->has($fq_class_name_lc)
        ) {
            if ((
                !isset($this->existing_classes_lc[$fq_class_name_lc])
                    || $this->existing_classes_lc[$fq_class_name_lc]
                )
                && !$this->classlike_storage_provider->has($fq_class_name_lc)
            ) {
                if (!isset($this->existing_enums_lc[$fq_class_name_lc])) {
                    $this->existing_enums_lc[$fq_class_name_lc] = false;

                    return false;
                }

                return $this->existing_enums_lc[$fq_class_name_lc];
            }

            return false;
        }

        if ($this->collect_references && $code_location) {
            if ($calling_method_id) {
                $this->file_reference_provider->addMethodReferenceToClass(
                    $calling_method_id,
                    $fq_class_name_lc
                );
            } else {
                $this->file_reference_provider->addNonMethodReferenceToClass(
                    $code_location->file_path,
                    $fq_class_name_lc
                );

                if ($calling_fq_class_name) {
                    $class_storage = $this->classlike_storage_provider->get($calling_fq_class_name);

                    if ($class_storage->location
                        && $class_storage->location->file_path !== $code_location->file_path
                    ) {
                        $this->file_reference_provider->addNonMethodReferenceToClass(
                            $class_storage->location->file_path,
                            $fq_class_name_lc
                        );
                    }
                }
            }
        }

        if ($this->collect_locations && $code_location) {
            $this->file_reference_provider->addCallingLocationForClass(
                $code_location,
                strtolower($fq_class_name)
            );
        }

        return true;
    }

    public function hasFullyQualifiedTraitName(string $fq_class_name, ?CodeLocation $code_location = null): bool
    {
        $fq_class_name_lc = strtolower($this->getUnAliasedName($fq_class_name));

        if (!isset($this->existing_traits_lc[$fq_class_name_lc]) ||
            !$this->existing_traits_lc[$fq_class_name_lc]
        ) {
            return false;
        }

        if ($this->collect_references && $code_location) {
            $this->file_reference_provider->addNonMethodReferenceToClass(
                $code_location->file_path,
                $fq_class_name_lc
            );
        }

        return true;
    }

    /**
     * Check whether a class/interface exists
     */
    public function classOrInterfaceExists(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        return $this->classExists($fq_class_name, $code_location, $calling_fq_class_name, $calling_method_id)
            || $this->interfaceExists($fq_class_name, $code_location, $calling_fq_class_name, $calling_method_id);
    }

    /**
     * Check whether a class/interface exists
     */
    public function classOrInterfaceOrEnumExists(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        return $this->classExists($fq_class_name, $code_location, $calling_fq_class_name, $calling_method_id)
            || $this->interfaceExists($fq_class_name, $code_location, $calling_fq_class_name, $calling_method_id)
            || $this->enumExists($fq_class_name, $code_location, $calling_fq_class_name, $calling_method_id);
    }

    /**
     * Determine whether or not a given class exists
     */
    public function classExists(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        if (isset(ClassLikeAnalyzer::SPECIAL_TYPES[$fq_class_name])) {
            return false;
        }

        if ($fq_class_name === 'Generator') {
            return true;
        }

        return $this->hasFullyQualifiedClassName(
            $fq_class_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    /**
     * Determine whether or not a class extends a parent
     *
     * @throws UnpopulatedClasslikeException when called on unpopulated class
     * @throws InvalidArgumentException when class does not exist
     */
    public function classExtends(string $fq_class_name, string $possible_parent, bool $from_api = false): bool
    {
        $unaliased_fq_class_name = $this->getUnAliasedName($fq_class_name);
        $unaliased_fq_class_name_lc = strtolower($unaliased_fq_class_name);

        if ($unaliased_fq_class_name_lc === 'generator') {
            return false;
        }

        $class_storage = $this->classlike_storage_provider->get($unaliased_fq_class_name);

        if ($from_api && !$class_storage->populated) {
            throw new UnpopulatedClasslikeException($fq_class_name);
        }

        return isset($class_storage->parent_classes[strtolower($possible_parent)]);
    }

    /**
     * Check whether a class implements an interface
     */
    public function classImplements(string $fq_class_name, string $interface): bool
    {
        $interface_id = strtolower($interface);

        $fq_class_name = strtolower($fq_class_name);

        if ($interface_id === 'callable' && $fq_class_name === 'closure') {
            return true;
        }

        if ($interface_id === 'traversable' && $fq_class_name === 'generator') {
            return true;
        }

        if ($interface_id === 'traversable' && $fq_class_name === 'iterator') {
            return true;
        }

        if (isset(ClassLikeAnalyzer::SPECIAL_TYPES[$interface_id])
            || isset(ClassLikeAnalyzer::SPECIAL_TYPES[$fq_class_name])
        ) {
            return false;
        }

        $fq_class_name = $this->getUnAliasedName($fq_class_name);

        if (!$this->classlike_storage_provider->has($fq_class_name)) {
            return false;
        }
        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->class_implements[$interface_id])) {
            return true;
        }

        foreach ($class_storage->class_implements as $implementing_interface_lc => $_) {
            $aliased_interface_lc = strtolower(
                $this->getUnAliasedName($implementing_interface_lc)
            );

            if ($aliased_interface_lc === $interface_id) {
                return true;
            }
        }

        return false;
    }

    public function interfaceExists(
        string $fq_interface_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        if (isset(ClassLikeAnalyzer::SPECIAL_TYPES[strtolower($fq_interface_name)])) {
            return false;
        }

        return $this->hasFullyQualifiedInterfaceName(
            $fq_interface_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    public function enumExists(
        string $fq_enum_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        if (isset(ClassLikeAnalyzer::SPECIAL_TYPES[strtolower($fq_enum_name)])) {
            return false;
        }

        return $this->hasFullyQualifiedEnumName(
            $fq_enum_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    public function interfaceExtends(string $interface_name, string $possible_parent): bool
    {
        return isset($this->getParentInterfaces($interface_name)[strtolower($possible_parent)]);
    }

    /**
     * @return array<lowercase-string, string>   all interfaces extended by $interface_name
     */
    public function getParentInterfaces(string $fq_interface_name): array
    {
        $fq_interface_name = strtolower($fq_interface_name);

        return $this->classlike_storage_provider->get($fq_interface_name)->parent_interfaces;
    }

    public function traitExists(string $fq_trait_name, ?CodeLocation $code_location = null): bool
    {
        return $this->hasFullyQualifiedTraitName($fq_trait_name, $code_location);
    }

    /**
     * Determine whether or not a class has the correct casing
     */
    public function classHasCorrectCasing(string $fq_class_name): bool
    {
        if ($fq_class_name === 'Generator') {
            return true;
        }

        if (isset($this->existing_classlike_aliases[$fq_class_name])) {
            return true;
        }

        return isset($this->existing_classes[$fq_class_name]);
    }

    public function interfaceHasCorrectCasing(string $fq_interface_name): bool
    {
        if (isset($this->existing_classlike_aliases[$fq_interface_name])) {
            return true;
        }

        return isset($this->existing_interfaces[$fq_interface_name]);
    }

    public function enumHasCorrectCasing(string $fq_enum_name): bool
    {
        if (isset($this->existing_classlike_aliases[$fq_enum_name])) {
            return true;
        }

        return isset($this->existing_enums[$fq_enum_name]);
    }

    public function traitHasCorrectCasing(string $fq_trait_name): bool
    {
        if (isset($this->existing_classlike_aliases[$fq_trait_name])) {
            return true;
        }

        return isset($this->existing_traits[$fq_trait_name]);
    }

    public function getTraitNode(string $fq_trait_name): PhpParser\Node\Stmt\Trait_
    {
        $fq_trait_name_lc = strtolower($fq_trait_name);

        if (isset($this->trait_nodes[$fq_trait_name_lc])) {
            return $this->trait_nodes[$fq_trait_name_lc];
        }

        $storage = $this->classlike_storage_provider->get($fq_trait_name);

        if (!$storage->location) {
            throw new UnexpectedValueException('Storage should exist for ' . $fq_trait_name);
        }

        $file_statements = $this->statements_provider->getStatementsForFile($storage->location->file_path, '7.4');

        $trait_finder = new TraitFinder($fq_trait_name);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            $trait_finder
        );

        $traverser->traverse($file_statements);

        $trait_node = $trait_finder->getNode();

        if ($trait_node) {
            $this->trait_nodes[$fq_trait_name_lc] = $trait_node;

            return $trait_node;
        }

        throw new UnexpectedValueException('Could not locate trait statement');
    }

    public function addClassAlias(string $fq_class_name, string $alias_name): void
    {
        $this->classlike_aliases_map[strtolower($alias_name)] = $fq_class_name;
        $this->existing_classlike_aliases[$alias_name] = true;
    }

    public function getUnAliasedName(string $alias_name): string
    {
        $alias_name_lc = strtolower($alias_name);
        if ($this->existing_classlikes_lc[$alias_name_lc] ?? false) {
            return $alias_name;
        }

        $result = $this->classlike_aliases_map[$alias_name_lc] ?? $alias_name;
        if ($result === $alias_name) {
            return $result;
        }

        return $this->getUnAliasedName($result);
    }

    public function consolidateAnalyzedData(Methods $methods, ?Progress $progress, bool $find_unused_code): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $progress->debug('Checking class references' . PHP_EOL);

        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        foreach ($this->existing_classlikes_lc as $fq_class_name_lc => $_) {
            try {
                $classlike_storage = $this->classlike_storage_provider->get($fq_class_name_lc);
            } catch (InvalidArgumentException $e) {
                continue;
            }

            if ($classlike_storage->location
                && $this->config->isInProjectDirs($classlike_storage->location->file_path)
                && !$classlike_storage->is_trait
            ) {
                if ($find_unused_code) {
                    if (!$this->file_reference_provider->isClassReferenced($fq_class_name_lc)) {
                        IssueBuffer::maybeAdd(
                            new UnusedClass(
                                'Class ' . $classlike_storage->name . ' is never used',
                                $classlike_storage->location,
                                $classlike_storage->name
                            ),
                            $classlike_storage->suppressed_issues
                        );
                    } else {
                        $this->checkMethodReferences($classlike_storage, $methods);
                        $this->checkPropertyReferences($classlike_storage);
                    }
                }

                $this->findPossibleMethodParamTypes($classlike_storage);

                if ($codebase->alter_code
                    && isset($project_analyzer->getIssuesToFix()['MissingImmutableAnnotation'])
                    && !isset($codebase->analyzer->mutable_classes[$fq_class_name_lc])
                    && !$classlike_storage->external_mutation_free
                    && $classlike_storage->properties
                    && isset($classlike_storage->methods['__construct'])
                ) {
                    $stmts = $codebase->getStatementsForFile(
                        $classlike_storage->location->file_path
                    );

                    foreach ($stmts as $stmt) {
                        if ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                            foreach ($stmt->stmts as $namespace_stmt) {
                                if ($namespace_stmt instanceof PhpParser\Node\Stmt\Class_
                                    && strtolower((string) $stmt->name . '\\' . (string) $namespace_stmt->name)
                                        === $fq_class_name_lc
                                ) {
                                    self::makeImmutable(
                                        $namespace_stmt,
                                        $project_analyzer,
                                        $classlike_storage->location->file_path
                                    );
                                }
                            }
                        } elseif ($stmt instanceof PhpParser\Node\Stmt\Class_
                            && strtolower((string) $stmt->name) === $fq_class_name_lc
                        ) {
                            self::makeImmutable(
                                $stmt,
                                $project_analyzer,
                                $classlike_storage->location->file_path
                            );
                        }
                    }
                }
            }
        }
    }

    public static function makeImmutable(
        PhpParser\Node\Stmt\Class_ $class_stmt,
        ProjectAnalyzer $project_analyzer,
        string $file_path
    ): void {
        $manipulator = ClassDocblockManipulator::getForClass(
            $project_analyzer,
            $file_path,
            $class_stmt
        );

        $manipulator->makeImmutable();
    }

    public function moveMethods(Methods $methods, ?Progress $progress = null): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        if (!$codebase->methods_to_move) {
            return;
        }

        $progress->debug('Refactoring methods ' . PHP_EOL);

        $code_migrations = [];

        foreach ($codebase->methods_to_move as $source => $destination) {
            $source_parts = explode('::', $source);

            try {
                $source_method_storage = $methods->getStorage(
                    new MethodIdentifier(...$source_parts)
                );
            } catch (InvalidArgumentException $e) {
                continue;
            }

            [$destination_fq_class_name, $destination_name] = explode('::', $destination);

            try {
                $classlike_storage = $this->classlike_storage_provider->get($destination_fq_class_name);
            } catch (InvalidArgumentException $e) {
                continue;
            }

            if ($classlike_storage->stmt_location
                && $this->config->isInProjectDirs($classlike_storage->stmt_location->file_path)
                && $source_method_storage->stmt_location
                && $source_method_storage->stmt_location->file_path
                && $source_method_storage->location
            ) {
                $new_class_bounds = $classlike_storage->stmt_location->getSnippetBounds();
                $old_method_bounds = $source_method_storage->stmt_location->getSnippetBounds();

                $old_method_name_bounds = $source_method_storage->location->getSelectionBounds();

                FileManipulationBuffer::add(
                    $source_method_storage->stmt_location->file_path,
                    [
                        new FileManipulation(
                            $old_method_name_bounds[0],
                            $old_method_name_bounds[1],
                            $destination_name
                        ),
                    ]
                );

                $selection = $classlike_storage->stmt_location->getSnippet();

                $insert_pos = strrpos($selection, "\n", -1);

                if (!$insert_pos) {
                    $insert_pos = strlen($selection) - 1;
                } else {
                    ++$insert_pos;
                }

                $code_migrations[] = new CodeMigration(
                    $source_method_storage->stmt_location->file_path,
                    $old_method_bounds[0],
                    $old_method_bounds[1],
                    $classlike_storage->stmt_location->file_path,
                    $new_class_bounds[0] + $insert_pos
                );
            }
        }

        FileManipulationBuffer::addCodeMigrations($code_migrations);
    }

    public function moveProperties(Properties $properties, ?Progress $progress = null): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        if (!$codebase->properties_to_move) {
            return;
        }

        $progress->debug('Refacting properties ' . PHP_EOL);

        $code_migrations = [];

        foreach ($codebase->properties_to_move as $source => $destination) {
            try {
                $source_property_storage = $properties->getStorage($source);
            } catch (InvalidArgumentException $e) {
                continue;
            }

            [$source_fq_class_name] = explode('::$', $source);
            [$destination_fq_class_name, $destination_name] = explode('::$', $destination);

            $source_classlike_storage = $this->classlike_storage_provider->get($source_fq_class_name);
            $destination_classlike_storage = $this->classlike_storage_provider->get($destination_fq_class_name);

            if ($destination_classlike_storage->stmt_location
                && $this->config->isInProjectDirs($destination_classlike_storage->stmt_location->file_path)
                && $source_property_storage->stmt_location
                && $source_property_storage->stmt_location->file_path
                && $source_property_storage->location
            ) {
                if ($source_property_storage->type
                    && $source_property_storage->type_location
                    && $source_property_storage->type_location !== $source_property_storage->signature_type_location
                ) {
                    $bounds = $source_property_storage->type_location->getSelectionBounds();

                    $replace_type = TypeExpander::expandUnion(
                        $codebase,
                        $source_property_storage->type,
                        $source_classlike_storage->name,
                        $source_classlike_storage->name,
                        $source_classlike_storage->parent_class
                    );

                    $this->airliftClassDefinedDocblockType(
                        $replace_type,
                        $destination_fq_class_name,
                        $source_property_storage->stmt_location->file_path,
                        $bounds[0],
                        $bounds[1]
                    );
                }

                $new_class_bounds = $destination_classlike_storage->stmt_location->getSnippetBounds();
                $old_property_bounds = $source_property_storage->stmt_location->getSnippetBounds();

                $old_property_name_bounds = $source_property_storage->location->getSelectionBounds();

                FileManipulationBuffer::add(
                    $source_property_storage->stmt_location->file_path,
                    [
                        new FileManipulation(
                            $old_property_name_bounds[0],
                            $old_property_name_bounds[1],
                            '$' . $destination_name
                        ),
                    ]
                );

                $selection = $destination_classlike_storage->stmt_location->getSnippet();

                $insert_pos = strrpos($selection, "\n", -1);

                if (!$insert_pos) {
                    $insert_pos = strlen($selection) - 1;
                } else {
                    ++$insert_pos;
                }

                $code_migrations[] = new CodeMigration(
                    $source_property_storage->stmt_location->file_path,
                    $old_property_bounds[0],
                    $old_property_bounds[1],
                    $destination_classlike_storage->stmt_location->file_path,
                    $new_class_bounds[0] + $insert_pos
                );
            }
        }

        FileManipulationBuffer::addCodeMigrations($code_migrations);
    }

    public function moveClassConstants(?Progress $progress = null): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        if (!$codebase->class_constants_to_move) {
            return;
        }

        $progress->debug('Refacting constants ' . PHP_EOL);

        $code_migrations = [];

        foreach ($codebase->class_constants_to_move as $source => $destination) {
            [$source_fq_class_name, $source_const_name] = explode('::', $source);
            [$destination_fq_class_name, $destination_name] = explode('::', $destination);

            $source_classlike_storage = $this->classlike_storage_provider->get($source_fq_class_name);
            $destination_classlike_storage = $this->classlike_storage_provider->get($destination_fq_class_name);

            $constant_storage = $source_classlike_storage->constants[$source_const_name];

            $source_const_stmt_location = $constant_storage->stmt_location;
            $source_const_location = $constant_storage->location;

            if (!$source_const_location || !$source_const_stmt_location) {
                continue;
            }

            if ($destination_classlike_storage->stmt_location
                && $this->config->isInProjectDirs($destination_classlike_storage->stmt_location->file_path)
                && $source_const_stmt_location->file_path
            ) {
                $new_class_bounds = $destination_classlike_storage->stmt_location->getSnippetBounds();
                $old_const_bounds = $source_const_stmt_location->getSnippetBounds();

                $old_const_name_bounds = $source_const_location->getSelectionBounds();

                FileManipulationBuffer::add(
                    $source_const_stmt_location->file_path,
                    [
                        new FileManipulation(
                            $old_const_name_bounds[0],
                            $old_const_name_bounds[1],
                            $destination_name
                        ),
                    ]
                );

                $selection = $destination_classlike_storage->stmt_location->getSnippet();

                $insert_pos = strrpos($selection, "\n", -1);

                if (!$insert_pos) {
                    $insert_pos = strlen($selection) - 1;
                } else {
                    ++$insert_pos;
                }

                $code_migrations[] = new CodeMigration(
                    $source_const_stmt_location->file_path,
                    $old_const_bounds[0],
                    $old_const_bounds[1],
                    $destination_classlike_storage->stmt_location->file_path,
                    $new_class_bounds[0] + $insert_pos
                );
            }
        }

        FileManipulationBuffer::addCodeMigrations($code_migrations);
    }

    /**
     * @param lowercase-string|null $calling_method_id
     */
    public function handleClassLikeReferenceInMigration(
        Codebase $codebase,
        StatementsSource $source,
        PhpParser\Node $class_name_node,
        string $fq_class_name,
        ?string $calling_method_id,
        bool $force_change = false,
        bool $was_self = false
    ): bool {
        if ($class_name_node instanceof VirtualNode) {
            return false;
        }
        $calling_fq_class_name = $source->getFQCLN();

        // if we're inside a moved class static method
        if ($codebase->methods_to_move
            && $calling_fq_class_name
            && $calling_method_id
            && isset($codebase->methods_to_move[$calling_method_id])
        ) {
            $destination_class = explode('::', $codebase->methods_to_move[$calling_method_id])[0];

            $intended_fq_class_name = strtolower($calling_fq_class_name) === strtolower($fq_class_name)
                && isset($codebase->classes_to_move[strtolower($calling_fq_class_name)])
                ? $destination_class
                : $fq_class_name;

            $this->airliftClassLikeReference(
                $intended_fq_class_name,
                $destination_class,
                $source->getFilePath(),
                (int) $class_name_node->getAttribute('startFilePos'),
                (int) $class_name_node->getAttribute('endFilePos') + 1,
                $class_name_node instanceof PhpParser\Node\Scalar\MagicConst\Class_,
                $was_self
            );

            return true;
        }

        // if we're outside a moved class, but we're changing all references to a class
        if (isset($codebase->class_transforms[strtolower($fq_class_name)])) {
            $new_fq_class_name = $codebase->class_transforms[strtolower($fq_class_name)];
            $file_manipulations = [];

            if ($class_name_node instanceof PhpParser\Node\Identifier) {
                $destination_parts = explode('\\', $new_fq_class_name);

                $destination_class_name = array_pop($destination_parts);

                $file_manipulations[] = new FileManipulation(
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    $destination_class_name
                );

                FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);

                return true;
            }

            $uses_flipped = $source->getAliasedClassesFlipped();
            $uses_flipped_replaceable = $source->getAliasedClassesFlippedReplaceable();

            $old_fq_class_name = strtolower($fq_class_name);

            $migrated_source_fqcln = $calling_fq_class_name;

            if ($calling_fq_class_name
                && isset($codebase->class_transforms[strtolower($calling_fq_class_name)])
            ) {
                $migrated_source_fqcln = $codebase->class_transforms[strtolower($calling_fq_class_name)];
            }

            $source_namespace = $source->getNamespace();

            if ($migrated_source_fqcln && $calling_fq_class_name !== $migrated_source_fqcln) {
                $new_source_parts = explode('\\', $migrated_source_fqcln, -1);
                $source_namespace = implode('\\', $new_source_parts);
            }

            if (isset($uses_flipped_replaceable[$old_fq_class_name])) {
                $alias = $uses_flipped_replaceable[$old_fq_class_name];
                unset($uses_flipped[$old_fq_class_name]);
                $old_class_name_parts = explode('\\', $old_fq_class_name);
                $old_class_name = end($old_class_name_parts);
                if ($old_class_name === strtolower($alias)) {
                    $new_class_name_parts = explode('\\', $new_fq_class_name);
                    $new_class_name = end($new_class_name_parts);
                    $uses_flipped[strtolower($new_fq_class_name)] = $new_class_name;
                } else {
                    $uses_flipped[strtolower($new_fq_class_name)] = $alias;
                }
            }

            $file_manipulations[] = new FileManipulation(
                (int) $class_name_node->getAttribute('startFilePos'),
                (int) $class_name_node->getAttribute('endFilePos') + 1,
                Type::getStringFromFQCLN(
                    $new_fq_class_name,
                    $source_namespace,
                    $uses_flipped,
                    $migrated_source_fqcln,
                    $was_self
                )
                    . ($class_name_node instanceof PhpParser\Node\Scalar\MagicConst\Class_ ? '::class' : '')
            );

            FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);

            return true;
        }

        // if we're inside a moved class (could be a method, could be a property/class const default)
        if ($codebase->classes_to_move
            && $calling_fq_class_name
            && isset($codebase->classes_to_move[strtolower($calling_fq_class_name)])
        ) {
            $destination_class = $codebase->classes_to_move[strtolower($calling_fq_class_name)];

            if ($class_name_node instanceof PhpParser\Node\Identifier) {
                $destination_parts = explode('\\', $destination_class);

                $destination_class_name = array_pop($destination_parts);
                $file_manipulations = [];

                $file_manipulations[] = new FileManipulation(
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    $destination_class_name
                );

                FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);
            } else {
                $this->airliftClassLikeReference(
                    strtolower($calling_fq_class_name) === strtolower($fq_class_name)
                        ? $destination_class
                        : $fq_class_name,
                    $destination_class,
                    $source->getFilePath(),
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    $class_name_node instanceof PhpParser\Node\Scalar\MagicConst\Class_
                );
            }

            return true;
        }

        if ($force_change) {
            if ($calling_fq_class_name) {
                $this->airliftClassLikeReference(
                    $fq_class_name,
                    $calling_fq_class_name,
                    $source->getFilePath(),
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1
                );
            } else {
                $file_manipulations = [];

                $file_manipulations[] = new FileManipulation(
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    Type::getStringFromFQCLN(
                        $fq_class_name,
                        $source->getNamespace(),
                        $source->getAliasedClassesFlipped(),
                        null
                    )
                );

                FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);
            }

            return true;
        }

        return false;
    }

    /**
     * @param lowercase-string|null $calling_method_id
     */
    public function handleDocblockTypeInMigration(
        Codebase $codebase,
        StatementsSource $source,
        Union $type,
        CodeLocation $type_location,
        ?string $calling_method_id
    ): void {
        $calling_fq_class_name = $source->getFQCLN();
        $fq_class_name_lc = strtolower($calling_fq_class_name ?? '');

        $moved_type = false;

        // if we're inside a moved class static method
        if ($codebase->methods_to_move
            && $calling_fq_class_name
            && $calling_method_id
            && isset($codebase->methods_to_move[$calling_method_id])
        ) {
            $bounds = $type_location->getSelectionBounds();

            $destination_class = explode('::', $codebase->methods_to_move[$calling_method_id])[0];

            $this->airliftClassDefinedDocblockType(
                $type,
                $destination_class,
                $source->getFilePath(),
                $bounds[0],
                $bounds[1]
            );

            $moved_type = true;
        }

        // if we're outside a moved class, but we're changing all references to a class
        if (!$moved_type && $codebase->class_transforms) {
            $uses_flipped = $source->getAliasedClassesFlipped();
            $uses_flipped_replaceable = $source->getAliasedClassesFlippedReplaceable();

            $migrated_source_fqcln = $calling_fq_class_name;

            if ($calling_fq_class_name
                && isset($codebase->class_transforms[$fq_class_name_lc])
            ) {
                $migrated_source_fqcln = $codebase->class_transforms[$fq_class_name_lc];
            }

            $source_namespace = $source->getNamespace();

            if ($migrated_source_fqcln && $calling_fq_class_name !== $migrated_source_fqcln) {
                $new_source_parts = explode('\\', $migrated_source_fqcln, -1);
                $source_namespace = implode('\\', $new_source_parts);
            }

            foreach ($codebase->class_transforms as $old_fq_class_name => $new_fq_class_name) {
                if (isset($uses_flipped_replaceable[$old_fq_class_name])) {
                    $alias = $uses_flipped_replaceable[$old_fq_class_name];
                    unset($uses_flipped[$old_fq_class_name]);
                    $old_class_name_parts = explode('\\', $old_fq_class_name);
                    $old_class_name = end($old_class_name_parts);
                    if ($old_class_name === strtolower($alias)) {
                        $new_class_name_parts = explode('\\', $new_fq_class_name);
                        $new_class_name = end($new_class_name_parts);
                        $uses_flipped[strtolower($new_fq_class_name)] = $new_class_name;
                    } else {
                        $uses_flipped[strtolower($new_fq_class_name)] = $alias;
                    }
                }
            }

            foreach ($codebase->class_transforms as $old_fq_class_name => $new_fq_class_name) {
                if ($type->containsClassLike($old_fq_class_name)) {
                    $type = clone $type;

                    $type->replaceClassLike($old_fq_class_name, $new_fq_class_name);

                    $bounds = $type_location->getSelectionBounds();

                    $file_manipulations = [];

                    $file_manipulations[] = new FileManipulation(
                        $bounds[0],
                        $bounds[1],
                        $type->toNamespacedString(
                            $source_namespace,
                            $uses_flipped,
                            $migrated_source_fqcln,
                            false
                        )
                    );

                    FileManipulationBuffer::add(
                        $source->getFilePath(),
                        $file_manipulations
                    );

                    $moved_type = true;
                }
            }
        }

        // if we're inside a moved class (could be a method, could be a property/class const default)
        if (!$moved_type
            && $codebase->classes_to_move
            && $calling_fq_class_name
            && isset($codebase->classes_to_move[$fq_class_name_lc])
        ) {
            $bounds = $type_location->getSelectionBounds();

            $destination_class = $codebase->classes_to_move[$fq_class_name_lc];

            if ($type->containsClassLike($fq_class_name_lc)) {
                $type = clone $type;

                $type->replaceClassLike($fq_class_name_lc, $destination_class);
            }

            $this->airliftClassDefinedDocblockType(
                $type,
                $destination_class,
                $source->getFilePath(),
                $bounds[0],
                $bounds[1]
            );
        }
    }

    public function airliftClassLikeReference(
        string $fq_class_name,
        string $destination_fq_class_name,
        string $source_file_path,
        int $source_start,
        int $source_end,
        bool $add_class_constant = false,
        bool $allow_self = false
    ): void {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        $destination_class_storage = $codebase->classlike_storage_provider->get($destination_fq_class_name);

        if (!$destination_class_storage->aliases) {
            throw new UnexpectedValueException('Aliases should not be null');
        }

        $file_manipulations = [];

        $file_manipulations[] = new FileManipulation(
            $source_start,
            $source_end,
            Type::getStringFromFQCLN(
                $fq_class_name,
                $destination_class_storage->aliases->namespace,
                $destination_class_storage->aliases->uses_flipped,
                $destination_class_storage->name,
                $allow_self
            ) . ($add_class_constant ? '::class' : '')
        );

        FileManipulationBuffer::add(
            $source_file_path,
            $file_manipulations
        );
    }

    public function airliftClassDefinedDocblockType(
        Union $type,
        string $destination_fq_class_name,
        string $source_file_path,
        int $source_start,
        int $source_end
    ): void {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        $destination_class_storage = $codebase->classlike_storage_provider->get($destination_fq_class_name);

        if (!$destination_class_storage->aliases) {
            throw new UnexpectedValueException('Aliases should not be null');
        }

        $file_manipulations = [];

        $file_manipulations[] = new FileManipulation(
            $source_start,
            $source_end,
            $type->toNamespacedString(
                $destination_class_storage->aliases->namespace,
                $destination_class_storage->aliases->uses_flipped,
                $destination_class_storage->name,
                false
            )
        );

        FileManipulationBuffer::add(
            $source_file_path,
            $file_manipulations
        );
    }

    /**
     * @param ReflectionProperty::IS_PUBLIC|ReflectionProperty::IS_PROTECTED|ReflectionProperty::IS_PRIVATE
     *  $visibility
     *
     * @return array<string, ClassConstantStorage>
     */
    public function getConstantsForClass(string $class_name, int $visibility): array
    {
        $class_name = strtolower($class_name);

        $storage = $this->classlike_storage_provider->get($class_name);

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return array_filter(
                $storage->constants,
                function ($constant) {
                    return $constant->type
                        && $constant->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC;
                }
            );
        }

        if ($visibility === ReflectionProperty::IS_PROTECTED) {
            return array_filter(
                $storage->constants,
                function ($constant) {
                    return $constant->type
                        && ($constant->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                            || $constant->visibility === ClassLikeAnalyzer::VISIBILITY_PROTECTED);
                }
            );
        }

        return array_filter(
            $storage->constants,
            function ($constant) {
                return $constant->type !== null;
            }
        );
    }

    /**
     * @param ReflectionProperty::IS_PUBLIC|ReflectionProperty::IS_PROTECTED|ReflectionProperty::IS_PRIVATE
     *  $visibility
     */
    public function getClassConstantType(
        string $class_name,
        string $constant_name,
        int $visibility,
        ?StatementsAnalyzer $statements_analyzer = null,
        array $visited_constant_ids = []
    ): ?Union {
        $class_name = strtolower($class_name);

        if (!$this->classlike_storage_provider->has($class_name)) {
            return null;
        }

        $storage = $this->classlike_storage_provider->get($class_name);

        if (isset($storage->constants[$constant_name])) {
            $constant_storage = $storage->constants[$constant_name];

            if ($visibility === ReflectionProperty::IS_PUBLIC
                && $constant_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PUBLIC
            ) {
                return null;
            }

            if ($visibility === ReflectionProperty::IS_PROTECTED
                && $constant_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PUBLIC
                && $constant_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PROTECTED
            ) {
                return null;
            }

            if ($constant_storage->unresolved_node) {
                $constant_storage->type = new Union([ConstantTypeResolver::resolve(
                    $this,
                    $constant_storage->unresolved_node,
                    $statements_analyzer,
                    $visited_constant_ids
                )]);
            }

            return $constant_storage->type;
        } elseif (isset($storage->enum_cases[$constant_name])) {
            return new Union([new TEnumCase($storage->name, $constant_name)]);
        }
        return null;
    }

    private function checkMethodReferences(ClassLikeStorage $classlike_storage, Methods $methods): void
    {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        foreach ($classlike_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $appearing_fq_classlike_name = $appearing_method_id->fq_class_name;

            if ($appearing_fq_classlike_name !== $classlike_storage->name) {
                continue;
            }

            $method_id = $appearing_method_id;

            $declaring_classlike_storage = $classlike_storage;

            if (isset($classlike_storage->methods[$method_name])) {
                $method_storage = $classlike_storage->methods[$method_name];
            } else {
                $declaring_method_id = $classlike_storage->declaring_method_ids[$method_name];

                $declaring_fq_classlike_name = $declaring_method_id->fq_class_name;
                $declaring_method_name = $declaring_method_id->method_name;

                try {
                    $declaring_classlike_storage = $this->classlike_storage_provider->get($declaring_fq_classlike_name);
                } catch (InvalidArgumentException $e) {
                    continue;
                }

                $method_storage = $declaring_classlike_storage->methods[$declaring_method_name];
                $method_id = $declaring_method_id;
            }

            if ($method_storage->location
                && !$project_analyzer->canReportIssues($method_storage->location->file_path)
                && !$codebase->analyzer->canReportIssues($method_storage->location->file_path)
            ) {
                continue;
            }

            $method_referenced = $this->file_reference_provider->isClassMethodReferenced(
                strtolower((string) $method_id)
            );

            if (!$method_referenced
                && $method_storage->location
            ) {
                if ($method_name !== '__destruct'
                    && $method_name !== '__clone'
                    && $method_name !== '__invoke'
                    && $method_name !== '__unset'
                    && $method_name !== '__isset'
                    && $method_name !== '__sleep'
                    && $method_name !== '__wakeup'
                    && $method_name !== '__serialize'
                    && $method_name !== '__unserialize'
                    && $method_name !== '__set_state'
                    && $method_name !== '__debuginfo'
                    && $method_name !== '__tostring' // can be called in array_unique)
                ) {
                    $method_location = $method_storage->location;

                    $method_id = $classlike_storage->name . '::' . $method_storage->cased_name;

                    if ($method_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
                        $has_parent_references = false;

                        if ($codebase->classImplements($classlike_storage->name, 'Serializable')
                            && ($method_name === 'serialize' || $method_name === 'unserialize')
                        ) {
                            continue;
                        }

                        $has_variable_calls = $codebase->analyzer->hasMixedMemberName($method_name)
                            || $codebase->analyzer->hasMixedMemberName(strtolower($classlike_storage->name . '::'));

                        if (isset($classlike_storage->overridden_method_ids[$method_name])) {
                            foreach ($classlike_storage->overridden_method_ids[$method_name] as $parent_method_id) {
                                $parent_method_storage = $methods->getStorage($parent_method_id);

                                if ($parent_method_storage->location
                                    && !$project_analyzer->canReportIssues($parent_method_storage->location->file_path)
                                ) {
                                    // here we just don’t know
                                    $has_parent_references = true;
                                    break;
                                }

                                $parent_method_referenced = $this->file_reference_provider->isClassMethodReferenced(
                                    strtolower((string) $parent_method_id)
                                );

                                if (!$parent_method_storage->abstract || $parent_method_referenced) {
                                    $has_parent_references = true;
                                    break;
                                }
                            }
                        }

                        foreach ($classlike_storage->parent_classes as $parent_method_fqcln) {
                            if ($codebase->analyzer->hasMixedMemberName(
                                strtolower($parent_method_fqcln) . '::'
                            )) {
                                $has_variable_calls = true;
                                break;
                            }
                        }

                        foreach ($classlike_storage->class_implements as $fq_interface_name_lc => $_) {
                            try {
                                $interface_storage = $this->classlike_storage_provider->get($fq_interface_name_lc);
                            } catch (InvalidArgumentException $e) {
                                continue;
                            }

                            if ($codebase->analyzer->hasMixedMemberName(
                                $fq_interface_name_lc . '::'
                            )) {
                                $has_variable_calls = true;
                            }

                            if (isset($interface_storage->methods[$method_name])) {
                                $interface_method_referenced = $this->file_reference_provider->isClassMethodReferenced(
                                    $fq_interface_name_lc . '::' . $method_name
                                );

                                if ($interface_method_referenced) {
                                    $has_parent_references = true;
                                }
                            }
                        }

                        if (!$has_parent_references) {
                            $issue = new PossiblyUnusedMethod(
                                'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                                    . ' calls to method ' . $method_id
                                    . ($has_variable_calls ? ' (but did find some potential callers)' : ''),
                                $method_storage->location,
                                $method_id
                            );

                            if ($codebase->alter_code) {
                                if ($method_storage->stmt_location
                                    && !$declaring_classlike_storage->is_trait
                                    && isset($project_analyzer->getIssuesToFix()['PossiblyUnusedMethod'])
                                    && !$has_variable_calls
                                    && !IssueBuffer::isSuppressed($issue, $method_storage->suppressed_issues)
                                ) {
                                    FileManipulationBuffer::addForCodeLocation(
                                        $method_storage->stmt_location,
                                        '',
                                        true
                                    );
                                }
                            } elseif (IssueBuffer::accepts(
                                $issue,
                                $method_storage->suppressed_issues,
                                $method_storage->stmt_location
                                    && !$declaring_classlike_storage->is_trait
                                    && !$has_variable_calls
                            )) {
                                // fall through
                            }
                        }
                    } elseif (!isset($classlike_storage->declaring_method_ids['__call'])) {
                        $has_variable_calls = $codebase->analyzer->hasMixedMemberName(
                            strtolower($classlike_storage->name . '::')
                        ) || $codebase->analyzer->hasMixedMemberName($method_name);

                        if ($method_name === '__construct') {
                            $issue = new UnusedConstructor(
                                'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                                    . ' calls to private constructor ' . $method_id
                                    . ($has_variable_calls ? ' (but did find some potential callers)' : ''),
                                $method_location,
                                $method_id
                            );
                        } else {
                            $issue = new UnusedMethod(
                                'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                                    . ' calls to private method ' . $method_id
                                    . ($has_variable_calls ? ' (but did find some potential callers)' : ''),
                                $method_location,
                                $method_id
                            );
                        }

                        if ($codebase->alter_code) {
                            if ($method_storage->stmt_location
                                && !$declaring_classlike_storage->is_trait
                                && isset($project_analyzer->getIssuesToFix()['UnusedMethod'])
                                && !$has_variable_calls
                                && !IssueBuffer::isSuppressed($issue, $method_storage->suppressed_issues)
                            ) {
                                FileManipulationBuffer::addForCodeLocation(
                                    $method_storage->stmt_location,
                                    '',
                                    true
                                );
                            }
                        } elseif (IssueBuffer::accepts(
                            $issue,
                            $method_storage->suppressed_issues,
                            $method_storage->stmt_location
                                && !$declaring_classlike_storage->is_trait
                                && !$has_variable_calls
                        )) {
                            // fall through
                        }
                    }
                }
            } else {
                if ($method_storage->return_type
                    && $method_storage->return_type_location
                    && !$method_storage->return_type->isVoid()
                    && !$method_storage->return_type->isNever()
                    && $method_id->method_name !== '__tostring'
                    && ($method_storage->is_static || !$method_storage->probably_fluent)
                ) {
                    $method_return_referenced = $this->file_reference_provider->isMethodReturnReferenced(
                        strtolower((string) $method_id)
                    );

                    if (!$method_return_referenced) {
                        if ($method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
                            IssueBuffer::maybeAdd(
                                new UnusedReturnValue(
                                    'The return value for this private method is never used',
                                    $method_storage->return_type_location
                                ),
                                $method_storage->suppressed_issues
                            );
                        } else {
                            IssueBuffer::maybeAdd(
                                new PossiblyUnusedReturnValue(
                                    'The return value for this method is never used',
                                    $method_storage->return_type_location
                                ),
                                $method_storage->suppressed_issues
                            );
                        }
                    }
                }

                if ($method_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PRIVATE
                    && !$classlike_storage->is_interface
                ) {
                    foreach ($method_storage->params as $offset => $param_storage) {
                        if (empty($classlike_storage->overridden_method_ids[$method_name])
                            && $param_storage->location
                            && !$param_storage->promoted_property
                            && !$this->file_reference_provider->isMethodParamUsed(
                                strtolower((string) $method_id),
                                $offset
                            )
                        ) {
                            if ($method_storage->final) {
                                IssueBuffer::maybeAdd(
                                    new UnusedParam(
                                        'Param #' . ($offset + 1) . ' is never referenced in this method',
                                        $param_storage->location
                                    ),
                                    $method_storage->suppressed_issues
                                );
                            } else {
                                IssueBuffer::maybeAdd(
                                    new PossiblyUnusedParam(
                                        'Param #' . ($offset + 1) . ' is never referenced in this method',
                                        $param_storage->location
                                    ),
                                    $method_storage->suppressed_issues
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    private function findPossibleMethodParamTypes(ClassLikeStorage $classlike_storage): void
    {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        foreach ($classlike_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $appearing_fq_classlike_name = $appearing_method_id->fq_class_name;

            if ($appearing_fq_classlike_name !== $classlike_storage->name) {
                continue;
            }

            $method_id = $appearing_method_id;

            $declaring_classlike_storage = $classlike_storage;

            if (isset($classlike_storage->methods[$method_name])) {
                $method_storage = $classlike_storage->methods[$method_name];
            } else {
                $declaring_method_id = $classlike_storage->declaring_method_ids[$method_name];

                $declaring_fq_classlike_name = $declaring_method_id->fq_class_name;
                $declaring_method_name = $declaring_method_id->method_name;

                try {
                    $declaring_classlike_storage = $this->classlike_storage_provider->get($declaring_fq_classlike_name);
                } catch (InvalidArgumentException $e) {
                    continue;
                }

                $method_storage = $declaring_classlike_storage->methods[$declaring_method_name];
                $method_id = $declaring_method_id;
            }

            if ($method_storage->location
                && !$project_analyzer->canReportIssues($method_storage->location->file_path)
                && !$codebase->analyzer->canReportIssues($method_storage->location->file_path)
            ) {
                continue;
            }

            if ($declaring_classlike_storage->is_trait) {
                continue;
            }

            $method_id_lc = strtolower((string) $method_id);

            if (isset($codebase->analyzer->possible_method_param_types[$method_id_lc])) {
                if ($method_storage->location) {
                    $possible_param_types
                        = $codebase->analyzer->possible_method_param_types[$method_id_lc];

                    if ($possible_param_types) {
                        foreach ($possible_param_types as $offset => $possible_type) {
                            if (!isset($method_storage->params[$offset])) {
                                continue;
                            }

                            $param_name = $method_storage->params[$offset]->name;

                            if ($possible_type->hasMixed() || $possible_type->isNull()) {
                                continue;
                            }

                            if ($method_storage->params[$offset]->default_type) {
                                if ($method_storage->params[$offset]->default_type instanceof Union) {
                                    $default_type = clone $method_storage->params[$offset]->default_type;
                                } else {
                                    $default_type_atomic = ConstantTypeResolver::resolve(
                                        $codebase->classlikes,
                                        $method_storage->params[$offset]->default_type,
                                        null
                                    );

                                    $default_type = new Union([$default_type_atomic]);
                                }

                                $possible_type = Type::combineUnionTypes(
                                    $possible_type,
                                    $default_type
                                );
                            }

                            if ($codebase->alter_code
                                && isset($project_analyzer->getIssuesToFix()['MissingParamType'])
                            ) {
                                $function_analyzer = $project_analyzer->getFunctionLikeAnalyzer(
                                    $method_id,
                                    $method_storage->location->file_path
                                );

                                $has_variable_calls = $codebase->analyzer->hasMixedMemberName(
                                    $method_name
                                )
                                    || $codebase->analyzer->hasMixedMemberName(
                                        strtolower($classlike_storage->name . '::')
                                    );

                                if ($has_variable_calls) {
                                    $possible_type->from_docblock = true;
                                }

                                if ($function_analyzer) {
                                    $function_analyzer->addOrUpdateParamType(
                                        $project_analyzer,
                                        $param_name,
                                        $possible_type,
                                        $possible_type->from_docblock
                                            && $project_analyzer->only_replace_php_types_with_non_docblock_types
                                    );
                                }
                            } else {
                                IssueBuffer::addFixableIssue('MissingParamType');
                            }
                        }
                    }
                }
            }
        }
    }

    private function checkPropertyReferences(ClassLikeStorage $classlike_storage): void
    {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        foreach ($classlike_storage->properties as $property_name => $property_storage) {
            $referenced_property_name = strtolower($classlike_storage->name) . '::$' . $property_name;
            $property_referenced = $this->file_reference_provider->isClassPropertyReferenced(
                $referenced_property_name
            );

            $property_constructor_referenced = false;
            if ($property_referenced && $property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
                $all_method_references = $this->file_reference_provider->getAllMethodReferencesToClassProperties();

                if (isset($all_method_references[$referenced_property_name])
                    && count($all_method_references[$referenced_property_name]) === 1) {
                    $constructor_name = strtolower($classlike_storage->name) . '::__construct';
                    $property_references = $all_method_references[$referenced_property_name];

                    $property_constructor_referenced = isset($property_references[$constructor_name])
                        && !$property_storage->is_static;
                }
            }

            if ((!$property_referenced || $property_constructor_referenced)
                && $property_storage->location
            ) {
                $property_id = $classlike_storage->name . '::$' . $property_name;

                if ($property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                    || $property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PROTECTED
                ) {
                    $has_parent_references = isset($classlike_storage->overridden_property_ids[$property_name]);

                    $has_variable_calls = $codebase->analyzer->hasMixedMemberName('$' . $property_name)
                        || $codebase->analyzer->hasMixedMemberName(strtolower($classlike_storage->name) . '::$');

                    foreach ($classlike_storage->parent_classes as $parent_method_fqcln) {
                        if ($codebase->analyzer->hasMixedMemberName(
                            strtolower($parent_method_fqcln) . '::$'
                        )) {
                            $has_variable_calls = true;
                            break;
                        }
                    }

                    foreach ($classlike_storage->class_implements as $fq_interface_name) {
                        if ($codebase->analyzer->hasMixedMemberName(
                            strtolower($fq_interface_name) . '::$'
                        )) {
                            $has_variable_calls = true;
                            break;
                        }
                    }

                    if (!$has_parent_references
                        && ($property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                            || !isset($classlike_storage->declaring_method_ids['__get']))
                    ) {
                        $issue = new PossiblyUnusedProperty(
                            'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                                . ' references to property ' . $property_id
                                . ($has_variable_calls ? ' (but did find some potential references)' : ''),
                            $property_storage->location
                        );

                        if ($codebase->alter_code) {
                            if ($property_storage->stmt_location
                                && isset($project_analyzer->getIssuesToFix()['PossiblyUnusedProperty'])
                                && !$has_variable_calls
                                && !IssueBuffer::isSuppressed($issue, $classlike_storage->suppressed_issues)
                            ) {
                                FileManipulationBuffer::addForCodeLocation(
                                    $property_storage->stmt_location,
                                    '',
                                    true
                                );
                            }
                        } elseif (IssueBuffer::accepts(
                            $issue,
                            $classlike_storage->suppressed_issues + $property_storage->suppressed_issues
                        )) {
                            // fall through
                        }
                    }
                } elseif (!isset($classlike_storage->declaring_method_ids['__get'])) {
                    $has_variable_calls = $codebase->analyzer->hasMixedMemberName('$' . $property_name);

                    $issue = new UnusedProperty(
                        'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                            . ' references to private property ' . $property_id
                            . ($has_variable_calls ? ' (but did find some potential references)' : ''),
                        $property_storage->location
                    );

                    if ($codebase->alter_code) {
                        if (!$property_constructor_referenced
                            && $property_storage->stmt_location
                            && isset($project_analyzer->getIssuesToFix()['UnusedProperty'])
                            && !$has_variable_calls
                            && !IssueBuffer::isSuppressed($issue, $classlike_storage->suppressed_issues)
                        ) {
                            FileManipulationBuffer::addForCodeLocation(
                                $property_storage->stmt_location,
                                '',
                                true
                            );
                        }
                    } elseif (IssueBuffer::accepts(
                        $issue,
                        $classlike_storage->suppressed_issues + $property_storage->suppressed_issues
                    )) {
                        // fall through
                    }
                }
            }
        }
    }

    /**
     * @param  lowercase-string $fq_classlike_name_lc
     */
    public function registerMissingClassLike(string $fq_classlike_name_lc): void
    {
        $this->existing_classlikes_lc[$fq_classlike_name_lc] = false;
    }

    /**
     * @param  lowercase-string $fq_classlike_name_lc
     */
    public function isMissingClassLike(string $fq_classlike_name_lc): bool
    {
        return isset($this->existing_classlikes_lc[$fq_classlike_name_lc])
            && $this->existing_classlikes_lc[$fq_classlike_name_lc] === false;
    }

    /**
     * @param  lowercase-string $fq_classlike_name_lc
     */
    public function doesClassLikeExist(string $fq_classlike_name_lc): bool
    {
        return isset($this->existing_classlikes_lc[$fq_classlike_name_lc])
            && $this->existing_classlikes_lc[$fq_classlike_name_lc];
    }

    public function forgetMissingClassLikes(): void
    {
        $this->existing_classlikes_lc = array_filter($this->existing_classlikes_lc);
    }

    public function removeClassLike(string $fq_class_name): void
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        unset(
            $this->existing_classlikes_lc[$fq_class_name_lc],
            $this->existing_traits_lc[$fq_class_name_lc],
            $this->existing_traits[$fq_class_name],
            $this->existing_enums_lc[$fq_class_name_lc],
            $this->existing_enums[$fq_class_name],
            $this->existing_interfaces_lc[$fq_class_name_lc],
            $this->existing_interfaces[$fq_class_name],
            $this->existing_classes_lc[$fq_class_name_lc],
            $this->existing_classes[$fq_class_name],
            $this->trait_nodes[$fq_class_name_lc]
        );

        $this->scanner->removeClassLike($fq_class_name_lc);
    }

    /**
     * @return array{
     *     array<lowercase-string, bool>,
     *     array<lowercase-string, bool>,
     *     array<lowercase-string, bool>,
     *     array<string, bool>,
     *     array<lowercase-string, bool>,
     *     array<string, bool>,
     *     array<lowercase-string, bool>,
     *     array<string, bool>,
     *     array<string, bool>,
     * }
     */
    public function getThreadData(): array
    {
        return [
            $this->existing_classlikes_lc,
            $this->existing_classes_lc,
            $this->existing_traits_lc,
            $this->existing_traits,
            $this->existing_enums_lc,
            $this->existing_enums,
            $this->existing_interfaces_lc,
            $this->existing_interfaces,
            $this->existing_classes,
        ];
    }

    /**
     * @param array{
     *     0: array<lowercase-string, bool>,
     *     1: array<lowercase-string, bool>,
     *     2: array<lowercase-string, bool>,
     *     3: array<string, bool>,
     *     4: array<lowercase-string, bool>,
     *     5: array<string, bool>,
     *     6: array<lowercase-string, bool>,
     *     7: array<string, bool>,
     *     8: array<string, bool>,
     * } $thread_data
     *
     */
    public function addThreadData(array $thread_data): void
    {
        [
            $existing_classlikes_lc,
            $existing_classes_lc,
            $existing_traits_lc,
            $existing_traits,
            $existing_enums_lc,
            $existing_enums,
            $existing_interfaces_lc,
            $existing_interfaces,
            $existing_classes
        ] = $thread_data;

        $this->existing_classlikes_lc = array_merge($existing_classlikes_lc, $this->existing_classlikes_lc);
        $this->existing_classes_lc = array_merge($existing_classes_lc, $this->existing_classes_lc);
        $this->existing_traits_lc = array_merge($existing_traits_lc, $this->existing_traits_lc);
        $this->existing_traits = array_merge($existing_traits, $this->existing_traits);
        $this->existing_enums_lc = array_merge($existing_enums_lc, $this->existing_enums_lc);
        $this->existing_enums = array_merge($existing_enums, $this->existing_enums);
        $this->existing_interfaces_lc = array_merge($existing_interfaces_lc, $this->existing_interfaces_lc);
        $this->existing_interfaces = array_merge($existing_interfaces, $this->existing_interfaces);
        $this->existing_classes = array_merge($existing_classes, $this->existing_classes);
    }

    public function getStorageFor(string $fq_class_name): ?ClassLikeStorage
    {
        $fq_class_name = $this->getUnAliasedName($fq_class_name);

        try {
            return $this->classlike_storage_provider->get($fq_class_name);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
}
