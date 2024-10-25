<?php

namespace Psalm\Internal\PhpVisitor\Reflector;

use PhpParser;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Exception\FileIncludeException;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\IncludeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;

use function assert;
use function defined;
use function dirname;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function strpos;
use function strtolower;
use function substr;

use const DIRECTORY_SEPARATOR;

class ExpressionScanner
{
    public static function scan(
        Codebase $codebase,
        FileScanner $file_scanner,
        FileStorage $file_storage,
        Aliases $aliases,
        PhpParser\Node\Expr $node,
        ?FunctionLikeStorage $functionlike_storage,
        ?int $skip_if_descendants
    ): void {
        if ($node instanceof PhpParser\Node\Expr\Include_ && !$skip_if_descendants) {
            self::visitInclude(
                $codebase,
                $file_storage,
                $node,
                $file_scanner->will_analyze
            );
        } elseif ($node instanceof PhpParser\Node\Expr\Yield_ || $node instanceof PhpParser\Node\Expr\YieldFrom) {
            if ($functionlike_storage) {
                $functionlike_storage->has_yield = true;
            }
        } elseif ($node instanceof PhpParser\Node\Expr\Cast\Object_) {
            $codebase->scanner->queueClassLikeForScanning('stdClass', false, false);
            $file_storage->referenced_classlikes['stdclass'] = 'stdClass';
        } elseif (($node instanceof PhpParser\Node\Expr\New_
                || $node instanceof PhpParser\Node\Expr\Instanceof_
                || $node instanceof PhpParser\Node\Expr\StaticPropertyFetch
                || $node instanceof PhpParser\Node\Expr\ClassConstFetch
                || $node instanceof PhpParser\Node\Expr\StaticCall)
            && $node->class instanceof PhpParser\Node\Name
        ) {
            $fq_classlike_name = ClassLikeAnalyzer::getFQCLNFromNameObject($node->class, $aliases);

            if (!in_array(strtolower($fq_classlike_name), ['self', 'static', 'parent'], true)) {
                $codebase->scanner->queueClassLikeForScanning(
                    $fq_classlike_name,
                    false,
                    !($node instanceof PhpParser\Node\Expr\ClassConstFetch)
                        || !($node->name instanceof PhpParser\Node\Identifier)
                        || strtolower($node->name->name) !== 'class'
                );
                $file_storage->referenced_classlikes[strtolower($fq_classlike_name)] = $fq_classlike_name;
            }
        } elseif ($node instanceof PhpParser\Node\Expr\FuncCall && $node->name instanceof PhpParser\Node\Name) {
            $function_id = implode('\\', $node->name->parts);

            if (InternalCallMapHandler::inCallMap($function_id)) {
                self::registerClassMapFunctionCall(
                    $codebase,
                    $file_storage,
                    $file_scanner,
                    $aliases,
                    $function_id,
                    $node,
                    $functionlike_storage,
                    $skip_if_descendants
                );
            }
        }
    }

    private static function registerClassMapFunctionCall(
        Codebase $codebase,
        FileStorage $file_storage,
        FileScanner $file_scanner,
        Aliases $aliases,
        string $function_id,
        PhpParser\Node\Expr\FuncCall $node,
        ?FunctionLikeStorage $functionlike_storage,
        ?int $skip_if_descendants
    ): void {
        $callables = InternalCallMapHandler::getCallablesFromCallMap($function_id);

        if ($callables) {
            foreach ($callables as $callable) {
                assert($callable->params !== null);

                foreach ($callable->params as $function_param) {
                    if ($function_param->type) {
                        $function_param->type->queueClassLikesForScanning(
                            $codebase,
                            $file_storage
                        );
                    }
                }

                if ($callable->return_type && !$callable->return_type->hasMixed()) {
                    $callable->return_type->queueClassLikesForScanning($codebase, $file_storage);
                }
            }
        }

        if ($node->isFirstClassCallable()) {
            return;
        }

        if ($function_id === 'define') {
            $first_arg_value = isset($node->getArgs()[0]) ? $node->getArgs()[0]->value : null;
            $second_arg_value = isset($node->getArgs()[1]) ? $node->getArgs()[1]->value : null;
            if ($first_arg_value && $second_arg_value) {
                $type_provider = new NodeDataProvider();
                $const_name = ConstFetchAnalyzer::getConstName(
                    $first_arg_value,
                    $type_provider,
                    $codebase,
                    $aliases
                );

                if ($const_name !== null) {
                    $const_type = SimpleTypeInferer::infer(
                        $codebase,
                        $type_provider,
                        $second_arg_value,
                        $aliases
                    ) ?? Type::getMixed();

                    $config = Config::getInstance();

                    if ($functionlike_storage && !$config->hoist_constants) {
                        $functionlike_storage->defined_constants[$const_name] = $const_type;
                    } else {
                        $file_storage->constants[$const_name] = $const_type;
                        $file_storage->declaring_constants[$const_name] = $file_storage->file_path;
                    }

                    if (($codebase->register_stub_files || $codebase->register_autoload_files)
                        && (!defined($const_name) || $const_type->isMixed())
                    ) {
                        $codebase->addGlobalConstantType($const_name, $const_type);
                    }
                }
            }
        }

        $mapping_function_ids = [];

        if (($function_id === 'array_map' && isset($node->getArgs()[0]))
            || ($function_id === 'array_filter' && isset($node->getArgs()[1]))
        ) {
            $node_arg_value = $function_id === 'array_map' ? $node->getArgs()[0]->value : $node->getArgs()[1]->value;

            if ($node_arg_value instanceof PhpParser\Node\Scalar\String_
                || $node_arg_value instanceof PhpParser\Node\Expr\Array_
                || $node_arg_value instanceof PhpParser\Node\Expr\BinaryOp\Concat
            ) {
                $mapping_function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
                    $file_scanner,
                    $node_arg_value
                );
            }

            foreach ($mapping_function_ids as $potential_method_id) {
                if (strpos($potential_method_id, '::') === false) {
                    continue;
                }

                [$callable_fqcln] = explode('::', $potential_method_id);

                if (!in_array(strtolower($callable_fqcln), ['self', 'parent', 'static'], true)) {
                    $codebase->scanner->queueClassLikeForScanning(
                        $callable_fqcln
                    );
                }
            }
        }

