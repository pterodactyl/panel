<?php

namespace Psalm\Internal\Codebase;

use InvalidArgumentException;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\SourceAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\MethodExistenceProvider;
use Psalm\Internal\Provider\MethodParamsProvider;
use Psalm\Internal\Provider\MethodReturnTypeProvider;
use Psalm\Internal\Provider\MethodVisibilityProvider;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeExpander;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_merge;
use function array_pop;
use function array_values;
use function assert;
use function count;
use function explode;
use function in_array;
use function is_int;
use function reset;
use function strtolower;

/**
 * @internal
 *
 * Handles information about class methods
 */
class Methods
{
    /**
     * @var ClassLikeStorageProvider
     */
    private $classlike_storage_provider;

    /**
     * @var bool
     */
    public $collect_locations = false;

    /**
     * @var FileReferenceProvider
     */
    public $file_reference_provider;

    /**
     * @var ClassLikes
     */
    private $classlikes;

    /** @var MethodReturnTypeProvider */
    public $return_type_provider;

    /** @var MethodParamsProvider */
    public $params_provider;

    /** @var MethodExistenceProvider */
    public $existence_provider;

    /** @var MethodVisibilityProvider */
    public $visibility_provider;

    public function __construct(
        ClassLikeStorageProvider $storage_provider,
        FileReferenceProvider $file_reference_provider,
        ClassLikes $classlikes
    ) {
        $this->classlike_storage_provider = $storage_provider;
        $this->file_reference_provider = $file_reference_provider;
        $this->classlikes = $classlikes;
        $this->return_type_provider = new MethodReturnTypeProvider();
        $this->existence_provider = new MethodExistenceProvider();
        $this->visibility_provider = new MethodVisibilityProvider();
        $this->params_provider = new MethodParamsProvider();
    }

