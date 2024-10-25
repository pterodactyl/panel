<?php

namespace Psalm\Internal\PhpVisitor\Reflector;

use LogicException;
use PhpParser;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\UnionType;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Exception\ComplicatedExpressionException;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Issue\DuplicateFunction;
use Psalm\Issue\DuplicateMethod;
use Psalm\Issue\DuplicateParam;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\MissingDocblockType;
use Psalm\Issue\ParseError;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\FunctionStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;
use ReflectionFunction;
use UnexpectedValueException;

use function array_keys;
use function array_merge;
use function array_pop;
use function array_search;
use function count;
use function end;
use function explode;
use function implode;
use function in_array;
use function is_string;
use function spl_object_id;
use function strpos;
use function strtolower;

class FunctionLikeNodeScanner
{
    /**
     * @var FileScanner
     */
    private $file_scanner;

    /**
     * @var Codebase
     */
    private $codebase;

    /**
     * @var string
     */
    private $file_path;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var FileStorage
     */
    private $file_storage;

    /**
     * @var ?ClassLikeStorage
     */
    private $classlike_storage;

    /**
     * @var array<string, non-empty-array<string, Union>>
     */
    private $existing_function_template_types;

    /**
     * @var Aliases
     */
    private $aliases;

    /**
     * @var array<string, TypeAlias>
     */
    private $type_aliases;

    /**
     * @var ?FunctionLikeStorage
     */
    public $storage;

    /**
     * @param array<string, non-empty-array<string, Union>> $existing_function_template_types
     * @param array<string, TypeAlias> $type_aliases
     */
    public function __construct(
        Codebase $codebase,
        FileScanner $file_scanner,
        FileStorage $file_storage,
        Aliases $aliases,
        array $type_aliases,
        ?ClassLikeStorage $classlike_storage,
        array $existing_function_template_types
    ) {
        $this->codebase = $codebase;
        $this->file_storage = $file_storage;
        $this->file_scanner = $file_scanner;
        $this->file_path = $file_storage->file_path;
        $this->aliases = $aliases;
        $this->type_aliases = $type_aliases;
        $this->config = Config::getInstance();
        $this->classlike_storage = $classlike_storage;
        $this->existing_function_template_types = $existing_function_template_types;
    }

    /**
     * @param  bool $fake_method in the case of @method annotations we do something a little strange
     *
     * @return FunctionStorage|MethodStorage|false
     */
    public function start(PhpParser\Node\FunctionLike $stmt, bool $fake_method = false)
    {
        if ($stmt instanceof PhpParser\Node\Expr\Closure
            || $stmt instanceof PhpParser\Node\Expr\ArrowFunction
        ) {
            $this->codebase->scanner->queueClassLikeForScanning('Closure');
        }

        $functionlike_info = $this->createStorageForFunctionLike($stmt, $fake_method);

        if ($functionlike_info === false) {
            return false;
        }

        [
            $cased_function_id,
            $storage,
            $function_id,
            $fq_classlike_name,
            $method_name_lc,
            $classlike_storage,
            $is_functionlike_override,
            $method_id,
            $is_dupe
        ] = $functionlike_info;

        if ($is_dupe) {
            return $storage;
        }

        if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
            $storage->cased_name = $stmt->name->name;
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            $storage->cased_name =
                ($this->aliases->namespace ? $this->aliases->namespace . '\\' : '') . $stmt->name->name;
        }

