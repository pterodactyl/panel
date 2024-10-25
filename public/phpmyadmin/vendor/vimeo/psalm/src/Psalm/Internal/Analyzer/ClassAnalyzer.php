<?php

namespace Psalm\Internal\Analyzer;

use Exception;
use InvalidArgumentException;
use LogicException;
use PhpParser;
use PhpParser\Node\Stmt\Class_;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\FunctionLike\ReturnTypeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\AtomicPropertyFetchAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\FileManipulation\PropertyDocblockManipulator;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\DeprecatedInterface;
use Psalm\Issue\DeprecatedTrait;
use Psalm\Issue\DuplicateEnumCaseValue;
use Psalm\Issue\ExtensionRequirementViolation;
use Psalm\Issue\ImplementationRequirementViolation;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InvalidEnumCaseValue;
use Psalm\Issue\InvalidExtendClass;
use Psalm\Issue\InvalidTemplateParam;
use Psalm\Issue\InvalidTraversableImplementation;
use Psalm\Issue\MethodSignatureMismatch;
use Psalm\Issue\MismatchingDocblockPropertyType;
use Psalm\Issue\MissingConstructor;
use Psalm\Issue\MissingImmutableAnnotation;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\MissingTemplateParam;
use Psalm\Issue\MutableDependency;
use Psalm\Issue\NoEnumProperties;
use Psalm\Issue\NonInvariantDocblockPropertyType;
use Psalm\Issue\NonInvariantPropertyType;
use Psalm\Issue\OverriddenPropertyAccess;
use Psalm\Issue\ParseError;
use Psalm\Issue\PropertyNotSetInConstructor;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\TooManyTemplateParams;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedInterface;
use Psalm\Issue\UndefinedTrait;
use Psalm\Issue\UnimplementedAbstractMethod;
use Psalm\Issue\UnimplementedInterfaceMethod;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualStaticCall;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\Name\VirtualFullyQualified;
use Psalm\Node\Stmt\VirtualClassMethod;
use Psalm\Node\Stmt\VirtualExpression;
use Psalm\Node\VirtualArg;
use Psalm\Node\VirtualIdentifier;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeAnalysisEvent;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_search;
use function array_values;
use function assert;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_int;
use function is_string;
use function preg_match;
use function preg_replace;
use function reset;
use function str_replace;
use function strtolower;
use function substr;

/**
 * @internal
 */
class ClassAnalyzer extends ClassLikeAnalyzer
{
    /**
     * @var array<string, Union>
     */
    public $inferred_property_types = [];

    /**
     * @param PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Enum_ $class
     */
    public function __construct(PhpParser\Node\Stmt $class, SourceAnalyzer $source, ?string $fq_class_name)
    {
        if (!$fq_class_name) {
            if (!$class instanceof PhpParser\Node\Stmt\Class_) {
                throw new UnexpectedValueException('Anonymous enums are not allowed');
            }

            $fq_class_name = self::getAnonymousClassName($class, $source->getFilePath());
        }

        parent::__construct($class, $source, $fq_class_name);

        if ($this->class instanceof PhpParser\Node\Stmt\Class_ && $this->class->extends) {
            $this->parent_fq_class_name = self::getFQCLNFromNameObject(
                $this->class->extends,
                $this->source->getAliases()
            );
        }
    }