    /**
     * Whether or not a given method exists
     *
     * If you pass true in $is_used argument the method return is considered used
     *
     * @param lowercase-string|null $calling_method_id
     */
    public function methodExists(
        MethodIdentifier $method_id,
        ?string $calling_method_id = null,
        ?CodeLocation $code_location = null,
        ?StatementsSource $source = null,
        ?string $source_file_path = null,
        bool $use_method_existence_provider = true,
        bool $is_used = false
    ): bool {
        $fq_class_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        if ($use_method_existence_provider && $this->existence_provider->has($fq_class_name)) {
            $method_exists = $this->existence_provider->doesMethodExist(
                $fq_class_name,
                $method_name,
                $source,
                $code_location
            );

            if ($method_exists !== null) {
                return $method_exists;
            }
        }

        $old_method_id = null;

        $fq_class_name = strtolower($this->classlikes->getUnAliasedName($fq_class_name));

        try {
            $class_storage = $this->classlike_storage_provider->get($fq_class_name);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        if ($class_storage->is_enum) {
            if ($method_name === 'cases') {
                return true;
            }

            if ($class_storage->enum_type
                && in_array($method_name, ['from', 'tryFrom'], true)
            ) {
                return true;
            }
        }

        $source_file_path = $source ? $source->getFilePath() : $source_file_path;

        $calling_class_name = $source ? $source->getFQCLN() : null;

        if (!$calling_class_name && $calling_method_id) {
            $calling_class_name = explode('::', $calling_method_id)[0];
        }

        if (isset($class_storage->declaring_method_ids[$method_name])) {
            $declaring_method_id = $class_storage->declaring_method_ids[$method_name];

            if ($calling_method_id === strtolower((string) $declaring_method_id)) {
                return true;
            }

            $declaring_fq_class_name = strtolower($declaring_method_id->fq_class_name);

            if ($declaring_fq_class_name !== strtolower((string) $calling_class_name)) {
                if ($calling_method_id) {
                    $this->file_reference_provider->addMethodReferenceToClass(
                        $calling_method_id,
                        $declaring_fq_class_name
                    );
                } elseif ($source_file_path) {
                    $this->file_reference_provider->addNonMethodReferenceToClass(
                        $source_file_path,
                        $declaring_fq_class_name
                    );
                }
            }

            if ((string) $method_id !== (string) $declaring_method_id
                && $class_storage->user_defined
                && isset($class_storage->potential_declaring_method_ids[$method_name])
            ) {
                foreach ($class_storage->potential_declaring_method_ids[$method_name] as $potential_id => $_) {
                    if ($calling_method_id) {
                        $this->file_reference_provider->addMethodReferenceToClassMember(
                            $calling_method_id,
                            $potential_id,
                            $is_used
                        );
                    } elseif ($source_file_path) {
                        $this->file_reference_provider->addFileReferenceToClassMember(
                            $source_file_path,
                            $potential_id,
                            $is_used
                        );
                    }
                }
            } else {
                if ($calling_method_id) {
                    $this->file_reference_provider->addMethodReferenceToClassMember(
                        $calling_method_id,
                        strtolower((string) $declaring_method_id),
                        $is_used
                    );
                } elseif ($source_file_path) {
                    $this->file_reference_provider->addFileReferenceToClassMember(
                        $source_file_path,
                        strtolower((string) $declaring_method_id),
                        $is_used
                    );
                }
            }

            if ($this->collect_locations && $code_location) {
                $this->file_reference_provider->addCallingLocationForClassMethod(
                    $code_location,
                    strtolower((string) $declaring_method_id)
                );
            }

            foreach ($class_storage->class_implements as $fq_interface_name) {
                $interface_method_id_lc = strtolower($fq_interface_name . '::' . $method_name);

                if ($this->collect_locations && $code_location) {
                    $this->file_reference_provider->addCallingLocationForClassMethod(
                        $code_location,
                        $interface_method_id_lc
                    );
                }

                if ($calling_method_id) {
                    $this->file_reference_provider->addMethodReferenceToClassMember(
                        $calling_method_id,
                        $interface_method_id_lc,
                        $is_used
                    );
                } elseif ($source_file_path) {
                    $this->file_reference_provider->addFileReferenceToClassMember(
                        $source_file_path,
                        $interface_method_id_lc,
                        $is_used
                    );
                }
            }

            $declaring_method_class = $declaring_method_id->fq_class_name;
            $declaring_method_name = $declaring_method_id->method_name;

            $declaring_class_storage = $this->classlike_storage_provider->get($declaring_method_class);

            if (isset($declaring_class_storage->overridden_method_ids[$declaring_method_name])) {
                $overridden_method_ids = $declaring_class_storage->overridden_method_ids[$declaring_method_name];

                foreach ($overridden_method_ids as $overridden_method_id) {
                    if ($this->collect_locations && $code_location) {
                        $this->file_reference_provider->addCallingLocationForClassMethod(
                            $code_location,
                            strtolower((string) $overridden_method_id)
                        );
                    }

                    if ($calling_method_id) {
                        // also store failures in case the method is added later
                        $this->file_reference_provider->addMethodReferenceToClassMember(
                            $calling_method_id,
                            strtolower((string) $overridden_method_id),
                            $is_used
                        );
                    } elseif ($source_file_path) {
                        $this->file_reference_provider->addFileReferenceToClassMember(
                            $source_file_path,
                            strtolower((string) $overridden_method_id),
                            $is_used
                        );
                    }
                }
            }

            return true;
        }

        if ($source_file_path && $fq_class_name !== strtolower((string) $calling_class_name)) {
            if ($calling_method_id) {
                $this->file_reference_provider->addMethodReferenceToClass(
                    $calling_method_id,
                    $fq_class_name
                );
            } else {
                $this->file_reference_provider->addNonMethodReferenceToClass(
                    $source_file_path,
                    $fq_class_name
                );
            }
        }

        if ($class_storage->abstract && isset($class_storage->overridden_method_ids[$method_name])) {
            return true;
        }

        // support checking oldstyle constructors
        if ($method_name === '__construct') {
            $method_name_parts = explode('\\', $fq_class_name);
            $old_constructor_name = array_pop($method_name_parts);
            $old_method_id = $fq_class_name . '::' . $old_constructor_name;
        }

        if (!$class_storage->user_defined
            && (InternalCallMapHandler::inCallMap((string) $method_id)
                || ($old_method_id && InternalCallMapHandler::inCallMap($old_method_id)))
        ) {
            return true;
        }

        foreach ($class_storage->parent_classes + $class_storage->used_traits as $potential_future_declaring_fqcln) {
            $potential_id = strtolower($potential_future_declaring_fqcln) . '::' . $method_name;

            if ($calling_method_id) {
                // also store failures in case the method is added later
                $this->file_reference_provider->addMethodReferenceToMissingClassMember(
                    $calling_method_id,
                    $potential_id
                );
            } elseif ($source_file_path) {
                $this->file_reference_provider->addFileReferenceToMissingClassMember(
                    $source_file_path,
                    $potential_id
                );
            }
        }

        if ($calling_method_id) {
            // also store failures in case the method is added later
            $this->file_reference_provider->addMethodReferenceToMissingClassMember(
                $calling_method_id,
                strtolower((string) $method_id)
            );
        } elseif ($source_file_path) {
            $this->file_reference_provider->addFileReferenceToMissingClassMember(
                $source_file_path,
                strtolower((string) $method_id)
            );
        }

        return false;
    }

    /**
     * @param  list<PhpParser\Node\Arg> $args
     *
     * @return list<FunctionLikeParameter>
     */
    public function getMethodParams(
        MethodIdentifier $method_id,
        ?StatementsSource $source = null,
        ?array $args = null,
        ?Context $context = null
    ): array {
        $fq_class_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        if ($this->params_provider->has($fq_class_name)) {
            $method_params = $this->params_provider->getMethodParams(
                $fq_class_name,
                $method_name,
                $args,
                $source,
                $context
            );

            if ($method_params !== null) {
                return $method_params;
            }
        }

        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        $callmap_id = $declaring_method_id ?? $method_id;

        // functions
        if (InternalCallMapHandler::inCallMap((string) $callmap_id)) {
            $class_storage = $this->classlike_storage_provider->get($callmap_id->fq_class_name);

            $declaring_method_name = $declaring_method_id->method_name ?? $method_name;

            if (!$class_storage->stubbed || empty($class_storage->methods[$declaring_method_name]->stubbed)) {
                $function_callables = InternalCallMapHandler::getCallablesFromCallMap((string) $callmap_id);

                if ($function_callables === null) {
                    throw new UnexpectedValueException(
                        'Not expecting $function_callables to be null for ' . $callmap_id
                    );
                }

                if (!$source || $args === null || count($function_callables) === 1) {
                    assert($function_callables[0]->params !== null);

                    return $function_callables[0]->params;
                }

                if ($context && $source instanceof StatementsAnalyzer) {
                    $was_inside_call = $context->inside_call;

                    $context->inside_call = true;

                    foreach ($args as $arg) {
                        ExpressionAnalyzer::analyze(
                            $source,
                            $arg->value,
                            $context
                        );
                    }

                    $context->inside_call = $was_inside_call;
                }

                $matching_callable = InternalCallMapHandler::getMatchingCallableFromCallMapOptions(
                    $source->getCodebase(),
                    $function_callables,
                    $args,
                    $source->getNodeTypeProvider(),
                    (string) $callmap_id
                );

                assert($matching_callable->params !== null);

                return $matching_callable->params;
            }
        }

        if ($declaring_method_id) {
            $storage = $this->getStorage($declaring_method_id);

            $params = $storage->params;

            if ($storage->has_docblock_param_types) {
                return $params;
            }

            $appearing_method_id = $this->getAppearingMethodId($declaring_method_id);

            if (!$appearing_method_id) {
                return $params;
            }

            $appearing_fq_class_name = $appearing_method_id->fq_class_name;
            $appearing_method_name = $appearing_method_id->method_name;

            $class_storage = $this->classlike_storage_provider->get($appearing_fq_class_name);

            if (!isset($class_storage->overridden_method_ids[$appearing_method_name])) {
                return $params;
            }

            if (!isset($class_storage->documenting_method_ids[$appearing_method_name])) {
                return $params;
            }

            $overridden_method_id = $class_storage->documenting_method_ids[$appearing_method_name];

            $overridden_storage = $this->getStorage($overridden_method_id);

            $overriding_fq_class_name = $overridden_method_id->fq_class_name;

            foreach ($params as $i => $param) {
                if (isset($overridden_storage->params[$i]->type)
                    && $overridden_storage->params[$i]->has_docblock_type
                ) {
                    $params[$i] = clone $param;
                    /** @var Union $params[$i]->type */
                    $params[$i]->type = clone $overridden_storage->params[$i]->type;

                    if ($source) {
                        $overridden_class_storage = $this->classlike_storage_provider->get($overriding_fq_class_name);
                        $params[$i]->type = self::localizeType(
                            $source->getCodebase(),
                            $params[$i]->type,
                            $appearing_fq_class_name,
                            $overridden_class_storage->name
                        );
                    }

                    if ($params[$i]->signature_type
                        && $params[$i]->signature_type->isNullable()
                    ) {
                        $params[$i]->type->addType(new TNull);
                    }

                    $params[$i]->type_location = $overridden_storage->params[$i]->type_location;
                }
            }

            return $params;
        }

        throw new UnexpectedValueException('Cannot get method params for ' . $method_id);
    }

    public static function localizeType(
        Codebase $codebase,
        Union $type,
        string $appearing_fq_class_name,
        string $base_fq_class_name
    ): Union {
        $class_storage = $codebase->classlike_storage_provider->get($appearing_fq_class_name);
        $extends = $class_storage->template_extended_params;

        if (!$extends) {
            return $type;
        }

        $type = clone $type;

        foreach ($type->getAtomicTypes() as $key => $atomic_type) {
            if ($atomic_type instanceof TTemplateParam
                && ($atomic_type->defining_class === $base_fq_class_name
                    || isset($extends[$atomic_type->defining_class]))
            ) {
                $types_to_add = self::getExtendedTemplatedTypes(
                    $atomic_type,
                    $extends
                );

                if ($types_to_add) {
                    $type->removeType($key);

                    foreach ($types_to_add as $extra_added_type) {
                        $type->addType($extra_added_type);
                    }
                }
            }

            if ($atomic_type instanceof TTemplateParamClass) {
                if ($atomic_type->defining_class === $base_fq_class_name) {
                    if (isset($extends[$base_fq_class_name][$atomic_type->param_name])) {
                        $extended_param = $extends[$base_fq_class_name][$atomic_type->param_name];

                        $types = array_values($extended_param->getAtomicTypes());

                        if (count($types) === 1 && $types[0] instanceof TNamedObject) {
                            $atomic_type->as_type = $types[0];
                        } else {
                            $atomic_type->as_type = null;
                        }
                    }
                }
            }

            if ($atomic_type instanceof TArray
                || $atomic_type instanceof TIterable
                || $atomic_type instanceof TGenericObject
            ) {
                foreach ($atomic_type->type_params as &$type_param) {
                    $type_param = self::localizeType(
                        $codebase,
                        $type_param,
                        $appearing_fq_class_name,
                        $base_fq_class_name
                    );
                }
            }

            if ($atomic_type instanceof TList) {
                $atomic_type->type_param = self::localizeType(
                    $codebase,
                    $atomic_type->type_param,
                    $appearing_fq_class_name,
                    $base_fq_class_name
                );
            }

            if ($atomic_type instanceof TKeyedArray) {
                foreach ($atomic_type->properties as &$property_type) {
                    $property_type = self::localizeType(
                        $codebase,
                        $property_type,
                        $appearing_fq_class_name,
                        $base_fq_class_name
                    );
                }
            }

            if ($atomic_type instanceof TCallable
                || $atomic_type instanceof TClosure
            ) {
                if ($atomic_type->params) {
                    foreach ($atomic_type->params as $param) {
                        if ($param->type) {
                            $param->type = self::localizeType(
                                $codebase,
                                $param->type,
                                $appearing_fq_class_name,
                                $base_fq_class_name
                            );
                        }
                    }
                }

                if ($atomic_type->return_type) {
                    $atomic_type->return_type = self::localizeType(
                        $codebase,
                        $atomic_type->return_type,
                        $appearing_fq_class_name,
                        $base_fq_class_name
                    );
                }
            }
        }

        $type->bustCache();

        return $type;
    }

    /**
     * @param array<string, array<string, Union>> $extends
     * @return list<Atomic>
     */
    public static function getExtendedTemplatedTypes(
        TTemplateParam $atomic_type,
        array $extends
    ): array {
        $extra_added_types = [];

        if (isset($extends[$atomic_type->defining_class][$atomic_type->param_name])) {
            $extended_param = clone $extends[$atomic_type->defining_class][$atomic_type->param_name];

            foreach ($extended_param->getAtomicTypes() as $extended_atomic_type) {
                if ($extended_atomic_type instanceof TTemplateParam) {
                    $extra_added_types = array_merge(
                        $extra_added_types,
                        self::getExtendedTemplatedTypes(
                            $extended_atomic_type,
                            $extends
                        )
                    );
                } else {
                    $extra_added_types[] = $extended_atomic_type;
                }
            }
        } else {
            $extra_added_types[] = $atomic_type;
        }

        return $extra_added_types;
    }

    public function isVariadic(MethodIdentifier $method_id): bool
    {
        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            return false;
        }

        return $this->getStorage($declaring_method_id)->variadic;
    }

