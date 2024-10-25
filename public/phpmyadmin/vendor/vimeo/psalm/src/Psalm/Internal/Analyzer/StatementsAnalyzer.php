<?php

namespace Psalm\Internal\Analyzer;

use InvalidArgumentException;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\Statements\Block\DoAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\ForAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfElseAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\SwitchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\TryAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\WhileAnalyzer;
use Psalm\Internal\Analyzer\Statements\BreakAnalyzer;
use Psalm\Internal\Analyzer\Statements\ContinueAnalyzer;
use Psalm\Internal\Analyzer\Statements\EchoAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ClassConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\GlobalAnalyzer;
use Psalm\Internal\Analyzer\Statements\ReturnAnalyzer;
use Psalm\Internal\Analyzer\Statements\StaticAnalyzer;
use Psalm\Internal\Analyzer\Statements\ThrowAnalyzer;
use Psalm\Internal\Analyzer\Statements\UnsetAnalyzer;
use Psalm\Internal\Analyzer\Statements\UnusedAssignmentRemover;
use Psalm\Internal\Codebase\DataFlowGraph;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\ReferenceConstraint;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Issue\ComplexFunction;
use Psalm\Issue\ComplexMethod;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\MissingDocblockType;
use Psalm\Issue\Trace;
use Psalm\Issue\UndefinedDocblockClass;
use Psalm\Issue\UndefinedTrace;
use Psalm\Issue\UnevaluatedCode;
use Psalm\Issue\UnrecognizedStatement;
use Psalm\Issue\UnusedForeachValue;
use Psalm\Issue\UnusedVariable;
use Psalm\IssueBuffer;
use Psalm\NodeTypeProvider;
use Psalm\Plugin\EventHandler\Event\AfterStatementAnalysisEvent;
use Psalm\Type;
use UnexpectedValueException;

use function array_change_key_case;
use function array_column;
use function array_combine;
use function array_keys;
use function array_merge;
use function array_search;
use function assert;
use function count;
use function fwrite;
use function get_class;
use function in_array;
use function is_string;
use function preg_split;
use function reset;
use function round;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;
use function trim;

use const PREG_SPLIT_NO_EMPTY;
use const STDERR;

/**
 * @internal
 */
class StatementsAnalyzer extends SourceAnalyzer
{
    /**
     * @var SourceAnalyzer
     */
    protected $source;

    /**
     * @var FileAnalyzer
     */
    protected $file_analyzer;

    /**
     * @var Codebase
     */
    protected $codebase;

    /**
     * @var array<string, CodeLocation>
     */
    private $all_vars = [];

    /**
     * @var array<string, int>
     */
    private $var_branch_points = [];

    /**
     * Possibly undefined variables should be initialised if we're altering code
     *
     * @var array<string, int>|null
     */
    private $vars_to_initialize;

    /**
     * @var array<string, FunctionAnalyzer>
     */
    private $function_analyzers = [];

    /**
     * @var array<string, array{0: string, 1: CodeLocation}>
     */
    private $unused_var_locations = [];

    /**
     * @var ?array<string, bool>
     */
    public $byref_uses;

    /**
     * @var ParsedDocblock|null
     */
    private $parsed_docblock;

    /**
     * @var ?string
     */
    private $fake_this_class;

    /** @var NodeDataProvider */
    public $node_data;

    /** @var ?DataFlowGraph */
    public $data_flow_graph;

    /**
     * Locations of foreach values
     *
     * Used to discern ordinary UnusedVariables from UnusedForeachValues
     *
     * @var array<string, list<CodeLocation>>
     * @psalm-internal Psalm\Internal\Analyzer
     */
    public $foreach_var_locations = [];

    public function __construct(SourceAnalyzer $source, NodeDataProvider $node_data)
    {
        $this->source = $source;
        $this->file_analyzer = $source->getFileAnalyzer();
        $this->codebase = $source->getCodebase();
        $this->node_data = $node_data;

        if ($this->codebase->taint_flow_graph) {
            $this->data_flow_graph = new TaintFlowGraph();
        } elseif ($this->codebase->find_unused_variables) {
            $this->data_flow_graph = new VariableUseGraph();
        }
    }