    /** @return non-empty-string */
    public static function getAnonymousClassName(PhpParser\Node\Stmt\Class_ $class, string $file_path): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '_', $file_path)
            . '_' . $class->getLine() . '_' . (int)$class->getAttribute('startFilePos');
    }

    public function analyze(
        ?Context $class_context = null,
        ?Context $global_context = null
    ): void {
        $class = $this->class;

        if (!$class instanceof PhpParser\Node\Stmt\Class_ && !$class instanceof PhpParser\Node\Stmt\Enum_) {
            throw new LogicException('Something went badly wrong');
        }

        $fq_class_name = $class_context && $class_context->self ? $class_context->self : $this->fq_class_name;

        $storage = $this->storage;

        if ($storage->has_visitor_issues) {
            return;
        }

        if ($class->name
            && (preg_match(
                '/(^|\\\)(int|float|bool|string|void|null|false|true|object|mixed)$/i',
                $fq_class_name
            ) || strtolower($fq_class_name) === 'resource')
        ) {
            $class_name_parts = explode('\\', $fq_class_name);
            $class_name = array_pop($class_name_parts);

            IssueBuffer::maybeAdd(
                new ReservedWord(
                    $class_name . ' is a reserved word',
                    new CodeLocation(
                        $this,
                        $class->name,
                        null,
                        true
                    ),
                    $class_name
                ),
                $storage->suppressed_issues + $this->getSuppressedIssues()
            );

            return;
        }

        $project_analyzer = $this->file_analyzer->project_analyzer;
        $codebase = $this->getCodebase();

        if ($codebase->alter_code && $class->name && $codebase->classes_to_move) {
            if (isset($codebase->classes_to_move[strtolower($this->fq_class_name)])) {
                $destination_class = $codebase->classes_to_move[strtolower($this->fq_class_name)];

                $source_class_parts = explode('\\', $this->fq_class_name);
                $destination_class_parts = explode('\\', $destination_class);

                array_pop($source_class_parts);
                array_pop($destination_class_parts);

                $source_ns = implode('\\', $source_class_parts);
                $destination_ns = implode('\\', $destination_class_parts);

                if (strtolower($source_ns) !== strtolower($destination_ns)) {
                    if ($storage->namespace_name_location) {
                        $bounds = $storage->namespace_name_location->getSelectionBounds();

                        $file_manipulations = [
                            new FileManipulation(
                                $bounds[0],
                                $bounds[1],
                                $destination_ns
                            )
                        ];

                        FileManipulationBuffer::add(
                            $this->getFilePath(),
                            $file_manipulations
                        );
                    } elseif (!$source_ns) {
                        $first_statement_pos = $this->getFileAnalyzer()->getFirstStatementOffset();

                        if ($first_statement_pos === -1) {
                            $first_statement_pos = (int) $class->getAttribute('startFilePos');
                        }

                        $file_manipulations = [
                            new FileManipulation(
                                $first_statement_pos,
                                $first_statement_pos,
                                'namespace ' . $destination_ns . ';' . "\n\n",
                                true
                            )
                        ];

                        FileManipulationBuffer::add(
                            $this->getFilePath(),
                            $file_manipulations
                        );
                    }
                }
            }

            $codebase->classlikes->handleClassLikeReferenceInMigration(
                $codebase,
                $this,
                $class->name,
                $this->fq_class_name,
                null
            );
        }

        foreach ($storage->docblock_issues as $docblock_issue) {
            IssueBuffer::maybeAdd($docblock_issue);
        }

        $classlike_storage_provider = $codebase->classlike_storage_provider;

        $parent_fq_class_name = $this->parent_fq_class_name;

        if ($class instanceof PhpParser\Node\Stmt\Class_ && $class->extends && $parent_fq_class_name) {
            $this->checkParentClass(
                $class,
                $class->extends,
                $fq_class_name,
                $parent_fq_class_name,
                $storage,
                $codebase,
                $class_context
            );
        }


        if ($storage->template_types) {
            foreach ($storage->template_types as $param_name => $_) {
                $fq_classlike_name = Type::getFQCLNFromString(
                    $param_name,
                    $this->getAliases()
                );

                if ($codebase->classOrInterfaceExists($fq_classlike_name)) {
                    IssueBuffer::maybeAdd(
                        new ReservedWord(
                            'Cannot use ' . $param_name . ' as template name since the class already exists',
                            new CodeLocation($this, $this->class),
                            'resource'
                        ),
                        $this->getSuppressedIssues()
                    );
                }
            }
        }

        if (($storage->templatedMixins || $storage->namedMixins)
            && $storage->mixin_declaring_fqcln === $storage->name) {
            /** @var non-empty-array<int, TTemplateParam|TNamedObject> $mixins */
            $mixins = array_merge($storage->templatedMixins, $storage->namedMixins);
            $union = new Union($mixins);

            $static_self = new TNamedObject($storage->name);
            $static_self->was_static = true;

            $union = TypeExpander::expandUnion(
                $codebase,
                $union,
                $storage->name,
                $static_self,
                null
            );

            $union->check(
                $this,
                new CodeLocation(
                    $this,
                    $class->name ?: $class,
                    null,
                    true
                ),
                $this->getSuppressedIssues()
            );
        }

        if ($storage->template_extended_params) {
            foreach ($storage->template_extended_params as $type_map) {
                foreach ($type_map as $atomic_type) {
                    $atomic_type->check(
                        $this,
                        new CodeLocation(
                            $this,
                            $class->name ?: $class,
                            null,
                            true
                        ),
                        $this->getSuppressedIssues()
                    );
                }
            }
        }

        if (!$class_context) {
            $class_context = new Context($this->fq_class_name);
            $class_context->parent = $parent_fq_class_name;
        }

        if ($global_context) {
            $class_context->strict_types = $global_context->strict_types;
        }

        if ($this->checkImplementedInterfaces(
            $class_context,
            $class,
            $codebase,
            $fq_class_name,
            $storage
        ) === false) {
            return;
        }

        if ($storage->invalid_dependencies) {
            return;
        }

        if ($this->leftover_stmts) {
            (new StatementsAnalyzer(
                $this,
                new NodeDataProvider()
            ))->analyze(
                $this->leftover_stmts,
                $class_context
            );
        }

        if (!$storage->abstract) {
            foreach ($storage->declaring_method_ids as $declaring_method_id) {
                $method_storage = $codebase->methods->getStorage($declaring_method_id);

                $declaring_class_name = $declaring_method_id->fq_class_name;
                $method_name_lc = $declaring_method_id->method_name;

                if ($method_storage->abstract) {
                    if (IssueBuffer::accepts(
                        new UnimplementedAbstractMethod(
                            'Method ' . $method_name_lc . ' is not defined on class ' .
                            $this->fq_class_name . ', defined abstract in ' . $declaring_class_name,
                            new CodeLocation(
                                $this,
                                $class->name ?? $class,
                                $class_context->include_location,
                                true
                            )
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        return;
                    }
                }
            }
        }

        AttributesAnalyzer::analyze(
            $this,
            $class_context,
            $storage,
            $class->attrGroups,
            AttributesAnalyzer::TARGET_CLASS,
            $storage->suppressed_issues + $this->getSuppressedIssues()
        );

        self::addContextProperties(
            $this,
            $storage,
            $class_context,
            $this->fq_class_name,
            $this->parent_fq_class_name,
            $class->stmts
        );

        $constructor_analyzer = null;
        $member_stmts = [];

        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_analyzer = $this->analyzeClassMethod(
                    $stmt,
                    $storage,
                    $this,
                    $class_context,
                    $global_context
                );

                if ($stmt->name->name === '__construct') {
                    $constructor_analyzer = $method_analyzer;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                if ($this->analyzeTraitUse(
                    $this->source->getAliases(),
                    $stmt,
                    $project_analyzer,
                    $storage,
                    $class_context,
                    $global_context,
                    $constructor_analyzer
                ) === false) {
                    return;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($storage->is_enum) {
                        if (IssueBuffer::accepts(new NoEnumProperties(
                            'Enums cannot have properties',
                            new CodeLocation($this, $prop),
                            $fq_class_name
                        ))) {
                            // fall through
                        }
                        continue;
                    }
                    if ($prop->default) {
                        $member_stmts[] = $stmt;
                    }

                    if ($codebase->alter_code) {
                        $property_id = strtolower($this->fq_class_name) . '::$' . $prop->name;

                        $property_storage = $codebase->properties->getStorage($property_id);

                        if ($property_storage->type
                            && $property_storage->type_location
                            && $property_storage->type_location !== $property_storage->signature_type_location
                        ) {
                            $replace_type = TypeExpander::expandUnion(
                                $codebase,
                                $property_storage->type,
                                $this->getFQCLN(),
                                $this->getFQCLN(),
                                $this->getParentFQCLN()
                            );

                            $codebase->classlikes->handleDocblockTypeInMigration(
                                $codebase,
                                $this,
                                $replace_type,
                                $property_storage->type_location,
                                null
                            );
                        }

                        foreach ($codebase->properties_to_rename as $original_property_id => $new_property_name) {
                            if ($property_id === $original_property_id) {
                                $file_manipulations = [
                                    new FileManipulation(
                                        (int) $prop->name->getAttribute('startFilePos'),
                                        (int) $prop->name->getAttribute('endFilePos') + 1,
                                        '$' . $new_property_name
                                    )
                                ];

                                FileManipulationBuffer::add(
                                    $this->getFilePath(),
                                    $file_manipulations
                                );
                            }
                        }
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                $member_stmts[] = $stmt;

                foreach ($stmt->consts as $const) {
                    if ($const->name->toLowerString() === 'class') {
                        IssueBuffer::maybeAdd(
                            new ReservedWord(
                                'A class constant cannot be named \'class\'',
                                new CodeLocation($this, $this->class),
                                $this->fq_class_name
                            )
                        );
                    }
                    
                    $const_id = strtolower($this->fq_class_name) . '::' . $const->name;

                    foreach ($codebase->class_constants_to_rename as $original_const_id => $new_const_name) {
                        if ($const_id === $original_const_id) {
                            $file_manipulations = [
                                new FileManipulation(
                                    (int) $const->name->getAttribute('startFilePos'),
                                    (int) $const->name->getAttribute('endFilePos') + 1,
                                    $new_const_name
                                )
                            ];

                            FileManipulationBuffer::add(
                                $this->getFilePath(),
                                $file_manipulations
                            );
                        }
                    }
                }
            }
        }

        $statements_analyzer = new StatementsAnalyzer($this, new NodeDataProvider());
        $statements_analyzer->analyze($member_stmts, $class_context, $global_context, true);

        $config = Config::getInstance();

        if ($class instanceof PhpParser\Node\Stmt\Class_) {
            $this->checkPropertyInitialization(
                $codebase,
                $config,
                $storage,
                $class_context,
                $global_context,
                $constructor_analyzer
            );
        }

        if ($class instanceof PhpParser\Node\Stmt\Enum_) {
            $this->checkEnum();
        }

        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Property) {
                $this->analyzeProperty($this, $stmt, $class_context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source->getAliases()
                    );

                    try {
                        $trait_file_analyzer = $project_analyzer->getFileAnalyzerForClassLike($fq_trait_name);
                    } catch (Exception $e) {
                        continue;
                    }

                    $trait_storage = $codebase->classlike_storage_provider->get($fq_trait_name);
                    $trait_node = $codebase->classlikes->getTraitNode($fq_trait_name);
                    $trait_aliases = $trait_storage->aliases;

                    if ($trait_aliases === null) {
                        continue;
                    }

                    $trait_analyzer = new TraitAnalyzer(
                        $trait_node,
                        $trait_file_analyzer,
                        $fq_trait_name,
                        $trait_aliases
                    );

                    $fq_trait_name_lc = strtolower($fq_trait_name);

                    if (isset($storage->template_type_uses_count[$fq_trait_name_lc])) {
                        $this->checkTemplateParams(
                            $codebase,
                            $storage,
                            $trait_storage,
                            new CodeLocation(
                                $this,
                                $trait
                            ),
                            $storage->template_type_uses_count[$fq_trait_name_lc]
                        );
                    }

                    foreach ($trait_node->stmts as $trait_stmt) {
                        if ($trait_stmt instanceof PhpParser\Node\Stmt\Property) {
                            $this->analyzeProperty($trait_analyzer, $trait_stmt, $class_context);
                        }
                    }

                    $trait_file_analyzer->clearSourceBeforeDestruction();
                }
            }
        }

        $pseudo_methods = $storage->pseudo_methods + $storage->pseudo_static_methods;

        foreach ($pseudo_methods as $pseudo_method_name => $pseudo_method_storage) {
            $pseudo_method_id = new MethodIdentifier(
                $this->fq_class_name,
                $pseudo_method_name
            );

            $overridden_method_ids = $codebase->methods->getOverriddenMethodIds($pseudo_method_id);

            if ($overridden_method_ids
                && $pseudo_method_name !== '__construct'
                && $pseudo_method_storage->location
            ) {
                foreach ($overridden_method_ids as $overridden_method_id) {
                    $parent_method_storage = $codebase->methods->getStorage($overridden_method_id);

                    $overridden_fq_class_name = $overridden_method_id->fq_class_name;

                    $parent_storage = $classlike_storage_provider->get($overridden_fq_class_name);

                    MethodComparator::compare(
                        $codebase,
                        null,
                        $storage,
                        $parent_storage,
                        $pseudo_method_storage,
                        $parent_method_storage,
                        $this->fq_class_name,
                        $pseudo_method_storage->visibility ?: 0,
                        $storage->location ?: $pseudo_method_storage->location,
                        $storage->suppressed_issues,
                        true,
                        false
                    );
                }
            }
        }

        $event = new AfterClassLikeAnalysisEvent(
            $class,
            $storage,
            $this,
            $codebase,
            []
        );

        if ($codebase->config->eventDispatcher->dispatchAfterClassLikeAnalysis($event) === false) {
            return;
        }
        $file_manipulations = $event->getFileReplacements();
        if ($file_manipulations) {
            FileManipulationBuffer::add(
                $this->getFilePath(),
                $file_manipulations
            );
        }
    }

    public static function addContextProperties(
        StatementsSource $statements_source,
        ClassLikeStorage $storage,
        Context $class_context,
        string $fq_class_name,
        ?string $parent_fq_class_name,
        array $stmts = []
    ): void {
        $codebase = $statements_source->getCodebase();

        foreach ($storage->appearing_property_ids as $property_name => $appearing_property_id) {
            $property_class_name = $codebase->properties->getDeclaringClassForProperty(
                $appearing_property_id,
                true
            );

            if ($property_class_name === null) {
                continue;
            }

            $property_class_storage = $codebase->classlike_storage_provider->get($property_class_name);

            $property_storage = $property_class_storage->properties[$property_name];

            if (isset($storage->overridden_property_ids[$property_name])) {
                foreach ($storage->overridden_property_ids[$property_name] as $overridden_property_id) {
                    [$guide_class_name] = explode('::$', $overridden_property_id);
                    $guide_class_storage = $codebase->classlike_storage_provider->get($guide_class_name);
                    $guide_property_storage = $guide_class_storage->properties[$property_name];

                    if ($property_storage->visibility > $guide_property_storage->visibility
                        && $property_storage->location
                    ) {
                        IssueBuffer::maybeAdd(
                            new OverriddenPropertyAccess(
                                'Property ' . $fq_class_name . '::$' . $property_name
                                    . ' has different access level than '
                                    . $storage->name . '::$' . $property_name,
                                $property_storage->location
                            )
                        );
                    }

                    if ((($property_storage->signature_type && !$guide_property_storage->signature_type)
                            || (!$property_storage->signature_type && $guide_property_storage->signature_type)
                            || ($property_storage->signature_type
                                && !$property_storage->signature_type->equals(
                                    $guide_property_storage->signature_type
                                )))
                        && $property_storage->location
                    ) {
                        IssueBuffer::maybeAdd(
                            new NonInvariantPropertyType(
                                'Property ' . $fq_class_name . '::$' . $property_name
                                    . ' has type '
                                    . ($property_storage->signature_type
                                        ? $property_storage->signature_type->getId()
                                        : '<empty>'
                                    )
                                    . ", not invariant with " . $guide_class_name . '::$'
                                    . $property_name . ' of type '
                                    . ($guide_property_storage->signature_type
                                        ? $guide_property_storage->signature_type->getId()
                                        : '<empty>'
                                    ),
                                $property_storage->location
                            ),
                            $property_storage->suppressed_issues
                        );
                    }

                    if ($property_storage->type === null) {
                        // Property type not set, no need to check for docblock invariance
                        continue;
                    }

                    $property_type = clone $property_storage->type;

                    $guide_property_type = $guide_property_storage->type === null
                        ? Type::getMixed()
                        : clone $guide_property_storage->type;

                    // Set upper bounds for all templates
                    $lower_bounds = [];
                    $extended_templates = $storage->template_extended_params ?? [];
                    foreach ($extended_templates as $et_name => $et_array) {
                        foreach ($et_array as $et_class_name => $extended_template) {
                            if (!isset($lower_bounds[$et_class_name][$et_name])) {
                                $lower_bounds[$et_class_name][$et_name] = $extended_template;
                            }
                        }
                    }

                    // Get actual types used for templates (to support @template-covariant)
                    $template_standins = new TemplateResult($lower_bounds, []);
                    TemplateStandinTypeReplacer::replace(
                        $guide_property_type,
                        $template_standins,
                        $codebase,
                        null,
                        $property_type
                    );

                    // Iterate over parent classes to find template-covariants, and replace the upper bound with the
                    // standin. Since @template-covariant allows child classes, we want to use the standin type
                    // instead of the template extended type.
                    $parent_class = $storage->parent_class;
                    while ($parent_class !== null) {
                        $parent_storage = $codebase->classlike_storage_provider->get($parent_class);
                        foreach ($parent_storage->template_covariants ?? [] as $pt_offset => $covariant) {
                            if ($covariant) {
                                // If template_covariants is set template_types should also be set
                                assert($parent_storage->template_types !== null);
                                $pt_name = array_keys($parent_storage->template_types)[$pt_offset];
                                if (isset($template_standins->lower_bounds[$pt_name][$parent_class])) {
                                    $lower_bounds[$pt_name][$parent_class] =
                                        TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                                            $template_standins->lower_bounds[$pt_name][$parent_class],
                                            $codebase
                                        );
                                }
                            }
                        }
                        $parent_class = $parent_storage->parent_class;
                    }

                    $template_result = new TemplateResult([], $lower_bounds);

                    TemplateInferredTypeReplacer::replace(
                        $guide_property_type,
                        $template_result,
                        $codebase
                    );
                    TemplateInferredTypeReplacer::replace(
                        $property_type,
                        $template_result,
                        $codebase
                    );

                    if ($property_storage->location
                        && !$property_type->equals($guide_property_type, false)
                        && $guide_class_storage->user_defined
                    ) {
                        IssueBuffer::maybeAdd(
                            new NonInvariantDocblockPropertyType(
                                'Property ' . $fq_class_name . '::$' . $property_name
                                    . ' has type ' . $property_type->getId()
                                    . ", not invariant with " . $guide_class_name . '::$'
                                    . $property_name . ' of type '
                                    . $guide_property_type->getId(),
                                $property_storage->location
                            ),
                            $property_storage->suppressed_issues
                        );
                    }
                }
            }

            if ($property_storage->type) {
                $property_type = clone $property_storage->type;

                if (!$property_type->isMixed()
                    && !$property_storage->is_promoted
                    && !$property_storage->has_default
                    && !($property_type->isNullable() && $property_type->from_docblock)
                ) {
                    $property_type->initialized = false;
                    $property_type->from_property = true;
                    $property_type->from_static_property = $property_storage->is_static === true;
                }
            } else {
                $property_type = Type::getMixed();

                if (!$property_storage->has_default && !$property_storage->is_promoted) {
                    $property_type->initialized = false;
                    $property_type->from_property = true;
                    $property_type->from_static_property = $property_storage->is_static === true;
                }
            }

            $property_type_location = $property_storage->type_location;

            $fleshed_out_type = !$property_type->isMixed()
                ? TypeExpander::expandUnion(
                    $codebase,
                    $property_type,
                    $fq_class_name,
                    $fq_class_name,
                    $parent_fq_class_name,
                    true,
                    false,
                    $storage->final
                )
                : $property_type;

            $class_template_params = ClassTemplateParamCollector::collect(
                $codebase,
                $property_class_storage,
                $storage,
                null,
                new TNamedObject($fq_class_name),
                true
            );

            if ($class_template_params) {
                $this_object_type = self::getThisObjectType(
                    $storage,
                    $fq_class_name
                );

                if (!$this_object_type instanceof TGenericObject) {
                    $type_params = [];

                    foreach ($class_template_params as $type_map) {
                        $type_params[] = clone array_values($type_map)[0];
                    }

                    $this_object_type = new TGenericObject($this_object_type->value, $type_params);
                }

                $fleshed_out_type = AtomicPropertyFetchAnalyzer::localizePropertyType(
                    $codebase,
                    $fleshed_out_type,
                    $this_object_type,
                    $storage,
                    $property_class_storage
                );
            }

            if ($property_type_location && !$fleshed_out_type->isMixed()) {
                $stmt = array_filter(
                    $stmts,
                    function ($stmt) use ($property_name): bool {
                        return $stmt instanceof PhpParser\Node\Stmt\Property
                            && isset($stmt->props[0]->name->name)
                            && $stmt->props[0]->name->name === $property_name;
                    }
                );

                $suppressed = [];
                if (count($stmt) > 0) {
                    $stmt = array_pop($stmt);

                    $docComment = $stmt->getDocComment();
                    if ($docComment) {
                        try {
                            $docBlock = DocComment::parsePreservingLength($docComment);
                            $suppressed = $docBlock->tags['psalm-suppress'] ?? [];
                        } catch (DocblockParseException $e) {
                            // do nothing to keep original behavior
                        }
                    }
                }

                $fleshed_out_type->check(
                    $statements_source,
                    $property_type_location,
                    $storage->suppressed_issues + $statements_source->getSuppressedIssues() + $suppressed,
                    [],
                    false
                );

                if ($property_storage->signature_type) {
                    $union_comparison_result = new TypeComparisonResult();

                    if (!UnionTypeComparator::isContainedBy(
                        $codebase,
                        $fleshed_out_type,
                        $property_storage->signature_type,
                        false,
                        false,
                        $union_comparison_result
                    ) && !$union_comparison_result->type_coerced_from_mixed
                    ) {
                        IssueBuffer::maybeAdd(
                            new MismatchingDocblockPropertyType(
                                'Parameter '
                                    . $property_class_name . '::$' . $property_name
                                    . ' has wrong type \'' . $fleshed_out_type .
                                    '\', should be \'' . $property_storage->signature_type . '\'',
                                $property_type_location
                            )
                        );
                    }
                }
            }

            if ($property_storage->is_static) {
                $property_id = $fq_class_name . '::$' . $property_name;

                $class_context->vars_in_scope[$property_id] = $fleshed_out_type;
            } else {
                $class_context->vars_in_scope['$this->' . $property_name] = $fleshed_out_type;
            }
        }

        foreach ($storage->pseudo_property_get_types as $property_name => $property_type) {
            $property_name = substr($property_name, 1);

            if (isset($class_context->vars_in_scope['$this->' . $property_name])) {
                $fleshed_out_type = !$property_type->isMixed()
                    ? TypeExpander::expandUnion(
                        $codebase,
                        $property_type,
                        $fq_class_name,
                        $fq_class_name,
                        $parent_fq_class_name
                    )
                    : $property_type;

                $class_context->vars_in_scope['$this->' . $property_name] = $fleshed_out_type;
            }
        }
    }

    private function checkPropertyInitialization(
        Codebase $codebase,
        Config $config,
        ClassLikeStorage $storage,
        Context $class_context,
        ?Context $global_context = null,
        ?MethodAnalyzer $constructor_analyzer = null
    ): void {
        if (!$config->reportIssueInFile('PropertyNotSetInConstructor', $this->getFilePath())) {
            return;
        }

        if (!isset($storage->declaring_method_ids['__construct'])
            && !$config->reportIssueInFile('MissingConstructor', $this->getFilePath())
        ) {
            return;
        }

        $fq_class_name = $class_context->self ?: $this->fq_class_name;
        $fq_class_name_lc = strtolower($fq_class_name);

        $included_file_path = $this->getFilePath();

        $method_already_analyzed = $codebase->analyzer->isMethodAlreadyAnalyzed(
            $included_file_path,
            $fq_class_name_lc . '::__construct',
            true
        );

        if ($method_already_analyzed && !$codebase->diff_methods) {
            // this can happen when re-analysing a class that has been include()d inside another
            return;
        }

        /** @var PhpParser\Node\Stmt\Class_ */
        $class = $this->class;
        $classlike_storage_provider = $codebase->classlike_storage_provider;
        $class_storage = $classlike_storage_provider->get($fq_class_name_lc);

        $constructor_appearing_fqcln = $fq_class_name_lc;

        $uninitialized_variables = [];
        $uninitialized_properties = [];
        $uninitialized_typed_properties = [];
        $uninitialized_private_properties = false;

        foreach ($storage->appearing_property_ids as $property_name => $appearing_property_id) {
            $property_class_name = $codebase->properties->getDeclaringClassForProperty(
                $appearing_property_id,
                true
            );

            if ($property_class_name === null) {
                continue;
            }

            $property_class_storage = $classlike_storage_provider->get($property_class_name);

            $property = $property_class_storage->properties[$property_name];

            $property_is_initialized = isset($property_class_storage->initialized_properties[$property_name]);

            if ($property->is_static) {
                continue;
            }

            if ($property->has_default || $property_is_initialized) {
                continue;
            }

            if ($property->type && $property->type->from_docblock && $property->type->isNullable()) {
                continue;
            }

            if ($codebase->diff_methods && $method_already_analyzed && $property->location) {
                [$start, $end] = $property->location->getSelectionBounds();

                $existing_issues = $codebase->analyzer->getExistingIssuesForFile(
                    $this->getFilePath(),
                    $start,
                    $end,
                    'PropertyNotSetInConstructor'
                );

                if ($existing_issues) {
                    IssueBuffer::addIssues([$this->getFilePath() => $existing_issues]);
                    continue;
                }
            }

            if ($property->location) {
                $codebase->analyzer->removeExistingDataForFile(
                    $this->getFilePath(),
                    $property->location->raw_file_start,
                    $property->location->raw_file_end,
                    'PropertyNotSetInConstructor'
                );
            }

            $codebase->file_reference_provider->addMethodReferenceToMissingClassMember(
                $fq_class_name_lc . '::__construct',
                strtolower($property_class_name) . '::$' . $property_name
            );

            if ($property->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
                $uninitialized_private_properties = true;
            }

            $uninitialized_variables[] = '$this->' . $property_name;
            $uninitialized_properties[$property_class_name . '::$' . $property_name] = $property;

            if ($property->type && !$property->type->isMixed()) {
                $uninitialized_typed_properties[$property_class_name . '::$' . $property_name] = $property;
            }
        }

        if (!$uninitialized_properties) {
            return;
        }

        if (!$storage->abstract
            && !$constructor_analyzer
            && isset($storage->declaring_method_ids['__construct'])
            && isset($storage->appearing_method_ids['__construct'])
            && $class->extends
        ) {
            $constructor_declaring_fqcln = $storage->declaring_method_ids['__construct']->fq_class_name;
            $constructor_appearing_fqcln = $storage->appearing_method_ids['__construct']->fq_class_name;

            $constructor_class_storage = $classlike_storage_provider->get($constructor_declaring_fqcln);

            // ignore oldstyle constructors and classes without any declared properties
            if ($constructor_class_storage->user_defined
                && !$constructor_class_storage->stubbed
                && isset($constructor_class_storage->methods['__construct'])
            ) {
                $constructor_storage = $constructor_class_storage->methods['__construct'];

                $fake_constructor_params = array_map(
                    function (FunctionLikeParameter $param): PhpParser\Node\Param {
                        $fake_param = (new PhpParser\Builder\Param($param->name));
                        if ($param->signature_type) {
                            $fake_param->setType((string)$param->signature_type);
                        }

                        $node = $fake_param->getNode();

                        $attributes = $param->location
                            ? [
                                'startFilePos' => $param->location->raw_file_start,
                                'endFilePos' => $param->location->raw_file_end,
                                'startLine' => $param->location->raw_line_number
                            ]
                            : [];

                        $node->setAttributes($attributes);

                        return $node;
                    },
                    $constructor_storage->params
                );

                $fake_constructor_stmt_args = array_map(
                    function (FunctionLikeParameter $param): PhpParser\Node\Arg {
                        $attributes = $param->location
                            ? [
                                'startFilePos' => $param->location->raw_file_start,
                                'endFilePos' => $param->location->raw_file_end,
                                'startLine' => $param->location->raw_line_number
                            ]
                            : [];

                        return new VirtualArg(
                            new VirtualVariable($param->name, $attributes),
                            false,
                            $param->is_variadic,
                            $attributes
                        );
                    },
                    $constructor_storage->params
                );

                $fake_constructor_attributes = [
                    'startLine' => $class->extends->getLine(),
                    'startFilePos' => $class->extends->getAttribute('startFilePos'),
                    'endFilePos' => $class->extends->getAttribute('endFilePos'),
                ];

                $fake_call_attributes = $fake_constructor_attributes
                    + [
                        'comments' => [new PhpParser\Comment\Doc(
                            '/** @psalm-suppress InaccessibleMethod */',
                            $class->extends->getLine(),
                            (int) $class->extends->getAttribute('startFilePos')
                        )],
                    ];

                $fake_constructor_stmts = [
                    new VirtualExpression(
                        new VirtualStaticCall(
                            new VirtualFullyQualified($constructor_declaring_fqcln),
                            new VirtualIdentifier('__construct', $fake_constructor_attributes),
                            $fake_constructor_stmt_args,
                            $fake_call_attributes
                        ),
                        $fake_call_attributes
                    ),
                ];

                $fake_stmt = new VirtualClassMethod(
                    new VirtualIdentifier('__construct'),
                    [
                        'type' => PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC,
                        'params' => $fake_constructor_params,
                        'stmts' => $fake_constructor_stmts,
                    ],
                    $fake_constructor_attributes
                );

                $codebase->analyzer->disableMixedCounts();

                $was_collecting_initializations = $class_context->collect_initializations;

                $class_context->collect_initializations = true;
                $class_context->collect_nonprivate_initializations = !$uninitialized_private_properties;

                $constructor_analyzer = $this->analyzeClassMethod(
                    $fake_stmt,
                    $storage,
                    $this,
                    $class_context,
                    $global_context,
                    true
                );

                $class_context->collect_initializations = $was_collecting_initializations;

                $codebase->analyzer->enableMixedCounts();
            }
        }

        if ($constructor_analyzer) {
            $method_context = clone $class_context;
            $method_context->collect_initializations = true;
            $method_context->collect_nonprivate_initializations = !$uninitialized_private_properties;
            $method_context->self = $fq_class_name;

            $this_atomic_object_type = new TNamedObject($fq_class_name);
            $this_atomic_object_type->was_static = !$storage->final;

            $method_context->vars_in_scope['$this'] = new Union([$this_atomic_object_type]);
            $method_context->vars_possibly_in_scope['$this'] = true;
            $method_context->calling_method_id = strtolower($fq_class_name) . '::__construct';

            $constructor_analyzer->analyze(
                $method_context,
                new NodeDataProvider(),
                $global_context,
                true
            );

            foreach ($uninitialized_properties as $property_id => $property_storage) {
                [, $property_name] = explode('::$', $property_id);

                if (!isset($method_context->vars_in_scope['$this->' . $property_name])) {
                    $end_type = Type::getVoid();
                    $end_type->initialized = false;
                } else {
                    $end_type = $method_context->vars_in_scope['$this->' . $property_name];
                }

                $constructor_class_property_storage = $property_storage;

                $error_location = $property_storage->location;

                if ($storage->declaring_property_ids[$property_name] !== $fq_class_name) {
                    $error_location = $storage->location ?: $storage->stmt_location;
                }

                if ($fq_class_name_lc !== $constructor_appearing_fqcln
                    && $property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
                ) {
                    $a_class_storage = $classlike_storage_provider->get(
                        $end_type->initialized_class ?: $constructor_appearing_fqcln
                    );

                    if (!isset($a_class_storage->declaring_property_ids[$property_name])) {
                        $constructor_class_property_storage = null;
                    } else {
                        $declaring_property_class = $a_class_storage->declaring_property_ids[$property_name];
                        $constructor_class_property_storage = $classlike_storage_provider
                            ->get($declaring_property_class)
                            ->properties[$property_name];
                    }
                }

                if ($property_storage->location
                    && $error_location
                    && (!$end_type->initialized || $property_storage !== $constructor_class_property_storage)
                ) {
                    if ($property_storage->type) {
                        $expected_visibility = $uninitialized_private_properties
                            ? 'private or final '
                            : '';

                        IssueBuffer::maybeAdd(
                            new PropertyNotSetInConstructor(
                                'Property ' . $class_storage->name . '::$' . $property_name
                                    . ' is not defined in constructor of '
                                    . $this->fq_class_name . ' or in any ' . $expected_visibility
                                    . 'methods called in the constructor',
                                $error_location,
                                $property_id
                            ),
                            $storage->suppressed_issues + $this->getSuppressedIssues()
                        );
                    } elseif (!$property_storage->has_default) {
                        if (isset($this->inferred_property_types[$property_name])) {
                            $this->inferred_property_types[$property_name]->addType(new TNull());
                            $this->inferred_property_types[$property_name]->setFromDocblock();
                        }
                    }
                }
            }

            $codebase->analyzer->setAnalyzedMethod(
                $included_file_path,
                $fq_class_name_lc . '::__construct',
                true
            );

            return;
        }

        if (!$storage->abstract && $uninitialized_typed_properties) {
            foreach ($uninitialized_typed_properties as $id => $uninitialized_property) {
                if ($uninitialized_property->location) {
                    IssueBuffer::maybeAdd(
                        new MissingConstructor(
                            $class_storage->name . ' has an uninitialized property ' . $id .
                                ', but no constructor',
                            $uninitialized_property->location,
                            $class_storage->name . '::' . $uninitialized_variables[0]
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    );
                }
            }
        }
    }

    /**
     * @return false|null
     */
    private function analyzeTraitUse(
        Aliases $aliases,
        PhpParser\Node\Stmt\TraitUse $stmt,
        ProjectAnalyzer $project_analyzer,
        ClassLikeStorage $storage,
        Context $class_context,
        ?Context $global_context = null,
        ?MethodAnalyzer &$constructor_analyzer = null,
        ?TraitAnalyzer $previous_trait_analyzer = null
    ): ?bool {
        $codebase = $this->getCodebase();

        $previous_context_include_location = $class_context->include_location;

        foreach ($stmt->traits as $trait_name) {
            $trait_location = new CodeLocation($this, $trait_name, null, true);
            $class_context->include_location = new CodeLocation($this, $trait_name, null, true);

            $fq_trait_name = self::getFQCLNFromNameObject(
                $trait_name,
                $aliases
            );

            if (!$codebase->classlikes->hasFullyQualifiedTraitName($fq_trait_name, $trait_location)) {
                IssueBuffer::maybeAdd(
                    new UndefinedTrait(
                        'Trait ' . $fq_trait_name . ' does not exist',
                        new CodeLocation($previous_trait_analyzer ?? $this, $trait_name)
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );

                return false;
            }

            if (!$codebase->traitHasCorrectCasing($fq_trait_name)) {
                if (IssueBuffer::accepts(
                    new UndefinedTrait(
                        'Trait ' . $fq_trait_name . ' has wrong casing',
                        new CodeLocation($previous_trait_analyzer ?? $this, $trait_name)
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                )) {
                    return false;
                }

                continue;
            }

            $fq_trait_name_resolved = $codebase->classlikes->getUnAliasedName($fq_trait_name);
            $trait_storage = $codebase->classlike_storage_provider->get($fq_trait_name_resolved);

            if ($trait_storage->deprecated) {
                IssueBuffer::maybeAdd(
                    new DeprecatedTrait(
                        'Trait ' . $fq_trait_name . ' is deprecated',
                        new CodeLocation($previous_trait_analyzer ?? $this, $trait_name)
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }

            if ($trait_storage->extension_requirement !== null) {
                $extension_requirement = $codebase->classlikes->getUnAliasedName(
                    $trait_storage->extension_requirement
                );
                $extensionRequirementMet = in_array($extension_requirement, $storage->parent_classes);

                if (!$extensionRequirementMet) {
                    IssueBuffer::maybeAdd(
                        new ExtensionRequirementViolation(
                            $fq_trait_name . ' requires using class to extend ' . $extension_requirement
                                . ', but ' . $storage->name . ' does not',
                            new CodeLocation($previous_trait_analyzer ?? $this, $trait_name)
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    );
                }
            }

            foreach ($trait_storage->implementation_requirements as $implementation_requirement) {
                $implementation_requirement = $codebase->classlikes->getUnAliasedName($implementation_requirement);
                $implementationRequirementMet = in_array($implementation_requirement, $storage->class_implements);

                if (!$implementationRequirementMet) {
                    IssueBuffer::maybeAdd(
                        new ImplementationRequirementViolation(
                            $fq_trait_name . ' requires using class to implement '
                                . $implementation_requirement . ', but ' . $storage->name . ' does not',
                            new CodeLocation($previous_trait_analyzer ?? $this, $trait_name)
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    );
                }
            }

            if ($storage->mutation_free && !$trait_storage->mutation_free) {
                IssueBuffer::maybeAdd(
                    new MutableDependency(
                        $storage->name . ' is marked @psalm-immutable but ' . $fq_trait_name . ' is not',
                        new CodeLocation($previous_trait_analyzer ?? $this, $trait_name)
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }

            $trait_file_analyzer = $project_analyzer->getFileAnalyzerForClassLike($fq_trait_name_resolved);
            $trait_node = $codebase->classlikes->getTraitNode($fq_trait_name_resolved);
            $trait_aliases = $trait_storage->aliases;
            if ($trait_aliases === null) {
                continue;
            }

            $trait_analyzer = new TraitAnalyzer(
                $trait_node,
                $trait_file_analyzer,
                $fq_trait_name_resolved,
                $trait_aliases
            );

            foreach ($trait_node->stmts as $trait_stmt) {
                if ($trait_stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                    $trait_method_analyzer = $this->analyzeClassMethod(
                        $trait_stmt,
                        $storage,
                        $trait_analyzer,
                        $class_context,
                        $global_context
                    );

                    if ($trait_stmt->name->name === '__construct') {
                        $constructor_analyzer = $trait_method_analyzer;
                    }
                } elseif ($trait_stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                    if ($this->analyzeTraitUse(
                        $trait_aliases,
                        $trait_stmt,
                        $project_analyzer,
                        $storage,
                        $class_context,
                        $global_context,
                        $constructor_analyzer,
                        $trait_analyzer
                    ) === false) {
                        return false;
                    }
                }
            }

            $trait_file_analyzer->clearSourceBeforeDestruction();
        }

        $class_context->include_location = $previous_context_include_location;

        return null;
    }

    private function analyzeProperty(
        SourceAnalyzer $source,
        PhpParser\Node\Stmt\Property $stmt,
        Context $context
    ): void {
        $fq_class_name = $source->getFQCLN();
        $property_name = $stmt->props[0]->name->name;

        $codebase = $this->getCodebase();

        $property_id = $fq_class_name . '::$' . $property_name;

        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
            $property_id,
            true
        );

        if (!$declaring_property_class) {
            return;
        }

        $fq_class_name = $declaring_property_class;

        // gets inherited property type
        $class_property_type = $codebase->properties->getPropertyType($property_id, false, $source, $context);

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $property_storage = $class_storage->properties[$property_name];

        AttributesAnalyzer::analyze(
            $source,
            $context,
            $property_storage,
            $stmt->attrGroups,
            AttributesAnalyzer::TARGET_PROPERTY,
            $property_storage->suppressed_issues + $this->getSuppressedIssues()
        );

        if ($class_property_type && ($property_storage->type_location || !$codebase->alter_code)) {
            return;
        }

        $message = 'Property ' . $property_id . ' does not have a declared type';

        $suggested_type = $property_storage->suggested_type;

        if (isset($this->inferred_property_types[$property_name])) {
            $suggested_type = Type::combineUnionTypes(
                $suggested_type,
                $this->inferred_property_types[$property_name] ?? null,
                $codebase
            );
        }

        if ($suggested_type && !$property_storage->has_default && $property_storage->is_static) {
            $suggested_type->addType(new TNull());
        }

        if ($suggested_type && !$suggested_type->isNull()) {
            $message .= ' - consider ' . str_replace(
                ['<array-key, mixed>', '<empty, empty>'],
                '',
                (string)$suggested_type
            );
        }

        $project_analyzer = ProjectAnalyzer::getInstance();

        if ($codebase->alter_code
            && $source === $this
            && isset($project_analyzer->getIssuesToFix()['MissingPropertyType'])
            && !in_array('MissingPropertyType', $this->getSuppressedIssues())
            && $suggested_type
        ) {
            if ($suggested_type->hasMixed() || $suggested_type->isNull()) {
                return;
            }

            self::addOrUpdatePropertyType(
                $project_analyzer,
                $stmt,
                $suggested_type,
                $this,
                $suggested_type->from_docblock
            );

            return;
        }

        IssueBuffer::maybeAdd(
            new MissingPropertyType(
                $message,
                new CodeLocation($source, $stmt->props[0]->name),
                $property_id
            ),
            $this->source->getSuppressedIssues() + $property_storage->suppressed_issues
        );
    }

    private static function addOrUpdatePropertyType(
        ProjectAnalyzer $project_analyzer,
        PhpParser\Node\Stmt\Property $property,
        Union $inferred_type,
        StatementsSource $source,
        bool $docblock_only = false
    ): void {
        $manipulator = PropertyDocblockManipulator::getForProperty(
            $project_analyzer,
            $source->getFilePath(),
            $property
        );

        $codebase = $project_analyzer->getCodebase();

        $allow_native_type = !$docblock_only
            && $codebase->php_major_version >= 7
            && ($codebase->php_major_version > 7 || $codebase->php_minor_version >= 4)
            && $codebase->allow_backwards_incompatible_changes;

        $manipulator->setType(
            $allow_native_type
                ? (string) $inferred_type->toPhpString(
                    $source->getNamespace(),
                    $source->getAliasedClassesFlipped(),
                    $source->getFQCLN(),
                    $codebase->php_major_version,
                    $codebase->php_minor_version
                ) : null,
            $inferred_type->toNamespacedString(
                $source->getNamespace(),
                $source->getAliasedClassesFlipped(),
                $source->getFQCLN(),
                false
            ),
            $inferred_type->toNamespacedString(
                $source->getNamespace(),
                $source->getAliasedClassesFlipped(),
                $source->getFQCLN(),
                true
            ),
            $inferred_type->canBeFullyExpressedInPhp($codebase->php_major_version, $codebase->php_minor_version)
        );
    }

    private function analyzeClassMethod(
        PhpParser\Node\Stmt\ClassMethod $stmt,
        ClassLikeStorage $class_storage,
        SourceAnalyzer $source,
        Context $class_context,
        ?Context $global_context = null,
        bool $is_fake = false
    ): ?MethodAnalyzer {
        $config = Config::getInstance();

        if ($stmt->stmts === null && !$stmt->isAbstract()) {
            IssueBuffer::maybeAdd(
                new ParseError(
                    'Non-abstract class method must have statements',
                    new CodeLocation($this, $stmt)
                )
            );

            return null;
        }

        try {
            $method_analyzer = new MethodAnalyzer($stmt, $source);
        } catch (UnexpectedValueException $e) {
            IssueBuffer::maybeAdd(
                new ParseError(
                    'Problem loading method: ' . $e->getMessage(),
                    new CodeLocation($this, $stmt)
                )
            );

            return null;
        }

        $actual_method_id = $method_analyzer->getMethodId();

        $project_analyzer = $source->getProjectAnalyzer();
        $codebase = $source->getCodebase();

        $analyzed_method_id = $actual_method_id;

        $included_file_path = $source->getFilePath();

        if ($class_context->self && strtolower($class_context->self) !== strtolower((string) $source->getFQCLN())) {
            $analyzed_method_id = $method_analyzer->getMethodId($class_context->self);

            $declaring_method_id = $codebase->methods->getDeclaringMethodId($analyzed_method_id);

            if ((string) $actual_method_id !== (string) $declaring_method_id) {
                // the method is an abstract trait method

                $declaring_method_storage = $method_analyzer->getFunctionLikeStorage();

                if (!$declaring_method_storage instanceof MethodStorage) {
                    throw new LogicException('This should never happen');
                }

                if ($declaring_method_id && $declaring_method_storage->abstract) {
                    $implementer_method_storage = $codebase->methods->getStorage($declaring_method_id);
                    $declaring_storage = $codebase->classlike_storage_provider->get(
                        $actual_method_id->fq_class_name
                    );

                    MethodComparator::compare(
                        $codebase,
                        null,
                        $class_storage,
                        $declaring_storage,
                        $implementer_method_storage,
                        $declaring_method_storage,
                        $this->fq_class_name,
                        $implementer_method_storage->visibility,
                        new CodeLocation($source, $stmt),
                        $implementer_method_storage->suppressed_issues,
                        false
                    );
                }

                return null;
            }
        }

        $trait_safe_method_id = strtolower((string) $analyzed_method_id);

        $actual_method_id_str = strtolower((string) $actual_method_id);

        if ($actual_method_id_str !== $trait_safe_method_id) {
            $trait_safe_method_id .= '&' . $actual_method_id_str;
        }

        $method_already_analyzed = $codebase->analyzer->isMethodAlreadyAnalyzed(
            $included_file_path,
            $trait_safe_method_id
        );

        $start = (int)$stmt->getAttribute('startFilePos');
        $end = (int)$stmt->getAttribute('endFilePos');

        $comments = $stmt->getComments();

        if ($comments) {
            $start = $comments[0]->getStartFilePos();
        }

        if ($codebase->diff_methods
            && $method_already_analyzed
            && !$class_context->collect_initializations
            && !$class_context->collect_mutations
            && !$is_fake
        ) {
            $project_analyzer->progress->debug(
                'Skipping analysis of pre-analyzed method ' . $analyzed_method_id . "\n"
            );

            $existing_issues = $codebase->analyzer->getExistingIssuesForFile(
                $source->getFilePath(),
                $start,
                $end
            );

            IssueBuffer::addIssues([$source->getFilePath() => $existing_issues]);

            return $method_analyzer;
        }

        $codebase->analyzer->removeExistingDataForFile(
            $source->getFilePath(),
            $start,
            $end
        );

        $method_context = clone $class_context;

        foreach ($method_context->vars_in_scope as $context_var_id => $context_type) {
            $method_context->vars_in_scope[$context_var_id] = clone $context_type;

            if ($context_type->from_property && $stmt->name->name !== '__construct') {
                $method_context->vars_in_scope[$context_var_id]->initialized = true;
            }
        }

        $method_context->collect_exceptions = $config->check_for_throws_docblock;

        $type_provider = new NodeDataProvider();

        $method_analyzer->analyze(
            $method_context,
            $type_provider,
            $global_context ? clone $global_context : null
        );

        if ($stmt->name->name !== '__construct'
            && $config->reportIssueInFile('InvalidReturnType', $source->getFilePath())
            && $class_context->self
        ) {
            self::analyzeClassMethodReturnType(
                $stmt,
                $method_analyzer,
                $source,
                $type_provider,
                $codebase,
                $class_storage,
                $class_context->self,
                $analyzed_method_id,
                $actual_method_id,
                $method_context->has_returned
            );
        }

        if (!$method_already_analyzed
            && !$class_context->collect_initializations
            && !$class_context->collect_mutations
            && !$is_fake
        ) {
            $codebase->analyzer->setAnalyzedMethod($included_file_path, $trait_safe_method_id);
        }

        return $method_analyzer;
    }

    private static function getThisObjectType(
        ClassLikeStorage $class_storage,
        string $original_fq_classlike_name
    ): TNamedObject {
        if ($class_storage->template_types) {
            $template_params = [];

            foreach ($class_storage->template_types as $param_name => $template_map) {
                $key = array_keys($template_map)[0];

                $template_params[] = new Union([
                    new TTemplateParam(
                        $param_name,
                        reset($template_map),
                        $key
                    )
                ]);
            }

            return new TGenericObject(
                $original_fq_classlike_name,
                $template_params
            );
        }

        return new TNamedObject($original_fq_classlike_name);
    }

    public static function analyzeClassMethodReturnType(
        PhpParser\Node\Stmt\ClassMethod $stmt,
        MethodAnalyzer $method_analyzer,
        SourceAnalyzer $source,
        NodeDataProvider $type_provider,
        Codebase $codebase,
        ClassLikeStorage $class_storage,
        string $fq_classlike_name,
        MethodIdentifier $analyzed_method_id,
        MethodIdentifier $actual_method_id,
        bool $did_explicitly_return
    ): void {
        $secondary_return_type_location = null;

        $actual_method_storage = $codebase->methods->getStorage($actual_method_id);

        $return_type_location = $codebase->methods->getMethodReturnTypeLocation(
            $actual_method_id,
            $secondary_return_type_location
        );

        $original_fq_classlike_name = $fq_classlike_name;

        $return_type = $codebase->methods->getMethodReturnType(
            $analyzed_method_id,
            $fq_classlike_name,
            $method_analyzer
        );

        if ($return_type && $class_storage->template_extended_params) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($analyzed_method_id);

            if ($declaring_method_id) {
                $declaring_class_name = $declaring_method_id->fq_class_name;

                $class_storage = $codebase->classlike_storage_provider->get($declaring_class_name);
            }

            $this_object_type = self::getThisObjectType(
                $class_storage,
                $original_fq_classlike_name
            );

            $class_template_params = ClassTemplateParamCollector::collect(
                $codebase,
                $class_storage,
                $codebase->classlike_storage_provider->get($original_fq_classlike_name),
                strtolower($stmt->name->name),
                $this_object_type
            ) ?: [];

            $template_result = new TemplateResult(
                $class_template_params ?: [],
                []
            );

            $return_type = TemplateStandinTypeReplacer::replace(
                $return_type,
                $template_result,
                $codebase,
                null,
                null,
                null,
                $original_fq_classlike_name
            );
        }

        $overridden_method_ids = $class_storage->overridden_method_ids[strtolower($stmt->name->name)] ?? [];

        if (!$return_type
            && !$class_storage->is_interface
            && $overridden_method_ids
        ) {
            foreach ($overridden_method_ids as $interface_method_id) {
                $interface_class = $interface_method_id->fq_class_name;

                if (!$codebase->classlikes->interfaceExists($interface_class)) {
                    continue;
                }

                $interface_return_type = $codebase->methods->getMethodReturnType(
                    $interface_method_id,
                    $interface_class
                );

                $interface_return_type_location = $codebase->methods->getMethodReturnTypeLocation(
                    $interface_method_id
                );

                ReturnTypeAnalyzer::verifyReturnType(
                    $stmt,
                    $stmt->getStmts() ?: [],
                    $source,
                    $type_provider,
                    $method_analyzer,
                    $interface_return_type,
                    $interface_class,
                    $original_fq_classlike_name,
                    $interface_return_type_location,
                    [$analyzed_method_id->__toString()],
                    $did_explicitly_return
                );
            }
        }

        $overridden_method_ids = array_map(
            function ($method_id) {
                return $method_id->__toString();
            },
            $overridden_method_ids
        );

        if ($actual_method_storage->overridden_downstream) {
            $overridden_method_ids['overridden::downstream'] = 'overridden::downstream';
        }


        ReturnTypeAnalyzer::verifyReturnType(
            $stmt,
            $stmt->getStmts() ?: [],
            $source,
            $type_provider,
            $method_analyzer,
            $return_type,
            $fq_classlike_name,
            $original_fq_classlike_name,
            $return_type_location,
            $overridden_method_ids,
            $did_explicitly_return
        );
    }

    private function checkTemplateParams(
        Codebase $codebase,
        ClassLikeStorage $storage,
        ClassLikeStorage $parent_storage,
        CodeLocation $code_location,
        int $given_param_count
    ): void {
        $expected_param_count = $parent_storage->template_types === null
            ? 0
            : count($parent_storage->template_types);

        if ($expected_param_count > $given_param_count) {
            IssueBuffer::maybeAdd(
                new MissingTemplateParam(
                    $storage->name . ' has missing template params when extending ' . $parent_storage->name
                        . ' , expecting ' . $expected_param_count,
                    $code_location
                ),
                $storage->suppressed_issues + $this->getSuppressedIssues()
            );
        } elseif ($expected_param_count < $given_param_count) {
            IssueBuffer::maybeAdd(
                new TooManyTemplateParams(
                    $storage->name . ' has too many template params when extending ' . $parent_storage->name
                        . ' , expecting ' . $expected_param_count,
                    $code_location
                ),
                $storage->suppressed_issues + $this->getSuppressedIssues()
            );
        }

        $storage_param_count = ($storage->template_types ? count($storage->template_types) : 0);

        if ($parent_storage->enforce_template_inheritance
            && $expected_param_count !== $storage_param_count
        ) {
            if ($expected_param_count > $storage_param_count) {
                IssueBuffer::maybeAdd(
                    new MissingTemplateParam(
                        $storage->name . ' requires the same number of template params as ' . $parent_storage->name
                            . ' but saw ' . $storage_param_count,
                        $code_location
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            } else {
                IssueBuffer::maybeAdd(
                    new TooManyTemplateParams(
                        $storage->name . ' requires the same number of template params as ' . $parent_storage->name
                            . ' but saw ' . $storage_param_count,
                        $code_location
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }
        }

        if ($parent_storage->template_types && $storage->template_extended_params) {
            $i = 0;

            $previous_extended = [];

            foreach ($parent_storage->template_types as $template_name => $type_map) {
                // declares the variables
                foreach ($type_map as $declaring_class => $template_type) {
                }

                if (isset($storage->template_extended_params[$parent_storage->name][$template_name])) {
                    $extended_type = $storage->template_extended_params[$parent_storage->name][$template_name];

                    if (isset($parent_storage->template_covariants[$i])
                        && !$parent_storage->template_covariants[$i]
                    ) {
                        foreach ($extended_type->getAtomicTypes() as $t) {
                            if ($t instanceof TTemplateParam
                                && $storage->template_types
                                && $storage->template_covariants
                                && ($local_offset
                                    = array_search($t->param_name, array_keys($storage->template_types)))
                                    !== false
                                && !empty($storage->template_covariants[$local_offset])
                            ) {
                                IssueBuffer::maybeAdd(
                                    new InvalidTemplateParam(
                                        'Cannot extend an invariant template param ' . $template_name
                                            . ' into a covariant context',
                                        $code_location
                                    ),
                                    $storage->suppressed_issues + $this->getSuppressedIssues()
                                );
                            }
                        }
                    }

                    if ($parent_storage->enforce_template_inheritance) {
                        foreach ($extended_type->getAtomicTypes() as $t) {
                            if (!$t instanceof TTemplateParam
                                || !isset($storage->template_types[$t->param_name])
                            ) {
                                IssueBuffer::maybeAdd(
                                    new InvalidTemplateParam(
                                        'Cannot extend a strictly-enforced parent template param '
                                            . $template_name
                                            . ' with a non-template type',
                                        $code_location
                                    ),
                                    $storage->suppressed_issues + $this->getSuppressedIssues()
                                );
                            } elseif ($storage->template_types[$t->param_name][$storage->name]->getId()
                                !== $template_type->getId()
                            ) {
                                IssueBuffer::maybeAdd(
                                    new InvalidTemplateParam(
                                        'Cannot extend a strictly-enforced parent template param '
                                            . $template_name
                                            . ' with constraint ' . $template_type->getId()
                                            . ' with a child template param ' . $t->param_name
                                            . ' with different constraint '
                                            . $storage->template_types[$t->param_name][$storage->name]->getId(),
                                        $code_location
                                    ),
                                    $storage->suppressed_issues + $this->getSuppressedIssues()
                                );
                            }
                        }
                    }

                    if (!$template_type->isMixed()) {
                        $template_type_copy = clone $template_type;

                        $template_result = new TemplateResult(
                            $previous_extended ?: [],
                            []
                        );

                        $template_type_copy = TemplateStandinTypeReplacer::replace(
                            $template_type_copy,
                            $template_result,
                            $codebase,
                            null,
                            $extended_type,
                            null,
                            null
                        );

                        if (!UnionTypeComparator::isContainedBy($codebase, $extended_type, $template_type_copy)) {
                            IssueBuffer::maybeAdd(
                                new InvalidTemplateParam(
                                    'Extended template param ' . $template_name
                                        . ' expects type ' . $template_type_copy->getId()
                                        . ', type ' . $extended_type->getId() . ' given',
                                    $code_location
                                ),
                                $storage->suppressed_issues + $this->getSuppressedIssues()
                            );
                        } else {
                            $previous_extended[$template_name] = [
                                $declaring_class => $extended_type
                            ];
                        }
                    } else {
                        $previous_extended[$template_name] = [
                            $declaring_class => $extended_type
                        ];
                    }
                }

                $i++;
            }
        }
    }

    /**
     * @param PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Enum_ $class
     */
    private function checkImplementedInterfaces(
        Context $class_context,
        PhpParser\Node\Stmt $class,
        Codebase $codebase,
        string $fq_class_name,
        ClassLikeStorage $storage
    ): bool {
        $classlike_storage_provider = $codebase->classlike_storage_provider;

        foreach ($class->implements as $interface_name) {
            $fq_interface_name = self::getFQCLNFromNameObject(
                $interface_name,
                $this->source->getAliases()
            );

            $fq_interface_name_lc = strtolower($fq_interface_name);

            $codebase->analyzer->addNodeReference(
                $this->getFilePath(),
                $interface_name,
                $codebase->classlikes->interfaceExists($fq_interface_name)
                    ? $fq_interface_name
                    : '*'
                        . ($interface_name instanceof PhpParser\Node\Name\FullyQualified
                            ? '\\'
                            : $this->getNamespace() . '-')
                        . implode('\\', $interface_name->parts)
            );

            $interface_location = new CodeLocation($this, $interface_name);

            if (self::checkFullyQualifiedClassLikeName(
                $this,
                $fq_interface_name,
                $interface_location,
                null,
                null,
                $this->getSuppressedIssues()
            ) === false) {
                return false;
            }

            if ($codebase->store_node_types && $fq_class_name) {
                $bounds = $interface_location->getSelectionBounds();

                $codebase->analyzer->addOffsetReference(
                    $this->getFilePath(),
                    $bounds[0],
                    $bounds[1],
                    $fq_interface_name
                );
            }

            $codebase->classlikes->handleClassLikeReferenceInMigration(
                $codebase,
                $this,
                $interface_name,
                $fq_interface_name,
                null
            );

            try {
                $interface_storage = $classlike_storage_provider->get($fq_interface_name);
            } catch (InvalidArgumentException $e) {
                return false;
            }

            $code_location = new CodeLocation(
                $this,
                $interface_name,
                $class_context->include_location,
                true
            );

            if (!$interface_storage->is_interface) {
                IssueBuffer::maybeAdd(
                    new UndefinedInterface(
                        $fq_interface_name . ' is not an interface',
                        $code_location,
                        $fq_interface_name
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }

            if (isset($storage->template_type_implements_count[$fq_interface_name_lc])) {
                $this->checkTemplateParams(
                    $codebase,
                    $storage,
                    $interface_storage,
                    $code_location,
                    $storage->template_type_implements_count[$fq_interface_name_lc]
                );
            }
        }

        foreach ($storage->class_implements as $fq_interface_name_lc => $fq_interface_name) {
            try {
                $interface_storage = $classlike_storage_provider->get($fq_interface_name_lc);
            } catch (InvalidArgumentException $e) {
                return false;
            }

            $code_location = new CodeLocation(
                $this,
                $class->name ?? $class,
                $class_context->include_location,
                true
            );

            if ($fq_interface_name_lc === 'traversable'
                && !$storage->abstract
                && !isset($storage->class_implements['iteratoraggregate'])
                && !isset($storage->class_implements['iterator'])
                && !isset($storage->parent_classes['pdostatement'])
                && !isset($storage->parent_classes['ds\collection'])
                && !isset($storage->parent_classes['domnodelist'])
                && !isset($storage->parent_classes['dateperiod'])
            ) {
                IssueBuffer::maybeAdd(
                    new InvalidTraversableImplementation(
                        'Traversable should be implemented by implementing IteratorAggregate or Iterator',
                        $code_location,
                        $fq_class_name
                    )
                );
            }

            if ($interface_storage->deprecated) {
                IssueBuffer::maybeAdd(
                    new DeprecatedInterface(
                        $fq_interface_name . ' is marked deprecated',
                        $code_location,
                        $fq_interface_name
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }

            if ($interface_storage->external_mutation_free
                && !$storage->external_mutation_free
            ) {
                IssueBuffer::maybeAdd(
                    new MissingImmutableAnnotation(
                        $fq_interface_name . ' is marked @psalm-immutable, but '
                        . $fq_class_name . ' is not marked @psalm-immutable',
                        $code_location
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }

            foreach ($interface_storage->methods as $interface_method_name_lc => $interface_method_storage) {
                if ($interface_method_storage->visibility === self::VISIBILITY_PUBLIC) {
                    $implementer_declaring_method_id = $codebase->methods->getDeclaringMethodId(
                        new MethodIdentifier(
                            $this->fq_class_name,
                            $interface_method_name_lc
                        )
                    );

                    $implementer_method_storage = null;
                    $implementer_classlike_storage = null;

                    if ($implementer_declaring_method_id) {
                        $implementer_fq_class_name = $implementer_declaring_method_id->fq_class_name;
                        $implementer_method_storage = $codebase->methods->getStorage(
                            $implementer_declaring_method_id
                        );
                        $implementer_classlike_storage = $classlike_storage_provider->get(
                            $implementer_fq_class_name
                        );
                    }

                    if ($storage->is_enum) {
                        if ($interface_method_name_lc === 'cases') {
                            continue;
                        }
                        if ($storage->enum_type
                            && in_array($interface_method_name_lc, ['from', 'tryfrom'], true)
                        ) {
                            continue;
                        }
                    }

                    if (!$implementer_method_storage) {
                        IssueBuffer::maybeAdd(
                            new UnimplementedInterfaceMethod(
                                'Method ' . $interface_method_name_lc . ' is not defined on class ' .
                                $storage->name,
                                $code_location
                            ),
                            $storage->suppressed_issues + $this->getSuppressedIssues()
                        );

                        return true;
                    }

                    $implementer_appearing_method_id = $codebase->methods->getAppearingMethodId(
                        new MethodIdentifier(
                            $this->fq_class_name,
                            $interface_method_name_lc
                        )
                    );

                    $implementer_visibility = $implementer_method_storage->visibility;

                    if ($implementer_appearing_method_id
                        && $implementer_appearing_method_id !== $implementer_declaring_method_id
                    ) {
                        $appearing_fq_class_name = $implementer_appearing_method_id->fq_class_name;
                        $appearing_method_name = $implementer_appearing_method_id->method_name;

                        $appearing_class_storage = $classlike_storage_provider->get(
                            $appearing_fq_class_name
                        );

                        if (isset($appearing_class_storage->trait_visibility_map[$appearing_method_name])) {
                            $implementer_visibility
                                = $appearing_class_storage->trait_visibility_map[$appearing_method_name];
                        }
                    }

                    if ($implementer_visibility !== self::VISIBILITY_PUBLIC) {
                        IssueBuffer::maybeAdd(
                            new InaccessibleMethod(
                                'Interface-defined method ' . $implementer_method_storage->cased_name
                                . ' must be public in ' . $storage->name,
                                $code_location
                            ),
                            $storage->suppressed_issues + $this->getSuppressedIssues()
                        );

                        return true;
                    }

                    if ($interface_method_storage->is_static && !$implementer_method_storage->is_static) {
                        IssueBuffer::maybeAdd(
                            new MethodSignatureMismatch(
                                'Method ' . $implementer_method_storage->cased_name
                                . ' should be static like '
                                . $storage->name . '::' . $interface_method_storage->cased_name,
                                $code_location
                            ),
                            $implementer_method_storage->suppressed_issues
                        );

                        return true;
                    }

                    if ($storage->abstract && $implementer_method_storage === $interface_method_storage) {
                        continue;
                    }

                    MethodComparator::compare(
                        $codebase,
                        null,
                        $implementer_classlike_storage ?? $storage,
                        $interface_storage,
                        $implementer_method_storage,
                        $interface_method_storage,
                        $this->fq_class_name,
                        $implementer_visibility,
                        $code_location,
                        $implementer_method_storage->suppressed_issues,
                        false
                    );
                }
            }
        }

        return true;
    }

    private function checkParentClass(
        Class_ $class,
        PhpParser\Node\Name $extended_class,
        string $fq_class_name,
        string $parent_fq_class_name,
        ClassLikeStorage $storage,
        Codebase $codebase,
        ?Context $class_context
    ): void {
        $classlike_storage_provider = $codebase->classlike_storage_provider;

        if (!$parent_fq_class_name) {
            throw new UnexpectedValueException('Parent class should be filled in for ' . $fq_class_name);
        }

        $parent_reference_location = new CodeLocation($this, $extended_class);

        if (self::checkFullyQualifiedClassLikeName(
            $this->getSource(),
            $parent_fq_class_name,
            $parent_reference_location,
            null,
            null,
            $storage->suppressed_issues + $this->getSuppressedIssues()
        ) === false) {
            return;
        }

        if ($codebase->alter_code && $codebase->classes_to_move) {
            $codebase->classlikes->handleClassLikeReferenceInMigration(
                $codebase,
                $this,
                $extended_class,
                $parent_fq_class_name,
                null
            );
        }

        try {
            $parent_class_storage = $classlike_storage_provider->get($parent_fq_class_name);

            $code_location = new CodeLocation(
                $this,
                $extended_class,
                $class_context->include_location ?? null,
                true
            );

            if ($parent_class_storage->is_trait || $parent_class_storage->is_interface) {
                IssueBuffer::maybeAdd(
                    new UndefinedClass(
                        $parent_fq_class_name . ' is not a class',
                        $code_location,
                        $parent_fq_class_name . ' as class'
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }

            if ($parent_class_storage->final) {
                IssueBuffer::maybeAdd(
                    new InvalidExtendClass(
                        'Class ' . $fq_class_name . ' may not inherit from final class ' . $parent_fq_class_name,
                        $code_location,
                        $fq_class_name
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }

            if ($parent_class_storage->deprecated) {
                IssueBuffer::maybeAdd(
                    new DeprecatedClass(
                        $parent_fq_class_name . ' is marked deprecated',
                        $code_location,
                        $parent_fq_class_name
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }

            if (!NamespaceAnalyzer::isWithinAny($fq_class_name, $parent_class_storage->internal)) {
                IssueBuffer::maybeAdd(
                    new InternalClass(
                        $parent_fq_class_name . ' is internal to '
                            . InternalClass::listToPhrase($parent_class_storage->internal)
                            . ' but called from ' . $fq_class_name,
                        $code_location,
                        $parent_fq_class_name
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }

            if ($parent_class_storage->external_mutation_free
                && !$storage->external_mutation_free
            ) {
                IssueBuffer::maybeAdd(
                    new MissingImmutableAnnotation(
                        $parent_fq_class_name . ' is marked @psalm-immutable, but '
                        . $fq_class_name . ' is not marked @psalm-immutable',
                        $code_location
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }

            if ($storage->mutation_free
                && !$parent_class_storage->mutation_free
            ) {
                IssueBuffer::maybeAdd(
                    new MutableDependency(
                        $fq_class_name . ' is marked @psalm-immutable but ' . $parent_fq_class_name . ' is not',
                        $code_location
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                );
            }

            if ($codebase->store_node_types) {
                $codebase->analyzer->addNodeReference(
                    $this->getFilePath(),
                    $extended_class,
                    $codebase->classlikes->classExists($parent_fq_class_name)
                        ? $parent_fq_class_name
                        : '*'
                            . ($extended_class instanceof PhpParser\Node\Name\FullyQualified
                                ? '\\'
                                : $this->getNamespace() . '-')
                            . implode('\\', $extended_class->parts)
                );
            }

            if ($storage->template_extended_count !== null || $parent_class_storage->enforce_template_inheritance) {
                $code_location = new CodeLocation(
                    $this,
                    $class->name ?: $class,
                    $class_context->include_location ?? null,
                    true
                );

                $this->checkTemplateParams(
                    $codebase,
                    $storage,
                    $parent_class_storage,
                    $code_location,
                    $storage->template_extended_count ?? 0
                );
            }
        } catch (InvalidArgumentException $e) {
            // do nothing
        }
    }

    private function checkEnum(): void
    {
        $storage = $this->storage;

        $seen_values = [];
        foreach ($storage->enum_cases as $case_storage) {
            if ($case_storage->value !== null && $storage->enum_type === null) {
                if (IssueBuffer::accepts(
                    new InvalidEnumCaseValue(
                        'Case of a non-backed enum should not have a value',
                        $case_storage->stmt_location,
                        $storage->name
                    )
                )) {
                }
            } elseif ($case_storage->value === null && $storage->enum_type !== null) {
                if (IssueBuffer::accepts(
                    new InvalidEnumCaseValue(
                        'Case of a backed enum should have a value',
                        $case_storage->stmt_location,
                        $storage->name
                    )
                )) {
                }
            } elseif ($case_storage->value !== null && $storage->enum_type !== null) {
                if ((is_int($case_storage->value) && $storage->enum_type === 'string')
                    || (is_string($case_storage->value) && $storage->enum_type === 'int')
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidEnumCaseValue(
                            'Enum case value type should be ' . $storage->enum_type,
                            $case_storage->stmt_location,
                            $storage->name
                        )
                    )) {
                    }
                }
            }

            if ($case_storage->value !== null) {
                if (in_array($case_storage->value, $seen_values, true)) {
                    if (IssueBuffer::accepts(
                        new DuplicateEnumCaseValue(
                            'Enum case values should be unique',
                            $case_storage->stmt_location,
                            $storage->name
                        )
                    )) {
                    }
                } else {
                    $seen_values[] = $case_storage->value;
                }
            }
        }
    }
}