    /**
     * @param  list<PhpParser\Node\Arg>|null $args
     *
     */
    public function getMethodReturnType(
        MethodIdentifier $method_id,
        ?string &$self_class,
        ?SourceAnalyzer $source_analyzer = null,
        ?array $args = null
    ): ?Union {
        $original_fq_class_name = $method_id->fq_class_name;
        $original_method_name = $method_id->method_name;

        $adjusted_fq_class_name = $this->classlikes->getUnAliasedName($original_fq_class_name);

        if ($adjusted_fq_class_name !== $original_fq_class_name) {
            $original_fq_class_name = strtolower($adjusted_fq_class_name);
        }

        $original_class_storage = $this->classlike_storage_provider->get($original_fq_class_name);

        if (isset($original_class_storage->pseudo_methods[$original_method_name])) {
            return $original_class_storage->pseudo_methods[$original_method_name]->return_type;
        }

        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            return null;
        }

        $appearing_method_id = $this->getAppearingMethodId($method_id);

        if (!$appearing_method_id) {
            $class_storage = $this->classlike_storage_provider->get($original_fq_class_name);

            if ($class_storage->abstract && isset($class_storage->overridden_method_ids[$original_method_name])) {
                $appearing_method_id = reset($class_storage->overridden_method_ids[$original_method_name]);
            } else {
                return null;
            }
        }