    /**
     * Checks an array of statements for validity
     *
     * @param  array<PhpParser\Node\Stmt>   $stmts
     *
     * @return null|false
     */
    public function analyze(
        array $stmts,
        Context $context,
        ?Context $global_context = null,
        bool $root_scope = false
    ): ?bool {
        if (!$stmts) {
            return null;
        }

        // hoist functions to the top
        $this->hoistFunctions($stmts, $context);

        $project_analyzer = $this->getFileAnalyzer()->project_analyzer;
        $codebase = $project_analyzer->getCodebase();

        if ($codebase->config->hoist_constants) {
            self::hoistConstants($this, $stmts, $context);
        }

        foreach ($stmts as $stmt) {
            if (self::analyzeStatement($this, $stmt, $context, $global_context) === false) {
                return false;
            }
        }

        if ($root_scope
            && !$context->collect_initializations
            && !$context->collect_mutations
            && $codebase->find_unused_variables
            && $context->check_variables
        ) {
            //var_dump($this->data_flow_graph);
            $this->checkUnreferencedVars($stmts, $context);
        }

        if ($codebase->alter_code && $root_scope && $this->vars_to_initialize) {
            $file_contents = $codebase->getFileContents($this->getFilePath());

            foreach ($this->vars_to_initialize as $var_id => $branch_point) {
                $newline_pos = (int)strrpos($file_contents, "\n", $branch_point - strlen($file_contents)) + 1;
                $indentation = substr($file_contents, $newline_pos, $branch_point - $newline_pos);
                FileManipulationBuffer::add($this->getFilePath(), [
                    new FileManipulation($branch_point, $branch_point, $var_id . ' = null;' . "\n" . $indentation),
                ]);
            }
        }

        if ($root_scope
            && $this->data_flow_graph instanceof TaintFlowGraph
            && $this->codebase->taint_flow_graph
            && $codebase->config->trackTaintsInPath($this->getFilePath())
        ) {
            $this->codebase->taint_flow_graph->addGraph($this->data_flow_graph);
        }

        return null;
    }

    /**
     * @param  array<PhpParser\Node\Stmt>   $stmts
     */
    private function hoistFunctions(array $stmts, Context $context): void
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_name = strtolower($stmt->name->name);

                if ($ns = $this->getNamespace()) {
                    $fq_function_name = strtolower($ns) . '\\' . $function_name;
                } else {
                    $fq_function_name = $function_name;
                }

                if ($this->data_flow_graph
                    && $this->codebase->find_unused_variables
                ) {
                    foreach ($stmt->stmts as $function_stmt) {
                        if ($function_stmt instanceof PhpParser\Node\Stmt\Global_) {
                            foreach ($function_stmt->vars as $var) {
                                if (!$var instanceof PhpParser\Node\Expr\Variable
                                    || !is_string($var->name)
                                ) {
                                    continue;
                                }

                                $var_id = '$' . $var->name;

                                if ($var_id !== '$argv' && $var_id !== '$argc') {
                                    $context->byref_constraints[$var_id] = new ReferenceConstraint();
                                }
                            }
                        }
                    }
                }