        if ($function_id === 'func_get_arg'
            || $function_id === 'func_get_args'
            || $function_id === 'func_num_args'
        ) {
            if ($functionlike_storage) {
                $functionlike_storage->variadic = true;
            }
        }

        if ($function_id === 'is_a' || $function_id === 'is_subclass_of') {
            $second_arg = $node->getArgs()[1]->value ?? null;

            if ($second_arg instanceof PhpParser\Node\Scalar\String_) {
                $codebase->scanner->queueClassLikeForScanning(
                    $second_arg->value
                );
            }
        }

        if ($function_id === 'class_alias' && !$skip_if_descendants) {
            $first_arg = $node->getArgs()[0]->value ?? null;
            $second_arg = $node->getArgs()[1]->value ?? null;

            if ($first_arg instanceof PhpParser\Node\Scalar\String_) {
                $first_arg_value = $first_arg->value;
            } elseif ($first_arg instanceof PhpParser\Node\Expr\ClassConstFetch
                && $first_arg->class instanceof PhpParser\Node\Name
                && $first_arg->name instanceof PhpParser\Node\Identifier
                && strtolower($first_arg->name->name) === 'class'
            ) {
                /** @var string */
                $first_arg_value = $first_arg->class->getAttribute('resolvedName');
            } else {
                $first_arg_value = null;
            }

            if ($second_arg instanceof PhpParser\Node\Scalar\String_) {
                $second_arg_value = $second_arg->value;
            } elseif ($second_arg instanceof PhpParser\Node\Expr\ClassConstFetch
                && $second_arg->class instanceof PhpParser\Node\Name
                && $second_arg->name instanceof PhpParser\Node\Identifier
                && strtolower($second_arg->name->name) === 'class'
            ) {
                /** @var string */
                $second_arg_value = $second_arg->class->getAttribute('resolvedName');
            } else {
                $second_arg_value = null;
            }

            if ($first_arg_value !== null && $second_arg_value !== null) {
                if ($first_arg_value[0] === '\\') {
                    $first_arg_value = substr($first_arg_value, 1);
                }

                if ($second_arg_value[0] === '\\') {
                    $second_arg_value = substr($second_arg_value, 1);
                }

                $codebase->classlikes->addClassAlias(
                    $first_arg_value,
                    $second_arg_value
                );

                $file_storage->classlike_aliases[$second_arg_value] = $first_arg_value;
            }
        }
    }

    public static function visitInclude(
        Codebase $codebase,
        FileStorage $file_storage,
        PhpParser\Node\Expr\Include_ $stmt,
        bool $scan_deep
    ): void {
        $config = Config::getInstance();

        if (!$config->allow_includes) {
            throw new FileIncludeException(
                'File includes are not allowed per your Psalm config - check the allowFileIncludes flag.'
            );
        }

        if ($stmt->expr instanceof PhpParser\Node\Scalar\String_) {
            $path_to_file = $stmt->expr->value;

            // attempts to resolve using get_include_path dirs
            $include_path = IncludeAnalyzer::resolveIncludePath($path_to_file, dirname($file_storage->file_path));
            $path_to_file = $include_path ?: $path_to_file;

            if (DIRECTORY_SEPARATOR === '/') {
                $is_path_relative = $path_to_file[0] !== DIRECTORY_SEPARATOR;
            } else {
                $is_path_relative = !preg_match('~^[A-Z]:\\\\~i', $path_to_file);
            }

            if ($is_path_relative) {
                $path_to_file = $config->base_dir . DIRECTORY_SEPARATOR . $path_to_file;
            }
        } else {
            $path_to_file = IncludeAnalyzer::getPathTo(
                $stmt->expr,
                null,
                null,
                $file_storage->file_path,
                $config
            );
        }

        if ($path_to_file) {
            $path_to_file = IncludeAnalyzer::normalizeFilePath($path_to_file);

            if ($file_storage->file_path === $path_to_file) {
                return;
            }

            if ($codebase->fileExists($path_to_file)) {
                if ($scan_deep) {
                    $codebase->scanner->addFileToDeepScan($path_to_file);
                } else {
                    $codebase->scanner->addFileToShallowScan($path_to_file);
                }

                $file_storage->required_file_paths[strtolower($path_to_file)] = $path_to_file;

                return;
            }
        }
    }
}