        $appearing_fq_class_name = $appearing_method_id->fq_class_name;
        $appearing_method_name = $appearing_method_id->method_name;

        $appearing_fq_class_storage = $this->classlike_storage_provider->get($appearing_fq_class_name);

        if ($appearing_fq_class_name === 'UnitEnum'
            && $original_class_storage->is_enum
        ) {
            if ($original_method_name === 'cases') {
                if ($original_class_storage->enum_cases === []) {
                    return Type::getEmptyArray();
                }
                $types = [];

                foreach ($original_class_storage->enum_cases as $case_name => $_) {
                    $types[] = new Union([new TEnumCase($original_fq_class_name, $case_name)]);
                }

                $list = new TKeyedArray($types);
                $list->is_list = true;
                $list->sealed = true;
                return new Union([$list]);
            }
        }

        if ($appearing_fq_class_name === 'BackedEnum'
            && $original_class_storage->is_enum
            && $original_class_storage->enum_type
        ) {
            if (($original_method_name === 'from'
                || $original_method_name === 'tryfrom'
                ) && $source_analyzer
                && isset($args[0])
                && ($first_arg_type = $source_analyzer->getNodeTypeProvider()->getType($args[0]->value))
            ) {
                $types = [];
                foreach ($original_class_storage->enum_cases as $case_name => $case_storage) {
                    if (UnionTypeComparator::isContainedBy(
                        $source_analyzer->getCodebase(),
                        is_int($case_storage->value) ?
                            Type::getInt(false, $case_storage->value) :
                            Type::getString($case_storage->value),
                        $first_arg_type
                    )) {
                        $types[] = new TEnumCase($original_fq_class_name, $case_name);
                    }
                }
                if ($types) {
                    if ($original_method_name === 'tryfrom') {
                        $types[] = new TNull();
                    }
                    return new Union($types);
                }
                return $original_method_name === 'tryfrom' ? Type::getNull() : Type::getNever();
            }
        }