                try {
                    $function_analyzer = new FunctionAnalyzer($stmt, $this->source);
                    $this->function_analyzers[$fq_function_name] = $function_analyzer;
                } catch (UnexpectedValueException $e) {
                    // do nothing
                }
            }
        }
    }

    /**
     * @param  array<PhpParser\Node\Stmt>   $stmts
     */
    private static function hoistConstants(
        StatementsAnalyzer $statements_analyzer,
        array $stmts,
        Context $context
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                foreach ($stmt->consts as $const) {
                    ConstFetchAnalyzer::setConstType(
                        $statements_analyzer,
                        $const->name->name,
                        SimpleTypeInferer::infer(
                            $codebase,
                            $statements_analyzer->node_data,
                            $const->value,
                            $statements_analyzer->getAliases(),
                            $statements_analyzer
                        ) ?? Type::getMixed(),
                        $context
                    );
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Expression
                && $stmt->expr instanceof PhpParser\Node\Expr\FuncCall
                && $stmt->expr->name instanceof PhpParser\Node\Name
                && $stmt->expr->name->parts === ['define']
                && isset($stmt->expr->getArgs()[1])
            ) {
                $const_name = ConstFetchAnalyzer::getConstName(
                    $stmt->expr->getArgs()[0]->value,
                    $statements_analyzer->node_data,
                    $codebase,
                    $statements_analyzer->getAliases()
                );

                if ($const_name !== null) {
                    ConstFetchAnalyzer::setConstType(
                        $statements_analyzer,
                        $const_name,
                        SimpleTypeInferer::infer(
                            $codebase,
                            $statements_analyzer->node_data,
                            $stmt->expr->getArgs()[1]->value,
                            $statements_analyzer->getAliases(),
                            $statements_analyzer
                        ) ?? Type::getMixed(),
                        $context
                    );
                }
            }
        }
    }

    /**
     * @return false|null
     */
    private static function analyzeStatement(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt $stmt,
        Context $context,
        ?Context $global_context
    ): ?bool {
        $ignore_variable_property = false;
        $ignore_variable_method = false;

        $codebase = $statements_analyzer->getCodebase();

        if ($statements_analyzer->getProjectAnalyzer()->debug_lines) {
            fwrite(STDERR, $statements_analyzer->getFilePath() . ':' . $stmt->getLine() . "\n");
        }

        $new_issues = null;
        $traced_variables = [];

        if ($docblock = $stmt->getDocComment()) {
            $statements_analyzer->parseStatementDocblock($docblock, $stmt, $context);

            if (isset($statements_analyzer->parsed_docblock->tags['psalm-trace'])) {
                foreach ($statements_analyzer->parsed_docblock->tags['psalm-trace'] as $traced_variable_line) {
                    $possible_traced_variable_names = preg_split(
                        '/(?:\s*,\s*|\s+)/',
                        $traced_variable_line,
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );
                    if ($possible_traced_variable_names) {
                        $traced_variables = array_merge($traced_variables, $possible_traced_variable_names);
                    }
                }
            }

            if (isset($statements_analyzer->parsed_docblock->tags['psalm-ignore-variable-method'])) {
                $context->ignore_variable_method = $ignore_variable_method = true;
            }

            if (isset($statements_analyzer->parsed_docblock->tags['psalm-ignore-variable-property'])) {
                $context->ignore_variable_property = $ignore_variable_property = true;
            }

            if (isset($statements_analyzer->parsed_docblock->tags['psalm-suppress'])) {
                $suppressed = $statements_analyzer->parsed_docblock->tags['psalm-suppress'];
                if ($suppressed) {
                    $new_issues = [];

                    foreach ($suppressed as $offset => $suppress_entry) {
                        foreach (DocComment::parseSuppressList($suppress_entry) as $issue_offset => $issue_type) {
                            $new_issues[$issue_offset + $offset] = $issue_type;
                        }
                    }

                    if ($codebase->track_unused_suppressions
                        && (
                            (count($new_issues) === 1) // UnusedPsalmSuppress by itself should be marked as unused
                            || !in_array("UnusedPsalmSuppress", $new_issues)
                        )
                    ) {
                        foreach ($new_issues as $offset => $issue_type) {
                            if ($issue_type === 'InaccessibleMethod') {
                                continue;
                            }

                            IssueBuffer::addUnusedSuppression(
                                $statements_analyzer->getFilePath(),
                                $offset,
                                $issue_type
                            );
                        }
                    }

                    $statements_analyzer->addSuppressedIssues($new_issues);
                }
            }

            if (isset($statements_analyzer->parsed_docblock->combined_tags['var'])
                && !($stmt instanceof PhpParser\Node\Stmt\Expression
                    && $stmt->expr instanceof PhpParser\Node\Expr\Assign)
                && !$stmt instanceof PhpParser\Node\Stmt\Foreach_
                && !$stmt instanceof PhpParser\Node\Stmt\Return_
            ) {
                $file_path = $statements_analyzer->getRootFilePath();

                $file_storage_provider = $codebase->file_storage_provider;

                $file_storage = $file_storage_provider->get($file_path);

                $template_type_map = $statements_analyzer->getTemplateTypeMap();

                $var_comments = [];

                try {
                    $var_comments = $codebase->config->disable_var_parsing
                        ? []
                        : CommentAnalyzer::arrayToDocblocks(
                            $docblock,
                            $statements_analyzer->parsed_docblock,
                            $statements_analyzer->getSource(),
                            $statements_analyzer->getAliases(),
                            $template_type_map,
                            $file_storage->type_aliases
                        );
                } catch (IncorrectDocblockException $e) {
                    IssueBuffer::maybeAdd(
                        new MissingDocblockType(
                            $e->getMessage(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        )
                    );
                } catch (DocblockParseException $e) {
                    IssueBuffer::maybeAdd(
                        new InvalidDocblock(
                            $e->getMessage(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        )
                    );
                }

                foreach ($var_comments as $var_comment) {
                    AssignmentAnalyzer::assignTypeFromVarDocblock(
                        $statements_analyzer,
                        $stmt,
                        $var_comment,
                        $context
                    );

                    if ($var_comment->var_id === '$this'
                        && $var_comment->type
                        && $codebase->classExists((string)$var_comment->type)
                    ) {
                        $statements_analyzer->setFQCLN((string)$var_comment->type);
                    }
                }
            }
        } else {
            $statements_analyzer->parsed_docblock = null;
        }

        if ($context->has_returned
            && !$context->collect_initializations
            && !$context->collect_mutations
            && !($stmt instanceof PhpParser\Node\Stmt\Nop)
            && !($stmt instanceof PhpParser\Node\Stmt\Function_)
            && !($stmt instanceof PhpParser\Node\Stmt\Class_)
            && !($stmt instanceof PhpParser\Node\Stmt\Interface_)
            && !($stmt instanceof PhpParser\Node\Stmt\Trait_)
            && !($stmt instanceof PhpParser\Node\Stmt\HaltCompiler)
        ) {
            if ($codebase->find_unused_variables) {
                if (IssueBuffer::accepts(
                    new UnevaluatedCode(
                        'Expressions after return/throw/continue',
                        new CodeLocation($statements_analyzer->source, $stmt)
                    ),
                    $statements_analyzer->source->getSuppressedIssues()
                )) {
                    return null;
                }
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Stmt\If_) {
            if (IfElseAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
            if (TryAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
            if (ForAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
            if (ForeachAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
            if (WhileAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
            if (DoAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Const_) {
            ConstFetchAnalyzer::analyzeConstAssignment($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Unset_) {
            UnsetAnalyzer::analyze($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Return_) {
            ReturnAnalyzer::analyze($statements_analyzer, $stmt, $context);
            $context->has_returned = true;
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Throw_) {
            ThrowAnalyzer::analyze($statements_analyzer, $stmt, $context);
            $context->has_returned = true;
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
            SwitchAnalyzer::analyze($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Break_) {
            BreakAnalyzer::analyze($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Continue_) {
            ContinueAnalyzer::analyze($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Static_) {
            StaticAnalyzer::analyze($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
            if (EchoAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            FunctionAnalyzer::analyzeStatement($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Expression) {
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $stmt->expr,
                $context,
                false,
                $global_context,
                true
            ) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\InlineHTML) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Global_) {
            GlobalAnalyzer::analyze($statements_analyzer, $stmt, $context, $global_context);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
            InstancePropertyAssignmentAnalyzer::analyzeStatement($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
            ClassConstFetchAnalyzer::analyzeClassConstAssignment($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            try {
                $class_analyzer = new ClassAnalyzer(
                    $stmt,
                    $statements_analyzer->source,
                    $stmt->name->name ?? null
                );

                $class_analyzer->analyze(null, $global_context);
            } catch (InvalidArgumentException $e) {
                // disregard this exception, we'll likely see it elsewhere in the form
                // of an issue
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
            TraitAnalyzer::analyze($statements_analyzer, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Nop) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Goto_) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Label) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Declare_) {
            foreach ($stmt->declares as $declaration) {
                if ((string) $declaration->key === 'strict_types'
                    && $declaration->value instanceof PhpParser\Node\Scalar\LNumber
                    && $declaration->value->value === 1
                ) {
                    $context->strict_types = true;
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\HaltCompiler) {
            $context->has_returned = true;
        } else {
            if (IssueBuffer::accepts(
                new UnrecognizedStatement(
                    'Psalm does not understand ' . get_class($stmt),
                    new CodeLocation($statements_analyzer->source, $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $codebase = $statements_analyzer->getCodebase();

        $event = new AfterStatementAnalysisEvent(
            $stmt,
            $context,
            $statements_analyzer,
            $codebase,
            []
        );

        if ($codebase->config->eventDispatcher->dispatchAfterStatementAnalysis($event) === false) {
            return false;
        }

        $file_manipulations = $event->getFileReplacements();
        if ($file_manipulations) {
            FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
        }

        if ($new_issues) {
            $statements_analyzer->removeSuppressedIssues($new_issues);
        }

        if ($ignore_variable_property) {
            $context->ignore_variable_property = false;
        }

        if ($ignore_variable_method) {
            $context->ignore_variable_method = false;
        }

        foreach ($traced_variables as $traced_variable) {
            if (isset($context->vars_in_scope[$traced_variable])) {
                IssueBuffer::maybeAdd(
                    new Trace(
                        $traced_variable . ': ' . $context->vars_in_scope[$traced_variable]->getId(),
                        new CodeLocation($statements_analyzer->source, $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } else {
                IssueBuffer::maybeAdd(
                    new UndefinedTrace(
                        'Attempt to trace undefined variable ' . $traced_variable,
                        new CodeLocation($statements_analyzer->source, $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }
        }

        return null;
    }

    private function parseStatementDocblock(
        PhpParser\Comment\Doc $docblock,
        PhpParser\Node\Stmt $stmt,
        Context $context
    ): void {
        $codebase = $this->getCodebase();

        try {
            $this->parsed_docblock = DocComment::parsePreservingLength($docblock);
        } catch (DocblockParseException $e) {
            IssueBuffer::maybeAdd(
                new InvalidDocblock(
                    $e->getMessage(),
                    new CodeLocation($this->getSource(), $stmt, null, true)
                )
            );

            $this->parsed_docblock = null;
        }

        $comments = $this->parsed_docblock;

        if (isset($comments->tags['psalm-scope-this'])) {
            $trimmed = trim(reset($comments->tags['psalm-scope-this']));

            if (!$codebase->classExists($trimmed)) {
                IssueBuffer::maybeAdd(
                    new UndefinedDocblockClass(
                        'Scope class ' . $trimmed . ' does not exist',
                        new CodeLocation($this->getSource(), $stmt, null, true),
                        $trimmed
                    )
                );
            } else {
                $this_type = Type::parseString($trimmed);
                $context->self = $trimmed;
                $context->vars_in_scope['$this'] = $this_type;
                $this->setFQCLN($trimmed);
            }
        }
    }

    /**
     * @param  array<PhpParser\Node\Stmt>   $stmts
     */
    public function checkUnreferencedVars(array $stmts, Context $context): void
    {
        $source = $this->getSource();
        $codebase = $source->getCodebase();
        $function_storage = $source instanceof FunctionLikeAnalyzer ? $source->getFunctionLikeStorage($this) : null;
        $var_list = array_column($this->unused_var_locations, 0);
        $loc_list = array_column($this->unused_var_locations, 1);

        $project_analyzer = $this->getProjectAnalyzer();

        $unused_var_remover = new UnusedAssignmentRemover();

        if ($this->data_flow_graph instanceof VariableUseGraph
            && $codebase->config->limit_method_complexity
            && $source instanceof FunctionLikeAnalyzer
            && !$source instanceof ClosureAnalyzer
            && $function_storage
            && $function_storage->location
        ) {
            [$count, , $unique_destinations, $mean] = $this->data_flow_graph->getEdgeStats();

            $average_destination_branches_converging = $unique_destinations > 0 ? $count / $unique_destinations : 0;

            if ($count > $codebase->config->max_graph_size
                && $mean > $codebase->config->max_avg_path_length
                && $average_destination_branches_converging > 1.1
            ) {
                if ($source instanceof FunctionAnalyzer) {
                    IssueBuffer::maybeAdd(
                        new ComplexFunction(
                            'This function’s complexity is greater than the project limit'
                                . ' (method graph size = ' . $count .', average path length = ' . round($mean). ')',
                            $function_storage->location
                        ),
                        $this->getSuppressedIssues()
                    );
                } elseif ($source instanceof MethodAnalyzer) {
                    IssueBuffer::maybeAdd(
                        new ComplexMethod(
                            'This method’s complexity is greater than the project limit'
                                . ' (method graph size = ' . $count .', average path length = ' . round($mean) . ')',
                            $function_storage->location
                        ),
                        $this->getSuppressedIssues()
                    );
                }
            }
        }

        foreach ($this->unused_var_locations as [$var_id, $original_location]) {
            if (strpos($var_id, '$_') === 0) {
                continue;
            }

            if ($function_storage) {
                $param_index = array_search(substr($var_id, 1), array_keys($function_storage->param_lookup));
                if ($param_index !== false) {
                    $param = $function_storage->params[$param_index];

                    if ($param->location
                        && ($original_location->raw_file_end === $param->location->raw_file_end
                            || $param->by_ref)
                    ) {
                        continue;
                    }
                }
            }

            $assignment_node = DataFlowNode::getForAssignment($var_id, $original_location);

            if (!isset($this->byref_uses[$var_id])
                && !isset($context->vars_from_global[$var_id])
                && !VariableFetchAnalyzer::isSuperGlobal($var_id)
                && $this->data_flow_graph instanceof VariableUseGraph
                && !$this->data_flow_graph->isVariableUsed($assignment_node)
            ) {
                $is_foreach_var = false;

                if (isset($this->foreach_var_locations[$var_id])) {
                    foreach ($this->foreach_var_locations[$var_id] as $location) {
                        if ($location->raw_file_start === $original_location->raw_file_start) {
                            $is_foreach_var = true;
                            break;
                        }
                    }
                }

                if ($is_foreach_var) {
                    $issue = new UnusedForeachValue(
                        $var_id . ' is never referenced or the value is not used',
                        $original_location
                    );
                } else {
                    $issue = new UnusedVariable(
                        $var_id . ' is never referenced or the value is not used',
                        $original_location
                    );
                }

                if ($codebase->alter_code
                    && $issue instanceof UnusedVariable
                    && !$unused_var_remover->checkIfVarRemoved($var_id, $original_location)
                    && isset($project_analyzer->getIssuesToFix()['UnusedVariable'])
                    && !IssueBuffer::isSuppressed($issue, $this->getSuppressedIssues())
                ) {
                    $unused_var_remover->findUnusedAssignment(
                        $this->getCodebase(),
                        $stmts,
                        array_combine($var_list, $loc_list),
                        $var_id,
                        $original_location
                    );
                }

                IssueBuffer::maybeAdd(
                    $issue,
                    $this->getSuppressedIssues(),
                    $issue instanceof UnusedVariable
                );
            }
        }
    }

    public function hasVariable(string $var_name): bool
    {
        return isset($this->all_vars[$var_name]);
    }

    public function registerVariable(string $var_id, CodeLocation $location, ?int $branch_point): void
    {
        $this->all_vars[$var_id] = $location;

        if ($branch_point) {
            $this->var_branch_points[$var_id] = $branch_point;
        }

        $this->registerVariableAssignment($var_id, $location);
    }

    public function registerVariableAssignment(string $var_id, CodeLocation $location): void
    {
        $this->unused_var_locations[$location->getHash()] = [$var_id, $location];
    }

    /**
     * @return array<string, array{0: string, 1: CodeLocation}>
     */
    public function getUnusedVarLocations(): array
    {
        return $this->unused_var_locations;
    }

    public function registerPossiblyUndefinedVariable(
        string $undefined_var_id,
        PhpParser\Node\Expr\Variable $stmt
    ): void {
        if (!$this->data_flow_graph) {
            return;
        }

        $use_location = new CodeLocation($this->getSource(), $stmt);
        $use_node = DataFlowNode::getForAssignment($undefined_var_id, $use_location);

        $stmt_type = $this->node_data->getType($stmt);

        if ($stmt_type) {
            $stmt_type->parent_nodes[$use_node->id] = $use_node;
        }

        foreach ($this->unused_var_locations as [$var_id, $original_location]) {
            if ($var_id === $undefined_var_id) {
                $parent_node = DataFlowNode::getForAssignment($var_id, $original_location);

                $this->data_flow_graph->addPath($parent_node, $use_node, '=');
            }
        }
    }

    /**
     * @return array<string, DataFlowNode>
     */
    public function getParentNodesForPossiblyUndefinedVariable(string $undefined_var_id): array
    {
        if (!$this->data_flow_graph) {
            return [];
        }

        $parent_nodes = [];

        foreach ($this->unused_var_locations as [$var_id, $original_location]) {
            if ($var_id === $undefined_var_id) {
                $assignment_node = DataFlowNode::getForAssignment($var_id, $original_location);
                $parent_nodes[$assignment_node->id] = $assignment_node;
            }
        }

        return $parent_nodes;
    }

    /**
     * The first appearance of the variable in this set of statements being evaluated
     */
    public function getFirstAppearance(string $var_id): ?CodeLocation
    {
        return $this->all_vars[$var_id] ?? null;
    }

    public function getBranchPoint(string $var_id): ?int
    {
        return $this->var_branch_points[$var_id] ?? null;
    }

    public function addVariableInitialization(string $var_id, int $branch_point): void
    {
        $this->vars_to_initialize[$var_id] = $branch_point;
    }

    public function getFileAnalyzer(): FileAnalyzer
    {
        return $this->file_analyzer;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }

    /**
     * @return array<string, FunctionAnalyzer>
     */
    public function getFunctionAnalyzers(): array
    {
        return $this->function_analyzers;
    }

    /**
     * @param array<string, bool> $byref_uses
     */
    public function setByRefUses(array $byref_uses): void
    {
        $this->byref_uses = $byref_uses;
    }

    /**
     * @return array<string, array<array-key, CodeLocation>>
     */
    public function getUncaughtThrows(Context $context): array
    {
        $uncaught_throws = [];

        if ($context->collect_exceptions) {
            if ($context->possibly_thrown_exceptions) {
                $config = $this->codebase->config;
                $ignored_exceptions = array_change_key_case(
                    $context->is_global ?
                        $config->ignored_exceptions_in_global_scope :
                        $config->ignored_exceptions
                );
                $ignored_exceptions_and_descendants = array_change_key_case(
                    $context->is_global ?
                        $config->ignored_exceptions_and_descendants_in_global_scope :
                        $config->ignored_exceptions_and_descendants
                );

                foreach ($context->possibly_thrown_exceptions as $possibly_thrown_exception => $codelocations) {
                    if (isset($ignored_exceptions[strtolower($possibly_thrown_exception)])) {
                        continue;
                    }

                    $is_expected = false;

                    foreach ($ignored_exceptions_and_descendants as $expected_exception => $_) {
                        try {
                            if ($expected_exception === strtolower($possibly_thrown_exception)
                                || $this->codebase->classExtends($possibly_thrown_exception, $expected_exception)
                                || $this->codebase->interfaceExtends($possibly_thrown_exception, $expected_exception)
                            ) {
                                $is_expected = true;
                                break;
                            }
                        } catch (InvalidArgumentException $e) {
                            $is_expected = true;
                            break;
                        }
                    }

                    if (!$is_expected) {
                        $uncaught_throws[$possibly_thrown_exception] = $codelocations;
                    }
                }
            }
        }

        return $uncaught_throws;
    }

    public function getFunctionAnalyzer(string $function_id): ?FunctionAnalyzer
    {
        return $this->function_analyzers[$function_id] ?? null;
    }

    public function getParsedDocblock(): ?ParsedDocblock
    {
        return $this->parsed_docblock;
    }

    public function getFQCLN(): ?string
    {
        if ($this->fake_this_class) {
            return $this->fake_this_class;
        }

        return parent::getFQCLN();
    }

    public function setFQCLN(string $fake_this_class): void
    {
        $this->fake_this_class = $fake_this_class;
    }

    /**
     * @return NodeDataProvider
     */
    public function getNodeTypeProvider(): NodeTypeProvider
    {
        return $this->node_data;
    }

    public function getFullyQualifiedFunctionMethodOrNamespaceName(): ?string
    {
        if ($this->source instanceof MethodAnalyzer) {
            $fqcn = $this->getFQCLN();
            $method_name = $this->source->getFunctionLikeStorage($this)->cased_name;
            assert($fqcn !== null && $method_name !== null);

            return "$fqcn::$method_name";
        }

        if ($this->source instanceof FunctionAnalyzer) {
            $namespace = $this->getNamespace();
            $namespace = $namespace === "" ? "" : "$namespace\\";
            $function_name = $this->source->getFunctionLikeStorage($this)->cased_name;
            assert($function_name !== null);

            return "{$namespace}{$function_name}";
        }

        return $this->getNamespace();
    }
}