        if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod || $stmt instanceof PhpParser\Node\Stmt\Function_) {
            $storage->location = new CodeLocation($this->file_scanner, $stmt->name, null, true);
        } else {
            $storage->location = new CodeLocation($this->file_scanner, $stmt, null, true);
        }

        $storage->stmt_location = new CodeLocation($this->file_scanner, $stmt);

        $required_param_count = 0;
        $i = 0;
        $has_optional_param = false;

        $existing_params = [];
        $storage->setParams([]);

        foreach ($stmt->getParams() as $param) {
            if ($param->var instanceof PhpParser\Node\Expr\Error) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    'Param' . ($i + 1) . ' of ' . $cased_function_id . ' has invalid syntax',
                    new CodeLocation($this->file_scanner, $param, null, true)
                );

                ++$i;

                continue;
            }

            $param_storage = $this->getTranslatedFunctionParam($param, $stmt, $fake_method, $fq_classlike_name);

            foreach ($param->attrGroups as $attr_group) {
                foreach ($attr_group->attrs as $attr) {
                    $param_storage->attributes[] = AttributeResolver::resolve(
                        $this->codebase,
                        $this->file_scanner,
                        $this->file_storage,
                        $this->aliases,
                        $attr,
                        $this->classlike_storage->name ?? null
                    );
                }
            }

            if ($param_storage->name === 'haystack'
                && in_array($this->file_path, $this->codebase->config->internal_stubs)
            ) {
                $param_storage->expect_variable = true;
            }

            if (isset($existing_params['$' . $param_storage->name])) {
                $storage->docblock_issues[] = new DuplicateParam(
                    'Duplicate param $' . $param_storage->name . ' in docblock for ' . $cased_function_id,
                    new CodeLocation($this->file_scanner, $param, null, true)
                );

                ++$i;

                continue;
            }

            $existing_params['$' . $param_storage->name] = $i;
            $storage->addParam($param_storage, (bool)$param->type);

            if (!$param_storage->is_optional && !$param_storage->is_variadic) {
                $required_param_count = $i + 1;

                if (!$param->variadic
                    && $has_optional_param
                ) {
                    foreach ($storage->params as $param) {
                        $param->is_optional = false;
                    }
                }
            } else {
                $has_optional_param = true;
            }

            ++$i;
        }

        $storage->required_param_count = $required_param_count;

        if ($stmt instanceof PhpParser\Node\Stmt\Function_
            || $stmt instanceof PhpParser\Node\Stmt\ClassMethod
        ) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod
                && $storage instanceof MethodStorage
                && $classlike_storage
                && !$classlike_storage->mutation_free
                && $stmt->stmts
                && count($stmt->stmts) === 1
                && !count($stmt->params)
                && $stmt->stmts[0] instanceof PhpParser\Node\Stmt\Return_
                && $stmt->stmts[0]->expr instanceof PhpParser\Node\Expr\PropertyFetch
                && $stmt->stmts[0]->expr->var instanceof PhpParser\Node\Expr\Variable
                && $stmt->stmts[0]->expr->var->name === 'this'
                && $stmt->stmts[0]->expr->name instanceof PhpParser\Node\Identifier
            ) {
                $property_name = $stmt->stmts[0]->expr->name->name;

                if (isset($classlike_storage->properties[$property_name])
                    && $classlike_storage->properties[$property_name]->type
                ) {
                    $storage->mutation_free = true;
                    $storage->external_mutation_free = true;
                    $storage->mutation_free_inferred = !$stmt->isFinal() && !$classlike_storage->final;

                    $classlike_storage->properties[$property_name]->getter_method = strtolower($stmt->name->name);
                }
            } elseif (strpos($stmt->name->name, 'assert') === 0
                && $stmt->stmts
            ) {
                $var_assertions = [];

                foreach ($stmt->stmts as $function_stmt) {
                    if ($function_stmt instanceof PhpParser\Node\Stmt\If_) {
                        $final_actions = ScopeAnalyzer::getControlActions(
                            $function_stmt->stmts,
                            null,
                            $this->config->exit_functions,
                            [],
                            false
                        );

                        if ($final_actions !== [ScopeAnalyzer::ACTION_END]) {
                            $var_assertions = [];
                            break;
                        }

                        $cond_id = spl_object_id($function_stmt->cond);

                        $if_clauses = FormulaGenerator::getFormula(
                            $cond_id,
                            $cond_id,
                            $function_stmt->cond,
                            $this->classlike_storage->name ?? null,
                            $this->file_scanner,
                            null
                        );

                        try {
                            $negated_formula = Algebra::negateFormula($if_clauses);
                        } catch (ComplicatedExpressionException $e) {
                            $var_assertions = [];
                            break;
                        }

                        $rules = Algebra::getTruthsFromFormula($negated_formula);

                        if (!$rules) {
                            $var_assertions = [];
                            break;
                        }

                        foreach ($rules as $var_id => $rule) {
                            foreach ($rule as $rule_part) {
                                if (count($rule_part) > 1) {
                                    continue 2;
                                }
                            }

                            if (isset($existing_params[$var_id])) {
                                $param_offset = $existing_params[$var_id];

                                $var_assertions[] = new Assertion(
                                    $param_offset,
                                    $rule
                                );
                            } elseif (strpos($var_id, '$this->') === 0) {
                                $var_assertions[] = new Assertion(
                                    $var_id,
                                    $rule
                                );
                            }
                        }
                    } else {
                        $var_assertions = [];
                        break;
                    }
                }

                $storage->assertions = $var_assertions;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod
                && $stmt->stmts
                && $storage instanceof MethodStorage
            ) {
                $last_stmt = end($stmt->stmts);

                if ($last_stmt instanceof PhpParser\Node\Stmt\Return_
                    && $last_stmt->expr instanceof PhpParser\Node\Expr\Variable
                    && $last_stmt->expr->name === 'this'
                ) {
                    $storage->probably_fluent = true;
                }
            }
        }

        if (!$this->file_scanner->will_analyze
            && ($stmt instanceof PhpParser\Node\Stmt\Function_
                || $stmt instanceof PhpParser\Node\Stmt\ClassMethod
                || $stmt instanceof PhpParser\Node\Expr\Closure)
            && $stmt->stmts
        ) {
            // pick up func_get_args that would otherwise be missed
            foreach ($stmt->stmts as $function_stmt) {
                if ($function_stmt instanceof PhpParser\Node\Stmt\Expression
                    && $function_stmt->expr instanceof PhpParser\Node\Expr\Assign
                    && $function_stmt->expr->expr instanceof PhpParser\Node\Expr\FuncCall
                    && $function_stmt->expr->expr->name instanceof PhpParser\Node\Name
                ) {
                    $inner_function_id = implode('\\', $function_stmt->expr->expr->name->parts);

                    if ($inner_function_id === 'func_get_arg'
                        || $inner_function_id === 'func_get_args'
                        || $inner_function_id === 'func_num_args'
                    ) {
                        $storage->variadic = true;
                    }
                } elseif ($function_stmt instanceof PhpParser\Node\Stmt\If_
                    && $function_stmt->cond instanceof PhpParser\Node\Expr\BinaryOp
                    && $function_stmt->cond->left instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    && $function_stmt->cond->left->left instanceof PhpParser\Node\Expr\FuncCall
                    && $function_stmt->cond->left->left->name instanceof PhpParser\Node\Name
                ) {
                    $inner_function_id = implode('\\', $function_stmt->cond->left->left->name->parts);

                    if ($inner_function_id === 'func_get_arg'
                        || $inner_function_id === 'func_get_args'
                        || $inner_function_id === 'func_num_args'
                    ) {
                        $storage->variadic = true;
                    }
                }
            }
        }

        $parser_return_type = $stmt->getReturnType();

        if ($parser_return_type) {
            $original_type = $parser_return_type;
            if ($original_type instanceof PhpParser\Node\IntersectionType) {
                throw new UnexpectedValueException('Intersection types not yet supported');
            }
            /** @var Identifier|Name|NullableType|UnionType $original_type */

            $storage->return_type = TypeHintResolver::resolve(
                $original_type,
                $this->codebase->scanner,
                $this->file_storage,
                $this->classlike_storage,
                $this->aliases,
                $this->codebase->php_major_version,
                $this->codebase->php_minor_version
            );

            $storage->return_type_location = new CodeLocation(
                $this->file_scanner,
                $original_type
            );

            if ($stmt->returnsByRef()) {
                $storage->return_type->by_ref = true;
            }

            $storage->signature_return_type = $storage->return_type;
            $storage->signature_return_type_location = $storage->return_type_location;
        }

        if ($stmt->returnsByRef()) {
            $storage->returns_by_ref = true;
        }

        $doc_comment = $stmt->getDocComment();


        if ($classlike_storage && !$classlike_storage->is_trait) {
            $storage->internal = array_merge($classlike_storage->internal, $storage->internal);
        }

        if ($doc_comment) {
            try {
                $code_location = new CodeLocation($this->file_scanner, $stmt, null, true);
                $docblock_info = FunctionLikeDocblockParser::parse($doc_comment, $code_location, $cased_function_id);
            } catch (IncorrectDocblockException $e) {
                $storage->docblock_issues[] = new MissingDocblockType(
                    $e->getMessage() . ' in docblock for ' . $cased_function_id,
                    new CodeLocation($this->file_scanner, $stmt, null, true)
                );

                $docblock_info = null;
            } catch (DocblockParseException $e) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    $e->getMessage() . ' in docblock for ' . $cased_function_id,
                    new CodeLocation($this->file_scanner, $stmt, null, true)
                );

                $docblock_info = null;
            }

            if ($docblock_info) {
                if ($docblock_info->since_php_major_version && !$this->aliases->namespace) {
                    if ($docblock_info->since_php_major_version > $this->codebase->php_major_version) {
                        return false;
                    }

                    if ($docblock_info->since_php_major_version === $this->codebase->php_major_version
                        && $docblock_info->since_php_minor_version > $this->codebase->php_minor_version
                    ) {
                        return false;
                    }
                }

                if ($stmt instanceof PhpParser\Node\Expr\Closure
                    || $stmt instanceof PhpParser\Node\Expr\ArrowFunction
                ) {
                    if ($docblock_info->templates !== []) {
                        $docblock_info->templates = [];
                        $storage->docblock_issues[] = new InvalidDocblock(
                            'Templated closures are not supported',
                            new CodeLocation($this->file_scanner, $stmt, null, true)
                        );
                    }
                }

                FunctionLikeDocblockScanner::addDocblockInfo(
                    $this->codebase,
                    $this->file_scanner,
                    $this->file_storage,
                    $this->aliases,
                    $this->type_aliases,
                    $this->classlike_storage,
                    $this->existing_function_template_types,
                    $storage,
                    $stmt,
                    $docblock_info,
                    $is_functionlike_override,
                    $fake_method,
                    $cased_function_id
                );
            }
        }

        // register the functionlike once the @since check has been completed
        if ($stmt instanceof PhpParser\Node\Stmt\Function_
            && $function_id
            && $storage instanceof FunctionStorage
        ) {
            if ($this->codebase->register_stub_files
                || ($this->codebase->register_autoload_files
                    && !$this->codebase->functions->hasStubbedFunction($function_id))
            ) {
                $this->codebase->functions->addGlobalFunction($function_id, $storage);
            }

            $this->file_storage->functions[$function_id] = $storage;
            $this->file_storage->declaring_function_ids[$function_id] = strtolower($this->file_path);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassMethod
            && $classlike_storage
            && $storage instanceof MethodStorage
            && $method_name_lc
            && !$fake_method
            && $method_id
        ) {
            $classlike_storage->methods[$method_name_lc] = $storage;

            $classlike_storage->declaring_method_ids[$method_name_lc]
                = $classlike_storage->appearing_method_ids[$method_name_lc]
                = $method_id;

            if (!$stmt->isPrivate() || $method_name_lc === '__construct' || $classlike_storage->is_trait) {
                $classlike_storage->inheritable_method_ids[$method_name_lc] = $method_id;
            }

            if (!isset($classlike_storage->overridden_method_ids[$method_name_lc])) {
                $classlike_storage->overridden_method_ids[$method_name_lc] = [];
            }

            if ($storage->final && $method_name_lc === '__construct') {
                // a bit of a hack, but makes sure that `new static` works for these classes
                $classlike_storage->preserve_constructor_signature = true;
            }
        } elseif (($stmt instanceof PhpParser\Node\Expr\Closure
                || $stmt instanceof PhpParser\Node\Expr\ArrowFunction)
            && $function_id
            && $storage instanceof FunctionStorage
        ) {
            $this->file_storage->functions[$function_id] = $storage;
        }

        if ($classlike_storage && $method_name_lc === '__construct') {
            foreach ($stmt->getParams() as $param) {
                if (!$param->flags || !$param->var instanceof PhpParser\Node\Expr\Variable) {
                    continue;
                }

                $param_storage = null;

                foreach ($storage->params as $param_storage) {
                    if ($param_storage->name === $param->var->name) {
                        break;
                    }
                }

                if (!$param_storage) {
                    continue;
                }

                if (isset($classlike_storage->properties[$param_storage->name]) && $param_storage->location) {
                    IssueBuffer::maybeAdd(
                        new ParseError(
                            'Promoted property ' . $param_storage->name . ' clashes with an existing property',
                            $param_storage->location
                        )
                    );

                    $storage->has_visitor_issues = true;
                    $this->file_storage->has_visitor_issues = true;
                    continue;
                }

                $doc_comment = $param->getDocComment();
                $var_comment_type = null;
                $var_comment_readonly = false;
                $var_comment_allow_private_mutation = false;
                if ($doc_comment) {
                    $var_comments = CommentAnalyzer::getTypeFromComment(
                        $doc_comment,
                        $this->file_scanner,
                        $this->aliases,
                        $this->existing_function_template_types ?: [],
                        $this->type_aliases
                    );

                    $var_comment = array_pop($var_comments);

                    if ($var_comment !== null) {
                        $var_comment_type = $var_comment->type;
                        $var_comment_readonly = $var_comment->readonly;
                        $var_comment_allow_private_mutation = $var_comment->allow_private_mutation;
                    }
                }

                //both way to document type were used
                if ($param_storage->type && $param_storage->type->from_docblock && $var_comment_type) {
                    if (IssueBuffer::accepts(
                        new InvalidDocblock(
                            'Param ' . $param_storage->name . ' of ' . $cased_function_id .
                            ' should be documented as a param or a property, not both',
                            new CodeLocation($this->file_scanner, $param, null, true)
                        )
                    )) {
                        return false;
                    }
                }

                //no docblock type was provided for param but we have one for property
                if ($var_comment_type) {
                    $param_storage->type = $var_comment_type;
                }

                $property_storage = $classlike_storage->properties[$param_storage->name] = new PropertyStorage();
                $property_storage->is_static = false;
                $property_storage->type = $param_storage->type;
                $property_storage->signature_type = $param_storage->signature_type;
                $property_storage->signature_type_location = $param_storage->signature_type_location;
                $property_storage->type_location = $param_storage->type_location;
                $property_storage->location = $param_storage->location;
                $property_storage->stmt_location = new CodeLocation($this->file_scanner, $param);
                $property_storage->has_default = (bool)$param->default;
                $param_type_readonly = (bool)($param->flags & PhpParser\Node\Stmt\Class_::MODIFIER_READONLY);
                $property_storage->readonly = $param_type_readonly ?: $var_comment_readonly;
                $property_storage->allow_private_mutation = $var_comment_allow_private_mutation;
                $param_storage->promoted_property = true;
                $property_storage->is_promoted = true;

                $property_id = $fq_classlike_name . '::$' . $param_storage->name;

                switch ($param->flags & Class_::VISIBILITY_MODIFIER_MASK) {
                    case Class_::MODIFIER_PUBLIC:
                        $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
                        $classlike_storage->inheritable_property_ids[$param_storage->name] = $property_id;
                        break;

                    case Class_::MODIFIER_PROTECTED:
                        $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
                        $classlike_storage->inheritable_property_ids[$param_storage->name] = $property_id;
                        break;

                    case Class_::MODIFIER_PRIVATE:
                        $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PRIVATE;
                        break;
                }

                $fq_classlike_name = $classlike_storage->name;

                $property_id = $fq_classlike_name . '::$' . $param_storage->name;

                $classlike_storage->declaring_property_ids[$param_storage->name] = $fq_classlike_name;
                $classlike_storage->appearing_property_ids[$param_storage->name] = $property_id;
                $classlike_storage->initialized_properties[$param_storage->name] = true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod
                && $storage instanceof MethodStorage
                && $storage->params
                && $this->config->infer_property_types_from_constructor
            ) {
                $this->inferPropertyTypeFromConstructor($stmt, $storage, $classlike_storage);
            }
        }

        foreach ($stmt->getAttrGroups() as $attr_group) {
            foreach ($attr_group->attrs as $attr) {
                $attribute = AttributeResolver::resolve(
                    $this->codebase,
                    $this->file_scanner,
                    $this->file_storage,
                    $this->aliases,
                    $attr,
                    $this->classlike_storage->name ?? null
                );

                if ($attribute->fq_class_name === 'Psalm\\Pure'
                    || $attribute->fq_class_name === 'JetBrains\\PhpStorm\\Pure'
                ) {
                    $storage->specialize_call = true;
                    $storage->mutation_free = true;
                    if ($storage instanceof MethodStorage) {
                        $storage->external_mutation_free = true;
                    }
                }

                if ($attribute->fq_class_name === 'Psalm\\Deprecated'
                    || $attribute->fq_class_name === 'JetBrains\\PhpStorm\\Deprecated'
                ) {
                    $storage->deprecated = true;
                }

                if ($attribute->fq_class_name === 'Psalm\\Internal' && !$storage->internal && $fq_classlike_name) {
                    $storage->internal = [NamespaceAnalyzer::getNameSpaceRoot($fq_classlike_name)];
                }

                if ($attribute->fq_class_name === 'Psalm\\ExternalMutationFree'
                    && $storage instanceof MethodStorage
                ) {
                    $storage->external_mutation_free = true;
                }

                if ($attribute->fq_class_name === 'JetBrains\\PhpStorm\\NoReturn') {
                    $storage->return_type = new Union([new TNever()]);
                }

                $storage->attributes[] = $attribute;
            }
        }

        return $storage;
    }

    private function inferPropertyTypeFromConstructor(
        PhpParser\Node\Stmt\ClassMethod $stmt,
        MethodStorage $storage,
        ClassLikeStorage $classlike_storage
    ): void {
        if (!$stmt->stmts) {
            return;
        }

        $assigned_properties = [];

        foreach ($stmt->stmts as $function_stmt) {
            if ($function_stmt instanceof PhpParser\Node\Stmt\Expression
                && $function_stmt->expr instanceof PhpParser\Node\Expr\Assign
                && $function_stmt->expr->var instanceof PhpParser\Node\Expr\PropertyFetch
                && $function_stmt->expr->var->var instanceof PhpParser\Node\Expr\Variable
                && $function_stmt->expr->var->var->name === 'this'
                && $function_stmt->expr->var->name instanceof PhpParser\Node\Identifier
                && ($property_name = $function_stmt->expr->var->name->name)
                && isset($classlike_storage->properties[$property_name])
                && $function_stmt->expr->expr instanceof PhpParser\Node\Expr\Variable
                && is_string($function_stmt->expr->expr->name)
                && ($param_name = $function_stmt->expr->expr->name)
                && isset($storage->param_lookup[$param_name])
            ) {
                if ($classlike_storage->properties[$property_name]->type
                    || !$storage->param_lookup[$param_name]
                ) {
                    continue;
                }

                $param_index = array_search($param_name, array_keys($storage->param_lookup), true);

                if ($param_index === false || !isset($storage->params[$param_index]->type)) {
                    continue;
                }

                $param_type = $storage->params[$param_index]->type;

                $assigned_properties[$property_name] =
                    $storage->params[$param_index]->is_variadic
                        ? new Union([
                            new TArray([
                                Type::getInt(),
                                $param_type,
                            ]),
                        ])
                        : $param_type;
            } else {
                $assigned_properties = [];
                break;
            }
        }

        if (!$assigned_properties) {
            return;
        }

        $storage->external_mutation_free = true;

        foreach ($assigned_properties as $property_name => $property_type) {
            $classlike_storage->properties[$property_name]->type = clone $property_type;
        }
    }

    private function getTranslatedFunctionParam(
        PhpParser\Node\Param $param,
        PhpParser\Node\FunctionLike $stmt,
        bool $fake_method,
        ?string $fq_classlike_name
    ): FunctionLikeParameter {
        $param_type = null;

        $is_nullable = $param->default instanceof PhpParser\Node\Expr\ConstFetch &&
            strtolower($param->default->name->parts[0]) === 'null';

        $param_typehint = $param->type;

        if ($param_typehint) {
            if ($param_typehint instanceof PhpParser\Node\IntersectionType) {
                throw new UnexpectedValueException('Intersection types not yet supported');
            }
            /** @var Identifier|Name|NullableType|UnionType $param_typehint */

            $param_type = TypeHintResolver::resolve(
                $param_typehint,
                $this->codebase->scanner,
                $this->file_storage,
                $this->classlike_storage,
                $this->aliases,
                $this->codebase->php_major_version,
                $this->codebase->php_minor_version
            );

            if ($is_nullable) {
                $param_type->addType(new TNull);
            } else {
                $is_nullable = $param_type->isNullable();
            }
        }

        $is_optional = $param->default !== null;

        if ($param->var instanceof PhpParser\Node\Expr\Error || !is_string($param->var->name)) {
            throw new UnexpectedValueException('Not expecting param name to be non-string');
        }

        $default_type = null;

        if ($param->default) {
            $default_type = SimpleTypeInferer::infer(
                $this->codebase,
                new NodeDataProvider(),
                $param->default,
                $this->aliases,
                null,
                null,
                $fq_classlike_name
            );

            if (!$default_type) {
                $default_type = ExpressionResolver::getUnresolvedClassConstExpr(
                    $param->default,
                    $this->aliases,
                    $fq_classlike_name
                );
            }
        }

        return new FunctionLikeParameter(
            $param->var->name,
            $param->byRef,
            $param_type,
            new CodeLocation(
                $this->file_scanner,
                $fake_method ? $stmt : $param->var,
                null,
                false,
                !$fake_method
                    ? CodeLocation::FUNCTION_PARAM_VAR
                    : CodeLocation::FUNCTION_PHPDOC_METHOD
            ),
            $param_typehint
                ? new CodeLocation(
                    $this->file_scanner,
                    $fake_method ? $stmt : $param,
                    null,
                    false,
                    CodeLocation::FUNCTION_PARAM_TYPE
                )
                : null,
            $is_optional,
            $is_nullable,
            $param->variadic,
            $default_type
        );
    }

    /**
     * @return array{
     *     string,
     *     FunctionStorage|MethodStorage,
     *     null|string,
     *     null|string,
     *     null|lowercase-string,
     *     ClassLikeStorage|null,
     *     bool,
     *     MethodIdentifier|null,
     *     bool
     * }|false
     */
    private function createStorageForFunctionLike(
        PhpParser\Node\FunctionLike $stmt,
        bool $fake_method
    ) {
        $classlike_storage = null;
        $fq_classlike_name = null;
        $is_functionlike_override = false;

        $function_id = null;
        $method_name_lc = null;
        $method_id = null;

        if ($fake_method && $stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
            $cased_function_id = '@method ' . $stmt->name->name;

            $storage = $this->storage = new MethodStorage();
            $storage->defining_fqcln = '';
            $storage->is_static = $stmt->isStatic();
            $storage->final = $this->classlike_storage && $this->classlike_storage->final;
            $storage->final_from_docblock = $this->classlike_storage && $this->classlike_storage->final_from_docblock;
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            $cased_function_id =
                ($this->aliases->namespace ? $this->aliases->namespace . '\\' : '') . $stmt->name->name;
            $function_id = strtolower($cased_function_id);

            $storage = $this->storage = new FunctionStorage();

            if ($this->codebase->register_stub_files || $this->codebase->register_autoload_files) {
                if (isset($this->file_storage->functions[$function_id])
                    && ($this->codebase->register_stub_files
                        || !$this->codebase->functions->hasStubbedFunction($function_id))
                ) {
                    $this->codebase->functions->addGlobalFunction(
                        $function_id,
                        $this->file_storage->functions[$function_id]
                    );

                    $storage = $this->storage = $this->file_storage->functions[$function_id];

                    return [$function_id, $storage, null, null, null, null, false, null, true];
                }
            } else {
                if (isset($this->file_storage->functions[$function_id])) {
                    $duplicate_function_storage = $this->file_storage->functions[$function_id];

                    if ($duplicate_function_storage->location
                        && $duplicate_function_storage->location->getLineNumber() === $stmt->getLine()
                    ) {
                        $storage = $this->storage = $this->file_storage->functions[$function_id];

                        return [$function_id, $storage, null, null, null, null, false, null, true];
                    }

                    IssueBuffer::maybeAdd(
                        new DuplicateFunction(
                            'Method ' . $function_id . ' has already been defined'
                            . ($duplicate_function_storage->location
                                ? ' in ' . $duplicate_function_storage->location->file_path
                                : ''),
                            new CodeLocation($this->file_scanner, $stmt, null, true)
                        )
                    );

                    $this->file_storage->has_visitor_issues = true;

                    $duplicate_function_storage->has_visitor_issues = true;

                    $storage = $this->storage = $this->file_storage->functions[$function_id];

                    return [$function_id, $storage, null, null, null, null, false, null, true];
                }

                if (isset($this->config->getPredefinedFunctions()[$function_id])) {
                    /** @psalm-suppress ArgumentTypeCoercion */
                    $reflection_function = new ReflectionFunction($function_id);

                    if ($reflection_function->getFileName() !== $this->file_path) {
                        IssueBuffer::maybeAdd(
                            new DuplicateFunction(
                                'Method ' . $function_id . ' has already been defined as a core function',
                                new CodeLocation($this->file_scanner, $stmt, null, true)
                            )
                        );
                    }
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
            if (!$this->classlike_storage) {
                throw new LogicException('$this->classlike_storage should not be null');
            }

            $fq_classlike_name = $this->classlike_storage->name;

            $method_name_lc = strtolower($stmt->name->name);

            $function_id = $fq_classlike_name . '::' . $method_name_lc;
            $cased_function_id = $fq_classlike_name . '::' . $stmt->name->name;

            $classlike_storage = $this->classlike_storage;

            $storage = null;

            if (isset($classlike_storage->methods[$method_name_lc])) {
                if (!$this->codebase->register_stub_files) {
                    $duplicate_method_storage = $classlike_storage->methods[$method_name_lc];

                    IssueBuffer::maybeAdd(
                        new DuplicateMethod(
                            'Method ' . $function_id . ' has already been defined'
                            . ($duplicate_method_storage->location
                                ? ' in ' . $duplicate_method_storage->location->file_path
                                : ''),
                            new CodeLocation($this->file_scanner, $stmt, null, true)
                        )
                    );

                    $this->file_storage->has_visitor_issues = true;

                    $duplicate_method_storage->has_visitor_issues = true;

                    return false;
                }

                // skip methods based on @since docblock tag
                $doc_comment = $stmt->getDocComment();

                if ($doc_comment) {
                    $docblock_info = null;
                    try {
                        $code_location = new CodeLocation($this->file_scanner, $stmt, null, true);
                        $docblock_info = FunctionLikeDocblockParser::parse(
                            $doc_comment,
                            $code_location,
                            $cased_function_id
                        );
                    } catch (IncorrectDocblockException|DocblockParseException $e) {
                    }
                    if ($docblock_info) {
                        if ($docblock_info->since_php_major_version && !$this->aliases->namespace) {
                            if ($docblock_info->since_php_major_version > $this->codebase->php_major_version) {
                                return false;
                            }
                            if ($docblock_info->since_php_major_version === $this->codebase->php_major_version
                                && $docblock_info->since_php_minor_version > $this->codebase->php_minor_version
                            ) {
                                return false;
                            }
                        }
                    }
                }

                $is_functionlike_override = true;
                $storage = $this->storage = $classlike_storage->methods[$method_name_lc];
            }

            if (!$storage) {
                $storage = $this->storage = new MethodStorage();
            }

            $storage->stubbed = $this->codebase->register_stub_files;
            $storage->defining_fqcln = $fq_classlike_name;

            $class_name_parts = explode('\\', $fq_classlike_name);
            $class_name = array_pop($class_name_parts);

            if ($method_name_lc === strtolower($class_name)
                && !isset($classlike_storage->methods['__construct'])
                && strpos($fq_classlike_name, '\\') === false
                && $this->codebase->php_major_version < 8
            ) {
                $this->codebase->methods->setDeclaringMethodId(
                    $fq_classlike_name,
                    '__construct',
                    $fq_classlike_name,
                    $method_name_lc
                );

                $this->codebase->methods->setAppearingMethodId(
                    $fq_classlike_name,
                    '__construct',
                    $fq_classlike_name,
                    $method_name_lc
                );
            }

            $method_id = new MethodIdentifier(
                $fq_classlike_name,
                $method_name_lc
            );

            $storage->is_static = $stmt->isStatic();
            $storage->abstract = $stmt->isAbstract();

            $storage->final = $classlike_storage->final || $stmt->isFinal();
            $storage->final_from_docblock = $classlike_storage->final_from_docblock;

            if ($stmt->isPrivate()) {
                $storage->visibility = ClassLikeAnalyzer::VISIBILITY_PRIVATE;
            } elseif ($stmt->isProtected()) {
                $storage->visibility = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
            } else {
                $storage->visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Closure
            || $stmt instanceof PhpParser\Node\Expr\ArrowFunction
        ) {
            $function_id = $cased_function_id = strtolower($this->file_path)
                . ':' . $stmt->getLine()
                . ':' . (int)$stmt->getAttribute('startFilePos') . ':-:closure';

            $storage = $this->storage = $this->file_storage->functions[$function_id] = new FunctionStorage();

            if ($stmt instanceof PhpParser\Node\Expr\Closure) {
                foreach ($stmt->uses as $closure_use) {
                    if ($closure_use->byRef && is_string($closure_use->var->name)) {
                        $storage->byref_uses[$closure_use->var->name] = true;
                    }
                }
            }
        } else {
            throw new UnexpectedValueException('Unrecognized functionlike');
        }

        return [
            $cased_function_id,
            $storage,
            $function_id,
            $fq_classlike_name,
            $method_name_lc,
            $classlike_storage,
            $is_functionlike_override,
            $method_id,
            false
        ];
    }
}