        if (!$appearing_fq_class_storage->user_defined
            && !$appearing_fq_class_storage->stubbed
            && InternalCallMapHandler::inCallMap((string) $appearing_method_id)
        ) {
            if ((string) $appearing_method_id === 'Closure::fromcallable'
                && isset($args[0])
                && $source_analyzer
                && ($first_arg_type = $source_analyzer->getNodeTypeProvider()->getType($args[0]->value))
                && $first_arg_type->isSingle()
            ) {
                foreach ($first_arg_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TCallable
                        || $atomic_type instanceof TClosure
                    ) {
                        $callable_type = clone $atomic_type;

                        return new Union([new TClosure(
                            'Closure',
                            $callable_type->params,
                            $callable_type->return_type
                        )]);
                    }

                    if ($atomic_type instanceof TNamedObject
                        && $this->methodExists(
                            new MethodIdentifier($atomic_type->value, '__invoke')
                        )
                    ) {
                        $invokable_storage = $this->getStorage(
                            new MethodIdentifier($atomic_type->value, '__invoke')
                        );

                        return new Union([new TClosure(
                            'Closure',
                            $invokable_storage->params,
                            $invokable_storage->return_type
                        )]);
                    }
                }
            }

            $callmap_callables = InternalCallMapHandler::getCallablesFromCallMap((string) $appearing_method_id);

            if (!$callmap_callables || $callmap_callables[0]->return_type === null) {
                throw new UnexpectedValueException('Shouldn’t get here');
            }

            $return_type_candidate = $callmap_callables[0]->return_type;

            if ($return_type_candidate->isFalsable()) {
                $return_type_candidate->ignore_falsable_issues = true;
            }

            return $return_type_candidate;
        }

        $class_storage = $this->classlike_storage_provider->get($appearing_fq_class_name);

        $storage = $this->getStorage($declaring_method_id);

        $candidate_type = $storage->return_type;

        if ($candidate_type && $candidate_type->isVoid()) {
            return clone $candidate_type;
        }

        if (isset($class_storage->documenting_method_ids[$appearing_method_name])) {
            $overridden_method_id = $class_storage->documenting_method_ids[$appearing_method_name];

            // special override to allow inference of Iterator types
            if ($overridden_method_id->fq_class_name === 'Iterator'
                && $storage->return_type
                && $storage->return_type === $storage->signature_return_type
            ) {
                return clone $storage->return_type;
            }

            $overridden_storage = $this->getStorage($overridden_method_id);

            if ($overridden_storage->return_type) {
                if ($overridden_storage->return_type->isNull()) {
                    return Type::getVoid();
                }

                if (!$candidate_type || !$source_analyzer) {
                    $self_class = $overridden_method_id->fq_class_name;

                    return clone $overridden_storage->return_type;
                }

                if ($candidate_type->getId() === $overridden_storage->return_type->getId()) {
                    $self_class = $appearing_fq_class_storage->name;

                    return clone $candidate_type;
                }

                $overridden_class_storage =
                    $this->classlike_storage_provider->get($overridden_method_id->fq_class_name);

                $overridden_storage_return_type = TypeExpander::expandUnion(
                    $source_analyzer->getCodebase(),
                    clone $overridden_storage->return_type,
                    $overridden_method_id->fq_class_name,
                    $appearing_fq_class_name,
                    $overridden_class_storage->parent_class,
                    true,
                    false,
                    $storage->final
                );

                $old_contained_by_new = UnionTypeComparator::isContainedBy(
                    $source_analyzer->getCodebase(),
                    $candidate_type,
                    $overridden_storage_return_type
                );

                $new_contained_by_old = UnionTypeComparator::isContainedBy(
                    $source_analyzer->getCodebase(),
                    $overridden_storage_return_type,
                    $candidate_type
                );

                if ((!$old_contained_by_new && !$new_contained_by_old)
                    || ($old_contained_by_new && $new_contained_by_old)
                ) {
                    if ($old_contained_by_new) { //implicitly $new_contained_by_old as well
                        $attempted_intersection = Type::intersectUnionTypes(
                            $candidate_type,
                            $overridden_storage->return_type,
                            $source_analyzer->getCodebase()
                        );
                    } else {
                        $attempted_intersection = Type::intersectUnionTypes(
                            $overridden_storage->return_type,
                            $candidate_type,
                            $source_analyzer->getCodebase()
                        );
                    }

                    if ($attempted_intersection) {
                        $self_class = $overridden_method_id->fq_class_name;

                        return $attempted_intersection;
                    }

                    $self_class = $appearing_fq_class_storage->name;

                    return clone $candidate_type;
                }

                if ($old_contained_by_new) {
                    $self_class = $appearing_fq_class_storage->name;

                    return clone $candidate_type;
                }

                $self_class = $overridden_method_id->fq_class_name;

                return clone $overridden_storage->return_type;
            }
        }

        if ($candidate_type) {
            $self_class = $appearing_fq_class_storage->name;

            return clone $candidate_type;
        }

        if (!isset($class_storage->overridden_method_ids[$appearing_method_name])) {
            return null;
        }

        $candidate_type = null;

        foreach ($class_storage->overridden_method_ids[$appearing_method_name] as $overridden_method_id) {
            $overridden_storage = $this->getStorage($overridden_method_id);

            if ($overridden_storage->return_type) {
                if ($overridden_storage->return_type->isNull()) {
                    if ($candidate_type && !$candidate_type->isVoid()) {
                        return null;
                    }

                    $candidate_type = Type::getVoid();
                    continue;
                }

                $fq_overridden_class = $overridden_method_id->fq_class_name;

                $overridden_class_storage =
                    $this->classlike_storage_provider->get($fq_overridden_class);

                $overridden_return_type = clone $overridden_storage->return_type;

                $self_class = $overridden_class_storage->name;

                if ($candidate_type && $source_analyzer && !$candidate_type->isMixed()) {
                    $old_contained_by_new = UnionTypeComparator::isContainedBy(
                        $source_analyzer->getCodebase(),
                        $candidate_type,
                        $overridden_return_type
                    );

                    $new_contained_by_old = UnionTypeComparator::isContainedBy(
                        $source_analyzer->getCodebase(),
                        $overridden_return_type,
                        $candidate_type
                    );

                    if ((!$old_contained_by_new && !$new_contained_by_old)
                        || ($old_contained_by_new && $new_contained_by_old)
                    ) {
                        $attempted_intersection = Type::intersectUnionTypes(
                            $candidate_type,
                            $overridden_return_type,
                            $source_analyzer->getCodebase()
                        );

                        if ($attempted_intersection) {
                            $candidate_type = $attempted_intersection;
                            continue;
                        }

                        return null;
                    }

                    if ($old_contained_by_new) {
                        continue;
                    }
                }

                $candidate_type = $overridden_return_type;
            }
        }

        return $candidate_type;
    }

    public function getMethodReturnsByRef(MethodIdentifier $method_id): bool
    {
        $method_id = $this->getDeclaringMethodId($method_id);

        if (!$method_id) {
            return false;
        }

        $fq_class_storage = $this->classlike_storage_provider->get($method_id->fq_class_name);

        if (!$fq_class_storage->user_defined && InternalCallMapHandler::inCallMap((string) $method_id)) {
            return false;
        }

        return $this->getStorage($method_id)->returns_by_ref;
    }

    /**
     * @param  CodeLocation|null    $defined_location
     *
     */
    public function getMethodReturnTypeLocation(
        MethodIdentifier $method_id,
        CodeLocation &$defined_location = null
    ): ?CodeLocation {
        $method_id = $this->getDeclaringMethodId($method_id);

        if ($method_id === null) {
            return null;
        }

        $storage = $this->getStorage($method_id);

        if (!$storage->return_type_location) {
            $overridden_method_ids = $this->getOverriddenMethodIds($method_id);

            foreach ($overridden_method_ids as $overridden_method_id) {
                $overridden_storage = $this->getStorage($overridden_method_id);

                if ($overridden_storage->return_type_location) {
                    $defined_location = $overridden_storage->return_type_location;
                    break;
                }
            }
        }

        return $storage->return_type_location;
    }

    /**
     * @param lowercase-string $method_name_lc
     * @param lowercase-string $declaring_method_name_lc
     *
     */
    public function setDeclaringMethodId(
        string $fq_class_name,
        string $method_name_lc,
        string $declaring_fq_class_name,
        string $declaring_method_name_lc
    ): void {
        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        $class_storage->declaring_method_ids[$method_name_lc] = new MethodIdentifier(
            $declaring_fq_class_name,
            $declaring_method_name_lc
        );
    }

    /**
     * @param lowercase-string $method_name_lc
     * @param lowercase-string $appearing_method_name_lc
     *
     */
    public function setAppearingMethodId(
        string $fq_class_name,
        string $method_name_lc,
        string $appearing_fq_class_name,
        string $appearing_method_name_lc
    ): void {
        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        $class_storage->appearing_method_ids[$method_name_lc] = new MethodIdentifier(
            $appearing_fq_class_name,
            $appearing_method_name_lc
        );
    }

    public function getDeclaringMethodId(
        MethodIdentifier $method_id
    ): ?MethodIdentifier {
        $fq_class_name = $this->classlikes->getUnAliasedName($method_id->fq_class_name);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        $method_name = $method_id->method_name;

        if (isset($class_storage->declaring_method_ids[$method_name])) {
            return $class_storage->declaring_method_ids[$method_name];
        }

        if ($class_storage->abstract && isset($class_storage->overridden_method_ids[$method_name])) {
            return reset($class_storage->overridden_method_ids[$method_name]);
        }

        return null;
    }

    /**
     * Get the class this method appears in (vs is declared in, which could give a trait
     */
    public function getAppearingMethodId(
        MethodIdentifier $method_id
    ): ?MethodIdentifier {
        $fq_class_name = $this->classlikes->getUnAliasedName($method_id->fq_class_name);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        $method_name = $method_id->method_name;

        return $class_storage->appearing_method_ids[$method_name] ?? null;
    }

    /**
     * @return array<string, MethodIdentifier>
     */
    public function getOverriddenMethodIds(MethodIdentifier $method_id): array
    {
        $class_storage = $this->classlike_storage_provider->get($method_id->fq_class_name);
        $method_name = $method_id->method_name;

        return $class_storage->overridden_method_ids[$method_name] ?? [];
    }

    public function getCasedMethodId(MethodIdentifier $original_method_id): string
    {
        $method_id = $this->getDeclaringMethodId($original_method_id);

        if ($method_id === null) {
            return (string) $original_method_id;
        }

        $fq_class_name = $method_id->fq_class_name;
        $new_method_name = $method_id->method_name;

        $old_fq_class_name = $original_method_id->fq_class_name;
        $old_method_name = $original_method_id->method_name;

        $storage = $this->getStorage($method_id);

        if ($old_method_name === $new_method_name
            && strtolower($old_fq_class_name) !== $old_fq_class_name
        ) {
            return $old_fq_class_name . '::' . $storage->cased_name;
        }

        return $fq_class_name . '::' . $storage->cased_name;
    }

    public function getUserMethodStorage(MethodIdentifier $method_id): ?MethodStorage
    {
        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            if (InternalCallMapHandler::inCallMap((string) $method_id)) {
                return null;
            }

            throw new UnexpectedValueException('$storage should not be null for ' . $method_id);
        }

        $storage = $this->getStorage($declaring_method_id);

        if (!$storage->location) {
            return null;
        }

        return $storage;
    }

    public function getClassLikeStorageForMethod(MethodIdentifier $method_id): ClassLikeStorage
    {
        $fq_class_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        if ($this->existence_provider->has($fq_class_name)) {
            if ($this->existence_provider->doesMethodExist(
                $fq_class_name,
                $method_name,
                null,
                null
            )) {
                return $this->classlike_storage_provider->get($fq_class_name);
            }
        }

        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        if ($declaring_method_id === null) {
            if (InternalCallMapHandler::inCallMap((string) $method_id)) {
                $declaring_method_id = $method_id;
            } else {
                throw new UnexpectedValueException('$storage should not be null for ' . $method_id);
            }
        }

        $declaring_fq_class_name = $declaring_method_id->fq_class_name;

        return $this->classlike_storage_provider->get($declaring_fq_class_name);
    }

    public function getStorage(MethodIdentifier $method_id): MethodStorage
    {
        try {
            $class_storage = $this->classlike_storage_provider->get($method_id->fq_class_name);
        } catch (InvalidArgumentException $e) {
            throw new UnexpectedValueException($e->getMessage());
        }

        $method_name = $method_id->method_name;

        if (!isset($class_storage->methods[$method_name])) {
            throw new UnexpectedValueException(
                '$storage should not be null for ' . $method_id
            );
        }

        return $class_storage->methods[$method_name];
    }

    public function hasStorage(MethodIdentifier $method_id): bool
    {
        try {
            $class_storage = $this->classlike_storage_provider->get($method_id->fq_class_name);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        $method_name = $method_id->method_name;

        if (!isset($class_storage->methods[$method_name])) {
            return false;
        }

        return true;
    }
}
